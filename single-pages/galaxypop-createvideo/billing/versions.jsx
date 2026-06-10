/* global React, Icon, useCountdown, pad, GRAND_STACK, GRAND_BONUSES, PAYMENTS, LifetimeCard, PriceCard */
// ============================================================
// OmniRogue Checkout — Lander version variants
//   VersionSwitcher · GrandSlamOffer (Hormozi) · MinimalLanding
// ============================================================
const { useState: useStateV } = React;
const fmt = n => "$" + n.toLocaleString();

const VERSIONS = [
  { id: "balanced", label: "Balanced", desc: "Lifetime + plan · full urgency" },
  { id: "grandslam", label: "Grand Slam", desc: "Hormozi value-stack offer" },
  { id: "minimal", label: "Minimal", desc: "Clean premium · two cards" },
];

function VersionSwitcher({ value, onChange }) {
  return (
    <div className="vswitch">
      <div className="vswitch-label">Pick a lander layout to preview</div>
      <div className="vswitch-seg">
        {VERSIONS.map(v => (
          <button key={v.id} className={`vseg${v.id === value ? " on" : ""}`} onClick={() => onChange(v.id)}>
            <span className="vseg-l">{v.label}</span>
            <span className="vseg-d">{v.desc}</span>
          </button>
        ))}
      </div>
    </div>
  );
}

const GS_LIFETIME = { name: "OmniRogue Lifetime Access", price: "399", per: "one-time", cta: "Pay $399 once" };

// ---------- Grand Slam offer (Hormozi) ----------
function GrandSlamOffer({ urgency, hours, onBuy }) {
  const t = useCountdown(hours);
  const stackTotal = GRAND_STACK.reduce((s, x) => s + x.value, 0);
  const bonusTotal = GRAND_BONUSES.reduce((s, x) => s + x.value, 0);
  const total = stackTotal + bonusTotal;
  const price = 399;
  const save = total - price;
  const pct = Math.round((save / total) * 100);
  return (
    <div className="gs">
      <div className="gs-hero">
        <div className="eyebrow gs-eyebrow">★ The Grand Slam Offer</div>
        <h1 className="gs-h1">Every frontier AI model, <span className="grad-gold">for life.</span><br />Never pay a monthly bill again.</h1>
        <p className="gs-sub">One payment today unlocks 140+ AI models forever — text, video, image, voice &amp; music — plus credits, three bonuses, and every future model. No subscription. No renewals. No catch.</p>
        {urgency && (
          <div className="gs-count">
            <span className="gs-count-lab"><Icon name="Timer" size={15} /> Founding price ends in</span>
            <div className="gs-count-segs">
              {[["h", t.h], ["m", t.m], ["s", t.s]].map(([u, v]) => <span className="gs-seg" key={u}>{pad(v)}<i>{u}</i></span>)}
            </div>
          </div>
        )}
      </div>

      <div className="gs-card">
        <div className="gs-card-label">Here's everything you get when you join today</div>
        <div className="gs-stack">
          {GRAND_STACK.map((r, i) => (
            <div className="gs-row" key={i}>
              <Icon className="gs-ck" name="Check" size={17} />
              <div className="gs-main"><div className="gs-l">{r.label}</div><div className="gs-s">{r.sub}</div></div>
              <div className="gs-v">{fmt(r.value)}</div>
            </div>
          ))}
        </div>

        <div className="gs-bonus-head">+ 3 fast-action bonuses when you join today</div>
        <div className="gs-stack">
          {GRAND_BONUSES.map((r) => (
            <div className="gs-row bonus" key={r.n}>
              <span className="gs-gift">🎁</span>
              <div className="gs-main"><div className="gs-l">Bonus #{r.n}: {r.label}</div><div className="gs-s">{r.sub}</div></div>
              <div className="gs-v">{fmt(r.value)}</div>
            </div>
          ))}
        </div>

        <div className="gs-total">
          <div className="gs-total-row"><span>Total real value</span><span className="strike gs-was">{fmt(total)}</span></div>
          <div className="gs-today">
            <span>Yours today, one time</span>
            <span className="gs-price grad-gold">$399</span>
          </div>
          <div className="gs-save">You save {fmt(save)} — that's {pct}% off</div>
        </div>

        {urgency && (
          <div className="scarcity gs-scar">
            <div className="sc-top"><span className="l">Founding-price seats remaining</span><span className="r">43 / 200</span></div>
            <div className="sc-bar"><div className="sc-fill" style={{ width: "21.5%" }}></div></div>
            <div className="sc-note">Price jumps to <b>$699</b> when these sell out · 1,401 creators went Lifetime this month</div>
          </div>
        )}

        <button className="btn btn-goldgrad btn-lg btn-full gs-cta" onClick={() => onBuy(GS_LIFETIME)}>
          🔒 Claim your lifetime deal — $399 <Icon name="ArrowRight" size={18} />
        </button>
        <div className="gs-installment">or 3× $133 · 0% interest · one-time payment, no renewals</div>

        <div className="gs-guarantee">
          <span className="seal2">30-DAY<br />CREATE<br />OR FREE</span>
          <div>
            <div className="gt">The “Create or it's free” guarantee</div>
            <div className="gb">Use OmniRogue for 30 days. If you don't create something you're proud of, email us for a 100% refund — and keep everything you made. The risk is entirely on us.</div>
          </div>
        </div>

        <div className="lt-trust">
          <span><Icon name="Lock" size={14} /> Secure Stripe checkout</span>
          {PAYMENTS.map(p => <span key={p} className="gs-pm">{p}</span>)}
        </div>
      </div>
    </div>
  );
}

// ---------- Minimal premium landing ----------
function MinimalLanding({ yearly, setYearly, onBuy, onInfo }) {
  return (
    <div className="min-wrap">
      <div className="min-hero">
        <div className="eyebrow" style={{ color: "var(--fg-subtle)", justifyContent: "center" }}>OmniRogue Membership</div>
        <h1 className="min-h1">Every frontier AI model.<br /><span className="grad">One membership.</span></h1>
        <p className="min-sub">140+ models for text, video, image, voice &amp; music — under one calm, premium membership. Go lifetime, or start monthly.</p>
        <div className="min-trust">
          <span><span className="stars">★★★★★</span> 4.9/5 · 265,000+ members</span>
          <span className="min-dot">·</span>
          <span>Featured in Forbes, TechCrunch &amp; WIRED</span>
        </div>
        <div className="min-toggle">
          <button className={!yearly ? "on" : ""} onClick={() => setYearly(false)}>Monthly</button>
          <button className={yearly ? "on" : ""} onClick={() => setYearly(true)}>Yearly · save 45%</button>
        </div>
      </div>
      <div className="min-cards">
        <LifetimeCard bare urgency={false} onBuy={onBuy} onInfo={onInfo} />
        <PriceCard yearly={yearly} onBuy={onBuy} onInfo={onInfo} />
      </div>
    </div>
  );
}

Object.assign(window, { VersionSwitcher, GrandSlamOffer, MinimalLanding });
