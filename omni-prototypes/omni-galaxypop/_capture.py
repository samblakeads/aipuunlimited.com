#!/usr/bin/env python3
"""Capture OmniRogue pages as flat static HTML files in omni-prototypes/omni-galaxypop."""
import html as html_lib
import json
import re
import subprocess
import time
import urllib.request
from pathlib import Path

import websocket

BASE = Path(__file__).resolve().parent
CHROMIUM = '/usr/bin/chromium-browser'
OMNI = 'https://omnirogue.com'
LOCAL = '/omni-prototypes/omni-galaxypop'
DEBUG_PORT = 9224

# route -> flat filename
PAGES = {
    '': 'home.html',
    'about': 'about.html',
    'gpt-library': 'gpt-library.html',
    'prompt-library': 'prompt-library.html',
    'create/video': 'createvideo.html',
    'create/image': 'create-image.html',
    'create/audio': 'create-audio.html',
    'create/music': 'create-music.html',
    'create/upscale': 'create-upscale.html',
    'create/omnireels': 'create-omnireels.html',
    'create/podcast': 'create-podcast.html',
    'create/ai-chat': 'create-ai-chat.html',
    'create/voice-agents': 'create-voice-agents.html',
    'help-center': 'help-center.html',
    'affiliate': 'affiliate.html',
    'privacy-policy': 'privacy-policy.html',
    'terms-of-service': 'terms-of-service.html',
    'data-deletion-request': 'data-deletion-request.html',
    'acceptable-use-policy': 'acceptable-use-policy.html',
}

# These live routes redirect to /login or /register (auth-gated), so a capture
# only yields the auth page. Their HTML files are hand-built to match the rest
# of the site and must NOT be overwritten by a re-run. Delete an entry here if
# the live route ever becomes publicly accessible.
SKIP_ROUTES = {
    'create/voice-agents',
    'acceptable-use-policy',
}

# Sidebar label -> filename (for button-to-link conversion)
SIDEBAR_LINKS = {
    'AI Video': 'createvideo.html',
    'Image': 'create-image.html',
    'Audio': 'create-audio.html',
    'Music': 'create-music.html',
    'Upscale': 'create-upscale.html',
    'OmniReels': 'create-omnireels.html',
    'Podcast': 'create-podcast.html',
    'AI Chat': 'create-ai-chat.html',
    'Voice Agents': 'create-voice-agents.html',
}

SCROLL_JS = """
(async () => {
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const countSources = () => document.querySelectorAll('video source[src]').length;
  const scrollers = [...document.querySelectorAll('*')].filter((el) => {
    const s = getComputedStyle(el);
    return (s.overflowY === 'auto' || s.overflowY === 'scroll') && el.scrollHeight > el.clientHeight + 40;
  });
  const targets = scrollers.length ? scrollers : [document.documentElement];
  for (const scroller of targets) {
    const max = scroller.scrollHeight || document.body.scrollHeight;
    for (let y = 0; y <= max; y += 180) {
      if (scroller === document.documentElement) window.scrollTo(0, y);
      else scroller.scrollTop = y;
      await sleep(350);
    }
    if (scroller !== document.documentElement) scroller.scrollTop = scroller.scrollHeight;
  }
  window.scrollTo(0, document.body.scrollHeight);
  document.querySelectorAll('[data-lazy-video]').forEach((el) => {
    el.scrollIntoView({ block: 'center' });
  });
  await sleep(1500);
  document.querySelectorAll('video').forEach((v) => {
    try { v.load(); } catch (e) {}
  });
  for (let i = 0; i < 25; i++) {
    await sleep(600);
    if (countSources() >= 9) break;
    document.querySelectorAll('[data-lazy-video]').forEach((el) => el.scrollIntoView({ block: 'nearest' }));
  }
  await sleep(1500);
  return {
    title: document.title,
    sources: countSources(),
    videos: document.querySelectorAll('video').length,
    urls: [...document.querySelectorAll('video source[src]')].map((s) => s.src),
  };
})();
"""

MANIFESTO_SCROLL_JS = """
(async () => {
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const reveal = () => {
    document.querySelectorAll('[style]').forEach((el) => {
      const s = el.getAttribute('style') || '';
      if (/opacity:\\s*0/.test(s) || s.includes('opacity:0')) {
        el.style.opacity = '1';
        el.style.transform = 'none';
      }
    });
  };
  for (let pass = 0; pass < 3; pass++) {
    const max = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    for (let y = 0; y <= max; y += 100) {
      window.scrollTo(0, y);
      await sleep(250);
    }
    window.scrollTo(0, max);
    await sleep(800);
    reveal();
    document.querySelectorAll('section').forEach((sec) => sec.scrollIntoView({ block: 'center' }));
    await sleep(400);
    reveal();
  }
  window.scrollTo(0, 0);
  await sleep(300);
  reveal();
  return {
    title: document.title,
    scrollHeight: document.body.scrollHeight,
    sections: document.querySelectorAll('section').length,
    hidden: [...document.querySelectorAll('[style]')].filter((el) => {
      const s = el.getAttribute('style') || '';
      return /opacity:\\s*0/.test(s) || s.includes('opacity:0');
    }).length,
  };
})();
"""

VIDEO_DISCOVER_URLS = [
    'https://omnirogue-images.b-cdn.net/optimized/v2/create-discover-veo-offroad/720p.mp4',
    'https://omnirogue-images.b-cdn.net/optimized/v2/create-discover-kling-3/720p.mp4',
    'https://omnirogue-images.b-cdn.net/optimized/v2/create-discover-kling30-3/720p.mp4',
    'https://omnirogue-images.b-cdn.net/optimized/v2/create-discover-veo-paper/720p.mp4',
    'https://omnirogue-images.b-cdn.net/optimized/v2/create-discover-kling-4/720p.mp4',
    'https://omnirogue-images.b-cdn.net/optimized/v2/create-discover-veo-controls/720p.mp4',
    'https://omnirogue-images.b-cdn.net/optimized/v2/create-discover-kling-5/720p.mp4',
    'https://omnirogue-images.b-cdn.net/optimized/v2/create-discover-kling30-4/720p.mp4',
    'https://omnirogue-images.b-cdn.net/optimized/v2/create-discover-seedance/720p.mp4',
]


class CdpSession:
    def __init__(self, ws_url: str):
        self.ws = websocket.create_connection(ws_url, timeout=120)
        self._id = 0

    def call(self, method: str, params: dict | None = None, timeout: float = 90):
        self._id += 1
        msg_id = self._id
        self.ws.send(json.dumps({'id': msg_id, 'method': method, 'params': params or {}}))
        deadline = time.time() + timeout
        while time.time() < deadline:
            raw = self.ws.recv()
            data = json.loads(raw)
            if data.get('id') == msg_id:
                if 'error' in data:
                    raise RuntimeError(data['error'])
                return data.get('result', {})
        raise TimeoutError(method)

    def close(self):
        self.ws.close()


def start_chrome() -> subprocess.Popen:
    return subprocess.Popen([
        CHROMIUM, f'--remote-debugging-port={DEBUG_PORT}', '--remote-allow-origins=*',
        '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu',
        'about:blank',
    ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)


def get_ws_url() -> str:
    for _ in range(30):
        try:
            tabs = json.load(urllib.request.urlopen(f'http://127.0.0.1:{DEBUG_PORT}/json/list'))
            for tab in tabs:
                if tab.get('type') == 'page':
                    return tab['webSocketDebuggerUrl']
        except Exception:
            time.sleep(0.2)
    raise RuntimeError('Chrome debug port not ready')


def capture_page(cdp: CdpSession, url: str, scroll_js: str | None = None) -> tuple[str, dict]:
    cdp.call('Page.setDeviceMetricsOverride', {
        'width': 1440, 'height': 900, 'deviceScaleFactor': 1, 'mobile': False,
    })
    cdp.call('Page.navigate', {'url': url})
    time.sleep(6)
    for _ in range(40):
        state = cdp.call('Runtime.evaluate', {
            'expression': 'document.readyState',
            'returnByValue': True,
        })
        if state.get('result', {}).get('value') == 'complete':
            break
        time.sleep(0.5)
    time.sleep(2)
    scroll = scroll_js or SCROLL_JS
    meta = cdp.call('Runtime.evaluate', {
        'expression': f'({scroll})',
        'awaitPromise': True,
        'returnByValue': True,
    })
    info = meta.get('result', {}).get('value', {})
    dom = cdp.call('DOM.getDocument', {'depth': -1})
    html_result = cdp.call('DOM.getOuterHTML', {'nodeId': dom['root']['nodeId']})
    return html_result.get('outerHTML', ''), info


def route_to_file(route: str) -> str:
    return PAGES.get(route, route.replace('/', '-') + '.html')


def make_static(html: str, route: str, filename: str) -> str:
    html = html_lib.unescape(html)

    # Remove SPA scripts that re-hydrate and break on local paths
    html = re.sub(r'<script[^>]*src="(?:https://omnirogue\.com)?/_next/[^"]*"[^>]*></script>', '', html)
    html = re.sub(r'<script[^>]*src="https://clerk\.omnirogue\.com[^"]*"[^>]*></script>', '', html)
    html = re.sub(r'<script id="__NEXT_DATA__"[^>]*>.*?</script>', '', html, flags=re.DOTALL)
    html = re.sub(r'<script id="__NEXT_DATA__"[^>]*>.*?</script>', '', html, flags=re.DOTALL)
    html = re.sub(r'<link[^>]*rel="prefetch"[^>]*>', '', html)
    html = re.sub(r'<link[^>]*rel="preload"[^>]*as="script"[^>]*>', '', html)
    html = re.sub(r'<base href="[^"]*">\s*', '', html)

    # Absolute asset URLs (keep CSS/fonts/images; scripts already stripped)
    html = html.replace('href="/_next/', f'href="{OMNI}/_next/')
    html = html.replace('src="/_next/', f'src="{OMNI}/_next/')
    html = html.replace('srcSet="/_next/', f'srcSet="{OMNI}/_next/')
    html = html.replace('imageSrcSet="/_next/', f'imageSrcSet="{OMNI}/_next/')
    html = html.replace('href="/logo-', f'href="{OMNI}/logo-')
    html = html.replace('src="/logo-', f'src="{OMNI}/logo-')

    # Rewrite all page routes to flat local files
    for r, fname in PAGES.items():
        html = html.replace(f'href="/{r}"', f'href="{LOCAL}/{fname}"')
        html = html.replace(f'href="/{r}/"', f'href="{LOCAL}/{fname}"')

    # Home / create studio nav
    html = html.replace('href="/"', f'href="{LOCAL}/home.html"')
    html = html.replace('href="/create"', f'href="{LOCAL}/createvideo.html"')
    html = html.replace('href="/create/"', f'href="{LOCAL}/createvideo.html"')

    # Help center in nav/footer
    html = html.replace('href="/help-center"', f'href="{LOCAL}/help-center.html"')
    html = html.replace('href="/help-center/"', f'href="{LOCAL}/help-center.html"')
    html = html.replace('href="/affiliate"', f'href="{LOCAL}/affiliate.html"')
    html = html.replace('href="/affiliate/"', f'href="{LOCAL}/affiliate.html"')

    # Legal footer links
    for legal in ['privacy-policy', 'terms-of-service', 'data-deletion-request', 'acceptable-use-policy']:
        html = html.replace(f'href="/{legal}"', f'href="{LOCAL}/{legal}.html"')
        html = html.replace(f'href="/{legal}/"', f'href="{LOCAL}/{legal}.html"')

    # External links stay external (login, contact)
    for ext in ['login', 'contact']:
        html = html.replace(f'href="/{ext}"', f'href="{OMNI}/{ext}"')
        html = html.replace(f'href="/{ext}/"', f'href="{OMNI}/{ext}"')

    # Pricing + signup CTAs -> local checkout
    html = html.replace(f'href="{OMNI}/plans"', f'href="{LOCAL}/checkout.html"')
    html = html.replace(f'href="{OMNI}/register"', f'href="{LOCAL}/checkout.html"')
    html = html.replace('href="/plans"', f'href="{LOCAL}/checkout.html"')
    html = html.replace('href="/plans/"', f'href="{LOCAL}/checkout.html"')
    html = html.replace('href="/register"', f'href="{LOCAL}/checkout.html"')
    html = html.replace('href="/register/"', f'href="{LOCAL}/checkout.html"')

    # Convert sidebar buttons to anchor links
    for label, fname in SIDEBAR_LINKS.items():
        pattern = (
            rf'<button([^>]*class="[^"]*flex items-center gap-3[^"]*"[^>]*)>'
            rf'((?:(?!</button>).)*?)<span class="flex-1 truncate">{re.escape(label)}</span>'
        )
        def repl(m, fn=fname):
            attrs = m.group(1)
            inner = m.group(2)
            return f'<a{attrs} href="{LOCAL}/{fn}">{inner}<span class="flex-1 truncate">{label}</span>'
        html = re.sub(pattern, repl, html, flags=re.DOTALL)
    static_js = f"""
<script>
// Static copy: sidebar nav, video hover play, lazy-load
(function () {{
  // Sidebar buttons that weren't converted — wire by label text
  var navMap = {json.dumps({k: f'{LOCAL}/{v}' for k, v in SIDEBAR_LINKS.items()})};
  document.querySelectorAll('aside button, nav button').forEach(function (btn) {{
    var label = btn.querySelector('.flex-1.truncate, .truncate');
    if (!label) return;
    var text = label.textContent.trim();
    if (navMap[text]) {{
      btn.addEventListener('click', function (e) {{
        e.preventDefault();
        window.location.href = navMap[text];
      }});
      btn.style.cursor = 'pointer';
    }}
  }});

  document.querySelectorAll('[data-lazy-video] video').forEach(function (v) {{
    var card = v.closest('button') || v.closest('a') || v.parentElement;
    if (!card) return;
    card.addEventListener('mouseenter', function () {{ v.play().catch(function () {{}}); }});
    card.addEventListener('mouseleave', function () {{ v.pause(); v.currentTime = 0; }});
  }});
  var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {{
    entries.forEach(function (e) {{
      if (!e.isIntersecting) return;
      var v = e.target.querySelector('video');
      if (v && v.querySelector('source[src]')) v.load();
    }});
  }}, {{ rootMargin: '200px' }}) : null;
  if (io) document.querySelectorAll('[data-lazy-video]').forEach(function (el) {{ io.observe(el); }});
}})();
</script>
"""

    # Backfill discover videos that lazy-load after initial paint (desktop + mobile grids)
    if route == 'create/video':
        from itertools import cycle
        url_cycle = cycle(VIDEO_DISCOVER_URLS)

        def inject_source(match: re.Match) -> str:
            url = next(url_cycle)
            return f'{match.group(1)}<source src="{url}" type="video/mp4">{match.group(2)}'

        for pattern in (
            r'(<video class="w-full h-full object-cover"[^>]*>)\s*(</video>)',
            r'(<video class="absolute inset-0 h-full w-full object-cover"[^>]*>)\s*(</video>)',
        ):
            html = re.sub(pattern, inject_source, html)

    # Add footer with legal links on create pages (no native footer in studio layout)
    footer_html = f'''
<footer class="omni-static-footer" style="padding:2rem 1.5rem;border-top:1px solid rgba(255,255,255,0.08);background:#0b0b14;margin-top:auto">
  <div style="max-width:72rem;margin:0 auto;display:flex;flex-wrap:wrap;gap:1.5rem;justify-content:space-between;align-items:center;font-size:0.875rem;color:rgba(255,255,255,0.6)">
    <span>&copy; OmniRogue</span>
    <nav style="display:flex;flex-wrap:wrap;gap:1.25rem">
      <a href="{LOCAL}/help-center.html" style="color:#a78bfa;text-decoration:none">Help Center</a>
      <a href="{LOCAL}/privacy-policy.html" style="color:#a78bfa;text-decoration:none">Privacy Policy</a>
      <a href="{LOCAL}/terms-of-service.html" style="color:#a78bfa;text-decoration:none">Terms of Service</a>
      <a href="{LOCAL}/data-deletion-request.html" style="color:#a78bfa;text-decoration:none">Data Deletion Request</a>
      <a href="{LOCAL}/acceptable-use-policy.html" style="color:#a78bfa;text-decoration:none">Acceptable Use Policy</a>
    </nav>
  </div>
</footer>'''
    if route.startswith('create/') and 'Privacy Policy' not in html:
        html = html.replace('</body>', footer_html + '\n</body>', 1)

    if '</body>' in html:
        html = html.replace('</body>', static_js + '\n</body>', 1)
    if '<head>' in html:
        html = html.replace('<head>', f'<head>\n<meta name="x-static-copy" content="{route}">\n', 1)

    return html


def main():
    BASE.mkdir(parents=True, exist_ok=True)
    results = []
    proc = start_chrome()
    try:
        time.sleep(1.5)
        cdp = CdpSession(get_ws_url())
        cdp.call('Page.enable')
        cdp.call('Runtime.enable')
        for route, filename in PAGES.items():
            if route in SKIP_ROUTES:
                print(f'Skipping {route} (auth-gated; {filename} is hand-built)')
                continue
            url = f'{OMNI}/{route}'
            print(f'Capturing {url} -> {filename}...')
            try:
                raw, info = capture_page(cdp, url)
                out = make_static(raw, route, filename)
                (BASE / filename).write_text(out, encoding='utf-8')
                sources = len(re.findall(r'<source src="', out))
                print(f'  -> {info.get("title", "?")} | video sources: {sources} | {len(out)} bytes')
                results.append((route, filename, sources, info.get('title', '?')))
            except Exception as exc:
                print(f'  ERROR: {exc}')
                results.append((route, filename, 0, str(exc)))
        cdp.close()
    finally:
        proc.terminate()
        proc.wait(timeout=5)

    items = '\n'.join(
        f'  <li><a href="{LOCAL}/{fname}">{route}</a> ({s} video sources)</li>'
        for route, fname, s, _ in results
    )
    (BASE / 'index.html').write_text(f'''<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>OmniRogue New Pages</title>
<style>body{{font-family:system-ui;background:#0b0b14;color:#eee;padding:2rem}} a{{color:#a78bfa}} h2{{margin-top:2rem}}</style></head>
<body>
<h1>OmniRogue — Static HTML Pages</h1>
<h2>Create Studio</h2>
<ul>{items}</ul>
</body></html>''', encoding='utf-8')
    print('Done.')


if __name__ == '__main__':
    main()
