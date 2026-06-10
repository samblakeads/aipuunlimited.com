# KK Format — Landing Page Instructions

This document describes the **KowboyKit (KK) format** that every landing page in a
`-kk` folder must follow. Any new or duplicated lander must be converted to this
format before it goes live.

## What "KK Format" means

A KK-formatted lander is a PHP page that:

1. Loads the KowboyKit money/link helper at the very top.
2. Lives as an `index.php` inside a folder named after the lander.
3. Uses **absolute, folder-named** asset paths.
4. Sends **every** outgoing link to the single KowboyKit destination, `$link['step1link']`.

---

## 1. Required PHP header (must be line 1)

Put this on the **very first line** of every `index.php`, before `<!doctype html>`:

```php
<?php require_once($_SERVER['DOCUMENT_ROOT'].'/kowboykit/includes/money.php'); ?>
```

This include defines `$link['step1link']` (and the rest of the KK money/link
variables) used throughout the page.

## 2. The file must be `index.php`

- Each lander is a folder containing a single `index.php`.
- Never use `.html` for a KK lander — it must be `.php` so the PHP header runs.
- Example folders in this project:
  - `omnirogue-kk/lander7omni/index.php`
  - `omnirogue-kk/lander11omni/index.php`

## 3. File paths must contain the lander folder name (with a leading slash)

Every asset path (CSS, JS, images, favicons, OG images, etc.) must be **absolute**
and start with `/` followed by the **name of the folder the `index.php` lives in**.

> Rule: if `index.php` is in `omnirogue-kk/lander7omni/`, then a file like
> `assets/css/main.css` must be linked as `/lander7omni/assets/css/main.css`
> (note the leading slash).

Examples:

| File on disk (relative to the lander folder) | KK link to use                         |
| -------------------------------------------- | -------------------------------------- |
| `assets/css/main.css`                        | `/lander7omni/assets/css/main.css`     |
| `assets/js/main.js`                          | `/lander7omni/assets/js/main.js`       |
| `assets/img/logo-omnirogue-h.png`            | `/lander7omni/assets/img/logo-omnirogue-h.png` |
| `assets/logo-omnirogue-h.png` (lander11)     | `/lander11omni/assets/logo-omnirogue-h.png` |

Do **not** use relative paths (`assets/...`, `./assets/...`, `../...`) and do
**not** leave any old folder name in the path (e.g. `/omnirogue/lander7/...`).

This applies everywhere a path appears, including:

- `<link rel="stylesheet" href="...">`
- `<link rel="icon" ...>` / `<link rel="apple-touch-icon" ...>`
- `<script src="...">`
- `<img src="...">`
- `og:image` / `twitter:image` meta tags

## 4. Outgoing links must always be `$link['step1link']`

Every link that leaves the page must point to the KowboyKit step-1 destination:

```php
<a href="<?= $link['step1link']; ?>">Call to action</a>
```

If a link needs a plan parameter, append it after the variable:

```php
<a href="<?= $link['step1link']; ?>?plan=lifetime399">Lock LIFETIME · $399 →</a>
```

### The hard rule

> **There can NEVER be an outgoing link on a KK lander that goes anywhere other
> than `<?= $link['step1link']; ?>`.**

This means buttons, nav links, footer links (Terms, Privacy, Refund, Contact,
Support, DMCA, Affiliates, Press, etc.), the logo link, and the sticky CTA all
point to `$link['step1link']`. There are **no** links to `/terms.html`,
`/privacy`, `/contact`, `/`, external sites, etc.

### Allowed exceptions (NOT outgoing navigation)

These are fine to keep because they don't navigate the user off-page:

- **In-page anchors**: `href="#pricing"`, `href="#faq"`, `href="#top"`, etc.
- **Font stylesheets**: `https://fonts.googleapis.com/...`, `https://fonts.gstatic.com`.
- **Tracking pixels / SDK scripts** (image beacons and `<script src>` such as the
  Meta/Facebook pixel) — these are not clickable `<a>` links.

---

## Conversion checklist (use for every new KK lander)

- [ ] Folder named after the lander (e.g. `lander7omni`), file is `index.php`.
- [ ] Line 1 is the `require_once(... /kowboykit/includes/money.php)` PHP header.
- [ ] All asset paths rewritten to `/<folderName>/assets/...` (leading slash).
- [ ] No old folder names left in any path (e.g. no `/omnirogue/...`).
- [ ] Every `<a href>` is either an in-page `#anchor` or `<?= $link['step1link']; ?>`.
- [ ] No `.html`, `/terms`, `/privacy`, `/`, or external destinations in any link.
- [ ] Plan variants use `<?= $link['step1link']; ?>?plan=...`.

## Quick audit commands

From the lander folder, these should return **no** results:

```bash
# Any leftover old folder paths:
grep -rn "/omnirogue/" index.php

# Any outgoing links that are not step1link or in-page anchors
# (review output: every <a href> must be "#..." or "<?= $link['step1link']; ?>"):
grep -n '<a[^>]*href="' index.php
```

And this should return the header on line 1:

```bash
head -1 index.php
```
