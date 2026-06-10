#!/usr/bin/env python3
"""Duplicate omnifull multistep landers into aipu-multistep with AIPU branding."""
from __future__ import annotations

import re
import shutil
import sys
from pathlib import Path

HTDOCS = Path(__file__).resolve().parent.parent
MULTISTEP = HTDOCS / "multistep"
AIPU_MULTISTEP = HTDOCS / "aipu-multistep"
AIPU_MULTISTEP_KK = HTDOCS / "aipu-multistep-kk"
AIPU_PAGES = HTDOCS / "aipu-pages"
LOGO_SRC = AIPU_PAGES / "assets" / "logo-aipu.png"

sys.path.insert(0, str(HTDOCS / "scripts"))
from build_plans_and_omnifull import (  # noqa: E402
    extract_site_chrome,
    finalize_omnifull_headers,
    patch_static_js,
    build_kk_copy,
    inject_lander_base_script,
    STATIC_JS_BUST,
)

OMNIFULL_SOURCES = [
    "omnifull-plans-v3-49",
    "omnifull-plans-v3-299",
    "omnifullred-v3a-49",
    "omnifullred-v3-299",
    "omnifullred-v3-v1",
]

LEGAL_PAGES = [
    "terms-of-service.html",
    "privacy-policy.html",
    "acceptable-use-policy.html",
    "data-deletion-request.html",
]

# From aipu-pages/_rebrand.py (+ multistep path renames)
AIPU_REPLACEMENTS = [
    ("/multistep/omnifull", "/aipu-multistep/aipufull"),
    ("omnifull", "aipufull"),
    ("/omnirogue-pages", "/aipu-pages"),
    ("omnirogue-pages", "aipu-pages"),
    ("logo-omnirogue.png", "logo-aipu.png"),
    ("OmniRogue Inc.", "AI Profit University LLC"),
    ("OmniRogue Billing", "AIPU Billing"),
    ("OmniRogue", "AIPU"),
    ("OMNIROGUE AI", "AIPU AI"),
    ("OMNIROGUE", "AIPU"),
    ("omnirogue", "aipu"),
    ("OmniReels", "AIPU Reels"),
    ("support@omnirogue.com", "support@aiprofessionalsuniversity.com"),
    ("1-888-777-4675", "1-(844) 402-4236"),
    ("https://omnirogue.com/billing", "https://app.aiprofessionalsuniversity.com/billing"),
    ("https://omnirogue.com/register", "https://app.aiprofessionalsuniversity.com/register"),
    ("https://omnirogue.com/login", "https://app.aiprofessionalsuniversity.com/login"),
    ("https://omnirogue.com/contact", "https://aiprofessionalsuniversity.com/contact"),
    ("https://omnirogue.com/acceptable-use-policy", "acceptable-use-policy.html"),
    ("https://omnirogue.com", "https://app.aiprofessionalsuniversity.com"),
    (
        "400 N Tampa St Ste 1550 #767523</p><p>Tampa, FL 33602-4719",
        "5036 Dr Phillips Blvd Unit #5224</p><p>Orlando, FL 32819",
    ),
    (
        "400 N Tampa St Ste 1550 #767523</p><p class=\"mb-4\">Tampa, FL 33602-4719",
        "5036 Dr Phillips Blvd Unit #5224</p><p class=\"mb-4\">Orlando, FL 32819",
    ),
    (
        "400 N Tampa St Ste 1550 #767523</p><p>Tampa, FL 33602-4719</p><p>United States",
        "5036 Dr Phillips Blvd Unit #5224</p><p>Orlando, FL 32819</p><p>United States",
    ),
    (
        'AIPU is operated by AI Profit University LLC ("AIPU," "Company," "we," "us," or "our").',
        'AIPU (AI Professionals University) is owned and operated by AI Profit University LLC ("AIPU," "Company," "we," "us," or "our").',
    ),
    (
        'AIPU, a product operated by AI Profit University LLC ("Company," "we," "us," or "our"),',
        'AIPU (AI Professionals University), owned and operated by AI Profit University LLC ("Company," "we," "us," or "our"),',
    ),
    (
        "AI Profit University LLC is the Merchant of Record for AIPU transactions",
        "AI Profit University LLC is the merchant of record for AIPU (AI Professionals University) transactions",
    ),
    (
        "© <!-- -->2026<!-- --> <!-- -->AI Profit University LLC<!-- -->",
        "© <!-- -->2026<!-- --> <!-- -->AI Professionals University<!-- -->",
    ),
    ("'/omnirogue-newpages/'", "'/aipu-pages/'"),
    ("'/omnirogue-newpages'", "'/aipu-pages'"),
    ("'/omnirogue-pages'", "'/aipu-pages'"),
    ("omni-static-fixes", "aipu-static-fixes"),
    ("omni-static-footer", "aipu-static-footer"),
    ("omni-voice-fixes", "aipu-voice-fixes"),
    ("omni-nav-isolation", "aipu-nav-isolation"),
    ("OmniRogue static pages", "AIPU static pages"),
    ("Plans - OmniRogue", "Plans - AIPU"),
    ("Choose your OmniRogue plan", "Choose your AIPU plan"),
]


def aipufull_name(omnifull_name: str) -> str:
    return omnifull_name.replace("omnifull", "aipufull", 1)


def aipu_web_path(folder_name: str) -> str:
    return f"/aipu-multistep/{folder_name}"


def rebrand_text(text: str, web_path: str) -> str:
    for old, new in AIPU_REPLACEMENTS:
        text = text.replace(old, new)
    # Legal cross-links inside this lander folder
    text = text.replace("/aipu-pages/privacy-policy.html", f"{web_path}/privacy-policy.html")
    text = text.replace("/aipu-pages/terms-of-service.html", f"{web_path}/terms-of-service.html")
    text = text.replace("/aipu-pages/acceptable-use-policy.html", f"{web_path}/acceptable-use-policy.html")
    text = text.replace("/aipu-pages/data-deletion-request.html", f"{web_path}/data-deletion-request.html")
    text = text.replace("/aipu-pages/checkout.html", f"{web_path}/checkout.html")
    text = text.replace("/aipu-pages/home.html", f"{web_path}/index.html")
    text = text.replace("/aipu-pages/index.html", f"{web_path}/index.html")
    return text


def extract_main(html: str) -> str | None:
    m = re.search(r"<main\b[^>]*>.*?</main>", html, re.S | re.I)
    return m.group(0) if m else None


def overlay_legal_page(dst_page: Path, aipu_src: Path, web_path: str) -> None:
    """Replace legal page body with AIPU content while keeping lander nav/footer."""
    if not aipu_src.is_file() or not dst_page.is_file():
        return
    dst_html = dst_page.read_text(errors="replace")
    aipu_html = aipu_src.read_text(errors="replace")
    aipu_main = extract_main(aipu_html)
    if not aipu_main:
        return
    aipu_main = rebrand_text(aipu_main, web_path)
    dst_html = re.sub(r"<main\b[^>]*>.*?</main>", aipu_main, dst_html, count=1, flags=re.S | re.I)
    # Title / meta from aipu source
    title_m = re.search(r"<title[^>]*>([^<]+)</title>", aipu_html, re.I)
    if title_m:
        dst_html = re.sub(r"<title[^>]*>[^<]*</title>", title_m.group(0), dst_html, count=1, flags=re.I)
    desc_m = re.search(
        r'<meta name="description" content="[^"]*"[^>]*>',
        aipu_html,
        re.I,
    )
    if desc_m:
        dst_html = re.sub(
            r'<meta name="description" content="[^"]*"[^>]*>',
            desc_m.group(0),
            dst_html,
            count=1,
            flags=re.I,
        )
    dst_page.write_text(dst_html)


def rebrand_folder_tree(folder: Path, web_path: str) -> None:
    exts = {".html", ".js", ".css", ".json"}
    for path in folder.rglob("*"):
        if not path.is_file() or path.suffix not in exts:
            continue
        text = path.read_text(errors="replace")
        out = rebrand_text(text, web_path)
        if out != text:
            path.write_text(out)


def aipufull_kk_name(aipufull_name: str) -> str:
    return f"{aipufull_name}-kk"


def aipu_kk_web_path(aipufull_name: str) -> str:
    return f"/{aipufull_kk_name(aipufull_name)}"


def patch_aipu_static_js(folder: Path, web_path: str) -> None:
    patch_static_js(folder, web_path, php_ext=False)
    static = folder / "assets" / "static.js"
    if not static.is_file():
        return
    text = static.read_text()
    text = text.replace("'OmniReels':", "'AIPU Reels':")
    text = text.replace("('OmniReels',", "('AIPU Reels',")
    text = text.replace("OmniRogue studio", "AIPU studio")
    text = text.replace("the OmniRogue studio", "the AIPU studio")
    text = text.replace("omni-library-", "aipu-library-")
    text = text.replace("omni-lib-upgrade", "aipu-lib-upgrade")
    text = text.replace("omni-gen-", "aipu-gen-")
    text = text.replace("omni-exit-", "aipu-exit-")
    text = text.replace("omni-conv-", "aipu-conv-")
    text = text.replace("omni-dd", "aipu-dd")
    text = text.replace("STORAGE_KEY = 'omnirogue-local-uploads'", "STORAGE_KEY = 'aipu-local-uploads'")
    static.write_text(text)


def build_aipufull_lander(src_name: str) -> Path:
    dst_name = aipufull_name(src_name)
    src = MULTISTEP / src_name
    dst = AIPU_MULTISTEP / dst_name
    web_path = aipu_web_path(dst_name)

    if not src.is_dir():
        raise FileNotFoundError(f"Source not found: {src}")

    if dst.exists():
        shutil.rmtree(dst)
    shutil.copytree(src, dst)

    rebrand_folder_tree(dst, web_path)

    if LOGO_SRC.is_file():
        shutil.copy2(LOGO_SRC, dst / "assets" / "logo-aipu.png")
        root_logo = dst / "logo-aipu.png"
        if root_logo.is_file() or (src / "logo-aipu.png").is_file():
            shutil.copy2(LOGO_SRC, dst / "logo-aipu.png")

    for legal in LEGAL_PAGES:
        overlay_legal_page(dst / legal, AIPU_PAGES / legal, web_path)

    patch_aipu_static_js(dst, web_path)

    chrome_ref = dst / "create-image.html"
    if chrome_ref.is_file():
        site_nav, site_footer = extract_site_chrome(chrome_ref.read_text(errors="replace"))
        finalize_omnifull_headers(dst, web_path, site_nav, site_footer)

    print(f"Built {dst} → https://aipuunlimited.com{web_path}/")
    return dst


def build_aipu_kk_copy(aipufull_name: str) -> None:
    build_kk_copy(
        aipufull_name,
        src_root=AIPU_MULTISTEP,
        dst_root=AIPU_MULTISTEP_KK,
        ms_web_fn=aipu_web_path,
        kk_web_fn=aipu_kk_web_path,
        kk_name_fn=aipufull_kk_name,
        symlink_subdir="aipu-multistep-kk",
    )


def main() -> None:
    AIPU_MULTISTEP.mkdir(parents=True, exist_ok=True)
    AIPU_MULTISTEP_KK.mkdir(parents=True, exist_ok=True)
    built = []
    for name in OMNIFULL_SOURCES:
        src = MULTISTEP / name
        if src.is_dir():
            built.append(build_aipufull_lander(name))
            build_aipu_kk_copy(aipufull_name(name))
        else:
            print(f"Skip missing source: {name}")
    print(f"\nDone — {len(built)} AIPU landers in {AIPU_MULTISTEP}")
    print(f"         {len(built)} AIPU KK landers in {AIPU_MULTISTEP_KK}")
    print("\nHTML test URLs:")
    for d in built:
        wp = aipu_web_path(d.name)
        print(f"  https://aipuunlimited.com{wp}/")
        print(f"  https://aipuunlimited.com{wp}/checkout.html")
    print("\nKK test URLs:")
    for d in built:
        kw = aipu_kk_web_path(d.name)
        print(f"  https://aipuunlimited.com{kw}/")
        print(f"  https://aipuunlimited.com{kw}/checkout.php")


if __name__ == "__main__":
    main()
