<?php
/**
 * Final-pass link patcher.  Sweeps every kk-june8 page and rewrites any
 * lingering hardcoded site paths to either $__checkout (sales pages) or
 * the in-page footer popup tokens (checkout pages).
 *
 * Targets:
 *   /refund-policy/, /privacy/, /terms/, /acceptable-use/, /contact/,
 *   /earnings-disclaimer/, /data-deletion/, /affiliate/dashboard,
 *   /omnirogue/... or any /aiprofessionalsuniversity/ leftover.
 */

$base = realpath(__DIR__ . '/..');

$files = [
    'pricing-v2-kk1/checkout.php',
    'flash-sale-kk1/checkout.php',
    'plans-v3-sam-lifetime-kk1/checkout.php',
    'sabrina-pricing-v2-kk1/index.php',
    'sabrina-flash-sale-kk1/index.php',
    'sabrina-plansv3-samlifetime-kk1/index.php',
    'unlimited-plansv3-kk1/index.php',
];

// Pages and the regex patterns to scrub.  href value is rewritten to the
// PHP-echoed checkout URL (the same on sales and checkout — both keep users
// on the funnel).
$phpOpen  = '<' . '?= ';
$phpClose = ' ?' . '>';
$repl = 'href=$1' . $phpOpen . 'htmlspecialchars($__checkout, ENT_QUOTES, "UTF-8")' . $phpClose . '$1';

$patterns = [
    '~href=("|\')/(refund-policy|privacy|terms|acceptable-use|contact|earnings-disclaimer|data-deletion|register|signup|login|affiliate(?:/[^"\']*)?)/?\1~i',
    '~href=("|\')/(refund-policy|privacy|terms|acceptable-use|contact|earnings-disclaimer|data-deletion)/?(\#[^"\']*)?\1~i',
];

foreach ($files as $rel) {
    $p = $base . '/' . $rel;
    if (!is_file($p)) continue;
    $c = file_get_contents($p);
    $orig = $c;
    foreach ($patterns as $rx) {
        $c = preg_replace($rx, $repl, $c);
    }
    if ($c !== $orig) {
        file_put_contents($p, $c);
        echo "patched: $rel\n";
    } else {
        echo "no-op:   $rel\n";
    }
}
