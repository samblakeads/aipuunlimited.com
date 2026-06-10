#!/usr/bin/env python3
"""Capture OmniRogue create pages as static HTML with lazy content loaded."""
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
LOCAL = '/omnirogue-mainpages'
DEBUG_PORT = 9223

ROUTES = [
    'create/video', 'create/image', 'create/audio', 'create/music',
    'create/upscale', 'create/omnireels', 'create/podcast', 'create/ai-chat',
    'create/voice-agents', 'create/knowledge-base',
]

SIDEBAR_LINKS = {
    'AI Video': 'video',
    'Image': 'image',
    'Audio': 'audio',
    'Music': 'music',
    'Upscale': 'upscale',
    'OmniReels': 'omnireels',
    'Podcast': 'podcast',
    'AI Chat': 'ai-chat',
    'Voice Agents': 'voice-agents',
    'Knowledge Base': 'knowledge-base',
}

SCROLL_JS = """
(async () => {
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
  const countSources = () => document.querySelectorAll('video source[src]').length;
  const scroller = document.querySelector('.overflow-y-auto') ||
    [...document.querySelectorAll('*')].find((el) => {
      const s = getComputedStyle(el);
      return (s.overflowY === 'auto' || s.overflowY === 'scroll') && el.scrollHeight > el.clientHeight + 40;
    });
  if (scroller) {
    for (let y = 0; y <= scroller.scrollHeight; y += 220) {
      scroller.scrollTop = y;
      await sleep(400);
    }
    scroller.scrollTop = scroller.scrollHeight;
  } else {
    for (let y = 0; y <= document.body.scrollHeight; y += 220) {
      window.scrollTo(0, y);
      await sleep(400);
    }
  }
  await sleep(2000);
  return {
    title: document.title,
    sources: countSources(),
    videos: document.querySelectorAll('video').length,
  };
})();
"""


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


def capture_page(cdp: CdpSession, url: str) -> tuple[str, dict]:
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
    meta = cdp.call('Runtime.evaluate', {
        'expression': f'({SCROLL_JS})',
        'awaitPromise': True,
        'returnByValue': True,
    })
    info = meta.get('result', {}).get('value', {})
    dom = cdp.call('DOM.getDocument', {'depth': -1})
    html_result = cdp.call('DOM.getOuterHTML', {'nodeId': dom['root']['nodeId']})
    return html_result.get('outerHTML', ''), info


def wire_sidebar(html: str, active_slug: str) -> str:
    """Turn sidebar nav buttons into real links between static pages."""
    for label, slug in SIDEBAR_LINKS.items():
        href = f'{LOCAL}/create/{slug}/index.html'
        btn_pat = (
            r'<button(\s+class="flex items-center gap-3[^"]*"[^>]*)>'
            r'([\s\S]*?<span class="flex-1 truncate">' + re.escape(label) + r'</span>[\s\S]*?)'
            r'</button>'
        )
        html = re.sub(
            btn_pat,
            lambda m, h=href: f'<a href="{h}"{m.group(1)}>{m.group(2)}</a>',
            html,
        )
        # Voice Agents / Knowledge Base may already be <a> tags in the sidebar
        a_pat = (
            r'<a(\s+class="flex items-center gap-3[^"]*"[^>]*?)>'
            r'([\s\S]*?<span class="flex-1 truncate">' + re.escape(label) + r'</span>[\s\S]*?)'
            r'</a>'
        )
        html = re.sub(
            a_pat,
            lambda m, h=href: f'<a href="{h}"{m.group(1)}>{m.group(2)}</a>',
            html,
        )
    # Dashboard -> main create studio entry
    html = html.replace(
        f'href="{LOCAL}/index.html" class="hidden md:flex items-center gap-3 px-5 pt-3 pb-3',
        f'href="{LOCAL}/create/video/index.html" class="hidden md:flex items-center gap-3 px-5 pt-3 pb-3',
    )
    html = html.replace('href="/create/video"', f'href="{LOCAL}/create/video/index.html"')
    return html


def repair_video_tags(html: str) -> str:
    """Fix malformed <video> tags produced by earlier capture passes."""
    html = html.replace('muted=""<source', 'muted=""><source')
    html = html.replace('muted=""</video>', 'muted=""></video>')
    html = re.sub(
        r'(<video[^>]*?muted="")(?=</video>)',
        r'\1>',
        html,
    )
    return html


def enhance_videos(html: str) -> str:
    """Ensure discover videos can play in static copies (muted + hover/visible autoplay)."""
    html = repair_video_tags(html)

    def _fix_video(m: re.Match) -> str:
        tag = m.group(0)
        if not tag.endswith('>'):
            return tag
        inner = tag[6:-1]
        if 'muted' not in inner:
            inner += ' muted=""'
        if 'playsinline' not in inner:
            inner += ' playsinline=""'
        return f'<video{inner}>'

    html = re.sub(r'<video[^>]*>', _fix_video, html)
    html = html.replace('preload="metadata"', 'preload="auto"')
    return html


STATIC_JS = """
<script>
(function () {
  var scrollRoot = document.querySelector('.overflow-y-auto') ||
    document.querySelector('main .overflow-y-auto') || null;

  function hasSrc(v) {
    var s = v.querySelector('source');
    return s && s.getAttribute('src');
  }

  function playVideo(v) {
    if (!hasSrc(v)) return;
    v.muted = true;
    v.setAttribute('muted', '');
    v.playsInline = true;
    v.play().catch(function () {});
  }

  function stopVideo(v) {
    v.pause();
    try { v.currentTime = 0; } catch (e) {}
  }

  function wireVideo(v) {
    if (!v || v.dataset.staticWired) return;
    v.dataset.staticWired = '1';
    v.muted = true;
    v.playsInline = true;
    v.setAttribute('muted', '');
    v.setAttribute('playsinline', '');
    v.loop = true;
    if (hasSrc(v)) {
      try { v.load(); } catch (e) {}
    }
    var card = v.closest('button') || v.closest('[data-lazy-video]') || v.parentElement;
    if (!card) return;
    card.addEventListener('mouseenter', function () { playVideo(v); });
    card.addEventListener('mouseleave', function () { stopVideo(v); });
    card.addEventListener('focusin', function () { playVideo(v); });
    card.addEventListener('focusout', function () { stopVideo(v); });
    card.addEventListener('click', function (e) {
      if (!hasSrc(v)) return;
      if (card.tagName === 'BUTTON') {
        e.preventDefault();
        if (v.paused) playVideo(v); else stopVideo(v);
      }
    });
  }

  function observeCards() {
    if (!('IntersectionObserver' in window)) return;
    var ioOpts = { threshold: 0.35, rootMargin: '60px' };
    if (scrollRoot) ioOpts.root = scrollRoot;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        var v = entry.target.tagName === 'VIDEO'
          ? entry.target
          : entry.target.querySelector('video');
        if (!v || !hasSrc(v)) return;
        if (entry.isIntersecting) playVideo(v);
        else stopVideo(v);
      });
    }, ioOpts);
    document.querySelectorAll('[data-lazy-video], button[data-keep-dark]').forEach(function (el) {
      io.observe(el);
      var v = el.querySelector('video');
      if (v) wireVideo(v);
    });
    document.querySelectorAll('video source[src]').forEach(function (s) {
      wireVideo(s.parentElement);
      io.observe(s.parentElement);
    });
  }

  document.querySelectorAll('video').forEach(wireVideo);
  observeCards();

  if (scrollRoot) {
    scrollRoot.addEventListener('scroll', function () {
      document.querySelectorAll('video').forEach(function (v) {
        if (!hasSrc(v)) return;
        var r = v.getBoundingClientRect();
        var rootR = scrollRoot.getBoundingClientRect();
        var visible = r.bottom > rootR.top + 40 && r.top < rootR.bottom - 40;
        if (visible) playVideo(v);
      });
    }, { passive: true });
  }

  document.querySelectorAll('nav a[href*="/omnirogue-mainpages/create/"]').forEach(function (a) {
    a.addEventListener('click', function () {
      document.querySelectorAll('video').forEach(stopVideo);
    });
  });
})();
</script>
"""


def make_static(html: str, route: str) -> str:
    # Decode entities in URLs so video/img src work
    html = html_lib.unescape(html)
    html = html.replace('&amp;', '&')

    # Remove SPA scripts that re-hydrate and break on local paths
    html = re.sub(r'<script[^>]*src="https://omnirogue\.com/_next/[^"]*"[^>]*></script>', '', html)
    html = re.sub(r'<script[^>]*src="https://clerk\.omnirogue\.com[^"]*"[^>]*></script>', '', html)
    html = re.sub(r'<script id="__NEXT_DATA__"[^>]*>.*?</script>', '', html, flags=re.DOTALL)
    html = re.sub(r'<base href="[^"]*">\s*', '', html)

    # Absolute asset URLs
    for attr in ('href', 'src', 'srcset', 'srcSet', 'imagesrcset', 'imageSrcSet'):
        html = html.replace(f'{attr}="/_next/', f'{attr}="{OMNI}/_next/')
        html = html.replace(f"{attr}='/_next/", f"{attr}='{OMNI}/_next/")
    # srcset lists can embed multiple relative urls
    html = re.sub(r'(\s)/_next/image', rf'\1{OMNI}/_next/image', html)
    html = html.replace('href="/logo-', f'href="{OMNI}/logo-')
    html = html.replace('src="/logo-', f'src="{OMNI}/logo-')

    for slug in SIDEBAR_LINKS.values():
        html = html.replace(f'href="/create/{slug}"', f'href="{LOCAL}/create/{slug}/index.html"')
        html = html.replace(f'href="/create/{slug}/"', f'href="{LOCAL}/create/{slug}/index.html"')
    html = html.replace('href="/"', f'href="{LOCAL}/index.html"')

    active_slug = route.split('/')[-1] if '/' in route else route
    html = wire_sidebar(html, active_slug)
    html = enhance_videos(html)

    # Replace any prior static helper script
    html = re.sub(r'<script>\s*\(function \(\) \{[\s\S]*?data-lazy-video[\s\S]*?</script>\s*', '', html)
    html = re.sub(r'<script>\s*\(function \(\) \{[\s\S]*?staticWired[\s\S]*?</script>\s*', '', html)

    if '</body>' in html:
        html = html.replace('</body>', STATIC_JS + '\n</body>', 1)
    if '<head>' in html:
        html = html.replace('<head>', f'<head>\n<meta name="x-static-copy" content="{route}">\n', 1)

    return html


def main():
    results = []
    proc = start_chrome()
    try:
        time.sleep(1.5)
        cdp = CdpSession(get_ws_url())
        cdp.call('Page.enable')
        cdp.call('Runtime.enable')
        for route in ROUTES:
            url = f'{OMNI}/{route}'
            print(f'Capturing {url}...')
            try:
                raw, info = capture_page(cdp, url)
                out = make_static(raw, route)
                dest = BASE / route
                dest.mkdir(parents=True, exist_ok=True)
                (dest / 'index.html').write_text(out, encoding='utf-8')
                sources = len(re.findall(r'<source src="', out))
                print(f'  -> {info.get("title", "?")} | video sources: {sources} | {len(out)} bytes')
                results.append((route, sources, info.get('title', '?')))
            except Exception as exc:
                print(f'  ERROR: {exc}')
                results.append((route, 0, str(exc)))
        cdp.close()
    finally:
        proc.terminate()
        proc.wait(timeout=5)

    items = '\n'.join(
        f'  <li><a href="{LOCAL}/create/{r.split("/")[1]}/index.html">{r}</a> ({s} video sources)</li>'
        for r, s, _ in results
    )
    (BASE / 'index.html').write_text(f'''<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>OmniRogue Main Pages</title>
<style>body{{font-family:system-ui;background:#0b0b14;color:#eee;padding:2rem}} a{{color:#a78bfa}}</style></head>
<body>
<h1>OmniRogue Create Studio — Static HTML Copies</h1>
<p>Rendered snapshots from <a href="https://omnirogue.com/create/video">omnirogue.com/create/video</a>.
Lazy discover videos are baked in at capture time; no live SPA re-hydration.</p>
<ul>{items}</ul>
</body></html>''', encoding='utf-8')
    print('Done.')


def patch_existing():
    """Re-apply sidebar wiring, video fixes, and playback JS to saved HTML copies."""
    for route in ROUTES:
        path = BASE / route / 'index.html'
        if not path.exists():
            continue
        html = path.read_text(encoding='utf-8')
        slug = route.split('/')[-1]
        html = repair_video_tags(html)
        html = wire_sidebar(html, slug)
        html = enhance_videos(html)
        html = re.sub(
            r'<script>\s*\(function \(\) \{[\s\S]*?staticWired[\s\S]*?</script>\s*',
            '',
            html,
        )
        if '</body>' in html:
            html = html.replace('</body>', STATIC_JS + '\n</body>', 1)
        path.write_text(html, encoding='utf-8')
        sources = len(re.findall(r'<source src="', html))
        broken = html.count('muted=""</video>')
        print(f'Patched {route}: {sources} sources, {broken} broken tags')


if __name__ == '__main__':
    import sys
    if len(sys.argv) > 1 and sys.argv[1] == 'patch':
        patch_existing()
    else:
        main()
