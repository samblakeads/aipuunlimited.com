<?php
/**
 * Replaces the strict KK money.php require with a file_exists-guarded version
 * on every PHP page in kk-june8 so the pages render in dev (without money.php).
 *
 * On the KK production server money.php WILL exist at the resolved path, so
 * behavior is identical there. This shim only avoids 500s in local dev/preview.
 */

$base = realpath(__DIR__ . '/..');
$files = [
    'pricing-v2-kk1/checkout.php',
    'pricing-v2-kk1/index.php',
    'flash-sale-kk1/checkout.php',
    'flash-sale-kk1/index.php',
    'plans-v3-sam-lifetime-kk1/checkout.php',
    'plans-v3-sam-lifetime-kk1/index.php',
    'sabrina-pricing-v2-kk1/index.php',
    'sabrina-flash-sale-kk1/index.php',
    'sabrina-plansv3-samlifetime-kk1/index.php',
    'unlimited-plansv3-kk1/index.php',
];

/* Old strict header (matches the multistep-kk pattern) */
$old = <<<'PHP'
<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
require_once $base_path.'/includes/money.php';
?>
PHP;

/* New header — same KK-spec line 1 require_once, but guarded by file_exists.
   On production where money.php exists, behavior is identical. */
$new = <<<'PHP'
<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
$money_php = $base_path.'/includes/money.php';
if (file_exists($money_php)) { require_once $money_php; }
/* Dev-mode fallback: ensure KK globals exist so pages render without money.php */
if (!isset($multi_page)) { $multi_page = ['step1link' => '']; }
if (!isset($link))       { $link       = ['step1link' => '#']; }
if (!isset($offer))      { $offer      = []; }
?>
PHP;

foreach ($files as $rel) {
    $p = $base . '/' . $rel;
    if (!is_file($p)) { fwrite(STDERR, "skip (missing): $rel\n"); continue; }
    $c = file_get_contents($p);
    if (strpos($c, "if (file_exists(\$money_php))") !== false) {
        echo "noop (already patched): $rel\n";
        continue;
    }
    if (strpos($c, "require_once \$base_path.'/includes/money.php';") === false) {
        // For the redirect-style index.php (no money.php block), skip.
        echo "skip (no money block): $rel\n";
        continue;
    }
    $c = str_replace($old, $new, $c, $count);
    if ($count === 0) {
        // Fall back to looser line-by-line replacement
        $c = preg_replace(
            '~require_once \$base_path\.\'/includes/money\.php\';~',
            "\$money_php = \$base_path.'/includes/money.php';\nif (file_exists(\$money_php)) { require_once \$money_php; }\nif (!isset(\$multi_page)) { \$multi_page = ['step1link' => '']; }\nif (!isset(\$link)) { \$link = ['step1link' => '#']; }\nif (!isset(\$offer)) { \$offer = []; }",
            $c, 1
        );
    }
    file_put_contents($p, $c);
    echo "patched: $rel\n";
}
