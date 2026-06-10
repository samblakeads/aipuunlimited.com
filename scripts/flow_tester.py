#!/usr/bin/env python3
"""
flow_tester.py - LIVE click-through tester for a deployed KK funnel.

Point it at the live URL of a funnel after it has been uploaded to KowboyKit
(KK). It drives a headless Chromium with Playwright, crawls every page that
lives inside the funnel package, and clicks every link, button and popup it can
find. Each interaction must end in one of two acceptable states:

  1. STAY  - the visitor stays on the page (in-page "#" anchors, dead Home/logo
             links, FAQ accordions, plan billing toggles).
  2. PRODUCT - the visitor lands on a working "step1link" product page (HTTP
             200, not a 404 / "offer not found" / blank / error page, and - when
             an expected product host is configured - on that host).

Anything else is a finding: a dead CTA (a button that looks like it should
convert but goes nowhere), a 4xx/5xx, an unexpected external site, a popup that
never opens or whose button is dead, a JavaScript crash, or a broken CSS/JS
asset. The run produces a GREEN (safe to launch) / RED (do not launch) verdict
plus a per-page list of exactly what is wrong, and writes incremental progress
to a status JSON file so a dashboard can poll it while the test runs.

SAFETY: this is a first-hop tester. It never types into inputs, never submits a
form, and never completes a registration or payment. It only verifies that a
CTA reaches a working product page, then stops.

Usage:
  python3 flow_tester.py --url https://host/lander/index.php [options]

Options:
  --out PATH            status JSON file (written incrementally + at the end)
  --screens-dir PATH    directory for failure screenshots (web-served)
  --screens-web PREFIX  web path prefix that maps to --screens-dir (for the UI)
  --allow-host CSV      expected product host(s); enables strict host checking
  --max-pages N         cap on pages crawled (default 40)
  --max-clicks N        cap on distinct interactive controls clicked (default 250)
  --nav-timeout MS      per-navigation timeout (default 20000)
  --settle MS           pause after a click to observe the result (default 900)
  --strict              exit non-zero when the verdict is RED (for CI)
  --json                print the final result JSON to stdout (always on for CLI)

Prints one JSON line on stdout at the end.
"""
from __future__ import annotations

import argparse
import json
import os
import re
import sys
import time
import traceback
from urllib.parse import urlparse, urljoin, urldefrag

try:
    from playwright.sync_api import sync_playwright, Error as PWError, TimeoutError as PWTimeout
except Exception as exc:  # pragma: no cover - environment guard
    print(json.dumps({"ok": False, "status": "error",
                      "error": "Playwright is not installed: %s" % exc}))
    sys.exit(1)


# --------------------------------------------------------------------------- #
#  Vocabulary                                                                  #
# --------------------------------------------------------------------------- #

# Text that marks a control as a real conversion CTA (so a dead one is a FAIL).
CTA_TEXT = [
    "get started", "start for", "get me access", "get access", "buy", "purchase",
    "subscribe", "checkout", "check out", "choose", "pick your plan", "choose plan",
    "choose your plan", "see plans", "view plans", "see plan", "get plan",
    "join now", "join", "sign up", "signup", "create account", "register",
    "unlock", "claim", "upgrade", "start free", "start now", "try ", "start creating",
    "start generating", "generate", "create video", "create image", "open studio",
    "launch studio", "continue", "get lifetime", "lifetime", "select", "add to cart",
    "order now", "buy now", "pricing", "plans", "go premium", "go pro",
]

# Text that marks a benign, stay-on-page control (clicking it is allowed to do
# nothing visible / only mutate in-page state).
TOGGLE_TEXT = [
    "monthly", "yearly", "annual", "annually", "faq", "read more", "show more",
    "learn more", "details", "expand", "collapse", "menu", "close", "next",
    "previous", "prev", "play", "pause", "accept", "got it", "ok", "dismiss",
    "cookie", "consent", "toggle", "tab",
]

# Dangerous final-step labels we must never click (would submit / pay).
DANGER_TEXT = [
    "complete order", "place order", "submit payment", "pay now", "confirm payment",
    "complete purchase", "confirm order", "submit order", "finish payment",
    "complete registration", "create my account",
]

# Error signatures (checked in <title>/<h1> or as strong phrases in body text).
ERROR_TITLE = [
    "404", "not found", "page not found", "offer not found", "no longer available",
    "not available", "error", "forbidden", "access denied", "server error",
    "service unavailable", "bad gateway", "account suspended", "expired",
    "under maintenance", "coming soon", "domain for sale", "this site can",
]
ERROR_STRONG = [
    "offer not found", "this offer is not available", "page not found",
    "error establishing a database connection", "internal server error",
    "503 service unavailable", "account has been suspended", "404 not found",
]

ASSET_EXT = (".css", ".js", ".mjs")
SKIP_SCHEMES = ("mailto:", "tel:", "sms:", "javascript:", "data:", "blob:", "ftp:")


def now() -> int:
    return int(time.time())


def norm_text(s: str) -> str:
    return re.sub(r"\s+", " ", (s or "")).strip()


def looks_like(text: str, vocab) -> bool:
    t = norm_text(text).lower()
    if not t:
        return False
    return any(k in t for k in vocab)


def is_hard_cta(text: str) -> bool:
    """True only for short, button-like conversion labels - NOT long content
    (FAQ questions, paragraphs) that merely happens to contain a CTA word."""
    t = norm_text(text)
    if not t or len(t) > 40 or t.endswith("?"):
        return False
    tl = t.lower()
    return any(k in tl for k in CTA_TEXT) and not any(k in tl for k in TOGGLE_TEXT)


def host_of(url: str) -> str:
    try:
        return (urlparse(url).hostname or "").lower()
    except Exception:
        return ""


def same_host(a: str, b: str) -> bool:
    ha, hb = host_of(a), host_of(b)
    return bool(ha) and ha == hb


def package_base(url: str) -> str:
    """Directory the funnel lives in, e.g. .../u/x/my-lander/index.php -> /u/x/my-lander/."""
    p = urlparse(url)
    path = p.path or "/"
    if path.endswith("/"):
        base = path
    else:
        base = path.rsplit("/", 1)[0] + "/"
    return base


def in_package(url: str, origin: str, base: str) -> bool:
    p = urlparse(url)
    if (p.scheme not in ("http", "https")):
        return False
    if not same_host(url, origin):
        return False
    return (p.path or "/").startswith(base)


# --------------------------------------------------------------------------- #
#  Tester                                                                      #
# --------------------------------------------------------------------------- #
class FlowTester:
    def __init__(self, url, *, out=None, screens_dir=None, screens_web=None,
                 allow_hosts=None, max_pages=40, max_clicks=250,
                 nav_timeout=20000, settle=450):
        self.start_url = urldefrag(url)[0]
        self.origin = self.start_url
        self.base = package_base(self.start_url)
        self.funnel_host = host_of(self.start_url)
        self.allow_hosts = set(h.lower().strip() for h in (allow_hosts or []) if h.strip())
        self.out = out
        self.screens_dir = screens_dir
        self.screens_web = (screens_web or "").rstrip("/")
        self.max_pages = max_pages
        self.max_clicks = max_clicks
        self.nav_timeout = nav_timeout
        self.settle = settle

        self.started_at = now()
        self.pages = []                 # ordered page records
        self.page_index = {}            # url -> page record
        self.queue = [self.start_url]
        self.seen_pages = set()
        self.dest_cache = {}            # absolute url -> verify dict
        self.clicked_sigs = set()       # global de-dup of interactive controls
        self.clicks_done = 0
        self.shot_n = 0
        self.error = None
        self._finding_keys = set()      # de-dup identical findings

    # ---- status / findings ------------------------------------------------- #
    def _page_rec(self, url):
        rec = self.page_index.get(url)
        if rec is None:
            rec = {"url": url, "http": None, "findings": [],
                   "errors": 0, "warnings": 0, "probes": 0}
            self.page_index[url] = rec
            self.pages.append(rec)
        return rec

    def finding(self, page_url, sev, kind, message, *, element=None,
                expected=None, actual=None, dest=None, shot=None):
        rec = self._page_rec(page_url)
        key = (page_url, kind, norm_text(element or "")[:80], str(actual or ""), str(dest or ""))
        if key in self._finding_keys:
            return None
        self._finding_keys.add(key)
        f = {"sev": sev, "kind": kind, "message": message}
        if element:
            f["element"] = norm_text(element)[:140]
        if expected:
            f["expected"] = expected
        if actual:
            f["actual"] = actual
        if dest:
            f["dest"] = dest
        if shot:
            f["screenshot"] = shot
        rec["findings"].append(f)
        if sev == "error":
            rec["errors"] += 1
        elif sev == "warn":
            rec["warnings"] += 1
        return f

    def verdict(self):
        errs = sum(p["errors"] for p in self.pages)
        return "red" if (errs or self.error) else "green"

    def snapshot(self, status="running"):
        errs = sum(p["errors"] for p in self.pages)
        warns = sum(p["warnings"] for p in self.pages)
        probes = sum(p["probes"] for p in self.pages)
        flat = []
        for p in self.pages:
            for f in p["findings"]:
                if f["sev"] in ("error", "warn"):
                    flat.append(dict(f, page=p["url"]))
        flat.sort(key=lambda f: 0 if f["sev"] == "error" else 1)
        return {
            "ok": self.error is None,
            "status": status,
            "verdict": self.verdict() if status == "done" else None,
            "url": self.start_url,
            "funnel_host": self.funnel_host,
            "allow_hosts": sorted(self.allow_hosts),
            "started_at": self.started_at,
            "updated_at": now(),
            "finished_at": now() if status in ("done", "error") else None,
            "progress": {
                "pages_done": len([p for p in self.pages if p["http"] is not None]),
                "pages_known": len(self.pages) + len([u for u in self.queue if u not in self.page_index]),
                "clicks_done": self.clicks_done,
            },
            "summary": {"pages": len(self.pages), "errors": errs,
                        "warnings": warns, "probes": probes},
            "pages": self.pages,
            "findings": flat,
            "error": self.error,
        }

    def write_status(self, status="running"):
        if not self.out:
            return
        data = self.snapshot(status)
        try:
            tmp = self.out + ".tmp"
            with open(tmp, "w", encoding="utf-8") as fh:
                json.dump(data, fh)
            os.replace(tmp, self.out)
        except Exception:
            pass

    def screenshot(self, page, tag):
        if not self.screens_dir:
            return None
        try:
            os.makedirs(self.screens_dir, exist_ok=True)
            self.shot_n += 1
            name = "shot-%02d-%s.png" % (self.shot_n, re.sub(r"[^a-z0-9]+", "-", tag.lower())[:30])
            page.screenshot(path=os.path.join(self.screens_dir, name), full_page=False)
            return (self.screens_web + "/" + name) if self.screens_web else name
        except Exception:
            return None

    # ---- destination verification ----------------------------------------- #
    def classify_dest(self, raw_href, abs_href):
        """internal | inpage | external | skip | empty"""
        h = (raw_href or "").strip()
        if not h or h == "#":
            return "empty"
        low = h.lower()
        if low.startswith("#"):
            return "inpage"
        if low.startswith(SKIP_SCHEMES):
            return "skip"
        target = abs_href or h
        if in_package(target, self.origin, self.base):
            return "internal"
        # same host but outside the package dir -> treat as external nav off the funnel
        return "external"

    def verify_dest(self, url):
        """Fetch a (off-funnel) destination and decide if it is a working product."""
        url = urldefrag(url)[0]
        if url in self.dest_cache:
            return self.dest_cache[url]
        res = {"ok": False, "sev": "error", "status": None, "final": url,
               "reason": "", "note": ""}
        try:
            r = self.ctx.request.get(url, max_redirects=8, timeout=self.nav_timeout)
        except Exception as exc:
            res["reason"] = "unreachable (%s)" % str(exc).splitlines()[0][:120]
            self.dest_cache[url] = res
            return res
        status = r.status
        final = r.url
        res["status"] = status
        res["final"] = final
        body = ""
        try:
            body = r.text()
        except Exception:
            body = ""
        title = ""
        m = re.search(r"<title[^>]*>(.*?)</title>", body, re.I | re.S)
        if m:
            title = norm_text(m.group(1))[:160]
        h1 = ""
        m = re.search(r"<h1[^>]*>(.*?)</h1>", body, re.I | re.S)
        if m:
            h1 = norm_text(re.sub(r"<[^>]+>", " ", m.group(1)))[:160]
        text_only = norm_text(re.sub(r"<[^>]+>", " ", body))
        head = (title + " " + h1).lower()
        fhost = host_of(final)

        if status >= 400:
            res["reason"] = "HTTP %d" % status
        elif any(sig in head for sig in ERROR_TITLE):
            res["reason"] = "error page (title/h1: %r)" % (title or h1)
        elif any(sig in text_only.lower() for sig in ERROR_STRONG):
            res["reason"] = "error text on page"
        elif len(text_only) < 200 and 300 <= status < 400:
            res["reason"] = "redirect with no content"
        elif len(text_only) < 120:
            res["sev"] = "warn"
            res["reason"] = "near-empty page (%d chars)" % len(text_only)
            res["ok"] = True
        elif self.allow_hosts and fhost not in self.allow_hosts:
            res["reason"] = "unexpected destination host '%s' (expected %s)" % (
                fhost, ", ".join(sorted(self.allow_hosts)))
        else:
            res["ok"] = True
            res["sev"] = "info"
            if not self.allow_hosts and fhost and fhost != self.funnel_host:
                res["note"] = "product host '%s' not verified (no expected host set)" % fhost
        self.dest_cache[url] = res
        return res

    # ---- element discovery ------------------------------------------------- #
    GATHER_JS = r"""
() => {
  const sel = 'a, button, [role=button], [data-cta], input[type=button], input[type=submit], summary, [onclick]';
  const els = Array.from(document.querySelectorAll(sel));
  const seen = new Set();
  const out = [];
  let id = 0;
  for (const el of els) {
    if (seen.has(el)) continue; seen.add(el);
    const tag = el.tagName.toLowerCase();
    const r = el.getBoundingClientRect();
    const st = getComputedStyle(el);
    const visible = r.width > 1 && r.height > 1 && st.display !== 'none'
        && st.visibility !== 'hidden' && st.opacity !== '0' && el.offsetParent !== null;
    let text = (el.innerText || el.textContent || '').replace(/\s+/g, ' ').trim();
    if (!text) text = (el.getAttribute('aria-label') || el.value || el.title || '').trim();
    const img = el.querySelector ? el.querySelector('img') : null;
    const logoBits = ((el.className || '') + ' ' + (img ? (img.alt || '') + ' ' + (img.getAttribute('src') || '') + ' ' + (img.className || '') : '')).toLowerCase();
    el.setAttribute('data-ft-id', String(id));
    out.push({
      id: id,
      tag: tag,
      text: text.slice(0, 160),
      rawHref: el.getAttribute('href'),
      absHref: (tag === 'a' && el.href) ? el.href : null,
      target: el.getAttribute('target') || '',
      visible: visible,
      isLogo: tag === 'a' && /logo/.test(logoBits),
      isHome: text.toLowerCase().trim() === 'home',
      hasOnclick: !!el.getAttribute('onclick') || (tag === 'button') || el.getAttribute('role') === 'button',
      ariaExpanded: el.getAttribute('aria-expanded'),
      dataToggle: !!(el.getAttribute('data-toggle') || el.getAttribute('data-bs-toggle') || el.getAttribute('data-target')),
    });
    id++;
  }
  return out;
}
"""

    def gather(self, page):
        try:
            return page.evaluate(self.GATHER_JS) or []
        except Exception:
            return []

    def sig(self, d):
        return "%s|%s|%s" % (d.get("tag"), norm_text(d.get("text"))[:60].lower(),
                             (d.get("rawHref") or "").strip()[:80])

    # ---- per-page processing ---------------------------------------------- #
    def process_page(self, page, url):
        rec = self._page_rec(url)
        descs = self.gather(page)
        anchors = [d for d in descs if d["tag"] == "a"]
        # First pass: anchors -> classify + verify destinations without clicking.
        click_probes = []
        for d in descs:
            if d["tag"] != "a":
                click_probes.append(d)
                continue
            raw = d.get("rawHref")
            kind = self.classify_dest(raw, d.get("absHref"))
            if kind == "internal":
                tgt = urldefrag(d["absHref"])[0]
                if tgt not in self.seen_pages and tgt not in self.queue:
                    self.queue.append(tgt)
                # Home/logo that navigate inside the funnel are still "alive" nav;
                # KK law wants them dead, but on a live site that is a soft issue.
                if (d["isHome"] or d["isLogo"]):
                    self.finding(url, "warn", "home-alive",
                                 "%s link navigates instead of staying put" %
                                 ("Home" if d["isHome"] else "Logo"),
                                 element=d["text"] or ("logo" if d["isLogo"] else "Home"),
                                 expected="stay on page (#)", actual=raw)
                rec["probes"] += 1
            elif kind == "inpage":
                rec["probes"] += 1  # stays on page -> ok
            elif kind == "external":
                rec["probes"] += 1
                res = self.verify_dest(d["absHref"] or raw)
                if not res["ok"]:
                    self.finding(url, "error", "broken-cta",
                                 "Outgoing link is not a working product: %s" % res["reason"],
                                 element=d["text"], expected="working step1link product",
                                 actual="HTTP %s" % (res["status"] or "?"),
                                 dest=res["final"])
                elif res["sev"] == "warn":
                    self.finding(url, "warn", "thin-product",
                                 "Outgoing link loads but looks thin: %s" % res["reason"],
                                 element=d["text"], dest=res["final"])
            elif kind == "empty":
                # "#" / empty href: a dead anchor. Only a problem if it reads like
                # a real CTA (then a JS handler must do something -> click-probe).
                if d["isHome"] or d["isLogo"]:
                    rec["probes"] += 1  # correctly dead
                elif is_hard_cta(d["text"]):
                    click_probes.append(d)
                else:
                    rec["probes"] += 1  # benign in-page anchor
            # skip: mailto/tel/etc -> ignored
        return click_probes

    # ---- click probing ----------------------------------------------------- #
    OVERLAY_JS = r"""
() => {
  const sels = ['[role=dialog]','.modal','.popup','.lightbox','.drawer',
                '[class*=modal]','[class*=popup]','[class*=lightbox]','[class*=overlay]'];
  const seen = new Set(); const out = [];
  for (const s of sels) { document.querySelectorAll(s).forEach(el => {
    if (seen.has(el)) return; seen.add(el);
    const r = el.getBoundingClientRect(); const st = getComputedStyle(el);
    const vis = r.width > 60 && r.height > 60 && st.display !== 'none'
        && st.visibility !== 'hidden' && parseFloat(st.opacity || '1') > 0.05;
    if (vis) out.push((el.id || '') + '#' + String(el.className || '').slice(0, 60));
  }); }
  return out;
}
"""
    MODAL_JS = r"""
() => {
  const sels = ['[role=dialog]','.modal','.popup','.lightbox','.drawer',
                '[class*=modal]','[class*=popup]','[class*=lightbox]','[class*=overlay]'];
  let best = null, area = 0; const seen = new Set();
  for (const s of sels) { document.querySelectorAll(s).forEach(el => {
    if (seen.has(el)) return; seen.add(el);
    const r = el.getBoundingClientRect(); const st = getComputedStyle(el);
    const vis = r.width > 60 && r.height > 60 && st.display !== 'none'
        && st.visibility !== 'hidden' && parseFloat(st.opacity || '1') > 0.05;
    if (vis && r.width * r.height > area) { area = r.width * r.height; best = el; }
  }); }
  if (!best) return { found: false, links: [] };
  const links = []; let mid = 0;
  best.querySelectorAll('a, button, [role=button]').forEach(el => {
    const r = el.getBoundingClientRect(); const st = getComputedStyle(el);
    const vis = r.width > 1 && r.height > 1 && st.display !== 'none' && st.visibility !== 'hidden';
    el.setAttribute('data-ft-mid', String(mid));
    const t = (el.innerText || el.textContent || el.getAttribute('aria-label') || '').replace(/\s+/g, ' ').trim();
    links.push({ mid: mid, tag: el.tagName.toLowerCase(), text: t.slice(0, 160),
                 rawHref: el.getAttribute('href'),
                 absHref: (el.tagName.toLowerCase() === 'a' && el.href) ? el.href : null,
                 visible: vis });
    mid++;
  });
  return { found: true, links: links };
}
"""

    def _overlays(self, page):
        try:
            return set(page.evaluate(self.OVERLAY_JS) or [])
        except Exception:
            return set()

    STATE_JS = r"""
() => {
  const b = document.body || document.documentElement;
  let expanded = document.querySelectorAll('[aria-expanded="true"],[open],.active,.show,.is-open').length;
  return [b ? b.scrollHeight : 0, b ? (b.innerText || '').length : 0, expanded];
}
"""

    def _page_state(self, page):
        try:
            return tuple(page.evaluate(self.STATE_JS) or (0, 0, 0))
        except Exception:
            return (0, 0, 0)

    @staticmethod
    def _state_changed(a, b):
        if not a or not b:
            return False
        return abs(a[0] - b[0]) > 8 or a[1] != b[1] or a[2] != b[2]

    def _dismiss_overlays(self, page, baseline):
        """Try to close any popup opened since `baseline` without a full reload.
        Returns True if no extra overlay remains (cheap path succeeded)."""
        for _ in range(2):
            extra = self._overlays(page) - baseline
            if not extra:
                return True
            try:
                page.keyboard.press("Escape")
                page.wait_for_timeout(150)
            except Exception:
                break
            extra = self._overlays(page) - baseline
            if not extra:
                return True
            # click a visible close affordance, if any
            try:
                for sstyle in ('[aria-label="Close"]', '.modal-close', '.close',
                               '[class*="close"]', '[data-dismiss]'):
                    loc = page.locator(sstyle)
                    if loc.count() > 0:
                        loc.first.click(timeout=1200)
                        page.wait_for_timeout(150)
                        break
            except Exception:
                pass
        return not (self._overlays(page) - baseline)

    def reload_base(self, page, url):
        try:
            page.goto(url, wait_until="domcontentloaded", timeout=self.nav_timeout)
            page.wait_for_timeout(250)
            self.gather(page)  # re-apply data-ft-id tags deterministically
        except Exception:
            pass

    def _close_extra_pages(self, keep):
        for p in list(self.ctx.pages):
            if p is not keep:
                try:
                    p.close()
                except Exception:
                    pass

    def _verify_nav_dest(self, page_url, d, dest, *, via="click"):
        """A click navigated somewhere; decide pass/fail."""
        kind = self.classify_dest(dest, dest)
        if kind in ("inpage", "skip", "empty"):
            return
        if kind == "internal":
            tgt = urldefrag(dest)[0]
            if tgt not in self.seen_pages and tgt not in self.queue:
                self.queue.append(tgt)
            return
        res = self.verify_dest(dest)
        if not res["ok"]:
            self.finding(page_url, "error", "broken-cta",
                         "Clicked control left to a broken destination: %s" % res["reason"],
                         element=d.get("text"), expected="working step1link product",
                         actual="HTTP %s" % (res["status"] or "?"), dest=res["final"])

    def handle_modal(self, page, page_url, d):
        try:
            info = page.evaluate(self.MODAL_JS)
        except Exception:
            return
        if not info or not info.get("found"):
            return
        links = [l for l in info.get("links", []) if l.get("visible")]
        # Choose the primary CTA inside the popup.
        cta = None
        for l in links:
            if l["tag"] == "a" and l.get("absHref") and looks_like(l["text"], CTA_TEXT):
                cta = l
                break
        if not cta:
            cta = next((l for l in links if l["tag"] == "a" and l.get("absHref")
                        and self.classify_dest(l.get("rawHref"), l.get("absHref")) == "external"), None)
        if not cta:
            cta = next((l for l in links if looks_like(l["text"], CTA_TEXT)
                        and not looks_like(l["text"], TOGGLE_TEXT)), None)
        if not cta:
            return  # popup with no clear CTA (informational) -> fine

        if cta["tag"] == "a":
            kind = self.classify_dest(cta.get("rawHref"), cta.get("absHref"))
            if kind == "external":
                res = self.verify_dest(cta["absHref"])
                if not res["ok"]:
                    self.finding(page_url, "error", "broken-popup-cta",
                                 "Popup CTA does not reach a working product: %s" % res["reason"],
                                 element=cta["text"], dest=res["final"])
            elif kind == "internal":
                tgt = urldefrag(cta["absHref"])[0]
                if tgt not in self.seen_pages and tgt not in self.queue:
                    self.queue.append(tgt)
            elif kind == "empty":
                shot = self.screenshot(page, "dead-popup")
                self.finding(page_url, "error", "dead-popup-cta",
                             "Popup CTA is a dead link (#)", element=cta["text"],
                             expected="working step1link product", actual="href='#'", shot=shot)
            return
        # CTA is a button inside the popup: click it once (first hop), watch result.
        pages_before = len(self.ctx.pages)
        url_before = page.url
        try:
            page.click('[data-ft-mid="%d"]' % cta["mid"], timeout=4000)
            page.wait_for_timeout(self.settle)
        except Exception:
            return
        if len(self.ctx.pages) > pages_before:
            newp = self.ctx.pages[-1]
            try:
                newp.wait_for_load_state("domcontentloaded", timeout=self.nav_timeout)
            except Exception:
                pass
            self._verify_nav_dest(page_url, cta, newp.url)
            self._close_extra_pages(page)
        elif urldefrag(page.url)[0] != urldefrag(url_before)[0]:
            self._verify_nav_dest(page_url, cta, page.url)
        else:
            shot = self.screenshot(page, "dead-popup-btn")
            self.finding(page_url, "error", "dead-popup-cta",
                         "Popup button did nothing when clicked", element=cta["text"],
                         expected="reach a product", actual="no navigation", shot=shot)

    def probe_clicks(self, page, page_url, probes):
        dirty = False
        for d in probes:
            if self.clicks_done >= self.max_clicks:
                break
            if not d.get("visible"):
                continue
            if looks_like(d["text"], DANGER_TEXT):
                self.finding(page_url, "info", "skipped",
                             "Skipped a payment/submit control for safety",
                             element=d["text"])
                continue
            s = self.sig(d)
            if s in self.clicked_sigs:
                continue
            self.clicked_sigs.add(s)

            if dirty:
                self.reload_base(page, page_url)
                dirty = False

            sel = '[data-ft-id="%d"]' % d["id"]
            try:
                loc = page.locator(sel).first
                if loc.count() == 0:
                    continue
                # guard: make sure we relocated the same control
                got = norm_text(loc.inner_text(timeout=1500) or "")
                if d["text"] and got and got[:40].lower() != norm_text(d["text"])[:40].lower():
                    # DOM drifted; skip rather than click the wrong thing
                    continue
                loc.scroll_into_view_if_needed(timeout=2000)
            except Exception:
                continue

            self._page_rec(page_url)["probes"] += 1
            self.clicks_done += 1
            overlays_before = self._overlays(page)
            state_before = self._page_state(page)
            url_before = page.url
            pages_before = len(self.ctx.pages)
            try:
                page.click(sel, timeout=4000)
            except Exception:
                # An element that claims to be interactive but cannot be clicked.
                if is_hard_cta(d["text"]):
                    self.finding(page_url, "warn", "unclickable",
                                 "CTA control could not be clicked", element=d["text"])
                continue
            try:
                page.wait_for_timeout(self.settle)
            except Exception:
                pass

            if len(self.ctx.pages) > pages_before:           # opened a new tab
                newp = self.ctx.pages[-1]
                try:
                    newp.wait_for_load_state("domcontentloaded", timeout=self.nav_timeout)
                except Exception:
                    pass
                self._verify_nav_dest(page_url, d, newp.url)
                self._close_extra_pages(page)
            elif urldefrag(page.url)[0] != urldefrag(url_before)[0]:   # same-tab nav
                self._verify_nav_dest(page_url, d, page.url)
                dirty = True
            else:
                new_overlays = self._overlays(page) - overlays_before
                if new_overlays:                               # opened a popup/modal
                    self.handle_modal(page, page_url, d)
                    # Try to dismiss the popup cheaply (Escape) so the next click
                    # isn't blocked by an overlay; only fall back to a full reload
                    # if the overlay is still up.
                    if not self._dismiss_overlays(page, overlays_before):
                        dirty = True
                else:
                    # No navigation and no popup. If the DOM visibly changed (an
                    # accordion, tab, slider, in-page panel) or the control is a
                    # known toggle, the visitor correctly stayed on the page.
                    # In-page mutations are harmless for later clicks (ids are
                    # stable), so we do NOT reload — that keeps the crawl fast.
                    changed = self._state_changed(state_before, self._page_state(page))
                    is_toggle = d.get("ariaExpanded") is not None or d.get("dataToggle")
                    if not (changed or is_toggle) and is_hard_cta(d["text"]):
                        shot = self.screenshot(page, "dead-" + (d["text"] or "cta"))
                        self.finding(page_url, "error", "dead-cta",
                                     "Control looks like a CTA but did nothing when clicked",
                                     element=d["text"], expected="open a product or popup",
                                     actual="no navigation, no popup", shot=shot)
                    # else: benign toggle/accordion that stayed on page -> ok

    # ---- page load + crawl ------------------------------------------------- #
    def _attach_handlers(self, page):
        self._page_errors = []
        self._asset_fail = []

        def on_error(err):
            try:
                self._page_errors.append(str(err))
            except Exception:
                pass

        def on_response(resp):
            try:
                if resp.status >= 400 and same_host(resp.url, self.origin):
                    self._asset_fail.append((resp.url, resp.status))
            except Exception:
                pass

        def on_failed(req):
            try:
                if same_host(req.url, self.origin):
                    self._asset_fail.append((req.url, "failed"))
            except Exception:
                pass

        page.on("pageerror", on_error)
        page.on("response", on_response)
        page.on("requestfailed", on_failed)

    def _load_page(self, page, url):
        rec = self._page_rec(url)
        self._page_errors = []
        self._asset_fail = []
        try:
            resp = page.goto(url, wait_until="domcontentloaded", timeout=self.nav_timeout)
            rec["http"] = resp.status if resp else None
        except Exception as exc:
            rec["http"] = None
            self.finding(url, "error", "page-load",
                         "Page failed to load: %s" % str(exc).splitlines()[0][:140],
                         expected="page loads (HTTP 200)")
            return False
        if rec["http"] and rec["http"] >= 400:
            shot = self.screenshot(page, "http-%s" % rec["http"])
            self.finding(url, "error", "page-load", "Page returned HTTP %d" % rec["http"],
                         expected="HTTP 200", actual="HTTP %d" % rec["http"], shot=shot)
            return False
        try:
            page.wait_for_load_state("networkidle", timeout=2500)
        except Exception:
            pass
        # uncaught JS errors (a crash can kill nav/menus, like static.js failures)
        for msg in list(dict.fromkeys(self._page_errors))[:3]:
            self.finding(url, "warn", "js-error",
                         "Uncaught JavaScript error: %s" % norm_text(msg)[:160])
        # broken own-domain assets
        seen = set()
        for u, status in self._asset_fail:
            if u in seen:
                continue
            seen.add(u)
            ext = os.path.splitext(urlparse(u).path)[1].lower()
            short = u.split("?", 1)[0]
            if ext in ASSET_EXT:
                self.finding(url, "error", "broken-asset",
                             "Critical asset failed to load (%s): %s" % (status, short),
                             actual="HTTP %s" % status)
            elif ext in (".png", ".jpg", ".jpeg", ".webp", ".gif", ".svg",
                         ".woff", ".woff2", ".ttf", ".mp4", ".webm"):
                self.finding(url, "warn", "broken-asset",
                             "Asset failed to load (%s): %s" % (status, short))
        return True

    def run(self):
        self.write_status("running")
        try:
            with sync_playwright() as pw:
                browser = pw.chromium.launch(
                    headless=True,
                    args=["--no-sandbox", "--disable-gpu", "--disable-dev-shm-usage"],
                )
                self.ctx = browser.new_context(
                    viewport={"width": 1366, "height": 900},
                    ignore_https_errors=True,
                    user_agent=("Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
                                "(KHTML, like Gecko) Chrome/124.0 Safari/537.36 FlowTester"),
                )
                self.ctx.set_default_timeout(self.nav_timeout)
                page = self.ctx.new_page()
                self._attach_handlers(page)

                loaded = 0
                while self.queue and loaded < self.max_pages:
                    url = self.queue.pop(0)
                    url = urldefrag(url)[0]
                    if url in self.seen_pages:
                        continue
                    self.seen_pages.add(url)
                    loaded += 1
                    if self._load_page(page, url):
                        probes = self.process_page(page, url)
                        self.probe_clicks(page, url, probes)
                    self.write_status("running")

                if self.queue:
                    self.finding(self.start_url, "info", "capped",
                                 "Reached the %d-page crawl cap; %d more page(s) not tested"
                                 % (self.max_pages, len(set(self.queue))))
                self.ctx.close()
                browser.close()
        except Exception as exc:
            self.error = "%s\n%s" % (exc, traceback.format_exc())
            self.write_status("error")
            return self.snapshot("error")
        self.write_status("done")
        return self.snapshot("done")


def main():
    ap = argparse.ArgumentParser(description="Live click-through tester for a deployed KK funnel.")
    ap.add_argument("--url", required=True, help="Live URL of the funnel's entry page.")
    ap.add_argument("--out", default=None, help="Status JSON file (incremental + final).")
    ap.add_argument("--screens-dir", default=None, help="Directory for failure screenshots.")
    ap.add_argument("--screens-web", default=None, help="Web prefix mapping to --screens-dir.")
    ap.add_argument("--allow-host", default="", help="Expected product host(s), comma-separated.")
    ap.add_argument("--max-pages", type=int, default=40)
    ap.add_argument("--max-clicks", type=int, default=250)
    ap.add_argument("--nav-timeout", type=int, default=20000)
    ap.add_argument("--settle", type=int, default=450)
    ap.add_argument("--strict", action="store_true")
    ap.add_argument("--json", action="store_true")
    args = ap.parse_args()

    if not re.match(r"^https?://", args.url, re.I):
        print(json.dumps({"ok": False, "status": "error",
                          "error": "URL must start with http:// or https://"}))
        return 1

    tester = FlowTester(
        args.url, out=args.out, screens_dir=args.screens_dir, screens_web=args.screens_web,
        allow_hosts=[h for h in args.allow_host.split(",") if h.strip()],
        max_pages=args.max_pages, max_clicks=args.max_clicks,
        nav_timeout=args.nav_timeout, settle=args.settle,
    )
    result = tester.run()
    print(json.dumps(result))
    if args.strict and result.get("verdict") == "red":
        return 1
    return 0 if result.get("ok") else 1


if __name__ == "__main__":
    sys.exit(main())





