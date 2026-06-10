/* global React, Icon, Button, Pill, ModelTile, useCountdown, pad, MODELS, MARQUEE, TOP_MODELS, LIFETIME_HIGHLIGHTS, LIFETIME_INCLUDED, CREATOR_ACCESS, PAYMENTS, COMPARE_TOOLS, PRESS, TESTIMONIALS, FAQS */
// ============================================================
// OmniRogue Checkout — content sections
// ============================================================
const { useState: useStateC } = React;

// ---------- Header + countdown ----------
function CheckoutHeader({ hours, urgency, livecount }) {
  const t = useCountdown(hours);
  return (
    <div className="co-head">
      <div className="co-sale"><span className="spk">✦</span> JUNE FLASH SALE <span className="spk">✦</span></div>
      <p className="co-sub">Unlock <b style={{ color: "var(--fg)" }}>every frontier AI model</b> — text, image, video, voice &amp; music — under one membership.</p>
      <div className="co-livebar">
        <span className="co-chip"><span className="stars">★★★★★</span><b>4.9</b><span className="subtle">/5 · 265,000+ members</span></span>
        <span className="co-chip"><span className="livedot"></span><b>{livecount.toLocaleString()}</b><span className="subtle">creating right now</span></span>
      </div>
      {urgency && (
        <React.Fragment>
          <div className="cd-label">Hurry! Offer ends in:</div>
          <div className="cd-clock">
            {[["Hours", t.h], ["Mins", t.m], ["Secs", t.s]].map(([u, v]) => (
              <div className="cd-seg" key={u}><div className="num">{pad(v)}</div><div className="unit">{u}</div></div>
            ))}
          </div>
        </React.Fragment>
      )}
    </div>
  );
}

// ---------- Unlimited models "see list" (grouped) ----------
function UnlimitedModels({ onInfo }) {
  const [open, setOpen] = useStateC(false);
  return (
    <div className="um">
      <div className="um-head">
        <span className="um-title"><Icon name="Infinity" size={16} style={{ color: "var(--gold)" }} /> Unlimited on <b>140+ models</b></span>
        <span className="um-actions">
          <button className="um-see" onClick={() => setOpen(o => !o)}>{open ? "Hide list" : "See list"} <Icon name={open ? "ChevronUp" : "ChevronDown"} size={13} /></button>
          <button className="um-info" title="What does unlimited mean?" onClick={onInfo}><Icon name="Info" size={15} /></button>
        </span>
      </div>
      {open && (
        <div className="um-body">
          {TOP_MODELS.map(g => (
            <div className="um-group" key={g.group}>
              <div className="um-glabel">{g.group}</div>
              <div className="um-chips">
                {g.items.map(k => {
                  const m = MODELS[k];
                  return (
                    <span className="um-chip" key={k}><ModelTile m={m} size={20} /> {m.name}</span>
                  );
                })}
              </div>
            </div>
          ))}
          <div className="um-foot">+ 130 more models · new ones added weekly</div>
        </div>
      )}
    </div>
  );
}

// ---------- Lifetime card (gold founder deal) ----------
function LifetimeCard({ urgency, onBuy, onInfo, bare }) {
  return (
    <React.Fragment>
      {!bare && (
        <div className="lt-section-head">
          <div className="eyebrow">★ Founder-style deal · Best value</div>
          <h2>One payment. <span className="g">Lifetime access.</span></h2>
          <p>For people who already know they'll keep creating. Pay once today, skip every monthly bill from now on, and keep building forever.</p>
        </div>
      )}
      <div className="lt-card">
        <div className="lt-top">
          <div className="lt-title"><span>👑</span> Lifetime Access</div>
          <span className="best-deal">★ Best deal</span>
        </div>

        <div className="lt-price">
          <span className="strike was">$1,900</span>
          <span className="now">$399</span>
          <span className="off">80% OFF</span>
        </div>
        <div className="lt-perday">One-time payment · lifetime updates included · then <b style={{ color: "var(--fg-muted)" }}>$0/month, forever</b></div>

        <div className="lt-strip">
          {LIFETIME_HIGHLIGHTS.map((h, i) => (
            <div className="lt-strip-item" key={i}>
              <span className="si"><Icon name={h.icon} size={18} /></span>
              <span className="st">{h.text}</span>
            </div>
          ))}
        </div>

        <UnlimitedModels onInfo={onInfo} />

        <div className="lt-incl">
          <div className="lt-incl-label">Everything included — forever</div>
          {LIFETIME_INCLUDED.map((b, i) => (
            <div className="lt-incl-item" key={i}>
              <span className="ic"><Icon name={b.icon} size={17} /></span>
              <div>
                <div className="it">{b.title}</div>
                <div className="is">{b.sub}</div>
              </div>
            </div>
          ))}
        </div>

        {urgency && (
          <div className="scarcity">
            <div className="sc-top"><span className="l">Founding-price spots remaining</span><span className="r">43 / 200</span></div>
            <div className="sc-bar"><div className="sc-fill" style={{ width: "21.5%" }}></div></div>
            <div className="sc-note">Price jumps to <b>$699</b> when this batch sells out · 1,401 creators went Lifetime this month</div>
          </div>
        )}

        <div className="lt-bigcta">
          <button className="btn btn-goldgrad btn-lg btn-full" onClick={() => onBuy({ name: "OmniRogue Lifetime Access", price: "399", per: "one-time", cta: "Pay $399 once" })}>
            🔒 Secure Lifetime Access — $399 <Icon name="ArrowRight" size={18} />
          </button>
          <div className="lt-cta-sub">or 3× $133 · 0% interest · one-time payment, no renewals</div>
        </div>

        <div className="lt-trust">
          <span><Icon name="ShieldCheck" size={14} className="ck" /> 30-day money-back guarantee</span>
          <span><Icon name="Lock" size={14} /> Secure Stripe checkout</span>
          <span><Icon name="Zap" size={14} /> Instant access</span>
        </div>
      </div>
    </React.Fragment>
  );
}

// ---------- Subscription toggle ----------
function SubToggle({ yearly, onChange }) {
  return (
    <React.Fragment>
      <div className="or-div"><span>OR</span></div>
      <div className="eyebrow sub-eyebrow" style={{ justifyContent: "center" }}>Choose a subscription plan</div>
      <div className={`toggle${yearly ? " yearly" : ""}`}>
        <span className="knob"></span>
        <button className={!yearly ? "on" : ""} onClick={() => onChange(false)}>Pay Monthly</button>
        <button className={yearly ? "on" : ""} onClick={() => onChange(true)}>
          Pay Yearly <Pill tone="save" className="save-tag" style={{ marginLeft: 6, position: "absolute", top: -10, right: 12 }}>Save $$$</Pill>
        </button>
      </div>
    </React.Fragment>
  );
}

// ---------- Creator access section renderer ----------
function CreatorSection({ sec, onInfo }) {
  if (sec.kind === "suite") {
    return (
      <div className="ca-box suite">
        <div className="ca-box-head"><span className="ca-suite-ic">✦</span> {sec.title}</div>
        {sec.rows.map((r, ri) => (
          <div className="ca-suite-row" key={ri}>
            <Icon className="ca-ck" name="Check" size={16} />
            <div className="ca-suite-main">
              <div className="ca-suite-name">{r.name}{r.info && <Icon className="ca-info" name="Info" size={12} onClick={onInfo} />}</div>
              <div className="ca-suite-sub">{r.sub}</div>
            </div>
            <div className="ca-badges">{r.badges.map((b, bi) => <span className={`ca-badge ${b.tone}`} key={bi}>{b.t}</span>)}</div>
          </div>
        ))}
      </div>
    );
  }
  if (sec.kind === "text") {
    return (
      <div className="ca-box text">
        <div className="ca-box-head"><span><Icon name="Infinity" size={15} style={{ color: "var(--green)", verticalAlign: "middle", marginRight: 6 }} />{sec.title}</span>{sec.info && <button className="ca-learn" title="What does unlimited mean?" onClick={onInfo}><Icon name="Info" size={15} /></button>}</div>
        {sec.rows.map((r, ri) => (
          <div className="ca-text-row" key={ri}>
            <Icon className="ca-ck" name="Check" size={15} />
            <span className="ca-text-name">{r.name}</span>
            <span className="ca-badge unlim">∞ Unlimited</span>
          </div>
        ))}
      </div>
    );
  }
  return (
    <div className="ca-box plain">
      <div className="ca-box-head"><span>{sec.title}{sec.note && <span className="ca-note"> · {sec.note}</span>}</span>{sec.info && <button className="ca-learn" title="What does unlimited mean?" onClick={onInfo}><Icon name="Info" size={15} /></button>}</div>
      {sec.rows.map((r, ri) => (
        <div className={`ca-row${r.access === "none" ? " off" : ""}`} key={ri}>
          <Icon className={r.access === "none" ? "ca-x" : "ca-ck"} name={r.access === "none" ? "X" : "Check"} size={16} />
          <span className="ca-name">{r.name}{r.info && <Icon className="ca-info" name="Info" size={12} onClick={onInfo} />}</span>
          {r.badge && <span className={`ca-badge ${r.badgeTone}`}>{r.badge}</span>}
        </div>
      ))}
    </div>
  );
}

// ---------- Creator (blue subscription) card ----------
function PriceCard({ yearly, onInfo, onBuy }) {
  const now = yearly ? "8.25" : "14.99";
  const [dollars, cents] = now.split(".");
  const credits = yearly ? "12,000" : "1,000";
  const ctaPrice = yearly ? "$8.25/mo" : "$14.99/mo";
  return (
    <div className={`price-card blue${yearly ? " annual" : ""} cc-card`}>
      <div className="cc-head">
        <div className="eyebrow cc-eyebrow">◆ Solo Creator</div>
        <span className="cc-flex-chip"><Icon name="RefreshCw" size={12} /> Cancel anytime</span>
      </div>
      <div className="cc-name">Creator</div>
      <div className="cc-desc">For solo founders, marketers, and creators who want one AI workspace that just works.</div>

      <div className="cc-top-grid">
        <div className="cc-price-block">
          <div className="cc-price">
            <span className="dollar">$</span>
            <span className="now">{dollars}</span>
            <span className="cents">.{cents}</span>
            <span className="per">/mo</span>
          </div>
          <div className="cc-price-sub">
            <span className="strike was">$29/mo</span>
            {yearly && <span className="cc-save">SAVE 45%</span>}
          </div>
          <div className="cc-billed">{yearly
            ? <React.Fragment>billed annually at <b style={{ color: "var(--fg)" }}>$99/year</b></React.Fragment>
            : "Flexible monthly · cancel anytime"}</div>
        </div>
        <div className="cc-credits-box">
          <span className="bolt">⚡</span>
          <div>
            <div className="cct">{credits} Premium Credits <Icon className="info" name="Info" size={13} style={{ verticalAlign: "middle", color: "var(--fg-faint)", cursor: "pointer" }} onClick={onInfo} /></div>
            <div className="ccs">{yearly ? "Per year · 1,000/mo" : "Per month"} · across 140+ video &amp; image models.</div>
          </div>
        </div>
      </div>

      <div className="cc-incl-label">What's included in every Creator plan</div>
      <div className="cc-access cc-grid">
        <div className="cc-acol">
          <CreatorSection sec={CREATOR_ACCESS[0]} onInfo={onInfo} />
          <CreatorSection sec={CREATOR_ACCESS[1]} onInfo={onInfo} />
        </div>
        <div className="cc-acol">
          <CreatorSection sec={CREATOR_ACCESS[2]} onInfo={onInfo} />
          <CreatorSection sec={CREATOR_ACCESS[3]} onInfo={onInfo} />
        </div>
      </div>

      <button className="btn btn-bluefill btn-lg btn-full" onClick={() => onBuy(yearly
        ? { name: "OmniRogue Creator · Annual", price: "99", per: "year", cta: "Pay $99/yr" }
        : { name: "OmniRogue Creator · Monthly", price: "14.99", per: "mo", cta: "Pay $14.99/mo" })}>
        Start Creating — {ctaPrice} <Icon name="ArrowRight" size={17} />
      </button>
      <div className="cc-foot">
        <span className="mb"><Icon name="ShieldCheck" size={14} /> 14-day money-back</span>
        <span><Icon name="Lock" size={13} /> Secure checkout</span>
        <span>★ 4.9/5 · 50k+ reviews</span>
      </div>
    </div>
  );
}

// ---------- Press logos ----------
function PressRow() {
  return (
    <div className="press">
      <div className="press-label">As featured in</div>
      <div className="press-logos">
        {PRESS.map(p => <span key={p}>{p}</span>)}
      </div>
    </div>
  );
}

// ---------- Benefits grid ----------
function BenefitsGrid({ premium }) {
  return (
    <div className="sec">
      <h3 className="sec-title">Everything you'll get</h3>
      <div className="ben-grid">
        {BENEFITS.map(b => (
          <div className="ben" key={b.title}>
            <span className="ic-wrap" style={{ color: b.color }}><Icon name={b.icon} size={19} /></span>
            <div>
              <div className="b-title">{b.title}</div>
              <div className="b-body">{b.body}</div>
            </div>
          </div>
        ))}
        {premium && PREMIUM_BENEFITS.map(b => (
          <div className="ben premium" key={b.title}>
            <span className="ic-wrap"><Icon name={b.icon} size={19} /></span>
            <div>
              <div className="b-tag">PREMIUM</div>
              <div className="b-title">{b.title}</div>
              <div className="b-body">{b.body}</div>
            </div>
          </div>
        ))}
      </div>
      {premium
        ? <div className="ben-note">✓ Premium benefits unlock with <b>annual &amp; lifetime</b> plans</div>
        : <div className="ben-note">Switch to <b>annual</b> or go <b>lifetime</b> to unlock 4 premium benefits</div>}
    </div>
  );
}

// ---------- vs paying separately ----------
function CompareTools({ aipuMonthly = 24.92 }) {
  const total = COMPARE_TOOLS.reduce((s, t) => s + t.price, 0);
  const a = aipuMonthly;
  const saveMo = Math.round(total - a);
  return (
    <div className="sec">
      <h3 className="sec-title center">One membership replaces all of these</h3>
      <div className="compare">
        {COMPARE_TOOLS.map(t => (
          <div className="cmp-row" key={t.name}>
            <span className="nm"><Icon name="X" size={14} style={{ color: "var(--pink-hot)" }} />{t.name}</span>
            <span className="pr">${t.price}/mo</span>
          </div>
        ))}
        <div className="cmp-sum"><span className="nm">Paying separately</span><span className="pr">${total}/mo</span></div>
        <div className="cmp-aipu">
          <span className="nm"><Icon name="Check" size={18} style={{ color: "var(--accent)" }} />OmniRogue — all of it</span>
          <span className="pr">${a < 100 ? a.toFixed(2) : Math.round(a)}<small>/mo</small></span>
        </div>
        <div className="cmp-save">That's <b>${saveMo.toLocaleString()}+ saved every month</b> — over <b>${(saveMo * 12).toLocaleString()}/year</b>.</div>
      </div>
    </div>
  );
}

// ---------- Model marquee ----------
function ModelMarquee() {
  const track = [...MARQUEE, ...MARQUEE];
  return (
    <div className="sec">
      <h3 className="sec-title center">140+ premium models included</h3>
      <div className="mq-label">New models added weekly — automatically in your account</div>
      <div className="mq">
        <div className="mq-track">
          {track.map((k, i) => {
            const m = MODELS[k];
            return (
              <div className="mq-badge" key={i}>
                <ModelTile m={m} size={30} />
                <div><div className="mn">{m.name}</div><div className="ms">{m.sub}</div></div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}

// ---------- Testimonials ----------
function Testimonials() {
  return (
    <div className="sec">
      <h3 className="sec-title center">See what creators say about OmniRogue</h3>
      <div className="tcards">
        {TESTIMONIALS.map((t, i) => (
          <div className="tcard" key={i}>
            <div className="stars">★★★★★</div>
            <p className="q">“{t.quote}”</p>
            <p className="pull">{t.pull}</p>
            <div className="who">
              <span className="av">{t.name[0]}</span>
              <div><div className="nm">{t.name}</div><div className="rl">{t.role}</div></div>
            </div>
          </div>
        ))}
      </div>
      <div className="t-stat"><b>85%</b> of our members choose the Lifetime Pass</div>
    </div>
  );
}

// ---------- Guarantee band ----------
function GuaranteeBand() {
  return (
    <div className="sec">
      <div className="gtee-band">
        <span className="seal"><Icon name="ShieldCheck" size={26} /></span>
        <div>
          <div className="gt">30-day, no-questions-asked money-back guarantee</div>
          <div className="gb">Try OmniRogue risk-free. If it's not for you, email us within 30 days for a full refund — and keep everything you already created. Monthly &amp; yearly plans are covered for 14 days.</div>
        </div>
      </div>
    </div>
  );
}

// ---------- FAQ ----------
function FAQ() {
  const [open, setOpen] = useStateC(0);
  return (
    <div className="sec">
      <h3 className="sec-title">🤔 Have questions?</h3>
      {FAQS.map((f, i) => (
        <div className={`faq-item${open === i ? " open" : ""}`} key={i}>
          <div className="faq-q" onClick={() => setOpen(open === i ? -1 : i)}>
            {f.q}<Icon className="faq-chev" name="ChevronDown" size={20} />
          </div>
          <div className="faq-a">{f.a}</div>
        </div>
      ))}
    </div>
  );
}

// ---------- Final CTA + footer ----------
function FinalCTA({ onLifetime, onAnnual }) {
  return (
    <div className="final">
      <h2>Start creating with <span className="grad">every model</span> today</h2>
      <p className="co-sub" style={{ margin: "12px auto 0" }}>Lock in lifetime before the price rises — or start annual, risk-free.</p>
      <div className="ctas">
        <Button variant="gold" size="lg" onClick={onLifetime}><Icon name="Crown" size={17} /> Claim Lifetime — $399</Button>
        <Button variant="primary" size="lg" onClick={onAnnual}>Start Annual — $8.25/mo <Icon name="ArrowRight" size={17} /></Button>
      </div>
      <div className="reassure">
        <span>✓ 30-day guarantee on Lifetime</span>
        <span>✓ 14-day on monthly &amp; yearly</span>
        <span>✓ Cancel anytime</span>
      </div>
    </div>
  );
}

function Footer() {
  return (
    <div className="foot">
      <img src={(window.BILLING_LOGO || "assets/logo-omnirogue.png")} alt="OmniRogue" />
      <div className="links">
        <a href="#">Pricing</a><a href="#">FAQ</a><a href="#">Terms</a><a href="#">Privacy</a><a href="#">Contact</a>
      </div>
      <span className="cp">© 2026 OmniRogue Inc.</span>
    </div>
  );
}

Object.assign(window, {
  CheckoutHeader, LifetimeCard, SubToggle, PriceCard, PressRow, BenefitsGrid,
  CompareTools, ModelMarquee, Testimonials, GuaranteeBand, FAQ, FinalCTA, Footer,
});
