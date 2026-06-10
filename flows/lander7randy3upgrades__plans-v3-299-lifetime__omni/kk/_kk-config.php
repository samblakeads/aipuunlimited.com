<?php
$__web = '/lander7randy3upgrades__plans-v3-299-lifetime__omni';
$__lander = $__web . '/';
if (!function_exists('__kk_step1link')) {
    function __kk_step1link() {
        // 1) money.php is authoritative when present (cloaked pages).
        global $multi_page;
        if (isset($multi_page['step1link']) && $multi_page['step1link'] !== '') {
            $s = $multi_page['step1link'];
            if (!headers_sent()) { setcookie('kk_s1', rawurlencode($s), time()+86400, '/'); }
            return $s;
        }
        // 2) Live request query string — the full journey, verbatim. This
        //    preserves repeated keys (o=..&o=..) and KK params (lid/action)
        //    that a whitelist or $_GET round-trip would drop.
        $qs = (string)($_SERVER['QUERY_STRING'] ?? '');
        if ($qs !== '') {
            $s = '?' . $qs;
            if (!headers_sent()) { setcookie('kk_s1', rawurlencode($s), time()+86400, '/'); }
            return $s;
        }
        // 3) Fall back to the remembered cookie (side page hit with no params).
        if (!empty($_COOKIE['kk_s1'])) {
            $s = rawurldecode((string)$_COOKIE['kk_s1']);
            if ($s !== '' && $s[0] === '?') { return $s; }
        }
        return '';
    }
}
$__step1link = __kk_step1link();
$__register = "/register";
$__checkout = $__lander . 'checkout.php' . $__step1link;
$__registercheckout = $offer['registercheckout']['link']['step1link'] ?? $__register;
$__registercreate = $offer['registercreate']['link']['step1link'] ?? $__register;
$__is_checkout = false;
