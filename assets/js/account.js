(function () {
  'use strict';

  var tabs = document.querySelectorAll('[data-auth-tab]');
  var panels = document.querySelectorAll('[data-auth-panel]');
  var alertEl = document.getElementById('auth-alert');

  function showAlert(msg, type) {
    if (!alertEl) return;
    alertEl.textContent = msg;
    alertEl.className = 'auth-alert show ' + (type || 'success');
  }

  function setMode(mode) {
    tabs.forEach(function (t) {
      t.classList.toggle('active', t.dataset.authTab === mode);
    });
    panels.forEach(function (p) {
      p.hidden = p.dataset.authPanel !== mode;
    });
    if (history.replaceState) {
      history.replaceState(null, '', '#/' + mode);
    }
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      setMode(tab.dataset.authTab);
    });
  });

  var hash = (location.hash || '').replace(/^#\/?/, '');
  if (hash === 'signup' || hash === 'signin') {
    setMode(hash);
  } else {
    setMode('signin');
  }

  document.querySelectorAll('[data-auth-form]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var mode = form.dataset.authForm;
      var email = form.querySelector('[name="email"]');
      if (!email || !email.value.trim()) {
        showAlert('Please enter a valid email address.', 'error');
        return;
      }
      if (mode === 'signup') {
        var agree = form.querySelector('[name="agree"]');
        if (agree && !agree.checked) {
          showAlert('Please accept the Terms of Service and Privacy Policy.', 'error');
          return;
        }
      }
      showAlert(
        mode === 'signup'
          ? 'Account created successfully. Redirecting to your workspace…'
          : 'Signed in successfully. Redirecting to your workspace…',
        'success'
      );
      setTimeout(function () {
        window.location.href = '/';
      }, 1800);
    });
  });
})();
