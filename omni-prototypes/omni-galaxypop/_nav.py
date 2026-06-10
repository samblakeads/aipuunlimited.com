#!/usr/bin/env python3
"""Shared site navigation snippets for omni-prototypes/omni-galaxypop."""
import re
from pathlib import Path

LOCAL = "/omni-prototypes/omni-galaxypop"

# Remove legacy About Us nav item (replaced by Become Affiliate + Help).
ABOUT_US_NAV = re.compile(
    r'<a class="flex items-center gap-1\.5 px-3 py-2 rounded-lg text-sm font-medium text-\(--text-secondary\) hover:text-foreground hover:bg-\[rgba\(255,255,255,0\.04\)\] transition-all duration-200 touch-manipulation relative group" href="[^"]*about\.html"><svg[^>]*lucide-users[^>]*>.*?</svg>About Us<span class="absolute bottom-0\.5 left-3 right-3 h-\[2px\] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a>',
    re.DOTALL,
)

AFFILIATE_NAV = (
    '<a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium '
    '{affiliate_cls} transition-all duration-200 touch-manipulation relative group" '
    f'href="{LOCAL}/affiliate.html">'
    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" '
    'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
    'class="lucide lucide-handshake w-3.5 h-3.5" aria-hidden="true">'
    '<path d="m11 17 2 2a1 1 0 1 0 3-3"></path>'
    '<path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"></path>'
    '<path d="m21 3 1 11h-2"></path><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"></path><path d="M3 4h8"></path>'
    '</svg>Become Affiliate'
    '<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary '
    '{affiliate_underline} transition-transform duration-200 origin-left"></span></a>'
)

HELP_NAV = (
    '<a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium '
    '{help_cls} transition-all duration-200 touch-manipulation relative group" '
    f'href="{LOCAL}/help-center.html">Help'
    '<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary '
    '{help_underline} transition-transform duration-200 origin-left"></span></a>'
)

NAV_IDLE = "text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)]"
NAV_ACTIVE = "text-foreground bg-[rgba(255,255,255,0.04)]"
UNDERLINE_IDLE = "scale-x-0 group-hover:scale-x-100"
UNDERLINE_ACTIVE = "scale-x-100"


def nav_extras(*, affiliate_active: bool = False, help_active: bool = False) -> str:
    return AFFILIATE_NAV.format(
        affiliate_cls=NAV_ACTIVE if affiliate_active else NAV_IDLE,
        affiliate_underline=UNDERLINE_ACTIVE if affiliate_active else UNDERLINE_IDLE,
    ) + HELP_NAV.format(
        help_cls=NAV_ACTIVE if help_active else NAV_IDLE,
        help_underline=UNDERLINE_ACTIVE if help_active else UNDERLINE_IDLE,
    )


def patch_nav(html: str, *, affiliate_active: bool = False, help_active: bool = False) -> str:
    html = ABOUT_US_NAV.sub("", html)
    marker = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag w-3.5 h-3.5"'
    pricing_end = '</svg>Pricing<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a>'
    if marker in html and f'href="{LOCAL}/affiliate.html"' not in html:
        html = html.replace(
            pricing_end,
            pricing_end + nav_extras(affiliate_active=affiliate_active, help_active=help_active),
            1,
        )
    return html


def patch_all_pages(base: Path | None = None) -> None:
    base = base or Path(__file__).resolve().parent
    for path in sorted(base.glob("*.html")):
        if path.name == "index.html":
            continue
        raw = path.read_text(encoding="utf-8")
        active_affiliate = path.name == "affiliate.html"
        active_help = path.name == "help-center.html"
        out = patch_nav(raw, affiliate_active=active_affiliate, help_active=active_help)
        if out != raw:
            path.write_text(out, encoding="utf-8")
            print(f"updated nav: {path.name}")


if __name__ == "__main__":
    patch_all_pages()
