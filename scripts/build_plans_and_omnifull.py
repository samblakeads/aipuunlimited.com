#!/usr/bin/env python3
"""Build plans-v3-fixed checkout and omnifull multi-page landers (+ KK copies)."""
from __future__ import annotations

import json
import re
import shutil
from pathlib import Path

HTDOCS = Path(__file__).resolve().parent.parent
CHECKOUTS = HTDOCS / "checkouts"
OMNI_PAGES = HTDOCS / "omnirogue-pages"
MULTISTEP = HTDOCS / "multistep"
MULTISTEP_KK = HTDOCS / "multistep-kk"
LANDER_SRC = HTDOCS / "omnirogue" / "lander7randy3upgrades-omni"
LANDER_UNLIMITED_V2 = HTDOCS / "omnirogue" / "lander7upgrades-omniunlimited-v2"


def multistep_web_path(folder_name: str) -> str:
    return f"/multistep/{folder_name}"


def kk_name_for(folder_name: str) -> str:
    return f"{folder_name}-kk"


def kk_web_path(folder_name: str) -> str:
    return f"/{kk_name_for(folder_name)}"

CHECK_SVG = (
    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
    'stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" '
    'style="flex: 0 0 auto; color: var(--green);"><path d="M20 6 9 17l-5-5"></path></svg>'
)
X_SVG = (
    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
    'stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" '
    'style="flex: 0 0 auto; color: var(--fg-faint);"><path d="M18 6 6 18"></path>'
    '<path d="m6 6 12 12"></path></svg>'
)
INFO_DOT = (
    '<span class="info-dot gen-info"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" '
    'stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" '
    'style="flex: 0 0 auto;"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path>'
    '<path d="M12 8h.01"></path></svg><span class="gen-tip" role="tooltip">'
    '<span class="gen-tip-title">Get unlimited generations for {name}</span>'
    '<span class="gen-tip-row">Buy until: June 8</span>'
    '<span class="gen-tip-row">Available for 1 year after purchase on web</span></span></span>'
)


def modrow_on(name: str, badge: str, bt: str, info: bool = True) -> str:
    info_html = INFO_DOT.format(name=name) if info else ""
    return (
        f'<div class="modrow">{CHECK_SVG}<span class="mname">{name} {info_html}</span>'
        f'<span class="tail"><span class="bdg bdg-{bt}">{badge}</span></span></div>'
    )


def modrow_off(name: str) -> str:
    return f'<div class="modrow off">{X_SVG}<span class="mname">{name} </span><span class="tail"></span></div>'


def gens_block(rows: list[str]) -> str:
    return '<div class="section-head">∞ Unlimited &amp; Free Gens</div>' + "".join(rows)


def _replace_plan_gens(text: str, plan_id: str, new_gens: str) -> str:
    marker = f'{{id:"{plan_id}"'
    start = text.index(marker)
    gens_key = "gens:["
    gstart = text.index(gens_key, start)
    depth = 0
    i = gstart + len(gens_key) - 1
    while i < len(text):
        ch = text[i]
        if ch == "[":
            depth += 1
        elif ch == "]":
            depth -= 1
            if depth == 0:
                return text[:gstart] + new_gens + text[i + 1 :]
        i += 1
    raise ValueError(f"gens block not found for {plan_id}")


def _plan_block_span(text: str, plan_id: str) -> tuple[int, int]:
    marker = f'{{id:"{plan_id}"'
    start = text.index(marker)
    nxt = text.find('},{id:"', start + 1)
    end = nxt if nxt != -1 else text.index("],S=", start)
    return start, end


def _replace_in_plan_block(text: str, plan_id: str, old: str, new: str, count: int = -1) -> str:
    start, end = _plan_block_span(text, plan_id)
    block = text[start:end]
    updated = block.replace(old, new, count)
    return text[:start] + updated + text[end:]


def patch_bundle_js(text: str) -> str:
    o = r"\u221E"
    creator_gens = (
        f'gens:[{{n:"Flux Dev",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Flux Schnell",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Nano Banana",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Seedream 4.5",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}}]'
    )
    text = _replace_plan_gens(text, "creator", creator_gens)

    premium_gens = (
        f'gens:[{{n:"Seedream 4.5",on:!0,badge:"3,000 FREE GENS",bt:"free"}},'
        f'{{n:"Flux Schnell",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Flux Pro",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Nano Banana",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Nano Banana 2",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"GPT Image 2",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Imagen Ultra",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Luma Ray2 Flash",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"MiniMax Hailuo",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}}]'
    )
    text = _replace_plan_gens(text, "premium", premium_gens)

    pro_gens = (
        f'gens:[{{n:"Seedream 4.5",on:!0,q:"4K",badge:"10,000 FREE GENS",bt:"free"}},'
        f'{{n:"Flux Schnell",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Flux Pro",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Nano Banana",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Nano Banana 2",on:!0,q:"4K",badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"GPT Image 2",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Imagen Premium",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"LTX Video",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}},'
        f'{{n:"Veo 3.1",on:!0,badge:`${{o}} UNLIMITED`,bt:"unlim"}}]'
    )
    text = _replace_plan_gens(text, "pro", pro_gens)

    text = _replace_in_plan_block(text, "agency", 'mPrice:"149.99"', 'mPrice:"299.99"', 1)
    text = _replace_in_plan_block(text, "agency", 'yPrice:"1,499"', 'yPrice:"2,998"', 1)
    text = _replace_in_plan_block(text, "agency", 'credits:"10,000"', 'credits:"30,000"', 1)
    if 'mCredits:"30,000"' not in text[text.index('{id:"agency"'):]:
        text = _replace_in_plan_block(
            text,
            "agency",
            'ySeatCredits:"120,000",',
            'ySeatCredits:"120,000",mCredits:"30,000",yCredits:"360,000",',
            1,
        )
    text = _replace_in_plan_block(text, "agency", '"1,500 FREE GENS"', '"500 FREE GENS"', 1)
    text = _replace_in_plan_block(text, "agency", '{n:"Imagen Premium",badge:', '{n:"Imagen Ultra",badge:', 1)
    return text


def iter_pcol_blocks(html: str) -> list[tuple[int, int, str]]:
    matches = list(re.finditer(r'<div class="pcol(?: pop| best)?">', html))
    blocks = []
    for i, m in enumerate(matches):
        start = m.start()
        end = matches[i + 1].start() if i + 1 < len(matches) else len(html)
        h3 = re.search(r"<h3>([^<]+)</h3>", html[start : start + 5000])
        if h3:
            blocks.append((start, end, h3.group(1)))
    return blocks


def replace_gens_section(col_html: str, new_gens_html: str) -> str:
    pat = (
        r'<div class="section-head">∞ Unlimited &amp; Free Gens</div>'
        r'(?:<div class="modrow(?: off)?">.*?</div>)+'
    )
    if re.search(pat, col_html, re.S):
        return re.sub(pat, new_gens_html, col_html, count=1, flags=re.S)
    return col_html


def patch_pcol_block(block: str, name: str) -> str:
    creator_gens = gens_block([
        modrow_on("Flux Dev", "∞ UNLIMITED", "unlim"),
        modrow_on("Flux Schnell", "∞ UNLIMITED", "unlim"),
        modrow_on("Nano Banana", "∞ UNLIMITED", "unlim"),
        modrow_on("Seedream 4.5", "∞ UNLIMITED", "unlim"),
    ])
    premium_gens = gens_block([
        modrow_on("Seedream 4.5", "3,000 FREE GENS", "free"),
        modrow_on("Flux Schnell", "∞ UNLIMITED", "unlim"),
        modrow_on("Flux Pro", "∞ UNLIMITED", "unlim"),
        modrow_on("Nano Banana", "∞ UNLIMITED", "unlim"),
        modrow_on("Nano Banana 2", "∞ UNLIMITED", "unlim"),
        modrow_on("GPT Image 2", "∞ UNLIMITED", "unlim"),
        modrow_on("Imagen Ultra", "∞ UNLIMITED", "unlim"),
        modrow_on("Luma Ray2 Flash", "∞ UNLIMITED", "unlim"),
        modrow_on("MiniMax Hailuo", "∞ UNLIMITED", "unlim"),
    ])
    pro_gens = gens_block([
        modrow_on("Seedream 4.5", "10,000 FREE GENS", "free"),
        modrow_on("Flux Schnell", "∞ UNLIMITED", "unlim"),
        modrow_on("Flux Pro", "∞ UNLIMITED", "unlim"),
        modrow_on("Nano Banana", "∞ UNLIMITED", "unlim"),
        (
            f'<div class="modrow">{CHECK_SVG}<span class="mname">Nano Banana 2 </span>'
            f'<span class="tail"><span class="bdg bdg-q">4K</span>'
            f'<span class="bdg bdg-unlim">∞ UNLIMITED</span></span></div>'
        ),
        modrow_on("GPT Image 2", "∞ UNLIMITED", "unlim"),
        modrow_on("Imagen Premium", "∞ UNLIMITED", "unlim"),
        modrow_on("LTX Video", "∞ UNLIMITED", "unlim"),
        modrow_on("Veo 3.1", "∞ UNLIMITED", "unlim"),
    ])
    if name == "Creator":
        return replace_gens_section(block, creator_gens)
    if name == "Premium":
        return replace_gens_section(block, premium_gens).replace("Imagen Premium", "Imagen Ultra")
    if name == "Pro":
        return replace_gens_section(block, pro_gens)
    if name == "Agency":
        b = block.replace("149.99", "299.99")
        b = b.replace("1,499", "2,998")
        b = b.replace("10,000 credits/mo", "30,000 credits/mo")
        b = b.replace("120,000 credits/yr", "360,000 credits/yr")
        b = b.replace("1,500 FREE GENS", "500 FREE GENS")
        return b.replace("Imagen Premium", "Imagen Ultra")
    return block


def patch_checkout_html(html: str) -> str:
    blocks = iter_pcol_blocks(html)
    out = html
    for start, end, name in reversed(blocks):
        if name not in {"Creator", "Premium", "Agency", "Pro"}:
            continue
        old = html[start:end]
        new = patch_pcol_block(old, name)
        out = out[:start] + new + out[end:]
    return out


def build_plans_v3_fixed() -> None:
    src = CHECKOUTS / "plans-v3-sam"
    dst = CHECKOUTS / "plans-v3-fixed"
    if dst.exists():
        shutil.rmtree(dst)
    shutil.copytree(src, dst, ignore=shutil.ignore_patterns("*.bak*", "*.react-bak"))

    bundle = dst / "plans-pick-your-plan" / "js" / "bundle.js"
    bundle.write_text(patch_bundle_js(bundle.read_text()))

    index = dst / "index.html"
    html = index.read_text()
    html = html.replace("/checkouts/plans-v3-sam/", "/checkouts/plans-v3-fixed/")
    html = html.replace("plans-v3-sam", "plans-v3-fixed")
    html = patch_checkout_html(html)
    index.write_text(html)
    print(f"Built {dst}")


def rewrite_folder_paths(text: str, old: str, new_web_path: str) -> str:
    """Rewrite omnirogue-pages paths to an absolute web path like /multistep/foo."""
    text = text.replace("/omnirogue-pages/", f"{new_web_path}/")
    if old != "omnirogue-pages":
        text = text.replace(f"/{old}/", f"{new_web_path}/")
    return text


# Legal pages that exist locally inside each lander folder.
LOCAL_LEGAL_SLUGS = (
    "acceptable-use-policy",
    "privacy-policy",
    "terms-of-service",
    "data-deletion-request",
    "knowledge-base",
    "help-center",
    "about",
)


def localize_omnirogue_html(text: str, web_path: str) -> str:
    """Remove every omnirogue.com navigation/redirect from an HTML page.

    Legal/info links point at the local copies, all auth/marketing links
    (login, register, contact, billing, ...) funnel to the local checkout,
    and external font/_next preloads are dropped (fonts are self-hosted).
    """
    # Drop preload/preconnect/dns-prefetch hints that point at omnirogue.com.
    text = re.sub(r'<link\b[^>]*omnirogue\.com[^>]*>', "", text)

    # Local legal/info pages.
    for slug in LOCAL_LEGAL_SLUGS:
        text = re.sub(
            r'https?://(?:www\.)?omnirogue\.com/' + re.escape(slug) + r'/?',
            f"{web_path}/{slug}.html",
            text,
        )

    # Favicon -> local asset.
    text = text.replace("https://omnirogue.com/favicon.ico", f"{web_path}/assets/img/favicon.ico")

    # Anything else that still links to the omnirogue.com site (login, register,
    # contact, billing, signup, pricing, ...) becomes the local checkout. We keep
    # _next asset URLs out of this (handled by font self-hosting) and never touch
    # mailto: links.
    text = re.sub(
        r'https?://(?:www\.|app\.)?omnirogue\.com/(?!_next)[^"\')\s]*',
        f"{web_path}/checkout.html",
        text,
    )
    return text


def self_host_fonts(dst: Path, web_path: str) -> None:
    """Download omnirogue.com Inter subsets locally and repoint every reference."""
    import urllib.request

    fonts_dir = dst / "assets" / "fonts"
    fonts_dir.mkdir(parents=True, exist_ok=True)

    font_urls: set[str] = set()
    for css_path in dst.rglob("*.css"):
        text = css_path.read_text(errors="replace")
        font_urls.update(
            re.findall(r"https://omnirogue\.com/_next/static/media/[A-Za-z0-9._-]+\.woff2", text)
        )

    ua = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"
    for url in sorted(font_urls):
        name = url.rsplit("/", 1)[-1]
        target = fonts_dir / name
        if not target.exists():
            try:
                req = urllib.request.Request(url, headers={"User-Agent": ua, "Accept": "*/*"})
                with urllib.request.urlopen(req, timeout=30) as resp:
                    target.write_bytes(resp.read())
            except Exception as exc:  # pragma: no cover - network guard
                print(f"  WARN could not fetch {url}: {exc}")

    for css_path in dst.rglob("*.css"):
        text = css_path.read_text(errors="replace")
        if "omnirogue.com/_next/static/media/" in text:
            text = text.replace(
                "https://omnirogue.com/_next/static/media/",
                f"{web_path}/assets/fonts/",
            )
            css_path.write_text(text)


def extract_site_chrome(html: str) -> tuple[str, str]:
    nav_start = html.index('<div class="fixed top-0 left-0 right-0 z-50">')
    sub = html[nav_start:]
    nav_end = sub.index("</nav>") + len("</nav></div>")
    site_nav = sub[:nav_end]
    foot_marker = '<footer class="relative border-t border-border bg-background">'
    foot_start = html.index(foot_marker)
    foot_end = html.index("</footer>", foot_start) + len("</footer>")
    site_footer = html[foot_start:foot_end]
    return site_nav, site_footer


def site_chrome_head_extras(web_path: str, *, checkout: bool = False) -> str:
    pad_rule = (
        "[data-checkout-prototype=\"plans-pick-your-plan\"] .pyp-wrap"
        "{padding-top:calc(var(--nav-height) + env(safe-area-inset-top) + 12px);}"
        if checkout
        else ".omni-lander-wrap{padding-top:calc(var(--nav-height) + env(safe-area-inset-top));min-height:100vh;background:#07080c}"
    )
    # Load the same fonts the site nav/footer were designed against (the
    # Create Studio pages use Inter for body text), so the injected chrome
    # renders identically on the presell AND the checkout even though each
    # page ships its own body font (the checkout uses Hanken Grotesk).
    # preconnect speeds up the TLS/handshake to the font CDNs; display=swap
    # (in the URL) avoids invisible-text while the face loads. Duplicate links
    # injected across pages are collapsed by dedupe_head_links().
    fonts_link = (
        '<link rel="preconnect" href="https://fonts.googleapis.com">\n'
        '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>\n'
        '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?'
        "family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800"
        '&family=JetBrains+Mono:wght@400;500;600&display=swap">\n'
    )
    # Presell pages ship perf-overrides that globally disable backdrop-filter on
    # mobile; keep the site nav blur identical to brand pages.
    nav_blur_rule = (
        ".fixed.top-0.left-0.right-0.z-50 .backdrop-blur-xl,"
        '.fixed.top-0.left-0.right-0.z-50 [class*="backdrop-blur"]'
        "{backdrop-filter:blur(24px)!important;-webkit-backdrop-filter:blur(24px)!important;}"
    )
    return (
        f"{fonts_link}"
        f'<link rel="stylesheet" href="{web_path}/assets/main1.css">\n'
        f'<link rel="stylesheet" href="{web_path}/assets/main2.css">\n'
        f'<style id="omni-static-fixes">\n'
        f"html{{--header-height:64px;--nav-height:64px;--nav-bg:rgba(7,8,18,0.85);color-scheme:dark}}\n"
        f"{nav_blur_rule}\n"
        f"{pad_rule}\n"
        f"</style>\n"
    )


# Signals that mark a top-of-page element as the lander's OWN site header/nav,
# so we can remove it and keep only the injected canonical nav. A hero/section
# <header> that is NOT site chrome carries none of these and is left untouched.
_SITE_HEADER_SIGNALS = re.compile(
    r'<img\b[^>]*\b(?:alt|src|srcset|class)\s*=\s*"[^"]*logo'  # a logo image
    r'|aria-label="Open menu"|data-or-mobile-toggle'           # hamburger trigger
    r'|class="[^"]*\bnav\b[^"]*"'                               # nav-classed element
    r'|>\s*Home\s*<',                                           # a "Home" link/label
    re.I,
)


def _looks_like_site_header(fragment: str) -> bool:
    return bool(_SITE_HEADER_SIGNALS.search(fragment))


def strip_lander_site_header(html: str) -> str:
    """Remove the lander's OWN top header/nav (ANY markup) so only the injected
    canonical site nav remains — preventing a double header.

    Conservative: only strips the first <header> or a leading site <nav> that
    (a) appears before the first <section>/<main> and (b) carries site-chrome
    signals (logo, Home link, hamburger, or a .nav class). The announcement /
    promo bar carries none of these signals, so it always survives here for the
    dedicated announcement-bar treatment. Div-based custom headers that match
    nothing are left for the double-header build gate to flag.
    """
    content_m = re.search(r"<(?:section|main)\b", html, re.I)
    content_at = content_m.start() if content_m else len(html)

    for tag in ("header", "nav"):
        pat = re.compile(r"<%s\b[^>]*>.*?</%s>\s*" % (tag, tag), re.S | re.I)
        m = pat.search(html)
        # Only consider the first occurrence, and only if it precedes content.
        if m and m.start() < content_at and _looks_like_site_header(m.group(0)):
            return html[: m.start()] + html[m.end():]
    return html


def wrap_with_site_chrome(
    page_html: str,
    site_nav: str,
    site_footer: str,
    web_path: str,
    *,
    checkout: bool = False,
    strip_lander_header: bool = False,
    strip_lander_footer: bool = False,
) -> str:
    extras = site_chrome_head_extras(web_path, checkout=checkout)
    page_html = page_html.replace("</head>", extras + "</head>", 1)

    if strip_lander_header:
        page_html = strip_lander_site_header(page_html)
    if strip_lander_footer:
        page_html = re.sub(r"<footer>.*?</footer>\s*", "", page_html, count=1, flags=re.S)

    wrapper_open = (
        '<div id="__next"><div class="inter_49defa59-module__D5ngMq__variable">\n'
        f"{site_nav}\n"
        + ('<div class="omni-lander-wrap">\n' if not checkout else "")
    )
    wrapper_close = (
        ("</div>\n" if not checkout else "")
        + f"{site_footer}\n"
        + "</div></div>\n"
        + f'<script src="{web_path}/assets/static.js" defer></script>\n'
    )

    page_html = re.sub(r"<body>\s*", f"<body>\n{wrapper_open}", page_html, count=1)
    page_html = re.sub(r"</body>", f"{wrapper_close}</body>", page_html, count=1)
    return page_html


PERF_OVERRIDES_BACKDROP_GLOBAL = (
    "*, *::before, *::after { backdrop-filter: none !important; "
    "-webkit-backdrop-filter: none !important; }"
)
PERF_OVERRIDES_BACKDROP_SCOPED = (
    ".omni-lander-wrap, .omni-lander-wrap *, .omni-lander-wrap *::before, "
    ".omni-lander-wrap *::after { backdrop-filter: none !important; "
    "-webkit-backdrop-filter: none !important; }"
)


STANDARD_HTML_TAG = (
    '<html lang="en" data-theme-mode="dark" data-browser-safari="false" '
    'style="--banner-height: 0px;">'
)

NAV_ISOLATION_CSS = """<style id="omni-nav-isolation">
/* Site nav must render identically on lander, checkout, and studio pages. */
.fixed.top-0.left-0.right-0.z-50 {
  z-index: 50 !important;
  isolation: isolate;
}
.fixed.top-0.left-0.right-0.z-50,
.fixed.top-0.left-0.right-0.z-50 *,
.fixed.top-0.left-0.right-0.z-50 *::before,
.fixed.top-0.left-0.right-0.z-50 *::after {
  box-sizing: border-box;
}
.omni-lander-wrap .promo-bar {
  position: sticky;
  top: calc(var(--nav-height, 64px) + env(safe-area-inset-top, 0px));
  z-index: 40;
}
/* Announcement / promo bar pinned cleanly directly under the fixed site nav.
   Keyed on the stable kk-announce-bar hook (added at build time) so it works
   regardless of the lander's original bar class name. */
.omni-lander-wrap .kk-announce-bar {
  position: sticky;
  top: calc(var(--nav-height, 64px) + env(safe-area-inset-top, 0px));
  z-index: 40;
}
.omni-lander-wrap .kk-announce-bar .container {
  flex-wrap: nowrap;
  gap: 10px;
  min-height: 0;
}
@media (max-width: 700px) {
  /* On phones, don't let the announcement bar dominate the header: keep it on
     a single compact row and let it scroll away instead of stacking under the
     nav, so the mobile header is just the slim site nav. */
  .omni-lander-wrap .kk-announce-bar {
    position: static;
  }
  .omni-lander-wrap .kk-announce-bar .container {
    flex-wrap: nowrap;
    justify-content: flex-start;
    gap: 8px;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}
</style>
"""


def normalize_html_attrs(html: str) -> str:
    return re.sub(r"<html[^>]*>", STANDARD_HTML_TAG, html, count=1)


def prefix_css(css: str, prefix: str) -> str:
    """Prefix selectors in a stylesheet, leaving at-rules intact."""
    out: list[str] = []
    i = 0
    n = len(css)
    while i < n:
        if css.startswith("/*", i):
            end = css.index("*/", i) + 2
            out.append(css[i:end])
            i = end
            continue
        if css[i] == "@":
            brace = css.index("{", i)
            header = css[i:brace].strip()
            depth = 0
            j = brace
            while j < n:
                if css[j] == "{":
                    depth += 1
                elif css[j] == "}":
                    depth -= 1
                    if depth == 0:
                        block = css[i : j + 1]
                        if header.startswith("@media") or header.startswith("@supports"):
                            inner = block[block.index("{") + 1 : -1]
                            out.append(header + "{" + prefix_css(inner, prefix) + "}")
                        else:
                            out.append(block)
                        i = j + 1
                        break
                j += 1
            else:
                out.append(css[i:])
                break
            continue
        brace = css.find("{", i)
        if brace == -1:
            out.append(css[i:])
            break
        selector = css[i:brace].strip()
        if not selector:
            i = brace + 1
            continue
        depth = 0
        j = brace
        while j < n:
            if css[j] == "{":
                depth += 1
            elif css[j] == "}":
                depth -= 1
                if depth == 0:
                    body = css[brace + 1 : j]
                    if selector.startswith(":root"):
                        scoped = prefix + " {\n" + body + "\n}"
                    else:
                        scoped_selectors = []
                        for part in selector.split(","):
                            part = part.strip()
                            if not part:
                                continue
                            if part.startswith(":root"):
                                scoped_selectors.append(prefix)
                            else:
                                scoped_selectors.append(f"{prefix} {part}")
                        scoped = ", ".join(scoped_selectors) + " {" + body + "}"
                    out.append(scoped)
                    i = j + 1
                    break
            j += 1
    return "".join(out)


def write_scoped_lander_css(folder: Path) -> None:
    src = folder / "assets" / "css" / "main.css"
    dst = folder / "assets" / "css" / "main.scoped.css"
    if not src.exists():
        return
    raw = src.read_text(errors="replace")
    scoped = "/* Scoped to .omni-lander-wrap — must not affect site nav/footer chrome. */\n"
    scoped += prefix_css(raw, ".omni-lander-wrap")
    scoped = re.sub(
        r"\.omni-lander-wrap\s*/\*[^*]*\*/\s*\nbody::(before|after)",
        r".omni-lander-wrap::\1",
        scoped,
    )
    scoped = re.sub(
        r"(?<![\w.-])body::(before|after)",
        r".omni-lander-wrap::\1",
        scoped,
    )
    scoped = scoped.replace(".omni-lander-wrap body {", ".omni-lander-wrap {")
    scoped = scoped.replace(".omni-lander-wrap html {", ".omni-lander-wrap {")
    dst.write_text(scoped)


def inject_nav_isolation(html: str) -> str:
    if 'id="omni-nav-isolation"' in html:
        return html
    if "</head>" in html:
        return html.replace("</head>", NAV_ISOLATION_CSS + "</head>", 1)
    return html


def patch_checkout_page_styles(html: str) -> str:
    """Keep checkout body styles from bleeding into the shared site nav."""
    html = html.replace(
        "html, body { margin: 0; padding: 0; background: #08060F; color: #fff; }",
        '[data-checkout-prototype="plans-pick-your-plan"] { background: #08060F; color: #fff; }',
    )
    html = html.replace(
        "html, body { margin: 0; padding: 0; background: #08060F; color: #fff; }\n",
        '[data-checkout-prototype="plans-pick-your-plan"] { background: #08060F; color: #fff; }\n',
    )
    return html


def use_scoped_lander_css(html: str, web_path: str, folder: Path | None = None) -> str:
    # When we know the output folder and the scoped stylesheet was never generated
    # (the lander ships no external assets/css/main.css — its styles are inline, as
    # with lander11 / sabrina), there is nothing to scope: don't rewrite or inject a
    # <link> to a main.scoped.css that does not exist (that left a broken reference).
    if folder is not None and not (folder / "assets" / "css" / "main.scoped.css").exists():
        return html
    html = html.replace(f"{web_path}/assets/css/main.css", f"{web_path}/assets/css/main.scoped.css")
    html = html.replace("assets/css/main.css", f"{web_path}/assets/css/main.scoped.css")
    html = html.replace(
        f'href="{web_path}/assets/css/main.scoped.css"',
        f'href="{web_path}/assets/css/main.scoped.css" data-lander-scoped="1"',
    )
    # Load scoped lander CSS after site chrome so nav tokens win.
    link = f'<link rel="stylesheet" href="{web_path}/assets/css/main.scoped.css" data-lander-scoped="1">'
    html = re.sub(
        rf'<link rel="stylesheet" href="{re.escape(web_path)}/assets/css/main\.scoped\.css"(?: data-lander-scoped="1")?>',
        "",
        html,
        count=1,
    )
    marker = '<style id="omni-static-fixes">'
    if marker in html and link not in html:
        html = html.replace(marker, link + "\n" + marker, 1)
    elif link not in html:
        html = html.replace("</head>", link + "\n</head>", 1)
    return html


NAV_BASE_CSS = (
    "html{--header-height:64px;--nav-height:64px;--nav-bg:rgba(7,8,18,0.85);color-scheme:dark}\n"
    ".fixed.top-0.left-0.right-0.z-50 .backdrop-blur-xl,"
    '.fixed.top-0.left-0.right-0.z-50 [class*="backdrop-blur"]'
    "{backdrop-filter:blur(24px)!important;-webkit-backdrop-filter:blur(24px)!important;}\n"
)


def ensure_nav_base_css(html: str) -> str:
    """Guarantee identical nav tokens + blur on every page."""
    marker = 'id="omni-static-fixes"'
    if marker not in html:
        block = f'<style id="omni-static-fixes">\n{NAV_BASE_CSS}</style>\n'
        return html.replace("</head>", block + "</head>", 1)

    def _merge(match: re.Match[str]) -> str:
        body = match.group(1)
        if '.fixed.top-0.left-0.right-0.z-50 [class*="backdrop-blur"]' in body:
            # Normalize the shared nav prefix, keep page-specific rules below it.
            rest = re.sub(
                r"html\{--header-height:64px;--nav-height:64px;--nav-bg:[^}]+\}[^\n]*\n",
                "",
                body,
            )
            rest = re.sub(
                r"\.fixed\.top-0\.left-0\.right-0\.z-50 \.backdrop-blur-xl,.*?\}\n",
                "",
                rest,
                count=1,
                flags=re.S,
            )
            return f'<style id="omni-static-fixes">\n{NAV_BASE_CSS}{rest}</style>'

        return f'<style id="omni-static-fixes">\n{NAV_BASE_CSS}{body}</style>'

    return re.sub(
        r'<style id="omni-static-fixes">(.*?)</style>',
        _merge,
        html,
        count=1,
        flags=re.S,
    )


def dedupe_head_links(html: str) -> str:
    """Collapse duplicate <head> resources to cut first-load weight.

    Chrome injection can emit the same stylesheet / font <link> (and the
    ``omni-static-fixes`` <style>) more than once per page. Removing identical
    duplicates is purely additive: the first occurrence of each unique tag is
    kept so nothing that the page actually needs is dropped.
    """
    m = re.search(r"<head\b[^>]*>(.*?)</head>", html, flags=re.S | re.I)
    if not m:
        return html
    head = m.group(1)

    seen: set[str] = set()

    def _link_sub(mm: "re.Match") -> str:
        tag = mm.group(0)
        key = re.sub(r"\s+", " ", tag.strip()).lower()
        if key in seen:
            return ""
        seen.add(key)
        return tag

    new_head = re.sub(r"<link\b[^>]*>", _link_sub, head, flags=re.I)

    # Keep only the first identical omni-static-fixes style block.
    blocks = list(re.finditer(r'<style id="omni-static-fixes">.*?</style>',
                              new_head, flags=re.S))
    if len(blocks) > 1:
        for mm in reversed(blocks[1:]):
            new_head = new_head[:mm.start()] + new_head[mm.end():]

    if new_head == head:
        return html
    return html[:m.start(1)] + new_head + html[m.end(1):]


def finalize_omnifull_headers(
    folder: Path,
    web_path: str,
    site_nav: str,
    site_footer: str,
) -> None:
    write_scoped_lander_css(folder)
    for path in sorted(folder.glob("*.html")):
        text = path.read_text(errors="replace")
        text = normalize_html_attrs(text)
        text = sync_site_chrome_on_page(text, site_nav, site_footer)
        if path.name == "checkout.html":
            text = patch_checkout_page_styles(text)
        if path.name == "index.html":
            text = use_scoped_lander_css(text, web_path, folder)
        text = ensure_nav_base_css(text)
        text = inject_nav_isolation(text)
        text = dedupe_head_links(text)
        path.write_text(text)


# Back-compat alias
finalize_omnifullred_headers = finalize_omnifull_headers


def sync_site_chrome_on_page(html: str, site_nav: str, site_footer: str) -> str:
    """Replace site nav/footer so every page uses identical chrome."""
    html = re.sub(
        r'<div class="fixed top-0 left-0 right-0 z-50">.*?</nav></div>',
        site_nav.rstrip("\n"),
        html,
        count=1,
        flags=re.S,
    )
    html = re.sub(
        r'<footer class="relative border-t border-border bg-background">.*?</footer>',
        site_footer.rstrip("\n"),
        html,
        count=1,
        flags=re.S,
    )
    return html


def normalize_presell_index(html: str, site_nav: str, site_footer: str) -> str:
    """Keep presell index.html site chrome identical to brand pages."""
    html = re.sub(
        r'<html lang="en">',
        '<html lang="en" data-theme-mode="dark" data-browser-safari="false" '
        'style="--banner-height: 0px;">',
        html,
        count=1,
    )
    html = html.replace(PERF_OVERRIDES_BACKDROP_GLOBAL, PERF_OVERRIDES_BACKDROP_SCOPED)
    html = html.replace(
        "@media (prefers-reduced-motion: reduce) {\n"
        "    *, *::before, *::after {",
        "@media (prefers-reduced-motion: reduce) {\n"
        "    .omni-lander-wrap, .omni-lander-wrap *, .omni-lander-wrap *::before, "
        ".omni-lander-wrap *::after {",
    )
    html = html.replace(
        "  footer { content-visibility: auto; contain-intrinsic-size: 1px 600px; }",
        "  .omni-lander-wrap footer { content-visibility: auto; contain-intrinsic-size: 1px 600px; }",
    )
    html = re.sub(
        r'<div class="fixed top-0 left-0 right-0 z-50">.*?</nav></div>',
        site_nav.rstrip("\n"),
        html,
        count=1,
        flags=re.S,
    )
    html = re.sub(
        r'<footer class="relative border-t border-border bg-background">.*?</footer>',
        site_footer.rstrip("\n"),
        html,
        count=1,
        flags=re.S,
    )
    # Drop presell-only font overrides so nav inherits the same stack as brand pages.
    html = re.sub(
        r"\.fixed\.top-0\.left-0\.right-0\.z-50,\.fixed\.top-0\.left-0\.right-0\.z-50 \*,"
        r"footer\.relative\.border-t\.border-border\.bg-background,"
        r"footer\.relative\.border-t\.border-border\.bg-background \*"
        r"\{font-family:[^}]+\}\n?",
        "",
        html,
    )
    return html


# Higher subscription tier featured in place of the (discontinued) $399 Lifetime
# card. The funnel name encodes the hero price: "-49" -> Premium $49.99,
# "-299" -> Agency $299.99.
FEATURE_PLANS = {
    "omnifull-plans-v3-49": {
        "name": "Premium",
        "price": "$49.99",
        "price_num": "49.99",
        "period": "/mo",
        "token": "premium",
        "badge": "Most Popular Upgrade",
        "tag1": "More models · more free gens",
        "tag2": "Priority generation queue",
        "sub": (
            "Love the $14.99 pass but want room to run? <b style=\"color:#fff;\">Premium</b> "
            "unlocks the heavy-hitter models, a far bigger monthly allotment and priority "
            "speed &mdash; for one simple monthly price. Same platform, a lot more horsepower."
        ),
        "bullets": [
            "<b style=\"color:#fff;\">3,000 FREE generations every month</b> &middot; 10&times; the Creator allotment",
            "<b style=\"color:#fff;\">Premium flagship models unlocked</b> &middot; Seedance, Kling, Veo, GPT Image 2 &amp; more",
            "<b style=\"color:#fff;\">Priority generation queue</b> &middot; skip the line at peak hours",
            "Higher-resolution images &amp; longer video exports",
            "Everything in the $14.99 Creator pass, included",
            "Cancel any time &middot; 30-day money-back guarantee",
        ],
        "stats": [
            ("3,000", "free gens every month"),
            ("140+", "models · premium tier unlocked"),
            ("Priority", "faster generation queue"),
        ],
        "rationale": (
            "<b>Why members upgrade:</b> if you're generating every day, Premium pays for "
            "itself in saved time and credits &mdash; more free gens, the best models and "
            "priority speed, all for less than a single standalone AI subscription."
        ),
        "once_only": "★ Premium · Monthly",
        "strike": "vs $20–60/mo for a single AI tool",
        "payline": "$49.99 / month · cancel anytime",
        "cta": "Upgrade to Premium &mdash; $49.99/mo &rarr;",
        "cta_sub": "Billed monthly · instant access · 30-day money-back guarantee",
        "purpose": (
            "more horsepower. The biggest monthly allotment, the premium flagship models and "
            "priority speed for creators who generate every day."
        ),
        "c3_tag": "Billed monthly · cancel any time · 30-day guarantee",
        "c3_bullets": [
            ("on", "<b style=\"color:#fff;\">Everything in the $14.99/mo plan</b>"),
            ("on", "+ <b style=\"color:#fff;\">3,000 FREE gens every month</b>"),
            ("on", "+ <b style=\"color:#fff;\">Premium flagship models unlocked</b>"),
            ("on", "+ Priority generation queue"),
            ("on", "+ Higher-res images &amp; longer video exports"),
            ("on", "Cancel any time &middot; 30-day money-back guarantee"),
        ],
        "obj_q": '"Why upgrade to Premium instead of staying on $14.99?"',
        "obj_a": (
            "If you generate every day, Premium pays for itself &mdash; <b>3,000 free gens a "
            "month, the premium flagship models and priority speed.</b> Stay on $14.99/mo as "
            "long as you like and upgrade only when you're ready."
        ),
        "note_line": (
            '<a href="#plans" style="color:#d6c7ff;border-bottom:1px dotted rgba(214,199,255,.5);">'
            'Premium ($49.99/mo)</a> keeps the same platform and steps you up to the heavy-hitter '
            "models, 3,000 free gens a month and a priority queue."
        ),
        "faq_diff": (
            '<b style="color:#d6c7ff;">Premium ($49.99/mo)</b> keeps the same platform and adds the '
            "heavy-hitter models, 3,000 free generations a month and a priority queue &mdash; for "
            "creators who generate every day. Cancel any time."
        ),
        "faq_why_q": '"Why upgrade to Premium instead of staying on $14.99/mo?"',
        "faq_why_a": (
            "If you only dabble, the $14.99/mo plan is plenty. But if you generate every day, "
            "Premium pays for itself: 3,000 free generations a month (10&times; the Creator "
            "allotment), the premium flagship models unlocked, a priority generation queue and "
            "higher-res / longer exports. Start at $14.99/mo and upgrade to Premium the moment you "
            "want more room to run &mdash; your founder rate stays locked either way."
        ),
    },
    "omnifull-plans-v3-299": {
        "name": "Agency",
        "price": "$299.99",
        "price_num": "299.99",
        "period": "/mo",
        "token": "agency",
        "badge": "Best for Teams & Agencies",
        "tag1": "Multiple seats · shared workspace",
        "tag2": "Highest limits & priority",
        "sub": (
            "Running a team or an agency? <b style=\"color:#fff;\">Agency</b> gives every seat "
            "the full platform, the highest generation limits, shared billing and analytics "
            "&mdash; built to scale client work without juggling a dozen separate tools."
        ),
        "bullets": [
            "<b style=\"color:#fff;\">Team seats on one shared workspace</b> &middot; shared credits &amp; assets",
            "<b style=\"color:#fff;\">Highest generation limits</b> on the platform",
            "<b style=\"color:#fff;\">All 140+ flagship models</b> &middot; premium tier &amp; priority queue",
            "Shared analytics, SSO &amp; one-minute creator onboarding",
            "Everything in Creator, Premium &amp; Pro, included",
            "Cancel any time &middot; 30-day money-back guarantee",
        ],
        "stats": [
            ("Team", "seats on one workspace"),
            ("Highest", "limits on the platform"),
            ("Priority", "support &amp; generation"),
        ],
        "rationale": (
            "<b>Why agencies pick this:</b> one workspace replaces Midjourney, Runway, "
            "ElevenLabs and more for your whole team &mdash; shared billing, shared assets, "
            "and onboarding a new creator takes about a minute."
        ),
        "once_only": "★ Agency · Monthly",
        "strike": "vs $1,000s/mo in separate team tools",
        "payline": "$299.99 / month per workspace · cancel anytime",
        "cta": "Go Agency &mdash; $299.99/mo &rarr;",
        "cta_sub": "Billed monthly · instant access · 30-day money-back guarantee",
        "purpose": (
            "scale for teams. Multiple seats on one workspace, the highest limits on the "
            "platform and shared billing built for agencies."
        ),
        "c3_tag": "Per workspace · billed monthly · cancel any time",
        "c3_bullets": [
            ("on", "<b style=\"color:#fff;\">Everything in Creator + Premium + Pro</b>"),
            ("on", "+ <b style=\"color:#fff;\">Team seats on one workspace</b>"),
            ("on", "+ <b style=\"color:#fff;\">Highest limits on the platform</b>"),
            ("on", "+ Shared analytics, SSO &amp; onboarding"),
            ("on", "+ All 140+ flagship models, premium tier"),
            ("on", "Cancel any time &middot; 30-day money-back guarantee"),
        ],
        "obj_q": '"Why go Agency instead of staying on $14.99?"',
        "obj_a": (
            "For a whole team, Agency is the cheap option &mdash; <b>multiple seats on one "
            "workspace, the highest limits and shared billing</b> replace a dozen separate tools. "
            "Start at $14.99/mo and move up when your team grows."
        ),
        "note_line": (
            '<a href="#plans" style="color:#d6c7ff;border-bottom:1px dotted rgba(214,199,255,.5);">'
            'Agency ($299.99/mo)</a> is the same platform built for teams &mdash; multiple seats on '
            "one workspace, the highest limits and shared billing."
        ),
        "faq_diff": (
            '<b style="color:#d6c7ff;">Agency ($299.99/mo)</b> is the same platform built for teams: '
            "multiple seats on one shared workspace, the highest limits on the platform, shared "
            "analytics and SSO. Cancel any time."
        ),
        "faq_why_q": '"Why go Agency instead of staying on $14.99/mo?"',
        "faq_why_a": (
            "For a single creator, the $14.99/mo plan is plenty. Agency is for teams and studios: "
            "every seat gets the full platform, you share one workspace, credits and assets, you "
            "get the highest limits on the platform plus shared analytics, SSO and one-minute "
            "onboarding. One workspace replaces Midjourney, Runway, ElevenLabs and more for the "
            "whole team. Start at $14.99/mo and move up to Agency when your team grows."
        ),
    },
}

_CHECK_LI = (
    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
    'stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>'
)


def repurpose_lifetime(html: str, web_path: str, plan: dict) -> str:
    """Replace the discontinued $399 Lifetime offer with a higher subscription tier."""
    checkout = f"{web_path}/checkout.html"
    name = plan["name"]
    price = plan["price"]
    period = plan["period"]

    bullets = "".join(
        f'\n          <li>{_CHECK_LI} <span>{b}</span></li>' for b in plan["bullets"]
    )
    stats = "".join(
        f'\n          <div>\n            <b>{n}</b>\n            <span>{l}</span>\n          </div>'
        for n, l in plan["stats"]
    )
    new_big_card = f"""<!-- ========= AOV PATH 2: {name} — step up to a higher plan ========= -->
    <div class="life399-card" data-reveal>
      <div class="life399-l">
        <div class="ai-badges">
          <span class="ai-badge violet">{plan['badge']}</span>
          <span class="ai-badge outline-gold">{plan['tag1']}</span>
          <span class="ai-badge outline-gold">{plan['tag2']}</span>
        </div>
        <h3>Want more firepower? <span class="gold-grad">Step up to {name}</span>.</h3>
        <p class="life399-sub">{plan['sub']}</p>

        <ul class="life399-bullets">{bullets}
        </ul>

        <div class="life399-math">{stats}
        </div>

        <p class="upg49-rationale" style="margin-top:14px;">{plan['rationale']}</p>
      </div>

      <div class="life399-r">
        <span class="once-only">{plan['once_only']}</span>
        <div class="life399-strike">{plan['strike']}</div>
        <div class="life399-price">
          <span class="cur">$</span>
          <span class="num">{plan['price_num']}</span>
        </div>
        <div class="pay-once">{plan['payline']}</div>
        <a class="life399-cta" href="{checkout}" data-tier="{plan['token']}" data-value="{plan['price_num']}">{plan['cta']}</a>
        <div class="life399-cta-sub">{plan['cta_sub']}</div>
      </div>
    </div>

    """
    html = re.sub(
        r'<!-- ========= AOV PATH 2:.*?(?=<p style="text-align:center;margin:22px auto 0;)',
        lambda _m: new_big_card,
        html,
        count=1,
        flags=re.S,
    )

    c3_bullets = "".join(
        (
            f'\n            <li>{_CHECK_LI} {txt}</li>'
            if kind == "on"
            else f'\n            <li class="c3-skip">{txt}</li>'
        )
        for kind, txt in plan["c3_bullets"]
    )
    new_c3 = f"""<article class="c3-card c3-lifetime">
          <span class="c3-badge">★ {plan['badge']}</span>
          <div class="c3-eyebrow">OmniRogue {name}</div>
          <h3>{name} &middot; {price}{period}</h3>
          <div class="c3-price">{price}<small>{period}</small></div>
          <div class="c3-tag">{plan['c3_tag']}</div>
          <div class="c3-purpose">
            <b>Purpose:</b> {plan['purpose']}
          </div>
          <ul class="c3-feats">{c3_bullets}
          </ul>
          <a class="c3-cta" href="{checkout}" data-tier="{plan['token']}" data-value="{plan['price_num']}">{plan['cta']}</a>
          <div class="c3-cta-sub">{plan['cta_sub']}</div>
        </article>"""
    html = re.sub(
        r'<article class="c3-card c3-lifetime">.*?</article>',
        lambda _m: new_c3,
        html,
        count=1,
        flags=re.S,
    )

    # ---- Scattered copy + meta references to the old $399 Lifetime offer ----
    replacements = [
        (
            "or Lock Lifetime $399",
            f"or go {name} ({price}/mo)",
        ),
        (
            ", or lock in $399 Lifetime Access",
            f", or step up to {name} for {price}/mo",
        ),
        (
            "or lock in $399 Lifetime Access.",
            f"or step up to {name} for {price}/mo.",
        ),
        (
            '<a class="fd-lifetime" href="' + checkout + '" data-tier="lifetime" data-value="399">Lock Lifetime &mdash; $399 &rarr;</a>',
            f'<a class="fd-lifetime" href="{checkout}" data-tier="{plan["token"]}" data-value="{plan["price_num"]}">{plan["cta"]}</a>',
        ),
        (
            "Start monthly. Add the vault. Or lock lifetime.",
            f"Start monthly. Add the vault. Or go {name}.",
        ),
        (
            '<b style="color:#d6c7ff;">$399 Lifetime</b> is for the member who already knows AI is part of how they work, forever.',
            f'<b style="color:#d6c7ff;">{name} ({price}/mo)</b> is for members who want the highest limits and the best models, every day.',
        ),
        (
            "See AI Creator Secrets &amp; $399 Lifetime",
            f"See AI Creator Secrets &amp; {name}",
        ),
        (
            "See the AI Creator Secrets and $399 Lifetime path here",
            f"See the AI Creator Secrets and {name} path here",
        ),
        (
            "AI Creator Secrets &amp; $399 Lifetime here",
            f"AI Creator Secrets &amp; {name} here",
        ),
        (
            "AI Creator Secrets and $399 Lifetime path",
            f"AI Creator Secrets and {name} path",
        ),
        (
            "How do I skip monthly billing entirely?",
            "How do I unlock more models &amp; higher limits?",
        ),
        (
            "some lock in $399 Lifetime to stop renewing forever",
            f"some step up to {name} for the highest limits and best models",
        ),
        (
            "Lifetime is the rational choice if you'll use OmniRogue for more than ~27 months.",
            f"{name} is the rational choice if you create every day and want the highest limits.",
        ),
        (
            "Start at <a href=\"" + checkout + "\" style=\"color:var(--brand);border-bottom:1px dotted var(--brand);\">$14.99/mo</a> and add either upgrade later. Both upgrades stack with the monthly plan. Neither is required to use OmniRogue.",
            f"Start at <a href=\"{checkout}\" style=\"color:var(--brand);border-bottom:1px dotted var(--brand);\">$14.99/mo</a> and upgrade any time. Add the AI Creator Secrets vault, or move up to {name} whenever you're ready. Neither is required to start.",
        ),
        # c3-upgrade ($199 vault) skip-line referenced Lifetime pairing.
        (
            "Monthly app billing still applies (unless paired with Lifetime)",
            "Monthly app billing applies &middot; cancel any time",
        ),
        # "A note on the optional upgrades" paragraph.
        (
            'The <a href="#plans" style="color:#d6c7ff;border-bottom:1px dotted rgba(214,199,255,.5);">$399 Lifetime</a> is the same platform, just paid once instead of monthly.',
            plan["note_line"],
        ),
        # Objection row.
        ('<div class="obj-q">"$399 Lifetime feels like a lot vs $14.99."</div>', f'<div class="obj-q">{plan["obj_q"]}</div>'),
        (
            '<div class="obj-a">$14.99/mo for ~27 months equals $399. <b>Past month 27, every additional month is free for life.</b> 19/20 members stay past that mark anyway.</div>',
            f'<div class="obj-a">{plan["obj_a"]}</div>',
        ),
        ("You can move to $399 Lifetime any time.", f"You can upgrade to {name} any time."),
        ("the AI Creator Secrets or $399 Lifetime path", f"the AI Creator Secrets or {name} path"),
        # FAQ: difference between tiers.
        (
            '"What\'s the difference between $14.99/mo, AI Creator Secrets, and $399 Lifetime?"',
            f'"What\'s the difference between $14.99/mo, AI Creator Secrets, and {name}?"',
        ),
        (
            '<b style="color:#d6c7ff;">$399 Lifetime Access (one-time)</b> is the same platform as the monthly plan, but you pay once and never see a monthly bill again. For the member who already knows AI is part of how they work for the next 2+ years.',
            plan["faq_diff"],
        ),
        # FAQ: why pay $399 once.
        (
            '"Why would I pay $399 once instead of $14.99/mo?"',
            plan["faq_why_q"],
        ),
        (
            "Pure math. $14.99/mo for ~27 months = $399. Past that, every additional month is free for life. If you already know you'll use OmniRogue for more than two years (about 19/20 of our members do), Lifetime is the rational choice. You also lock out any future price increases and never have to think about a monthly rebill again. If you're not sure yet, start at $14.99/mo &mdash; you can move to Lifetime any time once you've decided.",
            plan["faq_why_a"],
        ),
        # Remaining FAQ entries that mentioned the Lifetime option.
        (
            '"Can I add AI Creator Secrets or $399 Lifetime later, or only at sign-up?"',
            f'"Can I add AI Creator Secrets or {name} later, or only at sign-up?"',
        ),
        (
            "You can upgrade to Lifetime any time from your dashboard.",
            f"You can upgrade to {name} any time from your dashboard.",
        ),
        (
            '"Can I get a refund on AI Creator Secrets or $399 Lifetime?"',
            f'"Can I get a refund on AI Creator Secrets or {name}?"',
        ),
        (
            "Don't love the AI Creator Secrets vault or Lifetime?",
            f"Don't love the AI Creator Secrets vault or {name}?",
        ),
        (
            "<b>unlimited text AI usage</b> on the monthly plan, the Upgrade and Lifetime.",
            "<b>unlimited text AI usage</b> on the monthly plan and every higher tier.",
        ),
        # Consistency: the offer is a "double" guarantee everywhere else.
        (
            '<b style="color:#c4ff96;">Triple</b> money-back guarantee',
            '<b style="color:#c4ff96;">Double</b> money-back guarantee',
        ),
    ]
    for old, new in replacements:
        html = html.replace(old, new)

    return html


def build_lander_index_html(web_path: str, checkout_href: str) -> str:
    return build_lander_index_html_from_src(web_path, checkout_href, LANDER_SRC)


def build_lander_index_html_from_src(
    web_path: str, checkout_href: str, lander_src: Path
) -> str:
    folder_slug = web_path.strip("/").split("/")[-1]
    src = (lander_src / "index.php").read_text()
    src = src.replace(f"/omnirogue/{lander_src.name}/", f"{web_path}/")
    src = src.replace(lander_src.name, folder_slug)
    for attr in ("href", "src"):
        src = src.replace(f'{attr}="assets/', f'{attr}="{web_path}/assets/')
    src = re.sub(
        r'<meta property="og:url" content="[^"]*">',
        f'<meta property="og:url" content="{web_path}/index.html">',
        src,
    )
    src = re.sub(
        r'<meta property="og:image" content="[^"]*">',
        f'<meta property="og:image" content="{web_path}/assets/img/logo-omnirogue.png">',
        src,
    )
    src = re.sub(
        r'<link rel="canonical" href="[^"]*">',
        f'<link rel="canonical" href="{web_path}/index.html">',
        src,
    )
    src = re.sub(r"<\?= htmlspecialchars\(\$_SERVER\['HTTP_HOST'\]\); \?>", "", src)
    src = re.sub(r"<\?= htmlspecialchars\(strtok\(\$_SERVER\['REQUEST_URI'\], '\?'\)\); \?>", "", src)
    src = src.replace("https://assets/", f"{web_path}/assets/")
    src = src.replace("<?= $link['step1link']; ?>", checkout_href)
    return src


def install_checkout(folder: Path, web_path: str, checkout_name: str) -> None:
    src_dir = CHECKOUTS / checkout_name
    dest_sub = folder / "plans-pick-your-plan"
    if dest_sub.exists():
        shutil.rmtree(dest_sub)
    shutil.copytree(src_dir / "plans-pick-your-plan", dest_sub)
    for logo in ("logo-aipu.png",):
        lp = src_dir / logo
        if lp.exists():
            shutil.copy2(lp, folder / logo)

    entitlements = src_dir / "plan-entitlements.json"
    if entitlements.exists():
        shutil.copy2(entitlements, folder / "plan-entitlements.json")

    checkout_html = src_dir / "index.html"
    html = checkout_html.read_text()
    html = html.replace(f"/checkouts/{checkout_name}/", f"{web_path}/")
    html = html.replace("../assets/aipu-logo-horizontal.png", f"{web_path}/logo-aipu.png")
    # Brand casing: the checkout source uses "OMniRogue"; the rest of the site is "OmniRogue".
    html = html.replace("OMniRogue", "OmniRogue")
    (folder / "checkout.html").write_text(html)


STATIC_JS_BUST = "20260610a"

LANDER_BASE_SNIPPET = "  var __LANDER_BASE = '{web_path}';\n"


def inject_kk_page_script(html: str) -> str:
    snippet = (
        "<script>\n"
        "window.__LANDER_BASE=<?= json_encode($__web, JSON_UNESCAPED_SLASHES); ?>;\n"
        "window.__KK_CHECKOUT_URL=<?= json_encode($__checkout, JSON_UNESCAPED_SLASHES); ?>;\n"
        "window.__KK_REGISTER_CHECKOUT=<?= json_encode($offer['registercheckout']['link']['step1link'] ?? '', JSON_UNESCAPED_SLASHES); ?>;\n"
        "window.__KK_STEP1LINK=<?= json_encode($__step1link, JSON_UNESCAPED_SLASHES); ?>;\n"
        "</script>\n"
    )
    html = re.sub(r'<script>window\.__LANDER_BASE=[^<]+</script>\s*', "", html)
    html = re.sub(
        r'(<script[^>]+src="[^"]+/assets/static\.js[^"]*"[^>]*></script>)',
        snippet + r"\1",
        html,
        count=1,
    )
    if snippet not in html:
        html = html.replace("</head>", snippet + "</head>", 1)
    return html


def inject_lander_base_script(html: str, web_path: str) -> str:
    snippet = f'<script>window.__LANDER_BASE={json.dumps(web_path)};</script>\n'
    if snippet.strip() in html:
        return html
    html = re.sub(
        r'(<script[^>]+src="[^"]+/assets/static\.js[^"]*"[^>]*></script>)',
        snippet + r"\1",
        html,
        count=1,
    )
    if snippet not in html:
        html = html.replace("</head>", snippet + "</head>", 1)
    return html


def patch_kk_internal_links(html: str, kk_web: str) -> str:
    """Append $__step1link to internal lander .php hrefs so tracking survives page hops."""

    def repl(m: re.Match) -> str:
        page = m.group(1)
        if page == "checkout":
            return f'href="<?= $__checkout; ?>"'
        return f'href="{kk_web}/{page}.php<?= $__step1link; ?>"'

    html = re.sub(
        rf'href="{re.escape(kk_web)}/(checkout)\.php(?:\?[^"]*)?"',
        repl,
        html,
    )
    html = re.sub(
        rf'href="{re.escape(kk_web)}/([a-z0-9-]+)\.php"',
        repl,
        html,
        flags=re.I,
    )
    return html


MODERN_OMNIBASEPATH_JS = (
    "function omniBasePath() {\n"
    "    if (window.__LANDER_BASE) return window.__LANDER_BASE;\n"
    "    var scripts = document.getElementsByTagName('script');\n"
    "    for (var i = scripts.length - 1; i >= 0; i--) {\n"
    "      var src = scripts[i].getAttribute('src') || '';\n"
    "      var m = src.match(/^(.*)\\/assets\\/static\\.js/i);\n"
    "      if (m && m[1]) return m[1];\n"
    "    }\n"
    "    var pm = window.location.pathname.match(/^(.*)\\/[^/]+\\.(html|php)$/i);\n"
    "    return pm ? pm[1] : '';\n"
    "  }"
)


# Local page stems whose URL/string literals must follow the package's page
# extension (.html on flows, .php in KK packages).
LOCAL_PAGE_STEMS = [
    "index", "checkout", "about", "home",
    "gpt-library", "prompt-library", "help-center", "knowledge-base",
    "terms-of-service", "privacy-policy", "acceptable-use-policy",
    "data-deletion-request",
    "createvideo", "create-image", "create-audio", "create-music",
    "create-upscale", "create-omnireels", "create-podcast",
    "create-ai-chat", "create-voice-agents", "create-long-video",
]

# Always-defined runtime helpers. omniPageExt() prefers the baked package ext;
# omniCheckoutUrl() prefers the KK-injected checkout URL and degrades to the
# flow's own checkout page. Both are referenced by click handlers shipped in
# the packs, which previously crashed (ReferenceError) when the definitions
# were missing — buttons looked dead.
OMNI_RUNTIME_HELPERS_JS = (
    "function omniPageExt(){return window.__OMNI_PAGE_EXT||"
    "((/\\.php([?#]|$)/i.test(window.location.pathname))?'php':'html');}"
    "function omniCheckoutUrl(){if(window.__KK_CHECKOUT_URL)return window.__KK_CHECKOUT_URL;"
    "return omniBasePath()+'/checkout.'+omniPageExt()+(window.__KK_STEP1LINK||'');}"
)

# ---------------------------------------------------------------------------
# Tracking persistence + propagation. Ad-tracking params (clickid/gclid/utm/…)
# arrive ONLY on the entry URL. Internal navigation (the studio nav map, the
# library menu, content links) drops the query string, so after a couple of
# page hops the URL is bare. The server-side offer/cloaking logic (money.php)
# keys off the *request URL* params, so an untracked checkout request returns
# empty offers and every plan CTA renders as href="#" — a click just jumps to
# the top ("does nothing / refreshes"). The server's kk_s1 cookie was meant to
# bridge this, but it is never set on entry pages that take money.php's
# $multi_page['step1link'] branch (which bypasses __kk_track_suffix()), so the
# cookie is empty by the time the visitor browses to a content page.
#
# This module makes the params sticky on the client: on EVERY page it merges
# params from the URL, the kk_s1 cookie and localStorage, re-persists them
# (the cookie value matches the server's kk_s1 format so PHP's
# __kk_track_suffix() can read it on the next request), reflects them back onto
# the address bar, exposes them as window.__KK_STEP1LINK / __OMNI_TRACK_QS,
# appends them to the injected checkout URL, and decorates every same-flow link
# so the next request always carries the tracking forward. Self-contained,
# dependency-free, never throws. Idempotent (guarded by __OMNI_TRACK_INIT).
# ---------------------------------------------------------------------------
TRACKING_PERSIST_JS = (
    "if(!window.__OMNI_TRACK_INIT){window.__OMNI_TRACK_INIT=1;(function(){try{"
    "var K=['clickid','click_id','cid','gclid','fbclid','ttclid','msclkid',"
    "'wbraid','gbraid','affid','aff_id','aff','sub1','sub2','sub3','sub4','sub5',"
    "'utm_source','utm_medium','utm_campaign','utm_term','utm_content',"
    "'s1','s2','s3','oid','offer_id','tid','transaction_id'];"
    "var LS='kk_track',CK='kk_s1';"
    "function rc(n){var m=document.cookie.match(new RegExp('(?:^|; )'+n+'=([^;]*)'));"
    "return m?decodeURIComponent(m[1]):'';}"
    "function wc(n,v){try{document.cookie=n+'='+encodeURIComponent(v)+"
    "';path=/;max-age=86400;samesite=lax';}catch(e){}}"
    "function pq(s){var o={};s=String(s||'').replace(/^[?#]/,'');if(!s)return o;"
    "s.split('&').forEach(function(p){if(!p)return;var i=p.indexOf('=');"
    "var k=decodeURIComponent(i<0?p:p.slice(0,i));"
    "var v=i<0?'':decodeURIComponent(p.slice(i+1).replace(/\\+/g,' '));"
    "if(k&&K.indexOf(k)>=0)o[k]=v;});return o;}"
    "function bq(o){var a=[];K.forEach(function(k){"
    "if(o[k]!==undefined&&o[k]!=='')a.push(encodeURIComponent(k)+'='+encodeURIComponent(o[k]));});"
    "return a.join('&');}"
    "var st={};try{st=JSON.parse(localStorage.getItem(LS)||'{}')||{};}catch(e){}"
    "var ck=pq(rc(CK)),ur=pq(window.location.search),m={};"
    "K.forEach(function(k){if(ck[k]!==undefined)m[k]=ck[k];});"
    "K.forEach(function(k){if(st[k]!==undefined)m[k]=st[k];});"
    "K.forEach(function(k){if(ur[k]!==undefined)m[k]=ur[k];});"
    "var qs=bq(m);"
    "try{localStorage.setItem(LS,JSON.stringify(m));}catch(e){}"
    "if(qs)wc(CK,qs);"
    "var sfx=qs?('?'+qs):'';"
    "window.__OMNI_TRACK_QS=sfx;"
    "if(!window.__KK_STEP1LINK)window.__KK_STEP1LINK=sfx;"
    "function ht(u){return K.some(function(k){"
    "return new RegExp('[?&]'+k+'=').test(String(u).split('#')[0]);});}"
    "if(qs&&window.__KK_CHECKOUT_URL&&!ht(window.__KK_CHECKOUT_URL)){"
    "var c=String(window.__KK_CHECKOUT_URL),h=c.indexOf('#'),"
    "hp=h>=0?c.slice(h):'',bp=h>=0?c.slice(0,h):c;"
    "window.__KK_CHECKOUT_URL=bp+(bp.indexOf('?')>=0?'&':'?')+qs+hp;}"
    "if(qs&&!window.location.search&&window.history&&window.history.replaceState){"
    "try{window.history.replaceState(null,'',"
    "window.location.pathname+sfx+window.location.hash);}catch(e){}}"
    "function sf(h){if(!h)return false;"
    "if(/^(#|mailto:|tel:|javascript:)/i.test(h))return false;"
    "if(/^https?:\\/\\//i.test(h)){try{if(new URL(h).origin!==window.location.origin)"
    "return false;}catch(e){return false;}}"
    "var b=window.__LANDER_BASE;"
    "return /\\.(?:html|php)(?:[?#]|$)/i.test(h)||(b&&h.indexOf(b)===0);}"
    "function dec(a){if(!qs)return;var h=a.getAttribute('href');"
    "if(!sf(h)||ht(h))return;"
    "var hi=h.indexOf('#'),hp=hi>=0?h.slice(hi):'',bp=hi>=0?h.slice(0,hi):h;"
    "a.setAttribute('href',bp+(bp.indexOf('?')>=0?'&':'?')+qs+hp);}"
    "function da(){try{var l=document.querySelectorAll('a[href]');"
    "for(var i=0;i<l.length;i++)dec(l[i]);}catch(e){}}"
    "if(document.readyState!=='loading')da();"
    "else document.addEventListener('DOMContentLoaded',da);"
    "document.addEventListener('click',function(e){"
    "var a=e.target&&e.target.closest&&e.target.closest('a[href]');if(a)dec(a);},true);"
    "}catch(e){}})();}"
)

# Social-proof toast gate. The toasts live inside initStudioConversion (the
# create-* studio pages), so the only meaningful setting is on/off. Resolution
# order: per-page override (window.__OMNI_SOCIAL_PROOF) -> baked default
# (__OMNI_SOCIAL_DEFAULT, written by patch_static_js from the flow widgets
# config) -> 'on'. Checked lazily inside showToast so script order never
# matters. Legacy placement values ('sales'/'checkout'/'both') all show.
SOCIAL_PROOF_GATE_JS = (
    "var __sp=window.__OMNI_SOCIAL_PROOF||window.__OMNI_SOCIAL_DEFAULT||'on';"
    "if(String(__sp)==='off')return;"
)

# Previous gate revision (placement-based) — replaced in-place on re-patch.
_SOCIAL_GATE_V1_JS = (
    "var __sp=window.__OMNI_SOCIAL_PROOF||'both';"
    "var __pg=(/checkout\\.(html|php)([?#]|$)/i.test(window.location.pathname))?'checkout':'sales';"
    "if(__sp==='off'||(__sp!=='both'&&__sp!==__pg))return;"
)


def _inject_after_use_strict(text: str, snippet: str) -> str:
    """Insert snippet right after the first 'use strict'; statement (the main
    IIFE scope shared by omniBasePath and every page-wiring block)."""
    m = re.search(r"(['\"])use strict\1;?", text)
    if not m:
        return snippet + text
    return text[: m.end()] + snippet + text[m.end():]


def patch_static_js(folder: Path, web_path: str, *, php_ext: bool = False,
                    social_proof: str = "on") -> None:
    static = folder / "assets" / "static.js"
    if not static.is_file():
        return
    text = static.read_text()
    ext = "php" if php_ext else "html"
    social = "off" if str(social_proof).strip().lower() == "off" else "on"

    # ---- __LANDER_BASE (whitespace/minification tolerant) -----------------
    lb_re = re.compile(r"var __LANDER_BASE\s*=\s*(['\"])[^'\"]*\1\s*;")
    if lb_re.search(text):
        text = lb_re.sub(f"var __LANDER_BASE='{web_path}';", text, count=1)
    else:
        text = _inject_after_use_strict(text, f"var __LANDER_BASE='{web_path}';")

    # ---- baked page extension (read by helpers + the mobile nav block) ----
    ext_re = re.compile(r"var __OMNI_PAGE_EXT\s*=\s*(['\"])[^'\"]*\1\s*;window\.__OMNI_PAGE_EXT=__OMNI_PAGE_EXT;")
    bake = f"var __OMNI_PAGE_EXT='{ext}';window.__OMNI_PAGE_EXT=__OMNI_PAGE_EXT;"
    if ext_re.search(text):
        text = ext_re.sub(bake, text, count=1)
    else:
        text = _inject_after_use_strict(text, bake)

    # ---- baked social-proof default (read by the showToast gate) ----------
    # static.js is loaded by every page of the flow, so baking here reaches the
    # studio pages where the toasts actually run (per-page <script> flags only
    # ever landed on index/checkout).
    sp_re = re.compile(r"var __OMNI_SOCIAL_DEFAULT\s*=\s*(['\"])[^'\"]*\1\s*;window\.__OMNI_SOCIAL_DEFAULT=__OMNI_SOCIAL_DEFAULT;")
    sp_bake = f"var __OMNI_SOCIAL_DEFAULT='{social}';window.__OMNI_SOCIAL_DEFAULT=__OMNI_SOCIAL_DEFAULT;"
    if sp_re.search(text):
        text = sp_re.sub(sp_bake, text, count=1)
    else:
        text = _inject_after_use_strict(text, sp_bake)

    # ---- studio nav map (minified copies use 'Label':'url' without spaces) -
    for label, page in [
        ("AI Video", "createvideo"),
        ("Long Video", "create-long-video"),
        ("Image", "create-image"),
        ("Audio", "create-audio"),
        ("Music", "create-music"),
        ("Upscale", "create-upscale"),
        ("OmniReels", "create-omnireels"),
        ("AIPU Reels", "create-omnireels"),
        ("Podcast", "create-podcast"),
        ("AI Chat", "create-ai-chat"),
        ("Voice Agents", "create-voice-agents"),
    ]:
        text = re.sub(
            rf"(['\"]{label}['\"]\s*:\s*)['\"][^'\"]+/{page}\.(?:html|php)['\"]",
            f"\\1'{web_path}/{page}.{ext}'",
            text,
        )
    # Legacy per-folder omniBasePath() blocks from older builds — REPLACE with the
    # dynamic version (derives the folder from __LANDER_BASE / the script src /
    # the current URL). Older packs (e.g. aipu-pages) only have the legacy form,
    # so deleting it outright leaves dangling omniBasePath() calls and kills the
    # whole script with a ReferenceError.
    legacy_base_re = re.compile(
        r"function omniBasePath\(\)\s*\{\s*return window\.location\.pathname\.indexOf\([^)]+\)[^\}]+\}",
        re.S,
    )
    if legacy_base_re.search(text):
        text = legacy_base_re.sub(MODERN_OMNIBASEPATH_JS, text, count=1)
        text = legacy_base_re.sub("", text)
    # Self-heal copies that lost (or never had) the definitions. omniBasePath,
    # omniPageExt and omniCheckoutUrl are all referenced by pack click handlers.
    if "function omniBasePath" not in text:
        text = _inject_after_use_strict(text, MODERN_OMNIBASEPATH_JS.replace("\n", "").replace("    ", ""))
    if "function omniPageExt" not in text or "function omniCheckoutUrl" not in text:
        text = _inject_after_use_strict(text, OMNI_RUNTIME_HELPERS_JS)
    # ---- tracking persistence + propagation (survives multi-page navigation) --
    # Captures ad-tracking params once, persists them to localStorage + the
    # kk_s1 cookie, and re-attaches them to every same-flow link so the checkout
    # request always carries tracking (otherwise the server cloaks the offers and
    # the plan CTAs degrade to dead href="#" links).
    if "__OMNI_TRACK_INIT" not in text:
        text = _inject_after_use_strict(text, TRACKING_PERSIST_JS)
    # Legacy initGenerateFlow hardcoded base — use shared checkout helper.
    text = re.sub(
        r"var base\s*=\s*window\.location\.pathname\.indexOf\('/omnirogue-newpages/'\)[^\n]+\n"
        r"\s*\?\s*'/omnirogue-newpages'\s*:\s*'/omnirogue-pages';\n"
        r"\s*var checkoutUrl\s*=\s*base\s*\+\s*'/checkout\.html';",
        "var checkoutUrl = omniCheckoutUrl();",
        text,
    )
    # Legacy hardcoded redirects from older builds (spaced + minified forms).
    text = re.sub(
        rf"window\.location\.href\s*=\s*'{re.escape(web_path)}/createvideo\.(html|php)';",
        "window.location.href=omniBasePath()+'/createvideo.'+omniPageExt();",
        text,
    )
    text = re.sub(
        rf"window\.location\.href\s*=\s*'{re.escape(web_path)}/checkout\.(html|php)';",
        "window.location.href=omniCheckoutUrl();",
        text,
    )
    text = re.sub(
        r"window\.location\.href\s*=\s*'/omnirogue-pages/createvideo\.html';",
        "window.location.href=omniBasePath()+'/createvideo.'+omniPageExt();",
        text,
    )
    text = re.sub(
        r"window\.location\.href\s*=\s*'/omnirogue-pages/checkout\.html';",
        "window.location.href=omniCheckoutUrl();",
        text,
    )

    # ---- page-detection regex literals: make extension-agnostic -----------
    # e.g. /create(video|-[a-z]+)\.html/.test(path) must also match .php pages
    # in KK packages, else studio-page wiring never runs there.
    text = text.replace(
        "create(video|-[a-z]+)\\.html", "create(video|-[a-z]+)\\.(?:html|php)")
    text = text.replace(
        "home\\.html|about\\.html|gpt-library\\.html|prompt-library\\.html",
        "(?:home|about|gpt-library|prompt-library)\\.(?:html|php)")

    # ---- string literals: point every local page ref at the package ext ---
    # Quote-delimited matches only, so JS regex literals (/checkout\.html/...)
    # are never touched. Covers initLibraryDropdown's gptUrl/promptUrl, the
    # wireTopNav HOME/CHECKOUT constants, indexOf('about.html') page checks…
    other = "php" if ext == "html" else "html"
    for stem in LOCAL_PAGE_STEMS:
        text = re.sub(
            rf"(?<=[/'\"]){stem}\.{other}(['\"?#])",
            f"{stem}.{ext}\\1",
            text,
        )

    # ---- social proof gate (configurable via flow widgets) ----------------
    text = text.replace(_SOCIAL_GATE_V1_JS, SOCIAL_PROOF_GATE_JS)
    if "initSocialProof" in text and "__OMNI_SOCIAL_PROOF" not in text:
        text = re.sub(
            r"(function showToast\(\)\s*\{)",
            "\\1" + SOCIAL_PROOF_GATE_JS,
            text,
            count=1,
        )

    static.write_text(text)

    # Mobile nav menu (full-screen slide-over matching omnirogue.com mobile).
    # Canonical block lives in scripts/mobile_nav_block.js; ensure every build
    # output ships the current version regardless of how old its pack copy is.
    try:
        import sync_mobile_nav
        block = sync_mobile_nav.BLOCK_SRC.read_text(encoding="utf-8").strip()
        sync_mobile_nav.sync_static_js(static, block)
    except Exception as exc:  # non-fatal: pack copy already carries the block
        print(f"warning: mobile nav sync skipped for {static}: {exc}")


def build_multistep_lander(
    folder_name: str,
    checkout_name: str,
    lander_subpath: str,
    *,
    repurpose_key: str | None = None,
) -> Path:
    """Assemble a multistep HTML lander from omnirogue-pages + presell + checkout."""
    lander_src = HTDOCS / "omnirogue" / lander_subpath
    if not lander_src.is_dir():
        raise FileNotFoundError(f"Presell source not found: {lander_src}")
    if not (CHECKOUTS / checkout_name).is_dir():
        raise FileNotFoundError(f"Checkout source not found: {CHECKOUTS / checkout_name}")

    web_path = multistep_web_path(folder_name)
    checkout_href = f"{web_path}/checkout.html"
    dst = MULTISTEP / folder_name
    if dst.exists():
        shutil.rmtree(dst)
    shutil.copytree(
        OMNI_PAGES,
        dst,
        ignore=shutil.ignore_patterns("_*.py", "home.html", "index.html", "checkout.html"),
    )

    lander_assets = lander_src / "assets"
    for sub in ("css", "js", "img"):
        src_sub = lander_assets / sub
        if src_sub.exists():
            shutil.copytree(src_sub, dst / "assets" / sub, dirs_exist_ok=True)

    local_css = site_chrome_head_extras(web_path)

    for path in dst.rglob("*"):
        if path.is_file() and path.suffix in {".html", ".js", ".css"}:
            text = path.read_text(errors="replace")
            text = rewrite_folder_paths(text, "omnirogue-pages", web_path)
            text = text.replace("/home.html", "/index.html")
            text = text.replace(f"{web_path}/home.html", f"{web_path}/index.html")
            if path.suffix == ".html":
                if path.name in {"gpt-library.html", "prompt-library.html", "about.html"}:
                    text = re.sub(
                        r'<link rel="dns-prefetch" href="https://omnirogue\.com">.*?</style>',
                        local_css,
                        text,
                        count=1,
                        flags=re.S,
                    )
                text = localize_omnirogue_html(text, web_path)
            path.write_text(text)

    self_host_fonts(dst, web_path)
    install_checkout(dst, web_path, checkout_name)
    patch_static_js(dst, web_path)

    chrome_ref = (dst / "create-image.html").read_text(errors="replace")
    site_nav, site_footer = extract_site_chrome(chrome_ref)

    index_html = build_lander_index_html_from_src(web_path, checkout_href, lander_src)
    if repurpose_key and repurpose_key in FEATURE_PLANS:
        index_html = repurpose_lifetime(index_html, web_path, FEATURE_PLANS[repurpose_key])
    index_html = localize_omnirogue_html(index_html, web_path)
    index_html = wrap_with_site_chrome(
        index_html,
        site_nav,
        site_footer,
        web_path,
        strip_lander_header=True,
        strip_lander_footer=True,
    )
    index_html = normalize_presell_index(index_html, site_nav, site_footer)
    (dst / "index.html").write_text(index_html)

    checkout_html = (dst / "checkout.html").read_text(errors="replace")
    checkout_html = localize_omnirogue_html(checkout_html, web_path)
    checkout_html = wrap_with_site_chrome(
        checkout_html,
        site_nav,
        site_footer,
        web_path,
        checkout=True,
    )
    (dst / "checkout.html").write_text(checkout_html)

    print(f"Built {dst} (preview at {web_path}/)")
    return dst


def build_omnifull(folder_name: str, checkout_name: str) -> Path:
    web_path = multistep_web_path(folder_name)
    dst = MULTISTEP / folder_name
    if dst.exists():
        shutil.rmtree(dst)
    shutil.copytree(
        OMNI_PAGES,
        dst,
        ignore=shutil.ignore_patterns("_*.py", "home.html", "index.html", "checkout.html"),
    )

    lander_assets = LANDER_SRC / "assets"
    for sub in ("css", "js", "img"):
        src_sub = lander_assets / sub
        if src_sub.exists():
            shutil.copytree(src_sub, dst / "assets" / sub, dirs_exist_ok=True)

    local_css = site_chrome_head_extras(web_path)

    for path in dst.rglob("*"):
        if path.is_file() and path.suffix in {".html", ".js", ".css"}:
            text = path.read_text(errors="replace")
            text = rewrite_folder_paths(text, "omnirogue-pages", web_path)
            text = text.replace("/home.html", "/index.html")
            text = text.replace(f"{web_path}/home.html", f"{web_path}/index.html")
            if path.suffix == ".html":
                if path.name in {"gpt-library.html", "prompt-library.html", "about.html"}:
                    text = re.sub(
                        r'<link rel="dns-prefetch" href="https://omnirogue\.com">.*?</style>',
                        local_css,
                        text,
                        count=1,
                        flags=re.S,
                    )
                text = localize_omnirogue_html(text, web_path)
            path.write_text(text)

    self_host_fonts(dst, web_path)

    # The lander's main.css ships an unlayered global reset (`*{margin:0;padding:0}`)
    # that beats Tailwind's layered utilities and clobbers the shared nav/footer
    # spacing. Scope every main.css rule to `.omni-lander-wrap` so it only styles
    # the lander body and never leaks into the injected site chrome.
    write_scoped_lander_css(dst)

    install_checkout(dst, web_path, checkout_name)
    patch_static_js(dst, web_path)

    chrome_ref = (dst / "create-image.html").read_text(errors="replace")
    site_nav, site_footer = extract_site_chrome(chrome_ref)

    index_html = build_lander_index_html(web_path, f"{web_path}/checkout.html")
    index_html = repurpose_lifetime(index_html, web_path, FEATURE_PLANS[folder_name])
    index_html = localize_omnirogue_html(index_html, web_path)
    index_html = wrap_with_site_chrome(
        index_html,
        site_nav,
        site_footer,
        web_path,
        strip_lander_header=True,
        strip_lander_footer=True,
    )
    index_html = normalize_presell_index(index_html, site_nav, site_footer)
    index_html = use_scoped_lander_css(index_html, web_path)
    (dst / "index.html").write_text(index_html)

    checkout_html = (dst / "checkout.html").read_text(errors="replace")
    checkout_html = localize_omnirogue_html(checkout_html, web_path)
    checkout_html = wrap_with_site_chrome(
        checkout_html,
        site_nav,
        site_footer,
        web_path,
        checkout=True,
    )
    (dst / "checkout.html").write_text(checkout_html)

    finalize_omnifull_headers(dst, web_path, site_nav, site_footer)

    print(f"Built {dst} (preview at {web_path}/)")
    return dst


def build_omnifullred(folder_name: str, checkout_name: str) -> Path:
    """Omnifull journey with lander7upgrades-omniunlimited-v2 and a lifetime checkout."""
    lander_src = LANDER_UNLIMITED_V2
    web_path = multistep_web_path(folder_name)
    dst = MULTISTEP / folder_name
    if dst.exists():
        shutil.rmtree(dst)
    shutil.copytree(
        OMNI_PAGES,
        dst,
        ignore=shutil.ignore_patterns("_*.py", "home.html", "index.html", "checkout.html"),
    )

    lander_assets = lander_src / "assets"
    for sub in ("css", "js", "img"):
        src_sub = lander_assets / sub
        if src_sub.exists():
            shutil.copytree(src_sub, dst / "assets" / sub, dirs_exist_ok=True)

    local_css = site_chrome_head_extras(web_path)

    for path in dst.rglob("*"):
        if path.is_file() and path.suffix in {".html", ".js", ".css"}:
            text = path.read_text(errors="replace")
            text = rewrite_folder_paths(text, "omnirogue-pages", web_path)
            text = text.replace("/home.html", "/index.html")
            text = text.replace(f"{web_path}/home.html", f"{web_path}/index.html")
            if path.suffix == ".html":
                if path.name in {"gpt-library.html", "prompt-library.html", "about.html"}:
                    text = re.sub(
                        r'<link rel="dns-prefetch" href="https://omnirogue\.com">.*?</style>',
                        local_css,
                        text,
                        count=1,
                        flags=re.S,
                    )
                text = localize_omnirogue_html(text, web_path)
            path.write_text(text)

    self_host_fonts(dst, web_path)

    install_checkout(dst, web_path, checkout_name)
    patch_static_js(dst, web_path)

    chrome_ref = (dst / "create-image.html").read_text(errors="replace")
    site_nav, site_footer = extract_site_chrome(chrome_ref)

    index_html = build_lander_index_html_from_src(
        web_path, f"{web_path}/checkout.html", lander_src
    )
    index_html = localize_omnirogue_html(index_html, web_path)
    index_html = wrap_with_site_chrome(
        index_html,
        site_nav,
        site_footer,
        web_path,
        strip_lander_header=True,
        strip_lander_footer=True,
    )
    index_html = normalize_presell_index(index_html, site_nav, site_footer)
    (dst / "index.html").write_text(index_html)

    checkout_html = (dst / "checkout.html").read_text(errors="replace")
    checkout_html = localize_omnirogue_html(checkout_html, web_path)
    checkout_html = wrap_with_site_chrome(
        checkout_html,
        site_nav,
        site_footer,
        web_path,
        checkout=True,
    )
    (dst / "checkout.html").write_text(checkout_html)

    finalize_omnifull_headers(dst, web_path, site_nav, site_footer)

    print(f"Built {dst} (preview at {web_path}/)")
    return dst


KK_HEADER = (
    "<?php\n"
    "$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';\n"
    "$base_path = $_SERVER['DOCUMENT_ROOT'].\"/\".(\n"
    "    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)[\"location\"] : 'kowboykit'\n"
    ");\n"
    "require_once $base_path.'/includes/money.php';\n"
    "?>\n"
)
KK_PAGE_INCLUDES = "<?php require_once(__DIR__.'/_kk-config.php'); ?>\n"
KK_CHECKOUT_HEADER = (
    KK_HEADER
    + KK_PAGE_INCLUDES
    + "<?php require_once(__DIR__.'/_checkout-offers.php'); ?>\n"
)

KK_CONFIG_PHP = """<?php
$__web = '/{folder}';
$__lander = $__web . '/';
$__step1link = $multi_page['step1link'] ?? '';
$__checkout = $__lander . 'checkout.php' . $__step1link;
$__registercheckout = $offer['registercheckout']['link']['step1link'] ?? '';
"""

CHECKOUT_OFFERS_PHP = """<?php
if (!function_exists('__kk_offer_step1link')) {
    function __kk_offer_step1link($token) {
        global $offer, $link;
        if (!empty($offer[$token]['link']['step1link'])) {
            return $offer[$token]['link']['step1link'];
        }
        if ($token === 'lifetimeplan' && !empty($offer['lifetime']['link']['step1link'])) {
            return $offer['lifetime']['link']['step1link'];
        }
        if ($token === 'lifetime' && !empty($offer['lifetimeplan']['link']['step1link'])) {
            return $offer['lifetimeplan']['link']['step1link'];
        }
        if (!empty($offer['registercheckout']['link']['step1link'])) {
            return $offer['registercheckout']['link']['step1link'];
        }
        return !empty($link['step1link']) ? $link['step1link'] : '#';
    }
}
$__kk_offer_tokens = [
    'creatormonthly', 'studiomonthly', 'premiummonthly', 'scalemonthly',
    'promonthly', 'agencymonthly', 'promaxmonthly',
    'creatoryearly', 'studioyearly', 'premiumyearly', 'scaleyearly',
    'proyearly', 'agencyyearly', 'promaxyearly',
    'lifetime', 'lifetimeplan',
];
$__kk_offer_links = [];
foreach ($__kk_offer_tokens as $__t) {
    $__kk_offer_links[$__t] = __kk_offer_step1link($__t);
}
$__kk_lifetime_link = __kk_offer_step1link('lifetime');
unset($__t);
"""

KK_CHECKOUT_PLAN_ORDER = {
    "49": {
        "monthly": ["creatormonthly", "scalemonthly", "agencymonthly", "promonthly"],
        "yearly": ["creatoryearly", "scaleyearly", "agencyyearly", "proyearly"],
    },
    "299": {
        "monthly": ["creatormonthly", "premiummonthly", "promaxmonthly", "agencymonthly"],
        "yearly": ["creatoryearly", "premiumyearly", "promaxyearly", "agencyyearly"],
    },
}


def checkout_tier_for(folder_name: str) -> str:
    return "299" if "299" in folder_name else "49"


def patch_kk_checkout_plan_ctas(html: str, folder_name: str) -> str:
    """Replace plan <button class=\"pcol-cta\"> with KK-wired <a href> tags."""
    tier = checkout_tier_for(folder_name)
    order = KK_CHECKOUT_PLAN_ORDER[tier]
    tokens = order["monthly"] + order["yearly"]
    idx = 0

    def repl(m: re.Match) -> str:
        nonlocal idx
        token = tokens[idx] if idx < len(tokens) else "creatormonthly"
        idx += 1
        extra_classes = m.group(1)
        label = m.group(2)
        return (
            f'<a href="<?= htmlspecialchars($__kk_offer_links[\'{token}\'] ?? \'#\'); ?>" '
            f'class="pcol-cta{extra_classes}">{label}</a>'
        )

    return re.sub(
        r'<button class="pcol-cta([^"]*)">([^<]*)</button>',
        repl,
        html,
    )


def patch_kk_checkout_script(html: str) -> str:
    html = html.replace(
        "window.__KK_OFFER_LINKS = <?php echo json_encode($__kk_offer_links); ?>;",
        "window.__KK_OFFER_LINKS = <?php echo json_encode($__kk_offer_links, JSON_UNESCAPED_SLASHES); ?>;",
    )
    html = html.replace(
        "      if (!btn || !tok || !LINKS[tok]) return;\n"
        "      btn.onclick = function () { window.location.href = LINKS[tok]; };",
        "      if (!btn || !tok || !LINKS[tok] || LINKS[tok] === '#') return;\n"
        "      if (btn.tagName === 'A') { btn.href = LINKS[tok]; }\n"
        "      else { btn.onclick = function () { window.location.href = LINKS[tok]; }; }",
    )
    return html


LIFETIME_HREF_PHP = (
    'href="<?= htmlspecialchars($__kk_lifetime_link ?? '
    "$__kk_offer_links['lifetime'] ?? $__kk_offer_links['lifetimeplan'] ?? "
    "($link['step1link'] ?? '#')); ?>\""
)


def html_to_kk_php(
    html: str,
    kk_web: str,
    ms_web: str,
    kk_folder: str,
    is_index: bool,
    is_checkout: bool,
) -> str:
    html = html.replace(f"{ms_web}/", f"{kk_web}/")
    html = re.sub(
        rf'href="{re.escape(kk_web)}/([^"#?]+)\.html([^"]*)"',
        rf'href="{kk_web}/\1.php\2"',
        html,
    )
    html = html.replace(f"{kk_web}/index.html", f"{kk_web}/index.php")
    html = html.replace(f"{kk_web}/checkout.html", f"{kk_web}/checkout.php")
    html = re.sub(
        r'(/assets/static\.js)(\?v=[^"]*)?"',
        rf'\1?v={STATIC_JS_BUST}"',
        html,
    )
    html = patch_kk_internal_links(html, kk_web)
    html = inject_kk_page_script(html)

    if is_checkout:
        kk_script = (
            "<script>\n"
            "window.__KK_OFFER_LINKS = <?php echo json_encode($__kk_offer_links, JSON_UNESCAPED_SLASHES); ?>;\n"
            "</script>\n"
        )
        html = re.sub(
            r"<script>\s*\(function \(\) \{\s*var LINKS = \{.*?\};",
            kk_script + "<script>\n(function () {\n  var LINKS = window.__KK_OFFER_LINKS || {};",
            html,
            count=1,
            flags=re.S,
        )
        html = patch_kk_checkout_plan_ctas(html, kk_folder)
        html = patch_kk_checkout_script(html)
        header = KK_CHECKOUT_HEADER
        html = re.sub(
            r'href="/signup\?offer=lifetime"',
            LIFETIME_HREF_PHP,
            html,
        )
        html = re.sub(
            r"href='/signup\?offer=lifetime'",
            LIFETIME_HREF_PHP.replace('"', "'"),
            html,
        )
        html = re.sub(
            r"href=\"<\?= htmlspecialchars\(\$__kk_offer_links\['lifetimeplan'\][^\"]+\"",
            LIFETIME_HREF_PHP,
            html,
        )
        return header + html

    if is_index:
        html = html.replace(f'href="{kk_web}/checkout.php"', 'href="<?= $__checkout; ?>"')
        html = html.replace(f'href="{kk_web}/checkout.html"', 'href="<?= $__checkout; ?>"')
        html = re.sub(r"<\?= \$link\['step1link'\]; \?>", "<?= $__checkout; ?>", html)
        return KK_HEADER + KK_PAGE_INCLUDES + html

    html = html.replace(f"{kk_web}/checkout.php", "<?= $__checkout; ?>")
    html = html.replace(f"{kk_web}/checkout.html", "<?= $__checkout; ?>")
    html = re.sub(
        r'<a([^>]*?)href="https://(?:omnirogue|app\.aiprofessionalsuniversity)\.com[^"]*"',
        r'<a\1href="<?= $link[\'step1link\']; ?>"',
        html,
    )
    return KK_HEADER + KK_PAGE_INCLUDES + html


def build_kk_copy(
    src_folder_name: str,
    *,
    src_root: Path | None = None,
    dst_root: Path | None = None,
    ms_web_fn=None,
    kk_web_fn=None,
    kk_name_fn=None,
    symlink_subdir: str | None = None,
) -> None:
    src_root = src_root or MULTISTEP
    dst_root = dst_root or MULTISTEP_KK
    ms_web_fn = ms_web_fn or multistep_web_path
    kk_web_fn = kk_web_fn or kk_web_path
    kk_name_fn = kk_name_fn or kk_name_for
    symlink_subdir = symlink_subdir if symlink_subdir is not None else "multistep-kk"

    ms_web = ms_web_fn(src_folder_name)
    kk_folder = kk_name_fn(src_folder_name)
    kk_web = kk_web_fn(src_folder_name)

    src = src_root / src_folder_name
    dst = dst_root / kk_folder
    if dst.exists():
        shutil.rmtree(dst)
    shutil.copytree(
        src,
        dst,
        ignore=shutil.ignore_patterns("_*.py", "*.html"),
    )

    (dst / "_checkout-offers.php").write_text(CHECKOUT_OFFERS_PHP)
    (dst / "_kk-config.php").write_text(KK_CONFIG_PHP.format(folder=kk_folder))

    for html_path in sorted(src.glob("*.html")):
        stem = html_path.stem
        raw = html_path.read_text(errors="replace")
        if stem == "index":
            php_name = "index.php"
            content = html_to_kk_php(raw, kk_web, ms_web, kk_folder, True, False)
        elif stem == "checkout":
            php_name = "checkout.php"
            content = html_to_kk_php(raw, kk_web, ms_web, kk_folder, False, True)
        else:
            php_name = stem + ".php"
            content = html_to_kk_php(raw, kk_web, ms_web, kk_folder, False, False)
        (dst / php_name).write_text(content)

    patch_static_js(dst, kk_web, php_ext=True)

    for path in dst.rglob("*"):
        if path.is_file() and path.suffix in {".js", ".css", ".php"}:
            text = path.read_text(errors="replace")
            if ms_web in text:
                path.write_text(text.replace(ms_web, kk_web))

    link = HTDOCS / kk_folder
    if link.is_symlink():
        link.unlink()
    if not link.exists():
        link.symlink_to(Path(symlink_subdir) / kk_folder)

    print(f"Built KK {dst} (live at {kk_web}/)")


def main() -> None:
    build_plans_v3_fixed()
    omnifull_folders = [
        ("omnifull-plans-v3-49", "plans-v3-49-lifetime"),
        ("omnifull-plans-v3-299", "plans-v3-299-lifetime"),
        ("omnifullred-v3a-49", "plans-v3-49-lifetime"),
        ("omnifullred-v3-299", "plans-v3-299-lifetime"),
    ]
    for folder_name, checkout_name in omnifull_folders:
        if folder_name.startswith("omnifullred"):
            build_omnifullred(folder_name, checkout_name)
        else:
            build_omnifull(folder_name, checkout_name)
        build_kk_copy(folder_name)
    print("Done.")


if __name__ == "__main__":
    main()
