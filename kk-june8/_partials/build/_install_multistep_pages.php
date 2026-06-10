<?php
/**
 * Install KK multistep side-pages into each of the 7 KK-june8 folders.
 *
 * For each folder:
 *   1. Copy the 18 PHP side-pages from the multistep-kk reference
 *      (about.php, createvideo.php, create-image.php, create-audio.php,
 *       create-music.php, create-voice-agents.php, create-upscale.php,
 *       create-ai-chat.php, create-omnireels.php, create-podcast.php,
 *       gpt-library.php, prompt-library.php, knowledge-base.php,
 *       help-center.php, terms-of-service.php, privacy-policy.php,
 *       acceptable-use-policy.php, data-deletion-request.php)
 *   2. Rewrite asset paths inside each copy:
 *        /omnifull-plans-v3-299-kk/ → /<folder>/
 *   3. Replace AIPU brand references with OmniRogue
 *   4. Copy shared /assets/ folder (CSS, JS, fonts, logo) into each
 *
 * Idempotent — re-running just overwrites.
 */

$srcLander = '/var/www/aipuunlimited.com/htdocs/multistep-kk/omnifull-plans-v3-299-kk';
$srcAssets = $srcLander . '/assets';
$base      = realpath(__DIR__ . '/../..');

$folders = [
    'pricing-v2-kk1',
    'flash-sale-kk1',
    'plans-v3-sam-lifetime-kk1',
    'sabrina-pricing-v2-kk1',
    'sabrina-flash-sale-kk1',
    'sabrina-plansv3-samlifetime-kk1',
    'unlimited-plansv3-kk1',
];

$sidePages = glob($srcLander . '/*.php');
$sidePages = array_filter($sidePages, function ($f) {
    $b = basename($f);
    /* Skip index, checkout, and underscore-prefixed (KK config/offers) */
    return $b !== 'index.php'
        && $b !== 'checkout.php'
        && strpos($b, '_') !== 0;
});

function recurse_copy($src, $dst) {
    if (!is_dir($dst)) mkdir($dst, 0755, true);
    $h = opendir($src);
    while (false !== ($f = readdir($h))) {
        if ($f === '.' || $f === '..') continue;
        $s = $src . '/' . $f;
        $d = $dst . '/' . $f;
        if (is_dir($s)) recurse_copy($s, $d);
        else if (!is_file($d) || filemtime($s) > filemtime($d)) copy($s, $d);
    }
    closedir($h);
}

foreach ($folders as $folder) {
    $folderPath = $base . '/' . $folder;
    if (!is_dir($folderPath)) { fwrite(STDERR, "skip: $folder\n"); continue; }

    /* 1. Copy & rewrite each side-page */
    $copied = 0;
    foreach ($sidePages as $src) {
        $b = basename($src);
        $dst = $folderPath . '/' . $b;
        $c = file_get_contents($src);
        /* Rewrite asset paths */
        $c = str_replace(
            '/omnifull-plans-v3-299-kk/',
            '/' . $folder . '/',
            $c
        );
        /* Brand clean-ups (don't touch the protected lifetime/credit checks) */
        $c = str_replace('AIPU LLC',              'OmniRogue Inc.', $c);
        $c = str_replace('"AIPU"',                '"OmniRogue"', $c);
        $c = str_replace('>AIPU<',                '>OmniRogue<', $c);
        $c = str_replace(' AIPU ',                ' OmniRogue ', $c);
        $c = str_replace(' AIPU.',                ' OmniRogue.', $c);
        $c = str_replace(' AIPU,',                ' OmniRogue,', $c);
        $c = str_replace('aipuapp.com',           'omnirogueapp.com', $c);
        $c = str_replace('logo-aipu',             'logo-omnirogue', $c);
        $c = str_replace('aipu-logo-horizontal',  'logo-omnirogue', $c);
        file_put_contents($dst, $c);
        $copied++;
    }

    /* 2. Copy the assets/ folder (shared CSS, JS, fonts, logo) if not already there */
    $assetsDst = $folderPath . '/assets';
    if (!is_dir($assetsDst) || count(glob($assetsDst . '/*.css')) === 0) {
        recurse_copy($srcAssets, $assetsDst);
        /* Strip AIPU in CSS/JS bundles too */
        foreach (glob($assetsDst . '/*.{css,js}', GLOB_BRACE) as $f) {
            $c = file_get_contents($f);
            $orig = $c;
            $c = str_replace('AIPU',  'OmniRogue', $c);
            $c = str_replace('/omnifull-plans-v3-299-kk/', '/' . $folder . '/', $c);
            if ($c !== $orig) file_put_contents($f, $c);
        }
        echo "  copied assets/ → $folder/assets/\n";
    } else {
        echo "  noop (assets/ exists with CSS): $folder/assets/\n";
    }

    echo "done $folder: $copied side-pages\n";
}
echo "\nFINAL: $copied pages × " . count($folders) . " folders\n";
