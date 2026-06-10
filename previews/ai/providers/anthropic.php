<?php
declare(strict_types=1);

/**
 * Anthropic Messages API adapter.
 *
 * Translates the editor's normalized message format into Claude's native
 * format and back. Uses native tool-use blocks.
 *
 * Normalized message format (input):
 *   ['role' => 'user'|'assistant', 'blocks' => [
 *       ['type'=>'text', 'text'=>'...'],
 *       ['type'=>'tool_use', 'id'=>'...', 'name'=>'...', 'input'=>[...]],
 *       ['type'=>'tool_result', 'tool_use_id'=>'...', 'content'=>'...', 'is_error'=>bool],
 *   ]]
 *
 * Normalized response (output):
 *   ['kind' => 'tool_use'|'message',
 *    'text' => string,
 *    'tool_uses' => [['id'=>..., 'name'=>..., 'input'=>...]],
 *    'raw' => decoded api response]
 */
function ai_provider_anthropic_call(array $provider, string $model, string $system, array $messages, array $tools, int $maxTokens): array
{
    $url = $provider['apiUrl'];
    $apiKey = $provider['apiKey'];
    if ($apiKey === '') {
        throw new RuntimeException('Anthropic API key not configured');
    }

    $payload = [
        'model'      => $model,
        'max_tokens' => $maxTokens,
        'system'     => $system,
        'tools'      => array_map(fn($t) => [
            'name'         => $t['name'],
            'description'  => $t['description'],
            'input_schema' => $t['input_schema'],
        ], $tools),
        'messages'   => array_map('ai_anthropic_msg_normalize', $messages),
    ];

    $resp = ai_http_json_post($url, $payload, [
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
        'content-type: application/json',
    ]);

    if (!is_array($resp) || !isset($resp['content'])) {
        $errMsg = is_array($resp) ? json_encode($resp) : (string)$resp;
        throw new RuntimeException('Anthropic API error: ' . $errMsg);
    }

    $text = '';
    $toolUses = [];
    foreach ($resp['content'] as $block) {
        $t = $block['type'] ?? '';
        if ($t === 'text') {
            $text .= ($block['text'] ?? '');
        } elseif ($t === 'tool_use') {
            $toolUses[] = [
                'id'    => $block['id'] ?? '',
                'name'  => $block['name'] ?? '',
                'input' => $block['input'] ?? [],
            ];
        }
    }

    return [
        'kind'      => $toolUses ? 'tool_use' : 'message',
        'text'      => $text,
        'tool_uses' => $toolUses,
        'raw'       => $resp,
    ];
}

function ai_anthropic_msg_normalize(array $m): array
{
    $role = $m['role'] === 'assistant' ? 'assistant' : 'user';
    $content = [];
    foreach ($m['blocks'] ?? [] as $b) {
        switch ($b['type'] ?? '') {
            case 'text':
                $content[] = ['type' => 'text', 'text' => (string)($b['text'] ?? '')];
                break;
            case 'tool_use':
                $content[] = [
                    'type'  => 'tool_use',
                    'id'    => (string)($b['id'] ?? ''),
                    'name'  => (string)($b['name'] ?? ''),
                    'input' => ai_input_object($b['input'] ?? null),
                ];
                break;
            case 'tool_result':
                $content[] = [
                    'type'        => 'tool_result',
                    'tool_use_id' => (string)($b['tool_use_id'] ?? ''),
                    'content'     => (string)($b['content'] ?? ''),
                    'is_error'    => (bool)($b['is_error'] ?? false),
                ];
                break;
        }
    }
    return ['role' => $role, 'content' => $content];
}
