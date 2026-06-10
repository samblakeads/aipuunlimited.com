<?php
declare(strict_types=1);

/**
 * Canonical KowboyKit offer-token vocabulary — single source of truth.
 *
 * The catalogue lives in ../data/kk-tokens.json (next to plan-library.json)
 * and is shared with the Python pipeline via scripts/kk_tokens.py, so token
 * names and canonical prices can never drift between PHP and Python.
 *
 * Exposes (guarded, safe to require from multiple endpoints):
 *   KK_TOKENS        [token, ...]
 *   TOKEN_LABELS     [token => label]
 *   KK_TOKEN_CATALOG [token => {label, kind, plan, period, price}]
 *
 * Consumed by:
 *   - previews/flow-config.php   (per-flow register-CTA routing)
 *   - previews/flow-plans.php    (plan/token wiring)
 *   - previews/plan-library.php  (validates each plan's offer_tokens.*)
 */

if (!defined('KK_TOKEN_CATALOG')) {
    $kkTokensJson = dirname((string)($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2)))
        . '/data/kk-tokens.json';
    $kkCatalog = [];
    if (is_file($kkTokensJson)) {
        $kkDoc = json_decode((string)file_get_contents($kkTokensJson), true);
        if (is_array($kkDoc) && !empty($kkDoc['tokens']) && is_array($kkDoc['tokens'])) {
            $kkCatalog = $kkDoc['tokens'];
        }
    }
    if (!$kkCatalog) {
        // Hard-stop: endpoints must never run with an empty/forged vocabulary.
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'kk-tokens.json missing or invalid: ' . $kkTokensJson]);
        exit;
    }
    define('KK_TOKEN_CATALOG', $kkCatalog);
    define('KK_TOKENS', array_keys($kkCatalog));

    $kkLabels = [];
    foreach ($kkCatalog as $kkTok => $kkMeta) {
        $kkLabels[$kkTok] = (string)($kkMeta['label'] ?? $kkTok);
    }
    define('TOKEN_LABELS', $kkLabels);
    unset($kkTokensJson, $kkCatalog, $kkDoc, $kkLabels, $kkTok, $kkMeta);
}
