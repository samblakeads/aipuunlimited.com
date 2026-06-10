# KK Single-Step (Cloaked)

Single-page KK landers use **`money.php`** on line 1. These are **cloaked conversion pages** — every outgoing link routes through KowboyKit.

**Examples:** `omnirogue-kk/lander7omni/`, `omnirogue-kk/lander11omni/`

See also: [multi-step.md](multi-step.md) · [customer-facing.md](customer-facing.md)

---

## What single-step KK means

A single-step KK lander is a PHP presell page that:

1. Loads `money.php` at the very top (cloaked).
2. Lives as `index.php` inside a folder named after the lander.
3. Uses absolute, folder-named asset paths.
4. Sends **every** outgoing link to `$link['step1link']`.

There is no separate checkout step.

---

## Required PHP header (line 1)

Before `<!doctype html>`:

```php
<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
require_once $base_path.'/includes/money.php';
?>
```

Shorthand (when `kowboykit` path is fixed):

```php
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/kowboykit/includes/money.php'); ?>
```

This defines `$link['step1link']` and the rest of the KK money/link variables.

### Dev-mode fallback (local preview)

```php
<?php
$money_php = $_SERVER['DOCUMENT_ROOT'].'/kowboykit/includes/money.php';
if (file_exists($money_php)) {
    require_once $money_php;
} else {
    if (!isset($multi_page)) {
        $kk_dev_qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
            ? '?' . $_SERVER['QUERY_STRING'] : '';
        $multi_page = ['step1link' => $kk_dev_qs];
    }
    if (!isset($link)) { $link = ['step1link' => '#']; }
    if (!isset($offer)) { $offer = []; }
}
?>
```

---

## File structure

```
/lander-name/index.php
```

**Do NOT use:** `index.html`

---

## Asset paths

Every asset path must be **absolute** and start with `/` followed by the **lander folder name only**.

| File on disk | KK link |
|--------------|---------|
| `assets/css/main.css` | `/lander7omni/assets/css/main.css` |
| `assets/js/main.js` | `/lander7omni/assets/js/main.js` |
| `assets/img/logo.png` | `/lander7omni/assets/img/logo.png` |

**Wrong:** `assets/...`, `./assets/...`, `/omnirogue/lander7/...`, `/multistep-kk/...`

Applies to: `<link>`, `<script>`, `<img>`, favicons, `og:image`, `twitter:image`.

---

## Link rules

Every link that leaves the page must point to the KowboyKit step-1 destination:

```php
<a href="<?= $link['step1link']; ?>">Call to action</a>
```

With plan parameter:

```php
<a href="<?= $link['step1link']; ?>?plan=lifetime399">Lock LIFETIME · $399 →</a>
```

### Hard rule

> There can **never** be an outgoing link on a single-step KK lander that goes anywhere other than a KowboyKit step-1 destination.

Buttons, nav links, footer links (Terms, Privacy, Refund, Contact, Support, DMCA, Affiliates, Press, etc.) and the sticky CTA all resolve to a step1link. No links to `/terms.html`, `/privacy`, `/contact`, `/`, or external sites. The Home nav item and the logo anchor are **dead** (`href="#"`) — they never navigate (Law 5).

### Price-matched plan-token CTAs (automated pipeline)

The Flows pipeline (`scripts/kk_format.py`) classifies every CTA on a single-page package and wires it through `_checkout-offers.php`:

| CTA | Wired to |
|-----|----------|
| Shows a price that equals a token's canonical price (e.g. `$14.99/mo`, `$399 lifetime`) | That plan token: `<?= htmlspecialchars($__kk_offer_links['creatormonthly'] ?? '#'); ?>` |
| "Register to create" phrasing (Start Creating, Generate Now, Create Studio, ...) | `<?= htmlspecialchars($__registercreate ?? '#'); ?>` |
| Everything else that leaves the page (nav, footer, legal, sign-in) | `<?= htmlspecialchars($__registercheckout ?? '#'); ?>` |
| Home nav item / logo anchor | Dead: `href="#"` |
| In-page anchors (`#pricing`, `#faq`) | Kept as-is |

**Price law (Law 1):** a CTA that displays a price may only fire a token whose canonical price matches. A price that matches no token — or matches more than one — **hard-fails the build**; assign the intended token in the flow's CTA config to resolve it. The canonical price table lives in `data/kk-tokens.json`.

### Allowed exceptions

- **In-page anchors:** `href="#pricing"`, `href="#faq"`, `href="#top"`
- **Font stylesheets:** `https://fonts.googleapis.com/...`
- **Tracking pixels / SDK scripts** — not clickable `<a>` links

---

## Conversion checklist

- [ ] Folder named after the lander (e.g. `lander7omni`), file is `index.php`
- [ ] Line 1 loads `money.php` (cloaked page)
- [ ] All asset paths rewritten to `/<folderName>/assets/...`
- [ ] No old folder names left in any path
- [ ] Every `<a href>` is `#anchor`, a plan-token offer link, `$__registercreate`, or `$__registercheckout`
- [ ] Every price-bearing CTA fires the token whose canonical price matches the displayed price
- [ ] Home nav item and logo anchor are dead (`href="#"`)
- [ ] No `.html`, `/terms`, `/privacy`, `/`, or external destinations in links

---

## Quick audit

```bash
grep -rn "/omnirogue/" index.php
grep -n '<a[^>]*href="' index.php
head -1 index.php
```
