<?php
/**
 * Inject the Pro Max tier card after the Scale tier in Design A checkouts.
 * Also update the JS billing toggle to include the new tier.
 */
$base = realpath(__DIR__ . '/../..');
$folders = ['pricing-v2-kk1', 'sabrina-pricing-v2-kk1'];

$promaxBlock = file_get_contents('/tmp/promax_block_designA.html');

/* The Scale tier card opens with `<!-- Scale -->` and the next thing after
   its closing `</div>` (the data-plan="scale" wrapper) is `\n\n          </div>`
   which is the grid container close. We want to inject the Pro Max block right
   before that grid close. */
$anchor    = "            </div>\n\n          </div>\n        </div>\n\n      </div>";
/* That's the sequence right after Scale's tier closes. Let me anchor on a more
   unique pattern by including the line above. */
$anchorAlt = "                  <span class=\"inline-flex items-center gap-1\"><svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"h-3 w-3 text-emerald-400\" aria-hidden=\"true\"><path d=\"M20 6 9 17l-5-5\"></path></svg> Cancel anytime</span>\n                </div>\n              </div>\n            </div>\n\n          </div>";

/* We'll insert by finding the very LAST '            </div>\n\n          </div>' tier
   close (Scale is the last existing tier) and prepending Pro Max block before
   the grid close `          </div>`. */

foreach ($folders as $folder) {
    $path = $base . '/' . $folder . '/checkout.php';
    if (!is_file($path)) { fwrite(STDERR, "skip: $folder\n"); continue; }
    $c = file_get_contents($path);

    /* Idempotent guard */
    if (strpos($c, 'data-plan="promax"') !== false) {
        echo "noop (Pro Max already present): $folder\n";
        continue;
    }

    /* Find: the LAST occurrence of '<!-- Scale -->' block ending. We anchor
       on the closing </div> of the tier (12 spaces indent) followed by blank
       line and grid-close (10 spaces indent </div>). */
    $needle = "            </div>\n\n          </div>\n        </div>\n\n      </div>";
    $pos = strpos($c, $needle);
    if ($pos === false) {
        fwrite(STDERR, "anchor not found in: $folder\n");
        continue;
    }
    /* Insert Pro Max block BEFORE the '          </div>' (grid close), so AFTER
       the Scale tier's '            </div>'. */
    $insertAt = $pos + strlen("            </div>\n\n");
    $new = substr($c, 0, $insertAt) . $promaxBlock . "\n" . substr($c, $insertAt);
    file_put_contents($path, $new);
    echo "injected Pro Max tier into: $folder/checkout.php\n";
}
echo "done\n";
