/* global React */
// ============================================================
// OmniRogue Checkout — Primitives & Data
// Self-contained atoms + all product content for the checkout page.
// Exports to window for the other babel scripts.
// ============================================================
const { useState: useStateD, useEffect: useEffectD, useRef: useRefD } = React;

// ---------- Lucide icon (CDN, render-safe) ----------
function Icon({ name, size = 18, stroke = 1.75, style, ...rest }) {
  const L = window.lucide || {};
  let data = L[name] || (L.icons && L.icons[name]);
  let children = [];
  if (Array.isArray(data)) {
    if (data[0] === "svg" && Array.isArray(data[2])) children = data[2];
    else children = data;
  }
  return React.createElement(
    "svg",
    { width: size, height: size, viewBox: "0 0 24 24", fill: "none", stroke: "currentColor",
      strokeWidth: stroke, strokeLinecap: "round", strokeLinejoin: "round",
      style: { flex: "0 0 auto", ...style }, ...rest },
    children.map((c, i) => React.createElement(c[0], { ...c[1], key: i }))
  );
}

// ---------- Button ----------
function Button({ variant = "primary", size = "md", children, icon, iconRight, full, className = "", ...rest }) {
  return (
    <button className={`btn btn-${variant} btn-${size}${full ? " btn-full" : ""} ${className}`} {...rest}>
      {icon && <Icon name={icon} size={size === "sm" ? 15 : 18} />}
      {children}
      {iconRight && <Icon name={iconRight} size={size === "sm" ? 15 : 18} />}
    </button>
  );
}

// ---------- Pill ----------
function Pill({ tone = "neutral", children, icon, style }) {
  return (
    <span className={`pill pill-${tone}`} style={style}>
      {icon && <Icon name={icon} size={13} />}
      {children}
    </span>
  );
}

// ---------- Model tile (colored glyph stand-in for provider logo) ----------
function ModelTile({ m, size = 34 }) {
  return (
    <span className="model-tile" style={{
      width: size, height: size, borderRadius: size * 0.3, background: m.bg,
      color: m.fg || "#fff", fontSize: size * 0.46,
      ...(m.border ? { border: "1px solid var(--hairline-strong)" } : {})
    }}>{m.glyph}</span>
  );
}

// ---------- countdown hook ----------
function useCountdown(hours = 18) {
  const [t, setT] = useStateD({ h: hours, m: 21, s: 38 });
  useEffectD(() => { setT({ h: hours, m: 21, s: 38 }); }, [hours]);
  useEffectD(() => {
    const id = setInterval(() => {
      setT(p => {
        let { h, m, s } = p;
        s--; if (s < 0) { s = 59; m--; } if (m < 0) { m = 59; h--; } if (h < 0) { h = 23; }
        return { h, m, s };
      });
    }, 1000);
    return () => clearInterval(id);
  }, []);
  return t;
}
const pad = n => String(n).padStart(2, "0");

// ============================================================
// DATA
// ============================================================
const MODELS = {
  gemini:  { name: "Gemini 3.1 Pro", sub: "Google · Text", glyph: "✦", bg: "linear-gradient(135deg,#4F8BF0,#22D3EE)" },
  gpt:     { name: "GPT-5.4",        sub: "OpenAI · Text", glyph: "◆", bg: "#10A37F" },
  kimi:    { name: "Kimi K2.5",      sub: "Moonshot · Text", glyph: "K", bg: "#1F1F2E", border: true },
  grok:    { name: "Grok 4.1 Fast",  sub: "xAI · Text", glyph: "✕", bg: "#0A0A0A", border: true },
  deepseek:{ name: "DeepSeek V3.2",  sub: "Text", glyph: "≋", bg: "#3B6FE0" },
  seedance:{ name: "Seedance 2.0",   sub: "Video", glyph: "▸", bg: "linear-gradient(135deg,#D946C2,#8B5CF6)" },
  kling:   { name: "Kling 3.0",      sub: "Video", glyph: "K", bg: "#E0457A" },
  veo:     { name: "Veo 3.1",        sub: "Google · Video", glyph: "◉", bg: "linear-gradient(135deg,#4F8BF0,#34D399)" },
  pixverse:{ name: "PixVerse V6",    sub: "Video", glyph: "✺", bg: "linear-gradient(135deg,#8B5CF6,#EC4899)" },
  wan:     { name: "Wan 2.7",        sub: "Video", glyph: "彎", bg: "#2563EB" },
  ltx:     { name: "LTX Video 2.0",  sub: "Video", glyph: "⊿", bg: "#0A0A0A", border: true },
  nano:    { name: "Nano Banana 2",  sub: "Google · Image", glyph: "◗", bg: "#F5C24B", fg: "#1a1206" },
  seedream:{ name: "Seedream 4.5",   sub: "Image", glyph: "✦", bg: "linear-gradient(135deg,#F77FA8,#D946C2)" },
  flux:    { name: "Flux Pro",       sub: "Image", glyph: "F", bg: "#0A0A0A", border: true },
  gptimg:  { name: "GPT Image 2",    sub: "OpenAI · Image", glyph: "◆", bg: "#10A37F" },
  eleven:  { name: "ElevenLabs",     sub: "Voice & TTS", glyph: "❙❙", bg: "#0A0A0A", border: true },
  suno:    { name: "Suno V5",        sub: "Music & podcasts", glyph: "♫", bg: "#E0563B" },
};
const MARQUEE = ["gemini","gpt","kimi","grok","deepseek","seedance","kling","veo","pixverse","wan","nano","seedream","flux","gptimg","ltx","eleven","suno"];

// ---- Everything you'll get (base benefits) ----
const BENEFITS = [
  { icon: "Sparkles", title: "Every frontier AI model", body: "140+ premium models — Opus, GPT-5.4, Gemini 3.1 Pro, Seedance, Kling, Nano Banana & more.", color: "var(--magenta)" },
  { icon: "Plug", title: "API & MCP Access", body: "Plug OmniRogue into any app, agent or workflow — generate from code or connect via MCP.", color: "var(--cyan)" },
  { icon: "Link2", title: "Connectors", body: "Connect your apps so agents can read and act on your data across the tools you use.", color: "var(--blue)" },
  { icon: "Wand2", title: "Multi-modal generation", body: "Create images, videos, ads, speech, music, sound effects, websites and more.", color: "var(--pink)" },
  { icon: "FolderKanban", title: "Custom AI projects", body: "Build self-contained workspaces trained on your own specific data and brand.", color: "var(--violet)" },
  { icon: "Zap", title: "Flexible usage", body: "No waiting period or hourly limits — use your credits anytime, your way.", color: "var(--amber)" },
  { icon: "BriefcaseBusiness", title: "Commercial rights", body: "100% commercial use rights on every output you create — sell what you make.", color: "var(--teal)" },
  { icon: "ShieldCheck", title: "Data privacy", body: "Your data never trains AI models — complete privacy, guaranteed. 🇺🇸", color: "var(--green)" },
];

// ---- Premium benefits (annual + lifetime only) ----
const PREMIUM_BENEFITS = [
  { icon: "Crown", title: "Priority support", body: "24-hour personalized response guarantee from a real human." },
  { icon: "Rocket", title: "Early access", body: "Get brand-new AI tools and models before other subscribers." },
  { icon: "Gauge", title: "Enhanced speed", body: "10× faster generation speeds across every tool, front of queue." },
  { icon: "Network", title: "Priority API & MCP queue", body: "Priority processing for all your API and MCP requests." },
];

// ---- Lifetime hero highlights (the 3 headline value props) ----
const LIFETIME_HIGHLIGHTS = [
  { icon: "Infinity", text: "Unlimited on 140+ models for your first 90 days" },
  { icon: "Gem", text: "10,000 Premium Credits for ultra-fast generation" },
  { icon: "Zap", text: "2,500 XP every month — for life" },
];

// ---- Lifetime: curated "what's included" list ----
const LIFETIME_INCLUDED = [
  { icon: "MessageSquare", title: "Unlimited text & reasoning, forever", sub: "Gemini 3.1 Pro · GPT-5.4 · Kimi K2.5 · Grok 4.1 · DeepSeek" },
  { icon: "Clapperboard", title: "Premium video & image generation", sub: "Veo 3.1 · Kling 3 · Seedance 2.0 · Nano Banana 2 · Seedream" },
  { icon: "Mic", title: "Voice agents, music & podcasts", sub: "ElevenLabs custom voices · Suno music" },
  { icon: "BadgeCheck", title: "Commercial license + full music rights", sub: "Sell everything you create — content, music & audio" },
  { icon: "Sparkles", title: "Every future model & update — free, forever", sub: "New models added weekly, automatically in your account" },
  { icon: "Users", title: "VIP Founders circle + 1-on-1 onboarding", sub: "Founders-only community · get set up in under 24h" },
  { icon: "Headphones", title: "Priority support", sub: "Dedicated channel · average reply under 1 business hour" },
];

// ---- Top models grouped (for the Lifetime "see list") ----
const TOP_MODELS = [
  { group: "Video", items: ["seedance", "kling", "veo", "ltx", "pixverse"] },
  { group: "Image", items: ["nano", "seedream", "flux", "gptimg"] },
  { group: "Audio & voice", items: ["eleven", "suno"] },
];

// ---- Creator plan detailed access breakdown (monthly + yearly) ----
const CREATOR_ACCESS = [
  { kind: "plain", title: "Video models", icon: "Clapperboard", rows: [
    { name: "Seedance 2.0", access: "none", badge: "No access", badgeTone: "none" },
    { name: "Seedance 2.0 Fast", access: "full", badge: "Full access", badgeTone: "green" },
  ] },
  { kind: "plain", title: "Image models", note: "∞ Unlimited & free gens", info: true, rows: [
    { name: "Seedream 4.5", access: "yes", info: true, badge: "300 free gens", badgeTone: "gold" },
    { name: "Flux Schnell", access: "yes", info: true, badge: "450 free gens", badgeTone: "gold" },
    { name: "Flux Pro", access: "yes", info: true, badge: "200 free gens", badgeTone: "gold" },
    { name: "Nano Banana", access: "none" },
    { name: "Nano Banana 2", access: "none" },
    { name: "GPT Image 2", access: "yes", info: true, badge: "500 free gens", badgeTone: "gold" },
    { name: "Imagen Premium", access: "none" },
  ] },
  { kind: "suite", title: "Creative Suite", icon: "Wand2", rows: [
    { name: "Pro editing tools", sub: "Image, video & design", info: true, badges: [{ t: "1080p", tone: "purple" }, { t: "Included", tone: "green" }] },
    { name: "Music, voice & sound effects", sub: "Studio-grade audio with Suno", info: true, badges: [{ t: "Included", tone: "green" }] },
    { name: "99 Prebuilt AI Agents", sub: "Ready-to-run workflows", badges: [{ t: "Included", tone: "green" }] },
    { name: "Advanced Prompt Library", sub: "2,500 preset prompts", info: true, badges: [{ t: "Included", tone: "green" }] },
  ] },
  { kind: "text", title: "Unlimited text", info: true, rows: [
    { name: "Gemini 3.1 Pro" }, { name: "GPT-5.4" }, { name: "Kimi K2.5" }, { name: "Grok 4.1 Fast" }, { name: "DeepSeek V3.2" },
  ] },
];

// ---- Creator (subscription) benefit checklist ----
const CREATOR_BENEFITS = [
  { title: "Access to every video, image & audio model" },
  { title: "Pro editing tools: image, video & design", sub: "Image Editor, AI video editor & 1080p upscalers" },
  { title: "Music, voice & sound effects generation" },
  { title: "Advanced Prompt Library & GPT Builder" },
  { title: "Unlimited text & reasoning", sub: "No per-message, per-token, or per-minute fees" },
  { title: "Commercial AI License", sub: "Sell & monetize everything you create" },
];

const PAYMENTS = ["VISA", "AMEX", "PayPal", "Stripe Pay", "G Pay"];

// ---- Grand Slam offer (Hormozi value stack) ----
const GRAND_STACK = [
  { label: "Lifetime access to 140+ frontier AI models", sub: "First 90 days fully unlimited on every model", value: 2400 },
  { label: "Unlimited text & reasoning — forever", sub: "Gemini 3.1 Pro · GPT-5.4 · Kimi K2.5 · Grok 4.1 · DeepSeek", value: 1200 },
  { label: "10,000 Premium Credits for ultra-fast generation", sub: "Video, image & voice on the top premium models", value: 499 },
  { label: "Commercial license + full music rights", sub: "Sell everything you create — content, music & audio", value: 588 },
  { label: "Every future model & update — free, forever", sub: "New models added weekly, automatically in your account", value: 1200 },
  { label: "2,500 XP every month — for life", sub: "Climb the creator ranks & boost your work", value: 300 },
];
const GRAND_BONUSES = [
  { n: 1, label: "VIP Founders Circle + 1-on-1 onboarding", sub: "Founders-only community & a live setup call within 24h", value: 497 },
  { n: 2, label: "99 prebuilt AI agents + 2,500-prompt library", sub: "Ready-to-run workflows so you produce from day one", value: 297 },
  { n: 3, label: "Creator Accelerator workshops", sub: "Live playbooks for turning AI output into income", value: 497 },
];

// ---- vs paying separately ----
const COMPARE_TOOLS = [
  { name: "ChatGPT Pro", price: 200 },
  { name: "Claude Max", price: 100 },
  { name: "Midjourney", price: 60 },
  { name: "Runway / Kling video", price: 95 },
  { name: "ElevenLabs voice", price: 99 },
  { name: "Suno music", price: 30 },
  { name: "Perplexity + others", price: 40 },
];

const PRESS = ["Forbes", "TechCrunch", "WIRED", "The New York Times", "Product Hunt"];

const TESTIMONIALS = [
  { quote: "I've tested every AI platform out there and OmniRogue is the one that checks all the boxes — flexible, packed with options, and far more affordable than paying for each tool separately.", pull: "If you're on the fence, don't be — this is the one.", name: "Scott Molinaro", role: "Agency Founder" },
  { quote: "OmniRogue combines so many popular AI apps in one place. Instead of separate subscriptions to ChatGPT, Midjourney, video generators and more, I access them all under one platform.", pull: "It saves me money and keeps everything organized.", name: "Janet Giessl", role: "Content Marketer" },
  { quote: "I use OmniRogue every day — for work, life and hobbies — and it's honestly become my go-to app. It's packed with way more tools than I expected, so I never switch apps.", pull: "It's incredible for what you get.", name: "Dean Whitlock", role: "Indie Creator" },
  { quote: "I ditched subscriptions to 7 different AI tools because OmniRogue packs them all in — plus extras I use daily for everything from content creation to brainstorming.", pull: "No fluff, just pure efficiency and massive savings.", name: "Trelandon Jones", role: "Solopreneur" },
];

const FAQS = [
  { q: "What exactly do I get for one membership?", a: "Access to 140+ frontier AI models — text, image, video, voice and music — under a single login. Unlimited daily chat on the top text models, plus Premium Credits for premium media. New models are added weekly and included automatically." },
  { q: "What's the difference between the Lifetime Pass and a subscription?", a: "The Lifetime Pass is a one-time payment that unlocks everything forever — including 150M bonus credits up front and 15M Premium Credits refilling every month for life, with no recurring fees. Subscriptions bill monthly or yearly and can be cancelled anytime." },
  { q: "What does “unlimited” actually mean?", a: "On the unlimited models you generate as much as you want with no per-generation credit cost. Everything else runs on a shared pool of Premium Credits — roughly one credit per image and a few per short video — which refills every billing cycle." },
  { q: "Can I cancel or switch plans anytime?", a: "Yes. Upgrade, downgrade or cancel from your dashboard at any time — changes take effect on your next renewal, with no lock-in contracts. The Lifetime Pass never renews, so there's nothing to cancel." },
  { q: "Do I own what I create?", a: "Completely. Every plan includes 100% commercial use rights on your outputs, and Scale, Pro and Lifetime add full music rights. Your data never trains AI models." },
  { q: "Is there a money-back guarantee?", a: "Yes — and it's total. Every monthly and yearly plan is backed by a 14-day no-questions-asked guarantee, and the Lifetime Pass by a full 30-day guarantee. If it's not for you, email us and we'll refund every cent — keep whatever you already made." },
];

// ---- recent-purchase ticker ----
const TICKER = [
  { name: "Jordan", city: "Austin, TX", plan: "Lifetime Pass" },
  { name: "Amara", city: "London, UK", plan: "Annual membership" },
  { name: "Wei", city: "Singapore", plan: "Lifetime Pass" },
  { name: "Sofia", city: "Madrid, ES", plan: "Lifetime Pass" },
  { name: "Marcus", city: "Toronto, CA", plan: "Annual membership" },
  { name: "Priya", city: "Mumbai, IN", plan: "Lifetime Pass" },
  { name: "Liam", city: "Dublin, IE", plan: "Lifetime Pass" },
  { name: "Noah", city: "Berlin, DE", plan: "Annual membership" },
];

Object.assign(window, {
  Icon, Button, Pill, ModelTile, useCountdown, pad,
  MODELS, MARQUEE, BENEFITS, PREMIUM_BENEFITS, TOP_MODELS,
  LIFETIME_HIGHLIGHTS, LIFETIME_INCLUDED, CREATOR_BENEFITS, CREATOR_ACCESS, PAYMENTS,
  GRAND_STACK, GRAND_BONUSES,
  COMPARE_TOOLS, PRESS, TESTIMONIALS, FAQS, TICKER,
});
