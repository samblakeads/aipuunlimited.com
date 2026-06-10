<?php
/**
 * Fix the inline JS in Design C checkouts:
 *   "agencymonthly" → "promaxmonthly"
 *   "agencyyearly"  → "promaxyearly"
 *
 * The LINKS map + ORDER array both reference these tokens.  The pcol cards in
 * the grid HTML are positioned [Creator, Premium, Pro Max, Pro] left-to-right,
 * so ORDER should be: ["creatormonthly", "premiummonthly", "promaxmonthly", "promonthly"]
 *
 * Also rewires the "Get Plan" button click handler (already does this via
 * btn.onclick = ... LINKS[tok]) — no change needed there beyond token names.
 */

$base = realpath(__DIR__ . '/../..');
$folders = [
    'plans-v3-sam-lifetime-kk1',
    'sabrina-plansv3-samlifetime-kk1',
    'unlimited-plansv3-kk1',
];

foreach ($folders as $folder) {
    $path = $base . '/' . $folder . '/checkout.php';
    if (!is_file($path)) { fwrite(STDERR, "skip: $folder\n"); continue; }
    $c = file_get_contents($path);

    /* Replace token names in the inline JS — both LINKS map keys and ORDER list */
    $orig = $c;
    $c = str_replace('"agencymonthly"', '"promaxmonthly"', $c);
    $c = str_replace('"agencyyearly"',  '"promaxyearly"',  $c);
    $c = str_replace("'agencymonthly'", "'promaxmonthly'", $c);
    $c = str_replace("'agencyyearly'",  "'promaxyearly'",  $c);

    if ($c === $orig) {
        echo "no-op: $folder\n";
        continue;
    }
    file_put_contents($path, $c);
    echo "patched: $folder (inline JS tokens swapped)\n";
}
echo "done\n";
