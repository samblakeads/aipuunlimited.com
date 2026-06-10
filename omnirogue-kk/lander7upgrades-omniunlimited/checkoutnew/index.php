<?php require_once($_SERVER['DOCUMENT_ROOT'].'/kowboykit/includes/money.php'); ?>
<?php
$__checkout_base = '/omnirogue/lander7upgrades-omniunlimited/checkoutnew';
$__offer_tokens = [
    'creatormonthly', 'studiomonthly', 'scalemonthly',
    'creatoryearly', 'studioyearly', 'scaleyearly',
    'lifetime',
];
$__offer_links = [];
foreach ($__offer_tokens as $__t) {
    $__offer_links[$__t] = $offer[$__t]['link']['step1link'] ?? ($link['step1link'] ?? '#');
}
unset($__t);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Plans - OmniRogue</title>
<meta name="description" content="Choose your OmniRogue plan">
<meta name="robots" content="noindex,nofollow">
<link rel="icon" href="<?= htmlspecialchars($__checkout_base); ?>/logo-omnirogue.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<style>
  :root {
    --bg: #08060f;
    --bg-card: rgba(255,255,255,.03);
    --border: rgba(255,255,255,.08);
    --border-strong: rgba(255,255,255,.14);
    --fg: #e8eaf0;
    --fg-muted: #9aa3b2;
    --fg-dim: #6b7280;
    --primary: #7c6cff;
    --primary-2: #5b8cff;
    --gold: #ffd166;
    --lime: #c4ff32;
    --radius: 18px;
    --nav-h: 64px;
  }
  *, *::before, *::after { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: var(--bg); color: var(--fg); font-family: Inter, system-ui, sans-serif; }
  a { color: inherit; text-decoration: none; }
  .nav {
    position: sticky; top: 0; z-index: 50;
    border-bottom: 1px solid var(--border);
    background: rgba(8,6,15,.88);
    backdrop-filter: blur(16px);
    padding-top: env(safe-area-inset-top);
  }
  .nav-inner {
    max-width: 1200px; margin: 0 auto; padding: 0 20px;
    height: var(--nav-h); display: flex; align-items: center; justify-content: space-between;
  }
  .nav img { height: 36px; width: auto; display: block; }
  .nav-back { font-size: 14px; color: var(--fg-muted); }
  .nav-back:hover { color: #fff; }
  .wrap { max-width: 1200px; margin: 0 auto; padding: 40px 20px 80px; }
  .hero { text-align: center; max-width: 760px; margin: 0 auto 36px; }
  .hero h1 {
    font-size: clamp(2rem, 5vw, 3.1rem); line-height: 1.08; font-weight: 800;
    letter-spacing: -.03em; margin: 0 0 14px; color: #fff;
  }
  .hero p { margin: 0; color: var(--fg-muted); font-size: 1.05rem; line-height: 1.6; }
  .countdown {
    display: flex; justify-content: center; gap: 10px; margin: 28px 0 32px; flex-wrap: wrap;
  }
  .cd-box {
    min-width: 72px; padding: 12px 10px; border-radius: 12px;
    background: var(--bg-card); border: 1px solid var(--border);
    text-align: center;
  }
  .cd-box b { display: block; font-size: 1.5rem; color: #fff; font-variant-numeric: tabular-nums; }
  .cd-box span { font-size: 11px; color: var(--fg-dim); text-transform: uppercase; letter-spacing: .06em; }
  .stats {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 40px;
  }
  @media (max-width: 720px) { .stats { grid-template-columns: repeat(2, 1fr); } }
  .stat {
    padding: 16px; border-radius: 14px; background: var(--bg-card); border: 1px solid var(--border);
    text-align: center;
  }
  .stat b { display: block; font-size: 1.25rem; color: #fff; margin-bottom: 4px; }
  .stat span { font-size: 12px; color: var(--fg-muted); }
  .lifetime-wrap { margin-bottom: 48px; }
  .section-label {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
    color: var(--gold); margin-bottom: 12px;
  }
  .lifetime-card {
    border-radius: 24px; padding: clamp(24px, 4vw, 36px);
    border: 1px solid rgba(255,209,102,.28);
    background: linear-gradient(145deg, rgba(255,209,102,.08), rgba(124,108,255,.06) 45%, rgba(8,6,15,.2));
    position: relative; overflow: hidden;
  }
  .lifetime-card::before {
    content: ""; position: absolute; inset: 0;
    background: radial-gradient(circle at 20% 0%, rgba(255,209,102,.12), transparent 55%);
    pointer-events: none;
  }
  .lifetime-inner { position: relative; display: grid; grid-template-columns: 1.2fr .8fr; gap: 28px; align-items: center; }
  @media (max-width: 900px) { .lifetime-inner { grid-template-columns: 1fr; } }
  .badge {
    display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; padding: 6px 10px; border-radius: 999px;
    background: rgba(255,209,102,.14); color: var(--gold); border: 1px solid rgba(255,209,102,.35);
    margin-bottom: 12px;
  }
  .lifetime-card h2 { margin: 0 0 10px; font-size: clamp(1.5rem, 3vw, 2rem); color: #fff; }
  .lifetime-card .sub { color: var(--fg-muted); line-height: 1.65; margin: 0 0 18px; max-width: 560px; }
  .social-proof { font-size: 13px; color: var(--fg-dim); margin-bottom: 18px; }
  .price-row { display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
  .price-strike { font-size: 1.1rem; color: var(--fg-dim); text-decoration: line-through; }
  .price-main { font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 800; color: #fff; }
  .price-off { font-size: 13px; font-weight: 700; color: var(--lime); background: rgba(196,255,50,.1); padding: 4px 8px; border-radius: 6px; }
  .price-note { font-size: 13px; color: var(--fg-muted); margin-bottom: 20px; }
  .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 14px 22px; border-radius: 12px; font-weight: 700; font-size: 15px;
    border: none; cursor: pointer; transition: transform .15s ease, box-shadow .15s ease;
  }
  .btn:hover { transform: translateY(-1px); }
  .btn-primary {
    background: linear-gradient(135deg, var(--gold), #ffa54b); color: #1c1505;
    box-shadow: 0 16px 40px -14px rgba(255,181,71,.45);
  }
  .btn-outline {
    background: transparent; color: #fff; border: 1px solid var(--border-strong);
  }
  .btn-block { width: 100%; }
  .trust { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 14px; font-size: 12px; color: var(--fg-dim); }
  .lifetime-feats { list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
  .lifetime-feats li {
    padding: 12px 14px; border-radius: 12px; background: rgba(255,255,255,.03);
    border: 1px solid var(--border); font-size: 14px; line-height: 1.5;
  }
  .lifetime-feats li b { display: block; color: #fff; margin-bottom: 2px; }
  .toggle-wrap { display: flex; justify-content: center; margin: 8px 0 28px; }
  .toggle {
    display: inline-flex; align-items: center; gap: 4px; padding: 4px;
    border-radius: 999px; background: rgba(255,255,255,.04); border: 1px solid var(--border);
  }
  .toggle button {
    border: none; background: transparent; color: var(--fg-muted);
    font: inherit; font-size: 14px; font-weight: 600; padding: 10px 18px;
    border-radius: 999px; cursor: pointer;
  }
  .toggle button.on { background: #fff; color: #111; }
  .toggle .save { font-size: 11px; color: var(--lime); margin-left: 6px; font-weight: 700; }
  .plans { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
  @media (max-width: 960px) { .plans { grid-template-columns: 1fr; max-width: 480px; margin: 0 auto; } }
  .plan {
    border-radius: 20px; padding: 24px 22px 22px;
    background: var(--bg-card); border: 1px solid var(--border);
    display: flex; flex-direction: column; position: relative;
  }
  .plan.popular {
    border-color: rgba(124,108,255,.45);
    background: linear-gradient(180deg, rgba(124,108,255,.08), rgba(255,255,255,.02));
    box-shadow: 0 24px 60px -30px rgba(124,108,255,.35);
  }
  .plan-badge {
    position: absolute; top: -11px; left: 50%; transform: translateX(-50%);
    font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    padding: 5px 12px; border-radius: 999px; white-space: nowrap;
    background: linear-gradient(135deg, var(--primary), var(--primary-2)); color: #fff;
  }
  .plan-tier { font-size: 12px; color: var(--fg-dim); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 6px; }
  .plan h3 { margin: 0 0 8px; font-size: 1.45rem; color: #fff; }
  .plan .tagline { margin: 0 0 18px; font-size: 14px; color: var(--fg-muted); line-height: 1.55; min-height: 44px; }
  .plan .price-row { margin-bottom: 6px; }
  .plan .billing { font-size: 13px; color: var(--fg-dim); margin-bottom: 18px; }
  .feat-list { list-style: none; margin: 0 0 22px; padding: 0; flex: 1; display: grid; gap: 12px; }
  .feat-list li { font-size: 13.5px; line-height: 1.45; color: var(--fg-muted); padding-left: 22px; position: relative; }
  .feat-list li::before {
    content: ""; position: absolute; left: 0; top: 5px; width: 12px; height: 12px; border-radius: 50%;
    background: rgba(196,255,50,.15); border: 1px solid rgba(196,255,50,.45);
  }
  .feat-list li b { display: block; color: #fff; font-size: 14px; margin-bottom: 2px; }
  .best-for {
    font-size: 12px; color: var(--fg-dim); padding: 10px 12px; border-radius: 10px;
    background: rgba(255,255,255,.02); border: 1px solid var(--border); margin-bottom: 16px;
  }
  .best-for b { color: var(--fg-muted); }
  .secure { text-align: center; margin-top: 28px; font-size: 13px; color: var(--fg-dim); }
  footer {
    border-top: 1px solid var(--border); margin-top: 60px; padding: 28px 20px 40px;
    text-align: center; font-size: 13px; color: var(--fg-dim);
  }
</style>
</head>
<body>

<nav class="nav">
  <div class="nav-inner">
    <a href="../"><img src="<?= htmlspecialchars($__checkout_base); ?>/logo-omnirogue.png" alt="OmniRogue" width="160" height="42"></a>
    <a class="nav-back" href="../">&larr; Back to offer</a>
  </div>
</nav>

<main class="wrap">
  <div class="hero">
    <h1>Unlock 140+ AI Tools<br>in One Dashboard</h1>
    <p>Create videos, images, music, voiceovers, agents, and content faster with AIPU.</p>
  </div>

  <div class="countdown" aria-live="polite">
    <div class="cd-box"><b data-cd-d>00</b><span>Days</span></div>
    <div class="cd-box"><b data-cd-h>00</b><span>Hrs</span></div>
    <div class="cd-box"><b data-cd-m>00</b><span>Min</span></div>
    <div class="cd-box"><b data-cd-s>00</b><span>Sec</span></div>
  </div>

  <div class="stats">
    <div class="stat"><b>4.8★</b><span>Avg rating</span></div>
    <div class="stat"><b>1,500+</b><span>Active creators</span></div>
    <div class="stat"><b>140+</b><span>AI models</span></div>
    <div class="stat"><b>24/7</b><span>Secure access</span></div>
  </div>

  <section class="lifetime-wrap" aria-labelledby="lifetime-heading">
    <div class="section-label">Founder-style deal</div>
    <article class="lifetime-card">
      <div class="lifetime-inner">
        <div>
          <span class="badge">VIP · Most Popular</span>
          <h2 id="lifetime-heading">AIPU Creator Lifetime Plan</h2>
          <p class="sub">Lifetime access to current tools, trainings, updates, and eligible future platform features included in this plan.</p>
          <p class="social-proof">1,401 creators chose Lifetime in the last hour</p>
          <div class="price-row">
            <span class="price-strike">$1,900</span>
            <span class="price-main">$399</span>
            <span class="price-off">80% OFF</span>
          </div>
          <p class="price-note">No monthly renewal · One-time payment · Instant access</p>
          <a class="btn btn-primary" href="<?= htmlspecialchars($__offer_links['lifetime']); ?>">Get Lifetime Access — $399</a>
          <div class="trust">
            <span>Secure one-time payment</span>
            <span>Instant access</span>
          </div>
        </div>
        <ul class="lifetime-feats">
          <li><b>Everything in Scale Included</b>Unlimited access to 140+ AI models and tools for image, video, text, voice, music, agents, memory, knowledge bases, and agency workflows.</li>
          <li><b>Pay Once. No Monthly Renewal.</b>Get lifetime access with one secure payment instead of paying monthly or yearly.</li>
          <li><b>99 Prebuilt AI Agents Included</b>Use ready-made agents for ads, emails, scripts, research, planning, content, support, and business workflows.</li>
          <li><b>VIP AI University Included</b>Step-by-step training for AI tools, prompting, content creation, automation, agents, and business workflows.</li>
        </ul>
      </div>
    </article>
  </section>

  <div class="toggle-wrap">
    <div class="toggle" role="group" aria-label="Billing period">
      <button type="button" class="on" data-billing="monthly">Monthly</button>
      <button type="button" data-billing="yearly">Yearly<span class="save">Save up to 50%</span></button>
    </div>
  </div>

  <section class="plans" aria-label="Subscription plans">

    <article class="plan" data-plan="creator">
      <div class="plan-tier">Solo</div>
      <h3 data-plan-name>Creator Plan Monthly</h3>
      <p class="tagline">Unlimited AI models for text, image, video, voice, and music workflows.</p>
      <div class="price-row">
        <span class="price-strike" data-strike>$29/mo</span>
        <span class="price-main" data-price>$14.99/mo</span>
        <span class="price-off" data-save>SAVE 48%</span>
      </div>
      <p class="billing" data-billing-note>Flexible monthly billing. Cancel anytime.</p>
      <ul class="feat-list">
        <li><b>AI Image &amp; Video Generation</b>Create videos, images, ads, thumbnails, and social content with SeeDance 2.0, Veo 3.1, Kling, Nano Banana, GPT Image 2, and more.</li>
        <li><b>Unlimited Text AI Usage</b>Use premium text models like GPT-5, Claude 4, Gemini Pro 3, DeepSeek, Grok, Llama, Mistral, Qwen, and more without text usage limits.</li>
        <li><b>140+ AI Models &amp; Tools Included</b>One dashboard for text, image, video, voice, music, agents, automation, and creative workflows.</li>
        <li><b>Voice Agent</b>GPT Realtime · ElevenLabs · Fish Audio</li>
        <li><b>Music Generation</b>Suno · Udio · ElevenLabs Music</li>
        <li><b>All AI Agents Included</b></li>
        <li><b>Persistent Memory</b></li>
        <li><b>Knowledge Bases</b>Up to 50,000 PDFs</li>
      </ul>
      <div class="best-for"><b>Best for</b> Solo builders, creators, and founders who want one AI workspace that remembers.</div>
      <a class="btn btn-outline btn-block" data-cta href="<?= htmlspecialchars($__offer_links['creatormonthly']); ?>">Choose Creator</a>
    </article>

    <article class="plan popular" data-plan="studio">
      <span class="plan-badge">Most Popular</span>
      <div class="plan-tier">Team</div>
      <h3 data-plan-name>Studio Plan Monthly</h3>
      <p class="tagline">Team access to unlimited AI models, agents, memory, and knowledge bases.</p>
      <div class="price-row">
        <span class="price-strike" data-strike>$59/mo</span>
        <span class="price-main" data-price>$29.99/mo</span>
        <span class="price-off" data-save>SAVE 49%</span>
      </div>
      <p class="billing" data-billing-note>Flexible monthly billing. Cancel anytime.</p>
      <ul class="feat-list">
        <li><b>AI Image &amp; Video Generation</b>Create videos, images, ads, thumbnails, and social content with SeeDance 2.0, Veo 3.1, Kling, Nano Banana, GPT Image 2, and more.</li>
        <li><b>Unlimited Text AI Usage</b>Use premium text models like GPT-5, Claude 4, Gemini Pro 3, DeepSeek, Grok, Llama, Mistral, Qwen, and more without text usage limits.</li>
        <li><b>140+ AI Models &amp; Tools Included</b>One dashboard for text, image, video, voice, music, agents, automation, and creative workflows.</li>
        <li><b>Voice Agent</b>GPT Realtime · ElevenLabs · Fish Audio</li>
        <li><b>Music Generation</b>Suno · Udio · ElevenLabs Music</li>
        <li><b>All AI Agents Included</b></li>
        <li><b>Everything in Creator, plus</b></li>
        <li><b>Up to 5 users</b></li>
      </ul>
      <div class="best-for"><b>Best for</b> Small teams running daily workflows and sharing agents + knowledge bases.</div>
      <a class="btn btn-primary btn-block" data-cta href="<?= htmlspecialchars($__offer_links['studiomonthly']); ?>">Choose Studio</a>
    </article>

    <article class="plan" data-plan="scale">
      <div class="plan-tier">Agency</div>
      <h3 data-plan-name>Scale Plan Monthly</h3>
      <p class="tagline">Agency access to unlimited AI models, agents, memory, and knowledge bases.</p>
      <div class="price-row">
        <span class="price-strike" data-strike>$99/mo</span>
        <span class="price-main" data-price>$49.99/mo</span>
        <span class="price-off" data-save>SAVE 50%</span>
      </div>
      <p class="billing" data-billing-note>Flexible monthly billing. Cancel anytime.</p>
      <ul class="feat-list">
        <li><b>AI Image &amp; Video Generation</b>Create videos, images, ads, thumbnails, and social content with SeeDance 2.0, Veo 3.1, Kling, Nano Banana, GPT Image 2, and more.</li>
        <li><b>Unlimited Text AI Usage</b>Use premium text models like GPT-5, Claude 4, Gemini Pro 3, DeepSeek, Grok, Llama, Mistral, Qwen, and more without text usage limits.</li>
        <li><b>140+ AI Models &amp; Tools Included</b>One dashboard for text, image, video, voice, music, agents, automation, and creative workflows.</li>
        <li><b>Voice Agent</b>GPT Realtime · ElevenLabs · Fish Audio</li>
        <li><b>Music Generation</b>Suno · Udio · ElevenLabs Music</li>
        <li><b>All AI Agents Included</b></li>
        <li><b>Everything in Creator, plus</b></li>
        <li><b>Up to 10 users</b></li>
      </ul>
      <div class="best-for"><b>Best for</b> Agencies and operators managing multiple workflows, clients, or departments.</div>
      <a class="btn btn-outline btn-block" data-cta href="<?= htmlspecialchars($__offer_links['scalemonthly']); ?>">Choose Scale</a>
    </article>

  </section>

  <p class="secure">Secure checkout · Cancel anytime on monthly plans</p>
</main>

<footer>
  <p>&copy; <?= date('Y'); ?> OmniRogue Inc.</p>
</footer>

<script>
window.__KK_OFFER_LINKS = <?= json_encode($__offer_links, JSON_UNESCAPED_SLASHES); ?>;
</script>
<script>
(function () {
  var PLANS = {
    creator: {
      monthly: { name: 'Creator Plan Monthly', strike: '$29/mo', price: '$14.99/mo', save: 'SAVE 48%', note: 'Flexible monthly billing. Cancel anytime.', token: 'creatormonthly', cta: 'Choose Creator' },
      yearly:  { name: 'Creator Plan Yearly (Promo)', strike: '$179/yr', price: '$99/yr', save: 'SAVE 45%', note: 'Billed annually. Cancel anytime.', token: 'creatoryearly', cta: 'Choose Creator' }
    },
    studio: {
      monthly: { name: 'Studio Plan Monthly', strike: '$59/mo', price: '$29.99/mo', save: 'SAVE 49%', note: 'Flexible monthly billing. Cancel anytime.', token: 'studiomonthly', cta: 'Choose Studio' },
      yearly:  { name: 'Studio Plan Yearly', strike: '$359/yr', price: '$299/yr', save: 'SAVE 17%', note: 'Billed annually. Cancel anytime.', token: 'studioyearly', cta: 'Choose Studio' }
    },
    scale: {
      monthly: { name: 'Scale Plan Monthly', strike: '$99/mo', price: '$49.99/mo', save: 'SAVE 50%', note: 'Flexible monthly billing. Cancel anytime.', token: 'scalemonthly', cta: 'Choose Scale' },
      yearly:  { name: 'Scale Plan Yearly', strike: '$599/yr', price: '$499/yr', save: 'SAVE 17%', note: 'Billed annually. Cancel anytime.', token: 'scaleyearly', cta: 'Choose Scale' }
    }
  };
  var billing = 'monthly';
  var links = window.__KK_OFFER_LINKS || {};
  var toggleBtns = document.querySelectorAll('.toggle button[data-billing]');

  function applyBilling(mode) {
    billing = mode;
    toggleBtns.forEach(function (btn) {
      btn.classList.toggle('on', btn.getAttribute('data-billing') === mode);
    });
    document.querySelectorAll('.plan[data-plan]').forEach(function (card) {
      var key = card.getAttribute('data-plan');
      var plan = PLANS[key] && PLANS[key][mode];
      if (!plan) return;
      var nameEl = card.querySelector('[data-plan-name]');
      var strikeEl = card.querySelector('[data-strike]');
      var priceEl = card.querySelector('[data-price]');
      var saveEl = card.querySelector('[data-save]');
      var noteEl = card.querySelector('[data-billing-note]');
      var ctaEl = card.querySelector('[data-cta]');
      if (nameEl) nameEl.textContent = plan.name;
      if (strikeEl) strikeEl.textContent = plan.strike;
      if (priceEl) priceEl.textContent = plan.price;
      if (saveEl) saveEl.textContent = plan.save;
      if (noteEl) noteEl.textContent = plan.note;
      if (ctaEl) {
        ctaEl.textContent = plan.cta;
        ctaEl.href = links[plan.token] || '#';
      }
    });
  }

  toggleBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      applyBilling(btn.getAttribute('data-billing'));
    });
  });

  applyBilling('monthly');

  var DURATION = (2 * 24 * 3600 + 11 * 3600 + 47 * 60 + 33) * 1000;
  var key = 'omnirogue_pricing_cd';
  var end = parseInt(sessionStorage.getItem(key), 10);
  if (!end || end < Date.now()) { end = Date.now() + DURATION; sessionStorage.setItem(key, end); }
  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function tick() {
    var diff = Math.max(0, end - Date.now());
    var d = Math.floor(diff / 86400000); diff -= d * 86400000;
    var h = Math.floor(diff / 3600000); diff -= h * 3600000;
    var m = Math.floor(diff / 60000); diff -= m * 60000;
    var s = Math.floor(diff / 1000);
    var dd = document.querySelector('[data-cd-d]');
    var hh = document.querySelector('[data-cd-h]');
    var mm = document.querySelector('[data-cd-m]');
    var ss = document.querySelector('[data-cd-s]');
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
