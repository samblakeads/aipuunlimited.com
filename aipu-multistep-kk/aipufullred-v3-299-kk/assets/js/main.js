/* AIPU — interactivity (perf-optimized) */
(function () {
  'use strict';

  var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Reveal-on-scroll
  var revealEls = document.querySelectorAll('[data-reveal]');
  if ('IntersectionObserver' in window && revealEls.length) {
    var io = new IntersectionObserver(
      function (entries) {
        for (var i = 0; i < entries.length; i++) {
          var e = entries[i];
          if (e.isIntersecting) {
            e.target.classList.add('in');
            io.unobserve(e.target);
          }
        }
      },
      { threshold: 0.08, rootMargin: '0px 0px 200px 0px' }
    );
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  }

  // Hero composer typing animation — skip on reduced motion + only run when visible
  var fakeInput = document.querySelector('[data-fake-input]');
  if (fakeInput && !REDUCED) {
    var prompts = [
      'Cinematic drone shot of Tokyo at golden hour, anamorphic lens',
      'Editorial product photo of a glass perfume bottle, soft volumetric light',
      'Lo-fi 9:16 reel for a coffee brand, warm grain, slow zoom',
      'Talking head explainer with my AI avatar in a studio set',
      '60-second voiceover ad for a fitness app, hype tone, ElevenLabs voice',
      'Logo for an indie game studio called "Nova Drift" — vector, neon'
    ];
    var pi = 0, ci = 0, deleting = false, typingPaused = false, typingTimer = null;
    function typeTick() {
      if (typingPaused || document.hidden) {
        typingTimer = setTimeout(typeTick, 400);
        return;
      }
      var cur = prompts[pi];
      if (!deleting) {
        ci++;
        fakeInput.textContent = cur.slice(0, ci);
        if (ci >= cur.length) { deleting = true; typingTimer = setTimeout(typeTick, 1900); return; }
        typingTimer = setTimeout(typeTick, 28 + Math.random() * 50);
      } else {
        ci -= 2;
        if (ci <= 0) { ci = 0; deleting = false; pi = (pi + 1) % prompts.length; }
        fakeInput.textContent = cur.slice(0, ci);
        typingTimer = setTimeout(typeTick, 14);
      }
    }
    if ('IntersectionObserver' in window) {
      var typeIO = new IntersectionObserver(function (entries) {
        typingPaused = !entries[0].isIntersecting;
      }, { threshold: 0 });
      typeIO.observe(fakeInput);
    }
    typeTick();
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden && !typingTimer) typeTick();
    });
  }

  // Daily countdown — runs once per second only while tab visible
  var cdh = document.querySelector('[data-cd-h]');
  var cdm = document.querySelector('[data-cd-m]');
  var cds = document.querySelector('[data-cd-s]');
  if (cdh && cdm && cds) {
    var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
    var lastH = '', lastM = '', lastS = '';
    function update() {
      if (document.hidden) return;
      var now = new Date();
      var target = new Date(now);
      target.setHours(23, 59, 59, 999);
      var diff = Math.max(0, target - now);
      var h = Math.floor(diff / 3600000); diff -= h * 3600000;
      var m = Math.floor(diff / 60000); diff -= m * 60000;
      var s = Math.floor(diff / 1000);
      var ph = pad(h), pm = pad(m), ps = pad(s);
      if (ph !== lastH) { cdh.textContent = ph; lastH = ph; }
      if (pm !== lastM) { cdm.textContent = pm; lastM = pm; }
      if (ps !== lastS) { cds.textContent = ps; lastS = ps; }
    }
    update();
    setInterval(update, 1000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) update(); });
  }

  // Active-spot scarcity counter
  var spotEl = document.querySelector('[data-spots]');
  if (spotEl) {
    var n = parseInt(spotEl.textContent, 10) || 47;
    setInterval(function () {
      if (document.hidden) return;
      if (Math.random() < 0.18 && n > 8) { n -= 1; spotEl.textContent = n; }
    }, 9000);
  }

  // Live signups ticker — skip on reduced motion
  var liveEl = document.querySelector('[data-live-signups]');
  if (liveEl && !REDUCED) {
    var names = [
      'Maya from Austin, TX', 'Daniel from Toronto', 'Aisha from London',
      'Marcus from Brooklyn', 'Priya from Singapore', 'Liam from Dublin',
      'Carla from São Paulo', 'Yuki from Osaka', 'Eli from Tel Aviv',
      'Naledi from Cape Town', 'Rafael from Madrid', 'Anika from Toronto',
      'Tomás from Mexico City', 'Isabella from Sydney', 'Jonas from Berlin'
    ];
    function show() {
      if (document.hidden) return;
      var name = names[Math.floor(Math.random() * names.length)];
      liveEl.innerHTML = '<span class="dot"></span><span><b>' + name + '</b> just joined</span>';
      liveEl.classList.add('show');
      setTimeout(function () { liveEl.classList.remove('show'); }, 4200);
    }
    setTimeout(show, 4500);
    setInterval(show, 11000 + Math.random() * 6000);
  }

  // Smooth-scroll for anchor links
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href^="#"]');
    if (!a) return;
    var id = a.getAttribute('href');
    if (id.length < 2) return;
    var t = document.querySelector(id);
    if (!t) return;
    e.preventDefault();
    t.scrollIntoView({ behavior: REDUCED ? 'auto' : 'smooth', block: 'start' });
  });

  // Password strength meter
  var pwd = document.querySelector('[data-password]');
  if (pwd) {
    var meterEl = document.querySelector('[data-pw-meter]');
    pwd.addEventListener('input', function () {
      if (!meterEl) return;
      var v = pwd.value || '';
      var score = 0;
      if (v.length >= 8) score++;
      if (/[A-Z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      meterEl.style.width = (score / 4) * 100 + '%';
      meterEl.style.background =
        score <= 1 ? '#ff5b6c'
        : score === 2 ? '#ffb547'
        : score === 3 ? '#a3ff66'
        : 'linear-gradient(90deg,#a3ff66,#38e1ff)';
    });
  }
})();
