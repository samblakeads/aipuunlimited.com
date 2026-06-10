<?php
declare(strict_types=1);

/**
 * plan-library.php — central registry of the plans we sell (the "Plan Library").
 *
 *   GET                      -> { ok, version, updated_at, plans:[...], tokens, token_labels }
 *   GET  ?id=<slug>          -> { ok, plan:{...} }            (404 if missing)
 *   POST { action:create|update|delete, plan:{...} | id:<slug> }
 *                            -> { ok, plan|id, plans:[...] }
 *
 * Source of truth is materialized once (seeded from kk-master/multi-step.md +
 * omnirogue-checkouts/planinstructions.md) into:
 *     /var/www/aipuunlimited.com/data/plan-library.json
 *
 * Mirrors the standalone-endpoint conventions of flow-config.php (JSON in/out,
 * local fail(), strict validation, atomic write). The Phase-2 checkout
 * customizer reads this store to drive its plan-picker.
 */

require_once __DIR__ . '/lib/checkout-fs.php';   // cb_* helpers + ai_audit_append
require_once __DIR__ . '/lib/kk-tokens.php';     // KK_TOKENS, TOKEN_LABELS

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

ai_check_access(); // honours AI_EDITOR_ALLOW_IPS when set (no-op otherwise)

function fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function storePath(): string
{
    return cb_data_dir() . '/plan-library.json';
}

/**
 * The seed document, materialized from the KK plan docs. Prices/points are
 * stored as strings WITHOUT a leading "$", commas preserved, so they match the
 * literal forms used in the checkout templates (e.g. bundle.js mPrice:"14.99").
 */
function planSeedDoc(): array
{
    $bothBrands = ['aipu', 'omni'];
    $mk = function (array $o) use ($bothBrands): array {
        return array_merge([
            'id'              => '',
            'display_name'    => '',
            'tag'             => '',
            'badge'           => null,
            'points'          => '',
            'monthly_price'   => null,
            'monthly_strike'  => null,
            'yearly_price'    => null,
            'yearly_strike'   => null,
            'lifetime_price'  => null,
            'lifetime_strike' => null,
            'offer_tokens'    => ['monthly' => null, 'yearly' => null, 'lifetime' => null],
            'giveaways'       => [],
            'brands'          => $bothBrands,
            'order'           => 0,
        ], $o);
    };

    $plans = [
        $mk([
            'id' => 'creator', 'display_name' => 'Creator',
            'tag' => 'For first-time AI content creators',
            'points' => '1,000',
            'monthly_price' => '14.99', 'monthly_strike' => '29',
            'yearly_price' => '99', 'yearly_strike' => '179',
            'offer_tokens' => ['monthly' => 'creatormonthly', 'yearly' => 'creatoryearly', 'lifetime' => null],
            'order' => 1,
        ]),
        $mk([
            'id' => 'studio', 'display_name' => 'Studio',
            'tag' => 'For growing creators and small teams',
            'points' => '2,500',
            'monthly_price' => '29.99', 'monthly_strike' => '59',
            'yearly_price' => '299', 'yearly_strike' => '359',
            'offer_tokens' => ['monthly' => 'studiomonthly', 'yearly' => 'studioyearly', 'lifetime' => null],
            'order' => 2,
        ]),
        $mk([
            'id' => 'premium', 'display_name' => 'Premium',
            'tag' => 'Premium access for power users',
            'points' => '3,500',
            'monthly_price' => '39.99',
            'yearly_price' => '398',
            'offer_tokens' => ['monthly' => 'premiummonthly', 'yearly' => 'premiumyearly', 'lifetime' => null],
            'order' => 3,
        ]),
        $mk([
            'id' => 'scale', 'display_name' => 'Scale',
            'tag' => 'For agencies scaling content',
            'points' => '5,000',
            'monthly_price' => '49.99', 'monthly_strike' => '99',
            'yearly_price' => '499', 'yearly_strike' => '599',
            'offer_tokens' => ['monthly' => 'scalemonthly', 'yearly' => 'scaleyearly', 'lifetime' => null],
            'order' => 4,
        ]),
        $mk([
            'id' => 'pro', 'display_name' => 'Pro',
            'tag' => 'For high-volume professionals', 'badge' => 'Most Popular',
            'points' => '10,000',
            'monthly_price' => '99.99',
            'yearly_price' => '998',
            'offer_tokens' => ['monthly' => 'promonthly', 'yearly' => 'proyearly', 'lifetime' => null],
            'order' => 5,
        ]),
        $mk([
            'id' => 'agency', 'display_name' => 'Agency',
            'tag' => 'For agencies and small teams', 'badge' => 'Best Value',
            'points' => '10,000',
            'monthly_price' => '149.99',
            'yearly_price' => '1,499',
            'offer_tokens' => ['monthly' => 'agencymonthly', 'yearly' => 'agencyyearly', 'lifetime' => null],
            'order' => 6,
        ]),
        $mk([
            'id' => 'promax', 'display_name' => 'Pro Max',
            'tag' => 'Maximum scale and throughput',
            'points' => '30,000',
            'monthly_price' => '299.99',
            'yearly_price' => '2,998',
            'offer_tokens' => ['monthly' => 'promaxmonthly', 'yearly' => 'promaxyearly', 'lifetime' => null],
            'order' => 7,
        ]),
        $mk([
            'id' => 'lifetime', 'display_name' => 'Lifetime',
            'tag' => 'One payment. Lifetime access.',
            'points' => '10,000',
            'lifetime_price' => '399', 'lifetime_strike' => '1900',
            'offer_tokens' => ['monthly' => null, 'yearly' => null, 'lifetime' => 'lifetime'],
            'order' => 8,
        ]),
    ];

    return ['version' => 1, 'updated_at' => date('c'), 'plans' => $plans];
}

function loadLibrary(): array
{
    $path = storePath();
    if (!is_file($path)) {
        $doc = planSeedDoc();
        if (!cb_write_json_atomic($path, $doc)) {
            fail('Could not seed plan library at ' . $path . ' (permissions?)', 500);
        }
        return $doc;
    }
    $raw = (string)@file_get_contents($path);
    if (trim($raw) === '') {
        $doc = planSeedDoc();
        cb_write_json_atomic($path, $doc);
        return $doc;
    }
    $doc = json_decode($raw, true);
    if (!is_array($doc) || !isset($doc['plans']) || !is_array($doc['plans'])) {
        fail('Plan library store is corrupt: ' . $path, 500);
    }
    return $doc;
}

function saveLibrary(array $doc): void
{
    $doc['version'] = 1;
    $doc['updated_at'] = date('c');
    $doc['plans'] = array_values($doc['plans']);
    if (!cb_write_json_atomic(storePath(), $doc)) {
        fail('Could not write plan library (permissions?)', 500);
    }
}

/** Normalize a price/points string: strip "$" and spaces; "" -> null. */
function normPrice($v): ?string
{
    if ($v === null) return null;
    $v = trim((string)$v);
    $v = ltrim($v, '$');
    $v = trim($v);
    return $v === '' ? null : $v;
}

/** Validate + sanitize a plan payload. Returns clean plan array or fail()s. */
function sanitizePlan($raw): array
{
    if (!is_array($raw)) {
        fail('Missing plan object');
    }

    $id = strtolower(trim((string)($raw['id'] ?? '')));
    if (!preg_match('/^[a-z0-9-]+$/', $id)) {
        fail('Invalid plan id (use lowercase letters, numbers, hyphens): ' . $id);
    }

    $name = trim((string)($raw['display_name'] ?? ''));
    if ($name === '' || mb_strlen($name) > 60) {
        fail('display_name is required and must be ≤ 60 chars');
    }

    $priceRe = '/^[0-9][0-9,]*(\.[0-9]{1,2})?$/';
    $numericFields = [
        'points', 'monthly_price', 'monthly_strike', 'yearly_price',
        'yearly_strike', 'lifetime_price', 'lifetime_strike',
    ];
    $clean = [
        'id'           => $id,
        'display_name' => $name,
        'tag'          => mb_substr(trim((string)($raw['tag'] ?? '')), 0, 200),
    ];

    $badge = $raw['badge'] ?? null;
    if ($badge !== null) {
        $badge = trim((string)$badge);
        if ($badge === '') {
            $badge = null;
        } elseif (mb_strlen($badge) > 40) {
            fail('badge must be ≤ 40 chars');
        }
    }
    $clean['badge'] = $badge;

    foreach ($numericFields as $f) {
        $v = normPrice($raw[$f] ?? null);
        if ($v !== null && !preg_match($priceRe, $v)) {
            fail("Invalid number for $f: \"$v\" (digits, commas, up to 2 decimals)");
        }
        // points is non-nullable in practice but we allow "" -> "" for flexibility
        $clean[$f] = $v;
    }
    if ($clean['points'] === null) {
        $clean['points'] = '';
    }

    // offer_tokens: monthly / yearly / lifetime — each null or a known KK token.
    $tokensRaw = $raw['offer_tokens'] ?? [];
    if (!is_array($tokensRaw)) {
        fail('offer_tokens must be an object');
    }
    $tokens = ['monthly' => null, 'yearly' => null, 'lifetime' => null];
    foreach ($tokens as $slot => $_) {
        $t = $tokensRaw[$slot] ?? null;
        if ($t === null || $t === '') {
            $tokens[$slot] = null;
            continue;
        }
        $t = (string)$t;
        if (!in_array($t, KK_TOKENS, true)) {
            fail("Unknown offer token for $slot: \"$t\"");
        }
        $tokens[$slot] = $t;
    }
    $clean['offer_tokens'] = $tokens;

    // giveaways: array of non-empty strings.
    $give = $raw['giveaways'] ?? [];
    $clean['giveaways'] = [];
    if (is_array($give)) {
        foreach ($give as $g) {
            $g = trim((string)$g);
            if ($g !== '') {
                $clean['giveaways'][] = mb_substr($g, 0, 200);
            }
        }
    }

    // brands: subset of {aipu, omni}, defaulting to both.
    $brands = $raw['brands'] ?? ['aipu', 'omni'];
    $clean['brands'] = [];
    if (is_array($brands)) {
        foreach (['aipu', 'omni'] as $b) {
            if (in_array($b, $brands, true)) {
                $clean['brands'][] = $b;
            }
        }
    }
    if (empty($clean['brands'])) {
        $clean['brands'] = ['aipu', 'omni'];
    }

    $clean['order'] = (int)($raw['order'] ?? 0);

    return $clean;
}

function findIndexById(array $plans, string $id): int
{
    foreach ($plans as $i => $p) {
        if (($p['id'] ?? null) === $id) {
            return $i;
        }
    }
    return -1;
}

// -------------------------------------------------------------------------- GET
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $doc = loadLibrary();
    $wantId = isset($_GET['id']) ? strtolower(trim((string)$_GET['id'])) : '';
    if ($wantId !== '') {
        $idx = findIndexById($doc['plans'], $wantId);
        if ($idx < 0) {
            fail('Plan not found: ' . $wantId, 404);
        }
        echo json_encode(['ok' => true, 'plan' => $doc['plans'][$idx]]);
        exit;
    }
    echo json_encode([
        'ok'           => true,
        'version'      => $doc['version'] ?? 1,
        'updated_at'   => $doc['updated_at'] ?? null,
        'plans'        => array_values($doc['plans']),
        'tokens'       => KK_TOKENS,
        'token_labels' => TOKEN_LABELS,
    ]);
    exit;
}

// -------------------------------------------------------------------------- POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail('GET or POST required', 405);
}

$raw = file_get_contents('php://input') ?: '';
$input = ($raw !== '' && $raw[0] === '{') ? (json_decode($raw, true) ?: []) : $_POST;

$action = strtolower((string)($input['action'] ?? ''));
if (!in_array($action, ['create', 'update', 'delete'], true)) {
    fail('Invalid action (create|update|delete): ' . $action);
}

$doc = loadLibrary();
$plans = $doc['plans'];

if ($action === 'delete') {
    $id = strtolower(trim((string)($input['id'] ?? ($input['plan']['id'] ?? ''))));
    if ($id === '') {
        fail('delete requires an id');
    }
    $idx = findIndexById($plans, $id);
    if ($idx < 0) {
        fail('Plan not found: ' . $id, 404);
    }
    array_splice($plans, $idx, 1);
    $doc['plans'] = $plans;
    saveLibrary($doc);
    ai_audit_append(['kind' => 'plan-library', 'action' => 'delete', 'id' => $id]);
    echo json_encode(['ok' => true, 'id' => $id, 'plans' => array_values($plans)]);
    exit;
}

// create / update
$plan = sanitizePlan($input['plan'] ?? null);
$idx = findIndexById($plans, $plan['id']);

if ($action === 'create') {
    if ($idx >= 0) {
        fail('A plan with id "' . $plan['id'] . '" already exists', 409);
    }
    $plans[] = $plan;
} else { // update
    if ($idx < 0) {
        fail('Plan not found: ' . $plan['id'], 404);
    }
    $plans[$idx] = $plan;
}

// Keep stable ordering by 'order' then id.
usort($plans, function ($a, $b) {
    return ((int)($a['order'] ?? 0)) <=> ((int)($b['order'] ?? 0))
        ?: strcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
});

$doc['plans'] = $plans;
saveLibrary($doc);
ai_audit_append(['kind' => 'plan-library', 'action' => $action, 'id' => $plan['id']]);

echo json_encode(['ok' => true, 'plan' => $plan, 'plans' => array_values($plans)]);
