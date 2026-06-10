<?php
declare(strict_types=1);

/**
 * flow-test.php - launch scripts/flow_tester.py against a LIVE deployed funnel.
 *
 * The crawl can take minutes, so this endpoint spawns the tester DETACHED and
 * returns a job id immediately. Poll flow-test-status.php?job=<id> for progress
 * and the final GREEN/RED verdict.
 *
 * POST (JSON or form-encoded):
 *   url           required - live URL of the funnel's entry page (http/https)
 *   allow_host    optional - expected product host(s), comma-separated. When set
 *                            the tester requires CTAs to land on these host(s).
 *   max_pages     optional - crawl cap (default 40, clamped 1..120)
 *
 * Returns JSON: { ok, job, status_url } or { ok:false, error }.
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

$url = trim((string)($input['url'] ?? ''));
if ($url === '' || !preg_match('~^https?://~i', $url)) {
    fail('A live http(s):// URL is required');
}
// Reject anything that is not a plain URL (defence-in-depth before shell use).
if (preg_match('/[\s"\'`$\\\\<>|;]/', $url) || strlen($url) > 2000) {
    fail('URL contains invalid characters');
}
$parts = parse_url($url);
if ($parts === false || empty($parts['host'])) {
    fail('Could not parse the URL');
}

$allowHost = trim((string)($input['allow_host'] ?? ''));
if ($allowHost !== '' && !preg_match('/^[A-Za-z0-9.\-,: ]+$/', $allowHost)) {
    fail('Expected host contains invalid characters');
}

$maxPages = (int)($input['max_pages'] ?? 40);
$maxPages = max(1, min(120, $maxPages));

$python = '/usr/bin/python3';
$script = $docRoot . '/scripts/flow_tester.py';
if (!is_file($script)) {
    fail('Flow tester missing: ' . $script, 500);
}

// Job storage lives under flows/_tests/ — the leading underscore keeps it out of
// the flow scanner (scanFlows skips _*), so a job dir is never seen as a flow.
$jobsRoot = $docRoot . '/flows/_tests';
if (!is_dir($jobsRoot) && !@mkdir($jobsRoot, 0775, true) && !is_dir($jobsRoot)) {
    fail('Could not create the test jobs directory', 500);
}
if (!is_writable($jobsRoot)) {
    fail('Test jobs directory is not writable by the web server (' . $jobsRoot . ')', 500);
}

$jobId = date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
$jobDir = $jobsRoot . '/' . $jobId;
if (!@mkdir($jobDir, 0775, true)) {
    fail('Could not create the job directory', 500);
}
$statusFile = $jobDir . '/status.json';
$logFile = $jobDir . '/run.log';
$screensWeb = '/flows/_tests/' . $jobId;

// Seed an initial status so the first poll always has something to read.
@file_put_contents($statusFile, json_encode([
    'ok' => true,
    'status' => 'starting',
    'verdict' => null,
    'url' => $url,
    'started_at' => time(),
    'updated_at' => time(),
    'progress' => ['pages_done' => 0, 'pages_known' => 1, 'clicks_done' => 0],
    'summary' => ['pages' => 0, 'errors' => 0, 'warnings' => 0, 'probes' => 0],
    'pages' => [],
    'findings' => [],
]));

// Build the detached command. PLAYWRIGHT_BROWSERS_PATH points at the shared
// Chromium install; HOME must be writable by www-data for Playwright's cache.
// `env` sets them so nohup doesn't mistake VAR=val for the command name.
$cmd = '/usr/bin/env PLAYWRIGHT_BROWSERS_PATH=/opt/ms-playwright HOME=/tmp '
    . escapeshellarg($python) . ' ' . escapeshellarg($script)
    . ' --url ' . escapeshellarg($url)
    . ' --out ' . escapeshellarg($statusFile)
    . ' --screens-dir ' . escapeshellarg($jobDir)
    . ' --screens-web ' . escapeshellarg($screensWeb)
    . ' --max-pages ' . (int)$maxPages;
if ($allowHost !== '') {
    $cmd .= ' --allow-host ' . escapeshellarg($allowHost);
}
// Fully detach: redirect output to a log and background so the HTTP request can
// return immediately while the crawl keeps running.
$cmd = 'nohup ' . $cmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';

$pid = trim((string)shell_exec($cmd));

@file_put_contents($jobDir . '/job.json', json_encode([
    'job' => $jobId,
    'url' => $url,
    'allow_host' => $allowHost,
    'max_pages' => $maxPages,
    'pid' => $pid !== '' ? (int)$pid : null,
    'started_at' => time(),
]));

echo json_encode([
    'ok' => true,
    'job' => $jobId,
    'status_url' => '/previews/flow-test-status.php?job=' . rawurlencode($jobId),
    'pid' => $pid !== '' ? (int)$pid : null,
]);
