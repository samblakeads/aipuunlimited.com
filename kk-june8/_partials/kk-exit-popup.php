<?php
/**
 * KK-June8 — Shared exit-intent popup
 *
 * Expects these PHP locals to be in scope when this file is included:
 *   $kk_exit_monthly_href   — where the $14.99/mo "Nano Banana" CTA should go
 *   $kk_exit_lifetime_href  — where the $399 lifetime "unlimited 90 days" CTA should go
 *
 * Both pages and checkouts include this exactly the same way; the only
 * difference is what hrefs the host page passes in.
 */
$kk_exit_monthly_href  = isset($kk_exit_monthly_href)  ? $kk_exit_monthly_href  : '#';
$kk_exit_lifetime_href = isset($kk_exit_lifetime_href) ? $kk_exit_lifetime_href : '#';
?>
<style id="kk-exit-popup-styles">
.kk-exit-scrim {
  position: fixed; inset: 0; z-index: 9999;
  background: radial-gradient(ellipse at center, rgba(20,8,40,0.78), rgba(2,3,12,0.92));
  -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
  opacity: 0; visibility: hidden;
  transition: opacity .25s ease, visibility .25s;
  font-family: 'Hanken Grotesk','Inter',system-ui,-apple-system,Segoe UI,sans-serif;
}
.kk-exit-scrim[data-open="true"] { opacity: 1; visibility: visible; }
.kk-exit-card {
  position: relative;
  width: min(720px, 100%);
  max-height: 92vh; overflow-y: auto;
  border-radius: 24px;
  padding: 2px;
  background: conic-gradient(from 200deg at 50% 50%,
    #FFF8E7 0deg, #E6C97A 70deg, #8B5CFF 160deg,
    #6366F1 250deg, #E6C97A 320deg, #FFF8E7 360deg);
  box-shadow:
    0 0 60px -18px rgba(230,201,122,0.55),
    0 0 120px -40px rgba(139,92,255,0.65),
    0 60px 120px -30px rgba(0,0,0,0.92);
  transform: translateY(14px) scale(.98);
  transition: transform .28s cubic-bezier(.2,.7,.2,1);
}
.kk-exit-scrim[data-open="true"] .kk-exit-card { transform: translateY(0) scale(1); }
.kk-exit-inner {
  position: relative; overflow: hidden;
  border-radius: 22px;
  background:
    radial-gradient(circle at 12% 0%,  rgba(230,201,122,0.18), transparent 40%),
    radial-gradient(circle at 88% 18%, rgba(139,92,255,0.30),  transparent 45%),
    radial-gradient(circle at 50% 120%, rgba(99,102,241,0.20),  transparent 50%),
    linear-gradient(160deg, #08060F 0%, #0E0A22 50%, #08060F 100%);
  padding: 32px 28px 28px;
  color: #F4F6FF;
}
.kk-exit-close {
  position: absolute; top: 12px; right: 12px;
  width: 36px; height: 36px; border-radius: 10px;
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10);
  color: rgba(244,246,255,0.75);
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .15s ease, color .15s ease;
  z-index: 3;
}
.kk-exit-close:hover { background: rgba(255,255,255,0.12); color: #fff; }
.kk-exit-close svg { width: 18px; height: 18px; }
.kk-exit-eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 6px 12px; border-radius: 999px;
  background: rgba(255, 75, 75, 0.14);
  border: 1px solid rgba(255, 75, 75, 0.35);
  color: #FF9494;
  font-size: 11px; font-weight: 800;
  letter-spacing: 0.18em; text-transform: uppercase;
}
.kk-exit-eyebrow .pulse {
  width: 8px; height: 8px; border-radius: 999px;
  background: #FF4F4F;
  box-shadow: 0 0 0 4px rgba(255,79,79,0.25);
  animation: kkExitPulse 1.2s ease-in-out infinite;
}
@keyframes kkExitPulse {
  0%, 100% { box-shadow: 0 0 0 4px rgba(255,79,79,0.25); }
  50%      { box-shadow: 0 0 0 9px rgba(255,79,79,0); }
}
.kk-exit-title {
  margin: 14px 0 10px;
  font-family: 'Plus Jakarta Sans','Hanken Grotesk',system-ui,sans-serif;
  font-size: clamp(24px, 4.5vw, 34px);
  font-weight: 900; letter-spacing: -0.02em; line-height: 1.1;
  color: #fff;
}
.kk-exit-title .grad-gold {
  background: linear-gradient(115deg, #FFF8E7 0%, #F5E1A4 28%, #E6C97A 52%, #D4A647 76%, #FFF8E7 100%);
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.kk-exit-title .grad-purple {
  background: linear-gradient(110deg, #B79CFF 0%, #8B5CFF 50%, #6366F1 100%);
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.kk-exit-lead {
  margin: 0 0 22px;
  font-size: 14.5px; line-height: 1.55;
  color: rgba(244,246,255,0.72);
}
.kk-exit-grid {
  display: grid; gap: 14px;
  grid-template-columns: 1fr;
}
@media (min-width: 620px) {
  .kk-exit-grid { grid-template-columns: 1fr 1fr; }
}
.kk-exit-choice {
  position: relative; overflow: hidden;
  display: flex; flex-direction: column; gap: 10px;
  padding: 18px 18px 16px;
  border-radius: 16px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.10);
  color: inherit; text-decoration: none;
  transition: transform .18s ease, border-color .18s ease, background .18s ease;
}
.kk-exit-choice:hover { transform: translateY(-2px); border-color: rgba(255,255,255,0.22); background: rgba(255,255,255,0.05); }
.kk-exit-choice .pill {
  align-self: flex-start;
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 999px;
  font-size: 10.5px; font-weight: 800;
  letter-spacing: 0.16em; text-transform: uppercase;
}
.kk-exit-choice.kk-monthly { border-color: rgba(139,92,255,0.40); }
.kk-exit-choice.kk-monthly .pill {
  background: rgba(139,92,255,0.16); color: #C7B6FF;
  border: 1px solid rgba(139,92,255,0.40);
}
.kk-exit-choice.kk-lifetime { border-color: rgba(230,201,122,0.50); }
.kk-exit-choice.kk-lifetime .pill {
  background: rgba(230,201,122,0.16); color: #F5E1A4;
  border: 1px solid rgba(230,201,122,0.40);
}
.kk-exit-choice h4 {
  margin: 4px 0 0;
  font-size: 16px; font-weight: 800; line-height: 1.25;
  color: #fff;
}
.kk-exit-choice p {
  margin: 0;
  font-size: 13px; line-height: 1.5;
  color: rgba(244,246,255,0.65);
}
.kk-exit-price {
  display: flex; align-items: baseline; gap: 8px;
  margin-top: 6px;
}
.kk-exit-price .num {
  font-family: 'Plus Jakarta Sans','Hanken Grotesk',system-ui,sans-serif;
  font-size: 30px; font-weight: 900; letter-spacing: -0.025em;
  color: #fff;
}
.kk-exit-price .meta {
  font-size: 11px; font-weight: 600;
  color: rgba(244,246,255,0.55);
  text-transform: uppercase; letter-spacing: 0.10em;
}
.kk-exit-cta {
  margin-top: 6px;
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  min-height: 48px; padding: 12px 16px;
  border-radius: 12px;
  font-size: 12.5px; font-weight: 800; letter-spacing: 0.03em;
  color: #fff; border: 0; cursor: pointer; font-family: inherit;
  text-decoration: none;
  text-align: center; line-height: 1.2;
}
.kk-exit-cta svg { flex: 0 0 14px; width: 14px; height: 14px; }
.kk-monthly .kk-exit-cta {
  background: linear-gradient(110deg, #8B5CFF 0%, #6366F1 100%);
  box-shadow: 0 10px 26px -8px rgba(139,92,255,0.55);
}
.kk-lifetime .kk-exit-cta {
  color: #1A1208;
  background: linear-gradient(110deg, #FFF8E7 0%, #F5E1A4 32%, #E6C97A 60%, #D4A647 88%, #FFF8E7 100%);
  box-shadow: 0 10px 26px -8px rgba(230,201,122,0.65);
}
.kk-exit-foot {
  margin-top: 18px;
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; flex-wrap: wrap;
  padding-top: 14px;
  border-top: 1px solid rgba(255,255,255,0.08);
  font-size: 12px; color: rgba(244,246,255,0.5);
}
.kk-exit-foot .trust { display: inline-flex; align-items: center; gap: 6px; }
.kk-exit-foot .trust svg { width: 12px; height: 12px; color: #34D399; }
.kk-exit-dismiss {
  background: none; border: 0; cursor: pointer;
  font-family: inherit; font-size: 12px;
  color: rgba(244,246,255,0.45);
  text-decoration: underline;
  transition: color .15s ease;
}
.kk-exit-dismiss:hover { color: rgba(244,246,255,0.85); }
</style>

<div class="kk-exit-scrim" data-kk-exit-popup role="dialog" aria-modal="true" aria-labelledby="kk-exit-title">
  <div class="kk-exit-card">
    <div class="kk-exit-inner">
      <button type="button" class="kk-exit-close" data-kk-exit-close aria-label="Close">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      </button>

      <span class="kk-exit-eyebrow"><span class="pulse"></span> Wait — don't leave empty-handed</span>

      <h2 class="kk-exit-title" id="kk-exit-title">
        Take one of these <span class="grad-gold">free bonuses</span><br>
        on the way out.
      </h2>

      <p class="kk-exit-lead">
        Two limited-time bonuses for new OmniRogue creators. Pick the one that fits — both unlock the second you finish checkout.
      </p>

      <div class="kk-exit-grid">

        <a class="kk-exit-choice kk-monthly" data-kk-exit-monthly href="<?= htmlspecialchars($kk_exit_monthly_href, ENT_QUOTES, 'UTF-8'); ?>">
          <span class="pill">Bonus #1 · Free</span>
          <h4>500 free Nano Banana generations</h4>
          <p>Sign up for OmniRogue Creator at <strong style="color:#fff">$14.99/mo</strong> and we'll instantly credit your account with 500 free Nano Banana image generations. Cancel anytime.</p>
          <div class="kk-exit-price">
            <span class="num">$14.99</span>
            <span class="meta">/ month · cancel anytime</span>
          </div>
          <span class="kk-exit-cta">
            Claim 500 Free Nano Banana Gens
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </span>
        </a>

        <a class="kk-exit-choice kk-lifetime" data-kk-exit-lifetime href="<?= htmlspecialchars($kk_exit_lifetime_href, ENT_QUOTES, 'UTF-8'); ?>">
          <span class="pill">Bonus #2 · Best value</span>
          <h4>90 days unlimited video &amp; image</h4>
          <p>Lock in <strong style="color:#fff">$399 lifetime</strong> and we'll unlock <strong style="color:#fff">90 days of unlimited video and image generation</strong> on top. Pay once, keep building forever.</p>
          <div class="kk-exit-price">
            <span class="num">$399</span>
            <span class="meta">one-time · lifetime access</span>
          </div>
          <span class="kk-exit-cta">
            Unlock Lifetime + 90 Days Unlimited
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </span>
        </a>

      </div>

      <div class="kk-exit-foot">
        <span class="trust">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
          30-day money-back guarantee on every plan
        </span>
        <button type="button" class="kk-exit-dismiss" data-kk-exit-close>No thanks, I'll pass on the bonuses</button>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  if (window.__kkExitPopupBound) return;
  window.__kkExitPopupBound = true;

  var scrim = document.querySelector('[data-kk-exit-popup]');
  if (!scrim) return;

  var STORAGE_KEY  = 'kkExitPopup_shown_v1';
  var SESSION_KEY  = 'kkExitPopup_session_v1';
  var armedAt = Date.now();
  var ARM_DELAY_MS = 1200;
  var alreadyOpened = false;

  function hasShown() {
    try {
      if (sessionStorage.getItem(SESSION_KEY) === '1') return true;
      var ts = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
      return ts && (Date.now() - ts) < (3 * 24 * 60 * 60 * 1000);
    } catch (e) { return false; }
  }
  function markShown() {
    try {
      sessionStorage.setItem(SESSION_KEY, '1');
      localStorage.setItem(STORAGE_KEY, String(Date.now()));
    } catch (e) {}
  }

  function open() {
    if (alreadyOpened) return;
    if (hasShown()) return;
    if (Date.now() - armedAt < ARM_DELAY_MS) return;
    alreadyOpened = true;
    markShown();
    scrim.setAttribute('data-open', 'true');
    document.documentElement.style.overflow = 'hidden';
  }

  function close() {
    scrim.removeAttribute('data-open');
    document.documentElement.style.overflow = '';
  }

  document.addEventListener('mouseout', function (e) {
    if (e.relatedTarget || e.toElement) return;
    if (e.clientY <= 0) open();
  });

  var lastTouchY = 0;
  document.addEventListener('touchstart', function (e) {
    if (e.touches && e.touches.length) lastTouchY = e.touches[0].clientY;
  }, { passive: true });
  document.addEventListener('touchmove', function (e) {
    if (!e.touches || !e.touches.length) return;
    var y = e.touches[0].clientY;
    if (window.scrollY <= 0 && (y - lastTouchY) > 80) open();
    lastTouchY = y;
  }, { passive: true });

  var historyPushed = false;
  function armBack() {
    if (historyPushed) return;
    try {
      history.pushState({ kkExit: 1 }, '');
      historyPushed = true;
    } catch (e) {}
  }
  setTimeout(armBack, 800);
  window.addEventListener('popstate', function () { open(); armBack(); });

  scrim.addEventListener('click', function (e) {
    if (e.target === scrim) close();
    if (e.target.closest('[data-kk-exit-close]')) close();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && scrim.getAttribute('data-open') === 'true') close();
  });
})();
</script>
