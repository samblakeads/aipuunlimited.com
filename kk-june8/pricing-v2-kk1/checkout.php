<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
$money_php = $base_path.'/includes/money.php';
if (file_exists($money_php)) { require_once $money_php; }
/* Dev-mode fallback: ensure KK globals exist so pages render without money.php */
if (!isset($multi_page)) {
    $kk_dev_qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
    $multi_page = ['step1link' => $kk_dev_qs];
}
if (!isset($link))       { $link       = ['step1link' => '#']; }
if (!isset($offer))      { $offer      = []; }
?>
<?php require_once(__DIR__.'/_kk-config.php'); ?>
<?php require_once(__DIR__.'/_checkout-offers.php'); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<title>Plans v2 - OmniRogue</title>
<meta name="description" content="Pick your OmniRogue plan, Creator, Studio, Scale, or Lifetime Access. Unlimited GPT-5.4, Gemini 3.1 Pro, Nano Banana 2, Veo 3.1, Seedance and more.">
<link rel="icon" href="/pricing-v2-kk1/logo-omnirogue.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
<link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
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
<!-- chrome:head -->
<link rel="stylesheet" href="/checkouts-omni/_chrome/chrome.css?v=20260609d">
<!-- /chrome:head -->
</head>
<body class="bg-[#05060f] text-white">
<!-- kk-inject:v1 -->
<script>
window.__KK_CHECKOUT_URL  = <?= json_encode($__checkout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_PRESELL_HOME  = <?= json_encode($__presell_home, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_IS_CHECKOUT   = <?= $__is_checkout ? 'true' : 'false'; ?>;
window.__KK_STEP1LINK     = <?= json_encode($__step1link, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_OFFER_LINKS = {
  creatormonthly: <?= json_encode($__kk_offer_links['creatormonthly'] ?? '', JSON_UNESCAPED_SLASHES); ?>,
  creatoryearly:  <?= json_encode($__kk_offer_links['creatoryearly']  ?? '', JSON_UNESCAPED_SLASHES); ?>,
  studiomonthly:  <?= json_encode($__kk_offer_links['studiomonthly']  ?? '', JSON_UNESCAPED_SLASHES); ?>,
  studioyearly:   <?= json_encode($__kk_offer_links['studioyearly']   ?? '', JSON_UNESCAPED_SLASHES); ?>,
  premiummonthly: <?= json_encode($__kk_offer_links['premiummonthly'] ?? '', JSON_UNESCAPED_SLASHES); ?>,
  premiumyearly:  <?= json_encode($__kk_offer_links['premiumyearly']  ?? '', JSON_UNESCAPED_SLASHES); ?>,
  scalemonthly:   <?= json_encode($__kk_offer_links['scalemonthly']   ?? '', JSON_UNESCAPED_SLASHES); ?>,
  scaleyearly:    <?= json_encode($__kk_offer_links['scaleyearly']    ?? '', JSON_UNESCAPED_SLASHES); ?>,
  promonthly:     <?= json_encode($__kk_offer_links['promonthly']     ?? '', JSON_UNESCAPED_SLASHES); ?>,
  proyearly:      <?= json_encode($__kk_offer_links['proyearly']      ?? '', JSON_UNESCAPED_SLASHES); ?>,
  agencymonthly:  <?= json_encode($__kk_offer_links['agencymonthly']  ?? '', JSON_UNESCAPED_SLASHES); ?>,
  agencyyearly:   <?= json_encode($__kk_offer_links['agencyyearly']   ?? '', JSON_UNESCAPED_SLASHES); ?>,
  promaxmonthly:  <?= json_encode($__kk_offer_links['promaxmonthly']  ?? '', JSON_UNESCAPED_SLASHES); ?>,
  promaxyearly:   <?= json_encode($__kk_offer_links['promaxyearly']   ?? '', JSON_UNESCAPED_SLASHES); ?>,
  lifetime:       <?= json_encode($__kk_offer_links['lifetime']       ?? '', JSON_UNESCAPED_SLASHES); ?>,
  lifetimeplan:   <?= json_encode($__kk_offer_links['lifetimeplan']   ?? '', JSON_UNESCAPED_SLASHES); ?>
};
</script>

<!-- chrome:header -->
<!-- OmniRogue checkout header -->
<div class="or-nav-wrap" role="banner">
  <nav class="or-nav" aria-label="Primary">
    <div class="or-nav-bg" aria-hidden="true"></div>
    <div class="or-nav-inner">
      <button type="button" class="or-nav-logo" data-or-scroll="top" aria-label="OmniRogue - back to top">
        <img src="/checkouts-omni/logo-omnirogue.png" alt="OmniRogue" width="160" height="42" loading="eager" decoding="async">
      </button>

      <div class="or-nav-links">
        <a class="or-nav-link" href="/pricing-v2-kk1/index.php<?= $__step1link; ?>" style="text-decoration:none;">Home</a>

        <div style="position:relative;">
          <button type="button" class="or-nav-link" data-or-dropdown="create-studio" aria-haspopup="menu" aria-expanded="false">
            Create Studio
            <svg class="or-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="or-dropdown" role="menu" data-or-dropdown-menu="create-studio">
            <a class="or-dd-item" href="/pricing-v2-kk1/createvideo.php<?= $__step1link; ?>" role="menuitem" style="text-decoration:none;">
              <span class="or-dd-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2" ry="2"/></svg></span>
              <span class="or-dd-text"><span class="or-dd-title">AI Video</span><span class="or-dd-sub">Seedance 2.0, Veo 3.1, Kling 3.0</span></span>
            </a>
            <a class="or-dd-item" href="/pricing-v2-kk1/create-image.php<?= $__step1link; ?>" role="menuitem" style="text-decoration:none;">
              <span class="or-dd-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg></span>
              <span class="or-dd-text"><span class="or-dd-title">AI Image</span><span class="or-dd-sub">Nano Banana 2, GPT Image 2, Flux Pro</span></span>
            </a>
            <a class="or-dd-item" href="/pricing-v2-kk1/create-audio.php<?= $__step1link; ?>" role="menuitem" style="text-decoration:none;">
              <span class="or-dd-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h3l2-9 4 18 2-9 2 4h5"/></svg></span>
              <span class="or-dd-text"><span class="or-dd-title">Voice &amp; Audio</span><span class="or-dd-sub">ElevenLabs, Fish Audio, dubbing</span></span>
            </a>
            <a class="or-dd-item" href="/pricing-v2-kk1/create-music.php<?= $__step1link; ?>" role="menuitem" style="text-decoration:none;">
              <span class="or-dd-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></span>
              <span class="or-dd-text"><span class="or-dd-title">AI Music</span><span class="or-dd-sub">Suno, Udio, ElevenLabs Music</span></span>
            </a>
            <a class="or-dd-item" href="/pricing-v2-kk1/create-voice-agents.php<?= $__step1link; ?>" role="menuitem" style="text-decoration:none;">
              <span class="or-dd-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg></span>
              <span class="or-dd-text"><span class="or-dd-title">Voice Agents</span><span class="or-dd-sub">GPT Realtime, phone &amp; web agents</span></span>
            </a>
            <a class="or-dd-item" href="/pricing-v2-kk1/create-upscale.php<?= $__step1link; ?>" role="menuitem" style="text-decoration:none;">
              <span class="or-dd-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 21H3"/><path d="m6 6 6 6 6-6"/><path d="M6 18V9"/><path d="M18 18V9"/></svg></span>
              <span class="or-dd-text"><span class="or-dd-title">4K Upscaling</span><span class="or-dd-sub">Topaz, Magnific, Recraft HD</span></span>
            </a>
            <a class="or-dd-item" href="/pricing-v2-kk1/create-ai-chat.php<?= $__step1link; ?>" role="menuitem" style="text-decoration:none;">
              <span class="or-dd-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
              <span class="or-dd-text"><span class="or-dd-title">AI Chat</span><span class="or-dd-sub">GPT-5.4, Gemini 3.1 Pro, Claude, Grok</span></span>
            </a>
          </div>
        </div>

        <div style="position:relative;">
          <button type="button" class="or-nav-link" data-or-dropdown="library" aria-haspopup="menu" aria-expanded="false">
            Library
            <svg class="or-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="or-dropdown" role="menu" data-or-dropdown-menu="library">
            <a class="or-dd-item" href="/pricing-v2-kk1/gpt-library.php<?= $__step1link; ?>" role="menuitem" style="text-decoration:none;">
              <span class="or-dd-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
              <span class="or-dd-text"><span class="or-dd-title">GPT Library</span><span class="or-dd-sub">99+ prebuilt agents for every workflow</span></span>
            </a>
            <a class="or-dd-item" href="/pricing-v2-kk1/prompt-library.php<?= $__step1link; ?>" role="menuitem" style="text-decoration:none;">
              <span class="or-dd-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg></span>
              <span class="or-dd-text"><span class="or-dd-title">Prompt Library</span><span class="or-dd-sub">Battle-tested prompts for ads &amp; growth</span></span>
            </a>
            <a class="or-dd-item" href="/pricing-v2-kk1/knowledge-base.php<?= $__step1link; ?>" role="menuitem" style="text-decoration:none;">
              <span class="or-dd-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg></span>
              <span class="or-dd-text"><span class="or-dd-title">Knowledge Base</span><span class="or-dd-sub">Up to 50,000 PDFs per workspace</span></span>
            </a>
          </div>
        </div>

        <a class="or-nav-link" href="/pricing-v2-kk1/about.php<?= $__step1link; ?>" style="text-decoration:none;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          About Us
        </a>

        <button type="button" class="or-nav-link" data-or-scroll="plans">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>
          Pricing
        </button>

        <button type="button" class="or-nav-link or-nav-link-affiliate" data-or-popup="affiliate">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Become Affiliate
        </button>
      </div>

      <div class="or-nav-cta-cluster">
        <button type="button" class="or-nav-cta" data-or-scroll="plans">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
          Create Account
        </button>
        <button type="button" class="or-nav-secondary" data-or-scroll="plans">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
          Login
        </button>
      </div>

      <button type="button" class="or-nav-burger" data-or-mobile-toggle aria-label="Open menu" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>
      </button>
    </div>
  </nav>
</div>

<!-- Mobile sheet (full-screen, matches omnirogue.com mobile menu) -->
<div class="or-mobile-sheet" data-or-mobile-sheet role="dialog" aria-modal="true" aria-label="Menu">
  <div class="or-mobile-sheet-inner">
    <div class="or-mobile-head">
      <img src="/checkouts-omni/logo-omnirogue.png" alt="OmniRogue" style="height:36px;width:auto;">
      <button type="button" class="or-mobile-close" data-or-mobile-close aria-label="Close menu">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      </button>
    </div>
    <div class="or-mobile-list">
      <div class="or-mobile-label">Navigate</div>
      <a class="or-mobile-item" href="/pricing-v2-kk1/index.php<?= $__step1link; ?>" data-or-home-item style="text-decoration:none;">Home</a>
      <button type="button" class="or-mobile-item" data-or-studio-toggle aria-expanded="true">
        <svg class="or-mobile-ic or-mobile-ic-accent" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg>
        <span>Create Studio</span>
        <svg class="or-mobile-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <div class="or-mobile-sub or-mobile-sub-open" data-or-studio-sub>
        <div class="or-mobile-group"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg><span>AI Create</span></div>
        <a class="or-mobile-item" href="/pricing-v2-kk1/createvideo.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg><span>AI Video</span><span class="or-mobile-badge">New</span></a>
        <a class="or-mobile-item" href="/pricing-v2-kk1/create-image.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg><span>Image</span></a>
        <a class="or-mobile-item" href="/pricing-v2-kk1/create-audio.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm0 0a9 9 0 0 1 18 0m0 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z"/></svg><span>Audio</span></a>
        <a class="or-mobile-item" href="/pricing-v2-kk1/create-music.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg><span>Music</span><span class="or-mobile-badge">New</span></a>
        <div class="or-mobile-group"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"/><path d="m14 7 3 3"/><path d="M5 6v4"/><path d="M19 14v4"/><path d="M10 2v2"/><path d="M7 8H3"/><path d="M21 16h-4"/><path d="M11 3H9"/></svg><span>Omni Create</span></div>
        <a class="or-mobile-item" href="/pricing-v2-kk1/createvideo.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/></svg><span>OmniReels</span><span class="or-mobile-badge">New</span></a>
        <a class="or-mobile-item" href="/pricing-v2-kk1/create-audio.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19v3"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><rect x="9" y="2" width="6" height="13" rx="3"/></svg><span>Podcast</span></a>
        <div class="or-mobile-group"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span>AI Chat</span></div>
        <a class="or-mobile-item" href="/pricing-v2-kk1/create-ai-chat.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span>AI Chat</span><span class="or-mobile-badge">New</span></a>
        <div class="or-mobile-group"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v7.527a2 2 0 0 1-.211.896L4.72 20.55a1 1 0 0 0 .9 1.45h12.76a1 1 0 0 0 .9-1.45l-5.069-10.127A2 2 0 0 1 14 9.527V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/></svg><span>AI Tools</span></div>
        <a class="or-mobile-item" href="/pricing-v2-kk1/create-voice-agents.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19v3"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><rect x="9" y="2" width="6" height="13" rx="3"/></svg><span>Voice Agents</span></a>
        <a class="or-mobile-item" href="/pricing-v2-kk1/knowledge-base.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 6 4 14"/><path d="M12 6v14"/><path d="M8 8v12"/><path d="M4 4v16"/></svg><span>Knowledge Base</span></a>
      </div>
      <div class="or-mobile-label">Library</div>
      <a class="or-mobile-item" href="/pricing-v2-kk1/gpt-library.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg><span>GPT Library</span></a>
      <a class="or-mobile-item" href="/pricing-v2-kk1/prompt-library.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg><span>Prompt Library</span></a>
      <a class="or-mobile-item" href="/pricing-v2-kk1/about.php<?= $__step1link; ?>" style="text-decoration:none;"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><span>About Us</span></a>
      <button type="button" class="or-mobile-item" data-or-scroll="plans"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg><span>Pricing</span></button>
      <button type="button" class="or-mobile-item" data-or-popup="affiliate"><svg class="or-mobile-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg><span>Become Affiliate</span></button>
      <div class="or-mobile-cta-pair">
        <button type="button" class="or-mobile-cta" data-or-scroll="plans">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/></svg>
          Create Account
        </button>
        <button type="button" class="or-mobile-secondary" data-or-scroll="plans">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/></svg>
          Login
        </button>
      </div>
    </div>
  </div>
</div>
<!-- /chrome:header -->


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
          <h1 class="text-5xl font-extrabold leading-[0.95] tracking-[-0.04em] text-white sm:text-7xl">Unlock 140+ AI Tools<br>
            <span class="bg-clip-text text-transparent animate-plansGradient" style="background-image:linear-gradient(110deg, #8B5CFF 0%, #35D7FF 45%, #FF4FD8 100%);background-size:200% 100%">in One Dashboard</span>
          </h1>
          <p class="mx-auto mt-5 max-w-2xl text-base text-white/60 sm:text-lg">Create videos, images, music, voiceovers, agents, and content faster with OmniRogue.</p>

          <!-- Countdown -->
          <div class="mt-7 flex justify-center gap-2 sm:gap-3" aria-live="polite">
            <div class="inline-flex min-w-[74px] flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2.5 backdrop-blur-md sm:min-w-[90px] sm:px-4 sm:py-3">
              <span data-cd-d class="text-2xl font-extrabold tabular-nums tracking-tight text-white sm:text-3xl">00</span>
              <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/50">Days</span>
            </div>
            <div class="inline-flex min-w-[74px] flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2.5 backdrop-blur-md sm:min-w-[90px] sm:px-4 sm:py-3">
              <span data-cd-h class="text-2xl font-extrabold tabular-nums tracking-tight text-white sm:text-3xl">00</span>
              <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/50">Hrs</span>
            </div>
            <div class="inline-flex min-w-[74px] flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2.5 backdrop-blur-md sm:min-w-[90px] sm:px-4 sm:py-3">
              <span data-cd-m class="text-2xl font-extrabold tabular-nums tracking-tight text-white sm:text-3xl">00</span>
              <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/50">Min</span>
            </div>
            <div class="inline-flex min-w-[74px] flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] px-3 py-2.5 backdrop-blur-md sm:min-w-[90px] sm:px-4 sm:py-3">
              <span data-cd-s class="text-2xl font-extrabold tabular-nums tracking-tight text-white sm:text-3xl">00</span>
              <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/50">Sec</span>
            </div>
          </div>
        </div>

        <!-- Stats -->
        <div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
          <div class="flex items-center gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 backdrop-blur-md">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.03]">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-white/70" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path></svg>
            </div>
            <div class="min-w-0"><div class="text-base font-bold tracking-tight text-white sm:text-lg">4.9★</div><div class="text-[11px] uppercase tracking-wider text-white/45">Avg rating</div></div>
          </div>
          <div class="flex items-center gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 backdrop-blur-md">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.03]">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-white/70" aria-hidden="true"><path d="M16 7h6v6"></path><path d="m22 7-8.5 8.5-5-5L2 17"></path></svg>
            </div>
            <div class="min-w-0"><div class="text-base font-bold tracking-tight text-white sm:text-lg">250,000+</div><div class="text-[11px] uppercase tracking-wider text-white/45">Active creators</div></div>
          </div>
          <div class="flex items-center gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 backdrop-blur-md">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.03]">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-white/70" aria-hidden="true"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"></path><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"></path><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"></path></svg>
            </div>
            <div class="min-w-0"><div class="text-base font-bold tracking-tight text-white sm:text-lg">140+</div><div class="text-[11px] uppercase tracking-wider text-white/45">AI models</div></div>
          </div>
          <div class="flex items-center gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-4 py-3 backdrop-blur-md">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.03]">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-white/70" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 fill-[#E6C97A] text-[#E6C97A]" aria-hidden="true"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>
                    VIP · Most Popular
                  </div>
                  <h3 class="mt-5 text-4xl font-black tracking-[-0.04em] text-white sm:text-5xl">Lifetime Access</h3>
                  <p class="mt-3 max-w-md text-sm leading-6 text-white/70">Lifetime access to current tools, trainings, updates, and eligible future platform features included in this plan.</p>

                  <div class="mt-7">
                    <span class="inline-flex items-center gap-2 rounded-full border border-[#E6C97A]/30 bg-black/35 px-2.5 py-1 text-xs text-[#F5E1A4] backdrop-blur-md" style="box-shadow:0 0 20px -14px rgba(230,201,122,0.85),inset 0 1px 0 rgba(245,225,164,0.10)">
                      <span class="relative flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#D4A647]/15">
                        <span class="absolute h-2.5 w-2.5 animate-ping rounded-full bg-[#E6C97A]/45"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="relative h-3.5 w-3.5 fill-[#E6C97A] text-[#F5E1A4]" aria-hidden="true"><path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"></path></svg>
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
                      <svg viewBox="0 0 24 24" class="mt-0.5 h-4 w-4 shrink-0 text-[#F5E1A4]" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"></path><path d="m7 14 4-4 4 4 6-6"></path></svg>
                      <div class="text-left text-[12px] leading-snug text-[#FFF8E7]/90">
                        <span class="font-bold text-[#FFF8E7]">Pays for itself in under 10 months</span> of Premium ($39.99/mo) — then it's <span class="font-extrabold text-[#F5E1A4]">free for life</span>. About <span class="font-semibold tabular-nums text-[#F5E1A4]">$0.11/day</span> spread over 10 years.
                      </div>
                    </div>
                  </div>

                  <a href="<?= htmlspecialchars($__kk_offer_links['lifetime'] ?? '#', ENT_QUOTES, "UTF-8") ?>" data-offer="lifetime" class="group/cta relative mt-8 inline-flex w-full items-center justify-center overflow-hidden rounded-2xl border border-[#FFF8E7]/55 px-5 py-4 text-sm font-bold uppercase tracking-[0.16em] text-[#1A1208] transition-transform duration-150 hover:-translate-y-0.5 sm:w-auto sm:px-7" style="background:linear-gradient(110deg, #FFF8E7 0%, #F5E1A4 32%, #E6C97A 60%, #D4A647 88%, #FFF8E7 100%);box-shadow:0 0 24px -6px rgba(230,201,122,0.65),0 0 60px -26px rgba(75,29,82,0.55),0 26px 54px -18px rgba(0,0,0,0.85)">
                    <span class="relative z-10 inline-flex items-center justify-center">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="relative z-10 mr-2 h-4 w-4 fill-[#1A1208] text-[#1A1208]" aria-hidden="true"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>
                      <span class="relative z-10">Get Lifetime Access, $399</span>
                    </span>
                  </a>

                  <div class="mt-4 flex items-center gap-4 text-[11px] text-white/55">
                    <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Secure one-time payment</span>
                    <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 text-emerald-400" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg> Instant access</span>
                  </div>
                </div>

                <div class="relative">
                  <div class="rounded-2xl border border-[#E6C97A]/25 bg-black/45 p-5 backdrop-blur-md">
                    <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-[#F5E1A4]">Lifetime Access Bullet Points</p>
                    <ul class="mt-5 space-y-3.5">
                      <li class="flex items-start gap-3">
                        <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
                        <div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Everything in Scale Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Unlimited access to 140+ AI models and tools for image, video, text, voice, music, agents, memory, knowledge bases, and agency workflows.</div></div>
                      </li>
                      <li class="flex items-start gap-3">
                        <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
                        <div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Pay Once. No Monthly Renewal.</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Get lifetime access with one secure payment instead of paying monthly or yearly.</div></div>
                      </li>
                      <li class="flex items-start gap-3">
                        <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
                        <div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">99 Prebuilt AI Agents Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Use ready-made agents for ads, emails, scripts, research, planning, content, support, and business workflows.</div></div>
                      </li>
                      <li class="flex items-start gap-3">
                        <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
                        <div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">VIP AI University Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Get step-by-step training for AI tools, prompting, content creation, automation, agents, and business workflows.</div></div>
                      </li>
                      <li class="flex items-start gap-3">
                        <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
                        <div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">VIP OmniRogue Community Access</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Join creators, marketers, founders, and business owners sharing AI workflows, ideas, and real use cases.</div></div>
                      </li>
                      <li class="flex items-start gap-3">
                        <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#E6C97A]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg>
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
          <div role="tablist" aria-label="Billing interval" class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-slate-950/70 p-1.5 backdrop-blur-md" style="box-shadow:0 8px 30px -12px rgba(0,0,0,0.6)">
            <button role="tab" data-billing="monthly" aria-selected="true" class="relative rounded-full px-5 py-2.5 text-sm font-bold transition-colors bg-white text-slate-950" style="box-shadow:0 8px 30px -12px rgba(255,255,255,0.65)">Monthly</button>
            <button role="tab" data-billing="yearly" aria-selected="false" class="relative inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold transition-colors text-white/70 hover:text-white">Yearly<span class="inline-flex items-center rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.16em] text-emerald-300">Save up to 50%</span></button>
          </div>
        </div>

        <!-- Plans -->
        <div class="relative">
          <div class="relative mt-8 grid grid-cols-1 gap-5 sm:gap-6 md:grid-cols-2 xl:grid-cols-4 lg:items-stretch">

            <!-- Creator -->
            <div class="relative h-full" data-plan="creator">
              <div class="relative flex h-full flex-col rounded-3xl border border-white/[0.08] bg-white/[0.02] p-7 backdrop-blur-md" style="box-shadow:0 30px 60px -30px rgba(0,0,0,0.6)">
                <div>
                  <div class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.22em] text-indigo-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path></svg>Starter
                  </div>
                  <h3 class="mt-3 text-2xl font-extrabold tracking-tight text-white">Creator</h3>
                  <p class="mt-1 text-sm text-white/65">The essentials to dip your toes in — unlimited text AI plus a sampler of image, video, voice and music.</p>
                </div>
                <div class="mt-7">
                  <div class="mb-2 flex flex-wrap items-center gap-2 text-[12px]">
                    <span data-strike class="font-semibold tabular-nums text-white/42 line-through decoration-white/35 decoration-2">$29/mo</span>
                    <span data-save class="inline-flex items-center gap-1 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-emerald-300" style="box-shadow:0 0 18px -12px rgba(52,211,153,0.9)">SAVE 48%</span>
                  </div>
                  <div class="flex items-baseline gap-2">
                    <span data-price class="relative inline-flex tabular-nums text-6xl font-extrabold tracking-[-0.04em] text-white">$14.99</span>
                    <span data-period class="ml-1 text-base text-white/55">/mo</span>
                  </div>
                  <p data-billing-note class="mt-2 text-xs text-white/55">Flexible monthly billing. Cancel anytime.</p>
                </div>
                <ul class="mt-7 flex flex-col gap-3.5">
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">AI Image &amp; Video Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Create videos, images, ads, thumbnails, and social content with <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">SeeDance 2.0</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Veo 3.1</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Kling</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Nano Banana</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT Image 2</span>, and more.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Unlimited Text AI Usage</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Use premium text models like <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT-5</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Claude 4</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Gemini Pro 3</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">DeepSeek</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Grok</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Llama</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Mistral</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Qwen</span>, and more without text usage limits.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white"><span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">140+</span> AI Models &amp; Tools Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">One dashboard for text, image, video, voice, music, agents, automation, and creative workflows.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Voice Agent</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">GPT Realtime · ElevenLabs · Fish Audio</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Music Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Suno · Udio · ElevenLabs Music</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">All AI Agents Included</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Persistent Memory</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Knowledge Bases (up to 50,000 PDFs)</div></div></li>
                </ul>
                <div class="mt-6 rounded-2xl border border-violet-300/15 bg-violet-500/[0.05] p-4">
                  <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-violet-200"><svg viewBox="0 0 24 24" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 12c-2-2.67-4-4-6-4a4 4 0 1 0 0 8c2 0 4-1.33 6-4Zm0 0c2 2.67 4 4 6 4a4 4 0 0 0 0-8c-2 0-4 1.33-6 4Z"></path></svg> Unlimited Text Models</p>
                  <ul class="mt-3.5 flex flex-col gap-2.5">
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Gemini 3.1 Pro</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>GPT-5.4</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Kimi K2.5</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Grok 4.1 Fast</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>DeepSeek V3.2</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                  </ul>
                </div>
                <div class="mt-6 rounded-2xl border border-white/10 bg-black/30 p-4">
                  <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-white/55"><span class="text-violet-300">∞</span> Unlimited &amp; Free Gens</p>
                  <ul class="mt-3.5 flex flex-col gap-2.5">
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Seedream 4.5<span data-gen-info data-gen-type="free" data-gen-name="Seedream 4.5"></span></span>
                      <span class="inline-flex shrink-0 items-center rounded-md border border-[#E6C97A]/30 bg-[#E6C97A]/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-[#F5E1A4]">300 Free Gens</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Flux Schnell<span data-gen-info data-gen-type="unlim" data-gen-name="Flux Schnell"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Flux Pro<span data-gen-info data-gen-type="unlim" data-gen-name="Flux Pro"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Seedance 2.0 Fast<span data-gen-info data-gen-type="unlim" data-gen-name="Seedance 2.0 Fast"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300">Full Access</span>
                    </li>
                  </ul>
                </div>
                <div class="mt-6 rounded-2xl border border-white/[0.06] bg-white/[0.025] p-4">
                  <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/55">Best for</p>
                  <p class="mt-1.5 text-sm leading-6 text-white/80">First-time AI users testing the waters before going all-in. Upgrade anytime with one click.</p>
                </div>
                <a data-cta href="<?= htmlspecialchars($__kk_offer_links['creatormonthly'] ?? '#', ENT_QUOTES, "UTF-8") ?>" class="mt-7 inline-flex w-full items-center justify-center rounded-2xl border border-white/15 bg-white/[0.04] px-5 py-3.5 text-sm font-semibold text-white transition-all duration-200 hover:border-white/25 hover:bg-white/[0.08]"><span data-cta-label>Choose Creator</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></a>
                <div class="mt-3 flex items-center justify-center gap-4 text-[11px] text-white/55">
                  <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Secure checkout</span>
                  <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 text-emerald-400" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg> Cancel anytime</span>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 text-fuchsia-300" aria-hidden="true"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>Most Popular · Best Value
                      </div>
                    </div>
                    <div>
                      <div class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.22em] text-fuchsia-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M12 12c-2-2.67-4-4-6-4a4 4 0 1 0 0 8c2 0 4-1.33 6-4Zm0 0c2 2.67 4 4 6 4a4 4 0 0 0 0-8c-2 0-4 1.33-6 4Z"></path></svg>Unlimited / Premium
                      </div>
                      <h3 class="mt-3 text-2xl font-extrabold tracking-tight text-white">Studio</h3>
                      <p class="mt-1 text-sm text-white/65">Unlimited everything for serious creators. All 140+ models, full Seedance &amp; Veo, every premium image model — no caps, no surprises.</p>
                    </div>
                    <div class="mt-7">
                      <div class="mb-2 flex flex-wrap items-center gap-2 text-[12px]">
                        <span data-strike class="font-semibold tabular-nums text-white/42 line-through decoration-white/35 decoration-2">$79/mo</span>
                        <span data-save class="inline-flex items-center gap-1 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-emerald-300" style="box-shadow:0 0 18px -12px rgba(52,211,153,0.9)">SAVE 49%</span>
                      </div>
                      <div class="flex items-baseline gap-2">
                        <span data-price class="relative inline-flex tabular-nums text-6xl font-extrabold tracking-[-0.04em] text-white">$39.99</span>
                        <span data-period class="ml-1 text-base text-white/55">/mo</span>
                      </div>
                      <p data-billing-note class="mt-2 text-xs text-white/55">Flexible monthly billing. Cancel anytime.</p>
                    </div>
                    <div class="mt-5 rounded-2xl border border-fuchsia-300/30 p-4" style="background:linear-gradient(135deg, rgba(192,38,211,0.14) 0%, rgba(139,92,255,0.10) 50%, rgba(53,215,255,0.08) 100%);box-shadow:0 0 28px -12px rgba(139,92,255,0.55), inset 0 1px 0 rgba(255,255,255,0.06)">
                      <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-fuchsia-500 via-violet-500 to-cyan-500 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-[0.14em] text-white" style="box-shadow:0 0 18px -6px rgba(192,38,211,0.7)">
                          <svg viewBox="0 0 24 24" class="h-3 w-3" fill="currentColor" aria-hidden="true"><path d="M13.5 0c-.6 4.5-3.6 7.5-3.6 11.4 0 2.5 1.6 4.4 3.9 4.4 1.7 0 3.1-1 3.6-2.4.6 1.3 1.2 2.7 1.2 4.2 0 3.5-2.8 6.4-6.6 6.4-3.9 0-7-3.1-7-7 0-5.2 4.9-7.8 8.5-16Z"/></svg>
                          Best Value
                        </span>
                        <span data-per-day class="text-[11px] font-semibold uppercase tracking-[0.12em] text-fuchsia-200">~$1.33/day</span>
                      </div>
                      <p class="mt-2 text-[12.5px] leading-snug text-white/80">Replaces <span class="font-bold text-white">$200+/mo</span> of separate subscriptions — Midjourney, Runway, ChatGPT Plus, ElevenLabs and Claude Pro combined.</p>
                      <p class="mt-2 flex items-center gap-1.5 text-[11px] font-semibold text-fuchsia-100/85">
                        <svg viewBox="0 0 24 24" class="h-3 w-3 text-fuchsia-300" fill="currentColor" aria-hidden="true"><path d="M9 12l2 2 4-4 6 6-1.5 1.5L15 13l-4 4-4-4 1.5-1.5z"/><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.35"/></svg>
                        Picked by <span class="font-extrabold text-white">73%</span> of new members this month
                      </p>
                    </div>
                    <ul class="mt-7 flex flex-col gap-3.5">
                      <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">AI Image &amp; Video Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Create videos, images, ads, thumbnails, and social content with <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">SeeDance 2.0</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Veo 3.1</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Kling</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Nano Banana</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT Image 2</span>, and more.</div></div></li>
                      <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Unlimited Text AI Usage</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Use premium text models like <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT-5</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Claude 4</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Gemini Pro 3</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">DeepSeek</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Grok</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Llama</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Mistral</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Qwen</span>, and more without text usage limits.</div></div></li>
                      <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white"><span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">140+</span> AI Models &amp; Tools Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">One dashboard for text, image, video, voice, music, agents, automation, and creative workflows.</div></div></li>
                      <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Voice Agent</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">GPT Realtime · ElevenLabs · Fish Audio</div></div></li>
                      <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Music Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Suno · Udio · ElevenLabs Music</div></div></li>
                      <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">All AI Agents Included</div></div></li>
                      <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Everything in Creator, plus</div></div></li>
                      <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-fuchsia-300" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Up to 5 users</div></div></li>
                    </ul>
                    <div class="mt-6 rounded-2xl border border-fuchsia-300/20 bg-fuchsia-500/[0.06] p-4">
                      <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-fuchsia-200"><svg viewBox="0 0 24 24" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 12c-2-2.67-4-4-6-4a4 4 0 1 0 0 8c2 0 4-1.33 6-4Zm0 0c2 2.67 4 4 6 4a4 4 0 0 0 0-8c-2 0-4 1.33-6 4Z"></path></svg> Unlimited Text Models</p>
                      <ul class="mt-3.5 flex flex-col gap-2.5">
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Gemini 3.1 Pro</span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>GPT-5.4</span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Kimi K2.5</span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Grok 4.1 Fast</span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>DeepSeek V3.2</span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                      </ul>
                    </div>
                    <div class="mt-6 rounded-2xl border border-white/10 bg-black/30 p-4">
                      <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-white/55"><span class="text-violet-300">∞</span> Unlimited &amp; Free Gens</p>
                      <ul class="mt-3.5 flex flex-col gap-2.5">
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Seedream 4.5<span data-gen-info data-gen-type="free" data-gen-name="Seedream 4.5"></span></span>
                          <span class="inline-flex shrink-0 items-center rounded-md border border-[#E6C97A]/30 bg-[#E6C97A]/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-[#F5E1A4]">3,000 Free Gens</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Flux Schnell<span data-gen-info data-gen-type="unlim" data-gen-name="Flux Schnell"></span></span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Flux Pro<span data-gen-info data-gen-type="unlim" data-gen-name="Flux Pro"></span></span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Nano Banana<span data-gen-info data-gen-type="unlim" data-gen-name="Nano Banana"></span></span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Nano Banana 2<span data-gen-info data-gen-type="unlim" data-gen-name="Nano Banana 2"></span></span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>GPT Image 2<span data-gen-info data-gen-type="unlim" data-gen-name="GPT Image 2"></span></span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                        <li class="flex items-center justify-between gap-3">
                          <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Imagen Premium<span data-gen-info data-gen-type="unlim" data-gen-name="Imagen Premium"></span></span>
                          <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                        </li>
                      </ul>
                    </div>
                    <div class="mt-6 rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                      <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/55">Best for</p>
                      <p class="mt-1.5 text-sm leading-6 text-white/80">Serious creators, freelancers, and founders who run AI daily and want everything unlocked without thinking about it.</p>
                    </div>
                    <a data-cta href="<?= htmlspecialchars($__kk_offer_links['premiummonthly'] ?? '#', ENT_QUOTES, "UTF-8") ?>" class="relative mt-7 inline-flex w-full items-center justify-center overflow-hidden rounded-2xl px-5 py-3.5 text-sm font-bold text-white transition-transform duration-150 hover:-translate-y-0.5" style="background:linear-gradient(110deg, #6366F1 0%, #8B5CFF 50%, #C026D3 100%);box-shadow:0 20px 40px -12px rgba(139,92,255,0.6)"><span class="relative z-10 inline-flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path></svg><span data-cta-label>Choose Studio</span></span></a>
                    <div class="mt-3 flex items-center justify-center gap-4 text-[11px] text-white/55">
                      <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Secure checkout</span>
                      <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 text-emerald-400" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg> Cancel anytime</span>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"></path><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"></path><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"></path></svg>Agency · For Teams
                  </div>
                  <h3 class="mt-3 text-2xl font-extrabold tracking-tight text-white">Scale</h3>
                  <p class="mt-1 text-sm text-white/65">Built for 5-10 user agencies running multiple clients. Everything in Studio, plus extra seats, shared workflows and 4K outputs.</p>
                  <p class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-amber-300/25 bg-amber-500/10 px-2.5 py-1 text-[10.5px] font-semibold text-amber-100/85"><svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg> Solo creator? Studio gives you the same unlimited models for less.</p>
                </div>
                <div class="mt-7">
                  <div class="mb-2 flex flex-wrap items-center gap-2 text-[12px]">
                    <span data-strike class="font-semibold tabular-nums text-white/42 line-through decoration-white/35 decoration-2">$199/mo</span>
                    <span data-save class="inline-flex items-center gap-1 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-emerald-300" style="box-shadow:0 0 18px -12px rgba(52,211,153,0.9)">SAVE 50%</span>
                  </div>
                  <div class="flex items-baseline gap-2">
                    <span data-price class="relative inline-flex tabular-nums text-6xl font-extrabold tracking-[-0.04em] text-white">$99.99</span>
                    <span data-period class="ml-1 text-base text-white/55">/mo</span>
                  </div>
                  <p data-billing-note class="mt-2 text-xs text-white/55">Flexible monthly billing. Cancel anytime.</p>
                </div>
                <ul class="mt-7 flex flex-col gap-3.5">
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">AI Image &amp; Video Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Create videos, images, ads, thumbnails, and social content with <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">SeeDance 2.0</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Veo 3.1</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Kling</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Nano Banana</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT Image 2</span>, and more.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Unlimited Text AI Usage</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Use premium text models like <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">GPT-5</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Claude 4</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Gemini Pro 3</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">DeepSeek</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Grok</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Llama</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Mistral</span>, <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">Qwen</span>, and more without text usage limits.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white"><span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-fuchsia-300 bg-clip-text font-semibold text-transparent">140+</span> AI Models &amp; Tools Included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">One dashboard for text, image, video, voice, music, agents, automation, and creative workflows.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Voice Agent</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">GPT Realtime · ElevenLabs · Fish Audio</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Music Generation</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Suno · Udio · ElevenLabs Music</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">All AI Agents Included</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Everything in Creator, plus</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#FFD86B]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Up to 10 users</div></div></li>
                </ul>
                <div class="mt-6 rounded-2xl border border-amber-300/20 bg-amber-500/[0.06] p-4">
                  <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-amber-200"><svg viewBox="0 0 24 24" class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 12c-2-2.67-4-4-6-4a4 4 0 1 0 0 8c2 0 4-1.33 6-4Zm0 0c2 2.67 4 4 6 4a4 4 0 0 0 0-8c-2 0-4 1.33-6 4Z"></path></svg> Unlimited Text Models</p>
                  <ul class="mt-3.5 flex flex-col gap-2.5">
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Gemini 3.1 Pro</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>GPT-5.4</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Kimi K2.5</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Grok 4.1 Fast</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>DeepSeek V3.2</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                  </ul>
                </div>
                <div class="mt-6 rounded-2xl border border-white/10 bg-black/30 p-4">
                  <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-white/55"><span class="text-violet-300">∞</span> Unlimited &amp; Free Gens</p>
                  <ul class="mt-3.5 flex flex-col gap-2.5">
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Seedance 2.0<span data-gen-info data-gen-type="unlim" data-gen-name="Seedance 2.0"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Kling 3.0<span data-gen-info data-gen-type="free" data-gen-name="Kling 3.0"></span></span>
                      <span class="inline-flex shrink-0 items-center rounded-md border border-[#E6C97A]/30 bg-[#E6C97A]/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-[#F5E1A4]">1,500 Free Gens</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Veo 3.1<span data-gen-info data-gen-type="unlim" data-gen-name="Veo 3.1"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>PixVerse V6<span data-gen-info data-gen-type="unlim" data-gen-name="PixVerse V6"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Wan 2.7<span data-gen-info data-gen-type="unlim" data-gen-name="Wan 2.7"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Seedream 4.5<span class="inline-flex shrink-0 items-center rounded-md border border-violet-400/30 bg-violet-500/15 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-[0.08em] text-violet-200">4K</span><span data-gen-info data-gen-type="unlim" data-gen-name="Seedream 4.5 (4K)"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Flux Pro<span data-gen-info data-gen-type="unlim" data-gen-name="Flux Pro"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Nano Banana 2<span class="inline-flex shrink-0 items-center rounded-md border border-violet-400/30 bg-violet-500/15 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-[0.08em] text-violet-200">4K</span><span data-gen-info data-gen-type="unlim" data-gen-name="Nano Banana 2 (4K)"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>GPT Image 2<span data-gen-info data-gen-type="unlim" data-gen-name="GPT Image 2"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Imagen Premium<span data-gen-info data-gen-type="unlim" data-gen-name="Imagen Premium"></span></span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                  </ul>
                </div>
                <div class="mt-6 rounded-2xl border border-white/[0.06] bg-white/[0.025] p-4">
                  <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/55">Best for</p>
                  <p class="mt-1.5 text-sm leading-6 text-white/80">Agencies of 5-10 creators handling multi-client output, white-label delivery, and shared brand knowledge bases.</p>
                </div>
                <a data-cta href="<?= htmlspecialchars($__kk_offer_links['promonthly'] ?? '#', ENT_QUOTES, "UTF-8") ?>" class="mt-7 inline-flex w-full items-center justify-center rounded-2xl border border-white/15 bg-white/[0.04] px-5 py-3.5 text-sm font-semibold text-white transition-all duration-200 hover:border-white/25 hover:bg-white/[0.08]"><span data-cta-label>Choose Scale</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></a>
                <div class="mt-3 flex items-center justify-center gap-4 text-[11px] text-white/55">
                  <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Secure checkout</span>
                  <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 text-emerald-400" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg> Cancel anytime</span>
                </div>
              </div>
            </div>



            <!-- Pro Max -->
            <div class="relative h-full" data-plan="promax">
              <div class="relative flex h-full flex-col rounded-3xl border border-[#E6C97A]/30 bg-gradient-to-b from-[#E6C97A]/[0.06] to-white/[0.02] p-7 backdrop-blur-md" style="box-shadow:0 30px 60px -30px rgba(230,201,122,0.32),0 0 80px -40px rgba(230,201,122,0.4)">
                <div>
                  <div class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.22em] text-[#F5E1A4]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path></svg>Pro Max · For Enterprises
                  </div>
                  <h3 class="mt-3 text-2xl font-extrabold tracking-tight text-white">Pro Max</h3>
                  <p class="mt-1 text-sm text-white/65">For teams scaling AI workflows across multiple workspaces, brands, and clients. Everything in Scale, plus 15 seats, dedicated compute, and white-label exports.</p>
                </div>
                <div class="mt-7">
                  <div class="mb-2 flex flex-wrap items-center gap-2 text-[12px]">
                    <span data-strike class="font-semibold tabular-nums text-white/42 line-through decoration-white/35 decoration-2">$599/mo</span>
                    <span data-save class="inline-flex items-center gap-1 rounded-full border border-amber-300/30 bg-amber-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-amber-200" style="box-shadow:0 0 18px -12px rgba(245,225,164,0.9)">SAVE 50%</span>
                  </div>
                  <div class="flex items-baseline gap-2">
                    <span data-price class="relative inline-flex tabular-nums text-6xl font-extrabold tracking-[-0.04em] text-white">$299.99</span>
                    <span data-period class="ml-1 text-base text-white/55">/mo</span>
                  </div>
                  <p data-billing-note class="mt-2 text-xs text-white/55">Includes 15 seats. Flexible monthly billing. Cancel anytime.</p>
                </div>
                <ul class="mt-7 flex flex-col gap-3.5">
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#F5E1A4]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Everything in Scale included</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Unlimited 4K video &amp; image, all 140+ models, all studios, voice agents, music — at agency scale.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#F5E1A4]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Up to 15 team seats</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">2 to 15 members in one shared workspace with shared credits, SSO, and analytics.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#F5E1A4]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Dedicated supercompute</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Priority queue across every studio — render up to 16 videos and 16 images in parallel.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#F5E1A4]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Shareable Soul IDs &amp; brand kits</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Lock brand voice, characters, color palettes across every render — for clients and teams.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#F5E1A4]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Usage analytics &amp; SSO</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Track every member's usage, manage roles and permissions from one admin dashboard.</div></div></li>
                  <li class="flex items-start gap-3"><svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-[#F5E1A4]" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-width="1.4" opacity="0.3"></circle><path d="M7 12.5l3.5 3.5L17 8.5"></path></svg><div class="min-w-0"><div class="text-sm font-semibold leading-tight text-white">Priority support &amp; onboarding</div><div class="mt-0.5 text-[11px] leading-snug text-white/55">Dedicated account success, &lt; 1 business hour reply, 1-on-1 team setup call.</div></div></li>
                </ul>
                <div class="mt-6 rounded-2xl border border-[#E6C97A]/25 bg-[#E6C97A]/[0.04] p-4">
                  <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-[#F5E1A4]"><span>∞</span> Unlimited Premium Models</p>
                  <ul class="mt-3.5 flex flex-col gap-2.5">
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Seedance 2.0</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Veo 3.1</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Kling 3.0 (1080p)</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Nano Banana 2 (4K)</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>GPT Image 2</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                      <span class="inline-flex items-center gap-2 text-sm font-medium text-white"><svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>Imagen Premium</span>
                      <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-300/25 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-emerald-300"><span>∞</span> Unlimited</span>
                    </li>
                  </ul>
                </div>
                <a data-cta href="<?= htmlspecialchars($__kk_offer_links['promaxmonthly'] ?? '#', ENT_QUOTES, "UTF-8") ?>" class="mt-7 inline-flex w-full items-center justify-center rounded-2xl border border-[#FFF8E7]/55 px-5 py-3.5 text-sm font-bold uppercase tracking-[0.14em] text-[#1A1208] transition-transform duration-150 hover:-translate-y-0.5" style="background:linear-gradient(110deg, #FFF8E7 0%, #F5E1A4 32%, #E6C97A 60%, #D4A647 88%, #FFF8E7 100%);box-shadow:0 0 24px -6px rgba(230,201,122,0.65)"><span data-cta-label>Get Pro Max</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></a>
                <div class="mt-3 flex items-center justify-center gap-4 text-[11px] text-white/55">
                  <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Secure checkout</span>
                  <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 text-emerald-400" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg> Cancel anytime</span>
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
    },
    promax: {
      monthly: { strike: '$599/mo', price: '$299.99', period: '/mo', save: 'SAVE 50%', note: 'Includes 15 seats. Flexible monthly billing. Cancel anytime.', token: 'promaxmonthly', cta: 'Get Pro Max' },
      yearly:  { strike: '$5,996/yr', price: '$2,998', period: '/yr', save: 'SAVE 50%', note: 'Includes 15 seats. Billed annually.', token: 'promaxyearly', cta: 'Get Pro Max' }
    }
  };

  var billing = 'monthly';
  var toggleBtns = document.querySelectorAll('[data-billing]');

  function checkoutUrl(token) { var links = window.__KK_OFFER_LINKS || {}; return links[token] || '#'; }

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
  var key = 'omnirogue_pricing_v2_cd';
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
    { ico: ic.check, label: '250,000+ active creators' }
  ];
  function feat(t, s) { return { t: t, s: s }; }

  var POPUPS = {
    'ai-agent': {
      eyebrow: 'Product · AI Agents',
      title: 'Deploy AI agents that actually get work done',
      intro: 'OmniRogue ships with 99 prebuilt, production-ready agents, plus the tools to build your own. They run real workflows so you stop copy-pasting between tabs.',
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
      title: 'Join 250,000+ creators building with AI',
      intro: 'You are never building alone. The OmniRogue community shares workflows, prompts, agents and real use cases every single day.',
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
        feat('Responsive support team', 'Email support@omnirogue.com and get a real answer, not a bot loop.'),
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
      intro: 'Clear guidelines keep OmniRogue fast, safe and trustworthy for the entire community, so honest creators always get the best experience.',
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
    elCtaLabel.textContent = data.ctaLabel || 'Start creating with OmniRogue';
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
<!-- chrome:footer -->
<!-- OmniRogue checkout footer -->
<footer class="or-footer" role="contentinfo">
  <div class="or-footer-inner">

    <div class="or-footer-top">
      <a href="#" class="or-footer-logo" data-or-scroll="top" aria-label="OmniRogue">
        <img src="/checkouts-omni/logo-omnirogue.png" alt="OmniRogue" width="120" height="32">
      </a>
      <div class="or-footer-legal">
        <a class="or-footer-legal-btn" href="/pricing-v2-kk1/terms-of-service.php<?= $__step1link; ?>" style="text-decoration:none;">Terms of Service</a>
        <a class="or-footer-legal-btn" href="/pricing-v2-kk1/privacy-policy.php<?= $__step1link; ?>" style="text-decoration:none;">Privacy Policy</a>
        <button type="button" class="or-footer-legal-btn" data-or-popup="refund">Refund Policy</button>
        <a class="or-footer-legal-btn" href="/pricing-v2-kk1/acceptable-use-policy.php<?= $__step1link; ?>" style="text-decoration:none;">Acceptable Use</a>
        <a class="or-footer-legal-btn" href="/pricing-v2-kk1/data-deletion-request.php<?= $__step1link; ?>" style="text-decoration:none;">Data Deletion</a>
      </div>
    </div>

    <div class="or-footer-trust">
      <span class="or-trust-badge">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        14-day money-back guarantee
      </span>
      <span class="or-trust-badge or-trust-shield">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        256-bit SSL secure checkout
      </span>
      <span class="or-trust-badge or-trust-cancel">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        Cancel anytime
      </span>
    </div>

    <div class="or-footer-pay" aria-label="Accepted payment methods">
      <span class="or-pay-icon" title="Visa"><svg viewBox="0 0 48 30" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="30" rx="4" fill="#1A1F71"/><text x="24" y="20" text-anchor="middle" font-family="Arial Black, sans-serif" font-size="11" font-weight="900" fill="#fff" letter-spacing="0.5">VISA</text></svg></span>
      <span class="or-pay-icon" title="Mastercard"><svg viewBox="0 0 48 30" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="30" rx="4" fill="#000"/><circle cx="20" cy="15" r="7" fill="#EB001B"/><circle cx="28" cy="15" r="7" fill="#F79E1B" fill-opacity="0.85"/></svg></span>
      <span class="or-pay-icon" title="American Express"><svg viewBox="0 0 48 30" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="30" rx="4" fill="#006FCF"/><text x="24" y="19" text-anchor="middle" font-family="Arial Black, sans-serif" font-size="7.5" font-weight="900" fill="#fff" letter-spacing="0.3">AMEX</text></svg></span>
      <span class="or-pay-icon" title="Discover"><svg viewBox="0 0 48 30" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="30" rx="4" fill="#fff"/><rect y="15" width="48" height="15" fill="#F26E21" rx="0"/><text x="24" y="13" text-anchor="middle" font-family="Arial, sans-serif" font-size="6.5" font-weight="700" fill="#000">DISCOVER</text></svg></span>
      <span class="or-pay-icon" title="PayPal"><svg viewBox="0 0 48 30" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="30" rx="4" fill="#fff"/><text x="24" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="9" font-weight="800" fill="#003087" letter-spacing="-0.2">Pay<tspan fill="#009CDE">Pal</tspan></text></svg></span>
      <span class="or-pay-icon" title="Apple Pay"><svg viewBox="0 0 48 30" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="30" rx="4" fill="#000"/><text x="24" y="20" text-anchor="middle" font-family="-apple-system, Helvetica, sans-serif" font-size="9" font-weight="700" fill="#fff">&#xF8FF; Pay</text></svg></span>
      <span class="or-pay-icon" title="Google Pay"><svg viewBox="0 0 48 30" xmlns="http://www.w3.org/2000/svg"><rect width="48" height="30" rx="4" fill="#fff"/><text x="24" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="8" font-weight="700"><tspan fill="#4285F4">G</tspan><tspan fill="#EA4335">o</tspan><tspan fill="#FBBC05">o</tspan><tspan fill="#4285F4">g</tspan><tspan fill="#34A853">l</tspan><tspan fill="#EA4335">e</tspan><tspan fill="#5F6368"> Pay</tspan></text></svg></span>
    </div>

    <div class="or-footer-bottom">
      <span>&copy; 2026 OmniRogue Inc. &middot; <a href="mailto:support@omnirogue.com">support@omnirogue.com</a></span>
      <span>Made with &hearts; for creators worldwide</span>
    </div>

  </div>
</footer>

<!-- OmniRogue trust popup modal (content injected by chrome.js) -->
<div class="or-modal-scrim" data-or-modal role="dialog" aria-modal="true" aria-labelledby="or-modal-title">
  <div class="or-modal" role="document">
    <button type="button" class="or-modal-close" data-or-modal-close aria-label="Close">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
    <div class="or-modal-eyebrow" data-or-modal-eyebrow></div>
    <h3 class="or-modal-title" id="or-modal-title" data-or-modal-title></h3>
    <p class="or-modal-intro" data-or-modal-intro></p>
    <ul class="or-modal-list" data-or-modal-list></ul>
    <div class="or-modal-trust" data-or-modal-trust></div>
    <div class="or-modal-foot">
      <button type="button" class="or-modal-cta" data-or-modal-cta>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        <span data-or-modal-cta-label>Continue to Plans</span>
      </button>
      <span class="or-modal-note">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Secure &amp; private
      </span>
    </div>
  </div>
</div>

<!-- OmniRogue Affiliate Program — rich popup -->
<div class="or-aff-scrim" data-or-aff-modal role="dialog" aria-modal="true" aria-labelledby="or-aff-title">
  <div class="or-aff-modal" role="document">
    <button type="button" class="or-aff-close" data-or-aff-close aria-label="Close">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>

    <span class="or-aff-eyebrow">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      Affiliate Program
    </span>

    <h3 class="or-aff-title" id="or-aff-title">
      Earn real money every time you<br>
      <span class="or-aff-title-grad">share OmniRogue.</span>
    </h3>
    <p class="or-aff-intro">
      Refer anyone with your unique link and earn a <b>flat 30% commission</b> on every plan they buy — a one-time reward per referral, paid out as real, withdrawable cash.
    </p>

    <div class="or-aff-stats">
      <div class="or-aff-stat">
        <div class="or-aff-stat-pct">30%</div>
        <div class="or-aff-stat-plan">Monthly</div>
        <div class="or-aff-stat-sub">one-time</div>
      </div>
      <div class="or-aff-stat">
        <div class="or-aff-stat-pct">30%</div>
        <div class="or-aff-stat-plan">Yearly</div>
        <div class="or-aff-stat-sub">one-time</div>
      </div>
      <div class="or-aff-stat">
        <div class="or-aff-stat-pct">30%</div>
        <div class="or-aff-stat-plan">Lifetime</div>
        <div class="or-aff-stat-sub">one-time</div>
      </div>
    </div>

    <div class="or-aff-section">
      <div class="or-aff-section-eyebrow">How it works</div>
      <div class="or-aff-section-title">Three steps to your first payout.</div>
      <div class="or-aff-steps">
        <div class="or-aff-step">
          <span class="or-aff-step-num">01</span>
          <div class="or-aff-step-title">Grab your link</div>
          <div class="or-aff-step-text">Open your affiliate dashboard and copy your unique link. Share anywhere — socials, newsletter, DMs.</div>
        </div>
        <div class="or-aff-step">
          <span class="or-aff-step-num">02</span>
          <div class="or-aff-step-title">They subscribe</div>
          <div class="or-aff-step-text">Every click is tagged to you for 30 days. When they pick a paid plan, the sale is credited to your account automatically.</div>
        </div>
        <div class="or-aff-step">
          <span class="or-aff-step-num">03</span>
          <div class="or-aff-step-title">You get paid</div>
          <div class="or-aff-step-text">Earn a flat 30% of their first payment. After a 30-day clearing window, your commission unlocks as withdrawable cash.</div>
        </div>
      </div>
    </div>

    <div class="or-aff-section">
      <div class="or-aff-section-eyebrow">The rules</div>
      <div class="or-aff-section-title">Fair, simple, transparent.</div>
      <div class="or-aff-rules">
        <div class="or-aff-rule">
          <span class="or-aff-rule-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </span>
          <div>
            <div class="or-aff-rule-title">30-day attribution</div>
            <div class="or-aff-rule-text">First click wins. We remember your referral for 30 days, so you still earn if they decide to subscribe later.</div>
          </div>
        </div>
        <div class="or-aff-rule">
          <span class="or-aff-rule-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
          </span>
          <div>
            <div class="or-aff-rule-title">30-day clearing window</div>
            <div class="or-aff-rule-text">Every commission is held for 30 days to cover refunds and chargebacks, then unlocks as withdrawable cash.</div>
          </div>
        </div>
        <div class="or-aff-rule">
          <span class="or-aff-rule-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </span>
          <div>
            <div class="or-aff-rule-title">Flat 30% on every plan</div>
            <div class="or-aff-rule-text">30% of your referral's first payment on any plan — monthly, yearly, or lifetime. One-time per referral, not recurring.</div>
          </div>
        </div>
        <div class="or-aff-rule">
          <span class="or-aff-rule-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
          </span>
          <div>
            <div class="or-aff-rule-title">Withdraw your way</div>
            <div class="or-aff-rule-text">Cash out to PayPal, Wise, bank transfer — or apply credit toward your own OmniRogue plan. No minimum games.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="or-aff-section">
      <div class="or-aff-section-eyebrow">FAQ</div>
      <div class="or-aff-section-title">Questions, answered.</div>
      <div class="or-aff-faq">
        <details class="or-aff-faq-item">
          <summary class="or-aff-faq-q">
            How much can I earn?
            <svg class="or-aff-faq-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </summary>
          <div class="or-aff-faq-a">A flat <b>30% of the plan price on every plan</b> — monthly, yearly, or lifetime. It is a one-time commission per referral (paid once on their first payment), not recurring. Lifetime referrals pay out the largest single commission.</div>
        </details>
        <details class="or-aff-faq-item">
          <summary class="or-aff-faq-q">
            When can I withdraw?
            <svg class="or-aff-faq-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </summary>
          <div class="or-aff-faq-a">After a 30-day clearing window from your referral's purchase date — that covers the refund period. Cleared earnings move into your withdrawable balance instantly and you can cash out anytime.</div>
        </details>
        <details class="or-aff-faq-item">
          <summary class="or-aff-faq-q">
            How do I get paid?
            <svg class="or-aff-faq-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </summary>
          <div class="or-aff-faq-a">PayPal, Wise, or bank transfer — or apply your earnings as credit toward your own OmniRogue plan. You pick at withdrawal time.</div>
        </details>
        <details class="or-aff-faq-item">
          <summary class="or-aff-faq-q">
            Do I need a paid plan to refer?
            <svg class="or-aff-faq-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </summary>
          <div class="or-aff-faq-a">No. Anyone can grab an affiliate link for free. Paid members do get higher payout priority and access to ready-made marketing assets, but it's not required.</div>
        </details>
        <details class="or-aff-faq-item">
          <summary class="or-aff-faq-q">
            What counts as a valid referral?
            <svg class="or-aff-faq-chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </summary>
          <div class="or-aff-faq-a">A new customer who clicks your unique affiliate link, creates an OmniRogue account, and purchases a paid plan within the 30-day attribution window. Self-referrals, refunded purchases, and fraudulent traffic don't count.</div>
        </details>
      </div>
    </div>

    <div class="or-aff-foot">
      <button type="button" class="or-aff-cta-primary" data-or-aff-dashboard>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Open your dashboard
      </button>
      <button type="button" class="or-aff-cta-secondary" data-or-aff-plans>
        See the plans
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </button>
    </div>

    <div class="or-aff-microtrust">
      <span><b>Free to join</b></span>
      <span class="or-aff-dot"></span>
      <span>Pays in real cash</span>
      <span class="or-aff-dot"></span>
      <span>PayPal · Wise · Bank · Plan credit</span>
      <span class="or-aff-dot"></span>
      <span>30-day attribution</span>
    </div>
  </div>
</div>

<script src="/checkouts-omni/_chrome/chrome.js?v=20260609d" defer></script>
<!-- /chrome:footer -->

<!-- kk-inject:v1 footer -->
<?php
$kk_exit_monthly_href  = $__is_checkout
    ? ($__kk_offer_links['creatormonthly'] ?? '#')
    : $__checkout;
$kk_exit_lifetime_href = $__is_checkout
    ? ($__kk_lifetime_link ?? $__kk_offer_links['lifetime'] ?? '#')
    : $__checkout;
require __DIR__ . '/../_partials/kk-exit-popup.php';
?>
<script src="/_partials/kk-chrome-overrides.js?v=20260608d" defer></script>
</body>
</html>
