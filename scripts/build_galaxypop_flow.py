#!/usr/bin/env python3
"""Snapshot the omni-galaxypop prototype into a downloadable, KK-ready flow.

This is a "direct flow" build: unlike build_flow.py (which reassembles site
chrome from a brand pack), it preserves the prototype's own static.js + in-page
billing popup verbatim and only:

  * copies the prototype into flows/<name>/ (createvideo is already index.html)
  * drops the blank checkout.html so the flow is one-page (sales-only, Law 3)
  * re-roots /omni-prototypes/omni-galaxypop -> /flows/<name>
  * injects a <script data-flow-config></script> before each page's static.js so
    kk_format.py can wire window.__KK_OFFER_LINKS / register links
  * writes flow.json (brand=omni, flow_type=sales-only)

Run kk_format.py on the resulting flow dir to produce the cloaked .php package.
"""
import json
import os
import re
import shutil
import sys

DOCROOT = "/var/www/aipuunlimited.com/htdocs"
SRC = os.path.join(DOCROOT, "omni-prototypes", "omni-galaxypop")
FLOW_NAME = "galaxypop-createvideo__omni"
FLOW_BASE = "galaxypop-createvideo"
DEST = os.path.join(DOCROOT, "flows", FLOW_NAME)

SRC_WEB = "/omni-prototypes/omni-galaxypop"
FLOW_WEB = "/flows/" + FLOW_NAME

TEXT_EXT = (".html", ".htm", ".css", ".js", ".mjs", ".jsx", ".json", ".svg", ".txt")
# Files/dirs that must never enter the flow snapshot.
EXCLUDE_NAMES = {"kk", "kk.tmp", "__pycache__"}
# checkout.html (blank prototype stub) is overwritten with the billing offer below.
EXCLUDE_REL = {os.path.join("billing", "index.html")}  # popup lives in billing.html now


def want(rel: str) -> bool:
    base = os.path.basename(rel)
    if rel in EXCLUDE_REL:
        return False
    if base in EXCLUDE_NAMES:
        return False
    if base.startswith("_") and base.endswith(".py"):
        return False
    if base.endswith(".md"):
        return False
    if base.endswith(".pyc"):
        return False
    if base.startswith(".") and base not in (".htaccess",):
        return False
    return True


def copy_tree():
    if os.path.isdir(DEST):
        shutil.rmtree(DEST)
    os.makedirs(DEST)
    for root, dirs, files in os.walk(SRC):
        dirs[:] = [d for d in dirs if d not in EXCLUDE_NAMES and d != "__pycache__"]
        rel_root = os.path.relpath(root, SRC)
        for f in files:
            rel = f if rel_root == "." else os.path.join(rel_root, f)
            if not want(rel):
                continue
            src_fp = os.path.join(root, f)
            dst_fp = os.path.join(DEST, rel)
            os.makedirs(os.path.dirname(dst_fp), exist_ok=True)
            shutil.copy2(src_fp, dst_fp)


def make_checkout_page():
    """The popup is the primary checkout UX, but a multi-page KK flow needs a real
    checkout.php ($__checkout target + no-JS fallback). Render the same billing
    offer full-page by reusing billing.html."""
    billing = os.path.join(DEST, "billing.html")
    checkout = os.path.join(DEST, "checkout.html")
    if os.path.isfile(billing):
        shutil.copy2(billing, checkout)


def convert_conversion_links():
    """Re-point the brand auth/register URLs at the local checkout so Create
    Account / Login convert (instead of being killed as dead external links).
    kk_format then maps checkout.html -> $__checkout."""
    repl = [
        ("https://omnirogue.com/register", "checkout.html"),
        ("http://omnirogue.com/register", "checkout.html"),
        ("https://omnirogue.com/login", "checkout.html"),
        ("http://omnirogue.com/login", "checkout.html"),
    ]
    for f in os.listdir(DEST):
        if not f.endswith(".html"):
            continue
        fp = os.path.join(DEST, f)
        text = open(fp, encoding="utf-8", errors="replace").read()
        new = text
        for a, b in repl:
            new = new.replace(a, b)
        if new != text:
            open(fp, "w", encoding="utf-8").write(new)


def reroot_paths():
    for root, _dirs, files in os.walk(DEST):
        for f in files:
            if not f.endswith(TEXT_EXT):
                continue
            fp = os.path.join(root, f)
            try:
                text = open(fp, encoding="utf-8", errors="replace").read()
            except OSError:
                continue
            new = text.replace(SRC_WEB, FLOW_WEB)
            if new != text:
                open(fp, "w", encoding="utf-8").write(new)


FLOW_CFG_TAG = "<script data-flow-config></script>\n"
STATIC_JS_RE = re.compile(r'(<script\b[^>]*\bsrc="[^"]*assets/static\.js[^"]*"[^>]*>\s*</script>)', re.I)


def inject_flow_config():
    """Add a data-flow-config placeholder before the static.js include on every
    top-level page that doesn't already have one."""
    for f in os.listdir(DEST):
        if not f.endswith(".html"):
            continue
        fp = os.path.join(DEST, f)
        html = open(fp, encoding="utf-8", errors="replace").read()
        if "data-flow-config" in html:
            continue
        new, n = STATIC_JS_RE.subn(lambda m: FLOW_CFG_TAG + m.group(1), html, count=1)
        if n:
            open(fp, "w", encoding="utf-8").write(new)
        else:
            print("WARN: no static.js include in %s (no flow-config injected)" % f, file=sys.stderr)


def write_manifest():
    manifest = {
        "name": FLOW_NAME,
        "brand": "omni",
        "flow_base": FLOW_BASE,
        "flow_type": "multi",
        "lander_web": FLOW_WEB,
        "lander_entry": "index.html",
        "pack_web": "/omnirogue-pages",
        "register_url": "/register",
        "interactive": True,
        "source": "omni-prototypes/omni-galaxypop (direct flow; createvideo=index, popup checkout)",
        "cta_config": {"default_register_token": "registercheckout", "pages": {}},
        "plans_config": {"index_register_token": "registercheckout"},
    }
    with open(os.path.join(DEST, "flow.json"), "w", encoding="utf-8") as fh:
        json.dump(manifest, fh, indent=2)


def main():
    copy_tree()
    make_checkout_page()
    convert_conversion_links()
    reroot_paths()
    inject_flow_config()
    write_manifest()
    pages = sorted(f for f in os.listdir(DEST) if f.endswith(".html"))
    print(json.dumps({
        "ok": True,
        "flow": FLOW_NAME,
        "dest": DEST,
        "pages": pages,
        "has_checkout": os.path.isfile(os.path.join(DEST, "checkout.html")),
        "has_billing": os.path.isfile(os.path.join(DEST, "billing.html")),
    }, indent=2))


if __name__ == "__main__":
    main()
