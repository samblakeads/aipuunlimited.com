<?php
declare(strict_types=1);

/**
 * flow-widgets.php — attach conversion widgets (exit popup + mobile slider +
 * social-proof toast) to a flow and bake the rendered snippet into its pages.
 *
 * The studio-page "just created…" social_proof toggle (baked into static.js) is
 * a separate setting and still lives here; the `social` slot below is a real
 * library widget (sales/checkout purchase proof) injected like popup/slider.
 *
 *   GET  ?flow=<slug>  -> { ok, flow, brand, kk_ready, library_widgets,
 *                           flow_plan_ids, config }
 *   POST { flow, popup:{widget_id,placement}, slider:{widget_id,placement},
 *          social:{widget_id,placement}, social_proof:{placement} }
 *        -> saves flow.json widgets_config, injects the rendered snippet into
 *           flows/{slug}/index.html + checkout.html between
 *           <!-- KK-WIDGETS:BEGIN/END --> markers, re-runs kk_format.py so the
 *           KK package ships the widgets too.
 *
 * Placement values: "sales" | "checkout" | "both" | "off".
 *
 * Mirrors flow-plans.php conventions (JSON in/out, local fail(), atomic
 * flow.json write, kk re-format on save).
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/lib/checkout-fs.php';
require_once __DIR__ . '/lib/widget-render.php';

function fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__));
if ($docRoot === false) {
    fail('Cannot resolve document root', 500);
}
$flowsDir = realpath($docRoot . '/flows');
if ($flowsDir === false) {
    fail('Flows directory missing', 500);
}

const FW_PLACEMENTS = ['sales', 'checkout', 'both', 'off'];
const FW_MARK_BEGIN = '<!-- KK-WIDGETS:BEGIN -->';
const FW_MARK_END   = '<!-- KK-WIDGETS:END -->';

function flowDir(string $name, string $flowsDir): string
{
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
        fail('Invalid flow name');
    }
    $real = realpath($flowsDir . '/' . $name);
    if ($real === false || dirname($real) !== $flowsDir || !is_dir($real)) {
        fail('Flow not found: ' . $name, 404);
    }
    return $real;
}

function readManifest(string $flowDir): array
{
    $path = $flowDir . '/flow.json';
    if (!is_file($path)) {
        return [];
    }
    $json = json_decode((string)file_get_contents($path), true);
    return is_array($json) ? $json : [];
}

function loadWidgetLibrary(): array
{
    $path = cb_data_dir() . '/widget-library.json';
    if (!is_file($path)) {
        return [];
    }
    $doc = json_decode((string)file_get_contents($path), true);
    return is_array($doc) && is_array($doc['widgets'] ?? null) ? $doc['widgets'] : [];
}

function loadPlanLibrary(): array
{
    $path = cb_data_dir() . '/plan-library.json';
    if (!is_file($path)) {
        return [];
    }
    $doc = json_decode((string)file_get_contents($path), true);
    return is_array($doc) && is_array($doc['plans'] ?? null) ? $doc['plans'] : [];
}

function widgetById(array $widgets, string $id): ?array
{
    foreach ($widgets as $w) {
        if (($w['id'] ?? null) === $id) {
            return $w;
        }
    }
    return null;
}

/**
 * Plan map for the widget engine: id => name + tokens, with the flow's
 * plans_config overrides (display_name / offer_tokens) applied so plan-card
 * matching uses the names that flow actually renders.
 */
function fw_plan_map(array $manifest): array
{
    $map = [];
    foreach (loadPlanLibrary() as $p) {
        if (empty($p['id'])) {
            continue;
        }
        $map[$p['id']] = [
            'name'   => (string)($p['display_name'] ?? $p['id']),
            'tokens' => is_array($p['offer_tokens'] ?? null) ? $p['offer_tokens'] : [],
        ];
    }
    $savedPlans = $manifest['plans_config']['plans'] ?? null;
    if (is_array($savedPlans)) {
        foreach ($savedPlans as $entry) {
            if (!is_array($entry) || empty($entry['id']) || !isset($map[$entry['id']])) {
                continue;
            }
            if (!empty($entry['display_name'])) {
                $map[$entry['id']]['name'] = (string)$entry['display_name'];
            }
            if (is_array($entry['offer_tokens'] ?? null)) {
                $map[$entry['id']]['tokens'] = array_merge($map[$entry['id']]['tokens'], $entry['offer_tokens']);
            }
        }
    }
    return $map;
}

/** Enabled plan ids in the flow (for "plan not in this flow" warnings). */
function fw_flow_plan_ids(array $manifest): array
{
    $saved = $manifest['plans_config']['plans'] ?? null;
    if (is_array($saved)) {
        $ids = [];
        foreach ($saved as $entry) {
            if (is_array($entry) && !empty($entry['id']) && !empty($entry['enabled'])) {
                $ids[] = (string)$entry['id'];
            }
        }
        return $ids;
    }
    return array_values(array_filter(array_map(
        static fn(array $p) => (string)($p['id'] ?? ''),
        loadPlanLibrary()
    )));
}

function fw_norm_slot(array $cfg): array
{
    $id = trim((string)($cfg['widget_id'] ?? ''));
    $placement = strtolower(trim((string)($cfg['placement'] ?? 'off')));
    if (!in_array($placement, FW_PLACEMENTS, true)) {
        fail('Invalid placement: ' . $placement);
    }
    if ($id !== '' && !preg_match('/^[a-z0-9-]+$/', $id)) {
        fail('Invalid widget_id: ' . $id);
    }
    return ['widget_id' => $id === '' ? null : $id, 'placement' => $placement];
}

/** Social proof toasts are built into static.js and only run on the studio
 *  (create-*) pages, so the setting is a simple on/off. Legacy placement
 *  values ('sales'/'checkout'/'both') from earlier saves all mean "on". */
function fw_norm_social(array $cfg): array
{
    $placement = strtolower(trim((string)($cfg['placement'] ?? 'on')));
    if (!in_array($placement, ['on', 'off', 'sales', 'checkout', 'both'], true)) {
        fail('Invalid social proof placement: ' . $placement);
    }
    return ['placement' => $placement === 'off' ? 'off' : 'on'];
}

/** Bake the social-proof default into the flow's static.js sentinel
 *  (var __OMNI_SOCIAL_DEFAULT='on|off';…) so every page — including the
 *  studio pages where the toasts actually run — sees the setting. */
function fw_bake_social(string $flowDir, string $value): ?string
{
    $path = $flowDir . '/assets/static.js';
    if (!is_file($path)) {
        return 'assets/static.js not found — social proof setting not baked';
    }
    $js = (string)file_get_contents($path);
    $bake = "var __OMNI_SOCIAL_DEFAULT='" . $value . "';window.__OMNI_SOCIAL_DEFAULT=__OMNI_SOCIAL_DEFAULT;";
    $next = preg_replace(
        "/var __OMNI_SOCIAL_DEFAULT\\s*=\\s*(['\"])[^'\"]*\\1\\s*;window\\.__OMNI_SOCIAL_DEFAULT=__OMNI_SOCIAL_DEFAULT;/",
        $bake,
        $js,
        1,
        $count
    );
    if ($next === null) {
        return 'static.js social bake failed (regex error)';
    }
    if ($count === 0) {
        // No sentinel yet (flow built before this feature): inject after the
        // first 'use strict'; same as patch_static_js does.
        $next = preg_replace("/(['\"])use strict\\1;?/", '$0' . $bake, $js, 1, $count);
        if ($count === 0) {
            $next = $bake . $js;
        }
    }
    if ($next !== $js && @file_put_contents($path, $next) === false) {
        return 'could not write assets/static.js (permissions?)';
    }
    return null;
}

/** Replace or insert the marker block before </body>; '' removes the block. */
function fw_inject(string $html, string $block): string
{
    $wrapped = $block === ''
        ? ''
        : FW_MARK_BEGIN . "\n" . $block . "\n" . FW_MARK_END;

    $beginPos = strpos($html, FW_MARK_BEGIN);
    if ($beginPos !== false) {
        $endPos = strpos($html, FW_MARK_END, $beginPos);
        if ($endPos !== false) {
            $endPos += strlen(FW_MARK_END);
            // also swallow a trailing newline left behind on removal
            $trail = ($wrapped === '' && ($html[$endPos] ?? '') === "\n") ? 1 : 0;
            return substr($html, 0, $beginPos) . $wrapped . substr($html, $endPos + $trail);
        }
        // corrupt block: strip from BEGIN marker and fall through to re-insert
        $html = substr($html, 0, $beginPos);
        $html .= "\n</body>\n</html>\n";
    }
    if ($wrapped === '') {
        return $html;
    }
    $pos = strripos($html, '</body>');
    if ($pos === false) {
        return $html . "\n" . $wrapped . "\n";
    }
    return substr($html, 0, $pos) . $wrapped . "\n" . substr($html, $pos);
}

function fw_widgets_for(string $page, array $slots, array $library): array
{
    $out = [];
    foreach (['popup', 'slider', 'social'] as $kind) {
        if (!isset($slots[$kind])) {
            continue;
        }
        $slot = $slots[$kind];
        if ($slot['widget_id'] === null || $slot['placement'] === 'off') {
            continue;
        }
        if ($slot['placement'] !== 'both' && $slot['placement'] !== $page) {
            continue;
        }
        $w = widgetById($library, $slot['widget_id']);
        if ($w === null || empty($w['active'])) {
            continue;
        }
        $out[] = $w;
    }
    return $out;
}

function runKkFormat(string $flowDir, string $docRoot): array
{
    $python = '/usr/bin/python3';
    $script = $docRoot . '/scripts/kk_format.py';
    if (!is_file($script)) {
        return ['ok' => false, 'warnings' => ['kk_format.py missing — KK package not rebuilt']];
    }
    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script)
        . ' --flow-dir ' . escapeshellarg($flowDir)
        . ' --docroot ' . escapeshellarg($docRoot)
        . ' 2>&1';
    $output = shell_exec($cmd);
    $trimmed = trim((string)$output);
    $json = json_decode($trimmed, true);
    if (!is_array($json)) {
        $lines = preg_split('/\r?\n/', $trimmed);
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $try = json_decode(trim($lines[$i]), true);
            if (is_array($try)) {
                $json = $try;
                break;
            }
        }
    }
    if (!is_array($json) || empty($json['ok'])) {
        return ['ok' => false, 'warnings' => ['KK format failed: ' . substr($trimmed, 0, 300)]];
    }
    return $json;
}

// -------------------------------------------------------------------------- GET
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $flow = (string)($_GET['flow'] ?? '');
    $dir = flowDir($flow, $flowsDir);
    $manifest = readManifest($dir);
    $library = loadWidgetLibrary();

    $saved = is_array($manifest['widgets_config'] ?? null) ? $manifest['widgets_config'] : [];
    $config = [
        'popup'  => fw_norm_slot(is_array($saved['popup'] ?? null) ? $saved['popup'] : []),
        'slider' => fw_norm_slot(is_array($saved['slider'] ?? null) ? $saved['slider'] : []),
        'social' => fw_norm_slot(is_array($saved['social'] ?? null) ? $saved['social'] : []),
        'social_proof' => fw_norm_social(is_array($saved['social_proof'] ?? null) ? $saved['social_proof'] : []),
    ];

    echo json_encode([
        'ok'              => true,
        'flow'            => $flow,
        'brand'           => (string)($manifest['brand'] ?? 'aipu'),
        'kk_ready'        => !empty($manifest['kk']) && is_dir($dir . '/kk'),
        'library_widgets' => array_values($library),
        'flow_plan_ids'   => fw_flow_plan_ids($manifest),
        'config'          => $config,
    ]);
    exit;
}

// -------------------------------------------------------------------------- POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail('GET or POST required', 405);
}

$raw = file_get_contents('php://input');
$input = ($raw !== '' && $raw[0] === '{') ? (json_decode($raw, true) ?: []) : $_POST;

$flow = (string)($input['flow'] ?? '');
$dir = flowDir($flow, $flowsDir);
$manifest = readManifest($dir);
if (!$manifest) {
    fail('Flow has no flow.json — rebuild it first', 400);
}

$library = loadWidgetLibrary();

$slots = [
    'popup'  => fw_norm_slot(is_array($input['popup'] ?? null) ? $input['popup'] : []),
    'slider' => fw_norm_slot(is_array($input['slider'] ?? null) ? $input['slider'] : []),
    'social' => fw_norm_slot(is_array($input['social'] ?? null) ? $input['social'] : []),
];
$socialSlot = fw_norm_social(is_array($input['social_proof'] ?? null) ? $input['social_proof'] : []);

$warnings = [];
foreach ($slots as $kind => $slot) {
    if ($slot['widget_id'] === null || $slot['placement'] === 'off') {
        continue;
    }
    $w = widgetById($library, $slot['widget_id']);
    if ($w === null) {
        fail("Unknown $kind widget: " . $slot['widget_id']);
    }
    if (($w['type'] ?? '') !== $kind) {
        fail("Widget '{$slot['widget_id']}' is a {$w['type']}, not a $kind");
    }
    if (empty($w['active'])) {
        $warnings[] = "Widget '{$slot['widget_id']}' is inactive — it will not be injected until activated";
    }
}

// ---- persist flow.json
$manifest['widgets_config'] = $slots + ['social_proof' => $socialSlot];
$tmp = $dir . '/flow.json.tmp';
$ok = @file_put_contents($tmp, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
if ($ok === false || !@rename($tmp, $dir . '/flow.json')) {
    @unlink($tmp);
    fail('Could not write flow.json (permissions?)', 500);
}

// ---- render + inject into flow pages
$planMap = fw_plan_map($manifest);
$pages = [
    'sales'    => $dir . '/index.html',
    'checkout' => $dir . '/checkout.html',
];

$anySalesPlanCta = false;
foreach (fw_widgets_for('sales', $slots, $library) as $w) {
    if (!empty($w['plan_id']) || !empty($w['secondary']['plan_id'])) {
        $anySalesPlanCta = true;
    }
}

// Social proof: bake the on/off default into the flow's static.js so the
// studio pages (where the toasts actually run) see it. The KK rebuild below
// re-reads flow.json and bakes the same value into the package's static.js.
$socialWarn = fw_bake_social($dir, $socialSlot['placement']);
if ($socialWarn !== null) {
    $warnings[] = $socialWarn;
}

$injected = [];
foreach ($pages as $page => $path) {
    if (!is_file($path)) {
        $warnings[] = basename($path) . ' not found — skipped';
        continue;
    }
    $pageWidgets = fw_widgets_for($page, $slots, $library);

    $block = '';
    if ($pageWidgets) {
        $block = widget_render_block($pageWidgets, [
            'page'  => $page,
            'flow'  => $flow,
            'plans' => $planMap,
        ]);
    } elseif ($page === 'checkout' && $anySalesPlanCta) {
        // no checkout widgets, but sales CTAs link here with ?plan= — keep the
        // plan-select engine so those links still scroll/highlight the plan.
        $block = widget_render_plan_select_only([
            'flow'  => $flow,
            'plans' => $planMap,
        ]);
    }

    $html = (string)file_get_contents($path);
    $next = fw_inject($html, $block);
    if ($next !== $html) {
        if (@file_put_contents($path, $next) === false) {
            fail('Could not write ' . basename($path) . ' (permissions?)', 500);
        }
    }
    $injected[$page] = $block !== '';
}

// ---- rebuild the KK package so kk/index.php + kk/checkout.php pick it up
$kkResult = ['ok' => true];
if (!empty($manifest['kk'])) {
    $kkResult = runKkFormat($dir, $docRoot);
    $warnings = array_merge($warnings, $kkResult['warnings'] ?? []);
}

ai_audit_append([
    'kind'   => 'flow-widgets',
    'action' => 'save',
    'flow'   => $flow,
    'config' => $manifest['widgets_config'],
]);

echo json_encode([
    'ok'       => true,
    'flow'     => $flow,
    'config'   => $manifest['widgets_config'],
    'injected' => $injected,
    'rebuilt'  => !empty($manifest['kk']) && !empty($kkResult['ok']),
    'warnings' => array_values(array_unique($warnings)),
]);
