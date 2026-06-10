<?php
declare(strict_types=1);

/**
 * Safety guard for the AI Editor.
 *
 * Resolves and validates the "scope" the AI is allowed to touch
 * (a single page folder or flow folder) and rejects edits to
 * sensitive files / wiring.
 */

require_once __DIR__ . '/config.php';

const AI_SCOPE_KINDS = ['lander', 'checkout', 'flow'];

const AI_SCOPE_COLLECTIONS = [
    'sales-pages'         => 'lander',
    'checkout-pages'      => 'checkout',
    'aipu-landers'        => 'lander',
    'omnirogue-landers'   => 'lander',
    'aipu-checkouts'      => 'checkout',
    'omnirogue-checkouts' => 'checkout',
];

/**
 * Files whose names — anywhere in the tree — must never be written by the AI.
 * (We allow reads so the AI can see the wiring, but writes are blocked.)
 */
const AI_BLOCKED_BASENAMES = [
    '_kk-config.php',
    '_checkout-offers.php',
    'money.php',
    'safe.php',
    'db.php',
    '.env',
    '.env.example',
    '.htaccess',
    'composer.json',
    'composer.lock',
];

/**
 * Substrings that, if introduced or removed by an AI edit, should fail unless
 * the user explicitly confirmed a risky change. Used as a heuristic on diffs.
 */
const AI_DANGER_SUBSTRINGS = [
    'sk_live_',
    'rk_live_',
    'pk_live_',
    'ANTHROPIC_API_KEY',
    'OPENAI_API_KEY',
    'XAI_API_KEY',
    'kowboykit/includes/money.php',
    'kowboykit/includes/safe.php',
    "\$offer[",
    'mysqli(',
    'json_decode(file_get_contents($dat_path)',
];

/**
 * Resolves a scope descriptor coming from the front-end into an absolute,
 * locked-down folder we are allowed to touch.
 *
 * Returns:
 *   ['kind'=>..., 'name'=>..., 'collection'=>..., 'brand'=>..., 'root'=>absPath, 'webRoot'=>'/...']
 * Throws RuntimeException on any validation failure.
 */
function ai_resolve_scope(array $scope): array
{
    $cfg     = ai_config();
    $docRoot = $cfg['docroot'];

    $kind = strtolower((string)($scope['kind'] ?? ''));
    if (!in_array($kind, AI_SCOPE_KINDS, true)) {
        throw new RuntimeException('Invalid scope kind: ' . $kind);
    }

    $name = (string)($scope['name'] ?? '');
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
        throw new RuntimeException('Invalid scope name');
    }

    if ($kind === 'flow') {
        $collection = 'flows';
        $root = $docRoot . '/flows/' . $name;
    } else {
        $collection = (string)($scope['collection'] ?? '');
        if (!isset(AI_SCOPE_COLLECTIONS[$collection]) || AI_SCOPE_COLLECTIONS[$collection] !== $kind) {
            throw new RuntimeException('Invalid collection for kind ' . $kind);
        }
        $root = $docRoot . '/' . $collection . '/' . $name;
    }

    $real = realpath($root);
    if ($real === false || !is_dir($real)) {
        throw new RuntimeException('Scope folder not found: ' . $root);
    }
    if (strpos($real, $docRoot) !== 0) {
        throw new RuntimeException('Scope outside document root');
    }

    $brand = $kind === 'flow'
        ? ai_detect_flow_brand($real)
        : (strpos($collection, 'aipu') === 0 ? 'aipu' : 'omni');

    $webRoot = $kind === 'flow'
        ? '/flows/' . $name
        : '/' . $collection . '/' . $name;

    return [
        'kind'       => $kind,
        'name'       => $name,
        'collection' => $collection,
        'brand'      => $brand,
        'root'       => $real,
        'webRoot'    => $webRoot,
    ];
}

function ai_detect_flow_brand(string $flowDir): string
{
    $meta = $flowDir . '/flow.json';
    if (is_file($meta)) {
        $j = json_decode((string)@file_get_contents($meta), true);
        if (is_array($j) && in_array(($j['brand'] ?? null), ['aipu', 'omni'], true)) {
            return $j['brand'];
        }
    }
    return 'omni';
}

/**
 * Resolves a relative path inside a scope and ensures the result stays inside.
 * Returns the absolute path. Throws on traversal / invalid paths.
 */
function ai_resolve_path(array $scope, string $rel, bool $forWrite = false): string
{
    $rel = ltrim($rel, '/');
    if ($rel === '' || $rel === '.') {
        return $scope['root'];
    }
    if (strpos($rel, '..') !== false) {
        throw new RuntimeException('Path traversal not allowed: ' . $rel);
    }

    $abs = $scope['root'] . '/' . $rel;

    // For writes, the parent must exist and be inside scope.
    if ($forWrite) {
        $parent = dirname($abs);
        if (!is_dir($parent)) {
            throw new RuntimeException('Parent directory does not exist: ' . $rel);
        }
        $parentReal = realpath($parent);
        if ($parentReal === false || strpos($parentReal, $scope['root']) !== 0) {
            throw new RuntimeException('Parent escapes scope: ' . $rel);
        }
    } else {
        $real = realpath($abs);
        if ($real === false) {
            // For reads of yet-to-exist files we still want to validate the parent.
            $parent = dirname($abs);
            $parentReal = realpath($parent);
            if ($parentReal === false || strpos($parentReal, $scope['root']) !== 0) {
                throw new RuntimeException('Path escapes scope: ' . $rel);
            }
        } elseif (strpos($real, $scope['root']) !== 0) {
            throw new RuntimeException('Path escapes scope: ' . $rel);
        }
    }

    return $abs;
}

/**
 * Returns true if writes to the given basename are blocked outright.
 */
function ai_is_blocked_basename(string $abs): bool
{
    $base = basename($abs);
    return in_array($base, AI_BLOCKED_BASENAMES, true);
}

/**
 * Inspect a proposed write for risky substrings. Returns a list of warnings
 * (empty array means clean). Risky writes always require user confirmation.
 */
function ai_scan_dangerous(string $oldContent, string $newContent): array
{
    $warnings = [];
    foreach (AI_DANGER_SUBSTRINGS as $needle) {
        $oldHas = $needle !== '' && strpos($oldContent, $needle) !== false;
        $newHas = $needle !== '' && strpos($newContent, $needle) !== false;
        if ($oldHas && !$newHas) {
            $warnings[] = 'Removed sensitive token "' . $needle . '"';
        } elseif (!$oldHas && $newHas) {
            $warnings[] = 'Introduced sensitive token "' . $needle . '"';
        }
    }
    return $warnings;
}

/**
 * Returns true if the file should be read-only for the AI even though it
 * lives inside scope (we still expose it for context, but block writes).
 */
function ai_is_readonly_path(string $abs, array $scope): bool
{
    if (ai_is_blocked_basename($abs)) {
        return true;
    }
    return false;
}

/**
 * Authorises the request based on optional IP allow-list.
 */
function ai_check_access(): void
{
    $cfg = ai_config();
    $allow = $cfg['allow_ips'];
    if (empty($allow)) {
        return;
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, $allow, true)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Access denied for IP ' . $ip]);
        exit;
    }
}
