
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#07080c">
<title>Starting at $14.99 · Or Lock LIFETIME Access for $399 · OmniRogue</title>
<meta name="description" content="Sora 2, Veo 3.1, GPT-5, Claude 4.7, Midjourney-class, ElevenLabs, Suno + 130 more, every flagship AI in one workspace. Start at $14.99/mo, OR lock LIFETIME access for a one-time $399 (never pay monthly again). Cancel any time. Double money-back guarantee.">
<meta property="og:title" content="Starting at $14.99 · Or Lock LIFETIME Access for $399 · OmniRogue">
<meta property="og:description" content="140+ flagship AI models in one workspace. Start at $14.99/mo, or lock LIFETIME access for a one-time $399.">
<meta property="og:type" content="website">
<meta property="og:site_name" content="OmniRogue">
<meta property="og:url" content="https://<?= htmlspecialchars($_SERVER['HTTP_HOST']); ?><?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?')); ?>">
<meta property="og:image" content="https://<?= htmlspecialchars($_SERVER['HTTP_HOST']); ?>/sales-pages/lander7/assets/img/logo-omnirogue-h.png">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Starting at $14.99 · Or Lock LIFETIME Access for $399 · OmniRogue">
<meta name="twitter:description" content="140+ flagship AI models in one workspace. Start at $14.99/mo, or lock LIFETIME for $399.">
<link rel="canonical" href="https://<?= htmlspecialchars($_SERVER['HTTP_HOST']); ?><?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?')); ?>">
<link rel="icon" type="image/png" href="/sales-pages/lander7/assets/img/logo-omnirogue-stack.png">
<link rel="apple-touch-icon" href="/sales-pages/lander7/assets/img/logo-omnirogue-stack.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap">
<link rel="stylesheet" href="/sales-pages/lander7/assets/css/main.css">
</head>
<body>

<style>
  /* =====================================================================
     /onepromo, $14.99/mo promo overrides + helpers
     ===================================================================== */
  .promo-bar {
    background: linear-gradient(90deg, #0e1d3a 0%, #2a0f33 50%, #3a0a1d 100%);
    font-size: 14px;
  }
  .promo-bar .badge-fire {
    background: rgba(255, 209, 102, 0.18);
    color: #ffe096;
    border: 1px solid rgba(255, 209, 102, 0.3);
  }

  /* ---- Hero offer pill ---- */
  .offer-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px 6px 6px;
    border-radius: 999px;
    background: rgba(255, 209, 102, 0.08);
    border: 1px solid rgba(255, 209, 102, 0.3);
    font-size: 13px;
    color: #ffe096;
    margin-bottom: 22px;
    font-weight: 500;
  }
  .offer-tag .pill {
    background: var(--grad-fire);
    color: #1c0205;
    font-weight: 800;
    font-size: 11px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 999px;
  }

  /* ---- Big dollar headline accent ---- */
  .dollar {
    display: inline-block;
    background: linear-gradient(135deg, #ffd166 0%, #a3ff66 50%, #38e1ff 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 900;
    letter-spacing: -0.04em;
    position: relative;
  }

  .hero-trust .stars b { color: #fff; }

  /* ---- Inline three-step receipt under hero CTA ---- */
  .receipt {
    margin: 36px auto 0;
    max-width: 760px;
    border: 1px solid var(--line);
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.005));
    padding: 22px 26px;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 18px;
    text-align: left;
    backdrop-filter: blur(10px);
  }
  .receipt-row {
    display: flex;
    gap: 12px;
    align-items: flex-start;
  }
  .receipt-step {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    background: rgba(108, 140, 255, 0.18);
    border: 1px solid rgba(108, 140, 255, 0.4);
    color: #c2d0ff;
    display: grid;
    place-items: center;
    font-weight: 700;
    font-size: 13px;
    flex-shrink: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
  }
  .receipt-row b { display: block; font-size: 14.5px; color: var(--fg); margin-bottom: 2px; }
  .receipt-row span { display: block; font-size: 12.5px; color: var(--fg-muted); line-height: 1.45; }
  @media (max-width: 760px) {
    .receipt { grid-template-columns: 1fr; gap: 14px; padding: 18px 20px; }
  }

  /* ---- "What you unlock for $14.99" big card ---- */
  .unlock-card {
    border: 1px solid var(--line-2);
    border-radius: 24px;
    padding: 36px clamp(22px, 4vw, 44px);
    background:
      radial-gradient(60% 60% at 0% 0%, rgba(108, 140, 255, 0.12), transparent 70%),
      radial-gradient(60% 60% at 100% 100%, rgba(255, 209, 102, 0.10), transparent 70%),
      linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.005));
    position: relative;
    overflow: hidden;
  }
  .unlock-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-top: 26px;
  }
  .unlock-tile {
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 18px 18px 16px;
    background: rgba(255, 255, 255, 0.02);
    transition: border-color 0.2s, transform 0.2s;
  }
  .unlock-tile:hover { border-color: var(--line-2); transform: translateY(-2px); }
  .unlock-tile .ico {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: var(--grad-brand);
    display: grid;
    place-items: center;
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    margin-bottom: 10px;
    font-family: 'Plus Jakarta Sans', sans-serif;
  }
  .unlock-tile h5 { font-size: 14.5px; margin-bottom: 4px; color: var(--fg); }
  .unlock-tile p { font-size: 12.5px; color: var(--fg-muted); line-height: 1.45; }
  @media (max-width: 880px) { .unlock-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 480px) { .unlock-grid { grid-template-columns: 1fr; } }

  /* ---- The big $14.99 price card ---- */
  .dollar-card {
    border-radius: 28px;
    padding: 44px clamp(24px, 4vw, 56px);
    background:
      radial-gradient(70% 60% at 50% 0%, rgba(255, 209, 102, 0.18), transparent 70%),
      radial-gradient(80% 60% at 50% 100%, rgba(108, 140, 255, 0.14), transparent 70%),
      linear-gradient(180deg, #11131c 0%, #0a0c14 100%);
    border: 1px solid rgba(255, 209, 102, 0.35);
    position: relative;
    overflow: hidden;
    text-align: center;
  }
  .dollar-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 56px 56px;
    mask-image: radial-gradient(60% 60% at 50% 30%, black, transparent);
    -webkit-mask-image: radial-gradient(60% 60% at 50% 30%, black, transparent);
    pointer-events: none;
  }
  .dollar-card > * { position: relative; z-index: 1; }

  .dollar-card .ribbon-top {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--grad-fire);
    color: #1c0205;
    font-weight: 800;
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 6px 14px;
    border-radius: 999px;
    margin-bottom: 18px;
  }
  .dollar-card .ribbon-top::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #1c0205;
  }

  .dollar-amount {
    display: inline-flex;
    align-items: flex-start;
    justify-content: center;
    gap: 6px;
    margin: 8px 0 12px;
  }
  .dollar-amount .cur {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 48px;
    line-height: 1;
    background: linear-gradient(135deg, #ffd166 0%, #a3ff66 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-top: 22px;
  }
  .dollar-amount .num {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 900;
    font-size: clamp(120px, 18vw, 220px);
    line-height: 0.85;
    letter-spacing: -0.05em;
    background: linear-gradient(135deg, #ffd166 0%, #a3ff66 50%, #38e1ff 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 30px 80px rgba(255, 209, 102, 0.15);
  }
  .dollar-amount .per {
    color: var(--fg-muted);
    font-size: 18px;
    margin-top: 28px;
    text-align: left;
  }
  .dollar-amount .per b { color: #fff; display: block; font-size: 22px; }

  .dollar-strike {
    color: var(--fg-dim);
    font-size: 14px;
    letter-spacing: 0.02em;
  }
  .dollar-strike s { color: var(--fg-dim); }

  .dollar-after {
    margin-top: 14px;
    font-size: 15.5px;
    color: var(--fg);
  }
  .dollar-after b { color: #fff; }

  .dollar-includes {
    list-style: none;
    margin: 26px auto 0;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px 26px;
    text-align: left;
    max-width: 880px;
  }
  .dollar-includes li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: var(--fg);
  }
  .dollar-includes li svg { flex-shrink: 0; color: #5dffae; }
  @media (max-width: 760px) { .dollar-includes { grid-template-columns: 1fr; } }

  .dollar-cta-wrap {
    margin: 30px auto 0;
    max-width: 520px;
  }
  .dollar-card .micro {
    font-size: 12.5px;
    color: var(--fg-dim);
    margin-top: 12px;
    line-height: 1.5;
  }

  /* ---- Math / value proof bar ---- */
  .math-bar {
    margin-top: 26px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
    border: 1px solid var(--line);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.02);
    overflow: hidden;
    text-align: center;
  }
  .math-bar div {
    padding: 22px 18px;
    border-right: 1px solid var(--line);
  }
  .math-bar div:last-child { border-right: 0; }
  .math-bar b {
    display: block;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: clamp(22px, 2.6vw, 30px);
    background: var(--grad-brand);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.02em;
    margin-bottom: 4px;
  }
  .math-bar span { font-size: 12.5px; color: var(--fg-muted); }
  @media (max-width: 700px) {
    .math-bar { grid-template-columns: 1fr; }
    .math-bar div { border-right: 0; border-bottom: 1px solid var(--line); }
    .math-bar div:last-child { border-bottom: 0; }
  }

  /* ---- Sticky bottom CTA bar ---- */
  .sticky-cta {
    position: fixed;
    left: 50%;
    bottom: 18px;
    transform: translate(-50%, 30px);
    z-index: 75;
    background: rgba(13, 14, 22, 0.92);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border: 1px solid var(--line-2);
    border-radius: 16px;
    padding: 10px 14px 10px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 209, 102, 0.15);
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
    pointer-events: none;
    max-width: calc(100vw - 32px);
  }
  .sticky-cta.show { opacity: 1; transform: translate(-50%, 0); pointer-events: auto; }
  .sticky-cta-text {
    font-size: 13.5px;
    color: var(--fg);
    line-height: 1.3;
  }
  .sticky-cta-text b { color: #ffe096; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; }
  .sticky-cta-text span { display: block; font-size: 11.5px; color: var(--fg-muted); }
  .sticky-cta .btn { height: 42px; padding: 0 18px; font-size: 14px; }
  @media (max-width: 600px) {
    .sticky-cta { left: 12px; right: 12px; bottom: 12px; transform: translateY(30px); }
    .sticky-cta.show { transform: translateY(0); }
    .sticky-cta-text { font-size: 12px; }
    .sticky-cta-text span { display: none; }
  }

  /* ---- Mini countdown inside hero ---- */
  .hero-cd {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-top: 18px;
    padding: 10px 14px;
    border-radius: 12px;
    background: rgba(255, 91, 108, 0.08);
    border: 1px solid rgba(255, 91, 108, 0.25);
    font-size: 13.5px;
    color: var(--fg-muted);
  }
  .hero-cd .pulse {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: var(--danger);
    box-shadow: 0 0 0 0 rgba(255, 91, 108, 0.6);
    animation: dotpulse 1.6s infinite;
    flex-shrink: 0;
  }
  .hero-cd b {
    font-variant-numeric: tabular-nums;
    color: #fff;
    font-family: 'JetBrains Mono', monospace;
    background: rgba(255, 255, 255, 0.06);
    border-radius: 6px;
    padding: 2px 8px;
    margin: 0 2px;
  }

  /* ---- "What it costs everywhere else" comparison strip ---- */
  .price-strip {
    border: 1px solid var(--line);
    border-radius: 20px;
    padding: 28px clamp(18px, 3vw, 32px);
    background: rgba(255, 255, 255, 0.02);
    margin-top: 30px;
  }
  .price-strip h3 {
    font-size: 20px;
    margin-bottom: 18px;
    color: var(--fg);
  }
  .price-strip h3 .lo { color: var(--fg-muted); font-weight: 500; font-size: 14.5px; display: block; margin-top: 4px; letter-spacing: 0; }
  .price-rows {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px 18px;
  }
  .price-rows .row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.025);
    border: 1px solid var(--line);
    font-size: 14px;
  }
  .price-rows .row.you {
    background: linear-gradient(90deg, rgba(255, 209, 102, 0.10), rgba(108, 140, 255, 0.10));
    border-color: rgba(255, 209, 102, 0.4);
  }
  .price-rows .row .lbl { color: var(--fg); }
  .price-rows .row .px { color: var(--fg-muted); font-family: 'JetBrains Mono', monospace; font-weight: 600; }
  .price-rows .row.you .px {
    background: linear-gradient(135deg, #ffd166, #a3ff66);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 800;
  }
  @media (max-width: 700px) { .price-rows { grid-template-columns: 1fr; } }

  /* ---- Guarantee strip ---- */
  .guarantee-strip {
    margin-top: 36px;
    display: grid;
    grid-template-columns: 80px 1fr;
    gap: 22px;
    align-items: center;
    padding: 22px 26px;
    border-radius: 18px;
    background: rgba(255, 209, 102, 0.06);
    border: 1px solid rgba(255, 209, 102, 0.22);
  }
  .guarantee-strip .seal {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--grad-fire);
    display: grid;
    place-items: center;
    color: #1c0205;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 900;
    font-size: 14px;
    line-height: 1.05;
    text-align: center;
    box-shadow: 0 14px 36px -10px rgba(255, 181, 71, 0.5);
  }
  .guarantee-strip h4 { font-size: 17px; margin-bottom: 4px; color: #fff; }
  .guarantee-strip p { font-size: 13.5px; color: var(--fg-muted); line-height: 1.55; }
  @media (max-width: 600px) { .guarantee-strip { grid-template-columns: 1fr; text-align: center; } .guarantee-strip .seal { margin: 0 auto; } }

  /* =====================================================================
     v2, Lifetime $399 plan grid
     ===================================================================== */

  /* ---- 2-tier "Build Your Plan" grid ---- */
  .plan-grid {
    display: grid;
    grid-template-columns: 1fr 1.15fr;
    gap: 18px;
    margin-top: 36px;
    align-items: stretch;
  }
  .plan {
    position: relative;
    border: 1px solid var(--line);
    border-radius: 22px;
    padding: 30px clamp(22px, 3vw, 32px) 28px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.025), rgba(255, 255, 255, 0.005));
    display: flex;
    flex-direction: column;
    transition: border-color 0.2s, transform 0.2s;
  }
  .plan:hover { transform: translateY(-3px); }
  .plan-tag {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 11px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--fg-muted);
    margin-bottom: 8px;
  }
  .plan h3 {
    font-size: 22px;
    margin-bottom: 6px;
    color: #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    letter-spacing: -0.01em;
  }
  .plan .plan-sub {
    font-size: 13.5px;
    color: var(--fg-muted);
    line-height: 1.55;
    margin-bottom: 18px;
    min-height: 42px;
  }
  .plan .plan-price {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 900;
    font-size: 56px;
    line-height: 1;
    letter-spacing: -0.04em;
    color: #fff;
    margin: 4px 0 4px;
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
  }
  .plan .plan-price .cur { font-size: 28px; color: var(--fg-muted); font-weight: 700; }
  .plan .plan-price .per { font-size: 14px; color: var(--fg-muted); font-weight: 600; margin-left: 2px; }
  .plan .plan-strike { font-size: 13px; color: var(--fg-dim); margin-bottom: 16px; }
  .plan .plan-strike s { color: var(--fg-dim); }
  .plan ul {
    list-style: none;
    padding: 0;
    margin: 14px 0 22px;
    display: grid;
    gap: 9px;
  }
  .plan ul li {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    font-size: 13.5px;
    color: var(--fg);
    line-height: 1.5;
  }
  .plan ul li svg { flex-shrink: 0; color: #5dffae; margin-top: 3px; }
  .plan .plan-cta { margin-top: auto; }
  .plan .plan-cta .btn { width: 100%; }
  .plan .plan-foot {
    font-size: 11.5px;
    color: var(--fg-dim);
    text-align: center;
    margin-top: 10px;
    line-height: 1.5;
  }

  .plan.popular {
    border: 1px solid rgba(255, 209, 102, 0.5);
    background:
      radial-gradient(80% 60% at 50% 0%, rgba(255, 209, 102, 0.16), transparent 70%),
      linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.01));
    box-shadow: 0 30px 80px -20px rgba(255, 181, 71, 0.25);
    transform: translateY(-6px);
  }
  .plan.popular:hover { transform: translateY(-9px); }
  .plan.popular .plan-tag {
    color: #ffe096;
  }
  .plan.popular .plan-price {
    background: linear-gradient(135deg, #ffd166 0%, #a3ff66 60%, #38e1ff 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .plan.popular .ribbon {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--grad-fire);
    color: #1c0205;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 900;
    font-size: 11px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 7px 16px;
    border-radius: 999px;
    white-space: nowrap;
    box-shadow: 0 10px 24px -8px rgba(255, 181, 71, 0.6);
  }


  @media (max-width: 980px) {
    .plan-grid { grid-template-columns: 1fr; }
    .plan.popular { transform: none; }
    .plan.popular:hover { transform: translateY(-3px); }
  }

  /* ---- Lifetime savings calculator strip ---- */
  .lifetime-math {
    margin-top: 32px;
    border: 1px solid rgba(255, 209, 102, 0.3);
    border-radius: 18px;
    padding: 22px clamp(20px, 3vw, 30px);
    background:
      radial-gradient(80% 60% at 0% 50%, rgba(255, 209, 102, 0.10), transparent 70%),
      rgba(255, 255, 255, 0.02);
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 22px;
    align-items: center;
  }
  .lifetime-math h4 {
    font-size: 17.5px;
    color: #fff;
    margin-bottom: 6px;
    font-family: 'Plus Jakarta Sans', sans-serif;
  }
  .lifetime-math p {
    font-size: 13.5px;
    color: var(--fg-muted);
    line-height: 1.55;
  }
  .lifetime-math p b { color: #ffe096; }
  .lifetime-math .calc {
    display: grid;
    grid-template-columns: repeat(3, auto);
    gap: 0 18px;
    align-items: center;
    text-align: center;
  }
  .lifetime-math .calc b {
    display: block;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 900;
    font-size: 22px;
    color: #fff;
    letter-spacing: -0.02em;
  }
  .lifetime-math .calc span {
    font-size: 11.5px;
    color: var(--fg-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    display: block;
  }
  .lifetime-math .calc .op {
    font-size: 22px;
    color: var(--fg-dim);
    font-weight: 700;
  }
  @media (max-width: 760px) {
    .lifetime-math { grid-template-columns: 1fr; text-align: center; }
    .lifetime-math .calc { justify-content: center; margin: 0 auto; }
  }

  /* ---- "Stack savings" final ribbon ---- */
  .stack-savings {
    margin-top: 28px;
    text-align: center;
    padding: 16px 22px;
    border-radius: 14px;
    background: linear-gradient(90deg, rgba(255, 209, 102, 0.10), rgba(108, 140, 255, 0.10));
    border: 1px solid rgba(255, 209, 102, 0.28);
    color: var(--fg);
    font-size: 14px;
    line-height: 1.55;
  }
  .stack-savings b {
    background: linear-gradient(135deg, #ffd166, #a3ff66);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 800;
    font-family: 'Plus Jakarta Sans', sans-serif;
  }
</style>

<div class="promo-bar">
  <div class="container">
    <span class="badge-fire">Founder pricing · 2 ways to get in</span>
    <span>$14.99/mo · or <strong>$399 LIFETIME</strong> (never pay monthly again) · closes when timer hits zero</span>
    <span class="countdown" aria-label="Time remaining">
      <span data-cd-h>00</span>:<span data-cd-m>00</span>:<span data-cd-s>00</span>
    </span>
  </div>
</div>
<!-- ============================================================== HERO ================================================================== -->
<section class="hero" id="top">
  <div class="hero-grid"></div>
  <div class="hero-glow-a" style="background: radial-gradient(circle, rgba(255, 209, 102, 0.55), transparent 60%);"></div>
  <div class="hero-glow-b"></div>
  <div class="container">
    <div class="hero-head" data-reveal>
      <span class="offer-tag">
        <span class="pill">$14.99/mo · $399 lifetime</span>
        Two ways in — pick the one that fits. Smart buyers pick lifetime.
      </span>
      <h1>
        Steal the same AI stack the<br>
        <span class="underline-grad">top 1% of creators</span> use, starting at <span class="dollar">$14.99</span>.
      </h1>
      <p class="hero-sub">
        Sora 2. Veo 3.1. GPT-5. Claude 4.7. Midjourney-class. Flux Pro. ElevenLabs. Suno v4.5. Nano Banana. <b>140+ flagship models in one workspace, every one included, no "upgrade for that" upsells.</b> Sign up for $14.99/mo right now, walk in today, ship work by Sunday that competitors will swear cost you $10,000 to make. Cheaper than ChatGPT alone. <b style="color:#ffe096;">Or skip monthly billing forever and lock LIFETIME access for a one-time $399</b>. Cancel any time.
      </p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg btn-shine" href="<?= $link['step1link']; ?>">Get started for $14.99/mo →</a>
        <a class="btn btn-secondary btn-lg" href="#build-your-plan">See lifetime ($399)</a>
      </div>
      <div class="hero-trust">
        <span class="stars">
          <span class="stars-svg" aria-hidden="true">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      </span>
          <b>4.9 / 5</b> · 252,300+ creators · 8,200+ reviews
        </span>
        <span>·</span>
        <span>One-click cancel · no calls, no forms, no retention scripts</span>
        <span>·</span>
        <span><b style="color:#ffe096;">Double</b> money-back guarantee</span>
      </div>

      <div class="hero-cd">
        <span class="pulse"></span>
        <span>$14.99 pricing closes in</span>
        <b data-cd-h>00</b>:<b data-cd-m>00</b>:<b data-cd-s>00</b>
        <span>· then page resets to $97 setup + $47/mo</span>
      </div>

      <!-- 3-step receipt -->
      <div class="receipt">
        <div class="receipt-row">
          <div class="receipt-step">1</div>
          <div>
            <b>Right now · $14.99/mo</b>
            <span>Less than most AI tools alone. Every studio, every model, every bonus unlocks the moment you click. No "trial limit." No "credits running low." No upsells.</span>
          </div>
        </div>
        <div class="receipt-row">
          <div class="receipt-step">2</div>
          <div>
            <b>By Day 3 · ship something that turns heads</b>
            <span>74% of new members publish their first "wait, I made this?" piece inside 48 hours. By Sunday, you'll have a portfolio.</span>
          </div>
        </div>
        <div class="receipt-row">
          <div class="receipt-step">3</div>
          <div>
            <b>Every month · $14.99/mo, locked for life</b>
            <span>Stay because the math is absurd ($5,800+/yr saved). Cancel anytime in one click, keep everything you made.</span>
          </div>
        </div>
      </div>
    </div>

    <!-- App composer mock (lighter version, kept for visual fidelity with home) -->
    <div class="hero-app" data-reveal>
      <div class="app-bar">
        <span class="app-dot r"></span>
        <span class="app-dot y"></span>
        <span class="app-dot g"></span>
        <span class="app-url"><span class="dom">app.omnirogue.com</span>/studio/new</span>
        <span style="margin-left:auto;font-size:11.5px;color:var(--fg-dim);font-family:'JetBrains Mono',monospace;display:flex;align-items:center;gap:6px;">
          <span class="chip-dot"></span> Live · 138 models online
        </span>
      </div>
      <div class="app-body">
        <aside class="app-side">
          <div class="side-group">Studios</div>
          <div class="side-item active"><div class="side-icon">▶</div>Video</div>
          <div class="side-item"><div class="side-icon">◆</div>Image</div>
          <div class="side-item"><div class="side-icon">♪</div>Audio</div>
          <div class="side-item"><div class="side-icon">✦</div>Agents</div>
          <div class="side-item"><div class="side-icon">✎</div>Writing</div>
          <div class="side-item"><div class="side-icon">{}</div>Code</div>
          <div class="side-group">Library</div>
          <div class="side-item"><div class="side-icon">★</div>Saved</div>
          <div class="side-item"><div class="side-icon">⊞</div>Templates</div>
        </aside>
        <main class="app-main">
          <div class="composer">
            <div class="composer-row">
              <div class="fake-input" data-fake-input>Cinematic drone shot of Tokyo at golden hour</div>
              <button class="send-btn" tabindex="-1">Generate
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </button>
            </div>
            <div class="composer-actions">
              <span class="chip active"><span class="chip-dot"></span> Veo 3.1</span>
              <span class="chip">Sora 2</span>
              <span class="chip">Seedance 2</span>
              <span class="chip">Kling 2.1</span>
              <span class="chip">16:9</span>
              <span class="chip">8s · 4K</span>
              <span class="chip">+ Character lock</span>
            </div>
          </div>
          <div class="gen-grid">
            <div class="gen-card" data-style="neon"><div class="gen-progress">Rendering · 78%</div><span class="gen-meta">Veo 3.1 · 8s</span></div>
            <div class="gen-card" data-style="dawn"><span class="gen-meta">Sora 2 · 6s</span></div>
            <div class="gen-card" data-style="forest"><span class="gen-meta">Seedance 2</span></div>
            <div class="gen-card" data-style="grid"><span class="gen-meta">Kling 2.1</span></div>
            <div class="gen-card" data-style="aurora"><span class="gen-meta">Imagen 3</span></div>
            <div class="gen-card" data-style="metal"><span class="gen-meta">Flux Pro</span></div>
            <div class="gen-card" data-style="warm"><span class="gen-meta">Midjourney-class</span></div>
            <div class="gen-card" data-style="ocean"><span class="gen-meta">Recraft V3</span></div>
          </div>
        </main>
      </div>
    </div>

    <!-- Press / featured strip -->
    <div style="margin-top:48px;text-align:center;font-size:12px;color:var(--fg-dim);letter-spacing:0.18em;text-transform:uppercase;font-weight:600;" data-reveal>
      Featured by &nbsp;·&nbsp;
      <span style="color:var(--fg-muted);">ProductHunt</span> &nbsp;·&nbsp;
      <span style="color:var(--fg-muted);">Forbes</span> &nbsp;·&nbsp;
      <span style="color:var(--fg-muted);">TechCrunch</span> &nbsp;·&nbsp;
      <span style="color:var(--fg-muted);">VentureBeat</span> &nbsp;·&nbsp;
      <span style="color:var(--fg-muted);">Fast Company</span> &nbsp;·&nbsp;
      <span style="color:var(--fg-muted);">The Verge</span>
    </div>
  </div>
</section>

<!-- ============================================================ MODEL MARQUEE ============================================================ -->
<section class="marquee-section" id="models" style="padding-block:50px;">
  <div class="marquee-label">Your $14.99/mo plan unlocks every flagship model on Earth, in one workspace, no upgrades, no add-ons</div>
  <div class="marquee">
    <div class="marquee-track">
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Veo 3.1</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Sora 2</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Seedance 2</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Kling 2.1</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Hailuo</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Runway Gen-3</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Luma Ray 2</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Pika 2</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>GPT-5</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Claude 4.7</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Gemini 3 Pro</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Llama 4</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Mistral Large 2</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Cohere R+</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Perplexity</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Flux Pro</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Imagen 3</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Ideogram 3</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Recraft V3</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Stable Diffusion 3</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Nano Banana</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Qwen Image</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>ElevenLabs v3</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Suno v4.5</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Udio</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>OpenAI TTS HD</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Cartesia</span>
                <span class="model-pill" data-cat="agent"><span class="pdot"></span>HeyGen 4</span>
                <span class="model-pill" data-cat="agent"><span class="pdot"></span>D-ID</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Krea</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Higgsfield</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Topaz Upscale</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Veo 3.1</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Sora 2</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Seedance 2</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Kling 2.1</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Hailuo</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Runway Gen-3</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Luma Ray 2</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Pika 2</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>GPT-5</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Claude 4.7</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Gemini 3 Pro</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Llama 4</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Mistral Large 2</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Cohere R+</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Perplexity</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Flux Pro</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Imagen 3</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Ideogram 3</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Recraft V3</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Stable Diffusion 3</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Nano Banana</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Qwen Image</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>ElevenLabs v3</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Suno v4.5</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Udio</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>OpenAI TTS HD</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Cartesia</span>
                <span class="model-pill" data-cat="agent"><span class="pdot"></span>HeyGen 4</span>
                <span class="model-pill" data-cat="agent"><span class="pdot"></span>D-ID</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Krea</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Higgsfield</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Topaz Upscale</span>
          </div>
    <div class="marquee-track reverse">
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Stable Video</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Wan 2.2</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Vidu</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Minimax</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Genmo</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>DALL·E 4</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Krea Realtime</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Magnific</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Z-Image</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Adobe Firefly</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Anthropic Claude Code</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>o3-mini</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>DeepSeek V3</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Grok 4</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Suno v5</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Riffusion</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>MusicGen</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Bark</span>
                <span class="model-pill" data-cat="agent"><span class="pdot"></span>Synthesia</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Perplexity Pages</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Replicate routes</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Fal AI</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Together AI</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Stable Video</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Wan 2.2</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Vidu</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Minimax</span>
                <span class="model-pill" data-cat="video"><span class="pdot"></span>Genmo</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>DALL·E 4</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Krea Realtime</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Magnific</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Z-Image</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Adobe Firefly</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Anthropic Claude Code</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>o3-mini</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>DeepSeek V3</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Grok 4</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Suno v5</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Riffusion</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>MusicGen</span>
                <span class="model-pill" data-cat="audio"><span class="pdot"></span>Bark</span>
                <span class="model-pill" data-cat="agent"><span class="pdot"></span>Synthesia</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Perplexity Pages</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Replicate routes</span>
                <span class="model-pill" data-cat="image"><span class="pdot"></span>Fal AI</span>
                <span class="model-pill" data-cat="text"><span class="pdot"></span>Together AI</span>
          </div>
  </div>
</section>

<!-- ============================================================ STATS BAR ================================================================ -->
<section style="padding-block:60px;">
  <div class="container" data-reveal>
    <div class="stats-bar">
      <div class="stat-cell">
        <div class="stat-num">252,300+</div>
        <div class="stat-cap">Creators shipping right now</div>
      </div>
      <div class="stat-cell">
        <div class="stat-num">11.4M</div>
        <div class="stat-cap">Pieces of content delivered</div>
      </div>
      <div class="stat-cell">
        <div class="stat-num">$5,800</div>
        <div class="stat-cap">Avg saved on tools / member / yr</div>
      </div>
      <div class="stat-cell">
        <div class="stat-num">4.9 ★</div>
        <div class="stat-cap">From 8,200+ verified reviews</div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================ WHAT YOU UNLOCK ========================================================== -->
<section id="whats-included" style="padding-block:40px 60px;">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><span class="dot"></span> Here's exactly what $14.99/mo unlocks</span>
      <h2>Six studios. 140+ models. Every one included. <span class="gradient-text">Zero "upgrade for that"</span> upsells.</h2>
      <p class="lead" style="margin:18px auto 0;">For $14.99/mo, you get the same full-access membership our highest-earning members use to do $30K–$100K months. Same studios. Same models. Same access tier. The <em>only</em> difference between you and them is who clicked first.</p>
    </div>

    <div class="unlock-card" data-reveal>
      <h3 style="font-size:22px;margin-bottom:6px;">For the next 7 days, this is yours:</h3>
      <p style="color:var(--fg-muted);font-size:14.5px;">Every studio. Every model. Unlimited renders. Full commercial license on everything you make. Use it for clients, build a brand, launch a product, and if you cancel, you still keep every file you generated. Forever.</p>

      <div class="unlock-grid">
        <div class="unlock-tile">
          <div class="ico">▶</div>
          <h5>Video Studio</h5>
          <p>Sora 2, Veo 3.1, Seedance 2, Kling, Runway, Hailuo, Luma. 4K renders, character lock across shots.</p>
        </div>
        <div class="unlock-tile">
          <div class="ico">◆</div>
          <h5>Image Studio</h5>
          <p>Flux Pro, Imagen 3, Ideogram 3, Recraft V3, Nano Banana, MJ-class. 8K upscaling, restyle, masked edits.</p>
        </div>
        <div class="unlock-tile">
          <div class="ico">♪</div>
          <h5>Audio Studio</h5>
          <p>ElevenLabs v3 voices in 32 languages. Suno v4.5 commercial music. Clone your own voice in 60 seconds.</p>
        </div>
        <div class="unlock-tile">
          <div class="ico">✦</div>
          <h5>Agent Studio</h5>
          <p>Autonomous AI workers on GPT-5, Claude 4.7, Gemini 3. They research, write, design and publish, while you sleep.</p>
        </div>
        <div class="unlock-tile">
          <div class="ico">✎</div>
          <h5>Writing Studio</h5>
          <p>Blog, ad copy, ebooks, scripts, sales pages, trained on your exact brand voice, plagiarism-checked, SEO-scored.</p>
        </div>
        <div class="unlock-tile">
          <div class="ico">{}</div>
          <h5>Code Studio</h5>
          <p>Apps, sites, Shopify themes, Chrome extensions. One sentence in, live deploy to Vercel or your domain out.</p>
        </div>
        <div class="unlock-tile">
          <div class="ico">☆</div>
          <h5>200+ ready templates</h5>
          <p>Pre-built workflows for ads, reels, ebooks, courses, funnels. Drop your idea in, hit go, get assets.</p>
        </div>
        <div class="unlock-tile">
          <div class="ico">⚐</div>
          <h5>Full commercial license</h5>
          <p>Sell it. License it. Stream it. Run it as paid ads. Everything you make is 100% yours, even if you cancel.</p>
        </div>
      </div>

      <!-- The "what this would cost everywhere else" comparison -->
      <div class="price-strip">
        <h3>The math: what this stack costs everywhere else
          <span class="lo">— actual current public prices for the tools we replace (look them up)</span>
        </h3>
        <div class="price-rows">
          <div class="row"><span class="lbl">ChatGPT Plus</span><span class="px">$20 / mo</span></div>
          <div class="row"><span class="lbl">Midjourney Pro</span><span class="px">$60 / mo</span></div>
          <div class="row"><span class="lbl">Runway Standard</span><span class="px">$95 / mo</span></div>
          <div class="row"><span class="lbl">Sora / Veo credits</span><span class="px">$70 / mo</span></div>
          <div class="row"><span class="lbl">ElevenLabs Creator</span><span class="px">$22 / mo</span></div>
          <div class="row"><span class="lbl">Suno Pro</span><span class="px">$24 / mo</span></div>
          <div class="row"><span class="lbl">Leonardo / Ideogram</span><span class="px">$30 / mo</span></div>
          <div class="row"><span class="lbl">HeyGen Creator</span><span class="px">$48 / mo</span></div>
          <div class="row"><span class="lbl">Claude Pro</span><span class="px">$20 / mo</span></div>
          <div class="row"><span class="lbl">Topaz, Krea, Pika, Luma…</span><span class="px">$108 / mo</span></div>
          <div class="row you"><span class="lbl"><b>Your $14.99/mo plan, full access</b></span><span class="px">$14.99</span></div>
          <div class="row you"><span class="lbl"><b>Every month · same access · locked rate</b></span><span class="px">$14.99</span></div>
        </div>
        <p style="font-size:13px;color:var(--fg-muted);margin-top:14px;text-align:center;">That's <b style="color:#ffe096;">$497/mo of retail SaaS</b>, replaced with $14.99/mo. <b style="color:#fff;">$5,964 saved per year.</b> And you only need one login.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================= STUDIOS ================================================================= -->
<section id="studios">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><span class="dot"></span> Six studios. One workspace. Real output.</span>
      <h2>What you'll actually <span class="gradient-text">ship in 7 days</span>, not "play with."</h2>
      <p class="lead" style="margin:18px auto 0;">Tire-kickers don't last here. Members launch full YouTube channels, drop ebooks, ship 30-ad creative tests, build agency demo reels, replace contractors, inside their first week. Once you're in, the only friction left is which idea you pick first.</p>
    </div>

    <div class="studios-grid">
      <article class="studio-card" data-color="rose" data-reveal>
        <div class="stop"><span class="num">01</span><span>Video Studio</span></div>
        <h3>Drop Hollywood-grade ads on your audience by Sunday.</h3>
        <p>Pilot Sora 2, Veo 3.1, Seedance 2, Kling, Runway and Hailuo from a single cursor. Lock characters across every shot, dial in per-scene cameras, export 4K with audio baked in. No crew. No cameras. No 5-figure invoice.</p>
        <div class="studio-models">
          <span class="chip">Sora 2</span>
          <span class="chip">Veo 3.1</span>
          <span class="chip">Seedance 2</span>
          <span class="chip">Kling 2.1</span>
          <span class="chip">Runway</span>
          <span class="chip">Hailuo</span>
          <span class="chip">+ 11 more</span>
        </div>
        <div class="studio-vis">
          <div class="viz-video">
            <div style="background:radial-gradient(circle at 50% 30%, #ff6bd5, transparent 70%), linear-gradient(180deg,#2a0e3f,#0a1230);"></div>
            <div style="background:radial-gradient(circle at 50% 70%, #ffb547, transparent 70%), linear-gradient(180deg,#2a0e1f,#1a0e3a);"></div>
            <div style="background:radial-gradient(circle at 30% 50%, #38e1ff, transparent 70%), linear-gradient(180deg,#062c44,#11315a);"></div>
          </div>
        </div>
      </article>

      <article class="studio-card" data-color="violet" data-reveal>
        <div class="stop"><span class="num">02</span><span>Image Studio</span></div>
        <h3>Stock photography is dead. You can kill it tonight.</h3>
        <p>Flux Pro, Imagen 3, Ideogram 3, Recraft V3, Stable Diffusion 3 and Nano Banana, one prompt box, every aesthetic. Upscale to 8K. Relight. Restyle. Masked edits. Brand-consistent campaigns at the speed of thought.</p>
        <div class="studio-models">
          <span class="chip">Flux Pro</span>
          <span class="chip">Imagen 3</span>
          <span class="chip">Ideogram 3</span>
          <span class="chip">Recraft V3</span>
          <span class="chip">SD 3</span>
          <span class="chip">Nano Banana</span>
          <span class="chip">+ 22 more</span>
        </div>
        <div class="studio-vis">
          <div class="viz-img">
            <div style="background:conic-gradient(from 220deg at 30% 70%, #6c8cff, #a06bff, #ff6bd5, #ffb547, #6c8cff);"></div>
            <div style="background:radial-gradient(circle at 70% 30%, #38e1ff, transparent 60%), linear-gradient(135deg,#062c44,#1a4d6e);"></div>
            <div style="background:linear-gradient(135deg,#ff5b6c,#ffb547);"></div>
          </div>
        </div>
      </article>

      <article class="studio-card" data-color="lime" data-reveal>
        <div class="stop"><span class="num">03</span><span>Audio Studio</span></div>
        <h3>Voice, music and sound, without paying a single freelancer.</h3>
        <p>ElevenLabs v3 narration in 32 languages, indistinguishable from a hired pro. Suno v4.5 tracks you can publish commercially. Clone your own voice in 60 seconds. Auto-dub videos. Generate Foley and FX on the fly.</p>
        <div class="studio-models">
          <span class="chip">ElevenLabs v3</span>
          <span class="chip">Suno v4.5</span>
          <span class="chip">Udio</span>
          <span class="chip">OpenAI TTS HD</span>
          <span class="chip">Cartesia</span>
          <span class="chip">+ 9 more</span>
        </div>
        <div class="studio-vis">
          <div class="viz-audio">
            <div class="audio-meta"><span>"Hype 60s · Fitness ad"</span><span>0:42 / 1:00</span></div>
            <div class="wave">
              <span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>            </div>
            <div class="audio-meta"><span style="color:var(--brand);">▶ Playing · ElevenLabs · "River" · EN</span><span>FX · Master · Render</span></div>
          </div>
        </div>
      </article>

      <article class="studio-card" data-color="cyan" data-reveal>
        <div class="stop"><span class="num">04</span><span>Agent Studio</span></div>
        <h3>Hire an AI "employee" who works while you sleep.</h3>
        <p>Spin up agents that research a topic, write a 2,000-word post, design a hero image, generate a 60-second voiceover, and push the whole bundle to your CMS overnight. Costs you zero per month. Never asks for time off.</p>
        <div class="studio-models">
          <span class="chip">GPT-5</span>
          <span class="chip">Claude 4.7</span>
          <span class="chip">Gemini 3 Pro</span>
          <span class="chip">Tools / Web / Files</span>
          <span class="chip">Custom workflows</span>
        </div>
        <div class="studio-vis">
          <div class="viz-agent">
            <div class="line done"><span class="check">✓</span> Researched topic + 11 sources</div>
            <div class="line done"><span class="check">✓</span> Wrote 2,180-word article</div>
            <div class="line done"><span class="check">✓</span> Designed hero + 3 inline images</div>
            <div class="line live"><span class="check">▶</span> Generating 60s voiceover (ElevenLabs)…</div>
            <div class="line"><span class="check">○</span> Publishing to WordPress + scheduling shorts</div>
          </div>
        </div>
      </article>

      <article class="studio-card" data-color="violet" data-reveal style="grid-column:span 1;">
        <div class="stop"><span class="num">05</span><span>Writing Studio</span></div>
        <h3>Posts, ads, ebooks, scripts, in your voice, at scale.</h3>
        <p>Long-form blog posts with live research, ad copy that converts, video scripts, email sequences, sales pages, ebooks, white papers, SEO-scored landing pages, all trained on your exact brand voice. Plagiarism-checked. Publish-ready.</p>
        <div class="studio-models">
          <span class="chip">GPT-5</span>
          <span class="chip">Claude 4.7</span>
          <span class="chip">Brand voice training</span>
          <span class="chip">Plagiarism-checked</span>
          <span class="chip">SEO scoring</span>
        </div>
      </article>

      <article class="studio-card" data-color="cyan" data-reveal style="grid-column:span 1;">
        <div class="stop"><span class="num">06</span><span>Code Studio</span></div>
        <h3>Apps, sites, automations, from a single sentence.</h3>
        <p>Full landing pages, Shopify themes, Chrome extensions, automations, real working apps. Claude Code-class quality. One-click deploy to Vercel, Netlify, GitHub or your own domain. Engineers cost $120k/yr. This costs $14.99/mo.</p>
        <div class="studio-models">
          <span class="chip">Claude Code</span>
          <span class="chip">GPT-5 Codex</span>
          <span class="chip">DeepSeek</span>
          <span class="chip">One-click deploy</span>
          <span class="chip">Live preview</span>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- ============================================================ EXAMPLES =============================================================== -->
<section id="examples" style="padding-top:40px;">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><span class="dot"></span> Real outputs · made by members</span>
      <h2>Shipped in the <span class="gradient-text">first 7 days</span> by people just like you.</h2>
      <p class="lead" style="margin:18px auto 0;">A 12-tile snapshot of what members built during their $14.99/mo access, not in months, in <em>days</em>. From 19-year-old students to Fortune 500 marketing teams. Same tools, same week, same $14.99/mo.</p>
    </div>

    <div class="gallery" data-reveal>
      <div class="tile"><div class="tile-bg b1"><img src="/sales-pages/lander7/assets/img/gallery/gallery-1.jpg" alt="Neon Tokyo alley, anamorphic pull-in" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">Veo 3.1</span><span class="tile-title">"Neon Tokyo alley · anamorphic pull-in"<br><span class="by">— @noraseth · brand director</span></span></div></div>
      <div class="tile"><div class="tile-bg b2"><img src="/sales-pages/lander7/assets/img/gallery/gallery-2.jpg" alt="Editorial perfume hero, volumetric" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">Flux Pro</span><span class="tile-title">"Editorial perfume hero, volumetric"<br><span class="by">— @kenjiohno · creative tech</span></span></div></div>
      <div class="tile"><div class="tile-bg b3"><img src="/sales-pages/lander7/assets/img/gallery/gallery-3.jpg" alt="Cinematic drone reveal at sunset" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">Sora 2</span><span class="tile-title">"Cinematic drone reveal at sunset"<br><span class="by">— @mateorian · indie filmmaker</span></span></div></div>
      <div class="tile"><div class="tile-bg b4"><img src="/sales-pages/lander7/assets/img/gallery/gallery-4.jpg" alt="Fashion campaign still, soft light" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">Imagen 3</span><span class="tile-title">"Fashion campaign still, soft light"<br><span class="by">— @imanicole · head of content</span></span></div></div>
      <div class="tile"><div class="tile-bg b5"><img src="/sales-pages/lander7/assets/img/gallery/gallery-5.jpg" alt="Lo-fi coffee brand 9:16 reel" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">Kling 2.1</span><span class="tile-title">"Lo-fi coffee brand 9:16 reel"<br><span class="by">— @jadenstudio · agency</span></span></div></div>
      <div class="tile"><div class="tile-bg b6"><img src="/sales-pages/lander7/assets/img/gallery/gallery-6.jpg" alt="Isometric ceramic architecture" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">Recraft V3</span><span class="tile-title">"Isometric ceramic architecture"<br><span class="by">— @studio_vk · designer</span></span></div></div>
      <div class="tile"><div class="tile-bg b7"><img src="/sales-pages/lander7/assets/img/gallery/gallery-7.jpg" alt="Moody portrait, 35mm film grain" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">Midjourney-class</span><span class="tile-title">"Moody portrait, 35mm film grain"<br><span class="by">— @rosaleah · photographer</span></span></div></div>
      <div class="tile"><div class="tile-bg b8"><img src="/sales-pages/lander7/assets/img/gallery/gallery-8.jpg" alt="Synthwave product launch track" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">Suno v4.5</span><span class="tile-title">"Synthwave product launch track"<br><span class="by">— @nightowl · producer</span></span></div></div>
      <div class="tile"><div class="tile-bg b9"><img src="/sales-pages/lander7/assets/img/gallery/gallery-9.jpg" alt="Full landing page, one prompt, live deploy" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">Code Studio</span><span class="tile-title">"Full landing page · 1 prompt · live deploy"<br><span class="by">— @samkho · founder</span></span></div></div>
      <div class="tile"><div class="tile-bg b10"><img src="/sales-pages/lander7/assets/img/gallery/gallery-10.jpg" alt="Spokesperson explainer, 32 languages" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">HeyGen 4</span><span class="tile-title">"Spokesperson explainer · 32 languages"<br><span class="by">— @brandbox · marketing</span></span></div></div>
      <div class="tile"><div class="tile-bg b11"><img src="/sales-pages/lander7/assets/img/gallery/special-effects.jpg" alt="Vector logo set for an indie game studio" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">Ideogram 3</span><span class="tile-title">"Vector logo set for an indie game studio"<br><span class="by">— @novadrift · games</span></span></div></div>
      <div class="tile"><div class="tile-bg b12"><img src="/sales-pages/lander7/assets/img/gallery/vfx-banner.jpg" alt="60s ad voiceover, cloned my own voice" loading="lazy" decoding="async"></div><div class="tile-meta"><span class="tile-cat">ElevenLabs v3</span><span class="tile-title">"60s ad voiceover · cloned my own voice"<br><span class="by">— @taraq · creator</span></span></div></div>
    </div>
  </div>
</section>

<!-- ============================================================= THE $14.99 OFFER ============================================================ -->
<section id="pricing">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><span class="dot"></span> Step 1 · get access for $14.99/mo (the easy door)</span>
      <h2>Starting at $14.99/mo,<br>everything is yours with <span class="gradient-text">full unlimited access.</span></h2>
      <p class="lead" style="margin:18px auto 0;">No tier upsells. No "pro-only" lockouts. No "upgrade for that model." No surprise fees. Bring an idea, leave with shippable work. Or get every cent back, plus the $14.99 if you forget to cancel. <b style="color:#ffe096;">Already know you'll love it? Skip $14.99/mo forever and grab the $399 lifetime →</b> <a href="#build-your-plan" style="color:#ffe096;border-bottom:1px dotted #ffe096;">see plan grid</a>.</p>
    </div>

    <div class="dollar-card" data-reveal>
      <span class="ribbon-top">Today only · founders' $14.99/mo pricing</span>

      <div class="dollar-strike">Page resets to <s>$97 setup</s> + <s>$47/mo</s> when the timer hits zero</div>

      <div class="dollar-amount">
        <span class="cur">$</span>
        <span class="num">14.99</span>
        <span class="per">/mo<b>full access · cancel anytime</b></span>
      </div>

      <div class="dollar-after">
        <b>$14.99/month</b>, locked for life, cheaper than ChatGPT alone. <span style="color:var(--fg-muted);">One-click cancel any time. We even refund your first month if you forget.</span>
      </div>

      <ul class="dollar-includes">
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> Every model included · 140+ flagship</li>
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> Video, image, audio, agents, writing, code</li>
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> Full commercial license, you keep 100%</li>
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> 200+ pro templates · ready to ship</li>
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> Brand voice training, sound like you</li>
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> "Zero to Creator" 12-module course (80+ videos)</li>
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> 8,200+ creator community · weekly live calls</li>
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> Priority human support · 60-second onboarding</li>
        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> Zero credits · zero caps · zero upsells. Ever.</li>
      </ul>

      <div class="dollar-cta-wrap">
        <a class="btn btn-primary btn-block btn-lg btn-shine" href="<?= $link['step1link']; ?>">Yes, get me access for $14.99/mo →</a>
        <p class="micro">$14.99/mo · billed monthly · <a href="#faq" style="color:var(--brand);border-bottom:1px dotted var(--brand);">cancel any time in one click</a> · double money-back guarantee on top.</p>
        <p class="micro" style="margin-top:14px;font-size:13px;color:var(--fg);">
          <span style="color:#ffe096;font-weight:700;">Smart-buyer move →</span> skip $14.99/mo forever and lock <a href="#build-your-plan" style="color:#ffe096;font-weight:700;border-bottom:1px dotted #ffe096;">LIFETIME access for a one-time $399</a>.
        </p>
      </div>

      <!-- Math bar -->
      <div class="math-bar">
        <div>
          <b>$2,300+</b>
          <span>Retail value of everything you unlock today</span>
        </div>
        <div>
          <b>$497 / mo</b>
          <span>What this stack costs as separate SaaS subs</span>
        </div>
        <div>
          <b>$14.99 / mo</b>
          <span>What you actually pay, locked for life</span>
        </div>
      </div>

      <!-- Guarantee -->
      <div class="guarantee-strip">
        <div class="seal">DOUBLE<br>GUARANTEE</div>
        <div>
          <h4>Two guarantees, one promise: you don't lose money. Period.</h4>
          <p><b>Guarantee #1, Your money back.</b> Try every studio for 7 days. Don't ship a piece you're proud of? Contact us, get your $14.99 back. No forms. No calls. No retention script.<br><b>Guarantee #2, Your $14.99 back.</b> Even if you forgot to cancel and got billed, we'll refund it on request, no questions. Most companies trap you. We refuse to.</p>
        </div>
      </div>

      <p class="micro" style="margin-top:18px;">
        <span style="display:inline-block;width:8px;height:8px;border-radius:999px;background:var(--danger);box-shadow:0 0 0 0 rgba(255,91,108,0.6);animation:dotpulse 1.6s infinite;margin-right:8px;vertical-align:middle;"></span>
        Only <b data-spots>47</b> founders' $14.99/mo spots left this week, pricing reverts to $97 setup the moment they're claimed.
      </p>
    </div>
  </div>
</section>

<!-- ============================================================ BUILD YOUR PLAN (AOV BOOSTER) ============================================ -->
<section id="build-your-plan" style="padding-block:60px 30px;">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><span class="dot"></span> Two ways in · pick the one that fits the next 12 months of your life</span>
      <h2>Start for $14.99/mo. <span class="gradient-text">Or never pay monthly again, for $399 lifetime.</span></h2>
      <p class="lead" style="margin:18px auto 0;">Same workspace. Same 140+ models. Same studios. The <em>only</em> difference is how you want to pay. The $14.99/mo plan is the easy "find out" door. The $399 lifetime is what the people who already know their answer pick, they pay once and never see another invoice. </p>
    </div>

    <div class="plan-grid" data-reveal>

      <!-- Plan 1: $14.99/mo (the front door) -->
      <div class="plan">
        <div class="plan-tag">Try it · risk-free</div>
        <h3>$14.99/mo</h3>
        <p class="plan-sub">Full access from day one. Test every studio. Cancel anytime in one click.</p>

        <div class="plan-strike"><s>$97 setup</s> + <s>$47/mo</s></div>
        <div class="plan-price"><span class="cur">$</span>14.99<span class="per">/mo</span></div>
        <p style="font-size:12.5px;color:var(--fg-muted);margin-top:-2px;">$14.99/mo, locked for life · cancel any time</p>

        <ul>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> Full access · every studio &amp; model</li>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> Then just $14.99/mo (cheaper than ChatGPT)</li>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> One-click cancel · no calls, no scripts</li>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> Double money-back guarantee</li>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> Keep every file you make · forever</li>
        </ul>

        <div class="plan-cta">
          <a class="btn btn-secondary btn-block btn-lg" href="<?= $link['step1link']; ?>">Start for $14.99</a>
          <p class="plan-foot">Total this year: ~$180. Best for "let me just see."</p>
        </div>
      </div>

      <!-- Plan 2: $399 lifetime (the AOV winner) -->
      <div class="plan popular">
        <span class="ribbon">Most picked · Best value</span>
        <div class="plan-tag">Lifetime · pay once · own forever</div>
        <h3>$399 LIFETIME</h3>
        <p class="plan-sub">Skip the $14.99/mo forever. One payment, lifetime full access, every future model included as we add them.</p>

        <div class="plan-strike"><s>$14.99/mo for life</s> = $1,800 over 10 years</div>
        <div class="plan-price"><span class="cur">$</span>399<span class="per">one-time</span></div>
        <p style="font-size:12.5px;color:#ffe096;margin-top:-2px;font-weight:600;">→ $0/mo, ever. Lifetime upgrades baked in.</p>

        <ul>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> <b>Lifetime full access</b> · every studio &amp; every model</li>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> <b>Every future model included</b> · as we add them</li>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> <b>Pays itself back in ~27 months</b> vs. monthly</li>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg> Founder Slack · early access channel · priority support</li>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg> Lock today's price, never affected by future increases</li>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg> 7-day money-back guarantee · zero risk</li>
        </ul>

        <div class="plan-cta">
          <a class="btn btn-primary btn-block btn-lg btn-shine" href="<?= $link['step1link']; ?>?plan=lifetime399">Lock LIFETIME for $399 →</a>
          <p class="plan-foot" style="color:#ffe096;">Total this year: $399. Total in year 5: still $399. Best for "I already know."</p>
        </div>
      </div>

    </div>

    <!-- Lifetime payback math -->
    <div class="lifetime-math" data-reveal>
      <div>
        <h4>The lifetime math (it's not even close)</h4>
        <p>$14.99/mo × 12 months = <b>~$180/yr</b>. The $399 lifetime pays itself back in <b>~27 months</b>, <b>and you keep every future model we add for free.</b> If you stay longer than 2 years (96% of members do), the lifetime is the cheapest option you'll ever buy.</p>
      </div>
      <div class="calc">
        <div>
          <b>$180/yr</b>
          <span>$14.99/mo plan</span>
        </div>
        <div class="op">vs</div>
        <div>
          <b>$0/yr</b>
          <span>after $399 once</span>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ============================================================ WHY $14.99, REASON WHY ====================================================== -->
<section id="why-one-dollar" style="padding-block:60px 30px;">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><span class="dot"></span> The question we get a hundred times a day</span>
      <h2>"Wait, <span class="gradient-text">why $14.99?</span> What's the catch?"</h2>
      <p class="lead" style="margin:18px auto 0;">It's a fair question. There's no catch. There's a reason, and it's so obvious it's almost embarrassing to say out loud.</p>
    </div>

    <div class="unlock-card" data-reveal style="max-width:980px;margin:30px auto 0;">
      <h3 style="font-size:21px;margin-bottom:14px;">Here's the truth, in plain English:</h3>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;">
        We've watched the numbers. <b>About 19 out of every 20 people who actually use OmniRogue for 7 full days stay long-term.</b> Not because we're "sticky." Because once you replace 12 SaaS subscriptions with one workspace, you literally cannot go back. <em>You</em> tell <em>us</em> that.
      </p>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;margin-top:14px;">
        So we did the math. Charging you $97 upfront <em>filters out</em> the exact people who'd love this most, the curious creator, the broke student with a great idea, the agency owner who's been burned by every "AI tool of the month." Those are the people we want inside. People who'd never gamble $97 on a maybe.
      </p>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;margin-top:14px;">
        So we made it dumb-simple: <b style="color:#ffe096;">pay $14.99/mo — less than most single AI tools alone — and prove it to yourself.</b> If we're right, you stay at $14.99/mo (less than ChatGPT alone) and we both win. If we're wrong, you cancel, you keep the work you made anyway, and we lose your first month. We will happily lose your first month to be wrong.
      </p>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;margin-top:14px;">
        <b style="color:#fff;">That's it. That's the whole catch.</b> If "the catch" was anything else, none of the 8,200 people leaving five-star reviews would still be here three months later.
      </p>

      <div class="math-bar" style="margin-top:26px;">
        <div>
          <b>19 / 20</b>
          <span>Members who finish the 7 days stay long-term</span>
        </div>
        <div>
          <b>0</b>
          <span>Hidden fees, sneaky upsells, or "premium tiers"</span>
        </div>
        <div>
          <b>1-click</b>
          <span>Cancellation, before <em>or</em> after the trial</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =========================================================== TESTIMONIALS ============================================================== -->
<section id="testimonials">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><span class="dot"></span> 8,200+ five-star reviews · real names · real receipts</span>
      <h2>The people who said yes to <span class="gradient-text">$14.99/mo</span>, and what changed in 7 days.</h2>
      <p class="lead" style="margin:18px auto 0;">Every one of them started at the same $14.99/mo you're about to pay. Every one of them had the same doubts you have right now. Here's what happened next.</p>
    </div>

    <div class="testimonials-grid">
      <article class="tcard" data-reveal>
        <div class="stars-svg">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                  </div>
        <span class="badge-result">"I was 100% going to cancel" → +$8,400</span>
        <blockquote>I signed up for $14.99/mo with my finger already hovering over the cancel button. Day 4: I'd booked $8,400 in client work using only the tools inside. Cancelled Midjourney, Runway, ElevenLabs and Suno the same hour. Best $14.99 I've ever spent, and I've spent a lot of dollars chasing this.</blockquote>
        <div class="tperson">
          <div class="tavatar" style="background:linear-gradient(135deg,#6c8cff,#a06bff);">MR</div>
          <div><b>Mateo Rian</b><span>Indie filmmaker · Lisbon</span></div>
        </div>
      </article>

      <article class="tcard" data-reveal>
        <div class="stars-svg">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                  </div>
        <span class="badge-result">"I thought it'd take a week to learn" → 90 min</span>
        <blockquote>I figured it'd take me a week of YouTube tutorials just to figure out the interface. Started Saturday morning. By Saturday afternoon I'd shipped 12 reels for my brand, storyboarded, scored, cut, captioned. Last month that would've been 3 contractors and a week of back-and-forth.</blockquote>
        <div class="tperson">
          <div class="tavatar" style="background:linear-gradient(135deg,#ff6bd5,#ffb547);">NS</div>
          <div><b>Nora Seth</b><span>Brand Director · Brooklyn</span></div>
        </div>
      </article>

      <article class="tcard" data-reveal>
        <div class="stars-svg">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                  </div>
        <span class="badge-result">"No way it replaces 12 tools" → $4,180/mo cut</span>
        <blockquote>I run a 9-person agency. I genuinely thought there was no way one platform could replace our 12-tool stack. We did the $14.99/mo trial as a joke. Monday morning we cancelled $4,180/mo in subscriptions. Now we bill clients more for output we ship 4x faster. Most no-brainer purchase of 2026.</blockquote>
        <div class="tperson">
          <div class="tavatar" style="background:linear-gradient(135deg,#38e1ff,#a3ff66);">IC</div>
          <div><b>Imani Cole</b><span>Head of Content · Atlanta</span></div>
        </div>
      </article>

      <article class="tcard" data-reveal>
        <div class="stars-svg">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                  </div>
        <span class="badge-result">"I'm a complete beginner" → first $230 sale</span>
        <blockquote>I'm 19. Never made a dollar online. Don't know AI. Don't know "prompts." I followed the "Zero to Creator" course inside step-by-step, used the templates exactly as written, and made my first $230 on day 6. I literally cried at my desk. The dollar paid itself back 230 times. I'm in for life.</blockquote>
        <div class="tperson">
          <div class="tavatar" style="background:linear-gradient(135deg,#a3ff66,#38e1ff);">KO</div>
          <div><b>Kenji Ohno</b><span>Student · Osaka</span></div>
        </div>
      </article>

      <article class="tcard" data-reveal>
        <div class="stars-svg">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                  </div>
        <span class="badge-result">"Course stuck in my head 2 years" → $11k launch</span>
        <blockquote>I'd been "writing" the same course in my head for 2 years. Inside my your first week I wrote three ebooks, designed every cover, recorded the audio in my cloned voice, built three sales pages and launched. Did $11,000 in 6 days. I literally cannot believe this exists, and I cannot believe I waited.</blockquote>
        <div class="tperson">
          <div class="tavatar" style="background:linear-gradient(135deg,#ffb547,#ff5b6c);">PG</div>
          <div><b>Priya Gopal</b><span>Author &amp; Coach · Singapore</span></div>
        </div>
      </article>

      <article class="tcard" data-reveal>
        <div class="stars-svg">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.95 6.49 7.05.74-5.27 4.97 1.5 7-6.23-3.6L5.77 21.2l1.5-7L2 9.23l7.05-.74L12 2z"/></svg>
                  </div>
        <span class="badge-result">"It's a bait offer" → 2× ROAS in 7 days</span>
        <blockquote>I write copy for a living. I knew $14.99/mo was a "tease" hook. Signed up anyway just to roast it. Replaced our $9,800/mo creative agency with one in-house marketer + this. Doubled our ROAS in the first week. Pricing this at $14.99/mo should be illegal, and I'm grateful it isn't.</blockquote>
        <div class="tperson">
          <div class="tavatar" style="background:linear-gradient(135deg,#a06bff,#6c8cff);">DC</div>
          <div><b>Daniel Choi</b><span>CMO · Toronto</span></div>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- ============================================================= TWO PATHS / FUTURE PACE ================================================== -->
<section id="two-paths" style="padding-block:50px 30px;">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><span class="dot"></span> 7 days from right now, pick your path</span>
      <h2>Same seven days. <span class="gradient-text">Two completely different lives.</span></h2>
      <p class="lead" style="margin:18px auto 0;">It's Monday-ish, somewhere. Fourteen days from this exact moment, one of these two screenshots is your reality. The only fork in the road is what you do in the next 60 seconds.</p>
    </div>

    <div data-reveal style="margin-top:36px;display:grid;grid-template-columns:1fr 1fr;gap:22px;max-width:1080px;margin-left:auto;margin-right:auto;">
      <div style="border:1px solid var(--line);border-radius:20px;padding:30px clamp(20px,3vw,32px);background:rgba(255,255,255,0.015);">
        <div style="display:inline-block;font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:var(--fg-dim);font-weight:700;margin-bottom:10px;">Path A · close this tab</div>
        <h3 style="font-size:22px;margin-bottom:14px;color:#cfd2dc;">Next Monday, the same as last Monday.</h3>
        <ul style="list-style:none;padding:0;display:grid;gap:10px;font-size:14.5px;color:var(--fg-muted);line-height:1.55;">
          <li>· Still juggling 8 tabs. Still bouncing between 12 logins.</li>
          <li>· Still paying $20 here, $48 there, $95 over there, quietly losing $300–$500/mo to subscriptions you barely use.</li>
          <li>· Still putting off the video / course / launch / ebook for "when I have time."</li>
          <li>· Still watching other creators ship work you <em>know</em> you could match if you just had the tools.</li>
          <li>· Still wondering if "the AI thing" is a fad, while the people who learned it last year compound their lead.</li>
        </ul>
        <p style="margin-top:18px;font-size:13.5px;color:var(--fg-dim);">Cost: $0 today. Estimated 12-month cost: <b style="color:#cfd2dc;">~$5,800 in subs you didn't even need.</b> Plus the project you didn't ship.</p>
      </div>

      <div style="border:1px solid rgba(255,209,102,.4);border-radius:20px;padding:30px clamp(20px,3vw,32px);background:radial-gradient(60% 50% at 50% 0%,rgba(255,209,102,.12),transparent 70%),rgba(255,255,255,0.02);position:relative;">
        <div style="display:inline-block;font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#ffe096;font-weight:700;margin-bottom:10px;">Path B · start for $14.99 button</div>
        <h3 style="font-size:22px;margin-bottom:14px;color:#fff;">Next Monday, you're a different operator.</h3>
        <ul style="list-style:none;padding:0;display:grid;gap:10px;font-size:14.5px;color:var(--fg);line-height:1.55;">
          <li>✓ One tab. 140+ models. Every one included. No tool sprawl.</li>
          <li>✓ Cancelled $300–$500/mo in tools by Wednesday. That money is now yours.</li>
          <li>✓ The video / course / launch / ebook is <em>shipped.</em> Sitting live. Earning, getting feedback, or both.</li>
          <li>✓ You stopped admiring other creators, you started <em>being</em> one.</li>
          <li>✓ The AI gap closed in 7 days. You're not behind anymore. You're ahead.</li>
        </ul>
        <p style="margin-top:18px;font-size:13.5px;color:var(--fg-muted);">Cost: <b style="color:#ffe096;">$14.99/mo</b> (or $0 if you cancel). Estimated 12-month upside: priceless. Estimated risk: less than a lunch.</p>
      </div>
    </div>

    <p style="text-align:center;margin-top:32px;font-size:15.5px;color:var(--fg);max-width:720px;margin-left:auto;margin-right:auto;line-height:1.6;">
      You already know which path is rational. <b style="color:#fff;">Path B costs you a dollar.</b> Path A costs you another year of "I keep meaning to." The math has been telling you what to do this whole time.
    </p>

    <div class="hero-cta" data-reveal style="justify-content:center;margin-top:24px;flex-wrap:wrap;">
      <a class="btn btn-primary btn-lg btn-shine" href="<?= $link['step1link']; ?>">Take Path B for $14.99 →</a>
      <a class="btn btn-secondary btn-lg" href="#build-your-plan">Or skip $14.99/mo forever · $399 lifetime</a>
    </div>
  </div>
</section>

<!-- =============================================================== PERSONAS ============================================================= -->
<section id="personas">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><span class="dot"></span> Built for anyone who creates anything for a living</span>
      <h2>Whoever you are, your <span class="gradient-text">$14.99/mo</span> pays itself back before lunch.</h2>
      <p class="lead" style="margin:18px auto 0;">Find your tribe. Then realize the dollar is already overpaid.</p>
    </div>

    <div class="persona-grid">
      <div class="persona" data-reveal>
        <div class="persona-icon">🎬</div>
        <h4>If you're a creator or streamer</h4>
        <p>This is the unfair advantage every creator under 1M subs is begging for.</p>
        <ul>
          <li>30 reels shipped in your trial weekend</li>
          <li>Clone your voice, never record audio again</li>
          <li>Auto-translate every video into 32 languages</li>
        </ul>
      </div>
      <div class="persona" data-reveal>
        <div class="persona-icon">🛍️</div>
        <h4>If you sell products online</h4>
        <p>Replace every photographer, model and ad agency you've ever hired.</p>
        <ul>
          <li>Studio-grade product photos with no studio</li>
          <li>10× new ad creatives a day, A/B-ready</li>
          <li>Spokesperson UGC videos in any language</li>
        </ul>
      </div>
      <div class="persona" data-reveal>
        <div class="persona-icon">🚀</div>
        <h4>If you run marketing or an agency</h4>
        <p>Bill clients more. Deliver faster. Stop paying $497/mo per seat for tools.</p>
        <ul>
          <li>Full content calendars in an afternoon</li>
          <li>Brand-voice-trained writing, on tap</li>
          <li>White-label exports, client-ready</li>
        </ul>
      </div>
      <div class="persona" data-reveal>
        <div class="persona-icon">💼</div>
        <h4>If you're a founder or solopreneur</h4>
        <p>You just got a 4-person team for $14.99/mo. Use it accordingly.</p>
        <ul>
          <li>Replace 4 contractors, immediately</li>
          <li>Ship MVPs and demos in days, not months</li>
          <li>One platform, every asset you'll ever need</li>
        </ul>
      </div>
      <div class="persona" data-reveal>
        <div class="persona-icon">📚</div>
        <h4>If you teach or coach</h4>
        <p>Launch the course you've been "writing in your head" for two years.</p>
        <ul>
          <li>Full course shipped inside your trial week</li>
          <li>AI avatars that teach in 32 languages</li>
          <li>Auto-generated quizzes and worksheets</li>
        </ul>
      </div>
      <div class="persona" data-reveal>
        <div class="persona-icon">✍️</div>
        <h4>If you write for a living</h4>
        <p>Match agency output as a solo. Plagiarism-clean. SEO-scored.</p>
        <ul>
          <li>Researched 2,000-word posts in 90 seconds</li>
          <li>Brand-voice clone that sounds like <em>you</em></li>
          <li>Covers, illustrations and ads bundled in</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================ FAQ ====================================================================== -->
<section id="faq">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><span class="dot"></span> Last questions · answered honestly</span>
      <h2>The objections in your head, addressed up front.</h2>
      <p class="lead" style="margin:18px auto 0;">If something's still stopping you, it's almost certainly in here. If it isn't, email us, we read every one.</p>
    </div>

    <div class="faq-list" data-reveal>
      <details class="faq-item" open>
        <summary>"Wait, it's <em>really</em> $14.99/mo for everything? No tricks?"<span class="plus"></span></summary>
        <div class="faq-body">Really. $14.99/mo gets you full access from day one to every studio, every model, and every bonus on this page. No "pro-only" lockouts. No "premium tier." No "upgrade to use this model." No hidden setup fee. Your rate stays $14.99/mo, locked for life, and you can cancel any time, in one click, no questions asked. Cancel anytime in one click. That's the whole story.</div>
      </details>

      <details class="faq-item">
        <summary>"What's the $399 lifetime really get me, and how is it different from the $14.99/mo plan?"<span class="plus"></span></summary>

      <details class="faq-item">
        <summary>"What if I start on $14.99/mo — can I upgrade to lifetime later?"<span class="plus"></span></summary>
        <div class="faq-body">Yes. You can upgrade from $14.99/mo → $399 lifetime any day inside your dashboard, and we'll credit any $14.99/mo payments you've already made in the same billing year toward the $399. <a href="#build-your-plan">See the plan grid →</a></div>
      </details>

        <div class="faq-body">Same workspace. Same studios. Same 140+ models. The difference is you pay <b>once, $399, ever.</b> No $14.99/mo, no annual renewal, no future price increases. Every model we add later (Sora 3, Veo 4, GPT-6, whatever's next) is included in your lifetime, forever, no upgrade fee. The math: $14.99/mo × 27 months = ~$405, so it pays itself back in just over 2 years. After that, every additional year is free. And since 96% of members stay 2+ years, picking lifetime is the cheapest move you'll ever make on OmniRogue. <a href="#build-your-plan">See the plan grid →</a></div>
      </details>




      <details class="faq-item">
        <summary>"Am I going to wake up to a surprise charge?"<span class="plus"></span></summary>
        <div class="faq-body">No surprises, by design. We email you before each billing cycle reminding you, in plain English, exactly what's about to happen. Cancel any time and you won't be charged again. Cancel and we'll still refund that first $14.99 if you ask, inside our 7-day money-back window. Most companies trap you with this. We don't.</div>
      </details>

      <details class="faq-item">
        <summary>"How do I cancel? Is there going to be a 'retention call'?"<span class="plus"></span></summary>
        <div class="faq-body">One click in your dashboard. That's it. No phone call. No retention agent. No awkward "wait, before you go..." script. No three-screen exit survey. We genuinely want cancelling to take less time than logging in. If you cancel, you won't be charged again. If you need a refund, ask and we'll process it, that day.</div>
      </details>

      <details class="faq-item">
        <summary>"Why $14.99? What's actually in it for you?"<span class="plus"></span></summary>
        <div class="faq-body">Pure math. 19 out of 20 members who actually <em>use</em> OmniRogue for 7 days stay long-term, because once you replace 12 SaaS tools with one workspace, going back is genuinely painful. We priced it low enough that curious creators can get in, but (curious creators, broke students with great ideas, agency owners who've been burned). So we charge $14.99/mo — less than ChatGPT alone, let you prove it to yourself, and bet on the long-term relationship. We lose $14.99 if we're wrong about you. We'll happily take that bet.</div>
      </details>

      <details class="faq-item">
        <summary>"Do I really get every model, Sora 2, Veo 3.1, GPT-5, Midjourney-class, during the trial?"<span class="plus"></span></summary>
        <div class="faq-body">Every single one. No watered-down trial version. The full list as of this week: Sora 2, Veo 3.1, Seedance 2, Kling 2.1, Runway Gen-3, Hailuo, Luma Ray 2, Pika 2, GPT-5, Claude 4.7, Gemini 3 Pro, Llama 4, Mistral Large 2, Cohere R+, Perplexity, Flux Pro, Imagen 3, Ideogram 3, Recraft V3, Stable Diffusion 3, Nano Banana, Qwen, ElevenLabs v3, Suno v4.5, Udio, OpenAI TTS HD, Cartesia, HeyGen 4, D-ID, Krea, Magnific, Topaz upscale, plus 100+ more. All unlocked the second your dashboard loads.</div>
      </details>

      <details class="faq-item">
        <summary>"Can I sell what I make?"<span class="plus"></span></summary>
        <div class="faq-body">Yes. Your $14.99/mo access includes a <b>full commercial license</b> on everything you generate. Sell it. License it. Monetize it on YouTube, TikTok, Spotify, Amazon KDP. Use it in client work. Run it as paid ads. We take zero cut, zero royalty, zero "revenue share." Whatever you make this week is 100% yours forever, even if you cancel and never come back.</div>
      </details>

      <details class="faq-item">
        <summary>"What if I try it and don't love it, do I really get my money back?"<span class="plus"></span></summary>
        <div class="faq-body">Yes. You have 7 days to try every studio, every model, every bonus. If you don't ship a single piece of content you're proud of, click <a href="<?= $link['step1link']; ?>">here to contact us</a> and we'll refund every cent, including your payment, no forms, no calls, no script. If you got billed the $14.99 and want it back too? Refunded. We'd rather lose the money than have a single unhappy ex-member.</div>
      </details>

      <details class="faq-item">
        <summary>"Is my data, my work, and my cloned voice private?"<span class="plus"></span></summary>
        <div class="faq-body">Completely. Your prompts, uploads, voice clone, brand-voice training data and generated outputs are walled into your private workspace, encrypted at rest, and <em>never</em> used to train shared models. One click in settings wipes everything. Full receipts in our <a href="/privacy.html">privacy policy</a>.</div>
      </details>

      <details class="faq-item">
        <summary>"I'm in [my industry / my niche], will this actually work for me?"<span class="plus"></span></summary>
        <div class="faq-body">If you create anything online, content, ads, products, courses, services, software, yes. Members include indie creators, agencies, e-commerce brands, SaaS founders, realtors, coaches, authors, photographers, musicians, lawyers, consultants, churches, schools, and Fortune 500 marketing departments. The whole reason we keep pricing this low is so you don't have to take our word for it. Click in, test it for your exact use case, decide.</div>
      </details>

      <details class="faq-item">
        <summary>"What if I've never used AI before and I'm a complete beginner?"<span class="plus"></span></summary>
        <div class="faq-body">Then you're in the perfect place, and you have an actual advantage. Your membership includes the <b>"Zero to Creator" 12-module course (80+ videos)</b> walking you step-by-step through how our top members went from zero to $5K–$50K/mo using AI. Plus 200+ ready-made templates (literally drop your idea into a slot and hit go), weekly live calls with our team, and a community of 8,200+ creators who were exactly where you are 6 months ago. You'll be shipping by Sunday.</div>
      </details>

      <details class="faq-item">
        <summary>"This sounds too good, what's it going to cost me in 6 months?"<span class="plus"></span></summary>
        <div class="faq-body">$14.99/month. Same as today. Locked for life as a founders' rate, written into our terms. We don't do "introductory pricing" that secretly doubles in month 3. We don't do "based on usage" surprise bills. The price you see at signup is the price you pay as long as you stay. If we ever introduce a higher tier with extra features, your founders' price stays untouched. The whole offer is built on you trusting that, so we made it provable.</div>
      </details>
    </div>
  </div>
</section>

<!-- ============================================================ FOUNDER NOTE ============================================================= -->
<section id="founder-note" style="padding-block:30px 20px;">
  <div class="container" style="max-width:780px;">
    <div data-reveal style="border:1px solid var(--line-2);border-radius:22px;padding:34px clamp(22px,4vw,42px);background:linear-gradient(180deg,rgba(255,255,255,.025),rgba(255,255,255,.005));">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;">
        <div style="width:54px;height:54px;border-radius:50%;background:linear-gradient(135deg,#6c8cff,#a06bff);display:grid;place-items:center;color:#fff;font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:18px;">RB</div>
        <div>
          <div style="font-size:14px;color:var(--fg-muted);">A quick note from</div>
          <div style="font-size:17px;color:#fff;font-weight:700;">Randy · founder, OmniRogue</div>
        </div>
      </div>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;">Look, I'll be straight with you.</p>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;margin-top:14px;">Nobody pays $14.99/mo to "see what something is." If you've scrolled this far, you've already decided you want in. The low price is just my way of removing every excuse your brain is generating right now to say <em>"maybe later."</em></p>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;margin-top:14px;">"Maybe later" is the most expensive decision in your life. It's the one that has you 6 months from now, scrolling another landing page, wondering if <em>that</em> one is the one. It isn't. <b style="color:#fff;">This one is.</b> Because for $14.99/mo, you can literally just <em>find out.</em></p>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;margin-top:14px;">If I'm wrong about OmniRogue? You're out $14.99. You've lost more between the couch cushions. You've spent more on apps you opened twice. You'll be fine.</p>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;margin-top:14px;">If I'm right? You're about to have the strangest, most exhilarating week of your creative life. You'll wake up on day 4 and forget what you used to do at your desk. You'll cancel four subscriptions and feel weirdly powerful about it. You'll ship something on day 6 that you didn't think you were capable of. Then you'll send me an email I'll keep for years.</p>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;margin-top:14px;">And here's the part nobody else will tell you: if you already <em>know</em> you're going to use this for a couple of years (and yeah, you do), <b style="color:#ffe096;">don't pay $14.99/mo for the next decade. Pay $399 once and be done.</b> That's the move smart buyers actually make.</p>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;margin-top:14px;">Start at $14.99/mo to test. Pick the lifetime to win. Either way, I'll see you inside.</p>
      <p style="font-size:15.5px;color:var(--fg);line-height:1.75;margin-top:18px;font-style:italic;">— Randy</p>
      <p style="font-size:12.5px;color:var(--fg-dim);margin-top:6px;">P.S. If you cancel, you keep every file you generated. Forever. The lifetime plan has the same 7-day money-back guarantee. There's literally no version of this where you lose.</p>
    </div>
  </div>
</section>

<!-- ============================================================ FINAL CTA =============================================================== -->
<section class="cta-final">
  <div class="container">
    <div class="cta-final-card" data-reveal>
      <span class="eyebrow"><span class="dot"></span> Final call · founders' bundle closing</span>
      <h2>Stop reading about it. Start <span class="gradient-text">building with it</span>.<br>Pick your door, they all open the same workspace.</h2>
      <p>Two ways in. <b style="color:#fff;">$14.99/mo</b> if you want to start monthly. <b style="color:#ffe096;">$399 lifetime</b> if you already know — never pay monthly again. Cancel-friendly. Refund-friendly. Built for the next decade of your work.</p>
      <div class="hero-cta" style="justify-content:center;flex-wrap:wrap;">
        <a class="btn btn-primary btn-lg btn-shine" href="<?= $link['step1link']; ?>?plan=lifetime399">Lock LIFETIME · $399 →</a>
        <a class="btn btn-secondary btn-lg" href="<?= $link['step1link']; ?>">Or start with $14.99/mo</a>
      </div>
      <p style="font-size:13px;color:var(--fg-dim);margin-top:18px;">$14.99/mo · or one-time $399 lifetime · one-click cancel · 7-day refund window · keep every file you make, forever</p>
    </div>
  </div>
</section>

<!-- Sticky CTA bar -->
<div class="sticky-cta" data-sticky-cta>
  <div class="sticky-cta-text">
    <b>$14.99/mo · $399 LIFETIME</b>
    <span>140+ flagship AIs · pay once, own forever · 7-day refund</span>
  </div>
  <a class="btn btn-secondary btn-shine" href="<?= $link['step1link']; ?>" style="margin-right:6px;">$14.99/mo</a>
  <a class="btn btn-primary btn-shine" href="<?= $link['step1link']; ?>?plan=lifetime399">Lifetime $399</a>
</div>

<script>
  (function () {
    const sticky = document.querySelector('[data-sticky-cta]');
    if (!sticky) return;
    function onScroll() {
      const y = window.scrollY || window.pageYOffset;
      const h = document.documentElement.scrollHeight - window.innerHeight;
      const atBottom = h - y < 240;
      if (y > 700 && !atBottom) {
        sticky.classList.add('show');
      } else {
        sticky.classList.remove('show');
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  })();
</script>


<div data-live-signups class="live-signups" aria-live="polite"></div>
<style>
  .live-signups {
    position: fixed;
    left: 18px;
    bottom: 18px;
    background: rgba(20, 22, 32, 0.92);
    backdrop-filter: blur(16px);
    border: 1px solid var(--line-2);
    color: var(--fg);
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13px;
    display: flex;
    gap: 10px;
    align-items: center;
    z-index: 70;
    transform: translateY(20px);
    opacity: 0;
    pointer-events: none;
    transition: transform 0.4s ease, opacity 0.3s ease;
    max-width: 320px;
    box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.5);
  }
  .live-signups.show { transform: translateY(0); opacity: 1; }
  .live-signups .dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--lime);
    box-shadow: 0 0 8px var(--lime);
    flex-shrink: 0;
  }
  .live-signups b { color: #fff; }
  @media (max-width: 540px) { .live-signups { left: 12px; right: 12px; max-width: none; } }
</style>
<script src="/sales-pages/lander7/assets/js/main.js" defer></script>
<script>
(function () {
  var DURATION = (12 * 3600 + 31 * 60) * 1000;
  var key = 'aipu_cd_end';
  var end = parseInt(sessionStorage.getItem(key), 10);
  if (!end || end < Date.now()) {
    end = Date.now() + DURATION;
    sessionStorage.setItem(key, end);
  }

  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function tick() {
    var diff = Math.max(0, end - Date.now());
    var h = Math.floor(diff / 3600000); diff -= h * 3600000;
    var m = Math.floor(diff / 60000);   diff -= m * 60000;
    var s = Math.floor(diff / 1000);
    var hs = pad(h), ms = pad(m), ss = pad(s);
    document.querySelectorAll('[data-cd-h]').forEach(function(el){ el.textContent = hs; });
    document.querySelectorAll('[data-cd-m]').forEach(function(el){ el.textContent = ms; });
    document.querySelectorAll('[data-cd-s]').forEach(function(el){ el.textContent = ss; });
  }

  tick();
  setInterval(tick, 1000);
})();
</script>
</body>
</html>
