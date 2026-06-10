<?php
/**
 * Second-pass injector — adds to every PHP page in kk-june8:
 *
 *   1. window.__KK_* globals right after <body>
 *   2. Exit popup before </body>
 *   3. KK chrome overrides JS before </body>
 *
 * Idempotent — checks for an inject marker and skips if already present.
 */

$base = realpath(__DIR__ . '/..');

$pages = [
    /* checkout pages */
    'pricing-v2-kk1/checkout.php'                  => 'checkout',
    'flash-sale-kk1/checkout.php'                  => 'checkout',
    'plans-v3-sam-lifetime-kk1/checkout.php'       => 'checkout',
    /* presell pages */
    'sabrina-pricing-v2-kk1/index.php'             => 'presell',
    'sabrina-flash-sale-kk1/index.php'             => 'presell',
    'sabrina-plansv3-samlifetime-kk1/index.php'    => 'presell',
    'unlimited-plansv3-kk1/index.php'              => 'presell',
];

$MARKER = '<!-- kk-inject:v1 -->';

$bodyOpenInject = <<<'PHP'

<!-- kk-inject:v1 -->
<script>
window.__KK_CHECKOUT_URL  = <?= json_encode($__checkout, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_PRESELL_HOME  = <?= json_encode($__presell_home, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_IS_CHECKOUT   = <?= $__is_checkout ? 'true' : 'false'; ?>;
window.__KK_STEP1LINK     = <?= json_encode($__step1link, JSON_UNESCAPED_SLASHES); ?>;
window.__KK_OFFER_LINKS = {
  creatormonthly: <?= json_encode($__kk_offer_links['creatormonthly'] ?? '', JSON_UNESCAPED_SLASHES); ?>,
  creatoryearly:  <?= json_encode($__kk_offer_links['creatoryearly']  ?? '', JSON_UNESCAPED_SLASHES); ?>,
  studiomonthly:  <?= json_encode($__kk_offer_links['studiomonthly']  ?? '', JSON_UNESCAPED_SLASHES); ?>,
  studioyearly:   <?= json_encode($__kk_offer_links['studioyearly']   ?? '', JSON_UNESCAPED_SLASHES); ?>,
  premiummonthly: <?= json_encode($__kk_offer_links['premiummonthly'] ?? '', JSON_UNESCAPED_SLASHES); ?>,
  premiumyearly:  <?= json_encode($__kk_offer_links['premiumyearly']  ?? '', JSON_UNESCAPED_SLASHES); ?>,
  scalemonthly:   <?= json_encode($__kk_offer_links['scalemonthly']   ?? '', JSON_UNESCAPED_SLASHES); ?>,
  scaleyearly:    <?= json_encode($__kk_offer_links['scaleyearly']    ?? '', JSON_UNESCAPED_SLASHES); ?>,
  promonthly:     <?= json_encode($__kk_offer_links['promonthly']     ?? '', JSON_UNESCAPED_SLASHES); ?>,
  proyearly:      <?= json_encode($__kk_offer_links['proyearly']      ?? '', JSON_UNESCAPED_SLASHES); ?>,
  agencymonthly:  <?= json_encode($__kk_offer_links['agencymonthly']  ?? '', JSON_UNESCAPED_SLASHES); ?>,
  agencyyearly:   <?= json_encode($__kk_offer_links['agencyyearly']   ?? '', JSON_UNESCAPED_SLASHES); ?>,
  promaxmonthly:  <?= json_encode($__kk_offer_links['promaxmonthly']  ?? '', JSON_UNESCAPED_SLASHES); ?>,
  promaxyearly:   <?= json_encode($__kk_offer_links['promaxyearly']   ?? '', JSON_UNESCAPED_SLASHES); ?>,
  lifetime:       <?= json_encode($__kk_offer_links['lifetime']       ?? '', JSON_UNESCAPED_SLASHES); ?>,
  lifetimeplan:   <?= json_encode($__kk_offer_links['lifetimeplan']   ?? '', JSON_UNESCAPED_SLASHES); ?>
};
</script>

PHP;

$bodyCloseInject = <<<'PHP'

<!-- kk-inject:v1 footer -->
<?php
$kk_exit_monthly_href  = $__is_checkout
    ? ($__kk_offer_links['creatormonthly'] ?? '#')
    : $__checkout;
$kk_exit_lifetime_href = $__is_checkout
    ? ($__kk_lifetime_link ?? $__kk_offer_links['lifetime'] ?? '#')
    : $__checkout;
require __DIR__ . '/../_partials/kk-exit-popup.php';
?>
<script src="/kk-june8/_partials/kk-chrome-overrides.js?v=20260608d" defer></script>

PHP;

foreach ($pages as $rel => $role) {
    $path = $base . '/' . $rel;
    if (!is_file($path)) {
        fwrite(STDERR, "skip (missing): $rel\n");
        continue;
    }

    $c = file_get_contents($path);
    if (strpos($c, 'kk-inject:v1') !== false) {
        echo "noop (already injected): $rel\n";
        continue;
    }

    /* Insert body-open block right after <body> (handle <body...> with attrs). */
    $count = 0;
    $c = preg_replace(
        '~(<body\b[^>]*>)~i',
        '$1' . str_replace('$', '\$', $bodyOpenInject),
        $c, 1, $count
    );
    if (!$count) {
        fwrite(STDERR, "warn: $rel — no <body> tag found; prepending after first <html>\n");
    }

    /* Insert body-close block right before </body>. */
    $count2 = 0;
    $c = preg_replace(
        '~(</body>)~i',
        str_replace('$', '\$', $bodyCloseInject) . '$1',
        $c, 1, $count2
    );
    if (!$count2) {
        // Fallback: append before </html>
        $c = preg_replace('~(</html>)~i',  str_replace('$', '\$', $bodyCloseInject) . '$1', $c, 1);
    }

    file_put_contents($path, $c);
    echo "injected: $rel\n";
}
