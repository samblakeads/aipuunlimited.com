<?php
declare(strict_types=1);

/**
 * kk-download.php — stream the KK-formatted package(s) for a flow as a .zip.
 *
 * Brand-paired flows live in two sibling folders (<base>__aipu / <base>__omni).
 * Supported requests:
 *   ?flow=<slug>                 single flow folder (back-compat)
 *   ?base=<base>&brand=aipu      the AIPU build of a paired flow
 *   ?base=<base>&brand=omni      the OmniRogue build
 *   ?base=<base>&brand=all       BOTH builds in one zip (two top folders)
 *
 * Every package is HARD-GATED: each is re-validated against the 7 KK laws
 * (scripts/qa_checks.py) right before zipping. If any package fails QC the whole
 * download is refused — a failing package must never ship.
 */

function fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__));
if ($docRoot === false) {
    fail('Cannot resolve document root', 500);
}

$flowsDir = realpath($docRoot . '/flows');
if ($flowsDir === false) {
    fail('No flows directory', 404);
}

// ----------------------------------------------------------- resolve targets
$flowParam = (string)($_GET['flow'] ?? '');
$baseParam = (string)($_GET['base'] ?? '');
$brandParam = strtolower((string)($_GET['brand'] ?? 'all'));

$targetSlugs = [];
$comboName = null;

if ($flowParam !== '') {
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $flowParam)) {
        fail('Invalid flow name');
    }
    $targetSlugs[] = $flowParam;
} elseif ($baseParam !== '') {
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $baseParam)) {
        fail('Invalid base name');
    }
    if (!in_array($brandParam, ['aipu', 'omni', 'all'], true)) {
        fail('Invalid brand (expected aipu, omni or all)');
    }
    $brands = $brandParam === 'all' ? ['aipu', 'omni'] : [$brandParam];
    foreach ($brands as $b) {
        $slug = $baseParam . '__' . $b;
        if (is_dir($flowsDir . '/' . $slug . '/kk')) {
            $targetSlugs[] = $slug;
        }
    }
    if (!$targetSlugs) {
        fail('No KK packages found for "' . $baseParam . '" — run KK Format first', 404);
    }
    $comboName = $brandParam === 'all' ? ($baseParam . '-kk-all') : null;
} else {
    fail('Provide ?flow=<slug> or ?base=<base>&brand=aipu|omni|all');
}

// ------------------------------------------------------- collect + QC each pkg
$python = '/usr/bin/python3';
$qaScript = $docRoot . '/scripts/qa_checks.py';
if (!is_file($qaScript)) {
    fail('QC script missing — refusing to ship an unvalidated KK package', 500);
}

$packages = []; // [ ['kkName' => ..., 'kkDir' => ...], ... ]
foreach ($targetSlugs as $slug) {
    $flowDir = realpath($flowsDir . '/' . $slug);
    if ($flowDir === false || dirname($flowDir) !== $flowsDir || !is_dir($flowDir)) {
        fail('Flow not found: ' . $slug, 404);
    }
    $kkDir = realpath($flowDir . '/kk');
    if ($kkDir === false || !is_dir($kkDir)) {
        fail('No KK package for "' . $slug . '" — run KK Format first', 404);
    }

    // Top-level folder name from the manifest (falls back to slug).
    $kkName = $slug;
    $manifest = $flowDir . '/flow.json';
    if (is_file($manifest)) {
        $m = json_decode((string)file_get_contents($manifest), true);
        if (is_array($m) && !empty($m['kk_name'])) {
            $kkName = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string)$m['kk_name']);
        }
    }

    // HARD GATE: re-validate against the 7 KK laws right now.
    $qaOut = shell_exec(
        escapeshellarg($python) . ' ' . escapeshellarg($qaScript)
        . ' --kk-dir ' . escapeshellarg($kkDir)
        . ' --docroot ' . escapeshellarg($docRoot) . ' 2>&1'
    );
    $qa = null;
    if (is_string($qaOut) && $qaOut !== '') {
        $lines = preg_split('/\r?\n/', trim($qaOut));
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $try = json_decode(trim($lines[$i]), true);
            if (is_array($try)) { $qa = $try; break; }
        }
    }
    if (!is_array($qa) || empty($qa['ok']) || !is_array($qa['qc'] ?? null)) {
        fail('QC could not run for "' . $slug . '" — refusing to ship an unvalidated KK package: '
            . substr((string)$qaOut, 0, 200), 500);
    }
    if (($qa['qc']['status'] ?? '') === 'fail') {
        http_response_code(409);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => 'KK package "' . $slug . '" failed QC — download blocked. '
                . 'Fix the violations and re-run KK Format.',
            'flow' => $slug,
            'violations' => $qa['qc']['errors'] ?? [],
        ]);
        exit;
    }

    $packages[] = ['kkName' => $kkName, 'kkDir' => $kkDir];
}

// Guard against two builds resolving to the same top folder name in one zip.
if (count($packages) > 1) {
    $seen = [];
    foreach ($packages as &$pkg) {
        $n = $pkg['kkName'];
        if (isset($seen[$n])) {
            $pkg['kkName'] = $n . '-' . count($seen);
        }
        $seen[$pkg['kkName']] = true;
    }
    unset($pkg);
}

// ------------------------------------------------------------------- zip it up
if (!class_exists('ZipArchive')) {
    fail('ZipArchive PHP extension not available', 500);
}

$tmp = tempnam(sys_get_temp_dir(), 'kkzip');
if ($tmp === false) {
    fail('Could not allocate a temp file for the zip', 500);
}
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fail('Could not create zip', 500);
}

// Store deterministic, portable Unix permissions on every entry so the package
// extracts cleanly on KowboyKit / any host: files owner-writable (0644), dirs
// traversable but NOT world-writable (0755). Archive metadata only — never
// touches on-disk permissions.
$fileAttr = (0100644 << 16); // regular file, rw-r--r--
$dirAttr  = (040755 << 16);  // directory,    rwxr-xr-x
$setPerm = static function (ZipArchive $zip, string $name, bool $isDir) use ($fileAttr, $dirAttr): void {
    if (method_exists($zip, 'setExternalAttributesName')) {
        $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, $isDir ? $dirAttr : $fileAttr);
    }
};

foreach ($packages as $pkg) {
    $kkName = $pkg['kkName'];
    $kkDir  = $pkg['kkDir'];

    $rootEntry = $kkName . '/';
    $zip->addEmptyDir($rootEntry);
    $setPerm($zip, $rootEntry, true);

    $entries = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($kkDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        $rel = str_replace('\\', '/', ltrim(substr($file->getPathname(), strlen($kkDir)), '/\\'));
        if ($rel === '') {
            continue;
        }
        $entries[] = ['abs' => $file->getPathname(), 'rel' => $rel, 'dir' => $file->isDir()];
    }
    usort($entries, static function (array $a, array $b): int {
        if ($a['dir'] !== $b['dir']) {
            return $a['dir'] ? -1 : 1; // directories first
        }
        return strcmp($a['rel'], $b['rel']);
    });

    foreach ($entries as $e) {
        if ($e['dir']) {
            $zipPath = $kkName . '/' . $e['rel'] . '/';
            $zip->addEmptyDir($zipPath);
            $setPerm($zip, $zipPath, true);
        } else {
            $zipPath = $kkName . '/' . $e['rel'];
            $zip->addFile($e['abs'], $zipPath);
            $setPerm($zip, $zipPath, false);
        }
    }
}

if ($zip->close() !== true) {
    @unlink($tmp);
    fail('Failed to finalize zip archive', 500);
}

$downloadName = $comboName ?? ($packages[0]['kkName'] . '-kk');
$size = filesize($tmp);
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadName . '.zip"');
header('Content-Length: ' . $size);
header('Cache-Control: no-store');
readfile($tmp);
@unlink($tmp);
