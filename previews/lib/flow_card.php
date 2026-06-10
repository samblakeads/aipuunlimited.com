<?php
declare(strict_types=1);

/**
 * flow_card.php — single source of truth for one flow card's markup.
 *
 * The previews grid (index.php) and the live-insert partial (flow-card.php) both
 * render flow cards. To avoid two divergent copies, the card template lives here
 * in render_flow_card(); the index.php grid loop and flow-card.php both call it.
 *
 * $g is one grouped "Sales Flow" object as produced by index.php's grouping
 * step: ['base'=>..., 'variants'=>['aipu'=>{...},'omni'=>{...}], 'created'=>int,
 * 'modified'=>int, 'flowType'=>...]. Each variant is a scanFlows() row
 * (name, brand, salesPath, checkoutPath, kk, ...).
 */
function render_flow_card(array $g): string
{
    $variants = $g['variants'];
    $primaryBrand = isset($variants['omni']) ? 'omni' : 'aipu';
    $primary = $variants[$primaryBrand];
    $base = $g['base'];
    $isMulti = ($g['flowType'] === 'multi');
    $variantNames = [];
    foreach ($variants as $vb => $vf) { $variantNames[$vb] = $vf['name']; }
    $variantsJson = json_encode($variantNames, JSON_UNESCAPED_SLASHES);
    $kkAll = true; $kkAny = false;
    foreach ($variants as $vf) { $kkAll = $kkAll && $vf['kk']; $kkAny = $kkAny || $vf['kk']; }
    $searchText = strtolower($base . ' ' . implode(' ', $variantNames));
    $brandLabel = ['aipu' => 'AIPU', 'omni' => 'OmniRogue'];

    ob_start();
    ?>
        <article class="card flow-card" data-kind="flow"
                 data-base="<?= htmlspecialchars($base, ENT_QUOTES) ?>"
                 data-name="<?= htmlspecialchars($primary['name'], ENT_QUOTES) ?>"
                 data-brand="<?= htmlspecialchars($primaryBrand, ENT_QUOTES) ?>"
                 data-collection=""
                 data-variants="<?= htmlspecialchars($variantsJson, ENT_QUOTES) ?>"
                 data-multi="<?= $isMulti ? '1' : '0' ?>"
                 <?php foreach ($variants as $vb => $vf): ?>
                 data-sales-<?= $vb ?>="<?= htmlspecialchars($vf['salesPath'], ENT_QUOTES) ?>"
                 <?php if ($vf['checkoutPath']): ?>data-checkout-<?= $vb ?>="<?= htmlspecialchars($vf['checkoutPath'], ENT_QUOTES) ?>"<?php endif; ?>
                 data-kk-<?= $vb ?>="<?= $vf['kk'] ? '1' : '0' ?>"
                 data-name-<?= $vb ?>="<?= htmlspecialchars($vf['name'], ENT_QUOTES) ?>"
                 <?php endforeach; ?>
                 data-search="<?= htmlspecialchars($searchText, ENT_QUOTES) ?>">
          <div class="preview-frame-wrap">
            <label class="card-select" title="Select for bulk actions"><input type="checkbox" class="select-box" aria-label="Select <?= htmlspecialchars($base, ENT_QUOTES) ?>"></label>
            <button type="button" class="card-trash flow-trash-btn" title="Archive this flow (both brands) — restorable from the Archive" aria-label="Archive <?= htmlspecialchars($base, ENT_QUOTES) ?>">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
            </button>
            <?php if (count($variants) > 1): ?>
            <div class="brand-switch" role="group" aria-label="Preview brand">
              <?php foreach (['aipu', 'omni'] as $bb): if (!isset($variants[$bb])) continue; ?>
              <button type="button" class="brand-switch-btn<?= $bb === $primaryBrand ? ' active' : '' ?>" data-brand-switch="<?= $bb ?>"><span class="brand-dot <?= $bb ?>"></span><?= $brandLabel[$bb] ?></button>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <iframe src="<?= htmlspecialchars($primary['salesPath'], ENT_QUOTES) ?>" class="flow-preview-frame" loading="lazy" title="Preview: <?= htmlspecialchars($base, ENT_QUOTES) ?>" tabindex="-1"></iframe>
            <div class="preview-overlay">
              <div class="preview-overlay-links">
                <a href="<?= htmlspecialchars($primary['salesPath'], ENT_QUOTES) ?>" class="flow-open-sales" target="_blank" rel="noopener">Open sales page ↗</a>
                <button type="button" class="btn btn-mobile flow-mobile-btn" data-label="<?= htmlspecialchars($base, ENT_QUOTES) ?>">Mobile preview</button>
              </div>
            </div>
          </div>
          <div class="card-body">
            <h3 class="card-name"><?= htmlspecialchars($base, ENT_QUOTES) ?></h3>
            <div class="flow-meta-row">
              <span class="flow-created" title="Date &amp; time this flow was generated">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?= htmlspecialchars(date('M j, Y · g:i A', $g['created']), ENT_QUOTES) ?>
              </span>
            </div>
            <div class="flow-badges">
              <?php foreach (['aipu', 'omni'] as $bb): if (!isset($variants[$bb])) continue; ?>
              <span class="flow-badge brand-<?= $bb ?>"><?= $brandLabel[$bb] ?><?= $variants[$bb]['kk'] ? ' · KK ✓' : '' ?></span>
              <?php endforeach; ?>
              <span class="flow-badge sales"><?= $isMulti ? 'sales + checkout' : htmlspecialchars($g['flowType'], ENT_QUOTES) ?></span>
            </div>

            <div class="card-actions">
              <div class="dropdown flow-preview-dd">
                <button type="button" class="btn btn-primary dropdown-toggle" aria-haspopup="true" aria-expanded="false">Preview ▾</button>
                <div class="dropdown-menu" role="menu">
                  <?php foreach (['aipu', 'omni'] as $bb): if (!isset($variants[$bb])) continue; ?>
                  <div class="dropdown-heading"><span class="brand-dot <?= $bb ?>"></span><?= $brandLabel[$bb] ?></div>
                  <a class="dropdown-item" href="<?= htmlspecialchars($variants[$bb]['salesPath'], ENT_QUOTES) ?>" target="_blank" rel="noopener">Sales page ↗</a>
                  <?php if ($variants[$bb]['checkoutPath']): ?>
                  <a class="dropdown-item" href="<?= htmlspecialchars($variants[$bb]['checkoutPath'], ENT_QUOTES) ?>" target="_blank" rel="noopener">Checkout ↗</a>
                  <?php endif; ?>
                  <?php endforeach; ?>
                  <div class="dropdown-sep"></div>
                  <button type="button" class="dropdown-item flow-mobile-btn" data-label="<?= htmlspecialchars($base, ENT_QUOTES) ?>">📱 Mobile preview</button>
                </div>
              </div>

              <div class="kk-dl-group" role="group" aria-label="Download KK packages">
                <span class="kk-dl-label">Download KK</span>
                <button type="button" class="btn btn-kk btn-kk-sm flow-kk-download" data-kk-brand="aipu" data-base="<?= rawurlencode($base) ?>" title="Download AIPU KK package"><span class="brand-dot aipu"></span> AIPU</button>
                <button type="button" class="btn btn-kk btn-kk-sm flow-kk-download" data-kk-brand="omni" data-base="<?= rawurlencode($base) ?>" title="Download OmniRogue KK package"><span class="brand-dot omni"></span> Omni</button>
                <button type="button" class="btn btn-kk btn-kk-sm flow-kk-download" data-kk-brand="all" data-base="<?= rawurlencode($base) ?>" title="Download both brand packages">Both</button>
                <div class="kk-dl-error" role="alert"></div>
              </div>

              <div class="dropdown flow-configure">
                <button type="button" class="btn btn-flow-plans dropdown-toggle" aria-haspopup="true" aria-expanded="false">Configure ▾</button>
                <div class="dropdown-menu" role="menu">
                  <button type="button" class="dropdown-item flow-plans-btn" data-flow="<?= htmlspecialchars($primary['name'], ENT_QUOTES) ?>" data-variants="<?= htmlspecialchars($variantsJson, ENT_QUOTES) ?>">Plans &amp; prices</button>
                  <button type="button" class="dropdown-item cta-config-btn" data-flow="<?= htmlspecialchars($primary['name'], ENT_QUOTES) ?>" data-variants="<?= htmlspecialchars($variantsJson, ENT_QUOTES) ?>">CTA routing</button>
                  <button type="button" class="dropdown-item flow-widgets-btn" data-flow="<?= htmlspecialchars($primary['name'], ENT_QUOTES) ?>" data-variants="<?= htmlspecialchars($variantsJson, ENT_QUOTES) ?>">Widgets &amp; popups</button>
                  <button type="button" class="dropdown-item flow-timer-btn" data-flow="<?= htmlspecialchars($primary['name'], ENT_QUOTES) ?>" data-variants="<?= htmlspecialchars($variantsJson, ENT_QUOTES) ?>">Timer</button>
                  <button type="button" class="dropdown-item flow-assets-btn" data-flow="<?= htmlspecialchars($primary['name'], ENT_QUOTES) ?>" data-variants="<?= htmlspecialchars($variantsJson, ENT_QUOTES) ?>">Optimize &amp; publish</button>
                  <div class="dropdown-sep"></div>
                  <button type="button" class="dropdown-item kk-format-group" data-base="<?= htmlspecialchars($base, ENT_QUOTES) ?>" data-variants="<?= htmlspecialchars($variantsJson, ENT_QUOTES) ?>" data-reformat="<?= $kkAny ? '1' : '0' ?>">↻ Re-format KK (both)</button>
                  <button type="button" class="dropdown-item dropdown-item-danger flow-delete-group">🗑 Archive flow (both brands)</button>
                </div>
              </div>

              <button type="button" class="btn btn-ai btn-ai-edit" title="Edit the selected brand with AI">✨ Edit with AI</button>
            </div>
          </div>
        </article>
    <?php
    return (string)ob_get_clean();
}
