<?php
declare(strict_types=1);

require_once __DIR__ . '/safety.php';

/** Max bytes returned by a single un-ranged read_file (keeps context bounded). */
const AI_READ_CAP_BYTES = 60000;

/**
 * Returns the JSON-schema tool list given to the model. Provider adapters
 * translate this shape into Anthropic / OpenAI / xAI native formats.
 */
function ai_tool_specs(): array
{
    return [
        [
            'name' => 'list_files',
            'description' => 'List files and folders inside the current page/flow scope. Use this first to discover what exists.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'Optional sub-folder relative to the scope root (e.g. "assets/css"). Empty = scope root.',
                    ],
                ],
                'required' => [],
            ],
        ],
        [
            'name' => 'search_in_file',
            'description' => 'Find where text appears in a file WITHOUT loading the whole file. Returns matching lines with their line numbers and a little surrounding context. ALWAYS use this on large pages (e.g. to locate a headline, button, or section) before reading or editing. The returned line text can be used directly as the old_string for replace_in_file.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'path'          => ['type' => 'string', 'description' => 'Path relative to the scope root.'],
                    'query'         => ['type' => 'string', 'description' => 'Literal text to search for (case-insensitive).'],
                    'context_lines' => ['type' => 'integer', 'description' => 'Lines of context to include around each match (0-5, default 2).'],
                    'max_results'   => ['type' => 'integer', 'description' => 'Max matches to return (default 20).'],
                ],
                'required' => ['path', 'query'],
            ],
        ],
        [
            'name' => 'read_file',
            'description' => 'Read a UTF-8 text file inside the scope. For LARGE files (these pages can be 200KB+), pass start_line and end_line to read only the region you need — reading whole large files wastes the context window and can fail. Use search_in_file first to find the right line range.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'path'       => ['type' => 'string', 'description' => 'Path relative to the scope root.'],
                    'start_line' => ['type' => 'integer', 'description' => 'Optional 1-based first line to read.'],
                    'end_line'   => ['type' => 'integer', 'description' => 'Optional 1-based last line to read (defaults to start_line + 200).'],
                ],
                'required' => ['path'],
            ],
        ],
        [
            'name' => 'replace_in_file',
            'description' => 'Make a targeted edit by replacing one literal string with another inside a single file. The old_string must appear EXACTLY once in the file. Preferred over write_file for small edits.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'path'       => ['type' => 'string', 'description' => 'Path relative to the scope root.'],
                    'old_string' => ['type' => 'string', 'description' => 'The exact text to find. Must be unique in the file.'],
                    'new_string' => ['type' => 'string', 'description' => 'The replacement text.'],
                ],
                'required' => ['path', 'old_string', 'new_string'],
            ],
        ],
        [
            'name' => 'write_file',
            'description' => 'Overwrite a file with new contents (or create one). Use only when replace_in_file is impractical. Will fail on blocked files (money.php, _kk-config.php, _checkout-offers.php, db.php, .env).',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'path'    => ['type' => 'string', 'description' => 'Path relative to the scope root.'],
                    'content' => ['type' => 'string', 'description' => 'Full new file content.'],
                ],
                'required' => ['path', 'content'],
            ],
        ],
        [
            'name' => 'finish',
            'description' => 'Call this exactly once when you are done. Provide a clear status and a one-paragraph summary of what changed.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'status'  => [
                        'type' => 'string',
                        'enum' => ['done', 'failed', 'needs_confirmation', 'needs_more_context'],
                    ],
                    'summary' => ['type' => 'string', 'description' => 'Short, plain-English summary suitable for the user.'],
                ],
                'required' => ['status', 'summary'],
            ],
        ],
    ];
}

/**
 * Stateful tool executor. Tracks files touched and warnings.
 */
class AiEditorSession
{
    public array $scope;
    public array $changedFiles = [];   // [path => ['action'=>..., 'before'=>..., 'after'=>...]]
    public array $warnings = [];
    public array $logEvents = [];
    public ?array $finished = null;
    public string $runId;

    public function __construct(array $scope)
    {
        $this->scope = $scope;
        $this->runId = bin2hex(random_bytes(6));
    }

    public function run(string $tool, array $args): array
    {
        try {
            switch ($tool) {
                case 'list_files':     return $this->toolList((string)($args['path'] ?? ''));
                case 'search_in_file': return $this->toolSearch($args);
                case 'read_file':      return $this->toolRead(
                    (string)($args['path'] ?? ''),
                    isset($args['start_line']) ? (int)$args['start_line'] : null,
                    isset($args['end_line'])   ? (int)$args['end_line']   : null
                );
                case 'replace_in_file':return $this->toolReplace($args);
                case 'write_file':     return $this->toolWrite($args);
                case 'finish':         return $this->toolFinish($args);
            }
            return ['ok' => false, 'error' => 'Unknown tool: ' . $tool];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function toolList(string $rel): array
    {
        $base = $rel === ''
            ? $this->scope['root']
            : ai_resolve_path($this->scope, $rel, false);

        if (!is_dir($base)) {
            return ['ok' => false, 'error' => 'Not a directory: ' . $rel];
        }

        $entries = [];
        $relPrefix = $rel === '' ? '' : rtrim($rel, '/') . '/';
        foreach (scandir($base) ?: [] as $name) {
            if ($name === '.' || $name === '..') continue;
            $full = $base . '/' . $name;
            $entries[] = [
                'name' => $name,
                'path' => $relPrefix . $name,
                'type' => is_dir($full) ? 'dir' : 'file',
                'size' => is_file($full) ? filesize($full) : null,
            ];
        }
        usort($entries, fn($a, $b) => ($b['type'] === 'dir' ? 1 : 0) - ($a['type'] === 'dir' ? 1 : 0)
            ?: strcasecmp($a['name'], $b['name']));
        return ['ok' => true, 'path' => $rel, 'entries' => $entries];
    }

    private function toolRead(string $rel, ?int $startLine, ?int $endLine): array
    {
        $abs = ai_resolve_path($this->scope, $rel, false);
        if (!is_file($abs)) {
            return ['ok' => false, 'error' => 'File not found: ' . $rel];
        }
        $size = (int)filesize($abs);
        $raw   = (string)@file_get_contents($abs);
        $lines = preg_split("/\r\n|\r|\n/", $raw);
        $total = count($lines);

        // Ranged read -------------------------------------------------------
        if ($startLine !== null) {
            $start = max(1, $startLine);
            $end   = $endLine !== null ? max($start, $endLine) : ($start + 200);
            $end   = min($end, $total);
            $slice = array_slice($lines, $start - 1, $end - $start + 1);
            return [
                'ok'         => true,
                'path'       => $rel,
                'total_lines'=> $total,
                'start_line' => $start,
                'end_line'   => $end,
                'content'    => implode("\n", $slice),
                'note'       => "Showing lines {$start}-{$end} of {$total}.",
                'readonly'   => ai_is_readonly_path($abs, $this->scope),
            ];
        }

        // Whole-file read (small files only) --------------------------------
        if ($size <= AI_READ_CAP_BYTES) {
            return [
                'ok'         => true,
                'path'       => $rel,
                'total_lines'=> $total,
                'size'       => $size,
                'content'    => $raw,
                'truncated'  => false,
                'readonly'   => ai_is_readonly_path($abs, $this->scope),
            ];
        }

        // Too large: return only the head and steer toward search/ranges ----
        $head = substr($raw, 0, AI_READ_CAP_BYTES);
        return [
            'ok'         => true,
            'path'       => $rel,
            'total_lines'=> $total,
            'size'       => $size,
            'content'    => $head,
            'truncated'  => true,
            'note'       => "File is {$size} bytes ({$total} lines). Only the first " . strlen($head)
                          . " bytes are shown. Use search_in_file to locate what you need, then read_file"
                          . " with start_line/end_line. Do NOT use write_file on a file this large — use replace_in_file.",
            'readonly'   => ai_is_readonly_path($abs, $this->scope),
        ];
    }

    private function toolSearch(array $args): array
    {
        $rel   = (string)($args['path'] ?? '');
        $query = (string)($args['query'] ?? '');
        $ctx   = max(0, min(5, (int)($args['context_lines'] ?? 2)));
        $max   = max(1, min(50, (int)($args['max_results'] ?? 20)));

        if ($query === '') {
            return ['ok' => false, 'error' => 'query is empty'];
        }
        $abs = ai_resolve_path($this->scope, $rel, false);
        if (!is_file($abs)) {
            return ['ok' => false, 'error' => 'File not found: ' . $rel];
        }

        $raw   = (string)@file_get_contents($abs);
        $lines = preg_split("/\r\n|\r|\n/", $raw);
        $total = count($lines);
        $needle = mb_strtolower($query);

        $matches = [];
        foreach ($lines as $i => $line) {
            if ($needle !== '' && mb_strpos(mb_strtolower($line), $needle) !== false) {
                $ln   = $i + 1;
                $from = max(1, $ln - $ctx);
                $to   = min($total, $ln + $ctx);
                $context = [];
                for ($j = $from; $j <= $to; $j++) {
                    $context[] = ['line' => $j, 'text' => $lines[$j - 1]];
                }
                $matches[] = ['line' => $ln, 'text' => $line, 'context' => $context];
                if (count($matches) >= $max) {
                    break;
                }
            }
        }

        return [
            'ok'          => true,
            'path'        => $rel,
            'total_lines' => $total,
            'query'       => $query,
            'match_count' => count($matches),
            'matches'     => $matches,
        ];
    }

    private function toolReplace(array $args): array
    {
        $rel = (string)($args['path'] ?? '');
        $old = (string)($args['old_string'] ?? '');
        $new = (string)($args['new_string'] ?? '');

        if ($old === '') {
            return ['ok' => false, 'error' => 'old_string must not be empty'];
        }
        if ($old === $new) {
            return ['ok' => false, 'error' => 'old_string and new_string are identical'];
        }

        $abs = ai_resolve_path($this->scope, $rel, true);
        if (!is_file($abs)) {
            return ['ok' => false, 'error' => 'File not found: ' . $rel];
        }
        if (ai_is_blocked_basename($abs)) {
            return ['ok' => false, 'error' => 'Cannot edit protected file: ' . basename($abs)];
        }

        $before = (string)file_get_contents($abs);
        $count = substr_count($before, $old);
        if ($count === 0) {
            return ['ok' => false, 'error' => 'old_string not found in file'];
        }
        if ($count > 1) {
            return ['ok' => false, 'error' => 'old_string appears ' . $count . ' times — make it unique'];
        }

        $after = strtr($before, [$old => $new]);
        $danger = ai_scan_dangerous($before, $after);
        if (!empty($danger)) {
            $this->warnings = array_merge($this->warnings, $danger);
        }

        return $this->commitWrite($abs, $rel, $before, $after, 'replace');
    }

    private function toolWrite(array $args): array
    {
        $rel = (string)($args['path'] ?? '');
        $content = (string)($args['content'] ?? '');
        $abs = ai_resolve_path($this->scope, $rel, true);

        if (ai_is_blocked_basename($abs)) {
            return ['ok' => false, 'error' => 'Cannot write protected file: ' . basename($abs)];
        }

        $before = is_file($abs) ? (string)file_get_contents($abs) : '';
        $after  = $content;

        $danger = ai_scan_dangerous($before, $after);
        if (!empty($danger)) {
            $this->warnings = array_merge($this->warnings, $danger);
        }

        return $this->commitWrite($abs, $rel, $before, $after, is_file($abs) ? 'write' : 'create');
    }

    private function commitWrite(string $abs, string $rel, string $before, string $after, string $action): array
    {
        // Backup
        ai_backup_file($this->runId, $this->scope, $abs, $before);

        if (!is_writable($abs) && is_file($abs)) {
            $owner = function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner($abs))['name'] ?? '?') : '?';
            return ['ok' => false, 'error' => 'Cannot write ' . $rel . ' — file is not writable by the web server'
                . ' (owned by "' . $owner . '"). A server admin needs to grant write access to this page folder.'];
        }
        if (@file_put_contents($abs, $after) === false) {
            $err = error_get_last()['message'] ?? 'unknown error';
            return ['ok' => false, 'error' => 'Failed to write ' . $rel . ': ' . $err];
        }

        // PHP syntax sanity check
        $phpWarning = null;
        if (preg_match('/\.php$/i', $abs)) {
            $check = ai_php_lint($abs);
            if ($check !== true) {
                // Restore backup
                @file_put_contents($abs, $before);
                return ['ok' => false, 'error' => 'PHP syntax error after edit, change reverted: ' . $check];
            }
        }

        $this->changedFiles[$rel] = [
            'action'      => $action,
            'sizeBefore'  => strlen($before),
            'sizeAfter'   => strlen($after),
            'phpWarning'  => $phpWarning,
        ];
        return [
            'ok'         => true,
            'path'       => $rel,
            'action'     => $action,
            'sizeBefore' => strlen($before),
            'sizeAfter'  => strlen($after),
        ];
    }

    private function toolFinish(array $args): array
    {
        $status  = (string)($args['status'] ?? 'done');
        $summary = (string)($args['summary'] ?? '');
        $this->finished = ['status' => $status, 'summary' => $summary];
        return ['ok' => true, 'status' => $status, 'summary' => $summary];
    }
}

/**
 * Snapshot a file before an AI write so the user can revert.
 */
function ai_backup_file(string $runId, array $scope, string $abs, string $before): void
{
    $cfg = ai_config();
    $rel = ltrim(str_replace($scope['root'], '', $abs), '/');
    $bdir = $cfg['backups'] . '/' . $runId . '/' . $scope['kind'] . '__' . $scope['name'];
    if (!is_dir($bdir)) {
        @mkdir($bdir, 0775, true);
    }
    $target = $bdir . '/' . $rel;
    @mkdir(dirname($target), 0775, true);
    if ($before !== '' || is_file($abs)) {
        @file_put_contents($target, $before);
    }
}

/**
 * Returns true on clean php -l, otherwise an error string.
 */
function ai_php_lint(string $abs)
{
    $php = '/usr/bin/php';
    if (!is_executable($php)) {
        $php = trim((string)@shell_exec('which php')) ?: 'php';
    }
    $cmd = escapeshellarg($php) . ' -l ' . escapeshellarg($abs) . ' 2>&1';
    $out = (string)shell_exec($cmd);
    if (strpos($out, 'No syntax errors') !== false) {
        return true;
    }
    return trim($out);
}
