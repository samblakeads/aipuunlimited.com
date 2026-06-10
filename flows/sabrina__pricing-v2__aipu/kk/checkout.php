<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
require_once $base_path.'/includes/money.php';
?>
<?php require_once(__DIR__.'/_kk-config.php'); ?>
<?php require_once(__DIR__.'/_checkout-offers.php'); ?>
<!DOCTYPE html>

<html data-browser-safari="false" data-theme-mode="dark" lang="en" style="--banner-height: 0px;">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover" name="viewport"/>
<title>Plans v2 - AIPU</title>
<meta content="Pick your AIPU plan, Creator, Studio, Scale, or Lifetime Access. Unlimited GPT-5.4, Gemini 3.1 Pro, Nano Banana 2, Veo 3.1, Seedance and more." name="description"/>
<link href="https://aipu-assets.b-cdn.net/sabrina__pricing-v2__aipu/04a83c76/logo-aipu.png" rel="icon" type="image/png"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link crossorigin="" href="https://cdn.tailwindcss.com" rel="preconnect"/>
<link href="https://cdn.tailwindcss.com" rel="dns-prefetch"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" media="print" onload="this.media='all'" rel="stylesheet"/>
<noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/></noscript>
<script defer="defer" src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
        colors: {
          border: 'rgba(255,255,255,0.08)',
          foreground: '#e8eaf0',
          background: '#05060f',
        },
        keyframes: {
          plansNewTiersGradient: { '0%': { backgroundPosition: '0%' }, '100%': { backgroundPosition: '200%' } },
          shimmerMove: { '0%': { transform: 'translateX(-120%)' }, '100%': { transform: 'translateX(320%)' } },
          luxuryFloatA: { '0%,100%': { transform: 'translate3d(0,0,0)' }, '50%': { transform: 'translate3d(18px,12px,0)' } },
          luxuryFloatB: { '0%,100%': { transform: 'translate3d(0,0,0)' }, '50%': { transform: 'translate3d(-16px,-14px,0)' } },
          luxuryMotion: { '0%': { backgroundPosition: '0% 50%' }, '50%': { backgroundPosition: '100% 50%' }, '100%': { backgroundPosition: '0% 50%' } },
        },
        animation: {
          plansGradient: 'plansNewTiersGradient 8s linear infinite',
        },
      },
    },
  };
</script>
<style>
  :root { --nav-height: 64px; --nav-bg: rgba(5,6,15,0.88); --text-secondary: #9aa3b2; --text-muted: #6b7280; }
  html, body { margin: 0; padding: 0; background: #05060f; }
  body { font-family: Inter, system-ui, sans-serif; }
  a { text-decoration: none; }
  .plansNewTiersLuxuryGlowA { animation: luxuryFloatA 14s ease-in-out infinite; }
  .plansNewTiersLuxuryGlowB { animation: luxuryFloatB 16s ease-in-out infinite; }
  .plansNewTiersLuxuryGradientMotion { animation: luxuryMotion 18s ease infinite; }
  .plansNewTiersMarketingShine { animation: shimmerMove 6s ease-in-out infinite; }
  .tabular-nums { font-variant-numeric: tabular-nums; }
  @media (hover:none) and (pointer:coarse) { .plansNewTiers-page, .plansNewTiers-page * { -webkit-backdrop-filter: none !important; } }

  /* Gen info dot + tooltip (free / unlimited models) */
  .gen-info { position: relative; display: inline-flex; align-items: center; justify-content: center; vertical-align: middle; margin-left: 4px; color: rgba(255,255,255,0.4); cursor: help; }
  .gen-info:hover, .gen-info:focus-visible { color: #35D7FF; outline: none; }
  .gen-info svg { width: 13px; height: 13px; }
  .gen-tip { position: absolute; bottom: calc(100% + 9px); left: 50%; transform: translateX(-50%) translateY(4px);
    display: flex; flex-direction: column; gap: 4px; text-align: center; white-space: nowrap;
    background: #11131f; border: 1px solid rgba(255,255,255,0.12); border-radius: 12px;
    box-shadow: 0 18px 50px -16px rgba(0,0,0,0.85); padding: 11px 15px; z-index: 60;
    opacity: 0; visibility: hidden; pointer-events: none; transition: opacity .16s ease, transform .16s ease; }
  .gen-tip::after { content: ""; position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
    border: 6px solid transparent; border-top-color: #11131f; }
  .gen-info:hover .gen-tip, .gen-info:focus-visible .gen-tip { opacity: 1; visibility: visible; transform: translateX(-50%) translateY(0); }
  .gen-tip-title { font-weight: 800; font-size: 13px; color: #fff; letter-spacing: -0.01em; }
  .gen-tip-row { font-size: 12px; color: rgba(255,255,255,0.62); }
  .gen-tip-timer { display: inline-flex; align-items: center; justify-content: center; gap: 5px; font-size: 11px; font-weight: 700;
    color: #FCA5A5; }
  .gen-tip-timer svg { width: 12px; height: 12px; }

  /* Footer trust popups */
  .or-modal-scrim { position: fixed; inset: 0; z-index: 100; display: flex; align-items: center; justify-content: center;
    padding: 20px; background: rgba(3,4,12,0.78); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
    opacity: 0; visibility: hidden; transition: opacity .2s ease; }
  .or-modal-scrim.open { opacity: 1; visibility: visible; }
  .or-modal { position: relative; width: 100%; max-width: 540px; max-height: 86vh; overflow-y: auto;
    background: linear-gradient(160deg,#0b0d1a 0%,#0a0c17 100%); border: 1px solid rgba(255,255,255,0.1); border-radius: 22px;
    box-shadow: 0 40px 110px -30px rgba(0,0,0,0.95), 0 0 60px -30px rgba(139,92,255,0.5); padding: 28px;
    transform: translateY(14px) scale(0.985); transition: transform .22s ease; }
  .or-modal-scrim.open .or-modal { transform: translateY(0) scale(1); }
  .or-modal-close { position: absolute; top: 16px; right: 16px; display: inline-flex; align-items: center; justify-content: center;
    height: 34px; width: 34px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04);
    color: rgba(255,255,255,0.6); cursor: pointer; transition: all .15s ease; }
  .or-modal-close:hover { color: #fff; background: rgba(255,255,255,0.1); }
  .or-modal-eyebrow { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em;
    text-transform: uppercase; color: #8B5CFF; }
  .or-modal-title { margin: 12px 0 0; font-size: 23px; font-weight: 800; letter-spacing: -0.02em; color: #fff; }
  .or-modal-intro { margin: 10px 0 0; font-size: 14.5px; line-height: 1.6; color: rgba(255,255,255,0.7); }
  .or-modal-list { margin: 20px 0 0; display: flex; flex-direction: column; gap: 13px; }
  .or-modal-item { display: flex; gap: 11px; }
  .or-modal-item-ico { flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; height: 22px; width: 22px;
    border-radius: 8px; background: rgba(52,215,255,0.12); color: #35D7FF; margin-top: 1px; }
  .or-modal-item-ico svg { width: 13px; height: 13px; }
  .or-modal-item-ttl { font-size: 13.5px; font-weight: 700; color: #fff; line-height: 1.35; }
  .or-modal-item-sub { margin-top: 2px; font-size: 12.5px; line-height: 1.5; color: rgba(255,255,255,0.58); }
  .or-modal-foot { margin: 22px 0 0; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
  .or-modal-cta { display: inline-flex; align-items: center; justify-content: center; gap: 7px; flex: 1 1 auto;
    border-radius: 14px; padding: 13px 18px; font-size: 13.5px; font-weight: 800; color: #fff;
    background: linear-gradient(110deg,#6366F1 0%,#8B5CFF 50%,#C026D3 100%); box-shadow: 0 18px 38px -14px rgba(139,92,255,0.65);
    transition: transform .15s ease; }
  .or-modal-cta:hover { transform: translateY(-1px); }
  .or-modal-note { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; color: rgba(255,255,255,0.5); }
  .or-modal-trust { margin: 18px 0 0; display: flex; flex-wrap: wrap; gap: 8px; }
  .or-modal-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 11px; border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.03); font-size: 11.5px; font-weight: 600; color: rgba(255,255,255,0.72); }
  .or-modal-badge svg { width: 13px; height: 13px; color: #34D399; }
</style>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@500;600;700;800&amp;family=JetBrains+Mono:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="https://aipu-assets.b-cdn.net/sabrina__pricing-v2__aipu/04a83c76/assets/main1.css" rel="stylesheet"/>
<link href="https://aipu-assets.b-cdn.net/sabrina__pricing-v2__aipu/04a83c76/assets/main2.css" rel="stylesheet"/>
<style id="omni-static-fixes">
html{--header-height:64px;--nav-height:64px;--nav-bg:rgba(7,8,18,0.85);color-scheme:dark}
.fixed.top-0.left-0.right-0.z-50 .backdrop-blur-xl,.fixed.top-0.left-0.right-0.z-50 [class*="backdrop-blur"]{backdrop-filter:blur(24px)!important;-webkit-backdrop-filter:blur(24px)!important;}

[data-checkout-prototype="plans-pick-your-plan"] .pyp-wrap{padding-top:calc(var(--nav-height) + env(safe-area-inset-top) + 12px);}
</style>
<style id="omni-nav-isolation">
/* Site nav must render identically on lander, checkout, and studio pages. */
.fixed.top-0.left-0.right-0.z-50 {
  z-index: 50 !important;
  isolation: isolate;
}
.fixed.top-0.left-0.right-0.z-50,
.fixed.top-0.left-0.right-0.z-50 *,
.fixed.top-0.left-0.right-0.z-50 *::before,
.fixed.top-0.left-0.right-0.z-50 *::after {
  box-sizing: border-box;
}
.omni-lander-wrap .promo-bar {
  position: sticky;
  top: calc(var(--nav-height, 64px) + env(safe-area-inset-top, 0px));
  z-index: 40;
}
</style>
<script data-flow-config>
window.__LANDER_BASE=<?= json_encode($__web, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_CHECKOUT_URL=<?= json_encode($__checkout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_OFFER_LINKS=<?= json_encode((object)($__kk_offer_links ?? []), JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_CHECKOUT=<?= json_encode($__registercheckout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_CREATE=<?= json_encode($__registercreate, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_BILLING=<?= json_encode($__registercheckout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_TOKEN="registercheckout";
window.__KK_STEP1LINK=<?= json_encode($__step1link, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_IS_CHECKOUT=<?= json_encode($__is_checkout ?? false); ?>;
window.__OMNI_HOME_DEAD=1;
</script>

</head>
<body class="bg-[#05060f] text-white">
<!-- chrome:stripped -->
<main class="min-h-screen bg-[#05060f]">
<div class="plansNewTiers-page relative min-h-screen overflow-x-hidden bg-[#05060f] text-white">
<!-- Background glow layers -->
<div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-0 overflow-hidden">
<div class="absolute inset-0" style="background:radial-gradient(1200px 600px at 20% 0%, rgba(139,92,255,0.18), transparent 60%), radial-gradient(900px 500px at 80% 30%, rgba(53,215,255,0.12), transparent 60%), radial-gradient(700px 400px at 50% 100%, rgba(255,79,216,0.12), transparent 60%)"></div>
<div class="absolute h-[700px] w-[700px] rounded-full" style="left:5%;top:10%;background:radial-gradient(closest-side, rgba(139,92,255,0.55), transparent);filter:blur(110px)"></div>
<div class="absolute h-[600px] w-[600px] rounded-full" style="right:5%;top:20%;background:radial-gradient(closest-side, rgba(53,215,255,0.45), transparent);filter:blur(120px)"></div>
<div class="absolute h-[700px] w-[700px] rounded-full" style="left:30%;top:60%;background:radial-gradient(closest-side, rgba(255,79,216,0.38), transparent);filter:blur(140px)"></div>
</div>
<div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-0 opacity-[0.18]" style="background-image:radial-gradient(rgba(255,255,255,0.5) 1px, transparent 1px);background-size:24px 24px;-webkit-mask-image:radial-gradient(ellipse 80% 70% at 50% 30%, black 30%, transparent 80%);mask-image:radial-gradient(ellipse 80% 70% at 50% 30%, black 30%, transparent 80%)"></div>
<div class="relative z-10 px-4 pb-32 pt-24 sm:pt-28">
<div class="mx-auto w-full max-w-[1240px]">
<!-- Hero -->
<div class="text-center">
<h1 class="text-5xl font-extrabold leading-[0.95] tracking-[-0.04em] text-white sm:text-7xl">Unlock 140+ AI Tools<br/>
<span class="bg-clip-text text-transparent animate-plansGradient" style="background-image:linear-gradient(110deg, #8B5CFF 0%, #35D7FF 45%, #FF4FD8 100%);background-size:200% 100%">in One Dashboard</span>
</h1>
<p class="mx-auto mt-5 max-w-2xl text-base text-white/60 sm:text-lg">Create videos, images, music, voiceovers, agents, and content faster with AIPU.</p>
<!-- Countdown -->
<div aria-live="polite" class="mt-7 flex justify-center gap-2 sm:gap-3">
<div class="inline-flex min-w-[74px] flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2.5 backdrop-blur-md sm:min-w-[90px] sm:px-4 sm:py-3">
<span class="text-2xl font-extrabold tabular-nums tracking-tight text-white sm:text-3xl" data-cd-d="">00</span>
<span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/50">Days</span>
</div>
<div class="inline-flex min-w-[74px] flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2.5 backdrop-blur-md sm:min-w-[90px] sm:px-4 sm:py-3">
<span class="text-2xl font-extrabold tabular-nums tracking-tight text-white sm:text-3xl" data-cd-h="">00</span>
<span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/50">Hrs</span>
</div>
<div class="inline-flex min-w-[74px] flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2.5 backdrop-blur-md sm:min-w-[90px] sm:px-4 sm:py-3">
<span class="text-2xl font-extrabold tabular-nums tracking-tight text-white sm:text-3xl" data-cd-m="">00</span>
<span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/50">Min</span>
</div>
<div class="inline-flex min-w-[74px] flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2.5 backdrop-blur-md sm:min-w-[90px] sm:px-4 sm:py-3">
<span class="text-2xl font-extrabold tabular-nums tracking-tight text-white sm:text-3xl" data-cd-s="">00</span>
<span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/50">Sec</span>
</div>
</div>
</div>
<!-- Stats -->
<div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
<div class="flex items-center gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 backdrop-blur-md">
<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.03]">
<svg aria-hidden="true" class="h-4 w-4 text-white/70" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path></svg>
</div>
<div class="min-w-0"><div class="text-base font-bold tracking-tight text-white sm:text-lg">4.8★</div><div class="text-[11px] uppercase tracking-wider text-white/45">Avg rating</div></div>
</div>
<div class="flex items-center gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 backdrop-blur-md">
<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.03]">
<svg aria-hidden="true" class="h-4 w-4 text-white/70" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M16 7h6v6"></path><path d="m22 7-8.5 8.5-5-5L2 17"></path></svg>
</div>
<div class="min-w-0"><div class="text-base font-bold tracking-tight text-white sm:text-lg">1,500+</div><div class="text-[11px] uppercase tracking-wider text-white/45">Active creators</div></div>
</div>
<div class="flex items-center gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 backdrop-blur-md">
<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.03]">
<svg aria-hidden="true" class="h-4 w-4 text-white/70" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"></path><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"></path><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"></path></svg>
</div>
<div class="min-w-0"><div class="text-base font-bold tracking-tight text-white sm:text-lg">140+</div><div class="text-[11px] uppercase tracking-wider text-white/45">AI models</div></div>
</div>
<div class="flex items-center gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 backdrop-blur-md">
<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.03]">
<svg aria-hidden="true" class="h-4 w-4 text-white/70" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
</div>
<div class="min-w-0"><div class="text-base font-bold tracking-tight text-white sm:text-lg">24/7</div><div class="text-[11px] uppercase tracking-wider text-white/45">Secure access</div></div>
</div>
</div>
<!-- Lifetime section -->
<section class="relative mt-20">
<div class="mb-6 text-center">
<div class="text-[11px] font-bold uppercase tracking-[0.22em] text-[#E6C97A]">Founder-style deal</div>
<h2 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">One payment. Lifetime access.</h2>
<p class="mx-auto mt-3 max-w-xl text-sm text-white/60">For people who already know they will keep creating. Pay once, skip the monthly mental math, and keep building forever.</p>
</div>
<div class="relative h-full rounded-3xl p-[1.5px]" style="background:conic-gradient(from 0deg at 50% 50%, #FFF8E7 0deg, #F5E1A4 60deg, #E6C97A 130deg, #4B1D52 220deg, #D4A647 300deg, #FFF8E7 360deg)">
<div class="relative overflow-hidden rounded-[calc(1.5rem-1.5px)] bg-[#0A0908] p-7 backdrop-blur-md sm:p-10" style="background-image:radial-gradient(circle at 14% 0%,rgba(230,201,122,0.18),transparent 38%),radial-gradient(circle at 88% 18%,rgba(75,29,82,0.32),transparent 42%),radial-gradient(circle at 50% 118%,rgba(212,166,71,0.14),transparent 46%),linear-gradient(160deg,#0A0908 0%,#120E0A 44%,#0A0807 100%);box-shadow:0 0 38px -14px rgba(230,201,122,0.45),0 0 70px -30px rgba(75,29,82,0.55),0 48px 96px -34px rgba(0,0,0,0.92)">
<div aria-hidden="true" class="plansNewTiersLuxuryGradientMotion pointer-events-none absolute inset-0 opacity-70 mix-blend-screen" style="background:radial-gradient(circle at 16% 16%, rgba(245,225,164,0.30), transparent 34%), radial-gradient(circle at 84% 14%, rgba(212,166,71,0.24), transparent 38%), radial-gradient(circle at 70% 92%, rgba(75,29,82,0.30), transparent 42%), radial-gradient(circle at 8% 86%, rgba(230,201,122,0.16), transparent 36%);background-size:240% 240%"></div>
<div aria-hidden="true" class="plansNewTiersLuxuryGlowA pointer-events-none absolute -left-28 -top-24 h-80 w-80 rounded-full bg-[#E6C97A]/20 blur-3xl"></div>
<div aria-hidden="true" class="plansNewTiersLuxuryGlowB pointer-events-none absolute -bottom-28 -right-24 h-96 w-96 rounded-full bg-[#4B1D52]/35 blur-3xl"></div>
<div aria-hidden="true" class="plansNewTiersMarketingShine pointer-events-none absolute -inset-x-32 top-0 h-28 -rotate-[10deg] bg-gradient-to-r from-transparent via-[#F5E1A4]/20 to-transparent blur-lg"></div>
<div aria-hidden="true" class="pointer-events-none absolute inset-0" style="background:linear-gradient(180deg,rgba(245,225,164,0.06),transparent 38%,rgba(0,0,0,0.42))"></div>
<div aria-hidden="true" class="pointer-events-none absolute inset-x-6 top-4 h-px bg-gradient-to-r from-transparent via-[#E6C97A]/55 to-transparent"></div>
<div class="relative z-10 grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
<div>
<div class="inline-flex items-center gap-1.5 rounded-full border border-[#E6C97A]/45 px-3.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-[#E6C97A] backdrop-blur-md" style="background:linear-gradient(135deg,#0A0908 0%,#15110B 55%,#1F140C 100%);box-shadow:0 0 22px -8px rgba(230,201,122,0.7)">
<svg aria-hidden="true" class="h-3 w-3 fill-[#E6C97A] text-[#E6C97A]" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>
                    VIP · Most Popular
                  </div>
<h3 class="mt-5 text-4xl font-black tracking-[-0.04em] text-white sm:text-5xl">Lifetime Access</h3>
<p class="mt-3 max-w-md text-sm leading-6 text-white/70">Lifetime access to current tools, trainings, updates, and eligible future platform features included in this plan.</p>
<div class="mt-7">
<span class="inline-flex items-center gap-2 rounded-full border border-[#E6C97A]/30 bg-black/35 px-2.5 py-1 text-xs text-[#F5E1A4] backdrop-blur-md" style="box-shadow:0 0 20px -14px rgba(230,201,122,0.85),inset 0 1px 0 rgba(245,225,164,0.10)">
<span class="relative flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#D4A647]/15">
<span class="absolute h-2.5 w-2.5 animate-ping rounded-full bg-[#E6C97A]/45"></span>
<svg aria-hidden="true" class="relative h-3.5 w-3.5 fill-[#E6C97A] text-[#F5E1A4]" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"></path></svg>
</span>
<span><span class="font-bold tabular-nums text-[#FFF8E7]">1,401</span> creators chose Lifetime in the last hour</span>
</span>
</div>
<div class="mt-7">
<div class="flex items-baseline gap-3">
<span class="text-lg text-[#E6C97A]/55 line-through">$1,900</span>
<span class="bg-clip-text text-7xl font-black tracking-[-0.055em] text-transparent tabular-nums sm:text-8xl" style="background-image:linear-gradient(115deg, #FFF8E7 0%, #F5E1A4 28%, #E6C97A 52%, #D4A647 76%, #FFF8E7 100%);background-size:220% 220%;filter:drop-shadow(0 0 24px rgba(230,201,122,0.35))">$399</span>
<span class="ml-1 inline-flex items-center rounded-md border border-[#E6C97A]/40 bg-[#E6C97A]/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.16em] text-[#F5E1A4]" style="box-shadow:0 0 18px -8px rgba(230,201,122,0.65)">80% OFF</span>
</div>
<p class="mt-2 text-xs font-medium text-[#E6C97A]/75">No monthly renewal · One-time payment · Instant access</p>
<div class="mt-4 inline-flex items-start gap-2.5 rounded-xl border border-[#E6C97A]/25 bg-[#E6C97A]/[0.07] px-3 py-2.5" style="box-shadow:inset 0 1px 0 rgba(245,225,164,0.08)">
<svg aria-hidden="true" class="mt-0.5 h-4 w-4 shrink-0 text-[#F5E1A4]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewbox="0 0 24 24"><path d="M3 3v18h18"></path><path d="m7 14 4-4 4 4 6-6"></path></svg>
<div class="text-left text-[12px] leading-snug text-[#FFF8E7]/90">
<span class="font-bold text-[#FFF8E7]">Pays for itself in under 10 months</span> of Premium ($39.99/mo) — then it's <span class="font-extrabold text-[#F5E1A4]">free for life</span>. About <span class="font-semibold tabular-nums text-[#F5E1A4]">$0.11/day</span> spread over 10 years.
                      </div>
</div>
</div>
<a class="group/cta relative mt-8 inline-flex w-full items-center justify-center overflow-hidden rounded-2xl border border-[#FFF8E7]/55 px-5 py-4 text-sm font-bold uppercase tracking-[0.16em] text-[#1A1208] transition-transform duration-150 hover:-translate-y-0.5 sm:w-auto sm:px-7" data-offer="lifetime" href="<?= htmlspecialchars($__kk_offer_links['lifetime'] ?? '#'); ?>" style="background:linear-gradient(110deg, #FFF8E7 0%, #F5E1A4 32%, #E6C97A 60%, #D4A647 88%, #FFF8E7 100%);box-shadow:0 0 24px -6px rgba(230,201,122,0.65),0 0 60px -26px rgba(75,29,82,0.55),0 26px 54px -18px rgba(0,0,0,0.85)">
<span class="relative z-10 inline-flex items-center justify-center">
<svg aria-hidden="true" class="relative z-10 mr-2 h-4 w-4 fill-[#1A1208] text-[#1A1208]" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>
<span class="relative z-10">Get Lifetime Access, $399</span>
</span>
</a>
<div class="mt-4 flex items-center gap-4 text-[11px] text-white/55">
<span class="inline-flex items-center gap-1"><svg aria-hidden="true" class="h-3 w-3" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><rect height="11" rx="2" ry="2" width="18" x="3" y="11"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Secure one-time payment</span>
<span class="inline-flex items-center gap-1"><svg aria-hidden="true" class="h-3 w-3 text-emerald-400" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"></path></svg> Instant access</span>
</div>
</div>
<div class="relative">
<div class="rounded-2xl border border-[#E6C97A]/25 bg-black/45 p-5 backdrop-blur-md">
<p class="text-[10px] font-bold uppercase tracking-[0.22em] text-[#F5E1A4]">Lifetime Access Bullet Points</p>
<ul class="mt-5 space-y-3.5">
<li class="flex items-start gap-3">
<svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
<div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Everything in Scale Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Unlimited access to 140+ AI models and tools for image, video, text, voice, music, agents, memory, knowledge bases, and agency workflows.</div></div>
</li>
<li class="flex items-start gap-3">
<svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
<div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Pay Once. No Monthly Renewal.</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Get lifetime access with one secure payment instead of paying monthly or yearly.</div></div>
</li>
<li class="flex items-start gap-3">
<svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
<div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">99 Prebuilt AI Agents Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Use ready-made agents for ads, emails, scripts, research, planning, content, support, and business workflows.</div></div>
</li>
<li class="flex items-start gap-3">
<svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
<div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">VIP AI University Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Get step-by-step training for AI tools, prompting, content creation, automation, agents, and business workflows.</div></div>
</li>
<li class="flex items-start gap-3">
<svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
<div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">VIP AIPU Community Access</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Join creators, marketers, founders, and business owners sharing AI workflows, ideas, and real use cases.</div></div>
</li>
<li class="flex items-start gap-3">
<svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
<div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Premium Prompt Vault Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Unlock prompts for ads, emails, landing pages, videos, social posts, AI images, AI videos, and business growth.</div></div>
</li>
</ul>
</div>
</div>
</div>
</div>
</div>
</section>
<!-- Billing toggle -->
<div class="mt-12 flex justify-center">
<div aria-label="Billing interval" class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-slate-950/70 p-1.5 backdrop-blur-md" role="tablist" style="box-shadow:0 8px 30px -12px rgba(0,0,0,0.6)">
<button aria-selected="true" class="relative rounded-full px-5 py-2.5 text-sm font-bold transition-colors bg-white text-slate-950" data-billing="monthly" role="tab" style="box-shadow:0 8px 30px -12px rgba(255,255,255,0.65)">Monthly</button>
<button aria-selected="false" class="relative inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold transition-colors text-white/70 hover:text-white" data-billing="yearly" role="tab">Yearly<span class="inline-flex items-center rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.16em] text-emerald-300">Save up to 50%</span></button>
</div>
</div>
<!-- Plans -->
<div class="relative">
<div class="relative mt-8 grid grid-cols-1 gap-5 sm:gap-6 lg:grid-cols-3 lg:items-stretch">
<!-- Creator -->
<div class="relative h-full" data-plan="creator">
<div class="relative flex h-full flex-col rounded-3xl border border-white/[0.08] bg-white/[0.02] p-7 backdrop-blur-md" style="box-shadow:0 30px 60px -30px rgba(0,0,0,0.6)">
<div>
<div class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.22em] text-indigo-300">
<svg aria-hidden="true" class="h-3.5 w-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect height="18" rx="2" width="18" x="3" y="4"></rect><path d="M3 10h18"></path></svg>Starter
                  </div>
<h3 class="mt-3 text-2xl font-extrabold tracking-tight text-white">Creator</h3>
<p class="mt-1 text-sm text-white/65">The essentials to dip your toes in — unlimited text AI plus a sampler of image, video, voice and music.</p>
</div>
<div class="mt-7">
<div class="mb-2 flex flex-wrap items-center gap-2 text-[12px]">
<span class="font-semibold tabular-nums text-white/42 line-through decoration-white/35 decoration-2" data-strike="">$29/mo</span>
<span class="inline-flex items-center gap-1 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-emerald-300" data-save="" style="box-shadow:0 0 18px -12px rgba(52,211,153,0.9)">SAVE 48%</span>
</div>
<div class="flex items-baseline gap-2">
<span class="relative inline-flex tabular-nums text-6xl font-extrabold tracking-[-0.04em] text-white" data-price="">$14.99</span>
<span class="ml-1 text-base text-white/55" data-period="">/mo</span>
</div>
<p class="mt-2 text-xs text-white/55" data-billing-note="">Flexible monthly billing. Cancel anytime.</p>
</div>
<ul class="mt-7 flex flex-col gap-3.5">
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">AI Image &amp; Video Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Create videos, images, ads, thumbnails, and social content with <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">SeeDance 2.0</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Veo 3.1</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Kling</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Nano Banana</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT Image 2</span>, and more.</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Unlimited Text AI Usage</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Use premium text models like <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT-5</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Claude 4</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Gemini Pro 3</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">DeepSeek</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Grok</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Llama</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Mistral</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Qwen</span>, and more without text usage limits.</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white"><span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">140+</span> AI Models &amp; Tools Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">One dashboard for text, image, video, voice, music, agents, automation, and creative workflows.</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Voice Agent</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">GPT Realtime · ElevenLabs · Fish Audio</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Music Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Suno · Udio · ElevenLabs Music</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">All AI Agents Included</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Persistent Memory</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Knowledge Bases (up to 50,000 PDFs)</div></div></li>
</ul>
<div class="mt-6 rounded-2xl border border-violet-300/15 bg-violet-500/[0.05] p-4">
<p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-violet-200"><svg aria-hidden="true" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" viewbox="0 0 24 24"><path d="M12 12c-2-2.67-4-4-6-4a4 4 0 1 0 0 8c2 0 4-1.33 6-4Zm0 0c2 2.67 4 4 6 4a4 4 0 0 0 0-8c-2 0-4 1.33-6 4Z"></path></svg> Unlimited Text Models</p>
<ul class="mt-3.5 flex flex-col gap-2.5">
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Gemini 3.1 Pro</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>GPT-5.4</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Kimi K2.5</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Grok 4.1 Fast</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>DeepSeek V3.2</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
</ul>
</div>
<div class="mt-6 rounded-2xl border border-white/10 bg-black/30 p-4">
<p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-white/55"><span class="text-violet-300">∞</span> Unlimited &amp; Free Gens</p>
<ul class="mt-3.5 flex flex-col gap-2.5">
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Seedream 4.5<span data-gen-info="" data-gen-name="Seedream 4.5" data-gen-type="free"></span></span>
<span class="inline-flex shrink-0 items-center rounded-md border border-[#E6C97A]/30 bg-[#E6C97A]/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-[#F5E1A4]">300 Free Gens</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Flux Schnell<span data-gen-info="" data-gen-name="Flux Schnell" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Flux Pro<span data-gen-info="" data-gen-name="Flux Pro" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Seedance 2.0 Fast<span data-gen-info="" data-gen-name="Seedance 2.0 Fast" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300">Full Access</span>
</li>
</ul>
</div>
<div class="mt-6 rounded-2xl border border-white/[0.06] bg-white/[0.025] p-4">
<p class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/55">Best for</p>
<p class="mt-1.5 text-sm leading-6 text-white/80">First-time AI users testing the waters before going all-in. Upgrade anytime with one click.</p>
</div>
<a class="mt-7 inline-flex w-full items-center justify-center rounded-2xl border border-white/15 bg-white/[0.04] px-5 py-3.5 text-sm font-semibold text-white transition-all duration-200 hover:border-white/25 hover:bg-white/[0.08]" data-cta="" href="<?= htmlspecialchars($__kk_offer_links['creatormonthly'] ?? '#'); ?>"><span data-cta-label="">Choose Creator</span><svg aria-hidden="true" class="ml-2 h-4 w-4" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></a>
<div class="mt-3 flex items-center justify-center gap-4 text-[11px] text-white/55">
<span class="inline-flex items-center gap-1"><svg aria-hidden="true" class="h-3 w-3" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><rect height="11" rx="2" ry="2" width="18" x="3" y="11"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Secure checkout</span>
<span class="inline-flex items-center gap-1"><svg aria-hidden="true" class="h-3 w-3 text-emerald-400" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"></path></svg> Cancel anytime</span>
</div>
</div>
</div>
<!-- Studio (popular) -->
<div class="relative h-full scroll-mt-24 sm:scroll-mt-28" data-plan="studio">
<div class="relative h-full rounded-3xl p-[1.5px]" style="background:linear-gradient(140deg, rgba(139,92,255,0.85) 0%, rgba(192,38,211,0.7) 45%, rgba(53,215,255,0.55) 100%)">
<div class="relative h-full" style="transform-style:preserve-3d;transform:perspective(1200px)">
<div class="relative flex h-full flex-col rounded-3xl border border-fuchsia-400/30 bg-[#0a0c1f] p-7 backdrop-blur-md" style="background-image:radial-gradient(ellipse at top,rgba(139,92,255,0.15),transparent 55%),radial-gradient(ellipse at bottom right,rgba(192,38,211,0.10),transparent 50%);box-shadow:0 30px 60px -30px rgba(139,92,255,0.55)">
<div class="pointer-events-none absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap">
<div class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-fuchsia-400/45 bg-slate-950/95 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-fuchsia-200 backdrop-blur-md" style="box-shadow:0 10px 30px -12px rgba(139,92,255,0.65)">
<svg aria-hidden="true" class="h-3 w-3 text-fuchsia-300" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>Most Popular · Best Value
                      </div>
</div>
<div>
<div class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.22em] text-fuchsia-300">
<svg aria-hidden="true" class="h-3.5 w-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c-2-2.67-4-4-6-4a4 4 0 1 0 0 8c2 0 4-1.33 6-4Zm0 0c2 2.67 4 4 6 4a4 4 0 0 0 0-8c-2 0-4 1.33-6 4Z"></path></svg>Unlimited / Premium
                      </div>
<h3 class="mt-3 text-2xl font-extrabold tracking-tight text-white">Studio</h3>
<p class="mt-1 text-sm text-white/65">Unlimited everything for serious creators. All 140+ models, full Seedance &amp; Veo, every premium image model — no caps, no surprises.</p>
</div>
<div class="mt-7">
<div class="mb-2 flex flex-wrap items-center gap-2 text-[12px]">
<span class="font-semibold tabular-nums text-white/42 line-through decoration-white/35 decoration-2" data-strike="">$79/mo</span>
<span class="inline-flex items-center gap-1 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-emerald-300" data-save="" style="box-shadow:0 0 18px -12px rgba(52,211,153,0.9)">SAVE 49%</span>
</div>
<div class="flex items-baseline gap-2">
<span class="relative inline-flex tabular-nums text-6xl font-extrabold tracking-[-0.04em] text-white" data-price="">$39.99</span>
<span class="ml-1 text-base text-white/55" data-period="">/mo</span>
</div>
<p class="mt-2 text-xs text-white/55" data-billing-note="">Flexible monthly billing. Cancel anytime.</p>
</div>
<div class="mt-5 rounded-2xl border border-fuchsia-300/30 p-4" style="background:linear-gradient(135deg, rgba(192,38,211,0.14) 0%, rgba(139,92,255,0.10) 50%, rgba(53,215,255,0.08) 100%);box-shadow:0 0 28px -12px rgba(139,92,255,0.55), inset 0 1px 0 rgba(255,255,255,0.06)">
<div class="flex items-center gap-2">
<span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-fuchsia-500 via-violet-500 to-cyan-500 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-[0.14em] text-white" style="box-shadow:0 0 18px -6px rgba(192,38,211,0.7)">
<svg aria-hidden="true" class="h-3 w-3" fill="currentColor" viewbox="0 0 24 24"><path d="M13.5 0c-.6 4.5-3.6 7.5-3.6 11.4 0 2.5 1.6 4.4 3.9 4.4 1.7 0 3.1-1 3.6-2.4.6 1.3 1.2 2.7 1.2 4.2 0 3.5-2.8 6.4-6.6 6.4-3.9 0-7-3.1-7-7 0-5.2 4.9-7.8 8.5-16Z"></path></svg>
                          Best Value
                        </span>
<span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-fuchsia-200" data-per-day="">~$1.33/day</span>
</div>
<p class="mt-2 text-[12.5px] leading-snug text-white/80">Replaces <span class="font-bold text-white">$200+/mo</span> of separate subscriptions — Midjourney, Runway, ChatGPT Plus, ElevenLabs and Claude Pro combined.</p>
<p class="mt-2 flex items-center gap-1.5 text-[11px] font-semibold text-fuchsia-100/85">
<svg aria-hidden="true" class="h-3 w-3 text-fuchsia-300" fill="currentColor" viewbox="0 0 24 24"><path d="M9 12l2 2 4-4 6 6-1.5 1.5L15 13l-4 4-4-4 1.5-1.5z"></path><circle cx="12" cy="12" fill="none" opacity="0.35" r="10" stroke="currentColor" stroke-width="1.5"></circle></svg>
                        Picked by <span class="font-extrabold text-white">73%</span> of new members this month
                      </p>
</div>
<ul class="mt-7 flex flex-col gap-3.5">
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">AI Image &amp; Video Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Create videos, images, ads, thumbnails, and social content with <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">SeeDance 2.0</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Veo 3.1</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Kling</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Nano Banana</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT Image 2</span>, and more.</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Unlimited Text AI Usage</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Use premium text models like <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT-5</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Claude 4</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Gemini Pro 3</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">DeepSeek</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Grok</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Llama</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Mistral</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Qwen</span>, and more without text usage limits.</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white"><span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">140+</span> AI Models &amp; Tools Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">One dashboard for text, image, video, voice, music, agents, automation, and creative workflows.</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Voice Agent</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">GPT Realtime · ElevenLabs · Fish Audio</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Music Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Suno · Udio · ElevenLabs Music</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">All AI Agents Included</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Everything in Creator, plus</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Up to 5 users</div></div></li>
</ul>
<div class="mt-6 rounded-2xl border border-fuchsia-300/20 bg-fuchsia-500/[0.06] p-4">
<p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-fuchsia-200"><svg aria-hidden="true" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" viewbox="0 0 24 24"><path d="M12 12c-2-2.67-4-4-6-4a4 4 0 1 0 0 8c2 0 4-1.33 6-4Zm0 0c2 2.67 4 4 6 4a4 4 0 0 0 0-8c-2 0-4 1.33-6 4Z"></path></svg> Unlimited Text Models</p>
<ul class="mt-3.5 flex flex-col gap-2.5">
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Gemini 3.1 Pro</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>GPT-5.4</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Kimi K2.5</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Grok 4.1 Fast</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>DeepSeek V3.2</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
</ul>
</div>
<div class="mt-6 rounded-2xl border border-white/10 bg-black/30 p-4">
<p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-white/55"><span class="text-violet-300">∞</span> Unlimited &amp; Free Gens</p>
<ul class="mt-3.5 flex flex-col gap-2.5">
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Seedream 4.5<span data-gen-info="" data-gen-name="Seedream 4.5" data-gen-type="free"></span></span>
<span class="inline-flex shrink-0 items-center rounded-md border border-[#E6C97A]/30 bg-[#E6C97A]/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-[#F5E1A4]">3,000 Free Gens</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Flux Schnell<span data-gen-info="" data-gen-name="Flux Schnell" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Flux Pro<span data-gen-info="" data-gen-name="Flux Pro" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Nano Banana<span data-gen-info="" data-gen-name="Nano Banana" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Nano Banana 2<span data-gen-info="" data-gen-name="Nano Banana 2" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>GPT Image 2<span data-gen-info="" data-gen-name="GPT Image 2" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Imagen Premium<span data-gen-info="" data-gen-name="Imagen Premium" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
</ul>
</div>
<div class="mt-6 rounded-2xl border border-white/10 bg-white/[0.04] p-4">
<p class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/55">Best for</p>
<p class="mt-1.5 text-sm leading-6 text-white/80">Serious creators, freelancers, and founders who run AI daily and want everything unlocked without thinking about it.</p>
</div>
<a class="relative mt-7 inline-flex w-full items-center justify-center overflow-hidden rounded-2xl px-5 py-3.5 text-sm font-bold text-white transition-transform duration-150 hover:-translate-y-0.5" data-cta="" href="<?= htmlspecialchars($__kk_offer_links['premiummonthly'] ?? '#'); ?>" style="background:linear-gradient(110deg, #6366F1 0%, #8B5CFF 50%, #C026D3 100%);box-shadow:0 20px 40px -12px rgba(139,92,255,0.6)"><span class="relative z-10 inline-flex items-center justify-center"><svg aria-hidden="true" class="mr-2 h-4 w-4" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path></svg><span data-cta-label="">Choose Studio</span></span></a>
<div class="mt-3 flex items-center justify-center gap-4 text-[11px] text-white/55">
<span class="inline-flex items-center gap-1"><svg aria-hidden="true" class="h-3 w-3" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><rect height="11" rx="2" ry="2" width="18" x="3" y="11"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Secure checkout</span>
<span class="inline-flex items-center gap-1"><svg aria-hidden="true" class="h-3 w-3 text-emerald-400" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"></path></svg> Cancel anytime</span>
</div>
</div>
</div>
</div>
</div>
<!-- Scale -->
<div class="relative h-full" data-plan="scale">
<div class="relative flex h-full flex-col rounded-3xl border border-white/[0.08] bg-white/[0.02] p-7 backdrop-blur-md" style="box-shadow:0 30px 60px -30px rgba(0,0,0,0.6)">
<div>
<div class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.22em] text-amber-300">
<svg aria-hidden="true" class="h-3.5 w-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"></path><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"></path><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"></path></svg>Agency · For Teams
                  </div>
<h3 class="mt-3 text-2xl font-extrabold tracking-tight text-white">Scale</h3>
<p class="mt-1 text-sm text-white/65">Built for 5-10 user agencies running multiple clients. Everything in Studio, plus extra seats, shared workflows and 4K outputs.</p>
<p class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-amber-300/25 bg-amber-500/10 px-2.5 py-1 text-[10.5px] font-semibold text-amber-100/85"><svg aria-hidden="true" class="h-3 w-3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" viewbox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg> Solo creator? Studio gives you the same unlimited models for less.</p>
</div>
<div class="mt-7">
<div class="mb-2 flex flex-wrap items-center gap-2 text-[12px]">
<span class="font-semibold tabular-nums text-white/42 line-through decoration-white/35 decoration-2" data-strike="">$199/mo</span>
<span class="inline-flex items-center gap-1 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-emerald-300" data-save="" style="box-shadow:0 0 18px -12px rgba(52,211,153,0.9)">SAVE 50%</span>
</div>
<div class="flex items-baseline gap-2">
<span class="relative inline-flex tabular-nums text-6xl font-extrabold tracking-[-0.04em] text-white" data-price="">$99.99</span>
<span class="ml-1 text-base text-white/55" data-period="">/mo</span>
</div>
<p class="mt-2 text-xs text-white/55" data-billing-note="">Flexible monthly billing. Cancel anytime.</p>
</div>
<ul class="mt-7 flex flex-col gap-3.5">
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">AI Image &amp; Video Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Create videos, images, ads, thumbnails, and social content with <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">SeeDance 2.0</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Veo 3.1</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Kling</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Nano Banana</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT Image 2</span>, and more.</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Unlimited Text AI Usage</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Use premium text models like <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT-5</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Claude 4</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Gemini Pro 3</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">DeepSeek</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Grok</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Llama</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Mistral</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Qwen</span>, and more without text usage limits.</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white"><span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">140+</span> AI Models &amp; Tools Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">One dashboard for text, image, video, voice, music, agents, automation, and creative workflows.</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Voice Agent</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">GPT Realtime · ElevenLabs · Fish Audio</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Music Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Suno · Udio · ElevenLabs Music</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">All AI Agents Included</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Everything in Creator, plus</div></div></li>
<li class="flex items-start gap-3"><svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><circle cx="12" cy="12" opacity="0.3" r="10" stroke-width="1.4"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Up to 10 users</div></div></li>
</ul>
<div class="mt-6 rounded-2xl border border-amber-300/20 bg-amber-500/[0.06] p-4">
<p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-amber-200"><svg aria-hidden="true" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" viewbox="0 0 24 24"><path d="M12 12c-2-2.67-4-4-6-4a4 4 0 1 0 0 8c2 0 4-1.33 6-4Zm0 0c2 2.67 4 4 6 4a4 4 0 0 0 0-8c-2 0-4 1.33-6 4Z"></path></svg> Unlimited Text Models</p>
<ul class="mt-3.5 flex flex-col gap-2.5">
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Gemini 3.1 Pro</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>GPT-5.4</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Kimi K2.5</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Grok 4.1 Fast</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>DeepSeek V3.2</span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
</ul>
</div>
<div class="mt-6 rounded-2xl border border-white/10 bg-black/30 p-4">
<p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-white/55"><span class="text-violet-300">∞</span> Unlimited &amp; Free Gens</p>
<ul class="mt-3.5 flex flex-col gap-2.5">
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Seedance 2.0<span data-gen-info="" data-gen-name="Seedance 2.0" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Kling 3.0<span data-gen-info="" data-gen-name="Kling 3.0" data-gen-type="free"></span></span>
<span class="inline-flex shrink-0 items-center rounded-md border border-[#E6C97A]/30 bg-[#E6C97A]/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-[#F5E1A4]">1,500 Free Gens</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Veo 3.1<span data-gen-info="" data-gen-name="Veo 3.1" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>PixVerse V6<span data-gen-info="" data-gen-name="PixVerse V6" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Wan 2.7<span data-gen-info="" data-gen-name="Wan 2.7" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Seedream 4.5<span class="inline-flex shrink-0 items-center rounded-md border border-violet-400/30 bg-violet-500/15 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-[0.08em] text-violet-200">4K</span><span data-gen-info="" data-gen-name="Seedream 4.5 (4K)" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Flux Pro<span data-gen-info="" data-gen-name="Flux Pro" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Nano Banana 2<span class="inline-flex shrink-0 items-center rounded-md border border-violet-400/30 bg-violet-500/15 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-[0.08em] text-violet-200">4K</span><span data-gen-info="" data-gen-name="Nano Banana 2 (4K)" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>GPT Image 2<span data-gen-info="" data-gen-name="GPT Image 2" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
<li class="flex items-center justify-between gap-3">
<span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg aria-hidden="true" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Imagen Premium<span data-gen-info="" data-gen-name="Imagen Premium" data-gen-type="unlim"></span></span>
<span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
</li>
</ul>
</div>
<div class="mt-6 rounded-2xl border border-white/[0.06] bg-white/[0.025] p-4">
<p class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/55">Best for</p>
<p class="mt-1.5 text-sm leading-6 text-white/80">Agencies of 5-10 creators handling multi-client output, white-label delivery, and shared brand knowledge bases.</p>
</div>
<a class="mt-7 inline-flex w-full items-center justify-center rounded-2xl border border-white/15 bg-white/[0.04] px-5 py-3.5 text-sm font-semibold text-white transition-all duration-200 hover:border-white/25 hover:bg-white/[0.08]" data-cta="" href="<?= htmlspecialchars($__kk_offer_links['promonthly'] ?? '#'); ?>"><span data-cta-label="">Choose Scale</span><svg aria-hidden="true" class="ml-2 h-4 w-4" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></a>
<div class="mt-3 flex items-center justify-center gap-4 text-[11px] text-white/55">
<span class="inline-flex items-center gap-1"><svg aria-hidden="true" class="h-3 w-3" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><rect height="11" rx="2" ry="2" width="18" x="3" y="11"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Secure checkout</span>
<span class="inline-flex items-center gap-1"><svg aria-hidden="true" class="h-3 w-3 text-emerald-400" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"></path></svg> Cancel anytime</span>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</main>
<script>
(function () {
  var PLANS = {
    creator: {
      monthly: { strike: '$29/mo', price: '$14.99', period: '/mo', save: 'SAVE 48%', note: 'Flexible monthly billing. Cancel anytime.', token: 'creatormonthly', cta: 'Choose Creator' },
      yearly:  { strike: '$359/yr', price: '$99', period: '/yr', save: 'SAVE 50%', note: 'Billed annually. Cancel anytime.', token: 'creatoryearly', cta: 'Choose Creator' }
    },
    studio: {
      monthly: { strike: '$79/mo', price: '$39.99', period: '/mo', save: 'SAVE 49%', note: 'Flexible monthly billing. Cancel anytime.', token: 'premiummonthly', cta: 'Choose Studio', perDay: '~$1.33/day' },
      yearly:  { strike: '$798/yr', price: '$398', period: '/yr', save: 'SAVE 50%', note: 'Billed annually. Cancel anytime.', token: 'premiumyearly', cta: 'Choose Studio', perDay: '~$1.09/day' }
    },
    scale: {
      monthly: { strike: '$199/mo', price: '$99.99', period: '/mo', save: 'SAVE 50%', note: 'Flexible monthly billing. Cancel anytime.', token: 'promonthly', cta: 'Choose Scale' },
      yearly:  { strike: '$1,996/yr', price: '$998', period: '/yr', save: 'SAVE 50%', note: 'Billed annually. Cancel anytime.', token: 'proyearly', cta: 'Choose Scale' }
    }
  };

  var billing = 'monthly';
  var toggleBtns = document.querySelectorAll('[data-billing]');

  function checkoutUrl(token) { return ((window.__KK_OFFER_LINKS||{})[token]||window.__KK_REGISTER_CHECKOUT||'#'); }

  function applyBilling(mode) {
    billing = mode;
    toggleBtns.forEach(function (btn) {
      var active = btn.getAttribute('data-billing') === mode;
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
      if (active) {
        btn.classList.add('bg-white', 'text-slate-950');
        btn.classList.remove('text-white/70', 'hover:text-white');
        btn.style.boxShadow = '0 8px 30px -12px rgba(255,255,255,0.65)';
      } else {
        btn.classList.remove('bg-white', 'text-slate-950');
        btn.classList.add('text-white/70', 'hover:text-white');
        btn.style.boxShadow = '';
      }
    });
    document.querySelectorAll('[data-plan]').forEach(function (card) {
      var key = card.getAttribute('data-plan');
      var plan = PLANS[key] && PLANS[key][mode];
      if (!plan) return;
      var strikeEl = card.querySelector('[data-strike]');
      var priceEl = card.querySelector('[data-price]');
      var periodEl = card.querySelector('[data-period]');
      var saveEl = card.querySelector('[data-save]');
      var noteEl = card.querySelector('[data-billing-note]');
      var ctaEl = card.querySelector('[data-cta]');
      var ctaLabel = card.querySelector('[data-cta-label]');
      var perDayEl = card.querySelector('[data-per-day]');
      if (strikeEl) strikeEl.textContent = plan.strike;
      if (priceEl) priceEl.textContent = plan.price;
      if (periodEl) periodEl.textContent = plan.period;
      if (saveEl) saveEl.textContent = plan.save;
      if (noteEl) noteEl.textContent = plan.note;
      if (ctaLabel) ctaLabel.textContent = plan.cta;
      if (perDayEl && plan.perDay) perDayEl.textContent = plan.perDay;
      if (ctaEl) { ctaEl.href = checkoutUrl(plan.token); ctaEl.setAttribute('data-offer', plan.token); }
    });
  }

  toggleBtns.forEach(function (btn) {
    btn.addEventListener('click', function () { applyBilling(btn.getAttribute('data-billing')); });
  });
  applyBilling('monthly');

  var DURATION = (2 * 24 * 3600 + 11 * 3600 + 47 * 60 + 33) * 1000;
  var key = 'aipu_pricing_v2_cd';
  var end = parseInt(sessionStorage.getItem(key), 10);
  if (!end || end < Date.now()) { end = Date.now() + DURATION; sessionStorage.setItem(key, end); }
  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function tick() {
    var diff = Math.max(0, end - Date.now());
    var d = Math.floor(diff / 86400000); diff -= d * 86400000;
    var h = Math.floor(diff / 3600000); diff -= h * 3600000;
    var m = Math.floor(diff / 60000); diff -= m * 60000;
    var s = Math.floor(diff / 1000);
    var dd = document.querySelector('[data-cd-d]'), hh = document.querySelector('[data-cd-h]'),
        mm = document.querySelector('[data-cd-m]'), ss = document.querySelector('[data-cd-s]');
    if (dd) dd.textContent = pad(d);
    if (hh) hh.textContent = pad(h);
    if (mm) mm.textContent = pad(m);
    if (ss) ss.textContent = pad(s);
  }
  tick();
  setInterval(tick, 1000);

  /* ---------------------------------------------------------------
   * Gen info tooltips (free / unlimited models)
   * Tooltip shows a "Buy until" date one day away + a 12-hour timer.
   * --------------------------------------------------------------- */
  function buyUntilDate() {
    var d = new Date();
    d.setDate(d.getDate() + 1);
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
  }
  var infoSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>';
  var clockSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>';

  document.querySelectorAll('[data-gen-info]').forEach(function (el) {
    var type = el.getAttribute('data-gen-type');
    var name = el.getAttribute('data-gen-name') || '';
    var isFree = type === 'free';
    var title = isFree ? 'Free Gens for ' + name : 'Get unlimited generations for ' + name;
    var availability = isFree ? 'Available on web' : 'Available for 1 year after purchase on web';
    el.className = 'gen-info';
    el.setAttribute('tabindex', '0');
    el.setAttribute('role', 'button');
    el.setAttribute('aria-label', title);
    el.innerHTML =
      infoSvg +
      '<span class="gen-tip" role="tooltip">' +
        '<span class="gen-tip-title">' + title + '</span>' +
        '<span class="gen-tip-row">Buy until: ' + buyUntilDate() + '</span>' +
        '<span class="gen-tip-timer">' + clockSvg + 'Ends in 12 hours</span>' +
        '<span class="gen-tip-row">' + availability + '</span>' +
      '</span>';
  });

  /* ---------------------------------------------------------------
   * Footer trust-building popups
   * --------------------------------------------------------------- */
  var ic = {
    check: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>',
    shield: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg>',
    spark: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>'
  };
  var badgesDefault = [
    { ico: ic.check, label: '14-day money-back guarantee' },
    { ico: ic.check, label: '256-bit encrypted checkout' },
    { ico: ic.check, label: '265,000+ active creators' }
  ];
  function feat(t, s) { return { t: t, s: s }; }

  var POPUPS = {
    'ai-agent': {
      eyebrow: 'Product · AI Agents',
      title: 'Deploy AI agents that actually get work done',
      intro: 'AIPU ships with 99 prebuilt, production-ready agents, plus the tools to build your own. They run real workflows so you stop copy-pasting between tabs.',
      items: [
        feat('99 prebuilt agents included', 'Ready-made agents for ads, emails, scripts, research, planning, content, support and full business workflows.'),
        feat('Persistent memory', 'Your agents remember context, brand voice and past projects across every session.'),
        feat('Connect your knowledge', 'Ground agents on your own knowledge bases (up to 50,000 PDFs) for accurate, on-brand output.')
      ],
      badges: badgesDefault
    },
    'ai-studio': {
      eyebrow: 'Product · AI Studio',
      title: 'One dashboard. 140+ frontier AI models.',
      intro: 'Stop paying for five separate subscriptions. AI Studio puts every leading model for text, image, video, voice and music in a single workspace, always kept up to date.',
      items: [
        feat('140+ models & tools', 'GPT-5, Claude 4, Gemini 3, SeeDance 2.0, Veo 3.1, Kling, Nano Banana, Flux and many more.'),
        feat('New models added weekly', 'The moment a new frontier model launches, it shows up in your plan, no extra cost.'),
        feat('No usage games', 'Unlimited daily text generation and generous credits for premium media.')
      ],
      badges: badgesDefault
    },
    'create-studio': {
      eyebrow: 'Product · Create Studio',
      title: 'Produce scroll-stopping video and images',
      intro: 'Create Studio is the end-to-end production suite, generate, edit, upscale and export ready-to-post content without ever touching Photoshop or Premiere.',
      items: [
        feat('Video generation & editing', 'SeeDance 2.0, Veo 3.1 and Kling 3.0 with trimming, restyling and auto b-roll.'),
        feat('Image creation & retouch', 'Inpaint, swap backgrounds, expand the frame and restyle in a click.'),
        feat('4K upscaling built in', 'Turn any clip or image into crisp, print-ready 4K in a single pass.')
      ],
      badges: badgesDefault
    },
    'voice-agents': {
      eyebrow: 'Product · Voice Agents',
      title: 'Studio-grade voice, dubbing & realtime agents',
      intro: 'Clone a voice, score a scene or launch a realtime voice agent in seconds, powered by the best audio models on the market.',
      items: [
        feat('Realtime voice agents', 'GPT Realtime, ElevenLabs and Fish Audio for natural, low-latency conversations.'),
        feat('Voiceovers & dubbing', 'Generate narration and dub into any language with frame-accurate lipsync.'),
        feat('Original music', 'Suno, Udio and ElevenLabs Music for fully licensed soundtracks.')
      ],
      badges: badgesDefault
    },
    'knowledge-base': {
      eyebrow: 'Product · Knowledge Base',
      title: 'Give every AI your knowledge',
      intro: 'Upload your docs once and every agent, chat and workflow answers from your real data, accurate, sourced and private.',
      items: [
        feat('Up to 50,000 PDFs', 'Massive context with fast, reliable retrieval across all your documents.'),
        feat('Grounded, sourced answers', 'Reduce hallucinations, responses cite your own material.'),
        feat('Your data stays yours', 'Never used for model training. You keep full rights to your content.')
      ],
      badges: badgesDefault
    },
    'pricing': {
      eyebrow: 'Pricing',
      title: 'One membership. Every tool. Real value.',
      intro: 'Replace Midjourney, Runway, ElevenLabs and more with a single plan. Pick monthly, yearly, or lock in Lifetime Access and never pay again.',
      items: [
        feat('Lifetime Access, $399', 'Pay once, keep building forever. 80% off the regular $1,900 price.'),
        feat('Plans for every creator', 'Creator, Studio and Scale, from solo builders to 10-person agencies.'),
        feat('14-day money-back guarantee', "Try it risk-free. If it's not for you, email support within 14 days for a full refund.")
      ],
      badges: badgesDefault
    },
    'community': {
      eyebrow: 'Resources · Community',
      title: 'Join 265,000+ creators building with AI',
      intro: 'You are never building alone. The AIPU community shares workflows, prompts, agents and real use cases every single day.',
      items: [
        feat('Active, helpful community', 'Creators, marketers, founders and agencies trading what actually works.'),
        feat('Premium Prompt Vault', 'Battle-tested prompts for ads, emails, landing pages, video and growth.'),
        feat('VIP AI University', 'Step-by-step training for tools, prompting, automation and agents.')
      ],
      badges: [
        { ico: ic.spark, label: '4.9 / 5 average rating' },
        { ico: ic.check, label: '12M+ creations made' },
        { ico: ic.check, label: 'New workflows weekly' }
      ]
    },
    'contact': {
      eyebrow: 'Resources · Contact',
      title: 'Real humans, ready to help',
      intro: 'Questions before you buy? Our team responds fast and we back every plan with a no-questions-asked guarantee.',
      items: [
        feat('Responsive support team', 'Email support@aiprofessionalsuniversity.com and get a real answer, not a bot loop.'),
        feat('14-day money-back guarantee', 'Buy with total confidence, full refund within 14 days, no questions asked.'),
        feat('Priority support on teams', 'Studio and Scale plans get faster, dedicated assistance.')
      ],
      badges: badgesDefault,
      ctaLabel: 'See plans & get started'
    },
    'privacy': {
      eyebrow: 'Legal · Privacy Policy',
      title: 'Your data is protected, and never sold',
      intro: 'Privacy is built in, not bolted on. We use bank-grade encryption and we never train models on your private content.',
      items: [
        feat('Never trained on your data', 'Your prompts, files and outputs stay yours and are never used to train models.'),
        feat('256-bit encrypted', 'Payments and data are protected with industry-standard encryption end to end.'),
        feat('SOC 2 Type 2 in progress', 'We hold ourselves to enterprise-grade security and compliance standards.')
      ],
      badges: badgesDefault
    },
    'terms': {
      eyebrow: 'Legal · Terms of Service',
      title: 'Fair, transparent terms, no fine-print traps',
      intro: 'No lock-in, no surprise fees. Cancel anytime and you keep full rights to everything you create.',
      items: [
        feat('Cancel anytime', 'No contracts and no lock-in. Manage everything from your dashboard.'),
        feat('You own your outputs', 'Full commercial rights to use, edit and publish what you generate.'),
        feat('Clear, honest billing', 'Plans renew at the listed price, change or cancel before renewal, no tricks.')
      ],
      badges: badgesDefault
    },
    'data-deletion': {
      eyebrow: 'Legal · Data Deletion',
      title: 'Delete your data anytime, in one request',
      intro: 'You stay in control. Request full deletion of your account and data whenever you want, we honor it promptly.',
      items: [
        feat('One-click deletion request', 'Ask us to remove your data and we process it without hassle.'),
        feat('Complete removal', 'Your account, content and personal data are fully erased on request.'),
        feat('Transparent process', "We confirm every step so you always know what's happening with your data.")
      ],
      badges: badgesDefault
    },
    'acceptable-use': {
      eyebrow: 'Legal · Acceptable Use',
      title: 'A safe, fair platform for every creator',
      intro: 'Clear guidelines keep AIPU fast, safe and trustworthy for the entire community, so honest creators always get the best experience.',
      items: [
        feat('Fair-use protection', 'Sensible limits keep the platform fast for everyone, not throttled by abuse.'),
        feat('Safety first', 'Clear rules prevent harmful or illegal use, protecting you and your work.'),
        feat('Built for real creators', 'Designed around individual, human creativity, the way you actually work.')
      ],
      badges: badgesDefault
    }
  };

  var scrim = document.querySelector('[data-modal-scrim]');
  var elEyebrow = scrim.querySelector('[data-modal-eyebrow]');
  var elTitle = scrim.querySelector('[data-modal-title]');
  var elIntro = scrim.querySelector('[data-modal-intro]');
  var elList = scrim.querySelector('[data-modal-list]');
  var elTrust = scrim.querySelector('[data-modal-trust]');
  var elCtaLabel = scrim.querySelector('[data-modal-cta-label]');
  var lastFocused = null;

  function renderPopup(data) {
    elEyebrow.innerHTML = ic.spark + '<span>' + data.eyebrow + '</span>';
    elTitle.textContent = data.title;
    elIntro.textContent = data.intro;
    elList.innerHTML = data.items.map(function (it) {
      return '<div class="or-modal-item">' +
        '<span class="or-modal-item-ico">' + ic.check + '</span>' +
        '<div><div class="or-modal-item-ttl">' + it.t + '</div>' +
        '<div class="or-modal-item-sub">' + it.s + '</div></div>' +
      '</div>';
    }).join('');
    var badges = data.badges || badgesDefault;
    elTrust.innerHTML = badges.map(function (b) {
      return '<span class="or-modal-badge">' + b.ico + b.label + '</span>';
    }).join('');
    elCtaLabel.textContent = data.ctaLabel || 'Start creating with AIPU';
  }

  function openPopup(key) {
    var data = POPUPS[key];
    if (!data) return;
    renderPopup(data);
    lastFocused = document.activeElement;
    scrim.hidden = false;
    requestAnimationFrame(function () { scrim.classList.add('open'); });
    document.body.style.overflow = 'hidden';
    var closeBtn = scrim.querySelector('[data-modal-close]');
    if (closeBtn) closeBtn.focus();
  }

  function closePopup() {
    scrim.classList.remove('open');
    document.body.style.overflow = '';
    setTimeout(function () { scrim.hidden = true; }, 220);
    if (lastFocused && lastFocused.focus) lastFocused.focus();
  }

  document.querySelectorAll('[data-popup]').forEach(function (btn) {
    btn.addEventListener('click', function () { openPopup(btn.getAttribute('data-popup')); });
  });
  scrim.addEventListener('click', function (e) {
    if (e.target === scrim || e.target.closest('[data-modal-close]')) closePopup();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !scrim.hidden) closePopup();
  });
})();
</script>
<footer class="relative border-t border-border bg-background"><div class="absolute inset-0 bg-linear-to-t from-background via-background-secondary/30 to-transparent pointer-events-none"></div><div class="container container--xl max-w-7xl mx-auto px-4 sm:px-6 relative py-10 sm:py-16"><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 mb-10 sm:mb-16"><div class="sm:col-span-2"><a class="flex items-center mb-4" href="#"><div class="flex-layout flex-row gap-0 items-center h-[42px]"><picture><source srcset="https://aipu-assets.b-cdn.net/sabrina__pricing-v2__aipu/04a83c76/assets/logo-aipu.webp" type="image/webp"/><img alt="AIPU" class="object-contain" data-nimg="1" decoding="async" height="42" loading="lazy" src="https://aipu-assets.b-cdn.net/sabrina__pricing-v2__aipu/04a83c76/assets/logo-aipu.png" srcset="https://aipu-assets.b-cdn.net/sabrina__pricing-v2__aipu/04a83c76/assets/logo-aipu.png 1x, https://aipu-assets.b-cdn.net/sabrina__pricing-v2__aipu/04a83c76/assets/logo-aipu.png 2x" style="color:transparent" width="160"/></picture></div></a><p class="typography typography--body2 text-sm text-(--text-secondary) mb-6 max-w-xs leading-relaxed">Deploy powerful AI agents for content creation, automation, and beyond.</p></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Product</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/sabrina__pricing-v2__aipu/createvideo.php<?= $__step1link; ?>">AI Agent</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/sabrina__pricing-v2__aipu/createvideo.php<?= $__step1link; ?>">AI Studio</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/sabrina__pricing-v2__aipu/createvideo.php<?= $__step1link; ?>">Create Studio</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/sabrina__pricing-v2__aipu/create-voice-agents.php<?= $__step1link; ?>">Voice Agents</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/sabrina__pricing-v2__aipu/knowledge-base.php<?= $__step1link; ?>">Knowledge Base</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="<?= $__checkout; ?>">Pricing</a></div></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Resources</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/sabrina__pricing-v2__aipu/help-center.php<?= $__step1link; ?>">Community</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="<?= $__checkout; ?>">Contact Us</a></div></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Legal</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/sabrina__pricing-v2__aipu/privacy-policy.php<?= $__step1link; ?>">Privacy Policy</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/sabrina__pricing-v2__aipu/terms-of-service.php<?= $__step1link; ?>">Terms of Service</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/sabrina__pricing-v2__aipu/data-deletion-request.php<?= $__step1link; ?>">Data Deletion Request</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/sabrina__pricing-v2__aipu/acceptable-use-policy.php<?= $__step1link; ?>">Acceptable Use Policy</a></div></div></div><div class="shrink-0 h-[1px] w-full bg-border divider my-4" data-orientation="horizontal" role="none"></div><div class="flex-layout gap-4 items-center justify-between flex-col md:flex-row pt-8"><span class="typography typography--caption text-xs leading-normal text-(--text-muted)">© <!-- -->2026<!-- --> <!-- -->AI Professionals University<!-- --> · <a class="link link--muted link--hover-underline link--external inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4" href="mailto:support@aiprofessionalsuniversity.com" rel="noopener noreferrer" target="_blank">support@aiprofessionalsuniversity.com</a></span><span class="typography typography--caption text-xs leading-normal text-(--text-muted) flex items-center gap-1">Made with <span class="text-accent">❤</span> for creators worldwide</span></div></div></footer>

<script defer="" src="https://aipu-assets.b-cdn.net/sabrina__pricing-v2__aipu/04a83c76/assets/static.js"></script>
<script data-flow-home-dead="">
(function(){
  function kill(){
    var els=document.querySelectorAll('a,button');
    for(var i=0;i<els.length;i++){
      var el=els[i];
      var t=(el.textContent||'').replace(/\s+/g,' ').trim();
      if(t==='Home'){
        if(el.tagName==='A'){el.setAttribute('href','#');}
        el.style.cursor='default';
        if(!el.__flowHomeDead){el.__flowHomeDead=1;el.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();},true);}
      }
    }
  }
  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',kill);}else{kill();}
  setTimeout(kill,300);setTimeout(kill,1200);
})();
</script>
</body>
</html>
