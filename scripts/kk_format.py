#!/usr/bin/env python3
"""
kk_format.py - Convert a built flow into a KowboyKit-ready, self-contained
package: cloaked .php pages with money.php, _kk-config.php + _checkout-offers.php,
offer-token wiring, and all paths re-rooted to /{kk-name}/...

Follows kk-master/{multi-step,single-step,customer-facing}.md and enforces the
seven KK laws:

  1. Every outgoing CTA resolves to a plan token whose canonical price matches
     the displayed price, or to registercreate / registercheckout.
  2. Every package has an index.php with content.
  3. A one-page flow is hosted in index.php (sales-only AND checkout-only).
  4. Multi-step: sales page = index.php, plan picker = checkout.php.
  5. Home nav item and logo links are dead (href="#") on every page.
  6. No outgoing <a> links to external sites (CDN assets are fine).
  7. Fully self-contained: no path may reference anything outside /{kk-name}/.

The build is HARD-GATED: pages are written to <flow>/kk.tmp, validated by
qa_checks.validate_kk, and only swapped into <flow>/kk when the QC status is
not "fail". On any law violation the temp build is deleted and the script
returns ok:false with the full violation list — no package, no download.

Usage:
  python3 kk_format.py --flow-dir <abs> [--kk-name <slug>] [--docroot <abs>]

Prints a JSON result on stdout.
"""

import argparse
import json
import os
import re
import shutil
import sys
import time
from pathlib import Path

# Reuse the static.js patcher (KK web base + .php studio links).
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import build_plans_and_omnifull as omni  # noqa: E402
import apply_flow_plans  # noqa: E402
import kk_tokens  # noqa: E402  (canonical token/price catalogue)
import qa_checks  # noqa: E402  (blocking QC gate)
import asset_pipeline  # noqa: E402  (optimize the kk asset copy too)

DEFAULT_DOCROOT = "/var/www/aipuunlimited.com/htdocs"

FLOW_TYPES = ("multi", "sales-only", "checkout-only", "single-page")

# A single-page flow is a multi-page site whose checkout is an in-page billing
# pop-up (billing.php) instead of a separate checkout.php. For internal-anchor
# and route rewriting it behaves exactly like "multi" (every page -> .php with
# $__step1link tracking so the click ID rides the whole journey), but the
# conversion target $__checkout resolves to billing.php, and the offer/plan
# wiring is applied to billing.php rather than checkout.php.
MULTI_PAGE_TYPES = ("multi", "single-page")
# The stem that hosts the offer/checkout wiring for each flow shape.
def _checkout_stem(flow_type):
    if flow_type == "single-page":
        return "billing"
    if flow_type == "checkout-only":
        return "index"
    return "checkout"

# Default token used for the per-page register CTA on non-cloaked flow pages
# (Create Studio, libraries, etc.). The user picks an override per-page in the
# previews UI, which is stored in flow.json under "cta_config".
DEFAULT_REGISTER_TOKEN = "registercheckout"


class KKBuildError(Exception):
    """Raised when the build violates a KK law. Carries the violation list."""

    def __init__(self, violations):
        self.violations = list(violations)
        super().__init__("; ".join(self.violations))


MONEY_HEADER = """<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
require_once $base_path.'/includes/money.php';
?>
<?php require_once(__DIR__.'/_kk-config.php'); ?>
"""

# Customer-facing pages (Create Studio, libraries, affiliate, legal, help) are
# NOT cloaked: they load safe.php, never money.php. See kk-master/customer-facing.md.
SAFE_HEADER = """<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
require_once $base_path.'/includes/safe.php';
?>
<?php require_once(__DIR__.'/_kk-config.php'); ?>
"""

CHECKOUT_OFFERS_INCLUDE = "<?php require_once(__DIR__.'/_checkout-offers.php'); ?>\n"

OFFERLINKS_PLACEHOLDER = "__KK_OFFER_LINKS_PHP__"


# --------------------------------------------------------------------------- #
#  Click-tracking continuity ($__step1link)                                    #
# --------------------------------------------------------------------------- #
# $__step1link is the query-string suffix (e.g. "?clickid=abc") appended to
# every internal nav link so the click ID rides along the whole journey.
#
# On CLOAKED pages (index.php / checkout.php) KowboyKit's money.php populates
# $multi_page['step1link'] and that value is authoritative.
#
# On CUSTOMER-FACING pages (gpt-library.php, prompt-library.php, etc.) the page
# loads safe.php, which does NOT set $multi_page. Without a fallback the suffix
# became '' the moment a visitor browsed a side page, so every onward link —
# including the one to checkout — dropped the journey params and tracking was
# lost for the rest of the session.
#
# KowboyKit's real step1link is the WHOLE journey query string, e.g.
#   ?o=47118&o=47115&lid=0&action=multi-page
# It carries repeated keys ('o' appears twice) and KK-specific params ('o',
# 'lid', 'action') that a fixed tracking-key whitelist can never capture, and
# that parse_str()/$_GET would silently collapse (only the last 'o' survives).
# So instead of filtering to known keys we persist the ENTIRE raw entry query
# string verbatim in a cookie and replay it byte-for-byte on side pages.
#
# Precedence: money.php's $multi_page['step1link'] (authoritative on cloaked
# pages) -> the live raw QUERY_STRING -> the remembered cookie. The first time
# we see a non-empty suffix we stamp it into the kk_s1 cookie (URL-encoded so it
# round-trips intact, repeated keys and all) so even a visitor who later lands
# on a side page WITHOUT params in the URL keeps the full journey.
_STEP1LINK_PHP = (
    "if (!function_exists('__kk_step1link')) {\n"
    "    function __kk_step1link() {\n"
    "        // 1) money.php is authoritative when present (cloaked pages).\n"
    "        global $multi_page;\n"
    "        if (isset($multi_page['step1link']) && $multi_page['step1link'] !== '') {\n"
    "            $s = $multi_page['step1link'];\n"
    "            if (!headers_sent()) { setcookie('kk_s1', rawurlencode($s), time()+86400, '/'); }\n"
    "            return $s;\n"
    "        }\n"
    "        // 2) Live request query string — the full journey, verbatim. This\n"
    "        //    preserves repeated keys (o=..&o=..) and KK params (lid/action)\n"
    "        //    that a whitelist or $_GET round-trip would drop.\n"
    "        $qs = (string)($_SERVER['QUERY_STRING'] ?? '');\n"
    "        if ($qs !== '') {\n"
    "            $s = '?' . $qs;\n"
    "            if (!headers_sent()) { setcookie('kk_s1', rawurlencode($s), time()+86400, '/'); }\n"
    "            return $s;\n"
    "        }\n"
    "        // 3) Fall back to the remembered cookie (side page hit with no params).\n"
    "        if (!empty($_COOKIE['kk_s1'])) {\n"
    "            $s = rawurldecode((string)$_COOKIE['kk_s1']);\n"
    "            if ($s !== '' && $s[0] === '?') { return $s; }\n"
    "        }\n"
    "        return '';\n"
    "    }\n"
    "}\n"
    "$__step1link = __kk_step1link();\n"
)


def kk_config_php(kk_name, register_url="/register", flow_type="multi"):
    """Generates the per-flow _kk-config.php.

    $__checkout depends on the flow shape:
      multi          -> /{kk}/checkout.php + tracking
      single-page    -> /{kk}/billing.php + tracking (the pop-up page IS checkout)
      checkout-only  -> /{kk}/index.php + tracking (the page IS the checkout)
      sales-only     -> registercheckout step1link (there is no local checkout)
    """
    reg = json.dumps(register_url)
    if flow_type == "checkout-only":
        checkout_expr = "$__lander . 'index.php' . $__step1link"
        is_checkout = "true"
    elif flow_type == "single-page":
        # The billing pop-up page is the checkout target (loaded in the <iframe>
        # and as the no-JS fallback for Pricing / Login / Register).
        checkout_expr = "$__lander . 'billing.php' . $__step1link"
        is_checkout = "false"
    elif flow_type == "sales-only":
        checkout_expr = "$offer['registercheckout']['link']['step1link'] ?? $__register"
        is_checkout = "false"
    else:
        checkout_expr = "$__lander . 'checkout.php' . $__step1link"
        is_checkout = "false"
    return (
        "<?php\n"
        "$__web = '/%(name)s';\n"
        "$__lander = $__web . '/';\n"
        "%(step1)s"
        "$__register = %(reg)s;\n"
        "$__checkout = %(checkout)s;\n"
        "$__registercheckout = $offer['registercheckout']['link']['step1link'] ?? $__register;\n"
        "$__registercreate = $offer['registercreate']['link']['step1link'] ?? $__register;\n"
        "$__is_checkout = %(is_checkout)s;\n"
    ) % {"name": kk_name, "reg": reg, "checkout": checkout_expr,
         "is_checkout": is_checkout, "step1": _STEP1LINK_PHP}


def checkout_offers_php(docroot):
    """_checkout-offers.php with the token array generated from the canonical
    catalogue (data/kk-tokens.json) so the vocabulary can never drift."""
    toks = kk_tokens.token_list(docroot)
    tok_lines = "\n".join("    %s," % json.dumps(t).replace('"', "'") for t in toks)
    return """<?php
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
%s
];
$__kk_offer_links = [];
foreach ($__kk_offer_tokens as $__t) {
    $__kk_offer_links[$__t] = __kk_offer_step1link($__t);
}
$__kk_lifetime_link = __kk_offer_step1link('lifetime');
unset($__t);
""" % tok_lines


def _register_php_expr(token, tokens):
    """PHP expression yielding the URL for the given KK token, used as the value
    of window.__KK_REGISTER_CHECKOUT in a page's flow-config."""
    if token == "registercheckout":
        return "$__registercheckout"
    if token == "registercreate":
        return "$__registercreate"
    if token in tokens:
        return ("((isset($__kk_offer_links[%s]) && $__kk_offer_links[%s] !== '#')"
                " ? $__kk_offer_links[%s] : $__registercheckout)"
                ) % (json.dumps(token), json.dumps(token), json.dumps(token))
    return "$__registercheckout"


def kk_flow_config(register_token, tokens):
    """Build the <script data-flow-config> block for one page. `register_token`
    controls which KK offer link the page's register-style CTAs point at."""
    return (
        "<script data-flow-config>\n"
        "window.__LANDER_BASE=<?= json_encode($__web, JSON_UNESCAPED_SLASHES); ?>;\n"
        "window.__KK_CHECKOUT_URL=<?= json_encode($__checkout, JSON_UNESCAPED_SLASHES); ?>;\n"
        "window.__KK_OFFER_LINKS=<?= json_encode((object)($__kk_offer_links ?? []), JSON_UNESCAPED_SLASHES); ?>;\n"
        "window.__KK_REGISTER_CHECKOUT=<?= json_encode(%s, JSON_UNESCAPED_SLASHES); ?>;\n"
        "window.__KK_REGISTER_CREATE=<?= json_encode($__registercreate, JSON_UNESCAPED_SLASHES); ?>;\n"
        "window.__KK_REGISTER_BILLING=<?= json_encode($__registercheckout, JSON_UNESCAPED_SLASHES); ?>;\n"
        "window.__KK_REGISTER_TOKEN=%s;\n"
        "window.__KK_STEP1LINK=<?= json_encode($__step1link, JSON_UNESCAPED_SLASHES); ?>;\n"
        "window.__KK_IS_CHECKOUT=<?= json_encode($__is_checkout ?? false); ?>;\n"
        "window.__OMNI_HOME_DEAD=1;\n"
        "</script>\n"
    ) % (_register_php_expr(register_token, tokens), json.dumps(register_token))


def slugify(value):
    value = (value or "").strip().lower()
    value = re.sub(r"[^a-z0-9._-]+", "-", value)
    value = re.sub(r"-{2,}", "-", value).strip("-_.")
    return value or "flow"


# --------------------------------------------------------------------------- #
#  Anchor helpers (string-based; the exported markup is never re-parsed)       #
# --------------------------------------------------------------------------- #
# Attrs may contain '>' inside quoted values (e.g. href="...<?= $__step1link; ?>"),
# so the attr group must consume quoted strings whole.
ANCHOR_RE = re.compile(r'<a\b((?:[^<>"]|"[^"]*")*)>(.*?)</a>', re.S | re.I)
HREF_RE = re.compile(r'\bhref\s*=\s*"([^"]*)"', re.I)


def _anchor_text(inner):
    txt = re.sub(r"<[^>]+>", " ", inner)
    return re.sub(r"\s+", " ", txt).strip()


def _set_href(attrs, new_href):
    if HREF_RE.search(attrs):
        return HREF_RE.sub('href="%s"' % new_href, attrs, count=1)
    return attrs + ' href="%s"' % new_href


def _is_logo_anchor(inner):
    if "<img" not in inner.lower():
        return False
    return bool(re.search(r'<img\b[^>]*\b(?:alt|src|srcset|class)\s*=\s*"[^"]*logo', inner, re.I))


def kill_home_and_logo(html):
    """Law 5: Home nav items and logo anchors are dead (href='#') on every page.
    The runtime HOME_DEAD JS stays as a backstop, but the markup must already
    be dead so the law holds with JS disabled."""

    def repl(m):
        attrs, inner = m.group(1), m.group(2)
        text = _anchor_text(inner).lower()
        if text == "home" or _is_logo_anchor(inner):
            href = HREF_RE.search(attrs)
            if href and href.group(1).strip() not in ("#", ""):
                return "<a%s>%s</a>" % (_set_href(attrs, "#"), inner)
        return m.group(0)

    return ANCHOR_RE.sub(repl, html)


def kill_external_anchors(html):
    """Law 6: no clickable <a> may leave the site. CDN <link>/<script>/<img>
    assets are untouched (they are not anchors)."""

    def repl(m):
        attrs, inner = m.group(1), m.group(2)
        href = HREF_RE.search(attrs)
        if not href:
            return m.group(0)
        h = href.group(1).strip().lower()
        if h.startswith(("http://", "https://", "//")):
            return "<a%s>%s</a>" % (_set_href(attrs, "#"), inner)
        return m.group(0)

    return ANCHOR_RE.sub(repl, html)


def fix_meta_images(html, kk_web):
    """Heal malformed social-image URLs the flow builder can emit
    (content="https://assets/img/...") so they resolve inside the package."""
    html = re.sub(r'content="https?://assets/', 'content="%s/assets/' % kk_web, html)
    html = re.sub(r'content="assets/', 'content="%s/assets/' % kk_web, html)
    return html


def rewrite_internal_anchors(html, flow_web, kk_name, flow_type="multi"):
    """Re-point flow-local .html anchors at their .php counterparts, preserving
    click tracking ($__step1link). checkout -> $__checkout; index -> home.

    Matches both the absolute ("{flow_web}/x.html") and the bare relative
    ("x.html" / "./x.html") forms.

    On sales-only / checkout-only flows only index/checkout get mapped; every
    other .html anchor is left for the CTA classifier (those pages do not exist
    in a one-page package, so they must resolve to a KK token instead).

    On multi / single-page flows every flow-local .html anchor is mapped to its
    .php sibling (+ $__step1link tracking) so the click ID rides across the whole
    multi-page journey. On single-page flows the billing pop-up page is the
    conversion target, so billing.html anchors resolve to $__checkout.
    """
    esc = re.escape(flow_web)
    pre = r'(?:%s/|\./)?' % esc

    html = re.sub(r'href="%scheckout\.html(?:\?[^"]*)?"' % pre,
                  'href="<?= $__checkout; ?>"', html)
    if flow_type == "single-page":
        html = re.sub(r'href="%sbilling\.html(?:\?[^"]*)?"' % pre,
                      'href="<?= $__checkout; ?>"', html)
    html = re.sub(r'href="%sindex\.html(?:\?[^"]*)?"' % pre,
                  'href="/%s/index.php<?= $__step1link; ?>"' % kk_name, html)

    if flow_type not in MULTI_PAGE_TYPES:
        return html

    def repl(m):
        return 'href="/%s/%s.php<?= $__step1link; ?>"' % (kk_name, m.group(1))

    html = re.sub(r'href="%s([a-z0-9-]+)\.html(?:\?[^"]*)?"' % pre, repl, html, flags=re.I)
    return html


# Route names that signal a conversion destination (billing/auth/pricing).
CONVERSION_ROUTES = {
    "billing", "pricing", "checkout", "signup", "sign-up", "login", "sign-in",
    "register", "account", "upgrade", "subscribe", "plans",
}


def localize_route_anchors(html, kk_name, page_stems, flow_type):
    """Law 6/7: root-relative route links that escape the package (e.g.
    /help-center/..., /billing, /create/knowledge-base) are re-pointed inside it:

      - any path segment matching a produced page stem -> that page (+tracking)
      - conversion-ish routes (billing/login/...)      -> $__checkout
      - everything else                                -> dead (#)
    """
    kk_prefix = "/%s/" % kk_name

    def repl(m):
        attrs, inner = m.group(1), m.group(2)
        hm = HREF_RE.search(attrs)
        if not hm:
            return m.group(0)
        href = hm.group(1).strip()
        if (not href.startswith("/") or href.startswith("//")
                or "<?" in href or href.startswith(kk_prefix)
                or href == "/" + kk_name):
            return m.group(0)
        path = href.split("?", 1)[0].split("#", 1)[0]
        segments = [s for s in path.strip("/").split("/") if s]
        if not segments:
            new_href = "#"
        else:
            stem_hit = next((s for s in reversed(segments) if s in page_stems), None)
            if stem_hit:
                new_href = "/%s/%s.php<?= $__step1link; ?>" % (kk_name, stem_hit)
            elif any(s in CONVERSION_ROUTES for s in segments):
                new_href = "<?= $__checkout; ?>"
            else:
                new_href = "#"
        return "<a%s>%s</a>" % (_set_href(attrs, new_href), inner)

    return ANCHOR_RE.sub(repl, html)


def rewrite_signup_offer_anchors(html, tokens):
    """Static /signup?offer=<token> anchors -> the token's KK offer link."""

    def repl(m):
        tok = m.group(1).lower()
        if tok in tokens:
            return 'href="<?= htmlspecialchars($__kk_offer_links[%s] ?? \'#\'); ?>"' % json.dumps(tok).replace('"', "'")
        return 'href="<?= htmlspecialchars($__registercheckout ?? \'#\'); ?>"'

    return re.sub(r'href="/?signup\?offer=([A-Za-z0-9_-]+)[^"]*"', repl, html)


# JS-level signup builders bypass the KK offer wiring (Law 1). A checkout's
# plan picker often builds the destination in <script> instead of only in anchor
# hrefs, e.g.  '/signup?offer=' + encodeURIComponent(token)  or  "/signup?offer="+t
# These run on load and overwrite the (correctly rewritten) anchor hrefs, so the
# package would still point at /signup?offer=... after deploy. Re-point them at
# the PHP-injected window.__KK_OFFER_LINKS map (set in the flow-config block) so
# script-driven CTAs resolve to KowboyKit offer links too.
_JS_SIGNUP_ENC_RE = re.compile(
    r"""['"]/signup\?offer=['"]\s*\+\s*encodeURIComponent\(\s*([^()]+?)\s*\)""")
_JS_SIGNUP_CONCAT_RE = re.compile(
    r"""['"]/signup\?offer=['"]\s*\+\s*([A-Za-z_$][\w$.]*)""")


def _kk_offer_lookup_expr(token_expr):
    """A JS expression resolving a plan token to its KK offer link, falling back
    to the registercheckout link (then '#') so a button is never dead."""
    return ("((window.__KK_OFFER_LINKS||{})[%s]||window.__KK_REGISTER_CHECKOUT||'#')"
            % token_expr.strip())


def rewrite_js_signup_builders(html):
    """Law 1: rewrite in-script '/signup?offer='+token URL builders to a lookup
    into window.__KK_OFFER_LINKS so JS-driven plan/CTA buttons use KK wiring."""
    html = _JS_SIGNUP_ENC_RE.sub(lambda m: _kk_offer_lookup_expr(m.group(1)), html)
    html = _JS_SIGNUP_CONCAT_RE.sub(lambda m: _kk_offer_lookup_expr(m.group(1)), html)
    return html


# --------------------------------------------------------------------------- #
#  CTA classification (single-page flows) — Law 1                              #
# --------------------------------------------------------------------------- #
PRICE_RE = re.compile(r"\$\s*([0-9][0-9,]*(?:\.[0-9]{1,2})?)")

# Phrasing lists from kk-master/multi-step.md (register button routing rule).
REGISTER_CREATE_TEXT = (
    "register to create", "start creating", "open create studio", "launch studio",
    "generate now", "try create studio", "create video", "create image",
    "start generating", "create studio",
)

MONTHLY_HINTS = ("/mo", "per month", "/month", "monthly", "a month", "every month")
YEARLY_HINTS = ("/yr", "per year", "/year", "yearly", "annual", "a year")
LIFETIME_HINTS = ("lifetime", "life time", "one-time", "one time", "for life", "forever")


def _cta_id(text, href, seen):
    base = slugify(text)[:40] or slugify(href)[:40] or "cta"
    n = seen.get(base, 0)
    seen[base] = n + 1
    return base if n == 0 else "%s-%d" % (base, n + 1)


def _detect_period(text):
    low = text.lower()
    if any(h in low for h in LIFETIME_HINTS):
        return "lifetime"
    if any(h in low for h in YEARLY_HINTS):
        return "yearly"
    if any(h in low for h in MONTHLY_HINTS):
        return "monthly"
    return None


def classify_cta(text, href, price_index, tokens):
    """Return (kind, value) where kind is one of:
       keep | dead | token | registercreate | registercheckout | error
    """
    h = (href or "").strip()
    low_h = h.lower()
    low_t = (text or "").lower()

    if "<?" in h:
        return "keep", None  # already dynamic PHP
    if h.startswith("#") or h == "":
        return "keep", None
    if low_h.startswith(("mailto:", "tel:", "javascript:")):
        return "keep", None

    # href hint: /signup?offer=<token> or ?plan=<...>
    m = re.search(r"[?&]offer=([a-z0-9_-]+)", low_h)
    if m and m.group(1) in tokens:
        return "token", m.group(1)

    # Price-bearing CTA -> the plan token with the matching canonical price.
    prices = [kk_tokens.normalize_price(p) for p in PRICE_RE.findall(text or "")]
    prices = [p for p in prices if p]
    if prices:
        period = _detect_period(text)
        candidates = set()
        for p in set(prices):
            if period:
                for tok in price_index.get((period, p), []):
                    candidates.add(tok)
            else:
                for (per, price), toks in price_index.items():
                    if price == p:
                        candidates.update(toks)
        # lifetime/lifetimeplan both match $399 — collapse to 'lifetime'.
        if candidates == {"lifetime", "lifetimeplan"}:
            candidates = {"lifetime"}
        if len(candidates) == 1:
            return "token", candidates.pop()
        if len(candidates) > 1:
            return "error", (
                "ambiguous price CTA %r — prices %s match tokens %s; "
                "assign a token override in the Flow CTA config"
                % (text.strip()[:80], prices, sorted(candidates)))
        return "error", (
            "CTA %r displays price(s) %s that match no KK token's canonical price; "
            "fix the price or assign a token override in the Flow CTA config"
            % (text.strip()[:80], prices))

    # Register-to-Create phrasing.
    if any(k in low_t for k in REGISTER_CREATE_TEXT):
        return "registercreate", None

    # Everything else that leaves the page registers into billing/checkout.
    return "registercheckout", None


def _php_href_for(kind, value):
    if kind == "token":
        return "<?= htmlspecialchars($__kk_offer_links[%s] ?? '#'); ?>" % json.dumps(value).replace('"', "'")
    if kind == "registercreate":
        return "<?= htmlspecialchars($__registercreate ?? '#'); ?>"
    if kind == "registercheckout":
        return "<?= htmlspecialchars($__registercheckout ?? '#'); ?>"
    if kind == "dead":
        return "#"
    return None


def rewrite_single_page_ctas(html, page_name, price_index, tokens, overrides,
                             errors, detected_out, docroot):
    """Classify and rewrite every anchor on a single-page flow (Law 1 + 3).

    `overrides` maps cta_id -> token | 'dead' | 'keep'. An override with a plan
    token must still satisfy the price law when the CTA shows a price.
    """
    seen = {}
    catalog = kk_tokens.load_catalog(docroot)

    def repl(m):
        attrs, inner = m.group(1), m.group(2)
        href_m = HREF_RE.search(attrs)
        href = href_m.group(1) if href_m else ""
        text = _anchor_text(inner)
        if "<?" in href:
            return m.group(0)

        # Home/logo are killed by kill_home_and_logo; classify everything else.
        if text.lower() == "home" or _is_logo_anchor(inner):
            return m.group(0)

        cid = _cta_id(text, href, seen)
        kind, value = classify_cta(text, href, price_index, tokens)

        ov = overrides.get(cid)
        if ov:
            if ov == "dead":
                kind, value = "dead", None
            elif ov == "keep":
                kind, value = "keep", None
            elif ov in tokens:
                meta = catalog.get(ov) or {}
                if meta.get("kind") == "plan":
                    prices = [kk_tokens.normalize_price(p) for p in PRICE_RE.findall(text)]
                    prices = [p for p in prices if p]
                    canon = kk_tokens.normalize_price(meta.get("price"))
                    if prices and canon not in prices:
                        errors.append(
                            "%s: CTA %r override token '%s' ($%s) does not match the "
                            "displayed price(s) %s" % (page_name, text[:60], ov, canon, prices))
                kind, value = ("token", ov) if meta.get("kind") == "plan" else (ov, None)
                if ov in ("registercreate", "registercheckout"):
                    kind, value = ov, None
        elif kind == "error":
            errors.append("%s: %s [cta id: %s]" % (page_name, value, cid))
            kind, value = "registercheckout", None  # placeholder; build fails anyway

        applied = value if kind == "token" else kind
        detected_out.append({
            "id": cid,
            "text": text[:120],
            "href": href[:200],
            "auto": value if kind == "token" and not ov else (kind if not ov else "override"),
            "applied": applied,
        })

        new_href = _php_href_for(kind, value)
        if new_href is None:  # keep
            return m.group(0)
        return "<a%s>%s</a>" % (_set_href(attrs, new_href), inner)

    return ANCHOR_RE.sub(repl, html)


# --------------------------------------------------------------------------- #
#  Checkout ORDER / LINKS wiring + price validation — Law 1                    #
# --------------------------------------------------------------------------- #
ORDER_RE = re.compile(r"var ORDER\s*=\s*(\{.*?\})\s*;")


def _wire_checkout_offers(html, warnings):
    """Replace the checkout's `var LINKS = {...}` with the KK offer links so the
    plan buttons resolve to KowboyKit offer tokens. Returns (html, wired)."""
    new, n = re.subn(r"var LINKS = \{[^}]*\}",
                     "var LINKS = " + OFFERLINKS_PLACEHOLDER, html, count=1)
    if not n:
        warnings.append("checkout: 'var LINKS = {...}' not found; plan buttons not re-wired")
        return html, False
    new = new.replace(
        OFFERLINKS_PLACEHOLDER,
        "<?php echo json_encode($__kk_offer_links, JSON_UNESCAPED_SLASHES); ?>")
    return new, True


def rewire_checkout_order(html, resolved_plans, bundle_ids, tokens, errors, warnings):
    """Rewrite `var ORDER = {...}` from plans_config offer_tokens so the token
    choices made in the Flows UI are actually applied. Position i of each
    period array corresponds to bundle plan id i. Returns (html, order_map)."""
    m = ORDER_RE.search(html)
    if not m:
        return html, None
    try:
        old_order = json.loads(m.group(1))
    except ValueError:
        errors.append("checkout: could not parse var ORDER block")
        return html, None

    by_id = {p.get("id"): p for p in (resolved_plans or []) if p.get("id")}
    new_order = {}
    for period, old_toks in old_order.items():
        out = []
        for i, pid in enumerate(bundle_ids):
            plan = by_id.get(pid)
            tok = None
            if plan:
                tok = (plan.get("offer_tokens") or {}).get(period)
            if not tok:
                tok = old_toks[i] if i < len(old_toks) else None
                if plan is not None and tok:
                    warnings.append(
                        "checkout: plan '%s' has no %s offer token in plans_config; "
                        "keeping '%s'" % (pid, period, tok))
            if not tok:
                errors.append("checkout: no %s offer token resolvable for plan '%s'"
                              % (period, pid))
                tok = ""
            elif tok not in tokens:
                errors.append("checkout: plan '%s' (%s) uses unknown token '%s'"
                              % (pid, period, tok))
            out.append(tok)
        new_order[period] = out

    html = html[:m.start(1)] + json.dumps(new_order) + html[m.end(1):]
    return html, new_order


def validate_order_prices(bundle_text, bundle_ids, order_map, docroot, errors):
    """Law 1 hard gate: each plan card's displayed price must equal the
    canonical price of the token its button fires."""
    if not order_map or not bundle_text:
        return
    catalog = kk_tokens.load_catalog(docroot)
    field_for = {"monthly": "mPrice", "yearly": "yPrice", "lifetime": "lPrice"}
    for period, toks in order_map.items():
        field = field_for.get(period)
        if not field:
            continue
        for i, tok in enumerate(toks):
            if not tok or tok not in catalog:
                continue  # already errored by rewire_checkout_order
            meta = catalog[tok]
            pid = bundle_ids[i] if i < len(bundle_ids) else None
            if meta.get("kind") != "plan":
                errors.append(
                    "checkout: plan column '%s' (%s) is wired to register token '%s' — "
                    "plan buttons must use their own plan offer token"
                    % (pid or i, period, tok))
                continue
            if meta.get("period") != period:
                errors.append(
                    "checkout: plan '%s' %s button fires token '%s' which is a %s token"
                    % (pid or i, period, tok, meta.get("period")))
            if not pid:
                continue
            try:
                start, end = apply_flow_plans._plan_block_span(bundle_text, pid)
            except ValueError:
                continue
            block = bundle_text[start:end]
            pm = re.search(r'%s:"([^"]*)"' % field, block)
            if not pm:
                continue
            displayed = kk_tokens.normalize_price(pm.group(1))
            canonical = kk_tokens.normalize_price(meta.get("price"))
            if displayed and canonical and displayed != canonical:
                errors.append(
                    "PRICE MISMATCH on checkout: plan '%s' displays $%s/%s but its button "
                    "fires token '%s' (canonical $%s). Fix the plan price or assign the "
                    "matching token in Flow Plans." % (pid, displayed, period, tok, canonical))


# --------------------------------------------------------------------------- #
#  Self-containment (Law 7): re-root copied css/js/json text assets            #
# --------------------------------------------------------------------------- #
STALE_ROOTS = (
    "/omnirogue-checkouts", "/aipu-checkouts", "/checkouts-omni",
    "/omnirogue-landers", "/aipu-landers", "/omnirogue-pages", "/aipu-pages",
    "/omnirogue", "/multistep-kk", "/multistep", "/kk-june8",
)


ASSET_EXT_RE = re.compile(
    r"\.(?:png|jpe?g|gif|webp|avif|svg|ico|css|js|mjs|json|woff2?|ttf|otf|eot|mp4|webm|mp3|wav|ogg)$",
    re.I)


def _basename_exists_in_pkg(pkg_root, basename):
    """True if a file with this basename exists anywhere under the package."""
    target = basename.lower()
    for _root, _dirs, files in os.walk(pkg_root):
        for f in files:
            if f.lower() == target:
                return True
    return False


def _pkg_asset_web_for_basename(pkg_root, kk_web, basename):
    """Find a file by basename inside the package and return its /{kk_name}/...
    web path, or None if not found."""
    target = basename.lower()
    for root, _dirs, files in os.walk(pkg_root):
        for f in files:
            if f.lower() == target:
                rel = os.path.relpath(os.path.join(root, f), pkg_root).replace(os.sep, "/")
                return kk_web + "/" + rel
    return None


def _import_shared_asset(pkg_root, kk_web, docroot, ref):
    """A reference points at a shared collection root (e.g.
    /checkouts-omni/_chrome/chrome.css). Copy that file FROM the docroot INTO
    the package (preserving its sub-path) and return the new /{kk_name}/... web
    path, or None if the source doesn't exist. Keeps the package self-contained
    while still serving the right asset after deploy."""
    if not docroot:
        return None
    clean = ref.split("#", 1)[0].split("?", 1)[0]
    if not clean.startswith("/"):
        return None
    src = os.path.join(docroot, clean.lstrip("/"))
    if not os.path.isfile(src):
        return None
    # Mirror the path inside the package under an _imported/ namespace to avoid
    # collisions with the flow's own assets.
    rel_inside = "_imported" + clean  # clean already starts with "/"
    dest = os.path.join(pkg_root, rel_inside.lstrip("/"))
    try:
        os.makedirs(os.path.dirname(dest), exist_ok=True)
        if not os.path.isfile(dest):
            shutil.copy2(src, dest)
    except OSError:
        return None
    return kk_web + "/" + rel_inside.lstrip("/")


def heal_html_asset_refs(html, pkg_root, kk_name, kk_web, warnings, docroot=None):
    """Law 7 self-containment for HTML pages (the formatter only re-rooted CSS/
    JS/JSON before). Applied to href/src/srcset/content attributes:

      1. Absolute asset refs pointing at a sibling collection root
         (e.g. /checkouts-omni/logo-omnirogue.png) are re-pointed to the same
         asset inside THIS package when a file of that basename exists here.
      2. If not present here but the file exists in the docroot (e.g. shared
         /checkouts-omni/_chrome/chrome.css), it is COPIED into the package
         under _imported/ and the ref re-rooted there.
      3. Truly dead asset refs that resolve nowhere (e.g. a stale
         bundle.scoped.css whose folder was never built) are dropped:
           - <link ...> tags are removed entirely
           - other refs left as-is only if not matchable.

    This keeps the package fully self-contained without touching wiring,
    tracking, or offer tokens."""
    attr_re = re.compile(r'\b(href|src|content)\s*=\s*"([^"]+)"', re.I)
    kk_prefix = "/%s/" % kk_name

    def is_local_asset(url):
        clean = url.split("#", 1)[0].split("?", 1)[0]
        if not clean.startswith("/"):
            return False, clean
        if "://" in url or url.startswith("//"):
            return False, clean
        return bool(ASSET_EXT_RE.search(clean)), clean

    def resolve_stale_ref(url):
        """Return a new web path for a stale-root asset url, or None."""
        clean = url.split("#", 1)[0].split("?", 1)[0]
        if not ASSET_EXT_RE.search(clean):
            return None
        # 1) same basename already inside the package
        web = _pkg_asset_web_for_basename(pkg_root, kk_web, os.path.basename(clean))
        if web:
            return web
        # 2) import the shared asset from the docroot into the package
        return _import_shared_asset(pkg_root, kk_web, docroot, url)

    # ---- pass 1: re-root sibling-collection asset roots to this package ----
    for stale in STALE_ROOTS:
        if (stale + "/") not in html:
            continue

        def repl_attr(m, _stale=stale):
            attr, url = m.group(1), m.group(2)
            clean = url.split("#", 1)[0].split("?", 1)[0]
            if not clean.startswith(_stale + "/") or not ASSET_EXT_RE.search(clean):
                return m.group(0)
            web = resolve_stale_ref(url)
            if web:
                # Preserve any ?v= cache-buster query so versioning still works.
                q = url[len(clean):]
                return '%s="%s%s"' % (attr, web, q)
            return m.group(0)

        html = attr_re.sub(repl_attr, html)

    # srcset re-rooting for stale roots (dead-drop handled in pass 2)
    def repl_srcset(m):
        candidates = []
        for part in m.group(1).split(","):
            part = part.strip()
            if not part:
                continue
            bits = part.split()
            u = bits[0]
            for stale in STALE_ROOTS:
                if u.startswith(stale + "/") and ASSET_EXT_RE.search(u.split("?")[0]):
                    web = resolve_stale_ref(u)
                    if web:
                        bits[0] = web
                    break
            candidates.append(" ".join(bits))
        return 'srcset="%s"' % ", ".join(candidates)

    html = re.sub(r'srcset\s*=\s*"([^"]+)"', repl_srcset, html, flags=re.I)

    # ---- pass 2: drop dead asset refs that resolve nowhere in the package ----
    def drop_dead_links(text):
        def repl_link(m):
            tag = m.group(0)
            href_m = re.search(r'href\s*=\s*"([^"]+)"', tag, re.I)
            if not href_m:
                return tag
            ok, clean = is_local_asset(href_m.group(1))
            if not ok:
                return tag
            if clean.startswith(kk_prefix) and not _basename_exists_in_pkg(pkg_root, os.path.basename(clean)):
                warnings.append("dropped dead <link> (missing in package): %s" % clean)
                return ""  # remove the whole tag
            return tag
        return re.sub(r'<link\b[^>]*>', repl_link, text, flags=re.I)

    html = drop_dead_links(html)
    return html


def reroot_text_assets(kk_dir, flow_web, kk_web):
    """Rewrite /flows/<slug>/... (and known shared-collection roots) inside the
    package's css/js/json files to /{kk_name}/... so the package never reaches
    outside its own folder after deploy."""
    for root, _dirs, files in os.walk(kk_dir):
        for fn in files:
            if not fn.endswith((".css", ".js", ".json")):
                continue
            fp = os.path.join(root, fn)
            try:
                text = open(fp, encoding="utf-8", errors="replace").read()
            except OSError:
                continue
            new = text.replace(flow_web + "/", kk_web + "/")
            # Also catch slash-less refs (e.g. minified __LANDER_BASE='/flows/<slug>'
            # that patch_static_js' spaced regex can't rewrite). flow_web is the
            # full path so this can't truncate a longer sibling-flow name.
            new = new.replace(flow_web, kk_web)
            for stale in STALE_ROOTS:
                new = new.replace('"%s/' % stale, '"%s/' % kk_web)
                new = new.replace("'%s/" % stale, "'%s/" % kk_web)
                new = new.replace("url(%s/" % stale, "url(%s/" % kk_web)
            if new != text:
                open(fp, "w", encoding="utf-8").write(new)


# --------------------------------------------------------------------------- #
#  Build                                                                       #
# --------------------------------------------------------------------------- #
def detect_flow_type(manifest, flow_dir):
    ft = (manifest.get("flow_type") or "").strip().lower()
    if ft in FLOW_TYPES:
        return ft
    return "multi" if os.path.isfile(os.path.join(flow_dir, "checkout.html")) else "sales-only"


def build(flow_dir, kk_name, docroot):
    flow_dir = os.path.realpath(flow_dir)
    if not os.path.isdir(flow_dir):
        raise FileNotFoundError("Flow dir not found: %s" % flow_dir)

    index_html = os.path.join(flow_dir, "index.html")
    checkout_html = os.path.join(flow_dir, "checkout.html")
    if not os.path.isfile(index_html):
        raise KKBuildError(["Law 2: flow has no index.html — every flow must have an index page with content"])

    manifest_path = os.path.join(flow_dir, "flow.json")
    manifest = {}
    if os.path.isfile(manifest_path):
        with open(manifest_path, "r", encoding="utf-8") as fh:
            manifest = json.load(fh) or {}

    flow_type = detect_flow_type(manifest, flow_dir)
    billing_html = os.path.join(flow_dir, "billing.html")
    if flow_type == "multi" and not os.path.isfile(checkout_html):
        raise KKBuildError(["Law 4: multi-step flow has no checkout.html — rebuild the flow first"])
    if flow_type == "single-page":
        # The billing pop-up page IS the checkout; a separate checkout.html must
        # never exist (and billing.html must, since it hosts the offer wiring).
        if os.path.isfile(checkout_html):
            raise KKBuildError(["Law 3: single-page flow must not have a separate checkout.html — "
                                "its checkout is the in-page billing pop-up (billing.html)"])
        if not os.path.isfile(billing_html):
            raise KKBuildError(["single-page flow has no billing.html (the pop-up checkout) — "
                                "rebuild the flow first"])
    elif flow_type != "multi" and os.path.isfile(checkout_html):
        raise KKBuildError(["Law 3: flow_type is '%s' but checkout.html exists — a one-page "
                            "flow must be hosted entirely in index.php" % flow_type])

    tokens = set(kk_tokens.token_list(docroot))
    price_index = kk_tokens.plan_price_index(docroot)

    flow_slug = os.path.basename(flow_dir)
    kk_name = slugify(kk_name or manifest.get("kk_name") or flow_slug)
    kk_web = "/" + kk_name

    try:
        flow_web = "/" + os.path.relpath(flow_dir, docroot).replace(os.sep, "/").strip("/")
    except Exception:
        flow_web = "/flows/" + flow_slug

    register_url = manifest.get("register_url", "/register")
    warnings = []
    errors = []

    # Per-flow CTA config: { cta_config: { pages: { "<file.php>": { register_token } } } }
    cta_config = manifest.get("cta_config") or {}
    page_overrides = (cta_config.get("pages") or {}) if isinstance(cta_config, dict) else {}
    default_register_token = (
        cta_config.get("default_register_token")
        if isinstance(cta_config, dict) else None
    ) or DEFAULT_REGISTER_TOKEN

    # Per-CTA overrides for single-page flows: { cta_map: { overrides: { id: token|dead|keep } } }
    cta_map_cfg = manifest.get("cta_map") or {}
    cta_overrides = {}
    if isinstance(cta_map_cfg, dict) and isinstance(cta_map_cfg.get("overrides"), dict):
        for k, v in cta_map_cfg["overrides"].items():
            v = str(v)
            if v in tokens or v in ("dead", "keep"):
                cta_overrides[str(k)] = v
            else:
                warnings.append("cta_map: ignoring unknown override '%s' for '%s'" % (v, k))

    plans_config = manifest.get("plans_config") or {}
    index_register_token = (
        plans_config.get("index_register_token")
        if isinstance(plans_config, dict) else None
    ) or DEFAULT_REGISTER_TOKEN
    if index_register_token not in tokens:
        warnings.append("unknown index_register_token '%s'; using %s"
                        % (index_register_token, DEFAULT_REGISTER_TOKEN))
        index_register_token = DEFAULT_REGISTER_TOKEN

    # Patch checkout bundle.js prices/points from plans_config + plan library.
    resolved_plans = []
    bundle_ids = []
    bundle_text = None
    try:
        plan_apply = apply_flow_plans.apply_flow_plans(flow_dir, docroot, manifest)
        warnings.extend(plan_apply.get("warnings") or [])
    except Exception as exc:
        warnings.append("apply_flow_plans: %s" % exc)
    bundles = apply_flow_plans.find_bundle_files(flow_dir)
    if bundles:
        bundle_text = open(bundles[0], encoding="utf-8", errors="replace").read()
        bundle_ids = apply_flow_plans.detect_plan_ids(bundle_text)
        library = apply_flow_plans.load_plan_library(docroot)
        resolved = apply_flow_plans.resolve_plans_config(manifest, library, bundle_text)
        resolved_plans = resolved.get("plans") or []

    kk_dir = os.path.join(flow_dir, "kk")
    tmp_dir = os.path.join(flow_dir, "kk.tmp")
    if os.path.isdir(tmp_dir):
        shutil.rmtree(tmp_dir)

    # The interactive flow is already self-contained. Copy the whole tree, then
    # re-root paths and convert each .html page to .php with string ops only
    # (never re-serialise the exported markup through a parser).
    shutil.copytree(
        flow_dir, tmp_dir,
        ignore=shutil.ignore_patterns(
            "*.html", "flow.json", "kk", "kk.tmp", "_*.py", "__pycache__", "*.md", ".*"),
    )

    single_page = flow_type in ("sales-only", "checkout-only")
    offer_wired = False
    order_map = None
    produced_pages = []
    applied_overrides = {}
    detected_ctas = {}
    page_stems = {fn[:-5] for fn in os.listdir(flow_dir) if fn.endswith(".html")}

    for fn in sorted(os.listdir(flow_dir)):
        if not fn.endswith(".html"):
            continue
        stem = fn[:-5]
        html = open(os.path.join(flow_dir, fn), encoding="utf-8", errors="replace").read()
        # flow-local refs (assets, links) -> the KK web base /{kk_name}/...
        html = html.replace(flow_web + "/", kk_web + "/")
        html = html.replace(flow_web, kk_web)  # slash-less refs too (minified JS)
        # internal page anchors: .html -> .php (+ tracking); checkout -> $__checkout
        html = rewrite_internal_anchors(html, kk_web, kk_name, flow_type)
        # JS config URLs (e.g. __KK_CHECKOUT_URL / window.__CHECKOUT_URL) that
        # still point at checkout.html / billing.html must target the PHP page so
        # they don't 404. Covers both absolute (already re-rooted to /{kk_name}/)
        # and bare relative forms inside <script> blocks. On single-page flows the
        # billing pop-up page is the checkout target.
        if flow_type in MULTI_PAGE_TYPES:
            checkout_php_url = "/%s/%s.php" % (kk_name, _checkout_stem(flow_type))
            html = html.replace('"%s/checkout.html"' % kk_web, '"%s"' % checkout_php_url)
            html = html.replace("'%s/checkout.html'" % kk_web, "'%s'" % checkout_php_url)
            if flow_type == "single-page":
                html = html.replace('"%s/billing.html"' % kk_web, '"%s"' % checkout_php_url)
                html = html.replace("'%s/billing.html'" % kk_web, "'%s'" % checkout_php_url)
            html = re.sub(r'(__[A-Z_]*CHECKOUT[A-Z_]*\s*=\s*["\'])checkout\.html(["\'])',
                          lambda m: m.group(1) + checkout_php_url + m.group(2), html)
        # Meta tags (og:url, og:image, twitter:*) sometimes carry a bare page
        # ref like content="checkout.html" / "index.html". These are SEO/social
        # hints, not package assets — point them at the absolute page URL so the
        # self-containment link check doesn't treat them as broken local files.
        index_php_url = "/%s/index.php" % kk_name
        checkout_meta_url = (("/%s/%s.php" % (kk_name, _checkout_stem(flow_type)))
                             if flow_type in MULTI_PAGE_TYPES else index_php_url)
        html = re.sub(
            r'(<meta\b[^>]*\bcontent=")checkout\.html("[^>]*>)',
            lambda m: m.group(1) + checkout_meta_url + m.group(2),
            html, flags=re.I)
        html = re.sub(
            r'(<meta\b[^>]*\bcontent=")index\.html("[^>]*>)',
            lambda m: m.group(1) + index_php_url + m.group(2),
            html, flags=re.I)
        # static /signup?offer=... anchors -> KK offer links (all page types)
        html = rewrite_signup_offer_anchors(html, tokens)
        # in-script '/signup?offer='+token builders -> window.__KK_OFFER_LINKS
        # lookup (Law 1: no hardcoded signup in JS; plan-picker toggles, etc.)
        html = rewrite_js_signup_builders(html)
        # root-relative route links that escape the package -> local page /
        # checkout / dead (multi only; single-page CTAs go through the classifier)
        if not single_page:
            html = localize_route_anchors(html, kk_name, page_stems, flow_type)

        out_name = stem + ".php"
        checkout_stem = _checkout_stem(flow_type)
        if stem == "index":
            page_token = index_register_token
        elif stem == "checkout" or (flow_type == "single-page" and stem == checkout_stem):
            page_token = "registercheckout"
        else:
            override = page_overrides.get(out_name) or page_overrides.get(stem) or {}
            page_token = (override.get("register_token") if isinstance(override, dict) else None) or default_register_token
            if page_token not in tokens:
                warnings.append("unknown register_token '%s' for %s; falling back to %s"
                                % (page_token, out_name, DEFAULT_REGISTER_TOKEN))
                page_token = DEFAULT_REGISTER_TOKEN
            applied_overrides[out_name] = page_token

        # swap the demo flow-config for the PHP/KK version
        flow_cfg_block = kk_flow_config(page_token, tokens)
        html = re.sub(r'<script data-flow-config[^>]*>.*?</script>',
                      lambda _m, _cfg=flow_cfg_block: _cfg, html, count=1, flags=re.S)

        is_checkout_page = (
            (stem == "checkout")
            or (flow_type == "checkout-only" and stem == "index")
            or (flow_type == "single-page" and stem == "billing"))

        if is_checkout_page:
            html, wired = _wire_checkout_offers(html, warnings)
            offer_wired = offer_wired or wired
            html, order_map = rewire_checkout_order(
                html, resolved_plans, bundle_ids, tokens, errors, warnings)

        # Single-page flows: classify + rewire every remaining CTA (Law 1/3).
        if single_page and stem == "index":
            detected = []
            html = rewrite_single_page_ctas(
                html, "index.php", price_index, tokens, cta_overrides,
                errors, detected, docroot)
            detected_ctas["index.php"] = detected

        # Laws 5/6/7: static kills + social-image healing on every page.
        html = kill_home_and_logo(html)
        html = kill_external_anchors(html)
        html = fix_meta_images(html, kk_web)
        # Law 7: re-root sibling-collection asset refs into this package,
        # importing shared assets (e.g. _chrome/*) and dropping dead links so
        # the package stays self-contained (no 404s after deploy).
        html = heal_html_asset_refs(html, tmp_dir, kk_name, kk_web, warnings, docroot)

        # Cloaked (money.php) pages: index + the checkout-bearing page. On a
        # single-page flow the billing pop-up page IS the checkout, so it needs
        # money.php too ($offer / $multi_page['step1link'] for offer links).
        if stem in ("index", "checkout") or is_checkout_page:
            php = MONEY_HEADER + CHECKOUT_OFFERS_INCLUDE + html
        else:
            php = SAFE_HEADER + CHECKOUT_OFFERS_INCLUDE + html

        with open(os.path.join(tmp_dir, out_name), "w", encoding="utf-8") as fh:
            fh.write(php)
        produced_pages.append(out_name)

    # Law 1 price gate: displayed plan prices must match the wired tokens.
    validate_order_prices(bundle_text, bundle_ids, order_map, docroot, errors)

    def persist_cta_map():
        """Write the detected CTA map to flow.json even when the build is
        blocked, so the Flows UI can offer per-CTA overrides to fix it."""
        try:
            manifest["cta_map"] = {
                "overrides": cta_overrides,
                "detected": detected_ctas,
                "single_page": single_page,
            }
            manifest["flow_type"] = flow_type
            with open(manifest_path, "w", encoding="utf-8") as fh:
                json.dump(manifest, fh, indent=2)
        except Exception:
            pass

    # ---------- config + offers ----------
    with open(os.path.join(tmp_dir, "_kk-config.php"), "w", encoding="utf-8") as fh:
        fh.write(kk_config_php(kk_name, register_url, flow_type))
    with open(os.path.join(tmp_dir, "_checkout-offers.php"), "w", encoding="utf-8") as fh:
        fh.write(checkout_offers_php(docroot))

    # ---------- Law 7: re-root css/js/json text assets to /{kk_name}/ ----------
    reroot_text_assets(tmp_dir, flow_web, kk_web)

    # ---------- point the consolidated static.js at the KK base + .php pages ----
    social_proof = str(((manifest.get("widgets_config") or {})
                        .get("social_proof") or {}).get("placement") or "on")
    try:
        omni.patch_static_js(Path(tmp_dir), kk_web, php_ext=True,
                             social_proof=social_proof)
    except Exception as exc:
        warnings.append("patch_static_js: %s" % exc)

    # Belt & braces: a KK package must never link .html pages from its JS —
    # KowboyKit hosts .php only and 404s bounce to an unrelated sales page.
    kk_static = os.path.join(tmp_dir, "assets", "static.js")
    if os.path.isfile(kk_static):
        _sjs = open(kk_static, encoding="utf-8", errors="replace").read()
        _leftover = sorted(set(re.findall(r"[A-Za-z0-9/_.-]*?([a-z0-9-]+\.html)['\"]", _sjs)))
        if _leftover:
            warnings.append("static.js still references .html pages (would 404 on KK): "
                            + ", ".join(_leftover[:8]))

    # ---------- optimize the KK asset copy (idempotent; gated like the flow) ----
    # The flow assets are already optimized at flow-build time; this catches any
    # additions and ensures a standalone KK rebuild ships optimized assets too.
    kk_assets_cfg = manifest.get("assets_config") if isinstance(manifest.get("assets_config"), dict) else {}
    kk_optimize_result = None
    if kk_assets_cfg.get("optimize", True):
        try:
            kk_optimize_result = asset_pipeline.optimize_dir(tmp_dir)
            warnings.extend(kk_optimize_result.get("warnings", []))
        except Exception as exc:
            warnings.append("asset optimize (kk): %s" % exc)

    # Build artifacts (asset manifests) are not served assets and may record
    # original source paths (e.g. /checkouts-omni/...). Drop them so the package
    # is clean and never trips the Law-7 self-containment check.
    for _root, _dirs, _files in os.walk(tmp_dir):
        for _f in list(_files):
            if _f in (".asset-manifest.json", "asset-manifest.json"):
                try:
                    os.remove(os.path.join(_root, _f))
                except OSError:
                    pass

    if errors:
        shutil.rmtree(tmp_dir, ignore_errors=True)
        persist_cta_map()
        raise KKBuildError(errors)

    # ---------- blocking QC gate (Laws 1-7) ----------
    qc = qa_checks.validate_kk(tmp_dir, docroot, flow_type=flow_type)
    if qc.get("status") == "fail":
        shutil.rmtree(tmp_dir, ignore_errors=True)
        persist_cta_map()
        raise KKBuildError(qc.get("errors") or ["QC gate failed"])

    # ---------- atomic-ish swap: kk.tmp -> kk ----------
    if os.path.isdir(kk_dir):
        shutil.rmtree(kk_dir)
    os.rename(tmp_dir, kk_dir)

    # ---------- update manifest ----------
    new_cta_config = {
        "default_register_token": default_register_token,
        "pages": page_overrides if isinstance(page_overrides, dict) else {},
        "applied": applied_overrides,
        "configurable_pages": [
            p for p in produced_pages if p not in ("index.php", "checkout.php")
        ],
        "available_tokens": sorted(tokens),
    }
    new_cta_map = {
        "overrides": cta_overrides,
        "detected": detected_ctas,
        "single_page": single_page,
    }
    manifest.update({
        "kk": True,
        "kk_name": kk_name,
        "kk_built": int(time.time()),
        "kk_offer_wired": offer_wired,
        "kk_pages": len(produced_pages),
        "flow_type": flow_type,
        "cta_config": new_cta_config,
        "cta_map": new_cta_map,
        "plans_config": plans_config if isinstance(plans_config, dict) else {},
        "kk_qc_status": qc.get("status"),
    })
    with open(manifest_path, "w", encoding="utf-8") as fh:
        json.dump(manifest, fh, indent=2)

    produced = []
    for base, _dirs, files in os.walk(kk_dir):
        for f in files:
            produced.append(os.path.relpath(os.path.join(base, f), kk_dir))

    return {
        "ok": True,
        "flow": flow_slug,
        "kk_name": kk_name,
        "kk_dir": kk_dir,
        "flow_type": flow_type,
        "offer_wired": offer_wired,
        "pages": len(produced_pages),
        "file_count": len(produced),
        "cta_config": new_cta_config,
        "cta_map": new_cta_map,
        "warnings": warnings,
        "qc": qc,
        "assets": kk_optimize_result,
    }


def main():
    ap = argparse.ArgumentParser(description="KK-format a built flow (hard-gated).")
    ap.add_argument("--flow-dir", required=True)
    ap.add_argument("--kk-name", default=None)
    ap.add_argument("--docroot", default=DEFAULT_DOCROOT)
    args = ap.parse_args()
    try:
        print(json.dumps(build(args.flow_dir, args.kk_name, args.docroot)))
        return 0
    except KKBuildError as exc:
        print(json.dumps({
            "ok": False,
            "error": "KK build blocked — %d law violation(s). No package was produced."
                     % len(exc.violations),
            "violations": exc.violations,
        }))
        return 1
    except Exception as exc:
        print(json.dumps({"ok": False, "error": str(exc)}))
        return 1


if __name__ == "__main__":
    sys.exit(main())
