<?php
declare(strict_types=1);

/**
 * flow-test-status.php - poll a running/finished flow test.
 *
 * GET ?job=<id>  ->  the tester's status JSON, augmented with:
 *   running  : whether the OS process is still alive
 *   stalled  : true if the process died without writing a final verdict
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__));
if ($docRoot === false) {
    fail('Cannot resolve document root', 500);
}

$job = (string)($_GET['job'] ?? '');
if (!preg_match('/^[0-9]{8}-[0-9]{6}-[a-f0-9]{8}$/', $job)) {
    fail('Invalid job id');
}

$jobsRoot = realpath($docRoot . '/flows/_tests');
if ($jobsRoot === false) {
    fail('No test jobs yet', 404);
}
$jobDir = realpath($jobsRoot . '/' . $job);
if ($jobDir === false || dirname($jobDir) !== $jobsRoot || !is_dir($jobDir)) {
    fail('Job not found', 404);
}

$statusFile = $jobDir . '/status.json';
if (!is_file($statusFile)) {
    fail('Job has no status yet', 404);
}

$data = json_decode((string)file_get_contents($statusFile), true);
if (!is_array($data)) {
    // A half-written file mid-flush: report a soft "running" rather than error.
    echo json_encode(['ok' => true, 'status' => 'running', 'verdict' => null,
                      'transient' => true]);
    exit;
}

// Is the worker still alive?
$running = false;
$jobMetaFile = $jobDir . '/job.json';
$pid = null;
if (is_file($jobMetaFile)) {
    $meta = json_decode((string)file_get_contents($jobMetaFile), true) ?: [];
    $pid = isset($meta['pid']) ? (int)$meta['pid'] : null;
    if ($pid && function_exists('posix_kill')) {
        $running = @posix_kill($pid, 0);
    } elseif ($pid && is_dir('/proc')) {
        $running = is_dir('/proc/' . $pid);
    }
}

$status = (string)($data['status'] ?? 'running');
$finished = in_array($status, ['done', 'error'], true);

// Stall detection: the process is gone but no terminal status was written
// (e.g. it was OOM-killed). Surface it so the UI stops polling.
$stalled = false;
if (!$finished && !$running && $pid) {
    $age = time() - (int)($data['updated_at'] ?? $data['started_at'] ?? time());
    if ($age > 8) {
        $stalled = true;
    }
}

$data['ok'] = $data['ok'] ?? true;
$data['running'] = $running;
$data['stalled'] = $stalled;
$data['job'] = $job;
if ($stalled) {
    $data['status'] = 'error';
    $data['error'] = $data['error']
        ?? 'The tester process stopped unexpectedly before finishing. Check flows/_tests/' . $job . '/run.log';
}

echo json_encode($data);
