<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Append a structured record to the AI Editor audit log.
 */
function ai_audit_append(array $entry): void
{
    $cfg = ai_config();
    $path = $cfg['audit'];
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $entry = array_merge(['ts' => date('c'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? null], $entry);
    @file_put_contents($path, json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Read the most recent N audit entries, newest first.
 */
function ai_audit_tail(int $limit = 50, ?array $scope = null): array
{
    $cfg = ai_config();
    $path = $cfg['audit'];
    if (!is_file($path)) return [];

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $lines = array_reverse($lines);
    $out = [];
    foreach ($lines as $line) {
        $j = json_decode($line, true);
        if (!is_array($j)) continue;
        if ($scope !== null) {
            if (($j['scope']['kind'] ?? null) !== ($scope['kind'] ?? null)) continue;
            if (($j['scope']['name'] ?? null) !== ($scope['name'] ?? null)) continue;
        }
        $out[] = $j;
        if (count($out) >= $limit) break;
    }
    return $out;
}

/**
 * Restore the most recent backup created for a given scope (any run).
 * Returns ['restored'=>[paths], 'runId'=>...] or throws.
 */
function ai_revert_latest(array $scope): array
{
    $cfg = ai_config();
    $base = $cfg['backups'];
    if (!is_dir($base)) {
        throw new RuntimeException('No backups available');
    }

    $folder = $scope['kind'] . '__' . $scope['name'];
    $candidates = [];
    foreach (scandir($base) ?: [] as $runId) {
        if ($runId === '.' || $runId === '..') continue;
        $dir = $base . '/' . $runId . '/' . $folder;
        if (is_dir($dir)) {
            $candidates[] = ['runId' => $runId, 'dir' => $dir, 'mtime' => @filemtime($dir) ?: 0];
        }
    }
    if (empty($candidates)) {
        throw new RuntimeException('No backups for this page');
    }
    usort($candidates, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    $latest = $candidates[0];

    $restored = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($latest['dir'], FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $f) {
        if (!$f->isFile()) continue;
        $rel = ltrim(str_replace($latest['dir'], '', $f->getPathname()), '/');
        $target = $scope['root'] . '/' . $rel;
        @mkdir(dirname($target), 0775, true);
        @copy($f->getPathname(), $target);
        $restored[] = $rel;
    }
    return ['runId' => $latest['runId'], 'restored' => $restored];
}
