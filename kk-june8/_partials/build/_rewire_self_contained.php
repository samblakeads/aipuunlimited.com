<?php
/**
 * After each sales-page folder gets its own checkout.php, repoint
 * $__checkout at the folder's OWN checkout.php so the funnel stays
 * inside one folder per visitor.
 *
 * This makes each KK lander truly self-contained:
 *   /sabrina-pricing-v2-kk1/index.php       (presell)
 *   /sabrina-pricing-v2-kk1/checkout.php    (plan selector)
 *
 * The "$__partner" globals are kept for back-compat but no longer drive CTAs.
 */
$base = realpath(__DIR__ . '/../..');

$sales_folders = [
    'sabrina-pricing-v2-kk1',
    'sabrina-flash-sale-kk1',
    'sabrina-plansv3-samlifetime-kk1',
    'unlimited-plansv3-kk1',
];

foreach ($sales_folders as $folder) {
    $cfg = $base . '/' . $folder . '/_kk-config.php';
    if (!is_file($cfg)) { fwrite(STDERR, "skip (no _kk-config.php): $folder\n"); continue; }

    $web = '/kk-june8/' . $folder;
    $newContent = <<<PHP
<?php
\$__web              = '{$web}';
\$__lander           = \$__web . '/';
\$__step1link        = \$multi_page['step1link'] ?? '';
\$__checkout         = \$__web . '/checkout.php' . \$__step1link;
\$__presell_home     = \$__lander . \$__step1link;
\$__registercheckout = \$offer['registercheckout']['link']['step1link'] ?? '';
\$__is_checkout      = false;
/* Back-compat: same folder is its own partner now */
\$__partner_web      = \$__web;
\$__partner_lander   = \$__lander;
PHP;
    $newContent .= "\n";
    file_put_contents($cfg, $newContent);
    echo "rewrote: $folder/_kk-config.php (\$__checkout = own folder's checkout.php)\n";
}
echo "done\n";
