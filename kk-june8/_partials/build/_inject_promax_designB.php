<?php
/**
 * Inject Pro Max card after Scale tier into Design B (flash-sale) checkouts.
 * The Pro Max block is loaded from /tmp/promax_block_designB.html which
 * contains the PHP echo for the KK token.
 */
$base = realpath(__DIR__ . '/../..');
$folders = ['flash-sale-kk1', 'sabrina-flash-sale-kk1'];

$promaxBlock = file_get_contents('/tmp/promax_block_designB.html');

/* Anchor: the line right after Scale's "Cancel anytime" → tier close → grid close */
$anchor = "          <span class=\"inline-flex items-center gap-1\"><svg width=\"11\" height=\"11\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8\"/><path d=\"M3 3v5h5\"/></svg>Cancel anytime</span>\n        </div>\n      </div>\n    </div>\n  </div>\n</section>";

foreach ($folders as $folder) {
    $path = $base . '/' . $folder . '/checkout.php';
    if (!is_file($path)) { fwrite(STDERR, "skip: $folder\n"); continue; }
    $c = file_get_contents($path);

    if (strpos($c, 'data-plan="promax"') !== false) {
        echo "noop (Pro Max already present): $folder\n";
        continue;
    }

    $pos = strpos($c, $anchor);
    if ($pos === false) {
        fwrite(STDERR, "anchor not found in: $folder\n");
        continue;
    }
    /* Insert promax block AFTER Scale tier's `</div>` (Scale close), BEFORE the
       grid container `</div>`. The anchor contains:
          ...Cancel anytime</span>\n        </div>\n      </div>\n    </div>\n
       and we insert after the SECOND \n      </div>\n  (which is Scale close). */
    $insertAfter = "Cancel anytime</span>\n        </div>\n      </div>\n";
    $insertPos = strpos($c, $insertAfter, $pos) + strlen($insertAfter);
    $new = substr($c, 0, $insertPos) . $promaxBlock . "\n" . substr($c, $insertPos);
    file_put_contents($path, $new);
    echo "injected Pro Max tier into: $folder/checkout.php\n";
}
echo "done\n";
