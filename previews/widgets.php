<?php
declare(strict_types=1);

/**
 * widgets.php — Widget Builder (exit popups + mobile sticky sliders).
 *
 *   GET                      -> builder UI (list · live preview · edit form)
 *   POST ?preview=1          -> standalone HTML preview document for the
 *                               posted widget config (used by the live iframe)
 *
 * Widgets are persisted through widget-library.php; flows attach them via
 * flow-widgets.php (catalog "Attach Widgets" modal).
 */

require_once __DIR__ . '/lib/checkout-fs.php';
require_once __DIR__ . '/lib/widget-render.php';

function wb_load_plans(): array
{
    $path = cb_data_dir() . '/plan-library.json';
    $doc = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
    return is_array($doc) && is_array($doc['plans'] ?? null) ? $doc['plans'] : [];
}

function wb_plan_map(array $plans): array
{
    $map = [];
    foreach ($plans as $p) {
        if (empty($p['id'])) {
            continue;
        }
        $map[$p['id']] = [
            'name'   => (string)($p['display_name'] ?? $p['id']),
            'tokens' => is_array($p['offer_tokens'] ?? null) ? $p['offer_tokens'] : [],
        ];
    }
    return $map;
}

// ---------------------------------------------------------------- preview doc
if (isset($_GET['preview']) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');

    $raw = file_get_contents('php://input') ?: '';
    $input = json_decode($raw, true);
    if (!is_array($input) || !is_array($input['widget'] ?? null)) {
        http_response_code(400);
        echo 'Bad preview payload';
        exit;
    }
    $widget = $input['widget'];
    $widget['id'] = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($widget['id'] ?? 'preview'))) ?: 'preview';
    $page = ($input['page'] ?? 'sales') === 'checkout' ? 'checkout' : 'sales';

    $planMap = wb_plan_map(wb_load_plans());
    $block = widget_render_block([$widget], [
        'page'    => $page,
        'flow'    => '',
        'plans'   => $planMap,
        'preview' => true,
    ]);

    // Fake page backdrop so popups/sliders preview over realistic content.
    $isCheckout = $page === 'checkout';
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Widget preview</title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; min-height: 220vh; font-family: 'Inter', system-ui, sans-serif;
         background: linear-gradient(165deg, #0B0814 0%, #120D26 55%, #0B0814 100%); color: #cfd2e4; }
  .bd { max-width: 980px; margin: 0 auto; padding: 56px 28px; }
  .sk-pill { width: 130px; height: 26px; border-radius: 999px; background: rgba(139,92,255,0.18); }
  .sk-h { height: 46px; border-radius: 12px; background: rgba(255,255,255,0.10); margin: 22px 0 10px; width: 72%; }
  .sk-h2 { height: 46px; border-radius: 12px; background: rgba(255,255,255,0.07); width: 48%; }
  .sk-p { height: 13px; border-radius: 7px; background: rgba(255,255,255,0.06); margin: 12px 0; }
  .sk-p.short { width: 62%; }
  .sk-cta { width: 220px; height: 52px; border-radius: 13px; margin-top: 26px;
            background: linear-gradient(110deg, rgba(139,92,255,0.8), rgba(99,102,241,0.8)); }
  .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-top: 60px; }
  .card { border-radius: 18px; border: 1px solid rgba(255,255,255,0.09); background: rgba(255,255,255,0.03); padding: 24px; }
  .card h3 { margin: 0 0 4px; color: #fff; font-size: 19px; }
  .card .pr { font-size: 30px; font-weight: 800; color: #fff; margin: 10px 0 14px; }
  .card .pr small { font-size: 12px; color: rgba(255,255,255,0.45); font-weight: 600; }
  .card .ln { height: 11px; border-radius: 6px; background: rgba(255,255,255,0.06); margin: 9px 0; }
  .card a { display: block; text-align: center; padding: 12px; border-radius: 11px; margin-top: 16px;
            background: rgba(139,92,255,0.25); border: 1px solid rgba(139,92,255,0.5); color: #d9ccff;
            text-decoration: none; font-size: 13px; font-weight: 700; }
  .hint { position: fixed; top: 14px; left: 50%; transform: translateX(-50%); z-index: 99999;
          font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.45);
          background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.12); border-radius: 999px;
          padding: 6px 14px; pointer-events: none; }
</style>
</head>
<body>
<div class="hint"><?= $isCheckout ? 'Checkout page preview' : 'Sales page preview' ?> · scroll to test triggers</div>
<div class="bd">
<?php if ($isCheckout): ?>
  <div class="sk-pill"></div>
  <div class="sk-h"></div>
  <div class="sk-p"></div><div class="sk-p short"></div>
  <div class="cards">
    <?php foreach (array_slice(array_values(wb_load_plans()), 0, 4) as $p): ?>
    <div class="card" >
      <h3><?= htmlspecialchars((string)($p['display_name'] ?? ''), ENT_QUOTES) ?></h3>
      <div class="pr">$<?= htmlspecialchars((string)($p['monthly_price'] ?? $p['lifetime_price'] ?? '0'), ENT_QUOTES) ?><small><?= isset($p['monthly_price']) && $p['monthly_price'] !== null ? '/mo' : ' once' ?></small></div>
      <div class="ln"></div><div class="ln"></div><div class="ln"></div>
      <a href="#">Get <?= htmlspecialchars((string)($p['display_name'] ?? ''), ENT_QUOTES) ?></a>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="sk-p" style="margin-top:70px"></div><div class="sk-p"></div><div class="sk-p short"></div>
<?php else: ?>
  <div class="sk-pill"></div>
  <div class="sk-h"></div><div class="sk-h2"></div>
  <div class="sk-p"></div><div class="sk-p"></div><div class="sk-p short"></div>
  <div class="sk-cta"></div>
  <div class="sk-p" style="margin-top:90px"></div><div class="sk-p"></div><div class="sk-p"></div>
  <div class="sk-p"></div><div class="sk-p short"></div>
  <div class="sk-p" style="margin-top:90px"></div><div class="sk-p"></div><div class="sk-p short"></div>
<?php endif; ?>
</div>
<?= $block ?>
</body>
</html><?php
    exit;
}

// ---------------------------------------------------------------- builder UI
$plansLib = wb_load_plans();
$widgetsDoc = [];
$wlPath = cb_data_dir() . '/widget-library.json';
if (is_file($wlPath)) {
    $widgetsDoc = json_decode((string)file_get_contents($wlPath), true) ?: [];
}
$widgetsList = is_array($widgetsDoc['widgets'] ?? null) ? $widgetsDoc['widgets'] : [];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Widget Builder · Popups &amp; Sliders</title>
<script>
(function () {
  var t = localStorage.getItem('catalog-theme');
  document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root,
[data-theme="dark"] {
  color-scheme: dark;
  --bg: #0a0a0a; --bg-elevated: #111; --surface: #141414; --surface-2: #1c1c1c;
  --border: rgba(255,255,255,0.08); --border-strong: rgba(255,255,255,0.14);
  --text: #ededed; --muted: #888;
  --aipu: #818cf8; --omni: #34d399;
  --accent: #fff; --accent-fg: #0a0a0a; --accent-soft: rgba(255,255,255,0.08);
  --shadow-md: 0 8px 32px rgba(0,0,0,0.45);
  --radius: 14px;
}
[data-theme="light"] {
  color-scheme: light;
  --bg: #f5f5f7; --bg-elevated: #fff; --surface: #fff; --surface-2: #fbfbfd;
  --border: rgba(0,0,0,0.08); --border-strong: rgba(0,0,0,0.12);
  --text: #1d1d1f; --muted: #86868b;
  --aipu: #5856d6; --omni: #059669;
  --accent: #0071e3; --accent-fg: #fff; --accent-soft: rgba(0,113,227,0.1);
  --shadow-md: 0 8px 30px rgba(0,0,0,0.08);
  --radius: 14px;
}
*, *::before, *::after { box-sizing: border-box; }
body {
  margin: 0; font-family: Inter, -apple-system, system-ui, sans-serif;
  background: var(--bg); color: var(--text); line-height: 1.5;
  -webkit-font-smoothing: antialiased; height: 100vh; overflow: hidden;
}
.topbar {
  display: flex; align-items: center; gap: 14px;
  padding: 12px 20px; border-bottom: 1px solid var(--border);
  background: var(--bg-elevated);
}
.topbar h1 { margin: 0; font-size: 15px; font-weight: 700; letter-spacing: -0.01em; }
.topbar .crumb { color: var(--muted); font-size: 13px; text-decoration: none; }
.topbar .crumb:hover { color: var(--text); }
.topbar .spacer { flex: 1; }
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 8px 14px; border-radius: 9px;
  border: 1px solid var(--border-strong); background: transparent;
  color: var(--text); font: inherit; font-size: 13px; font-weight: 600; cursor: pointer;
  transition: background .15s, border-color .15s;
}
.btn:hover { background: var(--accent-soft); }
.btn-primary { background: var(--accent); color: var(--accent-fg); border-color: transparent; }
.btn-primary:hover { opacity: .9; background: var(--accent); }
.btn-danger { border-color: rgba(244,63,94,0.5); color: #f87171; }
.btn-danger:hover { background: rgba(244,63,94,0.1); }
.btn[disabled] { opacity: .45; pointer-events: none; }
.wrap { display: grid; grid-template-columns: 280px 1fr 360px; height: calc(100vh - 57px); }
/* ---- left list ---- */
.list {
  border-right: 1px solid var(--border); overflow-y: auto; background: var(--bg-elevated);
  padding: 12px;
}
.list-head { display: flex; gap: 6px; margin-bottom: 12px; }
.list-filter {
  flex: 1; display: inline-flex; align-items: center; justify-content: center;
  padding: 6px 0; border-radius: 8px; border: 1px solid var(--border);
  font-size: 12px; font-weight: 600; color: var(--muted); cursor: pointer; background: transparent;
}
.list-filter.on { color: var(--text); border-color: var(--border-strong); background: var(--accent-soft); }
.wcard {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 12px; border-radius: 11px; border: 1px solid var(--border);
  margin-bottom: 8px; cursor: pointer; background: var(--surface);
  transition: border-color .15s, background .15s;
}
.wcard:hover { border-color: var(--border-strong); }
.wcard.sel { border-color: var(--aipu); background: var(--accent-soft); }
.wdot { flex: 0 0 10px; width: 10px; height: 10px; border-radius: 999px; }
.wdot.gold { background: linear-gradient(110deg,#F5E1A4,#D4A647); }
.wdot.purple { background: linear-gradient(110deg,#8B5CFF,#6366F1); }
.wdot.emerald { background: linear-gradient(110deg,#34d399,#047857); }
.wdot.crimson { background: linear-gradient(110deg,#F43F5E,#BE123C); }
.wdot.blue { background: linear-gradient(110deg,#3B82F6,#1D4ED8); }
.winfo { flex: 1; min-width: 0; }
.winfo .nm { font-size: 12.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.winfo .ty { font-size: 10.5px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; }
.wstate { flex: 0 0 auto; font-size: 9.5px; font-weight: 700; padding: 3px 7px; border-radius: 999px;
          text-transform: uppercase; letter-spacing: 0.06em; }
.wstate.on  { color: var(--omni); background: rgba(52,211,153,0.12); }
.wstate.off { color: var(--muted); background: var(--accent-soft); }
.list-new { width: 100%; justify-content: center; margin-bottom: 12px; }
/* ---- center preview ---- */
.stage { display: flex; flex-direction: column; min-width: 0; background: var(--bg); }
.stage-bar {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 16px; border-bottom: 1px solid var(--border);
}
.seg { display: inline-flex; border: 1px solid var(--border-strong); border-radius: 9px; overflow: hidden; }
.seg button {
  border: 0; background: transparent; color: var(--muted);
  font: inherit; font-size: 12px; font-weight: 600; padding: 7px 13px; cursor: pointer;
}
.seg button.on { background: var(--accent-soft); color: var(--text); }
.stage-bar .spacer { flex: 1; }
.stage-body { flex: 1; display: flex; align-items: stretch; justify-content: center; padding: 18px; overflow: hidden; }
.frame-wrap {
  width: 100%; max-width: 1200px; height: 100%;
  border-radius: 14px; overflow: hidden; border: 1px solid var(--border-strong);
  box-shadow: var(--shadow-md); background: #0B0814;
  transition: max-width .3s ease; margin: 0 auto;
}
.frame-wrap.mobile { max-width: 390px; }
.frame-wrap iframe { width: 100%; height: 100%; border: 0; display: block; }
/* ---- right form ---- */
.panel { border-left: 1px solid var(--border); overflow-y: auto; background: var(--bg-elevated); padding: 16px 16px 60px; }
.panel h2 { margin: 4px 0 14px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }
.fgroup { margin-bottom: 13px; }
.fgroup label { display: block; font-size: 11.5px; font-weight: 600; color: var(--muted); margin-bottom: 5px; }
.fgroup input[type="text"], .fgroup input[type="number"], .fgroup textarea, .fgroup select {
  width: 100%; padding: 8px 10px; border-radius: 9px;
  border: 1px solid var(--border-strong); background: var(--surface);
  color: var(--text); font: inherit; font-size: 13px;
}
.fgroup textarea { resize: vertical; min-height: 54px; }
.fgroup input:focus, .fgroup textarea:focus, .fgroup select:focus { border-color: var(--aipu); outline: none; }
.frow { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.frow3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
.fcheck { display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 500; cursor: pointer; }
.fcheck input { accent-color: var(--aipu); width: 15px; height: 15px; }
.fsection {
  border-top: 1px solid var(--border); margin: 18px 0 14px; padding-top: 14px;
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted);
}
.accents { display: flex; gap: 8px; }
.acc {
  width: 30px; height: 30px; border-radius: 9px; cursor: pointer;
  border: 2px solid transparent; padding: 0;
}
.acc.gold { background: linear-gradient(110deg,#F5E1A4,#D4A647); }
.acc.purple { background: linear-gradient(110deg,#8B5CFF,#6366F1); }
.acc.emerald { background: linear-gradient(110deg,#34d399,#047857); }
.acc.crimson { background: linear-gradient(110deg,#F43F5E,#BE123C); }
.acc.blue { background: linear-gradient(110deg,#3B82F6,#1D4ED8); }
.acc.on { border-color: var(--text); box-shadow: 0 0 0 2px var(--bg-elevated), 0 0 0 4px var(--border-strong); }
.actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 18px; }
.status { font-size: 12px; color: var(--muted); margin-top: 10px; min-height: 18px; }
.status.ok { color: var(--omni); }
.status.err { color: #f87171; }
#duo-section[hidden] { display: none; }
.idline { font-size: 10.5px; color: var(--muted); font-family: 'JetBrains Mono', monospace; margin-top: 4px; }
</style>
</head>
<body>

<div class="topbar">
  <a class="crumb" href="index.php">&larr; Catalog</a>
  <h1>Widget Builder <span style="color:var(--muted);font-weight:500">· exit popups &amp; mobile sliders</span></h1>
  <div class="spacer"></div>
  <button type="button" class="btn" id="replay-btn" title="Re-fire the widget in the preview">Replay trigger</button>
</div>

<div class="wrap">

  <!-- ============ left: saved widgets ============ -->
  <aside class="list">
    <button type="button" class="btn btn-primary list-new" id="new-btn">+ New widget</button>
    <div class="list-head">
      <button type="button" class="list-filter on" data-filter="all">All</button>
      <button type="button" class="list-filter" data-filter="popup">Popups</button>
      <button type="button" class="list-filter" data-filter="slider">Sliders</button>
      <button type="button" class="list-filter" data-filter="social">Social</button>
    </div>
    <div id="wlist"></div>
  </aside>

  <!-- ============ center: live preview ============ -->
  <main class="stage">
    <div class="stage-bar">
      <div class="seg" id="vp-seg">
        <button type="button" data-vp="desktop" class="on">Desktop</button>
        <button type="button" data-vp="mobile">Mobile</button>
      </div>
      <div class="seg" id="pg-seg">
        <button type="button" data-pg="sales" class="on">Sales page</button>
        <button type="button" data-pg="checkout">Checkout</button>
      </div>
      <div class="spacer"></div>
      <span style="font-size:11.5px;color:var(--muted)" id="stage-note"></span>
    </div>
    <div class="stage-body">
      <div class="frame-wrap" id="frame-wrap">
        <iframe id="preview-frame" title="Widget preview"></iframe>
      </div>
    </div>
  </main>

  <!-- ============ right: edit form ============ -->
  <aside class="panel">
    <h2 id="form-title">Edit widget</h2>

    <div class="fgroup">
      <label for="f-name">Template name</label>
      <input type="text" id="f-name" placeholder="e.g. Lifetime $399 Exit Popup">
      <div class="idline" id="f-idline"></div>
    </div>

    <div class="frow">
      <div class="fgroup">
        <label for="f-type">Widget type</label>
        <select id="f-type">
          <option value="popup">Exit popup</option>
          <option value="slider">Mobile slider</option>
          <option value="social">Social proof</option>
        </select>
      </div>
      <div class="fgroup">
        <label for="f-design">Design</label>
        <select id="f-design">
          <option value="spotlight">Spotlight (single offer)</option>
          <option value="duo">Duo (two offers)</option>
        </select>
      </div>
    </div>

    <div class="fgroup">
      <label>Accent</label>
      <div class="accents" id="f-accents">
        <button type="button" class="acc gold on" data-acc="gold" title="Gold"></button>
        <button type="button" class="acc purple" data-acc="purple" title="Purple"></button>
        <button type="button" class="acc emerald" data-acc="emerald" title="Emerald"></button>
        <button type="button" class="acc crimson" data-acc="crimson" title="Crimson"></button>
        <button type="button" class="acc blue" data-acc="blue" title="Blue"></button>
      </div>
    </div>

    <div id="social-fields" hidden>
      <div class="fsection">Proof messages</div>
      <div class="fgroup">
        <label for="f-sp-messages">Messages (one per line · use {name}, {place}, {count})</label>
        <textarea id="f-sp-messages" style="min-height:96px" placeholder="{name} from {place} just bought the Lifetime plan&#10;{name} from {place} just upgraded to Scale"></textarea>
      </div>
      <div class="frow">
        <div class="fgroup">
          <label for="f-sp-names">Names (one per line)</label>
          <textarea id="f-sp-names" placeholder="Carl&#10;Maria&#10;Jake"></textarea>
        </div>
        <div class="fgroup">
          <label for="f-sp-places">Places (one per line)</label>
          <textarea id="f-sp-places" placeholder="Rhode Island&#10;Texas&#10;London"></textarea>
        </div>
      </div>

      <div class="fsection">Aggregate line (optional)</div>
      <div class="fgroup">
        <label class="fcheck"><input type="checkbox" id="f-sp-showcount"> Mix in a “N creators…” count line</label>
      </div>
      <div class="frow">
        <div class="fgroup">
          <label for="f-sp-countlabel">Count label</label>
          <input type="text" id="f-sp-countlabel" placeholder="creators upgraded today">
        </div>
        <div class="fgroup">
          <label for="f-sp-countsub">Count subtext</label>
          <input type="text" id="f-sp-countsub" placeholder="across AIPU">
        </div>
      </div>

      <div class="fsection">Behaviour</div>
      <div class="frow">
        <div class="fgroup">
          <label for="f-sp-position">Position</label>
          <select id="f-sp-position">
            <option value="bottom-left">Bottom left</option>
            <option value="bottom-right">Bottom right</option>
          </select>
        </div>
        <div class="fgroup">
          <label class="fcheck" style="margin-top:26px"><input type="checkbox" id="f-sp-link"> Clicking opens checkout</label>
        </div>
      </div>
      <div class="frow3">
        <div class="fgroup">
          <label for="f-sp-start">Start delay (s)</label>
          <input type="number" id="f-sp-start" min="0" max="120" value="5">
        </div>
        <div class="fgroup">
          <label for="f-sp-duration">Show for (s)</label>
          <input type="number" id="f-sp-duration" min="1" max="60" value="5">
        </div>
        <div class="fgroup">
          <label for="f-sp-interval">Gap between (s)</label>
          <input type="number" id="f-sp-interval" min="1" max="600" value="8">
        </div>
      </div>
    </div>

    <div id="offer-fields">
    <div class="fsection">Connected offer</div>

    <div class="frow">
      <div class="fgroup">
        <label for="f-plan">Plan</label>
        <select id="f-plan"><option value="">— none —</option></select>
      </div>
      <div class="fgroup">
        <label for="f-billing">Billing</label>
        <select id="f-billing">
          <option value="monthly">Monthly</option>
          <option value="yearly">Yearly</option>
          <option value="lifetime">Lifetime</option>
        </select>
      </div>
    </div>

    <div class="fgroup">
      <label for="f-title">Offer title</label>
      <textarea id="f-title" placeholder="Get Lifetime Access for just $399 — today only!"></textarea>
    </div>
    <div class="fgroup">
      <label for="f-subtitle">Subtitle</label>
      <textarea id="f-subtitle"></textarea>
    </div>
    <div class="frow3">
      <div class="fgroup">
        <label for="f-price">Price</label>
        <input type="text" id="f-price" placeholder="399">
      </div>
      <div class="fgroup">
        <label for="f-strike">Strike price</label>
        <input type="text" id="f-strike" placeholder="1900">
      </div>
      <div class="fgroup">
        <label for="f-pricemeta">Price meta</label>
        <input type="text" id="f-pricemeta" placeholder="one-time">
      </div>
    </div>
    <div class="fgroup">
      <label for="f-bonus">Bonus text</label>
      <input type="text" id="f-bonus" placeholder="Bonus: 2,500 free Nano Banana generations">
    </div>
    <div class="frow">
      <div class="fgroup">
        <label for="f-cta">CTA button text</label>
        <input type="text" id="f-cta" placeholder="Claim Lifetime Access">
      </div>
      <div class="fgroup">
        <label for="f-checkout-action">CTA on checkout</label>
        <select id="f-checkout-action">
          <option value="select_plan">Scroll + select plan</option>
          <option value="direct_payment">Direct to payment</option>
        </select>
      </div>
    </div>
    <div class="fgroup">
      <label for="f-image">Image URL (optional, popup hero)</label>
      <input type="text" id="f-image" placeholder="https://…">
    </div>

    <div id="duo-section" hidden>
      <div class="fsection">Second offer (duo)</div>
      <div class="frow">
        <div class="fgroup">
          <label for="f2-plan">Plan</label>
          <select id="f2-plan"><option value="">— none —</option></select>
        </div>
        <div class="fgroup">
          <label for="f2-billing">Billing</label>
          <select id="f2-billing">
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
            <option value="lifetime">Lifetime</option>
          </select>
        </div>
      </div>
      <div class="fgroup">
        <label for="f2-title">Title</label>
        <input type="text" id="f2-title">
      </div>
      <div class="fgroup">
        <label for="f2-bonus">Description</label>
        <textarea id="f2-bonus"></textarea>
      </div>
      <div class="frow3">
        <div class="fgroup">
          <label for="f2-price">Price</label>
          <input type="text" id="f2-price">
        </div>
        <div class="fgroup">
          <label for="f2-strike">Strike</label>
          <input type="text" id="f2-strike">
        </div>
        <div class="fgroup">
          <label for="f2-pricemeta">Price meta</label>
          <input type="text" id="f2-pricemeta">
        </div>
      </div>
      <div class="fgroup">
        <label for="f2-cta">CTA text</label>
        <input type="text" id="f2-cta">
      </div>
    </div>
    </div><!-- /#offer-fields -->

    <div id="urgency-fields">
    <div class="fsection">Urgency</div>
    <div class="frow">
      <div class="fgroup">
        <label for="f-cd-mode">Countdown</label>
        <select id="f-cd-mode">
          <option value="off">Off</option>
          <option value="today">Today only (to midnight)</option>
          <option value="minutes">Minutes timer</option>
        </select>
      </div>
      <div class="fgroup">
        <label for="f-cd-min">Minutes</label>
        <input type="number" id="f-cd-min" min="1" max="1440" value="15">
      </div>
    </div>

    <div class="fsection">Triggers</div>
    <div class="fgroup">
      <label class="fcheck"><input type="checkbox" id="f-tr-exit"> Exit intent (desktop mouse-out, mobile swipe-up / back)</label>
    </div>
    <div class="frow">
      <div class="fgroup">
        <label for="f-tr-scroll">Scroll % (0 = off)</label>
        <input type="number" id="f-tr-scroll" min="0" max="100" value="0">
      </div>
      <div class="fgroup">
        <label for="f-tr-delay">Delay seconds (0 = off)</label>
        <input type="number" id="f-tr-delay" min="0" max="3600" value="0">
      </div>
    </div>
    <div class="frow">
      <div class="fgroup">
        <label class="fcheck"><input type="checkbox" id="f-fq-session"> Once per session</label>
      </div>
      <div class="fgroup">
        <label for="f-fq-days">Show again after days (0 = every visit)</label>
        <input type="number" id="f-fq-days" min="0" max="365" value="0">
      </div>
    </div>
    </div><!-- /#urgency-fields -->

    <div class="fsection">Visibility</div>
    <div class="frow">
      <div class="fgroup"><label class="fcheck"><input type="checkbox" id="f-desktop" checked> Show on desktop</label></div>
      <div class="fgroup"><label class="fcheck"><input type="checkbox" id="f-mobile" checked> Show on mobile</label></div>
    </div>
    <div class="fgroup">
      <label class="fcheck"><input type="checkbox" id="f-active" checked> Active (available to flows)</label>
    </div>

    <div class="actions">
      <button type="button" class="btn btn-primary" id="save-btn">Save</button>
      <button type="button" class="btn" id="saveas-btn">Save as new</button>
      <button type="button" class="btn" id="dup-btn">Duplicate</button>
      <button type="button" class="btn btn-danger" id="del-btn">Delete</button>
    </div>
    <div class="status" id="status"></div>
  </aside>

</div>

<script>
window.WIDGET_DATA = <?= json_encode($widgetsList, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
window.PLAN_DATA = <?= json_encode(array_values($plansLib), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
</script>
<script>
(function () {
  'use strict';

  var widgets = Array.isArray(window.WIDGET_DATA) ? window.WIDGET_DATA : [];
  var plans = Array.isArray(window.PLAN_DATA) ? window.PLAN_DATA : [];
  var planById = {};
  plans.forEach(function (p) { if (p.id) planById[p.id] = p; });

  var sel = null;          // currently selected widget id (null = unsaved new)
  var current = null;      // working copy of the widget being edited
  var filter = 'all';
  var viewport = 'desktop';
  var page = 'sales';
  var dirty = false;

  var $ = function (id) { return document.getElementById(id); };
  var wlist = $('wlist');
  var statusEl = $('status');
  var frame = $('preview-frame');

  function defaultSocial() {
    return {
      messages: [
        '{name} from {place} just bought the Lifetime plan',
        '{name} from {place} just upgraded to Scale'
      ],
      names: ['Carl', 'Maria', 'Jake', 'Aisha', 'Liam', 'Sofia', 'Noah', 'Priya', 'Marcus', 'Elena'],
      places: ['Rhode Island', 'Texas', 'London', 'Toronto', 'Berlin', 'Austin', 'Madrid', 'Miami'],
      position: 'bottom-left',
      start_delay: 5, duration: 5, interval: 8,
      show_count: true, count_label: 'creators upgraded today', count_sub: 'across AIPU',
      link_checkout: true
    };
  }

  var TYPE_LABEL = { popup: 'popup', slider: 'slider', social: 'social' };

  function blankWidget() {
    return {
      id: '', name: '', type: 'popup', design: 'spotlight', accent: 'gold',
      active: true, builtin: false,
      plan_id: null, billing_slot: 'monthly',
      title: '', subtitle: '', price: '', price_meta: '', strike_price: '',
      bonus_text: '', cta_text: '', checkout_action: 'select_plan', image: '',
      countdown: { mode: 'off', minutes: 15 },
      show_desktop: true, show_mobile: true,
      secondary: null, social: null,
      triggers: { exit_intent: true, scroll_pct: 0, delay_seconds: 0 },
      frequency: { once_per_session: true, show_again_days: 3 },
      order: 0
    };
  }

  function slugify(s) {
    return String(s || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 50);
  }

  function linesToArr(s) {
    return String(s || '').split('\n').map(function (x) { return x.trim(); }).filter(Boolean);
  }

  function setStatus(msg, kind) {
    statusEl.textContent = msg || '';
    statusEl.className = 'status' + (kind ? ' ' + kind : '');
    if (msg) setTimeout(function () { if (statusEl.textContent === msg) { statusEl.textContent = ''; statusEl.className = 'status'; } }, 4000);
  }

  // ---------------- list ----------------
  function renderList() {
    wlist.innerHTML = '';
    widgets
      .filter(function (w) { return filter === 'all' || w.type === filter; })
      .forEach(function (w) {
        var card = document.createElement('div');
        card.className = 'wcard' + (w.id === sel ? ' sel' : '');
        card.innerHTML =
          '<span class="wdot ' + (w.accent || 'gold') + '"></span>' +
          '<span class="winfo"><span class="nm"></span><span class="ty">' +
          (TYPE_LABEL[w.type] || 'popup') + (w.builtin ? ' · default' : '') +
          '</span></span>' +
          '<span class="wstate ' + (w.active ? 'on' : 'off') + '">' + (w.active ? 'on' : 'off') + '</span>';
        card.querySelector('.nm').textContent = w.name || w.id;
        card.addEventListener('click', function () { select(w.id); });
        wlist.appendChild(card);
      });
  }

  document.querySelectorAll('.list-filter').forEach(function (b) {
    b.addEventListener('click', function () {
      filter = b.getAttribute('data-filter');
      document.querySelectorAll('.list-filter').forEach(function (x) { x.classList.toggle('on', x === b); });
      renderList();
    });
  });

  // ---------------- form <-> model ----------------
  function fillPlanSelects() {
    [['f-plan', true], ['f2-plan', true]].forEach(function (pair) {
      var el = $(pair[0]);
      el.innerHTML = '<option value="">— none —</option>';
      plans.forEach(function (p) {
        var o = document.createElement('option');
        o.value = p.id;
        o.textContent = p.display_name + (p.monthly_price ? ' · $' + p.monthly_price + '/mo' : (p.lifetime_price ? ' · $' + p.lifetime_price + ' once' : ''));
        el.appendChild(o);
      });
    });
  }

  function socialToForm(sp) {
    sp = sp || defaultSocial();
    $('f-sp-messages').value = (sp.messages || []).join('\n');
    $('f-sp-names').value = (sp.names || []).join('\n');
    $('f-sp-places').value = (sp.places || []).join('\n');
    $('f-sp-position').value = sp.position === 'bottom-right' ? 'bottom-right' : 'bottom-left';
    $('f-sp-start').value = sp.start_delay != null ? sp.start_delay : 5;
    $('f-sp-duration').value = sp.duration != null ? sp.duration : 5;
    $('f-sp-interval').value = sp.interval != null ? sp.interval : 8;
    $('f-sp-showcount').checked = !!sp.show_count;
    $('f-sp-countlabel').value = sp.count_label || '';
    $('f-sp-countsub').value = sp.count_sub || '';
    $('f-sp-link').checked = !!sp.link_checkout;
  }

  function applyTypeUI(type) {
    var isSocial = type === 'social';
    $('offer-fields').hidden = isSocial;
    $('urgency-fields').hidden = isSocial;
    $('social-fields').hidden = !isSocial;
    $('f-design').disabled = (type === 'slider' || isSocial);
    if (!isSocial) {
      $('duo-section').hidden = !(type === 'popup' && $('f-design').value === 'duo');
    }
  }

  function modelToForm(w) {
    $('f-name').value = w.name || '';
    $('f-idline').textContent = w.id ? 'id: ' + w.id : 'id will be generated from the name';
    $('f-type').value = w.type || 'popup';
    $('f-design').value = w.design === 'duo' ? 'duo' : 'spotlight';
    document.querySelectorAll('#f-accents .acc').forEach(function (a) {
      a.classList.toggle('on', a.getAttribute('data-acc') === (w.accent || 'gold'));
    });
    $('f-plan').value = w.plan_id || '';
    $('f-billing').value = w.billing_slot || 'monthly';
    $('f-title').value = w.title || '';
    $('f-subtitle').value = w.subtitle || '';
    $('f-price').value = w.price || '';
    $('f-strike').value = w.strike_price || '';
    $('f-pricemeta').value = w.price_meta || '';
    $('f-bonus').value = w.bonus_text || '';
    $('f-cta').value = w.cta_text || '';
    $('f-checkout-action').value = w.checkout_action || 'select_plan';
    $('f-image').value = w.image || '';
    var s = w.secondary || {};
    $('f2-plan').value = s.plan_id || '';
    $('f2-billing').value = s.billing_slot || 'monthly';
    $('f2-title').value = s.title || '';
    $('f2-bonus').value = s.bonus_text || '';
    $('f2-price').value = s.price || '';
    $('f2-strike').value = s.strike_price || '';
    $('f2-pricemeta').value = s.price_meta || '';
    $('f2-cta').value = s.cta_text || '';
    socialToForm(w.social || defaultSocial());
    applyTypeUI(w.type || 'popup');
    $('f-cd-mode').value = (w.countdown && w.countdown.mode) || 'off';
    $('f-cd-min').value = (w.countdown && w.countdown.minutes) || 15;
    $('f-tr-exit').checked = !!(w.triggers && w.triggers.exit_intent);
    $('f-tr-scroll').value = (w.triggers && w.triggers.scroll_pct) || 0;
    $('f-tr-delay').value = (w.triggers && w.triggers.delay_seconds) || 0;
    $('f-fq-session').checked = !!(w.frequency && w.frequency.once_per_session);
    $('f-fq-days').value = (w.frequency && w.frequency.show_again_days) || 0;
    $('f-desktop').checked = !!w.show_desktop;
    $('f-mobile').checked = !!w.show_mobile;
    $('f-active').checked = !!w.active;
    $('form-title').textContent = w.id ? 'Edit: ' + (w.name || w.id) : 'New widget';
    $('del-btn').disabled = !w.id;
  }

  function formToModel() {
    var w = current || blankWidget();
    w.name = $('f-name').value.trim();
    if (!w.id) w.id = slugify(w.name);
    w.type = $('f-type').value;
    w.design = w.type === 'slider' ? 'bar' : (w.type === 'social' ? 'toast' : $('f-design').value);
    var accOn = document.querySelector('#f-accents .acc.on');
    w.accent = accOn ? accOn.getAttribute('data-acc') : 'gold';
    w.plan_id = $('f-plan').value || null;
    w.billing_slot = $('f-billing').value;
    w.title = $('f-title').value.trim();
    w.subtitle = $('f-subtitle').value.trim();
    w.price = $('f-price').value.trim();
    w.strike_price = $('f-strike').value.trim();
    w.price_meta = $('f-pricemeta').value.trim();
    w.bonus_text = $('f-bonus').value.trim();
    w.cta_text = $('f-cta').value.trim();
    w.checkout_action = $('f-checkout-action').value;
    w.image = $('f-image').value.trim();
    if (w.type === 'popup' && $('f-design').value === 'duo') {
      w.secondary = {
        plan_id: $('f2-plan').value || null,
        billing_slot: $('f2-billing').value,
        title: $('f2-title').value.trim(),
        bonus_text: $('f2-bonus').value.trim(),
        price: $('f2-price').value.trim(),
        strike_price: $('f2-strike').value.trim(),
        price_meta: $('f2-pricemeta').value.trim(),
        cta_text: $('f2-cta').value.trim(),
        subtitle: ''
      };
    } else {
      w.secondary = null;
    }
    if (w.type === 'social') {
      w.social = {
        messages: linesToArr($('f-sp-messages').value),
        names: linesToArr($('f-sp-names').value),
        places: linesToArr($('f-sp-places').value),
        position: $('f-sp-position').value === 'bottom-right' ? 'bottom-right' : 'bottom-left',
        start_delay: parseInt($('f-sp-start').value, 10) || 0,
        duration: parseInt($('f-sp-duration').value, 10) || 5,
        interval: parseInt($('f-sp-interval').value, 10) || 8,
        show_count: $('f-sp-showcount').checked,
        count_label: $('f-sp-countlabel').value.trim(),
        count_sub: $('f-sp-countsub').value.trim(),
        link_checkout: $('f-sp-link').checked
      };
      w.plan_id = null;
      w.secondary = null;
    } else {
      w.social = null;
    }
    w.countdown = { mode: $('f-cd-mode').value, minutes: parseInt($('f-cd-min').value, 10) || 15 };
    w.triggers = {
      exit_intent: $('f-tr-exit').checked,
      scroll_pct: parseInt($('f-tr-scroll').value, 10) || 0,
      delay_seconds: parseInt($('f-tr-delay').value, 10) || 0
    };
    w.frequency = {
      once_per_session: $('f-fq-session').checked,
      show_again_days: parseInt($('f-fq-days').value, 10) || 0
    };
    w.show_desktop = $('f-desktop').checked;
    w.show_mobile = $('f-mobile').checked;
    w.active = $('f-active').checked;
    return w;
  }

  // ---------------- plan auto-fill ----------------
  function priceFor(p, slot) {
    if (!p) return ['', ''];
    if (slot === 'lifetime') return [p.lifetime_price || '', p.lifetime_strike || ''];
    if (slot === 'yearly') return [p.yearly_price || '', p.yearly_strike || ''];
    return [p.monthly_price || '', p.monthly_strike || ''];
  }
  function autoFillFromPlan(prefix) {
    var planSel = prefix === 'f2' ? 'f2-plan' : 'f-plan';
    var billSel = prefix === 'f2' ? 'f2-billing' : 'f-billing';
    var p = planById[$(planSel).value];
    if (!p) return;
    // pick a sensible default billing slot for the plan
    var slot = $(billSel).value;
    if (slot === 'lifetime' && !p.lifetime_price) slot = p.monthly_price ? 'monthly' : 'yearly';
    if (slot === 'monthly' && !p.monthly_price && p.lifetime_price) slot = 'lifetime';
    $(billSel).value = slot;
    var pr = priceFor(p, slot);
    if (prefix === 'f2') {
      $('f2-price').value = pr[0]; $('f2-strike').value = pr[1];
      $('f2-pricemeta').value = slot === 'lifetime' ? 'one-time · lifetime access' : (slot === 'yearly' ? '/ year' : '/ month · cancel anytime');
      if (!$('f2-cta').value.trim()) $('f2-cta').value = slot === 'lifetime' ? 'Claim Lifetime Access' : 'Claim ' + p.display_name + ' Plan';
      if (!$('f2-title').value.trim()) $('f2-title').value = p.display_name + ' Plan';
    } else {
      $('f-price').value = pr[0]; $('f-strike').value = pr[1];
      $('f-pricemeta').value = slot === 'lifetime' ? 'one-time · lifetime access' : (slot === 'yearly' ? '/ year' : '/ month · cancel anytime');
      if (!$('f-cta').value.trim()) $('f-cta').value = slot === 'lifetime' ? 'Claim Lifetime Access' : 'Claim ' + p.display_name + ' Plan';
      if (!$('f-title').value.trim()) {
        $('f-title').value = slot === 'lifetime'
          ? 'Get Lifetime Access for just $' + pr[0] + ' — today only!'
          : 'Get the ' + p.display_name + ' Plan for just $' + pr[0] + (slot === 'yearly' ? '/yr' : '/mo') + '!';
      }
    }
    onFormChange();
  }
  $('f-plan').addEventListener('change', function () { autoFillFromPlan('f'); });
  $('f-billing').addEventListener('change', function () { autoFillFromPlan('f'); });
  $('f2-plan').addEventListener('change', function () { autoFillFromPlan('f2'); });
  $('f2-billing').addEventListener('change', function () { autoFillFromPlan('f2'); });

  // ---------------- preview ----------------
  var previewTimer = null;
  function refreshPreview() {
    var w = formToModel();
    var payload = { widget: JSON.parse(JSON.stringify(w)), page: page };
    if (!payload.widget.id) payload.widget.id = 'preview';
    // in preview, always show on both viewports so the admin can see it
    payload.widget.show_desktop = true;
    payload.widget.show_mobile = true;
    fetch('widgets.php?preview=1', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function (r) { return r.text(); })
      .then(function (html) { frame.srcdoc = html; })
      .catch(function () { setStatus('Preview failed', 'err'); });
  }
  function schedulePreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(refreshPreview, 450);
  }
  function onFormChange() {
    dirty = true;
    current = formToModel();
    applyTypeUI($('f-type').value);
    $('f-idline').textContent = current.id ? 'id: ' + current.id : 'id will be generated from the name';
    schedulePreview();
  }
  document.querySelectorAll('.panel input, .panel select, .panel textarea').forEach(function (el) {
    el.addEventListener('input', onFormChange);
    el.addEventListener('change', onFormChange);
  });
  // switching a blank widget to "social" pre-fills sensible defaults
  $('f-type').addEventListener('change', function () {
    if ($('f-type').value === 'social' && !$('f-sp-messages').value.trim()) {
      socialToForm(defaultSocial());
      onFormChange();
    }
  });
  document.querySelectorAll('#f-accents .acc').forEach(function (a) {
    a.addEventListener('click', function () {
      document.querySelectorAll('#f-accents .acc').forEach(function (x) { x.classList.toggle('on', x === a); });
      onFormChange();
    });
  });

  $('replay-btn').addEventListener('click', function () {
    try {
      if (frame.contentWindow && frame.contentWindow.__kkwReplay) frame.contentWindow.__kkwReplay();
    } catch (e) {}
  });

  // viewport / page segments
  $('vp-seg').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    viewport = b.getAttribute('data-vp');
    document.querySelectorAll('#vp-seg button').forEach(function (x) { x.classList.toggle('on', x === b); });
    $('frame-wrap').classList.toggle('mobile', viewport === 'mobile');
  });
  $('pg-seg').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    page = b.getAttribute('data-pg');
    document.querySelectorAll('#pg-seg button').forEach(function (x) { x.classList.toggle('on', x === b); });
    refreshPreview();
  });

  // ---------------- select / new ----------------
  function select(id) {
    var w = widgets.find(function (x) { return x.id === id; });
    if (!w) return;
    sel = id;
    current = JSON.parse(JSON.stringify(w));
    dirty = false;
    renderList();
    modelToForm(current);
    refreshPreview();
  }

  $('new-btn').addEventListener('click', function () {
    sel = null;
    current = blankWidget();
    dirty = false;
    renderList();
    modelToForm(current);
    refreshPreview();
  });

  // ---------------- save / delete / duplicate ----------------
  function apiSave(action, w) {
    return fetch('widget-library.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: action, widget: w })
    }).then(function (r) { return r.json(); });
  }

  function afterSave(res, savedId) {
    if (!res.ok) { setStatus(res.error || 'Save failed', 'err'); return; }
    widgets = res.widgets || widgets;
    sel = savedId;
    current = JSON.parse(JSON.stringify(widgets.find(function (x) { return x.id === savedId; }) || current));
    dirty = false;
    renderList();
    modelToForm(current);
    setStatus('Saved', 'ok');
  }

  $('save-btn').addEventListener('click', function () {
    var w = formToModel();
    if (!w.name) { setStatus('Name is required', 'err'); return; }
    if (!w.id) w.id = slugify(w.name);
    if (!w.id) { setStatus('Could not derive an id from the name', 'err'); return; }
    var exists = widgets.some(function (x) { return x.id === w.id; });
    apiSave(exists ? 'update' : 'create', w).then(function (res) { afterSave(res, w.id); });
  });

  $('saveas-btn').addEventListener('click', function () {
    var w = formToModel();
    if (!w.name) { setStatus('Name is required', 'err'); return; }
    var base = slugify(w.name) || 'widget';
    var id = base, n = 2;
    while (widgets.some(function (x) { return x.id === id; })) { id = base + '-' + (n++); }
    w.id = id;
    w.builtin = false;
    apiSave('create', w).then(function (res) { afterSave(res, id); });
  });

  $('dup-btn').addEventListener('click', function () {
    var w = formToModel();
    var base = (w.id || slugify(w.name) || 'widget') + '-copy';
    var id = base, n = 2;
    while (widgets.some(function (x) { return x.id === id; })) { id = base + '-' + (n++); }
    w = JSON.parse(JSON.stringify(w));
    w.id = id;
    w.name = (w.name || w.id) + ' (copy)';
    w.builtin = false;
    apiSave('create', w).then(function (res) { afterSave(res, id); });
  });

  $('del-btn').addEventListener('click', function () {
    if (!sel) return;
    if (!confirm('Delete widget "' + (current.name || sel) + '"? Flows using it will stop showing it on their next rebuild.')) return;
    fetch('widget-library.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id: sel })
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (!res.ok) { setStatus(res.error || 'Delete failed', 'err'); return; }
      widgets = res.widgets || [];
      sel = null;
      current = blankWidget();
      renderList();
      modelToForm(current);
      refreshPreview();
      setStatus('Deleted', 'ok');
    });
  });

  // ---------------- boot ----------------
  fillPlanSelects();
  renderList();
  if (widgets.length) {
    select(widgets[0].id);
  } else {
    $('new-btn').click();
  }
})();
</script>
</body>
</html>
