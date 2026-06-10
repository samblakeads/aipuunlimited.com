#!/usr/bin/env python3
"""Post-process captured home.html and wire Home / Start creating links."""
import re
from pathlib import Path

from _footer import FOOTER, apply_footer, fix_page_links

LOCAL = '/omni-prototypes/omni-galaxypop'
ASSETS = f'{LOCAL}/assets'
OMNI = 'https://omnirogue.com'
ASSET_VER = '20260607d'

MARKETING_CSS = f'''<link rel="dns-prefetch" href="https://omnirogue.com">
<link rel="preconnect" href="https://omnirogue.com" crossorigin>
<link rel="stylesheet" href="{OMNI}/_next/static/chunks/0rd9ye~auu_te.css">
<link rel="stylesheet" href="{OMNI}/_next/static/chunks/178l0ejjsix32.css">
<style id="omni-marketing-fixes">
html{{--header-height:64px;--nav-height:64px;--nav-bg:rgba(7,8,18,0.85);color-scheme:dark}}
@keyframes shimmer{{0%{{background-position:200% 0}}100%{{background-position:-200% 0}}}}
</style>'''


def reveal_lazy_styles(html: str) -> str:
    """Framer-motion sections capture as opacity:0 without React hydration."""
    html = re.sub(r'opacity:\s*0(?=[;\s"])', 'opacity:1', html)
    html = re.sub(r'opacity:0(?=[;\s"])', 'opacity:1', html)
    html = re.sub(r'transform:\s*translateY\([^)]+\)\s*;?', '', html)
    html = re.sub(r'transform:\s*scale\(0\.8\)\s*;?', 'transform:none;', html)
    html = re.sub(r'transform:\s*none;\s*transform:\s*none;', 'transform:none;', html)
    return html


def fix_marketing_head(html: str) -> str:
    """Marketing pages need omnirogue.com CSS, not create-studio main1/main2."""
    html = re.sub(
        r'<link rel="dns-prefetch" href="https://omnirogue\.com">\s*'
        r'<link rel="preconnect" href="https://omnirogue\.com" crossorigin>\s*'
        r'(?:<link rel="stylesheet" href="[^"]+/_next/static/chunks/[^"]+\.css"[^>]*>\s*)*'
        r'<style id="omni-marketing-fixes">.*?</style>\s*',
        '',
        html,
        flags=re.DOTALL,
    )
    html = re.sub(
        r'<link rel="dns-prefetch" href="https://omnirogue-images\.b-cdn\.net">.*?<style id="omni-(?:marketing|static)-fixes">.*?</style>\s*',
        '',
        html,
        count=1,
        flags=re.DOTALL,
    )
    html = re.sub(
        r'<link rel="stylesheet" href="/omni-prototypes/omni-galaxypop/assets/main[12]\.css">\s*',
        '',
        html,
    )
    html = re.sub(
        rf'<link rel="stylesheet" href="{re.escape(OMNI)}/_next/static/chunks/[^"]+\.css"[^>]*>\s*',
        '',
        html,
    )
    html = html.replace('<head>', '<head>\n' + MARKETING_CSS + '\n', 1)
    return html


def _optimize_marketing_common(html: str) -> str:
    html = fix_page_links(html)

    # Nav + logo -> home
    html = html.replace(f'href="{LOCAL}/createvideo.html">Home', f'href="{LOCAL}/home.html">Home')
    html = re.sub(
        r'(<a class="flex items-center touch-manipulation" href=")' + re.escape(LOCAL) + r'/createvideo\.html"',
        rf'\1{LOCAL}/home.html"',
        html,
        count=1,
    )

    # Signup / pricing -> local checkout
    html = html.replace(f'href="{OMNI}/register"', f'href="{LOCAL}/checkout.html"')
    html = html.replace('href="https://omnirogue.com/register"', f'href="{LOCAL}/checkout.html"')
    html = html.replace(f'href="{OMNI}/plans"', f'href="{LOCAL}/checkout.html"')
    html = html.replace('href="https://omnirogue.com/plans"', f'href="{LOCAL}/checkout.html"')

    html = html.replace('href="/homepage/', f'href="{OMNI}/homepage/')
    html = html.replace('src="/homepage/', f'src="{OMNI}/homepage/')
    html = html.replace('href="/favicon.ico"', f'href="{OMNI}/favicon.ico"')

    html = re.sub(
        r'srcset="/_next/image\?url=%2Flogo-omnirogue\.png[^"]*"',
        f'srcset="{ASSETS}/logo-omnirogue.png 1x, {ASSETS}/logo-omnirogue.png 2x"',
        html,
    )
    html = re.sub(
        r'srcset="/omni-prototypes/omni-galaxypop/assets/logo-omnirogue\.png[^"]*"',
        f'srcset="{ASSETS}/logo-omnirogue.png 1x, {ASSETS}/logo-omnirogue.png 2x"',
        html,
    )
    html = re.sub(
        r'src="https://omnirogue\.com/_next/image\?url=%2Flogo-omnirogue\.png[^"]*"',
        f'src="{ASSETS}/logo-omnirogue.png"',
        html,
    )
    html = re.sub(
        r'src="/_next/image\?url=%2Flogo-omnirogue\.png[^"]*"',
        f'src="{ASSETS}/logo-omnirogue.png"',
        html,
    )

    html = re.sub(r'<script id="omni-site-health-preinit">.*?</script>', '', html, flags=re.DOTALL)
    html = re.sub(r'<script src="/omni-site-health-after-init\.js"[^>]*></script>', '', html)
    html = re.sub(r'<link rel="preload" as="image" imagesrcset="/_next/image[^>]*>', '', html)

    html = re.sub(r'<script>\s*// Static copy:.*?</script>\s*', '', html, flags=re.DOTALL)
    shared = f'<script src="{ASSETS}/static.js?v={ASSET_VER}" defer></script>'
    if shared not in html:
        html = html.replace('</body>', shared + '\n</body>', 1)

    html = apply_footer(html)
    html = reveal_lazy_styles(html)
    html = fix_marketing_head(html)
    return html


def optimize_home(html: str) -> str:
    return _optimize_marketing_common(html)


def optimize_about(html: str) -> str:
    html = _optimize_marketing_common(html)
    html = html.replace('href="/pricing"', f'href="{LOCAL}/checkout.html"')
    html = html.replace(
        f'href="{LOCAL}/home.html"><button',
        f'href="{LOCAL}/createvideo.html"><button',
    )
    return html


def optimize_library(html: str) -> str:
    """GPT Library + Prompt Library marketing pages."""
    html = _optimize_marketing_common(html)
    html = html.replace(f'href="{OMNI}/gpt-library"', f'href="{LOCAL}/gpt-library.html"')
    html = html.replace(f'href="{OMNI}/prompt-library"', f'href="{LOCAL}/prompt-library.html"')
    html = html.replace('href="/gpt-library"', f'href="{LOCAL}/gpt-library.html"')
    html = html.replace('href="/prompt-library"', f'href="{LOCAL}/prompt-library.html"')
    return html


def update_nav_home_links(html: str) -> str:
    """Point top-nav Home, About Us, and logo links at local pages."""
    html = fix_page_links(html)
    html = html.replace(f'href="{LOCAL}/createvideo.html">Home', f'href="{LOCAL}/home.html">Home')
    html = re.sub(
        r'(<a class="flex items-center touch-manipulation" href=")' + re.escape(LOCAL) + r'/createvideo\.html"',
        rf'\1{LOCAL}/home.html"',
        html,
        count=1,
    )
    # Footer brand logo
    html = re.sub(
        r'(<a class="flex items-center mb-4" href=")' + re.escape(LOCAL) + r'/createvideo\.html"',
        rf'\1{LOCAL}/home.html"',
        html,
    )
    return html


def main():
    base = Path(__file__).resolve().parent

    library_pages = {
        'gpt-library.html': optimize_library,
        'prompt-library.html': optimize_library,
    }
    for name, fn in (
        ('home.html', optimize_home),
        ('about.html', optimize_about),
        *library_pages.items(),
    ):
        path = base / name
        if path.exists():
            raw = path.read_text(encoding='utf-8')
            out = fn(raw)
            path.write_text(out, encoding='utf-8')
            print(f'optimized {name} ({len(raw)} -> {len(out)} bytes)')

    for f in sorted(base.glob('*.html')):
        if f.name in ('home.html', 'about.html', 'gpt-library.html', 'prompt-library.html', 'index.html'):
            continue
        raw = f.read_text(encoding='utf-8')
        out = update_nav_home_links(raw)
        if out != raw:
            f.write_text(out, encoding='utf-8')
            print(f'updated nav links: {f.name}')


if __name__ == '__main__':
    main()
