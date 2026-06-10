#!/usr/bin/env python3
"""Rebrand omnirogue-pages copy to AIPU across all aipu-pages files."""
import re
from pathlib import Path

BASE = Path(__file__).resolve().parent

# Ordered replacements (more specific patterns first)
REPLACEMENTS = [
    # Paths and assets
    ("/omnirogue-pages", "/aipu-pages"),
    ("omnirogue-pages", "aipu-pages"),
    ("logo-omnirogue.png", "logo-aipu.png"),
    # Company / legal entity
    ("OmniRogue Inc.", "AI Profit University LLC"),
    ("OmniRogue Billing", "AIPU Billing"),
    # Brand name (after Inc. replacement)
    ("OmniRogue", "AIPU"),
    ("OMNIROGUE AI", "AIPU AI"),
    ("OMNIROGUE", "AIPU"),
    ("omnirogue", "aipu"),
    # Product names
    ("OmniReels", "AIPU Reels"),
    # Contact
    ("support@omnirogue.com", "support@aiprofessionalsuniversity.com"),
    ("1-888-777-4675", "1-(844) 402-4236"),
    # URLs
    ("https://omnirogue.com/billing", "https://app.aiprofessionalsuniversity.com/billing"),
    ("https://omnirogue.com/register", "https://app.aiprofessionalsuniversity.com/register"),
    ("https://omnirogue.com/login", "https://app.aiprofessionalsuniversity.com/login"),
    ("https://omnirogue.com/contact", "https://aiprofessionalsuniversity.com/contact"),
    ("https://omnirogue.com/acceptable-use-policy", "/aipu-pages/acceptable-use-policy.html"),
    ("https://omnirogue.com", "https://app.aiprofessionalsuniversity.com"),
    # Address blocks (OmniRogue Tampa -> AIPU Orlando)
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
    # Company info intro (terms)
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
    # Footer copyright entity
    ("© <!-- -->2026<!-- --> <!-- -->AI Profit University LLC<!-- -->", "© <!-- -->2026<!-- --> <!-- -->AI Professionals University<!-- -->"),
    # Static JS base path helper
    ("'/omnirogue-newpages/'", "'/aipu-pages/'"),
    ("'/omnirogue-newpages'", "'/aipu-pages'"),
    ("'/omnirogue-pages'", "'/aipu-pages'"),
    # CSS/style ids (cosmetic consistency)
    ("omni-static-fixes", "aipu-static-fixes"),
    ("omni-static-footer", "aipu-static-footer"),
    ("omni-voice-fixes", "aipu-voice-fixes"),
    ("omni-library-", "aipu-library-"),
    ("OmniRogue static pages", "AIPU static pages"),
]

EXTENSIONS = {".html", ".py", ".js", ".css"}


def rebrand_text(text: str) -> str:
    for old, new in REPLACEMENTS:
        text = text.replace(old, new)
    return text


def main():
    updated = []
    for path in sorted(BASE.rglob("*")):
        if not path.is_file():
            continue
        if path.suffix not in EXTENSIONS:
            continue
        if path.name == "_rebrand.py":
            continue
        raw = path.read_text(encoding="utf-8")
        out = rebrand_text(raw)
        if out != raw:
            path.write_text(out, encoding="utf-8")
            updated.append(str(path.relative_to(BASE)))
    print(f"Updated {len(updated)} files:")
    for name in updated:
        print(f"  {name}")


if __name__ == "__main__":
    main()
