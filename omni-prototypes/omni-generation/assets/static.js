// OmniRogue static pages — shared interactions
(function () {
  'use strict';var __OMNI_SOCIAL_DEFAULT='on';window.__OMNI_SOCIAL_DEFAULT=__OMNI_SOCIAL_DEFAULT;var __OMNI_PAGE_EXT='html';window.__OMNI_PAGE_EXT=__OMNI_PAGE_EXT;

  var navMap = {
    'AI Video': '/omni-prototypes/omni-generation/createvideo.html',
    'Image': '/omni-prototypes/omni-generation/create-image.html',
    'Audio': '/omni-prototypes/omni-generation/create-audio.html',
    'Music': '/omni-prototypes/omni-generation/create-music.html',
    'Upscale': '/omni-prototypes/omni-generation/create-upscale.html',
    'OmniReels': '/omni-prototypes/omni-generation/create-omnireels.html',
    'Podcast': '/omni-prototypes/omni-generation/create-podcast.html',
    'AI Chat': '/omni-prototypes/omni-generation/create-ai-chat.html',
    'Voice Agents': '/omni-prototypes/omni-generation/create-voice-agents.html'
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

  // Set per-lander at build time (multistep / KK copies). window.__LANDER_BASE is
  // also injected from PHP on KK pages as a runtime override.
  var __LANDER_BASE='/omni-prototypes/omni-generation';

  function omniPageExt() {
    return /\.php(?:\?|$)/i.test(window.location.pathname) ? 'php' : 'html';
  }

  function landerBaseFromScript() {
    var scripts = document.getElementsByTagName('script');
    for (var i = scripts.length - 1; i >= 0; i--) {
      var src = scripts[i].getAttribute('src') || '';
      var m = src.match(/^(.*)\/assets\/static\.js/i);
      if (m && m[1]) return m[1];
    }
    return '';
  }

  function landerBaseFromNavMap() {
    var keys = Object.keys(navMap);
    if (!keys.length) return '';
    var m = String(navMap[keys[0]]).match(/^(.*)\/[^/]+\.(html|php)$/i);
    return (m && m[1]) ? m[1] : '';
  }

  function omniBasePath() {
    if (window.__LANDER_BASE) return window.__LANDER_BASE;
    if (__LANDER_BASE) return __LANDER_BASE;
    var fromScript = landerBaseFromScript();
    if (fromScript) return fromScript;
    var fromNav = landerBaseFromNavMap();
    if (fromNav) return fromNav;
    var path = window.location.pathname;
    var m = path.match(/^(.*)\/[^/]+\.(html|php)$/i);
    if (m && m[1]) return m[1];
    if (path.indexOf('/omnirogue-newpages/') >= 0) return '/omnirogue-newpages';
    if (path.indexOf('/omni-prototypes/omni-generation/') >= 0) return '/omni-prototypes/omni-generation';
    if (path.indexOf('/aipu-pages/') >= 0) return '/aipu-pages';
    var trimmed = path.replace(/\/$/, '');
    if (trimmed) return trimmed;
    return fromNav || fromScript || '/omni-prototypes/omni-generation';
  }

  function omniHomeUrl() {
    return omniBasePath() + '/index.' + omniPageExt();
  }

  function omniCheckoutUrl() {
    if (window.__KK_CHECKOUT_URL) return window.__KK_CHECKOUT_URL;
    var base = omniBasePath();
    var url = base + '/checkout.' + omniPageExt();
    var step = window.__KK_STEP1LINK || '';
    if (step && url.indexOf(step) < 0) url += step;
    return url;
  }

  function omniRegisterCheckoutUrl() {
    if (window.__KK_REGISTER_CHECKOUT) return window.__KK_REGISTER_CHECKOUT;
    return '';
  }

  function omniLanderPageUrl(page) {
    var base = omniBasePath();
    var step = window.__KK_STEP1LINK || '';
    return base + '/' + page + '.' + omniPageExt() + step;
  }

  // Global nav targets: Home -> index, Pricing/Plans -> checkout.
  (function wireTopNav() {
    var HOME = omniHomeUrl();
    var CHECKOUT = omniCheckoutUrl();
    var base = omniBasePath();
    var ext = omniPageExt();
    document.querySelectorAll('a').forEach(function (a) {
      var label = a.textContent.replace(/\s+/g, ' ').trim();
      var href = a.getAttribute('href') || '';
      // Home link in the top nav
      if (label === 'Home') { a.setAttribute('href', HOME); }
      // Pricing / Plans links (top nav + footer + anything pointing at /plans)
      if (label === 'Pricing' || label === 'Plans' || /\/plans\/?$/.test(href)) {
        a.setAttribute('href', CHECKOUT);
      }
      if (label === 'Help') {
        a.setAttribute('href', base + '/help-center.' + ext);
      }
      if (label === 'Become Affiliate') {
        a.setAttribute('href', base + '/affiliate.' + ext);
      }
    });
  })();

  // Library dropdown — GPT Library + Prompt Library
  (function initLibraryDropdown() {
    var ext = omniPageExt();
    var base = omniBasePath();
    var gptUrl = base + '/gpt-library.' + ext;
    var promptUrl = base + '/prompt-library.' + ext;

    if (!document.getElementById('omni-library-styles')) {
      var style = document.createElement('style');
      style.id = 'omni-library-styles';
      style.textContent = [
        '.omni-library-wrap{position:relative}',
        '.omni-library-menu{position:absolute;top:calc(100% + 10px);left:50%;transform:translateX(-50%);min-width:17.5rem;z-index:200;padding:.45rem;border-radius:1rem;border:1px solid rgba(139,92,255,.22);background:linear-gradient(165deg,rgba(22,16,42,.98) 0%,rgba(10,8,20,.99) 100%);box-shadow:0 24px 60px rgba(0,0,0,.55),0 0 40px rgba(139,92,255,.1),inset 0 1px 0 rgba(255,255,255,.05);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .18s ease,transform .18s ease,visibility .18s}',
        '.omni-library-menu.omni-library-open{opacity:1;visibility:visible;pointer-events:auto;transform:translateX(-50%) translateY(0)}',
        '.omni-library-menu-header{display:flex;align-items:center;gap:.45rem;padding:.55rem .75rem .45rem;font-size:.62rem;font-weight:700;letter-spacing:.14em;color:rgba(161,161,170,.85);border-bottom:1px solid rgba(255,255,255,.06);margin-bottom:.25rem}',
        '.omni-library-menu-header svg{width:.95rem;height:.95rem;color:#a78bfa;flex-shrink:0}',
        '.omni-library-item{display:flex;align-items:flex-start;gap:.75rem;padding:.7rem .75rem;border-radius:.75rem;text-decoration:none;color:inherit;transition:background .15s ease}',
        '.omni-library-item:hover{background:rgba(139,92,255,.12)}',
        '.omni-library-icon{width:2.35rem;height:2.35rem;border-radius:.7rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:rgba(139,92,255,.14);border:1px solid rgba(139,92,255,.22)}',
        '.omni-library-icon svg{width:1.1rem;height:1.1rem;color:#c4b5fd}',
        '.omni-library-title{font-size:.9rem;font-weight:700;color:#fafafa;line-height:1.25;margin:0}',
        '.omni-library-desc{font-size:.72rem;color:rgba(161,161,170,.9);margin:.2rem 0 0;line-height:1.35}',
        'button[aria-controls="omni-library-menu"]{cursor:pointer}'
      ].join('');
      document.head.appendChild(style);
    }

    var triggers = Array.from(document.querySelectorAll('button[aria-haspopup="menu"]')).filter(function (btn) {
      return /^Library/.test(btn.textContent.replace(/\s+/g, ' ').trim());
    });
    if (!triggers.length || document.getElementById('omni-library-menu')) return;

    var menu = document.createElement('div');
    menu.id = 'omni-library-menu';
    menu.setAttribute('role', 'menu');
    menu.className = 'omni-library-menu';
    menu.innerHTML =
      '<div class="omni-library-menu-header">' +
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M8 11h8"/><path d="M8 7h6"/></svg>' +
        '<span>LIBRARY</span>' +
      '</div>' +
      '<a class="omni-library-item" role="menuitem" href="' + gptUrl + '">' +
        '<div class="omni-library-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg></div>' +
        '<div><p class="omni-library-title">GPT Library</p><p class="omni-library-desc">Ready-made GPTs &amp; AI assistants</p></div>' +
      '</a>' +
      '<a class="omni-library-item" role="menuitem" href="' + promptUrl + '">' +
        '<div class="omni-library-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg></div>' +
        '<div><p class="omni-library-title">Prompt Library</p><p class="omni-library-desc">1,000+ battle-tested prompts by category</p></div>' +
      '</a>';

    var wrap = triggers[0].parentElement;
    if (wrap) wrap.classList.add('omni-library-wrap');
    (wrap || document.body).appendChild(menu);

    function setOpen(open) {
      menu.classList.toggle('omni-library-open', open);
      triggers.forEach(function (btn) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('data-state', open ? 'open' : 'closed');
      });
    }

    triggers.forEach(function (btn) {
      btn.setAttribute('aria-controls', 'omni-library-menu');
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setOpen(!menu.classList.contains('omni-library-open'));
      });
    });

    document.addEventListener('click', function (e) {
      if (!menu.contains(e.target) && !triggers.some(function (btn) { return btn.contains(e.target); })) {
        setOpen(false);
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
  })();

  // Library pages — upgrade gate when browsing GPTs or prompts
  (function initLibraryUpgradeGate() {
    var path = window.location.pathname;
    var isGpt = path.indexOf('gpt-library') >= 0;
    var isPrompt = path.indexOf('prompt-library') >= 0;
    if (!isGpt && !isPrompt) return;

    var checkoutUrl = omniRegisterCheckoutUrl();
    var kind = isGpt ? 'gpt' : 'prompt';
    var noun = isGpt ? 'your GPTs' : 'your prompts';
    var icon = isGpt ? '🤖' : '📚';
    var label = isGpt ? 'GPT Library' : 'Prompt Library';

    if (!document.getElementById('omni-lib-upgrade-styles')) {
      var style = document.createElement('style');
      style.id = 'omni-lib-upgrade-styles';
      style.textContent = [
        '#omni-lib-upgrade-overlay{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:1.25rem;background:rgba(4,4,10,.82);backdrop-filter:blur(12px);animation:omniGenFadeIn .3s ease}',
        '#omni-lib-upgrade-card{position:relative;max-width:28rem;width:100%;padding:0;border-radius:1.5rem;border:1px solid rgba(139,92,255,.22);background:linear-gradient(160deg,rgba(28,18,52,.97) 0%,rgba(10,8,20,.99) 100%);box-shadow:0 32px 80px rgba(0,0,0,.65),0 0 60px rgba(139,92,255,.12),inset 0 1px 0 rgba(255,255,255,.06)}',
        '.omni-lib-upgrade-glow{position:absolute;inset:-1px;border-radius:1.5rem;background:linear-gradient(135deg,rgba(139,92,255,.35),rgba(56,189,248,.15),rgba(139,92,255,.2));opacity:.45;pointer-events:none}',
        '.omni-lib-upgrade-inner{position:relative;padding:1.6rem 1.6rem 1.4rem}',
        '.omni-lib-upgrade-badge{display:inline-flex;align-items:center;gap:.5rem;padding:.35rem .85rem;border-radius:999px;font-size:.7rem;font-weight:700;letter-spacing:.04em;color:#86efac;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.28);margin-bottom:1rem}',
        '.omni-lib-upgrade-head{display:flex;align-items:center;gap:1rem;margin-bottom:1rem}',
        '.omni-lib-upgrade-icon{width:3rem;height:3rem;border-radius:1rem;display:flex;align-items:center;justify-content:center;font-size:1.5rem;background:linear-gradient(135deg,rgba(139,92,255,.2),rgba(56,189,248,.1));border:1px solid rgba(255,255,255,.08);flex-shrink:0}',
        '#omni-lib-upgrade-title{font-size:1.15rem;font-weight:700;color:#fafafa;margin:0;line-height:1.35}',
        '#omni-lib-upgrade-sub{font-size:.85rem;color:rgba(212,212,216,.92);margin:.5rem 0 0;line-height:1.5}',
        '.omni-lib-upgrade-price{margin:1.15rem 0;padding:.85rem 1rem;border-radius:.85rem;background:rgba(139,92,255,.1);border:1px solid rgba(139,92,255,.2);font-size:.82rem;color:#e4e4e7;line-height:1.5}',
        '.omni-lib-upgrade-price strong{color:#c4b5fd;font-weight:700}',
        '#omni-lib-upgrade-cta{display:block;width:100%;padding:.95rem 1.25rem;border:none;border-radius:.9rem;font-size:.95rem;font-weight:700;color:#fff;cursor:pointer;background:linear-gradient(135deg,#8b5cf6,#6d28d9);box-shadow:0 8px 28px rgba(139,92,255,.4);transition:transform .15s,box-shadow .15s}',
        '#omni-lib-upgrade-cta:hover{transform:translateY(-2px);box-shadow:0 12px 36px rgba(139,92,255,.5)}',
        '.omni-lib-upgrade-foot{text-align:center;font-size:.72rem;color:rgba(161,161,170,.8);margin:.7rem 0 0}',
        '#omni-lib-upgrade-close{position:absolute;top:.85rem;right:.85rem;width:2rem;height:2rem;border:none;border-radius:.5rem;background:rgba(255,255,255,.06);color:#a1a1aa;cursor:pointer;font-size:1.1rem;line-height:1}',
        '#omni-lib-upgrade-close:hover{background:rgba(255,255,255,.12);color:#fff}'
      ].join('');
      document.head.appendChild(style);
    }

    var overlay = document.createElement('div');
    overlay.id = 'omni-lib-upgrade-overlay';
    overlay.innerHTML =
      '<div id="omni-lib-upgrade-card">' +
        '<div class="omni-lib-upgrade-glow"></div>' +
        '<button type="button" id="omni-lib-upgrade-close" aria-label="Close">&times;</button>' +
        '<div class="omni-lib-upgrade-inner">' +
          '<div class="omni-lib-upgrade-badge">✓ Text &amp; GPT are always unlimited</div>' +
          '<div class="omni-lib-upgrade-head">' +
            '<div class="omni-lib-upgrade-icon">' + icon + '</div>' +
            '<div>' +
              '<p id="omni-lib-upgrade-title">Upgrade to access ' + noun + '</p>' +
              '<p id="omni-lib-upgrade-sub">To access ' + noun + ', upgrade today!</p>' +
            '</div>' +
          '</div>' +
          '<p class="omni-lib-upgrade-price">Plans starting at <strong>$14.99</strong> — or unlock <strong>unlimited</strong> on our higher plans!</p>' +
          '<button type="button" id="omni-lib-upgrade-cta">Upgrade today</button>' +
          '<p class="omni-lib-upgrade-foot">' + label + ' &middot; cancel anytime</p>' +
        '</div>' +
      '</div>';
    document.body.appendChild(overlay);

    function showUpgradeModal() {
      overlay.style.display = 'flex';
    }
    function hideUpgradeModal() {
      overlay.style.display = 'none';
    }

    overlay.querySelector('#omni-lib-upgrade-cta').addEventListener('click', function () {
      window.location.href = checkoutUrl;
    });
    overlay.querySelector('#omni-lib-upgrade-close').addEventListener('click', hideUpgradeModal);
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) hideUpgradeModal();
    });

    var main = document.querySelector('main');
    if (!main) return;

    main.addEventListener('click', function (e) {
      var t = e.target.closest('button, a, [role="button"], [tabindex="0"]');
      if (!t || !main.contains(t)) return;
      e.preventDefault();
      e.stopPropagation();
      showUpgradeModal();
    }, true);
  })();

  // Knowledge Base page — tabs, public library list, and upgrade gate.
  (function initKnowledgeBase() {
    if (window.location.pathname.indexOf('knowledge-base') < 0) return;

    var checkoutUrl = omniRegisterCheckoutUrl();

    // ---- Public library data (realistic curated KBs) ----
    var LIBRARY = [
      { t: 'Company SOPs & Playbooks', d: 'Standard operating procedures, onboarding guides, and internal playbooks for fast-growing teams.', docs: '128 docs', cat: 'Operations' },
      { t: 'Legal Contracts & NDAs', d: 'A vetted library of contract templates, NDAs, MSAs, and clause explanations for quick reference.', docs: '342 docs', cat: 'Legal' },
      { t: 'Product Manuals & Specs', d: 'Hardware and software manuals, technical specifications, and troubleshooting guides.', docs: '517 docs', cat: 'Technical' },
      { t: 'HR Policies & Handbook', d: 'Employee handbook, PTO policy, benefits, and compliance documentation for HR teams.', docs: '96 docs', cat: 'HR' },
      { t: 'Customer Support Knowledge', d: 'FAQs, macros, escalation paths, and resolution scripts to power instant support answers.', docs: '740 docs', cat: 'Support' },
      { t: 'Medical & Clinical Guidelines', d: 'HIPAA-aligned clinical references, dosage guides, and care protocols for healthcare staff.', docs: '1,204 docs', cat: 'Healthcare' },
      { t: 'Finance & Accounting Reference', d: 'GAAP guidance, tax procedures, invoicing rules, and month-end close checklists.', docs: '288 docs', cat: 'Finance' },
      { t: 'Sales Enablement Vault', d: 'Battle cards, objection handling, pricing sheets, and competitor intel for closing faster.', docs: '215 docs', cat: 'Sales' },
      { t: 'Engineering Wiki & Runbooks', d: 'Architecture docs, incident runbooks, API references, and deployment procedures.', docs: '963 docs', cat: 'Engineering' },
      { t: 'Real Estate Docs & Disclosures', d: 'Listing agreements, disclosures, lease templates, and closing documentation.', docs: '174 docs', cat: 'Real Estate' },
      { t: 'Marketing Brand Guidelines', d: 'Brand voice, style guides, campaign briefs, and approved messaging across channels.', docs: '132 docs', cat: 'Marketing' },
      { t: 'Compliance & Risk Library', d: 'Regulatory frameworks, audit checklists, and risk assessment templates kept current.', docs: '401 docs', cat: 'Compliance' }
    ];

    var docIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>';
    var fileIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>';
    var tagIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle></svg>';
    var viewIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    var plusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>';

    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }

    function cardHTML(kb) {
      return '<div class="kb-card">' +
        '<div class="kb-card-icon">' + docIcon + '</div>' +
        '<div class="kb-card-body">' +
          '<p class="kb-card-title">' + escapeHtml(kb.t) + '</p>' +
          '<p class="kb-card-desc">' + escapeHtml(kb.d) + '</p>' +
          '<div class="kb-card-meta"><span>' + fileIcon + escapeHtml(kb.docs) + '</span><span>' + tagIcon + escapeHtml(kb.cat) + '</span></div>' +
        '</div>' +
        '<div class="kb-card-actions">' +
          '<button type="button" class="kb-btn kb-btn--ghost" data-kb-gate>' + viewIcon + 'View</button>' +
          '<button type="button" class="kb-btn kb-btn--add" data-kb-gate>' + plusIcon + 'Add to My KB</button>' +
        '</div>' +
      '</div>';
    }

    var listEl = document.querySelector('[data-kb-lib-list]');
    function renderLibrary(filter) {
      if (!listEl) return;
      var q = (filter || '').toLowerCase().trim();
      var rows = LIBRARY.filter(function (kb) {
        if (!q) return true;
        return (kb.t + ' ' + kb.d + ' ' + kb.cat).toLowerCase().indexOf(q) >= 0;
      });
      listEl.innerHTML = rows.length
        ? rows.map(cardHTML).join('')
        : '<div class="kb-empty"><p class="kb-empty-title">No matches found</p></div>';
    }
    renderLibrary('');

    var searchInput = document.querySelector('[data-kb-search]');
    if (searchInput) {
      searchInput.addEventListener('input', function () { renderLibrary(searchInput.value); });
    }

    // ---- Tab switching ----
    var tabs = document.querySelectorAll('[data-kb-tab]');
    var panels = document.querySelectorAll('[data-kb-panel]');
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var name = tab.getAttribute('data-kb-tab');
        tabs.forEach(function (t) { t.classList.toggle('kb-tab--active', t === tab); });
        panels.forEach(function (p) { p.hidden = p.getAttribute('data-kb-panel') !== name; });
      });
    });

    // ---- Upgrade gate modal ----
    if (!document.getElementById('omni-kb-upgrade-styles')) {
      var style = document.createElement('style');
      style.id = 'omni-kb-upgrade-styles';
      style.textContent = [
        '#omni-kb-overlay{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:1.25rem;background:rgba(4,4,10,.84);backdrop-filter:blur(12px);animation:omniGenFadeIn .3s ease}',
        '#omni-kb-card{position:relative;max-width:30rem;width:100%;max-height:94vh;overflow-y:auto;border-radius:1.5rem;border:1px solid rgba(139,92,255,.24);background:linear-gradient(160deg,rgba(28,18,52,.98) 0%,rgba(10,8,20,.99) 100%);box-shadow:0 32px 80px rgba(0,0,0,.65),0 0 60px rgba(139,92,255,.14),inset 0 1px 0 rgba(255,255,255,.06)}',
        '.omni-kb-glow{position:absolute;inset:-1px;border-radius:1.5rem;background:linear-gradient(135deg,rgba(139,92,255,.35),rgba(56,189,248,.18),rgba(139,92,255,.22));opacity:.5;pointer-events:none}',
        '.omni-kb-inner{position:relative;padding:1.7rem 1.6rem 1.5rem}',
        '.omni-kb-head{display:flex;align-items:center;gap:.95rem;margin-bottom:1.05rem}',
        '.omni-kb-icon{width:3.1rem;height:3.1rem;border-radius:1rem;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,rgba(139,92,255,.24),rgba(56,189,248,.12));border:1px solid rgba(255,255,255,.1);flex-shrink:0}',
        '.omni-kb-icon svg{width:1.55rem;height:1.55rem;color:#c4b5fd}',
        '#omni-kb-title{font-size:1.22rem;font-weight:800;color:#fafafa;margin:0;line-height:1.3}',
        '#omni-kb-desc{font-size:.85rem;color:rgba(212,212,216,.92);margin:.45rem 0 0;line-height:1.55}',
        '.omni-kb-features{list-style:none;margin:1.1rem 0;padding:0;display:flex;flex-direction:column;gap:.6rem}',
        '.omni-kb-feat{display:flex;align-items:flex-start;gap:.65rem;font-size:.85rem;color:#e4e4e7;line-height:1.4}',
        '.omni-kb-feat-emoji{flex-shrink:0;font-size:1rem;line-height:1.3}',
        '.omni-kb-price{margin:1.05rem 0;padding:.9rem 1rem;border-radius:.85rem;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.24);font-size:.84rem;color:#e4e4e7;line-height:1.5}',
        '.omni-kb-price strong{color:#6ee7b7;font-weight:800}',
        '#omni-kb-cta{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;padding:1rem 1.25rem;border:none;border-radius:.9rem;font-size:1rem;font-weight:800;color:#fff;cursor:pointer;background:linear-gradient(135deg,#8b5cf6,#6d28d9);box-shadow:0 10px 30px rgba(139,92,255,.45);transition:transform .15s,box-shadow .15s}',
        '#omni-kb-cta:hover{transform:translateY(-2px);box-shadow:0 14px 40px rgba(139,92,255,.6)}',
        '#omni-kb-cta svg{width:1.05rem;height:1.05rem}',
        '.omni-kb-foot{text-align:center;font-size:.72rem;color:rgba(161,161,170,.85);margin:.8rem 0 0}',
        '#omni-kb-close{position:absolute;top:.9rem;right:.9rem;z-index:2;width:2rem;height:2rem;border:none;border-radius:.55rem;background:rgba(255,255,255,.06);color:#a1a1aa;cursor:pointer;font-size:1.15rem;line-height:1}',
        '#omni-kb-close:hover{background:rgba(255,255,255,.14);color:#fff}'
      ].join('');
      document.head.appendChild(style);
    }

    var overlay = document.createElement('div');
    overlay.id = 'omni-kb-overlay';
    overlay.innerHTML =
      '<div id="omni-kb-card">' +
        '<div class="omni-kb-glow"></div>' +
        '<button type="button" id="omni-kb-close" aria-label="Close">&times;</button>' +
        '<div class="omni-kb-inner">' +
          '<div class="omni-kb-head">' +
            '<div class="omni-kb-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 6 4 14"></path><path d="M12 6v14"></path><path d="M8 8v12"></path><path d="M4 4v16"></path></svg></div>' +
            '<div><p id="omni-kb-title">Unlock Your Private AI Knowledge Base</p></div>' +
          '</div>' +
          '<p id="omni-kb-desc">Upload and train AI on up to <strong>50,000 PDF documents</strong>, allowing it to answer questions using your own files, SOPs, contracts, manuals, and internal knowledge.</p>' +
          '<ul class="omni-kb-features">' +
            '<li class="omni-kb-feat"><span class="omni-kb-feat-emoji">🔒</span><span>Enterprise-grade encryption</span></li>' +
            '<li class="omni-kb-feat"><span class="omni-kb-feat-emoji">🛡️</span><span>HIPAA-compliant security standards</span></li>' +
            '<li class="omni-kb-feat"><span class="omni-kb-feat-emoji">⚡</span><span>Fast document search &amp; retrieval</span></li>' +
          '</ul>' +
          '<p class="omni-kb-price">Available on paid plans starting at just <strong>$14.99/month</strong>.</p>' +
          '<button type="button" id="omni-kb-cta"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>Upgrade Now</button>' +
          '<p class="omni-kb-foot">Cancel anytime · Encrypted &amp; private to your account</p>' +
        '</div>' +
      '</div>';
    document.body.appendChild(overlay);

    function showModal() { overlay.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    function hideModal() { overlay.style.display = 'none'; document.body.style.overflow = ''; }

    overlay.querySelector('#omni-kb-cta').addEventListener('click', function () {
      window.location.href = checkoutUrl;
    });
    overlay.querySelector('#omni-kb-close').addEventListener('click', hideModal);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) hideModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideModal(); });

    // Any gated control (delegated, so it also catches dynamically rendered cards)
    document.addEventListener('click', function (e) {
      var t = e.target.closest('[data-kb-gate]');
      if (!t) return;
      e.preventDefault();
      e.stopPropagation();
      showModal();
    });
  })();

  // Video discover — show a poster frame, then play on hover.
  // Seeking to a small time renders the first frame so the card is never blank.
  function primeFrame(v) {
    if (!v || !v.querySelector('source[src]')) return;
    v.muted = true;
    v.setAttribute('muted', '');
    var seek = function () {
      try { if (v.currentTime < 0.05) v.currentTime = 0.1; } catch (e) {}
      v.removeEventListener('loadeddata', seek);
    };
    if (v.readyState >= 2) seek();
    else { v.addEventListener('loadeddata', seek); try { v.load(); } catch (e) {} }
  }

  document.querySelectorAll('[data-lazy-video] video').forEach(function (v) {
    var card = v.closest('button') || v.closest('a') || v.parentElement;
    primeFrame(v);
    if (!card) return;
    card.addEventListener('mouseenter', function () { v.play().catch(function () {}); });
    card.addEventListener('mouseleave', function () {
      v.pause();
      try { v.currentTime = 0.1; } catch (e) {}
    });
  });

  // Lazy-load + prime any discover videos as they scroll into view.
  var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      var v = e.target.querySelector('video');
      if (v && v.querySelector('source[src]')) primeFrame(v);
    });
  }, { rootMargin: '300px' }) : null;
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

  // Local file uploads — stored in browser localStorage (never sent to server)
  (function initLocalUploads() {
    var STORAGE_KEY = 'omnirogue-local-uploads';
    var MAX_IMAGE_DIM = 1600;
    var MAX_TEXT_CHARS = 80000;
    var changeListeners = [];

    function pageId() {
      var m = window.location.pathname.match(/\/([^/]+)\.html$/);
      return m ? m[1] : 'page';
    }

    function readStore() {
      try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') || {};
      } catch (e) {
        return {};
      }
    }

    function writeStore(store) {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
        return true;
      } catch (e) {
        alert('Could not save — browser storage is full. Remove an older upload and try again.');
        return false;
      }
    }

    function notify() {
      changeListeners.forEach(function (fn) { fn(); });
      document.dispatchEvent(new CustomEvent('omni-upload-change'));
    }

    function getSlot(slot) {
      var store = readStore();
      var pid = pageId();
      return store[pid] && store[pid][slot] ? store[pid][slot] : null;
    }

    function setSlot(slot, data) {
      var store = readStore();
      var pid = pageId();
      if (!store[pid]) store[pid] = {};
      store[pid][slot] = data;
      if (!writeStore(store)) return false;
      notify();
      return true;
    }

    function clearSlot(slot) {
      var store = readStore();
      var pid = pageId();
      if (!store[pid] || !store[pid][slot]) return;
      delete store[pid][slot];
      if (!Object.keys(store[pid]).length) delete store[pid];
      writeStore(store);
      notify();
    }

    function compressImage(file) {
      return new Promise(function (resolve, reject) {
        var reader = new FileReader();
        reader.onerror = reject;
        reader.onload = function () {
          var img = new Image();
          img.onerror = reject;
          img.onload = function () {
            var w = img.naturalWidth;
            var h = img.naturalHeight;
            var scale = Math.min(1, MAX_IMAGE_DIM / Math.max(w, h));
            var cw = Math.round(w * scale);
            var ch = Math.round(h * scale);
            var canvas = document.createElement('canvas');
            canvas.width = cw;
            canvas.height = ch;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, cw, ch);
            var outType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
            var quality = outType === 'image/jpeg' ? 0.82 : undefined;
            canvas.toBlob(function (blob) {
              if (!blob) { reject(new Error('compress failed')); return; }
              var r2 = new FileReader();
              r2.onload = function () {
                resolve({
                  name: file.name,
                  type: outType,
                  size: blob.size,
                  dataUrl: r2.result,
                  savedAt: Date.now()
                });
              };
              r2.onerror = reject;
              r2.readAsDataURL(blob);
            }, outType, quality);
          };
          img.src = reader.result;
        };
        reader.readAsDataURL(file);
      });
    }

    function readTextFile(file) {
      return new Promise(function (resolve, reject) {
        var reader = new FileReader();
        reader.onerror = reject;
        reader.onload = function () {
          var text = String(reader.result || '');
          if (text.length > MAX_TEXT_CHARS) text = text.slice(0, MAX_TEXT_CHARS);
          resolve({
            name: file.name,
            type: file.type || 'text/plain',
            size: file.size,
            text: text,
            savedAt: Date.now()
          });
        };
        reader.readAsText(file);
      });
    }

    function processFile(file) {
      if (!file) return Promise.reject(new Error('no file'));
      if (/^image\//.test(file.type)) return compressImage(file);
      if (/^text\//.test(file.type) || /\.(txt|md|markdown|csv|tsv|json|yaml|yml|html?|xml|log|srt|vtt|js|jsx|ts|tsx|py|go|rs|java|c|cpp|cs|sql|sh|css|scss)$/i.test(file.name)) {
        return readTextFile(file);
      }
      return Promise.resolve({
        name: file.name,
        type: file.type || 'application/octet-stream',
        size: file.size,
        metaOnly: true,
        savedAt: Date.now()
      });
    }

    if (!document.getElementById('omni-upload-styles')) {
      var style = document.createElement('style');
      style.id = 'omni-upload-styles';
      style.textContent = [
        '.omni-upload-zone.omni-upload-drag{border-color:rgba(139,92,255,.55)!important;background:rgba(139,92,255,.08)!important}',
        '.omni-upload-zone.omni-upload-filled{border-style:solid!important;border-color:rgba(139,92,255,.35)!important;background:rgba(139,92,255,.06)!important;padding-right:.5rem!important}',
        '.omni-upload-thumb{width:3rem;height:3rem;border-radius:.75rem;object-fit:cover;flex-shrink:0;border:1px solid rgba(255,255,255,.1)}',
        '.omni-upload-meta{display:flex;flex-direction:column;min-width:0;flex:1}',
        '.omni-upload-name{font-weight:600;color:#fafafa;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
        '.omni-upload-hint{font-size:.68rem;color:rgba(161,161,170,.85)}',
        '.omni-upload-remove{width:1.75rem;height:1.75rem;border-radius:.5rem;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#a1a1aa;cursor:pointer;flex-shrink:0;font-size:1rem;line-height:1}',
        '.omni-upload-remove:hover{background:rgba(239,68,68,.15);color:#fca5a5;border-color:rgba(239,68,68,.3)}',
        '.omni-attach-list{display:flex;flex-wrap:wrap;gap:.4rem;padding:0 .25rem .35rem}',
        '.omni-attach-chip{display:inline-flex;align-items:center;gap:.35rem;max-width:12rem;padding:.3rem .55rem;border-radius:999px;font-size:.68rem;color:#e4e4e7;background:rgba(139,92,255,.12);border:1px solid rgba(139,92,255,.22)}',
        '.omni-attach-chip img{width:1.1rem;height:1.1rem;border-radius:.25rem;object-fit:cover}',
        '.omni-attach-chip button{border:none;background:transparent;color:#a1a1aa;cursor:pointer;padding:0 .1rem;font-size:.85rem;line-height:1}',
        '.omni-attach-chip button:hover{color:#fca5a5}'
      ].join('');
      document.head.appendChild(style);
    }

    function renderUpscaleZone(btn, data) {
      btn.classList.add('omni-upload-filled');
      btn.setAttribute('data-omni-upload-filled', 'true');
      btn.setAttribute('aria-label', 'Image attached — click to replace');
      btn.innerHTML =
        '<img class="omni-upload-thumb" src="' + data.dataUrl + '" alt="">' +
        '<span class="omni-upload-meta"><span class="omni-upload-name">' + escapeHtml(data.name) + '</span>' +
        '<span class="omni-upload-hint">Saved locally · click to replace</span></span>' +
        '<button type="button" class="omni-upload-remove" aria-label="Remove image">&times;</button>';
      var removeBtn = btn.querySelector('.omni-upload-remove');
      if (removeBtn) {
        removeBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          clearSlot('upscale-image');
          resetUpscaleZone(btn);
          syncAllUpscaleZones();
        });
      }
    }

    function resetUpscaleZone(btn) {
      btn.classList.remove('omni-upload-filled', 'omni-upload-drag');
      btn.removeAttribute('data-omni-upload-filled');
      btn.setAttribute('aria-label', 'Drop or click to attach image for upscale');
      btn.innerHTML =
        '<span class="flex items-center justify-center rounded-xl shrink-0 transition-colors w-10 h-10 bg-white/[0.06] text-foreground/65 group-hover:bg-primary/15 group-hover:text-primary">' +
          '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="lucide lucide-upload shrink-0"><path d="M12 3v12"></path><path d="m17 8-5-5-5 5"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path></svg>' +
        '</span>' +
        '<span class="flex flex-col min-w-0 leading-tight">' +
          '<span class="font-semibold">Drag & drop an image</span>' +
          '<span class="text-[11px] text-foreground/50">or click to browse · PNG, JPG, WebP</span>' +
        '</span>';
    }

    function escapeHtml(s) {
      return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    var upscaleZones = [];

    function wireUpscaleZone(btn, input) {
      btn.classList.add('omni-upload-zone', 'group');
      upscaleZones.push(btn);

      function handleFiles(files) {
        var file = files && files[0];
        if (!file || !/^image\//.test(file.type)) {
          alert('Please choose a PNG, JPG, or WebP image.');
          return;
        }
        processFile(file).then(function (data) {
          if (!setSlot('upscale-image', data)) return;
          syncAllUpscaleZones();
        }).catch(function () {
          alert('Could not read that image. Try another file.');
        });
      }

      btn.addEventListener('click', function (e) {
        if (e.target.closest('.omni-upload-remove')) return;
        e.preventDefault();
        input.click();
      });

      input.addEventListener('change', function () {
        handleFiles(input.files);
        input.value = '';
      });

      ['dragenter', 'dragover'].forEach(function (ev) {
        btn.addEventListener(ev, function (e) {
          e.preventDefault();
          e.stopPropagation();
          btn.classList.add('omni-upload-drag');
        });
      });
      ['dragleave', 'drop'].forEach(function (ev) {
        btn.addEventListener(ev, function (e) {
          e.preventDefault();
          e.stopPropagation();
          btn.classList.remove('omni-upload-drag');
        });
      });
      btn.addEventListener('drop', function (e) {
        handleFiles(e.dataTransfer && e.dataTransfer.files);
      });
    }

    function syncAllUpscaleZones() {
      var data = getSlot('upscale-image');
      upscaleZones.forEach(function (btn) {
        if (data && data.dataUrl) renderUpscaleZone(btn, data);
        else resetUpscaleZone(btn);
      });
    }

    document.querySelectorAll('button[aria-label="Drop or click to attach image for upscale"], button[data-testid="upscale-attach-empty"]').forEach(function (btn) {
      var input = btn.nextElementSibling;
      if (input && input.type === 'file') wireUpscaleZone(btn, input);
    });
    if (upscaleZones.length) syncAllUpscaleZones();

    // AI Chat — attach files
    var attachList = null;

    function ensureAttachList(anchor) {
      if (attachList && attachList.isConnected) return attachList;
      attachList = document.createElement('div');
      attachList.className = 'omni-attach-list';
      attachList.id = 'omni-attach-list';
      var row = anchor.closest('.flex') || anchor.parentElement;
      if (row && row.parentElement) row.parentElement.insertBefore(attachList, row);
      else anchor.parentElement.insertBefore(attachList, anchor);
      return attachList;
    }

    function getAttachments() {
      var data = getSlot('chat-attachments');
      return data && data.items ? data.items : [];
    }

    function setAttachments(items) {
      setSlot('chat-attachments', { items: items, savedAt: Date.now() });
    }

    function renderAttachments(anchor) {
      var items = getAttachments();
      var list = ensureAttachList(anchor);
      if (!items.length) {
        list.innerHTML = '';
        list.style.display = 'none';
        return;
      }
      list.style.display = 'flex';
      list.innerHTML = items.map(function (item) {
        var thumb = item.dataUrl
          ? '<img src="' + item.dataUrl + '" alt="">'
          : '<span>📄</span>';
        return '<span class="omni-attach-chip" data-id="' + item.id + '">' + thumb +
          '<span class="truncate">' + escapeHtml(item.name) + '</span>' +
          '<button type="button" aria-label="Remove ' + escapeHtml(item.name) + '">&times;</button></span>';
      }).join('');
      list.querySelectorAll('button').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          var chip = btn.closest('.omni-attach-chip');
          var id = chip && chip.getAttribute('data-id');
          if (!id) return;
          setAttachments(getAttachments().filter(function (it) { return it.id !== id; }));
          renderAttachments(anchor);
        });
      });
    }

    function wireChatAttach(btn, input) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        input.click();
      });

      input.addEventListener('change', function () {
        var files = Array.from(input.files || []);
        input.value = '';
        if (!files.length) return;
        Promise.all(files.map(processFile)).then(function (processed) {
          var items = getAttachments();
          processed.forEach(function (data) {
            items.push({
              id: 'f-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7),
              name: data.name,
              type: data.type,
              size: data.size,
              dataUrl: data.dataUrl || null,
              text: data.text || null,
              metaOnly: !!data.metaOnly,
              savedAt: data.savedAt
            });
          });
          setAttachments(items.slice(-12));
          renderAttachments(btn);
        }).catch(function () {
          alert('Could not read one or more files.');
        });
      });

      renderAttachments(btn);
    }

    document.querySelectorAll('input[type="file"]').forEach(function (input) {
      if (input.accept && input.accept.indexOf('application/pdf') >= 0) {
        var btn = input.previousElementSibling;
        if (btn && btn.tagName === 'BUTTON' && /attach/i.test(btn.textContent)) {
          wireChatAttach(btn, input);
        }
      }
    });

    window.omniLocalUploads = {
      has: function (slot) { return !!getSlot(slot); },
      get: getSlot,
      onChange: function (fn) { changeListeners.push(fn); }
    };
  })();

  // Create studio — prompt + Generate / Create Agent / Send flow
  (function initGenerateFlow() {
    var checkoutUrl = omniRegisterCheckoutUrl();
    var path = window.location.pathname;

    var STUDIO = {
      video: { key: 'video', label: 'AI Video', icon: '🎬', ready: 'video', kind: 'video' },
      image: { key: 'image', label: 'AI Image', icon: '🖼️', ready: 'image', kind: 'image' },
      upscale: { key: 'upscale', label: 'AI Upscale', icon: '✨', ready: 'upscaled image', kind: 'image' },
      audio: { key: 'audio', label: 'AI Audio', icon: '🎙️', ready: 'voiceover', kind: 'audio' },
      music: { key: 'music', label: 'AI Music', icon: '🎵', ready: 'track', kind: 'music' },
      omnireels: { key: 'omnireels', label: 'OmniReels', icon: '📱', ready: 'reel', kind: 'video' },
      podcast: { key: 'podcast', label: 'AI Podcast', icon: '🎧', ready: 'podcast episode', kind: 'audio' },
      voice: { key: 'voice', label: 'Voice Agents', icon: '🎤', ready: 'voice agent', kind: 'audio' },
      chat: { key: 'chat', label: 'AI Chat', icon: '💬', ready: 'response', kind: 'chat' }
    };

    var STUDIO_MODELS = {
      video: ['Seedance 2.0', 'Veo 3.1', 'Kling 3', 'Kling 3.0', 'Jimeng'],
      image: ['GPT Image 2', 'Nano Banana 2', 'Nano Banana', 'Flux Pro', 'Ideogram 3.0'],
      upscale: ['Real-ESRGAN', 'Topaz AI', 'Magnific', 'GFPGAN', 'CodeFormer'],
      audio: ['ElevenLabs', 'Multilingual v2', 'Turbo v2.5', 'Rachel', 'Josh'],
      music: ['Suno V5', 'Udio', 'Mureka', 'MiniMax Music', 'Stable Audio'],
      omnireels: ['OmniReels Studio', 'Kling 3', 'Veo 3.1', 'Seedance 2.0', 'CapCut AI'],
      podcast: ['ElevenLabs Podcast', 'PlayHT', 'Resemble AI', 'MiniMax Speech', 'Suno Instrumentals'],
      voice: ['ElevenLabs Conversational', 'GPT-4o Realtime', 'Gemini Live', 'PlayHT 2.0', 'Resemble AI'],
      chat: ['GPT-5.5', 'Claude 4.7', 'Gemini Pro 3.1', 'DeepSeek R1', 'Llama 4']
    };

    function detectStudio() {
      if (path.indexOf('create-upscale') >= 0) return STUDIO.upscale;
      if (path.indexOf('create-image') >= 0) return STUDIO.image;
      if (path.indexOf('create-audio') >= 0) return STUDIO.audio;
      if (path.indexOf('create-music') >= 0) return STUDIO.music;
      if (path.indexOf('create-omnireels') >= 0) return STUDIO.omnireels;
      if (path.indexOf('create-podcast') >= 0) return STUDIO.podcast;
      if (path.indexOf('create-voice-agents') >= 0) return STUDIO.voice;
      if (path.indexOf('create-ai-chat') >= 0) return STUDIO.chat;
      return STUDIO.video;
    }

    var studio = detectStudio();

    var textarea = document.querySelector('textarea[placeholder*="Describe"]') ||
      document.querySelector('textarea[placeholder*="voice agent"]') ||
      document.querySelector('textarea[placeholder*="Message"]') ||
      document.querySelector('textarea[placeholder*="image"]') ||
      document.querySelector('textarea[placeholder*="script"]') ||
      document.querySelector('textarea[placeholder*="podcast"]') ||
      document.querySelector('textarea[placeholder*="voiceover"]') ||
      document.querySelector('textarea[maxlength="2000"]');
    if (!textarea) return;

    function buttonLabel(btn) {
      var span = btn.querySelector('span');
      return (span ? span.textContent : btn.textContent).replace(/\s+/g, ' ').trim();
    }

    function findActionButtons() {
      var composer = textarea;
      for (var i = 0; i < 10 && composer; i++) composer = composer.parentElement;
      var scope = composer || document;
      var found = [];
      var seen = new Set();
      scope.querySelectorAll('button').forEach(function (btn) {
        if (seen.has(btn)) return;
        var label = buttonLabel(btn);
        var isGenerate = /^(Generate|Create Agent)$/i.test(label);
        var isChatSend = studio.key === 'chat' && !!btn.querySelector('.lucide-send, svg.lucide-send');
        if (isGenerate || isChatSend) {
          seen.add(btn);
          found.push(btn);
        }
      });
      return found;
    }

    var generateBtns = findActionButtons();
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

    var actionName = studio.key === 'voice' ? 'Create Agent'
      : studio.key === 'chat' ? 'Send' : 'Generate';

    function setGenerateEnabled(enabled) {
      generateBtns.forEach(function (btn) {
        if (enabled) {
          btn.disabled = false;
          btn.removeAttribute('disabled');
          toggleClass(btn, inactiveClasses, false);
          toggleClass(btn, activeClasses, true);
          btn.title = actionName;
          btn.setAttribute('aria-label', actionName);
        } else {
          btn.disabled = true;
          toggleClass(btn, activeClasses, false);
          toggleClass(btn, inactiveClasses, true);
          var hint = studio.key === 'upscale'
            ? 'Upload an image to enable ' + actionName + '.'
            : 'Add a short description to enable ' + actionName + '.';
          btn.title = hint;
          btn.setAttribute('aria-label', actionName + ' — ' + hint);
        }
      });
    }

    function canGenerate() {
      if (textarea.value.trim().length > 0) return true;
      if (studio.key === 'upscale' && window.omniLocalUploads && window.omniLocalUploads.has('upscale-image')) {
        return true;
      }
      return false;
    }

    function syncGenerateState() {
      setGenerateEnabled(canGenerate());
    }

    textarea.addEventListener('input', syncGenerateState);
    if (window.omniLocalUploads) window.omniLocalUploads.onChange(syncGenerateState);
    document.addEventListener('omni-upload-change', syncGenerateState);
    syncGenerateState();

    // Collect the top piece of media already on this page to use as the preview.
    function collectPreview() {
      var p = { kind: studio.kind, src: null, poster: null };
      if (studio.kind === 'video') {
        var srcEl = document.querySelector('[data-lazy-video] video source[src], video source[src]');
        if (srcEl) p.src = srcEl.getAttribute('src');
      } else if (studio.kind === 'image') {
        if (studio.key === 'upscale' && window.omniLocalUploads) {
          var up = window.omniLocalUploads.get('upscale-image');
          if (up && up.dataUrl) p.src = up.dataUrl;
        }
        if (!p.src) {
          var img = document.querySelector('main img[src*="b-cdn.net/images/"], main img[src*="unsplash"]');
          if (img) p.src = img.currentSrc || img.getAttribute('src');
        }
        if (!p.src) {
          var thumb = document.querySelector('.omni-upload-thumb');
          if (thumb) p.src = thumb.getAttribute('src');
        }
      } else if (studio.kind === 'audio') {
        var au = document.querySelector('audio[src]');
        if (au) p.src = au.getAttribute('src');
        var tmpl = document.querySelector('section img[src*="unsplash"], section img[src*="b-cdn"]');
        if (tmpl) p.poster = tmpl.currentSrc || tmpl.getAttribute('src');
      } else if (studio.kind === 'music') {
        var cover = document.querySelector('section img[src*="unsplash"], section img[src*="b-cdn"]');
        if (cover) p.poster = cover.currentSrc || cover.getAttribute('src');
      }
      return p;
    }

    var playSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="#fff"><path d="M5 5a2 2 0 0 1 3.008-1.728l11.997 6.998a2 2 0 0 1 .003 3.458l-12 7A2 2 0 0 1 5 19z"></path></svg>';
    var lockSvgSmall = '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';

    function preBadge() { return '<span class="omni-gen-preview-badge">' + (meta.tag || 'Preview') + '</span>'; }
    function lockOverlay(label) {
      return '<button type="button" class="omni-gen-lock" data-claim="1" aria-label="' + label + '">' +
        '<span class="omni-gen-lock-ico">' + lockSvgSmall + '</span>' +
        '<span class="omni-gen-lock-play">' + playSvg + '</span>' +
        '<span class="omni-gen-lock-label">' + label + '</span></button>';
    }

    function buildPreviewHTML(p) {
      // Video / reel — a real clip plays behind a gated "watch full" overlay.
      if (p.kind === 'video' && p.src) {
        var vert = studio.key === 'omnireels' ? ' omni-gen-preview--vert' : '';
        return '<div class="omni-gen-preview' + vert + '">' +
          '<video src="' + p.src + '" autoplay muted loop playsinline class="omni-gen-media"></video>' +
          '<span class="omni-gen-grad"></span>' + preBadge() +
          '<span class="omni-gen-wm">OmniRogue</span>' +
          '<span class="omni-gen-dur">' + fmtTime(genDur) + '</span>' +
          '<span class="omni-gen-scrub"><span class="omni-gen-scrub-fill"></span></span>' +
          lockOverlay(meta.play) + '</div>';
      }
      // Image / upscale — the finished frame with watermark + gated full view.
      if (p.kind === 'image' && p.src) {
        return '<div class="omni-gen-preview">' +
          '<img src="' + p.src + '" alt="Your generation" class="omni-gen-media">' +
          '<span class="omni-gen-grad"></span>' + preBadge() +
          '<span class="omni-gen-wm">OmniRogue</span>' +
          lockOverlay(meta.play) + '</div>';
      }
      // Audio / music / podcast — cover art + live waveform + locked snippet.
      if (p.kind === 'audio' || p.kind === 'music') {
        var music = p.kind === 'music';
        var art = p.poster ? '<img src="' + p.poster + '" alt="" class="omni-gen-media omni-gen-media--dim">' : '';
        return '<div class="omni-gen-preview omni-gen-preview--audio">' + art +
          '<div class="omni-gen-audio-ui">' +
            '<div class="omni-gen-wave' + (music ? ' omni-gen-wave--music' : '') + '">' + waveBars(music ? 34 : 28) + '</div>' +
            '<button type="button" class="omni-gen-play" data-claim="1" aria-label="' + meta.play + '">' + playSvg + '</button>' +
            '<span class="omni-gen-snip">0:08 preview &middot; <b>' + fmtTime(genDur) + '</b> unlocked</span>' +
          '</div>' + preBadge() + '</div>';
      }
      // Chat — a believable answer that streams in then fades out, gated.
      if (p.kind === 'chat') {
        return '<div class="omni-gen-preview omni-gen-preview--chat">' +
          '<div class="omni-gen-chat-bubble"><p>' + chatAnswerSnippet() + '</p><span class="omni-gen-chat-fade"></span></div>' +
          '<span class="omni-gen-chat-more" data-claim="1">Unlock to read the full answer &rarr;</span>' +
          '</div>';
      }
      // Graceful fallback so the result never looks broken.
      return '<div class="omni-gen-preview omni-gen-preview--ph">' +
        '<span class="omni-gen-ph-ico">' + studio.icon + '</span>' +
        '<span class="omni-gen-grad"></span>' + preBadge() + lockOverlay(meta.play) + '</div>';
    }

    // Believable, studio-specific output details (resolution, duration range,
    // file size range, file type, and the desire-driving CTA copy).
    var GEN_META = {
      video:     { noun: 'video',            file: 'mp4', spec: '1920×1080',          tag: '1080p HD', durMin: 5,   durMax: 12,  sizeMin: 8,  sizeMax: 22, cta: 'Watch your video in full HD',     play: 'Watch full video' },
      omnireels: { noun: 'reel',             file: 'mp4', spec: '1080×1920',          tag: '1080p HD', durMin: 8,   durMax: 22,  sizeMin: 10, sizeMax: 28, cta: 'Watch your reel in full HD',      play: 'Watch your reel' },
      image:     { noun: 'image',            file: 'png', spec: '2048×2048',          tag: '4K',       sizeMin: 3,  sizeMax: 9,                            cta: 'View & download your image',      play: 'View full image' },
      upscale:   { noun: 'upscaled image',   file: 'png', spec: '4096×4096',          tag: '4× · 4K',  sizeMin: 8,  sizeMax: 18,                           cta: 'Download your 4K image',          play: 'View 4K image' },
      audio:     { noun: 'voiceover',        file: 'mp3', spec: '320 kbps',           tag: 'Studio',   durMin: 16,  durMax: 48,  sizeMin: 1,  sizeMax: 3,  cta: 'Listen & download your voiceover', play: 'Play voiceover' },
      music:     { noun: 'track',            file: 'wav', spec: '48kHz · Lossless',   tag: 'Lossless', durMin: 95,  durMax: 210, sizeMin: 6,  sizeMax: 14, cta: 'Listen & download your track',     play: 'Play your track' },
      podcast:   { noun: 'podcast episode',  file: 'mp3', spec: '2 voices · 192 kbps',tag: 'Studio',   durMin: 480, durMax: 960, sizeMin: 9,  sizeMax: 22, cta: 'Listen & download your episode',   play: 'Play episode' },
      voice:     { noun: 'voice agent',      file: '',    spec: 'Live · <120ms',      tag: 'Realtime',                                                    cta: 'Talk to your voice agent',        play: 'Start live call' },
      chat:      { noun: 'response',         file: '',    spec: '',                   tag: '',                                                            cta: 'Continue this chat',              play: '' }
    };
    var meta = GEN_META[studio.key] || GEN_META.video;

    // The render pipeline shown to the user — looks like a real job queue.
    var GEN_STAGES = {
      video:     ['Queued', 'Analyzing your prompt', 'Generating frames', 'Upscaling to 1080p', 'Adding motion & sound', 'Finalizing render'],
      omnireels: ['Queued', 'Analyzing your prompt', 'Generating scenes', 'Editing the cut', 'Adding music & captions', 'Finalizing render'],
      image:     ['Queued', 'Analyzing your prompt', 'Composing the image', 'Refining the details', 'Upscaling', 'Finalizing'],
      upscale:   ['Queued', 'Reading your image', 'Detecting fine detail', 'Upscaling 4×', 'Sharpening textures', 'Finalizing'],
      audio:     ['Queued', 'Analyzing your script', 'Synthesizing the voice', 'Shaping intonation', 'Mastering audio', 'Finalizing'],
      music:     ['Queued', 'Analyzing your prompt', 'Composing the melody', 'Layering instruments', 'Mastering the mix', 'Finalizing'],
      podcast:   ['Queued', 'Analyzing your script', 'Casting the voices', 'Recording dialogue', 'Mixing the episode', 'Finalizing'],
      voice:     ['Queued', 'Configuring the agent', 'Cloning the voice', 'Connecting telephony', 'Going live'],
      chat:      ['Queued', 'Reading your message', 'Thinking it through', 'Composing the reply', 'Done']
    };
    var stages = GEN_STAGES[studio.key] || GEN_STAGES.video;

    var genDur = 0; // chosen per run; feeds duration badges + metadata

    function rnd(min, max) { return min + Math.random() * (max - min); }
    function fmtTime(sec) {
      sec = Math.max(1, Math.round(sec));
      var mm = Math.floor(sec / 60), ss = sec % 60;
      return mm + ':' + (ss < 10 ? '0' : '') + ss;
    }
    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }
    function promptText() { return (textarea.value || '').replace(/\s+/g, ' ').trim(); }
    function slugify(t) {
      var s = t.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').split('-').filter(Boolean).slice(0, 5).join('-');
      return s || ('omnirogue-' + studio.key);
    }
    function buildFilename() {
      var ext = meta.file || 'file';
      var tag = studio.key === 'upscale' ? '4k' : (meta.file === 'mp4' ? '1080p' : 'hd');
      return slugify(promptText()) + '_' + tag + '.' + ext;
    }
    function buildMetaLine() {
      var bits = [];
      if (meta.spec) bits.push(meta.spec);
      if (meta.durMin) bits.push(fmtTime(genDur));
      if (meta.file) bits.push(meta.file.toUpperCase());
      bits.push(rnd(meta.sizeMin || 2, meta.sizeMax || 6).toFixed(1) + ' MB');
      return bits.join('  ·  ');
    }
    function chatAnswerSnippet() {
      var q = promptText();
      var topic = q ? (q.length > 56 ? q.slice(0, 56) + '…' : q) : 'your question';
      return 'Absolutely — here\u2019s a clear breakdown for \u201c' + escapeHtml(topic) + '\u201d. ' +
        'First, the core idea and why it matters. Next, three concrete steps you can act on today, ' +
        'each with a quick example. Finally, the one mistake most people make and how to avoid it\u2026';
    }
    function renderStages(activeIdx) {
      var checkSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
      return stages.map(function (name, i) {
        var cls = 'omni-gen-stage', ico;
        if (i < activeIdx) { cls += ' omni-gen-stage--done'; ico = checkSvg; }
        else if (i === activeIdx) { cls += ' omni-gen-stage--active'; ico = '<span class="omni-gen-stage-spin"></span>'; }
        else { ico = '<span class="omni-gen-stage-dot"></span>'; }
        return '<div class="' + cls + '"><span class="omni-gen-stage-ico">' + ico + '</span><span>' + name + '</span></div>';
      }).join('');
    }
    function goClaim() {
      var url = (typeof omniRegisterCheckoutUrl === 'function' && omniRegisterCheckoutUrl()) ||
        (typeof omniCheckoutUrl === 'function' && omniCheckoutUrl()) || '';
      if (url) window.location.href = url;
    }

    function waveBars(n) {
      var heights = [40,65,50,85,60,95,55,75,90,60,100,45,70,85,55,80,40,65,90,50,75,95,60,85,45,70,100,55,80,65,50,90,60,75];
      var out = '';
      for (var i = 0; i < n; i++) {
        out += '<span class="omni-gen-bar" style="height:' + (heights[i % heights.length]) + '%;animation-delay:' + (i * 0.05) + 's"></span>';
      }
      return out;
    }

    if (!document.getElementById('omni-gen-styles')) {
      var style = document.createElement('style');
      style.id = 'omni-gen-styles';
      style.textContent = [
        '#omni-gen-overlay{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:1.25rem;background:rgba(4,4,10,.85);backdrop-filter:blur(14px);animation:omniGenFadeIn .3s ease}',
        '#omni-gen-card{position:relative;max-width:31rem;width:100%;max-height:94vh;overflow-y:auto;padding:0;border-radius:1.5rem;border:1px solid rgba(139,92,255,.24);background:linear-gradient(160deg,rgba(28,18,52,.98) 0%,rgba(10,8,20,.99) 100%);box-shadow:0 32px 80px rgba(0,0,0,.65),0 0 60px rgba(139,92,255,.14),inset 0 1px 0 rgba(255,255,255,.06);animation:omniGenCardIn .35s cubic-bezier(.2,.8,.2,1)}',
        '.omni-gen-glow{position:absolute;inset:-1px;border-radius:1.5rem;background:linear-gradient(135deg,rgba(139,92,255,.35),rgba(56,189,248,.15),rgba(139,92,255,.2));opacity:.45;pointer-events:none}',
        '#omni-gen-x{position:absolute;top:.7rem;right:.7rem;z-index:6;width:2rem;height:2rem;border:none;border-radius:.6rem;background:rgba(255,255,255,.06);color:#cbd5e1;cursor:pointer;font-size:1.25rem;line-height:1;transition:background .15s,color .15s}',
        '#omni-gen-x:hover{background:rgba(255,255,255,.14);color:#fff}',
        '.omni-gen-inner{position:relative;padding:1.5rem 1.5rem 1.35rem}',
        '.omni-gen-badge{display:inline-flex;align-items:center;gap:.5rem;padding:.35rem .8rem;border-radius:999px;font-size:.68rem;font-weight:700;letter-spacing:.03em;color:#c4b5fd;background:rgba(139,92,255,.12);border:1px solid rgba(139,92,255,.25);margin-bottom:1rem}',
        '.omni-gen-badge-dot{width:.45rem;height:.45rem;border-radius:50%;background:#22c55e;box-shadow:0 0 0 0 rgba(34,197,94,.5);animation:omniGenPing 1.6s ease-out infinite;flex-shrink:0}',
        '.omni-gen-badge-sep{width:1px;height:.8rem;background:rgba(255,255,255,.18)}',
        '#omni-gen-eng-tag{color:rgba(196,181,253,.78);font-weight:600;letter-spacing:.01em}',
        '.omni-gen-head{display:flex;align-items:center;gap:.9rem;margin-bottom:.9rem}',
        '.omni-gen-icon{width:3rem;height:3rem;border-radius:1rem;display:flex;align-items:center;justify-content:center;font-size:1.5rem;background:linear-gradient(135deg,rgba(139,92,255,.25),rgba(56,189,248,.12));border:1px solid rgba(255,255,255,.08);flex-shrink:0;transition:all .3s ease}',
        '.omni-gen-icon--ok{background:linear-gradient(135deg,rgba(34,197,94,.3),rgba(16,185,129,.15));border-color:rgba(34,197,94,.4);color:#86efac}',
        '.omni-gen-icon--ok svg{width:1.5rem;height:1.5rem}',
        '.omni-gen-head-txt{min-width:0}',
        '#omni-gen-status{font-size:1.18rem;font-weight:800;color:#fafafa;margin:0;line-height:1.3;display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}',
        '.omni-gen-spark{display:inline-block;animation:omniGenPop .5s ease}',
        '#omni-gen-sub{font-size:.8rem;color:rgba(184,184,196,.92);margin:.3rem 0 0;line-height:1.4}',
        '.omni-gen-prompt{margin:.1rem 0 1.1rem;padding:.6rem .8rem;border-radius:.7rem;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.06);font-size:.82rem;color:#d4d4d8;line-height:1.45;font-style:italic}',
        '.omni-gen-prompt-q{color:#a78bfa;font-weight:800;font-style:normal}',
        // processing — progress + pipeline stages
        '.omni-gen-progress{position:relative;height:.55rem;border-radius:999px;background:rgba(255,255,255,.07);overflow:hidden;margin:.2rem 0 .55rem}',
        '.omni-gen-fill{height:100%;width:0;border-radius:999px;background:linear-gradient(90deg,#8b5cf6,#c4b5fd,#38bdf8);background-size:200% 100%;animation:omniGenFlow 1.5s linear infinite;transition:width .25s ease;box-shadow:0 0 14px rgba(139,92,255,.6)}',
        '.omni-gen-progress-row{display:flex;align-items:center;justify-content:space-between;font-size:.74rem;margin-bottom:.95rem}',
        '#omni-gen-stage-lbl{color:#e4e4e7;font-weight:600}',
        '#omni-gen-pct{color:#c4b5fd;font-weight:800;font-variant-numeric:tabular-nums}',
        '.omni-gen-stages{display:flex;flex-direction:column;gap:.35rem;margin:0 0 .9rem;text-align:left}',
        '.omni-gen-stage{display:flex;align-items:center;gap:.6rem;padding:.4rem .55rem;border-radius:.6rem;font-size:.78rem;color:rgba(150,150,160,.7);background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.03);transition:all .3s ease}',
        '.omni-gen-stage--active{color:#fafafa;background:rgba(139,92,255,.14);border-color:rgba(139,92,255,.3)}',
        '.omni-gen-stage--done{color:#86efac}',
        '.omni-gen-stage-ico{width:1.1rem;height:1.1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0}',
        '.omni-gen-stage-ico svg{width:1rem;height:1rem}',
        '.omni-gen-stage-dot{width:.5rem;height:.5rem;border-radius:50%;background:rgba(255,255,255,.18)}',
        '.omni-gen-stage-spin{width:.95rem;height:.95rem;border:2px solid rgba(139,92,255,.25);border-top-color:#a78bfa;border-radius:50%;animation:omniGenSpin .7s linear infinite}',
        '.omni-gen-eng-note{font-size:.7rem;color:rgba(150,150,160,.75);margin:0;text-align:center}',
        '.omni-gen-eng-note b{color:#c4b5fd;font-weight:700}',
        // preview media (the gated result)
        '.omni-gen-preview{position:relative;margin:.1rem 0 .9rem;border-radius:1rem;overflow:hidden;border:1px solid rgba(255,255,255,.1);aspect-ratio:16/10;background:#0c0a18;animation:omniGenReveal .5s cubic-bezier(.2,.8,.2,1)}',
        '.omni-gen-preview--vert{aspect-ratio:9/13;max-width:13rem;margin-left:auto;margin-right:auto}',
        '.omni-gen-media{width:100%;height:100%;object-fit:cover;display:block}',
        '.omni-gen-media--dim{position:absolute;inset:0;opacity:.5}',
        '.omni-gen-grad{position:absolute;inset:0;z-index:1;background:linear-gradient(180deg,rgba(0,0,0,.04) 0%,rgba(0,0,0,0) 35%,rgba(0,0,0,.45) 100%);pointer-events:none}',
        '.omni-gen-wm{position:absolute;bottom:.55rem;right:.6rem;z-index:3;font-size:.7rem;font-weight:800;letter-spacing:.01em;color:rgba(255,255,255,.85);text-shadow:0 1px 4px rgba(0,0,0,.6);pointer-events:none}',
        '.omni-gen-dur{position:absolute;bottom:.5rem;left:.6rem;z-index:3;padding:.12rem .45rem;border-radius:.4rem;font-size:.66rem;font-weight:700;color:#fff;background:rgba(0,0,0,.6);backdrop-filter:blur(4px)}',
        '.omni-gen-scrub{position:absolute;left:0;right:0;bottom:0;z-index:3;height:.2rem;background:rgba(255,255,255,.2)}',
        '.omni-gen-scrub-fill{display:block;height:100%;width:35%;background:#8b5cf6;animation:omniGenScrub 6s linear infinite}',
        '.omni-gen-lock{position:absolute;inset:0;z-index:4;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;border:none;cursor:pointer;color:#fff;background:radial-gradient(circle at center,rgba(10,8,22,.12),rgba(10,8,22,.5));transition:background .2s ease}',
        '.omni-gen-lock:hover{background:radial-gradient(circle at center,rgba(10,8,22,.04),rgba(10,8,22,.4))}',
        '.omni-gen-lock-play{width:3.4rem;height:3.4rem;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(139,92,255,.95);box-shadow:0 8px 28px rgba(139,92,255,.55);transition:transform .15s}',
        '.omni-gen-lock:hover .omni-gen-lock-play{transform:scale(1.08)}',
        '.omni-gen-lock-ico{position:absolute;top:.55rem;right:.6rem;width:1.4rem;height:1.4rem;opacity:.9}',
        '.omni-gen-lock-ico svg{width:1.4rem;height:1.4rem}',
        '.omni-gen-lock-label{font-size:.8rem;font-weight:700;text-shadow:0 1px 6px rgba(0,0,0,.6)}',
        '.omni-gen-preview-badge{position:absolute;top:.6rem;left:.6rem;z-index:5;padding:.2rem .55rem;border-radius:.45rem;font-size:.6rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#fff;background:linear-gradient(135deg,rgba(139,92,255,.95),rgba(109,40,217,.95));box-shadow:0 2px 8px rgba(0,0,0,.4)}',
        '.omni-gen-preview--ph{display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,rgba(46,16,101,.95),rgba(56,189,248,.25))}',
        '.omni-gen-ph-ico{font-size:3rem;filter:drop-shadow(0 6px 14px rgba(0,0,0,.4))}',
        '.omni-gen-preview--audio{display:flex;align-items:center;justify-content:center;background:linear-gradient(150deg,rgba(46,16,101,.92),rgba(15,23,42,.96))}',
        '.omni-gen-audio-ui{position:relative;z-index:2;display:flex;flex-direction:column;align-items:center;gap:.85rem;padding:1rem}',
        '.omni-gen-wave{display:flex;align-items:center;gap:3px;height:46px}',
        '.omni-gen-bar{width:3px;border-radius:99px;background:linear-gradient(to top,#8b5cf6,#c4b5fd);animation:omniGenWave 1s ease-in-out infinite alternate}',
        '.omni-gen-wave--music .omni-gen-bar{background:linear-gradient(to top,#38bdf8,#a78bfa)}',
        '.omni-gen-play{width:3rem;height:3rem;border-radius:50%;border:1px solid rgba(255,255,255,.2);background:rgba(139,92,255,.5);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .15s,background .15s;box-shadow:0 6px 20px rgba(139,92,255,.4)}',
        '.omni-gen-play:hover{transform:scale(1.08);background:rgba(139,92,255,.75)}',
        '.omni-gen-snip{font-size:.7rem;color:rgba(220,220,230,.85)}',
        '.omni-gen-snip b{color:#fff;font-weight:700}',
        '.omni-gen-preview--chat{display:flex;flex-direction:column;align-items:flex-start;justify-content:center;gap:.55rem;padding:1.2rem;aspect-ratio:auto;min-height:7rem;background:linear-gradient(150deg,rgba(46,16,101,.9),rgba(15,23,42,.95))}',
        '.omni-gen-chat-bubble{position:relative;max-width:100%;max-height:7.5rem;overflow:hidden;padding:.9rem 1.05rem;border-radius:1rem 1rem 1rem .35rem;background:rgba(139,92,255,.16);border:1px solid rgba(139,92,255,.26);color:#e4e4e7;font-size:.85rem;line-height:1.55}',
        '.omni-gen-chat-bubble p{margin:0}',
        '.omni-gen-chat-fade{position:absolute;left:0;right:0;bottom:0;height:2.6rem;background:linear-gradient(180deg,rgba(28,18,52,0),rgba(28,18,52,.96))}',
        '.omni-gen-chat-more{font-size:.78rem;font-weight:700;color:#c4b5fd;cursor:pointer}',
        '.omni-gen-chat-more:hover{color:#fff}',
        // ready — result file bar + claim CTA
        '.omni-gen-filebar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin:0 0 1rem;padding:.7rem .8rem;border-radius:.85rem;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07)}',
        '.omni-gen-file{display:flex;align-items:center;gap:.7rem;min-width:0}',
        '.omni-gen-file-ico{width:2.1rem;height:2.1rem;border-radius:.6rem;display:flex;align-items:center;justify-content:center;font-size:1.1rem;background:rgba(139,92,255,.16);border:1px solid rgba(139,92,255,.22);flex-shrink:0}',
        '.omni-gen-file-txt{min-width:0}',
        '.omni-gen-filename{font-size:.82rem;font-weight:700;color:#fafafa;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:13rem}',
        '.omni-gen-meta{font-size:.68rem;color:rgba(160,160,170,.85);margin:.15rem 0 0;font-variant-numeric:tabular-nums}',
        '.omni-gen-file-ok{flex-shrink:0;font-size:.62rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#86efac;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.28);padding:.25rem .5rem;border-radius:.4rem}',
        '#omni-gen-claim{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;padding:1rem 1.25rem;border:none;border-radius:.9rem;font-size:.98rem;font-weight:800;color:#fff;cursor:pointer;background:linear-gradient(135deg,#8b5cf6,#6d28d9);box-shadow:0 10px 30px rgba(139,92,255,.45);transition:transform .15s,box-shadow .15s;animation:omniGenPulse 2s ease-in-out infinite}',
        '#omni-gen-claim:hover{transform:translateY(-2px);box-shadow:0 14px 40px rgba(139,92,255,.6)}',
        '.omni-gen-claim-arrow{transition:transform .15s}',
        '#omni-gen-claim:hover .omni-gen-claim-arrow{transform:translateX(3px)}',
        '.omni-gen-claim-sub{text-align:center;font-size:.71rem;color:rgba(160,160,170,.82);margin:.65rem 0 0;line-height:1.4}',
        // state: processing vs ready
        '#omni-gen-card:not(.omni-gen-ready) .omni-gen-ready-only{display:none}',
        '#omni-gen-card.omni-gen-ready .omni-gen-proc-only{display:none}',
        '@keyframes omniGenSpin{to{transform:rotate(360deg)}}',
        '@keyframes omniGenFadeIn{from{opacity:0}to{opacity:1}}',
        '@keyframes omniGenCardIn{from{opacity:0;transform:translateY(14px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}',
        '@keyframes omniGenReveal{from{opacity:0;transform:scale(.96)}to{opacity:1;transform:scale(1)}}',
        '@keyframes omniGenWave{from{transform:scaleY(.4)}to{transform:scaleY(1)}}',
        '@keyframes omniGenFlow{to{background-position:200% 0}}',
        '@keyframes omniGenScrub{from{width:8%}to{width:96%}}',
        '@keyframes omniGenPulse{0%,100%{box-shadow:0 10px 30px rgba(139,92,255,.45)}50%{box-shadow:0 10px 42px rgba(139,92,255,.7)}}',
        '@keyframes omniGenPop{0%{transform:scale(0)}60%{transform:scale(1.3)}100%{transform:scale(1)}}',
        '@keyframes omniGenPing{0%{box-shadow:0 0 0 0 rgba(34,197,94,.5)}70%{box-shadow:0 0 0 .4rem rgba(34,197,94,0)}100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}}'
      ].join('');
      document.head.appendChild(style);
    }

    var modal = null;

    function ensureModal() {
      if (modal) return modal;
      var firstModel = (STUDIO_MODELS[studio.key] || STUDIO_MODELS.video)[0];
      modal = document.createElement('div');
      modal.id = 'omni-gen-overlay';
      modal.innerHTML =
        '<div id="omni-gen-card">' +
          '<div class="omni-gen-glow"></div>' +
          '<button type="button" id="omni-gen-x" aria-label="Close">&times;</button>' +
          '<div class="omni-gen-inner">' +
            '<div class="omni-gen-badge"><span class="omni-gen-badge-dot"></span><span>' + studio.icon + ' ' + studio.label + '</span><span class="omni-gen-badge-sep"></span><span id="omni-gen-eng-tag">OmniRogue Engine</span></div>' +
            '<div class="omni-gen-head">' +
              '<div class="omni-gen-icon" id="omni-gen-ico">' + studio.icon + '</div>' +
              '<div class="omni-gen-head-txt"><p id="omni-gen-status">Generating your ' + meta.noun + '…</p><p id="omni-gen-sub">Spinning up the render pipeline</p></div>' +
            '</div>' +
            '<div id="omni-gen-prompt" class="omni-gen-prompt"></div>' +
            '<div class="omni-gen-proc-only">' +
              '<div class="omni-gen-progress"><div id="omni-gen-fill" class="omni-gen-fill"></div></div>' +
              '<div class="omni-gen-progress-row"><span id="omni-gen-stage-lbl">Queued</span><span id="omni-gen-pct">0%</span></div>' +
              '<div id="omni-gen-stages" class="omni-gen-stages"></div>' +
              '<p class="omni-gen-eng-note">Ensemble of <b id="omni-gen-eng-model">' + firstModel + '</b> &middot; <span id="omni-gen-elapsed">0.0s</span> &middot; secure GPU cluster</p>' +
            '</div>' +
            '<div class="omni-gen-ready-only">' +
              '<div id="omni-gen-preview-slot"></div>' +
              '<div class="omni-gen-filebar"><div class="omni-gen-file"><span class="omni-gen-file-ico">' + studio.icon + '</span><div class="omni-gen-file-txt"><p id="omni-gen-filename" class="omni-gen-filename">render.' + (meta.file || 'mp4') + '</p><p id="omni-gen-meta" class="omni-gen-meta"></p></div></div><span class="omni-gen-file-ok">Ready</span></div>' +
              '<button type="button" id="omni-gen-claim">' + meta.cta + ' <span class="omni-gen-claim-arrow">→</span></button>' +
              '<p class="omni-gen-claim-sub">Saved to your library &middot; unlock to watch, download &amp; remove the watermark &middot; cancel anytime</p>' +
            '</div>' +
          '</div>' +
        '</div>';
      document.body.appendChild(modal);
      modal.querySelector('#omni-gen-claim').addEventListener('click', goClaim);
      modal.querySelector('#omni-gen-x').addEventListener('click', closeModal);
      modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') closeModal();
      });
      return modal;
    }

    function closeModal() {
      if (!modal) return;
      modal.style.display = 'none';
      document.body.style.overflow = '';
      teardownPreview();
    }

    // Any gated control inside the result (lock overlay, play button, "read more")
    // routes the visitor straight to checkout — the moment desire peaks.
    function wirePreview(slot) {
      slot.querySelectorAll('[data-claim]').forEach(function (el) {
        el.addEventListener('click', function (e) { e.preventDefault(); goClaim(); });
      });
    }

    function teardownPreview() {
      var slot = modal && modal.querySelector('#omni-gen-preview-slot');
      if (slot) {
        var v = slot.querySelector('video'); if (v) { try { v.pause(); } catch (e) {} }
        var a = slot.querySelector('audio'); if (a) { try { a.pause(); } catch (e) {} }
      }
    }

    var processing = false;
    var tickTimer = null, elapsedTimer = null, engTimer = null;
    var okCheckSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

    function clearTimers() {
      if (tickTimer) { clearInterval(tickTimer); tickTimer = null; }
      if (elapsedTimer) { clearInterval(elapsedTimer); elapsedTimer = null; }
      if (engTimer) { clearInterval(engTimer); engTimer = null; }
    }

    function runGenerationFlow() {
      if (processing || !canGenerate()) return;
      processing = true;
      generateBtns.forEach(function (btn) { btn.disabled = true; });

      var m = ensureModal();
      var card = m.querySelector('#omni-gen-card');
      var statusEl = m.querySelector('#omni-gen-status');
      var subEl = m.querySelector('#omni-gen-sub');
      var icoEl = m.querySelector('#omni-gen-ico');
      var slot = m.querySelector('#omni-gen-preview-slot');
      var fill = m.querySelector('#omni-gen-fill');
      var pctEl = m.querySelector('#omni-gen-pct');
      var stageLbl = m.querySelector('#omni-gen-stage-lbl');
      var stagesEl = m.querySelector('#omni-gen-stages');
      var engModelEl = m.querySelector('#omni-gen-eng-model');
      var elapsedEl = m.querySelector('#omni-gen-elapsed');
      var promptEl = m.querySelector('#omni-gen-prompt');
      var models = STUDIO_MODELS[studio.key] || STUDIO_MODELS.video;

      clearTimers();

      // Pick the realistic output length for this run.
      genDur = Math.round(rnd(meta.durMin || 6, meta.durMax || 12));

      // Reset to the processing state.
      card.classList.remove('omni-gen-ready');
      icoEl.classList.remove('omni-gen-icon--ok');
      icoEl.textContent = studio.icon;

      var pt = promptText();
      if (pt) {
        promptEl.style.display = '';
        promptEl.innerHTML = '<span class="omni-gen-prompt-q">“</span>' +
          escapeHtml(pt.length > 140 ? pt.slice(0, 140) + '…' : pt) +
          '<span class="omni-gen-prompt-q">”</span>';
      } else {
        promptEl.style.display = 'none';
      }

      statusEl.textContent = 'Generating your ' + meta.noun + '…';
      subEl.textContent = 'Spinning up the render pipeline';
      stagesEl.innerHTML = renderStages(-1);
      fill.style.width = '0%';
      pctEl.textContent = '0%';
      stageLbl.textContent = stages[0];
      slot.innerHTML = '';
      card.scrollTop = 0;
      m.style.display = 'flex';
      document.body.style.overflow = 'hidden';

      var t0 = Date.now();
      var total = rnd(4600, 6200);    // ms — believable render time
      var frames = Math.round(rnd(96, 168));
      var pct = 0;

      elapsedEl.textContent = '0.0s';
      elapsedTimer = setInterval(function () {
        elapsedEl.textContent = ((Date.now() - t0) / 1000).toFixed(1) + 's';
      }, 100);

      var em = 0;
      engModelEl.textContent = models[0];
      engTimer = setInterval(function () {
        em = (em + 1) % models.length;
        engModelEl.textContent = models[em];
      }, 700);

      tickTimer = setInterval(function () {
        var elapsed = Date.now() - t0;
        var target = Math.min(98, (elapsed / total) * 100);
        // Ease toward target with a little organic jitter so it never looks linear.
        pct += Math.max(0.4, (target - pct) * 0.18 + Math.random() * 0.8);
        if (pct > 99) pct = 99;
        var shown = Math.min(99, Math.floor(pct));
        fill.style.width = shown + '%';
        pctEl.textContent = shown + '%';

        var si = Math.min(stages.length - 1, Math.floor((shown / 100) * stages.length));
        stageLbl.textContent = stages[si];
        stagesEl.innerHTML = renderStages(si);
        if (studio.kind === 'video' && si >= 2 && si <= 3) {
          subEl.textContent = 'Rendering frame ' + Math.min(frames, Math.max(1, Math.round(shown / 100 * frames))) + ' / ' + frames;
        } else {
          subEl.textContent = stages[si];
        }

        if (elapsed >= total && shown >= 98) finish();
      }, 70);

      function finish() {
        clearTimers();
        fill.style.width = '100%';
        pctEl.textContent = '100%';
        stagesEl.innerHTML = renderStages(stages.length);
        stageLbl.textContent = 'Complete';

        card.classList.add('omni-gen-ready');
        icoEl.classList.add('omni-gen-icon--ok');
        icoEl.innerHTML = okCheckSvg;
        statusEl.innerHTML = 'Your ' + meta.noun + ' is ready <span class="omni-gen-spark">✨</span>';
        subEl.textContent = 'It turned out incredible — unlock to watch & download in full quality.';

        var preview = collectPreview();
        if (studio.kind === 'chat') preview.kind = 'chat';
        slot.innerHTML = buildPreviewHTML(preview);
        wirePreview(slot);

        var fn = m.querySelector('#omni-gen-filename');
        var mt = m.querySelector('#omni-gen-meta');
        if (fn) fn.textContent = buildFilename();
        if (mt) mt.textContent = buildMetaLine();

        processing = false;
        syncGenerateState();
      }
    }

    generateBtns.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        if (btn.disabled || !canGenerate()) return;
        e.preventDefault();
        e.stopPropagation();
        runGenerationFlow();
      });
    });
  })();

  // ===== Create Studio conversion boosters =====
  // Shared studio detection + model list for the dropdown / chip / toast features.
  (function initStudioConversion() {
    var path = window.location.pathname;
    var isCreate = path.indexOf('/create') >= 0 || /create(video|-[a-z]+)\.(?:html|php)/.test(path);
    if (!isCreate) return;

    var checkoutUrl = omniRegisterCheckoutUrl();

    var STUDIO_DEF = {
      video: { key: 'video', label: 'AI Video', verb: 'generated a video', models: ['Seedance 2.0', 'Veo 3.1', 'Kling 3', 'Kling 3.0', 'Jimeng'] },
      image: { key: 'image', label: 'AI Image', verb: 'generated an image', models: ['GPT Image 2', 'Nano Banana 2', 'Nano Banana', 'Flux Pro', 'Ideogram 3.0'] },
      upscale: { key: 'upscale', label: 'AI Upscale', verb: 'upscaled an image', models: ['Real-ESRGAN', 'Topaz AI', 'Magnific', 'GFPGAN', 'CodeFormer'] },
      audio: { key: 'audio', label: 'AI Audio', verb: 'created a voiceover', models: ['ElevenLabs', 'Multilingual v2', 'Turbo v2.5', 'Rachel', 'Josh'] },
      music: { key: 'music', label: 'AI Music', verb: 'made a track', models: ['Suno V5', 'Udio', 'Mureka', 'MiniMax Music', 'Stable Audio'] },
      omnireels: { key: 'omnireels', label: 'OmniReels', verb: 'made a reel', models: ['OmniReels Studio', 'Kling 3', 'Veo 3.1', 'Seedance 2.0', 'CapCut AI'] },
      podcast: { key: 'podcast', label: 'AI Podcast', verb: 'produced a podcast', models: ['ElevenLabs Podcast', 'PlayHT', 'Resemble AI', 'MiniMax Speech', 'Suno Instrumentals'] },
      voice: { key: 'voice', label: 'Voice Agents', verb: 'deployed a voice agent', models: ['ElevenLabs Conversational', 'GPT-4o Realtime', 'Gemini Live', 'PlayHT 2.0', 'Resemble AI'] },
      chat: { key: 'chat', label: 'AI Chat', verb: 'started a chat', models: ['Gemini 2.5 Flash', 'GPT-5.5', 'Claude 4.7', 'DeepSeek R1', 'Llama 4'] }
    };
    function detectStudio() {
      if (path.indexOf('create-upscale') >= 0) return STUDIO_DEF.upscale;
      if (path.indexOf('create-image') >= 0) return STUDIO_DEF.image;
      if (path.indexOf('create-audio') >= 0) return STUDIO_DEF.audio;
      if (path.indexOf('create-music') >= 0) return STUDIO_DEF.music;
      if (path.indexOf('create-omnireels') >= 0) return STUDIO_DEF.omnireels;
      if (path.indexOf('create-podcast') >= 0) return STUDIO_DEF.podcast;
      if (path.indexOf('create-voice-agents') >= 0) return STUDIO_DEF.voice;
      if (path.indexOf('create-ai-chat') >= 0) return STUDIO_DEF.chat;
      return STUDIO_DEF.video;
    }
    var studio = detectStudio();

    function goCheckout() {
      var url = omniRegisterCheckoutUrl();
      if (url) window.location.href = url;
    }

    // Shared styles for studio dropdowns, exit popup and social proof toast.
    if (!document.getElementById('omni-conv-styles')) {
      var s = document.createElement('style');
      s.id = 'omni-conv-styles';
      s.textContent = [
        // dropdown shell (matches .omni-library-menu language)
        '.omni-dd{position:fixed;z-index:9998;min-width:14rem;max-width:20rem;max-height:60vh;overflow-y:auto;padding:.4rem;border-radius:1rem;border:1px solid rgba(139,92,255,.22);background:linear-gradient(165deg,rgba(22,16,42,.99) 0%,rgba(10,8,20,.99) 100%);box-shadow:0 24px 60px rgba(0,0,0,.6),0 0 40px rgba(139,92,255,.1),inset 0 1px 0 rgba(255,255,255,.05);opacity:0;transform:translateY(6px);pointer-events:none;transition:opacity .16s ease,transform .16s ease}',
        '.omni-dd.omni-dd-open{opacity:1;transform:translateY(0);pointer-events:auto}',
        '.omni-dd-head{padding:.5rem .65rem .4rem;font-size:.6rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(161,161,170,.85);border-bottom:1px solid rgba(255,255,255,.06);margin-bottom:.25rem}',
        '.omni-dd-item{display:flex;align-items:center;gap:.6rem;width:100%;text-align:left;padding:.55rem .65rem;border:none;background:none;border-radius:.6rem;color:#e4e4e7;font-size:.82rem;font-weight:500;cursor:pointer;transition:background .14s ease}',
        '.omni-dd-item:hover{background:rgba(139,92,255,.12)}',
        '.omni-dd-item .omni-dd-check{width:1rem;flex-shrink:0;color:#a78bfa;font-size:.85rem;text-align:center;opacity:0}',
        '.omni-dd-item.omni-dd-sel .omni-dd-check{opacity:1}',
        '.omni-dd-item .omni-dd-name{flex:1;min-width:0}',
        '.omni-dd-lock{font-size:.6rem;font-weight:700;letter-spacing:.04em;padding:.12rem .4rem;border-radius:.35rem;color:#fcd34d;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.25);flex-shrink:0;display:inline-flex;align-items:center;gap:.2rem}',
        '.omni-dd-upgrade{display:flex;align-items:center;gap:.55rem;width:100%;text-align:left;margin-top:.3rem;padding:.65rem;border:none;border-radius:.7rem;cursor:pointer;color:#fff;font-size:.8rem;font-weight:700;background:linear-gradient(135deg,#8b5cf6,#6d28d9);box-shadow:0 6px 20px rgba(139,92,255,.35)}',
        '.omni-dd-upgrade:hover{filter:brightness(1.08)}',
        '.omni-dd-upgrade svg{width:1rem;height:1rem;flex-shrink:0}',
        // exit popup (mirrors omni-lib-upgrade)
        '#omni-exit-overlay{position:fixed;inset:0;z-index:100000;display:none;align-items:center;justify-content:center;padding:1.25rem;background:rgba(4,4,10,.85);backdrop-filter:blur(12px);animation:omniGenFadeIn .3s ease}',
        '#omni-exit-card{position:relative;max-width:30rem;width:100%;border-radius:1.5rem;overflow:hidden;border:1px solid rgba(139,92,255,.25);background:linear-gradient(160deg,rgba(28,18,52,.98) 0%,rgba(10,8,20,.99) 100%);box-shadow:0 32px 80px rgba(0,0,0,.7),0 0 60px rgba(139,92,255,.14),inset 0 1px 0 rgba(255,255,255,.06)}',
        '.omni-exit-banner{position:relative;height:7rem;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,rgba(139,92,255,.55),rgba(56,189,248,.35))}',
        '.omni-exit-banner-emoji{font-size:3rem;filter:drop-shadow(0 6px 14px rgba(0,0,0,.4))}',
        '.omni-exit-inner{position:relative;padding:1.5rem 1.6rem 1.4rem;text-align:center}',
        '.omni-exit-kicker{display:inline-flex;align-items:center;gap:.4rem;font-size:.66rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#fcd34d;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.28);padding:.3rem .7rem;border-radius:999px;margin-bottom:.85rem}',
        '#omni-exit-title{font-size:1.45rem;font-weight:800;color:#fafafa;margin:0;line-height:1.25}',
        '#omni-exit-sub{font-size:.9rem;color:rgba(212,212,216,.92);margin:.6rem 0 0;line-height:1.5}',
        '.omni-exit-price{display:flex;align-items:baseline;justify-content:center;gap:.5rem;margin:1.1rem 0}',
        '.omni-exit-price .now{font-size:2rem;font-weight:800;color:#c4b5fd}',
        '.omni-exit-price .per{font-size:.85rem;color:rgba(161,161,170,.9)}',
        '#omni-exit-cta{display:block;width:100%;padding:1rem 1.25rem;border:none;border-radius:.9rem;font-size:1rem;font-weight:800;color:#fff;cursor:pointer;background:linear-gradient(135deg,#8b5cf6,#6d28d9);box-shadow:0 10px 30px rgba(139,92,255,.45);transition:transform .15s,box-shadow .15s;animation:omniExitPulse 2s ease-in-out infinite}',
        '#omni-exit-cta:hover{transform:translateY(-2px);box-shadow:0 14px 38px rgba(139,92,255,.55)}',
        '.omni-exit-foot{font-size:.72rem;color:rgba(161,161,170,.8);margin:.75rem 0 0}',
        '#omni-exit-close{position:absolute;top:.7rem;right:.7rem;z-index:2;width:2rem;height:2rem;border:none;border-radius:.5rem;background:rgba(0,0,0,.35);color:#fff;cursor:pointer;font-size:1.2rem;line-height:1}',
        '#omni-exit-close:hover{background:rgba(0,0,0,.55)}',
        '@keyframes omniExitPulse{0%,100%{box-shadow:0 10px 30px rgba(139,92,255,.45)}50%{box-shadow:0 10px 40px rgba(139,92,255,.7)}}',
        // social proof toast
        '#omni-proof{position:fixed;left:1rem;bottom:1rem;z-index:9990;max-width:20rem;display:flex;align-items:center;gap:.7rem;padding:.7rem .85rem;border-radius:.85rem;border:1px solid rgba(139,92,255,.2);background:linear-gradient(160deg,rgba(24,17,44,.96),rgba(12,9,22,.98));box-shadow:0 16px 40px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.05);cursor:pointer;opacity:0;transform:translateY(12px);transition:opacity .35s ease,transform .35s ease}',
        '#omni-proof.omni-proof-show{opacity:1;transform:translateY(0)}',
        '.omni-proof-dot{width:.55rem;height:.55rem;border-radius:50%;background:#22c55e;box-shadow:0 0 0 0 rgba(34,197,94,.5);animation:omniProofPing 1.6s ease-out infinite;flex-shrink:0}',
        '.omni-proof-body{min-width:0}',
        '.omni-proof-text{font-size:.78rem;color:#e4e4e7;line-height:1.3;margin:0}',
        '.omni-proof-text b{color:#fafafa;font-weight:700}',
        '.omni-proof-sub{font-size:.66rem;color:rgba(161,161,170,.85);margin:.15rem 0 0}',
        '@keyframes omniProofPing{0%{box-shadow:0 0 0 0 rgba(34,197,94,.5)}70%{box-shadow:0 0 0 .5rem rgba(34,197,94,0)}100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}}',
        '@media (max-width:640px){#omni-proof{left:.6rem;right:.6rem;bottom:5.5rem;max-width:none}}'
      ].join('');
      document.head.appendChild(s);
    }

    var lockSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';

    // ---- Generic dropdown manager (one open at a time) ----
    var openDd = null;
    function closeDd() {
      if (openDd) { openDd.menu.classList.remove('omni-dd-open'); openDd = null; }
    }
    document.addEventListener('click', function (e) {
      if (openDd && !openDd.menu.contains(e.target) && !openDd.trigger.contains(e.target)) closeDd();
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDd(); });
    window.addEventListener('resize', closeDd);
    window.addEventListener('scroll', closeDd, true);

    function positionMenu(trigger, menu) {
      var r = trigger.getBoundingClientRect();
      menu.style.visibility = 'hidden';
      menu.style.display = 'block';
      var mh = menu.offsetHeight;
      var mw = menu.offsetWidth;
      var top = r.bottom + 8;
      // open upward if it would overflow the viewport bottom (composer sits low)
      if (top + mh > window.innerHeight - 8) top = Math.max(8, r.top - mh - 8);
      var left = r.left;
      if (left + mw > window.innerWidth - 8) left = Math.max(8, window.innerWidth - mw - 8);
      menu.style.top = Math.round(top) + 'px';
      menu.style.left = Math.round(left) + 'px';
      menu.style.visibility = '';
      menu.style.display = '';
    }

    function attachDropdown(trigger, build) {
      if (trigger.getAttribute('data-omni-dd') === '1') return;
      trigger.setAttribute('data-omni-dd', '1');
      trigger.style.cursor = 'pointer';
      var menu = document.createElement('div');
      menu.className = 'omni-dd';
      document.body.appendChild(menu);
      build(menu);
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (openDd && openDd.menu === menu) { closeDd(); return; }
        closeDd();
        positionMenu(trigger, menu);
        menu.classList.add('omni-dd-open');
        openDd = { menu: menu, trigger: trigger };
      });
    }

    // ---- 1. Model picker dropdown ----
    (function initModelDropdown() {
      var freeCount = 2; // first couple selectable free; rest are premium-locked
      function setLabel(trigger, name) {
        // update the visible model name span without nuking icons
        var spans = trigger.querySelectorAll('span');
        for (var i = 0; i < spans.length; i++) {
          var sp = spans[i];
          // skip badge spans (Premium / New) and the chevron
          if (/premium|new/i.test(sp.textContent.trim()) || !sp.textContent.trim()) continue;
          if (sp.children.length === 0) { sp.textContent = name; return; }
        }
      }
      function buildMenu(trigger, currentName) {
        return function (menu) {
          var html = '<div class="omni-dd-head">Model · ' + studio.label + '</div>';
          studio.models.forEach(function (name, i) {
            var sel = name === currentName ? ' omni-dd-sel' : '';
            var premium = i >= freeCount;
            html += '<button type="button" class="omni-dd-item' + sel + '" data-model="' + name + '" data-premium="' + (premium ? '1' : '0') + '">' +
              '<span class="omni-dd-check">✓</span>' +
              '<span class="omni-dd-name">' + name + '</span>' +
              (premium ? '<span class="omni-dd-lock">' + lockSvg + 'PRO</span>' : '') +
            '</button>';
          });
          html += '<button type="button" class="omni-dd-upgrade" data-upgrade="1">' + lockSvg + 'Upgrade to see 140+ more models</button>';
          menu.innerHTML = html;
          menu.addEventListener('click', function (e) {
            var up = e.target.closest('[data-upgrade]');
            if (up) { goCheckout(); return; }
            var item = e.target.closest('.omni-dd-item');
            if (!item) return;
            if (item.getAttribute('data-premium') === '1') { goCheckout(); return; }
            menu.querySelectorAll('.omni-dd-item').forEach(function (el) { el.classList.remove('omni-dd-sel'); });
            item.classList.add('omni-dd-sel');
            setLabel(trigger, item.getAttribute('data-model'));
            closeDd();
          });
        };
      }

      var triggers = [];
      // mobile model button
      var mob = document.querySelector('button[aria-label="Choose model"]');
      if (mob) triggers.push(mob);
      // Scope desktop detection to the composer (the prompt bar), NOT the whole
      // page — Featured/Discover cards also contain model names in their captions.
      var ta = document.querySelector('textarea[maxlength="2000"]') ||
        document.querySelector('main textarea');
      var composer = null;
      if (ta) {
        composer = ta;
        for (var c = 0; c < 12 && composer && composer.parentElement; c++) {
          composer = composer.parentElement;
          // stop at the rounded prompt-bar container that holds the toolbar
          if (composer.querySelector && composer.querySelector('[class*="order-4"]')) break;
        }
      }
      var allModels = studio.models;
      if (composer) {
        composer.querySelectorAll('button').forEach(function (btn) {
          if (btn === mob) return;
          var label = btn.textContent.replace(/\s+/g, ' ').trim();
          // a compact model chip inside the composer that contains a model name and
          // isn't the Generate/options/send action. Composer scoping prevents the
          // Featured/Discover caption cards (which also name models) from matching.
          var hasModel = allModels.some(function (m) { return label.indexOf(m) >= 0; });
          if (!hasModel) return;
          if (/generate|create agent|options|send|expand/i.test(label)) return;
          if (label.length > 60) return; // exclude long caption buttons
          triggers.push(btn);
        });
      }
      // de-dupe
      var seen = [];
      triggers.forEach(function (trigger) {
        if (seen.indexOf(trigger) >= 0) return;
        seen.push(trigger);
        var current = studio.models[0];
        var lab = trigger.textContent.replace(/\s+/g, ' ').trim();
        studio.models.forEach(function (m) { if (lab.indexOf(m) >= 0) current = m; });
        attachDropdown(trigger, buildMenu(trigger, current));
      });
    })();

    // ---- 2. Toolbar option chips (Quality / Resolution / Duration / Aspect / Count) ----
    (function initChipDropdowns() {
      // label-keyword -> { head, free:[], premium:[] }
      var OPTION_SETS = [
        { match: /quality/i, head: 'Quality', free: ['Standard'], premium: ['High', 'Ultra HD', 'Cinematic'] },
        { match: /^(480p|720p|1080p|2k|4k|resolution)/i, head: 'Resolution', free: ['480p', '720p'], premium: ['1080p', '2K', '4K'] },
        { match: /^(\d+s|duration)$/i, head: 'Duration', free: ['5s', '10s'], premium: ['15s', '20s', '30s'] },
        { match: /^(\d+:\d+|aspect)/i, head: 'Aspect ratio', free: ['16:9', '1:1', '9:16'], premium: ['21:9', '4:3', '3:2'] },
        { match: /count/i, head: 'Count', free: ['1×', '2×'], premium: ['4×', '6×', '8×'] }
      ];

      function setChipLabel(trigger, value) {
        // the chip's last meaningful text span holds the value
        var spans = trigger.querySelectorAll('span');
        for (var i = spans.length - 1; i >= 0; i--) {
          var sp = spans[i];
          if (sp.children.length === 0 && sp.textContent.trim() && !/^(quality|count)$/i.test(sp.textContent.trim())) {
            sp.textContent = value; return;
          }
        }
      }

      function buildChipMenu(trigger, set, current) {
        return function (menu) {
          var html = '<div class="omni-dd-head">' + set.head + '</div>';
          set.free.forEach(function (v) {
            var sel = v === current ? ' omni-dd-sel' : '';
            html += '<button type="button" class="omni-dd-item' + sel + '" data-val="' + v + '" data-premium="0"><span class="omni-dd-check">✓</span><span class="omni-dd-name">' + v + '</span></button>';
          });
          set.premium.forEach(function (v) {
            html += '<button type="button" class="omni-dd-item" data-val="' + v + '" data-premium="1"><span class="omni-dd-check">✓</span><span class="omni-dd-name">' + v + '</span><span class="omni-dd-lock">' + lockSvg + 'PRO</span></button>';
          });
          html += '<button type="button" class="omni-dd-upgrade" data-upgrade="1">' + lockSvg + 'Unlock all options — upgrade</button>';
          menu.innerHTML = html;
          menu.addEventListener('click', function (e) {
            if (e.target.closest('[data-upgrade]')) { goCheckout(); return; }
            var item = e.target.closest('.omni-dd-item');
            if (!item) return;
            if (item.getAttribute('data-premium') === '1') { goCheckout(); return; }
            menu.querySelectorAll('.omni-dd-item').forEach(function (el) { el.classList.remove('omni-dd-sel'); });
            item.classList.add('omni-dd-sel');
            setChipLabel(trigger, item.getAttribute('data-val'));
            closeDd();
          });
        };
      }

      document.querySelectorAll('button[aria-haspopup="menu"]').forEach(function (btn) {
        // skip the top-nav Library trigger (handled elsewhere)
        if (/^library/i.test(btn.textContent.replace(/\s+/g, ' ').trim())) return;
        if (btn.getAttribute('data-omni-dd') === '1') return;
        var label = btn.textContent.replace(/\s+/g, ' ').trim();
        var set = null;
        for (var i = 0; i < OPTION_SETS.length; i++) {
          if (OPTION_SETS[i].match.test(label)) { set = OPTION_SETS[i]; break; }
        }
        if (!set) return;
        var current = set.free[0];
        set.free.concat(set.premium).forEach(function (v) { if (label.indexOf(v) >= 0) current = v; });
        attachDropdown(btn, buildChipMenu(btn, set, current));
      });
    })();

    // ---- 3. Exit-intent popup (once per session) ----
    (function initExitPopup() {
      var KEY = 'omniExitShown';
      try { if (sessionStorage.getItem(KEY)) return; } catch (e) {}
      var shown = false;

      var overlay = document.createElement('div');
      overlay.id = 'omni-exit-overlay';
      overlay.innerHTML =
        '<div id="omni-exit-card">' +
          '<button type="button" id="omni-exit-close" aria-label="Close">&times;</button>' +
          '<div class="omni-exit-banner"><span class="omni-exit-banner-emoji">🍌</span></div>' +
          '<div class="omni-exit-inner">' +
            '<span class="omni-exit-kicker">⏳ Limited-time launch offer</span>' +
            '<p id="omni-exit-title">Claim 250 Free Gens of Nano Banana</p>' +
            '<p id="omni-exit-sub">Don\'t leave empty-handed — start creating across 140+ top models today.</p>' +
            '<div class="omni-exit-price"><span class="now">$14.99</span><span class="per">to get started</span></div>' +
            '<button type="button" id="omni-exit-cta">Claim now →</button>' +
            '<p class="omni-exit-foot">No commitment · cancel anytime</p>' +
          '</div>' +
        '</div>';
      document.body.appendChild(overlay);

      function show() {
        if (shown) return;
        shown = true;
        try { sessionStorage.setItem(KEY, '1'); } catch (e) {}
        overlay.style.display = 'flex';
      }
      function hide() { overlay.style.display = 'none'; }

      overlay.querySelector('#omni-exit-cta').addEventListener('click', goCheckout);
      overlay.querySelector('#omni-exit-close').addEventListener('click', hide);
      overlay.addEventListener('click', function (e) { if (e.target === overlay) hide(); });

      // Desktop: mouse leaves toward the top of the viewport / tab bar.
      document.addEventListener('mouseout', function (e) {
        if (shown) return;
        if (e.relatedTarget || e.toElement) return;
        if (e.clientY <= 4) show();
      });

      // Mobile fallback: hide-the-page (tab switch / back gesture) or fast scroll up near top.
      var lastY = window.scrollY || 0;
      var isTouch = ('ontouchstart' in window) || navigator.maxTouchPoints > 0;
      if (isTouch) {
        window.addEventListener('scroll', function () {
          if (shown) return;
          var y = window.scrollY || 0;
          if (y < 120 && lastY - y > 60) show();
          lastY = y;
        }, { passive: true });
        document.addEventListener('visibilitychange', function () {
          if (document.visibilityState === 'hidden') {
            // don't show now (page hidden); arm so it shows on return is undesirable.
          }
        });
      }
    })();

    // ---- 4. Rotating social-proof toast (studio-aware) ----
    (function initSocialProof() {
      var firstNames = ['Maria', 'Jake', 'Aisha', 'Liam', 'Sofia', 'Noah', 'Chen', 'Priya', 'Marcus', 'Elena', 'Diego', 'Yuki'];
      var places = ['Texas', 'London', 'Toronto', 'Berlin', 'Sydney', 'Lagos', 'Mumbai', 'São Paulo', 'Dubai', 'Tokyo', 'Austin', 'Madrid'];
      var times = ['just now', '1 min ago', '2 min ago', '4 min ago', '7 min ago'];

      function rand(a) { return a[Math.floor(Math.random() * a.length)]; }
      function pad(n) { return n.toLocaleString('en-US'); }

      function nextMessage() {
        var roll = Math.random();
        if (roll < 0.5) {
          var model = rand(studio.models);
          return { text: '<b>' + rand(firstNames) + ' in ' + rand(places) + '</b> ' + studio.verb + ' with ' + model, sub: rand(times) };
        }
        if (roll < 0.8) {
          return { text: '<b>' + pad(900 + Math.floor(Math.random() * 1500)) + ' creations</b> in the last hour', sub: 'across the OmniRogue studio' };
        }
        return { text: '<b>' + rand(firstNames) + '</b> upgraded to Unlimited', sub: rand(times) };
      }

      var toast = document.createElement('div');
      toast.id = 'omni-proof';
      toast.setAttribute('role', 'status');
      toast.innerHTML = '<span class="omni-proof-dot"></span><div class="omni-proof-body"><p class="omni-proof-text"></p><p class="omni-proof-sub"></p></div>';
      document.body.appendChild(toast);
      toast.addEventListener('click', goCheckout);

      var textEl = toast.querySelector('.omni-proof-text');
      var subEl = toast.querySelector('.omni-proof-sub');
      var visible = false;

      function render() {
        var m = nextMessage();
        textEl.innerHTML = m.text;
        subEl.textContent = m.sub;
      }
      function showToast() {var __sp=window.__OMNI_SOCIAL_PROOF||window.__OMNI_SOCIAL_DEFAULT||'on';if(String(__sp)==='off')return;
        render();
        toast.classList.add('omni-proof-show');
        visible = true;
        setTimeout(hideToast, 5200);
      }
      function hideToast() {
        toast.classList.remove('omni-proof-show');
        visible = false;
        setTimeout(showToast, 6000 + Math.random() * 5000);
      }
      // first toast a few seconds after load
      setTimeout(showToast, 5000);
    })();
  })();

  // Marketing pages — reveal framer-motion sections frozen at opacity:0
  if (/(?:home|about|gpt-library|prompt-library)\.(?:html|php)/.test(window.location.pathname)) {
    document.querySelectorAll('[style*="opacity"]').forEach(function (el) {
      var s = el.getAttribute('style') || '';
      if (/opacity:\s*0/.test(s)) {
        el.style.opacity = '1';
        el.style.transform = 'none';
      }
    });
  }

  // About page — CTA buttons (not anchor links in static copy)
  if (/about\.(html|php)/i.test(window.location.pathname)) {
    var aboutExt = omniPageExt();
    var aboutBase = omniBasePath();
    document.querySelectorAll('button, [tabindex="0"]').forEach(function (el) {
      var text = el.textContent.replace(/\s+/g, ' ').trim();
      if (/Explore AI Agents/i.test(text)) {
        el.style.cursor = 'pointer';
        el.addEventListener('click', function () {
          window.location.href = aboutBase + '/createvideo.' + aboutExt;
        });
      }
      if (/View Pricing/i.test(text)) {
        el.style.cursor = 'pointer';
        el.addEventListener('click', function () {
          window.location.href = omniCheckoutUrl();
        });
      }
    });
  }
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
