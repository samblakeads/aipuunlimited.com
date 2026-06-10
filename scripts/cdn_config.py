#!/usr/bin/env python3
"""
cdn_config.py - read BunnyCDN / asset-pipeline settings from the project .env.

Python build scripts have no .env loader, and we never want CDN secrets passed
on the command line (they would leak via `ps`/argv). This module reads the same
.env the PHP side uses (`ai_env()` in previews/ai/env.php) directly from disk so
credentials stay out of process arguments.

If the explicit storage vars are blank but a BUNNY_API_KEY is present, the
storage zone name, password, host and pull-zone URL are resolved on demand from
the Bunny Core API (https://api.bunny.net) and cached for the process. This lets
an operator paste only the account API key; full credentials are discovered as
soon as a zone exists on the account. Provision a zone with bunny_provision.py.

Public API:
    load_env(docroot=None)   -> dict of KEY -> value (cached)
    cdn_settings(docroot)    -> normalized dict for the pipeline
    env_value(key, default)  -> single value lookup
"""

import json
import os
import urllib.error
import urllib.request

# Mirrors the PHP candidate order (secure parent-of-docroot first).
_ENV_CANDIDATES = [
    "/var/www/aipuunlimited.com/.env",
    "/var/www/aipuunlimited.com/htdocs/.env",
]

_CACHE = None


def _candidate_paths(docroot=None):
    paths = []
    if docroot:
        paths.append(os.path.join(os.path.dirname(os.path.abspath(docroot)), ".env"))
        paths.append(os.path.join(docroot, ".env"))
    paths.extend(_ENV_CANDIDATES)
    seen, out = set(), []
    for p in paths:
        rp = os.path.abspath(p)
        if rp not in seen:
            seen.add(rp)
            out.append(rp)
    return out


def load_env(docroot=None, force=False):
    """Parse the first existing .env file into a dict. Cached after first read."""
    global _CACHE
    if _CACHE is not None and not force:
        return _CACHE
    env = {}
    for path in _candidate_paths(docroot):
        if not os.path.isfile(path):
            continue
        try:
            with open(path, "r", encoding="utf-8", errors="replace") as fh:
                for line in fh:
                    line = line.strip()
                    if not line or line.startswith("#") or "=" not in line:
                        continue
                    key, _, val = line.partition("=")
                    key = key.strip()
                    val = val.strip()
                    if len(val) >= 2 and val[0] == val[-1] and val[0] in ("'", '"'):
                        val = val[1:-1]
                    if key:
                        env[key] = val
            break  # first existing file wins, like the PHP loader
        except Exception:
            continue
    _CACHE = env
    return env


def env_value(key, default=None, docroot=None):
    env = load_env(docroot)
    val = env.get(key)
    return val if (val is not None and val != "") else default


def _truthy(v):
    return str(v).strip().lower() in ("1", "true", "yes", "on")


# --------------------------------------------------------------- API auto-resolve

_API_BASE = "https://api.bunny.net"
_RESOLVE_CACHE = None  # caches the Core-API lookup so we hit it at most once

# Storage region code -> edge-storage endpoint host (default zone is host-less).
_STORAGE_HOSTS = {
    "DE": "storage.bunnycdn.com", "UK": "uk.storage.bunnycdn.com",
    "NY": "ny.storage.bunnycdn.com", "LA": "la.storage.bunnycdn.com",
    "SG": "sg.storage.bunnycdn.com", "SE": "se.storage.bunnycdn.com",
    "BR": "br.storage.bunnycdn.com", "JH": "jh.storage.bunnycdn.com",
    "SYD": "syd.storage.bunnycdn.com",
}


def _api_get(path, api_key):
    req = urllib.request.Request(_API_BASE + path, method="GET")
    req.add_header("AccessKey", api_key)
    req.add_header("Accept", "application/json")
    with urllib.request.urlopen(req, timeout=15) as resp:
        raw = resp.read().decode("utf-8", "replace")
    data = json.loads(raw) if raw.strip() else []
    return data if isinstance(data, list) else data.get("Items", [])


def _resolve_from_api(api_key, want_zone):
    """Discover storage credentials for `want_zone` (or the first/only zone) via
    the Bunny Core API. Returns a partial settings dict, or {} on any failure or
    if the account simply has no zones yet. Cached for the process; never raises.
    """
    global _RESOLVE_CACHE
    if _RESOLVE_CACHE is not None:
        return _RESOLVE_CACHE
    out = {}
    try:
        zones = _api_get("/storagezone", api_key)
        zone = None
        if want_zone:
            for z in zones:
                if (z.get("Name") or "").lower() == want_zone.lower():
                    zone = z
                    break
        if zone is None and len(zones) == 1:
            zone = zones[0]
        if zone is not None:
            region = (zone.get("Region") or "DE").upper()
            out["storage_zone"] = zone.get("Name") or want_zone
            out["storage_key"] = zone.get("Password") or ""
            out["storage_host"] = _STORAGE_HOSTS.get(region, "storage.bunnycdn.com")
            pulls = zone.get("PullZones") or []
            for p in pulls:
                hosts = p.get("Hostnames") or []
                sys_hosts = [h.get("Value") for h in hosts
                             if h.get("IsSystemHostname") and h.get("Value")]
                any_hosts = [h.get("Value") for h in hosts if h.get("Value")]
                host = (sys_hosts or any_hosts or [None])[0]
                if host:
                    out["pull_url"] = "https://" + host.lstrip("/")
                    break
            if "pull_url" not in out and out["storage_zone"]:
                out["pull_url"] = "https://%s.b-cdn.net" % out["storage_zone"]
    except (urllib.error.URLError, urllib.error.HTTPError, ValueError, OSError):
        out = {}
    _RESOLVE_CACHE = out
    return out


def cdn_settings(docroot=None):
    """Normalized CDN settings. `enabled` is only True when a credential set is
    actually present, so a half-configured .env never attempts uploads.

    When the explicit storage vars are blank but a BUNNY_API_KEY is present, the
    zone name / password / host / pull-zone URL are resolved from the Bunny Core
    API (and cached). This degrades silently to "not configured" if the account
    has no zones yet or the network is unavailable."""
    env = load_env(docroot)
    zone = env.get("BUNNY_STORAGE_ZONE", "").strip()
    key = env.get("BUNNY_STORAGE_KEY", "").strip()
    pull = env.get("BUNNY_PULL_URL", "").strip().rstrip("/")
    host = (env.get("BUNNY_STORAGE_HOST", "").strip() or "storage.bunnycdn.com")
    prefix = env.get("BUNNY_REMOTE_PREFIX", "").strip().strip("/")
    enabled_flag = _truthy(env.get("CDN_ENABLED", "0"))
    api_key = env.get("BUNNY_API_KEY", "").strip()
    resolved_via_api = False

    # Fill any blank storage var from the account API key if we have one.
    if api_key and not (zone and key and pull):
        api = _resolve_from_api(api_key, zone)
        if api:
            zone = zone or api.get("storage_zone", "")
            key = key or api.get("storage_key", "")
            pull = pull or api.get("pull_url", "").rstrip("/")
            if not env.get("BUNNY_STORAGE_HOST", "").strip() and api.get("storage_host"):
                host = api["storage_host"]
            resolved_via_api = bool(zone and key and pull)

    configured = bool(zone and key and pull)
    return {
        "enabled": enabled_flag and configured,
        "configured": configured,
        "flag": enabled_flag,
        "storage_zone": zone,
        "storage_key": key,
        "storage_host": host,
        "pull_url": pull,
        "remote_prefix": prefix,
        "resolved_via_api": resolved_via_api,
    }


if __name__ == "__main__":
    s = cdn_settings()
    # never print the secret
    s_safe = dict(s)
    s_safe["storage_key"] = "***" if s_safe.get("storage_key") else ""
    print(json.dumps(s_safe, indent=2))
