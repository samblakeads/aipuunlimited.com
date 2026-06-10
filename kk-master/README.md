# KK Master Docs

KowboyKit (KK) format instructions split by page type.

| Doc | Page type | PHP header | Purpose |
|-----|-----------|------------|---------|
| [single-step.md](single-step.md) | Cloaked presell | `money.php` | One-page lander — all CTAs → `$link['step1link']` |
| [multi-step.md](multi-step.md) | Cloaked funnel | `money.php` | Presell + checkout + offer tokens + tracking |
| [customer-facing.md](customer-facing.md) | Brand / legal / studio | `safe.php` | Browsable site pages inside the lander — no offer cloaking |

**Rule:** Pages that load `money.php` are **cloaked** conversion pages. Pages that load `safe.php` are **customer-facing** and must never expose KK offer wiring or registration destinations.

Compiled from all KK format instructions on this server — **2026-06-09**.
