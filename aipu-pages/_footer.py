#!/usr/bin/env python3
"""Shared AIPU footer template and link fixes for aipu-pages."""
import re
from pathlib import Path

LOCAL = '/aipu-pages'
ASSETS = f'{LOCAL}/assets'

FOOTER = f'''<footer class="relative border-t border-border bg-background"><div class="absolute inset-0 bg-linear-to-t from-background via-background-secondary/30 to-transparent pointer-events-none"></div><div class="container container--xl max-w-7xl mx-auto px-4 sm:px-6 relative py-10 sm:py-16"><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 mb-10 sm:mb-16"><div class="sm:col-span-2"><a class="flex items-center mb-4" href="{LOCAL}/home.html"><div class="flex-layout flex-row gap-0 items-center h-[42px]"><img alt="AIPU" loading="lazy" width="160" height="42" decoding="async" data-nimg="1" class="object-contain" style="color:transparent" srcset="{ASSETS}/logo-aipu.png 1x, {ASSETS}/logo-aipu.png 2x" src="{ASSETS}/logo-aipu.png"></div></a><p class="typography typography--body2 text-sm text-(--text-secondary) mb-6 max-w-xs leading-relaxed">Deploy powerful AI agents for content creation, automation, and beyond.</p></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Product</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="{LOCAL}/createvideo.html">AI Agent</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="{LOCAL}/createvideo.html">AI Studio</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="{LOCAL}/createvideo.html">Create Studio</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="{LOCAL}/create-voice-agents.html">Voice Agents</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/aipu-pages/knowledge-base.html">Knowledge Base</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="{LOCAL}/checkout.html">Pricing</a></div></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Resources</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="{LOCAL}/help-center.html">Community</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="https://app.aiprofessionalsuniversity.com/contact">Contact Us</a></div></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Legal</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="{LOCAL}/privacy-policy.html">Privacy Policy</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="{LOCAL}/terms-of-service.html">Terms of Service</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="{LOCAL}/data-deletion-request.html">Data Deletion Request</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="https://app.aiprofessionalsuniversity.com/acceptable-use-policy">Acceptable Use Policy</a></div></div></div><div data-orientation="horizontal" role="none" class="shrink-0 h-[1px] w-full bg-border divider my-4"></div><div class="flex-layout gap-4 items-center justify-between flex-col md:flex-row pt-8"><span class="typography typography--caption text-xs leading-normal text-(--text-muted)">© <!-- -->2026<!-- --> <!-- -->AI Professionals University<!-- --> · <a href="mailto:support@aiprofessionalsuniversity.com" target="_blank" rel="noopener noreferrer" class="link link--muted link--hover-underline link--external inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4">support@aiprofessionalsuniversity.com</a></span><span class="typography typography--caption text-xs leading-normal text-(--text-muted) flex items-center gap-1">Made with <span class="text-accent">❤</span> for creators worldwide</span></div></div></footer>'''

FOOTER_LINK_FIXES = [
    (r'href="/ai-tools"', f'href="{LOCAL}/createvideo.html"'),
    (r'href="https://aipu\.com/plans"', f'href="{LOCAL}/checkout.html"'),
    (r'href="/plans"', f'href="{LOCAL}/checkout.html"'),
    (r'href="https://aipu\.com/about"', f'href="{LOCAL}/about.html"'),
    (r'href="/about"', f'href="{LOCAL}/about.html"'),
    (r'href="/about/"', f'href="{LOCAL}/about.html"'),
    (r'href="/aipu-pages/acceptable-use-policy\.html"', 'href="https://app.aiprofessionalsuniversity.com/acceptable-use-policy"'),
    (r'href="/acceptable-use-policy"', 'href="https://app.aiprofessionalsuniversity.com/acceptable-use-policy"'),
]


def fix_page_links(html: str) -> str:
    for pattern, repl in FOOTER_LINK_FIXES:
        html = re.sub(pattern, repl, html)
    return html


def apply_footer(html: str) -> str:
    html = fix_page_links(html)
    if re.search(r'<footer class="aipu-static-footer"', html):
        html = re.sub(
            r'<footer class="aipu-static-footer"[^>]*>.*?</footer>',
            FOOTER,
            html,
            flags=re.DOTALL,
        )
    elif re.search(r'<footer class="relative border-t border-border bg-background">', html):
        html = re.sub(
            r'<footer class="relative border-t border-border bg-background">.*?</footer>',
            FOOTER,
            html,
            flags=re.DOTALL,
        )
    return html


def main():
    base = Path(__file__).resolve().parent
    for f in sorted(base.glob('*.html')):
        if f.name == 'index.html':
            continue
        raw = f.read_text(encoding='utf-8')
        out = apply_footer(raw)
        if out != raw:
            f.write_text(out, encoding='utf-8')
            print(f'updated footer: {f.name}')


if __name__ == '__main__':
    main()
