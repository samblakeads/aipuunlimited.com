<?php
declare(strict_types=1);

/**
 * generate-flow.php — async launcher for flow generation.
 *
 * Building a flow runs build_flow.py twice (aipu + omni) + kk_format.py twice,
 * which can take minutes. Instead of blocking the request (create-flow.php), this
 * endpoint validates the request, spawns build-flow-worker.php DETACHED, and
 * returns a job id immediately. Poll generate-flow-status.php?job=<id> for the
 * coarse step + the final result.
 *
 * Mirrors flow-test.php's detached-launch pattern; reuses create-flow.php's
 * validation and slug derivation.
 *
 * POST (JSON or form-encoded): same fields as create-flow.php
 *   lander_collection / lander_name / checkout_collection / checkout_name / flow_name / brand
 *
 * Returns JSON: { ok, job, base, status_url } or { ok:false, error, base? }.
 *   error === 'exists'  -> a flow with this base already exists or is building.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function gf_fail(string $msg, int $code = 400, array $extra = []): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg] + $extra);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    gf_fail('POST required', 405);
}

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__));
if ($docRoot === false) {
    gf_fail('Cannot resolve document root', 500);
}

// ----------------------------------------------------------- input + validation
$raw = file_get_contents('php://input');
$input = ($raw !== '' && $raw[0] === '{') ? (json_decode($raw, true) ?: []) : $_POST;

$landerCollections   = ['sales-pages', 'aipu-landers', 'omnirogue-landers'];
$checkoutCollections = ['checkout-pages', 'aipu-checkouts', 'omnirogue-checkouts'];
$singleCollections   = ['single-pages'];

$landerCol    = (string)($input['lander_collection'] ?? '');
$landerName   = (string)($input['lander_name'] ?? '');
$checkoutCol  = (string)($input['checkout_collection'] ?? '');
$checkoutName = (string)($input['checkout_name'] ?? '');
$singleCol    = (string)($input['single_collection'] ?? '');
$singleName   = (string)($input['single_name'] ?? '');
$flowName     = (string)($input['flow_name'] ?? '');

$isSinglePage = $singleCol !== '' || $singleName !== '';

$safe = '/^[A-Za-z0-9._-]+$/';

// ---- Single-page path (whole self-contained site + billing pop-up) ----------
if ($isSinglePage) {
    if (!in_array($singleCol, $singleCollections, true)) {
        gf_fail('Invalid single-page collection');
    }
    if (!preg_match($safe, $singleName)) {
        gf_fail('Invalid single-page source name');
    }
    $landerReal   = realpath($docRoot . '/' . $singleCol . '/' . $singleName);
    $checkoutReal = false;
    if ($landerReal === false || strpos($landerReal, $docRoot) !== 0 || !is_dir($landerReal)) {
        gf_fail('Single-page source not found: ' . $singleName);
    }
    if (!is_file($landerReal . '/index.html')) {
        gf_fail('Single-page source has no index.html');
    }
    if (!is_file($landerReal . '/billing.html')) {
        gf_fail('Single-page source has no billing.html');
    }
    if ($flowName === '') {
        $flowName = $singleName;
    }
} else {
    // ---- Normal lander + checkout path -------------------------------------
    $wantLander   = $landerName !== '' || $landerCol !== '';
    $wantCheckout = $checkoutName !== '' || $checkoutCol !== '';
    if (!$wantLander && !$wantCheckout) {
        gf_fail('Pick a sales page, a checkout, or both');
    }
    if ($wantLander && !in_array($landerCol, $landerCollections, true)) {
        gf_fail('Invalid lander collection');
    }
    if ($wantCheckout && !in_array($checkoutCol, $checkoutCollections, true)) {
        gf_fail('Invalid checkout collection');
    }
    if ($wantLander && !preg_match($safe, $landerName)) {
        gf_fail('Invalid lander name');
    }
    if ($wantCheckout && !preg_match($safe, $checkoutName)) {
        gf_fail('Invalid checkout name');
    }

    $landerReal = false;
    $checkoutReal = false;
    if ($wantLander) {
        $landerReal = realpath($docRoot . '/' . $landerCol . '/' . $landerName);
        if ($landerReal === false || strpos($landerReal, $docRoot) !== 0 || !is_dir($landerReal)) {
            gf_fail('Lander not found: ' . $landerName);
        }
        if (!is_file($landerReal . '/index.html') && !is_file($landerReal . '/index.php')) {
            gf_fail('Lander has no index.html/index.php');
        }
    }
    if ($wantCheckout) {
        $checkoutReal = realpath($docRoot . '/' . $checkoutCol . '/' . $checkoutName);
        if ($checkoutReal === false || strpos($checkoutReal, $docRoot) !== 0 || !is_dir($checkoutReal)) {
            gf_fail('Checkout not found: ' . $checkoutName);
        }
        if (!is_file($checkoutReal . '/index.html')) {
            gf_fail('Checkout has no index.html');
        }
    }

    if ($flowName === '') {
        if ($wantLander && $wantCheckout) {
            $flowName = $landerName . '__' . $checkoutName;
        } else {
            $flowName = $wantLander ? $landerName : $checkoutName;
        }
    }
}
$base = strtolower($flowName);
$base = preg_replace('/[^a-z0-9._-]+/', '-', $base);
$base = trim(preg_replace('/-{2,}/', '-', $base), '-_.');
if ($base === '') {
    $base = 'flow';
}
$base = preg_replace('/__(aipu|omni)$/', '', $base);
if ($base === '') {
    $base = 'flow';
}

$flowsDir = $docRoot . '/flows';
if (!is_dir($flowsDir)) {
    @mkdir($flowsDir, 0775, true);
}
if (!is_writable($flowsDir)) {
    gf_fail('Flows directory is not writable by the web server (' . $flowsDir . ')', 500);
}

$python = '/usr/bin/python3';
$script = $docRoot . '/scripts/build_flow.py';
if (!is_file($script)) {
    gf_fail('Build script missing: ' . $script, 500);
}

// ------------------------------------------------------------------ job storage
// Jobs live under flows/_gen/ — the leading underscore keeps them out of the
// flow scanner (scanFlows / scanCollection skip _*), so a job dir is never seen
// as a flow. Same trick flow-test.php uses with flows/_tests.
$jobsRoot = $flowsDir . '/_gen';
if (!is_dir($jobsRoot) && !@mkdir($jobsRoot, 0775, true) && !is_dir($jobsRoot)) {
    gf_fail('Could not create the generation jobs directory', 500);
}
if (!is_writable($jobsRoot)) {
    gf_fail('Generation jobs directory is not writable by the web server (' . $jobsRoot . ')', 500);
}

// Housekeeping: prune finished job dirs older than a day so _gen doesn't grow
// without bound. Cheap; only runs on launch.
foreach (@scandir($jobsRoot) ?: [] as $old) {
    if ($old === '.' || $old === '..') {
        continue;
    }
    $oldDir = $jobsRoot . '/' . $old;
    if (is_dir($oldDir) && (time() - @filemtime($oldDir)) > 86400) {
        gf_rrmdir($oldDir);
    }
}

// Duplicate guard: refuse if either brand build already exists on disk, OR an
// active job is already generating this base. The client surfaces this as a
// "regenerate?" warning rather than silently clobbering.
if (is_dir($flowsDir . '/' . $base . '__aipu') || is_dir($flowsDir . '/' . $base . '__omni')) {
    gf_fail('exists', 409, ['base' => $base]);
}
if (gf_base_has_active_job($jobsRoot, $base)) {
    gf_fail('exists', 409, ['base' => $base]);
}

$label = $isSinglePage
    ? ($singleName . ' (single-page)')
    : trim(($wantLander ? $landerName : 'no-lander') . ' → ' . ($wantCheckout ? $checkoutName : 'no-checkout'));

$jobId = date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
$jobDir = $jobsRoot . '/' . $jobId;
if (!@mkdir($jobDir, 0775, true)) {
    gf_fail('Could not create the job directory', 500);
}
$statusFile = $jobDir . '/status.json';
$logFile = $jobDir . '/run.log';

// Seed an initial status so the first poll always has something to read.
@file_put_contents($statusFile, json_encode([
    'ok' => true,
    'status' => 'queued',
    'step' => 'queued',
    'base' => $base,
    'label' => $label,
    'started_at' => time(),
    'updated_at' => time(),
]));

// Persist the build params for the detached worker to read (keeps the launched
// command short and avoids re-escaping paths through two shells).
@file_put_contents($jobDir . '/params.json', json_encode([
    'base' => $base,
    'flowsDir' => $flowsDir,
    'docRoot' => $docRoot,
    'python' => $python,
    'script' => $script,
    'landerReal' => $landerReal,
    'checkoutReal' => $checkoutReal,
    'singlePage' => $isSinglePage,
    'label' => $label,
]));

// Launch the worker (a PHP CLI script) fully detached so the HTTP request
// returns immediately. Under FPM, PHP_BINARY is the FPM binary, so resolve a
// real CLI php instead.
$phpCli = '/usr/bin/php';
foreach (['/usr/bin/php', '/usr/bin/php8.3', '/usr/local/bin/php'] as $cand) {
    if (is_file($cand) && is_executable($cand)) { $phpCli = $cand; break; }
}
$worker = __DIR__ . '/build-flow-worker.php';
$cmd = 'nohup ' . escapeshellarg($phpCli)
    . ' ' . escapeshellarg($worker)
    . ' --job ' . escapeshellarg($jobDir)
    . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';
$pid = trim((string)shell_exec($cmd));

@file_put_contents($jobDir . '/job.json', json_encode([
    'job' => $jobId,
    'base' => $base,
    'label' => $label,
    'pid' => $pid !== '' ? (int)$pid : null,
    'started_at' => time(),
]));

echo json_encode([
    'ok' => true,
    'job' => $jobId,
    'base' => $base,
    'label' => $label,
    'status_url' => '/previews/generate-flow-status.php?job=' . rawurlencode($jobId),
    'pid' => $pid !== '' ? (int)$pid : null,
]);

// ------------------------------------------------------------------- helpers ---

/** Is there an unfinished _gen job already building this base? */
function gf_base_has_active_job(string $jobsRoot, string $base): bool
{
    foreach (@scandir($jobsRoot) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $sf = $jobsRoot . '/' . $entry . '/status.json';
        if (!is_file($sf)) {
            continue;
        }
        $d = json_decode((string)@file_get_contents($sf), true);
        if (!is_array($d) || ($d['base'] ?? '') !== $base) {
            continue;
        }
        $status = (string)($d['status'] ?? 'running');
        if (!in_array($status, ['done', 'error'], true)) {
            return true;
        }
    }
    return false;
}

/** Recursive rmdir for stale-job housekeeping. */
function gf_rrmdir(string $dir): void
{
    foreach (@scandir($dir) ?: [] as $e) {
        if ($e === '.' || $e === '..') {
            continue;
        }
        $p = $dir . '/' . $e;
        is_dir($p) ? gf_rrmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}
