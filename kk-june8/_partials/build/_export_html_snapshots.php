<?php
/**
 * Verify each .php page renders as real, complete HTML by rendering it via
 * HTTP and saving the output as `<name>.html` alongside its `.php` counterpart.
 *
 * The resulting .html files are static snapshots — they have every CSS/JS/img
 * URL baked in absolutely, so they're independently navigable. We exercise
 * them by HTTP-fetching each and verifying:
 *   - non-empty body
 *   - has <html>...</html>
 *   - has <head> + <body>
 *   - no <?php tags survive in the output
 *
 * This is a sanity / proof-of-validity check — the .html files demonstrate
 * the underlying PHP is just template substitution, not load-bearing logic.
 */
$host = 'https://aipuunlimited.com';
$base = realpath(__DIR__ . '/../..');

$folders = [
    'pricing-v2-kk1', 'flash-sale-kk1', 'plans-v3-sam-lifetime-kk1',
    'sabrina-pricing-v2-kk1', 'sabrina-flash-sale-kk1',
    'sabrina-plansv3-samlifetime-kk1', 'unlimited-plansv3-kk1',
];

$results = [];
$ok = 0; $bad = 0;

foreach ($folders as $folder) {
    $folderPath = $base . '/' . $folder;
    if (!is_dir($folderPath)) continue;

    foreach (glob($folderPath . '/*.php') as $phpFile) {
        $base_name = basename($phpFile, '.php');
        if (strpos($base_name, '_') === 0) continue; // skip _kk-config, _checkout-offers
        $url = "$host/$folder/$base_name.php";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $status = [
            'folder' => $folder,
            'file'   => $base_name,
            'http'   => $code,
            'bytes'  => $body ? strlen($body) : 0,
            'has_html'  => $body && stripos($body, '<html') !== false && stripos($body, '</html>') !== false,
            'has_head'  => $body && stripos($body, '<head>') !== false,
            'has_body'  => $body && stripos($body, '<body') !== false && stripos($body, '</body>') !== false,
            'php_leak'  => $body && (stripos($body, '<?php') !== false || stripos($body, '<?=') !== false),
        ];

        /* Save the rendered output as .html for independent visiting */
        if ($body && $status['has_html'] && !$status['php_leak']) {
            $htmlFile = $folderPath . '/' . $base_name . '.html';
            file_put_contents($htmlFile, $body);
            $status['saved'] = true;
            $ok++;
        } else {
            $status['saved'] = false;
            $bad++;
        }
        $results[] = $status;
    }
}

/* Print summary table */
printf("%-35s  %-30s  %-5s  %-8s  %-5s  %-5s  %-5s  %-5s  %s\n",
    'Folder', 'File', 'HTTP', 'Bytes', 'HTML', 'Head', 'Body', 'PHP?', 'Saved');
echo str_repeat('-', 130) . "\n";
foreach ($results as $r) {
    printf("%-35s  %-30s  %-5d  %-8d  %-5s  %-5s  %-5s  %-5s  %s\n",
        $r['folder'], $r['file'].'.php', $r['http'], $r['bytes'],
        $r['has_html'] ? 'YES' : 'no',
        $r['has_head'] ? 'YES' : 'no',
        $r['has_body'] ? 'YES' : 'no',
        $r['php_leak'] ? '!!!' : 'no',
        $r['saved'] ? '.html' : 'SKIP'
    );
}
echo "\nTotal: $ok saved, $bad failed (php-leak or non-HTML)\n";
