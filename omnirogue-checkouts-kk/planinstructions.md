# Plan Instructions — OmniRogue Checkout

Reference for plan names, prices, and points when wiring checkout pages and offer tokens.

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

## Lifetime

| Plan Name | Price | Points |
|-----------|-------|--------|
| AIPU Creator Lifetime Plan | $399 One-Time | 10,000 Points |

## pricing-v1 page mapping

The [pricing-v1](https://omnirogue.com/pricing-v1) checkout shows **Lifetime** plus three recurring tiers with a Monthly/Yearly toggle. Points are **not** shown on this page — use plan names and prices only.

| UI Label | Monthly plan name | Monthly price | Yearly plan name | Yearly price | Offer token (monthly) | Offer token (yearly) |
|----------|-------------------|---------------|------------------|--------------|----------------------|----------------------|
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
