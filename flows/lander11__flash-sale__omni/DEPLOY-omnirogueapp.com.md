# OmniRogue Pages — Deploy Guide for omnirogueapp.com

Use this document as instructions for a coding agent setting up the static page pack on **omnirogueapp.com**.

---

## File inventory (`omnirogue-pages`)

### Shared assets (required — upload to `/assets/`)

```
assets/main1.css
assets/main2.css
assets/static.js
assets/logo-omnirogue.png
assets/inter.woff2
```

### Pages — folder routes (only these two)

| Source file | Live path |
|---|---|
| `createvideo.html` | `/createvideo/index.php` |
| `create-image.html` | `/createimage/index.php` |

### Pages — flat files (keep same pattern, rename `.html` → `.php`)

| Source file | Live path |
|---|---|
| `home.html` | **Do not deploy separately** — use as template for root `index.php` |
| `help-center.html` | `/help-center.php` |
| `affiliate.html` | `/affiliate.php` |
| `checkout.html` | `/checkout.php` |
| `gpt-library.html` | `/gpt-library.php` |
| `prompt-library.html` | `/prompt-library.php` |
| `knowledge-base.html` | `/knowledge-base.php` |
| `create-audio.html` | `/create-audio.php` |
| `create-music.html` | `/create-music.php` |
| `create-upscale.html` | `/create-upscale.php` |
| `create-omnireels.html` | `/create-omnireels.php` |
| `create-podcast.html` | `/create-podcast.php` |
| `create-ai-chat.html` | `/create-ai-chat.php` |
| `create-voice-agents.html` | `/create-voice-agents.php` |
| `privacy-policy.html` | `/privacy-policy.php` |
| `terms-of-service.html` | `/terms-of-service.php` |
| `data-deletion-request.html` | `/data-deletion-request.php` |
| `acceptable-use-policy.html` | `/acceptable-use-policy.php` |
| `about.html` | `/about.php` (optional — not in main nav) |

**Skip:** `index.html` (blank placeholder)

### Dev-only scripts (do not upload to live server)

```
_nav.py
_capture.py
_optimize.py
_footer.py
_home_fix.py
```

---

## Target site layout

```
/
├── index.php                         ← KEEP existing file; update HTML to match home.html
├── assets/
│   ├── main1.css
│   ├── main2.css
│   ├── static.js
│   ├── logo-omnirogue.png
│   └── inter.woff2
├── createvideo/
│   └── index.php                     ← from createvideo.html
├── createimage/
│   └── index.php                     ← from create-image.html
├── help-center.php
├── affiliate.php
├── checkout.php
├── gpt-library.php
├── prompt-library.php
├── knowledge-base.php
├── create-audio.php
├── create-music.php
├── create-upscale.php
├── create-omnireels.php
├── create-podcast.php
├── create-ai-chat.php
├── create-voice-agents.php
├── privacy-policy.php
├── terms-of-service.php
├── data-deletion-request.php
└── acceptable-use-policy.php
```

---

## Step 1 — Upload assets

1. Create `/assets/` at the web root.
2. Upload all five files from `omnirogue-pages/assets/`.
3. Confirm these URLs return 200:
   - `https://omnirogueapp.com/assets/main1.css`
   - `https://omnirogueapp.com/assets/main2.css`
   - `https://omnirogueapp.com/assets/static.js`
   - `https://omnirogueapp.com/assets/logo-omnirogue.png`

---

## Step 2 — Global path rewrite

Every source file hardcodes `/omnirogue-pages/`. Replace across **all** PHP files and `static.js`:

| Find | Replace with |
|---|---|
| `/omnirogue-pages/assets/` | `/assets/` |
| `/omnirogue-pages/home.html` | `/` or `/index.php` |
| `/omnirogue-pages/createvideo.html` | `/createvideo/` |
| `/omnirogue-pages/create-image.html` | `/createimage/` |
| `/omnirogue-pages/help-center.html` | `/help-center.php` |
| `/omnirogue-pages/affiliate.html` | `/affiliate.php` |
| `/omnirogue-pages/checkout.html` | `/checkout.php` |
| `/omnirogue-pages/gpt-library.html` | `/gpt-library.php` |
| `/omnirogue-pages/prompt-library.html` | `/prompt-library.php` |
| `/omnirogue-pages/knowledge-base.html` | `/knowledge-base.php` |
| `/omnirogue-pages/create-audio.html` | `/create-audio.php` |
| `/omnirogue-pages/create-music.html` | `/create-music.php` |
| `/omnirogue-pages/create-upscale.html` | `/create-upscale.php` |
| `/omnirogue-pages/create-omnireels.html` | `/create-omnireels.php` |
| `/omnirogue-pages/create-podcast.html` | `/create-podcast.php` |
| `/omnirogue-pages/create-ai-chat.html` | `/create-ai-chat.php` |
| `/omnirogue-pages/create-voice-agents.html` | `/create-voice-agents.php` |
| `/omnirogue-pages/privacy-policy.html` | `/privacy-policy.php` |
| `/omnirogue-pages/terms-of-service.html` | `/terms-of-service.php` |
| `/omnirogue-pages/data-deletion-request.html` | `/data-deletion-request.php` |
| `/omnirogue-pages/acceptable-use-policy.html` | `/acceptable-use-policy.php` |
| `/omnirogue-pages/about.html` | `/about.php` |

Also update the `LIBRARY_LINKS` / sidebar map at the top of `assets/static.js` to use the new paths (especially `/createvideo/` and `/createimage/`).

---

## Step 3 — Update root `index.php` (home page)

**Do not deploy `home.html` as a separate URL.** Use it as the source template.

1. Open existing root `index.php` and source `home.html`.
2. Copy from `home.html` into `index.php`:
   - `<head>` (title, meta, CSS, inline styles)
   - Top navigation HTML
   - Full page body (hero, sections, FAQ, etc.)
   - Footer HTML
   - `<script src="/assets/static.js?v=20260608d" defer></script>` before `</body>`
3. **Keep any existing PHP** in `index.php` (Konnektive tracking, `$offer` vars, includes, etc.). Merge static HTML around that logic — do not delete business logic.
4. **Use local CSS**, not remote omnirogue.com chunks:

```html
<link rel="stylesheet" href="/assets/main1.css">
<link rel="stylesheet" href="/assets/main2.css">
```

Do **not** rely on:
```
https://omnirogue.com/_next/static/chunks/...
```
Those URLs can 404 and break the layout.

---

## Step 4 — Create folder pages (only two)

### `/createvideo/index.php`

1. Copy `createvideo.html` → `createvideo/index.php`
2. Apply all path rewrites from Step 2
3. Ensure head includes:

```html
<link rel="stylesheet" href="/assets/main1.css">
<link rel="stylesheet" href="/assets/main2.css">
```

4. Ensure one script tag before `</body>`:

```html
<script src="/assets/static.js?v=20260608d" defer></script>
```

5. Add KK/tracking PHP at top if the lander uses Konnektive (match whatever root `index.php` already does)

### `/createimage/index.php`

Same steps as createvideo, using `create-image.html` as source.

---

## Step 5 — Deploy flat PHP pages

For every other page in the inventory table:

1. Copy `pagename.html` → `pagename.php` at web root
2. Apply path rewrites from Step 2
3. Include local CSS + `static.js` (same as createvideo steps 3–4)
4. Remove duplicate `static.js` tags if present — keep one versioned include only

---

## Step 6 — Navigation wiring

Expected top nav:

| Label | Target |
|---|---|
| **Home** | `/` or `/index.php` |
| **Create Studio** | `/createvideo/` |
| **Library** (dropdown) | GPT Library → `/gpt-library.php`, Prompt Library → `/prompt-library.php` |
| **Pricing** | `/checkout.php` (or KK checkout URL if injected) |
| **Become Affiliate** | `/affiliate.php` |
| **Help** | `/help-center.php` |

**Logo click** → same as Home (`/`).

`assets/static.js` runs `wireTopNav()` on load and rewrites:
- `Home` → `{base}/index.php`
- `Pricing` → checkout URL
- `Help` → `/help-center.php`
- `Become Affiliate` → `/affiliate.php`

After path rewrites, verify hardcoded `href` values in HTML **and** that `wireTopNav()` still matches link labels exactly (`Home`, `Pricing`, `Help`, `Become Affiliate`).

**Library dropdown** (`initLibraryDropdown` in `static.js`) maps create-studio sidebar items. Update paths to flat `.php` files and folder routes:

- AI Video → `/createvideo/`
- Image → `/createimage/`
- Audio → `/create-audio.php`
- Music → `/create-music.php`
- Upscale → `/create-upscale.php`
- OmniReels → `/create-omnireels.php`
- Podcast → `/create-podcast.php`
- AI Chat → `/create-ai-chat.php`
- Voice Agents → `/create-voice-agents.php`

---

## Step 7 — Footer links

Apply same path rewrites in footer HTML on every page:

- Create Studio → `/createvideo/`
- Voice Agents → `/create-voice-agents.php`
- Knowledge Base → `/knowledge-base.php`
- Pricing → `/checkout.php`
- Community → `/help-center.php`
- Legal pages → respective `.php` files

---

## Step 8 — Testing checklist

- [ ] `/` — home renders correctly (dark theme, nav, footer)
- [ ] `/assets/main1.css` and `/assets/main2.css` load (200)
- [ ] `/assets/logo-omnirogue.png` loads
- [ ] **Home** nav → `/`
- [ ] **Create Studio** nav → `/createvideo/`
- [ ] **Library** dropdown opens; GPT + Prompt links work
- [ ] **Pricing** → `/checkout.php`
- [ ] **Become Affiliate** → `/affiliate.php`
- [ ] **Help** → `/help-center.php`
- [ ] `/createvideo/` renders with sidebar + composer
- [ ] `/createimage/` renders with sidebar + composer
- [ ] View Source: no remaining `/omnirogue-pages/` strings
- [ ] Mobile nav menu works
- [ ] KK tracking params preserved on internal links (if applicable)

---

## Common pitfalls

1. **Leaving `/omnirogue-pages/` in paths** — breaks CSS, JS, images, and nav.
2. **Remote CSS from omnirogue.com** — can 404; always use `/assets/main1.css` and `/assets/main2.css`.
3. **Deploying `home.html` separately** — duplicate home; only update `index.php`.
4. **Duplicate `static.js` includes** — keep one versioned tag per page.
5. **Only createvideo and createimage get folders** — everything else stays as flat `.php` at root.

---

## Suggested agent task order

1. Upload `/assets/`
2. Rewrite paths in `assets/static.js`
3. Build `/createvideo/index.php` and `/createimage/index.php`
4. Deploy flat `.php` pages from remaining HTML files
5. Merge `home.html` into existing root `index.php`
6. Full link crawl + visual check (desktop + mobile)

---

## One-line agent prompt (copy/paste)

```
Deploy omnirogue-pages on omnirogueapp.com at web root. Keep existing index.php as home — update its HTML to match home.html. Only createvideo and createimage use folders (/createvideo/index.php, /createimage/index.php); all other pages are flat .php files at root. Upload assets/ (main1.css, main2.css, static.js, logo-omnirogue.png, inter.woff2). Replace every /omnirogue-pages/ path with root paths per DEPLOY-omnirogueapp.com.md. Use local CSS not omnirogue.com remote chunks. Wire nav: Home→/, Create Studio→/createvideo/, Pricing→/checkout.php, Affiliate→/affiliate.php, Help→/help-center.php. Update static.js LIBRARY_LINKS and verify wireTopNav().
```
