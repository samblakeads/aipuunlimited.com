#!/usr/bin/env python3
"""Import the omni-galaxypop prototype into the brand-neutral `single-pages`
source collection as one item, re-rooting its own web paths to the item's
canonical location (/single-pages/<item>) so the source itself previews, and
the flow builder can re-root /single-pages/<item> -> /flows/<name>.

The source stays in its canonical OMNI flavour (like sales-pages/checkout-pages):
the flow builder re-skins omni->aipu at build time via brandlib.

  * keeps the whole multi-page site + billing/ popup
  * drops the blank checkout.html stub (single-page flows have NO separate
    checkout; the billing popup IS the checkout)
  * injects <script data-flow-config></script> before each page's static.js so
    kk_format can wire window.__KK_OFFER_LINKS / register links later
"""
import os, re, shutil, sys, json

DOCROOT = "/var/www/aipuunlimited.com/htdocs"
SRC = os.path.join(DOCROOT, "omni-prototypes", "omni-galaxypop")
ITEM = "galaxypop-createvideo"
DEST = os.path.join(DOCROOT, "single-pages", ITEM)

SRC_WEB = "/omni-prototypes/omni-galaxypop"
SRC_WEB_BARE = "omni-prototypes/omni-galaxypop"
DEST_WEB = "/single-pages/" + ITEM
DEST_WEB_BARE = "single-pages/" + ITEM

TEXT_EXT = (".html", ".htm", ".css", ".js", ".mjs", ".jsx", ".json", ".svg", ".txt")
EXCLUDE_NAMES = {"kk", "kk.tmp", "__pycache__"}
# the popup lives in billing.html (preview) / billing.php (KK); drop the standalone
# billing/index.html demo entry, and the blank checkout.html stub.
EXCLUDE_REL = {os.path.join("billing", "index.html"), "checkout.html"}

def want(rel):
    base = os.path.basename(rel)
    if rel in EXCLUDE_REL: return False
    if base in EXCLUDE_NAMES: return False
    if base.startswith("_") and base.endswith(".py"): return False
    if base.endswith((".md", ".pyc")): return False
    if base.startswith(".") and base != ".htaccess": return False
    return True

def copy_tree():
    if os.path.isdir(DEST):
        shutil.rmtree(DEST)
    os.makedirs(DEST)
    for root, dirs, files in os.walk(SRC):
        dirs[:] = [d for d in dirs if d not in EXCLUDE_NAMES]
        rel_root = os.path.relpath(root, SRC)
        for f in files:
            rel = f if rel_root == "." else os.path.join(rel_root, f)
            if not want(rel): continue
            dst_fp = os.path.join(DEST, rel)
            os.makedirs(os.path.dirname(dst_fp), exist_ok=True)
            shutil.copy2(os.path.join(root, f), dst_fp)

def reroot():
    for root, _d, files in os.walk(DEST):
        for f in files:
            if not f.endswith(TEXT_EXT): continue
            fp = os.path.join(root, f)
            try:
                text = open(fp, encoding="utf-8", errors="replace").read()
            except OSError:
                continue
            new = text.replace(SRC_WEB, DEST_WEB).replace(SRC_WEB_BARE, DEST_WEB_BARE)
            if new != text:
                open(fp, "w", encoding="utf-8").write(new)

FLOW_CFG_TAG = "<script data-flow-config></script>\n"
STATIC_JS_RE = re.compile(r'(<script\b[^>]*\bsrc="[^"]*assets/static\.js[^"]*"[^>]*>\s*</script>)', re.I)

def inject_flow_config():
    for f in os.listdir(DEST):
        if not f.endswith(".html"): continue
        fp = os.path.join(DEST, f)
        html = open(fp, encoding="utf-8", errors="replace").read()
        if "data-flow-config" in html: continue
        new, n = STATIC_JS_RE.subn(lambda m: FLOW_CFG_TAG + m.group(1), html, count=1)
        if n:
            open(fp, "w", encoding="utf-8").write(new)

def main():
    copy_tree()
    reroot()
    inject_flow_config()
    pages = sorted(f for f in os.listdir(DEST) if f.endswith(".html"))
    # sanity: no original prototype path should survive
    leftover = []
    for root,_d,files in os.walk(DEST):
        for f in files:
            if f.endswith(TEXT_EXT):
                t = open(os.path.join(root,f),encoding="utf-8",errors="replace").read()
                if SRC_WEB in t or SRC_WEB_BARE in t:
                    leftover.append(os.path.relpath(os.path.join(root,f), DEST))
    print(json.dumps({
        "ok": not leftover,
        "dest": DEST,
        "pages": pages,
        "has_billing": os.path.isfile(os.path.join(DEST,"billing.html")),
        "has_checkout_stub": os.path.isfile(os.path.join(DEST,"checkout.html")),
        "index_is_createvideo": os.path.isfile(os.path.join(DEST,"index.html")),
        "leftover_src_paths": leftover[:10],
    }, indent=2))

if __name__ == "__main__":
    main()
