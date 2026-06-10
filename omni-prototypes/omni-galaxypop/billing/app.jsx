/* global React, ReactDOM, useTweaks, TweaksPanel, TweakSection, TweakColor, TweakToggle, TweakSlider, TweakButton, TweakRadio,
   FlashBar, TopBar, CheckoutHeader, LifetimeCard, SubToggle, PriceCard, PressRow, BenefitsGrid,
   CompareTools, ModelMarquee, Testimonials, GuaranteeBand, FAQ, FinalCTA, Footer,
   CheckoutModal, MonthlyWarning, InfoModal, ExitIntentModal, VersionSwitcher, GrandSlamOffer, MinimalLanding, Icon */
// ============================================================
// OmniRogue Checkout — App (full-screen checkout, no sidebar)
// ============================================================
const { useState, useEffect, useRef } = React;

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "version": "balanced",
  "accent": "#FF4FD8",
  "urgency": true,
  "countdownHours": 18
}/*EDITMODE-END*/;

const LIFETIME_PLAN = { name: "OmniRogue Lifetime Pass", price: "399", per: "one-time", cta: "Pay $399 once" };

function App() {
  const [t, setTweak] = useTweaks(TWEAK_DEFAULTS);
  const [yearly, setYearly] = useState(true);          // starts on YEARLY
  const [checkout, setCheckout] = useState(null);
  const [warnOpen, setWarnOpen] = useState(false);
  const [infoOpen, setInfoOpen] = useState(false);
  const [exitOpen, setExitOpen] = useState(false);
  const [live, setLive] = useState(1320);
  const exitShown = useRef(false);

  // exit-intent: fire once when the cursor leaves through the top of the window
  useEffect(() => {
    const onLeave = (e) => {
      if (exitShown.current) return;
      if (e.clientY <= 0 && (!e.relatedTarget && !e.toElement)) {
        exitShown.current = true;
        setExitOpen(true);
      }
    };
    document.addEventListener("mouseout", onLeave);
    return () => document.removeEventListener("mouseout", onLeave);
  }, []);

  useEffect(() => {
    const id = setInterval(() => setLive(v => Math.max(1180, Math.min(1480, v + Math.round((Math.random() - 0.45) * 14)))), 3200);
    return () => clearInterval(id);
  }, []);

  const accentStyle = {
    "--accent": t.accent,
    "--accent-soft": `color-mix(in srgb, ${t.accent} 16%, transparent)`,
  };

  const requestMonthly = () => { if (yearly) setWarnOpen(true); else setYearly(false); };
  const version = t.version || "balanced";

  let body;
  if (version === "grandslam") {
    body = <GrandSlamOffer urgency={t.urgency} hours={t.countdownHours} onBuy={setCheckout} />;
  } else if (version === "minimal") {
    body = <MinimalLanding yearly={yearly} setYearly={(y) => (y ? setYearly(true) : requestMonthly())} onBuy={setCheckout} onInfo={() => setInfoOpen(true)} />;
  } else {
    body = (
      <React.Fragment>
        <CheckoutHeader hours={t.countdownHours} urgency={t.urgency} livecount={live} />
        <LifetimeCard urgency={t.urgency} onBuy={setCheckout} onInfo={() => setInfoOpen(true)} />
        <SubToggle yearly={yearly} onChange={(y) => (y ? setYearly(true) : requestMonthly())} />
        <PriceCard yearly={yearly} onInfo={() => setInfoOpen(true)} onBuy={setCheckout} />
        <PressRow />
        <CompareTools aipuMonthly={8.25} />
        <ModelMarquee />
        <Testimonials />
        <FAQ />
        <FinalCTA
          onLifetime={() => setCheckout(LIFETIME_PLAN)}
          onAnnual={() => { setYearly(true); setCheckout({ name: "OmniRogue Creator · Annual", price: "99", per: "year", cta: "Pay $99/yr" }); }}
        />
      </React.Fragment>
    );
  }

  return (
    <div className="app" style={accentStyle}>
      <FlashBar hours={t.countdownHours} urgency={t.urgency} onClaim={() => setCheckout(LIFETIME_PLAN)} />
      <TopBar />

      <main className="app-main">
        <div className={`checkout-col v-${version}`}>
          {body}
          <Footer />
        </div>
      </main>

      <CheckoutModal plan={checkout} onClose={() => setCheckout(null)} />
      <MonthlyWarning open={warnOpen} saveYr={80.88} onKeep={() => setWarnOpen(false)} onSwitch={() => { setWarnOpen(false); setYearly(false); }} />
      <InfoModal open={infoOpen} onClose={() => setInfoOpen(false)} />
      <ExitIntentModal open={exitOpen} onClose={() => setExitOpen(false)} onClaim={() => { setExitOpen(false); setCheckout({ name: "OmniRogue Creator + 500 free gens", price: "14.99", per: "mo", cta: "Claim — $14.99/mo" }); }} />

      <TweaksPanel title="Tweaks">
        <TweakSection label="Lander layout" />
        <TweakRadio label="Version" value={version}
          options={["balanced", "grandslam", "minimal"]}
          onChange={(v) => setTweak("version", v)} />
        <TweakSection label="Brand accent" />
        <TweakColor label="Accent color" value={t.accent}
          options={["#FF4FD8", "#8B5CFF", "#35D7FF", "#2BFFB3"]}
          onChange={(v) => setTweak("accent", v)} />
        <TweakSection label="Urgency & trust" />
        <TweakToggle label="Urgency elements" value={t.urgency} onChange={(v) => setTweak("urgency", v)} />
        <TweakSlider label="Countdown start" value={t.countdownHours} min={1} max={48} unit="h" onChange={(v) => setTweak("countdownHours", v)} />
        <TweakButton label="Preview exit-intent popup" onClick={() => setExitOpen(true)} />
      </TweaksPanel>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById("root")).render(<App />);
