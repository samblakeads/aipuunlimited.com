# Plan Details — OmniRogue / AIPU

Reference for plan names, prices, and points. Use these values when wiring checkout pages and KowboyKit offer tokens.

## Instructions

- **Only include points on the checkout UI when the design has a points/credits field.** The [pricing-v1](https://omnirogue.com/pricing-v1) layout does not show points — use plan names and prices only there.
- Match **exact plan names** from this file when labeling cards, CTAs, and offer tokens.
- **Monthly / Yearly toggle** must swap both the displayed plan name suffix (Monthly ↔ Yearly) and the price.
- **Lifetime** is a one-time plan, not part of the monthly/yearly toggle.

---

## Monthly Plans

| Plan Name | Price | Points |
|-----------|-------|--------|
| Creator Plan Monthly | $14.99/month | 1,000 Points |
| Studio Plan Monthly | $29.99/month | 2,500 Points |
| Premium Access Monthly | $39.99/month | 3,500 Points |
| Scale Plan Monthly | $49.99/month | 5,000 Points |
| App Pro Plan Monthly | $99.99/month | 10,000 Points |
| App Agency Plan Monthly | $149.99/month | 10,000 Points |
| App Pro Max Plan Monthly | $299.99/month | 30,000 Points |

---

## Yearly Plans

| Plan Name | Price | Points |
|-----------|-------|--------|
| Creator Plan Yearly | $179/year | 12,000 Points |
| Creator Plan Yearly (Promo) | $99/year | 12,000 Points |
| Studio Plan Yearly | $299/year | 30,000 Points |
| Premium Access Yearly | $398/year | 42,000 Points |
| Scale Plan Yearly | $499/year | 60,000 Points |
| App Pro Plan Yearly | $998/year | 120,000 Points |
| App Agency Plan Yearly | $1,499/year | 120,000 Points |
| App Pro Max Plan Yearly | $2,998/year | 360,000 Points |

---

## Lifetime

| Plan Name | Price | Points |
|-----------|-------|--------|
| AIPU Creator Lifetime Plan | $399 One-Time | 10,000 Points |

---

## pricing-v1 checkout mapping

The [pricing-v1](https://omnirogue.com/pricing-v1) design shows **Lifetime** plus three recurring tiers with a Monthly/Yearly toggle:

| UI Label | Monthly plan name | Monthly price | Yearly plan name | Yearly price | KK offer token (monthly) | KK offer token (yearly) |
|----------|-------------------|---------------|------------------|--------------|--------------------------|-------------------------|
| Creator | Creator Plan Monthly | $14.99/mo | Creator Plan Yearly (Promo) | $99/yr | `creatormonthly` | `creatoryearly` |
| Studio | Studio Plan Monthly | $29.99/mo | Studio Plan Yearly | $299/yr | `studiomonthly` | `studioyearly` |
| Scale | Scale Plan Monthly | $49.99/mo | Scale Plan Yearly | $499/yr | `scalemonthly` | `scaleyearly` |
| Lifetime Access | AIPU Creator Lifetime Plan | $399 one-time | — | — | `lifetime` | — |

### Strike / anchor prices (pricing-v1 design)

| Tier | Monthly strike | Monthly sale | Yearly strike | Yearly sale |
|------|----------------|--------------|---------------|-------------|
| Creator | $29/mo | $14.99/mo | $179/yr | $99/yr |
| Studio | $59/mo | $29.99/mo | $359/yr | $299/yr |
| Scale | $99/mo | $49.99/mo | $599/yr | $499/yr |
| Lifetime | $1,900 | $399 | — | — |

---

## Full offer token list (KowboyKit)

| Token | Plan | Price |
|-------|------|-------|
| `creatormonthly` | Creator Plan Monthly | $14.99/mo |
| `studiomonthly` | Studio Plan Monthly | $29.99/mo |
| `premiummonthly` | Premium Access Monthly | $39.99/mo |
| `scalemonthly` | Scale Plan Monthly | $49.99/mo |
| `promonthly` | App Pro Plan Monthly | $99.99/mo |
| `agencymonthly` | App Agency Plan Monthly | $149.99/mo |
| `promaxmonthly` | App Pro Max Plan Monthly | $299.99/mo |
| `creatoryearly` | Creator Plan Yearly (Promo) | $99/yr |
| `studioyearly` | Studio Plan Yearly | $299/yr |
| `premiumyearly` | Premium Access Yearly | $398/yr |
| `scaleyearly` | Scale Plan Yearly | $499/yr |
| `proyearly` | App Pro Plan Yearly | $998/yr |
| `agencyyearly` | App Agency Plan Yearly | $1,499/yr |
| `promaxyearly` | App Pro Max Plan Yearly | $2,998/yr |
| `lifetime` | AIPU Creator Lifetime Plan | $399 one-time |
