#!/usr/bin/env python3
"""
sync_mobile_nav.py — keep the mobile nav menu identical across every flow.

The canonical menu lives in scripts/mobile_nav_block.js. This script appends it
to (or refreshes it inside) every assets/static.js copy under the docroot, then
bumps the ?v= cache-buster on each page that loads that static.js.

Idempotent: the block is delimited by marker comments and replaced wholesale.

Usage:
  python3 scripts/sync_mobile_nav.py             # sync everything
  python3 scripts/sync_mobile_nav.py <dir> ...   # sync specific flow dirs only
"""
import datetime
import re
import sys
from pathlib import Path

DOCROOT = Path(__file__).resolve().parent.parent
BLOCK_SRC = Path(__file__).resolve().parent / "mobile_nav_block.js"

START_RE = re.compile(r"/\* === OMNI MOBILE NAV[^=]*=== \*/")
END_MARK = "/* === END OMNI MOBILE NAV === */"

# A nav block whose marker comments were stripped (e.g. by the asset minifier).
# Matches our generated IIFE shape from "(function(){'use strict';function
# initOmniMobileNav(){" through the closing "initOmniMobileNav();}})();" tail —
# the only place a direct initOmniMobileNav() call precedes the IIFE close.
UNMARKED_BLOCK_RE = re.compile(
    r"\(function\s*\(\s*\)\s*\{\s*['\"]use strict['\"];?\s*"
    r"function initOmniMobileNav\s*\(.*?"
    r"initOmniMobileNav\(\)\s*;?\s*\}\s*\}?\s*\)\s*\(\s*\)\s*;?",
    re.S,
)

# Folders never synced (sources/templates/tools, not served flows).
SKIP_PARTS = {"node_modules", ".git", "__pycache__", "scripts", "previews"}


def strip_unmarked_blocks(text: str) -> str:
    """Remove marker-less (minified) copies of the nav block so a synced file
    never ships two competing menus."""
    return UNMARKED_BLOCK_RE.sub("", text)


def sync_static_js(path: Path, block: str) -> str:
    text = path.read_text(encoding="utf-8", errors="replace")
    m = START_RE.search(text)
    if m and END_MARK in text:
        start = m.start()
        end = text.index(END_MARK) + len(END_MARK)
        before = strip_unmarked_blocks(text[:start])
        after = strip_unmarked_blocks(text[end:])
        new = before + block + after
        action = "refreshed" if new != text else "unchanged"
        if new != text:
            path.write_text(new, encoding="utf-8")
        return action
    text = strip_unmarked_blocks(text).rstrip() + "\n\n" + block + "\n"
    path.write_text(text, encoding="utf-8")
    return "added"


def bump_versions(folder: Path, version: str) -> int:
    """Point every static.js reference in the folder's pages at ?v=<version>."""
    n = 0
    pat = re.compile(r"assets/static\.js(\?v=[A-Za-z0-9._-]*)?")
    for page in list(folder.glob("*.html")) + list(folder.glob("*.php")):
        raw = page.read_text(encoding="utf-8", errors="replace")
        out = pat.sub("assets/static.js?v=" + version, raw)
        if out != raw:
            page.write_text(out, encoding="utf-8")
            n += 1
    return n


def sync_all(targets=None) -> None:
    block = BLOCK_SRC.read_text(encoding="utf-8").strip()
    version = datetime.datetime.now().strftime("%Y%m%d%H%M")

    if targets:
        statics = []
        for t in targets:
            statics.extend(Path(t).resolve().rglob("assets/static.js"))
    else:
        statics = list(DOCROOT.rglob("assets/static.js"))

    for static in sorted(set(statics)):
        rel = static.relative_to(DOCROOT) if static.is_relative_to(DOCROOT) else static
        if any(part in SKIP_PARTS for part in rel.parts):
            continue
        action = sync_static_js(static, block)
        pages = bump_versions(static.parent.parent, version)
        print(f"{action:9s} {rel}  (bumped {pages} pages)")


if __name__ == "__main__":
    sync_all(sys.argv[1:] or None)
