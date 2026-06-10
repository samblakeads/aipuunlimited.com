<?php
declare(strict_types=1);

/**
 * archive-list.php — JSON list of archived landers / checkouts / flows.
 * GET → { ok, entries: [ {id, type, collection, name, archived_at}, ... ] }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/lib/archive.php';

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__));
if ($docRoot === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot resolve document root']);
    exit;
}

echo json_encode(['ok' => true, 'entries' => archive_list($docRoot)]);
