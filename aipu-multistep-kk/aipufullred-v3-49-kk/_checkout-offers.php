<?php
$__kk_offer_tokens = [
    'creatormonthly', 'studiomonthly', 'premiummonthly', 'scalemonthly',
    'promonthly', 'agencymonthly', 'promaxmonthly',
    'creatoryearly', 'studioyearly', 'premiumyearly', 'scaleyearly',
    'proyearly', 'agencyyearly', 'promaxyearly',
    'lifetimeplan',
];
$__kk_offer_links = [];
foreach ($__kk_offer_tokens as $__t) {
    $__kk_offer_links[$__t] = $offer[$__t]['link']['step1link'] ?? ($link['step1link'] ?? '#');
}
unset($__t);
