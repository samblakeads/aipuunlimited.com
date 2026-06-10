<?php
/**
 * Convert OmniRogue chrome nav popups → real multistep page links.
 *
 * Each `<button ... data-or-popup="X" ...>INNER</button>` whose token X has a
 * known PHP page mapping gets rewritten to:
 *   <a class="..." href="/<folder>/X.php<?= $__step1link; ?>" ...>INNER</a>
 *
 * Special tokens kept as popups (no page equivalent):
 *   affiliate, refund
 *
 * Idempotent — re-running is safe (any button without data-or-popup is left
 * alone; <a> elements are left alone).
 */
$base = realpath(__DIR__ . '/../..');

$folders = [
    'pricing-v2-kk1', 'flash-sale-kk1', 'plans-v3-sam-lifetime-kk1',
    'sabrina-pricing-v2-kk1', 'sabrina-flash-sale-kk1',
    'sabrina-plansv3-samlifetime-kk1', 'unlimited-plansv3-kk1',
];

$popupToPage = [
    'create-video'        => 'createvideo.php',
    'create-image'        => 'create-image.php',
    'create-audio'        => 'create-audio.php',
    'create-music'        => 'create-music.php',
    'create-voice-agents' => 'create-voice-agents.php',
    'create-upscale'      => 'create-upscale.php',
    'create-ai-chat'      => 'create-ai-chat.php',
    'gpt-library'         => 'gpt-library.php',
    'prompt-library'      => 'prompt-library.php',
    'knowledge-base'      => 'knowledge-base.php',
    'about'               => 'about.php',
    'home'                => 'index.php',
    'terms'               => 'terms-of-service.php',
    'privacy'             => 'privacy-policy.php',
    'acceptable-use'      => 'acceptable-use-policy.php',
    'data-deletion'       => 'data-deletion-request.php',
];

$count = 0;

foreach ($folders as $folder) {
    $folderPath = $base . '/' . $folder;
    if (!is_dir($folderPath)) continue;

    foreach (['index.php', 'checkout.php'] as $file) {
        $path = $folderPath . '/' . $file;
        if (!is_file($path)) continue;
        $c = file_get_contents($path);
        $orig = $c;

        foreach ($popupToPage as $tok => $page) {
            $href = '/' . $folder . '/' . $page . '<' . '?= $__step1link; ?' . '>';
            $tokQ = preg_quote($tok, '~');

            /* Match an entire <button data-or-popup="X" ...>INNER</button> element.
               Allow attributes in any order — use a permissive "any chars" pattern
               that lookahead-skips up to the matching </button>. */
            $pattern =
                '~<button\b' .                         // opening
                '(?P<attrs1>[^>]*?)' .                 // attrs before data-or-popup
                'data-or-popup="' . $tokQ . '"' .      // the token
                '(?P<attrs2>[^>]*?)' .                 // attrs after data-or-popup
                '>(?P<inner>[\s\S]*?)</button>~';      // content + close
            $c = preg_replace_callback($pattern, function ($m) use ($href, $tok) {
                /* Pull class= out of attrs1 + attrs2 to preserve styling */
                $combined = $m['attrs1'] . $m['attrs2'];
                $class = '';
                if (preg_match('~\bclass="([^"]*)"~', $combined, $cm)) {
                    $class = $cm[1];
                }
                /* Pull style= if present */
                $style = '';
                if (preg_match('~\bstyle="([^"]*)"~', $combined, $sm)) {
                    $style = $sm[1];
                }
                /* Pull role= if present */
                $role = '';
                if (preg_match('~\brole="([^"]*)"~', $combined, $rm)) {
                    $role = $rm[1];
                }

                $out = '<a class="' . $class . '" href="' . $href . '"';
                if ($role) $out .= ' role="' . $role . '"';
                /* preserve text-decoration:none so the nav-link doesn't show an underline */
                $styleVal = trim('text-decoration:none;' . ($style ? ' ' . $style : ''));
                $out .= ' style="' . htmlspecialchars($styleVal, ENT_QUOTES, 'UTF-8') . '"';
                $out .= '>' . $m['inner'] . '</a>';
                return $out;
            }, $c);
        }

        if ($c !== $orig) {
            file_put_contents($path, $c);
            $count++;
            echo "patched: $folder/$file\n";
        }
    }
}

echo "done — $count files patched\n";
