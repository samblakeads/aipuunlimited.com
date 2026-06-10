# KK Customer-Facing Pages

Customer-facing pages are **not cloaked**. They load **`safe.php`** instead of `money.php` and let visitors browse Create Studio, legal content, help, and library pages inside the lander without exposing KowboyKit offer wiring.

**Used in:** multi-step KK landers alongside cloaked `index.php` and `checkout.php`. See [multi-step.md](multi-step.md).

See also: [single-step.md](single-step.md) — single-step landers have no customer-facing pages; all links go to `$link['step1link']`.

---

## Cloaked vs customer-facing

| Page type | PHP header | Examples |
|-----------|------------|----------|
| **Cloaked** (conversion) | `money.php` | `index.php`, `checkout.php` |
| **Customer-facing** (browsable) | `safe.php` | `about.php`, `createvideo.php`, `privacy-policy.php`, etc. |

Customer-facing pages must **never** load `money.php`, expose `$offer[...]` tokens, or link directly to KK registration destinations.

---

## Required PHP header (line 1)

Before `<!doctype html>`:

```php
<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
require_once $base_path.'/includes/safe.php';
?>
```

Then include `_kk-config.php` for lander path and tracking variables:

```php
<?php require_once(__DIR__.'/_kk-config.php'); ?>
```

> `_kk-config.php` uses `$multi_page['step1link']` when available from the visitor's session/query string. It does **not** require `money.php`.

---

## Required side pages

Full multi-step KK landers include these customer-facing PHP pages. Reference: `multistep-kk/omnifull-plans-v3-299-kk/`.

| Page | Purpose |
|------|---------|
| `about.php` | About Us |
| `createvideo.php` | Create Studio entry |
| `create-image.php` | Create Studio |
| `create-audio.php` | Create Studio |
| `create-music.php` | Create Studio |
| `create-voice-agents.php` | Create Studio |
| `create-upscale.php` | Create Studio |
| `create-ai-chat.php` | Create Studio |
| `create-omnireels.php` | Create Studio |
| `create-podcast.php` | Create Studio |
| `gpt-library.php` | Library |
| `prompt-library.php` | Library |
| `knowledge-base.php` | Help |
| `help-center.php` | Help |
| `terms-of-service.php` | Legal |
| `privacy-policy.php` | Legal |
| `acceptable-use-policy.php` | Legal |
| `data-deletion-request.php` | Legal |

All use `safe.php` on line 1 — **not** `money.php`.

---

## Asset paths

Same rule as cloaked pages: absolute paths rooted at the lander folder name only.

```html
<link rel="stylesheet" href="/lander-folder-name/assets/main1.css">
<link rel="stylesheet" href="/lander-folder-name/assets/main2.css">
<script src="/lander-folder-name/assets/static.js" defer></script>
```

Never use `./assets/`, parent-directory prefixes (`/multistep-kk/`, `/kk-june8/`), or relative paths.

---

## Identical header and footer

The nav bar and footer must be **identical on every page** in the lander folder — presell, checkout, Create Studio, legal pages.

- Pick one reference page and extract the canonical nav + footer blocks.
- Reuse those same two blocks on all customer-facing pages.
- Do not leave duplicate presell headers alongside the site nav.
- Every page loads the same chrome CSS/JS (`main1.css`, `main2.css`, `static.js`).

### What not to do

- Do not use a simplified nav on legal pages
- Do not point Home or the logo anywhere — they are **dead** (`href="#"`) on every page
- Do not omit nav/footer on any customer-facing page
- Do not edit nav on one page without updating all pages

---

## Nav wiring

Internal links preserve tracking via `$__step1link` from `_kk-config.php`:

```php
<a href="/lander-folder-name/createvideo.php<?= $__step1link; ?>">Create Studio</a>
<a href="/lander-folder-name/about.php<?= $__step1link; ?>">About Us</a>
<a href="#">Home</a>
```

| Nav item | Target |
|----------|--------|
| Home / Logo | **Dead** — `href="#"` on every page (never navigates) |
| Pricing | `/lander-folder-name/checkout.php<?= $__step1link; ?>` |
| Create Studio | `/lander-folder-name/createvideo.php<?= $__step1link; ?>` |
| About | `/lander-folder-name/about.php<?= $__step1link; ?>` |
| Legal pages | Same lander, tracking preserved |

> **Law 5:** the Home nav item and the logo anchor must be `href="#"` on every
> page of every package — presell, checkout and customer-facing alike. They
> either do nothing or refresh in place; they never navigate.

### Purchase CTAs on customer-facing pages

Login, Register, Create Account, and Pricing buttons on customer-facing pages link to the **lander checkout** — not KK offer tokens:

```php
<a href="<?= $__checkout; ?>">Pricing</a>
```

On checkout pages, affiliate popup "Plans" scrolls to the pricing section. On sales/presell pages it goes to checkout.

Customer-facing pages do **not** use `$offer["creatormonthly"]["link"]["step1link"]` or other offer tokens directly.

---

## JavaScript config

Customer-facing pages inject lander globals before `static.js` (no offer links):

```html
<script>
window.__LANDER_BASE=<?= json_encode($__web, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_CHECKOUT_URL=<?= json_encode($__checkout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_STEP1LINK=<?= json_encode($__step1link, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_IS_CHECKOUT=false;
</script>
```

Do not inject `window.__KK_OFFER_LINKS` on customer-facing pages.

---

## Link rules

### Allowed

- Internal lander navigation with `$__step1link` tracking
- Links to presell (`index.php`) and checkout (`checkout.php`) within the lander
- In-page anchors: `#pricing`, `#faq`, `#top`
- Font stylesheets and tracking pixels

### Not allowed

- `money.php` on customer-facing pages
- `$offer[...]` token links
- Hardcoded `/signup?offer=...` or `omnirogue.com/register` URLs
- External registration or pricing destinations
- `$link['step1link']` for nav/footer (that is for cloaked pages only)

---

## Conversion checklist

- [ ] Page loads `safe.php` on line 1 (not `money.php`)
- [ ] `_kk-config.php` included after `safe.php`
- [ ] Asset paths use `/{lander-folder-name}/...` only
- [ ] Nav and footer match all other pages in the lander
- [ ] Home nav item AND logo anchor are dead (`href="#"`)
- [ ] Pricing / Register / Login → `$__checkout`
- [ ] Create Studio links use `$__step1link` tracking
- [ ] No offer tokens or KK registration URLs in HTML
- [ ] `static.js` loaded so nav dropdown and mobile menu work
- [ ] No `.html` files remain

---

## Quick audit

```bash
# Customer-facing pages must NOT load money.php:
grep -l "money.php" about.php createvideo.php privacy-policy.php

# Must load safe.php:
head -5 about.php

# Home / logo anchors must be dead (#):
grep -n '>Home<' about.php createvideo.php   # every hit must be href="#"

# No offer tokens on customer-facing pages:
grep -rn '\$offer\[' about.php createvideo.php privacy-policy.php
```

---

## Verify before launch

1. Open `index.php`, `checkout.php`, `createvideo.php`, and `privacy-policy.php` side by side — nav and footer should match.
2. Confirm customer-facing pages load `safe.php`, cloaked pages load `money.php`.
3. Confirm `assets/static.js` is on every page.
4. Click through nav — tracking params (`?clickid=...`) should persist across pages.
