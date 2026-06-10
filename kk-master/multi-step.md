# KK Multi-Step (Cloaked)

Multi-step KK landers use **`money.php`** on line 1 for all **cloaked conversion pages** — `index.php` (presell) and `checkout.php` (plan selection). These pages wire KowboyKit offer tokens and preserve click tracking through the funnel.

**Examples:** `multistep-kk/omnifull-plans-v3-299-kk/`, `checkouts-omni-kk/plans-v3-299-kk/`, `kk-june8/pricing-v2-kk1/`

See also: [single-step.md](single-step.md) · [customer-facing.md](customer-facing.md)

> HTML multistep landers (`multistep/multistepplan.md`) are the design/reference pass. KK multi-step is the production checkout wiring pass.

---

## The 7 KK Laws (canonical compliance checklist)

Every KK package — multi-step, sales-only or checkout-only — must satisfy all seven laws. The automated pipeline (`scripts/kk_format.py` + `scripts/qa_checks.py`) **hard-fails the build and blocks the download** on any violation.

1. **CTA correctness.** Every outgoing CTA resolves to a KK destination: a plan offer token whose **canonical price matches the price displayed** on the card/CTA, a register-to-create step1link (`registercreate`), or a register-to-billing step1link (`registercheckout`). Canonical token prices live in `data/kk-tokens.json`.
2. **Index with content.** Every package has an `index.php` whose rendered body is real content — never empty or header-only.
3. **One page = `index.php`.** A one-page flow (sales page alone OR checkout alone) is hosted entirely in `index.php`. No `checkout.php` may exist in a single-page package.
4. **Multi-step naming.** In a multi-step package the sales page is `index.php` and the plan picker is `checkout.php`, both at the package root.
5. **Home is dead.** The Home nav item AND the logo anchor are `href="#"` on every page. They never navigate.
6. **No outgoing links.** No `<a href>` may point to an external site. All redirects happen inside the package folder. CDN **assets** (`<link>`/`<script>`/`<img>` — fonts, pixels) are fine; clickable links are not.
7. **Self-contained.** No file (`.php`, `.css`, `.js`) may reference a path outside `/{lander-folder-name}/`. No shared-collection paths, no dev-server paths, no `url()` escapes. CDN assets are fine.

---

## Cloaked pages vs customer-facing pages

| Page | Header | Type |
|------|--------|------|
| `index.php` | `money.php` | Cloaked presell |
| `checkout.php` | `money.php` | Cloaked checkout |
| `about.php`, `createvideo.php`, legal pages, etc. | `safe.php` | Customer-facing — see [customer-facing.md](customer-facing.md) |

Only presell and checkout load `money.php`. Brand, legal, Create Studio, and library pages load `safe.php`.

---

## Requirements

Every multi-step KK lander must:

1. Have `index.php` (cloaked presell) and `checkout.php` (cloaked checkout) at the lander root.
2. Live inside its own lander folder.
3. Load `money.php` on line 1 of **cloaked pages only**.
4. Use KowboyKit offer tokens for all plan/offer links on cloaked pages.
5. Preserve click tracking and journey data on presell ↔ checkout links.
6. Follow asset path standards (`/{lander-folder-name}/...` only).
7. Be self-contained — both `index.php` and `checkout.php` in the same folder.

---

## Required PHP header (cloaked pages)

Before `<!doctype html>` on `index.php` and `checkout.php`:

```php
<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
require_once $base_path.'/includes/money.php';
?>
```

Then include `_kk-config.php`:

```php
<?php require_once(__DIR__.'/_kk-config.php'); ?>
```

On `checkout.php`, also include:

```php
<?php require_once(__DIR__.'/_checkout-offers.php'); ?>
```

---

## File structure

```
/lander-name/
├── index.php              ← cloaked presell
├── checkout.php           ← cloaked checkout
├── _kk-config.php         ← tracking vars (all page types)
├── _checkout-offers.php   ← offer wiring (checkout only)
├── about.php              ← customer-facing (safe.php) — see customer-facing.md
├── createvideo.php        ← customer-facing
├── … (other brand/legal pages)
└── assets/
```

**Do NOT use:** `index.html`, `checkout.html`, `checkout/index.php`, `/checkout/` as URL

Checkout support assets may live in subfolders (e.g. `/lander-name/checkout/plans-pick-your-plan-omni/`) but the checkout page itself must be `checkout.php` at the lander root.

---

## Asset paths

All assets use absolute paths rooted at the lander folder name only.

| Wrong | Correct |
|-------|---------|
| `assets/css/main.css` | `/unlimitedv3-pricingv1-kk/assets/css/main.css` |
| `/multistep-kk/unlimitedv3-pricingv1-kk/assets/...` | `/unlimitedv3-pricingv1-kk/assets/...` |
| `/kk-june8/pricing-v2-kk1/assets/...` | `/pricing-v2-kk1/assets/...` |

Never use `./assets/`, `../assets/`, or parent-directory prefixes in URLs.

---

## Which link to use (cloaked pages)

| Purpose | Link |
|---------|------|
| Presell CTAs → checkout | `<?= $__checkout; ?>` |
| Direct plan purchase on presell | `<?= $offer["creatormonthly"]["link"]["step1link"]; ?>` |
| Checkout plan CTAs | `<?= htmlspecialchars($__kk_offer_links['creatormonthly'] ?? '#'); ?>` |
| Back to presell (checkout.php) | `<?= $__lander . ($multi_page['step1link'] ?? '') ?>` |
| Contact, sign-in, legal (KK-managed) | `<?= $link['step1link']; ?>` |
| Create Studio popup → checkout | `<?= $offer["registercheckout"]["link"]["step1link"]; ?>` |

**Do NOT hardcode:** `/signup?offer=...`, `https://omnirogue.com/register1`, `/multistep-kk/{lander}/...`, `/lander-name/checkout/`

### Allowed

- KowboyKit offer links
- Multi-page journey links (`$__checkout`, `$__step1link`)
- In-page anchors: `#pricing`, `#faq`, `#top`

---

## Presell → checkout wiring

`_kk-config.php` defines:

- `$__web` — lander web root (e.g. `/omnifull-plans-v3-49-kk`)
- `$__lander` — lander root with trailing slash
- `$__step1link` — `$multi_page['step1link']` for internal links
- `$__checkout` — `checkout.php` URL with tracking preserved
- `$__registercheckout` — studio popup → checkout
- `$__is_checkout` — `true` on checkout, `false` on presell

```php
<a href="<?= $__checkout; ?>">Start for $14.99/mo</a>
```

When `$multi_page['step1link']` is `?clickid=abc123`:

```
/lander-folder-name/checkout.php?clickid=abc123
```

---

## Checkout page

### Static plan buttons

```php
<a href="<?= htmlspecialchars($__kk_offer_links['creatormonthly'] ?? '#'); ?>">Choose Creator</a>
<a href="<?= htmlspecialchars($__kk_lifetime_link ?? $__kk_offer_links['lifetime'] ?? $__kk_offer_links['lifetimeplan'] ?? ($link['step1link'] ?? '#')); ?>">Get Lifetime</a>
```

### Billing toggles (monthly / yearly)

```html
<script>
window.__KK_OFFER_LINKS = {
  creatormonthly: <?= json_encode($__kk_offer_links['creatormonthly'] ?? ''); ?>,
  creatoryearly: <?= json_encode($__kk_offer_links['creatoryearly'] ?? ''); ?>,
  studiomonthly: <?= json_encode($__kk_offer_links['studiomonthly'] ?? ''); ?>,
  studioyearly: <?= json_encode($__kk_offer_links['studioyearly'] ?? ''); ?>,
  scalemonthly: <?= json_encode($__kk_offer_links['scalemonthly'] ?? ''); ?>,
  scaleyearly: <?= json_encode($__kk_offer_links['scaleyearly'] ?? ''); ?>,
  promonthly: <?= json_encode($__kk_offer_links['promonthly'] ?? ''); ?>,
  proyearly: <?= json_encode($__kk_offer_links['proyearly'] ?? ''); ?>,
  premiummonthly: <?= json_encode($__kk_offer_links['premiummonthly'] ?? ''); ?>,
  premiumyearly: <?= json_encode($__kk_offer_links['premiumyearly'] ?? ''); ?>,
  agencymonthly: <?= json_encode($__kk_offer_links['agencymonthly'] ?? ''); ?>,
  agencyyearly: <?= json_encode($__kk_offer_links['agencyyearly'] ?? ''); ?>,
  promaxmonthly: <?= json_encode($__kk_offer_links['promaxmonthly'] ?? ''); ?>,
  promaxyearly: <?= json_encode($__kk_offer_links['promaxyearly'] ?? ''); ?>,
  lifetime: <?= json_encode($__kk_offer_links['lifetime'] ?? ''); ?>,
  lifetimeplan: <?= json_encode($__kk_offer_links['lifetimeplan'] ?? ''); ?>
};
</script>
```

Toggle handler — **not** `/signup?offer=`:

```javascript
function checkoutUrl(token) {
  var links = window.__KK_OFFER_LINKS || {};
  return links[token] || '#';
}
```

---

## JavaScript config (cloaked pages)

Inject before `static.js`:

```html
<script>
window.__LANDER_BASE=<?= json_encode($__web, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_CHECKOUT_URL=<?= json_encode($__checkout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_CHECKOUT=<?= json_encode($offer['registercheckout']['link']['step1link'] ?? '', JSON_UNESCAPED_SLASHES); ?>;
window.__KK_STEP1LINK=<?= json_encode($__step1link, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_IS_CHECKOUT=<?= json_encode($__is_checkout ?? false); ?>;
</script>
```

`static.js` uses `omniRegisterCheckoutUrl()` for generate popup / studio conversion CTAs.

---

## OmniRogue offer tokens

### Monthly

| Plan | Price | Token |
|------|-------|-------|
| Creator | $14.99 | `creatormonthly` |
| Studio | $29.99 | `studiomonthly` |
| Premium | $39.99 | `premiummonthly` |
| Scale | $49.99 | `scalemonthly` |
| Pro | $99.99 | `promonthly` |
| Agency | $149.99 | `agencymonthly` |
| Pro Max | $299.99 | `promaxmonthly` |

### Yearly

| Plan | Price | Token |
|------|-------|-------|
| Creator (Promo) | $99 | `creatoryearly` |
| Studio | $299 | `studioyearly` |
| Premium | $398 | `premiumyearly` |
| Scale | $499 | `scaleyearly` |
| Pro | $998 | `proyearly` |
| Agency | $1,499 | `agencyyearly` |
| Pro Max | $2,998 | `promaxyearly` |

### Special

| Purpose | Token |
|---------|-------|
| Lifetime ($399) | `lifetime` or `lifetimeplan` |
| Register → Create Studio | `registercreate` |
| Register → Checkout | `registercheckout` |

PHP usage: `<?= $offer["creatormonthly"]["link"]["step1link"]; ?>`

Both `lifetime` and `lifetimeplan` resolve via `_checkout-offers.php` with cross-fallback.

---

## Plan names, prices & points

Use exact plan names when labeling cards and CTAs. Only show points when the design has a points/credits field. Lifetime is not part of the monthly/yearly toggle.

### pricing-v1 mapping

| UI Label | Monthly | Yearly | Token (mo) | Token (yr) |
|----------|---------|--------|------------|------------|
| Creator | $14.99/mo | $99/yr | `creatormonthly` | `creatoryearly` |
| Studio | $29.99/mo | $299/yr | `studiomonthly` | `studioyearly` |
| Scale | $49.99/mo | $499/yr | `scalemonthly` | `scaleyearly` |
| Lifetime | $399 | — | `lifetime` | — |

---

============================================================
REGISTER BUTTON ROUTING RULE
============================================================

The special tokens `registercreate` and `registercheckout` are register-page destinations.

They are not plan tokens.

They are not replacements for normal plan/product offer links.

Plan buttons and product buttons must always go to their own individual KowboyKit offer links.

============================================================
SPECIAL REGISTER BUTTONS
============================================================

There are two special register buttons:

1. `registercreate`
2. `registercheckout`

Meaning:

- `registercreate` = Register page that opens/starts the Create Studio flow
- `registercheckout` = Register page that opens/starts the billing/checkout flow

Use these only when the button is specifically a register-style CTA.

============================================================
WHEN TO USE registercreate
============================================================

Use `registercreate` when the CTA should take the visitor to registration and then into Create Studio.

Examples:

- Register to Create
- Start Creating
- Open Create Studio
- Launch Studio
- Generate Now
- Try Create Studio
- Create Video
- Create Image
- Start Generating

PHP:

<a href="<?= htmlspecialchars($__registercreate ?? '#'); ?>">Start Creating</a>

============================================================
WHEN TO USE registercheckout
============================================================

Use `registercheckout` when the CTA should take the visitor to registration and then into billing/checkout.

Examples:

- Register
- Create Account
- Sign Up
- Continue to Billing
- Unlock Access
- Join Now
- Start Checkout
- Get Started
- Choose Plan

PHP:

<a href="<?= htmlspecialchars($__registercheckout ?? '#'); ?>">Create Account</a>

============================================================
PLAN / PRODUCT BUTTON RULE
============================================================

Normal plan and product buttons must still use their own individual offer links.

Correct:

<a href="<?= htmlspecialchars($__kk_offer_links['creatormonthly'] ?? '#'); ?>">Choose Creator</a>
<a href="<?= htmlspecialchars($__kk_offer_links['studiomonthly'] ?? '#'); ?>">Choose Studio</a>
<a href="<?= htmlspecialchars($__kk_offer_links['scalemonthly'] ?? '#'); ?>">Choose Scale</a>
<a href="<?= htmlspecialchars($__kk_offer_links['lifetime'] ?? $__kk_offer_links['lifetimeplan'] ?? '#'); ?>">Get Lifetime</a>

Wrong:

<a href="<?= htmlspecialchars($__registercreate ?? '#'); ?>">Choose Creator</a>
<a href="<?= htmlspecialchars($__registercheckout ?? '#'); ?>">Choose Studio</a>

============================================================
UPDATED _kk-config.php VARIABLES
============================================================

Add these to `_kk-config.php`:

$__registercreate = $offer['registercreate']['link']['step1link'] ?? $__checkout;
$__registercheckout = $offer['registercheckout']['link']['step1link'] ?? $__checkout;

============================================================
UPDATED JAVASCRIPT CONFIG
============================================================

Add these before `static.js`:

<script>
window.__KK_REGISTER_CREATE=<?= json_encode($__registercreate, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_CHECKOUT=<?= json_encode($__registercheckout, JSON_UNESCAPED_SLASHES); ?>;
</script>

Frontend usage:

- Use `window.__KK_REGISTER_CREATE` for register buttons that open Create Studio
- Use `window.__KK_REGISTER_CHECKOUT` for register buttons that open billing/checkout

============================================================
UPDATED _checkout-offers.php
============================================================

Add these tokens to `$__kk_offer_tokens`:

'registercreate',
'registercheckout',

These are special register button destinations.

They should not replace normal plan/product tokens.

============================================================
AUDIT CHECKLIST
============================================================

- `registercreate` is documented as register → Create Studio
- `registercheckout` is documented as register → billing/checkout
- Plan/product buttons still use their own individual offer links
- `registercreate` is not used for normal plan/product buttons
- `registercheckout` is not used for normal plan/product buttons
- Create Studio register buttons use `$__registercreate`
- Billing/checkout register buttons use `$__registercheckout`
- No hardcoded `/signup?offer=registercreate` links exist
- No hardcoded `/signup?offer=registercheckout` links exist
- No hardcoded external register URLs exist

## HTML → KK conversion

1. `index.html` → `index.php` with `money.php` + `_kk-config.php`
2. `checkout.html` → `checkout.php` with `money.php` + `_kk-config.php` + `_checkout-offers.php`
3. Brand `.html` pages → `.php` with `safe.php` (see [customer-facing.md](customer-facing.md))
4. Rewrite asset paths: `/multistep/{lander}/` → `/{lander}/`
5. Symlink at `htdocs` root if nested in a parent folder

```bash
python3 /var/www/aipuunlimited.com/htdocs/scripts/build_checkouts_omni_kk.py
```

### Deployment symlinks

When landers live in a parent folder but must serve at `/{lander-name}/`:

```bash
ln -s kk-june8/pricing-v2-kk1 /var/www/aipuunlimited.com/htdocs/pricing-v2-kk1
```

Asset paths must use `/{lander-name}/...` — never `/kk-june8/`, `/multistep-kk/`, etc.

---

## Conversion checklist

- [ ] `index.php` and `checkout.php` exist at lander root
- [ ] Both cloaked pages load `money.php` on line 1
- [ ] `_kk-config.php` on cloaked pages; `_checkout-offers.php` on checkout
- [ ] Presell uses `$__checkout` → `/lander-name/checkout.php`
- [ ] Asset paths absolute (`/lander-name/...` only)
- [ ] No hardcoded `/signup` or `omnirogue.com/register` URLs
- [ ] Checkout billing toggle uses `window.__KK_OFFER_LINKS`
- [ ] clickid survives presell → checkout → offer
- [ ] Customer-facing pages use `safe.php` (not `money.php`)
- [ ] Root symlink exists if nested
- [ ] No `.html` landers remain

---

## Quick audit

```bash
grep -rn "/kk-june8/" .
grep -rn "/multistep-kk/" .
grep -rn '/signup?offer=' .
grep -rn 'href="assets/' .
head -1 index.php checkout.php
```

---

## Appendix: generated PHP templates

### `_kk-config.php`

```php
<?php
$__web              = '/lander-folder-name';
$__lander           = $__web . '/';
$__step1link        = $multi_page['step1link'] ?? '';
$__checkout         = $__lander . 'checkout.php' . $__step1link;
$__registercheckout = $offer['registercheckout']['link']['step1link'] ?? '';
$__is_checkout      = false; // true on checkout.php
```

### `_checkout-offers.php`

```php
<?php
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
```
