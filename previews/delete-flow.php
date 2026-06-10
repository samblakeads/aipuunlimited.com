<?php
declare(strict_types=1);

/**
 * delete-flow.php — "delete" a built flow folder under /flows.
 *
 * Soft delete: the flow folder is moved into the Archive (htdocs/.archive) and
 * can be restored later from the Archive view. See lib/archive.php.
 *
 * POST { flow: "<slug>" }
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

$flow = (string)($input['flow'] ?? '');

[$id, $err] = archive_store('flow', '', $flow, $docRoot);
if ($id === null) {
    fail('Could not archive flow "' . $flow . '": ' . $err, $err === 'not found' ? 404 : 400);
}

echo json_encode(['ok' => true, 'flow' => $flow, 'archived_id' => $id]);
