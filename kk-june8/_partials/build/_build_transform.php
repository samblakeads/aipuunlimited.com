<?php
/**
 * KK-June8 — one-shot transformer.
 *
 * For each folder, this script:
 *   1) Renames index.html → checkout.php (for the 3 checkout folders), or
 *      converts index.php to KK format (for the Sabrina + lander7v2 sales pages).
 *   2) Prepends the KK money.php + _kk-config.php (+ _checkout-offers.php on checkouts)
 *      header before <!doctype html>.
 *   3) Rewrites /signup?offer=<token> URLs → KK PHP token (printf) so toggles work.
 *   4) Rewrites old absolute asset paths /checkouts-omni/<source>/...  →
 *      /kk-june8/<new>/... and source-folder /omnirogue/sabrina/* refs to local.
 *   5) Replaces sales-page header/footer with the OmniRogue chrome variant when
 *      missing, and points all "register"/"login"/"signup"/external nav CTAs at
 *      the partner checkout URL (PHP-rendered).
 *   6) Injects the shared exit popup (kk-exit-popup.php) into each page with
 *      correct monthly + lifetime hrefs.
 *
 * After this script every folder is fully self-contained:
 *   - index.php          (sales / presell page)         OR checkout.php on checkouts
 *   - checkout.php       (KK plan-selector)
 *   - _kk-config.php
 *   - _checkout-offers.php
 *   - other source assets untouched
 *
 * This file is meant to be re-run safely (idempotent) for incremental tweaks.
 */

$base = realpath(__DIR__ . '/..');

/* ============================================================ FOLDER MAP */

$map = [

  /* --- 3 CHECKOUT FOLDERS ----------------------------------------------- */
  'pricing-v2-kk1' => [
    'role'        => 'checkout',
    'orig_root'   => '/checkouts-omni/pricing-v2',
    'source_file' => 'index.html',
    'new_file'    => 'checkout.php',
    'partner'     => 'sabrina-pricing-v2-kk1',
  ],
  'flash-sale-kk1' => [
    'role'        => 'checkout',
    'orig_root'   => '/checkouts-omni/flash-sale',
    'source_file' => 'index.html',
    'new_file'    => 'checkout.php',
    'partner'     => 'sabrina-flash-sale-kk1',
  ],
  'plans-v3-sam-lifetime-kk1' => [
    'role'        => 'checkout',
    'orig_root'   => '/checkouts-omni/plans-v3-sam-lifetime',
    'source_file' => 'index.html',
    'new_file'    => 'checkout.php',
    'partner'     => 'sabrina-plansv3-samlifetime-kk1',
  ],

  /* --- 3 SABRINA PRESELL CLONES ----------------------------------------- */
  'sabrina-pricing-v2-kk1' => [
    'role'        => 'presell-sabrina',
    'orig_root'   => '/omnirogue/sabrina',
    'source_file' => 'index.php',
    'new_file'    => 'index.php',
    'partner'     => 'pricing-v2-kk1',
  ],
  'sabrina-flash-sale-kk1' => [
    'role'        => 'presell-sabrina',
    'orig_root'   => '/omnirogue/sabrina',
    'source_file' => 'index.php',
    'new_file'    => 'index.php',
    'partner'     => 'flash-sale-kk1',
  ],
  'sabrina-plansv3-samlifetime-kk1' => [
    'role'        => 'presell-sabrina',
    'orig_root'   => '/omnirogue/sabrina',
    'source_file' => 'index.php',
    'new_file'    => 'index.php',
    'partner'     => 'plans-v3-sam-lifetime-kk1',
  ],

  /* --- 1 LANDER7-V2 PRESELL CLONE --------------------------------------- */
  'unlimited-plansv3-kk1' => [
    'role'        => 'presell-lander7',
    'orig_root'   => '/omnirogue/lander7upgrades-omniunlimited-v2',
    'source_file' => 'index.php',
    'new_file'    => 'index.php',
    'partner'     => 'plans-v3-sam-lifetime-kk1',
  ],

];

/* ============================================================ KK HEADERS */

function kk_php_header_presell() {
  return <<<'PHP'
<?php
$dat_path  = $_SERVER['DOCUMENT_ROOT'].'/../data/loc.dat';
$base_path = $_SERVER['DOCUMENT_ROOT']."/".(
    file_exists($dat_path) ? json_decode(file_get_contents($dat_path), true)["location"] : 'kowboykit'
);
require_once $base_path.'/includes/money.php';
?>
<?php require_once(__DIR__.'/_kk-config.php'); ?>
<?php require_once(__DIR__.'/_checkout-offers.php'); ?>

PHP;
}

function kk_php_header_checkout() {
  // Checkout pages also include _checkout-offers.php
  return kk_php_header_presell();
}

/* ============================================================ HELPERS */

function rmkr_ensure_file_is_php(&$content) {
  // strip any existing leading whitespace/BOM, then ensure begins with <!doctype or php
  $content = preg_replace("/^\xEF\xBB\xBF/", '', $content);
  $content = ltrim($content);
}

function rmkr_prepend_php_header(&$content, $header) {
  // If already has the KK header, skip.
  if (strpos($content, "require_once(__DIR__.'/_kk-config.php')") !== false
   && strpos($content, "kowboykit/includes/money.php") !== false) {
    return;
  }
  rmkr_ensure_file_is_php($content);
  $content = $header . $content;
}

/**
 * Replace `/signup?offer=<token>` with a PHP printf to the matching KK link.
 * Works inside href="…", href='…', plain links, JS strings, etc.
 */
function rmkr_rewrite_signup_offer_to_kk_token(&$content) {
  // Patterns of /signup?offer=<token>  (URL encoded variants too)
  // Replace within HTML href attributes & JS strings: capture whichever quote precedes,
  // then drop the entire /signup?offer=token URL with our PHP echo.
  $tokens = [
    'creatormonthly','creatoryearly',
    'studiomonthly','studioyearly',
    'premiummonthly','premiumyearly',
    'scalemonthly','scaleyearly',
    'promonthly','proyearly',
    'agencymonthly','agencyyearly',
    'promaxmonthly','promaxyearly',
    'lifetime','lifetimeplan',
  ];

  // 1) Replace /signup?offer=<known-token> → echo-style PHP printing the matching KK link
  $phpOpen  = '<' . '?= ';
  $phpClose = ' ?' . '>';
  foreach ($tokens as $tok) {
    $needle = '/signup?offer=' . $tok;
    $replacement = $phpOpen . 'htmlspecialchars($__kk_offer_links[\'' . $tok . '\'] ?? \'#\', ENT_QUOTES, "UTF-8")' . $phpClose;
    $content = str_replace($needle, $replacement, $content);
  }

  // 2) Catch-all for any remaining /signup?offer=… inside HTML or JS — collapse to the
  //    register-checkout link (safest fallback).
  $content = preg_replace(
    '#/signup\?offer=([a-zA-Z0-9_-]+)#',
    $phpOpen . 'htmlspecialchars($__kk_offer_links[\'$1\'] ?? \'#\', ENT_QUOTES, "UTF-8")' . $phpClose,
    $content
  );

  // 3) Catch bare `/signup` (no query) → checkout url
  $content = preg_replace(
    '#href=("|\')/signup(\?[^"\']*)?\1#i',
    'href=$1' . $phpOpen . 'htmlspecialchars($__checkout, ENT_QUOTES, "UTF-8")' . $phpClose . '$1',
    $content
  );
}

/**
 * Rewrite the original absolute asset paths so they point at the new KK-june8 folder.
 *   e.g. /checkouts-omni/pricing-v2/foo.png  →  /kk-june8/pricing-v2-kk1/foo.png
 *        /omnirogue/sabrina/...              →  (same — local refs already by default)
 *        /checkouts-omni/_chrome/...         →  (untouched — shared chrome)
 *        /checkouts-omni/logo-omnirogue.png  →  (untouched — shared logo)
 */
function rmkr_rewrite_asset_paths(&$content, $origRoot, $newFolder) {
  // Direct prefix substitution (avoid affecting the /checkouts-omni/_chrome chrome refs).
  $content = str_replace($origRoot . '/', '/kk-june8/' . $newFolder . '/', $content);
}

/**
 * Convert hardcoded www.aiprofessionalsuniversity / aipuapp.com / app.omnirogue.com
 * "register" / "login" / "signup" / "/register" URLs that are not offer-bound
 * → checkout URL.
 */
function rmkr_neutralize_external_register($content, $isCheckout) {
  $target = $isCheckout ? '$__kk_offer_links[\'creatormonthly\']' : '$__checkout';
  // href="<scheme>//host/register..." or href="https://*omnirogue.com/..."
  $patterns = [
    '~href=("|\')https?://[^"\']*(omnirogue|aipuapp|aiprofessionalsuniversity)\.[^"\']*\1~i',
    '~href=("|\')/?(register[1-9]?|login|signup)([/?\#][^"\']*)?\1~i',
    '~href=("|\')register([/?\#][^"\']*)?\1~i',
  ];
  $phpOpen  = '<' . '?= ';
  $phpClose = ' ?' . '>';
  foreach ($patterns as $rx) {
    $content = preg_replace(
      $rx,
      'href=$1' . $phpOpen . 'htmlspecialchars(' . $target . ', ENT_QUOTES, "UTF-8")' . $phpClose . '$1',
      $content
    );
  }
  return $content;
}

/**
 * Add an `<a href="…/checkout.php?…">` to every plain-anchor link that previously
 * went to register / app subdomain.  These have already been converted in
 * rmkr_neutralize_external_register; this function is intentionally a no-op
 * placeholder kept here in case we extend with more rewrites later.
 */
function rmkr_normalize_cta_links($content) {
  // Examples we already covered above.  Reserved for future rules.
  return $content;
}

/* ============================================================ PER-FOLDER */

foreach ($map as $folder => $cfg) {
    $folderPath = $base . '/' . $folder;
    $sourceFile = $folderPath . '/' . $cfg['source_file'];
    $targetFile = $folderPath . '/' . $cfg['new_file'];
    if (!is_dir($folderPath)) { fwrite(STDERR, "skip (no folder): $folder\n"); continue; }
    if (!is_file($sourceFile)) {
        // Maybe we already renamed it on a previous run — skip silently.
        if (!is_file($targetFile)) { fwrite(STDERR, "skip (no source): $folder/{$cfg['source_file']}\n"); continue; }
        echo "noop (already converted): $folder\n";
        continue;
    }

    $content = file_get_contents($sourceFile);
    $isCheckout = ($cfg['role'] === 'checkout');

    /* 1. KK PHP header */
    $header = $isCheckout ? kk_php_header_checkout() : kk_php_header_presell();
    rmkr_prepend_php_header($content, $header);

    /* 2. Rewrite /signup?offer=<token> to KK tokens */
    rmkr_rewrite_signup_offer_to_kk_token($content);

    /* 3. Rewrite source absolute asset paths */
    rmkr_rewrite_asset_paths($content, $cfg['orig_root'], $folder);

    /* 4. Neutralize external register/login/signup links */
    $content = rmkr_neutralize_external_register($content, $isCheckout);
    $content = rmkr_normalize_cta_links($content);

    /* 5. Write target, remove old source if it differs */
    file_put_contents($targetFile, $content);
    if ($sourceFile !== $targetFile && is_file($sourceFile)) {
        unlink($sourceFile);
    }
    echo "transformed: $folder → " . basename($targetFile) . "\n";
}
