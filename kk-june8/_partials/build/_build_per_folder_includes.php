<?php
/**
 * One-shot build helper — generates per-folder _kk-config.php
 * and _checkout-offers.php from the shared template.
 *
 * Each KK-folder maps:
 *   - lander folder name           (string, no leading slash)
 *   - is_checkout (true/false)     for /checkout.php convention
 *   - presell_lander (string|null) for checkout pages whose presell is a sibling sales page
 *
 * For each sales page (Sabrina clone) we set $__checkout to its paired checkout folder.
 * For each checkout page we set $__lander to its paired sales-page folder.
 */

$base = __DIR__ . '/..';

$folders = [
    // [folder, paired_partner_folder_for_back_or_forward_link]
    'pricing-v2-kk1'                   => ['type' => 'checkout', 'partner' => 'sabrina-pricing-v2-kk1'],
    'flash-sale-kk1'                   => ['type' => 'checkout', 'partner' => 'sabrina-flash-sale-kk1'],
    'plans-v3-sam-lifetime-kk1'        => ['type' => 'checkout', 'partner' => 'sabrina-plansv3-samlifetime-kk1'],
    'sabrina-pricing-v2-kk1'           => ['type' => 'presell',  'partner' => 'pricing-v2-kk1'],
    'sabrina-flash-sale-kk1'           => ['type' => 'presell',  'partner' => 'flash-sale-kk1'],
    'sabrina-plansv3-samlifetime-kk1'  => ['type' => 'presell',  'partner' => 'plans-v3-sam-lifetime-kk1'],
    'unlimited-plansv3-kk1'            => ['type' => 'presell',  'partner' => 'plans-v3-sam-lifetime-kk1'],
];

$offerTemplate = file_get_contents($base . '/_partials/_checkout-offers.php');

foreach ($folders as $folder => $cfg) {
    $folderPath = $base . '/' . $folder;
    if (!is_dir($folderPath)) {
        fwrite(STDERR, "skip (missing): $folder\n");
        continue;
    }

    // _kk-config.php — defines $__web, $__lander, $__step1link, $__checkout
    // For presell pages, $__checkout points at the checkout folder.
    // For checkout pages, $__checkout points at their own checkout.php (so internal "Pricing" links work).
    $kkWeb       = '/kk-june8/' . $folder;
    $partnerWeb  = '/kk-june8/' . $cfg['partner'];
    $isCheckout  = ($cfg['type'] === 'checkout');
    $isCheckoutVal = $isCheckout ? 'true' : 'false';

    $checkoutTarget = $isCheckout
        ? $kkWeb . '/checkout.php'              // already on checkout, anchor here
        : $partnerWeb . '/checkout.php';        // presell points at partner's checkout
    $presellHome   = $isCheckout
        ? $partnerWeb . '/'                     // checkout: home points back to sales page
        : $kkWeb . '/';                         // sales: home points at itself

    $config = <<<PHP
<?php
\$__web              = '{$kkWeb}';
\$__lander           = \$__web . '/';
\$__step1link        = \$multi_page['step1link'] ?? '';
\$__partner_web      = '{$partnerWeb}';
\$__partner_lander   = \$__partner_web . '/';
\$__checkout         = '{$checkoutTarget}' . \$__step1link;
\$__presell_home     = '{$presellHome}' . \$__step1link;
\$__registercheckout = \$offer['registercheckout']['link']['step1link'] ?? '';
\$__is_checkout      = {$isCheckoutVal};
PHP;
    $config .= "\n";

    file_put_contents($folderPath . '/_kk-config.php', $config);
    file_put_contents($folderPath . '/_checkout-offers.php', $offerTemplate);
    echo "wrote: $folder/_kk-config.php and _checkout-offers.php\n";
}
