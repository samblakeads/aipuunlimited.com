#!/usr/bin/env python3
"""
qa_checks.py - automatic quality-control gate for generated funnels.

Runs two ways:

  1. As a library, called by the build scripts at the very end of generation.
     The returned ``qc`` block is merged into each script's existing JSON result
     under a "qc" key, which the PHP dashboard wrappers already echo straight to
     the browser. Nothing for the operator to run by hand.

  2. As a standalone CLI (argparse, single JSON line on stdout) for ad-hoc / CI:
       python3 qa_checks.py --flow-dir <abs>       # static .html demo flow
       python3 qa_checks.py --checkout-dir <abs>   # standalone checkout (+chrome)
       python3 qa_checks.py --lander-dir <abs>     # standalone lander
       python3 qa_checks.py --kk-dir <abs>         # KK .php package (flows/<x>/kk)
     Exit code is 0 (non-blocking) unless --strict is given and status == "fail".

Checks (severity-tiered): broken links/assets, lander->checkout->CTA funnel
reachability, CTA destinations, chrome-injection integrity + version drift, and
KK-format compliance.

Pure stdlib + BeautifulSoup (already used by build_flow.py / import_page.py).
Nothing here mutates files; it only reads and reports.
"""
from __future__ import annotations

import argparse
import importlib
import json
import os
import posixpath
import re
import sys
from pathlib import Path

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

try:
    from bs4 import BeautifulSoup  # noqa: F401  (kept for parity; checks use regex)
except Exception as exc:  # pragma: no cover - environment guard
    print(json.dumps({"ok": False, "error": "BeautifulSoup (bs4) not installed: %s" % exc}))
    sys.exit(1)

DEFAULT_DOCROOT = os.environ.get("AIPU_DOCROOT", "/var/www/aipuunlimited.com/htdocs")


# --------------------------------------------------------------------------- #
#  Chrome version: single source of truth, imported from the injectors so the  #
#  verifier never hardcodes a value that can silently drift from the builders. #
# --------------------------------------------------------------------------- #
def _const(modname: str, name: str, default: str) -> str:
    try:
        mod = importlib.import_module(modname)
        return getattr(mod, name, default) or default
    except Exception:
        return default


# omni standalone checkouts are maintained by inject_checkout_chrome.py;
# aipu checkouts (and cross-brand conversions) by brandlib.py.
EXPECTED_BUST = {
    "omni": _const("inject_checkout_chrome", "CHROME_BUST", "20260608e"),
    "aipu": _const("brandlib", "CHROME_BUST", "20260609a"),
}

# Canonical KK offer tokens — loaded from data/kk-tokens.json (single source of
# truth shared with kk_format.py and previews/lib/kk-tokens.php). The static
# set below is only the last-resort fallback if the catalogue cannot be read.
_FALLBACK_TOKENS = {
    "creatormonthly", "studiomonthly", "premiummonthly", "scalemonthly",
    "promonthly", "agencymonthly", "promaxmonthly",
    "creatoryearly", "studioyearly", "premiumyearly", "scaleyearly",
    "proyearly", "agencyyearly", "promaxyearly",
    "lifetime", "lifetimeplan",
    "registercreate", "registercheckout",
}


def _token_catalog(docroot: str):
    """{token: meta} from kk-tokens.json, or a meta-less fallback map."""
    try:
        import kk_tokens
        return dict(kk_tokens.load_catalog(docroot))
    except Exception:
        return {t: {} for t in _FALLBACK_TOKENS}


KK_OFFER_TOKENS = set(_FALLBACK_TOKENS)

ASSET_EXTS = {
    ".css", ".js", ".mjs",
    ".png", ".jpg", ".jpeg", ".webp", ".gif", ".svg", ".ico", ".avif", ".bmp",
    ".woff", ".woff2", ".ttf", ".eot", ".otf",
    ".mp4", ".webm", ".ogg", ".mp3", ".wav", ".m4a",
    ".json", ".pdf",
}
IMAGE_EXTS = {".png", ".jpg", ".jpeg", ".webp", ".gif", ".svg", ".ico", ".avif", ".bmp"}
PAGE_EXTS = {".html", ".htm", ".php"}


# --------------------------------------------------------------------------- #
#  Findings collector                                                          #
# --------------------------------------------------------------------------- #
class QC:
    """Accumulates errors/warnings and renders the qc block."""

    def __init__(self, kind: str):
        self.kind = kind
        self.errors: list[str] = []
        self.warnings: list[str] = []
        self.checks: dict = {}

    def error(self, msg: str) -> None:
        self.errors.append(msg)

    def warn(self, msg: str) -> None:
        self.warnings.append(msg)

    def result(self) -> dict:
        status = "fail" if self.errors else ("warn" if self.warnings else "pass")
        return {
            "status": status,
            "errors": self.errors,
            "warnings": self.warnings,
            "checks": self.checks,
            "kind": self.kind,
        }


# --------------------------------------------------------------------------- #
#  Reference resolution helpers                                                #
# --------------------------------------------------------------------------- #
def _read(path: str) -> str:
    try:
        with open(path, "r", encoding="utf-8", errors="replace") as fh:
            return fh.read()
    except Exception:
        return ""


def _strip_php(text: str) -> str:
    """Remove <?php ... ?> and <?= ... ?> blocks to inspect the rendered body."""
    text = re.sub(r"<\?php.*?\?>", "", text, flags=re.S)
    text = re.sub(r"<\?=.*?\?>", "", text, flags=re.S)
    text = re.sub(r"<\?.*?\?>", "", text, flags=re.S)
    return text


def classify_ref(ref: str) -> str:
    """external | dynamic | anchor | local"""
    ref = (ref or "").strip()
    if not ref or ref.startswith("#"):
        return "anchor"
    low = ref.lower()
    if low.startswith(("http://", "https://", "//", "data:", "mailto:", "tel:", "javascript:")):
        return "external"
    if "<?" in ref:
        return "dynamic"
    return "local"


def resolve_local(ref: str, page_path: str, docroot: str, mounts=None):
    """Map a local ref to (kind, fs_path, web_or_rel_path).

    kind is one of: asset | page | route | escape | empty.
    fs_path is None for route/escape/empty.

    ``mounts`` is an optional list of (web_prefix, fs_root): an absolute web
    path under web_prefix resolves beneath fs_root instead of docroot. Used so a
    self-contained KK package whose paths are rooted at /{kk_name}/ resolves
    against the package dir (its real web root) rather than the live docroot.
    """
    path = ref.split("#", 1)[0].split("?", 1)[0]
    if not path:
        return "empty", None, path
    if path.startswith("/"):
        norm = posixpath.normpath(path)
        if norm.startswith(".."):
            return "escape", None, path
        fs = None
        for prefix, root in (mounts or []):
            if norm == prefix or norm.startswith(prefix.rstrip("/") + "/"):
                fs = os.path.join(root, norm[len(prefix.rstrip("/")):].lstrip("/"))
                break
        if fs is None:
            fs = os.path.join(docroot, norm.lstrip("/"))
    else:
        fs = os.path.normpath(os.path.join(os.path.dirname(page_path), path))
    ext = os.path.splitext(path.rstrip("/"))[1].lower()
    if ext in ASSET_EXTS:
        return "asset", fs, path
    if ext in PAGE_EXTS:
        return "page", fs, path
    return "route", None, path


def _exists(fs_path: str) -> bool:
    if os.path.exists(fs_path):
        return True
    # tolerate a directory link that resolves to an index file
    if fs_path.endswith("/") or os.path.isdir(fs_path):
        for idx in ("index.html", "index.php"):
            if os.path.isfile(os.path.join(fs_path, idx)):
                return True
    return False


_SRC_ATTRS = ("src", "href")


def _iter_asset_refs(text: str):
    """Yield (attr_value) for asset/page-bearing attributes in raw HTML/PHP."""
    # link/script/img/source/video/audio/iframe href|src
    for m in re.finditer(r'<(?:link|script|img|source|video|audio|iframe)\b[^>]*?>', text, re.I):
        tag = m.group(0)
        for attr in _SRC_ATTRS:
            am = re.search(r'\b%s\s*=\s*"([^"]*)"' % attr, tag, re.I)
            if am:
                yield am.group(1)
        # srcset (take the url of each candidate)
        sm = re.search(r'\bsrcset\s*=\s*"([^"]*)"', tag, re.I)
        if sm:
            for cand in sm.group(1).split(","):
                url = cand.strip().split()[0] if cand.strip() else ""
                if url:
                    yield url
    # og:image / twitter:image meta
    for m in re.finditer(r'<meta\b[^>]*?>', text, re.I):
        tag = m.group(0)
        if re.search(r'(?:property|name)\s*=\s*"(?:og:image|twitter:image)"', tag, re.I):
            cm = re.search(r'\bcontent\s*=\s*"([^"]*)"', tag, re.I)
            if cm:
                yield cm.group(1)
    # url(...) in inline styles / <style> blocks
    for m in re.finditer(r'url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)', text, re.I):
        yield m.group(1)


def _iter_page_links(text: str):
    """Yield href values of <a> tags (for funnel / internal-link reachability)."""
    for m in re.finditer(r'<a\b[^>]*?\bhref\s*=\s*"([^"]*)"[^>]*>', text, re.I):
        yield m.group(1)


def _iter_cta_hrefs(text: str):
    """Yield href values of anchors carrying data-cta (PHP-safe, regex based).

    The attribute capture consumes quoted strings whole so a dynamic href that
    the KK formatter rewrote to a PHP expression (href="<?= ... ?>") does NOT
    terminate the tag early on the '>' inside '?>'. Without this, such anchors
    were misread as having an empty href and wrongly flagged as dead CTAs.
    """
    for m in re.finditer(r'<a\b((?:[^<>"]|"[^"]*")*)>', text, re.I):
        attrs = m.group(1)
        if not re.search(r'\bdata-cta\b', attrs, re.I):
            continue
        hm = re.search(r'\bhref\s*=\s*"([^"]*)"', attrs, re.I)
        yield hm.group(1) if hm else ""


# --------------------------------------------------------------------------- #
#  Individual checks                                                           #
# --------------------------------------------------------------------------- #
_CRITICAL_ASSET_EXTS = {".css", ".js", ".mjs"}


def _is_favicon(path: str) -> bool:
    base = os.path.basename(path.split("?", 1)[0].split("#", 1)[0]).lower()
    return base.startswith("favicon") or base.startswith("apple-touch-icon")


def _asset_severity(ref_path: str) -> str:
    """error | warn | skip — how bad a missing reference is.

    CSS/JS and internal page links break the page (error); images, fonts and
    media merely degrade it (warn); favicons are conventional and tolerated.
    """
    if _is_favicon(ref_path):
        return "skip"
    ext = os.path.splitext(ref_path.split("?", 1)[0].split("#", 1)[0].rstrip("/"))[1].lower()
    if ext in _CRITICAL_ASSET_EXTS or ext in PAGE_EXTS:
        return "error"
    return "warn"


def check_links(qc: QC, files: list[str], docroot: str, mounts=None) -> None:
    """Flag missing asset and internal page references across the given files."""
    checked = 0
    seen: set = set()
    errors = 0
    warns = 0

    def record(rel: str, ref: str, fs: str, label: str = "") -> None:
        nonlocal checked, errors, warns
        checked += 1
        if _exists(fs):
            return
        sev = _asset_severity(ref)
        if sev == "skip":
            return
        key = (rel, ref)
        if key in seen:
            return
        seen.add(key)
        suffix = (" (%s)" % label) if label else ""
        msg = "broken reference%s: %s -> %s" % (suffix, rel, ref)
        if sev == "error":
            errors += 1
            qc.error(msg)
        else:
            warns += 1
            qc.warn(msg)

    for f in files:
        text = _read(f)
        rel = os.path.basename(f)
        for ref in _iter_asset_refs(text):
            if classify_ref(ref) != "local":
                continue
            rkind, fs, _p = resolve_local(ref, f, docroot, mounts)
            if rkind == "escape":
                qc.error("%s: asset path escapes docroot: %s" % (rel, ref))
            elif rkind in ("asset", "page"):
                record(rel, ref, fs)
        # internal <a> page targets (skips routes like /register, /signup)
        for ref in _iter_page_links(text):
            if classify_ref(ref) != "local":
                continue
            rkind, fs, _p = resolve_local(ref, f, docroot, mounts)
            if rkind == "page":
                record(rel, ref, fs, "link")
    qc.checks["links"] = {
        "checked": checked, "missing": errors + warns,
        "missing_critical": errors, "missing_minor": warns,
        "php_aware": any(f.endswith(".php") for f in files),
    }


def check_funnel(qc: QC, flow_dir: str) -> None:
    """Assert the lander -> checkout -> CTA chain holds for a static flow."""
    index = os.path.join(flow_dir, "index.html")
    checkout = os.path.join(flow_dir, "checkout.html")
    reachable = os.path.isfile(checkout)
    linked = False
    has_cta = False
    if os.path.isfile(index):
        itext = _read(index)
        linked = bool(re.search(r'href\s*=\s*"[^"]*checkout\.html', itext, re.I))
    else:
        qc.error("funnel: entry index.html missing in %s" % os.path.basename(flow_dir))
    if not reachable:
        qc.error("funnel: checkout.html missing (presell cannot reach checkout)")
    elif not linked:
        qc.warn("funnel: index.html has no link to checkout.html")
    if reachable:
        ctext = _read(checkout)
        # Flow checkouts proceed via several CTA mechanisms: data-cta anchors,
        # the plan-picker's data-checkout / pcol-cta buttons, or a register link.
        has_cta = bool(
            list(_iter_cta_hrefs(ctext))
            or re.search(r"data-checkout|pcol-cta|data-cta|__KK_REGISTER|/register", ctext, re.I)
        )
        if not has_cta:
            qc.warn("funnel: checkout.html has no recognizable CTA / proceed button")
    qc.checks["funnel"] = {
        "checkout_reachable": reachable,
        "presell_links_checkout": linked,
        "checkout_has_cta": has_cta,
    }


def check_ctas(qc: QC, files: list[str], kind: str) -> None:
    """Audit data-cta destinations by artifact type."""
    total = 0
    bad = 0
    for f in files:
        text = _read(f)
        rel = os.path.basename(f)
        for href in _iter_cta_hrefs(text):
            total += 1
            h = (href or "").strip()
            cl = classify_ref(h)
            # dead / empty / pure-anchor CTA
            if cl == "anchor":
                bad += 1
                qc.error("%s: CTA points to a dead anchor (%r)" % (rel, h or ""))
                continue
            # CTA pointing at an image file
            ext = os.path.splitext(h.split("?", 1)[0].split("#", 1)[0])[1].lower()
            if ext in IMAGE_EXTS:
                bad += 1
                qc.error("%s: CTA points to an image (%s)" % (rel, h))
                continue
            if kind == "checkout":
                m = re.search(r"/signup\?offer=([a-z0-9_-]+)", h, re.I)
                if cl == "dynamic":
                    continue
                if not m:
                    qc.warn("%s: CTA target is not /signup?offer=... (%s)" % (rel, h))
                elif m.group(1).lower() not in KK_OFFER_TOKENS:
                    qc.warn("%s: CTA offer token not recognized: %s" % (rel, m.group(1)))
            elif kind == "kk":
                if "/signup?offer=" in h:
                    qc.error("%s: KK CTA still hardcodes /signup?offer= (%s)" % (rel, h))
                elif cl != "dynamic" and not (h.startswith("#") or h.startswith("/")):
                    qc.warn("%s: KK CTA is neither a PHP expr nor a site path (%s)" % (rel, h))
            elif kind == "flow":
                if "/signup?offer=" in h:
                    qc.warn("%s: flow CTA leaks a /signup?offer= link (expected checkout.html or /register)" % rel)
            # lander: only the universal dead/image checks above apply
    qc.checks["ctas"] = {"total": total, "bad": bad}


def _detect_brand_from_chrome(text: str):
    if "/checkouts-omni/_chrome/" in text or "OmniRogue checkout" in text:
        return "omni"
    if "/aipu-checkouts/_chrome/" in text or "AIPU checkout" in text:
        return "aipu"
    return None


def check_chrome(qc: QC, index_file: str) -> None:
    """Verify exactly one balanced chrome block of each kind + version sanity."""
    text = _read(index_file)
    counts = {}
    for marker in ("head", "header", "footer"):
        opens = len(re.findall(r"<!--\s*chrome:%s\s*-->" % marker, text))
        closes = len(re.findall(r"<!--\s*/chrome:%s\s*-->" % marker, text))
        counts[marker] = {"open": opens, "close": closes}
        if opens > 1 or closes > 1:
            qc.error("chrome: %s block injected %dx (double injection)" % (marker, max(opens, closes)))
        elif opens != closes:
            qc.error("chrome: %s block markers unbalanced (open=%d close=%d)" % (marker, opens, closes))

    any_chrome = any(c["open"] for c in counts.values())
    if not any_chrome:
        qc.warn("chrome: no chrome markers found (page has no injected header/footer)")
        qc.checks["chrome"] = {"present": False, "counts": counts}
        return

    brand = _detect_brand_from_chrome(text)
    # brand marker vs asset-path brand agreement
    asset_omni = "/checkouts-omni/_chrome/" in text
    asset_aipu = "/aipu-checkouts/_chrome/" in text
    marker_omni = "OmniRogue checkout" in text
    marker_aipu = "AIPU checkout" in text
    if (asset_omni and marker_aipu) or (asset_aipu and marker_omni):
        qc.error("chrome: brand mismatch between asset paths and header markers")

    css = re.search(r"chrome\.css\?v=([^\"'&]+)", text)
    js = re.search(r"chrome\.js\?v=([^\"'&]+)", text)
    css_v = css.group(1) if css else None
    js_v = js.group(1) if js else None
    if css_v and js_v and css_v != js_v:
        qc.error("chrome: css/js version mismatch on page (css=%s js=%s)" % (css_v, js_v))
    expected = EXPECTED_BUST.get(brand)
    found = css_v or js_v
    if expected and found and found != expected:
        qc.warn("chrome: version drift (found %s, expected %s for %s)" % (found, expected, brand))
    qc.checks["chrome"] = {
        "present": True, "brand": brand, "version": found,
        "expected": expected, "counts": counts,
    }


# Attrs may contain '>' inside quoted values (e.g. href="...<?= $__step1link; ?>"),
# so the attr group must consume quoted strings whole.
_KK_ANCHOR_RE = re.compile(r'<a\b((?:[^<>"]|"[^"]*")*)>(.*?)</a>', re.S | re.I)
_KK_HREF_RE = re.compile(r'\bhref\s*=\s*"([^"]*)"', re.I)

# Known shared-collection roots that must never leak into a KK package (Law 7).
_KK_STALE_ROOTS = (
    "/flows/", "/omnirogue-checkouts/", "/aipu-checkouts/", "/checkouts-omni/",
    "/omnirogue-landers/", "/aipu-landers/", "/omnirogue-pages/", "/aipu-pages/",
    "/multistep-kk/", "/multistep/", "/kk-june8/",
)


def _kk_anchor_text(inner: str) -> str:
    return re.sub(r"\s+", " ", re.sub(r"<[^>]+>", " ", inner)).strip()


def _kk_is_logo_anchor(inner: str) -> bool:
    if "<img" not in inner.lower():
        return False
    return bool(re.search(r'<img\b[^>]*\b(?:alt|src|srcset|class)\s*=\s*"[^"]*logo', inner, re.I))


_KK_FLOW_TYPES = ("multi", "sales-only", "checkout-only", "single-page")


def _kk_flow_type(kk_dir: str, flow_type) -> str:
    """flow_type from the caller, the parent flow.json, or file inference."""
    if flow_type in _KK_FLOW_TYPES:
        return flow_type
    manifest = os.path.join(os.path.dirname(kk_dir), "flow.json")
    if os.path.isfile(manifest):
        try:
            with open(manifest, encoding="utf-8") as fh:
                ft = (json.load(fh) or {}).get("flow_type")
            if ft in _KK_FLOW_TYPES:
                return ft
        except Exception:
            pass
    if os.path.isfile(os.path.join(kk_dir, "checkout.php")):
        return "multi"
    # billing.php (the pop-up checkout) alongside extra pages = single-page flow.
    if os.path.isfile(os.path.join(kk_dir, "billing.php")):
        return "single-page"
    return "sales-only"


def _check_kk_order_prices(qc: QC, kk_dir: str, checkout_file: str, docroot: str,
                           catalog: dict) -> None:
    """Law 1: var ORDER tokens must be canonical plan tokens whose prices match
    the plan card prices rendered by bundle.js."""
    text = _read(checkout_file)
    m = re.search(r"var ORDER\s*=\s*(\{.*?\})\s*;", text)
    if not m:
        return
    rel = os.path.basename(checkout_file)
    try:
        order = json.loads(m.group(1))
    except ValueError:
        qc.error("kk(L1): %s has an unparseable var ORDER block" % rel)
        return

    # LINKS must be PHP-wired, never a static /signup map.
    lm = re.search(r"var LINKS = (.{0,120})", text)
    if lm and "/signup?offer=" in lm.group(1):
        qc.error("kk(L1): %s wires plan buttons to static /signup?offer= links" % rel)

    bundle = None
    for root, _dirs, files in os.walk(kk_dir):
        if "plans-pick-your-plan" in root.replace("\\", "/") and "bundle.js" in files:
            bundle = os.path.join(root, "bundle.js")
            break
    bundle_text = _read(bundle) if bundle else ""
    bundle_ids = re.findall(r'\{id:"([a-z0-9-]+)"', bundle_text)

    try:
        import kk_tokens
        normalize = kk_tokens.normalize_price
    except Exception:
        normalize = lambda v: str(v or "").strip().lstrip("$").replace(",", "")  # noqa: E731

    field_for = {"monthly": "mPrice", "yearly": "yPrice", "lifetime": "lPrice"}
    for period, toks in order.items():
        if not isinstance(toks, list):
            continue
        for i, tok in enumerate(toks):
            if tok not in catalog:
                qc.error("kk(L1): %s ORDER %s[%d] uses unknown token '%s'"
                         % (rel, period, i, tok))
                continue
            meta = catalog.get(tok) or {}
            if meta and meta.get("kind") == "register":
                qc.error("kk(L1): %s ORDER %s[%d] wires a plan button to register "
                         "token '%s'" % (rel, period, i, tok))
                continue
            if meta.get("period") and meta["period"] != period:
                qc.error("kk(L1): %s ORDER %s[%d] token '%s' is a %s token"
                         % (rel, period, i, tok, meta["period"]))
            field = field_for.get(period)
            if not (field and bundle_text and i < len(bundle_ids)):
                continue
            pid = bundle_ids[i]
            bm = re.search(r'\{id:"%s".*?(?:\},\{id:"|\],S=)' % re.escape(pid),
                           bundle_text, re.S)
            block = bm.group(0) if bm else ""
            pm = re.search(r'%s:"([^"]*)"' % field, block)
            if not pm:
                continue
            displayed = normalize(pm.group(1))
            canonical = normalize(meta.get("price"))
            if displayed and canonical and displayed != canonical:
                qc.error("kk(L1): PRICE MISMATCH — plan '%s' displays $%s/%s but its "
                         "button fires '%s' (canonical $%s)"
                         % (pid, displayed, period, tok, canonical))


def _check_kk_anchor_laws(qc: QC, fp: str, kk_name: str, kk_dir: str) -> None:
    """Laws 5/6/7 over every <a> tag of one page (raw text, PHP-aware)."""
    text = _read(fp)
    rel = os.path.basename(fp)
    home_flagged = set()
    seen_targets = set()
    for m in _KK_ANCHOR_RE.finditer(text):
        attrs, inner = m.group(1), m.group(2)
        hm = _KK_HREF_RE.search(attrs)
        href = (hm.group(1) if hm else "").strip()
        atext = _kk_anchor_text(inner)

        # Law 5: Home + logo anchors must be dead.
        if atext.lower() == "home" or _kk_is_logo_anchor(inner):
            if href not in ("#", "") and not href.startswith("#"):
                key = (rel, atext.lower() or "logo")
                if key not in home_flagged:
                    home_flagged.add(key)
                    qc.error("kk(L5): %s %s link is not dead (href=%s)"
                             % (rel, "Home" if atext.lower() == "home" else "logo", href[:80]))
            continue

        # Law 6: no external anchors.
        low = href.lower()
        if low.startswith(("http://", "https://", "//")):
            qc.error("kk(L6): %s has an outgoing external link: %s" % (rel, href[:120]))
            continue

        if not href.startswith("/"):
            continue

        path = href.split("<?", 1)[0].split("?", 1)[0].split("#", 1)[0]

        # Law 7: local absolute anchors must stay inside /{kk_name}/.
        if "<?" not in href:
            if kk_name and not (path == "/" + kk_name or path.startswith("/%s/" % kk_name)):
                qc.error("kk(L7): %s links outside the package: %s" % (rel, href[:120]))
            continue

        # Tracked internal links (/{kk}/page.php<?= $__step1link; ?>): the page
        # itself must exist inside the package.
        if kk_name and path.startswith("/%s/" % kk_name) and path.endswith(".php"):
            target = os.path.join(kk_dir, path[len(kk_name) + 2:])
            if target not in seen_targets:
                seen_targets.add(target)
                if not os.path.isfile(target):
                    qc.error("kk(L7): %s links to a page missing from the package: %s"
                             % (rel, path))


def _check_kk_text_assets(qc: QC, kk_dir: str, kk_name: str) -> None:
    """Law 7 in css/js/json: no stale shared-collection roots, no /flows/ paths,
    and css url(...) absolute refs must stay inside /{kk_name}/."""
    for root, _dirs, files in os.walk(kk_dir):
        for fn in files:
            if not fn.endswith((".css", ".js", ".json")):
                continue
            fp = os.path.join(root, fn)
            text = _read(fp)
            rel = os.path.relpath(fp, kk_dir)
            for stale in _KK_STALE_ROOTS:
                if stale in text:
                    qc.error("kk(L7): %s references %s — package must be self-contained"
                             % (rel, stale.rstrip("/")))
            if fn.endswith(".css"):
                for um in re.finditer(r'url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)', text):
                    p = um.group(1).strip().split("?", 1)[0].split("#", 1)[0]
                    low = p.lower()
                    # external / CDN / inline assets are allowed (BunnyCDN pull-zone
                    # URLs are absolute https; protocol-relative + data: too).
                    if low.startswith(("http://", "https://", "//", "data:")):
                        continue
                    if not p.startswith("/"):
                        continue  # relative — resolved against the file's dir elsewhere
                    if kk_name and not p.startswith("/%s/" % kk_name):
                        qc.error("kk(L7): %s css url() escapes the package: %s"
                                 % (rel, um.group(1)[:120]))


# --------------------------------------------------------------------------- #
#  Double-header detection (Workstream 4)                                       #
# --------------------------------------------------------------------------- #
# The injected canonical site nav wrapper. Every built/KK page must carry
# EXACTLY ONE of these and nothing else that acts as a top-of-page header.
_CANON_NAV_RE = re.compile(
    r'<div class="fixed top-0 left-0 right-0 z-50">.*?</nav>\s*</div>', re.S)

# Signals that a <header>/<nav> is a SITE header (vs a content/section header).
# Mirror of build_plans_and_omnifull.strip_lander_site_header so the gate and
# the stripper agree on what counts as site chrome.
_SITE_HEADER_SIGNALS = re.compile(
    r'<img\b[^>]*\b(?:alt|src|srcset|class)\s*=\s*"[^"]*logo'  # a logo image
    r'|aria-label="Open menu"|data-or-mobile-toggle'           # hamburger trigger
    r'|class="[^"]*\bnav\b[^"]*"'                               # nav-classed element
    r'|>\s*Home\s*<',                                           # a "Home" link/label
    re.I,
)


def _detect_double_header(path: str) -> str | None:
    """Return a message if `path` has more than one top-of-page header bar — the
    injected canonical nav PLUS a leftover lander header/nav — else None."""
    raw = _strip_php(_read(path))
    if not raw:
        return None
    n_canon = len(_CANON_NAV_RE.findall(raw))
    if n_canon > 1:
        return "%d canonical site navs present (expected 1)" % n_canon
    # Drop the canonical nav, then scan the region ABOVE the main content for a
    # leftover site header/nav (deeper section/article headers are real content).
    rest = _CANON_NAV_RE.sub("", raw, count=1)
    content_m = re.search(r"<(?:section|main)\b", rest, re.I)
    top = rest[: content_m.start()] if content_m else rest
    for tag in ("header", "nav"):
        m = re.search(r"<%s\b[^>]*>.*?</%s>" % (tag, tag), top, re.S | re.I)
        if m and _SITE_HEADER_SIGNALS.search(m.group(0)):
            return "a second site <%s> remains above the page content" % tag
    return None


def check_single_header(qc: QC, files: list[str], *, blocking: bool) -> None:
    """Flag pages that ended up with two stacked headers (double header).

    blocking=True -> qc.error (hard-gates the KK package); False -> qc.warn
    (surfaced at flow build without blocking generation)."""
    for fp in files:
        msg = _detect_double_header(fp)
        if msg:
            text = "double header on %s: %s" % (os.path.basename(fp), msg)
            (qc.error if blocking else qc.warn)(text)


def check_kk(qc: QC, kk_dir: str, docroot: str, flow_type=None) -> None:
    """Validate the KK .php package against the seven KK laws."""
    index_php = os.path.join(kk_dir, "index.php")
    checkout_php = os.path.join(kk_dir, "checkout.php")
    billing_php = os.path.join(kk_dir, "billing.php")
    config_php = os.path.join(kk_dir, "_kk-config.php")
    offers_php = os.path.join(kk_dir, "_checkout-offers.php")

    flow_type = _kk_flow_type(kk_dir, flow_type)
    catalog = _token_catalog(docroot)
    # The page that carries the checkout / offer wiring for each flow shape.
    # single-page: the billing pop-up page is the checkout. multi: checkout.php.
    # one-page (sales-only/checkout-only): index.php.
    if flow_type == "multi":
        checkout_page = checkout_php
    elif flow_type == "single-page":
        checkout_page = billing_php
    else:
        checkout_page = index_php

    # ---- Laws 2/3/4: required structure ----
    required = [index_php, config_php, offers_php]
    if flow_type == "multi":
        required.append(checkout_php)
    elif flow_type == "single-page":
        required.append(billing_php)
    for req in required:
        if not os.path.isfile(req):
            qc.error("kk(L2-4): required file missing: %s" % os.path.basename(req))
    # A single-page flow's checkout is the billing pop-up; a separate checkout.php
    # must never exist. One-page flows (sales-only/checkout-only) host everything
    # in index.php, so they too must not carry a checkout.php.
    if flow_type != "multi" and os.path.isfile(checkout_php):
        where = "the billing pop-up (billing.php)" if flow_type == "single-page" else "index.php"
        qc.error("kk(L3): '%s' flow must be hosted in %s but checkout.php exists"
                 % (flow_type, where))
    for stray in ("index.html", "checkout.html", "billing.html"):
        if os.path.isfile(os.path.join(kk_dir, stray)):
            qc.error("kk(L4): stray static file present: %s (must be .php)" % stray)

    # Law 2: index.php must have real content, not just headers.
    if os.path.isfile(index_php):
        raw_index = _read(index_php)
        body = re.sub(r"\s+", "", _strip_php(raw_index))
        if "<body" not in raw_index.lower() or len(body) < 500:
            qc.error("kk(L2): index.php has no meaningful content")

    # kk_name from _kk-config.php ($__web)
    kk_name = None
    cfg = _read(config_php)
    wm = re.search(r"\$__web\s*=\s*'([^']+)'", cfg)
    if wm:
        kk_name = wm.group(1).strip("/")
    if not kk_name:
        kk_name = os.path.basename(os.path.dirname(kk_dir))

    # ---- critical: headers + includes on the cloaked pages ----
    cloaked = [("index.php", index_php, flow_type != "multi")]
    if flow_type == "multi":
        cloaked.append(("checkout.php", checkout_php, True))
    elif flow_type == "single-page":
        # The billing pop-up page is cloaked (money.php) — it resolves offers.
        cloaked.append(("billing.php", billing_php, True))
    for label, fp, _needs_offers in cloaked:
        if not os.path.isfile(fp):
            continue
        head = _read(fp)[:1200]
        if "money.php" not in head:
            qc.error("kk: %s missing money.php header (cloaked page)" % label)
        if "_kk-config.php" not in head:
            qc.error("kk: %s does not require _kk-config.php" % label)
        if "_checkout-offers.php" not in head:
            qc.error("kk: %s does not require _checkout-offers.php" % label)

    # ---- critical: no unreplaced placeholders leaking into rendered HTML ----
    php_files = [os.path.join(kk_dir, f) for f in os.listdir(kk_dir)
                 if f.endswith(".php") and not f.startswith("_")]
    for fp in php_files:
        body = _strip_php(_read(fp))
        rel = os.path.basename(fp)
        for pat, label in ((r"\$link\[", "$link[...]"),
                           (r"\$offer\[", "$offer[...]"),
                           (r"\$__", "$__ var")):
            if re.search(pat, body):
                qc.error("kk: %s leaks unresolved %s into rendered HTML" % (rel, label))
                break

    # ---- Law 1: hardcoded signup + plan-token/price wiring ----
    for fp in php_files:
        text = _read(fp)
        rel = os.path.basename(fp)
        if "/signup?offer=" in text:
            qc.error("kk(L1): %s hardcodes /signup?offer= (bypasses KK offer wiring)" % rel)
    if os.path.isfile(checkout_page):
        _check_kk_order_prices(qc, kk_dir, checkout_page, docroot, catalog)

    # ---- Laws 5/6/7 over every anchor of every page ----
    for fp in php_files:
        _check_kk_anchor_laws(qc, fp, kk_name, kk_dir)

    # ---- Law 7: absolute asset refs must stay inside /{kk_name}/ ----
    leaks: set = set()
    for fp in php_files:
        text = _read(fp)
        rel = os.path.basename(fp)
        for ref in _iter_asset_refs(text):
            if classify_ref(ref) != "local":
                continue
            p = ref.split("#", 1)[0].split("?", 1)[0]
            if not p.startswith("/") or _is_favicon(p):
                continue  # relative paths resolve within the package; favicons tolerated
            if os.path.splitext(p)[1].lower() not in ASSET_EXTS:
                continue
            if kk_name and not (p == "/" + kk_name or p.startswith("/%s/" % kk_name)):
                key = (rel, ref)
                if key not in leaks:
                    leaks.add(key)
                    qc.error("kk(L7): %s references an asset outside the KK package "
                             "(404 after deploy): %s" % (rel, ref))
    _check_kk_text_assets(qc, kk_dir, kk_name)

    # ---- medium: offer wiring + JS config ----
    offers = _read(offers_php)
    if "__kk_offer_step1link" not in offers:
        qc.warn("kk: _checkout-offers.php missing __kk_offer_step1link() function")
    if "$__kk_offer_links" not in offers:
        qc.warn("kk: _checkout-offers.php missing $__kk_offer_links array")
    if os.path.isfile(checkout_page) and "window.__KK_CHECKOUT_URL" not in _read(checkout_page):
        qc.warn("kk: %s missing window.__KK_* JS config block" % os.path.basename(checkout_page))

    qc.checks["kk"] = {"kk_name": kk_name, "php_files": len(php_files),
                       "flow_type": flow_type}
    # Asset existence, resolved against the package's own web root (/{kk_name}/).
    css_files = []
    for root, _dirs, files in os.walk(kk_dir):
        css_files.extend(os.path.join(root, f) for f in files if f.endswith(".css"))
    mounts = [("/" + kk_name, kk_dir)] if kk_name else None
    check_links(qc, php_files + css_files, docroot, mounts=mounts)
    check_ctas(qc, php_files, "kk")
    # Hard gate: a KK package must never ship two stacked headers.
    check_single_header(qc, php_files, blocking=True)


# --------------------------------------------------------------------------- #
#  Dispatchers (the public API the build scripts call)                         #
# --------------------------------------------------------------------------- #
def _html_files(d: str) -> list[str]:
    if not os.path.isdir(d):
        return []
    return [os.path.join(d, f) for f in sorted(os.listdir(d)) if f.endswith(".html")]


def _entry_file(d: str) -> str | None:
    for cand in ("index.html", "index.php"):
        p = os.path.join(d, cand)
        if os.path.isfile(p):
            return p
    return None


def validate_flow(flow_dir: str, docroot: str = DEFAULT_DOCROOT, brand: str | None = None) -> dict:
    qc = QC("flow")
    files = _html_files(flow_dir)
    if not files:
        qc.error("flow: no .html pages found in %s" % flow_dir)
        return qc.result()
    check_links(qc, files, docroot)
    # One-page (sales-only / checkout-only) and single-page flows have no
    # checkout.html by design — sales/checkout-only host everything in index.html
    # (Law 3); single-page's checkout is the in-page billing pop-up. The funnel
    # reachability check (lander -> checkout.html) only applies to multi.
    ft = None
    manifest = os.path.join(flow_dir, "flow.json")
    if os.path.isfile(manifest):
        try:
            with open(manifest, encoding="utf-8") as fh:
                ft = (json.load(fh) or {}).get("flow_type")
        except Exception:
            ft = None
    if ft not in ("sales-only", "checkout-only", "single-page"):
        check_funnel(qc, flow_dir)
    check_ctas(qc, files, "flow")
    # Surface (non-blocking) any page that ended up with a double header.
    check_single_header(qc, files, blocking=False)
    return qc.result()


def validate_checkout(item_dir: str, docroot: str = DEFAULT_DOCROOT, brand: str | None = None) -> dict:
    qc = QC("checkout")
    entry = _entry_file(item_dir)
    if not entry:
        qc.error("checkout: no index.html/index.php in %s" % item_dir)
        return qc.result()
    check_links(qc, [entry], docroot)
    check_ctas(qc, [entry], "checkout")
    if entry.endswith(".html"):
        check_chrome(qc, entry)
    return qc.result()


def validate_lander(item_dir: str, docroot: str = DEFAULT_DOCROOT, brand: str | None = None) -> dict:
    qc = QC("lander")
    entry = _entry_file(item_dir)
    if not entry:
        qc.error("lander: no index.html/index.php in %s" % item_dir)
        return qc.result()
    check_links(qc, [entry], docroot)
    check_ctas(qc, [entry], "lander")
    return qc.result()


def validate_kk(kk_dir: str, docroot: str = DEFAULT_DOCROOT, flow_type=None) -> dict:
    qc = QC("kk")
    if not os.path.isdir(kk_dir):
        qc.error("kk: directory not found: %s" % kk_dir)
        return qc.result()
    check_kk(qc, kk_dir, docroot, flow_type=flow_type)
    return qc.result()


_DISPATCH = {
    "flow": validate_flow,
    "checkout": validate_checkout,
    "lander": validate_lander,
    "kk": validate_kk,
}


def safe_validate(kind: str, *args, **kwargs) -> dict:
    """Non-blocking entry point for the build scripts.

    Runs the right validator and NEVER raises: any internal failure is reported
    as a single warning so a QC bug can't break a generation pipeline.
    """
    try:
        return _DISPATCH[kind](*args, **kwargs)
    except Exception as exc:  # pragma: no cover - defensive
        return {
            "status": "warn", "errors": [],
            "warnings": ["qc gate could not run: %s" % exc],
            "checks": {}, "kind": kind,
        }


# --------------------------------------------------------------------------- #
#  Standalone CLI                                                              #
# --------------------------------------------------------------------------- #
def main() -> int:
    ap = argparse.ArgumentParser(description="QC gate for generated funnels/checkouts/KK packages.")
    g = ap.add_mutually_exclusive_group(required=True)
    g.add_argument("--flow-dir")
    g.add_argument("--checkout-dir")
    g.add_argument("--lander-dir")
    g.add_argument("--kk-dir")
    ap.add_argument("--docroot", default=DEFAULT_DOCROOT)
    ap.add_argument("--strict", action="store_true",
                    help="exit non-zero when status == 'fail' (for CI)")
    args = ap.parse_args()
    docroot = os.path.realpath(args.docroot)
    try:
        if args.flow_dir:
            qc = validate_flow(os.path.realpath(args.flow_dir), docroot)
        elif args.checkout_dir:
            qc = validate_checkout(os.path.realpath(args.checkout_dir), docroot)
        elif args.lander_dir:
            qc = validate_lander(os.path.realpath(args.lander_dir), docroot)
        else:
            qc = validate_kk(os.path.realpath(args.kk_dir), docroot)
    except Exception as exc:
        print(json.dumps({"ok": False, "error": str(exc)}))
        return 1
    print(json.dumps({"ok": True, "qc": qc}))
    if args.strict and qc["status"] == "fail":
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
