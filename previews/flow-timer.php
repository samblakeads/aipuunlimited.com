<?php
declare(strict_types=1);

/**
 * flow-timer.php — one centralized, flow-level countdown timer.
 *
 *   GET  ?flow=<slug>  -> { ok, flow, brand, kk_ready, config }
 *   POST { flow, timer_config:{ enabled, duration:{d,h,m,s}, scope, apply_to } }
 *        -> saves flow.json timer_config, switches OFF the per-page legacy timer
 *           scripts (markup untouched), bakes ONE shared-deadline runtime into
 *           flows/{slug}/index.html + checkout.html between
 *           <!-- KK-TIMER:BEGIN/END --> markers, then re-runs kk_format.py so the
 *           KK package ships the same timer.
 *
 * The timer is editable ONLY here on the flow, so the sales page, checkout,
 * mobile slider and exit popup always count down to the SAME moment.
 *
 * Mirrors flow-widgets.php / flow-plans.php conventions.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/lib/checkout-fs.php';
require_once __DIR__ . '/lib/flow-timer-render.php';

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

/** All flow-local main.js asset files (never the KK copy). */
function ft_main_js_files(string $flowDir): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($flowDir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getFilename() !== 'main.js') {
            continue;
        }
        $path = str_replace('\\', '/', $file->getPathname());
        if (strpos($path, '/kk/') !== false) {
            continue;
        }
        $out[] = $file->getPathname();
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

    echo json_encode([
        'ok'       => true,
        'flow'     => $flow,
        'brand'    => (string)($manifest['brand'] ?? 'aipu'),
        'kk_ready' => !empty($manifest['kk']) && is_dir($dir . '/kk'),
        'config'   => flow_timer_normalize($manifest['timer_config'] ?? null),
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

$cfg = flow_timer_normalize($input['timer_config'] ?? null);
$warnings = [];

if (!empty($cfg['enabled']) && flow_timer_total_seconds($cfg) <= 0) {
    fail('Timer duration must be greater than zero');
}

// ---- persist flow.json
$manifest['timer_config'] = $cfg;
$tmp = $dir . '/flow.json.tmp';
$ok = @file_put_contents($tmp, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
if ($ok === false || !@rename($tmp, $dir . '/flow.json')) {
    @unlink($tmp);
    fail('Could not write flow.json (permissions?)', 500);
}

$enabled = !empty($cfg['enabled']);

// ---- HTML pages: neutralize (or restore) legacy timers + inject (or remove) block
$pages = [
    'sales'    => $dir . '/index.html',
    'checkout' => $dir . '/checkout.html',
];
$injected = [];
foreach ($pages as $page => $path) {
    if (!is_file($path)) {
        $warnings[] = basename($path) . ' not found — skipped';
        continue;
    }
    $html = (string)file_get_contents($path);

    if ($enabled) {
        $html = flow_timer_neutralize_html($html);
        $block = flow_timer_render_block($cfg, ['page' => $page, 'flow' => $flow]);
        $html = flow_timer_inject($html, $block);
        $injected[$page] = $block !== '';
    } else {
        // turning the timer off: remove our block and re-enable the originals
        $html = flow_timer_inject($html, '');
        $html = flow_timer_restore_html($html);
        $injected[$page] = false;
    }

    if (@file_put_contents($path, $html) === false) {
        fail('Could not write ' . basename($path) . ' (permissions?)', 500);
    }
}

// ---- assets: neutralize (or restore) the main.js daily countdown
$mainTouched = 0;
foreach (ft_main_js_files($dir) as $jsPath) {
    $js = (string)file_get_contents($jsPath);
    $next = $enabled ? flow_timer_neutralize_mainjs($js) : flow_timer_restore_mainjs($js);
    if ($next !== $js) {
        if (@file_put_contents($jsPath, $next) === false) {
            $warnings[] = 'Could not update ' . basename($jsPath);
        } else {
            $mainTouched++;
        }
    }
}

// ---- rebuild the KK package so kk/index.php + kk/checkout.php carry the timer
$kkResult = ['ok' => true];
if (!empty($manifest['kk'])) {
    $kkResult = runKkFormat($dir, $docRoot);
    $warnings = array_merge($warnings, $kkResult['warnings'] ?? []);
}

ai_audit_append([
    'kind'   => 'flow-timer',
    'action' => 'save',
    'flow'   => $flow,
    'config' => $cfg,
]);

echo json_encode([
    'ok'           => true,
    'flow'         => $flow,
    'config'       => $cfg,
    'injected'     => $injected,
    'main_js_updated' => $mainTouched,
    'rebuilt'      => !empty($manifest['kk']) && !empty($kkResult['ok']),
    'warnings'     => array_values(array_unique($warnings)),
]);
