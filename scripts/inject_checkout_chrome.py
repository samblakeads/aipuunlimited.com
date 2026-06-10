#!/usr/bin/env python3
"""Inject the shared checkout chrome (header, footer, popups, CSS, JS)
into every active checkouts-omni/*/index.html.

Idempotent: re-running replaces content between HTML comment markers
without touching the rest of the page.

Markers:
  <!-- chrome:head -->   ... <!-- /chrome:head -->     (in <head>, the CSS link)
  <!-- chrome:header --> ... <!-- /chrome:header -->   (right after <body>, the nav)
  <!-- chrome:footer --> ... <!-- /chrome:footer -->   (before </body>, footer + popup + JS)

Also: one-time strip of pricing-v1's inline header/footer block so the
injected chrome does not duplicate. The strip is wrapped in
<!-- chrome:stripped --> markers so we can detect on re-runs and skip.
"""
from __future__ import annotations

import os
import re
import sys
from pathlib import Path

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
try:
    import qa_checks  # automatic QC gate (per-page summary after injection)
except Exception:  # pragma: no cover - QC is best-effort, never blocks injection
    qa_checks = None

HTDOCS = Path(__file__).resolve().parent.parent
CHECKOUTS = HTDOCS / "checkouts-omni"
CHROME = CHECKOUTS / "_chrome"

CHROME_BUST = "20260609d"  # bump to force browsers to refetch chrome assets

HEAD_BLOCK = (
    "<!-- chrome:head -->\n"
    f'<link rel="stylesheet" href="/checkouts-omni/_chrome/chrome.css?v={CHROME_BUST}">\n'
    "<!-- /chrome:head -->"
)

FOOTER_BLOCK_TPL = (
    "<!-- chrome:footer -->\n"
    "{footer_html}\n"
    "{popups_html}\n"
    f'<script src="/checkouts-omni/_chrome/chrome.js?v={CHROME_BUST}" defer></script>\n'
    "<!-- /chrome:footer -->"
)

HEAD_RE = re.compile(r"<!-- chrome:head -->.*?<!-- /chrome:head -->", re.S)
HEADER_RE = re.compile(r"<!-- chrome:header -->.*?<!-- /chrome:header -->", re.S)
FOOTER_RE = re.compile(r"<!-- chrome:footer -->.*?<!-- /chrome:footer -->", re.S)


def find_pages() -> list[Path]:
    pages: list[Path] = []
    for sub in sorted(CHECKOUTS.iterdir()):
        if not sub.is_dir() or sub.name.startswith("_"):
            continue
        idx = sub / "index.html"
        if idx.exists():
            pages.append(idx)
    return pages


def strip_inline_chrome(html: str, page_name: str) -> str:
    """One-time removal of the inline header/footer that lives only in
    pricing-v1's source today. Idempotent via a marker comment we leave
    in place after the first strip."""
    if page_name != "pricing-v1":
        return html
    if "<!-- chrome:stripped -->" in html:
        return html

    new = html

    nav_re = re.compile(
        r"<!--\s*Top menu.*?-->\s*<div class=\"fixed top-0 left-0 right-0 z-50\">.*?</div>\s*</nav>\s*</div>",
        re.S,
    )
    if nav_re.search(new):
        new = nav_re.sub("<!-- chrome:stripped -->", new, count=1)

    footer_re = re.compile(
        r"<!--\s*Footer\s*-->\s*<footer\b.*?</footer>",
        re.S,
    )
    new = footer_re.sub("", new, count=1)

    modal_re = re.compile(
        r"<!--\s*Trust-building popup modal\s*-->\s*<div class=\"or-modal-scrim\".*?</div>\s*</div>\s*</div>",
        re.S,
    )
    new = modal_re.sub("", new, count=1)

    if "<!-- chrome:stripped -->" not in new:
        new = "<!-- chrome:stripped -->\n" + new

    return new


def inject_head(html: str, head_block: str) -> str:
    if HEAD_RE.search(html):
        return HEAD_RE.sub(head_block, html, count=1)
    if "</head>" in html:
        return html.replace("</head>", f"{head_block}\n</head>", 1)
    return head_block + "\n" + html


def inject_header(html: str, header_block: str) -> str:
    if HEADER_RE.search(html):
        return HEADER_RE.sub(header_block, html, count=1)
    body_open_re = re.compile(r"(<body[^>]*>)", re.I)
    m = body_open_re.search(html)
    if m:
        return html[: m.end()] + "\n" + header_block + "\n" + html[m.end():]
    return header_block + "\n" + html


def inject_footer(html: str, footer_block: str) -> str:
    if FOOTER_RE.search(html):
        return FOOTER_RE.sub(footer_block, html, count=1)
    if "</body>" in html:
        return html.replace("</body>", f"{footer_block}\n</body>", 1)
    return html + "\n" + footer_block


def main() -> int:
    if not CHROME.is_dir():
        print(f"ERROR: chrome bundle not found at {CHROME}", file=sys.stderr)
        return 2

    header_html = (CHROME / "header.html").read_text()
    footer_html = (CHROME / "footer.html").read_text()
    popups_html = (CHROME / "popups.html").read_text()

    header_block = (
        "<!-- chrome:header -->\n"
        f"{header_html}\n"
        "<!-- /chrome:header -->"
    )
    footer_block = FOOTER_BLOCK_TPL.format(
        footer_html=footer_html,
        popups_html=popups_html,
    )

    pages = find_pages()
    if not pages:
        print("No checkout pages found.")
        return 0

    qc_fail = qc_warn = 0
    for page in pages:
        original = page.read_text()
        page_name = page.parent.name

        updated = strip_inline_chrome(original, page_name)
        updated = inject_head(updated, HEAD_BLOCK)
        updated = inject_header(updated, header_block)
        updated = inject_footer(updated, footer_block)

        if updated != original:
            page.write_text(updated)
            verb = "updated"
        else:
            verb = "no-op  "

        # Per-page QC summary (chrome integrity + version + broken links/CTAs).
        qc_note = ""
        if qa_checks is not None:
            qc = qa_checks.safe_validate("checkout", str(page.parent), str(HTDOCS), "omni")
            if qc["status"] == "fail":
                qc_fail += 1
            elif qc["status"] == "warn":
                qc_warn += 1
            qc_note = "  [qc:%s%s]" % (
                qc["status"],
                " %de/%dw" % (len(qc["errors"]), len(qc["warnings"]))
                if (qc["errors"] or qc["warnings"]) else "",
            )
            for msg in qc["errors"] + qc["warnings"]:
                qc_note += "\n             - " + msg

        print(f"{verb}  {page.relative_to(HTDOCS)}{qc_note}")

    if qa_checks is not None:
        print(f"\nQC summary: {len(pages)} page(s), {qc_fail} fail, {qc_warn} warn.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
