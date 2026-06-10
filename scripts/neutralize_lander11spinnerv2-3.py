#!/usr/bin/env python3
"""
Neutralize lander11spinnerv2-3 into a brand-neutral, kk-free sales page.

Source : /lander11spinnerv2-3/index.php   (AIPU-branded, KowboyKit PHP)
Target : /sales-pages/lander11spinnerv2-3/index.html  (static, brand-neutral)

Two jobs:
  1. Strip KowboyKit ("kk") formatting  -> renders standalone in the previews UI
  2. Strip AIPU branding                -> brand is chosen later at Create Flow
"""
import re
import sys

SRC = "/var/www/aipuunlimited.com/htdocs/lander11spinnerv2-3/index.php"
DST = "/var/www/aipuunlimited.com/htdocs/sales-pages/lander11spinnerv2-3/index.html"

html = open(SRC, encoding="utf-8").read()
orig = html

def sub_once(pattern, repl, label, flags=0):
    global html
    new, n = re.subn(pattern, repl, html, flags=flags)
    if n == 0:
        sys.exit(f"!! NO MATCH for: {label}")
    html = new
    print(f"  [{n}x] {label}")

# ---- 1. KK FORMATTING -------------------------------------------------------

# 1a. Drop the KowboyKit money.php require (line 1) entirely.
sub_once(r"^<\?php require_once\(\$_SERVER\['DOCUMENT_ROOT'\]\.'/kowboykit/includes/money\.php'\); \?>\n",
         "", "kk money.php require -> removed")

# 1b. Dead kk CTA token  <?= $link['step1link']; ?>  -> neutral placeholder href.
sub_once(r"<\?=\s*\$link\['step1link'\];\s*\?>", "#", "kk $link['step1link'] -> #")

# 1c. Hard-coded brand-specific external operator checkout -> neutral placeholder.
sub_once(r'href="https://www\.fanbasis\.com/agency-checkout/omnirogue-inc/3j0OO\?src=lander11spinnerv2"',
         'href="#"', "fanbasis operator link -> #")

# ---- 2. BRANDING ------------------------------------------------------------

# 2a. <title> + meta description.
sub_once(r"Spin To Lock Your \$14\.99/mo AIPU Founder Rate",
         "Spin To Lock Your $14.99/mo Founder Rate", "title AIPU -> removed")
sub_once(r"Spin the AIPU Prize Vault and lock \$14\.99/mo",
         "Spin the AI Prize Vault and lock $14.99/mo", "meta-desc AIPU -> AI")

# 2b. Favicon: repath to this folder; drop the AIPU apple-touch logo entirely.
sub_once(r'href="/lander11spinnerv2-3/assets/favicon\.svg"',
         'href="/sales-pages/lander11spinnerv2-3/assets/favicon.svg"', "favicon path")
sub_once(r'<link rel="apple-touch-icon" href="/lander11spinnerv2-3/assets/logo-aipu-mark\.png">\n',
         "", "apple-touch-icon (AIPU mark) -> removed")

# 2c. Remove the entire brand-row header (AIPU logo). Brand is chosen at Create Flow.
sub_once(r"  <!-- Brand row -->\n  <div class=\"brand-row\">.*?  </div>\n\n",
         "", "brand-row header -> removed", flags=re.S)

# 2d. Hero sub copy.
sub_once(r"Spin the AIPU Prize Vault and lock in",
         "Spin the AI Prize Vault and lock in", "hero sub AIPU -> AI")

# 2e. Wheel hub label + claim code.
sub_once(r'<div class="hub-label">AIPU</div>',
         '<div class="hub-label">WIN</div>', "hub-label AIPU -> WIN")
sub_once(r'<b id="claimCode">AIPU-FOUNDER</b>',
         '<b id="claimCode">VAULT-FOUNDER</b>', "claim code AIPU-FOUNDER -> VAULT-FOUNDER")

# 2f. Tier product names / white-label copy.
sub_once(r"<h3>AIPU University Lifetime</h3>",
         "<h3>AI University Lifetime</h3>", "tier h3 AIPU University (all)")
html = html.replace("<h3>AIPU University Lifetime</h3>", "<h3>AI University Lifetime</h3>")
sub_once(r"White-label AIPU under your brand",
         "White-label the platform under your brand", "white-label AIPU -> platform")

# 2g. Trust line legal reference.
sub_once(r"you agree to AIPU's terms",
         "you agree to our terms", "trust AIPU's terms -> our terms")

# 2h. Remove the whole site footer (AIPU logo + legal + brand links).
sub_once(r"  <!-- ============ SITE FOOTER ============ -->\n  <footer class=\"site-foot\">.*?  </footer>\n\n",
         "", "site footer -> removed", flags=re.S)

# 2i. Internal analytics campaign label -> neutral (fbq is unused/guarded anyway).
sub_once(r"content_category: 'lander11spinnerv2'",
         "content_category: 'spin_to_win'", "fb content_category (all)")
html = html.replace("content_category: 'lander11spinnerv2'", "content_category: 'spin_to_win'")

assert html != orig, "no changes made"
open(DST, "w", encoding="utf-8").write(html)
print(f"\nWrote {DST} ({len(html)} bytes)")
