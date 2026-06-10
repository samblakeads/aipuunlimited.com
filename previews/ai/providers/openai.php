<?php
declare(strict_types=1);

/**
 * OpenAI Chat Completions adapter (also reused for xAI/Grok which is wire-compatible).
 *
 * Translates normalized messages into OpenAI's chat format with function tools.
 */
function ai_provider_openai_call(array $provider, string $model, string $system, array $messages, array $tools, int $maxTokens): array
{
    return ai_provider_oai_compat_call($provider, $model, $system, $messages, $tools, $maxTokens, 'OpenAI');
}

function ai_provider_xai_call(array $provider, string $model, string $system, array $messages, array $tools, int $maxTokens): array
{
    return ai_provider_oai_compat_call($provider, $model, $system, $messages, $tools, $maxTokens, 'xAI');
}

function ai_provider_oai_compat_call(array $provider, string $model, string $system, array $messages, array $tools, int $maxTokens, string $label): array
{
    $url = $provider['apiUrl'];
    $apiKey = $provider['apiKey'];
    if ($apiKey === '') {
        throw new RuntimeException($label . ' API key not configured');
    }

    $oaiTools = array_map(fn($t) => [
        'type' => 'function',
        'function' => [
            'name'        => $t['name'],
            'description' => $t['description'],
            'parameters'  => $t['input_schema'],
        ],
    ], $tools);

    $oaiMsgs = [['role' => 'system', 'content' => $system]];
    foreach ($messages as $m) {
        $oaiMsgs = array_merge($oaiMsgs, ai_oai_msg_normalize($m));
    }

    $payload = [
        'model'       => $model,
        'max_tokens'  => $maxTokens,
        'messages'    => $oaiMsgs,
        'tools'       => $oaiTools,
        'tool_choice' => 'auto',
    ];

    $resp = ai_http_json_post($url, $payload, [
        'authorization: Bearer ' . $apiKey,
        'content-type: application/json',
    ]);

    if (!is_array($resp) || !isset($resp['choices'][0]['message'])) {
        $errMsg = is_array($resp) ? json_encode($resp) : (string)$resp;
        throw new RuntimeException($label . ' API error: ' . $errMsg);
    }

    $msg = $resp['choices'][0]['message'];
    $text = (string)($msg['content'] ?? '');
    $toolUses = [];
    foreach (($msg['tool_calls'] ?? []) as $tc) {
        $args = $tc['function']['arguments'] ?? '{}';
        $decoded = is_string($args) ? json_decode($args, true) : $args;
        $toolUses[] = [
            'id'    => (string)($tc['id'] ?? ''),
            'name'  => (string)($tc['function']['name'] ?? ''),
            'input' => is_array($decoded) ? $decoded : [],
        ];
    }

    return [
        'kind'      => $toolUses ? 'tool_use' : 'message',
        'text'      => $text,
        'tool_uses' => $toolUses,
        'raw'       => $resp,
    ];
}

/**
 * Convert a normalized {role, blocks} message into one or more OpenAI messages.
 * - text blocks -> role:user|assistant
 * - tool_use blocks (assistant) -> assistant message with tool_calls
 * - tool_result blocks (user) -> role:tool messages
 */
function ai_oai_msg_normalize(array $m): array
{
    $role = $m['role'] === 'assistant' ? 'assistant' : 'user';
    $blocks = $m['blocks'] ?? [];

    $out = [];
    if ($role === 'assistant') {
        $textParts = [];
        $toolCalls = [];
        foreach ($blocks as $b) {
            if (($b['type'] ?? '') === 'text') $textParts[] = (string)$b['text'];
            elseif (($b['type'] ?? '') === 'tool_use') {
                $toolCalls[] = [
                    'id'   => (string)($b['id'] ?? ''),
                    'type' => 'function',
                    'function' => [
                        'name'      => (string)($b['name'] ?? ''),
                        'arguments' => json_encode(ai_input_object($b['input'] ?? null), JSON_UNESCAPED_SLASHES),
                    ],
                ];
            }
        }
        $msg = ['role' => 'assistant', 'content' => implode('', $textParts) ?: null];
        if ($toolCalls) {
            $msg['tool_calls'] = $toolCalls;
            if ($msg['content'] === null) unset($msg['content']);
        }
        $out[] = $msg;
        return $out;
    }

    // user role: split tool_results into separate role:tool messages
    $textParts = [];
    foreach ($blocks as $b) {
        if (($b['type'] ?? '') === 'text') {
            $textParts[] = (string)$b['text'];
        } elseif (($b['type'] ?? '') === 'tool_result') {
            $out[] = [
                'role'         => 'tool',
                'tool_call_id' => (string)($b['tool_use_id'] ?? ''),
                'content'      => (string)($b['content'] ?? ''),
            ];
        }
    }
    if ($textParts) {
        array_unshift($out, ['role' => 'user', 'content' => implode('', $textParts)]);
    }
    return $out;
}
