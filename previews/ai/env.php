<?php
declare(strict_types=1);

/**
 * Tiny .env loader for the AI Editor.
 * Reads /var/www/aipuunlimited.com/.env (outside the docroot by design).
 * Values are NOT exported to $_ENV/getenv() so they cannot leak into
 * unrelated PHP code; callers use ai_env(...).
 */

if (!function_exists('ai_env_load')) {
    function ai_env_load(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [];
        // Priority order: secure parent-of-docroot locations first, then a
        // convenience fallback inside the web root (docroot/.env). The parent
        // location is preferred because it can never be served as a static file.
        $candidates = [
            (string)($_SERVER['DOCUMENT_ROOT'] ?? '') . '/../.env',
            dirname(__DIR__, 4) . '/.env',
            '/var/www/aipuunlimited.com/.env',
            (string)($_SERVER['DOCUMENT_ROOT'] ?? '') . '/.env',
            '/var/www/aipuunlimited.com/htdocs/.env',
        ];
        $path = null;
        foreach ($candidates as $cand) {
            $real = @realpath($cand);
            if ($real !== false && is_file($real)) {
                $path = $real;
                break;
            }
        }
        if ($path === null) {
            return $cache;
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return $cache;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (!preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)$/i', $line, $m)) {
                continue;
            }
            $key = $m[1];
            $val = trim($m[2]);
            // Strip wrapping quotes
            if (strlen($val) >= 2) {
                $first = $val[0]; $last = $val[strlen($val) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                }
            }
            $cache[$key] = $val;
        }
        return $cache;
    }

    function ai_env(string $key, ?string $default = null): ?string
    {
        $env = ai_env_load();
        if (array_key_exists($key, $env) && $env[$key] !== '') {
            return $env[$key];
        }
        return $default;
    }
}
