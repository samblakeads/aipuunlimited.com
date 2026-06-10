<?php
declare(strict_types=1);

/**
 * clone-item.php — duplicate a lander / checkout / flow within the SAME brand.
 *
 * The cross-brand "Duplicate to AIPU/OmniRogue" button uses duplicate-item.php
 * which runs scripts/brand_convert.py and rewrites brand chrome. Cloning stays
 * inside the source collection: a verbatim copy of the folder, renamed to a
 * unique sibling (foo -> foo-clone, foo-clone-2, ...).
 *
 * POST JSON:
 *   kind        "lander" | "checkout" | "flow"
 *   collection  one of the four collections (required for lander/checkout)
 *   name        source folder name
 *   new_name    optional preferred clone name (defaults to "<name>-clone")
 *
 * Returns: { ok, kind, name, collection, web_path, fs_path, kk_rebuilt }
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

$kind       = (string)($input['kind'] ?? '');
$name       = (string)($input['name'] ?? '');
$collection = (string)($input['collection'] ?? '');
$newName    = trim((string)($input['new_name'] ?? ''));

if (!in_array($kind, ['lander', 'checkout', 'flow'], true)) {
    fail('Invalid kind');
}
if (!preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
    fail('Invalid name');
}
if ($newName !== '' && !preg_match('/^[A-Za-z0-9._-]+$/', $newName)) {
    fail('Invalid new_name (letters, digits, . _ - only)');
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
    $parentDir = $docRoot . '/flows';
} else {
    if (!isset($collectionDirs[$collection]) || $collectionDirs[$collection] !== $kind) {
        fail('Invalid collection for kind');
    }
    $parentDir = $docRoot . '/' . $collection;
}

$parentReal = realpath($parentDir);
if ($parentReal === false) {
    fail('Parent dir missing: ' . $parentDir, 500);
}

$srcReal = realpath($parentReal . '/' . $name);
if ($srcReal === false || dirname($srcReal) !== $parentReal || !is_dir($srcReal)) {
    fail('Source not found: ' . $name, 404);
}

// Default suffix: <name>-clone, then -clone-2, -clone-3 ...
$base = $newName !== '' ? $newName : $name . '-clone';
$candidate = $parentReal . '/' . $base;
if (file_exists($candidate)) {
    $i = 2;
    while (file_exists($parentReal . '/' . $base . '-' . $i)) {
        $i++;
    }
    $base = $base . '-' . $i;
    $candidate = $parentReal . '/' . $base;
}

function rcopy(string $src, string $dst): bool
{
    if (is_link($src)) {
        // copy the symlink target, not the link itself
        $target = readlink($src);
        return @symlink($target, $dst);
    }
    if (!is_dir($src)) {
        return @copy($src, $dst);
    }
    if (!is_dir($dst) && !@mkdir($dst, 0775, true)) {
        return false;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        $sub = $it->getSubPathname();
        $target = $dst . DIRECTORY_SEPARATOR . $sub;
        if ($file->isLink()) {
            $link = readlink($file->getPathname());
            @symlink($link, $target);
        } elseif ($file->isDir()) {
            if (!is_dir($target)) @mkdir($target, 0775, true);
        } else {
            @copy($file->getPathname(), $target);
        }
    }
    return true;
}

if (!rcopy($srcReal, $candidate)) {
    fail('Clone failed (permissions?)', 500);
}

$result = [
    'ok' => true,
    'kind' => $kind,
    'name' => $base,
    'source' => $name,
];

if ($kind === 'flow') {
    // Update flow.json with the new slug so KK output uses the cloned name as
    // its kk_name (matches what the build pipeline does for fresh flows).
    $manifestPath = $candidate . '/flow.json';
    if (is_file($manifestPath)) {
        $m = json_decode((string)@file_get_contents($manifestPath), true) ?: [];
        $m['name'] = $base;
        if (!empty($m['kk'])) {
            $m['kk_name'] = $base;
        }
        @file_put_contents(
            $manifestPath,
            json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
    // If the source had a KK package, re-run kk-format so /kk/ uses the new slug
    // for its $__web and asset paths. Old kk/ folder is replaced by the formatter.
    $kkRebuilt = false;
    if (is_dir($candidate . '/kk')) {
        $python = '/usr/bin/python3';
        $script = $docRoot . '/scripts/kk_format.py';
        if (is_file($script)) {
            $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script)
                . ' --flow-dir ' . escapeshellarg($candidate)
                . ' --docroot ' . escapeshellarg($docRoot) . ' 2>&1';
            $out = shell_exec($cmd);
            $jr = $out ? json_decode(trim((string)$out), true) : null;
            $kkRebuilt = is_array($jr) && !empty($jr['ok']);
            // Surface the QC gate result from the rebuilt KK package.
            if (is_array($jr) && isset($jr['qc'])) {
                $result['qc'] = $jr['qc'];
            }
        }
    }
    $result['collection'] = 'flows';
    $result['web_path'] = '/flows/' . $base . '/index.html';
    $result['fs_path'] = $candidate;
    $result['kk_rebuilt'] = $kkRebuilt;
} else {
    $entry = is_file($candidate . '/index.html') ? 'index.html'
           : (is_file($candidate . '/index.php') ? 'index.php' : null);
    $result['collection'] = $collection;
    $result['web_path'] = $entry ? ('/' . $collection . '/' . $base . '/' . $entry) : ('/' . $collection . '/' . $base);
    $result['fs_path'] = $candidate;
}

echo json_encode($result);
