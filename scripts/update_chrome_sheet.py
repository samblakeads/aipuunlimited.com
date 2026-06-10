#!/usr/bin/env python3
"""
update_chrome_sheet.py — roll the current chrome mobile sheet (the full-screen
mobile menu that matches omnirogue.com) into every page that carries the
injected checkout chrome (<!-- chrome:header --> markers).

Handles all three page flavours:
  * plain pages         -> sheet dropped in as-is (popup buttons)
  * KK .php pages       -> popup buttons converted to real <a href="...php<?= $__step1link; ?>">
  * KK .html snapshots  -> popup buttons converted to real <a href="...php">

The popup->page conversion mirrors _partials/build/_wire_multistep_nav.php and
is keyed off what the page's OLD sheet looked like, so each page keeps exactly
the linking behaviour it had before.

Idempotent — safe to re-run.
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

DOCROOT = Path(__file__).resolve().parent.parent
CHROME_BUST = "20260609d"

BRANDS = {
    "omni": DOCROOT / "checkouts-omni" / "_chrome" / "header.html",
    "aipu": DOCROOT / "aipu-checkouts" / "_chrome" / "header.html",
}

SHEET_START = "<!-- Mobile sheet"
HEADER_END = "<!-- /chrome:header -->"

POPUP_TO_PAGE = {
    "create-video": "createvideo",
    "create-image": "create-image",
    "create-audio": "create-audio",
    "create-music": "create-music",
    "create-voice-agents": "create-voice-agents",
    "create-upscale": "create-upscale",
    "create-ai-chat": "create-ai-chat",
    "gpt-library": "gpt-library",
    "prompt-library": "prompt-library",
    "knowledge-base": "knowledge-base",
    "about": "about",
    "home": "index",
}

BUTTON_RE_TPL = r'<button\b([^>]*?)data-or-popup="{tok}"([^>]*?)>([\s\S]*?)</button>'


def extract_sheet(header_html: str) -> str:
    idx = header_html.index(SHEET_START)
    return header_html[idx:].rstrip() + "\n"


def find_marker_pages():
    for path in DOCROOT.rglob("*"):
        if path.suffix not in (".html", ".php") or not path.is_file():
            continue
        if any(p.startswith("_") and p != "_partials" for p in path.parts):
            # skip _chrome templates / _menutest scratch files
            if "_chrome" in path.parts or path.name.startswith("_"):
                continue
        try:
            text = path.read_text(encoding="utf-8", errors="replace")
        except Exception:
            continue
        if "<!-- chrome:header -->" in text and SHEET_START in text:
            yield path, text


def old_sheet_link_style(old_sheet: str):
    """If the page's old sheet used real links, return (prefix, ext+suffix)."""
    m = re.search(r'<a class="or-mobile-[^"]*" href="(/[^"]+?)/(index|createvideo)\.(php|html)((?:<\?[^>]*\?>)?)"', old_sheet)
    if not m:
        return None
    return m.group(1), m.group(3), m.group(4)


def convert_popups_to_links(sheet: str, base: str, ext: str, suffix: str) -> str:
    for tok, page in POPUP_TO_PAGE.items():
        href = f"{base}/{page}.{ext}{suffix}"

        def repl(m, href=href):
            attrs = m.group(1) + m.group(2)
            cls = re.search(r'\bclass="([^"]*)"', attrs)
            cls = cls.group(1) if cls else ""
            keep = ""
            for extra in ("data-or-home-item",):
                if extra in attrs:
                    keep += " " + extra
            return ('<a class="' + cls + '" href="' + href + '"' + keep +
                    ' style="text-decoration:none;">' + m.group(3) + "</a>")

        sheet = re.sub(BUTTON_RE_TPL.format(tok=re.escape(tok)), repl, sheet)
    return sheet


def bump_versions(text: str) -> str:
    return re.sub(r"(_chrome/chrome\.(?:css|js))\?v=[A-Za-z0-9._-]+", r"\1?v=" + CHROME_BUST, text)


def main() -> int:
    sheets = {brand: extract_sheet(p.read_text(encoding="utf-8")) for brand, p in BRANDS.items()}
    only = [Path(a).resolve() for a in sys.argv[1:]]

    n = 0
    for path, text in find_marker_pages():
        if only and path.resolve() not in only:
            continue
        if path in (BRANDS["omni"], BRANDS["aipu"]):
            continue

        brand = "aipu" if "/aipu-checkouts/_chrome/" in text else "omni"
        sheet = sheets[brand]

        start = text.index(SHEET_START)
        end = text.index(HEADER_END, start)
        old_sheet = text[start:end]

        link_style = old_sheet_link_style(old_sheet)
        new_sheet = sheet
        if link_style:
            base, ext, suffix = link_style
            new_sheet = convert_popups_to_links(new_sheet, base, ext, suffix)

        out = text[:start] + new_sheet + text[end:]
        out = bump_versions(out)
        if out != text:
            path.write_text(out, encoding="utf-8")
            n += 1
            mode = f"links {link_style[0]}/*.{link_style[1]}{'+step' if link_style[2] else ''}" if link_style else "popups"
            print(f"updated [{brand}] ({mode}) {path.relative_to(DOCROOT)}")
    print(f"done — {n} pages updated")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
