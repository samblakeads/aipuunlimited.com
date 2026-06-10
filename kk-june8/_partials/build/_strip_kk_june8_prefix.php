<?php
/**
 * Strip `/kk-june8/` prefix from every asset path in every PHP/HTML file
 * inside the 7 KK folders, matching KK spec which requires `/<folder>/...`
 * paths (not `/<parent>/<folder>/...`).
 *
 * Also updates each `_kk-config.php`:
 *   $__web = '/kk-june8/foo'   →  $__web = '/foo'
 *
 * Combined with symlinks at htdocs root (created separately), this restores
 * KK-spec compliance: a URL like /foo/checkout.php resolves to the file at
 * /htdocs/foo/checkout.php (where `foo` is a symlink → kk-june8/foo/).
 *
 * Idempotent — running twice is safe.
 */
$base = realpath(__DIR__ . '/../..');

$folders = [
    'pricing-v2-kk1',
    'flash-sale-kk1',
    'plans-v3-sam-lifetime-kk1',
    'sabrina-pricing-v2-kk1',
    'sabrina-flash-sale-kk1',
    'sabrina-plansv3-samlifetime-kk1',
    'unlimited-plansv3-kk1',
];

/* Find every file that might contain the prefix.
   Walk recursively so we also catch nested .css/.js/.json/.svg/.html. */
function walk($dir, &$files) {
    $h = opendir($dir);
    while (false !== ($f = readdir($h))) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        if (is_dir($p)) walk($p, $files);
        else $files[] = $p;
    }
    closedir($h);
}

$total = 0;
foreach ($folders as $folder) {
    $folderPath = $base . '/' . $folder;
    if (!is_dir($folderPath)) { fwrite(STDERR, "skip (missing): $folder\n"); continue; }

    $files = [];
    walk($folderPath, $files);

    $touched = 0;
    foreach ($files as $f) {
        /* only process text-y files */
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ['php','html','htm','css','js','json','svg','txt','md','xml'], true)) continue;
        $c = file_get_contents($f);
        if ($c === false || strpos($c, '/kk-june8/') === false) continue;
        $new = str_replace('/kk-june8/', '/', $c);
        if ($new !== $c) {
            file_put_contents($f, $new);
            $touched++;
        }
    }

    echo "stripped /kk-june8/ from $touched files in $folder/\n";
    $total += $touched;
}
echo "done — $total files updated total\n";
