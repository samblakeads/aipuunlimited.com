<?php
declare(strict_types=1);

/**
 * create-flow.php — HTTP wrapper around scripts/build_flow.py
 *
 * POST (JSON or form-encoded):
 *   lander_collection   one of: aipu-landers | omnirogue-landers
 *   lander_name         folder name inside that collection
 *   checkout_collection one of: aipu-checkouts | omnirogue-checkouts
 *   checkout_name       folder name inside that collection
 *   flow_name           optional output slug
 *
 * Returns JSON: { ok, flow, url, checkout_url, ... } or { ok:false, error }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Builds two brands + auto-KK-formats both in one request — comfortably
// longer than the default 30s PHP cap on some SAPIs.
@set_time_limit(900);

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

// Accept JSON body or form-encoded
$raw = file_get_contents('php://input');
$input = [];
if ($raw !== '' && $raw[0] === '{') {
    $input = json_decode($raw, true) ?: [];
} else {
    $input = $_POST;
}

// 'sales-pages' / 'checkout-pages' are the brand-neutral source collections; the
// rest are the legacy branded ones (kept for back-compat).
$landerCollections   = ['sales-pages', 'aipu-landers', 'omnirogue-landers'];
$checkoutCollections = ['checkout-pages', 'aipu-checkouts', 'omnirogue-checkouts'];
$neutralCollections  = ['sales-pages', 'checkout-pages'];

$landerCol   = (string)($input['lander_collection'] ?? '');
$landerName  = (string)($input['lander_name'] ?? '');
$checkoutCol = (string)($input['checkout_collection'] ?? '');
$checkoutName = (string)($input['checkout_name'] ?? '');
$flowName    = (string)($input['flow_name'] ?? '');
$brandIn     = (string)($input['brand'] ?? '');

// One-page flows: a lander with no checkout (sales-only) or a checkout with no
// lander (checkout-only). At least one source is required (Law 3).
$wantLander   = $landerName !== '' || $landerCol !== '';
$wantCheckout = $checkoutName !== '' || $checkoutCol !== '';
if (!$wantLander && !$wantCheckout) {
    fail('Pick a sales page, a checkout, or both');
}

if ($wantLander && !in_array($landerCol, $landerCollections, true)) {
    fail('Invalid lander collection');
}
if ($wantCheckout && !in_array($checkoutCol, $checkoutCollections, true)) {
    fail('Invalid checkout collection');
}

// Folder names must be simple (no traversal)
$safe = '/^[A-Za-z0-9._-]+$/';
if ($wantLander && !preg_match($safe, $landerName)) {
    fail('Invalid lander name');
}
if ($wantCheckout && !preg_match($safe, $checkoutName)) {
    fail('Invalid checkout name');
}

$landerReal = false;
$checkoutReal = false;
if ($wantLander) {
    $landerReal = realpath($docRoot . '/' . $landerCol . '/' . $landerName);
    if ($landerReal === false || strpos($landerReal, $docRoot) !== 0 || !is_dir($landerReal)) {
        fail('Lander not found: ' . $landerName);
    }
    if (!is_file($landerReal . '/index.html') && !is_file($landerReal . '/index.php')) {
        fail('Lander has no index.html/index.php');
    }
}
if ($wantCheckout) {
    $checkoutReal = realpath($docRoot . '/' . $checkoutCol . '/' . $checkoutName);
    if ($checkoutReal === false || strpos($checkoutReal, $docRoot) !== 0 || !is_dir($checkoutReal)) {
        fail('Checkout not found: ' . $checkoutName);
    }
    if (!is_file($checkoutReal . '/index.html')) {
        fail('Checkout has no index.html');
    }
}

// A "Sales Flow" is brand-paired: we always build BOTH brands from the same
// source(s) so the previews UI can preview / configure / download either brand
// from one card. The source brand is auto-detected per directory inside
// build_flow.py (neutral sales-pages/checkout-pages -> omni canonical); the
// --brand flag re-skins the body, so any source can produce both brands.

// Base flow name from the chosen source(s) — the shared pairing key.
if ($flowName === '') {
    if ($wantLander && $wantCheckout) {
        $flowName = $landerName . '__' . $checkoutName;
    } else {
        $flowName = $wantLander ? $landerName : $checkoutName;
    }
}
// Slugify (mirror of python slugify)
$base = strtolower($flowName);
$base = preg_replace('/[^a-z0-9._-]+/', '-', $base);
$base = trim(preg_replace('/-{2,}/', '-', $base), '-_.');
if ($base === '') {
    $base = 'flow';
}
// Defensive: never let a base end in a brand suffix (would corrupt grouping).
$base = preg_replace('/__(aipu|omni)$/', '', $base);
if ($base === '') {
    $base = 'flow';
}

$flowsDir = $docRoot . '/flows';
if (!is_dir($flowsDir)) {
    @mkdir($flowsDir, 0775, true);
}
if (!is_writable($flowsDir)) {
    fail('Flows directory is not writable by the web server (' . $flowsDir . ')', 500);
}

$python = '/usr/bin/python3';
$script = $docRoot . '/scripts/build_flow.py';
if (!is_file($script)) {
    fail('Build script missing: ' . $script, 500);
}

// The build/KK/rollback pipeline is shared with the async worker
// (build-flow-worker.php) so there is a single source of truth. This endpoint
// runs it synchronously — kept as a fallback for any direct caller.
require_once __DIR__ . '/lib/build_flow_run.php';

$result = bfr_build_flow([
    'base'         => $base,
    'flowsDir'     => $flowsDir,
    'docRoot'      => $docRoot,
    'python'       => $python,
    'script'       => $script,
    'landerReal'   => $landerReal,
    'checkoutReal' => $checkoutReal,
]);

if (empty($result['ok'])) {
    http_response_code(500);
}
echo json_encode($result);
