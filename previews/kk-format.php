<?php
declare(strict_types=1);

/**
 * kk-format.php - run scripts/kk_format.py on a built flow.
 * POST { flow: "<slug>" }  ->  JSON result.
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

$flow = (string)($input['flow'] ?? '');
if (!preg_match('/^[A-Za-z0-9._-]+$/', $flow)) {
    fail('Invalid flow name');
}

$flowsDir = realpath($docRoot . '/flows');
if ($flowsDir === false) {
    fail('Flows directory missing', 500);
}
$flowDir = realpath($flowsDir . '/' . $flow);
if ($flowDir === false || dirname($flowDir) !== $flowsDir || !is_dir($flowDir)) {
    fail('Flow not found: ' . $flow, 404);
}
// Every flow needs an index page (Law 2). Whether checkout.html is required
// depends on the flow shape (multi vs one-page) — kk_format.py enforces that.
if (!is_file($flowDir . '/index.html')) {
    fail('Flow is missing index.html — rebuild it first');
}

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
if ($output === null || $output === '') {
    fail('KK formatter produced no output', 500);
}

$json = json_decode(trim($output), true);
if (!is_array($json)) {
    $lines = preg_split('/\r?\n/', trim($output));
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $try = json_decode(trim($lines[$i]), true);
        if (is_array($try)) { $json = $try; break; }
    }
}
if (!is_array($json)) {
    fail('Could not parse KK formatter output: ' . substr(trim($output), 0, 500), 500);
}
if (empty($json['ok'])) {
    http_response_code(500);
}
echo json_encode($json);
