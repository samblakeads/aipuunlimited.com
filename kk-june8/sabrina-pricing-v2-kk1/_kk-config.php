<?php
$__web              = '/sabrina-pricing-v2-kk1';
$__lander           = $__web . '/';
$__step1link        = $multi_page['step1link'] ?? '';
$__checkout         = $__web . '/checkout.php' . $__step1link;
$__presell_home     = $__lander . $__step1link;
$__registercheckout = $offer['registercheckout']['link']['step1link'] ?? '';
$__is_checkout      = false;
/* Back-compat: same folder is its own partner now */
$__partner_web      = $__web;
$__partner_lander   = $__lander;
