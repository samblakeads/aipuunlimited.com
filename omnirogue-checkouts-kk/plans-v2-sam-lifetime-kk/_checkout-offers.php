<?php
if (!function_exists('__kk_offer_step1link')) {
    function __kk_offer_step1link($token) {
        global $offer, $link;
        if (!empty($offer[$token]['link']['step1link'])) {
            return $offer[$token]['link']['step1link'];
        }
        if ($token === 'lifetimeplan' && !empty($offer['lifetime']['link']['step1link'])) {
            return $offer['lifetime']['link']['step1link'];
        }
        if ($token === 'lifetime' && !empty($offer['lifetimeplan']['link']['step1link'])) {
            return $offer['lifetimeplan']['link']['step1link'];
        }
        if (!empty($offer['registercheckout']['link']['step1link'])) {
            return $offer['registercheckout']['link']['step1link'];
        }
        return !empty($link['step1link']) ? $link['step1link'] : '#';
    }
}
$__kk_offer_tokens = [
    'creatormonthly', 'studiomonthly', 'premiummonthly', 'scalemonthly',
    'promonthly', 'agencymonthly', 'promaxmonthly',
    'creatoryearly', 'studioyearly', 'premiumyearly', 'scaleyearly',
    'proyearly', 'agencyyearly', 'promaxyearly',
    'lifetime', 'lifetimeplan',
];
$__kk_offer_links = [];
foreach ($__kk_offer_tokens as $__t) {
    $__kk_offer_links[$__t] = __kk_offer_step1link($__t);
}
$__kk_lifetime_link = __kk_offer_step1link('lifetime');
unset($__t);
