#!/usr/bin/env python3
"""
asset_pipeline.py - shared asset optimization + BunnyCDN publishing for flows.

One module, used by both build_flow.py and kk_format.py so a flow and its kk/
package never drift. Everything is idempotent and degrades gracefully: if a
tool (pngquant/optipng/cwebp/Pillow/rcssmin/rjsmin) is missing, that step is
skipped with a warning instead of failing the build.

Public API
----------
optimize_dir(root, ...)            -> recompress PNG/JPG, make WebP siblings,
                                      minify CSS/JS in place. Returns a result dict.
upgrade_responsive_images(root...) -> wrap <img> in <picture>+WebP, add
                                      width/height + loading/decoding (Phase 4).
publish_bunny(root, slug, ...)     -> upload assets to a BunnyCDN storage zone
                                      under an immutable build-hash folder.
rewrite_asset_urls(root, base, ...)-> point asset URLs at the CDN pull zone
                                      (asset file extensions only; nav untouched).

CLI
---
python3 asset_pipeline.py --root DIR [--optimize] [--responsive]
        [--publish --slug SLUG] [--rewrite --cdn-base URL]
        [--local-root /flows/SLUG/ ...] [--docroot D]
Prints a JSON result on stdout.
"""

import argparse
import hashlib
import json
import mimetypes
import os
import re
import shutil
import subprocess
import sys
import tempfile

# ---------------------------------------------------------------------------- consts

IMG_RECOMPRESS = {".png", ".jpg", ".jpeg"}
WEBPABLE = {".png", ".jpg", ".jpeg"}
TEXT_MIN = {".css", ".js"}
# Extensions we treat as "static assets" for CDN upload + URL rewriting.
ASSET_EXTS = {
    ".css", ".js", ".png", ".jpg", ".jpeg", ".webp", ".svg",
    ".woff", ".woff2", ".ico", ".gif", ".avif", ".mp4", ".webm",
}
# Never minify / recompress these (already-optimized or brittle bundles).
MIN_SKIP_NAMES = {"main1.css", "main2.css"}
MIN_SKIP_SUFFIX = (".min.css", ".min.js", "bundle.js")

MANIFEST_NAME = ".asset-manifest.json"
DEFAULT_SKIP_DIRS = {"kk", "__pycache__", ".git"}


# ---------------------------------------------------------------------------- helpers

def _which(name):
    return shutil.which(name)


def _tools():
    return {
        "pngquant": _which("pngquant"),
        "optipng": _which("optipng"),
        "cwebp": _which("cwebp"),
        "pillow": _have_pillow(),
        "rcssmin": _have_mod("rcssmin"),
        "rjsmin": _have_mod("rjsmin"),
    }


def _have_mod(name):
    try:
        __import__(name)
        return True
    except Exception:
        return False


def _have_pillow():
    try:
        import PIL  # noqa: F401
        return True
    except Exception:
        return False


def _iter_files(root, skip_dirs):
    for base, dirs, files in os.walk(root):
        dirs[:] = [d for d in dirs if d not in skip_dirs]
        for fn in files:
            yield os.path.join(base, fn)


def _looks_minified(text):
    if not text:
        return True
    lines = text.count("\n") + 1
    return (len(text) / max(lines, 1)) > 220


def _run(cmd):
    """Run a command, return (rc, stderr). Never raises."""
    try:
        p = subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
        return p.returncode, (p.stderr or b"").decode("utf-8", "replace")
    except Exception as exc:
        return 1, str(exc)


def _load_manifest(root):
    path = os.path.join(root, MANIFEST_NAME)
    if os.path.isfile(path):
        try:
            with open(path, "r", encoding="utf-8") as fh:
                return json.load(fh) or {}
        except Exception:
            return {}
    return {}


def _save_manifest(root, manifest):
    try:
        with open(os.path.join(root, MANIFEST_NAME), "w", encoding="utf-8") as fh:
            json.dump(manifest, fh)
    except Exception:
        pass


def _sig(path):
    st = os.stat(path)
    return {"s": st.st_size, "m": int(st.st_mtime)}


# ---------------------------------------------------------------------------- optimize

def _opt_png(path, tools, warnings):
    """Lossy palette (pngquant) + lossless (optipng), in place. Returns saved bytes."""
    before = os.path.getsize(path)
    if tools["pngquant"]:
        fd, tmp = tempfile.mkstemp(suffix=".png")
        os.close(fd)
        rc, _err = _run([tools["pngquant"], "--quality=65-90", "--strip", "--force",
                         "--skip-if-larger", "--output", tmp, "--", path])
        if rc == 0 and os.path.isfile(tmp) and os.path.getsize(tmp) > 0 \
                and os.path.getsize(tmp) < before:
            shutil.move(tmp, path)
        else:
            try:
                os.remove(tmp)
            except OSError:
                pass
    if tools["optipng"]:
        _run([tools["optipng"], "-o2", "-strip", "all", "-quiet", path])
    return before - os.path.getsize(path)


def _opt_jpg(path, tools, warnings):
    """Re-encode with Pillow (q82, progressive, stripped). Returns saved bytes."""
    if not tools["pillow"]:
        return 0
    before = os.path.getsize(path)
    try:
        from PIL import Image
        with Image.open(path) as im:
            if im.mode in ("RGBA", "P", "LA"):
                im = im.convert("RGB")
            fd, tmp = tempfile.mkstemp(suffix=".jpg")
            os.close(fd)
            im.save(tmp, "JPEG", quality=82, optimize=True, progressive=True)
        if os.path.isfile(tmp) and 0 < os.path.getsize(tmp) < before:
            shutil.move(tmp, path)
        else:
            os.remove(tmp)
    except Exception as exc:
        warnings.append("jpg %s: %s" % (os.path.basename(path), exc))
    return before - os.path.getsize(path)


def _make_webp(path, tools, warnings):
    """Create a .webp sibling next to a png/jpg. Returns True if (re)created."""
    if not tools["cwebp"]:
        return False
    dst = os.path.splitext(path)[0] + ".webp"
    # skip if a fresh sibling already exists
    if os.path.isfile(dst) and os.path.getmtime(dst) >= os.path.getmtime(path):
        return False
    rc, err = _run([tools["cwebp"], "-quiet", "-q", "80", path, "-o", dst])
    if rc != 0:
        warnings.append("cwebp %s: %s" % (os.path.basename(path), err.strip()[:120]))
        return False
    return True


def _minify_text(path, ext, tools, warnings):
    """Minify css/js in place. Returns saved bytes."""
    name = os.path.basename(path)
    if name.endswith(MIN_SKIP_SUFFIX) or name in MIN_SKIP_NAMES:
        return 0
    try:
        with open(path, "r", encoding="utf-8", errors="replace") as fh:
            src = fh.read()
    except Exception:
        return 0
    if _looks_minified(src):
        return 0
    out = None
    try:
        if ext == ".css" and tools["rcssmin"]:
            import rcssmin
            out = rcssmin.cssmin(src)
        elif ext == ".js" and tools["rjsmin"]:
            import rjsmin
            out = rjsmin.jsmin(src)
    except Exception as exc:
        warnings.append("minify %s: %s" % (name, exc))
        return 0
    if not out or len(out) >= len(src):
        return 0
    saved = len(src.encode("utf-8")) - len(out.encode("utf-8"))
    try:
        with open(path, "w", encoding="utf-8") as fh:
            fh.write(out)
    except Exception:
        return 0
    return max(0, saved)


def optimize_dir(root, skip_dirs=None, make_webp=True, minify=True):
    """Optimize every asset under `root` in place. Idempotent via a manifest."""
    root = os.path.realpath(root)
    skip_dirs = set(skip_dirs) if skip_dirs is not None else set(DEFAULT_SKIP_DIRS)
    tools = _tools()
    manifest = _load_manifest(root)
    res = {
        "ok": True,
        "root": root,
        "saved_bytes": 0,
        "images_optimized": 0,
        "webp_created": 0,
        "css_minified": 0,
        "js_minified": 0,
        "skipped_cached": 0,
        "tools": {k: bool(v) for k, v in tools.items()},
        "warnings": [],
    }
    if not os.path.isdir(root):
        res["ok"] = False
        res["warnings"].append("root not found: %s" % root)
        return res

    for path in _iter_files(root, skip_dirs):
        fn = os.path.basename(path)
        if fn == MANIFEST_NAME:
            continue
        ext = os.path.splitext(fn)[1].lower()
        rel = os.path.relpath(path, root)

        # WebP siblings first (cheap, independent of recompress).
        if make_webp and ext in WEBPABLE:
            try:
                if _make_webp(path, tools, res["warnings"]):
                    res["webp_created"] += 1
            except Exception as exc:
                res["warnings"].append("webp %s: %s" % (fn, exc))

        # Skip recompress/minify if unchanged since last optimize.
        try:
            sig = _sig(path)
        except OSError:
            continue
        cached = manifest.get(rel)
        if cached and cached.get("s") == sig["s"] and cached.get("m") == sig["m"]:
            res["skipped_cached"] += 1
            continue

        saved = 0
        if ext == ".png":
            saved = _opt_png(path, tools, res["warnings"])
            if saved:
                res["images_optimized"] += 1
        elif ext in (".jpg", ".jpeg"):
            saved = _opt_jpg(path, tools, res["warnings"])
            if saved:
                res["images_optimized"] += 1
        elif minify and ext in TEXT_MIN:
            saved = _minify_text(path, ext, tools, res["warnings"])
            if saved:
                res["css_minified" if ext == ".css" else "js_minified"] += 1

        res["saved_bytes"] += max(0, saved)
        try:
            manifest[rel] = _sig(path)  # record post-optimize signature
        except OSError:
            pass

    _save_manifest(root, manifest)
    return res


# ------------------------------------------------------------------- responsive images

def _img_size(path):
    if not _have_pillow():
        return None
    try:
        from PIL import Image
        with Image.open(path) as im:
            return im.size  # (w, h)
    except Exception:
        return None


def _resolve_local_asset(url, root, local_roots):
    """Map a built asset URL back to a file under `root`, or None."""
    u = url.split("?", 1)[0].split("#", 1)[0]
    for lr in local_roots:
        if lr and u.startswith(lr):
            return os.path.join(root, u[len(lr):].lstrip("/"))
    if u.startswith("assets/") or u.startswith("./assets/"):
        return os.path.join(root, u.lstrip("./"))
    return None


def upgrade_responsive_images(root, local_roots, pages=None):
    """Page-hardening pass (no design change):
      * wrap bare <img> (png/jpg with a webp sibling) in <picture>+WebP,
      * add width/height + loading/decoding to <img> to stop layout shift,
      * defer render-blocking EXTERNAL scripts in <head>.
    Conservative: only touches <img> not already inside a <picture> (and only
    when a .webp sibling exists on disk), and only defers external, non-module
    head scripts that aren't already async/defer. Inline scripts are never moved
    or altered, so behavior that depends on inline execution order is preserved.
    """
    res = {"ok": True, "pages": 0, "images_upgraded": 0, "attrs_added": 0,
           "scripts_deferred": 0, "warnings": []}
    try:
        from bs4 import BeautifulSoup
    except Exception as exc:
        res["ok"] = False
        res["warnings"].append("bs4 unavailable: %s" % exc)
        return res

    root = os.path.realpath(root)
    if pages is None:
        pages = [f for f in os.listdir(root)
                 if f.endswith(".html") and os.path.isfile(os.path.join(root, f))]

    for page in pages:
        fp = os.path.join(root, page)
        if not os.path.isfile(fp):
            continue
        try:
            html = open(fp, encoding="utf-8", errors="replace").read()
        except Exception:
            continue
        soup = BeautifulSoup(html, "html.parser")
        changed = False

        for img in soup.find_all("img"):
            src = (img.get("src") or "").strip()
            if not src:
                continue
            # loading / decoding hints (cheap, always safe)
            if not img.get("loading"):
                img["loading"] = "lazy"
                res["attrs_added"] += 1
                changed = True
            if not img.get("decoding"):
                img["decoding"] = "async"
                res["attrs_added"] += 1
                changed = True

            ext = os.path.splitext(src.split("?", 1)[0])[1].lower()
            if ext not in WEBPABLE:
                continue
            if img.find_parent("picture") is not None:
                continue
            local = _resolve_local_asset(src, root, local_roots)
            if not local:
                continue
            webp_local = os.path.splitext(local)[0] + ".webp"
            if not os.path.isfile(webp_local):
                continue

            # width/height to prevent layout shift
            if not img.get("width") or not img.get("height"):
                dim = _img_size(local) or _img_size(webp_local)
                if dim:
                    img["width"] = str(dim[0])
                    img["height"] = str(dim[1])
                    res["attrs_added"] += 1

            webp_url = re.sub(r"\.(png|jpe?g)(\?.*)?$",
                              lambda m: ".webp" + (m.group(2) or ""), src, flags=re.I)
            picture = soup.new_tag("picture")
            source = soup.new_tag("source")
            source["type"] = "image/webp"
            source["srcset"] = webp_url
            img.insert_before(picture)
            picture.append(source)
            picture.append(img.extract())
            res["images_upgraded"] += 1
            changed = True

        # Defer render-blocking external scripts in <head> (safe subset).
        head = soup.find("head")
        if head is not None:
            for s in head.find_all("script"):
                if not s.get("src"):
                    continue  # never touch inline scripts
                if s.has_attr("defer") or s.has_attr("async"):
                    continue
                if (s.get("type") or "").lower() == "module":
                    continue  # modules are already deferred by spec
                if s.has_attr("data-flow-config"):
                    continue  # flow runtime must stay synchronous
                s["defer"] = "defer"  # valid boolean-attribute form
                res["scripts_deferred"] += 1
                changed = True

        if changed:
            try:
                open(fp, "w", encoding="utf-8").write(str(soup))
                res["pages"] += 1
            except Exception as exc:
                res["warnings"].append("write %s: %s" % (page, exc))
    return res


# ---------------------------------------------------------------------------- CDN

def _build_hash(root, skip_dirs):
    """Stable 8-char hash over asset (relpath,size,mtime) so each distinct build
    publishes to an immutable, infinitely-cacheable folder."""
    h = hashlib.sha1()
    for path in sorted(_iter_files(root, skip_dirs)):
        ext = os.path.splitext(path)[1].lower()
        if ext not in ASSET_EXTS:
            continue
        try:
            st = os.stat(path)
        except OSError:
            continue
        rel = os.path.relpath(path, root)
        h.update(("%s|%d|%d;" % (rel, st.st_size, int(st.st_mtime))).encode("utf-8"))
    return h.hexdigest()[:8]


def _remote_base(settings, slug, build_hash):
    """Storage path inside the zone: <prefix>/<slug>/<build_hash>."""
    parts = [p for p in [settings.get("remote_prefix", ""), slug, build_hash] if p]
    return "/".join(parts)


def cdn_asset_base(settings, slug, build_hash):
    """Public pull-zone URL base for this build's assets."""
    return "%s/%s" % (settings["pull_url"].rstrip("/"), _remote_base(settings, slug, build_hash))


def publish_bunny(root, slug, settings, skip_dirs=None, build_hash=None):
    """Upload all static assets under `root` to a BunnyCDN storage zone via HTTP
    PUT. Returns the public asset base URL for use by rewrite_asset_urls()."""
    import urllib.request
    import urllib.error

    skip_dirs = set(skip_dirs) if skip_dirs is not None else set(DEFAULT_SKIP_DIRS)
    root = os.path.realpath(root)
    res = {"ok": False, "uploaded": 0, "bytes": 0, "warnings": [],
           "asset_base": None, "build_hash": None}

    if not settings.get("storage_zone") or not settings.get("storage_key") \
            or not settings.get("pull_url"):
        res["warnings"].append("BunnyCDN not configured (zone/key/pull_url missing)")
        return res

    bh = build_hash or _build_hash(root, skip_dirs)
    res["build_hash"] = bh
    remote_base = _remote_base(settings, slug, bh)
    host = settings["storage_host"]
    zone = settings["storage_zone"]

    for path in _iter_files(root, skip_dirs):
        ext = os.path.splitext(path)[1].lower()
        if ext not in ASSET_EXTS:
            continue
        rel = os.path.relpath(path, root).replace(os.sep, "/")
        remote = "%s/%s" % (remote_base, rel)
        url = "https://%s/%s/%s" % (host, zone, remote)
        try:
            with open(path, "rb") as fh:
                data = fh.read()
            ctype = mimetypes.guess_type(path)[0] or "application/octet-stream"
            req = urllib.request.Request(url, data=data, method="PUT")
            req.add_header("AccessKey", settings["storage_key"])
            req.add_header("Content-Type", ctype)
            with urllib.request.urlopen(req, timeout=60) as resp:
                if 200 <= resp.status < 300:
                    res["uploaded"] += 1
                    res["bytes"] += len(data)
                else:
                    res["warnings"].append("PUT %s -> HTTP %s" % (rel, resp.status))
        except urllib.error.HTTPError as exc:
            res["warnings"].append("PUT %s -> HTTP %s" % (rel, exc.code))
        except Exception as exc:
            res["warnings"].append("PUT %s -> %s" % (rel, exc))

    res["asset_base"] = "%s/%s" % (settings["pull_url"].rstrip("/"), remote_base)
    res["ok"] = res["uploaded"] > 0
    res["skipped"] = res.get("skipped", 0)
    return res


def rewrite_asset_urls(root, asset_base, local_roots, skip_dirs=None):
    """Repoint asset URLs at the CDN. Only file references ending in a known
    asset extension are rewritten; navigational .html/.php links and page
    routing (__LANDER_BASE) are left untouched."""
    skip_dirs = set(skip_dirs) if skip_dirs is not None else set(DEFAULT_SKIP_DIRS)
    root = os.path.realpath(root)
    asset_base = asset_base.rstrip("/")
    ext_alt = "|".join(sorted((e.lstrip(".") for e in ASSET_EXTS), key=len, reverse=True))
    res = {"ok": True, "files_changed": 0, "replacements": 0, "warnings": []}

    # Absolute local roots: /flows/<slug>/  or  /<kk_name>/
    abs_patterns = []
    for lr in local_roots:
        if not lr:
            continue
        lr = "/" + lr.strip("/") + "/"
        pat = re.compile(re.escape(lr) + r"([A-Za-z0-9_\-./]+?\.(?:%s))" % ext_alt)
        abs_patterns.append(pat)
    # Relative assets/ inside url()/srcset/src/href
    rel_pat = re.compile(
        r"(?P<pre>(?:url\(['\"]?|src=['\"]|href=['\"]|srcset=['\"]|,\s*))"
        r"(?:\./)?assets/(?P<path>[A-Za-z0-9_\-./]+?\.(?:%s))" % ext_alt
    )

    for path in _iter_files(root, skip_dirs):
        ext = os.path.splitext(path)[1].lower()
        if ext not in (".html", ".css", ".js", ".php"):
            continue
        try:
            text = open(path, encoding="utf-8", errors="replace").read()
        except Exception:
            continue
        orig = text
        n = [0]

        def _abs_sub(m):
            n[0] += 1
            return asset_base + "/" + m.group(1)

        for pat in abs_patterns:
            text = pat.sub(_abs_sub, text)

        def _rel_sub(m):
            n[0] += 1
            return "%s%s/assets/%s" % (m.group("pre"), asset_base, m.group("path"))

        text = rel_pat.sub(_rel_sub, text)

        if text != orig:
            try:
                open(path, "w", encoding="utf-8").write(text)
                res["files_changed"] += 1
                res["replacements"] += n[0]
            except Exception as exc:
                res["warnings"].append("write %s: %s" % (os.path.basename(path), exc))
    return res


# ---------------------------------------------------------------------------- CLI

def main():
    ap = argparse.ArgumentParser(description="Asset optimization + BunnyCDN publishing.")
    ap.add_argument("--root", required=True, help="flow or kk directory")
    ap.add_argument("--docroot", default="/var/www/aipuunlimited.com/htdocs")
    ap.add_argument("--slug", default=None, help="flow slug (CDN remote folder)")
    ap.add_argument("--optimize", action="store_true")
    ap.add_argument("--responsive", action="store_true")
    ap.add_argument("--publish", action="store_true")
    ap.add_argument("--rewrite", action="store_true")
    ap.add_argument("--cdn-base", default=None, help="override CDN asset base URL")
    ap.add_argument("--local-root", action="append", default=[],
                    help="local asset root(s) to rewrite, e.g. /flows/<slug>/")
    ap.add_argument("--no-webp", action="store_true")
    ap.add_argument("--no-minify", action="store_true")
    args = ap.parse_args()

    out = {"ok": True, "root": os.path.realpath(args.root)}

    if args.optimize:
        out["optimize"] = optimize_dir(
            args.root, make_webp=not args.no_webp, minify=not args.no_minify)

    if args.responsive:
        out["responsive"] = upgrade_responsive_images(args.root, args.local_root or [])

    slug = args.slug or os.path.basename(os.path.realpath(args.root))
    local_roots = args.local_root or (["/flows/%s/" % slug] if slug else [])
    asset_base = args.cdn_base

    settings = None
    if args.publish or (args.rewrite and not args.cdn_base):
        try:
            import cdn_config
            settings = cdn_config.cdn_settings(args.docroot)
        except Exception as exc:
            out.setdefault("warnings", []).append("cdn_config: %s" % exc)

    if args.publish:
        if not settings or not settings.get("configured"):
            out["publish"] = {"ok": False, "warnings": ["BunnyCDN not configured"]}
        else:
            # Immutable per-build folder. Rewrite references BEFORE upload so the
            # uploaded CSS/JS already point at the CDN (no chicken-and-egg).
            bh = _build_hash(args.root, set(DEFAULT_SKIP_DIRS))
            asset_base = cdn_asset_base(settings, slug, bh)
            if args.rewrite:
                out["rewrite"] = rewrite_asset_urls(args.root, asset_base, local_roots)
            out["publish"] = publish_bunny(args.root, slug, settings, build_hash=bh)
            out["asset_base"] = asset_base
    elif args.rewrite:
        if asset_base:
            out["rewrite"] = rewrite_asset_urls(args.root, asset_base, local_roots)
        else:
            out.setdefault("warnings", []).append("rewrite requested but no --cdn-base")

    print(json.dumps(out))
    return 0


if __name__ == "__main__":
    sys.exit(main())
