<?php
declare(strict_types=1);

/**
 * flow-plans.php — read/write per-flow plan configuration.
 *
 *   GET  ?flow=<slug>  -> { ok, flow, brand, library_plans, config, detected_plan_ids, tokens }
 *   POST { flow, index_register_token, plans:[{ id, enabled, ...overrides }] }
 *                        -> saves flow.json plans_config, patches bundle.js, re-runs kk_format.py
 *
 * Plan prices/points are merged from the central Plan Library with any per-flow
 * overrides, then written into plans-pick-your-plan/js/bundle.js before KK format.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/lib/checkout-fs.php';
require_once __DIR__ . '/lib/kk-tokens.php';

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

function loadPlanLibrary(): array
{
    $path = cb_data_dir() . '/plan-library.json';
    if (!is_file($path)) {
        return ['plans' => []];
    }
    $doc = json_decode((string)file_get_contents($path), true);
    return is_array($doc) ? $doc : ['plans' => []];
}

function findBundleSample(string $flowDir): ?string
{
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($flowDir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getFilename() !== 'bundle.js') {
            continue;
        }
        $path = str_replace('\\', '/', $file->getPathname());
        if (strpos($path, '/kk/') !== false) {
            continue;
        }
        if (strpos($path, 'plans-pick-your-plan') === false) {
            continue;
        }
        return (string)file_get_contents($file->getPathname());
    }
    return null;
}

function detectPlanIds(?string $bundleText): array
{
    if ($bundleText === null || $bundleText === '') {
        return [];
    }
    if (!preg_match_all('/\{id:"([a-z0-9-]+)"/', $bundleText, $m)) {
        return [];
    }
    return array_values(array_unique($m[1]));
}

function libraryById(array $doc): array
{
    $out = [];
    foreach ($doc['plans'] ?? [] as $p) {
        if (!empty($p['id'])) {
            $out[$p['id']] = $p;
        }
    }
    return $out;
}

function mergePlanEntry(array $libraryById, array $entry): ?array
{
    $id = strtolower(trim((string)($entry['id'] ?? '')));
    if ($id === '' || !isset($libraryById[$id])) {
        return null;
    }
    $base = $libraryById[$id];
    $fields = [
        'display_name', 'tag', 'badge', 'points',
        'monthly_price', 'monthly_strike', 'yearly_price', 'yearly_strike',
        'lifetime_price', 'lifetime_strike',
    ];
    foreach ($fields as $f) {
        if (array_key_exists($f, $entry) && $entry[$f] !== '' && $entry[$f] !== null) {
            $base[$f] = $entry[$f];
        }
    }
    if (!empty($entry['offer_tokens']) && is_array($entry['offer_tokens'])) {
        $tokens = $base['offer_tokens'] ?? ['monthly' => null, 'yearly' => null, 'lifetime' => null];
        foreach (['monthly', 'yearly', 'lifetime'] as $slot) {
            if (!empty($entry['offer_tokens'][$slot])) {
                $tokens[$slot] = (string)$entry['offer_tokens'][$slot];
            }
        }
        $base['offer_tokens'] = $tokens;
    }
    $base['enabled'] = !empty($entry['enabled']);
    return $base;
}

function resolvePlansConfig(array $manifest, array $libraryDoc, ?string $bundleText): array
{
    $byId = libraryById($libraryDoc);
    $detected = detectPlanIds($bundleText);
    $saved = $manifest['plans_config'] ?? [];
    $savedPlans = (is_array($saved) && isset($saved['plans']) && is_array($saved['plans']))
        ? $saved['plans'] : null;

    $indexToken = (is_array($saved) && !empty($saved['index_register_token']))
        ? (string)$saved['index_register_token'] : 'registercheckout';
    if (!in_array($indexToken, KK_TOKENS, true)) {
        $indexToken = 'registercheckout';
    }

    if ($savedPlans !== null) {
        $resolved = [];
        foreach ($savedPlans as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $merged = mergePlanEntry($byId, $entry);
            if ($merged !== null) {
                $resolved[] = $merged;
            }
        }
        return [
            'index_register_token' => $indexToken,
            'plans' => $resolved,
            'detected_plan_ids' => $detected,
        ];
    }

    $seedIds = $detected ?: array_map(
        static fn(array $p): string => (string)$p['id'],
        array_values(array_filter($libraryDoc['plans'] ?? [], static fn($p) => !empty($p['id'])))
    );
    $resolved = [];
    foreach ($seedIds as $pid) {
        if (!isset($byId[$pid])) {
            continue;
        }
        $p = $byId[$pid];
        $p['enabled'] = true;
        $resolved[] = $p;
    }
    return [
        'index_register_token' => $indexToken,
        'plans' => $resolved,
        'detected_plan_ids' => $detected,
    ];
}

function sanitizePlanEntry(array $raw, array $libraryById): array
{
    $id = strtolower(trim((string)($raw['id'] ?? '')));
    if ($id === '' || !isset($libraryById[$id])) {
        fail('Unknown plan id: ' . $id);
    }
    $enabled = !empty($raw['enabled']);
    $out = ['id' => $id, 'enabled' => $enabled];
    $priceRe = '/^[0-9][0-9,]*(\.[0-9]{1,2})?$/';
    foreach (['points', 'monthly_price', 'monthly_strike', 'yearly_price', 'yearly_strike', 'lifetime_price', 'lifetime_strike'] as $f) {
        if (!array_key_exists($f, $raw)) {
            continue;
        }
        $v = trim((string)$raw[$f]);
        if ($v === '') {
            continue;
        }
        if (!preg_match($priceRe, ltrim($v, '$'))) {
            fail("Invalid value for $f on plan $id");
        }
        $out[$f] = ltrim($v, '$');
    }
    if (!empty($raw['offer_tokens']) && is_array($raw['offer_tokens'])) {
        $tokens = [];
        foreach (['monthly', 'yearly', 'lifetime'] as $slot) {
            $t = $raw['offer_tokens'][$slot] ?? null;
            if ($t === null || $t === '') {
                continue;
            }
            $t = (string)$t;
            if (!in_array($t, KK_TOKENS, true)) {
                fail("Unknown offer token for $id.$slot: $t");
            }
            $tokens[$slot] = $t;
        }
        if ($tokens) {
            $out['offer_tokens'] = $tokens;
        }
    }
    return $out;
}

function runKkFormat(string $flowDir, string $docRoot): array
{
    $python = '/usr/bin/python3';
    $script = $docRoot . '/scripts/kk_format.py';
    if (!is_file($script)) {
        fail('KK formatter missing: ' . $script, 500);
    }
    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script)
        . ' --flow-dir ' . escapeshellarg($flowDir)
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
            if (is_array($try)) {
                $json = $try;
                break;
            }
        }
    }
    if (!is_array($json)) {
        fail('KK format failed: ' . substr($trimmed, 0, 500), 500);
    }
    if (empty($json['ok'])) {
        // Hard gate blocked the rebuild — surface every law violation so the
        // user can see exactly which plan/price/token combination is wrong.
        $violations = isset($json['violations']) && is_array($json['violations'])
            ? $json['violations'] : [];
        $msg = (string)($json['error'] ?? 'KK build blocked');
        if ($violations) {
            $msg .= ' — ' . implode(' | ', array_slice($violations, 0, 8));
        }
        fail($msg, 422);
    }
    return $json;
}

function runApplyFlowPlans(string $flowDir, string $docRoot): array
{
    $python = '/usr/bin/python3';
    $script = $docRoot . '/scripts/apply_flow_plans.py';
    if (!is_file($script)) {
        return ['ok' => false, 'warnings' => ['apply_flow_plans.py missing']];
    }
    $cmd = escapeshellarg($python) . ' -c '
        . escapeshellarg(
            'import json,sys;'
            . 'sys.path.insert(0,' . json_encode($docRoot . '/scripts') . ');'
            . 'import apply_flow_plans as afp;'
            . 'print(json.dumps(afp.apply_flow_plans('
            . json_encode($flowDir) . ',' . json_encode($docRoot) . ')))'
        )
        . ' 2>&1';
    $output = shell_exec($cmd);
    $json = json_decode(trim((string)$output), true);
    return is_array($json) ? $json : ['ok' => false, 'warnings' => ['Could not parse apply_flow_plans output']];
}

// -------------------------------------------------------------------------- GET
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $flow = (string)($_GET['flow'] ?? '');
    $dir = flowDir($flow, $flowsDir);
    $manifest = readManifest($dir);
    $library = loadPlanLibrary();
    $bundleSample = findBundleSample($dir);
    $config = resolvePlansConfig($manifest, $library, $bundleSample);
    $brand = (string)($manifest['brand'] ?? 'aipu');

    $libraryPlans = array_values(array_filter(
        $library['plans'] ?? [],
        static function (array $p) use ($brand): bool {
            $brands = $p['brands'] ?? ['aipu', 'omni'];
            return in_array($brand, $brands, true);
        }
    ));
    usort($libraryPlans, static fn(array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

    echo json_encode([
        'ok' => true,
        'flow' => $flow,
        'brand' => $brand,
        'kk_ready' => !empty($manifest['kk']) && is_dir($dir . '/kk'),
        'has_bundle' => $bundleSample !== null,
        'library_plans' => $libraryPlans,
        'detected_plan_ids' => $config['detected_plan_ids'],
        'config' => [
            'index_register_token' => $config['index_register_token'],
            'plans' => $config['plans'],
        ],
        'tokens' => KK_TOKENS,
        'token_labels' => TOKEN_LABELS,
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

$library = loadPlanLibrary();
$byId = libraryById($library);

$indexToken = (string)($input['index_register_token'] ?? 'registercheckout');
if (!in_array($indexToken, KK_TOKENS, true)) {
    fail('Invalid index_register_token');
}

$rawPlans = $input['plans'] ?? [];
if (!is_array($rawPlans)) {
    fail('Invalid plans payload');
}

$cleanPlans = [];
foreach ($rawPlans as $entry) {
    if (!is_array($entry)) {
        continue;
    }
    $cleanPlans[] = sanitizePlanEntry($entry, $byId);
}

$manifest['plans_config'] = [
    'index_register_token' => $indexToken,
    'plans' => $cleanPlans,
];

$tmp = $dir . '/flow.json.tmp';
$ok = @file_put_contents($tmp, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
if ($ok === false || !@rename($tmp, $dir . '/flow.json')) {
    @unlink($tmp);
    fail('Could not write flow.json (permissions?)', 500);
}

$applyResult = runApplyFlowPlans($dir, $docRoot);
$kkResult = runKkFormat($dir, $docRoot);

echo json_encode([
    'ok' => true,
    'flow' => $flow,
    'config' => [
        'index_register_token' => $indexToken,
        'plans' => $cleanPlans,
        'applied_plan_ids' => $applyResult['applied'] ?? [],
    ],
    'rebuilt' => true,
    'apply' => $applyResult,
    'warnings' => array_values(array_unique(array_merge(
        $applyResult['warnings'] ?? [],
        $kkResult['warnings'] ?? []
    ))),
]);
