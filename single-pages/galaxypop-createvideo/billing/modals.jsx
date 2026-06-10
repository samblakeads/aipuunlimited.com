/* global React, Icon, Button, TICKER */
// ============================================================
// OmniRogue Checkout — modals + recent-purchase ticker
// ============================================================
const { useState: useStateM, useEffect: useEffectM } = React;

// Map a plan to its KowboyKit offer token (the "Product ID" the KK funnel sells).
//   one-time  -> lifetime ($399)   year -> creatoryearly ($99)   else -> creatormonthly ($14.99)
function omniPlanToken(plan) {
  if (!plan) return "creatormonthly";
  if (plan.token) return plan.token;
  if (plan.per === "one-time") return "lifetime";
  if (plan.per === "year" || plan.per === "yr" || plan.per === "annual") return "creatoryearly";
  return "creatormonthly";
}

// Resolve the KK offer link for a token. The popup runs inside an <iframe>; on a
// KK-formatted page its own data-flow-config injects window.__KK_OFFER_LINKS, but
// fall back to the parent page's wiring (and finally the register/billing link).
function omniOfferLink(token) {
  function pick(scope) {
    if (!scope) return "";
    var links = scope.__KK_OFFER_LINKS || {};
    var url = links[token];
    if (url && url !== "#") return url;
    if (token === "lifetime" && links.lifetimeplan && links.lifetimeplan !== "#") return links.lifetimeplan;
    return scope.__KK_REGISTER_BILLING || scope.__KK_REGISTER_CHECKOUT || "";
  }
  var url = pick(window);
  if (!url) { try { if (window.parent && window.parent !== window) url = pick(window.parent); } catch (e) {} }
  return url || "";
}

// ---------- Secure checkout modal ----------
function CheckoutModal({ plan, onClose }) {
  const [stage, setStage] = useStateM("form"); // form | done
  useEffectM(() => { if (plan) setStage("form"); }, [plan]);
  if (!plan) return null;
  const priceLabel = plan.per === "one-time" ? `$${plan.price} one-time` : `$${plan.price}/${plan.per || "mo"}`;
  const goCheckout = () => {
    const url = omniOfferLink(omniPlanToken(plan));
    if (url) { (window.top || window).location.href = url; return; }
    setStage("done"); // demo fallback when no KK offer wiring is present
  };
  return (
    <div className="scrim" onClick={onClose}>
      <div className="modal" onClick={e => e.stopPropagation()}>
        <span className="modal-x" onClick={onClose}><Icon name="X" size={18} /></span>
        {stage === "form" ? (
          <div className="modal-pad">
            <h3><Icon name="Lock" size={18} style={{ color: "var(--accent)" }} /> Secure checkout</h3>
            <div className="sumline">
              <span className="pn">{plan.name}</span>
              <span className="pp">{priceLabel}</span>
            </div>
            <div className="field">
              <label>Email</label>
              <input type="email" placeholder="you@studio.com" />
            </div>
            <div className="field">
              <label>Card number</label>
              <input inputMode="numeric" placeholder="1234 1234 1234 1234" />
            </div>
            <div className="field-row">
              <div className="field"><label>Expiry</label><input placeholder="MM / YY" /></div>
              <div className="field"><label>CVC</label><input placeholder="123" /></div>
            </div>
            <Button variant={plan.per === "one-time" ? "gold" : "primary"} size="lg" full onClick={goCheckout}>
              {plan.cta || `Pay ${priceLabel}`} <Icon name="ArrowRight" size={17} />
            </Button>
            <div className="lock-note"><Icon name="ShieldCheck" size={14} /> 256-bit encrypted · 30-day money-back guarantee</div>
          </div>
        ) : (
          <div className="success-wrap">
            <div className="success-ring"><Icon name="Check" size={34} /></div>
            <h3 style={{ justifyContent: "center" }}>You're in! 🎉</h3>
            <p className="muted" style={{ margin: "12px auto 20px", maxWidth: 320 }}>
              Welcome to OmniRogue. Your <b style={{ color: "var(--fg)" }}>{plan.name}</b> is active — every frontier model is unlocked. Let's create something.
            </p>
            <Button variant="primary" size="lg" full onClick={onClose}>Start creating <Icon name="Sparkles" size={16} /></Button>
          </div>
        )}
      </div>
    </div>
  );
}

// ---------- Switching-to-monthly warning ----------
const MONTHLY_LOSSES = [
  "Priority customer support",
  "10× faster generation speeds",
  "Exclusive early access to new AI models",
  "Priority API & MCP queue",
];
function MonthlyWarning({ open, saveYr, onKeep, onSwitch }) {
  if (!open) return null;
  return (
    <div className="scrim" onClick={onKeep}>
      <div className="modal wide" onClick={e => e.stopPropagation()}>
        <span className="modal-x" onClick={onKeep}><Icon name="X" size={18} /></span>
        <div className="modal-pad">
          <h3><Icon name="TriangleAlert" size={20} style={{ color: "var(--cyan)" }} /> Switching to monthly</h3>
          <p className="muted" style={{ marginTop: 10 }}>You're about to switch to the monthly plan and lose these annual-subscriber benefits:</p>
          <ul className="warn-list">
            {MONTHLY_LOSSES.map(l => (
              <li key={l}><Icon className="ck" name="X" size={17} />{l}</li>
            ))}
            <li className="loss"><Icon className="ck" name="X" size={17} />Total savings of ${(saveYr || 0).toLocaleString()} per year</li>
          </ul>
          <div className="warn-actions">
            <Button variant="ghost" onClick={onKeep}>Keep annual plan</Button>
            <Button variant="dark" onClick={onSwitch}>Switch to monthly</Button>
          </div>
        </div>
      </div>
    </div>
  );
}

// ---------- Unlimited vs Premium Credits explainer ----------
const UNLIM_LIST = ["AI chat & reasoning", "Writing, blogs & emails", "Scripts, ad & sales copy", "Summaries & PDF analysis", "Research & rewriting", "Translations & outlines", "Business documents", "Everyday productivity"];
const CREDIT_LIST = ["Advanced video generation", "Premium image generation", "Voiceovers & podcasts", "Reels & UGC-style clips", "Premium model chats", "Agent Slides", "Faster, higher-powered processing", "Higher-quality outputs"];
function InfoModal({ open, onClose }) {
  if (!open) return null;
  return (
    <div className="scrim" onClick={onClose}>
      <div className="modal info-modal" onClick={e => e.stopPropagation()}>
        <span className="modal-x" onClick={onClose}><Icon name="X" size={18} /></span>
        <div className="modal-pad">
          <h3><Icon name="Sparkles" size={18} style={{ color: "var(--accent)" }} /> How creating on OmniRogue works</h3>
          <p className="muted" style={{ marginTop: 8, fontSize: 14 }}>OmniRogue gives you two ways to create — unlimited everyday AI, plus Premium Credits for advanced media.</p>
          <div className="info-cards">
            <div className="info-card green">
              <div className="ic-head"><Icon name="Infinity" size={18} /> Unlimited Daily Generations</div>
              <div className="ic-sub">Create freely on supported everyday AI workflows — no credit counter.</div>
              <ul>{UNLIM_LIST.map(x => <li key={x}><Icon name="Check" size={13} /> {x}</li>)}</ul>
            </div>
            <div className="info-card gold">
              <div className="ic-head"><Icon name="Gem" size={17} /> Premium Credits</div>
              <div className="ic-sub">Your monthly creation power for higher-cost, advanced media tools.</div>
              <ul>{CREDIT_LIST.map(x => <li key={x}><Icon name="Check" size={13} /> {x}</li>)}</ul>
            </div>
          </div>
          <div className="info-together"><b>How they work together:</b> Unlimited Daily Generations are your creative playground for daily work; Premium Credits are your production power for heavier outputs like video, images, voice & premium models.</div>
          <div className="info-fair"><Icon name="ShieldCheck" size={13} /> Unlimited is for normal, individual human use. Automated, shared, resold or abusive usage may be slowed, limited or paused to keep OmniRogue fast for everyone.</div>
          <Button variant="secondary" size="md" full onClick={onClose} style={{ marginTop: 16 }}>Got it</Button>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { CheckoutModal, MonthlyWarning, InfoModal });
