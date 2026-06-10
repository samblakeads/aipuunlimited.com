<?php
declare(strict_types=1);

/**
 * build_flow_run.php — shared build/KK/rollback core for a single flow.
 *
 * This is the exact build pipeline that used to live inline in create-flow.php:
 * run scripts/build_flow.py for BOTH brands (aipu + omni), roll both back on any
 * failure so we never leave a half-paired flow, then auto-KK-format both builds
 * (non-fatal), and finally assemble the result payload the UI expects.
 *
 * It is called from two places:
 *   - create-flow.php          — synchronous HTTP fallback (behaviour unchanged)
 *   - build-flow-worker.php    — the detached worker for async generation, which
 *                                passes a $progress callback to surface coarse
 *                                step labels into the job status file.
 *
 * Inputs are assumed ALREADY VALIDATED by the caller (collections checked,
 * folder names sanitised, realpath-confined, base slug derived). This file does
 * no HTTP / argument parsing of its own.
 *
 * Returns a result array shaped like:
 *   success: ['ok'=>true, 'flow'=>base, 'flow_base'=>base, 'brands'=>[...],
 *             'builds'=>[...], 'url'=>..., 'checkout_url'=>..., 'flow_type'=>...,
 *             'qc'=>..., 'kk'=>['aipu'=>bool,'omni'=>bool], 'warnings'=>[...]]
 *   failure: ['ok'=>false, 'error'=>..., 'brand'=>..., 'builds'=>[...]]
 */

/**
 * Run build_flow.py for one brand and return the parsed JSON result (or null if
 * the process produced no output at all).
 *
 * Mirrors the $runBuild closure that previously lived in create-flow.php.
 */
function bfr_run_build(
    string $brand,
    string $python,
    string $script,
    $landerReal,
    $checkoutReal,
    string $base,
    string $flowsDir,
    string $docRoot,
    bool $singlePage = false
): ?array {
    $slug = $base . '__' . $brand;
    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script)
        . ($landerReal !== false ? ' --lander-dir ' . escapeshellarg($landerReal) : '')
        . ($checkoutReal !== false ? ' --checkout-dir ' . escapeshellarg($checkoutReal) : '')
        . ($singlePage ? ' --single-page' : '')
        . ' --name ' . escapeshellarg($slug)
        . ' --brand ' . escapeshellarg($brand)
        . ' --flow-base ' . escapeshellarg($base)
        . ' --flows-dir ' . escapeshellarg($flowsDir)
        . ' --docroot ' . escapeshellarg($docRoot)
        . ' 2>&1';
    $output = shell_exec($cmd);
    if ($output === null || $output === '') {
        return null;
    }
    $trimmed = trim($output);
    $decoded = json_decode($trimmed, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    // build_flow.py emits a single JSON line to stdout, but defensively scan from
    // the last line back in case something printed before it.
    $lines = preg_split('/\r?\n/', $trimmed);
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $try = json_decode(trim($lines[$i]), true);
        if (is_array($try)) {
            return $try;
        }
    }
    return ['ok' => false, 'error' => 'Could not parse build output: ' . substr($trimmed, 0, 300)];
}

/**
 * Build both brands + KK for one flow.
 *
 * @param array         $opts     validated inputs:
 *                                base, flowsDir, docRoot, python, script,
 *                                landerReal (string|false), checkoutReal (string|false)
 * @param callable|null $progress fn(string $step): void — coarse step ticks:
 *                                'building-aipu','building-omni','kk-aipu','kk-omni'
 * @return array result payload (see file docblock)
 */
function bfr_build_flow(array $opts, ?callable $progress = null): array
{
    $base         = (string)$opts['base'];
    $flowsDir     = (string)$opts['flowsDir'];
    $docRoot      = (string)$opts['docRoot'];
    $python       = (string)($opts['python'] ?? '/usr/bin/python3');
    $script       = (string)$opts['script'];
    $landerReal   = $opts['landerReal'] ?? false;
    $checkoutReal = $opts['checkoutReal'] ?? false;
    $singlePage   = (bool)($opts['singlePage'] ?? false);

    $tick = static function (string $step) use ($progress): void {
        if ($progress !== null) {
            $progress($step);
        }
    };

    // ---- Build both brands (sequential, rollback both on any failure) --------
    $builds = [];
    foreach (['aipu', 'omni'] as $brand) {
        $tick('building-' . $brand);
        $res = bfr_run_build($brand, $python, $script, $landerReal, $checkoutReal, $base, $flowsDir, $docRoot, $singlePage);
        if ($res === null) {
            // Roll back any sibling build so we never leave a half-paired flow.
            bfr_rollback($base, $flowsDir, $docRoot);
            return [
                'ok' => false,
                'error' => 'Build produced no output for ' . $brand
                    . ' (is python3 available to the web server?)',
                'brand' => $brand,
                'builds' => $builds,
            ];
        }
        $builds[$brand] = $res;
        if (empty($res['ok'])) {
            bfr_rollback($base, $flowsDir, $docRoot);
            return [
                'ok' => false,
                'error' => 'Build failed for ' . strtoupper($brand) . ': '
                    . (string)($res['error'] ?? 'unknown'),
                'brand' => $brand,
                'builds' => $builds,
            ];
        }
    }

    // ---- Auto KK-format both builds (non-fatal) ------------------------------
    $kkScript = $docRoot . '/scripts/kk_format.py';
    $kkResults = [];
    foreach (['aipu', 'omni'] as $brand) {
        $tick('kk-' . $brand);
        $flowDir = $flowsDir . '/' . $base . '__' . $brand;
        if (!is_file($kkScript) || !is_dir($flowDir)) {
            $kkResults[$brand] = ['ok' => false, 'error' => 'kk_format.py or flow dir missing'];
            continue;
        }
        $cmd = escapeshellarg($python) . ' ' . escapeshellarg($kkScript)
            . ' --flow-dir ' . escapeshellarg($flowDir)
            . ' --docroot ' . escapeshellarg($docRoot)
            . ' 2>&1';
        $out = trim((string)shell_exec($cmd));
        $json = json_decode($out, true);
        if (!is_array($json)) {
            foreach (array_reverse(preg_split('/\r?\n/', $out)) as $line) {
                $try = json_decode(trim($line), true);
                if (is_array($try)) { $json = $try; break; }
            }
        }
        $kkResults[$brand] = is_array($json) ? $json : ['ok' => false, 'error' => substr($out, 0, 300)];
        if (empty($kkResults[$brand]['ok'])) {
            $builds[$brand]['warnings'][] = 'Auto KK format failed: '
                . (string)($kkResults[$brand]['error']
                    ?? implode('; ', $kkResults[$brand]['violations'] ?? ['unknown']));
        }
    }

    // ---- Worst QC status across the two brand builds, for the summary --------
    $qcRank = ['pass' => 0, 'warn' => 1, 'fail' => 2];
    $worstQc = null;
    foreach ($builds as $b) {
        $qc = $b['qc'] ?? null;
        if (is_array($qc) && isset($qc['status'])) {
            if ($worstQc === null
                || ($qcRank[$qc['status']] ?? 0) > ($qcRank[$worstQc['status']] ?? 0)) {
                $worstQc = $qc;
            }
        }
    }

    $omni = $builds['omni'];
    return [
        'ok' => true,
        'flow' => $base,
        'flow_base' => $base,
        'brands' => array_keys($builds),
        'builds' => $builds,
        // Top-level convenience links point at the OmniRogue build.
        'url' => $omni['url'] ?? null,
        'checkout_url' => $omni['checkout_url'] ?? null,
        'flow_type' => $omni['flow_type'] ?? 'multi',
        'qc' => $worstQc,
        'kk' => [
            'aipu' => !empty($kkResults['aipu']['ok']),
            'omni' => !empty($kkResults['omni']['ok']),
        ],
        'warnings' => array_merge(
            $builds['aipu']['warnings'] ?? [],
            $builds['omni']['warnings'] ?? []
        ),
    ];
}

/**
 * Roll back both per-brand builds of a base into the Archive, so a failed build
 * never leaves a half-paired flow on disk. Identical to create-flow.php's old
 * inline rollback.
 */
function bfr_rollback(string $base, string $flowsDir, string $docRoot): void
{
    require_once __DIR__ . '/archive.php';
    foreach (['aipu', 'omni'] as $b) {
        $dir = $flowsDir . '/' . $base . '__' . $b;
        if (is_dir($dir)) {
            @archive_store('flow', '', $base . '__' . $b, $docRoot);
        }
    }
}
