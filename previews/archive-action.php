<?php
declare(strict_types=1);

/**
 * archive-action.php — restore or permanently purge an archived entry.
 *
 * POST JSON:
 *   { "action": "restore", "id": "<archive-entry-id>" }
 *   { "action": "purge",   "id": "<archive-entry-id>" }
 *
 * Returns: { ok, restored?: {type,collection,name} } or { ok:false, error }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/lib/archive.php';

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

$action = (string)($input['action'] ?? '');
$id = (string)($input['id'] ?? '');
if ($id === '') {
    fail('id required');
}

if ($action === 'restore') {
    $res = archive_restore($id, $docRoot);
    if (!$res['ok']) {
        fail($res['error'] ?? 'restore failed');
    }
    echo json_encode(['ok' => true, 'restored' => $res['restored'] ?? null]);
} elseif ($action === 'purge') {
    $res = archive_purge($id, $docRoot);
    if (!$res['ok']) {
        fail($res['error'] ?? 'delete failed');
    }
    echo json_encode(['ok' => true]);
} else {
    fail('unknown action');
}
