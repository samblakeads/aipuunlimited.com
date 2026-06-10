<?php
/**
 * Find every PHP file across the 7 KK-june8 folders that contains the strict
 * KK money.php loader, and replace it with the file_exists-guarded variant
 * (so dev preview works while the canonical KK behavior is preserved
 * — production has money.php, dev does not).
 *
 * Idempotent.
 */
$base = realpath(__DIR__ . '/../..');
$folders = [
    'pricing-v2-kk1', 'flash-sale-kk1', 'plans-v3-sam-lifetime-kk1',
    'sabrina-pricing-v2-kk1', 'sabrina-flash-sale-kk1',
    'sabrina-plansv3-samlifetime-kk1', 'unlimited-plansv3-kk1',
];

$old = "require_once \$base_path.'/includes/money.php';";
$new = <<<'PHP'
$money_php = $base_path.'/includes/money.php';
if (file_exists($money_php)) { require_once $money_php; }
if (!isset($multi_page)) { $multi_page = ['step1link' => '']; }
if (!isset($link))       { $link       = ['step1link' => '#']; }
if (!isset($offer))      { $offer      = []; }
PHP;

$count = 0;
foreach ($folders as $folder) {
    $folderPath = $base . '/' . $folder;
    if (!is_dir($folderPath)) continue;

    foreach (glob($folderPath . '/*.php') as $f) {
        $c = file_get_contents($f);
        if (strpos($c, $old) === false) continue;     // already patched, or no header
        if (strpos($c, 'if (file_exists($money_php))') !== false) continue; // already patched
        $c = str_replace($old, $new, $c);
        file_put_contents($f, $c);
        $count++;
    }
}
echo "patched $count files\n";
