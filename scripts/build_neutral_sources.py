#!/usr/bin/env python3
"""
build_neutral_sources.py - seed the brand-neutral source collections.

Converts the existing branded collections into two brand-neutral, header/footer-
free collections that the Create-Flow wizard authors against:

    aipu-landers   + omnirogue-landers   ->  sales-pages/
    aipu-checkouts + omnirogue-checkouts ->  checkout-pages/

"Neutral" means: stored ONCE in the canonical OmniRogue token flavour with the
header/footer stripped. The brand is applied later, at flow-build time, by
build_flow.py (which rebrands the body omni->chosen and injects that brand's
chrome + page pack). So this script's job is only to dedupe, canonicalise to
omni, and strip chrome.

Pipeline per item (reusing brandlib so the transforms match the rest of the
tooling exactly):
  1. canonical dedupe slug = strip a TRAILING -omni / -aipu suffix only
     (never an interior brand word -> no false collisions); prefer the omni
     source on a clash (it carries the step1link PHP the builder expects).
  2. copy the folder (brandlib.copy_tree skips _chrome / .DS_Store).
  3. brandlib.swap_self_dir: rebrand the body to omni (no-op for omni sources)
     and re-point the item's own web paths at the new /sales-pages|/checkout-pages dir.
  4. strip header/footer:
       checkouts -> remove the <!-- chrome:* --> comment blocks (their only
                    footer lives inside that block).
       landers   -> remove the known top-nav patterns + the page <footer>, and
                    LOG anything left (e.g. sabrina's or-nav-wrap, which has no
                    safely-regexable close). build_flow strips again at flow time
                    as a backstop, so a leftover nav never reaches the output.
  5. checkouts: brandlib.mirror_collection_assets so ../assets/.. refs resolve.
  6. brandlib.heal_missing_logos (omni) so canonicalised logo refs don't 404.

Idempotent: by default an item that already exists is left untouched (safe re-run,
never clobbers hand edits). Pass --force to wipe and rebuild both collections.

Usage:
  python3 build_neutral_sources.py [--force] [--only landers|checkouts] [--docroot DIR]
"""
from __future__ import annotations

import argparse
import os
import re
import shutil
import sys
from pathlib import Path

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import brandlib  # noqa: E402

DOCROOT = Path(os.environ.get("AIPU_DOCROOT", "/var/www/aipuunlimited.com/htdocs"))

# Source collections, omni first so the omni variant wins a dedupe clash.
SOURCES = {
    "landers": [("omnirogue-landers", "omni"), ("aipu-landers", "aipu")],
    "checkouts": [("omnirogue-checkouts", "omni"), ("aipu-checkouts", "aipu")],
}
TARGET = {"landers": "sales-pages", "checkouts": "checkout-pages"}

TEXT_EXT = (".html", ".php", ".css", ".js")
PAGE_EXT = (".html", ".php")

# Lander top-nav patterns we can strip with confidence (well-delimited).
#   - <header class="nav"> ... </header>           (the lander7 family)
#   - the omnirogue-pages / aipu chrome nav         (fixed top bar -> </nav></div>)
# NOTE: <header class="hero"> is intentionally NOT matched -- on lander11 that is
# the hero section, not a nav.
NAV_HEADER_RE = re.compile(r'<header\b[^>]*class="[^"]*\bnav\b[^"]*"[^>]*>.*?</header>\s*', re.S | re.I)
CHROME_NAV_RE = re.compile(r'<div class="fixed top-0 left-0 right-0 z-50">.*?</nav>\s*</div>\s*', re.S)
# The "or-" chrome header (nav-wrap + mobile sheet), as used by sabrina. It runs
# from the or-nav-wrap open up to the page's <main>, so bound it with a lookahead;
# if there's no <main> the pattern simply doesn't fire (safe).
OR_NAV_RE = re.compile(r'<div class="or-nav-wrap"[^>]*>.*?(?=<main\b)', re.S | re.I)
FOOTER_RE = re.compile(r'<footer\b[^>]*>.*?</footer>', re.S | re.I)


def canonical_slug(name: str) -> str:
    """Strip a single TRAILING -omni / -aipu suffix; leave interior words alone."""
    for suf in ("-omni", "-aipu"):
        if name.endswith(suf):
            return name[: -len(suf)]
    return name


def item_entry(d: Path) -> str | None:
    for e in ("index.php", "index.html"):
        if (d / e).is_file():
            return e
    return None


def strip_checkout_chrome(html: str) -> str:
    """Remove the injected <!-- chrome:* --> head/header/footer blocks."""
    html = brandlib.HEAD_RE.sub("", html)
    html = brandlib.HEADER_RE.sub("", html)
    html = brandlib.FOOTER_RE.sub("", html)
    return html


def strip_lander_chrome(html: str, slug: str, warnings: list[str]) -> str:
    """Remove the known top-nav patterns + the page footer. Log if a top nav was
    left behind (build_flow strips it again at flow-build time)."""
    nav_hit = False
    for pat in (NAV_HEADER_RE, CHROME_NAV_RE, OR_NAV_RE):
        new = pat.sub("", html, count=1)
        if new != html:
            nav_hit = True
            html = new

    # Strip the LAST <footer>...</footer> (reliably the page footer across variants).
    foots = list(FOOTER_RE.finditer(new))
    if foots:
        m = foots[-1]
        new = new[: m.start()] + new[m.end():]
    else:
        warnings.append(f"{slug}: no <footer> found to strip")

    if not nav_hit and ("or-nav-wrap" in new or re.search(r'<header\b', new, re.I)):
        warnings.append(
            f"{slug}: top nav left in source (no safe strip pattern matched; "
            f"build_flow strips it at flow time)"
        )
    return new


def convert_item(src_item: Path, source_brand: str, kind: str,
                 target_col: Path, seen: dict, warnings: list[str], force: bool) -> tuple[str, str]:
    name = src_item.name
    slug = canonical_slug(name)
    dst = target_col / slug

    if slug in seen:
        return ("skip-dup", f"{slug}  (kept {seen[slug]})")
    if dst.exists():
        if not force:
            seen[slug] = name
            return ("skip-exists", slug)
        shutil.rmtree(dst)

    brandlib.copy_tree(src_item, dst)
    seen[slug] = name
    new_web = "/" + target_col.name + "/" + slug

    for f in sorted(dst.rglob("*")):
        if not f.is_file() or f.suffix.lower() not in TEXT_EXT:
            continue
        text = f.read_text(encoding="utf-8", errors="replace")
        # canonicalise to omni + re-point this item's own paths at new_web
        text = brandlib.swap_self_dir(text, src_item, new_web, source_brand, "omni", DOCROOT)
        if f.suffix.lower() in PAGE_EXT:
            if kind == "checkouts":
                text = strip_checkout_chrome(text)
            else:
                text = strip_lander_chrome(text, slug, warnings)
        f.write_text(text, encoding="utf-8")

    if kind == "checkouts":
        brandlib.mirror_collection_assets(src_item, target_col, warnings)
    brandlib.heal_missing_logos(dst, "omni", warnings, self_web=new_web)
    return ("created", slug)


def main() -> int:
    ap = argparse.ArgumentParser(description="Seed brand-neutral sales-pages/ + checkout-pages/.")
    ap.add_argument("--force", action="store_true",
                    help="Wipe and rebuild the target collections (clobbers edits).")
    ap.add_argument("--only", choices=["landers", "checkouts"], default=None,
                    help="Convert only one kind.")
    ap.add_argument("--docroot", default=str(DOCROOT))
    args = ap.parse_args()

    docroot = Path(args.docroot)
    kinds = [args.only] if args.only else ["landers", "checkouts"]
    warnings: list[str] = []
    summary: dict[str, list[str]] = {}

    for kind in kinds:
        target_col = docroot / TARGET[kind]
        if args.force and target_col.exists():
            shutil.rmtree(target_col)
        target_col.mkdir(parents=True, exist_ok=True)

        seen: dict[str, str] = {}
        rows: list[str] = []
        for col_name, source_brand in SOURCES[kind]:
            col = docroot / col_name
            if not col.is_dir():
                warnings.append(f"source collection missing: {col_name}")
                continue
            for sub in sorted(col.iterdir()):
                if not sub.is_dir() or sub.name.startswith(("_", ".")):
                    continue
                if item_entry(sub) is None:
                    continue
                status, detail = convert_item(sub, source_brand, kind, target_col,
                                              seen, warnings, args.force)
                rows.append(f"  [{status:11}] {col_name}/{sub.name} -> {detail}")
        summary[kind] = rows

    for kind in kinds:
        print(f"\n=== {kind} -> {TARGET[kind]}/ ===")
        for r in summary[kind]:
            print(r)
    if warnings:
        print("\n--- warnings ---")
        for w in warnings:
            print("  ! " + w)
    print("\nDone.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
