<?php
declare(strict_types=1);

/**
 * flow-assets.php — per-flow asset optimization + BunnyCDN publishing.
 *
 *   GET  ?flow=<slug>  -> { ok, flow, brand, kk_ready, cdn:{configured,enabled,pull_url}, config }
 *   POST { flow, assets_config:{ optimize, cdn_publish, cdn_rewrite } }
 *        -> saves flow.json assets_config, runs scripts/asset_pipeline.py over the
 *           flow (optimize images/CSS/JS + responsive <picture>), optionally
 *           publishes assets to BunnyCDN and rewrites asset URLs to the pull zone,
 *           then re-runs kk_format.py so the KK package matches.
 *
 * CDN credentials live in /var/www/aipuunlimited.com/.env and are read by the
 * Python side (scripts/cdn_config.py) — never passed on the command line.
 *
 * Mirrors flow-timer.php / flow-widgets.php conventions.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/lib/checkout-fs.php';

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

/** Normalized per-flow asset config with safe defaults (optimize on). */
function normAssetsConfig($raw): array
{
    $raw = is_array($raw) ? $raw : [];
    return [
        'optimize'    => array_key_exists('optimize', $raw) ? !empty($raw['optimize']) : true,
        'cdn_publish' => !empty($raw['cdn_publish']),
        'cdn_rewrite' => !empty($raw['cdn_rewrite']),
    ];
}

/** CDN configuration state from .env (no secrets leave this function). */
function cdnStatus(): array
{
    $zone = (string)ai_env('BUNNY_STORAGE_ZONE', '');
    $key  = (string)ai_env('BUNNY_STORAGE_KEY', '');
    $pull = rtrim((string)ai_env('BUNNY_PULL_URL', ''), '/');
    $flag = in_array(strtolower((string)ai_env('CDN_ENABLED', '0')), ['1', 'true', 'yes', 'on'], true);
    $configured = $zone !== '' && $key !== '' && $pull !== '';
    return [
        'configured' => $configured,
        'enabled'    => $flag && $configured,
        'flag'       => $flag,
        'pull_url'   => $pull,
    ];
}

/** Shell out to a python script, parse the last JSON line of stdout. */
function runPython(array $argv, string $docRoot): array
{
    $python = '/usr/bin/python3';
    $cmd = escapeshellarg($python);
    foreach ($argv as $a) {
        $cmd .= ' ' . escapeshellarg($a);
    }
    $cmd .= ' 2>&1';
    $output = (string)shell_exec($cmd);
    $trimmed = trim($output);
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
    if (!is_array($json)) {
        return ['ok' => false, 'error' => substr($trimmed, 0, 400)];
    }
    return $json;
}

function runKkFormat(string $flowDir, string $docRoot): array
{
    $script = $docRoot . '/scripts/kk_format.py';
    if (!is_file($script)) {
        return ['ok' => false, 'warnings' => ['kk_format.py missing — KK package not rebuilt']];
    }
    $res = runPython([$script, '--flow-dir', $flowDir, '--docroot', $docRoot], $docRoot);
    if (empty($res['ok'])) {
        $msg = $res['error'] ?? 'KK format failed';
        return ['ok' => false, 'warnings' => ['KK format: ' . substr((string)$msg, 0, 300)]];
    }
    return $res;
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
        'cdn'      => cdnStatus(),
        'config'   => normAssetsConfig($manifest['assets_config'] ?? null),
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

$cfg = normAssetsConfig($input['assets_config'] ?? null);
$cdn = cdnStatus();
$warnings = [];

// CDN actions require credentials + the master switch.
if (($cfg['cdn_publish'] || $cfg['cdn_rewrite']) && !$cdn['configured']) {
    $warnings[] = 'BunnyCDN is not configured in .env — CDN publish/rewrite skipped';
    $cfg['cdn_publish'] = false;
    $cfg['cdn_rewrite'] = false;
}
if (($cfg['cdn_publish'] || $cfg['cdn_rewrite']) && !$cdn['flag']) {
    $warnings[] = 'CDN_ENABLED=0 in .env — set it to 1 to publish; CDN actions skipped';
    $cfg['cdn_publish'] = false;
    $cfg['cdn_rewrite'] = false;
}
// Rewriting URLs without uploading would 404 — require publish when rewriting.
if ($cfg['cdn_rewrite'] && !$cfg['cdn_publish']) {
    $cfg['cdn_publish'] = true;
}

// ---- persist flow.json
$manifest['assets_config'] = $cfg;
$tmp = $dir . '/flow.json.tmp';
$ok = @file_put_contents($tmp, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
if ($ok === false || !@rename($tmp, $dir . '/flow.json')) {
    @unlink($tmp);
    fail('Could not write flow.json (permissions?)', 500);
}

// ---- run the asset pipeline over the flow
$script = $docRoot . '/scripts/asset_pipeline.py';
if (!is_file($script)) {
    fail('asset_pipeline.py missing', 500);
}
$argv = [$script, '--root', $dir, '--docroot', $docRoot, '--slug', $flow,
    '--local-root', '/flows/' . $flow . '/'];
if ($cfg['optimize']) {
    $argv[] = '--optimize';
    $argv[] = '--responsive';
}
if ($cfg['cdn_publish']) {
    $argv[] = '--publish';
}
if ($cfg['cdn_rewrite']) {
    $argv[] = '--rewrite';
}

$pipeline = ['ok' => true];
if ($cfg['optimize'] || $cfg['cdn_publish']) {
    $pipeline = runPython($argv, $docRoot);
    if (!empty($pipeline['warnings'])) {
        $warnings = array_merge($warnings, (array)$pipeline['warnings']);
    }
    foreach (['optimize', 'responsive', 'publish', 'rewrite'] as $k) {
        if (!empty($pipeline[$k]['warnings'])) {
            $warnings = array_merge($warnings, (array)$pipeline[$k]['warnings']);
        }
    }
}

// ---- rebuild the KK package so kk/ matches (assets + any CDN rewrite)
$kkResult = ['ok' => true];
if (!empty($manifest['kk'])) {
    $kkResult = runKkFormat($dir, $docRoot);
    $warnings = array_merge($warnings, $kkResult['warnings'] ?? []);
}

ai_audit_append([
    'kind'   => 'flow-assets',
    'action' => 'save',
    'flow'   => $flow,
    'config' => $cfg,
]);

$opt = $pipeline['optimize'] ?? null;
$pub = $pipeline['publish'] ?? null;
echo json_encode([
    'ok'          => true,
    'flow'        => $flow,
    'config'      => $cfg,
    'saved_bytes' => $opt['saved_bytes'] ?? 0,
    'webp_created' => $opt['webp_created'] ?? 0,
    'images_optimized' => $opt['images_optimized'] ?? 0,
    'css_minified' => $opt['css_minified'] ?? 0,
    'js_minified' => $opt['js_minified'] ?? 0,
    'uploaded'    => $pub['uploaded'] ?? 0,
    'asset_base'  => $pipeline['asset_base'] ?? ($pub['asset_base'] ?? null),
    'rebuilt'     => !empty($manifest['kk']) && !empty($kkResult['ok']),
    'warnings'    => array_values(array_unique(array_filter($warnings))),
]);
