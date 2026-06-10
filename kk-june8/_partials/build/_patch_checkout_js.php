<?php
/**
 * Patch the inline JS checkoutUrl() function in each checkout page so the
 * billing toggle uses window.__KK_OFFER_LINKS instead of /signup?offer=...
 *
 * Per kkformat.md MULTI-OFFER CHECKOUT section.
 */
$base = realpath(__DIR__ . '/..');
$files = [
    'pricing-v2-kk1/checkout.php',
    'flash-sale-kk1/checkout.php',
    'plans-v3-sam-lifetime-kk1/checkout.php',
];

$old1 = "function checkoutUrl(token) { return '/signup?offer=' + encodeURIComponent(token); }";
$new1 = "function checkoutUrl(token) { var links = window.__KK_OFFER_LINKS || {}; return links[token] || '#'; }";

$count = 0;
foreach ($files as $rel) {
    $p = $base . '/' . $rel;
    if (!is_file($p)) { echo "skip (missing): $rel\n"; continue; }
    $c = file_get_contents($p);
    $orig = $c;
    $c = str_replace($old1, $new1, $c);
    /* Also catch any variant that builds the same URL inline */
    $c = str_replace(
        "return '/signup?offer=' + encodeURIComponent(token);",
        "var links = window.__KK_OFFER_LINKS || {}; return links[token] || '#';",
        $c
    );
    if ($c !== $orig) {
        file_put_contents($p, $c);
        echo "patched: $rel\n";
        $count++;
    } else {
        echo "no-op (no inline checkoutUrl found): $rel\n";
    }
}
echo "done — patched $count files\n";
