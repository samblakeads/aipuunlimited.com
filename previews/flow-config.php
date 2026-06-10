<?php
declare(strict_types=1);

/**
 * flow-config.php — read/write the per-flow CTA configuration.
 *
 *   GET  ?flow=<slug>                     -> { ok, flow, pages, config, tokens, label_map }
 *   POST { flow, default_register_token,  -> { ok, flow, applied, rebuilt, warnings }
 *          pages: { "<file.php>": { register_token } } }
 *
 * The POST handler writes the config into flows/<slug>/flow.json and re-runs
 * kk_format.py so the live KK package picks up the new register-CTA routing
 * for every non-cloaked page. Plan buttons on checkout.php stay hard-coded.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

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

// Canonical token catalogue + labels (must match scripts/kk_format.py KK_OFFER_TOKENS).
// Single source of truth lives in lib/kk-tokens.php (shared with plan-library.php).
require_once __DIR__ . '/lib/kk-tokens.php';

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
    $raw = file_get_contents($path);
    $json = json_decode((string)$raw, true);
    return is_array($json) ? $json : [];
}

function listKkPages(string $flowDir): array
{
    $kkDir = $flowDir . '/kk';
    if (!is_dir($kkDir)) {
        return [];
    }
    $out = [];
    foreach (scandir($kkDir) as $entry) {
        if ($entry === '.' || $entry === '..' || $entry[0] === '_' || $entry[0] === '.') continue;
        if (!is_file($kkDir . '/' . $entry)) continue;
        if (substr($entry, -4) !== '.php') continue;
        $out[] = $entry;
    }
    sort($out);
    return $out;
}

/**
 * Pages where the register-style CTA is configurable. Hard-coded plan pages
 * (index.php presell, checkout.php plan picker) are excluded — the user said
 * those buttons must stay wired to their own plan tokens.
 */
function configurablePages(array $pages): array
{
    $out = [];
    foreach ($pages as $p) {
        if ($p === 'index.php' || $p === 'checkout.php') continue;
        $out[] = $p;
    }
    return $out;
}

function ensureValidToken(string $token): string
{
    return in_array($token, KK_TOKENS, true) ? $token : 'registercheckout';
}

// -------------------------------------------------------------------------- GET
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $flow = (string)($_GET['flow'] ?? '');
    $dir = flowDir($flow, $flowsDir);
    $manifest = readManifest($dir);
    $pages = listKkPages($dir);
    $configurable = configurablePages($pages);

    $cta = $manifest['cta_config'] ?? [];
    $default = $cta['default_register_token'] ?? 'registercheckout';
    $perPage = $cta['pages'] ?? [];
    $applied = $cta['applied'] ?? [];

    // Per-CTA map (single-page flows): detected CTAs + saved overrides.
    $ctaMap = is_array($manifest['cta_map'] ?? null) ? $manifest['cta_map'] : [];
    $flowType = (string)($manifest['flow_type'] ?? 'multi');

    echo json_encode([
        'ok' => true,
        'flow' => $flow,
        'flow_type' => $flowType,
        'pages' => $pages,
        'configurable_pages' => $configurable,
        'tokens' => KK_TOKENS,
        'token_labels' => TOKEN_LABELS,
        'config' => [
            'default_register_token' => ensureValidToken((string)$default),
            'pages' => is_array($perPage) ? $perPage : (object)[],
            'applied' => is_array($applied) ? $applied : (object)[],
        ],
        'cta_map' => [
            'single_page' => !empty($ctaMap['single_page']) || $flowType !== 'multi',
            'detected' => is_array($ctaMap['detected'] ?? null) ? $ctaMap['detected'] : (object)[],
            'overrides' => is_array($ctaMap['overrides'] ?? null) ? $ctaMap['overrides'] : (object)[],
        ],
        'kk_ready' => !empty($manifest['kk']) && is_dir($dir . '/kk'),
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

$default = ensureValidToken((string)($input['default_register_token'] ?? 'registercheckout'));
$rawPages = $input['pages'] ?? [];
if (!is_array($rawPages)) {
    fail('Invalid pages payload');
}

$pages = listKkPages($dir);
$configurable = configurablePages($pages);
$cleanPages = [];
foreach ($rawPages as $pageName => $entry) {
    if (!is_string($pageName) || !preg_match('/^[A-Za-z0-9._-]+\.php$/', $pageName)) {
        continue;
    }
    if (in_array($pageName, ['index.php', 'checkout.php'], true)) {
        continue; // ignore attempts to override hard-coded plan pages
    }
    // Only persist entries for pages that actually exist in the flow's KK output.
    if ($pages && !in_array($pageName, $pages, true)) {
        continue;
    }
    if (is_array($entry) && isset($entry['register_token'])) {
        $token = ensureValidToken((string)$entry['register_token']);
        if ($token !== $default) {
            $cleanPages[$pageName] = ['register_token' => $token];
        }
    }
}

$manifest['cta_config'] = [
    'default_register_token' => $default,
    'pages' => $cleanPages,
];

// Per-CTA overrides (single-page flows). Values: any KK token, 'dead', 'keep',
// or '' / 'auto' to clear the override and fall back to auto-classification.
if (array_key_exists('cta_overrides', $input)) {
    $rawOverrides = $input['cta_overrides'];
    if (!is_array($rawOverrides)) {
        fail('Invalid cta_overrides payload');
    }
    $cleanOverrides = [];
    foreach ($rawOverrides as $ctaId => $value) {
        if (!is_string($ctaId) || !preg_match('/^[A-Za-z0-9._-]{1,120}$/', $ctaId)) {
            continue;
        }
        $value = (string)$value;
        if ($value === '' || $value === 'auto') {
            continue; // cleared -> auto
        }
        if (!in_array($value, KK_TOKENS, true) && !in_array($value, ['dead', 'keep'], true)) {
            fail("Unknown CTA override '$value' for $ctaId");
        }
        $cleanOverrides[$ctaId] = $value;
    }
    $ctaMap = is_array($manifest['cta_map'] ?? null) ? $manifest['cta_map'] : [];
    $ctaMap['overrides'] = $cleanOverrides;
    $manifest['cta_map'] = $ctaMap;
}

$tmp = $dir . '/flow.json.tmp';
$ok = @file_put_contents($tmp, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
if ($ok === false || !@rename($tmp, $dir . '/flow.json')) {
    @unlink($tmp);
    fail('Could not write flow.json (permissions?)', 500);
}

// Re-run kk-format so the new config takes effect immediately.
$python = '/usr/bin/python3';
$script = $docRoot . '/scripts/kk_format.py';
if (!is_file($script)) {
    fail('KK formatter missing: ' . $script, 500);
}
$cmd = escapeshellarg($python) . ' ' . escapeshellarg($script)
    . ' --flow-dir ' . escapeshellarg($dir)
    . ' --docroot ' . escapeshellarg($docRoot)
    . ' 2>&1';
$output = shell_exec($cmd);
if ($output === null || trim((string)$output) === '') {
    fail('KK format produced no output', 500);
}
$trimmed = trim((string)$output);
$json = json_decode($trimmed, true);
if (!is_array($json)) {
    $lines = preg_split('/\r?\n/', $trimmed);
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $try = json_decode(trim($lines[$i]), true);
        if (is_array($try)) { $json = $try; break; }
    }
}
if (!is_array($json)) {
    fail('KK format failed: ' . substr($trimmed, 0, 500), 500);
}
if (empty($json['ok'])) {
    // Config was saved, but the hard gate blocked the rebuild. Surface the
    // violations so the user can fix them (e.g. assign a CTA override).
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'saved' => true,
        'error' => (string)($json['error'] ?? 'KK build blocked'),
        'violations' => $json['violations'] ?? [],
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'flow' => $flow,
    'config' => [
        'default_register_token' => $default,
        'pages' => $cleanPages,
        'applied' => $json['cta_config']['applied'] ?? (object)[],
    ],
    'cta_map' => $json['cta_map'] ?? (object)[],
    'rebuilt' => true,
    'warnings' => $json['warnings'] ?? [],
]);
