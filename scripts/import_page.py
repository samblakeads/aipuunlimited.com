#!/usr/bin/env python3
"""
import_page.py - Import a lander / checkout from a URL or a local HTML file
into one of the four previews collections, localizing its assets and applying
the destination brand's header/footer chrome + brand text.

Usage:
  python3 import_page.py (--url <url> | --file <path>) \
      --kind lander|checkout \
      --collection aipu-landers|omnirogue-landers|aipu-checkouts|omnirogue-checkouts \
      [--name <slug>] [--source-url <url>] [--docroot <abs>]

Prints a single JSON line on stdout: { ok, item, collection, web_path, assets, ... }.

Best-effort asset localization: same-origin (and relative) CSS/JS/images/fonts
are downloaded into the new folder and references rewritten to local paths.
Cross-origin assets (CDNs, Google Fonts, etc.) are left as absolute URLs.
For --file imports, asset localization runs only when --source-url is given to
provide an origin to resolve relative references against.
"""
from __future__ import annotations

import argparse
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
import time
from pathlib import Path
from urllib.parse import urljoin, urlparse, urldefrag

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import brandlib as B  # noqa: E402
import qa_checks  # noqa: E402  (automatic QC gate, merged into the import result)

try:
    import requests
except Exception as exc:  # pragma: no cover
    print(json.dumps({"ok": False, "error": "python 'requests' not installed: %s" % exc}))
    sys.exit(1)

try:
    from bs4 import BeautifulSoup
except Exception as exc:  # pragma: no cover
    print(json.dumps({"ok": False, "error": "BeautifulSoup (bs4) not installed: %s" % exc}))
    sys.exit(1)

# A realistic desktop-Chrome UA + headers; many sites 403 a bot-looking UA.
UA = ("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
      "(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36")
BROWSER_HEADERS = {
    "User-Agent": UA,
    "Accept": ("text/html,application/xhtml+xml,application/xml;q=0.9,"
               "image/avif,image/webp,image/apng,*/*;q=0.8"),
    "Accept-Language": "en-US,en;q=0.9",
    "Sec-CH-UA": '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
    "Sec-CH-UA-Mobile": "?0",
    "Sec-CH-UA-Platform": '"Windows"',
    "Sec-Fetch-Dest": "document",
    "Sec-Fetch-Mode": "navigate",
    "Sec-Fetch-Site": "none",
    "Sec-Fetch-User": "?1",
    "Upgrade-Insecure-Requests": "1",
}

MAX_ASSETS = 300
MAX_TOTAL_BYTES = 40 * 1024 * 1024  # 40 MB safety cap
REQ_TIMEOUT = 20

# Headless-browser rendering (for JS-only SPAs + Cloudflare/JS challenges).
BROWSER_CANDIDATES = (
    "/opt/google/chrome/chrome",
    "google-chrome-stable",
    "google-chrome",
    "chromium",
    "chromium-browser",
    "/usr/bin/chromium-browser",
    "/snap/bin/chromium",  # snap: only usable when run as a real login user
)
RENDER_MAX_WAIT = 22          # seconds to poll for content / challenge clearance
RENDER_LAUNCH_WAIT = 15       # seconds to wait for the DevTools endpoint to open

ASSET_EXT = (".css", ".js", ".png", ".jpg", ".jpeg", ".webp", ".svg", ".gif",
             ".ico", ".woff", ".woff2", ".ttf", ".otf", ".mp4", ".webm", ".avif",
             ".json", ".map")

# Markers that mean "the static HTML isn't the real page" — escalate to a browser.
_CHALLENGE_MARKERS = (
    "just a moment",
    "cf-mitigated",
    "challenge-platform",
    "/cdn-cgi/challenge",
    "cf_chl_opt",
    "checking your browser",
    "verifying you are human",
    "enable javascript and cookies",
)
_SPA_ROOT_RE = re.compile(r'id=["\'](?:__next|__nuxt|root|app|svelte|q-app)["\']', re.I)


def _find_browser() -> str | None:
    for cand in BROWSER_CANDIDATES:
        if "/" in cand:
            if os.path.isfile(cand) and os.access(cand, os.X_OK):
                # Skip the snap shim — it can't run under the web-server user.
                if "/snap/" in cand:
                    continue
                return cand
        else:
            found = shutil.which(cand)
            if found and "/snap/" not in os.path.realpath(found):
                return found
    return None


def _visible_text_len(html: str) -> int:
    """Rough count of human-visible text, ignoring script/style/markup."""
    body = re.sub(r"(?is)<(script|style|noscript|template)[^>]*>.*?</\1>", " ", html)
    body = re.sub(r"(?s)<[^>]+>", " ", body)
    body = re.sub(r"\s+", " ", body).strip()
    return len(body)


def needs_render(html: str, status: int) -> bool:
    """Decide whether a static fetch needs to be re-done in a real browser."""
    low = html.lower()
    if any(m in low for m in _CHALLENGE_MARKERS):
        return True
    if status in (403, 429, 503) and ("cloudflare" in low or "server: cloudflare" in low or low.strip() == ""):
        return True
    vlen = _visible_text_len(html)
    # A "Loading…" shell that client-side JS is meant to replace.
    if vlen < 600 and any(p in low for p in _LOADING_PHRASES):
        return True
    # JS-only shell: almost no rendered text but lots of script.
    if vlen < 200 and low.count("<script") >= 5:
        return True
    if _SPA_ROOT_RE.search(html) and vlen < 200:
        return True
    return False


def detect_brand(html: str) -> str | None:
    low = html.lower()
    if "omnirogue" in low:
        return "omni"
    if "aiprofessionalsuniversity" in low or "aipuunlimited" in low or "aipu reels" in low or ">aipu<" in low:
        return "aipu"
    return None


def safe_rel_from_url(u: str) -> str | None:
    """Map an asset URL to a safe relative path under the item dir."""
    parsed = urlparse(u)
    path = parsed.path
    if not path or path.endswith("/"):
        return None
    path = path.lstrip("/")
    # normalize, reject traversal
    parts = []
    for seg in path.split("/"):
        if seg in ("", ".", ".."):
            continue
        seg = re.sub(r"[^A-Za-z0-9._-]", "_", seg)
        parts.append(seg)
    if not parts:
        return None
    return "/".join(parts)


class Importer:
    def __init__(self, url: str, out_dir: Path, web_base: str, warnings: list[str]):
        self.start_url = url
        self.out_dir = out_dir
        self.web_base = web_base.rstrip("/")
        self.warnings = warnings
        self.session = requests.Session()
        self.session.headers.update(BROWSER_HEADERS)
        self.downloaded: dict[str, str] = {}  # abs_url -> local web path
        self.total_bytes = 0
        self.count = 0
        self.origin = ""
        self.final_url = url or ""
        self.status = 0
        self.rendered = False

    def _set_origin(self, url: str) -> None:
        self.final_url = url
        p = urlparse(url)
        if p.scheme and p.netloc:
            self.origin = f"{p.scheme}://{p.netloc}"

    def fetch_html(self) -> str:
        """Plain HTTP fetch. Returns decoded HTML and records the status; does
        NOT raise on 4xx/5xx so the caller can detect challenge pages."""
        r = self.session.get(self.start_url, timeout=REQ_TIMEOUT, allow_redirects=True)
        self.status = r.status_code
        self._set_origin(str(r.url))
        # When the server omits a charset, requests assumes latin-1 which mangles
        # UTF-8 pages (em dashes, middle dots, quotes). Honour <meta charset> / utf-8.
        ctype = (r.headers.get("content-type") or "").lower()
        if "charset=" in ctype:
            return r.text
        head = r.content[:2048].decode("ascii", errors="replace").lower()
        m = re.search(r'charset=["\']?\s*([\w-]+)', head)
        enc = m.group(1) if m else (r.apparent_encoding or "utf-8")
        try:
            return r.content.decode(enc, errors="replace")
        except (LookupError, TypeError):
            return r.content.decode("utf-8", errors="replace")

    def _launch_browser(self, tmp_home: str):
        """Start headless Chrome with a CDP endpoint; return (proc, profile)."""
        browser = _find_browser()
        if not browser:
            raise RuntimeError(
                "no usable headless browser found (install google-chrome-stable); "
                "cannot render JS / bypass challenge for this URL")
        profile = os.path.join(tmp_home, "profile")
        cmd = [
            browser,
            "--headless=new",
            "--no-sandbox",
            "--disable-gpu",
            "--disable-dev-shm-usage",
            "--hide-scrollbars",
            "--mute-audio",
            "--no-first-run",
            "--no-default-browser-check",
            "--disable-extensions",
            "--disable-background-networking",
            "--disable-features=Translate,BackForwardCache",
            "--remote-debugging-port=0",
            "--remote-allow-origins=*",
            f"--user-data-dir={profile}",
            f"--user-agent={UA}",
            "--window-size=1280,2000",
            "about:blank",
        ]
        env = dict(os.environ)
        env["HOME"] = tmp_home  # crashpad / profile want a writable HOME
        proc = subprocess.Popen(
            cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, env=env)
        return proc, profile

    def _devtools_ws_url(self, profile: str, proc, deadline: float) -> str:
        """Read the DevToolsActivePort file Chrome writes, then resolve the page
        target's websocket URL."""
        import urllib.request

        port_file = os.path.join(profile, "DevToolsActivePort")
        port = None
        while time.time() < deadline:
            if proc.poll() is not None:
                raise RuntimeError("browser exited before DevTools came up")
            if os.path.isfile(port_file):
                try:
                    first = Path(port_file).read_text().splitlines()[0].strip()
                    if first.isdigit():
                        port = int(first)
                        break
                except Exception:
                    pass
            time.sleep(0.15)
        if not port:
            raise RuntimeError("DevTools port did not open")
        # Find a 'page' target.
        while time.time() < deadline:
            try:
                with urllib.request.urlopen(f"http://127.0.0.1:{port}/json", timeout=3) as r:
                    targets = json.loads(r.read().decode("utf-8", "replace"))
                for t in targets:
                    if t.get("type") == "page" and t.get("webSocketDebuggerUrl"):
                        return t["webSocketDebuggerUrl"]
            except Exception:
                pass
            time.sleep(0.2)
        raise RuntimeError("no page target available")

    _PROBE_JS = (
        "(function(){var t=(document.title||'');"
        "var b=document.body?document.body.innerText:'';"
        "var low=(t+' '+b).toLowerCase();"
        "var ch=/just a moment|verifying you are human|checking your browser|"
        "needs to review the security|enable javascript and cookies/.test(low);"
        "var len=b.replace(/\\s+/g,' ').trim().length;"
        "return JSON.stringify({ch:ch,len:len});})()"
    )

    def _drive_cdp(self, ws_url: str) -> str:
        """Navigate to the target URL and return the rendered DOM once the page
        is past any challenge and has real content (or the best-effort DOM when
        the wait budget elapses)."""
        from websocket import create_connection, WebSocketTimeoutException

        ws = create_connection(ws_url, timeout=20, max_size=None,
                               suppress_origin=True, enable_multithread=False)
        msg_id = 0

        def cmd(method, params=None, timeout=8):
            nonlocal msg_id
            msg_id += 1
            mid = msg_id
            ws.send(json.dumps({"id": mid, "method": method, "params": params or {}}))
            end = time.time() + timeout
            ws.settimeout(timeout)
            while time.time() < end:
                try:
                    raw = ws.recv()
                except WebSocketTimeoutException:
                    break
                if not raw:
                    continue
                try:
                    m = json.loads(raw)
                except Exception:
                    continue
                if m.get("id") == mid:
                    if "error" in m:
                        raise RuntimeError(m["error"].get("message", "cdp error"))
                    return m.get("result", {})
                # else: an event — ignore
            raise TimeoutError(f"cdp timeout: {method}")

        def get_outer_html() -> str:
            res = cmd("Runtime.evaluate", {
                "expression": "document.documentElement.outerHTML",
                "returnByValue": True,
            }, timeout=10)
            return (res.get("result") or {}).get("value") or ""

        try:
            cmd("Page.enable")
            cmd("Runtime.enable")
            cmd("Page.navigate", {"url": self.start_url}, timeout=20)
            deadline = time.time() + RENDER_MAX_WAIT
            best = ""
            min_wait_until = time.time() + 1.5  # let an SPA paint at least once
            while time.time() < deadline:
                time.sleep(0.7)
                try:
                    probe = cmd("Runtime.evaluate", {
                        "expression": self._PROBE_JS, "returnByValue": True,
                    }, timeout=8)
                    info = json.loads((probe.get("result") or {}).get("value") or "{}")
                except Exception:
                    continue
                if not info.get("ch") and info.get("len", 0) > 200 and time.time() >= min_wait_until:
                    best = get_outer_html()
                    if best:
                        break
            if not best:
                best = get_outer_html()
            return best
        finally:
            try:
                ws.close()
            except Exception:
                pass

    def render_with_browser(self) -> str:
        """Render the page in headless Chrome (via the DevTools Protocol) and
        return the post-JS DOM. Executes the page's JavaScript, so it yields real
        content for SPAs and clears non-interactive Cloudflare / JS challenges.

        Uses a real wall-clock readiness wait (not --virtual-time-budget, which
        can get stuck in a challenge's reload loop)."""
        tmp_home = tempfile.mkdtemp(prefix="aipu-render-")
        proc = None
        try:
            proc, profile = self._launch_browser(tmp_home)
            ws_url = self._devtools_ws_url(profile, proc, time.time() + RENDER_LAUNCH_WAIT)
            html = self._drive_cdp(ws_url)
            if not html or not html.strip():
                raise RuntimeError("browser render produced no DOM")
        finally:
            if proc is not None:
                try:
                    proc.terminate()
                    proc.wait(timeout=5)
                except Exception:
                    try:
                        proc.kill()
                    except Exception:
                        pass
            shutil.rmtree(tmp_home, ignore_errors=True)
        self.rendered = True
        self.status = 200
        self._set_origin(self.start_url)
        return html

    def obtain_html(self, render_mode: str) -> str:
        """Return the best HTML for start_url.

        render_mode:
          never  - plain HTTP only (raises on HTTP error)
          always - go straight to the headless browser
          auto   - HTTP first; escalate to the browser on a challenge / JS shell
        """
        if render_mode == "always":
            return self.render_with_browser()

        html = self.fetch_html()
        if render_mode == "never":
            if self.status >= 400:
                raise RuntimeError(f"source returned HTTP {self.status}")
            return html

        # auto
        if needs_render(html, self.status):
            try:
                return self.render_with_browser()
            except Exception as exc:
                if self.status >= 400:
                    raise RuntimeError(
                        f"source returned HTTP {self.status} and browser render failed: {exc}")
                self.warnings.append(f"browser render failed, using static HTML: {exc}")
                return html
        if self.status >= 400:
            raise RuntimeError(f"source returned HTTP {self.status}")
        return html

    def read_file(self, path: Path, source_url: str = "") -> str:
        """Read a local HTML file. If source_url is given, set origin so that
        relative/same-origin asset references can be localized just like a URL
        import would."""
        data = path.read_bytes()
        # Honour <meta charset> if present, else best-effort utf-8.
        head = data[:2048].decode("ascii", errors="replace").lower()
        m = re.search(r'charset=["\']?\s*([\w-]+)', head)
        enc = (m.group(1) if m else "utf-8")
        try:
            text = data.decode(enc, errors="replace")
        except (LookupError, TypeError):
            text = data.decode("utf-8", errors="replace")
        if source_url:
            self.final_url = source_url
            p = urlparse(source_url)
            self.origin = f"{p.scheme}://{p.netloc}"
        else:
            # No source URL: leave origin empty so localize() skips everything
            # (we can't safely fetch relative assets without a base).
            self.final_url = ""
            self.origin = ""
        return text

    def _is_localizable(self, abs_url: str) -> bool:
        p = urlparse(abs_url)
        if p.scheme not in ("http", "https"):
            return False
        # only same-origin assets get pulled local
        return f"{p.scheme}://{p.netloc}" == self.origin

    def localize(self, raw_url: str, referrer_url: str) -> str | None:
        """Download a same-origin asset; return its new local web path or None."""
        if not raw_url or raw_url.strip().startswith(("data:", "blob:", "javascript:", "mailto:", "tel:", "#")):
            return None
        abs_url = urljoin(referrer_url, urldefrag(raw_url)[0])
        if not self._is_localizable(abs_url):
            return None
        if abs_url in self.downloaded:
            return self.downloaded[abs_url]
        if self.count >= MAX_ASSETS or self.total_bytes >= MAX_TOTAL_BYTES:
            self.warnings.append("asset budget reached; some assets left remote")
            return None
        rel = safe_rel_from_url(abs_url)
        if rel is None:
            return None
        try:
            r = self.session.get(abs_url, timeout=REQ_TIMEOUT)
            if r.status_code != 200:
                return None
            content = r.content
        except Exception:
            return None
        self.total_bytes += len(content)
        self.count += 1
        target = self.out_dir / rel
        target.parent.mkdir(parents=True, exist_ok=True)
        try:
            target.write_bytes(content)
        except Exception as exc:  # pragma: no cover
            self.warnings.append(f"write failed {rel}: {exc}")
            return None
        local_web = f"{self.web_base}/{rel}"
        self.downloaded[abs_url] = local_web
        # If we just pulled a CSS file, localize url(...) refs inside it too.
        if rel.lower().endswith(".css"):
            self._localize_css(target, abs_url)
        return local_web

    def _localize_css(self, css_path: Path, css_url: str) -> None:
        try:
            text = css_path.read_text(encoding="utf-8", errors="replace")
        except Exception:
            return
        def repl(m):
            raw = m.group(1).strip().strip("'\"")
            new = self.localize(raw, css_url)
            return f"url({new})" if new else m.group(0)
        new_text = re.sub(r"url\(([^)]+)\)", repl, text)
        if new_text != text:
            css_path.write_text(new_text, encoding="utf-8")

    def _abs_or_local(self, raw_url: str, referrer: str) -> str | None:
        """Resolve one asset reference.

        Returns a local web path when the asset was downloaded; otherwise the
        absolute source URL so it still loads from the origin site (never a bare
        relative path, which would 404 against *our* document root). Returns
        None for non-fetchable schemes (data:/blob:/#/mailto:/tel:) so the
        original value is kept untouched.
        """
        if not raw_url:
            return None
        s = raw_url.strip()
        if s.startswith(("data:", "blob:", "javascript:", "mailto:", "tel:", "#")):
            return None
        local = self.localize(raw_url, referrer)
        if local:
            return local
        if not referrer:
            return None
        absu = urljoin(referrer, urldefrag(raw_url)[0])
        return absu if urlparse(absu).scheme in ("http", "https") else None

    def localize_srcset(self, value: str, referrer: str) -> str:
        out = []
        for part in value.split(","):
            part = part.strip()
            if not part:
                continue
            bits = part.split()
            new = self._abs_or_local(bits[0], referrer)
            if new:
                bits[0] = new
            out.append(" ".join(bits))
        return ", ".join(out)

    def localize_assets(self, soup: BeautifulSoup) -> None:
        ref = self.final_url
        for tag in soup.find_all(["link", "script", "img", "source", "video", "audio"]):
            if tag.name == "link":
                rel = " ".join(tag.get("rel", [])).lower() if tag.get("rel") else ""
                if tag.get("href") and (rel == "" or any(
                    r in rel for r in ["stylesheet", "icon", "preload", "manifest", "prefetch", "mask-icon", "apple-touch-icon"]
                )):
                    new = self._abs_or_local(tag["href"], ref)
                    if new:
                        tag["href"] = new
            else:
                if tag.get("src"):
                    new = self._abs_or_local(tag["src"], ref)
                    if new:
                        tag["src"] = new
                if tag.get("srcset"):
                    tag["srcset"] = self.localize_srcset(tag["srcset"], ref)
                if tag.get("poster"):
                    new = self._abs_or_local(tag["poster"], ref)
                    if new:
                        tag["poster"] = new
        for meta in soup.find_all("meta"):
            prop = (meta.get("property") or meta.get("name") or "").lower()
            if prop in ("og:image", "twitter:image", "og:image:secure_url") and meta.get("content"):
                new = self._abs_or_local(meta["content"], ref)
                if new:
                    meta["content"] = new


_LOADING_ID_CLASS_RE = re.compile(
    r"\b(loading|loader|pre-?loader|splash(?:-screen)?|spinner|page-?loader|"
    r"app-?loading|loading-overlay|loading-screen|skeleton-screen)\b", re.I)
_LOADING_PHRASES = (
    "loading content, please wait",
    "loading, please wait",
    "please wait while we load",
    "checking your browser before accessing",
    "verifying you are human",
    "enable javascript and cookies to continue",
)
_BOOTSTRAP_SRC_RE = re.compile(
    r"(/_next/|/_nuxt/|/static/js/|/build/|webpack|runtime|polyfill|framework|"
    r"chunk|vendor|bundle|main[.\-]|app[.\-]|\.mjs(\?|$))", re.I)
_BOOTSTRAP_INLINE_RE = re.compile(
    r"(__NEXT_DATA__|self\.__next_f|__NUXT__|window\.__remixContext|"
    r"webpackJsonp|webpackChunk|__sveltekit|System\.import|import\()", re.I)


def strip_blocking(soup: BeautifulSoup, rendered: bool, warnings: list[str]) -> None:
    """Remove code that prevents an imported page from displaying:

      * Cloudflare / JS challenge scriptlets and their CSP meta.
      * <noscript> "enable JavaScript" notices.
      * Loading overlays / preloaders / spinners (incl. "Loading content,
        please wait..." shells from client-rendered SPAs).
      * When the page was rendered in a real browser, the framework bootstrap
        scripts too — we keep the rendered DOM as a stable static snapshot so it
        can't re-hydrate back into a perpetual loading state.
    """
    removed = 0

    # 1) Cloudflare challenge remnants + restrictive challenge CSP.
    for tag in soup.find_all("script"):
        src = (tag.get("src") or "")
        if "challenges.cloudflare.com" in src or "/cdn-cgi/challenge-platform/" in src \
                or "cdn-cgi/challenge" in src:
            tag.decompose(); removed += 1
    for meta in soup.find_all("meta"):
        if (meta.get("http-equiv") or "").lower() == "content-security-policy" \
                and "challenges.cloudflare.com" in (meta.get("content") or ""):
            meta.decompose(); removed += 1

    # 2) <noscript> "enable JavaScript" notices (they shouldn't gate our copy).
    for ns in soup.find_all("noscript"):
        txt = ns.get_text(" ", strip=True).lower()
        if "javascript" in txt and ("enable" in txt or "turn on" in txt or "requires" in txt):
            ns.decompose(); removed += 1

    # 3) Elements containing a known loading/blocking phrase → drop the overlay.
    for el in list(soup.find_all(True)):
        if not el.parent:  # already detached
            continue
        try:
            txt = el.get_text(" ", strip=True).lower()
        except Exception:
            continue
        if txt and len(txt) < 240 and any(p in txt for p in _LOADING_PHRASES):
            el.decompose(); removed += 1

    # 4) Elements explicitly named like a loader/overlay with little/no content.
    for el in list(soup.find_all(attrs={"id": _LOADING_ID_CLASS_RE})) + \
            list(soup.find_all(attrs={"class": _LOADING_ID_CLASS_RE})):
        if not el.parent:
            continue
        if el.name in ("html", "body", "head"):
            continue
        if len(el.get_text(" ", strip=True)) < 120:
            el.decompose(); removed += 1

    # 5) For browser-rendered snapshots, neutralise the SPA so it stays static.
    if rendered:
        for tag in soup.find_all("script"):
            src = tag.get("src") or ""
            typ = (tag.get("type") or "").lower()
            inline = tag.string or ""
            if typ == "application/ld+json":
                continue  # harmless structured data
            if (typ == "module" or _BOOTSTRAP_SRC_RE.search(src)
                    or (not src and _BOOTSTRAP_INLINE_RE.search(inline))):
                tag.decompose(); removed += 1
        for link in soup.find_all("link"):
            rel = " ".join(link.get("rel", [])).lower() if link.get("rel") else ""
            href = link.get("href") or ""
            as_attr = (link.get("as") or "").lower()
            if "modulepreload" in rel or (("preload" in rel or "prefetch" in rel)
                                          and (as_attr == "script" or _BOOTSTRAP_SRC_RE.search(href))):
                link.decompose(); removed += 1

    if removed:
        warnings.append(f"stripped {removed} blocking element(s)")


def main() -> int:
    ap = argparse.ArgumentParser(description="Import a page from a URL or local HTML file into a collection.")
    src = ap.add_mutually_exclusive_group(required=True)
    src.add_argument("--url", help="http(s) URL to fetch and import")
    src.add_argument("--file", dest="file", help="path to a local HTML file to import")
    ap.add_argument("--source-url", default="",
                    help="optional origin URL when --file is used (enables asset localization)")
    ap.add_argument("--kind", required=True, choices=["lander", "checkout"])
    ap.add_argument("--collection", required=True,
                    choices=["sales-pages", "checkout-pages",
                             "aipu-landers", "omnirogue-landers",
                             "aipu-checkouts", "omnirogue-checkouts"])
    ap.add_argument("--name", default=None)
    ap.add_argument("--render", choices=["auto", "always", "never"], default="auto",
                    help="auto (default): HTTP first, fall back to a headless browser on a "
                         "JS/Cloudflare challenge or empty SPA shell; always: always render; "
                         "never: plain HTTP only")
    ap.add_argument("--docroot", default=str(B.DOCROOT))
    args = ap.parse_args()

    docroot = Path(args.docroot).resolve()
    B.DOCROOT = docroot
    warnings: list[str] = []
    created_dir: Path | None = None

    try:
        # collection must match kind
        is_checkout_col = args.collection == "checkout-pages" or args.collection.endswith("-checkouts")
        if (args.kind == "checkout") != is_checkout_col:
            raise ValueError("collection does not match kind")
        is_neutral = args.collection in ("sales-pages", "checkout-pages")
        dst_brand = None if is_neutral else ("aipu" if args.collection.startswith("aipu") else "omni")

        # Pick a default name from the URL slug or filename.
        if args.name:
            name = B.slugify(args.name)
        elif args.url:
            p = urlparse(args.url)
            guess = (p.path.rstrip("/").split("/")[-1] or p.netloc).split(".")[0]
            name = B.slugify(guess or "imported")
        else:
            stem = Path(args.file).stem if args.file else "imported"
            # "index" is a useless slug — fall back to the parent dir if so.
            if stem.lower() in ("", "index"):
                parent = Path(args.file).parent.name if args.file else ""
                stem = parent or "imported"
            name = B.slugify(stem or "imported")

        collection_dir = docroot / args.collection
        collection_dir.mkdir(parents=True, exist_ok=True)
        out_dir = B.unique_dir(collection_dir, name)
        out_dir.mkdir(parents=True)
        created_dir = out_dir
        web_base = B.web_dir_for(out_dir, docroot)

        imp = Importer(args.url or "", out_dir, web_base, warnings)
        if args.url:
            html = imp.obtain_html(args.render)
        else:
            file_path = Path(args.file).resolve()
            if not file_path.is_file():
                raise ValueError(f"file not found: {file_path}")
            html = imp.read_file(file_path, source_url=args.source_url or "")
        src_brand = detect_brand(html)

        # For checkouts, drop any existing chrome regions before localizing so we
        # don't download the old brand's header/footer assets (replaced below).
        if args.kind == "checkout":
            html = B.HEAD_RE.sub("", html)
            html = B.HEADER_RE.sub("", html)
            html = B.FOOTER_RE.sub("", html)

        soup = BeautifulSoup(html, "html.parser")
        # Strip loading overlays / challenge code / SPA bootstrap so the imported
        # page actually displays instead of getting stuck on a loading screen.
        strip_blocking(soup, imp.rendered, warnings)
        imp.localize_assets(soup)
        html = str(soup)

        # Rebrand to destination brand when the source is the *other* brand,
        # shielding the localized asset paths (they may contain brand-named URL
        # segments like "checkouts-omni" that the rebrand must not rewrite).
        if dst_brand and src_brand and src_brand != dst_brand:
            shields: dict[str, str] = {}
            for i, lw in enumerate(sorted(set(imp.downloaded.values()), key=len, reverse=True)):
                ph = f"\x00ASSET{i}\x00"
                if lw in html:
                    shields[ph] = lw
                    html = html.replace(lw, ph)
            html = B.rebrand_text(html, src_brand, dst_brand)
            for ph, lw in shields.items():
                html = html.replace(ph, lw)

        # Branded checkouts get the destination brand's header/footer chrome injected.
        if args.kind == "checkout" and dst_brand:
            html = B.inject_chrome(html, dst_brand, docroot, warnings)

        (out_dir / "index.html").write_text(html, encoding="utf-8")

        if args.kind == "checkout" and dst_brand:
            B.mirror_collection_assets(out_dir, collection_dir, warnings)
        if dst_brand:
            B.heal_missing_logos(out_dir, dst_brand, warnings, self_web=web_base)

        # Automatic QC gate over the imported page (non-blocking).
        qc_brand = dst_brand or src_brand or "omni"
        qc = qa_checks.safe_validate(
            "checkout" if args.kind == "checkout" else "lander",
            str(out_dir), str(docroot), qc_brand,
        )

        print(json.dumps({
            "ok": True,
            "kind": args.kind,
            "item": out_dir.name,
            "collection": args.collection,
            "brand": dst_brand,
            "source_brand": src_brand,
            "web_path": f"{web_base}/index.html",
            "fs_path": str(out_dir),
            "assets_localized": imp.count,
            "rendered": imp.rendered,
            "warnings": warnings,
            "qc": qc,
        }))
        return 0
    except Exception as exc:
        # Don't leave an empty output folder behind on failure.
        if created_dir is not None and not (created_dir / "index.html").is_file():
            shutil.rmtree(created_dir, ignore_errors=True)
        print(json.dumps({"ok": False, "error": str(exc), "warnings": warnings}))
        return 1


if __name__ == "__main__":
    sys.exit(main())
