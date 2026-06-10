#!/usr/bin/env python3
"""Fix HTML issues and optimize loading for omnirogue-newpages."""
import re
from pathlib import Path

from _footer import apply_footer

BASE = Path(__file__).resolve().parent
ASSETS = '/omnirogue-newpages/assets'
OMNI = 'https://omnirogue.com'

PERF_HEAD = f'''<link rel="dns-prefetch" href="https://omnirogue-images.b-cdn.net">
<link rel="dns-prefetch" href="https://images.unsplash.com">
<link rel="preconnect" href="https://omnirogue-images.b-cdn.net" crossorigin>
<link rel="stylesheet" href="{ASSETS}/main1.css">
<link rel="stylesheet" href="{ASSETS}/main2.css">
<style id="omni-static-fixes">
html{{--header-height:64px;--nav-height:64px;--nav-bg:rgba(7,8,18,0.85);color-scheme:dark}}
/* Static page fixes */
@media (min-width:768px){{.md\\:hidden.shrink-0.flex.items-center.justify-between{{display:none!important}}aside.fixed.inset-0.z-50{{position:relative!important;inset:auto!important;z-index:auto!important;width:240px!important;height:100%!important}}div.fixed.inset-0.z-30.bg-black\\/50{{display:none!important}}}}
audio{{display:none}}
button[data-keep-dark] .bg-gradient-to-br,[data-keep-dark] .bg-gradient-to-br{{min-height:100%;background:linear-gradient(to bottom right,rgb(46,16,101),rgb(30,27,75),rgb(15,23,42))!important}}
.hidden.md\\:flex.md\\:flex-col{{display:flex!important;flex-direction:column;gap:10px}}
.hidden.md\\:flex.md\\:flex-col > .flex{{min-height:120px}}
.omni-static-footer a:hover{{text-decoration:underline}}
</style>'''

SHARED_JS = f'<script src="{ASSETS}/static.js" defer></script>'


def optimize(html: str) -> str:
    # Fix broken button close tags from sidebar conversion
    html = html.replace('</svg></a><button', '</svg></button><button')
    html = re.sub(
        r'(<button class="p-1\.5 rounded-lg[^"]*"[^>]*>.*?chevron-left.*?</svg>)</a>',
        r'\1</button>',
        html,
        flags=re.DOTALL,
    )

    # Remove remote CSS (replaced with local)
    html = re.sub(r'<link rel="preload" href="https://omnirogue\.com/_next/static/chunks/[^"]+\.css"[^>]*>', '', html)
    html = re.sub(r'<link rel="stylesheet" href="https://omnirogue\.com/_next/static/chunks/[^"]+\.css"[^>]*>', '', html)

    # Remove font preload (bundled in local CSS path or skip)
    html = re.sub(r'<link rel="preload" href="https://omnirogue\.com/_next/static/media/[^"]+\.woff2"[^>]*>', '', html)

    # Remove tracking / health scripts
    html = re.sub(r'<script id="omni-site-health-preinit">.*?</script>', '', html, flags=re.DOTALL)
    html = re.sub(r'<script src="/omni-site-health-after-init\.js"[^>]*></script>', '', html)
    html = re.sub(r'<link rel="preload" as="image" imagesrcset="/_next/image[^>]*>', '', html)
    html = re.sub(r'<link rel="preload" as="image" imagesrcset="/_next/image[^>]*>', '', html)
    html = re.sub(r'<script[^>]*clerk\.omnirogue\.com[^>]*></script>', '', html)
    html = re.sub(r'<meta name="sentry-trace"[^>]*>', '', html)
    html = re.sub(r'<meta name="baggage"[^>]*>', '', html)
    html = re.sub(r'<link rel="stylesheet" href="/_next/static/chunks/[^"]+\.css"[^>]*>', '', html)
    html = re.sub(r'<link rel="preload" href="/_next/static/chunks/[^"]+\.css"[^>]*>', '', html)
    html = re.sub(r'<link rel="preload" href="/_next/static/media/[^"]+\.woff2"[^>]*>', '', html)
    html = re.sub(r'<link[^>]*rel="prefetch"[^>]*>', '', html)

    # Local logo
    html = re.sub(
        r'srcset="/_next/image\?url=%2Flogo-omnirogue\.png[^"]*"',
        f'srcset="{ASSETS}/logo-omnirogue.png 1x, {ASSETS}/logo-omnirogue.png 2x"',
        html,
    )
    html = html.replace(
        'src="https://omnirogue.com/_next/image?url=%2Flogo-omnirogue.png&w=384&q=75"',
        f'src="{ASSETS}/logo-omnirogue.png"',
    )
    html = html.replace(
        'src="/_next/image?url=%2Flogo-omnirogue.png&w=384&q=75"',
        f'src="{ASSETS}/logo-omnirogue.png"',
    )

    # Inject or refresh perf head
    if 'omni-static-fixes' in html:
        html = re.sub(r'<link rel="dns-prefetch"[^>]*>\s*<style id="omni-static-fixes">.*?</style>', PERF_HEAD.strip(), html, flags=re.DOTALL)
    else:
        html = html.replace('<head>', '<head>\n' + PERF_HEAD, 1)

    # Remove duplicate inline static scripts, use shared
    html = re.sub(
        r'<script>\s*// Static copy:.*?</script>\s*',
        '',
        html,
        flags=re.DOTALL,
    )
    if SHARED_JS not in html:
        html = html.replace('</body>', SHARED_JS + '\n</body>', 1)

    # Remove goober toast styles (not needed statically)
    html = re.sub(r'<style id="_goober">.*?</style>', '', html, flags=re.DOTALL)

    # Remove next-route-announcer and clerk-components
    html = re.sub(r'<next-route-announcer>.*?</next-route-announcer>', '', html, flags=re.DOTALL)
    html = re.sub(r'<div id="clerk-components"></div>', '', html)

    html = apply_footer(html)
    return html


def main():
    for f in sorted(BASE.glob('*.html')):
        raw = f.read_text(encoding='utf-8')
        out = optimize(raw)
        if out != raw:
            f.write_text(out, encoding='utf-8')
            print(f'optimized {f.name} ({len(raw)} -> {len(out)} bytes)')
        else:
            print(f'unchanged {f.name}')


if __name__ == '__main__':
    main()
