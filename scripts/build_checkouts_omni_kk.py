#!/usr/bin/env python3
"""Duplicate checkouts-omni landers into checkouts-omni-kk with KK offer wiring."""
from __future__ import annotations

import re
import shutil
import sys
from pathlib import Path

HTDOCS = Path(__file__).resolve().parent.parent
SRC_ROOT = HTDOCS / "checkouts-omni"
DST_ROOT = HTDOCS / "checkouts-omni-kk"
KKFORMAT_SRC = HTDOCS / "multistep-kk" / "kkformat.md"

KK_HEADER = (
    "<?php\n"
    "$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';\n"
    "$base_path = $_SERVER['DOCUMENT_ROOT'].\"/\".(\n"
    "    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)[\"location\"] : 'kowboykit'\n"
    ");\n"
    "require_once $base_path.'/includes/money.php';\n"
    "?>\n"
    "<?php require_once(__DIR__.'/_checkout-offers.php'); ?>\n"
)

CHECKOUT_OFFERS_PHP = """<?php
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
    'creatormonthly', 'studiomonthly', 'premiummonthly', 'scalemonthly',
    'promonthly', 'agencymonthly', 'promaxmonthly',
    'creatoryearly', 'studioyearly', 'premiumyearly', 'scaleyearly',
    'proyearly', 'agencyyearly', 'promaxyearly',
    'lifetime', 'lifetimeplan',
];
$__kk_offer_links = [];
foreach ($__kk_offer_tokens as $__t) {
    $__kk_offer_links[$__t] = __kk_offer_step1link($__t);
}
$__kk_lifetime_link = __kk_offer_step1link('lifetime');
unset($__t);
"""

KK_OFFER_SCRIPT = (
    "<script>\n"
    "window.__KK_OFFER_LINKS = <?php echo json_encode($__kk_offer_links, JSON_UNESCAPED_SLASHES); ?>;\n"
    "</script>\n"
)

LIFETIME_HREF_PHP = (
    'href="<?= htmlspecialchars($__kk_lifetime_link ?? '
    "$__kk_offer_links['lifetime'] ?? $__kk_offer_links['lifetimeplan'] ?? "
    "($link['step1link'] ?? '#')); ?>\""
)

PLAN_ORDERS = {
    "299": {
        "monthly": ["creatormonthly", "premiummonthly", "promaxmonthly", "agencymonthly"],
        "yearly": ["creatoryearly", "premiumyearly", "promaxyearly", "agencyyearly"],
    },
    "49": {
        "monthly": ["creatormonthly", "scalemonthly", "agencymonthly", "promonthly"],
        "yearly": ["creatoryearly", "scaleyearly", "agencyyearly", "proyearly"],
    },
    "sam": {
        "monthly": ["creatormonthly", "premiummonthly", "agencymonthly", "promonthly"],
        "yearly": ["creatoryearly", "premiumyearly", "agencyyearly", "proyearly"],
    },
    "v2": {
        "monthly": ["creatormonthly", "studiomonthly", "scalemonthly", "agencymonthly"],
        "yearly": ["creatoryearly", "studioyearly", "scaleyearly", "agencyyearly"],
    },
}

SKIP_NAMES = {"logo-omnirogue.png", "logo-omnirogue.webp", "planinstructions.md"}


def kk_name(src_name: str) -> str:
    return f"{src_name}-kk"


def kk_web(src_name: str) -> str:
    return f"/{kk_name(src_name)}"


def plan_order_key(src_name: str) -> str:
    if "299" in src_name:
        return "299"
    if "v2-sam" in src_name or src_name == "lifetime-create":
        return "v2"
    if "sam" in src_name or "fixed" in src_name:
        return "sam"
    return "49"


def rewrite_paths(html: str, src_name: str) -> str:
    kk = kk_name(src_name)
    web = kk_web(src_name)
    html = html.replace(f"/checkouts/{src_name}/", f"{web}/")
    html = html.replace("/checkouts/logo-omnirogue.png", f"{web}/logo-omnirogue.png")
    html = html.replace("/checkouts/logo-omnirogue.webp", f"{web}/logo-omnirogue.webp")
    return html


def patch_plan_ctas(html: str, src_name: str) -> str:
    order = PLAN_ORDERS[plan_order_key(src_name)]
    tokens = order["monthly"] + order["yearly"]
    idx = 0

    def repl(m: re.Match) -> str:
        nonlocal idx
        token = tokens[idx] if idx < len(tokens) else "creatormonthly"
        idx += 1
        extra = m.group(1)
        label = m.group(2)
        return (
            f'<a href="<?= htmlspecialchars($__kk_offer_links[\'{token}\'] ?? \'#\'); ?>" '
            f'class="pcol-cta{extra}">{label}</a>'
        )

    return re.sub(
        r'<button class="pcol-cta([^"]*)">([^<]*)</button>',
        repl,
        html,
    )


def patch_links_script(html: str) -> str:
    html = re.sub(
        r"<script>\s*\(function \(\) \{\s*var LINKS = \{.*?\};",
        KK_OFFER_SCRIPT + "<script>\n(function () {\n  var LINKS = window.__KK_OFFER_LINKS || {};",
        html,
        count=1,
        flags=re.S,
    )
    html = html.replace(
        "      if (!btn || !tok || !LINKS[tok]) return;\n"
        "      btn.onclick = function () { window.location.href = LINKS[tok]; };",
        "      if (!btn || !tok || !LINKS[tok] || LINKS[tok] === '#') return;\n"
        "      if (btn.tagName === 'A') { btn.href = LINKS[tok]; }\n"
        "      else { btn.onclick = function () { window.location.href = LINKS[tok]; }; }",
    )
    return html


def patch_pricing_v1(html: str) -> str:
    html = html.replace(
        "function checkoutUrl(token) { return '/signup?offer=' + encodeURIComponent(token); }",
        "function checkoutUrl(token) {\n"
        "    var links = window.__KK_OFFER_LINKS || {};\n"
        "    return links[token] || '#';\n"
        "  }",
    )
    for token in [
        "creatormonthly", "studiomonthly", "scalemonthly",
        "creatoryearly", "studioyearly", "scaleyearly", "lifetime",
    ]:
        html = html.replace(
            f'href="/signup?offer={token}"',
            f'href="<?= htmlspecialchars($__kk_offer_links[\'{token}\'] ?? \'#\'); ?>"',
        )
    html = re.sub(r'href="/signup\?offer=lifetime"', LIFETIME_HREF_PHP, html)
    html = re.sub(
        r'href="/signup\?offer=([^"]+)"',
        r'href="<?= htmlspecialchars($__kk_offer_links[\'\1\'] ?? \'#\'); ?>"',
        html,
    )
    if "window.__KK_OFFER_LINKS = <?php" not in html:
        html = html.replace("<script>\n(function () {", KK_OFFER_SCRIPT + "<script>\n(function () {", 1)
    return html


def patch_signup_hrefs(html: str) -> str:
    html = re.sub(r'href="/signup\?offer=lifetime"', LIFETIME_HREF_PHP, html)
    html = re.sub(
        r'href="/signup\?offer=([^"]+)"',
        r'href="<?= htmlspecialchars($__kk_offer_links[\'\1\'] ?? \'#\'); ?>"',
        html,
    )
    return html


def convert_index(html: str, src_name: str) -> str:
    html = rewrite_paths(html, src_name)
    html = patch_signup_hrefs(html)
    if src_name == "pricing-v1":
        html = patch_pricing_v1(html)
    else:
        html = patch_links_script(html)
        html = patch_plan_ctas(html, src_name)
    if "<!DOCTYPE" in html or "<!doctype" in html:
        html = re.sub(r"<!DOCTYPE html>", "", html, count=1, flags=re.I)
        html = re.sub(r"<!doctype html>", "", html, count=1, flags=re.I)
    return KK_HEADER + html.lstrip()


def copy_lander(src_name: str) -> Path:
    src_dir = SRC_ROOT / src_name
    dst_dir = DST_ROOT / kk_name(src_name)
    if dst_dir.exists():
        shutil.rmtree(dst_dir)
    shutil.copytree(
        src_dir,
        dst_dir,
        ignore=shutil.ignore_patterns("*.bak*", "*.react-bak", "index.html.bak*"),
    )
    index_html = src_dir / "index.html"
    if not index_html.exists():
        raise FileNotFoundError(f"missing index.html in {src_name}")
    php = convert_index(index_html.read_text(encoding="utf-8", errors="replace"), src_name)
    (dst_dir / "index.php").write_text(php, encoding="utf-8")
    (dst_dir / "index.html").unlink(missing_ok=True)
    (dst_dir / "_checkout-offers.php").write_text(CHECKOUT_OFFERS_PHP, encoding="utf-8")
    if src_name == "lifetime-create" and not (dst_dir / "logo-omnirogue.png").exists():
        logo = SRC_ROOT / "logo-omnirogue.png"
        if logo.exists():
            shutil.copy2(logo, dst_dir / "logo-omnirogue.png")
    return dst_dir


def link_at_webroot(kk_folder: str) -> None:
    link = HTDOCS / kk_folder
    target = DST_ROOT / kk_folder
    if link.is_symlink():
        if link.resolve() == target.resolve():
            return
        link.unlink()
    elif link.exists():
        return
    link.symlink_to(target)


def main() -> int:
    if not SRC_ROOT.is_dir():
        print(f"Missing source: {SRC_ROOT}", file=sys.stderr)
        return 1

    DST_ROOT.mkdir(parents=True, exist_ok=True)
    shutil.copy2(KKFORMAT_SRC, DST_ROOT / "kkformat.md")
    for extra in ("planinstructions.md", "logo-omnirogue.png", "logo-omnirogue.webp"):
        src = SRC_ROOT / extra
        if src.exists():
            shutil.copy2(src, DST_ROOT / extra)

    landers = sorted(
        p.name for p in SRC_ROOT.iterdir()
        if p.is_dir() and p.name not in SKIP_NAMES
    )
    if not landers:
        print("No checkout landers found", file=sys.stderr)
        return 1

    for name in landers:
        dst = copy_lander(name)
        link_at_webroot(kk_name(name))
        print(f"built {dst} -> {kk_web(name)}/index.php")

    print(f"\nDone: {len(landers)} KK checkout landers in {DST_ROOT}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
