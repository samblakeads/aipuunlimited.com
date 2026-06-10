<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
require_once $base_path.'/includes/safe.php';
?>
<?php require_once(__DIR__.'/_kk-config.php'); ?>
<?php require_once(__DIR__.'/_checkout-offers.php'); ?>
<!DOCTYPE html>
<html data-browser-safari="false" data-theme-mode="dark" lang="en" style="--banner-height: 0px;"><head>
<link href="https://omnirogue-images.b-cdn.net" rel="dns-prefetch"/>
<link href="https://images.unsplash.com" rel="dns-prefetch"/>
<link crossorigin="" href="https://omnirogue-images.b-cdn.net" rel="preconnect"/>
<link href="https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/main1.css" rel="stylesheet"/>
<link href="https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/main2.css" rel="stylesheet"/>
<style id="aipu-static-fixes">
html{--header-height:64px;--nav-height:64px;--nav-bg:rgba(7,8,18,0.85);color-scheme:dark}
/* Static page fixes */
@media (min-width:768px){.md\:hidden.shrink-0.flex.items-center.justify-between{display:none!important}aside.fixed.inset-0.z-50{position:relative!important;inset:auto!important;z-index:auto!important;width:240px!important;height:100%!important}div.fixed.inset-0.z-30.bg-black\/50{display:none!important}}
audio{display:none}
button[data-keep-dark] .bg-gradient-to-br,[data-keep-dark] .bg-gradient-to-br{min-height:100%;background:linear-gradient(to bottom right,rgb(46,16,101),rgb(30,27,75),rgb(15,23,42))!important}
/* Discover grid (video + audio). The hydrated React app sizes each masonry row
   from per-card flex ratios + aspect-ratio capped at 38vh. Those JS-driven inline
   ratios collapse in a static snapshot, so the optimize step rewrites each card to
   a fixed height:220px and we lay the whole Discover section out as a uniform
   responsive CSS grid here. */
.hidden.md\:flex.md\:flex-col{display:grid!important;grid-template-columns:repeat(3,1fr);gap:10px}
.hidden.md\:flex.md\:flex-col > .flex{display:contents!important}
.hidden.md\:flex.md\:flex-col > .flex > button{width:auto!important;min-width:0!important;max-width:none!important;align-self:stretch}
.hidden.md\:flex.md\:flex-col > .flex > button > div{height:100%}
.aipu-static-footer a:hover{text-decoration:underline}
</style>
<meta content="data-deletion-request" name="x-static-copy"/>
<meta charset="utf-8" data-next-head=""/><meta content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover" data-next-head="" name="viewport"/><title data-next-head="">Data Deletion Requests - AIPU</title><meta content="Request deletion of your personal information from AI Professionals University." data-next-head="" name="description"/><link data-next-head="" href="/favicon.ico" rel="icon"/><link data-next-head="" href="/favicon.ico" rel="apple-touch-icon"/><noscript data-n-css=""></noscript><style id="omni-static-fixes">
html{--header-height:64px;--nav-height:64px;--nav-bg:rgba(7,8,18,0.85);color-scheme:dark}
.fixed.top-0.left-0.right-0.z-50 .backdrop-blur-xl,.fixed.top-0.left-0.right-0.z-50 [class*="backdrop-blur"]{backdrop-filter:blur(24px)!important;-webkit-backdrop-filter:blur(24px)!important;}
</style>
<style id="omni-nav-isolation">
/* Site nav must render identically on lander, checkout, and studio pages. */
.fixed.top-0.left-0.right-0.z-50 {
  z-index: 50 !important;
  isolation: isolate;
}
.fixed.top-0.left-0.right-0.z-50,
.fixed.top-0.left-0.right-0.z-50 *,
.fixed.top-0.left-0.right-0.z-50 *::before,
.fixed.top-0.left-0.right-0.z-50 *::after {
  box-sizing: border-box;
}
.omni-lander-wrap .promo-bar {
  position: sticky;
  top: calc(var(--nav-height, 64px) + env(safe-area-inset-top, 0px));
  z-index: 40;
}
/* Announcement / promo bar pinned cleanly directly under the fixed site nav.
   Keyed on the stable kk-announce-bar hook (added at build time) so it works
   regardless of the lander's original bar class name. */
.omni-lander-wrap .kk-announce-bar {
  position: sticky;
  top: calc(var(--nav-height, 64px) + env(safe-area-inset-top, 0px));
  z-index: 40;
}
.omni-lander-wrap .kk-announce-bar .container {
  flex-wrap: nowrap;
  gap: 10px;
  min-height: 0;
}
@media (max-width: 700px) {
  /* On phones, don't let the announcement bar dominate the header: keep it on
     a single compact row and let it scroll away instead of stacking under the
     nav, so the mobile header is just the slim site nav. */
  .omni-lander-wrap .kk-announce-bar {
    position: static;
  }
  .omni-lander-wrap .kk-announce-bar .container {
    flex-wrap: nowrap;
    justify-content: flex-start;
    gap: 8px;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}
</style>
<script data-flow-config>
window.__LANDER_BASE=<?= json_encode($__web, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_CHECKOUT_URL=<?= json_encode($__checkout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_OFFER_LINKS=<?= json_encode((object)($__kk_offer_links ?? []), JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_CHECKOUT=<?= json_encode($__registercheckout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_CREATE=<?= json_encode($__registercreate, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_BILLING=<?= json_encode($__registercheckout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_REGISTER_TOKEN="registercheckout";
window.__KK_STEP1LINK=<?= json_encode($__step1link, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_IS_CHECKOUT=<?= json_encode($__is_checkout ?? false); ?>;
window.__OMNI_HOME_DEAD=1;
</script>

</head><body><div id="__next"><div class="inter_49defa59-module__D5ngMq__variable"><div class="fixed top-0 left-0 right-0 z-50"><nav class="relative" style="padding-top:env(safe-area-inset-top);height:calc(var(--nav-height) + env(safe-area-inset-top))"><div class="absolute inset-0 backdrop-blur-xl border-b border-border" style="background-color:var(--nav-bg)"></div><div class="container container--xl max-w-7xl mx-auto px-4 sm:px-6 relative h-full"><div class="flex-layout flex-row gap-0 items-center justify-between h-full"><a class="flex items-center touch-manipulation" href="#"><div class="h-[36px] sm:h-[42px] flex items-center"><picture><source srcset="https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/logo-aipu.webp" type="image/webp"/><img alt="AIPU" class="object-contain w-auto h-[36px] sm:h-[42px]" data-nimg="1" decoding="async" height="42" loading="lazy" src="https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/logo-aipu.png" srcset="https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/logo-aipu.png 1x, https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/logo-aipu.png 2x" style="color:transparent" width="160"/></picture></div></a><div class="hidden xl:flex items-center gap-1"><a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation relative group" href="#">Home<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a><a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation relative group" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/createvideo.php<?= $__step1link; ?>">Create Studio<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a><button aria-expanded="false" aria-haspopup="menu" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation outline-none" data-state="closed" id="radix-_R_1lairm_" type="button">Library<svg aria-hidden="true" class="lucide lucide-chevron-down w-3.5 h-3.5 opacity-60" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m6 9 6 6 6-6"></path></svg></button><a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation relative group" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/about.php<?= $__step1link; ?>"><svg aria-hidden="true" class="lucide lucide-users w-3.5 h-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><path d="M16 3.128a4 4 0 0 1 0 7.744"></path><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><circle cx="9" cy="7" r="4"></circle></svg>About Us<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a><a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-(--text-secondary) hover:text-foreground hover:bg-[rgba(255,255,255,0.04)] transition-all duration-200 touch-manipulation relative group" href="<?= $__checkout; ?>"><svg aria-hidden="true" class="lucide lucide-tag w-3.5 h-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" fill="currentColor" r=".5"></circle></svg>Pricing<span class="absolute bottom-0.5 left-3 right-3 h-[2px] rounded-full bg-linear-to-r from-primary to-secondary scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></span></a></div><div class="hidden xl:flex items-center gap-3 ml-4"><div tabindex="0"><a class="inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 cursor-pointer bg-linear-to-r from-primary to-primary-glow text-primary-foreground shadow-[0_4px_24px_rgba(139,92,255,0.35)] hover:shadow-[0_8px_32px_rgba(139,92,255,0.5)] hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] h-8 rounded-md px-3 text-xs gap-1.5" href="<?= $__checkout; ?>"><svg aria-hidden="true" class="lucide lucide-crown w-3.5 h-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>Create Account</a></div><a class="inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 cursor-pointer text-foreground hover:bg-[rgba(255,255,255,0.06)] active:scale-[0.98] h-8 rounded-md px-3 text-xs border border-border/50 hover:border-border gap-1.5" href="<?= $__checkout; ?>"><svg aria-hidden="true" class="lucide lucide-log-in w-3.5 h-3.5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m10 17 5-5-5-5"></path><path d="M15 12H3"></path><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path></svg>Login</a></div><button aria-controls="radix-_R_9airm_" aria-expanded="false" aria-haspopup="dialog" aria-label="Open menu" class="xl:hidden p-2 rounded-lg text-foreground hover:bg-[rgba(255,255,255,0.06)] transition-colors touch-manipulation" data-state="closed" type="button"><svg aria-hidden="true" class="lucide lucide-menu w-5 h-5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M4 5h16"></path><path d="M4 12h16"></path><path d="M4 19h16"></path></svg></button></div></div></nav></div><main class="box bg-background min-h-screen"><div class="min-h-screen bg-background text-foreground"><div class="max-w-4xl mx-auto px-6 pt-24 md:pt-32 pb-12"><div class="mb-8" style="opacity: 1; transform: none;"><div class="flex items-center gap-3 mb-4"><div class="w-12 h-12 rounded-xl bg-linear-to-br from-primary to-secondary flex items-center justify-center"><svg aria-hidden="true" class="lucide lucide-trash2 lucide-trash-2 w-6 h-6 text-white" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></div><h1 class="text-3xl md:text-4xl font-bold">Data Deletion Requests</h1></div></div><div class="prose prose-invert max-w-none" style="opacity: 1; transform: none;"><div class="space-y-6 text-(--text-secondary)"><p class="text-lg">You may request deletion of your personal information by contacting us at<!-- --> <a class="text-primary hover:text-primary-glow" href="mailto:help@aiprofessionalsuniversity.com">help@aiprofessionalsuniversity.com</a>.</p><p>To help us locate your account, please include your name, account email address, purchase email address if different, and any relevant order or receipt information.</p><p>We will review and process data deletion requests within a reasonable timeframe, subject to any legal, billing, tax, fraud prevention, security, dispute, or compliance obligations that may require us to retain certain limited records.</p></div></div></div></div></main><footer class="relative border-t border-border bg-background"><div class="absolute inset-0 bg-linear-to-t from-background via-background-secondary/30 to-transparent pointer-events-none"></div><div class="container container--xl max-w-7xl mx-auto px-4 sm:px-6 relative py-10 sm:py-16"><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 mb-10 sm:mb-16"><div class="sm:col-span-2"><a class="flex items-center mb-4" href="#"><div class="flex-layout flex-row gap-0 items-center h-[42px]"><picture><source srcset="https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/logo-aipu.webp" type="image/webp"/><img alt="AIPU" class="object-contain" data-nimg="1" decoding="async" height="42" loading="lazy" src="https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/logo-aipu.png" srcset="https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/logo-aipu.png 1x, https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/logo-aipu.png 2x" style="color:transparent" width="160"/></picture></div></a><p class="typography typography--body2 text-sm text-(--text-secondary) mb-6 max-w-xs leading-relaxed">Deploy powerful AI agents for content creation, automation, and beyond.</p></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Product</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/createvideo.php<?= $__step1link; ?>">AI Agent</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/createvideo.php<?= $__step1link; ?>">AI Studio</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/createvideo.php<?= $__step1link; ?>">Create Studio</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/create-voice-agents.php<?= $__step1link; ?>">Voice Agents</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/knowledge-base.php<?= $__step1link; ?>">Knowledge Base</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="<?= $__checkout; ?>">Pricing</a></div></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Resources</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/help-center.php<?= $__step1link; ?>">Community</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="<?= $__checkout; ?>">Contact Us</a></div></div><div><h6 class="typography typography--subtitle2 text-sm font-medium leading-relaxed text-foreground mb-4">Legal</h6><div class="stack stack--vertical gap-2.5"><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/privacy-policy.php<?= $__step1link; ?>">Privacy Policy</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/terms-of-service.php<?= $__step1link; ?>">Terms of Service</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/data-deletion-request.php<?= $__step1link; ?>">Data Deletion Request</a><a class="link link--muted link--hover-underline inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4 text-sm" href="/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/acceptable-use-policy.php<?= $__step1link; ?>">Acceptable Use Policy</a></div></div></div><div class="shrink-0 h-[1px] w-full bg-border divider my-4" data-orientation="horizontal" role="none"></div><div class="flex-layout gap-4 items-center justify-between flex-col md:flex-row pt-8"><span class="typography typography--caption text-xs leading-normal text-(--text-muted)">© <!-- -->2026<!-- --> <!-- -->AI Professionals University<!-- --> · <a class="link link--muted link--hover-underline link--external inline-flex items-center gap-1 transition-colors duration-200 text-(--text-secondary) hover:text-foreground no-underline hover:underline underline-offset-4" href="mailto:support@aiprofessionalsuniversity.com" rel="noopener noreferrer" target="_blank">support@aiprofessionalsuniversity.com</a></span><span class="typography typography--caption text-xs leading-normal text-(--text-muted) flex items-center gap-1">Made with <span class="text-accent">❤</span> for creators worldwide</span></div></div></footer><div data-rht-toaster="" style="position:fixed;z-index:9999;top:16px;left:16px;right:16px;bottom:16px;pointer-events:none"></div></div></div>
<script defer="" src="https://aipu-assets.b-cdn.net/lander7upgrades-omniunlimited-v2__plans-v3-299-lifetime__aipu/bf1e70b2/assets/static.js?v=202606100158"></script>
<script data-flow-home-dead="">
(function(){
  function kill(){
    var els=document.querySelectorAll('a,button');
    for(var i=0;i<els.length;i++){
      var el=els[i];
      var t=(el.textContent||'').replace(/\s+/g,' ').trim();
      if(t==='Home'){
        if(el.tagName==='A'){el.setAttribute('href','#');}
        el.style.cursor='default';
        if(!el.__flowHomeDead){el.__flowHomeDead=1;el.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();},true);}
      }
    }
  }
  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',kill);}else{kill();}
  setTimeout(kill,300);setTimeout(kill,1200);
})();
</script>
</body></html>