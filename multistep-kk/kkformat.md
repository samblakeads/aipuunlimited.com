ADDITIONAL KOWBOYKIT FORMAT REQUIREMENTS

Every OmniRogue KK lander must follow the standard KowboyKit format in addition to the Multi-Page and Multi-Offer setup.

====================================================

KK FORMAT REQUIREMENTS

====================================================

Every KK lander must:

1. Be an index.php file (presell page).

2. Have a checkout.php file at the lander root (plan selection / checkout step).

3. Live inside its own lander folder.

4. Load the KowboyKit money include on line 1 of every PHP page.

5. Use KowboyKit offer tokens for all offer links.

6. Preserve click tracking and journey data on presell ↔ checkout links.

7. Follow KowboyKit asset path standards (/lander-name/... only).

====================================================

REQUIRED PHP HEADER

====================================================

The first line of every KK lander must be:

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/kowboykit/includes/money.php'); ?>

This must appear before:

<!DOCTYPE html>

====================================================

FILE STRUCTURE

====================================================

Each lander must use:

/lander-name/index.php
/lander-name/checkout.php

Examples:

/lander7omni/index.php
/lander7omni/checkout.php

/unlimitedv3-pricingv1-kk/index.php
/unlimitedv3-pricingv1-kk/checkout.php

Do NOT use:

index.html
checkout/index.php
checkout/ as the checkout page URL

All KK landers must use PHP.

====================================================

CHECKOUT PAGE

====================================================

Every multi-step KK lander must have a checkout page as a single PHP file at the lander root:

/lander-name/checkout.php

Examples:

/unlimitedv3-pricingv1-kk/checkout.php
/lander7randyaipu-11-omni/checkout.php

Do NOT use:

checkout/index.php
/checkout/ as the checkout page URL
checkout.html

Checkout support assets (CSS bundles, JS bundles, logos) may live in subfolders under the lander (e.g. /lander-name/checkout/plans-pick-your-plan-omni/) — but the checkout page itself must be checkout.php at the lander root.

Required checkout.php header:

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/kowboykit/includes/money.php'); ?>
<?php require_once(__DIR__.'/_checkout-offers.php'); ?>
<?php $__lander = '/lander-folder-name/'; ?>

Notes:

- money.php must be line 1
- _checkout-offers.php is the per-lander offer wiring include (lives in the lander folder alongside checkout.php)
- $__lander is used for back-links to the presell page with tracking preserved

====================================================

PRESELL → CHECKOUT WIRING (index.php)

====================================================

Every presell index.php must define a checkout URL that preserves multi-page tracking:

<?php require_once($_SERVER['DOCUMENT_ROOT'].'/kowboykit/includes/money.php'); ?>
<?php $__checkout = '/lander-folder-name/checkout.php' . ($multi_page['step1link'] ?? ''); ?>

Use $__checkout for all presell CTAs that send the visitor to the plan-selection checkout step:

<a href="<?= $__checkout; ?>">Start for $14.99/mo</a>

Wrong:

<a href="/lander-folder-name/checkout/">...</a>
<a href="/lander-folder-name/checkout/index.php">...</a>

When $multi_page['step1link'] is ?clickid=abc123, the result is:

/lander-folder-name/checkout.php?clickid=abc123

====================================================

MULTI-OFFER CHECKOUT (checkout.php)

====================================================

Checkout pages must use KowboyKit offer tokens for all plan CTAs — never hardcoded /signup or registration URLs.

Include the per-lander offer wiring:

<?php require_once(__DIR__.'/_checkout-offers.php'); ?>

Static plan buttons in HTML:

<a href="<?= htmlspecialchars($__kk_offer_links['creatormonthly'] ?? '#'); ?>">Choose Creator</a>
<a href="<?= htmlspecialchars($__kk_lifetime_link ?? $__kk_offer_links['lifetime'] ?? $__kk_offer_links['lifetimeplan'] ?? ($link['step1link'] ?? '#')); ?>">Get Lifetime</a>

For billing toggles (monthly / yearly), inject links into JavaScript:

<script>
window.__KK_OFFER_LINKS = {
  creatormonthly: <?= json_encode($__kk_offer_links['creatormonthly'] ?? ''); ?>,
  creatoryearly: <?= json_encode($__kk_offer_links['creatoryearly'] ?? ''); ?>,
  studiomonthly: <?= json_encode($__kk_offer_links['studiomonthly'] ?? ''); ?>,
  studioyearly: <?= json_encode($__kk_offer_links['studioyearly'] ?? ''); ?>,
  scalemonthly: <?= json_encode($__kk_offer_links['scalemonthly'] ?? ''); ?>,
  scaleyearly: <?= json_encode($__kk_offer_links['scaleyearly'] ?? ''); ?>,
  lifetime: <?= json_encode($__kk_offer_links['lifetime'] ?? ''); ?>,
  lifetimeplan: <?= json_encode($__kk_offer_links['lifetimeplan'] ?? ''); ?>
};
</script>

Toggle handler must read from window.__KK_OFFER_LINKS — not build /signup?offer= URLs:

function checkoutUrl(token) {
  var links = window.__KK_OFFER_LINKS || {};
  return links[token] || '#';
}

When the billing toggle changes, only the offer token (and resulting KK link) should change.

====================================================

WHICH LINK TO USE

====================================================

Presell CTAs → checkout step:

<?= $__checkout; ?>

Direct plan purchase on presell (specific tier CTAs):

<?= $offer["creatormonthly"]["link"]["step1link"]; ?>
<?= $offer["scalemonthly"]["link"]["step1link"]; ?>
<?= $offer["promonthly"]["link"]["step1link"]; ?>
<?= $offer["lifetimeplan"]["link"]["step1link"]; ?>

Checkout plan CTAs:

<?= htmlspecialchars($__kk_offer_links['creatormonthly'] ?? '#'); ?>

Back to presell (from checkout.php):

<?= $__lander . ($multi_page['step1link'] ?? '') ?>

Contact, sign-in, legal (KK-managed — not checkout):

<?= $link['step1link']; ?>

In-page anchors (allowed):

href="#pricing"
href="#faq"

Do NOT hardcode:

/signup?offer=creatormonthly
https://omnirogue.com/register1
/multistep-kk/{lander}/...
/lander-name/checkout/

====================================================

ASSET PATHS

====================================================

All assets must use absolute paths rooted at the lander folder name only.

Example:

Wrong:

assets/css/main.css

/multistep-kk/unlimitedv3-pricingv1-kk/assets/css/main.css

Correct:

/unlimitedv3-pricingv1-kk/assets/css/main.css

Examples:

<link rel="stylesheet" href="/unlimitedv3-pricingv1-kk/assets/css/main.css">

<script src="/unlimitedv3-pricingv1-kk/assets/js/main.js"></script>

<img src="/unlimitedv3-pricingv1-kk/assets/img/logo.png">

Path rule:

- Start paths with /{lander-folder-name}/
- Do NOT include parent directories such as /multistep-kk/
- Apply the same rule to checkout pages, CSS, JS, images, favicons, and internal page links

This applies to:

- CSS

- JS

- Images

- Favicons

- Open Graph images

- Twitter images

- Logos

- Background images

- Checkout URLs (e.g. /unlimitedv3-pricingv1-kk/checkout.php)

Never use:

./assets/

../assets/

relative paths

/multistep-kk/{lander-folder}/...

====================================================

MULTI PAGE TRACKING

====================================================

money.php provides $multi_page['step1link'] for preserving tracking params (clickid, affiliate, journey, subids).

Every KK page must include `_kk-config.php` after money.php (generated per lander by the build):

<?php require_once(__DIR__.'/_kk-config.php'); ?>

This defines:

- `$__web` — lander web root (e.g. `/omnifull-plans-v3-49-kk`)
- `$__lander` — lander root with trailing slash
- `$__step1link` — `$multi_page['step1link']` for internal links
- `$__checkout` — checkout.php URL with tracking preserved
- `$__registercheckout` — `$offer['registercheckout']['link']['step1link']` only (studio popup → checkout)

Inject KK JavaScript config before static.js on every page:

<script>
window.__LANDER_BASE=<?= json_encode($__web, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_CHECKOUT_URL=<?= json_encode($__checkout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_CHECKOUT=<?= json_encode($offer['registercheckout']['link']['step1link'] ?? '', JSON_UNESCAPED_SLASHES); ?>;
window.__KK_STEP1LINK=<?= json_encode($__step1link, JSON_UNESCAPED_SLASHES); ?>;
</script>

Presell → checkout:

<a href="<?= $__checkout; ?>">Start for $14.99/mo</a>

Internal lander links (index, create studio, library, etc.):

<a href="/lander-folder-name/createvideo.php<?= $__step1link; ?>">Create Studio</a>

Create Studio generate popup → checkout (use registercheckout, not bare checkout.php):

<?= $offer["registercheckout"]["link"]["step1link"]; ?>

static.js uses `omniRegisterCheckoutUrl()` for generate popup / studio conversion CTAs.

Checkout → presell:

<a href="<?= $__lander . ($multi_page['step1link'] ?? '') ?>">Home</a>

Equivalent explicit form:

<a href="/lander-folder-name/checkout.php<?= $multi_page['step1link']; ?>">

<a href="/lander-folder-name/<?= $multi_page['step1link']; ?>">

This preserves:

- clickid

- affiliate tracking

- journey tracking

- subids

====================================================

OMNIROGUE OFFER TOKENS

====================================================

Creator Monthly ($14.99)

<?= $offer["creatormonthly"]["link"]["step1link"]; ?>

Studio Monthly ($29.99)

<?= $offer["studiomonthly"]["link"]["step1link"]; ?>

Premium Monthly ($39.99)

<?= $offer["premiummonthly"]["link"]["step1link"]; ?>

Scale Monthly ($49.99)

<?= $offer["scalemonthly"]["link"]["step1link"]; ?>

Pro Monthly ($99.99)

<?= $offer["promonthly"]["link"]["step1link"]; ?>

Agency Monthly ($149.99)

<?= $offer["agencymonthly"]["link"]["step1link"]; ?>

Pro Max Monthly ($299.99)

<?= $offer["promaxmonthly"]["link"]["step1link"]; ?>

Creator Yearly ($179)

<?= $offer["creatoryearly"]["link"]["step1link"]; ?>

Studio Yearly ($299)

<?= $offer["studioyearly"]["link"]["step1link"]; ?>

Premium Yearly ($398)

<?= $offer["premiumyearly"]["link"]["step1link"]; ?>

Scale Yearly ($499)

<?= $offer["scaleyearly"]["link"]["step1link"]; ?>

Pro Yearly ($998)

<?= $offer["proyearly"]["link"]["step1link"]; ?>

Agency Yearly ($1,499)

<?= $offer["agencyyearly"]["link"]["step1link"]; ?>

Pro Max Yearly ($2,998)

<?= $offer["promaxyearly"]["link"]["step1link"]; ?>

Lifetime ($399)

Token: `lifetime` or `lifetimeplan` (both resolve via `_checkout-offers.php`)

<?= $offer["lifetime"]["link"]["step1link"]; ?>
<!-- or -->
<?= htmlspecialchars($__kk_lifetime_link ?? $__kk_offer_links['lifetime'] ?? $__kk_offer_links['lifetimeplan'] ?? ($link['step1link'] ?? '#')); ?>

Register link to Create Studio: 

Token: 'registercreate'

<?= $offer["registercreate"]["link"]["step1link"]; ?>

Register link to Checkout: 

Token: 'registercheckout'

<?= $offer["registercheckout"]["link"]["step1link"]; ?>

====================================================

OUTGOING LINK RULE

====================================================

For offer buttons, CTA buttons, pricing cards, hero buttons, sticky buttons, and offer links:

Use the appropriate KowboyKit offer token.

Examples:

<a href="<?= $offer["creatormonthly"]["link"]["step1link"]; ?>">

Get Started

</a>

<a href="<?= $offer["creatoryearly"]["link"]["step1link"]; ?>">

Get Started

</a>

Do NOT hardcode:

https://omnirogue.com/register1

https://omnirogue.com/register8

etc.

Always use the offer token.

====================================================

ALLOWED LINKS

====================================================

Allowed:

- KowboyKit offer links

- Multi-page journey links

- In-page anchors

Examples:

href="#pricing"

href="#faq"

href="#top"

====================================================

DO NOT HARDCODE

====================================================

Do not hardcode:

- Registration URLs

- Offer assignments

- Pricing URLs

- Redirect destinations

Use KowboyKit tokens so offers can be rotated and split-tested from KowboyKit.

====================================================

MONTHLY / YEARLY TOGGLE

====================================================

Monthly:

<?= $offer["creatormonthly"]["link"]["step1link"]; ?>

Yearly:

<?= $offer["creatoryearly"]["link"]["step1link"]; ?>

When the billing toggle changes, only the offer token should change.

====================================================

CONVERSION CHECKLIST

====================================================

[ ] index.php exists

[ ] checkout.php exists at lander root (not checkout/index.php)

[ ] Presell uses $__checkout pointing to /lander-name/checkout.php

[ ] checkout.php includes money.php (line 1) and _checkout-offers.php

[ ] checkout.php defines $__lander for back-links

[ ] KowboyKit money include is line 1 on index.php

[ ] Multi-page tracking on presell ↔ checkout links

[ ] Asset paths are absolute (/lander-name/... only)

[ ] No relative asset paths remain

[ ] No parent-folder paths remain (e.g. /multistep-kk/...)

[ ] No hardcoded registration URLs remain (/signup, omnirogue.com/register)

[ ] Offer links use KowboyKit tokens

[ ] Checkout billing toggle uses window.__KK_OFFER_LINKS

[ ] clickid survives presell → checkout → offer transitions

[ ] Monthly offers work

[ ] Yearly offers work

[ ] Lifetime offer works (token: lifetimeplan)

[ ] Offer rotation works

[ ] Attribution survives through purchase

[ ] No /checkout/ folder URLs remain

[ ] No .html landers remain

Every OmniRogue KK lander must follow these standards before deployment.