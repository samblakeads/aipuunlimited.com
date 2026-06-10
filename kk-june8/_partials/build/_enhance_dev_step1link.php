<?php
/**
 * Enhance the dev fallback so `$multi_page['step1link']` carries the current
 * request's query string when money.php isn't available locally.  In production
 * (where money.php IS loaded) this fallback is skipped (the `if !isset` check
 * makes it a no-op).
 *
 * This makes our preview-mode multistep navigation preserve tracking the same
 * way it will in production.
 */
$base = realpath(__DIR__ . '/../..');
$folders = [
    'pricing-v2-kk1', 'flash-sale-kk1', 'plans-v3-sam-lifetime-kk1',
    'sabrina-pricing-v2-kk1', 'sabrina-flash-sale-kk1',
    'sabrina-plansv3-samlifetime-kk1', 'unlimited-plansv3-kk1',
];

$old = "if (!isset(\$multi_page)) { \$multi_page = ['step1link' => '']; }";
$new = "if (!isset(\$multi_page)) {\n"
     . "    \$kk_dev_qs = isset(\$_SERVER['QUERY_STRING']) && \$_SERVER['QUERY_STRING'] !== '' ? '?' . \$_SERVER['QUERY_STRING'] : '';\n"
     . "    \$multi_page = ['step1link' => \$kk_dev_qs];\n"
     . "}";

$count = 0;
foreach ($folders as $folder) {
    $folderPath = $base . '/' . $folder;
    if (!is_dir($folderPath)) continue;
    foreach (glob($folderPath . '/*.php') as $f) {
        $c = file_get_contents($f);
        if (strpos($c, $old) === false) continue;
        $c = str_replace($old, $new, $c);
        file_put_contents($f, $c);
        $count++;
    }
}
echo "enhanced dev step1link in $count files\n";
