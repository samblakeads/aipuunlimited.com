<?php
/**
 * For each Design C folder (plans-v3-sam-lifetime-kk1 + its 3 paired sales-folder
 * checkouts), copy the source bundle.js from /checkouts-omni/plans-v3-299-lifetime/
 * which already has the 4-tier structure with $299.99 ProMax + $149.99 Pro at
 * the right positions.  Then transform:
 *
 *   • id:"agency"   → id:"promax"
 *   • name:"Agency" → name:"Pro Max"
 *   • All "AIPU" → "OmniRogue"
 *   • All 265,000+ / 265K+ → 250,000+ / 250K+
 *   • aipu:openInfo → or:openInfo (CustomEvent name)
 *   • aipu-logo-horizontal → omnirogue-logo-horizontal
 *
 * The corresponding bundle.scoped.css is also copied (same in both folders so
 * design stays consistent).
 */

$base = realpath(__DIR__ . '/../..');
$srcDir = '/var/www/aipuunlimited.com/htdocs/checkouts-omni/plans-v3-299-lifetime/plans-pick-your-plan';

$folders = [
    'plans-v3-sam-lifetime-kk1',
    'sabrina-plansv3-samlifetime-kk1',
    'unlimited-plansv3-kk1',
];

foreach ($folders as $folder) {
    $dstDir = $base . '/' . $folder . '/plans-pick-your-plan';
    if (!is_dir($dstDir)) {
        fwrite(STDERR, "skip (no plans-pick-your-plan dir): $folder\n");
        continue;
    }

    foreach (['js/bundle.js', 'css/bundle.scoped.css'] as $rel) {
        $s = $srcDir . '/' . $rel;
        $d = $dstDir . '/' . $rel;
        if (!is_file($s)) { fwrite(STDERR, "  src missing: $rel\n"); continue; }
        $content = file_get_contents($s);

        if (str_ends_with($rel, '.js')) {
            /* Tier rename */
            $content = str_replace('id:"agency"',  'id:"promax"', $content);
            $content = str_replace('name:"Agency"', 'name:"Pro Max"', $content);
            /* If there's any text saying "agencies" in the tag, replace contextually */
            $content = str_replace(
                'tag:"For agencies and small teams"',
                'tag:"For teams scaling AI workflows across workspaces"',
                $content
            );

            /* Brand cleanup */
            $content = str_replace('AIPU',             'OmniRogue',       $content);
            $content = str_replace('aipu:openInfo',    'or:openInfo',     $content);
            $content = str_replace('aipu-logo-horizontal', 'logo-omnirogue', $content);
            $content = str_replace('265,000+',         '250,000+',        $content);
            $content = str_replace('265K+',            '250K+',           $content);

            /* Asset path inside the bundle: it references `../assets/aipu-logo-horizontal.png`
               which (after rename above) becomes `../assets/logo-omnirogue.png`.  But our
               folder doesn't have ../assets/logo-omnirogue.png — it has /checkouts-omni/logo-omnirogue.png.
               Rewrite to absolute path. */
            $content = str_replace(
                'src:"../assets/logo-omnirogue.png"',
                'src:"/checkouts-omni/logo-omnirogue.png"',
                $content
            );
        }

        file_put_contents($d, $content);
        echo "wrote: $folder/plans-pick-your-plan/$rel\n";
    }
}
echo "done\n";
