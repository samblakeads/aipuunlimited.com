/* global React, Icon, Button, useCountdown, pad */
// ============================================================
// OmniRogue Checkout — App shell (flash bar + sidebar)
// ============================================================
const { useState: useStateS } = React;

function FlashBar({ hours, urgency, onClaim }) {
  const t = useCountdown(hours);
  if (!urgency) {
    return (
      <div className="flashbar">
        <div className="flashbar-inner">
          <span className="fb-life">Lifetime Pass</span>
          <span className="muted">— every frontier AI model, one payment.</span>
          <button className="fb-cta" onClick={onClaim}>See the offer</button>
        </div>
      </div>
    );
  }
  return (
    <div className="flashbar">
      <div className="flashbar-inner">
        <b className="fb-deal">Pay once, get a <span className="fb-life">LIFETIME</span> deal forever — for only $399</b>
        <span className="fb-clock"><Icon className="ic" name="Clock" size={14} />{pad(t.h)}h {pad(t.m)}m {pad(t.s)}s</span>
        <button className="fb-cta" onClick={onClaim}>Claim now</button>
      </div>
    </div>
  );
}

function TopBar() {
  return (
    <header className="topbar">
      <img src={(window.BILLING_LOGO || "assets/logo-omnirogue.png")} alt="OmniRogue" />
      <div className="tb-right">
        <span className="tb-secure"><Icon name="ShieldCheck" size={15} /> Secure checkout</span>
        <span className="tb-link">Already a member? Log in</span>
        <span className="tb-av">S</span>
      </div>
    </header>
  );
}

Object.assign(window, { FlashBar, TopBar });
