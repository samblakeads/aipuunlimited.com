<?php
/**
 * Rewrite each Design C checkout (plans-v3-sam-lifetime variants) to:
 *   1. Replace its 4-tier plan grid HTML with the equivalent from
 *      checkouts-omni/plans-v3-299-lifetime/index.html (which already has
 *      Creator $14.99 / Premium $39.99 / Pro $149.99 / Agency $299.99).
 *   2. Rename "Agency" tier → "Pro Max".
 *   3. Swap KK tokens: agencymonthly → promaxmonthly, agencyyearly → promaxyearly.
 *   4. Fix asset paths inside the swapped block to point at the local folder.
 *
 * Design A (pricing-v2) and Design B (flash-sale) are handled separately
 * since their HTML structure differs.
 */

$base = realpath(__DIR__ . '/../..');
$src  = '/var/www/aipuunlimited.com/htdocs/checkouts-omni/plans-v3-299-lifetime/index.html';

if (!is_file($src)) {
    fwrite(STDERR, "source not found: $src\n");
    exit(1);
}

$srcHtml = file_get_contents($src);

/* Extract the source's `<div class="pyp-board">...</div>` block which wraps
   the billing toggle + monthly grid + annual grid. */
$boardStart = strpos($srcHtml, '<div class="pyp-board">');
$boardEnd   = strpos($srcHtml, '</section>', $boardStart);
/* That last </section> isn't right — pyp-board doesn't have one inside.
   The structure: <div class="pyp-board"> ... </div> followed by other markup.
   Let's find the matching closing </div> via brace count. */
function find_matching_close($html, $start) {
    /* Cheap parser: count <div ...> opens and </div> closes after $start. */
    $depth = 0;
    $pos = $start;
    while ($pos < strlen($html)) {
        $nextOpen  = stripos($html, '<div',  $pos);
        $nextClose = stripos($html, '</div>', $pos);
        if ($nextClose === false) return false;
        if ($nextOpen !== false && $nextOpen < $nextClose) {
            $depth++;
            $pos = strpos($html, '>', $nextOpen) + 1;
        } else {
            $depth--;
            if ($depth === 0) return $nextClose + strlen('</div>');
            $pos = $nextClose + strlen('</div>');
        }
    }
    return false;
}

$boardCloseEnd = find_matching_close($srcHtml, $boardStart);
if (!$boardCloseEnd) { fwrite(STDERR, "could not find pyp-board close\n"); exit(1); }
$srcBoard = substr($srcHtml, $boardStart, $boardCloseEnd - $boardStart);

echo "Source pyp-board block extracted: " . strlen($srcBoard) . " bytes\n";

/* Rename Agency → Pro Max inside the extracted source.
   The class cta-blue belongs to Agency; rename in HTML labels only. */
$srcBoard = preg_replace_callback(
    '~<h3>Agency</h3>~',
    function () { return '<h3>Pro Max</h3>'; },
    $srcBoard
);

/* Update "Agency" callouts in tag text + Best Value/Most Popular banners */
$srcBoard = str_replace(
    'For agencies and small teams',
    'For teams scaling AI workflows across multiple workspaces',
    $srcBoard
);

/* Now apply to each Design C folder.  We replace the existing
   <div class="pyp-board">...</div> block with the new $srcBoard. */
$folders = [
    'plans-v3-sam-lifetime-kk1',
    'sabrina-plansv3-samlifetime-kk1',
    'unlimited-plansv3-kk1',
];

foreach ($folders as $folder) {
    $path = $base . '/' . $folder . '/checkout.php';
    if (!is_file($path)) { fwrite(STDERR, "skip (missing): $folder\n"); continue; }
    $c = file_get_contents($path);

    $oldStart = strpos($c, '<div class="pyp-board">');
    if ($oldStart === false) { fwrite(STDERR, "no pyp-board in: $folder\n"); continue; }
    $oldEnd = find_matching_close($c, $oldStart);
    if (!$oldEnd) { fwrite(STDERR, "could not close pyp-board in: $folder\n"); continue; }

    /* Build replacement block; rewrite source asset paths to local folder */
    $localBoard = $srcBoard;
    /* the source uses no absolute asset paths in the board, but just in case: */
    $localBoard = str_replace(
        '/checkouts-omni/plans-v3-299-lifetime/',
        '/kk-june8/' . $folder . '/',
        $localBoard
    );

    $new = substr($c, 0, $oldStart) . $localBoard . substr($c, $oldEnd);
    file_put_contents($path, $new);
    echo "swapped: $folder/checkout.php (board $oldStart..$oldEnd → " . strlen($localBoard) . " bytes)\n";
}
