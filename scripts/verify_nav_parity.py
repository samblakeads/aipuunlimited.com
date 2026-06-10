#!/usr/bin/env python3
"""
verify_nav_parity.py — post-generation gate that proves every built page ships
the SAME header on desktop and mobile, exactly matching the brand reference
(omnirogue.com / app.aiprofessionalsuniversity.com).

Two layers:

  1. STATIC (every page, no browser): the desktop nav exists with the brand's
     required links, the mobile hamburger trigger exists, and the mobile menu
     source is present (menu block inside assets/static.js, or the chrome
     mobile-sheet markup). Chrome-sheet markup is validated label-by-label.

  2. RUNTIME (entry pages, headless Chrome at 390px): loads the live page,
     lets the real JS build the menu, then asserts the rendered menu's labels
     match the brand spec in exact order — and that key destinations (Pricing,
     Create Account, Login) agree between the desktop nav and the mobile menu.
     This catches script crashes (e.g. a ReferenceError killing static.js)
     that static checks can't see.

Usage:
  python3 verify_nav_parity.py --dir <flow-or-pages-dir> [--brand omni|aipu]
        [--no-runtime] [--pages index.html,checkout.html] [--strict]
        [--base-url https://aipuunlimited.com]

As a library: verify_dir(...) returns the result dict (used by build_flow.py).
"""
from __future__ import annotations

import argparse
import json
import os
import re
import shutil
import subprocess
import sys
from pathlib import Path

DOCROOT = Path(os.environ.get("AIPU_DOCROOT", "/var/www/aipuunlimited.com/htdocs"))
BASE_URL = "https://aipuunlimited.com"

# ---------------------------------------------------------------- brand specs
# Exact, ordered text content of the opened mobile menu (badges render as
# "New"). Source of truth: the live brand sites' mobile menus.
MOBILE_SPEC = {
    "omni": (
        "Navigate Home Create Studio "
        "AI Create AI Video New Image Audio Music New "
        "Omni Create OmniReels New Podcast "
        "AI Chat AI Chat New "
        "AI Tools Voice Agents Knowledge Base "
        "Library GPT Library Prompt Library About Us Pricing Become Affiliate "
        "Create Account Login"
    ),
    "aipu": (
        "Navigate Create Studio "
        "AI Create AI Video New Long Video New Image Audio Music New "
        "Omni Create OmniReels New Podcast "
        "AI Chat AI Chat New "
        "AI Tools Voice Agents Knowledge Base "
        "Library GPT Library Prompt Library About Us Pricing "
        "Create Account Login"
    ),
}

# Labels that must appear in the desktop nav of every built page.
DESKTOP_REQUIRED = ["Create Studio", "Pricing", "Create Account", "Login"]

HAMBURGER_RE = re.compile(r'aria-label="Open menu"|data-or-mobile-toggle')
NAV_RE = re.compile(r"<nav\b[\s\S]*?</nav>|<div class=\"or-nav-wrap\"[\s\S]*?</nav>")
SHEET_START = "<!-- Mobile sheet"

# Double-header detection: every page must carry EXACTLY ONE top-of-page header
# (the injected canonical nav) and no leftover lander header/nav stacked above
# the content. Mirrors qa_checks so both gates agree on what is "site chrome".
CANON_NAV_RE = re.compile(
    r'<div class="fixed top-0 left-0 right-0 z-50">[\s\S]*?</nav>\s*</div>')
SITE_HEADER_SIGNALS = re.compile(
    r'<img\b[^>]*\b(?:alt|src|srcset|class)\s*=\s*"[^"]*logo'
    r'|aria-label="Open menu"|data-or-mobile-toggle'
    r'|class="[^"]*\bnav\b[^"]*"'
    r'|>\s*Home\s*<',
    re.I,
)


def double_header_problem(html: str) -> str | None:
    """Return a message if `html` has more than one top-of-page header bar (the
    canonical injected nav PLUS a leftover lander header/nav), else None."""
    html = re.sub(r"<\?[\s\S]*?\?>", " ", html)  # strip PHP for .php pages
    n = len(CANON_NAV_RE.findall(html))
    if n > 1:
        return "%d canonical site navs present (expected 1)" % n
    rest = CANON_NAV_RE.sub("", html, count=1)
    m = re.search(r"<(?:section|main)\b", rest, re.I)
    top = rest[: m.start()] if m else rest
    for tag in ("header", "nav"):
        hm = re.search(r"<%s\b[^>]*>[\s\S]*?</%s>" % (tag, tag), top, re.I)
        if hm and SITE_HEADER_SIGNALS.search(hm.group(0)):
            return "a second site <%s> remains above the page content" % tag
    return None


def _text(html: str) -> str:
    html = re.sub(r"<script\b[\s\S]*?</script>", " ", html)
    html = re.sub(r"<style\b[\s\S]*?</style>", " ", html)
    html = re.sub(r"<\?[\s\S]*?\?>", " ", html)
    html = re.sub(r"<[^>]+>", " ", html)
    return re.sub(r"\s+", " ", html).strip()


def detect_brand(folder: Path) -> str:
    manifest = folder / "flow.json"
    if manifest.is_file():
        try:
            b = json.loads(manifest.read_text()).get("brand")
            if b in ("omni", "aipu"):
                return b
        except Exception:
            pass
    name = folder.name.lower()
    if "aipu" in name:
        return "aipu"
    for probe in ("index.html", "index.php", "home.html"):
        p = folder / probe
        if p.is_file() and re.search(r"logo-aipu", p.read_text(errors="replace")):
            return "aipu"
    return "omni"


def chrome_binary() -> str | None:
    for c in ("google-chrome", "chromium-browser", "chromium"):
        if shutil.which(c):
            return c
    return None


def web_path(page: Path) -> str:
    return "/" + str(page.resolve().relative_to(DOCROOT.resolve())).replace(os.sep, "/")


# ------------------------------------------------------------- static checks
def check_page_static(page: Path, brand: str) -> list[str]:
    problems: list[str] = []
    raw = page.read_text(encoding="utf-8", errors="replace")

    nav_m = NAV_RE.search(raw)
    if not nav_m:
        problems.append("desktop nav missing")
    else:
        nav_text = _text(nav_m.group(0))
        for label in DESKTOP_REQUIRED:
            if label not in nav_text:
                problems.append(f"desktop nav missing '{label}'")

    if not HAMBURGER_RE.search(raw):
        problems.append("mobile hamburger trigger missing")

    dh = double_header_problem(raw)
    if dh:
        problems.append("double header: %s" % dh)

    # mobile menu source: chrome sheet markup, or the static.js menu block
    if SHEET_START in raw:
        sheet = raw[raw.index(SHEET_START):]
        cut = sheet.find("</button>", sheet.find("or-mobile-secondary"))
        sheet = sheet[: cut + 9 if cut > 0 else len(sheet)]
        got = _text(sheet)
        want = MOBILE_SPEC[brand]
        if want not in got:
            problems.append(f"chrome mobile sheet does not match {brand} spec (got: {got[:160]}...)")
    else:
        m = re.search(r'src="([^"]*assets/static\.js[^"]*)"', raw)
        if not m:
            problems.append("no chrome sheet and no static.js include")
        else:
            sj = (page.parent / "assets" / "static.js")
            if not sj.is_file():
                ref = m.group(1).split("?")[0]
                sj = (DOCROOT / ref.lstrip("/")) if ref.startswith("/") else (page.parent / ref)
            if not sj.is_file():
                problems.append(f"static.js not found ({m.group(1)})")
            else:
                js = sj.read_text(encoding="utf-8", errors="replace")
                if "omni-mnav" not in js:
                    problems.append("static.js lacks the mobile menu block")
                if re.search(r"\bomniBasePath\(\)", js) and "function omniBasePath" not in js:
                    problems.append("static.js calls omniBasePath() but never defines it (script will crash)")
    return problems


# ------------------------------------------------------------ runtime checks
def runtime_dump(url: str, chrome: str) -> str:
    out = subprocess.run(
        [chrome, "--headless=new", "--no-sandbox", "--disable-gpu",
         "--window-size=390,844", "--virtual-time-budget=9000", "--dump-dom", url],
        capture_output=True, text=True, timeout=90,
    )
    return out.stdout or ""


def _hrefs_by_label(html_fragment: str) -> dict[str, str]:
    found: dict[str, str] = {}
    for m in re.finditer(r'<a\b[^>]*href="([^"]*)"[^>]*>([\s\S]*?)</a>', html_fragment):
        label = _text(m.group(2))
        if label and label not in found:
            found[label] = m.group(1)
    return found


def check_page_runtime(page: Path, brand: str, chrome: str, base_url: str) -> list[str]:
    problems: list[str] = []
    url = base_url + web_path(page)
    dump = runtime_dump(url, chrome)
    if not dump:
        return [f"runtime: empty response for {url}"]

    dh = double_header_problem(dump)
    if dh:
        problems.append("runtime: double header: %s" % dh)

    raw = page.read_text(encoding="utf-8", errors="replace")
    if SHEET_START in raw:
        # chrome-sheet pages are fully validated statically; runtime just
        # confirms the sheet survived into the live DOM.
        if "or-mobile-sheet" not in dump:
            problems.append("runtime: chrome mobile sheet missing from live DOM")
        return problems

    idx = dump.find('id="omni-mnav"')
    if idx < 0:
        problems.append("runtime: mobile menu was not built (JS crashed or static.js stale)")
        return problems

    menu = dump[dump.rfind("<", 0, idx):]
    got = _text(menu)
    want = MOBILE_SPEC[brand]
    if not got.startswith(want):
        problems.append(f"runtime: mobile menu does not match {brand} spec "
                        f"(want: '{want[:80]}...' got: '{got[:80]}...')")

    # destination parity between desktop nav and mobile menu
    nav_m = NAV_RE.search(dump[:idx])
    if nav_m:
        desk = _hrefs_by_label(nav_m.group(0))
        mob = _hrefs_by_label(menu)
        for label in ("Pricing", "Create Account", "Login", "Home"):
            if label in desk and label in mob and desk[label] != mob[label]:
                problems.append(f"runtime: '{label}' differs — desktop '{desk[label]}' vs mobile '{mob[label]}'")
    return problems


# ----------------------------------------------------------------- top level
def verify_dir(folder, brand=None, runtime=True, pages=None,
               base_url=BASE_URL) -> dict:
    folder = Path(folder)
    brand = brand or detect_brand(folder)
    chrome = chrome_binary() if runtime else None

    all_pages = sorted(
        p for p in list(folder.glob("*.html")) + list(folder.glob("*.php"))
        if not p.name.startswith("_")
    )
    entry_names = pages or ["index.html", "index.php", "checkout.html", "checkout.php"]
    entries = [folder / n for n in entry_names if (folder / n).is_file()]

    result = {"brand": brand, "dir": str(folder), "status": "pass",
              "checked": 0, "runtime_checked": 0, "problems": []}

    for page in all_pages:
        raw = page.read_text(encoding="utf-8", errors="replace")
        if not HAMBURGER_RE.search(raw) and not NAV_RE.search(raw):
            continue  # not a chromed page (e.g. raw fragments)
        result["checked"] += 1
        for prob in check_page_static(page, brand):
            result["problems"].append({"page": page.name, "layer": "static", "problem": prob})

    if runtime and chrome:
        for page in entries:
            raw = page.read_text(encoding="utf-8", errors="replace")
            if not HAMBURGER_RE.search(raw):
                continue  # placeholder/redirect page without site chrome
            result["runtime_checked"] += 1
            for prob in check_page_runtime(page, brand, chrome, base_url):
                result["problems"].append({"page": page.name, "layer": "runtime", "problem": prob})
    elif runtime and not chrome:
        result["problems"].append({"page": "-", "layer": "runtime",
                                   "problem": "headless chrome not available; runtime checks skipped"})

    if any(p["layer"] != "info" for p in result["problems"]):
        result["status"] = "fail"
    return result


def safe_verify(folder, brand=None, runtime=True) -> dict:
    """Never raises — used by build scripts so QC cannot break a build."""
    try:
        return verify_dir(folder, brand=brand, runtime=runtime)
    except Exception as exc:  # pragma: no cover
        return {"status": "error", "error": str(exc), "dir": str(folder)}


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--dir", required=True)
    ap.add_argument("--brand", choices=["omni", "aipu"], default=None)
    ap.add_argument("--no-runtime", action="store_true")
    ap.add_argument("--pages", default=None, help="comma-separated entry pages for runtime checks")
    ap.add_argument("--base-url", default=BASE_URL)
    ap.add_argument("--strict", action="store_true")
    args = ap.parse_args()

    res = verify_dir(args.dir, brand=args.brand, runtime=not args.no_runtime,
                     pages=args.pages.split(",") if args.pages else None,
                     base_url=args.base_url)
    print(json.dumps(res, indent=2))
    return 1 if (args.strict and res["status"] != "pass") else 0


if __name__ == "__main__":
    raise SystemExit(main())
