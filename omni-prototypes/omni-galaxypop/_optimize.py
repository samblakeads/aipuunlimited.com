#!/usr/bin/env python3
"""Fix HTML issues and optimize loading for omni-prototypes/omni-galaxypop."""
import re
from pathlib import Path

BASE = Path(__file__).resolve().parent
ASSETS = '/omni-prototypes/omni-galaxypop/assets'
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
/* Discover grid (video + audio). The hydrated React app sizes each masonry row
   from per-card flex ratios + aspect-ratio capped at 38vh. Those JS-driven inline
   ratios collapse in a static snapshot, so the optimize step rewrites each card to
   a fixed height:220px and we lay the whole Discover section out as a uniform
   responsive CSS grid here. */
.hidden.md\\:flex.md\\:flex-col{{display:grid!important;grid-template-columns:repeat(3,1fr);gap:10px}}
.hidden.md\\:flex.md\\:flex-col > .flex{{display:contents!important}}
.hidden.md\\:flex.md\\:flex-col > .flex > button{{width:auto!important;min-width:0!important;max-width:none!important;align-self:stretch}}
.hidden.md\\:flex.md\\:flex-col > .flex > button > div{{height:100%}}
.omni-static-footer a:hover{{text-decoration:underline}}
</style>'''

VOICE_AGENTS_CSS = r'''<style id="omni-voice-fixes">
/* Voice agents discover — preserve masonry rows of audio cards */
html[data-omni-page="voice-agents"] .hidden.md\:flex.md\:flex-col{display:flex!important;flex-direction:column!important;gap:10px!important}
html[data-omni-page="voice-agents"] .hidden.md\:flex.md\:flex-col > .flex{display:flex!important;gap:10px!important;align-items:stretch!important;max-height:38vh!important}
html[data-omni-page="voice-agents"] .hidden.md\:flex.md\:flex-col > .flex > button{flex:1 1 0%!important;height:220px!important;min-width:0!important;max-width:none!important}
html[data-omni-page="voice-agents"] .hidden.md\:flex.md\:flex-col > .flex > button > div{height:100%!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important}
html[data-omni-page="voice-agents"] button[data-keep-dark] .bg-gradient-to-br{min-height:100%!important}
</style>'''

# Bump ASSET_VER whenever static.js changes so browsers fetch the new file.
ASSET_VER = '20260607h'
SHARED_JS = f'<script src="{ASSETS}/static.js?v={ASSET_VER}" defer></script>'


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

    # Inject or refresh perf head. The existing block spans from the first
    # dns-prefetch link through the end of the omni-static-fixes style, with
    # several preconnect/stylesheet links in between, so match across all of it.
    if 'omni-static-fixes' in html:
        html = re.sub(
            r'<link rel="dns-prefetch" href="https://omnirogue-images\.b-cdn\.net">.*?<style id="omni-static-fixes">.*?</style>',
            lambda _m: PERF_HEAD.strip(),
            html,
            count=1,
            flags=re.DOTALL,
        )
    else:
        html = html.replace('<head>', '<head>\n' + PERF_HEAD, 1)

    # Remove duplicate inline static scripts, use shared
    html = re.sub(
        r'<script>\s*// Static copy:.*?</script>\s*',
        '',
        html,
        flags=re.DOTALL,
    )
    # Remove ALL existing static.js includes (dedupe + drop old versions), then
    # inject exactly one current versioned include before </body>.
    html = re.sub(
        r'\s*<script src="' + re.escape(ASSETS) + r'/static\.js[^"]*"[^>]*></script>',
        '',
        html,
    )
    html = html.replace('</body>', SHARED_JS + '\n</body>', 1)

    # Normalize desktop Discover cards. The hydrated app gives each masonry card a
    # JS-computed `flex: <ratio> 1 0%; aspect-ratio: <r>` inline style; in a static
    # snapshot those collapse/balloon. Replace them with a uniform grid-cell style so
    # the Discover grid (see omni-static-fixes CSS) renders evenly on every page.
    html = re.sub(
        r'(<button data-keep-dark="true"[^>]*\bstyle=")flex:\s*[0-9.]+\s+1\s+0%;\s*aspect-ratio:[^;"]*;\s*',
        r'\1height:220px;',
        html,
    )

    # Remove goober toast styles (not needed statically)
    html = re.sub(r'<style id="_goober">.*?</style>', '', html, flags=re.DOTALL)

    # Remove next-route-announcer and clerk-components
    html = re.sub(r'<next-route-announcer>.*?</next-route-announcer>', '', html, flags=re.DOTALL)
    html = re.sub(r'<div id="clerk-components"></div>', '', html)

    return html


MARKETING_PAGES = {'home.html', 'about.html', 'gpt-library.html', 'prompt-library.html'}
# Blank placeholder pages the user maintains by hand — never inject into these.
SKIP_FILES = {'index.html', 'checkout.html'}


def post_process(filename: str, html: str) -> str:
    if filename == 'create-voice-agents.html':
        html = html.replace('<html lang="en"', '<html lang="en" data-omni-page="voice-agents"', 1)
        if 'omni-voice-fixes' not in html:
            html = html.replace('</head>', VOICE_AGENTS_CSS + '\n</head>', 1)
    return html


def main():
    for f in sorted(BASE.glob('*.html')):
        if f.name in MARKETING_PAGES or f.name in SKIP_FILES:
            print(f'skipped {f.name}')
            continue
        raw = f.read_text(encoding='utf-8')
        out = post_process(f.name, optimize(raw))
        if out != raw:
            f.write_text(out, encoding='utf-8')
            print(f'optimized {f.name} ({len(raw)} -> {len(out)} bytes)')
        else:
            print(f'unchanged {f.name}')


if __name__ == '__main__':
    main()
