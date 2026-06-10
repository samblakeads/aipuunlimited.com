<?php
declare(strict_types=1);

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__));
$host = $_SERVER['HTTP_HOST'] ?? 'aipuunlimited.com';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $host;

$collections = [
    [
        'id' => 'sales-pages',
        'label' => 'Sales Pages',
        'brand' => 'neutral',
        'dir' => 'sales-pages',
        'description' => 'Brand-neutral sales pages (no header/footer) — pick the brand at Create Flow',
    ],
    [
        'id' => 'checkout-pages',
        'label' => 'Checkouts',
        'brand' => 'neutral',
        'dir' => 'checkout-pages',
        'description' => 'Brand-neutral checkouts (no header/footer) — pick the brand at Create Flow',
    ],
    [
        'id' => 'single-pages',
        'label' => 'Single Page',
        'brand' => 'neutral',
        'dir' => 'single-pages',
        'description' => 'Self-contained multi-page sites whose checkout is an in-page billing pop-up — no separate checkout page. Pick the brand at Create Flow.',
        // Items here become single-page flows (whole site + pop-up checkout).
        'kind' => 'single-page',
    ],
    [
        'id' => 'aipu-landers',
        'label' => 'AIPU Landers',
        'brand' => 'aipu',
        'dir' => 'aipu-landers',
        'description' => 'Single-step AIPU presell landing pages',
        'hidden' => true,
    ],
    [
        'id' => 'omnirogue-landers',
        'label' => 'OmniRogue Landers',
        'brand' => 'omni',
        'dir' => 'omnirogue-landers',
        'description' => 'Single-step OmniRogue presell landing pages',
        'hidden' => true,
    ],
    [
        'id' => 'omnirogue-checkouts',
        'label' => 'OmniRogue Checkouts',
        'brand' => 'omni',
        'dir' => 'omnirogue-checkouts',
        'description' => 'Checkout / plan-picker pages for OmniRogue funnels',
        'hidden' => true,
    ],
    [
        'id' => 'aipu-checkouts',
        'label' => 'AIPU Checkouts',
        'brand' => 'aipu',
        'dir' => 'aipu-checkouts',
        'description' => 'Checkout / plan-picker pages for AIPU funnels',
        'hidden' => true,
    ],
];

function scanCollection(string $docRoot, string $webDir): array
{
    $fullDir = $docRoot . '/' . $webDir;
    $items = [];

    if (!is_dir($fullDir)) {
        return $items;
    }

    foreach (scandir($fullDir) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if ($entry[0] === '_' || $entry[0] === '.') {
            continue;
        }

        $itemDir = $fullDir . '/' . $entry;
        if (!is_dir($itemDir)) {
            continue;
        }

        $entryFile = null;
        foreach (['index.html', 'index.php'] as $candidate) {
            if (is_file($itemDir . '/' . $candidate)) {
                $entryFile = $candidate;
                break;
            }
        }

        if ($entryFile === null) {
            continue;
        }

        $webPath = '/' . $webDir . '/' . $entry . '/' . $entryFile;
        $items[] = [
            'name' => $entry,
            'entryFile' => $entryFile,
            'fsPath' => $itemDir,
            'webPath' => $webPath,
            'modified' => filemtime($itemDir . '/' . $entryFile),
        ];
    }

    usort($items, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));

    return $items;
}

foreach ($collections as &$collection) {
    $collection['items'] = scanCollection($docRoot, $collection['dir']);
    $collection['count'] = count($collection['items']);
}
unset($collection);

$visibleCollections = array_values(array_filter(
    $collections,
    static fn(array $c): bool => empty($c['hidden'])
));
$totalCount = array_sum(array_column($visibleCollections, 'count'));

// --------------------------------------------------------------- flow builder
function scanFlows(string $docRoot): array
{
    $dir = $docRoot . '/flows';
    $flows = [];
    if (!is_dir($dir)) {
        return $flows;
    }
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..' || $entry[0] === '_' || $entry[0] === '.') {
            continue;
        }
        $fdir = $dir . '/' . $entry;
        if (!is_dir($fdir) || !is_file($fdir . '/index.html')) {
            continue;
        }
        $hasCheckout = is_file($fdir . '/checkout.html');

        $meta = [];
        if (is_file($fdir . '/flow.json')) {
            $meta = json_decode((string)file_get_contents($fdir . '/flow.json'), true) ?: [];
        }
        $brand = $meta['brand'] ?? null;
        if ($brand !== 'aipu' && $brand !== 'omni') {
            // Fallback: infer from the folder suffix, lander web path or content
            if (preg_match('/__aipu$/', $entry)) {
                $brand = 'aipu';
            } elseif (preg_match('/__omni$/', $entry)) {
                $brand = 'omni';
            } else {
                $probe = strtolower(($meta['lander_web'] ?? '') . ' ' . (string)@file_get_contents($fdir . '/index.html', false, null, 0, 4000));
                $brand = (strpos($probe, 'omnirogue') !== false || strpos($probe, 'omni') !== false) ? 'omni' : 'aipu';
            }
        }
        $kkReady = !empty($meta['kk']) && is_dir($fdir . '/kk');

        // Shared pairing key: the base slug both brand builds share.
        $flowBase = $meta['flow_base'] ?? '';
        if ($flowBase === '') {
            $flowBase = preg_replace('/__(aipu|omni)$/', '', $entry);
            if ($flowBase === '') {
                $flowBase = $entry;
            }
        }

        $flows[] = [
            'name' => $entry,
            'brand' => $brand,
            'flowBase' => $flowBase,
            'flowType' => $meta['flow_type'] ?? ($hasCheckout ? 'multi' : 'sales-only'),
            'salesPath' => '/flows/' . $entry . '/index.html',
            'checkoutPath' => $hasCheckout ? '/flows/' . $entry . '/checkout.html' : null,
            'fsPath' => $fdir,
            'modified' => filemtime($fdir . '/index.html'),
            'created' => (int)($meta['built_at'] ?? filemtime($fdir . '/flow.json') ?: filemtime($fdir . '/index.html')),
            'kk' => $kkReady,
            'kkName' => $meta['kk_name'] ?? $entry,
            'kkOfferWired' => $meta['kk_offer_wired'] ?? null,
        ];
    }
    usort($flows, static fn(array $a, array $b): int => $b['modified'] <=> $a['modified']);
    return $flows;
}

$flows = scanFlows($docRoot);

// Group the per-brand builds into one "Sales Flow" each, keyed by flow_base.
$flowGroups = [];
foreach ($flows as $f) {
    $base = $f['flowBase'];
    if (!isset($flowGroups[$base])) {
        $flowGroups[$base] = [
            'base' => $base,
            'variants' => [],
            'modified' => 0,
            'created' => $f['created'],
            'flowType' => $f['flowType'],
        ];
    }
    $flowGroups[$base]['variants'][$f['brand']] = $f;
    if ($f['modified'] > $flowGroups[$base]['modified']) {
        $flowGroups[$base]['modified'] = $f['modified'];
    }
    if ($f['created'] > 0 && $f['created'] < $flowGroups[$base]['created']) {
        $flowGroups[$base]['created'] = $f['created'];
    }
}
uasort($flowGroups, static fn(array $a, array $b): int => $b['modified'] <=> $a['modified']);
$flowGroups = array_values($flowGroups);
$flowCount = count($flowGroups);

// Neutral collections expose brand === null so the wizard knows not to filter or
// badge them by brand; legacy collections keep their brand.
$landerOptions = [];
foreach (scanCollection($docRoot, 'sales-pages') as $it) {
    $landerOptions[] = ['collection' => 'sales-pages', 'brand' => null, 'name' => $it['name'], 'webPath' => $it['webPath']];
}
$checkoutOptions = [];
foreach (scanCollection($docRoot, 'checkout-pages') as $it) {
    $checkoutOptions[] = ['collection' => 'checkout-pages', 'brand' => null, 'name' => $it['name'], 'webPath' => $it['webPath']];
}

// ------------------------------------------------------------------- archive
// Soft-deleted landers / checkouts / flows, restorable from the Archive view.
require_once __DIR__ . '/lib/archive.php';
$archivedEntries = archive_list($docRoot);
$archivedCount = count($archivedEntries);
// id => human label, for showing where an archived entry came from.
$collectionLabels = array_column($collections, 'label', 'id');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lander Catalog · AIPU / OmniRogue</title>
<script>
(function () {
  var t = localStorage.getItem('catalog-theme');
  document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="ai/editor.css">
<style>
/* Dark = Cursor-inspired · Light = Apple-inspired */
:root,
[data-theme="dark"] {
  color-scheme: dark;
  --bg: #0a0a0a;
  --bg-elevated: #111111;
  --surface: #141414;
  --surface-2: #1c1c1c;
  --border: rgba(255, 255, 255, 0.08);
  --border-strong: rgba(255, 255, 255, 0.14);
  --text: #ededed;
  --muted: #888888;
  --aipu: #818cf8;
  --aipu-dim: rgba(129, 140, 248, 0.14);
  --omni: #34d399;
  --omni-dim: rgba(52, 211, 153, 0.14);
  --accent: #ffffff;
  --accent-fg: #0a0a0a;
  --accent-soft: rgba(255, 255, 255, 0.08);
  --code-text: #b4b4b4;
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.35);
  --shadow-md: 0 8px 32px rgba(0, 0, 0, 0.45);
  --shadow-lg: 0 24px 80px rgba(0, 0, 0, 0.55);
  --overlay: rgba(0, 0, 0, 0.72);
  --overlay-soft: rgba(0, 0, 0, 0.55);
  --sidebar-bg: rgba(20, 20, 20, 0.82);
  --radius: 14px;
  --sidebar-w: 272px;
  --focus-ring: 0 0 0 3px rgba(255, 255, 255, 0.12);
}

[data-theme="light"] {
  color-scheme: light;
  --bg: #f5f5f7;
  --bg-elevated: #ffffff;
  --surface: #ffffff;
  --surface-2: #fbfbfd;
  --border: rgba(0, 0, 0, 0.08);
  --border-strong: rgba(0, 0, 0, 0.12);
  --text: #1d1d1f;
  --muted: #86868b;
  --aipu: #5856d6;
  --aipu-dim: rgba(88, 86, 214, 0.12);
  --omni: #059669;
  --omni-dim: rgba(5, 150, 105, 0.12);
  --accent: #0071e3;
  --accent-fg: #ffffff;
  --accent-soft: rgba(0, 113, 227, 0.1);
  --code-text: #424245;
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04);
  --shadow-md: 0 8px 30px rgba(0, 0, 0, 0.08);
  --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.12);
  --overlay: rgba(0, 0, 0, 0.35);
  --overlay-soft: rgba(255, 255, 255, 0.72);
  --sidebar-bg: rgba(255, 255, 255, 0.78);
  --radius: 16px;
  --sidebar-w: 272px;
  --focus-ring: 0 0 0 4px rgba(0, 113, 227, 0.18);
}

*, *::before, *::after { box-sizing: border-box; }

html { scroll-behavior: smooth; }

body {
  margin: 0;
  font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
  background: var(--bg);
  color: var(--text);
  line-height: 1.5;
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  transition: background 0.25s ease, color 0.25s ease;
}

[data-theme="light"] body {
  background:
    radial-gradient(ellipse 80% 50% at 50% -20%, rgba(0, 113, 227, 0.06), transparent),
    var(--bg);
}

[data-theme="dark"] body {
  background:
    radial-gradient(ellipse 70% 45% at 50% -15%, rgba(255, 255, 255, 0.03), transparent),
    var(--bg);
}

.layout {
  display: grid;
  grid-template-columns: var(--sidebar-w) 1fr;
  min-height: 100vh;
}

.sidebar {
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  border-right: 1px solid var(--border);
  background: var(--sidebar-bg);
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  padding: 1.35rem 0;
}

.sidebar-header {
  padding: 0 1.35rem 1.35rem;
  border-bottom: 1px solid var(--border);
  margin-bottom: 0.85rem;
}

.sidebar-header h1 {
  margin: 0 0 0.4rem;
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.sidebar-header p {
  margin: 0;
  font-size: 0.8rem;
  color: var(--muted);
  line-height: 1.45;
}

.stat-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin-top: 0.85rem;
  padding: 0.3rem 0.7rem;
  border-radius: 999px;
  background: var(--accent-soft);
  border: 1px solid var(--border);
  font-size: 0.72rem;
  color: var(--muted);
  box-shadow: var(--shadow-sm);
}

.stat-pill strong { color: var(--text); }

.nav-section {
  padding: 0.35rem 0.75rem;
}

.nav-section-label {
  padding: 0.4rem 0.5rem;
  font-size: 0.65rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--muted);
}

.nav-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.55rem 0.75rem;
  border-radius: 8px;
  color: var(--text);
  text-decoration: none;
  font-size: 0.85rem;
  transition: background 0.15s;
}

.nav-link:hover { background: var(--surface-2); }

.nav-link.active { background: var(--surface-2); font-weight: 600; }

.nav-count {
  font-size: 0.72rem;
  color: var(--muted);
  background: var(--bg);
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  min-width: 1.4rem;
  text-align: center;
}

.main {
  padding: 1.5rem 2rem 3rem;
  max-width: 1400px;
}

.topbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.search-wrap {
  flex: 1;
  min-width: 220px;
  position: relative;
}

.search-wrap svg {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  pointer-events: none;
}

#search {
  width: 100%;
  padding: 0.7rem 0.9rem 0.7rem 2.5rem;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--surface);
  color: var(--text);
  font: inherit;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-shadow: var(--shadow-sm);
}

#search:focus {
  border-color: var(--border-strong);
  box-shadow: var(--focus-ring);
}

.view-toggle,
.theme-toggle {
  display: flex;
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
  background: var(--surface);
  box-shadow: var(--shadow-sm);
}

.view-toggle button,
.theme-toggle button {
  padding: 0.58rem 0.85rem;
  border: none;
  background: transparent;
  color: var(--muted);
  font: inherit;
  font-size: 0.82rem;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}

.view-toggle button + button,
.theme-toggle button + button {
  border-left: 1px solid var(--border);
}

.view-toggle button.active,
.theme-toggle button.active {
  background: var(--accent-soft);
  color: var(--text);
  font-weight: 600;
}

.theme-toggle button svg {
  display: block;
  width: 16px;
  height: 16px;
}

.collection {
  margin-bottom: 3rem;
  scroll-margin-top: 1rem;
}

.collection-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border);
}

.collection-title {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin: 0;
  font-size: 1.45rem;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.brand-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.brand-dot.aipu { background: var(--aipu); box-shadow: 0 0 10px var(--aipu-dim); }
.brand-dot.omni { background: var(--omni); box-shadow: 0 0 10px var(--omni-dim); }
.brand-dot.neutral { background: linear-gradient(135deg, var(--aipu), var(--omni)); }

.collection-desc {
  margin: 0.25rem 0 0;
  font-size: 0.85rem;
  color: var(--muted);
}

.collection-meta {
  font-family: "JetBrains Mono", monospace;
  font-size: 0.75rem;
  color: var(--muted);
  background: var(--surface);
  border: 1px solid var(--border);
  padding: 0.35rem 0.65rem;
  border-radius: 8px;
}

.empty-state {
  padding: 2.5rem;
  text-align: center;
  border: 1px dashed var(--border);
  border-radius: var(--radius);
  color: var(--muted);
  background: var(--surface);
}

.empty-state code {
  display: inline-block;
  margin-top: 0.5rem;
  padding: 0.2rem 0.5rem;
  background: var(--surface-2);
  border-radius: 6px;
  font-size: 0.8rem;
  color: var(--text);
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.25rem;
}

.grid.list-view {
  grid-template-columns: 1fr;
}

.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: border-color 0.18s, box-shadow 0.18s, transform 0.18s;
  box-shadow: var(--shadow-sm);
}

.card:hover {
  border-color: var(--border-strong);
  box-shadow: var(--shadow-md);
  /* No transform here — transform on .card breaks position:fixed dropdown menus. */
}

.card.hidden { display: none; }

.preview-frame-wrap {
  position: relative;
  background: #fff;
  border-bottom: 1px solid var(--border);
  height: 220px;
  overflow: hidden;
}

.preview-frame-wrap::after {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,0.06);
}

.preview-frame-wrap iframe {
  width: 1440px;
  height: 900px;
  border: none;
  transform: scale(0.24);
  transform-origin: top left;
  pointer-events: none;
}

.preview-frame-wrap.mobile-thumb {
  display: flex;
  align-items: flex-start;
  justify-content: center;
}

.preview-frame-wrap.mobile-thumb iframe {
  width: 390px;
  height: 844px;
  transform: scale(0.26);
  transform-origin: top center;
}

.btn-mobile {
  background: transparent;
  border-color: rgba(56, 189, 248, 0.45);
  color: #38bdf8;
}
.btn-mobile:hover { background: rgba(56, 189, 248, 0.12); border-color: #38bdf8; color: #fff; }

.preview-overlay-links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  justify-content: center;
}

/* ---- Mobile preview modal ---- */
.mobile-preview-overlay {
  position: fixed;
  inset: 0;
  z-index: 300;
  display: none;
  flex-direction: column;
  background: var(--overlay);
  backdrop-filter: blur(10px);
}
.mobile-preview-overlay.open { display: flex; }

.mobile-preview-toolbar {
  flex-shrink: 0;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem;
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid var(--border);
  background: var(--surface);
}

.mobile-preview-toolbar h2 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  flex: 1;
  min-width: 140px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mobile-preview-controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.mobile-preview-controls label {
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted);
}

.mobile-preview-controls select {
  padding: 0.4rem 0.55rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--bg);
  color: var(--text);
  font: inherit;
  font-size: 0.82rem;
}

.mobile-page-tabs {
  display: flex;
  border: 1px solid var(--border);
  border-radius: 8px;
  overflow: hidden;
}
.mobile-page-tabs button {
  padding: 0.4rem 0.75rem;
  border: none;
  background: var(--surface);
  color: var(--muted);
  font: inherit;
  font-size: 0.8rem;
  cursor: pointer;
}
.mobile-page-tabs button.active {
  background: var(--surface-2);
  color: var(--text);
  font-weight: 600;
}
.mobile-page-tabs button:disabled { opacity: 0.45; cursor: not-allowed; }

.mobile-preview-stage {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  overflow: auto;
  min-height: 0;
}

.mobile-device {
  position: relative;
  flex-shrink: 0;
  background: #111;
  border-radius: 36px;
  padding: 12px;
  box-shadow:
    0 0 0 2px #2a2a2a,
    0 0 0 4px #1a1a1a,
    0 24px 80px rgba(0, 0, 0, 0.55);
}

.mobile-device::before {
  content: "";
  position: absolute;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  width: 96px;
  height: 24px;
  background: #111;
  border-radius: 0 0 14px 14px;
  z-index: 2;
  pointer-events: none;
}

.mobile-device-screen {
  overflow: hidden;
  border-radius: 26px;
  background: #fff;
  line-height: 0;
}

.mobile-device-screen iframe {
  display: block;
  border: none;
  background: #fff;
}

.preview-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--overlay-soft);
  opacity: 0;
  transition: opacity 0.18s;
  backdrop-filter: blur(2px);
  pointer-events: none; /* never steal clicks from card buttons / dropdowns */
}

.preview-frame-wrap:hover .preview-overlay { opacity: 1; }

.preview-overlay-links {
  pointer-events: auto; /* links/buttons inside overlay stay clickable on hover */
}

.preview-overlay a,
.preview-overlay .btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.55rem 1rem;
  background: #fff;
  color: #111;
  text-decoration: none;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  font-family: inherit;
}

.card-body { padding: 1rem 1.1rem 1.1rem; flex: 1; display: flex; flex-direction: column; gap: 0.65rem; }

.card-name {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  word-break: break-all;
}

.card-entry {
  font-size: 0.75rem;
  color: var(--muted);
}

.path-block {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.path-row {
  display: flex;
  align-items: stretch;
  gap: 0.35rem;
}

.path-label {
  font-size: 0.62rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--muted);
  margin-bottom: 0.15rem;
}

.path-value {
  flex: 1;
  font-family: "JetBrains Mono", ui-monospace, monospace;
  font-size: 0.68rem;
  line-height: 1.45;
  padding: 0.45rem 0.6rem;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--code-text);
  word-break: break-all;
  user-select: all;
}

.copy-btn {
  flex-shrink: 0;
  padding: 0 0.55rem;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: var(--surface-2);
  color: var(--muted);
  cursor: pointer;
  font-size: 0.72rem;
  transition: color 0.15s, border-color 0.15s;
}

.copy-btn:hover { color: var(--text); border-color: var(--border-strong); }
.copy-btn.copied { color: var(--omni); border-color: var(--omni); }

.card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: auto;
  padding-top: 0.35rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem 0.75rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  text-decoration: none;
  border: 1px solid transparent;
  cursor: pointer;
  font-family: inherit;
}

.btn-primary {
  background: var(--accent);
  color: var(--accent-fg);
}

.btn-primary:hover { filter: brightness(1.06); }

.btn-ghost {
  background: transparent;
  border-color: var(--border);
  color: var(--text);
}

.btn-ghost:hover { background: var(--surface-2); }

.toast {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  padding: 0.65rem 1rem;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  font-size: 0.85rem;
  opacity: 0;
  transform: translateY(8px);
  transition: opacity 0.2s, transform 0.2s;
  pointer-events: none;
  z-index: 100;
}

.toast.show {
  opacity: 1;
  transform: translateY(0);
}

/* Top-right generation queue widget — shows async flow builds as they run so
   the operator can queue several and keep working instead of waiting on the
   builder modal. */
.gen-queue {
  position: fixed;
  top: 1rem;
  right: 1rem;
  width: 300px;
  max-width: calc(100vw - 2rem);
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: 12px;
  box-shadow: var(--shadow-lg);
  font-size: 0.82rem;
  color: var(--text);
  z-index: 160;
  overflow: hidden;
  opacity: 0;
  transform: translateY(-8px);
  transition: opacity 0.2s, transform 0.2s;
  pointer-events: none;
}
.gen-queue.show {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}
.gen-queue-head {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 0.8rem;
  border-bottom: 1px solid var(--border);
  background: var(--surface-2);
  font-weight: 600;
}
.gen-queue-head .gen-queue-title { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gen-queue-head .gen-queue-count { color: var(--muted); font-variant-numeric: tabular-nums; }
.gen-queue-head button {
  background: none;
  border: none;
  color: var(--muted);
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
  padding: 0 0.2rem;
}
.gen-queue-head button:hover { color: var(--text); }
.gen-queue-list {
  list-style: none;
  margin: 0;
  padding: 0.25rem 0;
  max-height: 50vh;
  overflow-y: auto;
}
.gen-queue.collapsed .gen-queue-list { display: none; }
.gen-queue-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.8rem;
  cursor: default;
}
.gen-queue-item .gqi-icon { width: 1.1em; text-align: center; flex: none; }
.gen-queue-item .gqi-label { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gen-queue-item .gqi-step { color: var(--muted); font-size: 0.74rem; white-space: nowrap; }
.gen-queue-item.state-done { cursor: pointer; }
.gen-queue-item.state-done .gqi-icon { color: #34c759; }
.gen-queue-item.state-error { cursor: pointer; }
.gen-queue-item.state-error .gqi-icon { color: #ff453a; }
.gen-queue-item.state-error .gqi-step { color: #ff453a; }
.gqi-spin { display: inline-block; animation: gqi-spin 0.9s linear infinite; }
@keyframes gqi-spin { to { transform: rotate(360deg); } }

/* Brief highlight on a card that was just live-inserted. */
.flow-card.flash-in { animation: flow-flash 1.6s ease; }
@keyframes flow-flash {
  0% { box-shadow: 0 0 0 2px var(--accent); }
  100% { box-shadow: 0 0 0 0 transparent; }
}

@media (max-width: 900px) {
  .layout { grid-template-columns: 1fr; }
  .sidebar {
    position: static;
    height: auto;
    border-right: none;
    border-bottom: 1px solid var(--border);
  }
  .main { padding: 1.25rem; }
  .grid { grid-template-columns: 1fr; }
}

/* ---- Flow builder ---- */
.btn-create-flow {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.68rem 1.05rem;
  border: 1px solid var(--border-strong);
  border-radius: 12px;
  background: var(--accent);
  color: var(--accent-fg);
  font: inherit;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: var(--shadow-sm);
  transition: filter 0.15s, transform 0.12s;
}
.btn-create-flow:hover { filter: brightness(1.06); transform: translateY(-1px); }

[data-theme="dark"] .btn-create-flow {
  background: linear-gradient(180deg, #ffffff 0%, #e8e8e8 100%);
  color: #0a0a0a;
  border-color: rgba(255, 255, 255, 0.2);
}

[data-theme="light"] .btn-create-flow {
  background: linear-gradient(180deg, #0077ed 0%, #0071e3 100%);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 4px 14px rgba(0, 113, 227, 0.28);
}

.sidebar-create {
  margin: 0.9rem 1.25rem 0;
}
.sidebar-create button {
  width: 100%;
  justify-content: center;
}

.flow-badges { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.flow-badge {
  font-size: 0.68rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  color: var(--muted);
}
.flow-badge.sales { color: var(--aipu); }
.flow-badge.checkout { color: var(--omni); }
.flow-badge.kk { color: #f0b429; border-color: rgba(240,180,41,0.4); }
.flow-badge.brand-aipu { color: var(--aipu); border-color: var(--aipu-dim); }
.flow-badge.brand-omni { color: var(--omni); border-color: var(--omni-dim); }
.brand-dot.both { background: linear-gradient(135deg, var(--aipu), var(--omni)); }

/* ---- brand preview switch (on the flow card thumbnail) ---- */
.brand-switch {
  position: absolute;
  top: 0.55rem;
  right: 2.9rem; /* clear of the trash icon */
  z-index: 3;
  display: inline-flex;
  gap: 2px;
  padding: 2px;
  border-radius: 999px;
  background: var(--overlay, rgba(0,0,0,0.6));
  backdrop-filter: blur(6px);
  border: 1px solid var(--border-strong);
}

/* ---- single-archive trash icon (top right of every card) ---- */
.card-trash {
  position: absolute;
  top: 0.55rem;
  right: 0.55rem;
  z-index: 3;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 999px;
  border: 1px solid var(--border-strong);
  background: var(--overlay, rgba(0,0,0,0.6));
  backdrop-filter: blur(6px);
  color: #fca5a5;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.15s, background 0.15s, color 0.15s, transform 0.12s;
}
.card:hover .card-trash, .card.selected .card-trash { opacity: 1; }
.card-trash:hover { background: rgba(239, 68, 68, 0.22); color: #fff; transform: scale(1.06); }

/* ---- flow generated date/time ---- */
.flow-meta-row { display: flex; align-items: center; gap: 0.5rem; margin-top: -0.15rem; }
.flow-created {
  display: inline-flex;
  align-items: center;
  gap: 0.32rem;
  font-size: 0.72rem;
  color: var(--muted);
  font-variant-numeric: tabular-nums;
}
.flow-created svg { opacity: 0.75; }
.brand-switch-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  border: 0;
  background: transparent;
  color: #fff;
  font-size: 0.66rem;
  font-weight: 600;
  padding: 0.18rem 0.5rem;
  border-radius: 999px;
  cursor: pointer;
  opacity: 0.7;
}
.brand-switch-btn .brand-dot { width: 7px; height: 7px; }
.brand-switch-btn.active { background: rgba(255,255,255,0.18); opacity: 1; }
.brand-switch-btn:hover { opacity: 1; }

/* ---- dropdown menus (Preview / Configure) ----
   Menus are portaled to <body> and position:fixed so they are never clipped
   by card overflow and are not affected by card stacking contexts. */
.dropdown { position: relative; display: inline-flex; }
.dropdown.is-disabled { display: none; }
.dropdown-toggle { white-space: nowrap; cursor: pointer; }
.dropdown-menu {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 2000;
  min-width: 200px;
  max-height: min(70vh, 480px);
  overflow-y: auto;
  display: none;
  flex-direction: column;
  padding: 6px;
  border-radius: 11px;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  box-shadow: var(--shadow-md), 0 16px 48px rgba(0, 0, 0, 0.45);
}
.dropdown.open .dropdown-menu,
.dropdown-menu.is-open { display: flex; }
.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  width: 100%;
  text-align: left;
  border: 0;
  background: transparent;
  color: var(--text);
  font: inherit;
  font-size: 0.82rem;
  font-weight: 500;
  padding: 0.55rem 0.7rem;
  border-radius: 7px;
  cursor: pointer;
  text-decoration: none;
  white-space: nowrap;
  min-height: 36px;
}
.dropdown-item:hover { background: var(--surface-2); }
.dropdown-item.disabled {
  opacity: 0.4;
  pointer-events: none;
  cursor: not-allowed;
}
.dropdown-item .brand-dot { width: 8px; height: 8px; }
.dropdown-item.is-busy { opacity: 0.55; pointer-events: none; }
.dropdown-item-danger { color: #f87171; }
.dropdown-item-danger:hover { background: rgba(239, 68, 68, 0.12); }
.dropdown-heading {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.64rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--muted);
  padding: 0.5rem 0.62rem 0.2rem;
}
.dropdown-heading .brand-dot { width: 7px; height: 7px; }
.dropdown-sep { height: 1px; background: var(--border); margin: 4px 2px; }

.btn-kk {
  background: linear-gradient(135deg, #f0b429, #f7931e);
  color: #1a1205;
  border: 1px solid transparent;
}
.btn-kk:hover { filter: brightness(1.06); }
.btn-kk-sm {
  padding: 0.4rem 0.55rem;
  font-size: 0.74rem;
  font-weight: 700;
}
.kk-dl-group {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  align-items: center;
}
.kk-dl-label {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted);
  margin-right: 0.15rem;
}
/* KK download button result states */
.btn-kk.kk-error {
  background: linear-gradient(135deg, #ef4444, #b91c1c) !important;
  color: #fff !important;
  animation: kk-shake 0.4s ease;
}
.btn-kk.kk-ok {
  background: linear-gradient(135deg, #10b981, #059669) !important;
  color: #fff !important;
}
@keyframes kk-shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-3px); }
  75% { transform: translateX(3px); }
}
.kk-dl-error {
  flex-basis: 100%;
  margin-top: 0.35rem;
  font-size: 0.72rem;
  line-height: 1.4;
  color: #fca5a5;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.4);
  border-radius: 8px;
  padding: 0.45rem 0.6rem;
  display: none;
}
.kk-dl-error.show { display: block; }
.kk-dl-error a { color: #fff; font-weight: 600; }
.kk-dl-error .kk-dl-error-fix { display: block; margin-top: 0.3rem; color: var(--muted); }
.kk-dl-error-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.45rem; margin-top: 0.5rem; }
.kk-dl-error-actions .kk-dl-error-fix { margin-top: 0; display: inline; }
.kk-fix-ai { padding: 0.3rem 0.7rem; font-size: 0.74rem; font-weight: 600; border-radius: 8px; cursor: pointer; line-height: 1.1; }
.kk-fix-ai.is-busy { opacity: 0.7; pointer-events: none; }
.btn.is-busy { opacity: 0.6; pointer-events: none; }

.btn-flow {
  background: var(--accent);
  color: var(--accent-fg);
  border: 1px solid var(--border-strong);
}
.btn-flow:hover { filter: brightness(1.06); }

[data-theme="dark"] .btn-flow {
  background: linear-gradient(180deg, #ffffff 0%, #e8e8e8 100%);
  color: #0a0a0a;
}
[data-theme="light"] .btn-flow {
  background: linear-gradient(180deg, #0077ed 0%, #0071e3 100%);
  color: #fff;
  border-color: transparent;
}

/* ---- guided flow wizard ---- */
.modal-wizard { max-width: 900px; }
.builder-step { padding: 1.15rem 1.5rem; }
.picker-toolbar {
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; margin-bottom: 0.85rem;
}
.picker-toolbar-label { font-size: 0.85rem; font-weight: 600; color: var(--text); }
.picker-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(165px, 1fr));
  gap: 0.75rem;
  max-height: 56vh;
  overflow-y: auto;
  padding: 0.2rem;
}
.picker-item {
  position: relative;
  display: flex; flex-direction: column; text-align: left;
  padding: 0; border: 1px solid var(--border); border-radius: 10px;
  overflow: hidden; background: var(--surface-2); cursor: pointer;
  font: inherit; color: var(--text);
  transition: border-color 0.12s, box-shadow 0.12s, transform 0.08s;
}
.picker-item:hover { border-color: var(--border-strong); transform: translateY(-2px); }
.picker-item.selected { border-color: var(--accent); box-shadow: var(--focus-ring); }
.picker-item.selected::after {
  content: "✓";
  position: absolute;
  top: 6px; right: 6px;
  width: 20px; height: 20px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 999px;
  background: var(--accent);
  color: var(--accent-fg);
  font-size: 12px; font-weight: 800;
  box-shadow: 0 2px 8px rgba(0,0,0,0.35);
  z-index: 2;
}

/* matrix (multi-select) confirmation summary */
.matrix-summary {
  flex: 1;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--surface-2);
  padding: 0.85rem 1rem;
}
.matrix-count { font-size: 1rem; font-weight: 700; display: flex; flex-direction: column; gap: 0.15rem; }
.matrix-count .matrix-sub { font-size: 0.75rem; font-weight: 500; color: var(--muted); }
.matrix-list {
  margin: 0.6rem 0 0; padding: 0 0 0 1.1rem;
  font-size: 0.78rem; color: var(--muted); line-height: 1.65;
  max-height: 180px; overflow-y: auto;
}
.matrix-list strong { color: var(--text); font-weight: 600; }
/* editable per-combination flow names (matrix mode) */
.matrix-hint { font-size: 0.74rem; color: var(--muted); margin: 0.55rem 0 0.1rem; }
.matrix-names {
  list-style: none; margin: 0.45rem 0 0; padding: 0;
  display: flex; flex-direction: column; gap: 0.5rem;
  max-height: 260px; overflow-y: auto;
}
.matrix-row { display: flex; flex-direction: column; gap: 0.2rem; }
.matrix-row-pair {
  font-size: 0.72rem; color: var(--muted); line-height: 1.3; word-break: break-all;
}
.matrix-row-pair strong { color: var(--text); font-weight: 600; }
.matrix-name-input {
  width: 100%; box-sizing: border-box;
  padding: 0.42rem 0.55rem;
  font-size: 0.8rem; font-family: inherit;
  color: var(--text); background: var(--surface);
  border: 1px solid var(--border); border-radius: 7px;
}
.matrix-name-input:focus { outline: none; border-color: var(--accent, #6366f1); }
.picker-thumb {
  position: relative; height: 104px; background: #fff; overflow: hidden;
  border-bottom: 1px solid var(--border);
}
.picker-thumb iframe {
  width: 1280px; height: 820px; border: 0;
  transform: scale(0.135); transform-origin: top left; pointer-events: none;
}
.picker-label { display: flex; align-items: center; justify-content: space-between; gap: 0.4rem; padding: 0.5rem 0.55rem; }
.picker-name { font-size: 0.76rem; font-weight: 600; word-break: break-all; line-height: 1.25; }
.picker-empty {
  grid-column: 1 / -1; padding: 2rem; text-align: center; color: var(--muted);
  border: 1px dashed var(--border); border-radius: 10px;
}
.confirm-row { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.1rem; }
.confirm-card { flex: 1; min-width: 0; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; background: var(--surface-2); }
.confirm-cap { font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); padding: 0.45rem 0.55rem 0.1rem; }
.confirm-arrow { color: var(--muted); font-size: 1.4rem; flex: 0 0 auto; }
#builder-back { margin-right: auto; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: var(--overlay);
  backdrop-filter: blur(8px);
  display: none;
  align-items: flex-start;
  justify-content: center;
  padding: 4vh 1rem;
  z-index: 200;
  overflow-y: auto;
}
.modal-overlay.open { display: flex; }

.modal {
  width: 100%;
  max-width: 620px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: calc(var(--radius) + 2px);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}
.modal-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border);
}
.modal-head h2 { margin: 0; font-size: 1.15rem; font-weight: 700; }
.modal-head p { margin: 0.25rem 0 0; font-size: 0.82rem; color: var(--muted); }
.modal-close {
  background: none;
  border: none;
  color: var(--muted);
  font-size: 1.4rem;
  line-height: 1;
  cursor: pointer;
  padding: 0.2rem;
}
.modal-close:hover { color: var(--text); }
.modal-body { padding: 1.4rem 1.5rem; display: flex; flex-direction: column; gap: 1.1rem; }

.field label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  margin-bottom: 0.4rem;
  color: var(--text);
}
.field .hint { font-weight: 400; color: var(--muted); }
.field select, .field input[type="text"] {
  width: 100%;
  padding: 0.6rem 0.7rem;
  border: 1px solid var(--border);
  border-radius: 9px;
  background: var(--bg);
  color: var(--text);
  font: inherit;
  font-size: 0.88rem;
  outline: none;
}
.field select:focus, .field input[type="text"]:focus { border-color: var(--accent); }

.field-inline { display: flex; align-items: center; gap: 0.5rem; }
.field-inline input { width: auto; }
.field-inline label { margin: 0; font-weight: 500; font-size: 0.82rem; color: var(--muted); }

.modal-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.1rem 1.5rem;
  border-top: 1px solid var(--border);
  background: var(--surface-2);
}
.modal-status { font-size: 0.82rem; color: var(--muted); }
.modal-status.error { color: #f87171; }
.modal-status.ok { color: var(--omni); }

.flow-result {
  margin: 0 1.5rem 1.4rem;
  padding: 1rem;
  border: 1px solid var(--omni);
  border-radius: 10px;
  background: rgba(16, 185, 129, 0.07);
  display: none;
}
.flow-result.show { display: block; }
.flow-result h3 { margin: 0 0 0.5rem; font-size: 0.95rem; }
.flow-result .links { display: flex; flex-wrap: wrap; gap: 0.5rem; }

.brand-tag {
  font-size: 0.62rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  margin-left: 0.4rem;
}
.brand-tag.aipu { background: var(--aipu-dim); color: var(--accent); }
.brand-tag.omni { background: var(--omni-dim); color: var(--omni); }
.brand-tag.neutral { background: var(--surface-2); color: var(--muted); border: 1px solid var(--border); }

/* ---- brand chooser (flow wizard step 3) ---- */
.brand-choose { display: flex; gap: 0.6rem; }
.brand-opt {
  flex: 1;
  padding: 0.7rem 0.9rem;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--bg);
  color: var(--text);
  font: inherit;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: border-color 0.12s, box-shadow 0.12s, background 0.12s;
}
.brand-opt:hover { border-color: #3a4155; }
.brand-opt .brand-dot { width: 9px; height: 9px; }
.brand-opt.selected { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); background: var(--surface-2); }

/* ---- selection, delete, duplicate, import ---- */
.card { position: relative; }
.card.selected { border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent); }
.card-select {
  position: absolute;
  top: 0.6rem;
  left: 0.6rem;
  z-index: 5;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: rgba(11, 13, 18, 0.82);
  border: 1px solid var(--border);
  cursor: pointer;
  /* Always visible so bulk-select is discoverable (incl. touch — no hover). */
  opacity: 0.55;
  transition: opacity 0.15s, background 0.15s, border-color 0.15s;
}
.card:hover .card-select { opacity: 1; }
.card.selected .card-select { opacity: 1; background: var(--accent); border-color: var(--accent); }
.card-select input { width: 16px; height: 16px; cursor: pointer; accent-color: var(--accent); margin: 0; }

.btn-danger { background: transparent; border-color: rgba(248, 113, 113, 0.4); color: #f87171; }
.btn-danger:hover { background: rgba(248, 113, 113, 0.12); border-color: #f87171; }
.btn-dup { background: transparent; border-color: var(--border); color: var(--text); }
.btn-dup:hover { background: var(--surface-2); border-color: var(--accent); color: var(--accent); }

.bulk-bar {
  position: fixed;
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%) translateY(140%);
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.7rem 0.8rem 0.7rem 1.1rem;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);
  z-index: 150;
  transition: transform 0.22s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.bulk-bar.show { transform: translateX(-50%) translateY(0); }
.bulk-bar-count { font-size: 0.88rem; font-weight: 600; }
.bulk-bar-count strong { color: var(--accent); }

.btn-import {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.58rem 0.95rem;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--surface);
  color: var(--text);
  font: inherit;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: var(--shadow-sm);
  transition: border-color 0.15s, background 0.15s;
}
.btn-import:hover { border-color: var(--border-strong); background: var(--accent-soft); }

.import-tabs {
  display: flex;
  gap: 0.25rem;
  padding: 0 1.5rem;
  margin-top: -0.25rem;
  border-bottom: 1px solid var(--border);
}
.import-tab {
  background: transparent;
  border: none;
  color: var(--muted);
  font: inherit;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.65rem 0.9rem;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: color 0.15s, border-color 0.15s;
}
.import-tab:hover { color: var(--text); }
.import-tab.active {
  color: var(--accent);
  border-bottom-color: var(--accent);
}

.imp-pane[hidden] { display: none; }

.file-drop {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 110px;
  padding: 0.9rem 1rem;
  border: 1.5px dashed var(--border);
  border-radius: 10px;
  background: var(--bg);
  color: var(--muted);
  cursor: pointer;
  text-align: center;
  font-size: 0.85rem;
  transition: border-color 0.15s, background 0.15s, color 0.15s;
}
.file-drop:hover { border-color: var(--accent); color: var(--text); }
.file-drop.drag { border-color: var(--accent); background: rgba(124, 92, 255, 0.06); color: var(--text); }
.file-drop-prompt {
  display: inline-flex;
  align-items: center;
  gap: 0.7rem;
  line-height: 1.4;
}
.file-drop-prompt strong { color: var(--text); }
.file-drop-prompt code {
  background: var(--surface-2);
  padding: 0.05rem 0.35rem;
  border-radius: 4px;
  font-size: 0.78rem;
}
.file-drop-chosen {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text);
  font-weight: 600;
}
.file-drop-chosen .file-meta { color: var(--muted); font-weight: 400; font-size: 0.78rem; }
.file-drop-chosen .file-clear {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--muted);
  width: 22px; height: 22px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
  line-height: 1;
  padding: 0;
}
.file-drop-chosen .file-clear:hover { color: #f87171; border-color: #f87171; }

.confirm-list {
  margin: 0;
  padding: 0.5rem 0.75rem;
  list-style: none;
  max-height: 200px;
  overflow-y: auto;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 8px;
  font-family: "JetBrains Mono", monospace;
  font-size: 0.78rem;
  color: #c4cad6;
}
.confirm-list li { padding: 0.15rem 0; word-break: break-all; }
.modal-foot .btn-danger { padding: 0.55rem 1rem; }
.import-result { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.4rem; }

/* ---- Configure CTAs modal (flows only) ---- */
.btn-cta { background: transparent; border-color: rgba(99, 102, 241, 0.45); color: var(--accent); }
.btn-cta:hover { background: rgba(99, 102, 241, 0.12); border-color: var(--accent); color: #fff; }
.btn-flow-plans { background: transparent; border-color: rgba(16, 185, 129, 0.45); color: var(--omni); }
.btn-flow-plans:hover { background: rgba(16, 185, 129, 0.12); border-color: var(--omni); color: #fff; }
.btn-flow-widgets { background: transparent; border-color: rgba(139, 92, 255, 0.45); color: #a78bfa; }
.btn-flow-widgets:hover { background: rgba(139, 92, 255, 0.12); border-color: #a78bfa; color: #fff; }
.btn-flow-timer { background: transparent; border-color: rgba(251, 191, 36, 0.5); color: #fbbf24; }
.btn-flow-timer:hover { background: rgba(251, 191, 36, 0.12); border-color: #fbbf24; color: #fff; }
.btn-flow-assets { background: transparent; border-color: rgba(56, 189, 248, 0.5); color: #38bdf8; }
.btn-flow-assets:hover { background: rgba(56, 189, 248, 0.12); border-color: #38bdf8; color: #fff; }
a.btn-plans { text-decoration: none; }

/* ---- Optimize & Publish modal ---- */
.fa-toggle-row { display: flex; align-items: flex-start; gap: 0.6rem; margin-bottom: 0.9rem; }
.fa-toggle-row label { font-size: 0.85rem; font-weight: 600; color: var(--text); }
.fa-toggle-row .fa-sub { display: block; font-size: 0.72rem; font-weight: 400; color: var(--muted); margin-top: 0.15rem; }
.fa-cdn-block { border: 1px solid var(--border); border-radius: 10px; padding: 0.9rem 1rem; background: var(--bg); margin-top: 0.6rem; }
.fa-cdn-block.fa-disabled { opacity: 0.5; pointer-events: none; }
.fa-cdn-note { font-size: 0.74rem; color: var(--muted); margin: 0.3rem 0 0; }
.fa-cdn-warn { font-size: 0.74rem; color: #fbbf24; margin: 0.3rem 0 0; }
.fa-results { margin-top: 0.9rem; font-size: 0.8rem; }
.fa-results .fa-stat { display: inline-flex; gap: 0.35rem; align-items: baseline; margin-right: 1rem; }
.fa-results .fa-stat b { font-variant-numeric: tabular-nums; color: var(--text); }
.fa-results .fa-stat span { color: var(--muted); font-size: 0.72rem; }

/* ---- Set Timer modal ---- */
.ft-toggle-row { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; }
.ft-toggle-row label { font-size: 0.85rem; font-weight: 600; color: var(--text); }
.ft-duration { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.7rem; margin-bottom: 1rem; }
.ft-duration .ft-field { display: flex; flex-direction: column; gap: 0.3rem; }
.ft-duration label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); }
.ft-duration input {
  width: 100%; padding: 0.5rem 0.55rem; text-align: center;
  border: 1px solid var(--border); border-radius: 8px;
  background: var(--bg); color: var(--text); font: inherit; font-size: 1rem; font-variant-numeric: tabular-nums;
}
.ft-duration input:focus { border-color: var(--accent); outline: none; }
.ft-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.ft-row label { display: block; font-size: 0.72rem; font-weight: 600; color: var(--muted); margin-bottom: 0.25rem; }
.ft-row select {
  width: 100%; padding: 0.45rem 0.55rem;
  border: 1px solid var(--border); border-radius: 6px;
  background: var(--surface); color: var(--text); font: inherit; font-size: 0.8rem;
}
.ft-apply { display: flex; flex-wrap: wrap; gap: 1.1rem; }
.ft-apply .ft-check { display: flex; align-items: center; gap: 0.45rem; font-size: 0.82rem; color: var(--text); }
.ft-section-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--muted); margin: 0 0 0.5rem; }
.ft-disabled { opacity: 0.45; pointer-events: none; }

/* ---- Attach Widgets modal ---- */
.fw-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.fw-grid .fw-slot:last-child { grid-column: 1 / -1; }
.fw-slot-note { margin: 0; font-size: 0.74rem; color: var(--muted); line-height: 1.45; }
.fw-slot {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 0.9rem;
  background: var(--bg);
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}
.fw-slot h3 { margin: 0; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
.fw-slot h3 .fw-kind {
  font-size: 0.62rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
  padding: 0.15rem 0.5rem; border-radius: 999px;
  background: rgba(139, 92, 255, 0.14); color: #a78bfa; border: 1px solid rgba(139, 92, 255, 0.4);
}
.fw-slot label { display: block; font-size: 0.72rem; font-weight: 600; color: var(--muted); margin-bottom: 0.25rem; }
.fw-slot select {
  width: 100%; padding: 0.45rem 0.55rem;
  border: 1px solid var(--border); border-radius: 6px;
  background: var(--surface); color: var(--text); font: inherit; font-size: 0.8rem;
}
.fw-slot select:focus { border-color: var(--accent); outline: none; }
.fw-warn { font-size: 0.74rem; color: #fbbf24; min-height: 1em; }
.fw-meta { font-size: 0.72rem; color: var(--muted); font-family: "JetBrains Mono", monospace; }
@media (max-width: 700px) { .fw-grid { grid-template-columns: 1fr; } }

.flow-plans-table input[type="text"],
.flow-plans-table input[type="checkbox"] {
  font: inherit;
}
.flow-plans-table input[type="text"] {
  width: 100%;
  padding: 0.4rem 0.5rem;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: var(--bg);
  color: var(--text);
  font-size: 0.8rem;
}
.flow-plans-table input[type="text"]:focus { border-color: var(--accent); outline: none; }
.flow-plans-table td.plan-name-cell { font-weight: 600; }
.flow-plans-table td.plan-id-cell {
  font-family: "JetBrains Mono", monospace;
  font-size: 0.72rem;
  color: var(--muted);
}
.flow-plans-table tr.disabled-row { opacity: 0.45; }

.cta-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 0.85rem;
}
.cta-table th {
  text-align: left;
  font-weight: 600;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--muted);
  padding: 0.5rem 0.6rem;
  border-bottom: 1px solid var(--border);
  background: var(--surface-2);
  position: sticky;
  top: 0;
  z-index: 2;
}
.cta-table td {
  padding: 0.55rem 0.6rem;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.cta-table tr:last-child td { border-bottom: none; }
.cta-table td.cta-page-cell {
  font-family: "JetBrains Mono", monospace;
  font-size: 0.78rem;
  color: #c4cad6;
  word-break: break-all;
  min-width: 14rem;
}
.cta-table .cta-applied {
  font-size: 0.7rem;
  color: var(--muted);
  margin-top: 0.15rem;
}
.cta-table select {
  width: 100%;
  padding: 0.45rem 0.55rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--bg);
  color: var(--text);
  font: inherit;
  font-size: 0.85rem;
  outline: none;
}
.cta-table select:focus { border-color: var(--accent); }
.cta-scroll {
  max-height: 60vh;
  overflow-y: auto;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--surface);
}
.cta-toolbar {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}
.cta-toolbar .field { flex: 1; min-width: 240px; margin: 0; }
.cta-toolbar .field label { margin-bottom: 0.3rem; }
.cta-hint {
  font-size: 0.78rem;
  color: var(--muted);
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 0.55rem 0.7rem;
  margin-bottom: 0.85rem;
  line-height: 1.5;
}
.cta-hint strong { color: var(--text); }
.cta-empty {
  padding: 1.4rem;
  text-align: center;
  color: var(--muted);
  font-size: 0.85rem;
}
.cta-modal-body { padding-top: 0; }
.cta-modal-body .cta-flow-meta {
  font-size: 0.78rem;
  color: var(--muted);
  font-family: "JetBrains Mono", monospace;
  margin: 0 0 0.85rem;
  word-break: break-all;
}
.btn-bulk-apply {
  padding: 0.5rem 0.85rem;
  font-size: 0.82rem;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  cursor: pointer;
  font: inherit;
  white-space: nowrap;
}
.btn-bulk-apply:hover { border-color: var(--accent); color: var(--accent); }

/* ---- Plan Library ---- */
.btn-plans {
  display: inline-flex; align-items: center; gap: 0.45rem;
  padding: 0.58rem 0.95rem;
  border: 1px solid var(--border); border-radius: 12px;
  background: var(--surface); color: var(--text);
  font: inherit; font-size: 0.9rem; font-weight: 600; cursor: pointer; white-space: nowrap;
  box-shadow: var(--shadow-sm);
  transition: border-color 0.15s, background 0.15s;
}
.btn-plans:hover { border-color: var(--border-strong); background: var(--accent-soft); }
.plans-scroll { max-height: 64vh; overflow-y: auto; display: flex; flex-direction: column; gap: 0.9rem; padding: 0.1rem; }
.plans-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.9rem; }
.plan-card { border: 1px solid var(--border); border-radius: 12px; background: var(--surface-2); padding: 0.9rem 1rem 1rem; }
.plan-card.is-new { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
.plan-card-head { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.65rem; }
.plan-card-title { margin: 0; font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
.plan-card-title .plan-id-tag { font-family: "JetBrains Mono", monospace; font-size: 0.7rem; color: var(--muted); background: var(--bg); border: 1px solid var(--border); padding: 0.1rem 0.4rem; border-radius: 5px; }
.plan-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.6rem 0.7rem; }
.plan-grid .field { margin: 0; }
.plan-grid .field label { margin-bottom: 0.25rem; font-size: 0.7rem; }
.plan-grid .field.col-2 { grid-column: span 2; }
.plan-grid .field input, .plan-grid .field select, .plan-grid .field textarea {
  width: 100%; padding: 0.45rem 0.55rem; border: 1px solid var(--border); border-radius: 8px;
  background: var(--bg); color: var(--text); font: inherit; font-size: 0.83rem; outline: none;
}
.plan-grid .field input:focus, .plan-grid .field select:focus, .plan-grid .field textarea:focus { border-color: var(--accent); }
.plan-grid .field input:disabled { opacity: 0.6; cursor: not-allowed; }
.plan-grid .field textarea { resize: vertical; min-height: 2.4rem; font-family: inherit; }
.plan-brands { display: flex; align-items: center; gap: 0.85rem; padding-top: 1.25rem; }
.plan-brands label { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; color: var(--muted); font-weight: 500; }
.plan-brands input { width: auto; }
.plan-card-actions { display: flex; gap: 0.5rem; margin-top: 0.8rem; }
.plan-card-status { font-size: 0.78rem; color: var(--muted); align-self: center; margin-left: auto; }
.plan-card-status.ok { color: var(--omni); }
.plan-card-status.error { color: #f87171; }

/* ---- Live Flow Tester ---- */
.ft-run { margin-top: 1.1rem; border-top: 1px solid var(--border); padding-top: 1.1rem; }
.ft-verdict { display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1rem; border-radius: 12px;
  border: 1px solid var(--border); background: var(--surface-2, rgba(127,127,127,0.06)); }
.ft-verdict.green { border-color: rgba(52,199,89,0.5); background: rgba(52,199,89,0.10); }
.ft-verdict.red { border-color: rgba(248,113,113,0.5); background: rgba(248,113,113,0.10); }
.ft-light { width: 16px; height: 16px; border-radius: 50%; flex: 0 0 auto; background: #9aa0a6;
  box-shadow: 0 0 0 4px rgba(154,160,166,0.15); }
.ft-light.running { background: #f0b429; box-shadow: 0 0 0 4px rgba(240,180,41,0.18); animation: ft-pulse 1.1s infinite; }
.ft-light.green { background: #34c759; box-shadow: 0 0 0 4px rgba(52,199,89,0.2); }
.ft-light.red { background: #f87171; box-shadow: 0 0 0 4px rgba(248,113,113,0.2); }
@keyframes ft-pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.35; } }
.ft-verdict-text { display: flex; flex-direction: column; gap: 0.1rem; }
.ft-verdict-text strong { font-size: 1rem; }
.ft-verdict-text span { font-size: 0.82rem; color: var(--muted); }
.ft-progress { height: 6px; border-radius: 4px; background: rgba(127,127,127,0.18); margin: 0.8rem 0 0.4rem; overflow: hidden; }
.ft-progress-bar { height: 100%; width: 0; background: var(--accent, #4f8cff); transition: width 0.4s ease; }
.ft-stats { display: flex; flex-wrap: wrap; gap: 0.4rem 0.9rem; font-size: 0.8rem; color: var(--muted); margin-bottom: 0.6rem; }
.ft-stats b { color: var(--fg, #e8e8e8); font-weight: 600; }
.ft-findings { display: flex; flex-direction: column; gap: 0.5rem; max-height: 360px; overflow-y: auto; }
.ft-page-group { border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
.ft-page-head { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.82rem;
  background: rgba(127,127,127,0.07); font-weight: 600; }
.ft-page-head .ft-page-url { color: var(--muted); font-weight: 500; word-break: break-all; }
.ft-pill { font-size: 0.68rem; font-weight: 700; padding: 0.08rem 0.45rem; border-radius: 999px; flex: 0 0 auto; }
.ft-pill.err { background: rgba(248,113,113,0.18); color: #f87171; }
.ft-pill.warn { background: rgba(240,180,41,0.18); color: #f0b429; }
.ft-pill.ok { background: rgba(52,199,89,0.16); color: #34c759; }
.ft-finding { display: flex; gap: 0.55rem; padding: 0.5rem 0.75rem; border-top: 1px solid var(--border); font-size: 0.82rem; align-items: flex-start; }
.ft-finding .ft-sev { font-size: 0.66rem; font-weight: 700; text-transform: uppercase; padding: 0.1rem 0.4rem;
  border-radius: 5px; flex: 0 0 auto; margin-top: 0.05rem; }
.ft-finding .ft-sev.error { background: rgba(248,113,113,0.18); color: #f87171; }
.ft-finding .ft-sev.warn { background: rgba(240,180,41,0.18); color: #f0b429; }
.ft-finding .ft-body { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
.ft-finding .ft-msg { color: var(--fg, #e8e8e8); }
.ft-finding .ft-meta { color: var(--muted); font-size: 0.75rem; word-break: break-all; }
.ft-finding .ft-meta code { font-size: 0.72rem; }
.ft-finding a.ft-shot { font-size: 0.72rem; color: var(--accent, #4f8cff); text-decoration: none; }
.ft-empty-ok { padding: 0.9rem; text-align: center; color: #34c759; font-size: 0.85rem; font-weight: 600; }
</style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-header">
      <h1>Lander Catalog</h1>
      <p>Sales pages & checkouts — brand at Create Flow</p>
      <div class="stat-pill"><strong><?= $totalCount ?></strong> pages indexed</div>
    </div>
    <div class="sidebar-create">
      <button type="button" class="btn-create-flow" data-open-builder>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Create Flow
      </button>
    </div>
    <nav class="nav-section">
      <div class="nav-section-label">Funnels</div>
      <a class="nav-link" href="#sales-flows">
        <span>Sales Flows</span>
        <span class="nav-count"><?= $flowCount ?></span>
      </a>
      <div class="nav-section-label">Collections</div>
      <?php foreach ($visibleCollections as $collection): ?>
      <a class="nav-link" href="#<?= htmlspecialchars($collection['id'], ENT_QUOTES) ?>">
        <span><?= htmlspecialchars($collection['label'], ENT_QUOTES) ?></span>
        <span class="nav-count"><?= $collection['count'] ?></span>
      </a>
      <?php endforeach; ?>
      <div class="nav-section-label">System</div>
      <a class="nav-link" href="#archive">
        <span>Archive</span>
        <span class="nav-count"><?= $archivedCount ?></span>
      </a>
    </nav>
  </aside>

  <main class="main">
    <div class="topbar">
      <div class="search-wrap">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" id="search" placeholder="Search by name or path…" autocomplete="off">
      </div>
      <div class="view-toggle" role="group" aria-label="View mode">
        <button type="button" class="active" data-view="grid">Grid</button>
        <button type="button" data-view="list">List</button>
      </div>
      <div class="view-toggle preview-mode-toggle" role="group" aria-label="Card preview device" title="Switch thumbnail previews between desktop and mobile">
        <button type="button" class="active" data-preview-mode="desktop">Desktop</button>
        <button type="button" data-preview-mode="mobile">Mobile</button>
      </div>
      <div class="theme-toggle" role="group" aria-label="Color theme">
        <button type="button" data-theme="light" title="Light (Apple)" aria-label="Light theme">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
        </button>
        <button type="button" class="active" data-theme="dark" title="Dark (Cursor)" aria-label="Dark theme">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
      </div>
      <button type="button" class="btn-import" data-open-import>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
        Import
      </button>
      <button type="button" class="btn-plans" data-open-plans title="Manage the plans your checkouts can use">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 16l4-6 4 3 5-8"/></svg>
        Plan Library
      </button>
      <a class="btn-plans" href="widgets.php" title="Build &amp; manage exit popups and mobile offer sliders">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
        Widgets
      </a>
      <button type="button" class="btn-plans" data-open-tester title="Live-test a funnel after uploading it to KK — clicks every link &amp; popup and reports green/red">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Test Flow
      </button>
      <button type="button" class="btn-create-flow" data-open-builder>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Create Flow
      </button>
    </div>

    <section class="collection" id="sales-flows">
      <div class="collection-header">
        <div>
          <h2 class="collection-title">
            <span class="brand-dot both"></span>
            Sales Flows
          </h2>
          <p class="collection-desc">Every flow is built for <strong>both</strong> brands. Switch the preview, configure once for both, and download AIPU, OmniRogue or both at once.</p>
        </div>
        <code class="collection-meta">htdocs/flows/</code>
      </div>

      <?php if ($flowCount === 0): ?>
      <div class="empty-state">
        <p>No sales flows yet. Click <strong>Create Flow</strong> — each one is generated for AIPU and OmniRogue automatically.</p>
      </div>
      <?php else: ?>
      <div class="grid" data-collection="sales-flows">
        <?php
        // Card markup lives in lib/flow_card.php so the live-insert partial
        // (flow-card.php) renders identical cards from one source of truth.
        require_once __DIR__ . '/lib/flow_card.php';
        foreach ($flowGroups as $g) {
            echo render_flow_card($g);
        }
        ?>
      </div>
      <?php endif; ?>
    </section>

    <?php foreach ($visibleCollections as $collection): ?>
    <section class="collection" id="<?= htmlspecialchars($collection['id'], ENT_QUOTES) ?>">
      <div class="collection-header">
        <div>
          <h2 class="collection-title">
            <span class="brand-dot <?= htmlspecialchars($collection['brand'], ENT_QUOTES) ?>"></span>
            <?= htmlspecialchars($collection['label'], ENT_QUOTES) ?>
          </h2>
          <p class="collection-desc"><?= htmlspecialchars($collection['description'], ENT_QUOTES) ?></p>
        </div>
        <code class="collection-meta">htdocs/<?= htmlspecialchars($collection['dir'], ENT_QUOTES) ?>/</code>
      </div>

      <?php if ($collection['count'] === 0): ?>
      <div class="empty-state">
        <p>No pages found yet.</p>
        <code>htdocs/<?= htmlspecialchars($collection['dir'], ENT_QUOTES) ?>/</code>
      </div>
      <?php else: ?>
      <div class="grid" data-collection="<?= htmlspecialchars($collection['id'], ENT_QUOTES) ?>">
        <?php foreach ($collection['items'] as $item):
          $fullUrl = $baseUrl . $item['webPath'];
          // Cache-bust the live preview by the entry file's mtime so an edited page
          // always renders fresh — browsers otherwise keep showing the stale (e.g.
          // previously-broken) iframe copy even though HTML is sent no-cache.
          $previewSrc = $item['webPath'] . '?v=' . (int)$item['modified'];
          $searchText = strtolower($item['name'] . ' ' . $item['fsPath'] . ' ' . $item['webPath']);
          $isNeutralCol = $collection['brand'] === 'neutral';
          // Single-page collections build a whole-site flow with a pop-up checkout.
          $isSinglePageCol = ($collection['kind'] ?? '') === 'single-page';
          if ($isSinglePageCol) {
              $itemKind = 'single-page';
          } else {
              $itemKind = (substr($collection['dir'], -8) === '-landers' || $collection['dir'] === 'sales-pages') ? 'lander' : 'checkout';
          }
          $itemDst = $collection['brand'] === 'aipu' ? 'omni' : 'aipu';
          $itemDstLabel = $itemDst === 'aipu' ? 'AIPU' : 'OmniRogue';
        ?>
        <article class="card" data-kind="<?= $itemKind ?>"<?= $isSinglePageCol ? ' data-single-page="1"' : '' ?> data-name="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>" data-collection="<?= htmlspecialchars($collection['dir'], ENT_QUOTES) ?>" data-brand="<?= htmlspecialchars($collection['brand'], ENT_QUOTES) ?>" data-dst="<?= $itemDst ?>" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES) ?>">
          <div class="preview-frame-wrap">
            <label class="card-select" title="Select for bulk actions"><input type="checkbox" class="select-box" aria-label="Select <?= htmlspecialchars($item['name'], ENT_QUOTES) ?>"></label>
            <button type="button" class="card-trash item-delete-btn" title="Archive — restorable from the Archive" aria-label="Archive <?= htmlspecialchars($item['name'], ENT_QUOTES) ?>">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
            </button>
            <iframe src="<?= htmlspecialchars($previewSrc, ENT_QUOTES) ?>" loading="lazy" title="Preview: <?= htmlspecialchars($item['name'], ENT_QUOTES) ?>" tabindex="-1"></iframe>
            <div class="preview-overlay">
              <div class="preview-overlay-links">
                <a href="<?= htmlspecialchars($previewSrc, ENT_QUOTES) ?>" target="_blank" rel="noopener">Open full page ↗</a>
                <button type="button" class="btn btn-mobile mobile-preview-btn" data-label="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>" data-url="<?= htmlspecialchars($previewSrc, ENT_QUOTES) ?>">Mobile preview</button>
              </div>
            </div>
          </div>
          <div class="card-body">
            <h3 class="card-name"><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></h3>
            <div class="card-entry">Entry: <code><?= htmlspecialchars($item['entryFile'], ENT_QUOTES) ?></code></div>

            <div class="path-block">
              <div>
                <div class="path-label">Filesystem</div>
                <div class="path-row">
                  <code class="path-value" data-copy="<?= htmlspecialchars($item['fsPath'], ENT_QUOTES) ?>"><?= htmlspecialchars($item['fsPath'], ENT_QUOTES) ?></code>
                  <button type="button" class="copy-btn" data-copy="<?= htmlspecialchars($item['fsPath'], ENT_QUOTES) ?>" title="Copy path">⎘</button>
                </div>
              </div>
              <div>
                <div class="path-label">Web path</div>
                <div class="path-row">
                  <code class="path-value" data-copy="<?= htmlspecialchars($item['webPath'], ENT_QUOTES) ?>"><?= htmlspecialchars($item['webPath'], ENT_QUOTES) ?></code>
                  <button type="button" class="copy-btn" data-copy="<?= htmlspecialchars($item['webPath'], ENT_QUOTES) ?>" title="Copy path">⎘</button>
                </div>
              </div>
              <div>
                <div class="path-label">Full URL</div>
                <div class="path-row">
                  <code class="path-value" data-copy="<?= htmlspecialchars($fullUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($fullUrl, ENT_QUOTES) ?></code>
                  <button type="button" class="copy-btn" data-copy="<?= htmlspecialchars($fullUrl, ENT_QUOTES) ?>" title="Copy URL">⎘</button>
                </div>
              </div>
            </div>

            <div class="card-actions">
              <?php if ($isSinglePageCol): ?>
              <button type="button" class="btn btn-flow create-flow-from-single" data-single="<?= htmlspecialchars($collection['dir'] . '|' . $item['name'], ENT_QUOTES) ?>" title="Build a single-page flow (whole site + pop-up checkout) for both brands">+ Create Single Page Flow</button>
              <?php elseif ($itemKind === 'lander'): ?>
              <button type="button" class="btn btn-flow create-flow-from-lander" data-lander="<?= htmlspecialchars($collection['dir'] . '|' . $item['name'], ENT_QUOTES) ?>">+ Create Flow</button>
              <?php endif; ?>
              <a class="btn btn-primary" href="<?= htmlspecialchars($previewSrc, ENT_QUOTES) ?>" target="_blank" rel="noopener">Open in browser</a>
              <button type="button" class="btn btn-mobile mobile-preview-btn" data-label="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>" data-url="<?= htmlspecialchars($previewSrc, ENT_QUOTES) ?>">Mobile preview</button>
              <button type="button" class="btn btn-ai btn-ai-edit" title="Edit this <?= htmlspecialchars($itemKind, ENT_QUOTES) ?> with AI">✨ Edit with AI</button>
              <button type="button" class="btn btn-dup item-clone-btn" title="Duplicate within the same collection">Clone</button>
              <?php if (!$isNeutralCol): ?>
              <button type="button" class="btn btn-dup item-dup-btn">Duplicate to <?= $itemDstLabel ?></button>
              <?php endif; ?>
              <button type="button" class="btn btn-ghost copy-all-btn" data-name="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>" data-fs="<?= htmlspecialchars($item['fsPath'], ENT_QUOTES) ?>" data-web="<?= htmlspecialchars($item['webPath'], ENT_QUOTES) ?>" data-url="<?= htmlspecialchars($fullUrl, ENT_QUOTES) ?>">Copy for AI</button>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>
    <?php endforeach; ?>

    <section class="collection" id="archive">
      <div class="collection-header">
        <div>
          <h2 class="collection-title">
            <span class="brand-dot neutral"></span>
            Archive
          </h2>
          <p class="collection-desc">Deleted landers, checkouts &amp; flows are moved here instead of being destroyed. Restore one to put it back in its original collection, or delete it permanently.</p>
        </div>
        <code class="collection-meta">htdocs/.archive/</code>
      </div>

      <?php if ($archivedCount === 0): ?>
      <div class="empty-state">
        <p>Archive is empty. Anything you delete will appear here, ready to restore.</p>
      </div>
      <?php else: ?>
      <div class="grid grid-archive" data-collection="archive">
        <?php foreach ($archivedEntries as $a):
          $aType = (string)($a['type'] ?? 'item');
          $aName = (string)($a['name'] ?? $a['id']);
          $aCol = (string)($a['collection'] ?? '');
          $originLabel = $aType === 'flow'
            ? 'Flow'
            : ($collectionLabels[$aCol] ?? ($aCol !== '' ? $aCol : 'Item'));
          $when = !empty($a['archived_at']) ? date('M j, Y · g:ia', (int)$a['archived_at']) : '';
          $searchText = strtolower($aName . ' ' . $originLabel . ' ' . $aType);
        ?>
        <article class="card card-archive" data-name="<?= htmlspecialchars($aName, ENT_QUOTES) ?>" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES) ?>">
          <div class="card-body">
            <div class="flow-badges">
              <span class="flow-badge <?= $aType === 'flow' ? 'checkout' : 'sales' ?>"><?= htmlspecialchars($originLabel, ENT_QUOTES) ?></span>
            </div>
            <h3 class="card-name"><?= htmlspecialchars($aName, ENT_QUOTES) ?></h3>
            <?php if ($when !== ''): ?>
            <div class="card-entry">Archived <?= htmlspecialchars($when, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <div class="card-actions">
              <button type="button" class="btn btn-primary archive-restore-btn" data-id="<?= htmlspecialchars($a['id'], ENT_QUOTES) ?>" data-name="<?= htmlspecialchars($aName, ENT_QUOTES) ?>">Restore</button>
              <button type="button" class="btn btn-danger archive-purge-btn" data-id="<?= htmlspecialchars($a['id'], ENT_QUOTES) ?>" data-name="<?= htmlspecialchars($aName, ENT_QUOTES) ?>">Delete permanently</button>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>
  </main>
</div>

<div class="modal-overlay" id="builder" role="dialog" aria-modal="true" aria-labelledby="builder-title">
  <div class="modal modal-wizard">
    <div class="modal-head">
      <div>
        <h2 id="builder-title">Create a Flow</h2>
        <p id="builder-step-hint">Step 1 of 3 · Choose a landing page</p>
      </div>
      <button type="button" class="modal-close" data-close-builder aria-label="Close">&times;</button>
    </div>

    <div class="builder-step" data-step="1">
      <div class="picker-toolbar">
        <span class="picker-toolbar-label">Pick the sales page(s) for your funnel <span class="hint">— select several to build every combination at once</span></span>
      </div>
      <div class="picker-grid" id="lander-grid"></div>
    </div>

    <div class="builder-step" data-step="2" hidden>
      <div class="picker-toolbar">
        <span class="picker-toolbar-label" id="checkout-brand-label">Checkouts</span>
        <label class="field-inline"><input type="checkbox" id="f-allbrands"> <span>Show all brands</span></label>
      </div>
      <div class="picker-grid" id="checkout-grid"></div>
    </div>

    <div class="builder-step" data-step="3" hidden>
      <div class="confirm-row" id="confirm-row"></div>
      <p class="picker-toolbar-label" style="margin:0 0 0.6rem;">Every flow is built for <strong>both</strong> brands — AIPU and OmniRogue — and KK-formatted automatically.</p>
      <div class="field">
        <label for="f-name">Flow name <span class="hint">&mdash; optional, auto-generated if blank</span></label>
        <input type="text" id="f-name" placeholder="e.g. lander7-v3fixed" autocomplete="off">
      </div>
      <div class="flow-result" id="flow-result">
        <h3>Flow created ✓</h3>
        <div class="links" id="flow-result-links"></div>
      </div>
    </div>

    <div class="modal-foot">
      <button type="button" class="btn btn-ghost" id="builder-back" hidden>&larr; Back</button>
      <span class="modal-status" id="builder-status"></span>
      <button type="button" class="btn-create-flow" id="builder-next" disabled>Next &rarr;</button>
    </div>
  </div>
</div>

<div class="bulk-bar" id="bulk-bar" role="region" aria-label="Bulk actions">
  <span class="bulk-bar-count"><strong id="bulk-count">0</strong> selected</span>
  <button type="button" class="btn btn-ghost" id="bulk-clear">Clear</button>
  <button type="button" class="btn btn-danger" id="bulk-delete">📦 Archive selected</button>
</div>

<div class="modal-overlay" id="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
  <div class="modal" style="max-width:480px;">
    <div class="modal-head">
      <div>
        <h2 id="confirm-title">Archive selected</h2>
        <p>Moves the folder(s) to the Archive. Nothing is permanently deleted — restore anytime.</p>
      </div>
      <button type="button" class="modal-close" data-confirm-cancel aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
      <p id="confirm-msg" style="margin:0;font-size:0.9rem;"></p>
      <ul class="confirm-list" id="confirm-list"></ul>
    </div>
    <div class="modal-foot">
      <span class="modal-status" id="confirm-status"></span>
      <span style="display:flex;gap:0.5rem;">
        <button type="button" class="btn btn-ghost" data-confirm-cancel>Cancel</button>
        <button type="button" class="btn btn-danger" id="confirm-ok">📦 Archive</button>
      </span>
    </div>
  </div>
</div>

<div class="modal-overlay" id="import-modal" role="dialog" aria-modal="true" aria-labelledby="import-title">
  <div class="modal" style="max-width:560px;">
    <div class="modal-head">
      <div>
        <h2 id="import-title">Import page</h2>
        <p>Pull a lander or checkout from a URL or upload an <code>index.html</code> &mdash; brand-neutral pages stay unbranded; pick the brand when you create a flow.</p>
      </div>
      <button type="button" class="modal-close" data-import-cancel aria-label="Close">&times;</button>
    </div>
    <div class="import-tabs" role="tablist" aria-label="Import source">
      <button type="button" class="import-tab active" data-imp-tab="url" role="tab" aria-selected="true">From URL</button>
      <button type="button" class="import-tab" data-imp-tab="file" role="tab" aria-selected="false">Upload HTML</button>
    </div>
    <div class="modal-body">
      <div class="field imp-pane" data-imp-pane="url">
        <label for="imp-url">Page URL</label>
        <input type="text" id="imp-url" placeholder="https://example.com/checkout/" autocomplete="off">
      </div>
      <div class="imp-pane" data-imp-pane="file" hidden>
        <div class="field">
          <label for="imp-file">HTML file <span class="hint">&mdash; .html / .htm only</span></label>
          <label class="file-drop" id="imp-file-drop" for="imp-file">
            <input type="file" id="imp-file" accept=".html,.htm,text/html" hidden>
            <span class="file-drop-prompt">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
              <span><strong>Click to choose</strong> or drag &amp; drop an <code>index.html</code> here</span>
            </span>
            <span class="file-drop-chosen" id="imp-file-chosen" hidden></span>
          </label>
        </div>
        <div class="field">
          <label for="imp-source-url">Source URL <span class="hint">&mdash; optional, enables asset localization</span></label>
          <input type="text" id="imp-source-url" placeholder="https://example.com/checkout/ (optional)" autocomplete="off">
        </div>
      </div>
      <div class="field">
        <label for="imp-kind">Type</label>
        <select id="imp-kind">
          <option value="checkout">Checkout</option>
          <option value="lander">Lander</option>
        </select>
      </div>
      <div class="field">
        <label for="imp-target">Add to folder</label>
        <select id="imp-target"></select>
      </div>
      <div class="field">
        <label for="imp-name">Name <span class="hint">&mdash; optional, auto-generated if blank</span></label>
        <input type="text" id="imp-name" placeholder="auto from URL or filename" autocomplete="off">
      </div>
      <div class="flow-result" id="imp-result">
        <h3>Imported &check;</h3>
        <div class="links" id="imp-result-links"></div>
      </div>
    </div>
    <div class="modal-foot">
      <span class="modal-status" id="imp-status"></span>
      <button type="button" class="btn-create-flow" id="imp-go">Import page</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="cta-modal" role="dialog" aria-modal="true" aria-labelledby="cta-title">
  <div class="modal" style="max-width:780px;">
    <div class="modal-head">
      <div>
        <h2 id="cta-title">Configure register CTAs</h2>
        <p>Choose where the <strong>register-style</strong> button on each page routes. Plan buttons on <code>checkout.php</code> &amp; <code>index.php</code> stay hard-coded to their plan tokens.</p>
      </div>
      <button type="button" class="modal-close" data-cta-cancel aria-label="Close">&times;</button>
    </div>
    <div class="modal-body cta-modal-body">
      <p class="cta-flow-meta" id="cta-flow-meta"></p>
      <div class="cta-hint">
        <strong>registercheckout</strong> = Register page → billing flow ·
        <strong>registercreate</strong> = Register page → Create Studio flow.
        Plan tokens (creatormonthly etc.) take the visitor straight to that plan's checkout.
      </div>
      <div class="cta-toolbar">
        <div class="field">
          <label for="cta-default">Default for unset pages</label>
          <select id="cta-default"></select>
        </div>
        <button type="button" class="btn-bulk-apply" id="cta-apply-all" title="Set every configurable page to the default">Apply default to all</button>
      </div>
      <div class="cta-scroll">
        <table class="cta-table" id="cta-table">
          <thead>
            <tr>
              <th style="width:38%;">Page</th>
              <th>Register CTA destination</th>
            </tr>
          </thead>
          <tbody id="cta-tbody">
            <tr><td colspan="2" class="cta-empty">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="modal-foot">
      <span class="modal-status" id="cta-status"></span>
      <span style="display:flex;gap:0.5rem;">
        <button type="button" class="btn btn-ghost" data-cta-cancel>Cancel</button>
        <button type="button" class="btn-create-flow" id="cta-save">Save &amp; rebuild KK</button>
      </span>
    </div>
  </div>
</div>

<div class="modal-overlay" id="flow-plans-modal" role="dialog" aria-modal="true" aria-labelledby="flow-plans-title">
  <div class="modal" style="max-width:960px;">
    <div class="modal-head">
      <div>
        <h2 id="flow-plans-title">Configure Plans</h2>
        <p>Set which plans this flow sells, their prices &amp; points, and which offer token drives the presell page step1 link. Saved config is applied to <code>bundle.js</code> and rebuilt into KK.</p>
      </div>
      <button type="button" class="modal-close" data-flow-plans-cancel aria-label="Close">&times;</button>
    </div>
    <div class="modal-body cta-modal-body">
      <p class="cta-flow-meta" id="flow-plans-meta"></p>
      <div class="cta-hint">
        Prices &amp; points patch <code>plans-pick-your-plan/js/bundle.js</code> on save.
        Plan checkout buttons still resolve through KowboyKit offer tokens (wired at KK format).
        The <strong>Presell step1 token</strong> controls where register-style CTAs on <code>index.php</code> route
        (including their step1link).
      </div>
      <div class="cta-toolbar">
        <div class="field">
          <label for="flow-plans-index-token">Presell step1 token (index.php)</label>
          <select id="flow-plans-index-token"></select>
        </div>
        <button type="button" class="btn-bulk-apply" id="flow-plans-reset" title="Reload values from Plan Library">Reset to library</button>
      </div>
      <div class="cta-scroll">
        <table class="cta-table flow-plans-table" id="flow-plans-table">
          <thead>
            <tr>
              <th style="width:3rem;">On</th>
              <th style="width:14%;">Plan</th>
              <th>Points</th>
              <th>$/mo</th>
              <th>$/yr</th>
              <th>Monthly token</th>
              <th>Yearly token</th>
            </tr>
          </thead>
          <tbody id="flow-plans-tbody">
            <tr><td colspan="7" class="cta-empty">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="modal-foot">
      <span class="modal-status" id="flow-plans-status"></span>
      <span style="display:flex;gap:0.5rem;">
        <button type="button" class="btn btn-ghost" data-flow-plans-cancel>Cancel</button>
        <button type="button" class="btn-create-flow" id="flow-plans-save">Save &amp; rebuild KK</button>
      </span>
    </div>
  </div>
</div>

<div class="modal-overlay" id="flow-widgets-modal" role="dialog" aria-modal="true" aria-labelledby="flow-widgets-title">
  <div class="modal" style="max-width:760px;">
    <div class="modal-head">
      <div>
        <h2 id="flow-widgets-title">Attach Widgets</h2>
        <p>Pick an exit popup, a mobile slider and/or a social-proof toast, and control the built-in studio-page activity toasts — all per flow. On save everything is baked into <code>index.html</code> / <code>checkout.html</code> and the KK package is rebuilt.</p>
      </div>
      <button type="button" class="modal-close" data-flow-widgets-cancel aria-label="Close">&times;</button>
    </div>
    <div class="modal-body cta-modal-body">
      <p class="cta-flow-meta" id="flow-widgets-meta"></p>
      <div class="cta-hint">
        Sales-page CTAs send visitors to this flow&apos;s checkout with the plan preselected (scroll + highlight).
        Checkout CTAs either select the plan in place or link straight to payment, depending on the widget&apos;s
        <strong>CTA on checkout</strong> setting. Build &amp; edit templates in the
        <a href="widgets.php" target="_blank" rel="noopener">Widget Builder</a>.
      </div>
      <div class="fw-grid">
        <div class="fw-slot">
          <h3><span class="fw-kind">Popup</span> Exit popup</h3>
          <div>
            <label for="fw-popup-widget">Template</label>
            <select id="fw-popup-widget"></select>
          </div>
          <div>
            <label for="fw-popup-placement">Appears on</label>
            <select id="fw-popup-placement">
              <option value="off">Disabled</option>
              <option value="sales">Sales page only</option>
              <option value="checkout">Checkout only</option>
              <option value="both">Sales page + checkout</option>
            </select>
          </div>
          <div class="fw-warn" id="fw-popup-warn"></div>
        </div>
        <div class="fw-slot">
          <h3><span class="fw-kind">Slider</span> Mobile slider</h3>
          <div>
            <label for="fw-slider-widget">Template</label>
            <select id="fw-slider-widget"></select>
          </div>
          <div>
            <label for="fw-slider-placement">Appears on</label>
            <select id="fw-slider-placement">
              <option value="off">Disabled</option>
              <option value="sales">Sales page only</option>
              <option value="checkout">Checkout only</option>
              <option value="both">Sales page + checkout</option>
            </select>
          </div>
          <div class="fw-warn" id="fw-slider-warn"></div>
        </div>
        <div class="fw-slot" style="grid-column:1 / -1;">
          <h3><span class="fw-kind">Proof</span> Social proof (sales / checkout)</h3>
          <p class="fw-slot-note">A purchase-proof toast (&ldquo;Carl from Rhode Island just bought!&rdquo;) shown on the sales &amp; checkout pages — a real template you build in the <a href="widgets.php" target="_blank" rel="noopener">Widget Builder</a>, just like popups and sliders.</p>
          <div>
            <label for="fw-social-widget">Template</label>
            <select id="fw-social-widget"></select>
          </div>
          <div>
            <label for="fw-social-placement">Appears on</label>
            <select id="fw-social-placement">
              <option value="off">Disabled</option>
              <option value="sales">Sales page only</option>
              <option value="checkout">Checkout only</option>
              <option value="both">Sales page + checkout</option>
            </select>
          </div>
          <div class="fw-warn" id="fw-social-warn"></div>
        </div>
        <div class="fw-slot">
          <h3><span class="fw-kind">Studio</span> Studio-page activity toasts</h3>
          <p class="fw-slot-note">The built-in &ldquo;Maria in Texas just created&hellip;&rdquo; activity toasts shown on the studio pages — AI Video, Image, Audio, Music&hellip; No template needed.</p>
          <div>
            <label for="fw-studio-placement">Toasts</label>
            <select id="fw-studio-placement">
              <option value="on">Enabled (studio pages)</option>
              <option value="off">Disabled</option>
            </select>
          </div>
          <div class="fw-warn" id="fw-studio-warn"></div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <span class="modal-status" id="flow-widgets-status"></span>
      <span style="display:flex;gap:0.5rem;">
        <button type="button" class="btn btn-ghost" data-flow-widgets-cancel>Cancel</button>
        <button type="button" class="btn-create-flow" id="flow-widgets-save">Save &amp; rebuild</button>
      </span>
    </div>
  </div>
</div>

<div class="modal-overlay" id="flow-timer-modal" role="dialog" aria-modal="true" aria-labelledby="flow-timer-title">
  <div class="modal" style="max-width:560px;">
    <div class="modal-head">
      <div>
        <h2 id="flow-timer-title">Set Timer</h2>
        <p>One countdown for the whole funnel. The configured time becomes the single source of truth — the sales page promo bar, the checkout, the mobile slider and the exit popup all count down to the same moment. The per-page timer scripts are switched off so nothing drifts.</p>
      </div>
      <button type="button" class="modal-close" data-flow-timer-cancel aria-label="Close">&times;</button>
    </div>
    <div class="modal-body cta-modal-body">
      <p class="cta-flow-meta" id="flow-timer-meta"></p>
      <div class="ft-toggle-row">
        <input type="checkbox" id="ft-enabled">
        <label for="ft-enabled">Enable centralized countdown for this flow</label>
      </div>
      <div id="ft-config">
        <p class="ft-section-label">Countdown duration (per visitor, restarts when it hits zero)</p>
        <div class="ft-duration">
          <div class="ft-field"><label for="ft-d">Days</label><input type="number" id="ft-d" min="0" max="365" value="0"></div>
          <div class="ft-field"><label for="ft-h">Hours</label><input type="number" id="ft-h" min="0" max="23" value="12"></div>
          <div class="ft-field"><label for="ft-m">Minutes</label><input type="number" id="ft-m" min="0" max="59" value="31"></div>
          <div class="ft-field"><label for="ft-s">Seconds</label><input type="number" id="ft-s" min="0" max="59" value="0"></div>
        </div>
        <div class="ft-row">
          <div>
            <label for="ft-scope">Persistence</label>
            <select id="ft-scope">
              <option value="session">Per session (resets in a new tab/visit)</option>
              <option value="persistent">Persistent (evergreen across days, per device)</option>
            </select>
          </div>
          <div>
            <p class="ft-section-label" style="margin:0 0 0.5rem;">Applies to</p>
            <div class="ft-apply">
              <span class="ft-check"><input type="checkbox" id="ft-apply-sales" checked> <label for="ft-apply-sales" style="margin:0;">Sales page</label></span>
              <span class="ft-check"><input type="checkbox" id="ft-apply-checkout" checked> <label for="ft-apply-checkout" style="margin:0;">Checkout</label></span>
              <span class="ft-check"><input type="checkbox" id="ft-apply-widgets" checked> <label for="ft-apply-widgets" style="margin:0;">Slider &amp; popup</label></span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <span class="modal-status" id="flow-timer-status"></span>
      <span style="display:flex;gap:0.5rem;">
        <button type="button" class="btn btn-ghost" data-flow-timer-cancel>Cancel</button>
        <button type="button" class="btn-create-flow" id="flow-timer-save">Save &amp; apply</button>
      </span>
    </div>
  </div>
</div>

<div class="modal-overlay" id="flow-assets-modal" role="dialog" aria-modal="true" aria-labelledby="flow-assets-title">
  <div class="modal" style="max-width:560px;">
    <div class="modal-head">
      <div>
        <h2 id="flow-assets-title">Optimize &amp; Publish</h2>
        <p>Compress images, generate WebP, minify CSS/JS, and (optionally) publish this flow&apos;s assets to BunnyCDN with URLs rewritten to the pull zone. Optimization is safe and reversible; CDN publishing only runs when configured.</p>
      </div>
      <button type="button" class="modal-close" data-flow-assets-cancel aria-label="Close">&times;</button>
    </div>
    <div class="modal-body cta-modal-body">
      <p class="cta-flow-meta" id="flow-assets-meta"></p>
      <div class="fa-toggle-row">
        <input type="checkbox" id="fa-optimize" checked>
        <label for="fa-optimize">Optimize assets on every build
          <span class="fa-sub">Recompress PNG/JPG, build <code>.webp</code> siblings, minify CSS/JS. Runs automatically; turn off to skip.</span>
        </label>
      </div>
      <div class="fa-cdn-block" id="fa-cdn-block">
        <div class="fa-toggle-row" style="margin-bottom:0;">
          <input type="checkbox" id="fa-cdn-publish">
          <label for="fa-cdn-publish">Publish assets to BunnyCDN
            <span class="fa-sub">Upload to an immutable, far-future-cacheable build folder.</span>
          </label>
        </div>
        <div class="fa-toggle-row" style="margin:0.7rem 0 0;">
          <input type="checkbox" id="fa-cdn-rewrite">
          <label for="fa-cdn-rewrite">Rewrite asset URLs to the CDN
            <span class="fa-sub">Point pages at the pull zone (asset files only; navigation untouched). Implies publish.</span>
          </label>
        </div>
        <p class="fa-cdn-note" id="fa-cdn-note"></p>
      </div>
      <div class="fa-results" id="fa-results" style="display:none;"></div>
    </div>
    <div class="modal-foot">
      <span class="modal-status" id="flow-assets-status"></span>
      <span style="display:flex;gap:0.5rem;">
        <button type="button" class="btn btn-ghost" data-flow-assets-cancel>Cancel</button>
        <button type="button" class="btn-create-flow" id="flow-assets-save">Save &amp; run</button>
      </span>
    </div>
  </div>
</div>

<div class="modal-overlay" id="tester-modal" role="dialog" aria-modal="true" aria-labelledby="tester-title">
  <div class="modal" style="max-width:860px;">
    <div class="modal-head">
      <div>
        <h2 id="tester-title">Live Flow Tester</h2>
        <p>Paste the live URL of a funnel <strong>after you upload it to KK</strong>. A headless browser clicks every page, link &amp; popup and checks each one either stays on the page or reaches a working step1 product &mdash; then gives you a <strong>green / red</strong> verdict.</p>
      </div>
      <button type="button" class="modal-close" data-tester-cancel aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
      <div class="field">
        <label for="tester-url">Live funnel URL</label>
        <input type="text" id="tester-url" placeholder="https://yourdomain.com/your-lander/index.php" autocomplete="off" spellcheck="false">
      </div>
      <div class="field">
        <label for="tester-host">Expected product host(s) <span class="hint">&mdash; optional, comma-separated. When set, every CTA must land here.</span></label>
        <input type="text" id="tester-host" placeholder="checkout.kowboykit.com, app.omnirogue.com" autocomplete="off" spellcheck="false">
      </div>
      <div class="field" style="max-width:240px;">
        <label for="tester-maxpages">Max pages to crawl</label>
        <input type="number" id="tester-maxpages" value="40" min="1" max="120">
      </div>

      <div class="ft-run" id="tester-run" hidden>
        <div class="ft-verdict" id="tester-verdict">
          <span class="ft-light" id="tester-light"></span>
          <div class="ft-verdict-text">
            <strong id="tester-verdict-title">Testing…</strong>
            <span id="tester-verdict-sub">Launching headless browser</span>
          </div>
        </div>
        <div class="ft-progress"><div class="ft-progress-bar" id="tester-bar"></div></div>
        <div class="ft-stats" id="tester-stats"></div>
        <div class="ft-findings" id="tester-findings"></div>
      </div>
    </div>
    <div class="modal-foot">
      <span class="modal-status" id="tester-status"></span>
      <span style="display:flex;gap:0.5rem;">
        <button type="button" class="btn btn-ghost" data-tester-cancel>Close</button>
        <button type="button" class="btn-create-flow" id="tester-go">Run Flow Test</button>
      </span>
    </div>
  </div>
</div>

<div class="modal-overlay" id="clone-modal" role="dialog" aria-modal="true" aria-labelledby="clone-title">
  <div class="modal" style="max-width:480px;">
    <div class="modal-head">
      <div>
        <h2 id="clone-title">Clone item</h2>
        <p>Duplicates the folder within the same brand. KK package is rebuilt automatically for flows.</p>
      </div>
      <button type="button" class="modal-close" data-clone-cancel aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
      <p class="cta-flow-meta" id="clone-source"></p>
      <div class="field">
        <label for="clone-name">New folder name <span class="hint">&mdash; leave blank for <code>&lt;name&gt;-clone</code></span></label>
        <input type="text" id="clone-name" placeholder="auto-generated" autocomplete="off">
      </div>
    </div>
    <div class="modal-foot">
      <span class="modal-status" id="clone-status"></span>
      <span style="display:flex;gap:0.5rem;">
        <button type="button" class="btn btn-ghost" data-clone-cancel>Cancel</button>
        <button type="button" class="btn-create-flow" id="clone-go">Clone</button>
      </span>
    </div>
  </div>
</div>

<div class="modal-overlay" id="plans-modal" role="dialog" aria-modal="true" aria-labelledby="plans-title">
  <div class="modal" style="max-width:920px;">
    <div class="modal-head">
      <div>
        <h2 id="plans-title">Plan Library</h2>
        <p>The central registry of plan prices, points &amp; offer tokens. Edit definitions here, then use <strong>Configure Plans</strong> on a flow to apply them into that funnel&apos;s checkout.</p>
      </div>
      <button type="button" class="modal-close" data-plans-cancel aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
      <div class="cta-hint">
        Each plan maps to its KowboyKit <strong>offer token</strong> (e.g. <code>creatormonthly</code>). The token dropdowns also list the two
        <strong>register destinations</strong> &mdash; <code>registercheckout</code> (register → billing) and <code>registercreate</code> (register → Create Studio)
        &mdash; for register-style CTAs. Plan buttons should use their own plan token, not a register token.
      </div>
      <div class="plans-toolbar">
        <span class="cta-flow-meta" id="plans-meta" style="margin:0;">data/plan-library.json</span>
        <button type="button" class="btn-create-flow" id="plans-add">+ Add plan</button>
      </div>
      <div class="plans-scroll" id="plans-list">
        <div class="cta-empty">Loading&hellip;</div>
      </div>
    </div>
    <div class="modal-foot">
      <span class="modal-status" id="plans-status"></span>
      <button type="button" class="btn btn-ghost" data-plans-cancel>Close</button>
    </div>
  </div>
</div>

<div class="mobile-preview-overlay" id="mobile-preview" role="dialog" aria-modal="true" aria-labelledby="mobile-preview-title">
  <div class="mobile-preview-toolbar">
    <h2 id="mobile-preview-title">Mobile preview</h2>
    <div class="mobile-preview-controls">
      <div class="mobile-page-tabs" id="mobile-page-tabs" hidden>
        <button type="button" data-mobile-page="sales" class="active">Sales page</button>
        <button type="button" data-mobile-page="checkout">Checkout</button>
      </div>
      <label for="mobile-device-select">Device</label>
      <select id="mobile-device-select" aria-label="Device size">
        <option value="iphone-se">iPhone SE (375)</option>
        <option value="iphone-14" selected>iPhone 14 (390)</option>
        <option value="iphone-14-max">iPhone 14 Pro Max (430)</option>
        <option value="pixel-7">Pixel 7 (412)</option>
        <option value="galaxy-s20">Galaxy S20 (360)</option>
      </select>
      <a class="btn btn-ghost" id="mobile-open-tab" href="#" target="_blank" rel="noopener">Open in new tab ↗</a>
      <button type="button" class="btn btn-ghost" data-mobile-close>Close</button>
    </div>
  </div>
  <div class="mobile-preview-stage">
    <div class="mobile-device" id="mobile-device">
      <div class="mobile-device-screen">
        <iframe id="mobile-preview-frame" title="Mobile page preview"></iframe>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<div class="gen-queue" id="gen-queue" role="status" aria-live="polite" aria-label="Flow generation queue">
  <div class="gen-queue-head">
    <span class="gqi-icon" aria-hidden="true">⚙</span>
    <span class="gen-queue-title">Generating flows</span>
    <span class="gen-queue-count" id="gen-queue-count"></span>
    <button type="button" id="gen-queue-collapse" title="Collapse" aria-label="Collapse">▾</button>
    <button type="button" id="gen-queue-dismiss" title="Dismiss" aria-label="Dismiss" hidden>×</button>
  </div>
  <ul class="gen-queue-list" id="gen-queue-list"></ul>
</div>

<script>
window.FLOW_DATA = {
  landers: <?= json_encode($landerOptions, JSON_UNESCAPED_SLASHES) ?>,
  checkouts: <?= json_encode($checkoutOptions, JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script>
(function () {
  const toast = document.getElementById('toast');
  let toastTimer;
  const REGISTER_TOKENS = ['registercreate', 'registercheckout'];

  function showToast(msg) {
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 1800);
  }

  /* Reload a flow's preview iframe(s) so server-side edits (timer, assets, …)
     show immediately. The iframe src is a static /flows/<slug>/index.html, so we
     append a cache-busting query param to force a fresh fetch even though the
     URL is otherwise identical. */
  function reloadFlowPreview(flow) {
    if (!flow) return;
    const frames = document.querySelectorAll('iframe[data-flow="' + (window.CSS && CSS.escape ? CSS.escape(flow) : flow) + '"]');
    frames.forEach(frame => {
      const base = frame.getAttribute('data-preview-src') || frame.src.split('?')[0];
      frame.src = base + (base.indexOf('?') >= 0 ? '&' : '?') + '_ts=' + Date.now();
    });
  }

  /* --- automatic QC gate: render the qc block the build scripts attach --- */
  function qcText(json) {
    const qc = json && json.qc;
    if (!qc || !qc.status) return '';
    if (qc.status === 'pass') return 'QC ✓ passed';
    const ne = (qc.errors || []).length, nw = (qc.warnings || []).length;
    const bits = [];
    if (ne) bits.push(ne + ' error' + (ne !== 1 ? 's' : ''));
    if (nw) bits.push(nw + ' warning' + (nw !== 1 ? 's' : ''));
    return 'QC ' + (qc.status === 'fail' ? '✕' : '⚠') + ' ' + (bits.join(', ') || qc.status);
  }
  function qcDetailHtml(json) {
    const qc = json && json.qc;
    if (!qc || !qc.status || qc.status === 'pass') return '';
    const msgs = (qc.errors || []).concat(qc.warnings || []);
    const shown = msgs.slice(0, 12).map(m => '<li>' + esc(m) + '</li>').join('');
    const more = msgs.length > 12 ? '<li>… and ' + (msgs.length - 12) + ' more</li>' : '';
    const color = qc.status === 'fail' ? '#b42318' : '#b54708';
    const bg = qc.status === 'fail' ? '#fef3f2' : '#fffaeb';
    return '<div style="margin-top:0.5rem;padding:0.5rem 0.7rem;border-radius:8px;font-size:12px;'
      + 'background:' + bg + ';color:' + color + ';border:1px solid ' + color + '33;">'
      + '<b>' + esc(qcText(json)) + '</b>'
      + '<ul style="margin:0.35rem 0 0;padding-left:1.1rem;line-height:1.5;">' + shown + more + '</ul></div>';
  }

  async function copyText(text) {
    try {
      await navigator.clipboard.writeText(text);
      showToast('Copied to clipboard');
    } catch {
      const ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      showToast('Copied to clipboard');
    }
  }

  document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      copyText(btn.dataset.copy);
      btn.classList.add('copied');
      setTimeout(() => btn.classList.remove('copied'), 1200);
    });
  });

  document.querySelectorAll('.copy-all-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const block = [
        'Name: ' + btn.dataset.name,
        'Filesystem: ' + btn.dataset.fs,
        'Web path: ' + btn.dataset.web,
        'URL: ' + btn.dataset.url,
      ].join('\n');
      copyText(block);
    });
  });

  const search = document.getElementById('search');
  search.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    document.querySelectorAll('.card').forEach(card => {
      const hay = card.dataset.search || '';
      card.classList.toggle('hidden', q !== '' && !hay.includes(q));
    });
  });

  const viewToggle = document.querySelector('.view-toggle[aria-label="View mode"]');
  viewToggle.addEventListener('click', e => {
    const btn = e.target.closest('button[data-view]');
    if (!btn) return;
    viewToggle.querySelectorAll('button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const list = btn.dataset.view === 'list';
    document.querySelectorAll('.grid').forEach(grid => {
      grid.classList.toggle('list-view', list);
    });
  });

  /* -------------------------------------------------------- mobile preview */
  const MOBILE_DEVICES = {
    'iphone-se': { w: 375, h: 667 },
    'iphone-14': { w: 390, h: 844 },
    'iphone-14-max': { w: 430, h: 932 },
    'pixel-7': { w: 412, h: 915 },
    'galaxy-s20': { w: 360, h: 800 },
  };

  const mobileOverlay = document.getElementById('mobile-preview');
  const mobileFrame = document.getElementById('mobile-preview-frame');
  const mobileTitle = document.getElementById('mobile-preview-title');
  const mobileDeviceSelect = document.getElementById('mobile-device-select');
  const mobileOpenTab = document.getElementById('mobile-open-tab');
  const mobilePageTabs = document.getElementById('mobile-page-tabs');
  let mobileState = { salesUrl: '', checkoutUrl: '', page: 'sales', label: '' };

  function applyMobileDevice() {
    const dev = MOBILE_DEVICES[mobileDeviceSelect.value] || MOBILE_DEVICES['iphone-14'];
    mobileFrame.style.width = dev.w + 'px';
    mobileFrame.style.height = dev.h + 'px';
  }

  function currentMobileUrl() {
    return mobileState.page === 'checkout' && mobileState.checkoutUrl
      ? mobileState.checkoutUrl
      : mobileState.salesUrl;
  }

  function syncMobileFrame() {
    const url = currentMobileUrl();
    // cache-bust so freshly saved edits (timer, assets, …) always render
    mobileFrame.src = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_ts=' + Date.now();
    let tabUrl = 'mobile.php?url=' + encodeURIComponent(mobileState.salesUrl)
      + '&device=' + encodeURIComponent(mobileDeviceSelect.value)
      + '&label=' + encodeURIComponent(mobileState.label);
    if (mobileState.checkoutUrl) {
      tabUrl += '&checkout=' + encodeURIComponent(mobileState.checkoutUrl)
        + '&page=' + encodeURIComponent(mobileState.page);
    }
    mobileOpenTab.href = tabUrl;
  }

  function setMobilePage(page) {
    mobileState.page = page;
    mobilePageTabs.querySelectorAll('button').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.mobilePage === page);
    });
    syncMobileFrame();
  }

  function openMobilePreview(opts) {
    mobileState = {
      salesUrl: opts.url || '',
      checkoutUrl: opts.checkout || '',
      page: opts.page || 'sales',
      label: opts.label || 'Mobile preview',
    };
    mobileTitle.textContent = mobileState.label;
    if (mobileState.checkoutUrl) {
      mobilePageTabs.hidden = false;
      setMobilePage(mobileState.page);
    } else {
      mobilePageTabs.hidden = true;
      mobileState.page = 'sales';
      syncMobileFrame();
    }
    if (opts.device && MOBILE_DEVICES[opts.device]) {
      mobileDeviceSelect.value = opts.device;
    }
    applyMobileDevice();
    mobileOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeMobilePreview() {
    mobileOverlay.classList.remove('open');
    document.body.style.overflow = '';
    mobileFrame.removeAttribute('src');
  }

  document.querySelectorAll('.mobile-preview-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      e.stopPropagation();
      openMobilePreview({
        url: btn.dataset.url,
        checkout: btn.dataset.checkout || '',
        label: btn.dataset.label || 'Mobile preview',
      });
    });
  });

  mobileDeviceSelect.addEventListener('change', () => {
    applyMobileDevice();
    syncMobileFrame();
  });
  mobilePageTabs.addEventListener('click', e => {
    const tab = e.target.closest('button[data-mobile-page]');
    if (!tab || tab.disabled) return;
    setMobilePage(tab.dataset.mobilePage);
  });
  document.querySelectorAll('[data-mobile-close]').forEach(b => b.addEventListener('click', closeMobilePreview));
  mobileOverlay.addEventListener('click', e => { if (e.target === mobileOverlay) closeMobilePreview(); });

  const previewModeToggle = document.querySelector('.preview-mode-toggle');
  const PREVIEW_MODE_KEY = 'previews-thumb-mode';
  function setPreviewMode(mode) {
    const mobile = mode === 'mobile';
    document.querySelectorAll('.preview-frame-wrap').forEach(wrap => {
      wrap.classList.toggle('mobile-thumb', mobile);
    });
    previewModeToggle.querySelectorAll('button').forEach(b => {
      b.classList.toggle('active', b.dataset.previewMode === mode);
    });
    try { localStorage.setItem(PREVIEW_MODE_KEY, mode); } catch (_) {}
  }
  previewModeToggle.addEventListener('click', e => {
    const btn = e.target.closest('button[data-preview-mode]');
    if (!btn) return;
    setPreviewMode(btn.dataset.previewMode);
  });
  try {
    const savedMode = localStorage.getItem(PREVIEW_MODE_KEY);
    if (savedMode === 'mobile') setPreviewMode('mobile');
  } catch (_) {}

  const THEME_KEY = 'catalog-theme';
  const themeToggle = document.querySelector('.theme-toggle');
  function setTheme(theme) {
    const next = theme === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    themeToggle.querySelectorAll('button[data-theme]').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.theme === next);
    });
    try { localStorage.setItem(THEME_KEY, next); } catch (_) {}
  }
  themeToggle.addEventListener('click', e => {
    const btn = e.target.closest('button[data-theme]');
    if (!btn) return;
    setTheme(btn.dataset.theme);
  });
  setTheme(document.documentElement.getAttribute('data-theme') || 'dark');

  const sections = document.querySelectorAll('.collection');
  const navLinks = document.querySelectorAll('.nav-link');
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      navLinks.forEach(link => {
        link.classList.toggle('active', link.getAttribute('href') === '#' + entry.target.id);
      });
    });
  }, { rootMargin: '-30% 0px -60% 0px' });
  sections.forEach(s => observer.observe(s));

  /* ---------------------------------------------------------- flow builder */
  const BRAND_LABEL = { aipu: 'AIPU', omni: 'OmniRogue' };
  const data = window.FLOW_DATA || { landers: [], checkouts: [] };
  const overlay = document.getElementById('builder');
  const stepHint = document.getElementById('builder-step-hint');
  const landerGrid = document.getElementById('lander-grid');
  const checkoutGrid = document.getElementById('checkout-grid');
  const allBrands = document.getElementById('f-allbrands');
  const brandLabelEl = document.getElementById('checkout-brand-label');
  const nameInput = document.getElementById('f-name');
  const statusEl = document.getElementById('builder-status');
  const nextBtn = document.getElementById('builder-next');
  const backBtn = document.getElementById('builder-back');
  const confirmRow = document.getElementById('confirm-row');
  const resultBox = document.getElementById('flow-result');
  const resultLinks = document.getElementById('flow-result-links');

  // Multi-select: pick several sales pages and/or checkouts and every
  // combination is built (2 sales × 3 checkouts = 6 flows = 12 brand builds).
  let step = 1, selLanders = [], selCheckouts = [], landerBuilt = false;

  const STEP_HINTS = {
    1: 'Step 1 of 3 \u00b7 Choose sales page(s) \u2014 pick more than one to build a matrix',
    2: 'Step 2 of 3 \u00b7 Choose checkout(s) \u2014 pick more than one to build a matrix',
    3: 'Step 3 of 3 \u00b7 Review &amp; create (both brands)'
  };

  function esc(s) { return (s || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }
  function thumb(webPath, name) {
    return '<div class="picker-thumb"><iframe loading="lazy" tabindex="-1" title="Preview ' + esc(name) + '" src="' + esc(webPath) + '"></iframe></div>';
  }
  function brandTag(brand) {
    if (!brand) return '<span class="brand-tag neutral">Neutral</span>';
    return '<span class="brand-tag ' + brand + '">' + (BRAND_LABEL[brand] || brand) + '</span>';
  }
  function makeItem(o) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'picker-item';
    el.dataset.value = o.collection + '|' + o.name;
    el.innerHTML = thumb(o.webPath, o.name) +
      '<div class="picker-label"><span class="picker-name">' + esc(o.name) + '</span>' + brandTag(o.brand) + '</div>';
    return el;
  }

  const NONE_LANDER = { none: true, name: 'No sales page', collection: '', brand: null };
  const NONE_CHECKOUT = { none: true, name: 'No checkout', collection: '', brand: null };

  function makeNoneItem(label, sub) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'picker-item';
    el.dataset.value = '__none__';
    el.innerHTML = '<div class="picker-thumb" style="display:flex;align-items:center;justify-content:center;font-size:26px;color:var(--text-dim,#888)">&empty;</div>' +
      '<div class="picker-label"><span class="picker-name">' + esc(label) + '</span>' +
      '<span class="brand-tag neutral">' + esc(sub) + '</span></div>';
    return el;
  }

  function isSel(list, o) {
    return list.some(x => x === o || (x.collection === o.collection && x.name === o.name && !!x.none === !!o.none));
  }
  function toggleSel(list, o) {
    const i = list.findIndex(x => x === o || (x.collection === o.collection && x.name === o.name && !!x.none === !!o.none));
    if (i >= 0) list.splice(i, 1);
    else {
      if (o.none) list.length = 0;                       // "none" is exclusive
      else { const n = list.findIndex(x => x.none); if (n >= 0) list.splice(n, 1); }
      list.push(o);
    }
  }
  function refreshPickerSelection(grid, list) {
    grid.querySelectorAll('.picker-item').forEach(el => {
      const v = el.dataset.value;
      el.classList.toggle('selected', list.some(o => (o.none ? '__none__' : o.collection + '|' + o.name) === v));
    });
  }

  function buildLanderGrid() {
    if (landerBuilt) return;
    landerGrid.innerHTML = '';
    const noneEl = makeNoneItem('No sales page', '1-page checkout flow');
    noneEl.addEventListener('click', () => selectLander(NONE_LANDER));
    landerGrid.appendChild(noneEl);
    if (!data.landers.length) {
      landerGrid.innerHTML += '<div class="picker-empty">No landers found.</div>';
    }
    data.landers.forEach(o => {
      const el = makeItem(o);
      el.addEventListener('click', () => selectLander(o));
      landerGrid.appendChild(el);
    });
    landerBuilt = true;
  }

  function selectLander(o) {
    toggleSel(selLanders, o);
    refreshPickerSelection(landerGrid, selLanders);
    updateNext();
  }

  function buildCheckoutGrid() {
    const realLanders = selLanders.filter(o => !o.none);
    const brands = [...new Set(realLanders.map(o => o.brand).filter(Boolean))];
    const brand = brands.length === 1 ? brands[0] : null;
    const showAll = allBrands.checked;
    // Neutral checkouts (o.brand falsy) always show. When a *legacy* lander is
    // selected, same-brand legacy checkouts also show; the brand filter only
    // applies between branded sources.
    const list = data.checkouts.filter(o => showAll || !o.brand || !brand || o.brand === brand);
    const brandWord = brand ? (BRAND_LABEL[brand] || brand) : 'Neutral';
    brandLabelEl.textContent = showAll ? 'All checkouts' : brandWord + ' & neutral checkouts';
    checkoutGrid.innerHTML = '';
    // Sales-only single-page flow: only offered when a real lander is selected.
    if (realLanders.length) {
      const noneEl = makeNoneItem('No checkout', '1-page sales flow');
      noneEl.addEventListener('click', () => selectCheckout(NONE_CHECKOUT));
      checkoutGrid.appendChild(noneEl);
    } else {
      const n = selCheckouts.findIndex(x => x.none);
      if (n >= 0) selCheckouts.splice(n, 1);
    }
    if (!list.length) {
      checkoutGrid.innerHTML += '<div class="picker-empty">No checkouts yet. ' +
        'Tick \u201cShow all brands\u201d, or add one to /checkout-pages/.</div>';
      refreshPickerSelection(checkoutGrid, selCheckouts);
      updateNext();
      return;
    }
    list.forEach(o => {
      const el = makeItem(o);
      el.addEventListener('click', () => selectCheckout(o));
      checkoutGrid.appendChild(el);
    });
    refreshPickerSelection(checkoutGrid, selCheckouts);
    updateNext();
  }

  function selectCheckout(o) {
    toggleSel(selCheckouts, o);
    refreshPickerSelection(checkoutGrid, selCheckouts);
    updateNext();
  }

  // Every (lander, checkout) combination to build. "None" entries become a
  // missing side; the none×none combo is dropped.
  function comboList() {
    const ls = selLanders.length ? selLanders : [NONE_LANDER];
    const cs = selCheckouts.length ? selCheckouts : [NONE_CHECKOUT];
    const out = [];
    ls.forEach(l => cs.forEach(c => {
      if (l.none && c.none) return;
      out.push({ lander: l.none ? null : l, checkout: c.none ? null : c });
    }));
    return out;
  }

  // Default flow name for a combo — mirrors create-flow.php's auto-naming
  // (lander__checkout, or the single side when one is missing). Slugged server-side.
  function defaultFlowName(cmb) {
    const l = cmb.lander, c = cmb.checkout;
    return l && c ? l.name + '__' + c.name : (l ? l.name : (c ? c.name : ''));
  }
  function comboLabel(cmb) {
    return (cmb.lander ? cmb.lander.name : '(no sales page)') + ' → ' +
           (cmb.checkout ? cmb.checkout.name : '(no checkout)');
  }

  function renderConfirm() {
    const combos = comboList();
    const single = combos.length === 1;
    const card = (cap, o, sub) => !o
      ? '<div class="confirm-card"><div class="confirm-cap">' + cap + '</div>' +
        '<div class="picker-thumb" style="display:flex;align-items:center;justify-content:center;color:var(--text-dim,#888)">' + esc(sub) + '</div>' +
        '<div class="picker-label"><span class="picker-name">none</span></div></div>'
      : '<div class="confirm-card"><div class="confirm-cap">' + cap + '</div>' + thumb(o.webPath, o.name) +
        '<div class="picker-label"><span class="picker-name">' + esc(o.name) + '</span>' + brandTag(o.brand) + '</div></div>';

    const nameField = nameInput.closest('.field');

    if (single) {
      if (nameField) nameField.hidden = false;
      confirmRow.innerHTML =
        card('Landing page', combos[0].lander, 'Single page: checkout hosted as index') +
        '<div class="confirm-arrow">\u2192</div>' +
        card('Checkout', combos[0].checkout, 'Single page: sales page only');
      nameInput.disabled = false;
      if (!nameInput.value.trim()) {
        nameInput.value = defaultFlowName(combos[0]);
      }
    } else {
      // The single "Flow name" field can't name N flows \u2014 hide it and give each
      // combination its own editable name pre-filled with the default slug.
      if (nameField) nameField.hidden = true;
      nameInput.value = '';
      const rows = combos.map((cmb, i) =>
        '<li class="matrix-row">' +
          '<div class="matrix-row-pair"><strong>' +
            esc(cmb.lander ? cmb.lander.name : '(no sales page)') + '</strong> \u2192 ' +
            esc(cmb.checkout ? cmb.checkout.name : '(no checkout)') + '</div>' +
          '<input type="text" class="matrix-name-input" data-combo-index="' + i + '"' +
            ' value="' + esc(defaultFlowName(cmb)) + '" autocomplete="off"' +
            ' aria-label="Flow name for ' + esc(comboLabel(cmb)) + '">' +
        '</li>').join('');
      confirmRow.innerHTML =
        '<div class="matrix-summary">' +
          '<div class="matrix-count">' + combos.length + ' flows will be created' +
            '<span class="matrix-sub">' + (selLanders.filter(o=>!o.none).length || 'no') + ' sales page(s) \u00d7 ' +
            (selCheckouts.filter(o=>!o.none).length || 'no') + ' checkout(s) \u00b7 both brands \u2192 ' +
            (combos.length * 2) + ' builds</span></div>' +
          '<div class="matrix-hint">Default names are shown below \u2014 edit any of them before creating.</div>' +
          '<ul class="matrix-names">' + rows + '</ul>' +
        '</div>';
    }
  }

  function updateNext() {
    let ok = true;
    if (step === 1) ok = selLanders.length > 0;
    else if (step === 2) ok = selCheckouts.length > 0;
    // Step 3 (name + create) has no required input — both brands always build.
    nextBtn.disabled = !ok;
  }

  function showStep(n) {
    step = n;
    overlay.querySelectorAll('.builder-step').forEach(s => { s.hidden = (parseInt(s.dataset.step, 10) !== n); });
    stepHint.innerHTML = STEP_HINTS[n];
    backBtn.hidden = (n === 1);
    if (n === 3) {
      const count = comboList().length;
      nextBtn.textContent = count > 1 ? ('Create ' + count + ' flows') : 'Create flow';
    } else {
      nextBtn.textContent = 'Next \u2192';
    }
    if (n === 2) buildCheckoutGrid();
    if (n === 3) renderConfirm();
    updateNext();
  }

  function resetBuilder() {
    selLanders = []; selCheckouts = [];
    allBrands.checked = false;
    nameInput.value = '';
    nameInput.disabled = false;
    nameInput.placeholder = 'e.g. lander7-v3fixed';
    const nameField = nameInput.closest('.field');
    if (nameField) nameField.hidden = false;
    statusEl.textContent = ''; statusEl.className = 'modal-status';
    resultBox.classList.remove('show');
    if (landerBuilt) landerGrid.querySelectorAll('.picker-item').forEach(x => x.classList.remove('selected'));
  }

  function openBuilder(preLanderValue) {
    resetBuilder();
    buildLanderGrid();
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    if (preLanderValue) {
      const o = data.landers.find(l => (l.collection + '|' + l.name) === preLanderValue);
      if (o) {
        selectLander(o);
        showStep(2);
        return;
      }
    }
    showStep(1);
  }

  function closeBuilder() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  function submitFlow() {
    const combos = comboList();
    if (!combos.length) {
      statusEl.textContent = 'Pick a sales page, a checkout, or both.';
      statusEl.className = 'modal-status error';
      return;
    }

    // Per-combination names from the matrix inputs (matrix mode only). Indexed to
    // match comboList()'s deterministic order, which is unchanged since render.
    const matrixInputs = Array.from(confirmRow.querySelectorAll('.matrix-name-input'));

    // Fire-and-forget: queue every combo and hand off to the background queue,
    // then close the modal so the operator can keep working. Progress shows in
    // the top-right widget; finished flows live-insert into the grid.
    combos.forEach((cmb, i) => {
      const label = (cmb.lander ? cmb.lander.name : 'no-lander') + ' \u2192 ' + (cmb.checkout ? cmb.checkout.name : 'no-checkout');
      const flowName = combos.length === 1
        ? nameInput.value.trim()
        : (matrixInputs[i] ? matrixInputs[i].value.trim() : '');
      const payload = { flow_name: flowName };
      if (cmb.lander) {
        payload.lander_collection = cmb.lander.collection;
        payload.lander_name = cmb.lander.name;
      }
      if (cmb.checkout) {
        payload.checkout_collection = cmb.checkout.collection;
        payload.checkout_name = cmb.checkout.name;
      }
      window.__enqueueGen(payload, label);
    });

    closeBuilder();
    showToast(combos.length === 1
      ? 'Flow queued \u2014 generating in the background\u2026'
      : (combos.length + ' flows queued \u2014 generating in the background\u2026'));
  }

  document.querySelectorAll('[data-open-builder]').forEach(b => b.addEventListener('click', () => openBuilder()));
  document.querySelectorAll('.create-flow-from-lander').forEach(b => b.addEventListener('click', () => openBuilder(b.dataset.lander)));
  document.querySelectorAll('.create-flow-from-single').forEach(b => b.addEventListener('click', () => {
    const parts = (b.dataset.single || '').split('|');
    if (parts.length < 2) return;
    const [col, name] = parts;
    const defaultName = name;
    const flowName = prompt('Flow name (leave blank to use "' + defaultName + '"):', '') ?? '';
    const payload = { single_collection: col, single_name: name, flow_name: flowName.trim() };
    window.__enqueueGen(payload, name + ' (single-page)');
  }));
  document.querySelectorAll('[data-close-builder]').forEach(b => b.addEventListener('click', closeBuilder));
  overlay.addEventListener('click', e => { if (e.target === overlay) closeBuilder(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.classList.contains('open')) closeBuilder(); });
  allBrands.addEventListener('change', buildCheckoutGrid);
  backBtn.addEventListener('click', () => { if (step > 1) showStep(step - 1); });
  nextBtn.addEventListener('click', () => {
    if (step === 1 && selLanders.length) showStep(2);
    else if (step === 2 && selCheckouts.length) showStep(3);
    else if (step === 3) submitFlow();
  });

  document.querySelectorAll('.kk-format-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const flow = btn.dataset.flow;
      const reformat = btn.dataset.reformat === '1';
      if (reformat && !confirm('Re-run KK Format for "' + flow + '"? This rebuilds its index.php / checkout.php package.')) return;
      const original = btn.textContent;
      btn.classList.add('is-busy');
      btn.textContent = 'Formatting…';
      try {
        const res = await fetch('kk-format.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ flow })
        });
        const json = await res.json();
        if (json.ok) {
          let msg = 'KK package built';
          if (json.offer_wired === false) msg += ' (note: plan-button offer links not auto-wired — verify)';
          if (json.warnings && json.warnings.length) msg += ' · ' + json.warnings.length + ' warning(s)';
          const qt = qcText(json);
          if (qt) msg += ' · ' + qt;
          showToast(msg);
          // On a QC failure, block the reload with the findings so they aren't lost.
          if (json.qc && json.qc.status === 'fail') {
            alert('KK package built, but QC found issues:\n\n• '
              + (json.qc.errors || []).concat(json.qc.warnings || []).join('\n• '));
          }
          setTimeout(() => window.location.reload(), 1100);
        } else {
          showToast('KK Format failed: ' + (json.error || ''));
          if (json.violations && json.violations.length) {
            alert('KK build blocked — law violations:\n\n• ' + json.violations.join('\n• ')
              + '\n\nNo package was produced. Fix the violations (Flow Plans / CTA config) and retry.');
          }
          btn.classList.remove('is-busy');
          btn.textContent = original;
        }
      } catch (err) {
        showToast('KK Format failed');
        btn.classList.remove('is-busy');
        btn.textContent = original;
      }
    });
  });

  /* ===================================================================== */
  /* Brand-paired "Sales Flow" cards: preview/configure/download both brands */
  /* ===================================================================== */

  // Variant names for a flow card, in a stable [aipu, omni] order.
  function flowVariants(card) {
    let map = {};
    try { map = JSON.parse(card.dataset.variants || '{}'); } catch (_) { map = {}; }
    const out = [];
    ['aipu', 'omni'].forEach(b => { if (map[b]) out.push(map[b]); });
    return out;
  }

  // primaryFlowName -> [all brand variant names] so a config modal opened on the
  // primary brand can replay its save to every brand (configs are brand-shared).
  const FLOW_SIBLINGS = {};
  document.querySelectorAll('.flow-card').forEach(card => {
    const vs = flowVariants(card);
    vs.forEach(n => { FLOW_SIBLINGS[n] = vs; });
  });
  function flowSiblings(name) { return FLOW_SIBLINGS[name] || [name]; }

  // POST the same config payload to every brand variant (flow key swapped).
  // Returns [{name, json}]; callers treat success as "all ok".
  async function saveConfigAllBrands(endpoint, payloadBase, primaryName) {
    const variants = flowSiblings(primaryName);
    const results = [];
    for (const name of variants) {
      const body = Object.assign({}, payloadBase, { flow: name });
      try {
        const res = await fetch(endpoint, {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body)
        });
        results.push({ name, json: await res.json() });
      } catch (err) {
        results.push({ name, json: { ok: false, error: String(err) } });
      }
    }
    return results;
  }
  function firstConfigFailure(results) {
    return results.find(r => !r.json || !r.json.ok) || null;
  }
  window.saveConfigAllBrands = saveConfigAllBrands;
  window.firstConfigFailure = firstConfigFailure;

  // ---- brand preview switch -------------------------------------------------
  function switchFlowBrand(card, brand) {
    const sales = card.dataset['sales' + (brand === 'aipu' ? 'Aipu' : 'Omni')];
    const checkout = card.dataset['checkout' + (brand === 'aipu' ? 'Aipu' : 'Omni')] || '';
    const name = card.dataset['name' + (brand === 'aipu' ? 'Aipu' : 'Omni')];
    if (!sales) return;
    card.dataset.brand = brand;
    if (name) card.dataset.name = name;          // AI-edit targets the shown brand
    const frame = card.querySelector('.flow-preview-frame');
    if (frame) frame.src = sales + (sales.indexOf('?') >= 0 ? '&' : '?') + '_ts=' + Date.now();
    card.querySelectorAll('.flow-open-sales').forEach(a => { a.href = sales; });
    card.querySelectorAll('.flow-open-checkout').forEach(a => { if (checkout) a.href = checkout; });
    card.querySelectorAll('.brand-switch-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.brandSwitch === brand);
    });
  }
  document.querySelectorAll('.flow-card .brand-switch-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const card = btn.closest('.flow-card');
      if (card) switchFlowBrand(card, btn.dataset.brandSwitch);
    });
  });

  // ---- mobile preview (selected brand) -------------------------------------
  document.querySelectorAll('.flow-mobile-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault(); e.stopPropagation();
      const card = btn.closest('.flow-card');
      if (!card) return;
      closeAllDropdowns(null);
      const brand = card.dataset.brand || 'omni';
      const sales = card.dataset['sales' + (brand === 'aipu' ? 'Aipu' : 'Omni')];
      const checkout = card.dataset['checkout' + (brand === 'aipu' ? 'Aipu' : 'Omni')] || '';
      openMobilePreview({ url: sales, checkout, label: (card.dataset.base || '') + ' · ' + brand.toUpperCase() });
    });
  });

  // ---- dropdown menus ------------------------------------------------------
  // Menus are moved to document.body so card overflow / transform never clips
  // or mispositions them. Only close when clicking truly outside.
  const dropdownMenus = new Map(); // dropdown element -> menu element

  document.querySelectorAll('.dropdown').forEach(dd => {
    const menu = dd.querySelector('.dropdown-menu');
    const toggle = dd.querySelector('.dropdown-toggle');
    if (!menu || !toggle) return;
    dropdownMenus.set(dd, menu);
    menu.dataset.dropdownOwner = 'dd-' + Math.random().toString(36).slice(2, 9);
    dd.dataset.menuId = menu.dataset.dropdownOwner;
    document.body.appendChild(menu);
  });

  function getDropdownMenu(dd) {
    return dropdownMenus.get(dd) || null;
  }

  function positionDropdown(dd) {
    const toggle = dd.querySelector('.dropdown-toggle');
    const menu = getDropdownMenu(dd);
    if (!toggle || !menu) return;
    const r = toggle.getBoundingClientRect();
    menu.style.visibility = 'hidden';
    menu.style.display = 'flex';
    const mw = menu.offsetWidth;
    const mh = menu.offsetHeight;
    menu.style.visibility = '';
    let left = Math.max(8, Math.min(r.left, window.innerWidth - mw - 8));
    const spaceBelow = window.innerHeight - r.bottom;
    let top = (spaceBelow < mh + 12 && r.top > mh + 12)
      ? r.top - mh - 6
      : r.bottom + 6;
    top = Math.max(8, Math.min(top, window.innerHeight - mh - 8));
    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
  }

  function setDropdownOpen(dd, open) {
    const menu = getDropdownMenu(dd);
    const toggle = dd.querySelector('.dropdown-toggle');
    dd.classList.toggle('open', open);
    if (menu) menu.classList.toggle('is-open', open);
    if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      document.body.classList.add('dropdown-open');
      positionDropdown(dd);
    } else if (!document.querySelector('.dropdown.open')) {
      document.body.classList.remove('dropdown-open');
    }
  }

  function closeAllDropdowns(except) {
    document.querySelectorAll('.dropdown.open').forEach(d => {
      if (d !== except) setDropdownOpen(d, false);
    });
    if (!except && !document.querySelector('.dropdown.open')) {
      document.body.classList.remove('dropdown-open');
    }
  }

  function isDropdownClickTarget(target) {
    if (!target || !target.closest) return false;
    if (target.closest('.dropdown-toggle')) return true;
    if (target.closest('.dropdown-menu')) return true;
    if (target.closest('.dropdown')) return true;
    return false;
  }

  document.querySelectorAll('.dropdown .dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', e => {
      e.preventDefault();
      e.stopPropagation();
      const dd = toggle.closest('.dropdown');
      if (!dd || toggle.disabled) return;
      const willOpen = !dd.classList.contains('open');
      closeAllDropdowns(willOpen ? dd : null);
      setDropdownOpen(dd, willOpen);
    });
  });

  // Close only when clicking outside — not when clicking menu items or toggles.
  document.addEventListener('mousedown', e => {
    if (isDropdownClickTarget(e.target)) return;
    closeAllDropdowns(null);
  }, true);

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeAllDropdowns(null);
  });

  window.addEventListener('scroll', () => {
    document.querySelectorAll('.dropdown.open').forEach(positionDropdown);
  }, true);

  window.addEventListener('resize', () => closeAllDropdowns(null));

  // Any click inside a portaled menu should not bubble to close handlers.
  document.querySelectorAll('.dropdown-menu').forEach(menu => {
    menu.addEventListener('click', e => e.stopPropagation());
    menu.addEventListener('mousedown', e => e.stopPropagation());
  });

  // Close after following a link inside a menu.
  document.querySelectorAll('.dropdown-menu a.dropdown-item').forEach(a => {
    a.addEventListener('click', () => closeAllDropdowns(null));
  });

  // ---- KK Format BOTH brands ----------------------------------------------
  async function kkFormatVariants(variants) {
    const results = [];
    for (const name of variants) {
      try {
        const res = await fetch('kk-format.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ flow: name })
        });
        results.push({ name, json: await res.json() });
      } catch (err) {
        results.push({ name, json: { ok: false, error: String(err) } });
      }
    }
    return results;
  }
  document.querySelectorAll('.kk-format-group').forEach(btn => {
    btn.addEventListener('click', async e => {
      e.stopPropagation();
      const card = btn.closest('.flow-card');
      const variants = flowVariants(card);
      if (!variants.length) return;
      if (btn.dataset.reformat === '1'
        && !confirm('Re-run KK Format for both brands of "' + btn.dataset.base + '"?')) return;
      closeAllDropdowns(null);
      if (typeof clearKkError === 'function') clearKkError(card);
      const original = btn.textContent;
      btn.classList.add('is-busy'); btn.textContent = 'Formatting…';
      showToast('Building KK packages…');
      const results = await kkFormatVariants(variants);
      const failed = results.filter(r => !r.json || !r.json.ok || (r.json.qc && r.json.qc.status === 'fail'));
      if (failed.length) {
        const failures = failed.map(r => ({ name: r.name, reasons: kkFailureReasons(r.json) }));
        showToast('KK Format failed for ' + failed.length + ' brand(s)');
        if (typeof showKkError === 'function') showKkError(card, failures);
        btn.classList.remove('is-busy'); btn.textContent = original;
        return;
      }
      showToast('KK packages built for both brands');
      setTimeout(() => window.location.reload(), 1100);
    });
  });

  // ---- Download KK (auto-formats any missing package first) ----------------
  const KK_BRAND_KEY = { aipu: 'kkAipu', omni: 'kkOmni' };

  // Pull the human-readable build failure reasons out of a kk-format response.
  function kkFailureReasons(json) {
    if (!json) return ['no response from server'];
    const out = [];
    if (json.error) out.push(json.error);
    const v = json.violations || (json.qc && json.qc.errors) || [];
    (v || []).forEach(x => out.push(x));
    if (!out.length) out.push('build blocked');
    return out;
  }

  // failures: [{ name, reasons:[...] }]  (a legacy flat string[] is tolerated)
  function showKkError(card, failures) {
    const box = card.querySelector('.kk-dl-error');
    if (!box) return;
    const groups = (failures && failures.length && typeof failures[0] === 'string')
      ? [{ name: '', reasons: failures }]
      : (failures || []);
    const lines = [];
    groups.forEach(g => (g.reasons || []).forEach(r => lines.push((g.name ? g.name + ': ' : '') + r)));
    const names = groups.map(g => g.name).filter(Boolean);
    box.innerHTML =
      '<strong>KK build blocked — package not produced.</strong><br>'
      + lines.slice(0, 6).map(esc).join('<br>')
      + '<div class="kk-dl-error-actions">'
      +   (names.length ? '<button type="button" class="btn btn-ai kk-fix-ai">✦ Fix with AI</button>' : '')
      +   '<span class="kk-dl-error-fix">or fix via <strong>Configure → Plans / CTA routing</strong>, then retry.</span>'
      + '</div>';
    box.classList.add('show');
    const fixBtn = box.querySelector('.kk-fix-ai');
    if (fixBtn) fixBtn.addEventListener('click', () => startKkAiFix(card, groups, fixBtn));
  }
  function clearKkError(card) {
    const box = card.querySelector('.kk-dl-error');
    if (box) { box.classList.remove('show'); box.innerHTML = ''; }
  }

  // Map a failing flow variant name back to its brand using the card's variant
  // map (when available), falling back to the name suffix.
  function brandForName(card, name) {
    let map = {};
    try { map = JSON.parse((card && card.dataset.variants) || '{}'); } catch (_) {}
    for (const b of Object.keys(map)) { if (map[b] === name) return b; }
    return /__aipu$/.test(name) ? 'aipu' : 'omni';
  }

  // True when a config-save response was blocked by KK law violations (vs. a
  // plain save/server error, which AI source edits can't fix).
  function kkHasViolations(json) {
    return !!(json && ((json.violations && json.violations.length)
      || (json.qc && json.qc.errors && json.qc.errors.length)));
  }

  // The instruction handed to the AI Editor when fixing a blocked KK build.
  function buildKkFixInstruction(reasons) {
    const list = (reasons || []).map(r => '- ' + r).join('\n');
    return [
      'The KowboyKit (KK) build for this flow is BLOCKED by these law violation(s):',
      '',
      list,
      '',
      'Fix the flow SOURCE so re-running KK Format passes. Edit the .html source',
      'files (e.g. checkout.html / index.html) — NOT the generated kk/ folder, which',
      'is overwritten on every build.',
      '',
      'KK wiring rules you must satisfy:',
      '- No hardcoded /signup?offer=... anywhere — not in an <a href>, and not in any',
      '  <script> (e.g. helpers such as "/signup?offer=" + token). Plan/CTA buttons',
      '  must resolve through the KK offer wiring. In JavaScript, build the URL from',
      '  window.__KK_OFFER_LINKS[token] with a fallback to window.__KK_REGISTER_CHECKOUT',
      '  (then "#") — never construct a /signup URL in script.',
      '- Every CTA / plan button must point at a real destination, never an empty',
      '  href or a bare "#".',
      '- Keep the money.php / safe.php header and the _kk-config.php /',
      '  _checkout-offers.php includes intact. Do not change offer tokens, plan',
      '  prices, or tracking.',
      '',
      'Make the smallest correct edits, then call finish.',
    ].join('\n');
  }

  // Core: drive the AI Editor to repair each blocked brand variant, then re-run
  // KK Format on the fixed source. Iterates variants sequentially.
  //   opts: { fixBtn, brandFor(name), onStillFailing(failures), onAllFixed() }
  async function runKkAiFix(groups, opts) {
    opts = opts || {};
    if (!window.aiEditor || !window.aiEditor.run) { showToast('AI Editor unavailable'); return; }
    const targets = (groups || []).filter(g => g.name);
    if (!targets.length) { showToast('Nothing to fix'); return; }
    if (!opts.skipConfirm && !confirm('Let AI attempt to fix the KK build for ' + targets.length + ' brand variant(s)?\n\n'
      + 'It will edit the flow source (the .html files) and then re-run KK Format. '
      + 'You can Revert from the AI panel afterwards.')) return;
    const fixBtn = opts.fixBtn;
    const brandFor = opts.brandFor || (name => /__aipu$/.test(name) ? 'aipu' : 'omni');
    if (fixBtn) fixBtn.classList.add('is-busy');
    const stillFailing = [];
    for (const g of targets) {
      const scope = {
        kind: 'flow', name: g.name, collection: '',
        brand: brandFor(g.name),
        webRoot: '/flows/' + g.name,
        label: 'Fix KK · ' + g.name,
      };
      showToast('AI is fixing ' + g.name + '…');
      let result = null;
      try {
        result = await window.aiEditor.run(scope, buildKkFixInstruction(g.reasons), { autoSend: true });
      } catch (e) { result = { ok: false, error: String(e) }; }
      if (!result || !result.ok || result.status === 'failed') {
        const why = (result && result.error) ? result.error : 'AI could not complete the fix';
        stillFailing.push({ name: g.name, reasons: [why] });
        continue;
      }
      // Re-run KK Format on the now-fixed source for this variant.
      showToast('Re-building KK for ' + g.name + '…');
      const res = await kkFormatVariants([g.name]);
      const ok = res.every(r => r.json && r.json.ok && !(r.json.qc && r.json.qc.status === 'fail'));
      if (!ok) {
        res.forEach(r => stillFailing.push({ name: r.name, reasons: kkFailureReasons(r.json) }));
      }
    }
    if (fixBtn) fixBtn.classList.remove('is-busy');
    if (!stillFailing.length) {
      showToast('KK build fixed — reloading…');
      if (opts.onAllFixed) opts.onAllFixed();
      else setTimeout(() => window.location.reload(), 1300);
    } else {
      if (opts.onStillFailing) opts.onStillFailing(stillFailing);
      showToast('Still failing for ' + stillFailing.length + ' brand(s) — see the AI panel');
    }
  }

  // Flow-card error box wrapper.
  function startKkAiFix(card, groups, fixBtn) {
    return runKkAiFix(groups, {
      fixBtn,
      brandFor: name => brandForName(card, name),
      onStillFailing: sf => showKkError(card, sf),
    });
  }

  // Render KK violations + a "Fix with AI" button into a modal status element.
  //   failures: [{name, reasons:[...]}];  onFixed: called after a clean rebuild.
  function showKkModalError(statusEl, prefix, failures, onFixed) {
    if (!statusEl) return;
    const groups = failures || [];
    const lines = [];
    groups.forEach(g => (g.reasons || []).forEach(r => lines.push((g.name ? g.name + ': ' : '') + r)));
    const names = groups.map(g => g.name).filter(Boolean);
    statusEl.className = 'modal-status error';
    statusEl.innerHTML =
      '<strong>' + esc(prefix || 'KK build blocked — package not produced.') + '</strong><br>'
      + lines.slice(0, 6).map(esc).join('<br>')
      + (names.length ? '<div class="kk-dl-error-actions"><button type="button" class="btn btn-ai kk-fix-ai">✦ Fix with AI</button></div>' : '');
    const fixBtn = statusEl.querySelector('.kk-fix-ai');
    if (fixBtn) {
      fixBtn.addEventListener('click', () => runKkAiFix(groups, {
        fixBtn,
        onStillFailing: sf => showKkModalError(statusEl, prefix, sf, onFixed),
        onAllFixed: onFixed || (() => setTimeout(() => window.location.reload(), 1300)),
      }));
    }
  }

  document.querySelectorAll('.flow-kk-download').forEach(btn => {
    btn.addEventListener('click', async e => {
      e.stopPropagation();
      const card = btn.closest('.flow-card');
      if (!card) return;
      clearKkError(card);
      btn.classList.remove('kk-error', 'kk-ok');
      const choice = btn.dataset.kkBrand; // aipu | omni | all
      const brands = choice === 'all' ? ['aipu', 'omni'] : [choice];
      let names = {};
      try { names = JSON.parse(card.dataset.variants || '{}'); } catch (_) {}
      const missing = brands.filter(b => card.dataset[KK_BRAND_KEY[b]] !== '1' && names[b]);
      if (missing.length) {
        const original = btn.innerHTML;
        btn.classList.add('is-busy');
        btn.innerHTML = 'Building ' + missing.map(b => b.toUpperCase()).join(' + ') + '…';
        showToast('No KK package yet — building it now…');
        const results = await kkFormatVariants(missing.map(b => names[b]));
        const failed = results.filter(r => !r.json || !r.json.ok || (r.json.qc && r.json.qc.status === 'fail'));
        btn.classList.remove('is-busy');
        btn.innerHTML = original;
        if (failed.length) {
          const failures = failed.map(r => ({ name: r.name, reasons: kkFailureReasons(r.json) }));
          // Turn the button red and show an inline, persistent error.
          btn.classList.add('kk-error');
          showKkError(card, failures);
          showToast('KK build failed — see the red note on the card');
          return;
        }
        missing.forEach(b => { card.dataset[KK_BRAND_KEY[b]] = '1'; });
        btn.classList.add('kk-ok');
        showToast('KK package ready — downloading…');
      } else {
        btn.classList.add('kk-ok');
      }
      closeAllDropdowns(null);
      window.location.href = 'kk-download.php?base=' + btn.dataset.base + '&brand=' + choice;
      // Drop the green confirmation after a moment so the button returns to gold.
      setTimeout(() => btn.classList.remove('kk-ok'), 2500);
    });
  });

  // ---- Archive BOTH brand folders (trash icon + Configure menu item) -------
  function archiveFlowCard(card) {
    const variants = flowVariants(card);
    if (!variants.length) return;
    const items = variants.map(n => ({ type: 'flow', name: n }));
    openConfirm(items, () => performDelete(items));
  }
  document.querySelectorAll('.flow-delete-group').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      closeAllDropdowns(null);
      archiveFlowCard(btn.closest('.flow-card'));
    });
  });
  document.querySelectorAll('.flow-trash-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      e.stopPropagation();
      archiveFlowCard(btn.closest('.flow-card'));
    });
  });

  /* ------------------------------------------- selection + bulk action bar */
  const selected = new Set();
  const bulkBar = document.getElementById('bulk-bar');
  const bulkCount = document.getElementById('bulk-count');

  function cardInfo(card) {
    return {
      kind: card.dataset.kind,
      name: card.dataset.name,
      collection: card.dataset.collection || '',
      brand: card.dataset.brand,
      dst: card.dataset.dst,
    };
  }
  function deleteItemFromCard(info) {
    return info.kind === 'flow'
      ? { type: 'flow', name: info.name }
      : { type: 'item', collection: info.collection, name: info.name };
  }
  function labelFor(it) {
    const tag = it.type === 'flow' ? 'flow' : (it.collection || 'item');
    return tag + ' / ' + it.name;
  }
  function updateBulkBar() {
    bulkCount.textContent = selected.size;
    bulkBar.classList.toggle('show', selected.size > 0);
  }

  document.querySelectorAll('.select-box').forEach(box => {
    box.addEventListener('change', () => {
      const card = box.closest('.card');
      if (box.checked) { selected.add(card); card.classList.add('selected'); }
      else { selected.delete(card); card.classList.remove('selected'); }
      updateBulkBar();
    });
  });
  document.getElementById('bulk-clear').addEventListener('click', () => {
    selected.forEach(card => {
      card.classList.remove('selected');
      const b = card.querySelector('.select-box');
      if (b) b.checked = false;
    });
    selected.clear();
    updateBulkBar();
  });

  /* ------------------------------------------------------- confirm + delete */
  const confirmModal = document.getElementById('confirm-modal');
  const confirmMsg = document.getElementById('confirm-msg');
  const confirmList = document.getElementById('confirm-list');
  const confirmStatus = document.getElementById('confirm-status');
  const confirmOk = document.getElementById('confirm-ok');
  let confirmAction = null;

  function openConfirm(items, onConfirm) {
    confirmMsg.textContent = items.length === 1
      ? 'Archive this item? It moves to the Archive — you can restore it anytime.'
      : 'Archive these ' + items.length + ' items? They move to the Archive — you can restore them anytime.';
    confirmList.innerHTML = items.map(it => '<li>' + esc(labelFor(it)) + '</li>').join('');
    confirmStatus.textContent = ''; confirmStatus.className = 'modal-status';
    confirmOk.disabled = false;
    confirmAction = onConfirm;
    confirmModal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeConfirm() {
    confirmModal.classList.remove('open');
    document.body.style.overflow = '';
    confirmAction = null;
  }
  document.querySelectorAll('[data-confirm-cancel]').forEach(b => b.addEventListener('click', closeConfirm));
  confirmModal.addEventListener('click', e => { if (e.target === confirmModal) closeConfirm(); });
  confirmOk.addEventListener('click', async () => {
    if (!confirmAction) return;
    confirmOk.disabled = true;
    confirmStatus.textContent = 'Archiving\u2026'; confirmStatus.className = 'modal-status';
    await confirmAction();
  });

  async function performDelete(items) {
    try {
      const res = await fetch('delete-items.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ items })
      });
      const json = await res.json();
      if (json.ok) {
        showToast('Archived ' + (json.deleted ? json.deleted.length : items.length) + ' \u2014 reloading\u2026');
        setTimeout(() => window.location.reload(), 700);
      } else {
        const errs = (json.errors || []).map(e => e.name + ': ' + e.error).join(', ');
        confirmStatus.textContent = 'Failed: ' + (errs || json.error || 'unknown');
        confirmStatus.className = 'modal-status error';
        confirmOk.disabled = false;
        if (json.deleted && json.deleted.length) setTimeout(() => window.location.reload(), 1500);
      }
    } catch (err) {
      confirmStatus.textContent = 'Request failed: ' + err;
      confirmStatus.className = 'modal-status error';
      confirmOk.disabled = false;
    }
  }

  // A flow card represents BOTH brand folders — expand it to both delete items.
  function deleteItemsForCard(card) {
    if (card.dataset.kind === 'flow') {
      const vs = flowVariants(card);
      if (vs.length) return vs.map(n => ({ type: 'flow', name: n }));
    }
    return [deleteItemFromCard(cardInfo(card))];
  }
  document.querySelectorAll('.item-delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const items = deleteItemsForCard(btn.closest('.card'));
      openConfirm(items, () => performDelete(items));
    });
  });
  document.getElementById('bulk-delete').addEventListener('click', () => {
    if (!selected.size) return;
    const items = Array.from(selected).flatMap(c => deleteItemsForCard(c));
    openConfirm(items, () => performDelete(items));
  });

  /* --------------------------------------------------- archive restore/purge */
  async function archiveAction(action, id) {
    const res = await fetch('archive-action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action, id })
    });
    return res.json();
  }

  document.querySelectorAll('.archive-restore-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const original = btn.textContent;
      btn.disabled = true; btn.textContent = 'Restoring…';
      try {
        const json = await archiveAction('restore', id);
        if (json.ok) {
          showToast('Restored — reloading…');
          setTimeout(() => window.location.reload(), 700);
        } else {
          showToast('Restore failed: ' + (json.error || 'unknown'));
          btn.disabled = false; btn.textContent = original;
        }
      } catch (err) {
        showToast('Restore failed'); btn.disabled = false; btn.textContent = original;
      }
    });
  });

  document.querySelectorAll('.archive-purge-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const name = btn.dataset.name || id;
      confirmMsg.textContent = 'Permanently delete this archived item? This cannot be undone.';
      confirmList.innerHTML = '<li>' + esc(name) + '</li>';
      confirmStatus.textContent = ''; confirmStatus.className = 'modal-status';
      confirmOk.disabled = false;
      confirmAction = async () => {
        confirmStatus.textContent = 'Deleting…'; confirmStatus.className = 'modal-status';
        try {
          const json = await archiveAction('purge', id);
          if (json.ok) {
            showToast('Deleted permanently — reloading…');
            setTimeout(() => window.location.reload(), 700);
          } else {
            confirmStatus.textContent = 'Failed: ' + (json.error || 'unknown');
            confirmStatus.className = 'modal-status error';
            confirmOk.disabled = false;
          }
        } catch (err) {
          confirmStatus.textContent = 'Request failed: ' + err;
          confirmStatus.className = 'modal-status error';
          confirmOk.disabled = false;
        }
      };
      confirmModal.classList.add('open');
      document.body.style.overflow = 'hidden';
    });
  });

  /* ----------------------------------------------- duplicate to other brand */
  document.querySelectorAll('.item-dup-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const info = cardInfo(btn.closest('.card'));
      const original = btn.textContent;
      btn.classList.add('is-busy');
      btn.textContent = 'Duplicating\u2026';
      try {
        const res = await fetch('duplicate-item.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ kind: info.kind, collection: info.collection, name: info.name, dst_brand: info.dst })
        });
        const json = await res.json();
        if (json.ok) {
          let msg = 'Duplicated to ' + (json.brand === 'aipu' ? 'AIPU' : 'OmniRogue');
          if (json.warnings && json.warnings.length) msg += ' \u00b7 ' + json.warnings.length + ' warning(s)';
          const qt = qcText(json);
          if (qt) msg += ' \u00b7 ' + qt;
          if (json.qc && json.qc.status === 'fail') {
            alert('Duplicated, but QC found issues:\n\n\u2022 '
              + (json.qc.errors || []).concat(json.qc.warnings || []).join('\n\u2022 '));
          }
          showToast(msg + ' \u2014 reloading\u2026');
          setTimeout(() => window.location.reload(), 1200);
        } else {
          showToast('Duplicate failed: ' + (json.error || ''));
          btn.classList.remove('is-busy'); btn.textContent = original;
        }
      } catch (err) {
        showToast('Duplicate failed'); btn.classList.remove('is-busy'); btn.textContent = original;
      }
    });
  });

  /* ------------------------------------------------------------ import flow */
  const importModal = document.getElementById('import-modal');
  const impKind = document.getElementById('imp-kind');
  const impTarget = document.getElementById('imp-target');
  const impUrl = document.getElementById('imp-url');
  const impSourceUrl = document.getElementById('imp-source-url');
  const impName = document.getElementById('imp-name');
  const impFile = document.getElementById('imp-file');
  const impFileDrop = document.getElementById('imp-file-drop');
  const impFileChosen = document.getElementById('imp-file-chosen');
  const impStatus = document.getElementById('imp-status');
  const impGo = document.getElementById('imp-go');
  const impResult = document.getElementById('imp-result');
  const impResultLinks = document.getElementById('imp-result-links');
  const impTabs = document.querySelectorAll('.import-tab');
  const impPanes = document.querySelectorAll('.imp-pane');
  let impMode = 'url'; // 'url' | 'file'
  let impSelectedFile = null;

  const IMPORT_TARGETS = {
    checkout: [['checkout-pages', 'Checkouts']],
    lander: [['sales-pages', 'Sales Pages']]
  };
  function fillImportTargets() {
    const opts = IMPORT_TARGETS[impKind.value] || [];
    impTarget.innerHTML = opts.map(o => '<option value="' + o[0] + '">' + o[1] + '</option>').join('');
  }
  impKind.addEventListener('change', fillImportTargets);
  fillImportTargets();

  function setImportMode(mode) {
    impMode = (mode === 'file') ? 'file' : 'url';
    impTabs.forEach(t => {
      const active = t.dataset.impTab === impMode;
      t.classList.toggle('active', active);
      t.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    impPanes.forEach(p => {
      p.hidden = (p.dataset.impPane !== impMode);
    });
    impStatus.textContent = ''; impStatus.className = 'modal-status';
    setTimeout(() => {
      if (impMode === 'url') impUrl.focus();
      else impFile.focus();
    }, 30);
  }
  impTabs.forEach(t => t.addEventListener('click', () => setImportMode(t.dataset.impTab)));

  function formatBytes(n) {
    if (n < 1024) return n + ' B';
    if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
    return (n / (1024 * 1024)).toFixed(2) + ' MB';
  }
  function setSelectedFile(file) {
    impSelectedFile = file || null;
    if (!file) {
      impFile.value = '';
      impFileChosen.hidden = true;
      impFileDrop.querySelector('.file-drop-prompt').hidden = false;
      return;
    }
    impFileDrop.querySelector('.file-drop-prompt').hidden = true;
    impFileChosen.hidden = false;
    impFileChosen.innerHTML = '';
    const name = document.createElement('span');
    name.textContent = file.name;
    const meta = document.createElement('span');
    meta.className = 'file-meta';
    meta.textContent = '(' + formatBytes(file.size) + ')';
    const clear = document.createElement('button');
    clear.type = 'button';
    clear.className = 'file-clear';
    clear.title = 'Clear file';
    clear.textContent = '\u00d7';
    clear.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); setSelectedFile(null); });
    impFileChosen.append(name, meta, clear);
  }

  impFile.addEventListener('change', () => {
    const f = impFile.files && impFile.files[0];
    if (f) setSelectedFile(f);
  });

  ['dragenter', 'dragover'].forEach(ev => {
    impFileDrop.addEventListener(ev, (e) => { e.preventDefault(); impFileDrop.classList.add('drag'); });
  });
  ['dragleave', 'drop'].forEach(ev => {
    impFileDrop.addEventListener(ev, (e) => { e.preventDefault(); impFileDrop.classList.remove('drag'); });
  });
  impFileDrop.addEventListener('drop', (e) => {
    const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
    if (!f) return;
    const nameLow = f.name.toLowerCase();
    if (!nameLow.endsWith('.html') && !nameLow.endsWith('.htm')) {
      impStatus.textContent = 'Only .html / .htm files are accepted';
      impStatus.className = 'modal-status error';
      return;
    }
    try {
      const dt = new DataTransfer();
      dt.items.add(f);
      impFile.files = dt.files;
    } catch (_) { /* DataTransfer not supported — file is still tracked in impSelectedFile */ }
    setSelectedFile(f);
  });

  function openImport() {
    impUrl.value = ''; impSourceUrl.value = ''; impName.value = '';
    setSelectedFile(null);
    setImportMode('url');
    impStatus.textContent = ''; impStatus.className = 'modal-status';
    impResult.classList.remove('show');
    impGo.disabled = false;
    importModal.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => impUrl.focus(), 50);
  }
  function closeImport() { importModal.classList.remove('open'); document.body.style.overflow = ''; }
  document.querySelectorAll('[data-open-import]').forEach(b => b.addEventListener('click', openImport));
  document.querySelectorAll('[data-import-cancel]').forEach(b => b.addEventListener('click', closeImport));
  importModal.addEventListener('click', e => { if (e.target === importModal) closeImport(); });

  impGo.addEventListener('click', async () => {
    let req;
    if (impMode === 'url') {
      const url = impUrl.value.trim();
      if (!/^https?:\/\//i.test(url)) {
        impStatus.textContent = 'Enter a valid http(s) URL'; impStatus.className = 'modal-status error'; return;
      }
      req = {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url, kind: impKind.value, collection: impTarget.value, name: impName.value.trim() })
      };
    } else {
      if (!impSelectedFile) {
        impStatus.textContent = 'Choose an .html file to upload'; impStatus.className = 'modal-status error'; return;
      }
      const sourceUrl = impSourceUrl.value.trim();
      if (sourceUrl && !/^https?:\/\//i.test(sourceUrl)) {
        impStatus.textContent = 'Source URL must start with http(s)://'; impStatus.className = 'modal-status error'; return;
      }
      const fd = new FormData();
      fd.append('file', impSelectedFile);
      fd.append('kind', impKind.value);
      fd.append('collection', impTarget.value);
      fd.append('name', impName.value.trim());
      if (sourceUrl) fd.append('source_url', sourceUrl);
      req = { method: 'POST', body: fd };
    }

    impGo.disabled = true;
    impStatus.textContent = (impMode === 'url')
      ? 'Importing\u2026 fetching page & assets'
      : 'Importing\u2026 processing uploaded file';
    impStatus.className = 'modal-status';
    impResult.classList.remove('show');
    try {
      const res = await fetch('import-page.php', req);
      const json = await res.json();
      if (json.ok) {
        const qt = qcText(json);
        impStatus.textContent = 'Imported \u2014 ' + (json.assets_localized || 0) + ' assets localized'
          + (json.warnings && json.warnings.length ? ' \u00b7 ' + json.warnings.length + ' warning(s)' : '')
          + (qt ? ' \u00b7 ' + qt : '');
        impStatus.className = 'modal-status ok';
        impResultLinks.innerHTML = '<a class="btn btn-primary" target="_blank" rel="noopener" href="'
          + json.web_path + '">Open imported page \u2197</a>' + qcDetailHtml(json);
        impResult.classList.add('show');
        const qstat = json.qc && json.qc.status;
        if (qstat === 'fail') {
          showToast('Imported \u2014 QC found issues');
        } else {
          showToast('Imported \u2014 reloading\u2026');
          setTimeout(() => window.location.reload(), qstat === 'warn' ? 3200 : 1800);
        }
      } else {
        impStatus.textContent = 'Error: ' + (json.error || 'import failed');
        impStatus.className = 'modal-status error';
        impGo.disabled = false;
      }
    } catch (err) {
      impStatus.textContent = 'Request failed: ' + err;
      impStatus.className = 'modal-status error';
      impGo.disabled = false;
    }
  });

  /* ------------------------------------------- per-flow CTA configuration */
  const ctaModal = document.getElementById('cta-modal');
  const ctaTbody = document.getElementById('cta-tbody');
  const ctaDefault = document.getElementById('cta-default');
  const ctaStatus = document.getElementById('cta-status');
  const ctaSave = document.getElementById('cta-save');
  const ctaApplyAll = document.getElementById('cta-apply-all');
  const ctaMeta = document.getElementById('cta-flow-meta');
  let ctaState = null;

  function tokenLabel(token, labels) {
    return (labels && labels[token]) ? (labels[token] + ' [' + token + ']') : token;
  }

  function buildTokenOptions(tokens, labels, selected) {
    return tokens.map(t =>
      '<option value="' + esc(t) + '"' + (t === selected ? ' selected' : '') + '>' +
      esc(tokenLabel(t, labels)) + '</option>'
    ).join('');
  }

  function openCtaModal(flow) {
    ctaState = null;
    ctaStatus.textContent = 'Loading…';
    ctaStatus.className = 'modal-status';
    ctaSave.disabled = true;
    ctaTbody.innerHTML = '<tr><td colspan="2" class="cta-empty">Loading…</td></tr>';
    ctaDefault.innerHTML = '';
    ctaMeta.textContent = 'flows/' + flow + '/';
    ctaModal.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch('flow-config.php?flow=' + encodeURIComponent(flow))
      .then(r => r.json())
      .then(json => {
        if (!json.ok) {
          ctaTbody.innerHTML = '<tr><td colspan="2" class="cta-empty">Error: ' + esc(json.error || 'unknown') + '</td></tr>';
          ctaStatus.textContent = 'Failed to load.'; ctaStatus.className = 'modal-status error';
          return;
        }
        ctaState = {
          flow: flow,
          tokens: json.tokens || [],
          labels: json.token_labels || {},
          pages: json.configurable_pages || [],
          applied: (json.config && json.config.applied) || {},
          perPage: (json.config && json.config.pages) || {},
          defaultToken: (json.config && json.config.default_register_token) || 'registercheckout',
          kkReady: !!json.kk_ready,
          flowType: json.flow_type || 'multi',
          ctaMap: json.cta_map || {}
        };
        ctaDefault.innerHTML = buildTokenOptions(ctaState.tokens, ctaState.labels, ctaState.defaultToken);

        // Single-page flows (sales-only / checkout-only): per-CTA token routing.
        if (ctaState.ctaMap && ctaState.ctaMap.single_page) {
          const detected = (ctaState.ctaMap.detected && ctaState.ctaMap.detected['index.php']) || [];
          const overrides = ctaState.ctaMap.overrides || {};
          if (!detected.length) {
            ctaTbody.innerHTML = '<tr><td colspan="2" class="cta-empty">No CTAs detected yet. Run <strong>KK Format</strong> once to scan the page, then come back.</td></tr>';
            ctaStatus.textContent = ''; ctaSave.disabled = true;
            return;
          }
          const extra = [
            { v: 'auto', l: 'Auto (classified)' },
            { v: 'dead', l: 'Dead link (#)' },
            { v: 'keep', l: 'Keep as-is' }
          ];
          ctaTbody.innerHTML = detected.map(cta => {
            const sel = overrides[cta.id] || 'auto';
            const opts = extra.map(o =>
              '<option value="' + esc(o.v) + '"' + (sel === o.v ? ' selected' : '') + '>' + esc(o.l) + '</option>'
            ).join('') + buildTokenOptions(ctaState.tokens, ctaState.labels, sel);
            return (
              '<tr data-cta-id="' + esc(cta.id) + '">' +
                '<td class="cta-page-cell">' + esc(cta.text || cta.id) +
                  '<div class="cta-applied">auto: ' + esc(cta.auto || '?') + ' · applied: ' + esc(cta.applied || '?') + '</div>' +
                '</td>' +
                '<td><select data-cta-select="' + esc(cta.id) + '">' + opts + '</select></td>' +
              '</tr>'
            );
          }).join('');
          ctaStatus.textContent = ctaState.kkReady ? '' : 'KK build is currently blocked — assign tokens to the flagged CTAs, then save.';
          ctaStatus.className = 'modal-status';
          ctaSave.disabled = false;
          return;
        }

        if (!ctaState.kkReady) {
          ctaTbody.innerHTML = '<tr><td colspan="2" class="cta-empty">This flow has no KK package yet. Run <strong>KK Format</strong> first, then come back.</td></tr>';
          ctaStatus.textContent = ''; ctaSave.disabled = true;
          return;
        }
        if (!ctaState.pages.length) {
          ctaTbody.innerHTML = '<tr><td colspan="2" class="cta-empty">No configurable pages in this flow.</td></tr>';
          ctaStatus.textContent = ''; ctaSave.disabled = true;
          return;
        }
        ctaTbody.innerHTML = ctaState.pages.map(page => {
          const override = ctaState.perPage[page];
          const token = (override && override.register_token) || ctaState.defaultToken;
          const appliedToken = ctaState.applied[page] || token;
          return (
            '<tr data-page="' + esc(page) + '">' +
              '<td class="cta-page-cell">' + esc(page) +
                (appliedToken && appliedToken !== token
                  ? '<div class="cta-applied">applied: ' + esc(appliedToken) + '</div>'
                  : '<div class="cta-applied">applied: ' + esc(appliedToken) + '</div>') +
              '</td>' +
              '<td><select data-page-select="' + esc(page) + '">' +
                buildTokenOptions(ctaState.tokens, ctaState.labels, token) +
              '</select></td>' +
            '</tr>'
          );
        }).join('');
        ctaStatus.textContent = '';
        ctaSave.disabled = false;
      })
      .catch(err => {
        ctaTbody.innerHTML = '<tr><td colspan="2" class="cta-empty">Request failed: ' + esc(String(err)) + '</td></tr>';
        ctaStatus.textContent = 'Request failed.'; ctaStatus.className = 'modal-status error';
      });
  }

  function closeCtaModal() {
    ctaModal.classList.remove('open');
    document.body.style.overflow = '';
    ctaState = null;
  }

  ctaApplyAll.addEventListener('click', () => {
    if (!ctaState) return;
    const dft = ctaDefault.value;
    ctaTbody.querySelectorAll('select[data-page-select]').forEach(sel => { sel.value = dft; });
  });

  ctaSave.addEventListener('click', async () => {
    if (!ctaState) return;
    const pages = {};
    ctaTbody.querySelectorAll('select[data-page-select]').forEach(sel => {
      pages[sel.dataset.pageSelect] = { register_token: sel.value };
    });
    const body = {
      flow: ctaState.flow,
      default_register_token: ctaDefault.value,
      pages: pages
    };
    // Single-page flows: per-CTA overrides ('auto' clears the override).
    const ctaSelects = ctaTbody.querySelectorAll('select[data-cta-select]');
    if (ctaSelects.length) {
      const overrides = {};
      ctaSelects.forEach(sel => { overrides[sel.dataset.ctaSelect] = sel.value; });
      body.cta_overrides = overrides;
    }
    ctaSave.disabled = true;
    ctaStatus.textContent = 'Saving & rebuilding KK for both brands…'; ctaStatus.className = 'modal-status';
    try {
      const results = await saveConfigAllBrands('flow-config.php', body, ctaState.flow);
      const failed = results.filter(r => !r.json || !r.json.ok);
      if (failed.length) {
        const kkBlocked = failed.filter(r => kkHasViolations(r.json));
        if (kkBlocked.length) {
          const failures = kkBlocked.map(r => ({ name: r.name, reasons: kkFailureReasons(r.json) }));
          const anySaved = kkBlocked.some(r => r.json && r.json.saved);
          showKkModalError(ctaStatus,
            anySaved ? 'Saved, but KK build blocked — package not produced.' : 'KK build blocked — package not produced.',
            failures, () => { closeCtaModal(); window.location.reload(); });
        } else {
          const bad = failed[0];
          ctaStatus.textContent = 'Error: ' + ((bad.json && bad.json.error) || 'save failed') + ' (' + bad.name + ')';
          ctaStatus.className = 'modal-status error';
        }
        ctaSave.disabled = false;
        return;
      }
      ctaStatus.textContent = 'Saved · KK rebuilt for both brands.'; ctaStatus.className = 'modal-status ok';
      showToast('CTA config saved (both brands)');
      setTimeout(() => { closeCtaModal(); window.location.reload(); }, 700);
    } catch (err) {
      ctaStatus.textContent = 'Request failed: ' + err;
      ctaStatus.className = 'modal-status error';
      ctaSave.disabled = false;
    }
  });

  document.querySelectorAll('.cta-config-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      closeAllDropdowns(null);
      openCtaModal(btn.dataset.flow);
    });
  });
  document.querySelectorAll('[data-cta-cancel]').forEach(b => b.addEventListener('click', closeCtaModal));
  ctaModal.addEventListener('click', e => { if (e.target === ctaModal) closeCtaModal(); });

  /* ---------------------------------------- per-flow plan configuration */
  const flowPlansModal = document.getElementById('flow-plans-modal');
  const flowPlansTbody = document.getElementById('flow-plans-tbody');
  const flowPlansStatus = document.getElementById('flow-plans-status');
  const flowPlansSave = document.getElementById('flow-plans-save');
  const flowPlansMeta = document.getElementById('flow-plans-meta');
  const flowPlansIndexToken = document.getElementById('flow-plans-index-token');
  const flowPlansReset = document.getElementById('flow-plans-reset');
  let flowPlansState = null;

  function flowPlanRow(plan, libById) {
    const lib = libById[plan.id] || {};
    const tok = plan.offer_tokens || lib.offer_tokens || {};
    const enabled = plan.enabled !== false;
    return (
      '<tr data-plan-id="' + esc(plan.id) + '" class="' + (enabled ? '' : 'disabled-row') + '">' +
        '<td><input type="checkbox" data-plan-enabled' + (enabled ? ' checked' : '') + ' aria-label="Enable ' + esc(plan.display_name || plan.id) + '"></td>' +
        '<td class="plan-name-cell">' + esc(plan.display_name || plan.id) +
          '<div class="plan-id-cell">' + esc(plan.id) + '</div></td>' +
        '<td><input type="text" data-field="points" value="' + esc(plan.points || lib.points || '') + '"></td>' +
        '<td><input type="text" data-field="monthly_price" value="' + esc(plan.monthly_price || lib.monthly_price || '') + '"></td>' +
        '<td><input type="text" data-field="yearly_price" value="' + esc(plan.yearly_price || lib.yearly_price || '') + '"></td>' +
        '<td><select data-field="monthly_token">' + planTokenOptionsForFlow(tok.monthly || (lib.offer_tokens || {}).monthly || '') + '</select></td>' +
        '<td><select data-field="yearly_token">' + planTokenOptionsForFlow(tok.yearly || (lib.offer_tokens || {}).yearly || '') + '</select></td>' +
      '</tr>'
    );
  }

  function planTokenOptionsForFlow(selected) {
    if (!flowPlansState) return '';
    const planToks = flowPlansState.tokens.filter(t => REGISTER_TOKENS.indexOf(t) < 0);
    const regToks = flowPlansState.tokens.filter(t => REGISTER_TOKENS.indexOf(t) >= 0);
    const opt = t => {
      const label = flowPlansState.labels[t] ? flowPlansState.labels[t] + ' [' + t + ']' : t;
      return '<option value="' + esc(t) + '"' + (t === selected ? ' selected' : '') + '>' + esc(label) + '</option>';
    };
    let html = '<option value="">— none —</option>';
    html += planToks.map(opt).join('');
    if (regToks.length) html += regToks.map(opt).join('');
    return html;
  }

  function openFlowPlansModal(flow) {
    flowPlansState = null;
    flowPlansStatus.textContent = 'Loading…';
    flowPlansStatus.className = 'modal-status';
    flowPlansSave.disabled = true;
    flowPlansTbody.innerHTML = '<tr><td colspan="7" class="cta-empty">Loading…</td></tr>';
    flowPlansIndexToken.innerHTML = '';
    flowPlansMeta.textContent = 'flows/' + flow + '/';
    flowPlansModal.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch('flow-plans.php?flow=' + encodeURIComponent(flow))
      .then(r => r.json())
      .then(json => {
        if (!json.ok) {
          flowPlansTbody.innerHTML = '<tr><td colspan="7" class="cta-empty">Error: ' + esc(json.error || 'unknown') + '</td></tr>';
          flowPlansStatus.textContent = 'Failed to load.'; flowPlansStatus.className = 'modal-status error';
          return;
        }
        const libById = {};
        (json.library_plans || []).forEach(p => { libById[p.id] = p; });
        flowPlansState = {
          flow: flow,
          tokens: json.tokens || [],
          labels: json.token_labels || {},
          libraryById: libById,
          libraryPlans: json.library_plans || [],
          detected: json.detected_plan_ids || [],
          indexToken: (json.config && json.config.index_register_token) || 'registercheckout',
          plans: (json.config && json.config.plans) || [],
          hasBundle: !!json.has_bundle
        };
        flowPlansIndexToken.innerHTML = buildTokenOptions(flowPlansState.tokens, flowPlansState.labels, flowPlansState.indexToken);

        if (!flowPlansState.hasBundle) {
          flowPlansTbody.innerHTML = '<tr><td colspan="7" class="cta-empty">This flow has no <code>plans-pick-your-plan/js/bundle.js</code>. Pick a checkout with a plan picker when creating the flow.</td></tr>';
          flowPlansStatus.textContent = ''; flowPlansSave.disabled = true;
          return;
        }

        const rows = flowPlansState.plans.length
          ? flowPlansState.plans
          : flowPlansState.libraryPlans.map(p => Object.assign({}, p, { enabled: flowPlansState.detected.indexOf(p.id) >= 0 }));

        if (!rows.length) {
          flowPlansTbody.innerHTML = '<tr><td colspan="7" class="cta-empty">No plans in library for this brand.</td></tr>';
          flowPlansSave.disabled = true;
          return;
        }

        flowPlansState.plans = rows;
        flowPlansTbody.innerHTML = rows.map(p => flowPlanRow(p, libById)).join('');
        flowPlansStatus.textContent = flowPlansState.detected.length
          ? ('Detected in bundle.js: ' + flowPlansState.detected.join(', '))
          : '';
        flowPlansSave.disabled = false;
      })
      .catch(err => {
        flowPlansTbody.innerHTML = '<tr><td colspan="7" class="cta-empty">Request failed: ' + esc(String(err)) + '</td></tr>';
        flowPlansStatus.textContent = 'Request failed.'; flowPlansStatus.className = 'modal-status error';
      });
  }

  function closeFlowPlansModal() {
    flowPlansModal.classList.remove('open');
    document.body.style.overflow = '';
    flowPlansState = null;
  }

  flowPlansTbody.addEventListener('change', e => {
    const row = e.target.closest('tr[data-plan-id]');
    if (!row || !e.target.matches('[data-plan-enabled]')) return;
    row.classList.toggle('disabled-row', !e.target.checked);
  });

  flowPlansReset.addEventListener('click', () => {
    if (!flowPlansState) return;
    flowPlansState.plans = flowPlansState.libraryPlans.map(p => {
      const enabled = flowPlansState.detected.indexOf(p.id) >= 0 || flowPlansState.plans.some(x => x.id === p.id && x.enabled !== false);
      return Object.assign({}, p, { enabled: enabled });
    });
    flowPlansTbody.innerHTML = flowPlansState.plans.map(p => flowPlanRow(p, flowPlansState.libraryById)).join('');
    flowPlansIndexToken.value = 'registercheckout';
    showToast('Reset to Plan Library defaults');
  });

  flowPlansSave.addEventListener('click', async () => {
    if (!flowPlansState) return;
    const plans = [];
    flowPlansTbody.querySelectorAll('tr[data-plan-id]').forEach(row => {
      const id = row.dataset.planId;
      const enabled = row.querySelector('[data-plan-enabled]').checked;
      const entry = { id: id, enabled: enabled };
      row.querySelectorAll('[data-field]').forEach(el => {
        if (el.tagName === 'SELECT') {
          const slot = el.dataset.field.replace('_token', '');
          if (el.value) {
            entry.offer_tokens = entry.offer_tokens || {};
            entry.offer_tokens[slot] = el.value;
          }
        } else if (el.value.trim() !== '') {
          entry[el.dataset.field] = el.value.trim();
        }
      });
      plans.push(entry);
    });
    flowPlansSave.disabled = true;
    flowPlansStatus.textContent = 'Saving, patching bundle.js & rebuilding KK for both brands…';
    flowPlansStatus.className = 'modal-status';
    try {
      const payload = {
        index_register_token: flowPlansIndexToken.value,
        plans: plans
      };
      const results = await saveConfigAllBrands('flow-plans.php', payload, flowPlansState.flow);
      const failed = results.filter(r => !r.json || !r.json.ok);
      if (failed.length) {
        const kkBlocked = failed.filter(r => kkHasViolations(r.json));
        if (kkBlocked.length) {
          const failures = kkBlocked.map(r => ({ name: r.name, reasons: kkFailureReasons(r.json) }));
          const anySaved = kkBlocked.some(r => r.json && r.json.saved);
          showKkModalError(flowPlansStatus,
            anySaved ? 'Saved, but KK build blocked — package not produced.' : 'KK build blocked — package not produced.',
            failures, () => { closeFlowPlansModal(); window.location.reload(); });
        } else {
          const bad = failed[0];
          flowPlansStatus.textContent = 'Error (' + bad.name + '): ' + ((bad.json && bad.json.error) || 'save failed');
          flowPlansStatus.className = 'modal-status error';
        }
        flowPlansSave.disabled = false;
        return;
      }
      flowPlansStatus.textContent = 'Saved · KK rebuilt for both brands';
      flowPlansStatus.className = 'modal-status ok';
      showToast('Plan config saved (both brands)');
      setTimeout(() => { closeFlowPlansModal(); window.location.reload(); }, 800);
    } catch (err) {
      flowPlansStatus.textContent = 'Request failed: ' + err;
      flowPlansStatus.className = 'modal-status error';
      flowPlansSave.disabled = false;
    }
  });

  document.querySelectorAll('.flow-plans-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      closeAllDropdowns(null);
      openFlowPlansModal(btn.dataset.flow);
    });
  });
  document.querySelectorAll('[data-flow-plans-cancel]').forEach(b => b.addEventListener('click', closeFlowPlansModal));
  flowPlansModal.addEventListener('click', e => { if (e.target === flowPlansModal) closeFlowPlansModal(); });

  /* ----------------------------------- per-flow widgets (popup + slider) */
  const flowWidgetsModal = document.getElementById('flow-widgets-modal');
  const flowWidgetsStatus = document.getElementById('flow-widgets-status');
  const flowWidgetsSave = document.getElementById('flow-widgets-save');
  const flowWidgetsMeta = document.getElementById('flow-widgets-meta');
  const fwSelects = {
    popup:  { widget: document.getElementById('fw-popup-widget'),  placement: document.getElementById('fw-popup-placement'),  warn: document.getElementById('fw-popup-warn') },
    slider: { widget: document.getElementById('fw-slider-widget'), placement: document.getElementById('fw-slider-placement'), warn: document.getElementById('fw-slider-warn') },
    social: { widget: document.getElementById('fw-social-widget'), placement: document.getElementById('fw-social-placement'), warn: document.getElementById('fw-social-warn') }
  };
  const FW_KINDS = ['popup', 'slider', 'social'];
  const fwStudioPlacement = document.getElementById('fw-studio-placement');
  let flowWidgetsState = null;

  function fwWidgetOptions(kind, selected) {
    const list = (flowWidgetsState.widgets || []).filter(w => w.type === kind);
    let html = '<option value="">— none —</option>';
    list.forEach(w => {
      const label = (w.name || w.id) + (w.active ? '' : ' (inactive)');
      html += '<option value="' + esc(w.id) + '"' + (w.id === selected ? ' selected' : '') + '>' + esc(label) + '</option>';
    });
    return html;
  }

  function fwUpdateWarn(kind) {
    const sel = fwSelects[kind];
    const id = sel.widget.value;
    const w = (flowWidgetsState.widgets || []).find(x => x.id === id);
    const msgs = [];
    if (w) {
      if (!w.active) msgs.push('This widget is inactive — activate it in the Widget Builder or it will not show.');
      const planIds = flowWidgetsState.flowPlanIds || [];
      [w.plan_id, w.secondary && w.secondary.plan_id].forEach(pid => {
        if (pid && planIds.length && planIds.indexOf(pid) < 0) {
          msgs.push('Plan "' + pid + '" is not enabled in this flow\u2019s plan config.');
        }
      });
      if (id && sel.placement.value === 'off') msgs.push('Pick where it appears — currently disabled.');
    }
    sel.warn.textContent = msgs.join(' ');
  }

  function openFlowWidgetsModal(flow) {
    flowWidgetsState = null;
    flowWidgetsStatus.textContent = 'Loading…';
    flowWidgetsStatus.className = 'modal-status';
    flowWidgetsSave.disabled = true;
    flowWidgetsMeta.textContent = 'flows/' + flow + '/';
    FW_KINDS.forEach(k => {
      fwSelects[k].widget.innerHTML = '<option value="">Loading…</option>';
      fwSelects[k].warn.textContent = '';
    });
    flowWidgetsModal.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch('flow-widgets.php?flow=' + encodeURIComponent(flow))
      .then(r => r.json())
      .then(json => {
        if (!json.ok) {
          flowWidgetsStatus.textContent = 'Error: ' + (json.error || 'unknown');
          flowWidgetsStatus.className = 'modal-status error';
          return;
        }
        flowWidgetsState = {
          flow: flow,
          widgets: json.library_widgets || [],
          flowPlanIds: json.flow_plan_ids || [],
          config: json.config || {}
        };
        FW_KINDS.forEach(k => {
          const cfg = flowWidgetsState.config[k] || {};
          fwSelects[k].widget.innerHTML = fwWidgetOptions(k, cfg.widget_id || '');
          fwSelects[k].placement.value = cfg.placement || 'off';
          fwUpdateWarn(k);
        });
        const spSaved = (flowWidgetsState.config.social_proof && flowWidgetsState.config.social_proof.placement) || 'on';
        fwStudioPlacement.value = spSaved === 'off' ? 'off' : 'on';
        flowWidgetsStatus.textContent = json.kk_ready ? 'KK package will be rebuilt on save.' : '';
        flowWidgetsSave.disabled = false;
      })
      .catch(err => {
        flowWidgetsStatus.textContent = 'Request failed: ' + err;
        flowWidgetsStatus.className = 'modal-status error';
      });
  }

  function closeFlowWidgetsModal() {
    flowWidgetsModal.classList.remove('open');
    document.body.style.overflow = '';
    flowWidgetsState = null;
  }

  FW_KINDS.forEach(k => {
    fwSelects[k].widget.addEventListener('change', () => {
      if (!flowWidgetsState) return;
      // picking a template with placement off defaults to a sensible placement
      if (fwSelects[k].widget.value && fwSelects[k].placement.value === 'off') {
        fwSelects[k].placement.value = 'both';
      }
      fwUpdateWarn(k);
    });
    fwSelects[k].placement.addEventListener('change', () => { if (flowWidgetsState) fwUpdateWarn(k); });
  });

  flowWidgetsSave.addEventListener('click', async () => {
    if (!flowWidgetsState) return;
    flowWidgetsSave.disabled = true;
    flowWidgetsStatus.textContent = 'Saving, injecting widgets & rebuilding both brands…';
    flowWidgetsStatus.className = 'modal-status';
    try {
      const payload = {
        popup:  { widget_id: fwSelects.popup.widget.value || null,  placement: fwSelects.popup.placement.value },
        slider: { widget_id: fwSelects.slider.widget.value || null, placement: fwSelects.slider.placement.value },
        social: { widget_id: fwSelects.social.widget.value || null, placement: fwSelects.social.placement.value },
        social_proof: { placement: fwStudioPlacement.value }
      };
      const results = await saveConfigAllBrands('flow-widgets.php', payload, flowWidgetsState.flow);
      const bad = firstConfigFailure(results);
      if (bad) {
        flowWidgetsStatus.textContent = 'Error (' + bad.name + '): ' + ((bad.json && bad.json.error) || 'save failed');
        flowWidgetsStatus.className = 'modal-status error';
        flowWidgetsSave.disabled = false;
        return;
      }
      flowWidgetsStatus.textContent = 'Saved · widgets injected · both brands rebuilt';
      flowWidgetsStatus.className = 'modal-status ok';
      showToast('Widgets saved (both brands)');
      setTimeout(() => { closeFlowWidgetsModal(); window.location.reload(); }, 1000);
    } catch (err) {
      flowWidgetsStatus.textContent = 'Request failed: ' + err;
      flowWidgetsStatus.className = 'modal-status error';
      flowWidgetsSave.disabled = false;
    }
  });

  document.querySelectorAll('.flow-widgets-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      closeAllDropdowns(null);
      openFlowWidgetsModal(btn.dataset.flow);
    });
  });
  document.querySelectorAll('[data-flow-widgets-cancel]').forEach(b => b.addEventListener('click', closeFlowWidgetsModal));
  flowWidgetsModal.addEventListener('click', e => { if (e.target === flowWidgetsModal) closeFlowWidgetsModal(); });

  /* --------------------------------------- Set Timer (centralized countdown) */
  const flowTimerModal = document.getElementById('flow-timer-modal');
  const flowTimerStatus = document.getElementById('flow-timer-status');
  const flowTimerSave = document.getElementById('flow-timer-save');
  const flowTimerMeta = document.getElementById('flow-timer-meta');
  const ftEnabled = document.getElementById('ft-enabled');
  const ftConfig = document.getElementById('ft-config');
  const ftInputs = {
    d: document.getElementById('ft-d'), h: document.getElementById('ft-h'),
    m: document.getElementById('ft-m'), s: document.getElementById('ft-s'),
    scope: document.getElementById('ft-scope'),
    sales: document.getElementById('ft-apply-sales'),
    checkout: document.getElementById('ft-apply-checkout'),
    widgets: document.getElementById('ft-apply-widgets')
  };
  let flowTimerFlow = null;

  function ftSyncEnabled() {
    ftConfig.classList.toggle('ft-disabled', !ftEnabled.checked);
  }
  ftEnabled.addEventListener('change', ftSyncEnabled);

  function openFlowTimerModal(flow) {
    flowTimerFlow = flow;
    flowTimerStatus.textContent = 'Loading…';
    flowTimerStatus.className = 'modal-status';
    flowTimerSave.disabled = true;
    flowTimerMeta.textContent = 'flows/' + flow + '/';
    flowTimerModal.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch('flow-timer.php?flow=' + encodeURIComponent(flow))
      .then(r => r.json())
      .then(json => {
        if (!json.ok) {
          flowTimerStatus.textContent = 'Error: ' + (json.error || 'unknown');
          flowTimerStatus.className = 'modal-status error';
          return;
        }
        const c = json.config || {};
        const dur = c.duration || {};
        ftEnabled.checked = !!c.enabled;
        ftInputs.d.value = dur.d != null ? dur.d : 0;
        ftInputs.h.value = dur.h != null ? dur.h : 0;
        ftInputs.m.value = dur.m != null ? dur.m : 0;
        ftInputs.s.value = dur.s != null ? dur.s : 0;
        ftInputs.scope.value = c.scope === 'persistent' ? 'persistent' : 'session';
        const apply = c.apply_to || {};
        ftInputs.sales.checked = apply.sales !== false;
        ftInputs.checkout.checked = apply.checkout !== false;
        ftInputs.widgets.checked = apply.widgets !== false;
        ftSyncEnabled();
        flowTimerStatus.textContent = json.kk_ready ? 'KK package will be rebuilt on save.' : '';
        flowTimerSave.disabled = false;
      })
      .catch(err => {
        flowTimerStatus.textContent = 'Request failed: ' + err;
        flowTimerStatus.className = 'modal-status error';
      });
  }

  function closeFlowTimerModal() {
    flowTimerModal.classList.remove('open');
    document.body.style.overflow = '';
    flowTimerFlow = null;
  }

  flowTimerSave.addEventListener('click', async () => {
    if (!flowTimerFlow) return;
    flowTimerSave.disabled = true;
    flowTimerStatus.textContent = 'Saving, applying timer & rebuilding both brands…';
    flowTimerStatus.className = 'modal-status';
    try {
      const payload = {
        timer_config: {
          enabled: ftEnabled.checked,
          duration: {
            d: parseInt(ftInputs.d.value, 10) || 0,
            h: parseInt(ftInputs.h.value, 10) || 0,
            m: parseInt(ftInputs.m.value, 10) || 0,
            s: parseInt(ftInputs.s.value, 10) || 0
          },
          scope: ftInputs.scope.value,
          apply_to: {
            sales: ftInputs.sales.checked,
            checkout: ftInputs.checkout.checked,
            widgets: ftInputs.widgets.checked
          }
        }
      };
      const results = await saveConfigAllBrands('flow-timer.php', payload, flowTimerFlow);
      const bad = firstConfigFailure(results);
      if (bad) {
        flowTimerStatus.textContent = 'Error (' + bad.name + '): ' + ((bad.json && bad.json.error) || 'save failed');
        flowTimerStatus.className = 'modal-status error';
        flowTimerSave.disabled = false;
        return;
      }
      flowTimerStatus.textContent = (ftEnabled.checked ? 'Saved · timer applied' : 'Saved · timer disabled')
        + ' · both brands rebuilt';
      flowTimerStatus.className = 'modal-status ok';
      showToast('Timer saved (both brands)');
      setTimeout(() => { closeFlowTimerModal(); window.location.reload(); }, 1000);
    } catch (err) {
      flowTimerStatus.textContent = 'Request failed: ' + err;
      flowTimerStatus.className = 'modal-status error';
      flowTimerSave.disabled = false;
    }
  });

  document.querySelectorAll('.flow-timer-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      closeAllDropdowns(null);
      openFlowTimerModal(btn.dataset.flow);
    });
  });
  document.querySelectorAll('[data-flow-timer-cancel]').forEach(b => b.addEventListener('click', closeFlowTimerModal));
  flowTimerModal.addEventListener('click', e => { if (e.target === flowTimerModal) closeFlowTimerModal(); });

  /* --------------------------------------- Optimize & Publish (assets + CDN) */
  const flowAssetsModal = document.getElementById('flow-assets-modal');
  const flowAssetsStatus = document.getElementById('flow-assets-status');
  const flowAssetsSave = document.getElementById('flow-assets-save');
  const flowAssetsMeta = document.getElementById('flow-assets-meta');
  const faOptimize = document.getElementById('fa-optimize');
  const faPublish = document.getElementById('fa-cdn-publish');
  const faRewrite = document.getElementById('fa-cdn-rewrite');
  const faCdnBlock = document.getElementById('fa-cdn-block');
  const faCdnNote = document.getElementById('fa-cdn-note');
  const faResults = document.getElementById('fa-results');
  let flowAssetsFlow = null;
  let faCdn = { configured: false, enabled: false, pull_url: '' };

  function faHumanBytes(n) {
    n = Number(n) || 0;
    if (n < 1024) return n + ' B';
    if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
    return (n / 1048576).toFixed(2) + ' MB';
  }

  function faSyncCdn() {
    const usable = faCdn.configured && faCdn.enabled;
    faCdnBlock.classList.toggle('fa-disabled', !usable);
    if (!usable) { faPublish.checked = false; faRewrite.checked = false; }
    // rewrite implies publish
    if (faRewrite.checked) faPublish.checked = true;
    if (faCdn.configured && !faCdn.enabled) {
      faCdnNote.className = 'fa-cdn-warn';
      faCdnNote.textContent = 'BunnyCDN is configured but CDN_ENABLED=0 in .env — set it to 1 to allow publishing.';
    } else if (!faCdn.configured) {
      faCdnNote.className = 'fa-cdn-warn';
      faCdnNote.textContent = 'BunnyCDN is not configured. Add BUNNY_STORAGE_ZONE / BUNNY_STORAGE_KEY / BUNNY_PULL_URL to .env to enable.';
    } else {
      faCdnNote.className = 'fa-cdn-note';
      faCdnNote.textContent = 'Pull zone: ' + (faCdn.pull_url || '(set BUNNY_PULL_URL)');
    }
  }
  faPublish.addEventListener('change', () => { if (!faPublish.checked) faRewrite.checked = false; faSyncCdn(); });
  faRewrite.addEventListener('change', () => { if (faRewrite.checked) faPublish.checked = true; faSyncCdn(); });

  function openFlowAssetsModal(flow) {
    flowAssetsFlow = flow;
    flowAssetsStatus.textContent = 'Loading…';
    flowAssetsStatus.className = 'modal-status';
    flowAssetsSave.disabled = true;
    flowAssetsMeta.textContent = 'flows/' + flow + '/';
    faResults.style.display = 'none';
    faResults.innerHTML = '';
    flowAssetsModal.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch('flow-assets.php?flow=' + encodeURIComponent(flow))
      .then(r => r.json())
      .then(json => {
        if (!json.ok) {
          flowAssetsStatus.textContent = 'Error: ' + (json.error || 'unknown');
          flowAssetsStatus.className = 'modal-status error';
          return;
        }
        const c = json.config || {};
        faOptimize.checked = c.optimize !== false;
        faPublish.checked = !!c.cdn_publish;
        faRewrite.checked = !!c.cdn_rewrite;
        faCdn = json.cdn || faCdn;
        faSyncCdn();
        flowAssetsStatus.textContent = json.kk_ready ? 'KK package will be rebuilt on save.' : '';
        flowAssetsSave.disabled = false;
      })
      .catch(err => {
        flowAssetsStatus.textContent = 'Request failed: ' + err;
        flowAssetsStatus.className = 'modal-status error';
      });
  }

  function closeFlowAssetsModal() {
    flowAssetsModal.classList.remove('open');
    document.body.style.overflow = '';
    flowAssetsFlow = null;
  }

  function faRenderResults(json) {
    const stat = (label, val) => '<span class="fa-stat"><b>' + val + '</b><span>' + label + '</span></span>';
    let html = stat('saved', faHumanBytes(json.saved_bytes))
      + stat('images', json.images_optimized || 0)
      + stat('WebP', json.webp_created || 0)
      + stat('CSS min', json.css_minified || 0)
      + stat('JS min', json.js_minified || 0);
    if (json.uploaded) html += stat('uploaded', json.uploaded);
    if (json.asset_base) html += '<div class="fa-cdn-note" style="margin-top:0.5rem;">CDN base: <code>' + json.asset_base + '</code></div>';
    faResults.innerHTML = html;
    faResults.style.display = '';
  }

  flowAssetsSave.addEventListener('click', async () => {
    if (!flowAssetsFlow) return;
    flowAssetsSave.disabled = true;
    flowAssetsStatus.textContent = 'Optimizing' + (faPublish.checked ? ', publishing' : '') + ' & rebuilding both brands…';
    flowAssetsStatus.className = 'modal-status';
    try {
      const payload = {
        assets_config: {
          optimize: faOptimize.checked,
          cdn_publish: faPublish.checked,
          cdn_rewrite: faRewrite.checked
        }
      };
      const results = await saveConfigAllBrands('flow-assets.php', payload, flowAssetsFlow);
      const bad = firstConfigFailure(results);
      if (bad) {
        flowAssetsStatus.textContent = 'Error (' + bad.name + '): ' + ((bad.json && bad.json.error) || 'save failed');
        flowAssetsStatus.className = 'modal-status error';
        flowAssetsSave.disabled = false;
        return;
      }
      // Aggregate stats across both brand builds.
      const agg = { saved_bytes: 0, images_optimized: 0, webp_created: 0,
                    css_minified: 0, js_minified: 0, uploaded: 0 };
      results.forEach(r => {
        const j = r.json || {};
        agg.saved_bytes += Number(j.saved_bytes) || 0;
        agg.images_optimized += Number(j.images_optimized) || 0;
        agg.webp_created += Number(j.webp_created) || 0;
        agg.css_minified += Number(j.css_minified) || 0;
        agg.js_minified += Number(j.js_minified) || 0;
        agg.uploaded += Number(j.uploaded) || 0;
      });
      faRenderResults(agg);
      flowAssetsStatus.textContent = 'Done (both brands) · saved ' + faHumanBytes(agg.saved_bytes)
        + (agg.uploaded ? ' · ' + agg.uploaded + ' uploaded' : '');
      flowAssetsStatus.className = 'modal-status ok';
      showToast('Assets optimized (both brands)');
      flowAssetsSave.disabled = false;
    } catch (err) {
      flowAssetsStatus.textContent = 'Request failed: ' + err;
      flowAssetsStatus.className = 'modal-status error';
      flowAssetsSave.disabled = false;
    }
  });

  document.querySelectorAll('.flow-assets-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      closeAllDropdowns(null);
      openFlowAssetsModal(btn.dataset.flow);
    });
  });
  document.querySelectorAll('[data-flow-assets-cancel]').forEach(b => b.addEventListener('click', closeFlowAssetsModal));
  flowAssetsModal.addEventListener('click', e => { if (e.target === flowAssetsModal) closeFlowAssetsModal(); });

  /* --------------------------------------- clone (same-brand duplicate) */
  const cloneModal = document.getElementById('clone-modal');
  const cloneInput = document.getElementById('clone-name');
  const cloneStatus = document.getElementById('clone-status');
  const cloneSource = document.getElementById('clone-source');
  const cloneGo = document.getElementById('clone-go');
  let cloneCtx = null;

  function openClone(info) {
    cloneCtx = info;
    cloneSource.textContent = (info.kind === 'flow' ? 'flow' : info.collection) + ' / ' + info.name;
    cloneInput.value = '';
    cloneInput.placeholder = info.name + '-clone';
    cloneStatus.textContent = ''; cloneStatus.className = 'modal-status';
    cloneGo.disabled = false;
    cloneModal.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => cloneInput.focus(), 50);
  }
  function closeClone() {
    cloneModal.classList.remove('open');
    document.body.style.overflow = '';
    cloneCtx = null;
  }

  cloneGo.addEventListener('click', async () => {
    if (!cloneCtx) return;
    cloneGo.disabled = true;
    cloneStatus.textContent = 'Cloning…'; cloneStatus.className = 'modal-status';
    try {
      const res = await fetch('clone-item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          kind: cloneCtx.kind,
          collection: cloneCtx.collection,
          name: cloneCtx.name,
          new_name: cloneInput.value.trim()
        })
      });
      const json = await res.json();
      if (!json.ok) {
        cloneStatus.textContent = 'Error: ' + (json.error || 'clone failed');
        cloneStatus.className = 'modal-status error';
        cloneGo.disabled = false;
        return;
      }
      const qt = qcText(json);
      cloneStatus.textContent = 'Cloned as ' + json.name + (json.kk_rebuilt ? ' · KK rebuilt' : '')
        + (qt ? ' · ' + qt : '');
      cloneStatus.className = 'modal-status ok';
      showToast('Cloned to ' + json.name);
      if (json.qc && json.qc.status === 'fail') {
        alert('Cloned, but QC found issues in the rebuilt KK package:\n\n• '
          + (json.qc.errors || []).concat(json.qc.warnings || []).join('\n• '));
      }
      setTimeout(() => { closeClone(); window.location.reload(); }, 800);
    } catch (err) {
      cloneStatus.textContent = 'Request failed: ' + err;
      cloneStatus.className = 'modal-status error';
      cloneGo.disabled = false;
    }
  });

  document.querySelectorAll('.item-clone-btn').forEach(btn => {
    btn.addEventListener('click', () => openClone(cardInfo(btn.closest('.card'))));
  });
  document.querySelectorAll('[data-clone-cancel]').forEach(b => b.addEventListener('click', closeClone));
  cloneModal.addEventListener('click', e => { if (e.target === cloneModal) closeClone(); });

  /* ------------------------------------------------------- Plan Library */
  const plansModal = document.getElementById('plans-modal');
  const plansList = document.getElementById('plans-list');
  const plansStatus = document.getElementById('plans-status');
  const plansAddBtn = document.getElementById('plans-add');
  let plansState = { tokens: [], labels: {}, plans: [] };

  function planTokenOptions(selected) {
    const opt = t => {
      const label = plansState.labels[t] ? plansState.labels[t] + ' [' + t + ']' : t;
      return '<option value="' + esc(t) + '"' + (t === selected ? ' selected' : '') + '>' + esc(label) + '</option>';
    };
    const planToks = plansState.tokens.filter(t => REGISTER_TOKENS.indexOf(t) < 0);
    const regToks = plansState.tokens.filter(t => REGISTER_TOKENS.indexOf(t) >= 0);
    let html = '<option value="">— none —</option>';
    html += '<optgroup label="Plan &amp; lifetime tokens">' + planToks.map(opt).join('') + '</optgroup>';
    if (regToks.length) {
      html += '<optgroup label="Register destinations">' + regToks.map(opt).join('') + '</optgroup>';
    }
    return html;
  }

  function planField(label, name, value, opts) {
    opts = opts || {};
    const cls = opts.col2 ? 'field col-2' : 'field';
    const dis = opts.disabled ? ' disabled' : '';
    const ph = opts.ph ? ' placeholder="' + esc(opts.ph) + '"' : '';
    return '<div class="' + cls + '"><label>' + esc(label) + '</label>' +
      '<input type="text" data-field="' + name + '" value="' + esc(value == null ? '' : String(value)) + '"' + dis + ph + '></div>';
  }

  function planTokenField(label, name, value) {
    return '<div class="field"><label>' + esc(label) + '</label>' +
      '<select data-field="' + name + '">' + planTokenOptions(value || '') + '</select></div>';
  }

  function planCardHTML(p, isNew) {
    const t = p.offer_tokens || {};
    const brands = p.brands || ['aipu', 'omni'];
    const give = (p.giveaways || []).join('\n');
    return '<div class="plan-card' + (isNew ? ' is-new' : '') + '" data-plan-card data-mode="' + (isNew ? 'create' : 'update') + '">' +
      '<div class="plan-card-head">' +
        '<h3 class="plan-card-title">' + (isNew ? 'New plan' : esc(p.display_name || p.id)) +
          (isNew ? '' : ' <span class="plan-id-tag">' + esc(p.id) + '</span>') + '</h3>' +
      '</div>' +
      '<div class="plan-grid">' +
        planField('Plan id (slug)', 'id', p.id, { disabled: !isNew, ph: 'e.g. creator' }) +
        planField('Display name', 'display_name', p.display_name, { ph: 'Creator' }) +
        planField('Order', 'order', p.order != null ? p.order : 0) +
        planField('Badge', 'badge', p.badge, { ph: 'e.g. Most Popular' }) +
        planField('Tag / description', 'tag', p.tag, { col2: true, ph: 'short line shown on the card' }) +
        planField('Points', 'points', p.points, { ph: '1,000' }) +
        planField('Monthly price', 'monthly_price', p.monthly_price, { ph: '14.99' }) +
        planField('Monthly strike', 'monthly_strike', p.monthly_strike, { ph: '29' }) +
        planField('Yearly price', 'yearly_price', p.yearly_price, { ph: '99' }) +
        planField('Yearly strike', 'yearly_strike', p.yearly_strike, { ph: '179' }) +
        planField('Lifetime price', 'lifetime_price', p.lifetime_price, { ph: '399' }) +
        planField('Lifetime strike', 'lifetime_strike', p.lifetime_strike, { ph: '1900' }) +
        planTokenField('Offer token · monthly', 'offer_tokens.monthly', t.monthly) +
        planTokenField('Offer token · yearly', 'offer_tokens.yearly', t.yearly) +
        planTokenField('Offer token · lifetime', 'offer_tokens.lifetime', t.lifetime) +
        '<div class="field"><label>Brands</label><div class="plan-brands">' +
          '<label><input type="checkbox" data-brand="aipu"' + (brands.indexOf('aipu') >= 0 ? ' checked' : '') + '> AIPU</label>' +
          '<label><input type="checkbox" data-brand="omni"' + (brands.indexOf('omni') >= 0 ? ' checked' : '') + '> OmniRogue</label>' +
        '</div></div>' +
        '<div class="field col-2"><label>Free giveaways (one per line)</label>' +
          '<textarea data-field="giveaways" rows="2" placeholder="300 free gens">' + esc(give) + '</textarea></div>' +
      '</div>' +
      '<div class="plan-card-actions">' +
        '<button type="button" class="btn btn-create-flow" data-plan-save>Save</button>' +
        (isNew ? '<button type="button" class="btn btn-ghost" data-plan-cancel-new>Cancel</button>'
               : '<button type="button" class="btn btn-danger" data-plan-delete>Delete</button>') +
        '<span class="plan-card-status" data-plan-status></span>' +
      '</div>' +
    '</div>';
  }

  function gatherPlan(card) {
    const plan = { offer_tokens: {}, brands: [], giveaways: [] };
    card.querySelectorAll('[data-field]').forEach(el => {
      const name = el.dataset.field;
      const val = el.value;
      if (name.indexOf('offer_tokens.') === 0) {
        plan.offer_tokens[name.split('.')[1]] = val || null;
      } else if (name === 'giveaways') {
        plan.giveaways = val.split('\n').map(s => s.trim()).filter(Boolean);
      } else if (name === 'order') {
        plan.order = parseInt(val, 10) || 0;
      } else {
        plan[name] = val;
      }
    });
    card.querySelectorAll('[data-brand]').forEach(cb => { if (cb.checked) plan.brands.push(cb.dataset.brand); });
    return plan;
  }

  function renderPlans() {
    if (!plansState.plans.length) {
      plansList.innerHTML = '<div class="cta-empty">No plans yet. Click “Add plan”.</div>';
      return;
    }
    plansList.innerHTML = plansState.plans.map(p => planCardHTML(p, false)).join('');
  }

  function addPlanCard() {
    const existing = plansList.querySelector('.plan-card.is-new');
    if (existing) { existing.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
    const blank = { id: '', display_name: '', offer_tokens: {}, brands: ['aipu', 'omni'], giveaways: [], order: plansState.plans.length + 1 };
    const wrap = document.createElement('div');
    wrap.innerHTML = planCardHTML(blank, true);
    plansList.prepend(wrap.firstChild);
    const idInput = plansList.querySelector('.plan-card.is-new [data-field="id"]');
    if (idInput) idInput.focus();
  }

  async function savePlanCard(card) {
    const mode = card.dataset.mode === 'create' ? 'create' : 'update';
    const plan = gatherPlan(card);
    const statusEl = card.querySelector('[data-plan-status]');
    if (!plan.id) { statusEl.textContent = 'id required'; statusEl.className = 'plan-card-status error'; return; }
    statusEl.textContent = 'Saving…'; statusEl.className = 'plan-card-status';
    try {
      const res = await fetch('plan-library.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: mode, plan })
      });
      const json = await res.json();
      if (!json.ok) { statusEl.textContent = json.error || 'save failed'; statusEl.className = 'plan-card-status error'; return; }
      plansState.plans = json.plans || plansState.plans;
      showToast('Plan “' + plan.id + '” saved');
      renderPlans();
    } catch (err) {
      statusEl.textContent = 'request failed'; statusEl.className = 'plan-card-status error';
    }
  }

  async function deletePlan(id) {
    if (!id || !confirm('Delete plan “' + id + '”? This removes it from the library.')) return;
    try {
      const res = await fetch('plan-library.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id })
      });
      const json = await res.json();
      if (!json.ok) { showToast('Delete failed: ' + (json.error || '')); return; }
      plansState.plans = json.plans || [];
      showToast('Plan “' + id + '” deleted');
      renderPlans();
    } catch (err) { showToast('Delete failed'); }
  }

  async function loadPlans() {
    plansList.innerHTML = '<div class="cta-empty">Loading…</div>';
    plansStatus.textContent = '';
    try {
      const res = await fetch('plan-library.php');
      const json = await res.json();
      if (!json.ok) { plansList.innerHTML = '<div class="cta-empty">Error: ' + esc(json.error || 'load failed') + '</div>'; return; }
      plansState = { tokens: json.tokens || [], labels: json.token_labels || {}, plans: json.plans || [] };
      renderPlans();
    } catch (err) {
      plansList.innerHTML = '<div class="cta-empty">Request failed: ' + esc(String(err)) + '</div>';
    }
  }

  function openPlans() { plansModal.classList.add('open'); document.body.style.overflow = 'hidden'; loadPlans(); }
  function closePlans() { plansModal.classList.remove('open'); document.body.style.overflow = ''; }

  plansList.addEventListener('click', e => {
    const card = e.target.closest('[data-plan-card]');
    if (!card) return;
    if (e.target.closest('[data-plan-save]')) savePlanCard(card);
    else if (e.target.closest('[data-plan-delete]')) deletePlan((card.querySelector('[data-field="id"]') || {}).value);
    else if (e.target.closest('[data-plan-cancel-new]')) card.remove();
  });
  document.querySelectorAll('[data-open-plans]').forEach(b => b.addEventListener('click', openPlans));
  document.querySelectorAll('[data-plans-cancel]').forEach(b => b.addEventListener('click', closePlans));
  plansModal.addEventListener('click', e => { if (e.target === plansModal) closePlans(); });
  plansAddBtn.addEventListener('click', addPlanCard);

  // ---- Live Flow Tester ------------------------------------------------- //
  const testerModal = document.getElementById('tester-modal');
  if (testerModal) {
    const tUrl = document.getElementById('tester-url');
    const tHost = document.getElementById('tester-host');
    const tMax = document.getElementById('tester-maxpages');
    const tGo = document.getElementById('tester-go');
    const tStatus = document.getElementById('tester-status');
    const tRun = document.getElementById('tester-run');
    const tLight = document.getElementById('tester-light');
    const tVerdict = document.getElementById('tester-verdict');
    const tvTitle = document.getElementById('tester-verdict-title');
    const tvSub = document.getElementById('tester-verdict-sub');
    const tBar = document.getElementById('tester-bar');
    const tStats = document.getElementById('tester-stats');
    const tFindings = document.getElementById('tester-findings');
    let tPoll = null;

    const KIND_LABEL = {
      'broken-cta': 'Broken link / CTA', 'dead-cta': 'Dead CTA',
      'dead-popup-cta': 'Dead popup CTA', 'broken-popup-cta': 'Broken popup CTA',
      'page-load': 'Page load', 'broken-asset': 'Broken asset',
      'js-error': 'JavaScript error', 'home-alive': 'Home/Logo not dead',
      'thin-product': 'Thin product page', 'unclickable': 'Unclickable CTA',
      'capped': 'Crawl capped', 'skipped': 'Skipped (safety)'
    };

    function openTester() {
      testerModal.classList.add('open');
      document.body.style.overflow = 'hidden';
      tUrl.focus();
    }
    function closeTester() {
      testerModal.classList.remove('open');
      document.body.style.overflow = '';
      if (tPoll) { clearInterval(tPoll); tPoll = null; }
    }

    function setLight(state, title, sub) {
      tLight.className = 'ft-light ' + state;
      tVerdict.className = 'ft-verdict ' + (state === 'green' || state === 'red' ? state : '');
      tvTitle.textContent = title;
      tvSub.textContent = sub || '';
    }

    function renderTester(d) {
      const prog = d.progress || {};
      const sum = d.summary || {};
      const known = Math.max(prog.pages_known || 1, prog.pages_done || 0, 1);
      const pct = Math.min(100, Math.round(((prog.pages_done || 0) / known) * 100));
      tBar.style.width = (d.status === 'done' ? 100 : pct) + '%';
      tStats.innerHTML =
        'Pages: <b>' + (prog.pages_done || 0) + '</b> / ' + known +
        ' · Clicks: <b>' + (prog.clicks_done || 0) + '</b>' +
        ' · Errors: <b style="color:#f87171">' + (sum.errors || 0) + '</b>' +
        ' · Warnings: <b style="color:#f0b429">' + (sum.warnings || 0) + '</b>' +
        ' · Checks: <b>' + (sum.probes || 0) + '</b>';

      if (d.status === 'running' || d.status === 'starting') {
        setLight('running', 'Testing…',
          'Crawling ' + (prog.pages_done || 0) + ' page(s) so far');
      } else if (d.status === 'error') {
        setLight('red', 'Test could not finish', d.error ? String(d.error).split('\n')[0] : 'Unknown error');
      } else if (d.status === 'done') {
        if (d.verdict === 'green') {
          setLight('green', 'GREEN — safe to launch',
            'Every link, button & popup behaves. ' + (sum.warnings ? sum.warnings + ' minor warning(s).' : 'No issues.'));
        } else {
          setLight('red', 'RED — do not launch',
            (sum.errors || 0) + ' blocking issue(s) found. Fix them, re-upload to KK, and re-test.');
        }
      }

      // Group findings by page.
      const findings = d.findings || [];
      if (d.status === 'done' && !findings.length) {
        tFindings.innerHTML = '<div class="ft-empty-ok">✓ No problems found across ' + (sum.pages || 0) + ' page(s).</div>';
        return;
      }
      const byPage = {};
      const order = [];
      findings.forEach(f => {
        const p = f.page || d.url || '';
        if (!byPage[p]) { byPage[p] = []; order.push(p); }
        byPage[p].push(f);
      });
      tFindings.innerHTML = order.map(p => {
        const items = byPage[p];
        const errs = items.filter(f => f.sev === 'error').length;
        const warns = items.filter(f => f.sev === 'warn').length;
        let short = p;
        try { const u = new URL(p); short = u.pathname.split('/').pop() || u.pathname; } catch (e) {}
        const pill = errs ? '<span class="ft-pill err">' + errs + ' error' + (errs > 1 ? 's' : '') + '</span>'
                          : '<span class="ft-pill warn">' + warns + ' warning' + (warns > 1 ? 's' : '') + '</span>';
        const rows = items.map(f => {
          const meta = [];
          if (f.element) meta.push('Element: “' + esc(f.element) + '”');
          if (f.expected) meta.push('Expected: ' + esc(f.expected));
          if (f.actual) meta.push('Got: ' + esc(f.actual));
          if (f.dest) meta.push('→ <code>' + esc(f.dest) + '</code>');
          const shot = f.screenshot ? ' <a class="ft-shot" href="' + esc(f.screenshot) + '" target="_blank" rel="noopener">screenshot ↗</a>' : '';
          return '<div class="ft-finding"><span class="ft-sev ' + f.sev + '">' + f.sev + '</span>' +
            '<div class="ft-body"><span class="ft-msg">' + esc(KIND_LABEL[f.kind] || f.kind) + ' — ' + esc(f.message) + '</span>' +
            (meta.length ? '<span class="ft-meta">' + meta.join(' · ') + shot + '</span>' : (shot ? '<span class="ft-meta">' + shot + '</span>' : '')) +
            '</div></div>';
        }).join('');
        return '<div class="ft-page-group"><div class="ft-page-head">' + pill +
          '<span class="ft-page-url">' + esc(short) + '</span></div>' + rows + '</div>';
      }).join('');
    }

    async function pollTester(statusUrl) {
      try {
        const res = await fetch(statusUrl, { cache: 'no-store' });
        const d = await res.json();
        renderTester(d);
        if (d.status === 'done' || d.status === 'error' || d.stalled) {
          if (tPoll) { clearInterval(tPoll); tPoll = null; }
          tGo.disabled = false;
          tGo.textContent = 'Run Flow Test';
          tStatus.textContent = d.status === 'done'
            ? 'Finished in ' + Math.max(1, (d.finished_at || 0) - (d.started_at || 0)) + 's'
            : 'Stopped';
        }
      } catch (err) { /* transient; keep polling */ }
    }

    async function runTester() {
      const url = (tUrl.value || '').trim();
      if (!/^https?:\/\//i.test(url)) { tStatus.textContent = 'Enter a valid http(s) URL'; tUrl.focus(); return; }
      if (tPoll) { clearInterval(tPoll); tPoll = null; }
      tGo.disabled = true;
      tGo.textContent = 'Testing…';
      tStatus.textContent = '';
      tRun.hidden = false;
      tFindings.innerHTML = '';
      tStats.textContent = '';
      tBar.style.width = '0%';
      setLight('running', 'Starting…', 'Launching headless browser');
      try {
        const res = await fetch('flow-test.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            url: url,
            allow_host: (tHost.value || '').trim(),
            max_pages: parseInt(tMax.value, 10) || 40
          })
        });
        const json = await res.json();
        if (!json.ok) {
          setLight('red', 'Could not start', json.error || 'Unknown error');
          tGo.disabled = false; tGo.textContent = 'Run Flow Test';
          return;
        }
        const statusUrl = json.status_url;
        pollTester(statusUrl);
        tPoll = setInterval(() => pollTester(statusUrl), 2500);
      } catch (err) {
        setLight('red', 'Request failed', String(err));
        tGo.disabled = false; tGo.textContent = 'Run Flow Test';
      }
    }

    document.querySelectorAll('[data-open-tester]').forEach(b => b.addEventListener('click', openTester));
    document.querySelectorAll('[data-tester-cancel]').forEach(b => b.addEventListener('click', closeTester));
    testerModal.addEventListener('click', e => { if (e.target === testerModal) closeTester(); });
    tGo.addEventListener('click', runTester);
    tUrl.addEventListener('keydown', e => { if (e.key === 'Enter') runTester(); });
  }

  document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    if (mobileOverlay.classList.contains('open')) closeMobilePreview();
    if (flowPlansModal.classList.contains('open')) closeFlowPlansModal();
    if (confirmModal.classList.contains('open')) closeConfirm();
    if (importModal.classList.contains('open')) closeImport();
    if (ctaModal.classList.contains('open')) closeCtaModal();
    if (cloneModal.classList.contains('open')) closeClone();
    if (plansModal.classList.contains('open')) closePlans();
    if (testerModal && testerModal.classList.contains('open')) testerModal.classList.remove('open'), document.body.style.overflow = '';
  });

  /* =====================================================================
     initFlowCard — wire ALL per-card listeners for ONE flow card.

     Page-load cards are already wired by the dedicated forEach loops above.
     This function exists for cards LIVE-INSERTED after a background build
     (see liveInsertFlow): it reproduces the same wiring, scoped to a single
     card element, so an inserted card behaves identically to a server-
     rendered one. It is idempotent (guarded by data-init).
     ===================================================================== */
  function initFlowCard(card) {
    if (!card || card.dataset.init === '1') return;
    card.dataset.init = '1';

    // Register the card's brand variants so cross-brand config saves work.
    const vs = flowVariants(card);
    vs.forEach(n => { FLOW_SIBLINGS[n] = vs; });

    // --- dropdowns: portal each menu to <body>, then wire toggle + menu ---
    card.querySelectorAll('.dropdown').forEach(dd => {
      const menu = dd.querySelector('.dropdown-menu');
      const toggle = dd.querySelector('.dropdown-toggle');
      if (!menu || !toggle) return;
      dropdownMenus.set(dd, menu);
      menu.dataset.dropdownOwner = 'dd-' + Math.random().toString(36).slice(2, 9);
      dd.dataset.menuId = menu.dataset.dropdownOwner;
      document.body.appendChild(menu);
      toggle.addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        if (toggle.disabled) return;
        const willOpen = !dd.classList.contains('open');
        closeAllDropdowns(willOpen ? dd : null);
        setDropdownOpen(dd, willOpen);
      });
      menu.addEventListener('click', e => e.stopPropagation());
      menu.addEventListener('mousedown', e => e.stopPropagation());
      menu.querySelectorAll('a.dropdown-item').forEach(a => {
        a.addEventListener('click', () => closeAllDropdowns(null));
      });
    });

    // --- brand switch ---
    card.querySelectorAll('.brand-switch-btn').forEach(btn => {
      btn.addEventListener('click', () => switchFlowBrand(card, btn.dataset.brandSwitch));
    });

    // --- mobile preview ---
    card.querySelectorAll('.flow-mobile-btn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault(); e.stopPropagation();
        closeAllDropdowns(null);
        const brand = card.dataset.brand || 'omni';
        const sales = card.dataset['sales' + (brand === 'aipu' ? 'Aipu' : 'Omni')];
        const checkout = card.dataset['checkout' + (brand === 'aipu' ? 'Aipu' : 'Omni')] || '';
        openMobilePreview({ url: sales, checkout, label: (card.dataset.base || '') + ' · ' + brand.toUpperCase() });
      });
    });

    // --- KK re-format (both brands) ---
    card.querySelectorAll('.kk-format-group').forEach(btn => {
      btn.addEventListener('click', async e => {
        e.stopPropagation();
        const variants = flowVariants(card);
        if (!variants.length) return;
        if (btn.dataset.reformat === '1'
          && !confirm('Re-run KK Format for both brands of "' + btn.dataset.base + '"?')) return;
        closeAllDropdowns(null);
        if (typeof clearKkError === 'function') clearKkError(card);
        const original = btn.textContent;
        btn.classList.add('is-busy'); btn.textContent = 'Formatting…';
        showToast('Building KK packages…');
        const results = await kkFormatVariants(variants);
        const failed = results.filter(r => !r.json || !r.json.ok || (r.json.qc && r.json.qc.status === 'fail'));
        if (failed.length) {
          const failures = failed.map(r => ({ name: r.name, reasons: kkFailureReasons(r.json) }));
          showToast('KK Format failed for ' + failed.length + ' brand(s)');
          if (typeof showKkError === 'function') showKkError(card, failures);
          btn.classList.remove('is-busy'); btn.textContent = original;
          return;
        }
        showToast('KK packages built for both brands');
        setTimeout(() => window.location.reload(), 1100);
      });
    });

    // --- KK download (auto-builds any missing package first) ---
    card.querySelectorAll('.flow-kk-download').forEach(btn => {
      btn.addEventListener('click', async e => {
        e.stopPropagation();
        clearKkError(card);
        btn.classList.remove('kk-error', 'kk-ok');
        const choice = btn.dataset.kkBrand; // aipu | omni | all
        const brands = choice === 'all' ? ['aipu', 'omni'] : [choice];
        let names = {};
        try { names = JSON.parse(card.dataset.variants || '{}'); } catch (_) {}
        const missing = brands.filter(b => card.dataset[KK_BRAND_KEY[b]] !== '1' && names[b]);
        if (missing.length) {
          const original = btn.innerHTML;
          btn.classList.add('is-busy');
          btn.innerHTML = 'Building ' + missing.map(b => b.toUpperCase()).join(' + ') + '…';
          showToast('No KK package yet — building it now…');
          const results = await kkFormatVariants(missing.map(b => names[b]));
          const failed = results.filter(r => !r.json || !r.json.ok || (r.json.qc && r.json.qc.status === 'fail'));
          btn.classList.remove('is-busy');
          btn.innerHTML = original;
          if (failed.length) {
            const failures = failed.map(r => ({ name: r.name, reasons: kkFailureReasons(r.json) }));
            btn.classList.add('kk-error');
            showKkError(card, failures);
            showToast('KK build failed — see the red note on the card');
            return;
          }
          missing.forEach(b => { card.dataset[KK_BRAND_KEY[b]] = '1'; });
          btn.classList.add('kk-ok');
          showToast('KK package ready — downloading…');
        } else {
          btn.classList.add('kk-ok');
        }
        closeAllDropdowns(null);
        window.location.href = 'kk-download.php?base=' + btn.dataset.base + '&brand=' + choice;
        setTimeout(() => btn.classList.remove('kk-ok'), 2500);
      });
    });

    // --- archive (Configure → Archive, and the corner trash icon) ---
    card.querySelectorAll('.flow-delete-group').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        closeAllDropdowns(null);
        archiveFlowCard(card);
      });
    });
    card.querySelectorAll('.flow-trash-btn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        archiveFlowCard(card);
      });
    });

    // --- bulk-select checkbox ---
    card.querySelectorAll('.select-box').forEach(box => {
      box.addEventListener('change', () => {
        if (box.checked) { selected.add(card); card.classList.add('selected'); }
        else { selected.delete(card); card.classList.remove('selected'); }
        updateBulkBar();
      });
    });

    // --- Configure menu items ---
    card.querySelectorAll('.flow-plans-btn').forEach(btn => {
      btn.addEventListener('click', e => { e.stopPropagation(); closeAllDropdowns(null); openFlowPlansModal(btn.dataset.flow); });
    });
    card.querySelectorAll('.cta-config-btn').forEach(btn => {
      btn.addEventListener('click', e => { e.stopPropagation(); closeAllDropdowns(null); openCtaModal(btn.dataset.flow); });
    });
    card.querySelectorAll('.flow-widgets-btn').forEach(btn => {
      btn.addEventListener('click', e => { e.stopPropagation(); closeAllDropdowns(null); openFlowWidgetsModal(btn.dataset.flow); });
    });
    card.querySelectorAll('.flow-timer-btn').forEach(btn => {
      btn.addEventListener('click', e => { e.stopPropagation(); closeAllDropdowns(null); openFlowTimerModal(btn.dataset.flow); });
    });
    card.querySelectorAll('.flow-assets-btn').forEach(btn => {
      btn.addEventListener('click', e => { e.stopPropagation(); closeAllDropdowns(null); openFlowAssetsModal(btn.dataset.flow); });
    });

    // --- Edit with AI (wired by ai/editor.js for page-load cards) ---
    card.querySelectorAll('.btn-ai-edit').forEach(btn => {
      btn.addEventListener('click', () => {
        if (window.aiEditor && typeof window.aiEditor.open === 'function') {
          const kind = card.dataset.kind || 'flow';
          const name = card.dataset.name;
          const collection = card.dataset.collection || '';
          const brand = card.dataset.brand || 'omni';
          const webRoot = kind === 'flow' ? '/flows/' + name : '/' + collection + '/' + name;
          const label = (kind === 'flow' ? 'Flow' : (kind === 'lander' ? 'Lander' : 'Checkout')) + ' · ' + name;
          window.aiEditor.open({ kind, name, collection, brand, webRoot, label });
        }
      });
    });
  }

  /* =====================================================================
     Generation queue — fire-and-forget background flow builds.

     submitFlow() enqueues each lander×checkout combo and closes the builder
     modal. Builds run ONE AT A TIME on the server (serial): pumpGen launches
     the next job only when the current one reaches a terminal state. The
     top-right widget tracks every job; finished flows are live-inserted into
     the grid with no page reload. Active jobs are mirrored to localStorage so
     a refresh re-attaches polling instead of orphaning an in-flight build.
     ===================================================================== */
  const GEN_LS_KEY = 'genQueue.active.v1';
  const genItems = [];          // all items this session: {id,label,base,state,job,statusUrl,error,step}
  const genQueue = [];          // pending items waiting to launch (FIFO)
  let genActive = null;         // the item currently building (serial guard)
  let genPoll = null;           // setInterval handle for the active job
  let genIdSeq = 0;
  let genHideTimer = null;

  const genWidget = document.getElementById('gen-queue');
  const genListEl = document.getElementById('gen-queue-list');
  const genCountEl = document.getElementById('gen-queue-count');
  const genCollapseBtn = document.getElementById('gen-queue-collapse');
  const genDismissBtn = document.getElementById('gen-queue-dismiss');

  function genActiveJobs() {
    // Jobs we may need to re-attach to after a reload: launched + not terminal.
    return genItems
      .filter(it => it.job && it.statusUrl && it.state !== 'done' && it.state !== 'error')
      .map(it => ({ id: it.id, label: it.label, base: it.base, job: it.job, statusUrl: it.statusUrl }));
  }
  function genPersist() {
    try { localStorage.setItem(GEN_LS_KEY, JSON.stringify(genActiveJobs())); } catch (_) {}
  }

  function genRenderWidget() {
    if (!genWidget) return;
    const total = genItems.length;
    const done = genItems.filter(it => it.state === 'done').length;
    const errored = genItems.filter(it => it.state === 'error').length;
    const finished = genItems.every(it => it.state === 'done' || it.state === 'error');

    if (!total) { genWidget.classList.remove('show'); return; }
    genWidget.classList.add('show');
    genCountEl.textContent = done + (errored ? '+' + errored + '✕' : '') + '/' + total;

    genListEl.innerHTML = genItems.map(it => {
      let icon, cls = '', step = '';
      if (it.state === 'queued') { icon = '⏳'; step = 'queued'; }
      else if (it.state === 'done') { icon = '✓'; cls = 'state-done'; step = 'ready'; }
      else if (it.state === 'error') { icon = '✕'; cls = 'state-error'; step = it.error || 'failed'; }
      else { icon = '<span class="gqi-spin">⟳</span>'; step = it.step || 'building…'; }
      return '<li class="gen-queue-item ' + cls + '" data-gen-id="' + it.id + '">'
        + '<span class="gqi-icon">' + icon + '</span>'
        + '<span class="gqi-label" title="' + esc(it.label) + '">' + esc(it.label) + '</span>'
        + '<span class="gqi-step" title="' + esc(step) + '">' + esc(step) + '</span>'
        + '</li>';
    }).join('');

    // When everything is finished, allow dismissing and auto-hide after a beat
    // (unless something errored — keep errors visible until dismissed).
    genDismissBtn.hidden = !finished;
    if (genHideTimer) { clearTimeout(genHideTimer); genHideTimer = null; }
    if (finished && !errored) {
      genHideTimer = setTimeout(() => { genItems.length = 0; genRenderWidget(); }, 5000);
    }
  }

  function genSetState(item, state, patch) {
    item.state = state;
    if (patch) Object.assign(item, patch);
    genRenderWidget();
    genPersist();
  }

  function pumpGen() {
    if (genActive || !genQueue.length) return;
    genActive = genQueue.shift();
    genSetState(genActive, 'launching', { step: 'starting…' });
    fetch('generate-flow.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(genActive.payload)
    })
      .then(r => r.json().then(j => ({ status: r.status, json: j })))
      .then(({ status, json }) => {
        if (!json || !json.ok) {
          if (json && json.error === 'exists') {
            genSetState(genActive, 'error', { error: 'already exists', base: json.base });
            showToast('"' + (json.base || genActive.label) + '" already exists — skipped');
          } else {
            genSetState(genActive, 'error', { error: (json && json.error) || ('HTTP ' + status) });
          }
          genActive = null;
          pumpGen();
          return;
        }
        genActive.job = json.job;
        genActive.base = json.base;
        genActive.statusUrl = json.status_url;
        genSetState(genActive, 'running', { step: 'building…' });
        startGenPoll(genActive);
      })
      .catch(err => {
        genSetState(genActive, 'error', { error: String(err) });
        genActive = null;
        pumpGen();
      });
  }

  function startGenPoll(item) {
    if (genPoll) { clearInterval(genPoll); genPoll = null; }
    const tick = async () => {
      try {
        const res = await fetch(item.statusUrl, { cache: 'no-store' });
        const d = await res.json();
        if (d.status === 'done') {
          finishGenItem(item, 'done', d);
        } else if (d.status === 'error' || d.stalled) {
          finishGenItem(item, 'error', d);
        } else {
          genSetState(item, 'running', { step: d.step_text || d.step || 'building…' });
        }
      } catch (err) { /* transient; keep polling */ }
    };
    tick();
    genPoll = setInterval(tick, 2500);
  }

  function finishGenItem(item, state, d) {
    if (genPoll) { clearInterval(genPoll); genPoll = null; }
    if (state === 'done') {
      genSetState(item, 'done', { step: 'ready' });
      const qstat = d && d.qc && d.qc.status;
      showToast('"' + (item.base || item.label) + '" ready' + (qstat === 'warn' ? ' · QC warnings' : (qstat === 'fail' ? ' · QC issues' : '')));
      liveInsertFlow(item.base);
    } else {
      genSetState(item, 'error', { error: (d && d.error) || 'build failed' });
      showToast('Build failed: ' + (item.base || item.label));
    }
    genActive = null;
    pumpGen();
  }

  function enqueueGen(payload, label) {
    const item = { id: ++genIdSeq, label, payload, base: null, state: 'queued', job: null, statusUrl: null, step: 'queued' };
    genItems.push(item);
    genQueue.push(item);
    if (genWidget) genWidget.classList.remove('collapsed');
    genRenderWidget();
    pumpGen();
  }

  // Re-attach to a job that was already running before a reload.
  function reattachGen(saved) {
    const item = { id: ++genIdSeq, label: saved.label, payload: null, base: saved.base, state: 'running', job: saved.job, statusUrl: saved.statusUrl, step: 'building…' };
    genItems.push(item);
    genRenderWidget();
    // These run independently of the serial queue (they were already launched).
    const poll = async () => {
      try {
        const res = await fetch(item.statusUrl, { cache: 'no-store' });
        const d = await res.json();
        if (d.status === 'done') { clearInterval(h); genSetState(item, 'done', { step: 'ready' }); showToast('"' + (item.base || item.label) + '" ready'); liveInsertFlow(item.base); }
        else if (d.status === 'error' || d.stalled) { clearInterval(h); genSetState(item, 'error', { error: (d && d.error) || 'build failed' }); }
        else { genSetState(item, 'running', { step: d.step_text || d.step || 'building…' }); }
      } catch (_) {}
    };
    const h = setInterval(poll, 2500);
    poll();
  }

  async function liveInsertFlow(base) {
    if (!base) return;
    try {
      const res = await fetch('flow-card.php?base=' + encodeURIComponent(base), { cache: 'no-store' });
      if (!res.ok) return;
      const html = (await res.text()).trim();
      const grid = document.querySelector('.grid[data-collection="sales-flows"]');
      if (!grid) return;
      // If a card for this base already exists (e.g. re-format), replace it.
      const existing = grid.querySelector('.flow-card[data-base="' + (window.CSS && CSS.escape ? CSS.escape(base) : base) + '"]');
      const tmp = document.createElement('template');
      tmp.innerHTML = html;
      const card = tmp.content.firstElementChild;
      if (!card) return;
      const emptyState = document.querySelector('.collection .empty-state, .empty-state');
      if (existing) { existing.replaceWith(card); }
      else {
        if (emptyState) emptyState.remove();
        grid.prepend(card);
      }
      initFlowCard(card);
      card.classList.add('flash-in');
    } catch (_) { /* non-fatal — the flow exists; a reload will show it */ }
  }

  if (genCollapseBtn) {
    genCollapseBtn.addEventListener('click', () => {
      genWidget.classList.toggle('collapsed');
      genCollapseBtn.textContent = genWidget.classList.contains('collapsed') ? '▸' : '▾';
    });
  }
  if (genDismissBtn) {
    genDismissBtn.addEventListener('click', () => { genItems.length = 0; genRenderWidget(); });
  }

  // On load, re-attach to any builds still running from before a reload.
  (function restoreGenQueue() {
    let saved = [];
    try { saved = JSON.parse(localStorage.getItem(GEN_LS_KEY) || '[]'); } catch (_) {}
    if (Array.isArray(saved) && saved.length) {
      saved.forEach(s => { if (s && s.job && s.statusUrl) reattachGen(s); });
    }
  })();

  // Expose for submitFlow (defined earlier in this IIFE).
  window.__enqueueGen = enqueueGen;
})();
</script>

<!-- AI Editor panel ------------------------------------------------------ -->
<div class="ai-overlay" id="ai-overlay"></div>
<aside class="ai-panel" id="ai-panel" role="dialog" aria-modal="true" aria-labelledby="ai-panel-title">
  <header class="ai-head">
    <div class="ai-head-row">
      <h2 id="ai-panel-title"><span class="ai-spark">✦</span> <span data-ai-title>AI Editor</span></h2>
      <button type="button" class="modal-close" data-ai-close aria-label="Close">&times;</button>
    </div>
    <div class="ai-scope" data-ai-scope></div>
    <div class="ai-head-row" style="gap:0.4rem;flex-wrap:wrap;">
      <a class="btn btn-ghost" target="_blank" rel="noopener" data-ai-preview>Open page ↗</a>
      <button type="button" class="btn btn-ghost" data-ai-history>History</button>
      <button type="button" class="btn btn-ghost" data-ai-revert>Revert latest</button>
    </div>
  </header>

  <div class="ai-controls">
    <span class="ai-pill">Provider</span>
    <select data-ai-provider aria-label="AI provider"></select>
    <span class="ai-pill">Model</span>
    <select data-ai-model aria-label="AI model"></select>
  </div>

  <div class="ai-stream" data-ai-stream></div>

  <div class="ai-status" data-ai-status>Ready.</div>

  <div class="ai-input-wrap">
    <textarea data-ai-input placeholder="Ask AI to edit this page… e.g. ‘Rewrite the hero headline to be bolder and more premium’"></textarea>
    <div class="ai-input-row">
      <span class="ai-hint">⌘/Ctrl + Enter to send · changes write directly · use Revert to undo</span>
      <button type="button" class="ai-send" data-ai-send>Send</button>
    </div>
  </div>
</aside>

<script src="ai/editor.js"></script>
</body>
</html>
