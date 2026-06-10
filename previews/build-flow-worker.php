<?php
declare(strict_types=1);

/**
 * build-flow-worker.php — detached CLI worker that builds ONE flow.
 *
 * Launched by generate-flow.php with `--job <jobDir>`. It reads the validated
 * build params from <jobDir>/params.json, runs the shared build/KK/rollback
 * pipeline (lib/build_flow_run.php — the same code create-flow.php uses), and
 * writes coarse progress into <jobDir>/status.json between steps so the poller
 * (generate-flow-status.php) can show "building omni…", "kk-aipu…", etc.
 *
 * Terminal state:
 *   success -> status.json { status:'done',  ...full build result }
 *   failure -> status.json { status:'error', error, base, ... }
 *
 * This script runs under PHP CLI, detached via nohup. It must never depend on
 * any HTTP/SAPI globals.
 */

// Belt-and-braces: this can run for minutes.
@set_time_limit(0);

// ------------------------------------------------------------------ arg parsing
$jobDir = null;
$argvLocal = $argv ?? [];
for ($i = 1; $i < count($argvLocal); $i++) {
    if ($argvLocal[$i] === '--job' && isset($argvLocal[$i + 1])) {
        $jobDir = $argvLocal[$i + 1];
        $i++;
    }
}
if ($jobDir === null || !is_dir($jobDir)) {
    fwrite(STDERR, "build-flow-worker: missing or invalid --job dir\n");
    exit(2);
}

$statusFile = $jobDir . '/status.json';
$paramsFile = $jobDir . '/params.json';

$params = is_file($paramsFile)
    ? json_decode((string)file_get_contents($paramsFile), true)
    : null;
if (!is_array($params)) {
    worker_write_status($statusFile, [
        'ok' => false,
        'status' => 'error',
        'error' => 'Worker could not read params.json',
    ]);
    exit(2);
}

$base  = (string)($params['base'] ?? '');
$label = (string)($params['label'] ?? '');

/** Merge-and-write the job status atomically. */
function worker_write_status(string $statusFile, array $patch): void
{
    $cur = is_file($statusFile)
        ? (json_decode((string)file_get_contents($statusFile), true) ?: [])
        : [];
    $next = array_merge($cur, $patch, ['updated_at' => time()]);
    @file_put_contents($statusFile, json_encode($next), LOCK_EX);
}

// Mark that the worker has picked the job up.
worker_write_status($statusFile, [
    'ok' => true,
    'status' => 'running',
    'step' => 'starting',
    'base' => $base,
    'label' => $label,
]);

require_once __DIR__ . '/lib/build_flow_run.php';

// Human-readable step labels surfaced to the widget.
$stepText = [
    'building-aipu' => 'Building AIPU…',
    'building-omni' => 'Building OmniRogue…',
    'kk-aipu'       => 'KK-formatting AIPU…',
    'kk-omni'       => 'KK-formatting OmniRogue…',
];

$progress = static function (string $step) use ($statusFile, $stepText, $base, $label): void {
    worker_write_status($statusFile, [
        'status' => 'running',
        'step' => $step,
        'step_text' => $stepText[$step] ?? $step,
        'base' => $base,
        'label' => $label,
    ]);
};

try {
    $result = bfr_build_flow([
        'base'         => $base,
        'flowsDir'     => (string)$params['flowsDir'],
        'docRoot'      => (string)$params['docRoot'],
        'python'       => (string)($params['python'] ?? '/usr/bin/python3'),
        'script'       => (string)$params['script'],
        'landerReal'   => $params['landerReal'] ?? false,
        'checkoutReal' => $params['checkoutReal'] ?? false,
        'singlePage'   => (bool)($params['singlePage'] ?? false),
    ], $progress);
} catch (\Throwable $e) {
    worker_write_status($statusFile, [
        'ok' => false,
        'status' => 'error',
        'step' => 'error',
        'error' => 'Worker exception: ' . $e->getMessage(),
        'base' => $base,
        'label' => $label,
    ]);
    exit(1);
}

if (empty($result['ok'])) {
    worker_write_status($statusFile, array_merge($result, [
        'status' => 'error',
        'step' => 'error',
        'base' => $base,
        'label' => $label,
        'finished_at' => time(),
    ]));
    exit(1);
}

// Success — fold the full build payload into the status so the poller returns
// everything the UI would have gotten from the old synchronous create-flow.php.
worker_write_status($statusFile, array_merge($result, [
    'status' => 'done',
    'step' => 'done',
    'base' => $base,
    'label' => $label,
    'finished_at' => time(),
]));
exit(0);
