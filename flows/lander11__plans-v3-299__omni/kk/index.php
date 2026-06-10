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
<meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport"/>
<meta content="#07060f" name="theme-color"/>
<title>AI Prize Vault — Spin To Unlock Your $1 OmniRogue Pass</title>
<meta content="Spin the OmniRogue Prize Vault and unlock 140+ flagship AI tools for $1 today. Limited prize codes available today." name="description"/>
<meta content="noindex,follow" name="robots"/>
<link href="/lander11__plans-v3-299__omni/assets/logo-omnirogue-stack.png" rel="icon" type="image/png"/>
<link href="/lander11__plans-v3-299__omni/assets/logo-omnirogue-stack.png" rel="apple-touch-icon"/>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&amp;family=Inter:wght@400;500;600;700;800;900&amp;family=Anton&amp;display=swap" rel="stylesheet"/>
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init','684677704074883');fbq('track','PageView');
</script>
<noscript><img alt="" decoding="async" height="1" loading="lazy" src="https://www.facebook.com/tr?id=684677704074883&amp;ev=PageView&amp;noscript=1" style="display:none" width="1"/></noscript>
<!-- End Meta Pixel Code -->
<style>
  :root {
    --bg-1: #0a0610;
    --bg-2: #160a24;
    --bg-3: #1f0d36;
    --felt: #0c2218;
    --felt-2: #0a1a13;
    --gold: #ffd84d;
    --gold-2: #f5b800;
    --gold-3: #ffe88f;
    --gold-deep: #b88500;
    --red: #ff2e4e;
    --red-2: #c91234;
    --green: #16d480;
    --green-2: #0fa15f;
    --purple: #b855ff;
    --cyan: #2cffe0;
    --ink: #f5f1e8;
    --muted: #b3aac0;
    --line: rgba(255,255,255,0.08);
    --line-2: rgba(255,255,255,0.16);
    --shadow-gold: 0 0 30px rgba(255, 216, 77, 0.55);
    --sans: 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
    --display: 'Anton', 'Bebas Neue', Impact, sans-serif;
    --rad: 'Bebas Neue', Impact, sans-serif;
  }

  *,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
  html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }
  body {
    font-family: var(--sans);
    color: var(--ink);
    background: var(--bg-1);
    font-size: 16px;
    line-height: 1.55;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    min-height: 100vh;
  }

  /* ============ ATMOSPHERE / BACKGROUND ============ */
  .stage {
    position: relative;
    background:
      radial-gradient(ellipse 80% 50% at 50% 0%, rgba(184, 85, 255, 0.18), transparent 60%),
      radial-gradient(ellipse 60% 40% at 50% 100%, rgba(255, 46, 78, 0.16), transparent 60%),
      radial-gradient(circle at 20% 30%, rgba(255, 216, 77, 0.06), transparent 50%),
      radial-gradient(circle at 80% 70%, rgba(44, 255, 224, 0.06), transparent 50%),
      linear-gradient(180deg, #0a0610 0%, #160a24 50%, #1f0d36 100%);
    min-height: 100vh;
    overflow: hidden;
  }
  .stage::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
      radial-gradient(circle 1px at 12% 22%, rgba(255,255,255,0.6), transparent),
      radial-gradient(circle 1px at 28% 64%, rgba(255,255,255,0.45), transparent),
      radial-gradient(circle 1.2px at 41% 18%, rgba(255,255,255,0.55), transparent),
      radial-gradient(circle 1px at 56% 82%, rgba(255,255,255,0.5), transparent),
      radial-gradient(circle 1.2px at 71% 36%, rgba(255,255,255,0.5), transparent),
      radial-gradient(circle 1px at 83% 72%, rgba(255,255,255,0.55), transparent),
      radial-gradient(circle 1px at 92% 14%, rgba(255,255,255,0.5), transparent),
      radial-gradient(circle 1px at 6% 88%, rgba(255,255,255,0.5), transparent);
    pointer-events: none;
    animation: twinkle 4s ease-in-out infinite alternate;
    opacity: 0.7;
  }
  @keyframes twinkle {
    from { opacity: 0.55; }
    to   { opacity: 0.95; }
  }

  /* ============ BRAND ROW ============ */
  .brand-row {
    position: relative;
    z-index: 5;
    text-align: center;
    padding: 16px 18px 4px;
  }
  .brand-row .logo {
    display: inline-block;
    line-height: 0;
    text-decoration: none;
  }
  .brand-row .logo img {
    height: 40px;
    width: auto;
    display: block;
    filter: drop-shadow(0 6px 18px rgba(184, 85, 255, 0.5));
  }
  @media (max-width: 480px) {
    .brand-row .logo img { height: 32px; }
    .brand-row { padding-top: 12px; }
  }

  /* ============ TOP BAR ============ */
  .topbar {
    position: relative;
    z-index: 5;
    text-align: center;
    padding: 14px 20px;
    background: linear-gradient(90deg, rgba(255,46,78,0.12), rgba(255,216,77,0.12), rgba(184,85,255,0.12));
    border-bottom: 1px solid var(--line);
    backdrop-filter: blur(8px);
    font-family: var(--sans);
    font-size: 12.5px;
    font-weight: 600;
    color: var(--gold-3);
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .topbar .dot {
    display: inline-block;
    width: 8px; height: 8px;
    background: var(--red);
    border-radius: 50%;
    margin-right: 8px;
    vertical-align: middle;
    box-shadow: 0 0 8px var(--red);
    animation: blink 1s infinite;
  }
  @keyframes blink { 50% { opacity: 0.35; } }
  .topbar b { color: #fff; }

  /* ============ HERO ============ */
  .hero {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 40px 20px 24px;
    max-width: 900px;
    margin: 0 auto;
  }

  .hero .eyebrow {
    display: inline-block;
    background: linear-gradient(90deg, var(--red), var(--gold));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    font-family: var(--display);
    font-size: clamp(14px, 2vw, 18px);
    letter-spacing: 0.4em;
    margin-bottom: 14px;
    text-shadow: 0 0 24px rgba(255, 216, 77, 0.3);
  }

  .hero h1 {
    font-family: var(--display);
    font-size: clamp(44px, 9vw, 96px);
    line-height: 0.95;
    letter-spacing: 0.01em;
    color: #fff;
    margin-bottom: 6px;
    text-shadow: 0 4px 0 rgba(0,0,0,0.5), 0 0 40px rgba(255, 216, 77, 0.4);
  }
  .hero h1 .gold {
    background: linear-gradient(180deg, #fff7cf 0%, #ffd84d 40%, #b88500 90%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    -webkit-text-stroke: 1px rgba(0,0,0,0.2);
    filter: drop-shadow(0 2px 0 rgba(0,0,0,0.4));
  }
  .hero h1 .neon {
    color: var(--cyan);
    text-shadow: 0 0 18px rgba(44, 255, 224, 0.7), 0 0 36px rgba(44, 255, 224, 0.4);
    font-style: italic;
  }

  .hero p.sub {
    font-family: var(--sans);
    font-size: clamp(15px, 1.8vw, 18px);
    color: var(--muted);
    max-width: 560px;
    margin: 16px auto 0;
    line-height: 1.55;
  }
  .hero p.sub b { color: var(--gold-3); }

  .live-bar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 18px;
    padding: 8px 18px;
    background: rgba(0,0,0,0.32);
    border: 1px solid rgba(255, 216, 77, 0.25);
    border-radius: 999px;
    font-size: 12.5px;
    color: var(--gold-3);
    letter-spacing: 0.04em;
  }
  .live-bar .sep { width: 4px; height: 4px; border-radius: 50%; background: var(--line-2); }
  .live-bar b { color: #fff; font-weight: 700; }
  @media (max-width: 480px) {
    .live-bar { font-size: 11.5px; gap: 8px; padding: 8px 14px; }
  }

  /* ============ WHEEL STAGE ============ */
  .wheel-stage {
    position: relative;
    margin: 22px auto 0;
    width: min(94vw, 560px);
    padding: 0 0 24px;
    z-index: 2;
  }

  .wheel-frame {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    border-radius: 50%;
    background:
      radial-gradient(circle at 50% 50%, #2b0e4a 0%, #1a0830 60%, #0d041a 100%);
    box-shadow:
      0 0 0 8px #0d041a,
      0 0 0 10px rgba(255,216,77,0.55),
      0 0 0 18px #1a0830,
      0 0 0 20px rgba(255,216,77,0.35),
      0 0 60px rgba(255,216,77,0.35),
      0 30px 80px rgba(0,0,0,0.6);
    display: grid;
    place-items: center;
  }

  /* Outer bulb ring */
  .bulbs {
    position: absolute;
    inset: -2px;
    border-radius: 50%;
    pointer-events: none;
  }
  .bulb {
    position: absolute;
    top: 50%; left: 50%;
    width: 10px; height: 10px;
    border-radius: 50%;
    background: var(--gold);
    box-shadow: 0 0 8px var(--gold), 0 0 16px rgba(255,216,77,0.7);
    transform-origin: 0 0;
  }
  .bulb.on { background: #fff5b0; box-shadow: 0 0 12px #fff5b0, 0 0 24px rgba(255,255,255,0.6); }
  .bulb.alt { background: var(--red); box-shadow: 0 0 8px var(--red), 0 0 16px rgba(255,46,78,0.7); }

  .wheel-inner {
    position: absolute;
    width: 86%;
    height: 86%;
    border-radius: 50%;
    overflow: hidden;
    transform: rotate(0deg);
    transition: transform 5.6s cubic-bezier(0.12, 0.7, 0.16, 1);
    box-shadow:
      inset 0 0 0 6px #0d041a,
      inset 0 0 0 8px rgba(255,216,77,0.6),
      inset 0 0 40px rgba(0,0,0,0.5);
  }

  .wheel-svg {
    display: block;
    width: 100%;
    height: 100%;
  }

  /* Center hub */
  .hub {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 22%;
    aspect-ratio: 1;
    border-radius: 50%;
    background:
      radial-gradient(circle at 35% 30%, #fff8c8 0%, #ffd84d 40%, #b88500 100%);
    box-shadow:
      inset 0 -6px 18px rgba(0,0,0,0.4),
      inset 0 6px 14px rgba(255,255,255,0.5),
      0 0 0 4px #0d041a,
      0 0 0 6px rgba(255,216,77,0.55),
      0 6px 22px rgba(0,0,0,0.5);
    display: grid;
    place-items: center;
    z-index: 4;
  }
  .hub::after {
    content: '';
    position: absolute;
    width: 22%;
    aspect-ratio: 1;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, #fff 0%, #d2a200 80%);
    box-shadow: inset 0 -2px 4px rgba(0,0,0,0.3), 0 0 4px rgba(0,0,0,0.4);
  }
  .hub-label {
    position: absolute;
    color: #2a1500;
    font-family: var(--display);
    font-size: clamp(8px, 1.25vw, 12px);
    letter-spacing: 0.02em;
    text-align: center;
    line-height: 0.95;
    max-width: 92%;
    text-shadow: 0 1px 0 rgba(255,255,255,0.4);
    z-index: 2;
    word-break: break-word;
  }

  /* Pointer */
  .pointer {
    position: absolute;
    top: -2%;
    left: 50%;
    transform: translateX(-50%);
    width: 12%;
    aspect-ratio: 1 / 1.3;
    z-index: 6;
    filter: drop-shadow(0 6px 8px rgba(0,0,0,0.5));
  }
  .pointer svg { width: 100%; height: 100%; display: block; }
  .pointer.bounce { animation: pointerBounce 0.08s ease-in-out; }
  @keyframes pointerBounce {
    0%   { transform: translateX(-50%) translateY(0) rotate(0); }
    50%  { transform: translateX(-50%) translateY(-4px) rotate(-6deg); }
    100% { transform: translateX(-50%) translateY(0) rotate(0); }
  }

  /* ============ SPIN BUTTON ============ */
  .spin-area {
    text-align: center;
    margin-top: 24px;
    position: relative;
    z-index: 3;
  }
  .spin-btn {
    appearance: none;
    border: 0;
    cursor: pointer;
    display: inline-block;
    font-family: var(--display);
    font-size: clamp(24px, 4vw, 36px);
    letter-spacing: 0.18em;
    color: #2a1500;
    background:
      radial-gradient(circle at 30% 25%, #fff8c8 0%, #ffd84d 35%, #f5b800 70%, #b88500 100%);
    padding: 22px 64px;
    border-radius: 999px;
    box-shadow:
      inset 0 -4px 0 #8a6300,
      inset 0 4px 0 rgba(255,255,255,0.5),
      0 0 0 4px #0d041a,
      0 0 0 6px rgba(255,216,77,0.6),
      0 14px 36px rgba(255,216,77,0.55),
      0 22px 60px rgba(0,0,0,0.5);
    transition: transform 0.15s ease, box-shadow 0.2s ease;
    text-shadow: 0 1px 0 rgba(255,255,255,0.5);
    animation: spinPulse 1.4s ease-in-out infinite;
    text-transform: uppercase;
  }
  .spin-btn:hover {
    transform: translateY(-2px) scale(1.02);
  }
  .spin-btn:active {
    transform: translateY(1px) scale(0.99);
    box-shadow:
      inset 0 -2px 0 #8a6300,
      inset 0 2px 0 rgba(255,255,255,0.4),
      0 0 0 4px #0d041a,
      0 0 0 6px rgba(255,216,77,0.6),
      0 6px 18px rgba(255,216,77,0.45);
  }
  .spin-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    animation: none;
  }
  @keyframes spinPulse {
    0%, 100% { box-shadow:
      inset 0 -4px 0 #8a6300,
      inset 0 4px 0 rgba(255,255,255,0.5),
      0 0 0 4px #0d041a,
      0 0 0 6px rgba(255,216,77,0.6),
      0 14px 36px rgba(255,216,77,0.55),
      0 22px 60px rgba(0,0,0,0.5); }
    50% { box-shadow:
      inset 0 -4px 0 #8a6300,
      inset 0 4px 0 rgba(255,255,255,0.5),
      0 0 0 4px #0d041a,
      0 0 0 6px rgba(255,216,77,0.6),
      0 14px 50px rgba(255,216,77,0.85),
      0 22px 76px rgba(0,0,0,0.55); }
  }

  .spin-hint {
    margin-top: 18px;
    font-size: 13px;
    color: var(--muted);
    letter-spacing: 0.04em;
  }
  .spin-hint b { color: var(--gold-3); }

  /* ============ RESULT / WINNER ============ */
  .winner {
    position: relative;
    z-index: 2;
    margin: 30px auto;
    padding: 0 16px;
    max-width: 720px;
    transform: translateY(20px);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.5s ease, transform 0.6s ease;
  }
  .winner.show {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
  }

  .winner-card {
    background:
      linear-gradient(135deg, rgba(255,46,78,0.18), rgba(255,216,77,0.18)),
      linear-gradient(180deg, #1a0830 0%, #0d041a 100%);
    border: 2px solid var(--gold);
    border-radius: 20px;
    padding: 30px 28px 28px;
    text-align: center;
    box-shadow:
      0 0 60px rgba(255,216,77,0.35),
      0 30px 80px rgba(0,0,0,0.5),
      inset 0 1px 0 rgba(255,255,255,0.15);
    position: relative;
    overflow: hidden;
  }
  .winner-card::before {
    content: '';
    position: absolute;
    inset: 4px;
    border-radius: 16px;
    border: 1px dashed rgba(255,216,77,0.35);
    pointer-events: none;
  }
  .winner-card .ribbon {
    display: inline-block;
    background: linear-gradient(90deg, var(--red), var(--red-2));
    color: #fff;
    font-family: var(--display);
    font-size: clamp(13px, 1.5vw, 15px);
    letter-spacing: 0.3em;
    padding: 8px 20px;
    border-radius: 4px;
    margin-bottom: 14px;
    box-shadow: 0 8px 20px rgba(255,46,78,0.4);
  }
  .winner-card h2 {
    font-family: var(--display);
    font-size: clamp(36px, 7vw, 64px);
    color: #fff;
    line-height: 0.95;
    margin-bottom: 8px;
    letter-spacing: 0.02em;
    text-shadow: 0 4px 0 rgba(0,0,0,0.4), 0 0 30px rgba(255,216,77,0.45);
  }
  .winner-card h2 .prize {
    background: linear-gradient(180deg, #fff7cf 0%, #ffd84d 50%, #b88500 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    -webkit-text-stroke: 1px rgba(0,0,0,0.2);
    filter: drop-shadow(0 2px 0 rgba(0,0,0,0.4));
  }
  .winner-card .dek {
    font-family: var(--sans);
    font-size: clamp(15px, 1.8vw, 18px);
    color: var(--muted);
    max-width: 520px;
    margin: 6px auto 22px;
    line-height: 1.55;
  }
  .winner-card .dek b { color: var(--gold-3); }

  .prize-pricing {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin: 0 auto 20px;
    flex-wrap: wrap;
  }
  .pp-old {
    font-family: var(--display);
    font-size: clamp(20px, 3vw, 26px);
    color: #99a;
    text-decoration: line-through;
    letter-spacing: 0.05em;
  }
  .pp-arrow {
    color: var(--gold);
    font-size: 24px;
  }
  .pp-new {
    font-family: var(--display);
    font-size: clamp(36px, 6vw, 56px);
    color: var(--gold);
    letter-spacing: 0.04em;
    text-shadow: 0 0 30px rgba(255,216,77,0.55);
  }
  .pp-new .dollar { color: #fff; font-size: 0.7em; vertical-align: top; margin-right: 4px; }

  .claim-btn {
    display: inline-block;
    text-decoration: none;
    font-family: var(--display);
    font-size: clamp(22px, 3.4vw, 30px);
    letter-spacing: 0.14em;
    color: #fff;
    background: linear-gradient(180deg, #ff6e6e 0%, #ff2e4e 50%, #c91234 100%);
    padding: 20px 44px;
    border-radius: 999px;
    box-shadow:
      inset 0 -4px 0 #8b0a22,
      inset 0 4px 0 rgba(255,255,255,0.35),
      0 12px 30px rgba(255,46,78,0.55),
      0 20px 50px rgba(0,0,0,0.4);
    transition: transform 0.18s ease, box-shadow 0.2s ease;
    text-transform: uppercase;
    text-shadow: 0 2px 0 rgba(0,0,0,0.3);
    animation: claimGlow 1.2s ease-in-out infinite alternate;
  }
  .claim-btn:hover { transform: translateY(-3px) scale(1.02); }
  .claim-btn:active { transform: translateY(1px); }
  @keyframes claimGlow {
    from { box-shadow: inset 0 -4px 0 #8b0a22, inset 0 4px 0 rgba(255,255,255,0.35), 0 12px 30px rgba(255,46,78,0.5), 0 20px 50px rgba(0,0,0,0.4); }
    to   { box-shadow: inset 0 -4px 0 #8b0a22, inset 0 4px 0 rgba(255,255,255,0.35), 0 12px 44px rgba(255,46,78,0.8), 0 20px 60px rgba(0,0,0,0.5); }
  }

  .micro {
    margin-top: 14px;
    font-size: 12.5px;
    color: var(--muted);
    line-height: 1.5;
  }
  .micro b { color: var(--ink); }

  .claim-code {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 2px auto 16px;
    padding: 8px 13px;
    border-radius: 999px;
    background: rgba(22,212,128,0.11);
    border: 1px solid rgba(22,212,128,0.35);
    color: #cfffe5;
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }
  .claim-code b {
    color: #fff;
    font-family: var(--display);
    font-size: 18px;
    letter-spacing: 0.12em;
  }
  .result-steps {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin: 18px 0 18px;
  }
  .result-step {
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    padding: 10px 8px;
    background: rgba(0,0,0,0.22);
    font-size: 11.5px;
    color: var(--muted);
  }
  .result-step b {
    display: block;
    color: var(--gold-3);
    font-size: 12.5px;
    margin-bottom: 2px;
  }
  @media (max-width: 560px) {
    .result-steps { grid-template-columns: 1fr; }
  }

  /* ============ STACK NOTE ============ */
  .stack-note {
    margin: 4px auto 16px;
    padding: 10px 14px;
    border-radius: 10px;
    background: rgba(255, 216, 77, 0.07);
    border: 1px dashed rgba(255, 216, 77, 0.32);
    color: var(--gold-3);
    font-size: 12.5px;
    line-height: 1.5;
    max-width: 560px;
    text-align: center;
  }
  .stack-note b { color: #fff; }

  /* ============ VAULT TIERS ============ */
  .vault-tiers {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
    margin: 4px auto 18px;
    max-width: 720px;
    text-align: left;
  }
  @media (min-width: 720px) {
    .vault-tiers { grid-template-columns: 1fr 1fr; }
    .tier--operator { grid-column: 1 / -1; }
    .tier--entry { grid-column: 1 / -1; }
    .tier-divider { grid-column: 1 / -1; }
  }

  /* Divider between primary $1 prize and upgrade tiers */
  .tier-divider {
    text-align: center;
    margin: 6px 0 0;
    padding: 4px 0 0;
    position: relative;
  }
  .tier-divider span {
    display: inline-block;
    font-family: var(--display);
    font-size: clamp(12px, 1.6vw, 15px);
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--gold-3);
    padding: 6px 16px;
    border: 1px dashed rgba(255,216,77,0.32);
    border-radius: 999px;
    background: rgba(255,216,77,0.05);
  }

  .tier {
    position: relative;
    border-radius: 16px;
    padding: 18px 18px 16px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.10);
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.2s ease;
  }
  .tier:hover { transform: translateY(-2px); }
  .tier h3 {
    font-family: var(--display);
    font-size: clamp(20px, 3vw, 26px);
    letter-spacing: 0.04em;
    color: #fff;
    line-height: 1.05;
    margin: 6px 0 6px;
  }
  .tier h3 span { color: var(--gold-3); }
  .tier-sub {
    font-size: 13.5px;
    color: var(--muted);
    line-height: 1.5;
    margin-bottom: 12px;
  }
  .tier-sub span, .tier-sub b { color: var(--ink); }
  .tier-price {
    display: flex;
    align-items: baseline;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 12px;
  }
  .tier-price s {
    font-family: var(--display);
    font-size: 17px;
    color: #888a99;
    letter-spacing: 0.04em;
  }
  .tier-price b {
    font-family: var(--display);
    font-size: clamp(30px, 4.6vw, 42px);
    color: #fff;
    letter-spacing: 0.02em;
    line-height: 1;
  }
  .tier-price b small {
    font-family: var(--sans);
    font-size: 13px;
    font-weight: 600;
    color: var(--muted);
    letter-spacing: 0.02em;
    margin-left: 4px;
  }
  .tier-bullets {
    list-style: none;
    margin: 0 0 14px;
    padding: 0;
  }
  .tier-bullets li {
    position: relative;
    padding-left: 22px;
    font-size: 13px;
    color: var(--ink);
    line-height: 1.45;
    margin-bottom: 7px;
  }
  .tier-bullets li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 5px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gold), var(--gold-deep));
    box-shadow: 0 0 8px rgba(255,216,77,0.4);
  }
  .tier-bullets li::after {
    content: '';
    position: absolute;
    left: 3.5px;
    top: 8.2px;
    width: 6px;
    height: 3px;
    border-left: 2px solid #2a1500;
    border-bottom: 2px solid #2a1500;
    transform: rotate(-45deg);
  }
  .tier-badge {
    display: inline-block;
    padding: 5px 11px;
    border-radius: 999px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.18);
    color: var(--ink);
    font-family: var(--sans);
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
  }
  .tier-cta {
    display: block;
    text-align: center;
    text-decoration: none;
    font-family: var(--display);
    letter-spacing: 0.12em;
    text-transform: uppercase;
    border-radius: 999px;
    padding: 14px 22px;
    transition: transform 0.18s ease, box-shadow 0.2s ease, filter 0.18s ease;
    box-shadow: 0 8px 22px rgba(0,0,0,0.35);
  }
  .tier-cta:hover { transform: translateY(-2px); filter: brightness(1.05); }
  .tier-cta:active { transform: translateY(1px); }
  .tier-scarcity {
    display: block;
    text-align: center;
    margin-top: 10px;
    font-size: 11.5px;
    color: var(--gold-3);
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }

  /* PRIMARY PRIZE — $1 Vault Pass — featured, top */
  .tier--entry {
    background:
      linear-gradient(135deg, rgba(255,216,77,0.18), rgba(255,46,78,0.14)),
      linear-gradient(180deg, #1a0830 0%, #0d041a 100%);
    border: 2px solid var(--gold);
    box-shadow: 0 0 50px rgba(255,216,77,0.30), 0 18px 50px rgba(0,0,0,0.45);
    padding: 24px 22px 22px;
  }
  .tier--entry h3 {
    font-size: clamp(24px, 3.6vw, 32px);
    background: linear-gradient(180deg, #fff7cf 0%, #ffd84d 50%, #b88500 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    letter-spacing: 0.02em;
    margin: 8px 0 8px;
  }
  .tier--entry h3 span {
    color: var(--gold-3);
    -webkit-text-fill-color: var(--gold-3);
  }
  .tier--entry .tier-sub {
    font-size: 14px;
    color: var(--ink);
  }
  .tier--entry .tier-sub b { color: var(--gold-3); }
  .tier--entry .tier-price b {
    font-size: clamp(36px, 5.2vw, 52px);
    color: var(--gold);
    text-shadow: 0 0 20px rgba(255,216,77,0.45);
  }
  .tier-badge--entry {
    background: linear-gradient(90deg, var(--red), var(--gold-2));
    color: #fff;
    border-color: rgba(255,255,255,0.25);
    box-shadow: 0 0 18px rgba(255,46,78,0.35);
    font-size: 10.5px;
    letter-spacing: 0.16em;
  }
  .tier-cta--entry {
    background: linear-gradient(180deg, #ff6e6e 0%, #ff2e4e 50%, #c91234 100%);
    color: #fff;
    font-size: clamp(20px, 2.8vw, 26px);
    padding: 18px 28px;
    border: 0;
    letter-spacing: 0.14em;
    box-shadow:
      inset 0 -4px 0 #8b0a22,
      inset 0 4px 0 rgba(255,255,255,0.35),
      0 12px 30px rgba(255,46,78,0.55),
      0 20px 50px rgba(0,0,0,0.4);
    text-shadow: 0 2px 0 rgba(0,0,0,0.3);
    animation: claimGlow 1.2s ease-in-out infinite alternate;
  }
  .tier-cta--entry:hover {
    background: linear-gradient(180deg, #ff8585 0%, #ff4060 50%, #d72a4e 100%);
  }

  /* MOST CLAIMED / Lifetime ($399) — strong secondary */
  .tier--lifetime {
    background:
      linear-gradient(135deg, rgba(184,85,255,0.16), rgba(255,216,77,0.10)),
      rgba(13,4,26,0.65);
    border: 2px solid rgba(184,85,255,0.55);
    box-shadow: 0 0 30px rgba(184,85,255,0.20), 0 14px 40px rgba(0,0,0,0.4);
  }
  .tier--lifetime h3 {
    background: linear-gradient(180deg, #fff7cf 0%, #ffd84d 50%, #b855ff 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }
  .tier--lifetime .tier-price b { color: var(--gold-3); }
  .tier-badge--popular {
    background: linear-gradient(90deg, var(--purple), var(--gold-2));
    color: #fff;
    border-color: rgba(255,255,255,0.25);
  }
  .tier-cta--popular {
    background: linear-gradient(180deg, #d68bff 0%, #b855ff 50%, #7a2cc8 100%);
    color: #fff;
    font-size: clamp(16px, 1.9vw, 19px);
    padding: 15px 24px;
    box-shadow:
      inset 0 -4px 0 #4f1989,
      inset 0 4px 0 rgba(255,255,255,0.35),
      0 10px 26px rgba(184,85,255,0.45),
      0 16px 40px rgba(0,0,0,0.4);
    text-shadow: 0 1px 0 rgba(0,0,0,0.25);
  }

  /* University ($49 bolt-on) */
  .tier--university {
    background: rgba(255,216,77,0.05);
    border: 1px solid rgba(255,216,77,0.42);
  }
  .tier--university h3 { color: var(--gold-3); }
  .tier--university .tier-price b { color: var(--gold); }
  .tier--university .tier-cta {
    background: linear-gradient(180deg, #ffe88f 0%, #ffd84d 50%, #f5b800 100%);
    color: #2a1500;
    font-size: clamp(15px, 1.7vw, 17px);
    padding: 13px 22px;
    box-shadow:
      inset 0 -3px 0 #8a6300,
      inset 0 3px 0 rgba(255,255,255,0.5),
      0 8px 20px rgba(255,216,77,0.35);
    text-shadow: 0 1px 0 rgba(255,255,255,0.5);
  }

  /* Operator ($499/mo) — modest agency upsell at bottom */
  .tier--operator {
    background:
      linear-gradient(135deg, rgba(22,212,128,0.10), rgba(255,216,77,0.06)),
      rgba(13,4,26,0.55);
    border: 1px solid rgba(22,212,128,0.42);
    box-shadow: 0 14px 36px rgba(0,0,0,0.4);
    padding: 18px 18px 16px;
  }
  .tier--operator h3 {
    font-size: clamp(20px, 2.8vw, 24px);
    background: linear-gradient(180deg, #2effa6 0%, #1ee79a 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }
  .tier--operator .tier-price b {
    font-size: clamp(28px, 4vw, 36px);
    color: var(--green);
    text-shadow: none;
  }
  .tier-badge--grand {
    background: linear-gradient(90deg, var(--green-2), var(--gold-2));
    color: #fff;
    border-color: rgba(255,255,255,0.20);
    box-shadow: none;
  }
  .tier-cta--grand {
    background: linear-gradient(180deg, #2effa6 0%, #16d480 50%, #0fa15f 100%);
    color: #04220f;
    font-size: clamp(15px, 1.8vw, 18px);
    padding: 13px 22px;
    box-shadow:
      inset 0 -3px 0 #0a6e3f,
      inset 0 3px 0 rgba(255,255,255,0.35),
      0 8px 20px rgba(22,212,128,0.35);
    text-shadow: 0 1px 0 rgba(255,255,255,0.35);
  }

  @media (max-width: 540px) {
    .tier { padding: 16px 14px 14px; }
    .tier--entry { padding: 20px 16px 18px; }
  }

  /* ============ BENEFITS ============ */
  .benefits {
    position: relative;
    z-index: 2;
    margin: 20px auto 40px;
    padding: 0 16px;
    max-width: 720px;
  }
  .benefits-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 22px 22px 18px;
    backdrop-filter: blur(6px);
  }
  .benefits-card .h {
    font-family: var(--display);
    font-size: clamp(18px, 2.6vw, 22px);
    color: var(--gold-3);
    letter-spacing: 0.16em;
    text-align: center;
    margin-bottom: 16px;
  }
  .benefits-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px 18px;
  }
  .benefit {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    font-size: 14px;
    color: var(--ink);
    line-height: 1.4;
  }
  .benefit .chk {
    flex-shrink: 0;
    width: 22px; height: 22px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gold), var(--gold-deep));
    color: #2a1500;
    display: grid;
    place-items: center;
    font-weight: 900;
    font-size: 12px;
    box-shadow: 0 0 12px rgba(255,216,77,0.4);
  }
  @media (max-width: 540px) {
    .benefits-grid { grid-template-columns: 1fr; }
  }

  /* ============ TRUST ROW ============ */
  .trust {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 0 20px 30px;
    color: var(--muted);
    font-size: 12.5px;
    line-height: 1.6;
    max-width: 640px;
    margin: 0 auto;
  }
  .trust b { color: var(--ink); }
  .trust .row {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 14px 22px;
    margin-bottom: 14px;
  }
  .trust .row span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .trust .row svg { color: var(--green); }

  /* ============ BONUS STRIP ============ */
  .bonus-strip {
    position: relative;
    z-index: 2;
    width: min(92vw, 920px);
    margin: 18px auto 34px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
  }
  .bonus-card {
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 16px;
    padding: 16px 15px;
    background: rgba(255,255,255,0.045);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06);
  }
  .bonus-card .num {
    font-family: var(--display);
    font-size: 30px;
    line-height: 1;
    color: var(--gold);
    margin-bottom: 6px;
  }
  .bonus-card b {
    display: block;
    color: #fff;
    font-size: 14px;
    margin-bottom: 4px;
  }
  .bonus-card span {
    display: block;
    color: var(--muted);
    font-size: 12.5px;
    line-height: 1.45;
  }
  @media (max-width: 720px) {
    .bonus-strip { grid-template-columns: 1fr; }
  }

  /* ============ SITE FOOTER (production) ============ */
  .site-foot {
    position: relative;
    z-index: 3;
    border-top: 1px solid rgba(255,255,255,0.08);
    background: rgba(0,0,0,0.42);
    padding: 32px 18px 28px;
    text-align: center;
  }
  .site-foot .sf-inner { max-width: 720px; margin: 0 auto; }
  .site-foot .sf-logo {
    height: 28px;
    width: auto;
    display: block;
    margin: 0 auto 10px;
    opacity: 0.78;
  }
  .site-foot .sf-tag {
    font-size: 12.5px;
    color: rgba(255,255,255,0.78);
    letter-spacing: 0.02em;
    margin-bottom: 16px;
  }
  .site-foot .sf-tag b { color: #fff; font-weight: 600; }
  .site-foot .sf-links {
    font-size: 12.5px;
    color: var(--muted);
    margin-bottom: 16px;
  }
  .site-foot .sf-links a {
    color: rgba(255,255,255,0.82);
    text-decoration: none;
    padding: 0 5px;
  }
  .site-foot .sf-links a:hover { color: #fff; text-decoration: underline; }
  .site-foot .sf-links span { color: rgba(255,255,255,0.3); padding: 0 2px; }
  .site-foot .sf-fine {
    font-size: 10.5px;
    color: rgba(255,255,255,0.5);
    line-height: 1.65;
    max-width: 580px;
    margin: 0 auto 12px;
  }
  .site-foot .sf-fine b { color: rgba(255,255,255,0.8); font-weight: 600; }
  .site-foot .sf-badges {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
    font-size: 10.5px;
    color: rgba(255,255,255,0.6);
    letter-spacing: 0.06em;
    text-transform: uppercase;
    border-top: 1px solid rgba(255,255,255,0.08);
    padding-top: 14px;
    margin-top: 4px;
  }
  .site-foot .sf-badges span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
  .site-foot .sf-badges svg { color: var(--green); }

  /* ============ CONFETTI ============ */
  .confetti {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 30;
    overflow: hidden;
    opacity: 0;
    transition: opacity 0.3s;
  }
  .confetti.show { opacity: 1; }
  .confetti i {
    position: absolute;
    top: -20px;
    width: 10px; height: 16px;
    background: var(--gold);
    opacity: 0;
    transform: rotate(0deg);
    border-radius: 1px;
  }
  .confetti.show i {
    animation: fall linear forwards;
  }
  @keyframes fall {
    0%   { transform: translate3d(0,-20px,0) rotate(0); opacity: 1; }
    100% { transform: translate3d(var(--dx, 40px), 110vh, 0) rotate(720deg); opacity: 1; }
  }

  /* ============ STICKY CTA ============ */
  .sticky-cta {
    position: fixed;
    left: 50%;
    bottom: 14px;
    transform: translate(-50%, 100px);
    z-index: 40;
    background:
      linear-gradient(135deg, rgba(255,46,78,0.18), rgba(255,216,77,0.18)),
      rgba(13, 4, 26, 0.92);
    border: 1px solid rgba(255,216,77,0.45);
    backdrop-filter: blur(10px);
    border-radius: 14px;
    padding: 12px 14px 12px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    max-width: calc(100vw - 24px);
    box-shadow: 0 16px 40px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.08);
    transition: transform 0.4s ease, opacity 0.3s ease;
    opacity: 0;
  }
  .sticky-cta.show { transform: translate(-50%, 0); opacity: 1; }
  .sticky-cta .badge {
    background: var(--red);
    color: #fff;
    font-family: var(--display);
    font-size: 13px;
    letter-spacing: 0.16em;
    padding: 6px 10px;
    border-radius: 4px;
    flex-shrink: 0;
  }
  .sticky-cta .txt {
    font-size: 13.5px;
    color: var(--ink);
    line-height: 1.3;
  }
  .sticky-cta .txt b { color: var(--gold-3); }
  .sticky-cta a {
    flex-shrink: 0;
    background: linear-gradient(180deg, #ff6e6e, #ff2e4e);
    color: #fff;
    font-family: var(--display);
    font-size: 14.5px;
    letter-spacing: 0.12em;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 6px 18px rgba(255,46,78,0.5);
  }
  .sticky-cta a:hover { transform: translateY(-1px); }
  @media (max-width: 600px) {
    .sticky-cta { left: 10px; right: 10px; transform: translateY(120px); max-width: none; gap: 10px; padding: 10px 12px; }
    .sticky-cta.show { transform: translateY(0); }
    .sticky-cta .txt { font-size: 12.5px; }
    .sticky-cta .badge { font-size: 11px; padding: 5px 8px; }
  }

  /* ============ SHAKE FOR WHEEL SPIN ============ */
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-3px); }
    40% { transform: translateX(3px); }
    60% { transform: translateX(-2px); }
    80% { transform: translateX(2px); }
  }
  .wheel-frame.shake { animation: shake 0.5s ease-in-out; }

  /* hide on tiny screens */
  @media (max-width: 380px) {
    .topbar { font-size: 11px; letter-spacing: 0.06em; }
  }
</style>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Plus+Jakarta+Sans:wght@500;600;700;800&amp;family=JetBrains+Mono:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="/lander11__plans-v3-299__omni/assets/main1.css" rel="stylesheet"/>
<link href="/lander11__plans-v3-299__omni/assets/main2.css" rel="stylesheet"/>
<style id="omni-static-fixes">
html{--header-height:64px;--nav-height:64px;--nav-bg:rgba(7,8,18,0.85);color-scheme:dark}
.fixed.top-0.left-0.right-0.z-50 .backdrop-blur-xl,.fixed.top-0.left-0.right-0.z-50 [class*="backdrop-blur"]{backdrop-filter:blur(24px)!important;-webkit-backdrop-filter:blur(24px)!important;}

.omni-lander-wrap{padding-top:calc(var(--nav-height) + env(safe-area-inset-top));min-height:100vh;background:#07080c}
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
/* Announcement / promo bar pinned cleanly directly under the fixed site nav.
   Keyed on the stable kk-announce-bar hook (added at build time) so it works
   regardless of the lander's original bar class name. */
.omni-lander-wrap .kk-announce-bar {
  position: sticky;
  top: calc(var(--nav-height, 64px) + env(safe-area-inset-top, 0px));
  z-index: 40;
}
.omni-lander-wrap .kk-announce-bar .container {
  flex-wrap: nowrap;
  gap: 10px;
  min-height: 0;
}
@media (max-width: 700px) {
  /* On phones, don't let the announcement bar dominate the header: keep it on
     a single compact row and let it scroll away instead of stacking under the
     nav, so the mobile header is just the slim site nav. */
  .omni-lander-wrap .kk-announce-bar {
    position: static;
  }
  .omni-lander-wrap .kk-announce-bar .container {
    flex-wrap: nowrap;
    justify-content: flex-start;
    gap: 8px;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
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
<body>
<div id="__next"><div class="inter_49defa59-module__D5ngMq__variable">
<div class="fixed top-0 left-0 right-0 z-50"><nav class="relative" style="padding-top:env(safe-area-inset-top);height:calc(var(--nav-height) + env(safe-area-inset-top))"><div class="absolute inset-0 backdrop-blur-xl border-b border-border" style="background-color:var(--nav-bg)"></div><div class="container container--xl max-w-7xl mx-auto px-4 sm:px-6 relative h-full"><div class="flex-layout flex-row gap-0 items-center justify-between h-full"><a class="flex items-center touch-manipulation" href="#"><div class="h-[36px] sm:h-[42px] flex items-center"><picture><source srcset="/lander11__plans-v3-299__omni/assets/logo-omnirogue.webp" type="image/webp"/><img alt="OmniRogue" class="object-contain w-auto h-[36px] sm:h-[42px]" data-nimg="1" decoding="async" height="42" loading="lazy" src="/lander11__plans-v3-299__omni/assets/logo-omnirogue.png" srcset="/lander11__plans-v3-299__omni/assets/logo-omnirogue.png 1x, /lander11__plans-v3-299__omni/assets/logo-omnirogue.png 2x" style="color:transparent" width="160"/></picture></div></a><div class="hidden xl:flex items-center gap-1"><a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation relative group" href="#">Home<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a><a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation relative group" href="/lander11__plans-v3-299__omni/createvideo.php<?= $__step1link; ?>">Create Studio<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a><button aria-expanded="false" aria-haspopup="menu" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation outline-none" data-state="closed" id="radix-_R_1lairm_" type="button">Library<svg aria-hidden="true" class="lucide lucide-chevron-down w-3.5 h-3.5 opacity-60" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m6 9 6 6 6-6"></path></svg></button><a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation relative group" href="<?= $__checkout; ?>"><svg aria-hidden="true" class="lucide lucide-tag w-3.5 h-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" fill="currentColor" r=".5"></circle></svg>Pricing<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a><a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation relative group" href="/lander11__plans-v3-299__omni/affiliate.php<?= $__step1link; ?>"><svg aria-hidden="true" class="lucide lucide-handshake w-3.5 h-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m11 17 2 2a1 1 0 1 0 3-3"></path><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"></path><path d="m21 3 1 11h-2"></path><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"></path><path d="M3 4h8"></path></svg>Become Affiliate<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a><a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation relative group" href="/lander11__plans-v3-299__omni/help-center.php<?= $__step1link; ?>">Help<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a></div><div class="hidden xl:flex items-center gap-3 ml-4"><div tabindex="0"><a class="inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 cursor-pointer bg-linear-to-r from-primary to-primary-glow text-primary-foreground shadow-[0_4px_24px_rgba(139,92,255,0.35)] hover:shadow-[0_8px_32px_rgba(139,92,255,0.5)] hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] h-8 rounded-md px-3 text-xs gap-1.5" href="<?= $__checkout; ?>"><svg aria-hidden="true" class="lucide lucide-crown w-3.5 h-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>Create Account</a></div><a class="inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 cursor-pointer text-foreground hover:bg-[rgba(255,255,255,0.06)] active:scale-[0.98] h-8 rounded-md px-3 text-xs border border-border/50 hover:border-border gap-1.5" href="<?= $__checkout; ?>"><svg aria-hidden="true" class="lucide lucide-log-in w-3.5 h-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m10 17 5-5-5-5"></path><path d="M15 12H3"></path><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path></svg>Login</a></div><button aria-controls="radix-_R_9airm_" aria-expanded="false" aria-haspopup="dialog" aria-label="Open menu" class="xl:hidden p-2 rounded-lg text-foreground hover:bg-[rgba(255,255,255,0.06)] transition-colors touch-manipulation" data-state="closed" type="button"><svg aria-hidden="true" class="lucide lucide-menu w-5 h-5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M4 5h16"></path><path d="M4 12h16"></path><path d="M4 19h16"></path></svg></button></div></div></nav></div>
<div class="omni-lander-wrap">
<div class="stage">
<!-- Brand row -->
<div class="brand-row">
<a aria-label="OmniRogue" class="logo" href="#">
<picture><source srcset="/lander11__plans-v3-299__omni/assets/logo-omnirogue-h.webp" type="image/webp"/><img alt="OmniRogue" decoding="async" height="422" loading="lazy" src="/lander11__plans-v3-299__omni/assets/logo-omnirogue-h.png" width="1024"/></picture>
</a>
</div>
<!-- Top urgency bar -->
<div class="topbar">
<span class="dot"></span>
<span><b>LIVE AI PRIZE VAULT</b> · 3 codes left in this session · <b>1 spin per device</b></span>
</div>
<!-- Hero header -->
<header class="hero">
<div class="eyebrow">★ PERSONALIZED AI PRIZE DROP ★</div>
<h1>
<span class="gold">SPIN</span>
<span class="neon">YOUR $1 PASS</span>
</h1>
<p class="sub">
      Spin the OmniRogue Prize Vault and unlock instant access to <b>140+ flagship AI tools</b> - video, images, writing, voice, music, agents, and more - for just <b>$1 today</b>.
    </p>
<div class="live-bar">
<span><b>2,341</b> prize codes claimed</span>
<span class="sep"></span>
<span>Expires in <b id="countdown">09:59</b></span>
<span class="sep"></span>
<span>Today's vault value: <b>$497</b></span>
</div>
</header>
<!-- Wheel -->
<div class="wheel-stage">
<div class="wheel-frame" id="wheelFrame">
<!-- Pointer at top -->
<div class="pointer" id="pointer">
<svg aria-hidden="true" viewbox="0 0 60 78" xmlns="http://www.w3.org/2000/svg">
<defs>
<lineargradient id="ptGrad" x1="0" x2="0" y1="0" y2="1">
<stop offset="0%" stop-color="#fff8c8"></stop>
<stop offset="40%" stop-color="#ffd84d"></stop>
<stop offset="100%" stop-color="#b88500"></stop>
</lineargradient>
</defs>
<path d="M30 76 L8 26 Q8 4 30 4 Q52 4 52 26 Z" fill="url(#ptGrad)" stroke="#0d041a" stroke-width="3"></path>
<circle cx="30" cy="22" fill="#0d041a" r="6"></circle>
</svg>
</div>
<!-- Bulb ring -->
<div class="bulbs" id="bulbs"></div>
<!-- Wheel inner -->
<div class="wheel-inner" id="wheel">
<svg aria-hidden="true" class="wheel-svg" viewbox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
<!-- 8 segments, each 45deg, starting at -22.5 so first segment is centered at top -->
<defs>
<radialgradient cx="0.5" cy="0.5" id="seg1" r="0.7">
<stop offset="0%" stop-color="#3a1565"></stop>
<stop offset="100%" stop-color="#1a0830"></stop>
</radialgradient>
<radialgradient cx="0.5" cy="0.5" id="seg2" r="0.7">
<stop offset="0%" stop-color="#ff6e6e"></stop>
<stop offset="100%" stop-color="#c91234"></stop>
</radialgradient>
<radialgradient cx="0.5" cy="0.5" id="seg3" r="0.7">
<stop offset="0%" stop-color="#3a1565"></stop>
<stop offset="100%" stop-color="#1a0830"></stop>
</radialgradient>
<radialgradient cx="0.5" cy="0.5" id="seg4" r="0.7">
<stop offset="0%" stop-color="#fff7cf"></stop>
<stop offset="100%" stop-color="#b88500"></stop>
</radialgradient>
<radialgradient cx="0.5" cy="0.5" id="seg5" r="0.7">
<stop offset="0%" stop-color="#3a1565"></stop>
<stop offset="100%" stop-color="#1a0830"></stop>
</radialgradient>
<radialgradient cx="0.5" cy="0.5" id="seg6" r="0.7">
<stop offset="0%" stop-color="#ff6e6e"></stop>
<stop offset="100%" stop-color="#c91234"></stop>
</radialgradient>
<radialgradient cx="0.5" cy="0.5" id="seg7" r="0.7">
<stop offset="0%" stop-color="#3a1565"></stop>
<stop offset="100%" stop-color="#1a0830"></stop>
</radialgradient>
<radialgradient cx="0.5" cy="0.5" id="seg8" r="0.7">
<stop offset="0%" stop-color="#1ee79a"></stop>
<stop offset="100%" stop-color="#0fa15f"></stop>
</radialgradient>
</defs>
<!-- Segment slices: 8 wedges (45deg each), starting from -22.5 deg so segment 0 is centered at top -->
<!-- Use group rotated for each -->
<g transform="translate(200,200)">
<!-- segment 0: top -->
<g>
<path d="M 0 0 L 0 -200 A 200 200 0 0 1 141.42 -141.42 Z" fill="url(#seg1)" stroke="#0d041a" stroke-width="3"></path>
</g>
<g transform="rotate(45)">
<path d="M 0 0 L 0 -200 A 200 200 0 0 1 141.42 -141.42 Z" fill="url(#seg2)" stroke="#0d041a" stroke-width="3"></path>
</g>
<g transform="rotate(90)">
<path d="M 0 0 L 0 -200 A 200 200 0 0 1 141.42 -141.42 Z" fill="url(#seg3)" stroke="#0d041a" stroke-width="3"></path>
</g>
<g transform="rotate(135)">
<path d="M 0 0 L 0 -200 A 200 200 0 0 1 141.42 -141.42 Z" fill="url(#seg4)" stroke="#0d041a" stroke-width="3"></path>
</g>
<g transform="rotate(180)">
<path d="M 0 0 L 0 -200 A 200 200 0 0 1 141.42 -141.42 Z" fill="url(#seg5)" stroke="#0d041a" stroke-width="3"></path>
</g>
<g transform="rotate(225)">
<path d="M 0 0 L 0 -200 A 200 200 0 0 1 141.42 -141.42 Z" fill="url(#seg6)" stroke="#0d041a" stroke-width="3"></path>
</g>
<g transform="rotate(270)">
<path d="M 0 0 L 0 -200 A 200 200 0 0 1 141.42 -141.42 Z" fill="url(#seg7)" stroke="#0d041a" stroke-width="3"></path>
</g>
<g transform="rotate(315)">
<path d="M 0 0 L 0 -200 A 200 200 0 0 1 141.42 -141.42 Z" fill="url(#seg8)" stroke="#0d041a" stroke-width="3"></path>
</g>
<!-- Labels: rotated to face outward, centered along each segment's midline -->
<!-- Each label is at angle midpoint of its slice -->
<g fill="#fff" font-family="Anton, Bebas Neue, Impact, sans-serif" font-weight="700" letter-spacing="1" text-anchor="middle">
<!-- segment 0 (top), midpoint at -90deg -> already at top: rotate 0, x=0, y around -135 -->
<g transform="rotate(0)">
<text fill="#ffd84d" font-size="20" x="0" y="-130">FREE</text>
<text fill="#ffe88f" font-size="14" x="0" y="-108">CREDITS</text>
</g>
<g transform="rotate(45)">
<text fill="#fff" font-size="22" x="0" y="-130">50% OFF</text>
<text fill="#ffe1e6" font-size="12" x="0" y="-108">MEMBERSHIP</text>
</g>
<g transform="rotate(90)">
<text fill="#ffd84d" font-size="18" x="0" y="-126">SPIN</text>
<text fill="#ffd84d" font-size="18" x="0" y="-108">AGAIN</text>
</g>
<!-- segment 3 (grand prize) - gold -->
<g transform="rotate(135)">
<text fill="#2a1500" font-size="26" letter-spacing="2" x="0" y="-138">$1</text>
<text fill="#2a1500" font-size="11" letter-spacing="3" x="0" y="-118">FULL</text>
<text fill="#2a1500" font-size="11" letter-spacing="3" x="0" y="-104">ACCESS</text>
</g>
<g transform="rotate(180)">
<text fill="#ffd84d" font-size="20" x="0" y="-130">25% OFF</text>
<text fill="#ffe88f" font-size="11" x="0" y="-108">FIRST MONTH</text>
</g>
<g transform="rotate(225)">
<text fill="#fff" font-size="20" x="0" y="-130">VIP</text>
<text fill="#ffe1e6" font-size="12" x="0" y="-108">UPGRADE</text>
</g>
<g transform="rotate(270)">
<text fill="#ffd84d" font-size="14" x="0" y="-126">MYSTERY</text>
<text fill="#ffd84d" font-size="14" x="0" y="-108">PRIZE</text>
</g>
<g transform="rotate(315)">
<text fill="#fff" font-size="18" x="0" y="-126">FREE</text>
<text fill="#cfffe5" font-size="14" x="0" y="-108">TRIAL</text>
</g>
</g>
<!-- Decorative inner dots -->
<circle cx="0" cy="0" fill="none" r="160" stroke="rgba(255,216,77,0.18)" stroke-dasharray="3 8" stroke-width="1"></circle>
</g>
</svg>
</div>
<!-- Hub -->
<div class="hub">
<div class="hub-label">OmniRogue</div>
</div>
</div>
<!-- Spin button -->
<div class="spin-area">
<button class="spin-btn" id="spinBtn" type="button">SPIN THE WHEEL</button>
<div class="spin-hint" id="spinHint">Tap once - your <b>$1 vault pass</b> is already reserved</div>
</div>
</div>
<!-- Winner reveal -->
<section aria-hidden="true" class="winner" id="winner">
<div class="winner-card">
<div class="ribbon">★ VAULT UNLOCKED ★</div>
<div class="claim-code">Claim code <b id="claimCode">OMNI-1PASS</b></div>
<h2>YOU UNLOCKED <span class="prize">$1 FULL ACCESS</span></h2>
<p class="dek">
        Your spin reserved your <b>$1 AI Vault Pass</b> — 7 days of full access to <b>Sora 2, Veo 3.1, GPT-5.2, Claude 4.7</b>, Nano Banana 2, Midjourney‑class image, Suno, ElevenLabs, agents, and 130+ more flagship models. <b>Then $15/mo. Cancel anytime in one click.</b>
</p>
<div class="result-steps">
<div class="result-step"><b>1. Pass reserved</b>Your $1 vault code is locked in.</div>
<div class="result-step"><b>2. $496 credit applied</b>Today's spin discount is active.</div>
<div class="result-step"><b>3. Login ready</b>Create your account to activate access.</div>
</div>
<div class="vault-tiers" id="vaultTiers">
<article class="tier tier--entry">
<span class="tier-badge tier-badge--entry">YOUR PRIZE · $1 FULL ACCESS</span>
<h3>$1 AI Vault Pass — 7 Days Full Access</h3>
<p class="tier-sub">All 140+ flagship AI models under one login for <b>$1 today</b>. Then $15/mo. Cancel anytime in one click.</p>
<ul class="tier-bullets">
<li>Sora 2, Veo 3.1, GPT-5.2, Claude 4.7, ElevenLabs v3, Suno v4.5</li>
<li>Midjourney‑class image, Nano Banana 2, Flux Pro, Ideogram, HeyGen 4</li>
<li>Agent Studio, Code Studio + 130 more under one login</li>
</ul>
<div class="tier-price"><s>$497/mo value</s><b>$1<small>today</small></b></div>
<a class="tier-cta tier-cta--entry" data-tier="trial" data-value="1" href="<?= $__checkout; ?>" id="claimBtn">UNLOCK $1 PASS →</a>
<span class="tier-scarcity">3 prize codes left in this session</span>
</article>
<div class="tier-divider"><span>Or stack your $1 prize with an upgrade ↓</span></div>
<article class="tier tier--lifetime">
<span class="tier-badge tier-badge--popular">MOST CLAIMED · LIFETIME</span>
<h3>OmniRogue Lifetime Vault</h3>
<p class="tier-sub">One payment, kill the $15/mo forever. Same 140+ models, locked in for life.</p>
<div class="tier-price"><s>$1,800 / 10 yrs</s><b>$399<small>once</small></b></div>
<ul class="tier-bullets">
<li>Pays for itself vs $15/mo in 27 months — yours forever</li>
<li>Every future model release, free, for life</li>
<li>30-day money-back guarantee</li>
</ul>
<a class="tier-cta tier-cta--popular" data-tier="lifetime" data-value="399" href="<?= $__checkout; ?>">LOCK LIFETIME · $399 →</a>
</article>
<article class="tier tier--university">
<span class="tier-badge">BONUS · STACKS WITH ANY TIER</span>
<h3>OmniRogue University Lifetime</h3>
<p class="tier-sub">The #1 ranked AI training program. 120+ hours, 14 specialization tracks, verified certifications, alumni community.</p>
<div class="tier-price"><s>$1,997</s><b>$49<small>one-time</small></b></div>
<ul class="tier-bullets">
<li>Lifetime access to every course + every future track</li>
<li>Verified AI certifications you can put on LinkedIn</li>
<li>Weekly live cohort calls + private alumni network</li>
</ul>
<a class="tier-cta" data-tier="university" data-value="49" href="<?= $__checkout; ?>">ADD UNIVERSITY · $49 →</a>
</article>
<article class="tier tier--operator">
<span class="tier-badge tier-badge--grand">AGENCY · OPERATOR TIER</span>
<h3>Own Your Own AI Platform</h3>
<p class="tier-sub">White-label OmniRogue under your brand. Unlimited client seats. Done-for-you agency. Sell AI, don't just use it.</p>
<div class="tier-price"><s>$20,000+ build</s><b>$499<small>/mo flat</small></b></div>
<ul class="tier-bullets">
<li>White-label dashboard, your logo, your domain</li>
<li>Unlimited client seats, no per-user fees</li>
<li>"First client in 60 days" sprint + dedicated manager</li>
</ul>
<a class="tier-cta tier-cta--grand" data-tier="enterprise" data-value="499" href="<?= $__checkout; ?>">CLAIM OPERATOR PRIZE · $499/mo →</a>
<span class="tier-scarcity">7 of 25 operator seats left this month</span>
</article>
</div>
<div class="stack-note">
<b>Stack your prizes —</b> $1 Pass + University = <b>$50 once</b>. Lifetime + University = <b>$448 once</b>. Most claimants stack at least one upgrade.
      </div>
<div class="micro">
        $1 today · 7 full days of access · $15/mo after · <b>cancel anytime in one click</b> · double money-back guarantee. Lifetime &amp; Operator tiers each carry their own 30-day refund window.
      </div>
</div>
</section>
<!-- Benefits -->
<section class="benefits" id="benefits" style="display:none;">
<div class="benefits-card">
<div class="h">★ WHAT THE $1 VAULT UNLOCKS ★</div>
<div class="benefits-grid">
<div class="benefit"><span class="chk">✓</span><span><b>Sora 2 &amp; Veo 3.1</b> - 4K AI video, unlimited renders</span></div>
<div class="benefit"><span class="chk">✓</span><span><b>GPT-5.2 &amp; Claude 4.7</b> - flagship text + agents</span></div>
<div class="benefit"><span class="chk">✓</span><span><b>Midjourney‑class</b> images, Flux Pro, Ideogram</span></div>
<div class="benefit"><span class="chk">✓</span><span><b>Nano Banana 2</b> - image generation and remixes</span></div>
<div class="benefit"><span class="chk">✓</span><span><b>ElevenLabs v3</b> - voice cloning &amp; voiceover</span></div>
<div class="benefit"><span class="chk">✓</span><span><b>Suno v4.5</b> - studio music in seconds</span></div>
<div class="benefit"><span class="chk">✓</span><span><b>HeyGen 4</b> AI avatars &amp; lip‑sync</span></div>
<div class="benefit"><span class="chk">✓</span><span><b>Agent Studio</b> - autonomous AI agents</span></div>
</div>
</div>
</section>
<section aria-label="Prize vault proof" class="bonus-strip">
<div class="bonus-card">
<div class="num">140+</div>
<b>AI tools under one login</b>
<span>Video, images, audio, text, code, automations, and agents.</span>
</div>
<div class="bonus-card">
<div class="num">$1</div>
<b>Activates your prize today</b>
<span>No giant software stack. Start with the discounted trial pass.</span>
</div>
<div class="bonus-card">
<div class="num">1 click</div>
<b>Cancel anytime</b>
<span>Simple account controls and a double money-back guarantee.</span>
</div>
</section>
<!-- Trust row -->
<div class="trust">
<div class="row">
<span>
<svg fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24" width="16"><path d="M5 12l5 5L20 7"></path></svg>
        Secure SSL checkout
      </span>
<span>
<svg fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24" width="16"><path d="M5 12l5 5L20 7"></path></svg>
        Cancel in 1 click
      </span>
<span>
<svg fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" viewbox="0 0 24 24" width="16"><path d="M5 12l5 5L20 7"></path></svg>
        2x money‑back guarantee
      </span>
</div>
<div>By spinning you agree to OmniRogue's terms and acknowledge this is a paid promotional offer. Limit one prize code per household. Offer subject to change.</div>
</div>
<!-- ============ SITE FOOTER ============ -->
</div>
<!-- Confetti layer -->
<div class="confetti" id="confetti"></div>
<!-- Sticky CTA -->
<div class="sticky-cta" id="stickyCTA">
<span class="badge">$1 VAULT</span>
<span class="txt">Your <b>$1 vault pass</b> is reserved</span>
<a href="<?= $__checkout; ?>" id="stickyClaim">Unlock →</a>
</div>
<script>
(function(){
  // ============ BULB RING GENERATION ============
  var bulbs = document.getElementById('bulbs');
  if (bulbs) {
    var count = 24;
    for (var i = 0; i < count; i++) {
      var angle = (360 / count) * i;
      var b = document.createElement('span');
      b.className = 'bulb' + (i % 2 ? ' alt' : '');
      b.style.transform = 'rotate(' + angle + 'deg) translate(0, -50%) translateY(-' + (window.innerWidth < 480 ? 142 : 226) + 'px)';
      b.dataset.idx = i;
      bulbs.appendChild(b);
    }
    // Recompute on resize
    window.addEventListener('resize', function(){
      var frame = document.getElementById('wheelFrame');
      var r = frame ? frame.offsetWidth/2 + 4 : 230;
      Array.prototype.forEach.call(bulbs.children, function(node, idx){
        var a = (360 / count) * idx;
        node.style.transform = 'rotate(' + a + 'deg) translate(0, -50%) translateY(-' + r + 'px)';
      });
    });
    // Initial precise positioning after layout
    requestAnimationFrame(function(){
      var frame = document.getElementById('wheelFrame');
      var r = frame ? frame.offsetWidth/2 + 4 : 230;
      Array.prototype.forEach.call(bulbs.children, function(node, idx){
        var a = (360 / count) * idx;
        node.style.transform = 'rotate(' + a + 'deg) translate(0, -50%) translateY(-' + r + 'px)';
      });
    });
    // Marquee animation: turn bulbs on/off in rotation
    var phase = 0;
    setInterval(function(){
      phase = (phase + 1) % 24;
      Array.prototype.forEach.call(bulbs.children, function(node, idx){
        if (idx % 3 === phase % 3) node.classList.add('on');
        else node.classList.remove('on');
      });
    }, 220);
  }

  // ============ SPIN LOGIC ============
  var wheel    = document.getElementById('wheel');
  var spinBtn  = document.getElementById('spinBtn');
  var spinHint = document.getElementById('spinHint');
  var winner   = document.getElementById('winner');
  var benefits = document.getElementById('benefits');
  var confetti = document.getElementById('confetti');
  var frame    = document.getElementById('wheelFrame');
  var countdown = document.getElementById('countdown');
  var tierCtas = Array.prototype.slice.call(document.querySelectorAll('.tier-cta'));

  // Per-tier InitiateCheckout — gives Meta CAPI the tier value so it can optimize for AOV mix
  tierCtas.forEach(function(btn){
    btn.addEventListener('click', function(){
      var tier  = btn.getAttribute('data-tier') || 'unknown';
      var value = parseFloat(btn.getAttribute('data-value')) || 0;
      try {
        if (typeof fbq === 'function') {
          fbq('track', 'InitiateCheckout', {
            content_name: 'Prize Tier - ' + tier,
            content_category: 'omnirogue-lander11',
            content_ids: [tier],
            num_items: 1,
            value: value,
            currency: 'USD'
          });
        }
      } catch(e){}
    });
  });

  // Cosmetic scarcity countdown for the reserved code.
  var secondsLeft = 9 * 60 + 59;
  setInterval(function(){
    if (!countdown) return;
    secondsLeft = Math.max(0, secondsLeft - 1);
    var m = Math.floor(secondsLeft / 60);
    var s = secondsLeft % 60;
    countdown.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
  }, 1000);

  var spun = false;
  // Grand prize segment index = 3 (top -> 0, then clockwise). Segments rotate by 45deg each.
  // Wheel rotates clockwise; pointer is at top.
  // To land on segment 3, we want segment 3's center to be at top after rotation.
  // segment 3's center is at angle 3*45 = 135 deg from top (clockwise).
  // We need to rotate the wheel by (360 - 135) = 225 deg to bring it to top, plus add full rotations.
  // Use 6 full rotations + (360 - 135) = 2160 + 225 = 2385 deg, plus small jitter.
  function doSpin() {
    if (spun) return;
    spun = true;
    spinBtn.disabled = true;
    spinBtn.textContent = 'OPENING…';
    spinHint.innerHTML = 'Reserving your <b>$1 vault pass</b>…';

    var jitter = (Math.random() * 6) - 3; // -3 to +3 deg
    var finalRot = (360 * 7) + 225 + jitter;
    wheel.style.transform = 'rotate(' + finalRot + 'deg)';

    // Pointer bounce loop as wheel passes each segment (simulate clicks)
    var tickStart = performance.now();
    var duration = 5600;
    function tick(now){
      var t = now - tickStart;
      if (t >= duration) {
        finalize();
        return;
      }
      // bounce at decreasing intervals
      var remaining = duration - t;
      var nextDelay = Math.max(60, 200 - (200 * (t / duration)));
      // We just visually bounce the pointer at a regular cadence
      var ptr = document.getElementById('pointer');
      if (ptr) {
        ptr.classList.remove('bounce');
        // trigger reflow
        void ptr.offsetWidth;
        ptr.classList.add('bounce');
      }
      setTimeout(function(){ requestAnimationFrame(tick); }, Math.max(80, nextDelay));
    }
    requestAnimationFrame(tick);

    function finalize() {
      spinBtn.style.display = 'none';
      spinHint.style.display = 'none';
      frame.classList.add('shake');
      setTimeout(function(){ frame.classList.remove('shake'); }, 600);

      winner.classList.add('show');
      winner.setAttribute('aria-hidden', 'false');
      benefits.style.display = 'block';

      // FB pixel: Lead event when user "wins" (engaged)
      try {
        if (typeof fbq === 'function') {
          fbq('track', 'Lead', {
            content_name: 'Prize Vault - $1 Pass',
            content_category: 'omnirogue-lander11',
            content_ids: ['trial'],
            value: 1.00,
            currency: 'USD'
          });
        }
      } catch(e){}

      launchConfetti();

      // Scroll winner card into view
      setTimeout(function(){
        winner.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 350);
    }
  }
  spinBtn.addEventListener('click', doSpin);
  spinBtn.addEventListener('touchend', function(e){ e.preventDefault(); doSpin(); }, { passive: false });

  // ============ CONFETTI ============
  function launchConfetti() {
    confetti.innerHTML = '';
    var colors = ['#ffd84d', '#ff2e4e', '#16d480', '#2cffe0', '#b855ff', '#ffe88f', '#fff'];
    var pieces = 90;
    for (var i = 0; i < pieces; i++) {
      var p = document.createElement('i');
      var size = 6 + Math.random() * 10;
      p.style.left  = (Math.random() * 100) + 'vw';
      p.style.width = size + 'px';
      p.style.height = (size * 1.6) + 'px';
      p.style.background = colors[i % colors.length];
      p.style.setProperty('--dx', ((Math.random() - 0.5) * 320) + 'px');
      p.style.animationDuration = (3 + Math.random() * 2.2) + 's';
      p.style.animationDelay = (Math.random() * 0.6) + 's';
      p.style.borderRadius = (i % 3 === 0) ? '50%' : '1px';
      confetti.appendChild(p);
    }
    confetti.classList.add('show');
    setTimeout(function(){ confetti.classList.remove('show'); confetti.innerHTML = ''; }, 6500);
  }

  // ============ STICKY CTA ============
  var sticky = document.getElementById('stickyCTA');
  function onScroll() {
    if (!sticky) return;
    if (!spun) { sticky.classList.remove('show'); return; }
    var y = window.scrollY || window.pageYOffset;
    var h = document.documentElement.scrollHeight - window.innerHeight;
    var atBottom = h - y < 240;
    // Show once user has won and scrolled at all, hide near bottom
    if (y > 120 && !atBottom) sticky.classList.add('show');
    else sticky.classList.remove('show');
  }
  window.addEventListener('scroll', onScroll, { passive: true });

  // ============ Pixel: outbound clicks ============
  (function(){
    var navigating = false;
    function getName(el){
      var n = (el.getAttribute && el.getAttribute('aria-label')) || el.textContent || '';
      return n.replace(/\s+/g, ' ').trim().slice(0, 80);
    }
    function isHash(a){
      var raw = a.getAttribute('href');
      if (!raw) return true;
      if (raw.charAt(0) === '#') return true;
      try {
        var url = new URL(a.href, window.location.href);
        return url.origin === window.location.origin && url.pathname === window.location.pathname && !!url.hash;
      } catch (e){ return false; }
    }
    function fire(payload, cb){
      if (typeof window.fbq !== 'function') { if (cb) cb(); return; }
      var done = false;
      var finish = function(){ if (done) return; done = true; if (cb) cb(); };
      try { window.fbq('track', 'ViewContent', payload, { eventCallback: finish }); }
      catch(e) { try { window.fbq('track', 'ViewContent', payload); } catch(_){} }
      setTimeout(finish, 350);
    }
    document.addEventListener('click', function(e){
      if (e.button !== undefined && e.button !== 0) return;
      var t = e.target; if (!t || !t.closest) return;
      var a = t.closest('a[href]'); if (!a) return;
      var raw = a.getAttribute('href');
      if (!raw || raw.indexOf('javascript:') === 0) return;
      if (isHash(a)) return;
      var dest;
      try { dest = new URL(a.href, window.location.href).href; } catch(_){ dest = raw; }
      var payload = {
        content_name: getName(a) || dest,
        content_category: 'spin_to_win_click',
        content_ids: [dest]
      };
      var newTab = a.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey;
      if (newTab) { fire(payload); return; }
      if (navigating) return;
      navigating = true;
      e.preventDefault();
      fire(payload, function(){ window.location.href = dest; });
    }, true);
  })();
})();
</script>
</div>
<footer class="relative border-t border-border bg-background"><div class="absolute inset-0 bg-linear-to-t from-background via-background-secondary/30 to-transparent pointer-events-none"></div><div class="container container--xl max-w-7xl mx-auto px-4 sm:px-6 relative py-10 sm:py-16"><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 mb-10 sm:mb-16"><div class="sm:col-span-2"><a class="flex items-center mb-4" href="#"><div class="flex-layout flex-row gap-0 items-center h-[42px]"><picture><source srcset="/lander11__plans-v3-299__omni/assets/logo-omnirogue.webp" type="image/webp"/><img alt="OmniRogue" class="object-contain" data-nimg="1" decoding="async" height="42" loading="lazy" src="/lander11__plans-v3-299__omni/assets/logo-omnirogue.png" srcset="/lander11__plans-v3-299__omni/assets/logo-omnirogue.png 1x, /lander11__plans-v3-299__omni/assets/logo-omnirogue.png 2x" style="color:transparent" width="160"/></picture></div></a><p class="typography typography--body2 text-sm text-(--text-secondary) mb-6 max-w-xs leading-relaxed">Deploy powerful AI agents for content creation, automation, and beyond.</p></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Product</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander11__plans-v3-299__omni/createvideo.php<?= $__step1link; ?>">AI Agent</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander11__plans-v3-299__omni/createvideo.php<?= $__step1link; ?>">AI Studio</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander11__plans-v3-299__omni/createvideo.php<?= $__step1link; ?>">Create Studio</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander11__plans-v3-299__omni/create-voice-agents.php<?= $__step1link; ?>">Voice Agents</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander11__plans-v3-299__omni/knowledge-base.php<?= $__step1link; ?>">Knowledge Base</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="<?= $__checkout; ?>">Pricing</a></div></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Resources</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander11__plans-v3-299__omni/help-center.php<?= $__step1link; ?>">Community</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="<?= $__checkout; ?>">Contact Us</a></div></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Legal</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander11__plans-v3-299__omni/privacy-policy.php<?= $__step1link; ?>">Privacy Policy</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander11__plans-v3-299__omni/terms-of-service.php<?= $__step1link; ?>">Terms of Service</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander11__plans-v3-299__omni/data-deletion-request.php<?= $__step1link; ?>">Data Deletion Request</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander11__plans-v3-299__omni/acceptable-use-policy.php<?= $__step1link; ?>">Acceptable Use Policy</a></div></div></div><div class="shrink-0 h-[1px] w-full bg-border divider my-4" data-orientation="horizontal" role="none"></div><div class="flex-layout gap-4 items-center justify-between flex-col md:flex-row pt-8"><span class="typography typography--caption text-xs leading-normal text-(--text-muted)">© <!-- -->2026<!-- --> <!-- -->OmniRogue Inc.<!-- --> · <a class="link link--muted link--hover-underline link--external inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4" href="mailto:support@omnirogue.com" rel="noopener noreferrer" target="_blank">support@omnirogue.com</a></span><span class="typography typography--caption text-xs leading-normal text-(--text-muted) flex items-center gap-1">Made with <span class="text-accent">❤</span> for creators worldwide</span></div></div></footer>
</div></div>
<script defer="" src="/lander11__plans-v3-299__omni/assets/static.js"></script>
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
