#!/usr/bin/env python3
"""
build_flow.py - Assemble a full, browsable OmniRogue/AIPU demo *site* ("flow")
from a sales page (lander) + a checkout, plus the brand's customer-facing pages
(Create Studio, GPT/Prompt/Knowledge libraries, Become Affiliate, legal, help).

The output is a self-contained clickable demo under flows/<slug>/:

    index.html        presell (lander), site nav + footer injected
    checkout.html     plan picker, same site nav + footer
    createvideo.html  create-image.html ... (Create Studio pages)
    gpt-library.html prompt-library.html knowledge-base.html
    affiliate.html (omni only)  about.html help-center.html  + legal pages
    assets/...        consolidated CSS/JS/img/fonts

Link pattern wired across every page (matches kk-master + the brand static.js):

  Home            -> inert (does not navigate; "refreshes" in place)
  Create Studio   -> real studio page; the Generate gate pops up -> /register
  GPT / Prompt / Knowledge -> real pages; using them pops up -> /register
  Pricing         -> checkout.html
  Create Account  -> checkout.html
  Login           -> checkout.html
  Become Affiliate-> affiliate.html (omni)
  Generate / library / KB gates -> REGISTER_URL ("/register" on the root domain)

KK formatting (scripts/kk_format.py) later converts this whole site to .php.

Usage:
  python3 build_flow.py --lander-dir <abs> --checkout-dir <abs> --name <slug> \
      [--brand aipu|omni] [--flows-dir <abs>] [--docroot <abs>] [--register-url /register]

Prints a JSON result on stdout.
"""

import argparse
import json
import os
import posixpath
import re
import shutil
import sys
import time
from pathlib import Path

try:
    from bs4 import BeautifulSoup
except Exception as exc:  # pragma: no cover
    print(json.dumps({"ok": False, "error": "BeautifulSoup (bs4) is not installed: %s" % exc}))
    sys.exit(1)

# Reuse the battle-tested site-chrome / static.js / scoped-CSS helpers that the
# multistep "omnifull" builder uses, so flow pages render identically.
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import build_plans_and_omnifull as omni  # noqa: E402
import brandlib  # noqa: E402  (rebrand body tokens omni<->aipu at build time)
import qa_checks  # noqa: E402  (automatic QC gate, merged into the build result)
import verify_nav_parity  # noqa: E402  (desktop/mobile header parity gate)
import asset_pipeline  # noqa: E402  (image/CSS/JS optimization on every build)
try:
    import cdn_config  # noqa: E402  (BunnyCDN credentials from .env)
except Exception:  # pragma: no cover
    cdn_config = None

DEFAULT_DOCROOT = "/var/www/aipuunlimited.com/htdocs"


def publish_flow_to_cdn(out_dir, slug, flow_web, manifest, docroot, warnings):
    """Publish the flow's static assets to BunnyCDN and rewrite the flow's
    HTML/CSS/JS to load them from the CDN pull-zone.

    Gated three ways (all must allow it):
      * global  : .env CDN_ENABLED=1 + valid Bunny credentials (cdn_settings)
      * per-flow: flow.json assets_config.cdn is not explicitly False
    The kk/ subtree is skipped — kk_format publishes/QCs that copy separately so
    a KK package stays self-contained. Never raises; failures become warnings.

    Returns the publish result dict, or None when CDN is off / not configured.
    """
    if cdn_config is None:
        return None
    assets_config = manifest.get("assets_config") if isinstance(manifest.get("assets_config"), dict) else {}
    # Per-flow opt-out: assets_config.cdn === False disables publishing for this flow.
    if assets_config.get("cdn") is False:
        return None
    try:
        settings = cdn_config.cdn_settings(docroot)
    except Exception as exc:
        warnings.append("cdn_config: %s" % exc)
        return None
    if not settings.get("enabled"):
        return None

    try:
        bh = asset_pipeline._build_hash(out_dir, {"kk"})
        asset_base = asset_pipeline.cdn_asset_base(settings, slug, bh)
        # Rewrite first so the uploaded CSS/JS already point at the CDN, then
        # upload — including the rewritten text files — under the same hash.
        rewrite = asset_pipeline.rewrite_asset_urls(
            out_dir, asset_base, [flow_web + "/"], skip_dirs={"kk"})
        warnings.extend(rewrite.get("warnings", []))
        pub = asset_pipeline.publish_bunny(
            out_dir, slug, settings, skip_dirs={"kk"}, build_hash=bh)
        warnings.extend(pub.get("warnings", []))
        pub["rewrite"] = rewrite
        pub["asset_base"] = asset_base
        return pub
    except Exception as exc:
        warnings.append("cdn publish: %s" % exc)
        return None

# Where a gate (Generate / library / knowledge-base) sends the visitor.
# Root-relative so it resolves to /register on whatever domain serves the page.
DEFAULT_REGISTER_URL = "/register"

# Brand customer-facing page packs (web path under docroot).
BRAND_PAGES = {"aipu": "/aipu-pages", "omni": "/omnirogue-pages"}

# Marketing/auth domains per brand -> rewritten to the local checkout.
BRAND_DOMAINS = {
    "omni": ["omnirogue.com"],
    "aipu": ["aiprofessionalsuniversity.com", "aipu.com"],
}

# Customer-facing pages that exist locally in the pack (info/legal links resolve here).
LOCAL_INFO_SLUGS = (
    "acceptable-use-policy", "privacy-policy", "terms-of-service",
    "data-deletion-request", "knowledge-base", "help-center", "about", "affiliate",
)

# Pack files that are NOT copied into the flow (placeholders / templates / dev scripts).
PACK_SKIP = {"home.html", "index.html", "checkout.html"}

STEP1_PLACEHOLDER = "__KK_STEP1__"

# Conversion signals (matched against link text) for presell-body CTAs.
CONV_TEXT = [
    "pricing", "get started", "start for", "get started for", "sign in", "signin",
    "sign up", "signup", "log in", "login", "register", "create account",
    "get me access", "get access", "lifetime", "checkout", "subscribe",
    "upgrade", "claim", "take path", "path b", "$14.99", "$399", "/mo",
    "see lifetime", "see plan", "choose your plan", "choose plan", "pick your plan",
    "buy", "purchase", "join", "see plans", "view plans", "start free", "try ",
]
CONV_TEXT_EXACT = {"plans", "plan", "$14.99/mo", "lifetime $399"}
CONV_HREF = [
    "register", "signup", "sign-up", "/login", "sign-in",
    "/checkout", "checkout.html", "checkout.php",
]
LIBRARY_TEXT = ["gpt library", "gpt builder", "prompt library"]
LIBRARY_HREF = ["gpt-library", "prompt-library", "gpt-builder"]

# Symlink aliases (canonical dir prefix -> alias used inside generated HTML).
SYMLINK_ALIASES = {
    "/omnirogue-landers": "/omnirogue",
    "/omnirogue-checkouts": "/checkouts-omni",
    "/aipu-landers": "/aipu-landers",
    "/aipu-checkouts": "/aipu-checkouts",
}

WARNINGS = []


# --------------------------------------------------------------------------- #
# small helpers
# --------------------------------------------------------------------------- #

def slugify(value):
    value = (value or "").strip().lower()
    value = re.sub(r"[^a-z0-9._-]+", "-", value)
    value = re.sub(r"-{2,}", "-", value).strip("-_.")
    return value or "flow"


def detect_brand(path, docroot=DEFAULT_DOCROOT):
    try:
        rel = os.path.relpath(os.path.realpath(path), os.path.realpath(docroot))
    except Exception:
        rel = path
    first = rel.replace(os.sep, "/").strip("/").split("/")[0].lower()
    if first.startswith("omni") or "omnirogue" in first:
        return "omni"
    if first.startswith("aipu") or "aipu" in first:
        return "aipu"
    return "omni"


def web_dir_for(abs_dir, docroot):
    rel = os.path.relpath(abs_dir, docroot)
    return "/" + rel.replace(os.sep, "/").strip("/")


def alias_variants(web_path):
    out = [web_path]
    for canon, alias in SYMLINK_ALIASES.items():
        if alias != canon and web_path.startswith(canon + "/"):
            out.append(alias + web_path[len(canon):])
        if alias != canon and web_path == canon:
            out.append(alias)
    return out


def is_external_or_special(url):
    if not url:
        return True
    u = url.strip()
    low = u.lower()
    return (
        low.startswith("http://") or low.startswith("https://") or u.startswith("//")
        or low.startswith("data:") or low.startswith("mailto:") or low.startswith("tel:")
        or low.startswith("javascript:") or low.startswith("blob:")
        or u.startswith("#") or u.startswith("/")
    )


def resolve_url(url, base_web_dir):
    if is_external_or_special(url):
        return url
    m = re.search(r"[?#]", url)
    if m:
        frag = url[m.start():]
        path_part = url[:m.start()]
    else:
        frag = ""
        path_part = url
    joined = posixpath.normpath(posixpath.join(base_web_dir + "/", path_part))
    if not joined.startswith("/"):
        joined = "/" + joined
    return joined + frag


def resolve_srcset(value, base_web_dir):
    out = []
    for part in value.split(","):
        part = part.strip()
        if not part:
            continue
        bits = part.split()
        bits[0] = resolve_url(bits[0], base_web_dir)
        out.append(" ".join(bits))
    return ", ".join(out)


def resolve_assets(soup, base_web_dir):
    """Make every relative asset reference absolute to its original location."""
    for tag in soup.find_all(["link", "script", "img", "source", "video", "audio", "use"]):
        name = tag.name
        if name == "link":
            rel = " ".join(tag.get("rel", [])).lower() if tag.get("rel") else ""
            if tag.get("href") and (rel == "" or any(
                r in rel for r in ["stylesheet", "icon", "preload", "manifest", "prefetch", "mask-icon", "apple-touch-icon"]
            )):
                tag["href"] = resolve_url(tag["href"], base_web_dir)
        else:
            if tag.get("src"):
                tag["src"] = resolve_url(tag["src"], base_web_dir)
            if tag.get("srcset"):
                tag["srcset"] = resolve_srcset(tag["srcset"], base_web_dir)
            if tag.get("poster"):
                tag["poster"] = resolve_url(tag["poster"], base_web_dir)
    for meta in soup.find_all("meta"):
        prop = (meta.get("property") or meta.get("name") or "").lower()
        if prop in ("og:image", "twitter:image", "og:image:secure_url") and meta.get("content"):
            meta["content"] = resolve_url(meta["content"], base_web_dir)


# --------------------------------------------------------------------------- #
# brand link localisation (shared with the multistep builder, generalised)
# --------------------------------------------------------------------------- #

def localize_brand_html(text, web_path, brand, conversion_target=None, local_pages=True):
    """Point every brand-domain link at the local site: info/legal -> local pages,
    auth/marketing (login, register, contact, billing) -> the local checkout.

    Single-page flows pass local_pages=False (there are no side pages) and a
    custom conversion_target (e.g. the register URL) instead of checkout.html.
    """
    domains = BRAND_DOMAINS.get(brand, BRAND_DOMAINS["omni"])
    dom_alt = "|".join(re.escape(d) for d in domains)
    conversion_target = conversion_target or ("%s/checkout.html" % web_path)

    # Drop preload/preconnect/dns-prefetch hints to the brand site (we self-
    # host fonts and rewrite asset URLs elsewhere). Stylesheets, icons, fonts,
    # and other real assets pointing at the brand CDN are KEPT — stripping
    # them blew away the CSS for gpt-library / prompt-library / about pages,
    # leaving them unstyled and illegible.
    text = re.sub(
        r'<link\b(?=[^>]*\brel\s*=\s*"(?:preconnect|dns-prefetch|prefetch|preload|modulepreload)")[^>]*(?:%s)[^>]*>'
        % dom_alt,
        "",
        text,
    )

    # Local info/legal pages.
    if local_pages:
        for slug in LOCAL_INFO_SLUGS:
            text = re.sub(
                r'https?://(?:www\.|app\.)?(?:%s)/%s/?' % (dom_alt, re.escape(slug)),
                "%s/%s.html" % (web_path, slug),
                text,
            )

    # Favicon -> local asset.
    text = re.sub(
        r'https?://(?:www\.)?(?:%s)/favicon\.ico' % dom_alt,
        "%s/assets/img/favicon.ico" % web_path,
        text,
    )

    # Everything else on the brand domain (login, register, contact, billing,
    # pricing, ...) becomes the local conversion target. _next asset URLs are
    # left alone (fonts are self-hosted separately); mailto: is never touched.
    text = re.sub(
        r'https?://(?:www\.|app\.)?(?:%s)/(?!_next)[^"\')\s]*' % dom_alt,
        conversion_target,
        text,
    )
    return text


# --------------------------------------------------------------------------- #
# presell-body link classification (only the lander body; nav comes from chrome)
# --------------------------------------------------------------------------- #

def classify_link(text, href):
    t = re.sub(r"\s+", " ", (text or "")).strip().lower()
    h = (href or "").strip()
    hl = h.lower()
    if any(k in t for k in LIBRARY_TEXT) or any(k in hl for k in LIBRARY_HREF):
        return "checkout"
    if t in CONV_TEXT_EXACT:
        return "checkout"
    if any(k in t for k in CONV_TEXT):
        return "checkout"
    if any(k in hl for k in CONV_HREF):
        return "checkout"
    if hl.startswith("#") and hl != "#":
        return "keep"
    return "hash"


def rewrite_presell_body_links(soup, conversion_target="checkout.html"):
    """Lander body CTAs -> the conversion target; soft anchors -> inert; keep
    in-page anchors. Sales-only flows pass the register URL (no checkout page)."""
    for a in soup.find_all("a"):
        href = a.get("href", "")
        # leave links the chrome/site nav owns (already absolute into the flow)
        if href.startswith("/") or href.startswith("http"):
            continue
        kind = classify_link(a.get_text(" ", strip=True), href)
        if kind == "checkout":
            a["href"] = conversion_target
            if a.get("target"):
                del a["target"]
        elif kind == "hash":
            a["href"] = "#"
            if a.get("target"):
                del a["target"]


# Class-name signals for a lander's leading announcement / promo / countdown bar.
ANNOUNCE_CLASS_RE = re.compile(r"(promo|announce|banner|ticker|top-?bar)", re.I)


def tag_announce_bar(soup):
    """Add a stable `kk-announce-bar` hook class to the lander's leading
    announcement/countdown bar so the injected site chrome can pin it cleanly
    under the nav (and compact it on mobile) regardless of the lander's original
    class name. Non-destructive (only adds a class). Tags the FIRST top-of-body
    bar: a body-level element whose class matches ANNOUNCE_CLASS_RE, or a thin
    top bar that carries a countdown ([data-cd-h]/[data-cd]/.countdown).
    Returns True if a bar was tagged."""
    body = soup.body or soup
    candidates = list(body.find_all(True, recursive=False)) or list(body.find_all(True))
    for el in candidates:
        if el.name in ("script", "style", "link", "meta", "noscript"):
            continue
        classes = el.get("class") or []
        is_bar = bool(ANNOUNCE_CLASS_RE.search(" ".join(classes)))
        if not is_bar and el.name not in ("section", "main", "article"):
            # Countdown-bearing top bar (sabrina-style data-cd / promo countdown).
            has_cd = (el.find(attrs={"data-cd-h": True}) is not None
                      or el.find(attrs={"data-cd": True}) is not None)
            try:
                has_cd = has_cd or (el.select_one(".countdown") is not None)
            except Exception:
                pass
            is_bar = has_cd
        if is_bar:
            if "kk-announce-bar" not in classes:
                el["class"] = classes + ["kk-announce-bar"]
            return True
    return False


# --------------------------------------------------------------------------- #
# source loading
# --------------------------------------------------------------------------- #

def load_lander_raw(lander_dir):
    """Return (raw_html, entry). Strips PHP, mapping step1link -> placeholder."""
    for entry in ("index.html", "index.php"):
        path = os.path.join(lander_dir, entry)
        if os.path.isfile(path):
            with open(path, "r", encoding="utf-8", errors="replace") as fh:
                raw = fh.read()
            if entry.endswith(".php"):
                raw = re.sub(r"<\?=\s*\$link\s*\[\s*'step1link'\s*\]\s*;?\s*\?>", STEP1_PLACEHOLDER, raw)
                raw = re.sub(r"<\?php\s+echo\s+\$link\s*\[\s*'step1link'\s*\]\s*;?\s*\?>", STEP1_PLACEHOLDER, raw)
                raw = re.sub(r"<\?php.*?\?>", "", raw, flags=re.S)
                raw = re.sub(r"<\?=.*?\?>", "", raw, flags=re.S)
            return raw, entry
    raise FileNotFoundError("No index.html or index.php in %s" % lander_dir)


def load_checkout_raw(checkout_dir):
    path = os.path.join(checkout_dir, "index.html")
    if not os.path.isfile(path):
        raise FileNotFoundError("No index.html in %s" % checkout_dir)
    with open(path, "r", encoding="utf-8", errors="replace") as fh:
        return fh.read()


# --------------------------------------------------------------------------- #
# runtime config injection (sets register/checkout targets + neutralises Home)
# --------------------------------------------------------------------------- #

HOME_DEAD_JS = (
    "<script data-flow-home-dead>\n"
    "(function(){\n"
    "  function kill(){\n"
    "    var els=document.querySelectorAll('a,button');\n"
    "    for(var i=0;i<els.length;i++){\n"
    "      var el=els[i];\n"
    "      var t=(el.textContent||'').replace(/\\s+/g,' ').trim();\n"
    "      if(t==='Home'){\n"
    "        if(el.tagName==='A'){el.setAttribute('href','#');}\n"
    "        el.style.cursor='default';\n"
    "        if(!el.__flowHomeDead){el.__flowHomeDead=1;el.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();},true);}\n"
    "      }\n"
    "    }\n"
    "  }\n"
    "  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',kill);}else{kill();}\n"
    "  setTimeout(kill,300);setTimeout(kill,1200);\n"
    "})();\n"
    "</script>\n"
)


def flow_config_js(flow_web, register_url, *, checkout_ext="html", checkout_url=None):
    if checkout_url is None:
        checkout_url = "%s/checkout.%s" % (flow_web, checkout_ext)
    return (
        "<script data-flow-config>\n"
        "window.__LANDER_BASE=%s;\n" % json.dumps(flow_web, ensure_ascii=False)
        + "window.__KK_CHECKOUT_URL=%s;\n" % json.dumps(checkout_url, ensure_ascii=False)
        + "window.__KK_REGISTER_CHECKOUT=%s;\n" % json.dumps(register_url, ensure_ascii=False)
        + "window.__OMNI_HOME_DEAD=1;\n"
        + "</script>\n"
    )


def inject_flow_runtime(html, flow_web, register_url, *, checkout_ext="html",
                        checkout_url=None):
    """Insert the flow config before </head> (runs before deferred static.js)
    and the Home-dead enforcer before </body>."""
    cfg = flow_config_js(flow_web, register_url, checkout_ext=checkout_ext,
                         checkout_url=checkout_url)
    if "data-flow-config" not in html:
        if "</head>" in html:
            html = html.replace("</head>", cfg + "</head>", 1)
        else:
            html = cfg + html
    if "data-flow-home-dead" not in html:
        if "</body>" in html:
            html = html.replace("</body>", HOME_DEAD_JS + "</body>", 1)
        else:
            html = html + HOME_DEAD_JS
    return html


# --------------------------------------------------------------------------- #
# checkout chrome stripping (remove the injected or-nav so the site nav wins)
# --------------------------------------------------------------------------- #

def strip_checkout_chrome(html):
    html = re.sub(r"<!-- chrome:head -->.*?<!-- /chrome:head -->", "", html, flags=re.S)
    html = re.sub(r"<!-- chrome:header -->.*?<!-- /chrome:header -->", "", html, flags=re.S)
    html = re.sub(r"<!-- chrome:footer -->.*?<!-- /chrome:footer -->", "", html, flags=re.S)
    return html


# --------------------------------------------------------------------------- #
# main build
# --------------------------------------------------------------------------- #

def _social_proof_setting(manifest):
    """'on'/'off' from a flow.json widgets_config (default on)."""
    cfg = manifest.get("widgets_config") if isinstance(manifest, dict) else None
    slot = cfg.get("social_proof") if isinstance(cfg, dict) else None
    val = slot.get("placement") if isinstance(slot, dict) else None
    return "off" if str(val or "").strip().lower() == "off" else "on"


def build(lander_dir, checkout_dir, name, brand, flows_dir, docroot, register_url,
          source_brand=None, optimize=True, flow_base=None, single_page=False):
    """Dispatch on flow shape (Law 3/4):
        single_page=True  -> whole multi-page site, billing pop-up = checkout,
                             NO separate checkout.html (the "Single Page" flow)
        lander + checkout -> multi-step site (index.html + checkout.html + pages)
        lander only       -> sales-only single page (index.html)
        checkout only     -> checkout-only single page (index.html)

    `flow_base` is the shared base slug that pairs a flow's per-brand builds
    (e.g. <base>__aipu and <base>__omni) so the previews UI can group them into
    a single "Sales Flow" card. Defaults to this build's own slug.
    """
    if single_page:
        # A single-page source is a self-contained multi-page site whose checkout
        # is the in-page billing pop-up. It comes in via --lander-dir (the site
        # root), but there is never a separate checkout.
        src_dir = lander_dir or checkout_dir
        if not src_dir:
            raise ValueError("Single-page flow needs a source site dir (--lander-dir)")
        return _build_single_page(src_dir, name, brand, flows_dir, docroot,
                                  register_url, source_brand, optimize, flow_base)
    if lander_dir and checkout_dir:
        return _build_multi(lander_dir, checkout_dir, name, brand, flows_dir,
                            docroot, register_url, source_brand, optimize, flow_base)
    if lander_dir or checkout_dir:
        return _build_single(lander_dir, checkout_dir, name, brand, flows_dir,
                             docroot, register_url, source_brand, optimize, flow_base)
    raise ValueError("Provide a lander dir, a checkout dir, or both")


def _build_multi(lander_dir, checkout_dir, name, brand, flows_dir, docroot,
                 register_url, source_brand=None, optimize=True, flow_base=None):
    lander_dir = os.path.realpath(lander_dir)
    checkout_dir = os.path.realpath(checkout_dir)

    if not os.path.isdir(lander_dir):
        raise FileNotFoundError("Lander dir not found: %s" % lander_dir)
    if not os.path.isdir(checkout_dir):
        raise FileNotFoundError("Checkout dir not found: %s" % checkout_dir)

    brand = brand or detect_brand(lander_dir, docroot)

    # The source's own brand flavour (what its body tokens are written in). For the
    # brand-neutral collections (sales-pages / checkout-pages) detect_brand returns
    # "omni" -- their canonical flavour -- so rebrand_text(omni->brand) re-skins the
    # body to the chosen brand. For legacy aipu-*/omnirogue-* sources this equals the
    # source brand, so the rebrand pass is a harmless no-op (back-compat). An explicit
    # --source-brand overrides both. Computed per source so a neutral lander can be
    # paired with a legacy checkout (or vice-versa) and each still re-skins correctly.
    src_lander = source_brand or detect_brand(lander_dir, docroot)
    src_checkout = source_brand or detect_brand(checkout_dir, docroot)

    pack_web = BRAND_PAGES.get(brand, BRAND_PAGES["omni"])
    pack_dir = os.path.join(docroot, pack_web.strip("/"))
    if not os.path.isdir(pack_dir):
        raise FileNotFoundError("Brand page pack not found: %s" % pack_dir)

    slug = slugify(name)
    out_dir = os.path.join(flows_dir, slug)
    flow_web = web_dir_for(out_dir, docroot)

    lander_web = web_dir_for(lander_dir, docroot)
    checkout_web = web_dir_for(checkout_dir, docroot)

    # Preserve a prior flow.json (KK state) + any built kk/ folder across rebuilds.
    manifest = {}
    manifest_path = os.path.join(out_dir, "flow.json")
    if os.path.isfile(manifest_path):
        try:
            with open(manifest_path, "r", encoding="utf-8") as fh:
                manifest = json.load(fh) or {}
        except Exception:
            manifest = {}
    _clean_flow_dir(out_dir)

    # 1) Copy the brand customer-facing pages (+ assets) into the flow.
    shutil.copytree(
        pack_dir, out_dir,
        ignore=shutil.ignore_patterns("_*.py", "__pycache__", *PACK_SKIP),
        dirs_exist_ok=True,
    )

    # 2) Overlay the lander's own assets (css/js/img) so the presell renders.
    lander_assets = os.path.join(lander_dir, "assets")
    for sub in ("css", "js", "img"):
        src_sub = os.path.join(lander_assets, sub)
        if os.path.isdir(src_sub):
            shutil.copytree(src_sub, os.path.join(out_dir, "assets", sub), dirs_exist_ok=True)

    # 3) Re-root pack paths -> flow-local, point Home at index, localise brand links.
    for root, _dirs, files in os.walk(out_dir):
        for fn in files:
            if not fn.endswith((".html", ".js", ".css")):
                continue
            fp = os.path.join(root, fn)
            text = open(fp, encoding="utf-8", errors="replace").read()
            text = text.replace(pack_web + "/", flow_web + "/")
            text = text.replace("/home.html", "/index.html")
            text = text.replace(flow_web + "/home.html", flow_web + "/index.html")
            if fn.endswith(".html"):
                text = localize_brand_html(text, flow_web, brand)
            open(fp, "w", encoding="utf-8").write(text)

    # 4) Self-host fonts + scope the lander stylesheet to .omni-lander-wrap.
    try:
        omni.self_host_fonts(Path(out_dir), flow_web)
    except Exception as exc:
        WARNINGS.append("self_host_fonts: %s" % exc)
    omni.write_scoped_lander_css(Path(out_dir))

    # 5) Canonical site nav + footer (already re-rooted + localised above).
    chrome_ref_path = os.path.join(out_dir, "create-image.html")
    if not os.path.isfile(chrome_ref_path):
        raise FileNotFoundError("Pack is missing create-image.html (needed for site chrome)")
    chrome_ref = open(chrome_ref_path, encoding="utf-8", errors="replace").read()
    site_nav, site_footer = omni.extract_site_chrome(chrome_ref)

    # 6) Presell (index.html): lander body + injected site chrome.
    index_html = _build_presell(lander_dir, lander_web, flow_web, brand, src_lander)
    index_html = omni.wrap_with_site_chrome(
        index_html, site_nav, site_footer, flow_web,
        strip_lander_header=True, strip_lander_footer=True,
    )
    index_html = omni.normalize_presell_index(index_html, site_nav, site_footer)
    # Pass out_dir so inline-CSS landers (no external main.css -> no main.scoped.css)
    # don't get a broken <link> injected.
    index_html = omni.use_scoped_lander_css(index_html, flow_web, Path(out_dir))
    open(os.path.join(out_dir, "index.html"), "w", encoding="utf-8").write(index_html)

    # 7) Checkout (checkout.html): plan picker + injected site chrome.
    checkout_html = _build_checkout(checkout_dir, checkout_web, flow_web, out_dir, brand, src_checkout)
    checkout_html = omni.wrap_with_site_chrome(
        checkout_html, site_nav, site_footer, flow_web, checkout=True,
    )
    open(os.path.join(out_dir, "checkout.html"), "w", encoding="utf-8").write(checkout_html)

    # 8) Point the flow's static.js at the flow base + studio sidebar pages.
    omni.patch_static_js(Path(out_dir), flow_web,
                         social_proof=_social_proof_setting(manifest))

    # 9) Sync identical chrome on every page; nav isolation; nav base CSS.
    omni.finalize_omnifull_headers(Path(out_dir), flow_web, site_nav, site_footer)

    # 10) Inject the flow runtime config (register/checkout targets + Home-dead).
    page_count = 0
    for fn in sorted(os.listdir(out_dir)):
        if fn.endswith(".html"):
            fp = os.path.join(out_dir, fn)
            text = open(fp, encoding="utf-8", errors="replace").read()
            text = inject_flow_runtime(text, flow_web, register_url, checkout_ext="html")
            open(fp, "w", encoding="utf-8").write(text)
            page_count += 1

    # 10b) Heal any brand-logo refs the body rebrand re-pointed (e.g. omni->aipu
    # turned logo-omnirogue.png into logo-aipu.png) by copying a canonical brand
    # logo in. self_web MUST be flow_web so the absolute /flows/<slug>/... refs are
    # recognised as ours to heal instead of being skipped as another collection's.
    try:
        brandlib.heal_missing_logos(Path(out_dir), brand, WARNINGS, self_web=flow_web)
    except Exception as exc:
        WARNINGS.append("heal_missing_logos: %s" % exc)

    # 10c) Optimize assets in place (recompress PNG/JPG, WebP siblings, minify
    # CSS/JS). Gated by flow.json assets_config.optimize (default on) and the
    # --no-optimize CLI flag. Skips the kk/ subtree (kk_format optimizes that
    # copy itself). Non-fatal: any failure is recorded as a warning.
    assets_config = manifest.get("assets_config") if isinstance(manifest.get("assets_config"), dict) else {}
    optimize_result = None
    if optimize and assets_config.get("optimize", True):
        try:
            optimize_result = asset_pipeline.optimize_dir(out_dir, skip_dirs={"kk"})
            # Upgrade bare <img> to <picture>+WebP with width/height + lazy/async.
            resp = asset_pipeline.upgrade_responsive_images(out_dir, [flow_web + "/"])
            optimize_result["responsive"] = resp
            WARNINGS.extend(optimize_result.get("warnings", []))
            WARNINGS.extend(resp.get("warnings", []))
        except Exception as exc:
            WARNINGS.append("asset optimize: %s" % exc)

    # 10d) Publish assets to BunnyCDN and rewrite refs to the pull-zone, if the
    # .env enables it (CDN_ENABLED=1 + credentials) and the flow hasn't opted out.
    cdn_result = publish_flow_to_cdn(out_dir, slug, flow_web, manifest, docroot, WARNINGS)

    # 11) Persist metadata (preserve KK state across rebuilds).
    new_manifest = {
        "name": slug,
        "brand": brand,
        "flow_base": slugify(flow_base) if flow_base else slug,
        "flow_type": "multi",
        "lander_dir": lander_dir,
        "lander_web": lander_web,
        "lander_entry": "index.php" if os.path.isfile(os.path.join(lander_dir, "index.php")) else "index.html",
        "checkout_dir": checkout_dir,
        "checkout_web": checkout_web,
        "pack_web": pack_web,
        "register_url": register_url,
        "interactive": True,
        "kk": False,
        # First-generation timestamp; survives rebuilds (the UI shows it).
        "built_at": int(manifest.get("built_at") or time.time()),
    }
    if manifest.get("kk"):
        new_manifest["kk"] = manifest.get("kk")
        new_manifest["kk_name"] = manifest.get("kk_name")
        new_manifest["kk_built"] = manifest.get("kk_built")
        new_manifest["kk_offer_wired"] = manifest.get("kk_offer_wired")
        new_manifest["kk_pages"] = manifest.get("kk_pages")
    # Preserve the per-page CTA / register-token config the previews UI
    # (flow-config.php) writes, so a rebuild never wipes the user's routing.
    if isinstance(manifest.get("cta_config"), dict):
        new_manifest["cta_config"] = manifest["cta_config"]
    # Preserve per-CTA token overrides (single-page flows; kk_format applies them).
    if isinstance(manifest.get("cta_map"), dict):
        new_manifest["cta_map"] = manifest["cta_map"]
    if isinstance(manifest.get("plans_config"), dict):
        new_manifest["plans_config"] = manifest["plans_config"]
    # Preserve the per-flow asset/CDN config the previews UI (flow-assets.php)
    # writes, so a rebuild never wipes the user's optimization/CDN choices.
    if isinstance(manifest.get("assets_config"), dict):
        new_manifest["assets_config"] = manifest["assets_config"]
    # Preserve the widgets config (exit popup / slider / social proof).
    if isinstance(manifest.get("widgets_config"), dict):
        new_manifest["widgets_config"] = manifest["widgets_config"]
    # Record the CDN publish outcome so the UI can show "on CDN" + the base URL.
    if cdn_result and cdn_result.get("ok"):
        new_manifest["cdn"] = {
            "published": True,
            "asset_base": cdn_result.get("asset_base"),
            "build_hash": cdn_result.get("build_hash"),
            "uploaded": cdn_result.get("uploaded", 0),
            "at": int(time.time()),
        }
    with open(manifest_path, "w", encoding="utf-8") as fh:
        json.dump(new_manifest, fh, indent=2)

    # 12) Automatic QC gate over the freshly written flow (non-blocking).
    qc = qa_checks.safe_validate("flow", out_dir, docroot, brand)

    # 13) Desktop/mobile header parity gate: renders the entry pages headless
    # and asserts the mobile menu matches the brand reference exactly.
    nav_parity = verify_nav_parity.safe_verify(out_dir, brand=brand)
    if nav_parity.get("status") != "pass":
        for p in nav_parity.get("problems", []):
            WARNINGS.append("nav-parity %s [%s]: %s" % (p.get("page"), p.get("layer"), p.get("problem")))

    return {
        "ok": True,
        "flow": slug,
        "brand": brand,
        "flow_base": new_manifest["flow_base"],
        "flow_type": "multi",
        "url": flow_web + "/index.html",
        "checkout_url": flow_web + "/checkout.html",
        "register_url": register_url,
        "pages": page_count,
        "lander": {"dir": lander_dir, "web": lander_web},
        "checkout": {"dir": checkout_dir, "web": checkout_web},
        "warnings": WARNINGS,
        "qc": qc,
        "nav_parity": nav_parity,
        "assets": optimize_result,
        "cdn": new_manifest.get("cdn"),
    }


def _build_single(lander_dir, checkout_dir, name, brand, flows_dir, docroot,
                  register_url, source_brand=None, optimize=True, flow_base=None):
    """Build a one-page flow (Law 3): the single page is hosted as index.html.

    sales-only:    the lander, body CTAs -> register_url (KK format later maps
                   each CTA to a price-matched plan token or register token).
    checkout-only: the plan picker hosted AS the index page (no chrome wrap).
    """
    flow_type = "sales-only" if lander_dir else "checkout-only"
    src_dir = os.path.realpath(lander_dir or checkout_dir)
    if not os.path.isdir(src_dir):
        raise FileNotFoundError("Source dir not found: %s" % src_dir)

    brand = brand or detect_brand(src_dir, docroot)
    src_brand = source_brand or detect_brand(src_dir, docroot)

    slug = slugify(name)
    out_dir = os.path.join(flows_dir, slug)
    flow_web = web_dir_for(out_dir, docroot)
    src_web = web_dir_for(src_dir, docroot)

    manifest = {}
    manifest_path = os.path.join(out_dir, "flow.json")
    if os.path.isfile(manifest_path):
        try:
            with open(manifest_path, "r", encoding="utf-8") as fh:
                manifest = json.load(fh) or {}
        except Exception:
            manifest = {}
    _clean_flow_dir(out_dir)
    os.makedirs(out_dir, exist_ok=True)

    if flow_type == "sales-only":
        # Copy the lander's support files (assets etc.), then build the page.
        shutil.copytree(
            src_dir, out_dir,
            ignore=shutil.ignore_patterns("index.html", "index.php", "*.php",
                                          "_*.py", "__pycache__", "*.md", ".*"),
            dirs_exist_ok=True,
        )
        html = _build_presell(src_dir, src_web, flow_web, brand, src_brand,
                              conversion_target=register_url, local_pages=False)
        checkout_url = register_url
    else:
        html = _build_checkout(src_dir, src_web, flow_web, out_dir, brand, src_brand,
                               conversion_target=register_url, local_pages=False)
        checkout_url = flow_web + "/index.html"

    html = inject_flow_runtime(html, flow_web, register_url,
                               checkout_url=checkout_url)
    open(os.path.join(out_dir, "index.html"), "w", encoding="utf-8").write(html)

    # Point any consolidated static.js at the flow base.
    try:
        omni.patch_static_js(Path(out_dir), flow_web,
                             social_proof=_social_proof_setting(manifest))
    except Exception as exc:
        WARNINGS.append("patch_static_js: %s" % exc)

    try:
        brandlib.heal_missing_logos(Path(out_dir), brand, WARNINGS, self_web=flow_web)
    except Exception as exc:
        WARNINGS.append("heal_missing_logos: %s" % exc)

    assets_config = manifest.get("assets_config") if isinstance(manifest.get("assets_config"), dict) else {}
    optimize_result = None
    if optimize and assets_config.get("optimize", True):
        try:
            optimize_result = asset_pipeline.optimize_dir(out_dir, skip_dirs={"kk"})
            resp = asset_pipeline.upgrade_responsive_images(out_dir, [flow_web + "/"])
            optimize_result["responsive"] = resp
            WARNINGS.extend(optimize_result.get("warnings", []))
            WARNINGS.extend(resp.get("warnings", []))
        except Exception as exc:
            WARNINGS.append("asset optimize: %s" % exc)

    # Publish assets to BunnyCDN + rewrite refs (gated by .env + per-flow config).
    cdn_result = publish_flow_to_cdn(out_dir, slug, flow_web, manifest, docroot, WARNINGS)

    new_manifest = {
        "name": slug,
        "brand": brand,
        "flow_base": slugify(flow_base) if flow_base else slug,
        "flow_type": flow_type,
        "lander_dir": lander_dir and os.path.realpath(lander_dir),
        "lander_web": src_web if flow_type == "sales-only" else None,
        "checkout_dir": checkout_dir and os.path.realpath(checkout_dir),
        "checkout_web": src_web if flow_type == "checkout-only" else None,
        "register_url": register_url,
        "interactive": True,
        "kk": False,
        "built_at": int(manifest.get("built_at") or time.time()),
    }
    if manifest.get("kk"):
        for k in ("kk", "kk_name", "kk_built", "kk_offer_wired", "kk_pages"):
            new_manifest[k] = manifest.get(k)
    for k in ("cta_config", "cta_map", "plans_config", "assets_config",
              "widgets_config"):
        if isinstance(manifest.get(k), dict):
            new_manifest[k] = manifest[k]
    if cdn_result and cdn_result.get("ok"):
        new_manifest["cdn"] = {
            "published": True,
            "asset_base": cdn_result.get("asset_base"),
            "build_hash": cdn_result.get("build_hash"),
            "uploaded": cdn_result.get("uploaded", 0),
            "at": int(time.time()),
        }
    with open(manifest_path, "w", encoding="utf-8") as fh:
        json.dump(new_manifest, fh, indent=2)

    qc = qa_checks.safe_validate("flow", out_dir, docroot, brand)

    nav_parity = verify_nav_parity.safe_verify(out_dir, brand=brand)
    if nav_parity.get("status") != "pass":
        for p in nav_parity.get("problems", []):
            WARNINGS.append("nav-parity %s [%s]: %s" % (p.get("page"), p.get("layer"), p.get("problem")))

    return {
        "ok": True,
        "flow": slug,
        "brand": brand,
        "flow_base": new_manifest["flow_base"],
        "flow_type": flow_type,
        "url": flow_web + "/index.html",
        "checkout_url": checkout_url,
        "register_url": register_url,
        "pages": 1,
        "lander": {"dir": lander_dir, "web": src_web} if flow_type == "sales-only" else None,
        "checkout": {"dir": checkout_dir, "web": src_web} if flow_type == "checkout-only" else None,
        "warnings": WARNINGS,
        "qc": qc,
        "nav_parity": nav_parity,
        "assets": optimize_result,
        "cdn": new_manifest.get("cdn"),
    }


def _build_single_page(src_dir, name, brand, flows_dir, docroot, register_url,
                       source_brand=None, optimize=True, flow_base=None):
    """Build a "Single Page" flow (flow_type='single-page').

    The source is a self-contained multi-page site (home, create-*, legal pages,
    ...) whose checkout is an in-page billing pop-up (billing.html). Unlike a
    multi-step flow there is NO separate checkout.html: every Pricing / Login /
    Register / Create-Account surface opens the pop-up (wired in the source
    static.js). This is a "direct flow" build — the source already carries its
    own site chrome + pop-up, so (unlike _build_multi) we do NOT reassemble
    chrome from a brand pack. We only:

      * copy the whole site verbatim into flows/<slug>/
      * re-root the source's own web paths (/single-pages/<item>) -> /flows/<slug>
        AND re-skin the body omni->brand (brandlib.swap_self_dir does both safely)
      * point brand auth/marketing links (login/register/billing) at the local
        billing page so they convert instead of leaving the site
      * patch static.js (__LANDER_BASE, page ext, studio nav) to the flow base
      * heal brand logos, optimize assets, publish to CDN
      * write flow.json with flow_type='single-page' (NO checkout.html)

    kk_format.py picks up flow_type='single-page' and: keeps every page (.php +
    $__step1link tracking so the click ID rides across the whole multi-page
    journey), wires the billing pop-up's offers/prices/product-IDs via
    window.__KK_OFFER_LINKS, and points conversion CTAs at the pop-up.
    """
    global WARNINGS
    WARNINGS = []

    src_dir = os.path.realpath(src_dir)
    if not os.path.isdir(src_dir):
        raise FileNotFoundError("Single-page source dir not found: %s" % src_dir)
    if not (os.path.isfile(os.path.join(src_dir, "index.html"))
            or os.path.isfile(os.path.join(src_dir, "index.php"))):
        raise FileNotFoundError("Single-page source has no index.html: %s" % src_dir)
    if not os.path.isfile(os.path.join(src_dir, "billing.html")):
        raise FileNotFoundError(
            "Single-page source has no billing.html (the pop-up checkout): %s" % src_dir)

    brand = brand or detect_brand(src_dir, docroot)
    # The source's canonical flavour (single-pages/ is authored in OMNI, like
    # sales-pages/checkout-pages), so rebrand_text(src->brand) re-skins it.
    src_brand = source_brand or detect_brand(src_dir, docroot)

    slug = slugify(name)
    out_dir = os.path.join(flows_dir, slug)
    flow_web = web_dir_for(out_dir, docroot)
    src_path = Path(src_dir)

    # Preserve prior flow.json (KK / config state) across rebuilds.
    manifest = {}
    manifest_path = os.path.join(out_dir, "flow.json")
    if os.path.isfile(manifest_path):
        try:
            with open(manifest_path, "r", encoding="utf-8") as fh:
                manifest = json.load(fh) or {}
        except Exception:
            manifest = {}
    _clean_flow_dir(out_dir)
    os.makedirs(out_dir, exist_ok=True)

    # 1) Copy the whole self-contained site (drop build artifacts / a stray
    #    checkout stub — a single-page flow must never carry a checkout page).
    shutil.copytree(
        src_dir, out_dir,
        ignore=shutil.ignore_patterns(
            "kk", "kk.tmp", "flow.json", "_*.py", "__pycache__", "*.md",
            "checkout.html", "checkout.php", ".asset-manifest.json"),
        dirs_exist_ok=True,
    )

    # 2) Re-root the source's own web paths -> the flow folder AND re-skin the
    #    body omni->brand. swap_self_dir shields the literal self-folder name
    #    from the brand pass, so re-rooting and rebranding never corrupt each
    #    other. Applied to every text asset (html/js/jsx/css/json/svg).
    text_ext = (".html", ".htm", ".css", ".js", ".mjs", ".jsx", ".json", ".svg", ".txt")
    for root, _dirs, files in os.walk(out_dir):
        for fn in files:
            if not fn.endswith(text_ext):
                continue
            fp = os.path.join(root, fn)
            try:
                text = open(fp, encoding="utf-8", errors="replace").read()
            except OSError:
                continue
            new = brandlib.swap_self_dir(text, src_path, flow_web, src_brand, brand,
                                         docroot=Path(docroot))
            # 3) On HTML pages, localise brand auth/marketing links (login,
            #    register, billing, contact) to the local billing page so they
            #    convert in-page instead of dead-ending off-site. The pop-up is
            #    the primary UX (static.js intercepts these clicks); this is the
            #    no-JS / KK fallback target. Home stays live (multi-page site).
            if fn.endswith(".html"):
                new = localize_brand_html(
                    new, flow_web, brand,
                    conversion_target=flow_web + "/billing.html",
                    local_pages=True)
            if new != text:
                open(fp, "w", encoding="utf-8").write(new)

    # 4) Point static.js at the flow base (+ studio nav, page ext, social proof).
    try:
        omni.patch_static_js(Path(out_dir), flow_web,
                             social_proof=_social_proof_setting(manifest))
    except Exception as exc:
        WARNINGS.append("patch_static_js: %s" % exc)

    # 5) Ensure every page carries a <script data-flow-config></script> slot so
    #    kk_format can inject window.__KK_OFFER_LINKS / register links later.
    _ensure_flow_config_slots(out_dir)

    # 6) Heal any brand-logo refs the omni->brand rebrand re-pointed.
    try:
        brandlib.heal_missing_logos(Path(out_dir), brand, WARNINGS, self_web=flow_web)
    except Exception as exc:
        WARNINGS.append("heal_missing_logos: %s" % exc)

    # 7) Optimize assets in place (gated like the other builders).
    assets_config = manifest.get("assets_config") if isinstance(manifest.get("assets_config"), dict) else {}
    optimize_result = None
    if optimize and assets_config.get("optimize", True):
        try:
            optimize_result = asset_pipeline.optimize_dir(out_dir, skip_dirs={"kk"})
            resp = asset_pipeline.upgrade_responsive_images(out_dir, [flow_web + "/"])
            optimize_result["responsive"] = resp
            WARNINGS.extend(optimize_result.get("warnings", []))
            WARNINGS.extend(resp.get("warnings", []))
        except Exception as exc:
            WARNINGS.append("asset optimize: %s" % exc)

    # 8) Publish assets to BunnyCDN if enabled.
    cdn_result = publish_flow_to_cdn(out_dir, slug, flow_web, manifest, docroot, WARNINGS)

    # 9) Persist metadata (preserve KK / config state across rebuilds).
    page_count = sum(1 for fn in os.listdir(out_dir) if fn.endswith(".html"))
    new_manifest = {
        "name": slug,
        "brand": brand,
        "flow_base": slugify(flow_base) if flow_base else slug,
        "flow_type": "single-page",
        "lander_dir": src_dir,
        "lander_web": flow_web,
        "lander_entry": "index.html",
        "pack_web": None,
        "register_url": register_url,
        "interactive": True,
        "kk": False,
        "source": "single-page direct flow (billing pop-up = checkout, no checkout page)",
        "built_at": int(manifest.get("built_at") or time.time()),
    }
    # Default CTA / plans config (mirrors the original galaxypop flow) so the KK
    # offer wiring + register tokens resolve out of the box; the previews UI can
    # override these per-flow.
    new_manifest["cta_config"] = manifest.get("cta_config") if isinstance(manifest.get("cta_config"), dict) \
        else {"default_register_token": "registercheckout", "pages": {}}
    new_manifest["plans_config"] = manifest.get("plans_config") if isinstance(manifest.get("plans_config"), dict) \
        else {"index_register_token": "registercheckout"}
    if manifest.get("kk"):
        for k in ("kk", "kk_name", "kk_built", "kk_offer_wired", "kk_pages"):
            new_manifest[k] = manifest.get(k)
    for k in ("cta_map", "assets_config", "widgets_config"):
        if isinstance(manifest.get(k), dict):
            new_manifest[k] = manifest[k]
    if cdn_result and cdn_result.get("ok"):
        new_manifest["cdn"] = {
            "published": True,
            "asset_base": cdn_result.get("asset_base"),
            "build_hash": cdn_result.get("build_hash"),
            "uploaded": cdn_result.get("uploaded", 0),
            "at": int(time.time()),
        }
    with open(manifest_path, "w", encoding="utf-8") as fh:
        json.dump(new_manifest, fh, indent=2)

    qc = qa_checks.safe_validate("flow", out_dir, docroot, brand)

    return {
        "ok": True,
        "flow": slug,
        "brand": brand,
        "flow_base": new_manifest["flow_base"],
        "flow_type": "single-page",
        "url": flow_web + "/index.html",
        # The pop-up is the checkout; expose billing.html as the checkout link.
        "checkout_url": flow_web + "/billing.html",
        "register_url": register_url,
        "pages": page_count,
        "lander": {"dir": src_dir, "web": flow_web},
        "checkout": None,
        "warnings": WARNINGS,
        "qc": qc,
        "assets": optimize_result,
        "cdn": new_manifest.get("cdn"),
    }


# data-flow-config placeholder + the static.js include matcher (shared by the
# single-page builder and the source importer).
_FLOW_CFG_TAG = "<script data-flow-config></script>\n"
_STATIC_JS_RE = re.compile(
    r'(<script\b[^>]*\bsrc="[^"]*assets/static\.js[^"]*"[^>]*>\s*</script>)', re.I)


def _ensure_flow_config_slots(out_dir):
    """Make sure every top-level .html page has a <script data-flow-config> slot
    before its static.js include, so kk_format can inject the KK offer/register
    wiring. billing.html already ships one (the pop-up reads it for offer links)."""
    for fn in os.listdir(out_dir):
        if not fn.endswith(".html"):
            continue
        fp = os.path.join(out_dir, fn)
        html = open(fp, encoding="utf-8", errors="replace").read()
        if "data-flow-config" in html:
            continue
        new, n = _STATIC_JS_RE.subn(lambda m: _FLOW_CFG_TAG + m.group(1), html, count=1)
        if n:
            open(fp, "w", encoding="utf-8").write(new)
        else:
            WARNINGS.append("no static.js include in %s (no flow-config slot)" % fn)


def _clean_flow_dir(out_dir):
    """Remove everything in the flow dir except flow.json and the kk/ build."""
    if not os.path.isdir(out_dir):
        os.makedirs(out_dir, exist_ok=True)
        return
    for entry in os.listdir(out_dir):
        if entry in ("flow.json", "kk"):
            continue
        p = os.path.join(out_dir, entry)
        if os.path.isdir(p) and not os.path.islink(p):
            shutil.rmtree(p, ignore_errors=True)
        else:
            try:
                os.remove(p)
            except OSError:
                pass


def _build_presell(lander_dir, lander_web, flow_web, brand, source_brand=None,
                   conversion_target="checkout.html", local_pages=True):
    raw, _entry = load_lander_raw(lander_dir)
    # lander self-paths -> flow-local
    raw = raw.replace(lander_web + "/", flow_web + "/")
    for alias in alias_variants(lander_web):
        raw = raw.replace(alias + "/", flow_web + "/")
    # relative "assets/..." -> flow-local
    for attr in ("href", "src"):
        raw = raw.replace('%s="assets/' % attr, '%s="%s/assets/' % (attr, flow_web))
    raw = raw.replace('url("assets/', 'url("%s/assets/' % flow_web)
    raw = raw.replace("url('assets/", "url('%s/assets/" % flow_web)
    raw = raw.replace("url(assets/", "url(%s/assets/" % flow_web)
    # every lander CTA (step1link) -> the conversion target
    raw = raw.replace(STEP1_PLACEHOLDER, conversion_target)

    soup = BeautifulSoup(raw, "html.parser")
    rewrite_presell_body_links(soup, conversion_target)
    # Tag the lander's leading announcement/countdown bar so the injected chrome
    # can pin it cleanly under the nav and compact it on mobile (no double header).
    tag_announce_bar(soup)
    html = str(soup)
    # Re-skin the body to the chosen brand. Runs AFTER self/asset paths were
    # re-rooted to flow_web (which carries no brand tokens, so rebrand can't corrupt
    # it) and BEFORE localize_brand_html, so any brand-domain links rebrand surfaces
    # are then localized to the flow's own checkout. No-op when source==target.
    if source_brand and source_brand != brand:
        html = brandlib.rebrand_text(html, source_brand, brand)
    html = localize_brand_html(html, flow_web, brand,
                               conversion_target=conversion_target,
                               local_pages=local_pages)
    # any leftover pack-path legal links -> flow-local
    html = html.replace(BRAND_PAGES.get(brand, BRAND_PAGES["omni"]) + "/", flow_web + "/")
    return html


def _build_checkout(checkout_dir, checkout_web, flow_web, out_dir, brand, source_brand=None,
                    conversion_target=None, local_pages=True):
    """Self-contain the checkout: copy its support assets into the flow and
    re-root every reference flow-local (string ops only - never re-serialise the
    checkout markup through a parser)."""
    raw = load_checkout_raw(checkout_dir)
    raw = strip_checkout_chrome(raw)

    # Copy checkout support assets into the flow so it is self-contained.
    # Match any `plans-pick-your-plan*` variant — some checkouts use the base
    # name, others use suffixed bundles (e.g. `plans-pick-your-plan-3tier` for
    # the lifetime-create checkout). Missing the variant here strips the
    # checkout's CSS/JS bundle entirely → unstyled, illegible page.
    try:
        sub_names = [
            n for n in os.listdir(checkout_dir)
            if n.startswith("plans-pick-your-plan")
            and os.path.isdir(os.path.join(checkout_dir, n))
        ]
    except OSError:
        sub_names = []
    for sub_name in sub_names:
        src_sub = os.path.join(checkout_dir, sub_name)
        shutil.copytree(src_sub, os.path.join(out_dir, sub_name), dirs_exist_ok=True)
    for extra in ("logo-aipu.png", "logo-omnirogue.png", "plan-entitlements.json"):
        p = os.path.join(checkout_dir, extra)
        if os.path.isfile(p):
            shutil.copy2(p, os.path.join(out_dir, extra))

    # Re-root absolute checkout refs (and their symlink alias) -> flow-local.
    raw = raw.replace(checkout_web + "/", flow_web + "/")
    for alias in alias_variants(checkout_web):
        raw = raw.replace(alias + "/", flow_web + "/")
    # The hidden pyp-top logo lives one level up in the source; point at a flow logo.
    raw = raw.replace('"../assets/aipu-logo-horizontal.png"', '"%s/logo-omnirogue.png"' % flow_web)
    raw = raw.replace("../assets/", flow_web + "/assets/")
    # Any remaining relative checkout-bundle refs -> flow-local. Cover every
    # `plans-pick-your-plan*` variant that this checkout actually ships.
    for attr in ("href", "src"):
        for sub_name in sub_names:
            raw = raw.replace(
                '%s="%s/' % (attr, sub_name),
                '%s="%s/%s/' % (attr, flow_web, sub_name),
            )
    # Re-skin the checkout body to the chosen brand. String-only (the checkout is
    # never re-serialised through a parser). Runs after re-rooting + the hardcoded
    # logo replace, before localize. No-op when source==target. plan-entitlements.json
    # is a separate copied file, so plan tokens are untouched.
    if source_brand and source_brand != brand:
        raw = brandlib.rebrand_text(raw, source_brand, brand)
    raw = localize_brand_html(raw, flow_web, brand,
                              conversion_target=conversion_target,
                              local_pages=local_pages)
    return raw


def main():
    ap = argparse.ArgumentParser(description="Assemble a full interactive flow site.")
    ap.add_argument("--lander-dir", default=None,
                    help="Sales page source. Omit for a checkout-only flow.")
    ap.add_argument("--checkout-dir", default=None,
                    help="Checkout source. Omit for a sales-only flow.")
    ap.add_argument("--name", required=True)
    ap.add_argument("--brand", choices=["aipu", "omni"], default=None,
                    help="Target brand to skin the flow as (drives pack/chrome + body rebrand).")
    ap.add_argument("--source-brand", choices=["aipu", "omni"], default=None,
                    help="Brand flavour the source bodies are written in. Default: auto-"
                         "detected per source (neutral sales-pages/checkout-pages -> omni).")
    ap.add_argument("--docroot", default=DEFAULT_DOCROOT)
    ap.add_argument("--flows-dir", default=None)
    ap.add_argument("--register-url", default=DEFAULT_REGISTER_URL,
                    help="Gate destination (Generate / library / KB). Default /register")
    ap.add_argument("--no-optimize", action="store_true",
                    help="Skip asset optimization (image recompress / WebP / minify).")
    ap.add_argument("--flow-base", default=None,
                    help="Shared base slug pairing this flow's per-brand builds "
                         "(<base>__aipu / <base>__omni). Defaults to the build slug.")
    ap.add_argument("--single-page", action="store_true",
                    help="Source is a self-contained multi-page site whose checkout "
                         "is an in-page billing pop-up (no separate checkout page).")
    args = ap.parse_args()

    flows_dir = args.flows_dir or os.path.join(args.docroot, "flows")
    try:
        result = build(args.lander_dir, args.checkout_dir, args.name,
                       args.brand, flows_dir, args.docroot, args.register_url,
                       source_brand=args.source_brand, optimize=not args.no_optimize,
                       flow_base=args.flow_base, single_page=args.single_page)
        print(json.dumps(result))
        return 0
    except Exception as exc:  # surface a clean JSON error to the PHP caller
        import traceback
        print(json.dumps({"ok": False, "error": str(exc), "trace": traceback.format_exc()}))
        return 1


if __name__ == "__main__":
    sys.exit(main())
