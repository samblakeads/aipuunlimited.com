<?php
declare(strict_types=1);

/**
 * duplicate-item.php — HTTP wrapper around scripts/brand_convert.py.
 *
 * Duplicates a lander / checkout / flow into the other brand (AIPU <-> OmniRogue),
 * auto-applying the right header/footer chrome, logos and legal links.
 *
 * POST JSON:
 *   kind        "lander" | "checkout" | "flow"
 *   collection  one of the four collections (required for lander/checkout)
 *   name        source folder name
 *   dst_brand   "aipu" | "omni" (optional; defaults to the opposite brand)
 *
 * Returns the JSON produced by brand_convert.py, e.g.
 *   { ok, kind, item, collection, web_path, brand, warnings, ... }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    fail('POST required', 405);
}

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__));
if ($docRoot === false) {
    fail('Cannot resolve document root', 500);
}

$raw = file_get_contents('php://input');
$input = ($raw !== '' && $raw[0] === '{') ? (json_decode($raw, true) ?: []) : $_POST;

$kind      = (string)($input['kind'] ?? '');
$name      = (string)($input['name'] ?? '');
$dstBrand  = (string)($input['dst_brand'] ?? '');
$collection = (string)($input['collection'] ?? '');

if (!in_array($kind, ['lander', 'checkout', 'flow'], true)) {
    fail('Invalid kind');
}
if (!preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
    fail('Invalid name');
}
if ($dstBrand !== '' && !in_array($dstBrand, ['aipu', 'omni'], true)) {
    fail('Invalid destination brand');
}

$collectionDirs = [
    'sales-pages'         => 'lander',
    'checkout-pages'      => 'checkout',
    'aipu-landers'        => 'lander',
    'omnirogue-landers'   => 'lander',
    'aipu-checkouts'      => 'checkout',
    'omnirogue-checkouts' => 'checkout',
];

if ($kind === 'flow') {
    $srcDir = $docRoot . '/flows/' . $name;
} else {
    if (!isset($collectionDirs[$collection])) {
        fail('Invalid collection');
    }
    if ($collectionDirs[$collection] !== $kind) {
        fail('Collection does not match kind');
    }
    $srcDir = $docRoot . '/' . $collection . '/' . $name;
}

$srcReal = realpath($srcDir);
if ($srcReal === false || !is_dir($srcReal)) {
    fail('Source not found: ' . $name, 404);
}

$python = '/usr/bin/python3';
$script = $docRoot . '/scripts/brand_convert.py';
if (!is_file($script)) {
    fail('Convert script missing: ' . $script, 500);
}

$cmd = escapeshellarg($python) . ' ' . escapeshellarg($script)
    . ' --src-dir ' . escapeshellarg($srcReal)
    . ' --kind ' . escapeshellarg($kind)
    . ' --docroot ' . escapeshellarg($docRoot);
if ($dstBrand !== '') {
    $cmd .= ' --dst-brand ' . escapeshellarg($dstBrand);
}
$cmd .= ' 2>&1';

$output = shell_exec($cmd);
if ($output === null || trim((string)$output) === '') {
    fail('Convert produced no output (is python3 available to the web server?)', 500);
}

// brand_convert.py prints one JSON line; grab the last parseable JSON object.
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
    fail('Could not parse convert output: ' . substr($trimmed, 0, 500), 500);
}

if (empty($json['ok'])) {
    http_response_code(500);
}
echo json_encode($json);
