<?php
declare(strict_types=1);

/**
 * flow-card.php — render ONE flow card as an HTML fragment, for live-inserting a
 * freshly-generated flow into the grid without a full page reload.
 *
 * GET ?base=<slug>  ->  the <article class="flow-card"> markup for that flow,
 *                       or 404 JSON if no flow with that base exists yet.
 *
 * Card markup is produced by lib/flow_card.php render_flow_card(), the same
 * function index.php's grid loop uses, so the inserted card is byte-identical to
 * a server-rendered one. The scan + grouping below mirror index.php exactly.
 */

function fc_fail(string $msg, int $code = 400): void
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__));
if ($docRoot === false) {
    fc_fail('Cannot resolve document root', 500);
}

$base = (string)($_GET['base'] ?? '');
// Same safe-slug shape build_flow.py / create-flow.php produce.
if ($base === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $base)) {
    fc_fail('Invalid base');
}

/**
 * Scan htdocs/flows for built per-brand flows. Mirror of index.php scanFlows().
 */
function fc_scan_flows(string $docRoot): array
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
    return $flows;
}

// Build just the one group we were asked for. (No need to sort the rest.)
$group = [
    'base' => $base,
    'variants' => [],
    'modified' => 0,
    'created' => 0,
    'flowType' => 'sales-only',
];
$found = false;
foreach (fc_scan_flows($docRoot) as $f) {
    if ($f['flowBase'] !== $base) {
        continue;
    }
    if (!$found) {
        $group['created'] = $f['created'];
        $group['flowType'] = $f['flowType'];
    }
    $found = true;
    $group['variants'][$f['brand']] = $f;
    if ($f['modified'] > $group['modified']) {
        $group['modified'] = $f['modified'];
    }
    if ($f['created'] > 0 && ($group['created'] === 0 || $f['created'] < $group['created'])) {
        $group['created'] = $f['created'];
    }
    // A multi flow takes precedence for the badge.
    if ($f['flowType'] === 'multi') {
        $group['flowType'] = 'multi';
    }
}

if (!$found || empty($group['variants'])) {
    fc_fail('No flow with base "' . $base . '" found yet', 404);
}

require_once __DIR__ . '/lib/flow_card.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
echo render_flow_card($group);
