<?php
/**
 * For each checkout folder, write a thin index.php that just renders the
 * KK checkout page directly (keeps tracking params intact).
 */
$checkouts = [
    'pricing-v2-kk1',
    'flash-sale-kk1',
    'plans-v3-sam-lifetime-kk1',
];
$base = realpath(__DIR__ . '/..');

/* index.php on checkout folders → 302 redirect to ./checkout.php preserving any
   query string (clickid, affiliate, journey, subids).  This way we keep the
   `index.php` filename convention and the canonical KK URL stays /folder/checkout.php */
$template = <<<'PHP'
<?php
$qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? '?' . $_SERVER['QUERY_STRING']
    : '';
header('Location: checkout.php' . $qs, true, 302);
exit;

PHP;

foreach ($checkouts as $folder) {
    $path = $base . '/' . $folder . '/index.php';
    if (!file_exists($path)) {
        file_put_contents($path, $template);
        echo "wrote: $folder/index.php\n";
    } else {
        echo "skip (exists): $folder/index.php\n";
    }
}
