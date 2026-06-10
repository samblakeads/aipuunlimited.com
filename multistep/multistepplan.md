# Multistep Lander Plan

How to build a **multistep lander** for OmniRogue (HTML version — no KowboyKit).

For the PHP/KK checkout variant, see `multistep-kk/kkformat.md`.

---

## What a multistep lander is

A multistep lander is a **self-contained folder** under `/multistep/` that looks like the real product site:

1. **`index.html`** — the presell / landing page (long-form sales page with CTAs)
2. **`checkout.html`** — plan selection and purchase step
3. **Brand `.html` pages** — Create Studio, About, Privacy Policy, Terms, Help Center, etc.

Every page shares the **same top nav and footer** so visitors can browse Create Studio, legal pages, and pricing as if they were on the live app — while all purchase CTAs stay inside the funnel.

The nav **Home** link must point to **`index.html`** (the presell), **not** `home.html`.

---

## Identical header and footer (required)

**The nav bar and footer must be byte-for-byte identical on every page** in the lander folder — presell, checkout, Create Studio, legal pages, everything.

Visitors must never see a different logo placement, missing nav items, or a footer with different links depending on which page they opened.

### One canonical chrome block

1. **Pick one reference page** after path rewrites (e.g. `create-image.html`).
2. **Extract exactly two HTML blocks** from it:
   - **Header / nav** — from `<div class="fixed top-0 left-0 right-0 z-50">` through the closing `</nav></div>`
   - **Footer** — from `<footer class="relative border-t border-border bg-background">` through `</footer>`
3. **Reuse those same two blocks on every page** — do not hand-edit nav per page.

The build script does this automatically via `extract_site_chrome()` and `wrap_with_site_chrome()` in `scripts/build_plans_and_omnifull.py`.

### Pages that must get the same chrome

| Page | Notes |
|------|--------|
| `index.html` (presell) | Strip the presell’s own `<header>` / `<footer>` first, then inject site nav + footer |
| `checkout.html` | Strip checkout’s duplicate header if present; same nav + footer as all other pages |
| All Create Studio pages | Already include nav/footer from `omnirogue-pages` — paths must match the lander folder |
| Library, About, Help, Legal | Same nav + footer as Create Studio — no simplified or stripped-down variants |

### Shared assets (every page)

Every `.html` file in the lander must load the **same chrome styles and script**:

```html
<link rel="stylesheet" href="/multistep/{lander-name}/assets/main1.css">
<link rel="stylesheet" href="/multistep/{lander-name}/assets/main2.css">
<script src="/multistep/{lander-name}/assets/static.js" defer></script>
```

Plus the shared `omni-static-fixes` style block (nav height, font stack for chrome). Presell and checkout use the same nav/footer CSS; only the **body padding** differs (presell uses `.omni-lander-wrap`, checkout adds extra top padding for the plan picker).

### What not to do

- Do **not** leave the presell’s original standalone header/footer alongside the site nav
- Do **not** use the checkout page’s own mini-header instead of the full site nav
- Do **not** edit nav links on one page without updating **all** pages
- Do **not** link Home to `home.html` on some pages and `index.html` on others
- Do **not** omit nav/footer on legal or Create Studio pages “to save space”

### How to verify before launch

1. Open `index.html`, `checkout.html`, `createvideo.html`, and `privacy-policy.html` side by side — nav and footer should look and behave the same (same links, same logo, same mobile menu).
2. Grep the lander folder for the nav opening tag and confirm the **href values inside the nav block are identical** across files (only the page body between nav and footer should differ).
3. Confirm `assets/static.js` is included on every page so the Library dropdown and mobile nav work everywhere.

If you change the nav or footer once, **re-sync it to every `.html` file** in the folder (or re-run the build script).

---

## Three source folders (OmniRogue)

| Piece | Source folder | What you take |
|-------|---------------|---------------|
| **Brand pages + nav chrome** | `/omnirogue-pages/` (template copy: `/multistep/omnirogue-plans/`) | All `.html` pages except `home.html`, `index.html`, `checkout.html`, and `_*.py` scripts |
| **Presell (becomes index.html)** | `/omnirogue/` | A lander folder, e.g. `lander7randy3upgrades-omni/index.php` |
| **Checkout** | `/checkouts/` | A checkout variant, e.g. `plans-v3-fixed/index.html` + `plans-pick-your-plan/` bundle |

### Available presell sources (`/omnirogue/`)

- `lander7randy3upgrades-omni` — default AOV funnel (used by build script)
- `lander7upgrades-omni`
- `lander7upgrades-omniunlimited`
- `lander7randyunlimited-omni`
- `lander7randyaipu-11-omni`
- Others in `/omnirogue/` as needed

### Available checkout sources (`/checkouts/`)

| Folder | Use case |
|--------|----------|
| `plans-v3-fixed` | Standard 3-tier monthly/yearly picker |
| `plans-v3-49` | Featured upgrade tier = Premium $49.99 |
| `plans-v3-299` | Featured upgrade tier = Agency $299.99 |
| `plans-v3-sam` | Sam variant entitlements |
| `plans-v2-sam` | 2-tier Sam variant |
| `pricing-v1` | Lifetime + 3 recurring tiers |
| `lifetime-create` | Lifetime-focused checkout |

See `/checkouts/planinstructions.md` for plan names, prices, and offer tokens.

---

## Target folder structure

Each finished lander lives at:

```
/multistep/{lander-name}/
├── index.html              ← presell (from /omnirogue/…)
├── checkout.html           ← from /checkouts/…
├── plans-pick-your-plan/   ← checkout JS/CSS bundle (copied from checkouts)
├── logo-aipu.png           ← checkout logo (if used)
├── about.html
├── createvideo.html        ← Create Studio entry
├── create-image.html
├── create-audio.html
├── create-music.html
├── create-upscale.html
├── create-omnireels.html
├── create-podcast.html
├── create-ai-chat.html
├── create-voice-agents.html
├── gpt-library.html
├── prompt-library.html
├── knowledge-base.html
├── help-center.html
├── privacy-policy.html
├── terms-of-service.html
├── acceptable-use-policy.html
├── data-deletion-request.html
└── assets/
    ├── css/                ← presell styles (from omnirogue lander)
    ├── js/
    ├── img/
    ├── fonts/              ← self-hosted Inter subsets
    ├── main1.css           ← site nav chrome (from omnirogue-pages)
    ├── main2.css
    └── static.js           ← nav dropdown + sidebar wiring
```

**Do not ship `home.html`.** The presell **is** the home page (`index.html`).

---

## Step-by-step assembly

### 1. Pick a lander name

Use a descriptive slug, e.g.:

- `omnifull-plans-v3-299`
- `lander3upgrades-plans2-plans-v3-sam`
- `my-new-omni-offer-v1`

Web path will be `/multistep/{lander-name}/`.

### 2. Copy brand pages

Start from `/omnirogue-pages/` (or the template at `/multistep/omnirogue-plans/`):

```bash
cp -r /omnirogue-pages /multistep/{lander-name}
```

Remove files that are replaced later:

- `home.html`
- `index.html`
- `checkout.html`
- `_*.py` (build helpers)

### 3. Rewrite all paths to the lander folder

Every reference to `/omnirogue-pages/` must become `/multistep/{lander-name}/`.

Also replace:

| Old | New |
|-----|-----|
| `/omnirogue-pages/home.html` | `/multistep/{lander-name}/index.html` |
| `href="/home.html"` | `href="/multistep/{lander-name}/index.html"` |
| Logo `href` in nav | `/multistep/{lander-name}/index.html` |

**Rule:** no path should still say `omnirogue-pages` or `home.html` when done.

### 4. Add presell assets

Copy presell CSS/JS/images from the chosen `/omnirogue/{lander}/assets/` into:

```
/multistep/{lander-name}/assets/css/
/multistep/{lander-name}/assets/js/
/multistep/{lander-name}/assets/img/
```

### 5. Build `index.html` from the presell

Take the body of `/omnirogue/{lander}/index.php` and save as `index.html`:

- Strip the PHP header (`<?php require_once … ?>`)
- Replace all asset paths: `/omnirogue/{lander}/` → `/multistep/{lander-name}/`
- Point every presell CTA at **`/multistep/{lander-name}/checkout.html`**
- Update `og:url`, `canonical`, and `og:image` to the new folder

Wrap the presell with the **site nav + footer** taken from any brand page (e.g. `create-image.html`):

- **Use the exact same nav and footer HTML** that every other page in the lander will use (see [Identical header and footer](#identical-header-and-footer-required))
- Inject nav above the presell content
- Inject footer below
- Add `main1.css`, `main2.css`, and nav padding styles in `<head>`
- Strip the presell’s own duplicate `<header>` / `<footer>` if present — never show two headers

Reference implementation: `scripts/build_plans_and_omnifull.py` → `extract_site_chrome()` + `wrap_with_site_chrome()`.

### 6. Install checkout

From `/checkouts/{checkout-name}/`:

1. Copy `plans-pick-your-plan/` → `/multistep/{lander-name}/plans-pick-your-plan/`
2. Copy `index.html` → `/multistep/{lander-name}/checkout.html`
3. Rewrite paths: `/checkouts/{checkout-name}/` → `/multistep/{lander-name}/`
4. Wrap checkout with the **same** site nav + footer as `index.html` and all brand pages (checkout mode adds top padding for the plan picker only — nav/footer markup stays identical)

Presell CTAs and nav **Pricing / Create Account / Login** should all land on `checkout.html` inside the lander — not on `omnirogue.com`.

### 7. Localize external OmniRogue links

On every `.html` page, rewrite outbound links:

| Link type | Target |
|-----------|--------|
| Privacy, Terms, About, Help, Knowledge Base, Acceptable Use, Data Deletion | `/multistep/{lander-name}/{page}.html` |
| Login, Register, Billing, Contact, Pricing (external) | `/multistep/{lander-name}/checkout.html` |
| Create Studio sidebar items | `/multistep/{lander-name}/createvideo.html` etc. |

Remove `<link>` tags that preload `omnirogue.com` fonts or `_next` chunks; self-host fonts under `/assets/fonts/` instead.

Reference: `localize_omnirogue_html()` in `scripts/build_plans_and_omnifull.py`.

### 8. Patch `assets/static.js`

The shared nav script must know this lander’s base path:

- `omniBasePath()` should return `/multistep/{lander-name}` when the URL contains that folder
- Sidebar nav map entries must use `/multistep/{lander-name}/create-*.html`
- **Home** wiring: replace `/home.html` with `/index.html`
- Library dropdown → `gpt-library.html`, `prompt-library.html` under the lander path

Reference: `patch_static_js()` in `scripts/build_plans_and_omnifull.py`.

### 9. Nav checklist (every page)

The nav and footer must match on **all** pages. Top nav should contain working links to:

| Nav item | File |
|----------|------|
| **Home** (logo + Home) | `index.html` |
| **Create Studio** | `createvideo.html` |
| **Library** (dropdown) | `gpt-library.html`, `prompt-library.html` |
| **About Us** | `about.html` |
| **Pricing** | `checkout.html` |
| **Create Account** | `checkout.html` |
| **Login** | `checkout.html` |

Footer should link to Create Studio pages, `help-center.html`, contact (→ checkout or local page), and legal pages.

---

## Worked examples in this repo

| Lander | Presell source | Checkout source |
|--------|----------------|-----------------|
| `/multistep/omnifull-plans-v3-299/` | `omnirogue/lander7randy3upgrades-omni` | `checkouts/plans-v3-299` |
| `/multistep/omnifull-plans-v3-49/` | same presell | `checkouts/plans-v3-49` |
| `/multistep/lander3upgrades-plans2/` | embedded presell in folder | `checkout/checkout.html` from checkouts |

Browse `/multistep/omnifull-plans-v3-299/index.html` to see a finished lander.

---

## Automated build (recommended)

Instead of hand-assembling, run the build script for the standard omnifull funnels:

```bash
python3 /scripts/build_plans_and_omnifull.py
```

This script:

1. Copies `omnirogue-pages` into `/multistep/omnifull-plans-v3-49` and `…-299`
2. Merges presell from `omnirogue/lander7randy3upgrades-omni`
3. Installs checkout from `checkouts/plans-v3-*`
4. Rewrites paths, wraps nav/footer, patches `static.js`, and builds `index.html`

Use this as the reference when creating new lander names manually.

---

## Template folder: `/multistep/omnirogue-plans/`

This is a **copy of `/omnirogue-pages/`** parked inside `multistep/` as the brand-pages starting point.

When building a new lander:

1. Duplicate `omnirogue-plans` → `multistep/{your-lander-name}`
2. Follow steps 3–9 above to add presell + checkout + path rewrites

Do **not** deploy `omnirogue-plans` directly as a live URL — it still has `/omnirogue-pages/` paths and no presell `index.html`.

---

## Pre-flight checklist

Before publishing `/multistep/{lander-name}/`:

- [ ] **Header and footer are identical** on `index.html`, `checkout.html`, Create Studio, and legal pages (same nav links, logo, footer columns)
- [ ] No duplicate presell or checkout headers under the site nav
- [ ] `main1.css`, `main2.css`, and `static.js` load on every page
- [ ] `index.html` exists and is the presell (not `home.html`)
- [ ] Nav logo + **Home** → `/multistep/{lander-name}/index.html`
- [ ] `checkout.html` exists with plan picker working
- [ ] All presell CTAs → `checkout.html`
- [ ] No remaining `/omnirogue-pages/` or `/home.html` paths
- [ ] No live links to `omnirogue.com` for login/register/pricing (local checkout instead)
- [ ] Legal pages open locally (`privacy-policy.html`, `terms-of-service.html`, etc.)
- [ ] Create Studio pages open from nav and sidebar
- [ ] `assets/static.js` base path matches the lander folder
- [ ] Favicon and logo load from `/multistep/{lander-name}/assets/…`

---

## KK (PHP) variant

If the lander needs KowboyKit offer tokens and `checkout.php`, build the HTML lander first, then convert to PHP per `multistep-kk/kkformat.md`:

- `index.html` → `index.php` with `money.php` on line 1
- `checkout.html` → `checkout.php` with `_checkout-offers.php`
- All `.html` brand pages → `.php`
- Folder moves to `/multistep-kk/{lander-name}-kk/` or gets a root symlink

The HTML multistep lander is always the design/reference pass; KK is the production checkout wiring pass.
