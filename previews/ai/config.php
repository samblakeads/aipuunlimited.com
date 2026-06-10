<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Returns the structured AI Editor configuration:
 *   providers => list of available providers (with key state + models)
 *   default   => default provider id
 *   limits    => max_turns, max_tokens
 *   docroot   => resolved document root
 *   audit     => audit log path
 *   backups   => backup folder
 */
function ai_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $defaultProvider = strtolower((string)ai_env('DEFAULT_AI_PROVIDER', 'anthropic'));
    if (!in_array($defaultProvider, ['anthropic', 'openai', 'xai'], true)) {
        $defaultProvider = 'anthropic';
    }

    $providers = [
        'anthropic' => [
            'id'         => 'anthropic',
            'label'      => 'Anthropic',
            'envKey'     => 'ANTHROPIC_API_KEY',
            'apiKey'     => (string)ai_env('ANTHROPIC_API_KEY', ''),
            'defaultModel' => (string)ai_env('DEFAULT_ANTHROPIC_MODEL', 'claude-opus-4-5'),
            'modelsCsv'  => (string)ai_env('ANTHROPIC_MODELS', ''),
            'apiUrl'     => 'https://api.anthropic.com/v1/messages',
        ],
        'openai' => [
            'id'         => 'openai',
            'label'      => 'OpenAI',
            'envKey'     => 'OPENAI_API_KEY',
            'apiKey'     => (string)ai_env('OPENAI_API_KEY', ''),
            'defaultModel' => (string)ai_env('DEFAULT_OPENAI_MODEL', 'gpt-5.1'),
            'modelsCsv'  => (string)ai_env('OPENAI_MODELS', ''),
            'apiUrl'     => 'https://api.openai.com/v1/chat/completions',
        ],
        'xai' => [
            'id'         => 'xai',
            'label'      => 'Grok / xAI',
            'envKey'     => 'XAI_API_KEY',
            'apiKey'     => (string)ai_env('XAI_API_KEY', ''),
            'defaultModel' => (string)ai_env('DEFAULT_XAI_MODEL', 'grok-4'),
            'modelsCsv'  => (string)ai_env('XAI_MODELS', ''),
            'apiUrl'     => 'https://api.x.ai/v1/chat/completions',
        ],
    ];

    foreach ($providers as $id => &$p) {
        $models = [];
        if ($p['defaultModel'] !== '') {
            $models[] = $p['defaultModel'];
        }
        foreach (array_filter(array_map('trim', explode(',', $p['modelsCsv']))) as $m) {
            if (!in_array($m, $models, true)) {
                $models[] = $m;
            }
        }
        $p['models']    = $models;
        $p['available'] = $p['apiKey'] !== '';
        unset($p['modelsCsv']);
    }
    unset($p);

    // If the configured default has no key, pick the first available one.
    if (!$providers[$defaultProvider]['available']) {
        foreach ($providers as $id => $p) {
            if ($p['available']) {
                $defaultProvider = $id;
                break;
            }
        }
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: realpath(dirname(__DIR__, 2));
    $dataDir = realpath($docRoot . '/../data');
    if ($dataDir === false) {
        $dataDir = dirname($docRoot) . '/data';
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0775, true);
        }
    }

    $cfg = [
        'providers' => $providers,
        'default'   => $defaultProvider,
        'limits'    => [
            'max_turns'         => max(1, (int)ai_env('AI_EDITOR_MAX_TURNS', '14')),
            'max_tokens'        => max(256, (int)ai_env('AI_EDITOR_MAX_TOKENS', '4096')),
            'max_context_chars' => max(50000, (int)ai_env('AI_EDITOR_MAX_CONTEXT_CHARS', '320000')),
        ],
        'docroot'   => $docRoot,
        'audit'     => $dataDir . '/ai-edit-audit.jsonl',
        'backups'   => $dataDir . '/ai-edit-backups',
        'allow_ips' => array_filter(array_map('trim', explode(',', (string)ai_env('AI_EDITOR_ALLOW_IPS', '')))),
    ];

    return $cfg;
}

/**
 * Coerce a tool-use "input" value into something that JSON-encodes as an
 * object ({}) rather than an array ([]). json_decode turns the JSON object
 * "{}" into an empty PHP array, which then re-encodes as "[]" and is rejected
 * by the Anthropic API ("Input should be an object"). This normalises it.
 */
function ai_input_object($input)
{
    if ($input === null) {
        return new stdClass();
    }
    if (is_array($input) && $input === []) {
        return new stdClass();
    }
    return $input;
}

/**
 * Returns a sanitised, JSON-safe view of the config for the front-end
 * (no API keys, just availability + model lists).
 */
function ai_config_public(): array
{
    $c = ai_config();
    $providers = [];
    foreach ($c['providers'] as $id => $p) {
        $providers[] = [
            'id'           => $p['id'],
            'label'        => $p['label'],
            'envKey'       => $p['envKey'],
            'available'    => $p['available'],
            'defaultModel' => $p['defaultModel'],
            'models'       => $p['models'],
        ];
    }
    return [
        'providers' => $providers,
        'default'   => $c['default'],
        'limits'    => $c['limits'],
    ];
}
