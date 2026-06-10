/* KK-June8 — Chrome.js override shim
 *
 * Runs AFTER /checkouts-omni/_chrome/chrome.js and intercepts:
 *   - "Continue to Plans" CTA in trust popups   → window.__KK_CHECKOUT_URL
 *   - Affiliate "Plans" CTA                     → checkout (sales) or scroll-to-plans (checkout)
 *   - Affiliate "Dashboard" CTA                 → checkout (sales) or scroll-to-plans (checkout)
 *   - Header "Claim Offer" CTA                  → checkout
 *   - Nav "Pricing" link                        → checkout (sales) / stay (checkout)
 *
 * Designed to be sticky over re-renders: uses capture-phase listeners at
 * document.body, so it wins even if the underlying chrome rebinds.
 *
 * Required globals (set by host page before this script runs):
 *   window.__KK_CHECKOUT_URL   — partner checkout URL with ?clickid preserved
 *   window.__KK_IS_CHECKOUT    — boolean: true on /checkout.php, false on /index.php
 *   window.__KK_OFFER_LINKS    — map of {creatormonthly: '...', lifetime: '...', ...}
 */
(function () {
  'use strict';

  var checkoutUrl = window.__KK_CHECKOUT_URL || '#';
  var isCheckout  = !!window.__KK_IS_CHECKOUT;

  function go(href) {
    if (!href || href === '#') return;
    window.location.href = href;
  }

  /* Intercept any click that bubbles up. Use the capture phase so we beat
     chrome.js handlers attached on document. */
  document.addEventListener('click', function (e) {
    var target = e.target;

    /* 1) Trust-popup CTA — chrome.js scrolls to plans; on sales pages we
          actually want to send them to checkout. */
    var trustCta = target.closest && target.closest('[data-or-modal-cta]');
    if (trustCta) {
      if (isCheckout) return;  // checkout page: let chrome scroll-to-plans
      e.preventDefault();
      e.stopPropagation();
      go(checkoutUrl);
      return;
    }

    /* 2) Affiliate modal — Dashboard + Plans both go to checkout on sales,
          and "Plans" should scroll on checkout page. */
    var affPlans = target.closest && target.closest('[data-or-aff-plans]');
    var affDash  = target.closest && target.closest('[data-or-aff-dashboard]');
    if (affPlans || affDash) {
      if (isCheckout) {
        // For checkout page, "Become an affiliate → Plans" stays as scroll-to-plans (default).
        // "Dashboard" however should NOT leave the page — also send to plans for now.
        if (affDash) {
          e.preventDefault();
          e.stopPropagation();
          // Try to scroll to plans grid (chrome.js exposes data attribute)
          var plansEl =
            document.querySelector('[data-or-anchor="plans"]') ||
            document.querySelector('[data-checkout-prototype]') ||
            document.querySelector('.pyp-grid');
          if (plansEl && plansEl.scrollIntoView) plansEl.scrollIntoView({ behavior: 'smooth' });
        }
        return;
      }
      e.preventDefault();
      e.stopPropagation();
      go(checkoutUrl);
      return;
    }
  }, true /* capture */);

  /* 3) Rewrite hrefs that linger pointing at fragments / dashboards / login.
        Done once on DOMContentLoaded so right-click "Open in new tab" still
        opens the checkout. */
  function rewriteHrefs() {
    if (isCheckout) return; // checkout page keeps in-page fragments
    var bad = document.querySelectorAll(
      'a[href="#pricing"], a[href="#plans"], a[href="#examples"], a[href="#studios"], ' +
      'a[href="#testimonials"], a[href="#faq"], a[href="#how"], a[href="#usecases"], a[href="#compare"]'
    );
    /* …but only the ones in the header / topbar / sticky-cta — leave anchor
       jumps inside the sales page body alone so users can navigate sections. */
    bad.forEach(function (a) {
      if (a.closest('.or-nav-wrap, .topbar, .sticky-cta, .or-nav-cta-cluster, .or-mobile-cta')) {
        a.setAttribute('href', checkoutUrl);
      }
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', rewriteHrefs);
  } else {
    rewriteHrefs();
  }
})();
