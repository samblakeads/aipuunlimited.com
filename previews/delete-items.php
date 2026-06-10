<?php
declare(strict_types=1);

/**
 * delete-items.php — bulk "delete" previews entries (flows + collection items).
 *
 * Deletes are SOFT: each target folder is moved into the Archive (htdocs/.archive)
 * and can be restored later from the Archive view. See lib/archive.php.
 *
 * POST JSON:
 *   { "items": [ { "type": "flow", "name": "<slug>" },
 *                { "type": "item", "collection": "aipu-landers", "name": "<slug>" } ] }
 *
 * Returns: { ok, deleted: [...names], archived: [{name,id}], errors: [{name,error}] }
 * Each target is validated to be a direct child of an allow-listed directory
 * inside the document root before anything is moved.
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

$items = $input['items'] ?? null;
if (!is_array($items) || count($items) === 0) {
    fail('No items supplied');
}

$deleted = [];
$archived = [];
$errors = [];

foreach ($items as $item) {
    if (!is_array($item)) {
        $errors[] = ['name' => '?', 'error' => 'malformed item'];
        continue;
    }
    $type = (string)($item['type'] ?? '');
    $name = (string)($item['name'] ?? '');
    $collection = (string)($item['collection'] ?? '');

    [$id, $err] = archive_store($type, $collection, $name, $docRoot);
    if ($id === null) {
        $errors[] = ['name' => $name !== '' ? $name : '?', 'error' => $err];
        continue;
    }
    $deleted[] = $name;
    $archived[] = ['name' => $name, 'id' => $id];
}

echo json_encode([
    'ok' => count($errors) === 0,
    'deleted' => $deleted,
    'archived' => $archived,
    'errors' => $errors,
]);
