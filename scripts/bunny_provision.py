#!/usr/bin/env python3
"""
bunny_provision.py - one-time BunnyCDN setup from the account API key.

Turns a bare ``BUNNY_API_KEY`` in the project .env into a fully working asset
CDN: it ensures a storage zone and a pull zone exist on the account (creating
them if missing), reads the storage-zone password back via the Core API, and
writes the four values the asset pipeline actually needs into the .env:

    BUNNY_STORAGE_ZONE   (storage zone name)
    BUNNY_STORAGE_KEY    (storage zone password / FTP key)
    BUNNY_STORAGE_HOST   (region storage endpoint, e.g. ny.storage.bunnycdn.com)
    BUNNY_PULL_URL       (public pull-zone base, e.g. https://aipu-assets.b-cdn.net)

The account API key (https://api.bunny.net) is *not* the same thing as the
storage-zone password (used as the AccessKey for edge-storage PUT/GET). This
script bridges the two so the operator only ever has to paste the account key.

Everything is idempotent: re-running reuses the existing zones and just
re-syncs the .env. The account key itself is never written into the four
pipeline vars and is never printed.

CLI
---
python3 bunny_provision.py \
        [--zone aipu-assets] [--region NY] [--tier standard|edge] \
        [--enable] [--dry-run] [--env /var/www/aipuunlimited.com/.env]

Prints a JSON result on stdout (storage key redacted).
"""

import argparse
import json
import os
import sys
import urllib.error
import urllib.request

API_BASE = "https://api.bunny.net"

# Region code -> edge-storage endpoint host. The "default" (DE/Falkenstein)
# zone uses the bare host; every other region prefixes it. Codes mirror the
# storage regions Bunny exposes for the *storage* endpoint specifically.
_STORAGE_HOSTS = {
    "DE": "storage.bunnycdn.com",
    "UK": "uk.storage.bunnycdn.com",
    "NY": "ny.storage.bunnycdn.com",
    "LA": "la.storage.bunnycdn.com",
    "SG": "sg.storage.bunnycdn.com",
    "SE": "se.storage.bunnycdn.com",
    "BR": "br.storage.bunnycdn.com",
    "JH": "jh.storage.bunnycdn.com",
    "SYD": "syd.storage.bunnycdn.com",
}
DEFAULT_STORAGE_HOST = "storage.bunnycdn.com"


def _storage_host(region):
    return _STORAGE_HOSTS.get((region or "DE").upper(), DEFAULT_STORAGE_HOST)


# --------------------------------------------------------------------------- env

def _find_env(explicit=None):
    candidates = [
        explicit,
        "/var/www/aipuunlimited.com/.env",
        "/var/www/aipuunlimited.com/htdocs/.env",
    ]
    for p in candidates:
        if p and os.path.isfile(p):
            return os.path.abspath(p)
    # default to the canonical secure location even if it doesn't exist yet
    return os.path.abspath(explicit or "/var/www/aipuunlimited.com/.env")


def _read_env(path):
    """Parse KEY=VALUE pairs (quotes stripped), matching cdn_config.load_env."""
    env = {}
    if not os.path.isfile(path):
        return env
    with open(path, "r", encoding="utf-8", errors="replace") as fh:
        for line in fh:
            s = line.strip()
            if not s or s.startswith("#") or "=" not in s:
                continue
            key, _, val = s.partition("=")
            key, val = key.strip(), val.strip()
            if len(val) >= 2 and val[0] == val[-1] and val[0] in ("'", '"'):
                val = val[1:-1]
            if key:
                env[key] = val
    return env


def _write_env_updates(path, updates):
    """Set/replace each KEY=VALUE in-place, preserving comments and order.
    Missing keys are appended. Returns the list of keys actually changed."""
    lines = []
    if os.path.isfile(path):
        with open(path, "r", encoding="utf-8", errors="replace") as fh:
            lines = fh.read().splitlines()

    remaining = dict(updates)
    changed = []
    out = []
    for line in lines:
        stripped = line.strip()
        if stripped and not stripped.startswith("#") and "=" in stripped:
            key = stripped.split("=", 1)[0].strip()
            if key in remaining:
                new_val = remaining.pop(key)
                new_line = "%s=%s" % (key, new_val)
                if new_line != line:
                    changed.append(key)
                out.append(new_line)
                continue
        out.append(line)

    for key, val in remaining.items():
        out.append("%s=%s" % (key, val))
        changed.append(key)

    text = "\n".join(out)
    if not text.endswith("\n"):
        text += "\n"
    tmp = path + ".tmp"
    with open(tmp, "w", encoding="utf-8") as fh:
        fh.write(text)
    os.replace(tmp, path)
    try:
        os.chmod(path, 0o600)
    except OSError:
        pass
    return changed


# ----------------------------------------------------------------------- bunny api

class BunnyError(Exception):
    pass


def _api(method, path, key, body=None):
    url = API_BASE + path
    data = json.dumps(body).encode("utf-8") if body is not None else None
    req = urllib.request.Request(url, data=data, method=method)
    req.add_header("AccessKey", key)
    req.add_header("Accept", "application/json")
    if data is not None:
        req.add_header("Content-Type", "application/json")
    try:
        with urllib.request.urlopen(req, timeout=60) as resp:
            raw = resp.read().decode("utf-8", "replace")
            if not raw.strip():
                return {}
            return json.loads(raw)
    except urllib.error.HTTPError as exc:
        detail = ""
        try:
            detail = exc.read().decode("utf-8", "replace")
        except Exception:
            pass
        raise BunnyError("%s %s -> HTTP %s %s" % (method, path, exc.code, detail[:300]))
    except Exception as exc:
        raise BunnyError("%s %s -> %s" % (method, path, exc))


def _list_storage_zones(key):
    data = _api("GET", "/storagezone", key)
    return data if isinstance(data, list) else data.get("Items", [])


def _list_pull_zones(key):
    data = _api("GET", "/pullzone", key)
    return data if isinstance(data, list) else data.get("Items", [])


def _find_storage_zone(zones, name):
    for z in zones:
        if (z.get("Name") or "").lower() == name.lower():
            return z
    return None


def _ensure_storage_zone(key, name, region, tier, dry_run, result):
    existing = _find_storage_zone(_list_storage_zones(key), name)
    if existing:
        result["storage_zone_created"] = False
        return existing
    if dry_run:
        result["storage_zone_created"] = "dry-run"
        return {"Name": name, "Region": region, "Password": "", "Id": None}
    payload = {"Name": name, "Region": region, "ZoneTier": 0 if tier == "standard" else 1}
    created = _api("POST", "/storagezone", key, payload)
    result["storage_zone_created"] = True
    return created


def _ensure_pull_zone(key, name, storage_zone_id, dry_run, result):
    for p in _list_pull_zones(key):
        if (p.get("Name") or "").lower() == name.lower():
            result["pull_zone_created"] = False
            return p
    if dry_run:
        result["pull_zone_created"] = "dry-run"
        return {"Name": name, "StorageZoneId": storage_zone_id, "Hostnames": []}
    payload = {"Name": name, "OriginType": 2, "StorageZoneId": storage_zone_id}
    created = _api("POST", "/pullzone", key, payload)
    result["pull_zone_created"] = True
    return created


def _pull_hostname(pull_zone, zone_name):
    """Prefer a system .b-cdn.net hostname; fall back to the default scheme."""
    hosts = pull_zone.get("Hostnames") or []
    system = [h.get("Value") for h in hosts if h.get("IsSystemHostname") and h.get("Value")]
    if system:
        return system[0]
    any_host = [h.get("Value") for h in hosts if h.get("Value")]
    if any_host:
        return any_host[0]
    return "%s.b-cdn.net" % zone_name


# ---------------------------------------------------------------------------- main

def main():
    ap = argparse.ArgumentParser(description="Provision BunnyCDN from the account API key.")
    ap.add_argument("--zone", default="aipu-assets", help="storage + pull zone name")
    ap.add_argument("--region", default="NY", help="main storage region code (e.g. NY, DE)")
    ap.add_argument("--tier", default="standard", choices=["standard", "edge"])
    ap.add_argument("--enable", action="store_true", help="also set CDN_ENABLED=1")
    ap.add_argument("--dry-run", action="store_true", help="don't create anything or write .env")
    ap.add_argument("--env", default=None, help="path to .env (default: secure parent-of-docroot)")
    args = ap.parse_args()

    result = {"ok": False, "zone": args.zone, "region": args.region, "warnings": []}

    env_path = _find_env(args.env)
    result["env_path"] = env_path
    env = _read_env(env_path)

    api_key = (env.get("BUNNY_API_KEY") or os.environ.get("BUNNY_API_KEY") or "").strip()
    if not api_key:
        result["error"] = "BUNNY_API_KEY not found in .env or environment"
        print(json.dumps(result))
        return 1

    try:
        sz = _ensure_storage_zone(api_key, args.zone, args.region, args.tier,
                                  args.dry_run, result)
        sz_id = sz.get("Id")
        # Re-fetch the zone so we get the Password even right after creation.
        password = sz.get("Password") or ""
        region = sz.get("Region") or args.region
        if not args.dry_run and (not password or not sz_id):
            sz = _find_storage_zone(_list_storage_zones(api_key), args.zone) or sz
            sz_id = sz.get("Id") or sz_id
            password = sz.get("Password") or password
            region = sz.get("Region") or region

        pz = _ensure_pull_zone(api_key, args.zone, sz_id, args.dry_run, result)
        pull_host = _pull_hostname(pz, args.zone)
    except BunnyError as exc:
        result["error"] = str(exc)
        print(json.dumps(result))
        return 1

    storage_host = _storage_host(region)
    pull_url = "https://" + pull_host.lstrip("/")

    result.update({
        "storage_zone": args.zone,
        "storage_host": storage_host,
        "pull_url": pull_url,
        "storage_zone_id": sz_id,
        "storage_key_present": bool(password),
    })

    updates = {
        "BUNNY_STORAGE_ZONE": args.zone,
        "BUNNY_STORAGE_KEY": password,
        "BUNNY_STORAGE_HOST": storage_host,
        "BUNNY_PULL_URL": pull_url,
    }
    if args.enable:
        updates["CDN_ENABLED"] = "1"

    if args.dry_run:
        result["ok"] = True
        result["would_write"] = sorted(updates.keys())
        print(json.dumps(result))
        return 0

    if not password:
        result["warnings"].append(
            "storage zone has no readable password yet; .env BUNNY_STORAGE_KEY left blank")
        updates.pop("BUNNY_STORAGE_KEY", None)

    try:
        changed = _write_env_updates(env_path, updates)
        result["env_changed"] = changed
        result["ok"] = bool(password)
    except Exception as exc:
        result["error"] = "could not write .env: %s" % exc
        print(json.dumps(result))
        return 1

    print(json.dumps(result))
    return 0 if result["ok"] else 1


if __name__ == "__main__":
    sys.exit(main())
