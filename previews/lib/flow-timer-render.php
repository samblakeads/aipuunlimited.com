<?php
declare(strict_types=1);

/**
 * flow-timer-render.php — one centralized countdown for a whole flow.
 *
 * A single evergreen duration is configured per flow (flow.json -> timer_config)
 * and rendered as ONE self-contained <script> block that becomes the sole driver
 * of every countdown element on the page:
 *
 *   - promo-bar / hero timers  : [data-cd-d] [data-cd-h] [data-cd-m] [data-cd-s]
 *   - plan-picker checkout      : .promo-clock-row .promo-num  (d,h,m,s)
 *   - sabrina-style timers      : [data-cd="dd|hh|mm|ss"]
 *   - exit popups / sliders     : [data-kkw-count]  (via window.__KK_FLOW_TIMER,
 *                                 consumed by widget-render.php's startCountdown)
 *
 * The deadline is stored under ONE flow-namespaced key so the sales page and the
 * checkout (same tab / origin) show the EXACT same remaining time — that is the
 * whole point: the timer is adjustable only on the flow, and stays consistent
 * across every surface.
 *
 * To avoid two clocks fighting over the same DOM, the per-page legacy timer
 * scripts are neutralized (NOT removed) by the helpers here — the markup/design
 * is untouched, only the old driver script is switched off.
 *
 * Used by:
 *   - previews/flow-timer.php  (bakes the block into flow pages + neutralizes legacy)
 *
 * Schema (timer_config):
 *   {
 *     "enabled":  true,
 *     "duration": { "d": 0, "h": 12, "m": 31, "s": 0 },
 *     "scope":    "session" | "persistent",
 *     "apply_to": { "sales": true, "checkout": true, "widgets": true }
 *   }
 */

if (!defined('FLOW_TIMER_MARK_BEGIN')) {
    define('FLOW_TIMER_MARK_BEGIN', '<!-- KK-TIMER:BEGIN -->');
    define('FLOW_TIMER_MARK_END', '<!-- KK-TIMER:END -->');
    define('FLOW_TIMER_DISABLED_TYPE', 'text/kk-timer-disabled');
}

if (!function_exists('flow_timer_default_config')) {

    function flow_timer_default_config(): array
    {
        return [
            'enabled'  => false,
            'duration' => ['d' => 0, 'h' => 12, 'm' => 31, 's' => 0],
            'scope'    => 'session',
            'apply_to' => ['sales' => true, 'checkout' => true, 'widgets' => true],
        ];
    }

    /** Clamp + normalize a raw timer_config (from flow.json or POST). */
    function flow_timer_normalize($raw): array
    {
        $def = flow_timer_default_config();
        if (!is_array($raw)) {
            return $def;
        }
        $durRaw = is_array($raw['duration'] ?? null) ? $raw['duration'] : [];
        $clampInt = static function ($v, int $min, int $max): int {
            $n = (int)$v;
            if ($n < $min) return $min;
            if ($n > $max) return $max;
            return $n;
        };
        $duration = [
            'd' => $clampInt($durRaw['d'] ?? 0, 0, 365),
            'h' => $clampInt($durRaw['h'] ?? 0, 0, 23),
            'm' => $clampInt($durRaw['m'] ?? 0, 0, 59),
            's' => $clampInt($durRaw['s'] ?? 0, 0, 59),
        ];
        $scope = strtolower(trim((string)($raw['scope'] ?? 'session')));
        if ($scope !== 'persistent') {
            $scope = 'session';
        }
        $applyRaw = is_array($raw['apply_to'] ?? null) ? $raw['apply_to'] : [];
        $apply = [
            'sales'    => !empty($applyRaw['sales']),
            'checkout' => !empty($applyRaw['checkout']),
            'widgets'  => !empty($applyRaw['widgets']),
        ];
        // a fresh/never-saved config defaults every surface on
        if (!array_key_exists('apply_to', $raw)) {
            $apply = ['sales' => true, 'checkout' => true, 'widgets' => true];
        }
        return [
            'enabled'  => !empty($raw['enabled']),
            'duration' => $duration,
            'scope'    => $scope,
            'apply_to' => $apply,
        ];
    }

    /** Total configured duration in whole seconds. */
    function flow_timer_total_seconds(array $cfg): int
    {
        $d = $cfg['duration'] ?? [];
        return (((((int)($d['d'] ?? 0) * 24) + (int)($d['h'] ?? 0)) * 60)
            + (int)($d['m'] ?? 0)) * 60 + (int)($d['s'] ?? 0);
    }

    /** The runtime engine: the single driver for every countdown element. */
    function flow_timer_engine_js(): string
    {
        return <<<'JS'
(function () {
  var cfg = window.__KK_FLOW_TIMER_CFG;
  if (!cfg || !cfg.enabled) return;
  if (window.__KK_FLOW_TIMER_BOUND) return;
  window.__KK_FLOW_TIMER_BOUND = true;

  var totalMs = (((((cfg.d || 0) * 24) + (cfg.h || 0)) * 60 + (cfg.m || 0)) * 60 + (cfg.s || 0)) * 1000;
  if (totalMs <= 0) return;

  function store() {
    try { return cfg.scope === 'persistent' ? window.localStorage : window.sessionStorage; }
    catch (e) { return null; }
  }

  // ONE deadline, shared across sales + checkout (same key + scope) so the
  // funnel always shows the same number. Evergreen: restart once expired.
  var s = store(), key = cfg.key, deadline = 0, now = Date.now();
  try { deadline = parseInt((s && s.getItem(key)) || '0', 10); } catch (e) { deadline = 0; }
  if (!deadline || deadline < now) {
    deadline = now + totalMs;
    try { if (s) s.setItem(key, String(deadline)); } catch (e) {}
  }

  // Expose for the widget engine (popups + sliders) so [data-kkw-count] uses
  // the SAME deadline instead of its own clock.
  window.__KK_FLOW_TIMER = { deadline: deadline, applyWidgets: !!cfg.applyWidgets };

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function setAll(list, txt) { for (var i = 0; i < list.length; i++) list[i].textContent = txt; }

  function paint() {
    var ms = Math.max(0, deadline - Date.now());
    var t = Math.floor(ms / 1000);
    var days = Math.floor(t / 86400);
    var hWithinDay = Math.floor((t % 86400) / 3600);
    var totalHours = Math.floor(t / 3600);
    var mm = Math.floor((t % 3600) / 60);
    var ss = t % 60;

    var dEls = document.querySelectorAll('[data-cd-d]');
    var hEls = document.querySelectorAll('[data-cd-h]');
    var mEls = document.querySelectorAll('[data-cd-m]');
    var sEls = document.querySelectorAll('[data-cd-s]');
    // A page WITHOUT a days field folds days into the hours field (legacy behavior).
    var hForCd = dEls.length ? hWithinDay : totalHours;
    setAll(dEls, pad(days));
    setAll(hEls, pad(hForCd));
    setAll(mEls, pad(mm));
    setAll(sEls, pad(ss));

    // plan-picker style: .promo-clock-row > .promo-num (d,h,m,s) or (h,m,s)
    var rows = document.querySelectorAll('.promo-clock-row');
    for (var r = 0; r < rows.length; r++) {
      var nums = rows[r].querySelectorAll('.promo-num');
      if (nums.length >= 4) {
        nums[0].textContent = pad(days); nums[1].textContent = pad(hWithinDay);
        nums[2].textContent = pad(mm);   nums[3].textContent = pad(ss);
      } else if (nums.length === 3) {
        nums[0].textContent = pad(totalHours); nums[1].textContent = pad(mm); nums[2].textContent = pad(ss);
      }
    }

    // sabrina-style data-cd="dd|hh|mm|ss"
    setAll(document.querySelectorAll('[data-cd="dd"]'), pad(days));
    setAll(document.querySelectorAll('[data-cd="hh"]'), pad(dEls.length ? hWithinDay : totalHours));
    setAll(document.querySelectorAll('[data-cd="mm"]'), pad(mm));
    setAll(document.querySelectorAll('[data-cd="ss"]'), pad(ss));

    if (ms <= 0) { clearInterval(timer); }
  }

  paint();
  var timer = setInterval(paint, 250);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) paint(); });
})();
JS;
    }

    /**
     * Render the injectable timer block for a page, or '' when this page/flow
     * should not carry the centralized timer.
     *
     * $ctx: [ 'page' => 'sales'|'checkout', 'flow' => slug ]
     */
    function flow_timer_render_block(array $cfg, array $ctx): string
    {
        $cfg = flow_timer_normalize($cfg);
        if (empty($cfg['enabled'])) {
            return '';
        }
        $page = ($ctx['page'] ?? 'sales') === 'checkout' ? 'checkout' : 'sales';
        if (empty($cfg['apply_to'][$page])) {
            return '';
        }
        if (flow_timer_total_seconds($cfg) <= 0) {
            return '';
        }

        $flow = preg_replace('/[^A-Za-z0-9._-]/', '', (string)($ctx['flow'] ?? ''));
        $d = $cfg['duration'];
        $jsCfg = [
            'enabled'      => true,
            'd'            => (int)$d['d'],
            'h'            => (int)$d['h'],
            'm'            => (int)$d['m'],
            's'            => (int)$d['s'],
            'scope'        => $cfg['scope'],
            'key'          => 'kk_flow_timer_' . ($flow !== '' ? $flow : 'preview'),
            'applyWidgets' => !empty($cfg['apply_to']['widgets']),
        ];
        $config = json_encode($jsCfg, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $out  = "<script>window.__KK_FLOW_TIMER_CFG = " . $config . ";</script>\n";
        $out .= "<script>\n" . flow_timer_engine_js() . "\n</script>";
        return $out;
    }

    /** Replace / insert the marked timer block before </body>; '' removes it. */
    function flow_timer_inject(string $html, string $block): string
    {
        $wrapped = $block === ''
            ? ''
            : FLOW_TIMER_MARK_BEGIN . "\n" . $block . "\n" . FLOW_TIMER_MARK_END;

        $beginPos = strpos($html, FLOW_TIMER_MARK_BEGIN);
        if ($beginPos !== false) {
            $endPos = strpos($html, FLOW_TIMER_MARK_END, $beginPos);
            if ($endPos !== false) {
                $endPos += strlen(FLOW_TIMER_MARK_END);
                $trail = ($wrapped === '' && ($html[$endPos] ?? '') === "\n") ? 1 : 0;
                return substr($html, 0, $beginPos) . $wrapped . substr($html, $endPos + $trail);
            }
            $html = substr($html, 0, $beginPos);
            $html .= "\n</body>\n</html>\n";
        }
        if ($wrapped === '') {
            return $html;
        }
        $pos = strripos($html, '</body>');
        if ($pos === false) {
            return $html . "\n" . $wrapped . "\n";
        }
        return substr($html, 0, $pos) . $wrapped . "\n" . substr($html, $pos);
    }

    /**
     * Switch OFF the per-page legacy countdown driver scripts inside an HTML
     * document, without touching any markup. Reversible (see flow_timer_restore_html).
     *
     * Handles:
     *   - standalone inline timer <script> (sales promo bar, flash-sale checkout)
     *     -> add type="text/kk-timer-disabled" so the browser ignores it
     *   - an embedded `function initCountdown()` IIFE (React plan-picker checkout)
     *     -> inject an early `return;` so the rest of the bundle is untouched
     */
    function flow_timer_neutralize_html(string $html): string
    {
        // 1) Standalone inline timer scripts.
        $html = preg_replace_callback(
            '#<script\b([^>]*)>(.*?)</script>#is',
            static function (array $m): string {
                $attrs = $m[1];
                $body  = $m[2];
                if (stripos($attrs, FLOW_TIMER_DISABLED_TYPE) !== false) {
                    return $m[0]; // already disabled
                }
                if (stripos($attrs, 'type=') !== false && stripos($attrs, 'javascript') === false
                    && stripos($attrs, 'module') === false) {
                    // some non-JS script (json-ld etc.) — leave alone unless it's clearly ours below
                }
                $drivesCd = (bool)preg_match('/\[data-cd-[dhms]\]/', $body)
                    || (bool)preg_match('/data-cd=\s*["\'](?:dd|hh|mm|ss)["\']/', $body);
                $isTimer = (strpos($body, 'DURATION') !== false)
                    || (bool)preg_match('/[A-Za-z0-9_]*_cd_end/', $body)
                    || strpos($body, 'WINDOW_SECONDS') !== false
                    || strpos($body, 'promo_deadline') !== false;
                if ($drivesCd && $isTimer) {
                    return '<!-- KK-TIMER:LEGACY-DISABLED -->' . "\n"
                        . '<script type="' . FLOW_TIMER_DISABLED_TYPE . '"' . $attrs . '>' . $body . '</script>';
                }
                return $m[0];
            },
            $html
        );

        // 2) Embedded initCountdown() IIFE (checkout plan-picker bundle).
        if (strpos($html, 'KK-TIMER-DISABLED-IC') === false) {
            $html = preg_replace(
                '/(function\s+initCountdown\s*\([^)]*\)\s*\{)/',
                '$1 return; /* KK-TIMER-DISABLED-IC */',
                $html,
                1
            );
        }

        return (string)$html;
    }

    /** Re-enable legacy timer scripts (reverse of flow_timer_neutralize_html). */
    function flow_timer_restore_html(string $html): string
    {
        $html = str_replace('<!-- KK-TIMER:LEGACY-DISABLED -->' . "\n", '', $html);
        $html = preg_replace(
            '#<script\s+type="' . preg_quote(FLOW_TIMER_DISABLED_TYPE, '#') . '"([^>]*)>#i',
            '<script$1>',
            $html
        );
        $html = str_replace(' return; /* KK-TIMER-DISABLED-IC */', '', (string)$html);
        return (string)$html;
    }

    /** Switch OFF the daily-midnight countdown inside a main.js asset. */
    function flow_timer_neutralize_mainjs(string $js): string
    {
        if (strpos($js, 'KK-TIMER-DISABLED-MJS') !== false) {
            return $js;
        }
        return (string)preg_replace(
            '/if\s*\(\s*cdh\s*&&\s*cdm\s*&&\s*cds\s*\)\s*\{/',
            'if (false /* KK-TIMER-DISABLED-MJS */ && cdh && cdm && cds) {',
            $js,
            1
        );
    }

    /** Re-enable the main.js daily countdown. */
    function flow_timer_restore_mainjs(string $js): string
    {
        return str_replace('false /* KK-TIMER-DISABLED-MJS */ && ', '', $js);
    }
}
