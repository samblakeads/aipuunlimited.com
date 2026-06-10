// OmniRogue static pages — shared interactions
(function () {
  'use strict';

  var navMap = {
    'AI Video': '/omnirogue-newpages/createvideo.html',
    'Image': '/omnirogue-newpages/create-image.html',
    'Audio': '/omnirogue-newpages/create-audio.html',
    'Music': '/omnirogue-newpages/create-music.html',
    'Upscale': '/omnirogue-newpages/create-upscale.html',
    'OmniReels': '/omnirogue-newpages/create-omnireels.html',
    'Podcast': '/omnirogue-newpages/create-podcast.html',
    'AI Chat': '/omnirogue-newpages/create-ai-chat.html',
    'Voice Agents': '/omnirogue-newpages/create-voice-agents.html'
  };

  // Sidebar buttons not converted to links
  document.querySelectorAll('aside button, nav button').forEach(function (btn) {
    var label = btn.querySelector('.flex-1.truncate, .truncate');
    if (!label) return;
    var text = label.textContent.trim();
    if (navMap[text]) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        window.location.href = navMap[text];
      });
      btn.style.cursor = 'pointer';
    }
  });

  // Video discover — play on hover
  document.querySelectorAll('[data-lazy-video] video').forEach(function (v) {
    var card = v.closest('button') || v.closest('a') || v.parentElement;
    if (!card) return;
    card.addEventListener('mouseenter', function () { v.play().catch(function () {}); });
    card.addEventListener('mouseleave', function () { v.pause(); v.currentTime = 0; });
  });

  var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      var v = e.target.querySelector('video');
      if (v && v.querySelector('source[src]')) v.load();
    });
  }, { rootMargin: '200px' }) : null;
  if (io) document.querySelectorAll('[data-lazy-video]').forEach(function (el) { io.observe(el); });

  // Audio discover — play/pause on play button click
  var currentAudio = null;
  document.querySelectorAll('audio[src]').forEach(function (audio) {
    var card = audio.closest('button[data-keep-dark]') || audio.closest('[data-keep-dark]');
    if (!card) return;
    // Prevent outer card buttons from navigating/submitting
    card.addEventListener('click', function (e) {
      if (e.target.closest('button svg.lucide-play, button .lucide-play')) return;
      e.preventDefault();
      e.stopPropagation();
    });
    var playBtns = card.querySelectorAll('button');
    playBtns.forEach(function (btn) {
      if (!btn.querySelector('.lucide-play, svg.lucide-play')) return;
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        if (currentAudio && currentAudio !== audio) {
          currentAudio.pause();
          currentAudio.currentTime = 0;
        }
        if (audio.paused) {
          audio.play().catch(function () {});
          currentAudio = audio;
        } else {
          audio.pause();
          audio.currentTime = 0;
          currentAudio = null;
        }
      });
    });
  });

  // Mobile sidebar — close overlay on load for static pages
  var overlay = document.querySelector('.fixed.inset-0.z-30.bg-black\\/50');
  var aside = document.querySelector('aside.fixed.inset-0.z-50');
  if (window.matchMedia('(min-width: 768px)').matches) {
    if (overlay) overlay.style.display = 'none';
    if (aside) {
      aside.classList.remove('fixed', 'inset-0', 'z-50', 'w-screen', 'h-dvh');
    }
  }

  // Create studio — prompt + Generate button flow
  (function initGenerateFlow() {
    var base = window.location.pathname.indexOf('/omnirogue-newpages/') >= 0
      ? '/omnirogue-newpages' : '/omnirogue-pages';
    var checkoutUrl = base + '/checkout.html';

    var textarea = document.querySelector('textarea[placeholder*="Describe"]') ||
      document.querySelector('textarea[maxlength="2000"]');
    if (!textarea) return;

    var generateBtns = Array.prototype.filter.call(
      document.querySelectorAll('button[data-keep-dark]'),
      function (btn) {
        var span = btn.querySelector('span');
        return span && /^Generate$/i.test(span.textContent.trim());
      }
    );
    if (!generateBtns.length) return;

    var inactiveClasses = ['bg-muted/30', 'text-muted-foreground/40', 'cursor-not-allowed'];
    var activeClasses = [
      'bg-linear-to-r', 'from-primary', 'to-primary-glow',
      'shadow-[0_4px_24px_rgba(139,92,255,0.35)]',
      'hover:shadow-[0_8px_32px_rgba(139,92,255,0.5)]',
      'hover:-translate-y-0.5', 'cursor-pointer'
    ];

    function toggleClass(el, list, on) {
      list.forEach(function (c) { el.classList[on ? 'add' : 'remove'](c); });
    }

    function setGenerateEnabled(enabled) {
      generateBtns.forEach(function (btn) {
        if (enabled) {
          btn.disabled = false;
          btn.removeAttribute('disabled');
          toggleClass(btn, inactiveClasses, false);
          toggleClass(btn, activeClasses, true);
          btn.title = 'Generate';
          btn.setAttribute('aria-label', 'Generate');
        } else {
          btn.disabled = true;
          toggleClass(btn, activeClasses, false);
          toggleClass(btn, inactiveClasses, true);
          btn.title = 'Add a short description to enable Generate.';
          btn.setAttribute('aria-label', 'Generate — Add a short description to enable Generate.');
        }
      });
    }

    function syncGenerateState() {
      setGenerateEnabled(textarea.value.trim().length > 0);
    }

    textarea.addEventListener('input', syncGenerateState);
    syncGenerateState();

    if (!document.getElementById('omni-gen-styles')) {
      var style = document.createElement('style');
      style.id = 'omni-gen-styles';
      style.textContent = [
        '#omni-gen-overlay{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:1.5rem;background:rgba(5,5,12,.75);backdrop-filter:blur(8px);animation:omniGenFadeIn .25s ease}',
        '#omni-gen-card{max-width:28rem;width:100%;padding:2rem 1.75rem;border-radius:1.25rem;border:1px solid rgba(139,92,255,.25);background:linear-gradient(145deg,rgba(30,20,55,.95),rgba(12,10,24,.98));box-shadow:0 24px 64px rgba(0,0,0,.55),0 0 40px rgba(139,92,255,.15);text-align:center}',
        '#omni-gen-status{font-size:1.125rem;font-weight:600;color:#f4f4f5;margin:0;line-height:1.5}',
        '#omni-gen-claim{display:none;margin-top:1.25rem;font-size:1rem;font-weight:600;color:#c4b5fd;cursor:pointer;text-decoration:underline;text-underline-offset:4px}',
        '#omni-gen-claim:hover{color:#e9d5ff}',
        '.omni-gen-spinner{width:2.5rem;height:2.5rem;margin:0 auto 1rem;border:3px solid rgba(139,92,255,.2);border-top-color:#8b5cf6;border-radius:50%;animation:omniGenSpin .8s linear infinite}',
        '@keyframes omniGenSpin{to{transform:rotate(360deg)}}',
        '@keyframes omniGenFadeIn{from{opacity:0}to{opacity:1}}'
      ].join('');
      document.head.appendChild(style);
    }

    var modal = null;
    function ensureModal() {
      if (modal) return modal;
      modal = document.createElement('div');
      modal.id = 'omni-gen-overlay';
      modal.innerHTML = '<div id="omni-gen-card"><div class="omni-gen-spinner" id="omni-gen-spinner"></div><p id="omni-gen-status">Processing...</p><button type="button" id="omni-gen-claim">Click here to claim unlimited generations this week only!</button></div>';
      document.body.appendChild(modal);
      modal.querySelector('#omni-gen-claim').addEventListener('click', function () {
        window.location.href = checkoutUrl;
      });
      return modal;
    }

    var processing = false;
    function runGenerationFlow() {
      if (processing || textarea.value.trim().length === 0) return;
      processing = true;
      generateBtns.forEach(function (btn) { btn.disabled = true; });

      var m = ensureModal();
      var status = m.querySelector('#omni-gen-status');
      var spinner = m.querySelector('#omni-gen-spinner');
      var claim = m.querySelector('#omni-gen-claim');
      spinner.style.display = 'block';
      claim.style.display = 'none';
      status.textContent = 'Processing...';
      m.style.display = 'flex';

      setTimeout(function () {
        spinner.style.display = 'none';
        status.textContent = 'Your generation is ready!';
        claim.style.display = 'inline-block';
        processing = false;
        syncGenerateState();
      }, 3000);
    }

    generateBtns.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        if (btn.disabled || textarea.value.trim().length === 0) return;
        e.preventDefault();
        e.stopPropagation();
        runGenerationFlow();
      });
    });
  })();
})();

/* === OMNI MOBILE NAV v20260609 === */
/* Full-screen mobile menu matching omnirogue.com. Self-contained: resolves every
   link from the page's own desktop nav at runtime (so flow/KK rewiring — Pricing,
   Create Account, Login, step params, register gates — carries over automatically).
   Canonical source: scripts/mobile_nav_block.js — synced into every static.js by
   scripts/sync_mobile_nav.py. Edit the source, then re-run the sync. */
(function () {
  'use strict';

  // Studio pages (createvideo, create-image, …) were snapshotted with their
  // sidebar drawer OPEN: on mobile the <aside> is `fixed inset-0 z-50 w-screen`
  // plus a full-screen blur backdrop, and the original React close button is
  // dead — the page loads buried under an unclosable menu. Hide both on
  // phones (<768px); the injected mobile nav (below) covers navigation. The
  // md: sidebar layout on tablet/desktop is untouched.
  function fixStudioDrawer() {
    if (document.getElementById('omni-drawer-css')) return;
    var closeBtn = document.querySelector('aside button[aria-label="Close studios"]');
    var aside = closeBtn ? closeBtn.closest && closeBtn.closest('aside') : null;
    if (!aside) {
      var asides = document.getElementsByTagName('aside');
      for (var i = 0; i < asides.length; i++) {
        var cls = asides[i].className || '';
        if (/\bfixed\b/.test(cls) && /\binset-0\b/.test(cls) && /\bz-50\b/.test(cls)) { aside = asides[i]; break; }
      }
    }
    if (!aside) return;
    var style = document.createElement('style');
    style.id = 'omni-drawer-css';
    style.textContent = '@media (max-width:767px){.omni-drawer-hidden{display:none !important}}';
    document.head.appendChild(style);
    aside.classList.add('omni-drawer-hidden');
    // The sibling backdrop (fixed full-screen blur, md:hidden) blocks every tap.
    var sibs = aside.parentElement ? aside.parentElement.children : [];
    for (var s = 0; s < sibs.length; s++) {
      var sc = sibs[s].className || '';
      if (sibs[s] !== aside && /\bfixed\b/.test(sc) && /\binset-0\b/.test(sc) && /(bg-black|backdrop-blur)/.test(sc)) {
        sibs[s].classList.add('omni-drawer-hidden');
      }
    }
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        aside.classList.add('omni-drawer-hidden');
      });
    }
  }

  function initOmniMobileNav() {
    try { fixStudioDrawer(); } catch (e) {}

    // Version gate: minified copies of older blocks may coexist in a built
    // flow's static.js (comments/markers stripped). The newest block wins —
    // it tears down any menu a stale block already built.
    var V = 20260609.4;
    if (window.__OMNI_MNAV_V && window.__OMNI_MNAV_V >= V) return;

    var triggers = Array.prototype.slice.call(document.querySelectorAll('button[aria-label="Open menu"]'));
    if (!triggers.length) return;
    window.__OMNI_MNAV_V = V;

    var stale = document.getElementById('omni-mnav');
    if (stale && stale.parentNode) stale.parentNode.removeChild(stale);
    var staleCss = document.getElementById('omni-mnav-styles');
    if (staleCss && staleCss.parentNode) staleCss.parentNode.removeChild(staleCss);

    // Page extension: prefer the build-time bake (__OMNI_PAGE_EXT, set by
    // patch_static_js — 'php' in KK packages, 'html' on flows), then the
    // current URL. A KK page served as "/pkg/" (no filename) must still
    // resolve .php siblings, which only the bake gets right.
    var ext = window.__OMNI_PAGE_EXT
      || (/\.php(?:\?|$)/i.test(window.location.pathname) ? 'php'
        : (/\.html?(?:\?|$)/i.test(window.location.pathname) ? 'html' : ''));
    if (!ext) ext = document.querySelector('a[href*=".php"]') ? 'php' : 'html';
    var step = window.__KK_STEP1LINK || '';

    function basePath() {
      if (window.__LANDER_BASE) return window.__LANDER_BASE;
      var scripts = document.getElementsByTagName('script');
      for (var i = scripts.length - 1; i >= 0; i--) {
        var src = scripts[i].getAttribute('src') || '';
        var m = src.match(/^(.*)\/assets\/static\.js/i);
        if (m) return m[1];
      }
      var pm = window.location.pathname.match(/^(.*)\/[^/]+\.(html|php)$/i);
      return pm ? pm[1] : '';
    }
    var base = basePath();

    var navLogoImg = document.querySelector('nav a img, header a img');
    var logoSrc = navLogoImg ? (navLogoImg.getAttribute('src') || '') : '';
    var isAipu = /aipu/i.test(logoSrc) || /aipu/i.test(base);
    var brandName = isAipu ? 'AIPU' : 'OmniRogue';
    var loginFallback = isAipu ? 'https://app.aiprofessionalsuniversity.com/login' : 'https://omnirogue.com/login';

    var anchors = Array.prototype.slice.call(document.querySelectorAll('a[href]'));
    var navAnchors = Array.prototype.slice.call(document.querySelectorAll('nav a[href]'));

    function byLabel(label) {
      var scopes = [navAnchors, anchors];
      for (var s = 0; s < scopes.length; s++) {
        for (var i = 0; i < scopes[s].length; i++) {
          var t = (scopes[s][i].textContent || '').replace(/\s+/g, ' ').trim();
          if (t === label) return scopes[s][i].getAttribute('href');
        }
      }
      return null;
    }
    function bySlug(slug) {
      var re = new RegExp('/' + slug + '\\.(html|php)([?#]|$)', 'i');
      for (var i = 0; i < anchors.length; i++) {
        var href = anchors[i].getAttribute('href') || '';
        if (re.test(href)) return href;
      }
      return null;
    }
    function pageUrl(slug) { return base + '/' + slug + '.' + ext + step; }

    // Create Studio target tells us whether the studio/site pages exist locally
    // (full flow / brand pack) or everything funnels to a conversion URL
    // (sales-only flow). Derive sibling page URLs from it so base, extension and
    // any tracking query carry over exactly.
    var studioHref = byLabel('Create Studio') || bySlug('createvideo');
    var studioMatch = studioHref ? studioHref.match(/^(.*)\/createvideo\.(html|php)(.*)$/i) : null;
    function siblingUrl(slug) {
      if (studioMatch) return studioMatch[1] + '/' + slug + '.' + studioMatch[2] + (studioMatch[3] || '');
      return studioHref || pageUrl(slug);
    }
    function resolve(label, slug) {
      return (label && byLabel(label)) || (slug && bySlug(slug)) || siblingUrl(slug);
    }

    var homeUrl = byLabel('Home') || pageUrl('index');
    var pricingUrl = byLabel('Pricing') || bySlug('checkout') || pageUrl('checkout');
    var createAccountUrl = byLabel('Create Account') || pricingUrl;
    var loginUrl = byLabel('Login') || loginFallback;

    if (!document.getElementById('omni-mnav-styles')) {
      var style = document.createElement('style');
      style.id = 'omni-mnav-styles';
      style.textContent = [
        '.omni-mnav{position:fixed;inset:0;z-index:9000;display:flex;flex-direction:column;background:#0a0a11;color:#fafafa;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .22s ease,visibility .22s}',
        '.omni-mnav.omni-mnav-open{opacity:1;visibility:visible;pointer-events:auto}',
        '.omni-mnav-top{display:flex;align-items:center;justify-content:space-between;flex-shrink:0;padding:env(safe-area-inset-top,0px) 1.1rem 0;height:calc(var(--nav-height,64px) + env(safe-area-inset-top,0px));border-bottom:1px solid rgba(255,255,255,.07)}',
        '.omni-mnav-logo{display:flex;align-items:center;text-decoration:none}',
        '.omni-mnav-logo img{height:36px;width:auto;object-fit:contain}',
        '.omni-mnav-close{display:flex;align-items:center;justify-content:center;padding:.5rem;margin-right:-.5rem;border-radius:.55rem;color:#fafafa;background:none;border:0;cursor:pointer;transition:background .15s ease}',
        '.omni-mnav-close:hover{background:rgba(255,255,255,.06)}',
        '.omni-mnav-close svg{width:1.4rem;height:1.4rem}',
        '.omni-mnav-body{flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;padding:1.35rem 1.25rem calc(2.2rem + env(safe-area-inset-bottom,0px));display:flex;flex-direction:column}',
        '.omni-mnav-label{font-size:.66rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;color:rgba(161,161,170,.75);padding:0 .5rem;margin:1.1rem 0 .4rem}',
        '.omni-mnav-label:first-child{margin-top:0}',
        '.omni-mnav-item{display:flex;align-items:center;gap:.8rem;width:100%;padding:.72rem .5rem;border:1px solid transparent;border-radius:1rem;font-size:1.02rem;font-weight:500;color:#fafafa;text-decoration:none;background:none;cursor:pointer;text-align:left;font-family:inherit;transition:background .15s ease}',
        '.omni-mnav-item:hover,.omni-mnav-item:active{background:rgba(255,255,255,.05)}',
        '.omni-mnav-item--active{background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.07);padding-left:1rem;padding-right:1rem}',
        '.omni-mnav-ic{width:1.15rem;height:1.15rem;flex-shrink:0;color:rgba(228,228,235,.85)}',
        '.omni-mnav-ic--accent{color:#a78bfa}',
        '.omni-mnav-chev{margin-left:auto;width:1.05rem;height:1.05rem;color:#a1a1aa;transition:transform .2s ease}',
        '.omni-mnav-item[aria-expanded="true"] .omni-mnav-chev{transform:rotate(180deg)}',
        '.omni-mnav-sub{display:none;margin:.1rem 0 .45rem .95rem;border-left:1px solid rgba(255,255,255,.09);padding-left:.85rem}',
        '.omni-mnav-sub.omni-mnav-sub-open{display:block}',
        '.omni-mnav-group{display:flex;align-items:center;gap:.5rem;font-size:.63rem;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:rgba(161,161,170,.85);padding:.85rem .5rem .3rem}',
        '.omni-mnav-group svg{width:.85rem;height:.85rem;flex-shrink:0}',
        '.omni-mnav-badge{margin-left:auto;font-size:.56rem;font-weight:700;letter-spacing:.1em;color:#c4b5fd;background:rgba(139,92,255,.16);border:1px solid rgba(139,92,255,.25);padding:.18rem .5rem;border-radius:.4rem;text-transform:uppercase}',
        '.omni-mnav-cta{margin-top:1.7rem;display:flex;flex-direction:column;gap:.85rem}',
        '.omni-mnav-btn{display:flex;align-items:center;justify-content:center;gap:.55rem;width:100%;padding:.95rem 1rem;border-radius:9999px;font-size:1rem;font-weight:600;text-decoration:none;color:#fff;transition:filter .15s ease,transform .15s ease}',
        '.omni-mnav-btn svg{width:1.05rem;height:1.05rem}',
        '.omni-mnav-btn:active{transform:scale(.985)}',
        '.omni-mnav-btn--primary{background:linear-gradient(90deg,#b1a0f8 0%,#8f6cf6 100%);box-shadow:0 6px 28px rgba(139,92,255,.35)}',
        '.omni-mnav-btn--secondary{background:#8b5cf6}',
        '@media (min-width:1280px){.omni-mnav{display:none}}'
      ].join('');
      document.head.appendChild(style);
    }

    function ic(parts, cls) {
      return '<svg class="' + (cls || 'omni-mnav-ic') + '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + parts + '</svg>';
    }

    var I = {
      sparkles: '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>',
      video: '<path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/>',
      image: '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
      headphones: '<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm0 0a9 9 0 0 1 18 0m0 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z"/>',
      music: '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
      wand: '<path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"/><path d="m14 7 3 3"/><path d="M5 6v4"/><path d="M19 14v4"/><path d="M10 2v2"/><path d="M7 8H3"/><path d="M21 16h-4"/><path d="M11 3H9"/>',
      film: '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/>',
      mic: '<path d="M12 19v3"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><rect x="9" y="2" width="6" height="13" rx="3"/>',
      message: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
      flask: '<path d="M10 2v7.527a2 2 0 0 1-.211.896L4.72 20.55a1 1 0 0 0 .9 1.45h12.76a1 1 0 0 0 .9-1.45l-5.069-10.127A2 2 0 0 1 14 9.527V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/>',
      library: '<path d="m16 6 4 14"/><path d="M12 6v14"/><path d="M8 8v12"/><path d="M4 4v16"/>',
      bot: '<path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/>',
      book: '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
      users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
      tag: '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
      dollar: '<circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/>',
      help: '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
      crown: '<path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/>',
      login: '<path d="m10 17 5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>',
      x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
      chevron: '<path d="m6 9 6 6 6-6"/>'
    };

    // current page (home and index are the same destination)
    function fileOf(href) {
      var m = String(href || '').match(/\/([^\/?#]+)\.(html|php)([?#]|$)/i);
      return m ? m[1].toLowerCase() : '';
    }
    var cur = fileOf(window.location.pathname) || 'index';
    if (cur === 'home') cur = 'index';
    function isActive(href) {
      var f = fileOf(href);
      if (f === 'home') f = 'index';
      return f !== '' && f === cur;
    }

    function navItem(label, href, iconKey, badge, accent) {
      return '<a class="omni-mnav-item' + (isActive(href) ? ' omni-mnav-item--active' : '') + '" href="' + href + '">' +
        (iconKey ? ic(I[iconKey], 'omni-mnav-ic' + (accent ? ' omni-mnav-ic--accent' : '')) : '') +
        '<span>' + label + '</span>' +
        (badge ? '<span class="omni-mnav-badge">New</span>' : '') +
        '</a>';
    }

    // AIPU's live menu (app.aiprofessionalsuniversity.com) adds "Long Video"
    // after AI Video; no dedicated page exists in our packs, so it shares the
    // AI Video destination.
    var aiVideoHref = resolve('AI Video', 'createvideo');
    var longVideoHref = byLabel('Long Video') || bySlug('create-long-video') || aiVideoHref;

    var studioSub =
      '<div class="omni-mnav-group">' + ic(I.sparkles, '') + '<span>AI Create</span></div>' +
      navItem('AI Video', aiVideoHref, 'video', true) +
      (isAipu ? navItem('Long Video', longVideoHref, 'film', true) : '') +
      navItem('Image', resolve('Image', 'create-image'), 'image') +
      navItem('Audio', resolve('Audio', 'create-audio'), 'headphones') +
      navItem('Music', resolve('Music', 'create-music'), 'music', true) +
      '<div class="omni-mnav-group">' + ic(I.wand, '') + '<span>Omni Create</span></div>' +
      navItem('OmniReels', byLabel('OmniReels') || byLabel('AIPU Reels') || bySlug('create-omnireels') || siblingUrl('create-omnireels'), 'film', true) +
      navItem('Podcast', resolve('Podcast', 'create-podcast'), 'mic') +
      '<div class="omni-mnav-group">' + ic(I.message, '') + '<span>AI Chat</span></div>' +
      navItem('AI Chat', resolve('AI Chat', 'create-ai-chat'), 'message', true) +
      '<div class="omni-mnav-group">' + ic(I.flask, '') + '<span>AI Tools</span></div>' +
      navItem('Voice Agents', resolve('Voice Agents', 'create-voice-agents'), 'mic') +
      navItem('Knowledge Base', resolve('Knowledge Base', 'knowledge-base'), 'library');

    var menu = document.createElement('div');
    menu.id = 'omni-mnav';
    menu.className = 'omni-mnav';
    menu.setAttribute('role', 'dialog');
    menu.setAttribute('aria-modal', 'true');
    menu.setAttribute('aria-label', 'Navigation menu');
    // Inline baseline: closed and inert even if the injected stylesheet is
    // stripped by a host/optimizer — the menu can never cover the page or
    // swallow taps unless setOpen(true) runs.
    menu.style.opacity = '0';
    menu.style.visibility = 'hidden';
    menu.style.pointerEvents = 'none';
    menu.innerHTML =
      '<div class="omni-mnav-top">' +
        '<a class="omni-mnav-logo" href="' + homeUrl + '"></a>' +
        '<button class="omni-mnav-close" type="button" aria-label="Close menu">' + ic(I.x, '') + '</button>' +
      '</div>' +
      '<div class="omni-mnav-body">' +
        '<div class="omni-mnav-label">Navigate</div>' +
        // AIPU's live menu has no Home row; OmniRogue's does.
        (isAipu ? '' : navItem('Home', homeUrl, null)) +
        '<button class="omni-mnav-item" type="button" id="omni-mnav-studio" aria-expanded="true">' +
          ic(I.sparkles, 'omni-mnav-ic omni-mnav-ic--accent') + '<span>Create Studio</span>' + ic(I.chevron, 'omni-mnav-chev') +
        '</button>' +
        '<div class="omni-mnav-sub omni-mnav-sub-open" id="omni-mnav-studio-sub">' + studioSub + '</div>' +
        '<div class="omni-mnav-label">Library</div>' +
        navItem('GPT Library', resolve('GPT Library', 'gpt-library'), 'bot') +
        navItem('Prompt Library', resolve('Prompt Library', 'prompt-library'), 'book') +
        navItem('About Us', resolve('About Us', 'about'), 'users') +
        navItem('Pricing', pricingUrl, 'tag') +
        // Become Affiliate exists on omnirogue.com's menu only.
        (isAipu ? '' : navItem('Become Affiliate', resolve('Become Affiliate', 'affiliate'), 'dollar')) +
        '<div class="omni-mnav-cta">' +
          '<a class="omni-mnav-btn omni-mnav-btn--primary" href="' + createAccountUrl + '">' + ic(I.crown, '') + 'Create Account</a>' +
          '<a class="omni-mnav-btn omni-mnav-btn--secondary" href="' + loginUrl + '">' + ic(I.login, '') + 'Login</a>' +
        '</div>' +
      '</div>';
    document.body.appendChild(menu);

    // Reuse the real header logo so the brand + image path is always right
    var logoSlot = menu.querySelector('.omni-mnav-logo');
    if (navLogoImg) {
      logoSlot.appendChild(navLogoImg.cloneNode(true));
    } else {
      logoSlot.textContent = brandName;
      logoSlot.style.cssText += 'font-weight:800;font-size:1.2rem;color:#a78bfa';
    }

    var studioBtn = menu.querySelector('#omni-mnav-studio');
    var studioSubEl = menu.querySelector('#omni-mnav-studio-sub');
    studioBtn.addEventListener('click', function () {
      var open = studioSubEl.classList.toggle('omni-mnav-sub-open');
      studioBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    var prevOverflow = '';
    function setOpen(open) {
      menu.classList.toggle('omni-mnav-open', open);
      // Mirror the class state with inline styles so open/close works even if
      // the injected stylesheet was stripped.
      menu.style.opacity = open ? '1' : '0';
      menu.style.visibility = open ? 'visible' : 'hidden';
      menu.style.pointerEvents = open ? 'auto' : 'none';
      triggers.forEach(function (btn) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('data-state', open ? 'open' : 'closed');
      });
      if (open) {
        prevOverflow = document.documentElement.style.overflow;
        document.documentElement.style.overflow = 'hidden';
      } else {
        document.documentElement.style.overflow = prevOverflow;
      }
    }

    triggers.forEach(function (btn) {
      btn.setAttribute('aria-controls', 'omni-mnav');
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setOpen(!menu.classList.contains('omni-mnav-open'));
      });
    });
    menu.querySelector('.omni-mnav-close').addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && menu.classList.contains('omni-mnav-open')) setOpen(false);
    });
    // The static page snapshot can carry a stale data-state="open" on the
    // hamburger (captured mid-animation on the source site). Normalize to a
    // definitively-closed state at init so nothing reads it as "menu open".
    setOpen(false);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOmniMobileNav);
  } else {
    initOmniMobileNav();
  }
})();
/* === END OMNI MOBILE NAV === */
