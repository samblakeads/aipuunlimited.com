<?php
declare(strict_types=1);

/**
 * widget-library.php — central registry of conversion widgets (exit popups +
 * mobile sticky sliders) used by the Flow Builder ("Widget Library").
 *
 *   GET                      -> { ok, version, updated_at, widgets:[...] }
 *   GET  ?id=<slug>          -> { ok, widget:{...} }          (404 if missing)
 *   POST { action:create|update|delete, widget:{...} | id:<slug> }
 *                            -> { ok, widget|id, widgets:[...] }
 *
 * Source of truth (seeded on first load with premium defaults):
 *     /var/www/aipuunlimited.com/data/widget-library.json
 *
 * Mirrors plan-library.php conventions (JSON in/out, local fail(), strict
 * validation, atomic write). Widgets are attached to flows via
 * flow-widgets.php which writes flow.json widgets_config and re-injects the
 * rendered snippet into the flow pages.
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
    return cb_data_dir() . '/widget-library.json';
}

const WIDGET_TYPES        = ['popup', 'slider', 'social'];
const WIDGET_ACCENTS      = ['gold', 'purple', 'emerald', 'crimson', 'blue'];
const WIDGET_DESIGNS      = ['spotlight', 'duo', 'bar', 'toast'];
const WIDGET_SOCIAL_POS   = ['bottom-left', 'bottom-right'];
const WIDGET_BILLING      = ['monthly', 'yearly', 'lifetime'];
const WIDGET_CHECKOUT_ACT = ['select_plan', 'direct_payment'];
const WIDGET_COUNTDOWN    = ['off', 'today', 'minutes'];

/**
 * Seeded premium defaults — 3 popups + 2 sliders. Copy mirrors the proven
 * kk-june8 exit popup offers; prices match data/plan-library.json.
 */
function widgetSeedDoc(): array
{
    $mk = function (array $o): array {
        return array_merge([
            'id'              => '',
            'name'            => '',
            'type'            => 'popup',
            'design'          => 'spotlight',
            'accent'          => 'gold',
            'active'          => true,
            'builtin'         => true,
            'plan_id'         => null,
            'billing_slot'    => 'monthly',
            'title'           => '',
            'subtitle'        => '',
            'price'           => '',
            'price_meta'      => '',
            'strike_price'    => '',
            'bonus_text'      => '',
            'cta_text'        => '',
            'checkout_action' => 'select_plan',
            'image'           => '',
            'countdown'       => ['mode' => 'off', 'minutes' => 15],
            'show_desktop'    => true,
            'show_mobile'     => true,
            'secondary'       => null,
            'social'          => null,
            'triggers'        => [
                'exit_intent'   => true,
                'scroll_pct'    => 0,
                'delay_seconds' => 0,
            ],
            'frequency'       => [
                'once_per_session' => true,
                'show_again_days'  => 3,
            ],
            'order'           => 0,
        ], $o);
    };

    $widgets = [
        $mk([
            'id'           => 'lifetime-399-popup',
            'name'         => 'Lifetime $399 — Today Only (Popup)',
            'type'         => 'popup',
            'design'       => 'spotlight',
            'accent'       => 'gold',
            'plan_id'      => 'lifetime',
            'billing_slot' => 'lifetime',
            'title'        => 'Get Lifetime Access for just $399 — today only!',
            'subtitle'     => 'One payment. Every model, every tool, forever. No subscription, no renewals — lock it in before this offer disappears.',
            'price'        => '399',
            'price_meta'   => 'one-time · lifetime access',
            'strike_price' => '1900',
            'bonus_text'   => 'Bonus: 90 days of unlimited video & image generation on top.',
            'cta_text'     => 'Claim Lifetime Access',
            'countdown'    => ['mode' => 'today', 'minutes' => 15],
            'triggers'     => ['exit_intent' => true, 'scroll_pct' => 0, 'delay_seconds' => 0],
            'order'        => 1,
        ]),
        $mk([
            'id'           => 'scale-nano-popup',
            'name'         => 'Scale + 2,500 Nano Banana (Popup)',
            'type'         => 'popup',
            'design'       => 'spotlight',
            'accent'       => 'purple',
            'plan_id'      => 'scale',
            'billing_slot' => 'monthly',
            'title'        => 'Get 2,500 Free Nano Banana Generations with the Scale Plan — just $49.99!',
            'subtitle'     => 'Join Scale today and we will instantly credit your account with 2,500 free Nano Banana image generations. Cancel anytime.',
            'price'        => '49.99',
            'price_meta'   => '/ month · cancel anytime',
            'strike_price' => '99',
            'bonus_text'   => 'Bonus: 2,500 free Nano Banana generations, credited instantly.',
            'cta_text'     => 'Claim Scale Plan',
            'countdown'    => ['mode' => 'minutes', 'minutes' => 15],
            'triggers'     => ['exit_intent' => true, 'scroll_pct' => 0, 'delay_seconds' => 0],
            'order'        => 2,
        ]),
        $mk([
            'id'           => 'unlock-unlimited-popup',
            'name'         => 'Unlock Unlimited — Dual Choice (Popup)',
            'type'         => 'popup',
            'design'       => 'duo',
            'accent'       => 'purple',
            'plan_id'      => 'creator',
            'billing_slot' => 'monthly',
            'title'        => 'Unlock Unlimited AI Creation Today',
            'subtitle'     => 'Two limited-time bonuses for new creators. Pick the one that fits — both unlock the second you finish checkout.',
            'price'        => '14.99',
            'price_meta'   => '/ month · cancel anytime',
            'strike_price' => '29',
            'bonus_text'   => '500 free Nano Banana generations, credited instantly.',
            'cta_text'     => 'Choose My Plan',
            'countdown'    => ['mode' => 'off', 'minutes' => 15],
            'secondary'    => [
                'plan_id'      => 'lifetime',
                'billing_slot' => 'lifetime',
                'title'        => '90 days unlimited video & image',
                'price'        => '399',
                'price_meta'   => 'one-time · lifetime access',
                'strike_price' => '1900',
                'bonus_text'   => 'Lock in $399 lifetime and we unlock 90 days of unlimited video and image generation on top.',
                'cta_text'     => 'Unlock Lifetime + 90 Days Unlimited',
            ],
            'triggers'     => ['exit_intent' => true, 'scroll_pct' => 0, 'delay_seconds' => 0],
            'order'        => 3,
        ]),
        $mk([
            'id'           => 'lifetime-399-slider',
            'name'         => 'Lifetime $399 — Today Only (Slider)',
            'type'         => 'slider',
            'design'       => 'bar',
            'accent'       => 'gold',
            'plan_id'      => 'lifetime',
            'billing_slot' => 'lifetime',
            'title'        => 'Get Lifetime Access for just $399 — today only!',
            'subtitle'     => 'One payment. Every tool, forever.',
            'price'        => '399',
            'price_meta'   => 'one-time',
            'strike_price' => '1900',
            'bonus_text'   => '',
            'cta_text'     => 'Claim Lifetime Access',
            'countdown'    => ['mode' => 'today', 'minutes' => 15],
            'show_desktop' => false,
            'triggers'     => ['exit_intent' => false, 'scroll_pct' => 25, 'delay_seconds' => 0],
            'frequency'    => ['once_per_session' => false, 'show_again_days' => 0],
            'order'        => 4,
        ]),
        $mk([
            'id'           => 'scale-nano-slider',
            'name'         => 'Scale + 2,500 Nano Banana (Slider)',
            'type'         => 'slider',
            'design'       => 'bar',
            'accent'       => 'purple',
            'plan_id'      => 'scale',
            'billing_slot' => 'monthly',
            'title'        => 'Get 2,500 Free Nano Banana Generations with the Scale Plan — just $49.99!',
            'subtitle'     => 'Credited instantly. Cancel anytime.',
            'price'        => '49.99',
            'price_meta'   => '/mo',
            'strike_price' => '99',
            'bonus_text'   => '',
            'cta_text'     => 'Claim Scale Plan',
            'countdown'    => ['mode' => 'off', 'minutes' => 15],
            'show_desktop' => false,
            'triggers'     => ['exit_intent' => false, 'scroll_pct' => 25, 'delay_seconds' => 0],
            'frequency'    => ['once_per_session' => false, 'show_again_days' => 0],
            'order'        => 5,
        ]),
        $mk([
            'id'           => 'recent-buyers-social',
            'name'         => 'Recent Buyers — Social Proof',
            'type'         => 'social',
            'design'       => 'toast',
            'accent'       => 'emerald',
            'plan_id'      => null,
            'billing_slot' => 'monthly',
            'social'       => [
                'messages'      => [
                    '{name} from {place} just bought the Lifetime plan',
                    '{name} from {place} just upgraded to Scale',
                    '{name} from {place} just unlocked unlimited access',
                    '{name} from {place} just claimed the $399 lifetime deal',
                ],
                'names'         => ['Carl', 'Maria', 'Jake', 'Aisha', 'Liam', 'Sofia', 'Noah', 'Priya', 'Marcus', 'Elena', 'Diego', 'Yuki', 'Hannah', 'Andre'],
                'places'        => ['Rhode Island', 'Texas', 'London', 'Toronto', 'Berlin', 'Sydney', 'Austin', 'Madrid', 'Miami', 'Chicago', 'Denver', 'Seattle'],
                'position'      => 'bottom-left',
                'start_delay'   => 5,
                'duration'      => 5,
                'interval'      => 8,
                'show_count'    => true,
                'count_label'   => 'creators upgraded today',
                'count_sub'     => 'across AIPU',
                'link_checkout' => true,
            ],
            'show_desktop' => true,
            'show_mobile'  => true,
            'triggers'     => ['exit_intent' => false, 'scroll_pct' => 0, 'delay_seconds' => 0],
            'frequency'    => ['once_per_session' => false, 'show_again_days' => 0],
            'order'        => 6,
        ]),
    ];

    return ['version' => 1, 'updated_at' => date('c'), 'widgets' => $widgets];
}

function loadLibrary(): array
{
    $path = storePath();
    if (!is_file($path)) {
        $doc = widgetSeedDoc();
        if (!cb_write_json_atomic($path, $doc)) {
            fail('Could not seed widget library at ' . $path . ' (permissions?)', 500);
        }
        return $doc;
    }
    $raw = (string)@file_get_contents($path);
    if (trim($raw) === '') {
        $doc = widgetSeedDoc();
        cb_write_json_atomic($path, $doc);
        return $doc;
    }
    $doc = json_decode($raw, true);
    if (!is_array($doc) || !isset($doc['widgets']) || !is_array($doc['widgets'])) {
        fail('Widget library store is corrupt: ' . $path, 500);
    }
    return $doc;
}

function saveLibrary(array $doc): void
{
    $doc['version'] = 1;
    $doc['updated_at'] = date('c');
    $doc['widgets'] = array_values($doc['widgets']);
    if (!cb_write_json_atomic(storePath(), $doc)) {
        fail('Could not write widget library (permissions?)', 500);
    }
}

/** Normalize a price string: strip "$" and spaces; "" stays "". */
function normPrice($v): string
{
    $v = trim((string)($v ?? ''));
    $v = trim(ltrim($v, '$'));
    return $v;
}

function sanitizeOfferFields(array $raw, string $ctx): array
{
    $priceRe = '/^[0-9][0-9,]*(\.[0-9]{1,2})?$/';
    $out = [];

    $planId = strtolower(trim((string)($raw['plan_id'] ?? '')));
    if ($planId !== '' && !preg_match('/^[a-z0-9-]+$/', $planId)) {
        fail("Invalid $ctx plan_id: $planId");
    }
    $out['plan_id'] = $planId === '' ? null : $planId;

    $slot = strtolower(trim((string)($raw['billing_slot'] ?? 'monthly')));
    if (!in_array($slot, WIDGET_BILLING, true)) {
        fail("Invalid $ctx billing_slot: $slot");
    }
    $out['billing_slot'] = $slot;

    $out['title']      = mb_substr(trim((string)($raw['title'] ?? '')), 0, 200);
    $out['subtitle']   = mb_substr(trim((string)($raw['subtitle'] ?? '')), 0, 400);
    $out['bonus_text'] = mb_substr(trim((string)($raw['bonus_text'] ?? '')), 0, 300);
    $out['cta_text']   = mb_substr(trim((string)($raw['cta_text'] ?? '')), 0, 80);
    $out['price_meta'] = mb_substr(trim((string)($raw['price_meta'] ?? '')), 0, 80);

    foreach (['price', 'strike_price'] as $f) {
        $v = normPrice($raw[$f] ?? '');
        if ($v !== '' && !preg_match($priceRe, $v)) {
            fail("Invalid number for $ctx $f: \"$v\"");
        }
        $out[$f] = $v;
    }
    return $out;
}

/** Validate + sanitize the social-proof config block (type=social only).
 *  Accepts arrays or newline-separated strings for the list fields. */
function sanitizeSocial(array $raw): array
{
    $strList = function ($v, int $maxItems, int $maxLen): array {
        if (is_string($v)) {
            $v = preg_split('/\r?\n/', $v);
        }
        if (!is_array($v)) {
            return [];
        }
        $out = [];
        foreach ($v as $item) {
            $s = trim((string)$item);
            if ($s === '') {
                continue;
            }
            $out[] = mb_substr($s, 0, $maxLen);
            if (count($out) >= $maxItems) {
                break;
            }
        }
        return $out;
    };

    $pos = strtolower(trim((string)($raw['position'] ?? 'bottom-left')));
    if (!in_array($pos, WIDGET_SOCIAL_POS, true)) {
        $pos = 'bottom-left';
    }

    return [
        'messages'      => $strList($raw['messages'] ?? [], 24, 160),
        'names'         => $strList($raw['names'] ?? [], 200, 40),
        'places'        => $strList($raw['places'] ?? [], 200, 60),
        'position'      => $pos,
        'start_delay'   => max(0, min(120, (int)($raw['start_delay'] ?? 5))),
        'duration'      => max(1, min(60, (int)($raw['duration'] ?? 5))),
        'interval'      => max(1, min(600, (int)($raw['interval'] ?? 8))),
        'show_count'    => !empty($raw['show_count']),
        'count_label'   => mb_substr(trim((string)($raw['count_label'] ?? '')), 0, 80),
        'count_sub'     => mb_substr(trim((string)($raw['count_sub'] ?? '')), 0, 80),
        'link_checkout' => !empty($raw['link_checkout']),
    ];
}

/** Validate + sanitize a widget payload. Returns clean widget array or fail()s. */
function sanitizeWidget($raw): array
{
    if (!is_array($raw)) {
        fail('Missing widget object');
    }

    $id = strtolower(trim((string)($raw['id'] ?? '')));
    if (!preg_match('/^[a-z0-9-]+$/', $id)) {
        fail('Invalid widget id (use lowercase letters, numbers, hyphens): ' . $id);
    }

    $name = trim((string)($raw['name'] ?? ''));
    if ($name === '' || mb_strlen($name) > 80) {
        fail('name is required and must be ≤ 80 chars');
    }

    $type = strtolower(trim((string)($raw['type'] ?? '')));
    if (!in_array($type, WIDGET_TYPES, true)) {
        fail('Invalid type (popup|slider): ' . $type);
    }

    $design = strtolower(trim((string)($raw['design'] ?? '')));
    if ($design === '') {
        $design = $type === 'slider' ? 'bar' : ($type === 'social' ? 'toast' : 'spotlight');
    }
    if (!in_array($design, WIDGET_DESIGNS, true)) {
        fail('Invalid design: ' . $design);
    }
    // design is derived from type for the non-popup kinds
    if ($type === 'social') {
        $design = 'toast';
    } elseif ($type === 'slider') {
        $design = 'bar';
    } elseif ($design === 'bar' || $design === 'toast') {
        $design = 'spotlight';
    }

    $accent = strtolower(trim((string)($raw['accent'] ?? 'gold')));
    if (!in_array($accent, WIDGET_ACCENTS, true)) {
        fail('Invalid accent: ' . $accent);
    }

    $checkoutAction = strtolower(trim((string)($raw['checkout_action'] ?? 'select_plan')));
    if (!in_array($checkoutAction, WIDGET_CHECKOUT_ACT, true)) {
        fail('Invalid checkout_action (select_plan|direct_payment): ' . $checkoutAction);
    }

    $clean = array_merge(
        [
            'id'              => $id,
            'name'            => $name,
            'type'            => $type,
            'design'          => $design,
            'accent'          => $accent,
            'active'          => !empty($raw['active']),
            'builtin'         => !empty($raw['builtin']),
            'checkout_action' => $checkoutAction,
            'image'           => mb_substr(trim((string)($raw['image'] ?? '')), 0, 500),
            'show_desktop'    => !empty($raw['show_desktop']),
            'show_mobile'     => !empty($raw['show_mobile']),
        ],
        sanitizeOfferFields($raw, 'widget')
    );

    // countdown: { mode: off|today|minutes, minutes: int }
    $cdRaw = is_array($raw['countdown'] ?? null) ? $raw['countdown'] : ['mode' => 'off'];
    $cdMode = strtolower(trim((string)($cdRaw['mode'] ?? 'off')));
    if (!in_array($cdMode, WIDGET_COUNTDOWN, true)) {
        fail('Invalid countdown mode (off|today|minutes): ' . $cdMode);
    }
    $cdMin = (int)($cdRaw['minutes'] ?? 15);
    $clean['countdown'] = ['mode' => $cdMode, 'minutes' => max(1, min(1440, $cdMin))];

    // secondary offer (duo design only)
    $clean['secondary'] = null;
    if ($design === 'duo' && is_array($raw['secondary'] ?? null)) {
        $clean['secondary'] = sanitizeOfferFields($raw['secondary'], 'secondary');
    }

    // social-proof config (type=social only)
    $clean['social'] = $type === 'social'
        ? sanitizeSocial(is_array($raw['social'] ?? null) ? $raw['social'] : [])
        : null;

    // triggers
    $trRaw = is_array($raw['triggers'] ?? null) ? $raw['triggers'] : [];
    $clean['triggers'] = [
        'exit_intent'   => !empty($trRaw['exit_intent']),
        'scroll_pct'    => max(0, min(100, (int)($trRaw['scroll_pct'] ?? 0))),
        'delay_seconds' => max(0, min(3600, (int)($trRaw['delay_seconds'] ?? 0))),
    ];

    // frequency
    $fqRaw = is_array($raw['frequency'] ?? null) ? $raw['frequency'] : [];
    $clean['frequency'] = [
        'once_per_session' => !empty($fqRaw['once_per_session']),
        'show_again_days'  => max(0, min(365, (int)($fqRaw['show_again_days'] ?? 0))),
    ];

    $clean['order'] = (int)($raw['order'] ?? 0);

    return $clean;
}

function findIndexById(array $widgets, string $id): int
{
    foreach ($widgets as $i => $w) {
        if (($w['id'] ?? null) === $id) {
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
        $idx = findIndexById($doc['widgets'], $wantId);
        if ($idx < 0) {
            fail('Widget not found: ' . $wantId, 404);
        }
        echo json_encode(['ok' => true, 'widget' => $doc['widgets'][$idx]]);
        exit;
    }
    echo json_encode([
        'ok'         => true,
        'version'    => $doc['version'] ?? 1,
        'updated_at' => $doc['updated_at'] ?? null,
        'widgets'    => array_values($doc['widgets']),
        'accents'    => WIDGET_ACCENTS,
        'designs'    => WIDGET_DESIGNS,
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
$widgets = $doc['widgets'];

if ($action === 'delete') {
    $id = strtolower(trim((string)($input['id'] ?? ($input['widget']['id'] ?? ''))));
    if ($id === '') {
        fail('delete requires an id');
    }
    $idx = findIndexById($widgets, $id);
    if ($idx < 0) {
        fail('Widget not found: ' . $id, 404);
    }
    array_splice($widgets, $idx, 1);
    $doc['widgets'] = $widgets;
    saveLibrary($doc);
    ai_audit_append(['kind' => 'widget-library', 'action' => 'delete', 'id' => $id]);
    echo json_encode(['ok' => true, 'id' => $id, 'widgets' => array_values($widgets)]);
    exit;
}

// create / update
$widget = sanitizeWidget($input['widget'] ?? null);
$idx = findIndexById($widgets, $widget['id']);

if ($action === 'create') {
    if ($idx >= 0) {
        fail('A widget with id "' . $widget['id'] . '" already exists', 409);
    }
    if ((int)$widget['order'] === 0) {
        $max = 0;
        foreach ($widgets as $w) {
            $max = max($max, (int)($w['order'] ?? 0));
        }
        $widget['order'] = $max + 1;
    }
    $widgets[] = $widget;
} else { // update
    if ($idx < 0) {
        fail('Widget not found: ' . $widget['id'], 404);
    }
    // builtin flag is sticky — keep whatever was stored
    $widget['builtin'] = !empty($widgets[$idx]['builtin']);
    $widgets[$idx] = $widget;
}

usort($widgets, function ($a, $b) {
    return ((int)($a['order'] ?? 0)) <=> ((int)($b['order'] ?? 0))
        ?: strcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
});

$doc['widgets'] = $widgets;
saveLibrary($doc);
ai_audit_append(['kind' => 'widget-library', 'action' => $action, 'id' => $widget['id']]);

echo json_encode(['ok' => true, 'widget' => $widget, 'widgets' => array_values($widgets)]);
