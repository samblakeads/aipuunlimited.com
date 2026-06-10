<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
require_once $base_path.'/includes/money.php';
?>
<?php require_once(__DIR__.'/_checkout-offers.php'); ?>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<title>Flash Sale - OmniRogue</title>
<meta name="description" content="OmniRogue Flash Sale: lifetime access for $399, or start monthly from $14.99. 140+ AI models, agents, voice, and video studios in one workspace.">
<meta name="robots" content="noindex,nofollow">
<link rel="icon" href="/flash-sale-kk/logo-omnirogue.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"></noscript>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
        colors: {
          border: 'rgba(255,255,255,0.08)',
          foreground: '#e8eaf0',
          background: '#05060f',
          gold: '#E6C97A',
          goldlite: '#FFF8E7',
        },
        keyframes: {
          fsPulse: { '0%,100%': { transform: 'scale(1)', opacity: '0.9' }, '50%': { transform: 'scale(1.06)', opacity: '1' } },
          fsShine: { '0%': { backgroundPosition: '0%' }, '100%': { backgroundPosition: '200%' } },
          fsFloatA: { '0%,100%': { transform: 'translate3d(0,0,0)' }, '50%': { transform: 'translate3d(18px,12px,0)' } },
          fsFloatB: { '0%,100%': { transform: 'translate3d(0,0,0)' }, '50%': { transform: 'translate3d(-16px,-14px,0)' } },
        },
        animation: {
          fsPulse: 'fsPulse 2.4s ease-in-out infinite',
          fsShine: 'fsShine 6s linear infinite',
          fsFloatA: 'fsFloatA 14s ease-in-out infinite',
          fsFloatB: 'fsFloatB 16s ease-in-out infinite',
        },
      },
    },
  };
</script>
<style>
  :root { --nav-height: 64px; --nav-bg: rgba(5,6,15,0.88); }
  html, body { margin: 0; padding: 0; background: #05060f; color: #e8eaf0; }
  body { font-family: Inter, system-ui, sans-serif; }
  a { text-decoration: none; }
  .tabular-nums { font-variant-numeric: tabular-nums; }
  .fs-grad-text {
    background: linear-gradient(120deg,#FFF8E7 0%,#F5E1A4 32%,#E6C97A 60%,#D4A647 88%,#FFF8E7 100%);
    background-size: 200% 100%;
    -webkit-background-clip: text; background-clip: text; color: transparent;
  }
  .fs-grad-violet {
    background: linear-gradient(120deg,#A78BFA 0%,#C084FC 50%,#F472B6 100%);
    -webkit-background-clip: text; background-clip: text; color: transparent;
  }
  .fs-cta-lifetime {
    background: linear-gradient(110deg,#FFF8E7 0%,#F5E1A4 32%,#E6C97A 60%,#D4A647 88%,#FFF8E7 100%);
    box-shadow: 0 0 24px -6px rgba(230,201,122,0.55), 0 26px 54px -18px rgba(0,0,0,0.85);
    color: #1A1208;
  }
  .fs-cta-violet {
    background: linear-gradient(110deg,#6366F1 0%,#8B5CFF 50%,#C026D3 100%);
    box-shadow: 0 20px 40px -12px rgba(139,92,255,0.6);
    color: #fff;
  }
  .fs-cta-ghost {
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.04);
    color: #fff;
  }
  .fs-cta-ghost:hover { border-color: rgba(255,255,255,0.28); background: rgba(255,255,255,0.08); }
  .fs-pill {
    display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 999px;
    border: 1px solid rgba(230,201,122,0.35); background: rgba(230,201,122,0.08);
    color: #F5E1A4; font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase;
  }
  .fs-card {
    border: 1px solid rgba(255,255,255,0.08); border-radius: 22px;
    background: linear-gradient(160deg,rgba(20,18,42,0.7) 0%,rgba(8,8,18,0.85) 100%);
    backdrop-filter: blur(8px);
  }
  .fs-card-gold {
    border-color: rgba(230,201,122,0.45);
    background: linear-gradient(160deg,rgba(75,29,82,0.42) 0%,rgba(10,8,18,0.92) 100%);
    box-shadow: 0 0 40px -10px rgba(230,201,122,0.25), inset 0 1px 0 rgba(255,255,255,0.04);
  }
  .fs-card-violet {
    border-color: rgba(139,92,255,0.4);
    box-shadow: 0 0 40px -10px rgba(139,92,255,0.35);
  }
  .fs-badge-best {
    background: linear-gradient(110deg,#FFF8E7,#E6C97A); color: #1A1208;
    font-size: 10px; font-weight: 900; letter-spacing: 0.16em; padding: 5px 10px; border-radius: 999px;
  }
  .fs-badge-pop {
    background: linear-gradient(110deg,#6366F1,#8B5CFF,#C026D3); color: #fff;
    font-size: 10px; font-weight: 900; letter-spacing: 0.16em; padding: 5px 10px; border-radius: 999px;
  }
  .fs-row { display: flex; gap: 10px; align-items: flex-start; padding: 12px 0; border-top: 1px solid rgba(255,255,255,0.06); font-size: 13.5px; line-height: 1.45; }
  .fs-row:first-child { border-top: none; }
  .fs-row svg { flex: 0 0 auto; margin-top: 2px; color: #34D399; }
  .fs-strike { color: rgba(255,255,255,0.4); text-decoration: line-through; font-weight: 600; }
  .fs-save {
    display: inline-flex; align-items: center; gap: 5px; padding: 4px 9px; border-radius: 999px;
    background: rgba(52,211,153,0.12); color: #6EE7B7; font-size: 11px; font-weight: 800; letter-spacing: 0.06em;
  }
  .fs-grid-card {
    border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 24px;
    background: linear-gradient(160deg,rgba(20,18,42,0.55) 0%,rgba(8,8,18,0.75) 100%);
  }
  details.faq-q summary { list-style: none; cursor: pointer; }
  details.faq-q summary::-webkit-details-marker { display: none; }
  details.faq-q[open] .faq-chev { transform: rotate(180deg); }
  .faq-chev { transition: transform .2s ease; }
  .scroll-x { scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; }
  .scroll-x > * { scroll-snap-align: start; }
  .fs-bg-glow {
    position: absolute; pointer-events: none; filter: blur(80px); opacity: 0.55; border-radius: 50%;
  }
</style>
</head>
<body class="bg-[#05060f] text-white">

<!-- Top nav -->
<div class="fixed top-0 left-0 right-0 z-50">
  <nav class="relative" style="padding-top:env(safe-area-inset-top);height:calc(var(--nav-height) + env(safe-area-inset-top))">
    <div class="absolute inset-0 backdrop-blur-xl border-b border-border" style="background-color:var(--nav-bg)"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 relative h-full">
      <div class="flex flex-row items-center justify-between h-full">
        <a class="flex items-center" href="/">
          <span class="h-[36px] sm:h-[42px] flex items-center">
            <img src="/flash-sale-kk/logo-omnirogue.png" alt="OmniRogue" width="160" height="42" class="object-contain w-auto h-[36px] sm:h-[42px]">
          </span>
        </a>
        <a href="#pricing" class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-bold text-[#1A1208] fs-cta-lifetime">
          Grab Lifetime · $399
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
      </div>
    </div>
  </nav>
</div>

<!-- Hero / Flash Sale -->
<section class="relative overflow-hidden pt-28 sm:pt-32 pb-20">
  <div class="fs-bg-glow" style="top:-80px;left:-80px;width:480px;height:480px;background:rgba(139,92,255,0.45);"></div>
  <div class="fs-bg-glow" style="top:120px;right:-100px;width:520px;height:520px;background:rgba(192,38,211,0.35);"></div>
  <div class="fs-bg-glow" style="bottom:-120px;left:30%;width:600px;height:600px;background:rgba(230,201,122,0.22);"></div>

  <div class="relative max-w-5xl mx-auto px-4 sm:px-6 text-center">
    <span class="fs-pill">Limited time offer</span>

    <h1 class="mt-6 text-5xl sm:text-7xl lg:text-8xl font-black tracking-tight leading-[0.92]">
      <span class="fs-grad-text animate-fsShine">FLASH SALE</span>
    </h1>

    <p class="mt-6 text-lg sm:text-xl text-white/70 max-w-2xl mx-auto leading-relaxed">
      Unlock OmniRogue today: 140+ AI models, prebuilt agents, voice studios, and video tools — one workspace, one price, built forever.
    </p>

    <!-- Countdown -->
    <div class="mt-8 inline-flex items-center justify-center gap-2 sm:gap-3" data-fs-countdown>
      <div class="fs-card px-4 sm:px-5 py-3 min-w-[72px]">
        <div class="text-2xl sm:text-3xl font-extrabold tabular-nums text-white" data-cd-d>02</div>
        <div class="text-[10px] uppercase tracking-[0.18em] text-white/40 mt-0.5">Days</div>
      </div>
      <div class="fs-card px-4 sm:px-5 py-3 min-w-[72px]">
        <div class="text-2xl sm:text-3xl font-extrabold tabular-nums text-white" data-cd-h>23</div>
        <div class="text-[10px] uppercase tracking-[0.18em] text-white/40 mt-0.5">Hours</div>
      </div>
      <div class="fs-card px-4 sm:px-5 py-3 min-w-[72px]">
        <div class="text-2xl sm:text-3xl font-extrabold tabular-nums text-white" data-cd-m>59</div>
        <div class="text-[10px] uppercase tracking-[0.18em] text-white/40 mt-0.5">Min</div>
      </div>
      <div class="fs-card px-4 sm:px-5 py-3 min-w-[72px]">
        <div class="text-2xl sm:text-3xl font-extrabold tabular-nums text-white" data-cd-s>00</div>
        <div class="text-[10px] uppercase tracking-[0.18em] text-white/40 mt-0.5">Sec</div>
      </div>
    </div>

    <div class="mt-5 flex items-center justify-center gap-2 text-sm text-white/60">
      <span class="text-amber-300">★★★★★</span>
      <span><b class="text-white">4.9 / 5</b> · 1,287 verified reviews</span>
    </div>

    <!-- Trust stat strip -->
    <div class="mt-12 grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 max-w-3xl mx-auto">
      <div class="fs-grid-card"><div class="text-2xl font-extrabold text-white">4.9★</div><div class="mt-1 text-[11px] uppercase tracking-wider text-white/50">Avg rating</div></div>
      <div class="fs-grid-card"><div class="text-2xl font-extrabold text-white">1,500+</div><div class="mt-1 text-[11px] uppercase tracking-wider text-white/50">Active creators</div></div>
      <div class="fs-grid-card"><div class="text-2xl font-extrabold text-white">140+</div><div class="mt-1 text-[11px] uppercase tracking-wider text-white/50">AI models</div></div>
      <div class="fs-grid-card"><div class="text-2xl font-extrabold text-white">24/7</div><div class="mt-1 text-[11px] uppercase tracking-wider text-white/50">Secure access</div></div>
    </div>
  </div>
</section>

<!-- Lifetime feature card -->
<section class="relative pb-20" id="lifetime">
  <div class="max-w-3xl mx-auto px-4 sm:px-6">
    <div class="text-center mb-8">
      <div class="text-[11px] uppercase tracking-[0.18em] font-bold text-amber-300/80">Founder-style deal</div>
      <h2 class="mt-3 text-3xl sm:text-5xl font-black tracking-tight leading-tight">One payment.<br><span class="fs-grad-text">Lifetime access.</span></h2>
      <p class="mt-5 text-white/65 leading-relaxed">For creators who already know they'll keep building. Pay once, skip the monthly mental math, and keep shipping forever.</p>
    </div>

    <div class="fs-card fs-card-gold p-7 sm:p-9 relative">
      <div class="absolute -top-3 left-1/2 -translate-x-1/2"><span class="fs-badge-best">Best Deal</span></div>
      <div class="flex items-center justify-between gap-4 mb-2">
        <h3 class="text-2xl font-extrabold tracking-tight">Lifetime Access</h3>
        <span class="fs-save">80% OFF</span>
      </div>
      <div class="flex items-end gap-3">
        <span class="fs-strike text-lg sm:text-xl">$1,900</span>
        <span class="text-5xl sm:text-6xl font-black fs-grad-text leading-none">$399</span>
        <span class="text-white/50 text-sm pb-1">one-time</span>
      </div>
      <p class="mt-3 text-sm text-white/60">No subscription, ever · Effectively $1.09/day for the first year</p>

      <div class="mt-6 grid sm:grid-cols-2 gap-x-6 gap-y-1">
        <div class="fs-row"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><div><b>Everything in Scale included</b><div class="text-white/55 text-xs mt-0.5">All 140+ models · agents · memory · knowledge bases</div></div></div>
        <div class="fs-row"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><div><b>Pay once, never billed again</b><div class="text-white/55 text-xs mt-0.5">One secure payment, no renewal anxiety</div></div></div>
        <div class="fs-row"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><div><b>99 prebuilt AI agents</b><div class="text-white/55 text-xs mt-0.5">Ads, emails, scripts, research — ready to run</div></div></div>
        <div class="fs-row"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><div><b>All future updates included</b><div class="text-white/55 text-xs mt-0.5">Every new model lands free in your account</div></div></div>
        <div class="fs-row"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><div><b>VIP OmniRogue community</b><div class="text-white/55 text-xs mt-0.5">Founders, marketers, and creators sharing playbooks</div></div></div>
        <div class="fs-row"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><div><b>Premium prompt vault</b><div class="text-white/55 text-xs mt-0.5">1,000+ battle-tested prompts for every workflow</div></div></div>
      </div>

      <div class="mt-7 flex flex-col sm:flex-row gap-3 items-stretch">
        <a data-cta href="<?= htmlspecialchars($__kk_lifetime_link ?? $__kk_offer_links['lifetime'] ?? $__kk_offer_links['lifetimeplan'] ?? ($link['step1link'] ?? '#')); ?>" class="fs-cta-lifetime inline-flex flex-1 items-center justify-center gap-2 rounded-2xl px-6 py-4 text-sm font-extrabold uppercase tracking-[0.14em]">
          Secure Lifetime Access
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
      </div>
      <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 items-center text-[11px] text-white/55">
        <span class="inline-flex items-center gap-1"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="color:#34D399"><path d="M20 6 9 17l-5-5"/></svg> 30-day refund</span>
        <span class="inline-flex items-center gap-1"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="color:#34D399"><path d="M20 6 9 17l-5-5"/></svg> Secure Stripe checkout</span>
        <span class="inline-flex items-center gap-1"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="color:#34D399"><path d="M20 6 9 17l-5-5"/></svg> Price locked forever</span>
      </div>
    </div>
  </div>
</section>

<!-- Value stack -->
<section class="relative py-20 border-t border-white/[0.06]">
  <div class="max-w-5xl mx-auto px-4 sm:px-6">
    <div class="text-center mb-12">
      <div class="text-[11px] uppercase tracking-[0.18em] font-bold text-amber-300/80">What you're actually getting</div>
      <h2 class="mt-3 text-3xl sm:text-4xl font-black tracking-tight">$7,378 in value. Today: <span class="fs-grad-text">$399.</span></h2>
      <p class="mt-4 text-white/65 max-w-2xl mx-auto">Most platforms charge for each model. With OmniRogue Lifetime, every line below is included — no upsells, no surprise overage bills.</p>
    </div>

    <div class="fs-card divide-y divide-white/[0.06]">
      <!-- Value rows -->
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white">Unlimited Video Generation</div>
          <div class="text-white/55 text-xs mt-0.5">Seedance · Kling · Sora · Veo 3.1 · Runway</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$1,188/yr</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white">Unlimited Image Generation</div>
          <div class="text-white/55 text-xs mt-0.5">Nano Banana · GPT Image · Imagen · Flux · Seedream</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$720/yr</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white">Unlimited Text & Reasoning</div>
          <div class="text-white/55 text-xs mt-0.5">GPT-5 · Claude 4 · Gemini Pro 3 · Grok · DeepSeek</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$960/yr</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white">Voice Agent & Audio Studios</div>
          <div class="text-white/55 text-xs mt-0.5">ElevenLabs · Fish Audio · Suno · Udio · custom voices</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$348/yr</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white">99 Prebuilt AI Agents</div>
          <div class="text-white/55 text-xs mt-0.5">Ads, emails, scripts, research, support, ops</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$1,200</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white">OmniRogue University</div>
          <div class="text-white/55 text-xs mt-0.5">Step-by-step training: prompting, automation, agent building</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$497</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white">VIP Community Access</div>
          <div class="text-white/55 text-xs mt-0.5">1,500+ creators, marketers, and founders trading real workflows</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$297/yr</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white flex items-center gap-2">VIP Inner Circle <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-300">Lifetime only</span></div>
          <div class="text-white/55 text-xs mt-0.5">Private mastermind · networking, business advice & custom trainings</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$997/yr</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white flex items-center gap-2">1:1 AI Growth Strategy Call <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-300">Lifetime only</span></div>
          <div class="text-white/55 text-xs mt-0.5">Mapped out 1-on-1 with our growth team</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$497</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white">Premium Prompt Vault</div>
          <div class="text-white/55 text-xs mt-0.5">1,000+ vetted prompts for ads, content, ops, growth</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$197</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white">Founder VIP Badge & Priority Support</div>
          <div class="text-white/55 text-xs mt-0.5">VIP queue · same-day reply · founder recognition</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">$197</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center">
        <div class="col-span-12 sm:col-span-8">
          <div class="font-bold text-white">All Future Models & Features</div>
          <div class="text-white/55 text-xs mt-0.5">Every new model we ship lands free in your account</div>
        </div>
        <div class="col-span-12 sm:col-span-4 sm:text-right text-amber-300 font-bold">∞</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-5 items-center bg-white/[0.03]">
        <div class="col-span-12 sm:col-span-8 font-extrabold text-white text-lg">Total real value</div>
        <div class="col-span-12 sm:col-span-4 sm:text-right font-black text-2xl fs-grad-text">$7,378</div>
      </div>
      <div class="grid grid-cols-12 gap-3 px-6 py-5 items-center">
        <div class="col-span-12 sm:col-span-8 font-extrabold text-white text-lg">Your one-time price today</div>
        <div class="col-span-12 sm:col-span-4 sm:text-right font-black text-3xl text-amber-300">$399</div>
      </div>
    </div>

    <div class="text-center mt-8">
      <a data-cta href="<?= htmlspecialchars($__kk_lifetime_link ?? $__kk_offer_links['lifetime'] ?? $__kk_offer_links['lifetimeplan'] ?? ($link['step1link'] ?? '#')); ?>" class="fs-cta-lifetime inline-flex items-center justify-center gap-2 rounded-2xl px-7 py-4 text-sm font-extrabold uppercase tracking-[0.14em]">
        Claim the $7,378 stack for $399
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- Pricing toggle / 3 plans -->
<section class="relative py-20 border-t border-white/[0.06]" id="pricing">
  <div class="max-w-6xl mx-auto px-4 sm:px-6">
    <div class="flex items-center justify-center mb-10">
      <div class="inline-flex items-center gap-1 rounded-2xl border border-white/10 bg-white/[0.04] p-1 text-sm font-bold">
        <button data-billing="monthly" aria-selected="true" class="rounded-xl px-5 py-2.5 transition-all bg-white text-slate-950" style="box-shadow:0 8px 30px -12px rgba(255,255,255,0.65)">Monthly</button>
        <button data-billing="yearly" aria-selected="false" class="rounded-xl px-5 py-2.5 transition-all text-white/70 hover:text-white inline-flex items-center gap-2">Yearly <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-300">Save up to 50%</span></button>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
      <!-- Creator -->
      <div class="fs-card p-7" data-plan="creator">
        <div class="text-[11px] uppercase tracking-[0.18em] font-bold text-white/55">Solo</div>
        <h3 class="mt-2 text-2xl font-extrabold tracking-tight">Creator</h3>
        <p class="mt-2 text-white/60 text-sm">One workspace. Every premium AI model. Zero usage limits — built for solo creators who want to ship faster.</p>
        <div class="mt-5 flex items-end gap-3">
          <span class="fs-strike" data-strike>$29/mo</span>
          <span class="fs-save" data-save>SAVE 48%</span>
        </div>
        <div class="flex items-baseline gap-1 mt-1">
          <span class="text-5xl font-black text-white" data-price>$14.99</span>
          <span class="text-white/55 text-sm" data-period>/mo</span>
        </div>
        <p class="mt-2 text-xs text-white/45" data-billing-note>Flexible monthly billing. Cancel anytime.</p>

        <div class="mt-5 space-y-3 text-sm">
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>140+ AI models</b> — image, video, voice, music, agents</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>Unlimited text AI</b> — GPT-5, Claude 4, Gemini Pro 3, Grok</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>Memory & knowledge bases</b> — persistent context + 50k PDFs</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>99 prebuilt AI agents</b> for ads, scripts, research</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>OmniRogue University + VIP community</span></div>
        </div>

        <a data-cta href="<?= htmlspecialchars($__kk_offer_links[\'creatormonthly\'] ?? \'#\'); ?>" class="fs-cta-ghost mt-7 inline-flex w-full items-center justify-center gap-2 rounded-2xl px-5 py-3.5 text-sm font-bold transition-all hover:-translate-y-0.5">
          <span data-cta-label>Choose Creator</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
      </div>

      <!-- Studio (Most Popular) -->
      <div class="fs-card fs-card-violet p-7 relative" data-plan="studio">
        <div class="absolute -top-3 left-1/2 -translate-x-1/2"><span class="fs-badge-pop">Most Popular</span></div>
        <div class="text-[11px] uppercase tracking-[0.18em] font-bold text-violet-300/80">Team · Up to 5 users</div>
        <h3 class="mt-2 text-2xl font-extrabold tracking-tight">Studio</h3>
        <p class="mt-2 text-white/60 text-sm">Replace 5 separate logins with one team workspace. Shared agents, shared memory, 2× faster output.</p>
        <div class="mt-5 flex items-end gap-3">
          <span class="fs-strike" data-strike>$59/mo</span>
          <span class="fs-save" data-save>SAVE 49%</span>
        </div>
        <div class="flex items-baseline gap-1 mt-1">
          <span class="text-5xl font-black text-white" data-price>$29.99</span>
          <span class="text-white/55 text-sm" data-period>/mo</span>
        </div>
        <p class="mt-2 text-xs text-white/45" data-billing-note>Flexible monthly billing. No per-seat contracts.</p>

        <div class="mt-3 text-[12px] font-bold uppercase tracking-wider text-violet-300/80">Everything in Creator, plus</div>

        <div class="mt-3 space-y-3 text-sm">
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>Up to 5 team seats</b> — shared workspaces & agents</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>2× faster generation</b> — priority queue for image, video, scripts</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>Admin controls & roles</b> — invite, manage, remove teammates</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>Priority email support</b> — under 4 business hours</span></div>
        </div>

        <a data-cta href="<?= htmlspecialchars($__kk_offer_links[\'studiomonthly\'] ?? \'#\'); ?>" class="fs-cta-violet mt-7 inline-flex w-full items-center justify-center gap-2 rounded-2xl px-5 py-3.5 text-sm font-extrabold transition-transform hover:-translate-y-0.5">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
          <span data-cta-label>Choose Studio</span>
        </a>
      </div>

      <!-- Scale -->
      <div class="fs-card p-7" data-plan="scale">
        <div class="text-[11px] uppercase tracking-[0.18em] font-bold text-white/55">Agency · Up to 10 users</div>
        <h3 class="mt-2 text-2xl font-extrabold tracking-tight">Scale</h3>
        <p class="mt-2 text-white/60 text-sm">Bill clients for AI work without per-seat overhead. 10 seats, multi-brand workspaces, priority everything.</p>
        <div class="mt-5 flex items-end gap-3">
          <span class="fs-strike" data-strike>$199/mo</span>
          <span class="fs-save" data-save>SAVE 50%</span>
        </div>
        <div class="flex items-baseline gap-1 mt-1">
          <span class="text-5xl font-black text-white" data-price>$99.99</span>
          <span class="text-white/55 text-sm" data-period>/mo</span>
        </div>
        <p class="mt-2 text-xs text-white/45" data-billing-note>Replaces ~$2,000/mo in standalone tool subscriptions.</p>

        <div class="mt-3 text-[12px] font-bold uppercase tracking-wider text-white/55">Everything in Studio, plus</div>

        <div class="mt-3 space-y-3 text-sm">
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>Up to 10 team users</b> — 2× the seats of Studio</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>Multi-brand workspaces</b> — isolated memory per client</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>Priority chat & email</b> — reply under 1 business hour</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span><b>1-on-1 onboarding</b> — launch your team in &lt; 24 hours</span></div>
          <div class="flex items-start gap-2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>Private <b>White-label Launch Masterclass</b> (Scale-exclusive)</span></div>
        </div>

        <a data-cta href="<?= htmlspecialchars($__kk_offer_links[\'scalemonthly\'] ?? \'#\'); ?>" class="fs-cta-ghost mt-7 inline-flex w-full items-center justify-center gap-2 rounded-2xl px-5 py-3.5 text-sm font-bold transition-all hover:-translate-y-0.5">
          <span data-cta-label>Power My Agency</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Guarantee -->
<section class="relative py-20 border-t border-white/[0.06]">
  <div class="max-w-4xl mx-auto px-4 sm:px-6">
    <div class="fs-card p-8 sm:p-12 grid sm:grid-cols-3 gap-8 items-center">
      <div class="text-center sm:col-span-1">
        <div class="inline-flex items-center justify-center w-28 h-28 rounded-full border-4 border-amber-300/30 bg-amber-500/10">
          <div class="text-center">
            <div class="text-[10px] uppercase tracking-[0.18em] text-amber-300/80 font-bold">30-Day</div>
            <div class="text-2xl font-black text-amber-300">100%</div>
            <div class="text-[10px] uppercase tracking-[0.18em] text-amber-300/80 font-bold">Money-Back</div>
          </div>
        </div>
      </div>
      <div class="sm:col-span-2">
        <div class="text-[11px] uppercase tracking-[0.18em] font-bold text-amber-300/80">Try Lifetime risk-free</div>
        <h2 class="mt-2 text-3xl font-extrabold tracking-tight">If you don't love it in 30 days, we'll refund 100%.</h2>
        <p class="mt-3 text-white/65 leading-relaxed">No questions. No forms. No “let me transfer you to a manager.” Email us inside 30 days and we'll send every penny back. Available exclusively on Lifetime VIP.</p>
        <div class="mt-5 grid sm:grid-cols-3 gap-3 text-xs">
          <div class="rounded-xl border border-white/10 bg-white/[0.04] p-3"><b class="block text-white">No questions asked</b><span class="text-white/55">A one-line email is enough.</span></div>
          <div class="rounded-xl border border-white/10 bg-white/[0.04] p-3"><b class="block text-white">Refunded in 48 hours</b><span class="text-white/55">Direct to your original payment.</span></div>
          <div class="rounded-xl border border-white/10 bg-white/[0.04] p-3"><b class="block text-white">Keep your work</b><span class="text-white/55">Export everything you made.</span></div>
        </div>
        <a data-cta href="<?= htmlspecialchars($__kk_lifetime_link ?? $__kk_offer_links['lifetime'] ?? $__kk_offer_links['lifetimeplan'] ?? ($link['step1link'] ?? '#')); ?>" class="mt-5 fs-cta-lifetime inline-flex items-center justify-center gap-2 rounded-2xl px-6 py-3 text-sm font-extrabold uppercase tracking-[0.14em]">
          Claim VIP Lifetime
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Features grid -->
<section class="relative py-20 border-t border-white/[0.06]">
  <div class="max-w-6xl mx-auto px-4 sm:px-6">
    <div class="text-center mb-12">
      <div class="text-[11px] uppercase tracking-[0.18em] font-bold text-violet-300/80">One workspace · 140+ models</div>
      <h2 class="mt-3 text-3xl sm:text-4xl font-black tracking-tight"><span class="fs-grad-violet">Everything.</span> Unlocked.</h2>
      <p class="mt-4 text-white/65 max-w-2xl mx-auto">Stop juggling 8 subscriptions. Open one tab, ask one question, get answers from the model best suited for the job.</p>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="fs-grid-card"><b class="block">All major video models</b><span class="text-white/55 text-sm mt-1 block">Veo 3.1, Kling, Sora, Seedance, Runway in one workflow.</span></div>
      <div class="fs-grid-card"><b class="block">Image models for everything</b><span class="text-white/55 text-sm mt-1 block">Nano Banana, GPT Image, Imagen 4, Flux, Seedream — native.</span></div>
      <div class="fs-grid-card"><b class="block">Text models without limits</b><span class="text-white/55 text-sm mt-1 block">GPT-5, Claude 4, Gemini Pro 3, Grok, DeepSeek — unlimited.</span></div>
      <div class="fs-grid-card"><b class="block">Voice & music studios</b><span class="text-white/55 text-sm mt-1 block">ElevenLabs, Fish Audio, Suno, Udio — ship voiceovers in minutes.</span></div>
      <div class="fs-grid-card"><b class="block">99 prebuilt AI agents</b><span class="text-white/55 text-sm mt-1 block">Ads, emails, research, content, ops — ready out of the box.</span></div>
      <div class="fs-grid-card"><b class="block">OmniRogue University</b><span class="text-white/55 text-sm mt-1 block">Step-by-step training. Get production-ready in a weekend.</span></div>
      <div class="fs-grid-card"><b class="block">VIP community</b><span class="text-white/55 text-sm mt-1 block">1,500+ creators trading workflows, leads, and live use-cases.</span></div>
      <div class="fs-grid-card"><b class="block">Premium prompt vault</b><span class="text-white/55 text-sm mt-1 block">1,000+ tested prompts for ads, emails, content & growth.</span></div>
      <div class="fs-grid-card"><b class="block">Future-proof access</b><span class="text-white/55 text-sm mt-1 block">Every model we add lands free in your account, forever.</span></div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="relative py-20 border-t border-white/[0.06]" id="faq">
  <div class="max-w-3xl mx-auto px-4 sm:px-6">
    <div class="text-center mb-10">
      <div class="text-[11px] uppercase tracking-[0.18em] font-bold text-white/55">Frequently asked</div>
      <h2 class="mt-3 text-3xl sm:text-4xl font-black tracking-tight">Quick answers before you buy.</h2>
    </div>
    <div class="space-y-3">
      <details class="faq-q fs-card p-5">
        <summary class="flex items-center justify-between gap-4 font-bold">
          <span>What if I don't love it? Can I get a refund?</span>
          <svg class="faq-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="mt-3 text-sm text-white/65 leading-relaxed">Lifetime is backed by a 30-day unconditional money-back guarantee. Email support@omnirogue.com within 30 days and we'll refund 100% — no questions, no forms. Monthly plans can be cancelled in one click and you won't be billed again.</p>
      </details>
      <details class="faq-q fs-card p-5">
        <summary class="flex items-center justify-between gap-4 font-bold">
          <span>Is Lifetime really lifetime? What about new models?</span>
          <svg class="faq-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="mt-3 text-sm text-white/65 leading-relaxed">Yes — one payment, forever. Every future AI model and tool we ship (text, image, video, voice, music, reasoning, agents) lands in your Lifetime account automatically at no extra cost. Your $399 price is locked.</p>
      </details>
      <details class="faq-q fs-card p-5">
        <summary class="flex items-center justify-between gap-4 font-bold">
          <span>Why is Lifetime $399 today? Will the price go up?</span>
          <svg class="faq-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="mt-3 text-sm text-white/65 leading-relaxed">We're seeding the platform with founding members. The regular Lifetime price is $1,900. At $399 today (80% off) we're capping the founding batch — once those spots are gone, Lifetime resets to $699, then to $1,900.</p>
      </details>
      <details class="faq-q fs-card p-5">
        <summary class="flex items-center justify-between gap-4 font-bold">
          <span>What's the difference between Creator, Studio, and Scale?</span>
          <svg class="faq-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="mt-3 text-sm text-white/65 leading-relaxed">All three plans include unlimited access to 140+ AI models, 99 prebuilt agents, OmniRogue University, the VIP community, memory, and knowledge bases. The difference is team size: Creator ($14.99/mo) is one solo seat; Studio ($29.99/mo) adds 5 team seats with shared workspaces and admin controls; Scale ($99.99/mo) doubles that to 10 seats with multi-brand workspaces and priority chat support.</p>
      </details>
      <details class="faq-q fs-card p-5">
        <summary class="flex items-center justify-between gap-4 font-bold">
          <span>Are there really no usage limits or hidden fees?</span>
          <svg class="faq-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="mt-3 text-sm text-white/65 leading-relaxed">Text models (GPT-5, Claude 4, Gemini Pro 3, DeepSeek, Grok and others) are truly unlimited on every plan — no per-message, per-token, or per-minute fees. Image, video, voice, and music generation use generous per-month credits sized for daily workflows. No surprise overage charges.</p>
      </details>
      <details class="faq-q fs-card p-5">
        <summary class="flex items-center justify-between gap-4 font-bold">
          <span>Is my data and payment information safe?</span>
          <svg class="faq-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="mt-3 text-sm text-white/65 leading-relaxed">We never see or store card data — payments are processed by Stripe (PCI DSS Level 1, the highest standard). All traffic uses 256-bit SSL encryption. We are GDPR and CCPA compliant. You can delete your data at any time from the dashboard or by emailing support.</p>
      </details>
      <details class="faq-q fs-card p-5">
        <summary class="flex items-center justify-between gap-4 font-bold">
          <span>Can I start with monthly and upgrade to Lifetime later?</span>
          <svg class="faq-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <p class="mt-3 text-sm text-white/65 leading-relaxed">Yes — but the Lifetime price is currently $399 (regular $1,900) and will increase to $699 once the founding spots sell out. Locking it in today saves you 27+ monthly subscription payments and protects you from future price increases.</p>
      </details>
    </div>
  </div>
</section>

<!-- Final CTA -->
<section class="relative py-24 border-t border-white/[0.06]">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
    <div class="text-[11px] uppercase tracking-[0.18em] font-bold text-amber-300/80">Last chance — today's price</div>
    <h2 class="mt-3 text-3xl sm:text-5xl font-black tracking-tight leading-[1.05]">
      Skip 27 months of monthly bills.<br>
      Lock in <span class="fs-grad-text">Lifetime for $399</span>.
    </h2>
    <p class="mt-5 text-white/65 max-w-xl mx-auto">$1,900 value · 80% off today · 30-day money-back guarantee · every future model included.</p>
    <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
      <a data-cta href="<?= htmlspecialchars($__kk_lifetime_link ?? $__kk_offer_links['lifetime'] ?? $__kk_offer_links['lifetimeplan'] ?? ($link['step1link'] ?? '#')); ?>" class="fs-cta-lifetime inline-flex items-center justify-center gap-2 rounded-2xl px-7 py-4 text-sm font-extrabold uppercase tracking-[0.14em]">
        Claim VIP Lifetime — $399 Once
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </a>
      <a data-cta href="<?= htmlspecialchars($__kk_offer_links[\'creatormonthly\'] ?? \'#\'); ?>" class="fs-cta-ghost inline-flex items-center justify-center gap-2 rounded-2xl px-6 py-4 text-sm font-bold">
        Not ready? Start monthly from $14.99
      </a>
    </div>
    <div class="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[11px] text-white/55">
      <span class="inline-flex items-center gap-1"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="color:#34D399"><path d="M20 6 9 17l-5-5"/></svg> 30-day refund</span>
      <span class="inline-flex items-center gap-1"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="color:#34D399"><path d="M20 6 9 17l-5-5"/></svg> Secure Stripe checkout</span>
      <span class="inline-flex items-center gap-1"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="color:#34D399"><path d="M20 6 9 17l-5-5"/></svg> 1,500+ creators trust OmniRogue</span>
      <span class="inline-flex items-center gap-1"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="color:#34D399"><path d="M20 6 9 17l-5-5"/></svg> SSL · PCI compliant</span>
    </div>
  </div>
</section>

<footer class="relative border-t border-white/[0.06] py-10">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 text-center text-xs text-white/45">
    © 2026 OmniRogue Inc. · support@omnirogue.com
  </div>
</footer>

<script>
(function () {
  /* Plan switching: monthly / yearly */
  var PLANS = {
    creator: {
      monthly: { strike: '$29/mo', price: '$14.99', period: '/mo', save: 'SAVE 48%', note: 'Flexible monthly billing. Cancel anytime.', token: 'creatormonthly', cta: 'Choose Creator' },
      yearly:  { strike: '$179/yr', price: '$99', period: '/yr', save: 'SAVE 45%', note: 'Billed annually. Cancel anytime.', token: 'creatoryearly', cta: 'Choose Creator' }
    },
    studio: {
      monthly: { strike: '$59/mo', price: '$29.99', period: '/mo', save: 'SAVE 49%', note: 'Flexible monthly billing. No per-seat contracts.', token: 'studiomonthly', cta: 'Choose Studio' },
      yearly:  { strike: '$359/yr', price: '$299', period: '/yr', save: 'SAVE 17%', note: 'Billed annually. Cancel anytime.', token: 'studioyearly', cta: 'Choose Studio' }
    },
    scale: {
      monthly: { strike: '$199/mo', price: '$99.99', period: '/mo', save: 'SAVE 50%', note: 'Replaces ~$2,000/mo in standalone tools.', token: 'scalemonthly', cta: 'Power My Agency' },
      yearly:  { strike: '$1,999/yr', price: '$998', period: '/yr', save: 'SAVE 17%', note: 'Billed annually. Cancel anytime.', token: 'scaleyearly', cta: 'Power My Agency' }
    }
  };

  var billing = 'monthly';
  var toggleBtns = document.querySelectorAll('[data-billing]');

  function checkoutUrl(token) { return '/signup?offer=' + encodeURIComponent(token); }

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
      if (strikeEl) strikeEl.textContent = plan.strike;
      if (priceEl) priceEl.textContent = plan.price;
      if (periodEl) periodEl.textContent = plan.period;
      if (saveEl) saveEl.textContent = plan.save;
      if (noteEl) noteEl.textContent = plan.note;
      if (ctaLabel) ctaLabel.textContent = plan.cta;
      if (ctaEl) { ctaEl.href = checkoutUrl(plan.token); ctaEl.setAttribute('data-offer', plan.token); }
    });
  }
  toggleBtns.forEach(function (btn) {
    btn.addEventListener('click', function () { applyBilling(btn.getAttribute('data-billing')); });
  });
  applyBilling('monthly');

  /* Countdown — persists for ~3 days via sessionStorage */
  var DURATION = (2 * 24 * 3600 + 23 * 3600 + 59 * 60 + 59) * 1000;
  var KEY = 'omnirogue_flash_sale_cd';
  var end = parseInt(sessionStorage.getItem(KEY), 10);
  if (!end || end < Date.now()) { end = Date.now() + DURATION; sessionStorage.setItem(KEY, end); }
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
})();
</script>
</body>
</html>
