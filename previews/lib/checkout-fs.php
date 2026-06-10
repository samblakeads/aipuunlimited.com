<?php
declare(strict_types=1);

/**
 * checkout-fs.php — shared, AI-free filesystem helpers for the checkout builder
 * (Plan Library + deterministic Checkout Customizer).
 *
 * It reuses the proven write-safety primitives from previews/ai/ WITHOUT going
 * through the AI provider loop. The required AI files are pure function/const
 * definitions (no side effects at include time), so including them here is safe:
 *
 *   - config.php  -> ai_config()        (data dir, audit/backup paths)
 *   - safety.php  -> ai_resolve_scope(), ai_resolve_path(), ai_is_blocked_basename()
 *   - tools.php   -> ai_backup_file(), ai_php_lint()
 *   - audit.php   -> ai_audit_append()
 */

require_once __DIR__ . '/../ai/config.php';
require_once __DIR__ . '/../ai/safety.php';
require_once __DIR__ . '/../ai/tools.php';
require_once __DIR__ . '/../ai/audit.php';

/**
 * Absolute path to the shared data directory (same dir that holds
 * ai-edit-audit.jsonl and ai-edit-backups/). Created if missing.
 */
if (!function_exists('cb_data_dir')) {
    function cb_data_dir(): string
    {
        $cfg = ai_config();
        $dir = dirname((string)$cfg['audit']); // .../data
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }
}

/**
 * Read + json_decode a file. Returns [] when the file is missing or invalid.
 */
if (!function_exists('cb_read_json')) {
    function cb_read_json(string $abs): array
    {
        if (!is_file($abs)) {
            return [];
        }
        $raw = @file_get_contents($abs);
        if ($raw === false || $raw === '') {
            return [];
        }
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }
}

/**
 * Atomically write a JSON document (pretty, slashes intact) via *.tmp + rename.
 * Returns true on success. Mirrors the atomic-write pattern in flow-config.php.
 */
if (!function_exists('cb_write_json_atomic')) {
    function cb_write_json_atomic(string $abs, array $data): bool
    {
        $dir = dirname($abs);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }
        $tmp = $abs . '.tmp';
        if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
            return false;
        }
        if (!@rename($tmp, $abs)) {
            @unlink($tmp);
            return false;
        }
        return true;
    }
}

/**
 * Deterministic, backed-up file write used by the Phase-2 customizer.
 * Thin extraction of AiEditorSession::commitWrite() so the customizer gets the
 * exact same guarantees (backup before write, php -l on .php with revert-on-fail)
 * without instantiating an AI session.
 *
 *   $scope must come from ai_resolve_scope().
 * Returns ['ok'=>true, 'sizeBefore'=>int, 'sizeAfter'=>int]
 *      or ['ok'=>false, 'error'=>string].
 */
if (!function_exists('cb_safe_write')) {
    function cb_safe_write(string $abs, string $before, string $after, string $runId, array $scope): array
    {
        if (ai_is_blocked_basename($abs)) {
            return ['ok' => false, 'error' => 'Cannot write protected file: ' . basename($abs)];
        }

        // Snapshot for revert (same backup tree the AI editor + ai_revert_latest use).
        ai_backup_file($runId, $scope, $abs, $before);

        if (@file_put_contents($abs, $after) === false) {
            return ['ok' => false, 'error' => 'Failed to write ' . basename($abs) . ' (permission denied?)'];
        }

        if (preg_match('/\.php$/i', $abs)) {
            $check = ai_php_lint($abs);
            if ($check !== true) {
                @file_put_contents($abs, $before); // revert
                return ['ok' => false, 'error' => 'PHP syntax error after edit, change reverted: ' . $check];
            }
        }

        return [
            'ok'         => true,
            'sizeBefore' => strlen($before),
            'sizeAfter'  => strlen($after),
        ];
    }
}
