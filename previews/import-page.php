<?php
declare(strict_types=1);

/**
 * import-page.php — HTTP wrapper around scripts/import_page.py.
 *
 * Imports a lander / checkout from either:
 *   - a URL (POST JSON: { url, kind, collection, name?, source_url? }), or
 *   - an uploaded HTML file (POST multipart/form-data with `file` plus the same
 *     text fields).
 *
 * Either way, the destination collection determines the brand chrome and
 * rebranding applied to the imported page.
 *
 * Returns the JSON produced by import_page.py.
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

$ctype = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
$isMultipart = str_contains($ctype, 'multipart/form-data');

$input = [];
$uploadedFile = null;

if ($isMultipart) {
    $input = $_POST;

    if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
        fail('Missing file upload');
    }
    $f = $_FILES['file'];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $msg = match ((int)$f['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large',
            UPLOAD_ERR_PARTIAL                        => 'Upload was interrupted',
            UPLOAD_ERR_NO_FILE                        => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR                     => 'Server has no temp directory',
            UPLOAD_ERR_CANT_WRITE                     => 'Server could not write the upload',
            UPLOAD_ERR_EXTENSION                      => 'Upload blocked by PHP extension',
            default                                   => 'Upload failed (code ' . (int)$f['error'] . ')',
        };
        fail($msg);
    }

    $tmpPath = (string)($f['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        fail('Invalid upload');
    }

    $size = (int)($f['size'] ?? 0);
    if ($size <= 0)              fail('Empty file');
    if ($size > 20 * 1024 * 1024) fail('File too large (max 20 MB)');

    $origName = (string)($f['name'] ?? 'upload.html');
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['html', 'htm'], true)) {
        fail('Only .html / .htm files are accepted');
    }

    // Sanity check — first KB must look like HTML.
    $peek = @file_get_contents($tmpPath, false, null, 0, 2048) ?: '';
    $peekLow = strtolower($peek);
    $looksHtml = str_contains($peekLow, '<html') || str_contains($peekLow, '<!doctype html')
        || str_contains($peekLow, '<head') || str_contains($peekLow, '<body');
    if (!$looksHtml) {
        fail('Uploaded file does not look like HTML');
    }

    // Move into our own temp location so the script's working dir / perms work.
    $safeDir = $docRoot . '/previews/.import-tmp';
    if (!is_dir($safeDir) && !@mkdir($safeDir, 0775, true) && !is_dir($safeDir)) {
        fail('Could not create temp directory', 500);
    }
    $uploadedFile = $safeDir . '/upload-' . bin2hex(random_bytes(8)) . '.html';
    if (!@move_uploaded_file($tmpPath, $uploadedFile)) {
        fail('Could not stash uploaded file', 500);
    }
    @chmod($uploadedFile, 0664);
} else {
    $raw = file_get_contents('php://input');
    $input = ($raw !== '' && $raw[0] === '{') ? (json_decode($raw, true) ?: []) : $_POST;
}

$url        = trim((string)($input['url'] ?? ''));
$sourceUrl  = trim((string)($input['source_url'] ?? ''));
$kind       = (string)($input['kind'] ?? '');
$collection = (string)($input['collection'] ?? '');
$name       = (string)($input['name'] ?? '');

if (!in_array($kind, ['lander', 'checkout'], true)) {
    if ($uploadedFile && file_exists($uploadedFile)) @unlink($uploadedFile);
    fail('Invalid kind');
}

$validCollections = ['sales-pages', 'checkout-pages', 'aipu-landers', 'omnirogue-landers', 'aipu-checkouts', 'omnirogue-checkouts'];
if (!in_array($collection, $validCollections, true)) {
    if ($uploadedFile && file_exists($uploadedFile)) @unlink($uploadedFile);
    fail('Invalid collection');
}
$isCheckoutCol = $collection === 'checkout-pages' || str_ends_with($collection, '-checkouts');
if (($kind === 'checkout') !== $isCheckoutCol) {
    if ($uploadedFile && file_exists($uploadedFile)) @unlink($uploadedFile);
    fail('Collection does not match kind');
}

if ($name !== '' && !preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
    if ($uploadedFile && file_exists($uploadedFile)) @unlink($uploadedFile);
    fail('Invalid name');
}

// Validate URL inputs (only when not a file upload, or when a source_url hint is provided).
$validateUrl = static function (string $u): bool {
    $p = parse_url($u);
    return is_array($p)
        && !empty($p['scheme']) && !empty($p['host'])
        && in_array(strtolower($p['scheme']), ['http', 'https'], true);
};

if (!$uploadedFile) {
    if ($url === '' || !$validateUrl($url)) {
        fail('Invalid URL (must be http or https)');
    }
} else {
    if ($sourceUrl !== '' && !$validateUrl($sourceUrl)) {
        if (file_exists($uploadedFile)) @unlink($uploadedFile);
        fail('Invalid source URL');
    }
}

$python = '/usr/bin/python3';
$script = $docRoot . '/scripts/import_page.py';
if (!is_file($script)) {
    if ($uploadedFile && file_exists($uploadedFile)) @unlink($uploadedFile);
    fail('Import script missing: ' . $script, 500);
}

$cmd = escapeshellarg($python) . ' ' . escapeshellarg($script)
    . ' --kind ' . escapeshellarg($kind)
    . ' --collection ' . escapeshellarg($collection)
    . ' --docroot ' . escapeshellarg($docRoot);
if ($uploadedFile) {
    $cmd .= ' --file ' . escapeshellarg($uploadedFile);
    if ($sourceUrl !== '') {
        $cmd .= ' --source-url ' . escapeshellarg($sourceUrl);
    }
} else {
    $cmd .= ' --url ' . escapeshellarg($url);
}
if ($name !== '') {
    $cmd .= ' --name ' . escapeshellarg($name);
}
$cmd .= ' 2>&1';

$output = shell_exec($cmd);

if ($uploadedFile && file_exists($uploadedFile)) {
    @unlink($uploadedFile);
}

if ($output === null || trim((string)$output) === '') {
    fail('Import produced no output (is python3 available to the web server?)', 500);
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
    fail('Could not parse import output: ' . substr($trimmed, 0, 500), 500);
}

if (empty($json['ok'])) {
    http_response_code(500);
}
echo json_encode($json);
