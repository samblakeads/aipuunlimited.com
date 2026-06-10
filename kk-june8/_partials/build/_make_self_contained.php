<?php
/**
 * Make each sales-page folder fully self-contained:
 *
 *   1. Copy the paired partner checkout.php into the sales folder
 *   2. Rewrite all `/kk-june8/<partner>/...` asset paths inside the copy
 *      to point at the sales folder
 *   3. Copy any non-PHP support assets (logo PNGs, plans-pick-your-plan/,
 *      plan-entitlements.json) from partner → sales folder
 *
 * After this, each sales-page folder has BOTH index.php (presell) AND
 * checkout.php (its own plan-selector) — strict KK spec satisfied.
 *
 * Idempotent: deletes pre-existing checkout.php in the sales folder before
 * re-copying, so this script can be re-run safely.
 */

$base = realpath(__DIR__ . '/../..');

$pairs = [
    'sabrina-pricing-v2-kk1'         => 'pricing-v2-kk1',
    'sabrina-flash-sale-kk1'         => 'flash-sale-kk1',
    'sabrina-plansv3-samlifetime-kk1'=> 'plans-v3-sam-lifetime-kk1',
    'unlimited-plansv3-kk1'          => 'plans-v3-sam-lifetime-kk1',
];

function recurse_copy($src, $dst) {
    if (!is_dir($dst)) mkdir($dst, 0755, true);
    $dir = opendir($src);
    while (false !== ($f = readdir($dir))) {
        if ($f === '.' || $f === '..') continue;
        $s = $src . '/' . $f;
        $d = $dst . '/' . $f;
        if (is_dir($s)) {
            recurse_copy($s, $d);
        } else {
            copy($s, $d);
        }
    }
    closedir($dir);
}

foreach ($pairs as $sales => $partner) {
    $salesDir   = $base . '/' . $sales;
    $partnerDir = $base . '/' . $partner;
    if (!is_dir($salesDir))   { fwrite(STDERR, "skip (no sales): $sales\n"); continue; }
    if (!is_dir($partnerDir)) { fwrite(STDERR, "skip (no partner): $partner\n"); continue; }

    /* 1. Copy partner/checkout.php → sales/checkout.php (rewriting asset paths) */
    $checkoutSrc = $partnerDir . '/checkout.php';
    $checkoutDst = $salesDir   . '/checkout.php';
    if (!is_file($checkoutSrc)) { fwrite(STDERR, "skip (no checkout): $partner\n"); continue; }

    $content = file_get_contents($checkoutSrc);
    /* Rewrite /kk-june8/<partner>/... → /kk-june8/<sales>/... so this self-contained
       copy looks at its own folder for CSS bundles, logos, plans-pick-your-plan, etc. */
    $content = str_replace(
        "/kk-june8/{$partner}/",
        "/kk-june8/{$sales}/",
        $content
    );
    file_put_contents($checkoutDst, $content);
    echo "wrote: $sales/checkout.php (copied from $partner, asset paths rewritten)\n";

    /* 2. Copy any support assets the checkout depends on */
    foreach (['logo-aipu.png', 'logo-omnirogue.png', 'plan-entitlements.json'] as $f) {
        $s = $partnerDir . '/' . $f;
        $d = $salesDir . '/' . $f;
        if (is_file($s) && !is_file($d)) {
            copy($s, $d);
            echo "  copied asset: $sales/$f\n";
        }
    }
    /* 3. Copy the plans-pick-your-plan/ subfolder if present */
    $sub = $partnerDir . '/plans-pick-your-plan';
    if (is_dir($sub) && !is_dir($salesDir . '/plans-pick-your-plan')) {
        recurse_copy($sub, $salesDir . '/plans-pick-your-plan');
        echo "  copied subfolder: $sales/plans-pick-your-plan/\n";
    }
}
echo "done\n";
