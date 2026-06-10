<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/safety.php';
require_once __DIR__ . '/tools.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/providers/anthropic.php';
require_once __DIR__ . '/providers/openai.php';

/**
 * Calls the right provider given a normalized request. This is the *one*
 * function the rest of the app uses to talk to any AI provider.
 *
 *   ai_provider_call('anthropic', 'claude-...', $system, $messages, $tools, 4096)
 *
 * Returns the normalized provider response described in providers/anthropic.php.
 */
function ai_provider_call(string $providerId, string $model, string $system, array $messages, array $tools, int $maxTokens): array
{
    $cfg = ai_config();
    $providers = $cfg['providers'];
    if (!isset($providers[$providerId])) {
        throw new RuntimeException('Unknown provider: ' . $providerId);
    }
    $p = $providers[$providerId];
    if (!$p['available']) {
        throw new RuntimeException(ucfirst($providerId) . ' is not configured (missing ' . $p['envKey'] . ')');
    }

    switch ($providerId) {
        case 'anthropic': return ai_provider_anthropic_call($p, $model, $system, $messages, $tools, $maxTokens);
        case 'openai':    return ai_provider_openai_call($p, $model, $system, $messages, $tools, $maxTokens);
        case 'xai':       return ai_provider_xai_call($p, $model, $system, $messages, $tools, $maxTokens);
    }
    throw new RuntimeException('Provider not implemented: ' . $providerId);
}

/**
 * Single, top-level entry point used by the editor endpoint.
 *
 *   $req = [
 *     'provider' => 'anthropic',
 *     'model'    => null|string,             // optional override
 *     'scope'    => ['kind'=>...,'name'=>...,'collection'=>...],
 *     'instruction' => 'change the headline',
 *     'history'  => [],                      // prior conversation (optional)
 *   ]
 *
 * Returns:
 *   ['ok'=>true, 'status'=>'done|failed|...', 'summary'=>..., 'changed'=>[...],
 *    'warnings'=>[...], 'turns'=>N, 'audit'=>{...}]
 */
function runAiEditorRequest(array $req): array
{
    $cfg = ai_config();

    // Validate scope first so authorisation is independent of provider config.
    try {
        $scope = ai_resolve_scope((array)($req['scope'] ?? []));
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Scope error: ' . $e->getMessage()];
    }

    $instruction = trim((string)($req['instruction'] ?? ''));
    if ($instruction === '') {
        return ['ok' => false, 'error' => 'instruction is empty'];
    }

    $providerId = (string)($req['provider'] ?? $cfg['default']);
    if (!isset($cfg['providers'][$providerId])) {
        return ['ok' => false, 'error' => 'Unknown provider: ' . $providerId];
    }
    $provider = $cfg['providers'][$providerId];
    if (!$provider['available']) {
        return [
            'ok' => false,
            'error' => 'Provider "' . $providerId . '" is not configured. '
                     . 'Add ' . $provider['envKey'] . ' to /var/www/aipuunlimited.com/.env',
        ];
    }
    $model = (string)($req['model'] ?? '') ?: $provider['defaultModel'];
    if ($model === '') {
        return ['ok' => false, 'error' => 'No model specified for provider ' . $providerId];
    }

    $session  = new AiEditorSession($scope);
    $tools    = ai_tool_specs();
    $system   = ai_build_system_prompt($scope);

    $messages = [];
    $messages[] = [
        'role'   => 'user',
        'blocks' => [['type' => 'text', 'text' => ai_build_first_user_message($scope, $instruction)]],
    ];

    $maxTurns  = $cfg['limits']['max_turns'];
    $maxTokens = $cfg['limits']['max_tokens'];
    $turns     = 0;
    $finalText = '';
    $error     = null;

    $maxContextChars = (int)($cfg['limits']['max_context_chars'] ?? 320000);

    while ($turns < $maxTurns) {
        $turns++;
        $messages = ai_trim_history($messages, $maxContextChars);
        try {
            $resp = ai_provider_call($providerId, $model, $system, $messages, $tools, $maxTokens);
        } catch (Throwable $e) {
            $error = $e->getMessage();
            break;
        }

        if ($resp['kind'] === 'message' && empty($resp['tool_uses'])) {
            $finalText = $resp['text'];
            break;
        }

        // Append the assistant turn (text + tool_uses) to history
        $assistantBlocks = [];
        if (!empty($resp['text'])) {
            $assistantBlocks[] = ['type' => 'text', 'text' => $resp['text']];
        }
        foreach ($resp['tool_uses'] as $tu) {
            $assistantBlocks[] = [
                'type'  => 'tool_use',
                'id'    => $tu['id'],
                'name'  => $tu['name'],
                'input' => $tu['input'],
            ];
        }
        $messages[] = ['role' => 'assistant', 'blocks' => $assistantBlocks];

        // Run each tool and append the corresponding tool_result blocks
        $resultBlocks = [];
        foreach ($resp['tool_uses'] as $tu) {
            $result = $session->run($tu['name'], (array)$tu['input']);
            $resultBlocks[] = [
                'type'        => 'tool_result',
                'tool_use_id' => $tu['id'],
                'content'     => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'is_error'    => empty($result['ok']),
            ];
            if ($tu['name'] === 'finish') {
                // After finish, give the model one chance to send a final text turn.
                $messages[] = ['role' => 'user', 'blocks' => $resultBlocks];
                $finalText  = $session->finished['summary'] ?? '';
                $resp2 = null;
                if (!empty($resp['text'])) $finalText = $resp['text']; // prefer assistant prose if any
                break 2;
            }
        }
        $messages[] = ['role' => 'user', 'blocks' => $resultBlocks];
    }

    if ($error !== null) {
        $payload = [
            'ok'       => false,
            'error'    => $error,
            'changed'  => array_keys($session->changedFiles),
            'turns'    => $turns,
            'provider' => $providerId,
            'model'    => $model,
        ];
        ai_audit_append([
            'kind'      => 'edit',
            'scope'     => ['kind' => $scope['kind'], 'name' => $scope['name'], 'collection' => $scope['collection']],
            'provider'  => $providerId,
            'model'     => $model,
            'instruction' => $instruction,
            'status'    => 'failed',
            'summary'   => $error,
            'changed'   => $session->changedFiles,
            'warnings'  => $session->warnings,
            'turns'     => $turns,
        ]);
        return $payload;
    }

    if ($session->finished) {
        $finished = $session->finished;
    } elseif ($finalText !== '') {
        // Model ended with a plain message and no explicit finish() call.
        $finished = [
            'status'  => empty($session->changedFiles) ? 'needs_more_context' : 'done',
            'summary' => $finalText,
        ];
    } else {
        // Loop exhausted the turn budget without finishing.
        $changed = !empty($session->changedFiles);
        $finished = [
            'status'  => $changed ? 'done' : 'failed',
            'summary' => $changed
                ? 'Edits were applied, but the assistant hit the step limit before summarising. Review the changed files below.'
                : 'Stopped after the maximum number of steps without completing the edit. Try a more specific instruction (e.g. quote the exact text to change) or a smaller change.',
        ];
    }

    $payload = [
        'ok'       => $finished['status'] !== 'failed',
        'status'   => $finished['status'],
        'summary'  => $finished['summary'] ?: $finalText,
        'changed'  => $session->changedFiles,
        'warnings' => $session->warnings,
        'turns'    => $turns,
        'provider' => $providerId,
        'model'    => $model,
        'runId'    => $session->runId,
    ];

    ai_audit_append([
        'kind'        => 'edit',
        'scope'       => ['kind' => $scope['kind'], 'name' => $scope['name'], 'collection' => $scope['collection']],
        'provider'    => $providerId,
        'model'       => $model,
        'instruction' => $instruction,
        'status'      => $finished['status'],
        'summary'     => $finished['summary'] ?: $finalText,
        'changed'     => $session->changedFiles,
        'warnings'    => $session->warnings,
        'turns'       => $turns,
        'runId'       => $session->runId,
    ]);

    return $payload;
}

/**
 * Bound the conversation size so we never blow past the model's context window.
 * Older, large tool_result payloads (file reads) are replaced with a stub once
 * the estimated size exceeds the budget. The last 3 messages are kept intact so
 * the model always has fresh, full context to act on.
 */
function ai_trim_history(array $messages, int $maxChars): array
{
    $len = 0;
    foreach ($messages as $m) {
        foreach ($m['blocks'] ?? [] as $b) {
            if (isset($b['text']))    $len += strlen((string)$b['text']);
            if (isset($b['content'])) $len += strlen((string)$b['content']);
            if (isset($b['input']))   $len += strlen((string)json_encode($b['input']));
        }
    }
    if ($len <= $maxChars) {
        return $messages;
    }

    $keepFrom = max(0, count($messages) - 3);
    foreach ($messages as $idx => &$m) {
        if ($idx >= $keepFrom) {
            continue;
        }
        foreach ($m['blocks'] as &$b) {
            if (($b['type'] ?? '') === 'tool_result'
                && isset($b['content'])
                && strlen((string)$b['content']) > 600) {
                $b['content'] = '[older tool result truncated to save context — re-read or re-search if you still need it]';
            }
        }
        unset($b);
    }
    unset($m);
    return $messages;
}

/**
 * Build the system prompt with the project's hard rules baked in.
 */
function ai_build_system_prompt(array $scope): string
{
    $kind  = $scope['kind'];
    $brand = $scope['brand'];
    $brandLabel = $brand === 'aipu' ? 'AIPU' : 'OmniRogue';
    $isCheckout = $kind === 'checkout';
    $isFlow = $kind === 'flow';

    return <<<TXT
You are the AI Editor for AIPU Unlimited — a code agent that edits checkout pages,
sales/presell landing pages, and full funnel flows by directly modifying files
on disk through a small set of tools.

## Your scope right now
- Page kind:       {$kind}
- Brand:           {$brandLabel}
- Folder name:     {$scope['name']}
- Filesystem root: {$scope['root']}
- Web root:        {$scope['webRoot']}

You may ONLY read and write files inside the filesystem root above. All paths
you pass to tools are RELATIVE to that root. Never use absolute paths or "..".

## Workflow (IMPORTANT — these pages are large, often 150-280KB)
1. Call list_files first to see what exists and identify the entry file
   (index.html, index.php, checkout.html, checkout.php, or the file the
   user pointed at).
2. DO NOT read whole large files. Instead call search_in_file to locate the
   exact text the user wants changed (e.g. search for a few words of the
   current headline, the button label, or a section keyword). The matching
   line text it returns can be used verbatim as old_string.
3. If you need more surrounding context, call read_file with start_line and
   end_line for just that region (a few dozen lines). Only read an entire
   file when it is small (under ~60KB).
4. Make the smallest correct edit. Strongly prefer replace_in_file with a
   unique old_string. Use write_file ONLY for small files or brand-new files
   — never to rewrite a large page.
5. When done, call the finish tool exactly once with status="done" and a
   short, plain-English summary of what changed.
6. If the request is impossible or risky, call finish with status="failed",
   "needs_confirmation", or "needs_more_context" and explain why.

## Staying within the context budget
- Keep tool calls focused. Search, then read a narrow range, then edit.
- Reading the same large file repeatedly will exhaust the context window and
  cause the run to fail. Search precisely instead.

## Logo rules
- When adding or referencing logos anywhere on a page (header, footer, body sections, etc.) ALWAYS use a transparent PNG — never a JPEG or any image with a solid background. Transparent logos have an RGBA PNG format so the dark page background shows through cleanly.
- For OmniRogue pages the shared transparent logo is at `/checkouts-omni/logo-omnirogue.png`. If the page already has its own local logo in its assets folder (e.g. `/omnirogue/lander11/assets/logo-omnirogue-h.png`) and it is a transparent PNG, use that; if it is a JPEG, use the shared `/checkouts-omni/logo-omnirogue.png` instead.
- For AIPU pages use the local transparent logo already in the page's own assets folder (e.g. `/{folder}/assets/logo-aipu-h.png`).
- Never introduce a new non-transparent logo file. If you cannot confirm a logo is transparent, default to the shared `/checkouts-omni/logo-omnirogue.png` (OmniRogue) or the page's existing transparent local logo (AIPU).

## Hard rules — DO NOT VIOLATE
- Do not edit, replace, move, or remove the PHP header that loads
  money.php or safe.php. Cloaked pages (index.php / checkout.php inside
  KK funnels) load money.php on line 1; customer-facing pages load safe.php.
- Do not edit _kk-config.php or _checkout-offers.php (the tools will refuse).
- Do not change KowboyKit offer tokens (\$offer[...], \$link['step1link'],
  \$multi_page['step1link']) or any Stripe / payment IDs.
- Do not touch tracking pixels, clickid handling, or form action URLs that
  point at /signup, /register, or KowboyKit endpoints.
- Do not hardcode /signup URLs. registercreate = register that opens Create
  Studio; registercheckout = register that starts billing/checkout. Use
  those exact tokens only where appropriate; do not swap them in for
  normal plan/product CTAs.
- Keep all asset paths absolute and rooted at /{folder-name}/... — never
  ./assets, ../assets, or another folder's name.
- Keep PHP syntax valid. The editor will revert any edit that fails php -l.
- Plan/product buttons must keep their own individual offer links. Don't
  collapse multiple plan buttons into one.

## Behavioural rules
- Be terse but concrete in your reasoning blocks; the user mainly cares
  about the final summary.
- If asked to "make it more premium" or similar vague requests, focus on
  copy, hierarchy, spacing — not wiring.
- Prefer copy/CSS edits over rewriting whole pages.
- If a file would exceed 200KB after editing, split your work or ask
  via finish(needs_more_context).

The user's instruction follows in the next message.
TXT;
}

function ai_build_first_user_message(array $scope, string $instruction): string
{
    $kind  = $scope['kind'];
    $name  = $scope['name'];
    $brand = $scope['brand'];

    $hint = match ($kind) {
        'flow'     => "This is a full funnel flow. Look for index.html (sales) and checkout.html (plan picker). The kk/ subfolder is the production PHP build — only edit it if the user explicitly asks; otherwise edit the .html files and the user can re-run KK Format.",
        'checkout' => "This is a checkout / plan-picker page. Be careful around plan cards, prices, and CTA buttons.",
        'lander'   => "This is a sales / presell page. Hero, offer, pricing tease, social proof, FAQ, footer.",
        default    => '',
    };

    return "Page: {$kind} ({$brand}) — {$name}\n{$hint}\n\nUser instruction:\n{$instruction}";
}

/**
 * Tiny cURL JSON helper used by every provider.
 */
function ai_http_json_post(string $url, array $payload, array $headers): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 180,
        CURLOPT_CONNECTTIMEOUT => 30,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException('cURL error: ' . $err);
    }
    $decoded = json_decode((string)$body, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Non-JSON response (HTTP ' . $code . '): ' . substr((string)$body, 0, 400));
    }
    if ($code >= 400) {
        $msg = $decoded['error']['message'] ?? ($decoded['error'] ?? null);
        if (is_array($msg)) $msg = json_encode($msg);
        throw new RuntimeException('HTTP ' . $code . ' — ' . ($msg ?: substr((string)$body, 0, 400)));
    }
    return $decoded;
}
