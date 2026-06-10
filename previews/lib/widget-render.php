<?php
declare(strict_types=1);

/**
 * widget-render.php — render conversion widgets (exit popups + mobile sticky
 * sliders) into a single self-contained <style> + markup + <script> snippet.
 *
 * Used by:
 *   - previews/widgets.php      (builder live preview — ctx.preview = true)
 *   - previews/flow-widgets.php (bakes the snippet into flow pages)
 *
 * The snippet has zero external dependencies. CTA URLs are resolved at
 * runtime by the embedded engine so the SAME snippet works in:
 *   - demo HTML   (flows/{slug}/index.html → checkout.html links)
 *   - KK PHP      (anchors rewritten by kk_format.py; offer links read from
 *                  window.__KK_OFFER_LINKS at click time)
 *
 * widget_render_block(array $widgets, array $ctx): string
 *   $widgets : list of widget configs (widget-library.php schema)
 *   $ctx     : [
 *     'page'    => 'sales' | 'checkout',
 *     'flow'    => string flow slug (storage-key namespace; '' for preview),
 *     'plans'   => [plan_id => ['name' => display_name,
 *                               'tokens' => ['monthly'=>..,'yearly'=>..,'lifetime'=>..]]],
 *     'preview' => bool (builder preview: no frequency gating, fire instantly),
 *   ]
 */

if (!function_exists('widget_render_block')) {

    function wr_e(?string $s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }

    /** Resolve the KK offer token for a widget offer (plan + billing slot). */
    function wr_token(?string $planId, string $slot, array $plans): ?string
    {
        if ($planId === null || $planId === '' || !isset($plans[$planId])) {
            return null;
        }
        $tokens = $plans[$planId]['tokens'] ?? [];
        return isset($tokens[$slot]) && $tokens[$slot] !== null && $tokens[$slot] !== ''
            ? (string)$tokens[$slot] : null;
    }

    /** Markup for one offer price row. */
    function wr_price_row(array $o): string
    {
        if (($o['price'] ?? '') === '') {
            return '';
        }
        $h = '<div class="kkw-price">';
        if (($o['strike_price'] ?? '') !== '') {
            $h .= '<span class="kkw-strike">$' . wr_e($o['strike_price']) . '</span>';
        }
        $h .= '<span class="kkw-num">$' . wr_e($o['price']) . '</span>';
        if (($o['price_meta'] ?? '') !== '') {
            $h .= '<span class="kkw-meta">' . wr_e($o['price_meta']) . '</span>';
        }
        $h .= '</div>';
        return $h;
    }

    function wr_arrow_svg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';
    }

    /** Popup markup (spotlight = single offer, duo = two choice cards). */
    function wr_popup_markup(array $w, string $page): string
    {
        $id     = wr_e($w['id']);
        $accent = wr_e($w['accent'] ?? 'gold');
        $isDuo  = ($w['design'] ?? 'spotlight') === 'duo' && is_array($w['secondary'] ?? null);

        $countdown = '';
        if (($w['countdown']['mode'] ?? 'off') !== 'off') {
            $countdown = '<span class="kkw-count" data-kkw-count>--:--</span>';
        }

        $h  = '<div class="kkw-scrim" id="kkw-' . $id . '" data-kkw-root="' . $id . '" role="dialog" aria-modal="true" aria-labelledby="kkw-title-' . $id . '">';
        $h .= '<div class="kkw-card kkw-acc-' . $accent . '"><div class="kkw-inner">';
        $h .= '<button type="button" class="kkw-close" data-kkw-close aria-label="Close">'
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>'
            . '</button>';
        $h .= '<span class="kkw-eyebrow"><span class="kkw-pulse"></span> Wait — before you go' . ($countdown !== '' ? ' · ' . $countdown : '') . '</span>';

        if (($w['image'] ?? '') !== '') {
            $h .= '<img class="kkw-hero" src="' . wr_e($w['image']) . '" alt="" loading="lazy">';
        }

        $h .= '<h2 class="kkw-title" id="kkw-title-' . $id . '">' . wr_e($w['title']) . '</h2>';
        if (($w['subtitle'] ?? '') !== '') {
            $h .= '<p class="kkw-lead">' . wr_e($w['subtitle']) . '</p>';
        }

        if ($isDuo) {
            $s = $w['secondary'];
            $h .= '<div class="kkw-grid">';
            // primary choice
            $h .= '<a class="kkw-choice kkw-choice-a" data-kkw-cta data-kkw-offer="primary" href="' . ($page === 'sales' ? 'checkout.html' : '#') . '">';
            $h .= '<span class="kkw-pill">Offer #1</span>';
            $h .= '<h4>' . wr_e($w['bonus_text'] !== '' ? $w['bonus_text'] : $w['title']) . '</h4>';
            $h .= wr_price_row($w);
            $h .= '<span class="kkw-cta">' . wr_e($w['cta_text'] !== '' ? $w['cta_text'] : 'Claim Offer') . ' ' . wr_arrow_svg() . '</span>';
            $h .= '</a>';
            // secondary choice
            $h .= '<a class="kkw-choice kkw-choice-b" data-kkw-cta data-kkw-offer="secondary" href="' . ($page === 'sales' ? 'checkout.html' : '#') . '">';
            $h .= '<span class="kkw-pill">Offer #2 · Best value</span>';
            $h .= '<h4>' . wr_e(($s['title'] ?? '') !== '' ? $s['title'] : 'Best value') . '</h4>';
            if (($s['bonus_text'] ?? '') !== '') {
                $h .= '<p class="kkw-choice-sub">' . wr_e($s['bonus_text']) . '</p>';
            }
            $h .= wr_price_row($s);
            $h .= '<span class="kkw-cta">' . wr_e(($s['cta_text'] ?? '') !== '' ? $s['cta_text'] : 'Claim Offer') . ' ' . wr_arrow_svg() . '</span>';
            $h .= '</a>';
            $h .= '</div>';
        } else {
            $h .= '<div class="kkw-offerbox">';
            $h .= wr_price_row($w);
            if (($w['bonus_text'] ?? '') !== '') {
                $h .= '<div class="kkw-bonus"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg> '
                    . wr_e($w['bonus_text']) . '</div>';
            }
            $h .= '<a class="kkw-cta kkw-cta-main" data-kkw-cta data-kkw-offer="primary" href="' . ($page === 'sales' ? 'checkout.html' : '#') . '">'
                . wr_e($w['cta_text'] !== '' ? $w['cta_text'] : 'Claim Offer') . ' ' . wr_arrow_svg() . '</a>';
            $h .= '</div>';
        }

        $h .= '<div class="kkw-foot">'
            . '<span class="kkw-trust"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> 30-day money-back guarantee on every plan</span>'
            . '<button type="button" class="kkw-dismiss" data-kkw-close>No thanks, I\'ll pass</button>'
            . '</div>';

        $h .= '</div></div></div>';
        return $h;
    }

    /** Mobile sticky slider (bottom bar) markup. */
    function wr_slider_markup(array $w, string $page): string
    {
        $id     = wr_e($w['id']);
        $accent = wr_e($w['accent'] ?? 'gold');

        $countdown = '';
        if (($w['countdown']['mode'] ?? 'off') !== 'off') {
            $countdown = ' <span class="kkw-count" data-kkw-count>--:--</span>';
        }

        $sub = trim((string)($w['subtitle'] ?? ''));

        $h  = '<div class="kkw-bar kkw-acc-' . $accent . '" id="kkw-' . $id . '" data-kkw-root="' . $id . '" role="complementary">';
        $h .= '<button type="button" class="kkw-bar-close" data-kkw-close aria-label="Dismiss">'
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>'
            . '</button>';
        $h .= '<div class="kkw-bar-body">';
        $h .= '<div class="kkw-bar-text"><strong>' . wr_e($w['title']) . '</strong>';
        if ($sub !== '' || $countdown !== '') {
            $h .= '<span>' . wr_e($sub) . $countdown . '</span>';
        }
        $h .= '</div>';
        $h .= '<a class="kkw-bar-cta" data-kkw-cta data-kkw-offer="primary" href="' . ($page === 'sales' ? 'checkout.html' : '#') . '">'
            . wr_e($w['cta_text'] !== '' ? $w['cta_text'] : 'Claim Offer') . ' ' . wr_arrow_svg() . '</a>';
        $h .= '</div></div>';
        return $h;
    }

    /** Social-proof toast (bottom-corner bubble). Messages are injected by the
     *  engine at runtime; this is just the shell. When link_checkout is on the
     *  whole bubble is a CTA so it reuses the shared checkout/plan engine. */
    function wr_social_markup(array $w, string $page): string
    {
        $id   = wr_e($w['id']);
        $acc  = wr_e($w['accent'] ?? 'emerald');
        $sp   = is_array($w['social'] ?? null) ? $w['social'] : [];
        $pos  = ($sp['position'] ?? 'bottom-left') === 'bottom-right' ? 'right' : 'left';
        $link = !empty($sp['link_checkout']);

        $tag  = $link ? 'a' : 'div';
        $attr = $link
            ? ' data-kkw-cta data-kkw-offer="primary" href="' . ($page === 'sales' ? 'checkout.html' : '#') . '"'
            : '';

        $h  = '<' . $tag . ' class="kkw-proof kkw-acc-' . $acc . '" id="kkw-' . $id . '"'
            . ' data-kkw-root="' . $id . '" data-kkw-pos="' . $pos . '" role="status" aria-live="polite"' . $attr . '>';
        $h .= '<span class="kkw-proof-dot"></span>';
        $h .= '<div class="kkw-proof-body"><p class="kkw-proof-text"></p><p class="kkw-proof-sub"></p></div>';
        if ($link) {
            $h .= '<span class="kkw-proof-arrow">' . wr_arrow_svg() . '</span>';
        }
        $h .= '</' . $tag . '>';
        return $h;
    }

    /** Shared CSS for every widget (accent classes + popup + bar + plan flash). */
    function wr_styles(): string
    {
        return <<<'CSS'
<style id="kkw-styles">
:root {
  --kkw-gold-a: #FFF8E7; --kkw-gold-b: #E6C97A; --kkw-gold-c: #D4A647;
  --kkw-purple-a: #B79CFF; --kkw-purple-b: #8B5CFF; --kkw-purple-c: #6366F1;
}
[data-kkw-root] { font-family: 'Hanken Grotesk','Inter',system-ui,-apple-system,'Segoe UI',sans-serif; }
/* ---------- popup ---------- */
.kkw-scrim {
  position: fixed; inset: 0; z-index: 99990;
  background: radial-gradient(ellipse at center, rgba(20,8,40,0.78), rgba(2,3,12,0.92));
  -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center;
  padding: 20px; opacity: 0; visibility: hidden;
  transition: opacity .25s ease, visibility .25s;
}
.kkw-scrim[data-open="true"] { opacity: 1; visibility: visible; }
.kkw-card {
  position: relative; width: min(680px, 100%);
  max-height: 92vh; overflow-y: auto;
  border-radius: 24px; padding: 2px;
  transform: translateY(14px) scale(.98);
  transition: transform .28s cubic-bezier(.2,.7,.2,1);
}
.kkw-scrim[data-open="true"] .kkw-card { transform: translateY(0) scale(1); }
.kkw-acc-gold.kkw-card, .kkw-bar.kkw-acc-gold {
  background: conic-gradient(from 200deg at 50% 50%, #FFF8E7 0deg, #E6C97A 70deg, #8B5CFF 160deg, #6366F1 250deg, #E6C97A 320deg, #FFF8E7 360deg);
  box-shadow: 0 0 60px -18px rgba(230,201,122,0.55), 0 0 120px -40px rgba(139,92,255,0.65), 0 60px 120px -30px rgba(0,0,0,0.92);
}
.kkw-acc-purple.kkw-card, .kkw-bar.kkw-acc-purple {
  background: conic-gradient(from 200deg at 50% 50%, #B79CFF 0deg, #8B5CFF 90deg, #6366F1 180deg, #B79CFF 270deg, #8B5CFF 360deg);
  box-shadow: 0 0 60px -18px rgba(139,92,255,0.6), 0 60px 120px -30px rgba(0,0,0,0.92);
}
.kkw-acc-emerald.kkw-card, .kkw-bar.kkw-acc-emerald {
  background: conic-gradient(from 200deg at 50% 50%, #6EE7B7 0deg, #10B981 90deg, #047857 180deg, #6EE7B7 270deg, #10B981 360deg);
  box-shadow: 0 0 60px -18px rgba(16,185,129,0.55), 0 60px 120px -30px rgba(0,0,0,0.92);
}
.kkw-acc-crimson.kkw-card, .kkw-bar.kkw-acc-crimson {
  background: conic-gradient(from 200deg at 50% 50%, #FDA4AF 0deg, #F43F5E 90deg, #BE123C 180deg, #FDA4AF 270deg, #F43F5E 360deg);
  box-shadow: 0 0 60px -18px rgba(244,63,94,0.55), 0 60px 120px -30px rgba(0,0,0,0.92);
}
.kkw-acc-blue.kkw-card, .kkw-bar.kkw-acc-blue {
  background: conic-gradient(from 200deg at 50% 50%, #93C5FD 0deg, #3B82F6 90deg, #1D4ED8 180deg, #93C5FD 270deg, #3B82F6 360deg);
  box-shadow: 0 0 60px -18px rgba(59,130,246,0.55), 0 60px 120px -30px rgba(0,0,0,0.92);
}
.kkw-inner {
  position: relative; overflow: hidden; border-radius: 22px;
  background:
    radial-gradient(circle at 12% 0%,  rgba(230,201,122,0.14), transparent 40%),
    radial-gradient(circle at 88% 18%, rgba(139,92,255,0.26),  transparent 45%),
    radial-gradient(circle at 50% 120%, rgba(99,102,241,0.18),  transparent 50%),
    linear-gradient(160deg, #08060F 0%, #0E0A22 50%, #08060F 100%);
  padding: 30px 28px 26px; color: #F4F6FF;
}
.kkw-close {
  position: absolute; top: 12px; right: 12px; z-index: 3;
  width: 36px; height: 36px; border-radius: 10px;
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10);
  color: rgba(244,246,255,0.75);
  display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .15s ease, color .15s ease;
}
.kkw-close:hover { background: rgba(255,255,255,0.12); color: #fff; }
.kkw-close svg { width: 18px; height: 18px; }
.kkw-eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 6px 12px; border-radius: 999px;
  background: rgba(255, 75, 75, 0.14); border: 1px solid rgba(255, 75, 75, 0.35);
  color: #FF9494; font-size: 11px; font-weight: 800;
  letter-spacing: 0.16em; text-transform: uppercase;
}
.kkw-pulse {
  width: 8px; height: 8px; border-radius: 999px; background: #FF4F4F;
  box-shadow: 0 0 0 4px rgba(255,79,79,0.25);
  animation: kkwPulse 1.2s ease-in-out infinite;
}
@keyframes kkwPulse {
  0%, 100% { box-shadow: 0 0 0 4px rgba(255,79,79,0.25); }
  50%      { box-shadow: 0 0 0 9px rgba(255,79,79,0); }
}
.kkw-count { font-variant-numeric: tabular-nums; color: #fff; }
.kkw-hero { display: block; width: 100%; max-height: 180px; object-fit: cover; border-radius: 14px; margin: 16px 0 0; }
.kkw-title {
  margin: 14px 0 10px;
  font-family: 'Plus Jakarta Sans','Hanken Grotesk',system-ui,sans-serif;
  font-size: clamp(22px, 4.5vw, 32px); font-weight: 900;
  letter-spacing: -0.02em; line-height: 1.12; color: #fff;
}
.kkw-acc-gold .kkw-title { background: linear-gradient(115deg, #FFF8E7 0%, #F5E1A4 28%, #E6C97A 52%, #D4A647 76%, #FFF8E7 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
.kkw-acc-purple .kkw-title { background: linear-gradient(110deg, #E6DcFF 0%, #B79CFF 40%, #8B5CFF 75%, #C7B6FF 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
.kkw-lead { margin: 0 0 20px; font-size: 14.5px; line-height: 1.55; color: rgba(244,246,255,0.72); }
.kkw-offerbox {
  border-radius: 16px; padding: 18px;
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.10);
  display: flex; flex-direction: column; gap: 12px;
}
.kkw-price { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
.kkw-strike { font-size: 17px; font-weight: 700; color: rgba(244,246,255,0.4); text-decoration: line-through; }
.kkw-num {
  font-family: 'Plus Jakarta Sans','Hanken Grotesk',system-ui,sans-serif;
  font-size: 34px; font-weight: 900; letter-spacing: -0.025em; color: #fff;
}
.kkw-meta { font-size: 11px; font-weight: 600; color: rgba(244,246,255,0.55); text-transform: uppercase; letter-spacing: 0.10em; }
.kkw-bonus {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px; font-weight: 600; color: #6EE7B7;
}
.kkw-bonus svg { flex: 0 0 16px; width: 16px; height: 16px; }
.kkw-cta {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  min-height: 50px; padding: 13px 18px; border-radius: 12px;
  font-size: 13.5px; font-weight: 800; letter-spacing: 0.03em;
  color: #fff; border: 0; cursor: pointer; font-family: inherit;
  text-decoration: none; text-align: center; line-height: 1.2;
  transition: transform .15s ease, filter .15s ease;
}
.kkw-cta:hover { transform: translateY(-1px); filter: brightness(1.07); }
.kkw-cta svg { flex: 0 0 14px; width: 14px; height: 14px; }
.kkw-acc-gold .kkw-cta, .kkw-bar.kkw-acc-gold .kkw-bar-cta, .kkw-choice-b .kkw-cta {
  color: #1A1208;
  background: linear-gradient(110deg, #FFF8E7 0%, #F5E1A4 32%, #E6C97A 60%, #D4A647 88%, #FFF8E7 100%);
  box-shadow: 0 10px 26px -8px rgba(230,201,122,0.65);
}
.kkw-acc-purple .kkw-cta, .kkw-bar.kkw-acc-purple .kkw-bar-cta {
  background: linear-gradient(110deg, #8B5CFF 0%, #6366F1 100%);
  box-shadow: 0 10px 26px -8px rgba(139,92,255,0.55);
}
.kkw-acc-emerald .kkw-cta, .kkw-bar.kkw-acc-emerald .kkw-bar-cta {
  background: linear-gradient(110deg, #10B981 0%, #047857 100%);
  box-shadow: 0 10px 26px -8px rgba(16,185,129,0.55);
}
.kkw-acc-crimson .kkw-cta, .kkw-bar.kkw-acc-crimson .kkw-bar-cta {
  background: linear-gradient(110deg, #F43F5E 0%, #BE123C 100%);
  box-shadow: 0 10px 26px -8px rgba(244,63,94,0.55);
}
.kkw-acc-blue .kkw-cta, .kkw-bar.kkw-acc-blue .kkw-bar-cta {
  background: linear-gradient(110deg, #3B82F6 0%, #1D4ED8 100%);
  box-shadow: 0 10px 26px -8px rgba(59,130,246,0.55);
}
.kkw-grid { display: grid; gap: 14px; grid-template-columns: 1fr; }
@media (min-width: 620px) { .kkw-grid { grid-template-columns: 1fr 1fr; } }
.kkw-choice {
  position: relative; overflow: hidden;
  display: flex; flex-direction: column; gap: 10px;
  padding: 18px 18px 16px; border-radius: 16px;
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.10);
  color: inherit; text-decoration: none;
  transition: transform .18s ease, border-color .18s ease, background .18s ease;
}
.kkw-choice:hover { transform: translateY(-2px); border-color: rgba(255,255,255,0.25); background: rgba(255,255,255,0.05); }
.kkw-choice-a { border-color: rgba(139,92,255,0.40); }
.kkw-choice-b { border-color: rgba(230,201,122,0.50); }
.kkw-pill {
  align-self: flex-start; display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 999px;
  font-size: 10.5px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase;
}
.kkw-choice-a .kkw-pill { background: rgba(139,92,255,0.16); color: #C7B6FF; border: 1px solid rgba(139,92,255,0.40); }
.kkw-choice-b .kkw-pill { background: rgba(230,201,122,0.16); color: #F5E1A4; border: 1px solid rgba(230,201,122,0.40); }
.kkw-choice h4 { margin: 4px 0 0; font-size: 15.5px; font-weight: 800; line-height: 1.3; color: #fff; }
.kkw-choice-sub { margin: 0; font-size: 13px; line-height: 1.5; color: rgba(244,246,255,0.65); }
.kkw-choice .kkw-num { font-size: 28px; }
.kkw-choice .kkw-cta { margin-top: 4px; min-height: 46px; font-size: 12px; }
.kkw-choice-a .kkw-cta { background: linear-gradient(110deg, #8B5CFF 0%, #6366F1 100%); box-shadow: 0 10px 26px -8px rgba(139,92,255,0.55); color: #fff; }
.kkw-foot {
  margin-top: 18px; display: flex; align-items: center; justify-content: space-between;
  gap: 12px; flex-wrap: wrap; padding-top: 14px;
  border-top: 1px solid rgba(255,255,255,0.08);
  font-size: 12px; color: rgba(244,246,255,0.5);
}
.kkw-trust { display: inline-flex; align-items: center; gap: 6px; }
.kkw-trust svg { width: 12px; height: 12px; color: #34D399; }
.kkw-dismiss {
  background: none; border: 0; cursor: pointer; font-family: inherit;
  font-size: 12px; color: rgba(244,246,255,0.45); text-decoration: underline;
  transition: color .15s ease;
}
.kkw-dismiss:hover { color: rgba(244,246,255,0.85); }
/* ---------- mobile sticky slider bar ---------- */
.kkw-bar {
  position: fixed; left: 10px; right: 10px; bottom: 10px; z-index: 99980;
  border-radius: 18px; padding: 2px;
  transform: translateY(calc(100% + 24px));
  transition: transform .38s cubic-bezier(.2,.7,.2,1);
}
.kkw-bar[data-open="true"] { transform: translateY(0); }
.kkw-bar-body {
  display: flex; align-items: center; gap: 12px;
  border-radius: 16px; padding: 12px 14px;
  background:
    radial-gradient(circle at 10% 0%, rgba(230,201,122,0.12), transparent 45%),
    radial-gradient(circle at 90% 100%, rgba(139,92,255,0.22), transparent 50%),
    linear-gradient(160deg, #0A0814 0%, #100B26 60%, #0A0814 100%);
  color: #F4F6FF;
}
.kkw-bar-text { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.kkw-bar-text strong {
  font-size: 13px; font-weight: 800; line-height: 1.3; color: #fff;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.kkw-bar-text span { font-size: 11px; color: rgba(244,246,255,0.6); }
.kkw-bar-cta {
  flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px;
  min-height: 42px; padding: 10px 14px; border-radius: 11px;
  font-size: 12px; font-weight: 800; letter-spacing: 0.02em;
  color: #fff; text-decoration: none; line-height: 1.15; text-align: center;
  font-family: inherit; cursor: pointer;
}
.kkw-bar-cta svg { flex: 0 0 12px; width: 12px; height: 12px; }
.kkw-bar-close {
  position: absolute; top: -9px; right: -4px; z-index: 3;
  width: 26px; height: 26px; border-radius: 999px;
  background: #1B1530; border: 1px solid rgba(255,255,255,0.18);
  color: rgba(244,246,255,0.8);
  display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
}
.kkw-bar-close svg { width: 13px; height: 13px; }
@media (min-width: 768px) {
  .kkw-bar { left: auto; right: 24px; bottom: 18px; width: min(460px, calc(100vw - 48px)); }
}
[data-kkw-root][data-kkw-hidden="1"] { display: none !important; }
/* ---------- social proof toast ---------- */
.kkw-proof {
  position: fixed; bottom: 16px; z-index: 99970;
  display: flex; align-items: flex-start; gap: 11px;
  width: min(330px, calc(100vw - 28px));
  padding: 11px 14px; border-radius: 14px;
  background: linear-gradient(160deg, rgba(14,11,30,0.97), rgba(8,6,16,0.97));
  border: 1px solid rgba(255,255,255,0.10);
  box-shadow: 0 18px 50px -16px rgba(0,0,0,0.85);
  color: #F4F6FF; text-decoration: none;
  opacity: 0; transform: translateY(14px) scale(.98);
  transition: opacity .35s ease, transform .35s cubic-bezier(.2,.7,.2,1);
  pointer-events: none;
}
a.kkw-proof { cursor: pointer; }
a.kkw-proof:hover { border-color: rgba(255,255,255,0.22); }
.kkw-proof[data-kkw-pos="left"]  { left: 16px; }
.kkw-proof[data-kkw-pos="right"] { right: 16px; }
.kkw-proof.kkw-proof-show { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
.kkw-proof-dot {
  flex: 0 0 9px; width: 9px; height: 9px; border-radius: 999px; margin-top: 3px;
  background: #34D399; box-shadow: 0 0 0 4px rgba(52,211,153,0.16);
  animation: kkwProofPulse 1.5s ease-in-out infinite;
}
.kkw-acc-gold    .kkw-proof-dot { background: #E6C97A; box-shadow: 0 0 0 4px rgba(230,201,122,0.16); }
.kkw-acc-purple  .kkw-proof-dot { background: #8B5CFF; box-shadow: 0 0 0 4px rgba(139,92,255,0.18); }
.kkw-acc-crimson .kkw-proof-dot { background: #F43F5E; box-shadow: 0 0 0 4px rgba(244,63,94,0.18); }
.kkw-acc-blue    .kkw-proof-dot { background: #3B82F6; box-shadow: 0 0 0 4px rgba(59,130,246,0.18); }
@keyframes kkwProofPulse { 0%,100% { transform: scale(1); opacity: 1; } 50% { transform: scale(.78); opacity: .6; } }
.kkw-proof-body { min-width: 0; flex: 1 1 auto; }
.kkw-proof-text { margin: 0; font-size: 13px; line-height: 1.35; color: #fff; font-weight: 600; }
.kkw-proof-text b { font-weight: 800; }
.kkw-proof-sub { margin: 2px 0 0; font-size: 11px; color: rgba(244,246,255,0.55); }
.kkw-proof-arrow { flex: 0 0 14px; margin-top: 2px; color: rgba(244,246,255,0.45); display: inline-flex; }
.kkw-proof-arrow svg { width: 14px; height: 14px; }
@media (max-width: 480px) {
  .kkw-proof { bottom: 10px; }
  .kkw-proof[data-kkw-pos="left"]  { left: 10px; }
  .kkw-proof[data-kkw-pos="right"] { right: 10px; }
}
/* ---------- checkout plan highlight ---------- */
@keyframes kkwPlanFlash {
  0%   { box-shadow: 0 0 0 0 rgba(230,201,122,0.0); }
  25%  { box-shadow: 0 0 0 5px rgba(230,201,122,0.85), 0 0 50px 8px rgba(230,201,122,0.35); }
  100% { box-shadow: 0 0 0 3px rgba(230,201,122,0.55), 0 0 34px 4px rgba(230,201,122,0.22); }
}
.kkw-plan-flash {
  animation: kkwPlanFlash 1.1s ease forwards;
  border-radius: 16px;
  position: relative; z-index: 5;
}
</style>
CSS;
    }

    /** The runtime engine (triggers, frequency, countdown, CTA + plan select). */
    function wr_engine_js(): string
    {
        return <<<'JS'
(function () {
  if (window.__KKW_BOUND) return;
  window.__KKW_BOUND = true;

  var CFG = window.__KKW_CONFIG || {};
  var widgets = CFG.widgets || [];
  var plans   = CFG.plans || {};
  var page    = CFG.page || 'sales';
  var preview = !!CFG.preview;
  var ns      = 'kkw_' + (CFG.flow || 'preview') + '_';

  var isMobile = function () {
    return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
  };

  function store(kind) {
    try { return kind === 's' ? window.sessionStorage : window.localStorage; }
    catch (e) { return null; }
  }
  function gateOpen(w) {
    if (preview) return true;
    var f = w.frequency || {};
    var s = store('s'), l = store('l');
    try {
      if (f.once_per_session && s && s.getItem(ns + w.id) === '1') return false;
      if (f.show_again_days > 0 && l) {
        var ts = parseInt(l.getItem(ns + w.id + '_ts') || '0', 10);
        if (ts && (Date.now() - ts) < f.show_again_days * 864e5) return false;
      }
    } catch (e) {}
    return true;
  }
  function markShown(w) {
    if (preview) return;
    var s = store('s'), l = store('l');
    try {
      if (s) s.setItem(ns + w.id, '1');
      if (l && (w.frequency || {}).show_again_days > 0) l.setItem(ns + w.id + '_ts', String(Date.now()));
    } catch (e) {}
  }

  // ---------- countdown ----------
  function startCountdown(w, root) {
    var cd = w.countdown || {};
    if (cd.mode === 'off' || !cd.mode) return;
    var spans = root.querySelectorAll('[data-kkw-count]');
    if (!spans.length) return;
    var deadline;
    var ft = window.__KK_FLOW_TIMER;
    if (ft && ft.applyWidgets && ft.deadline) {
      // centralized flow timer wins: same deadline as the page promo bar/checkout
      deadline = ft.deadline;
    } else if (cd.mode === 'today') {
      var d = new Date(); d.setHours(23, 59, 59, 999);
      deadline = d.getTime();
    } else {
      var mins = Math.max(1, cd.minutes || 15);
      var key = ns + w.id + '_dl', l = store('l');
      try { deadline = parseInt((l && l.getItem(key)) || '0', 10); } catch (e) { deadline = 0; }
      if (!deadline || deadline < Date.now()) {
        deadline = Date.now() + mins * 60000;
        try { if (l && !preview) l.setItem(key, String(deadline)); } catch (e) {}
      }
    }
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
      var ms = Math.max(0, deadline - Date.now());
      var t = Math.floor(ms / 1000);
      var hh = Math.floor(t / 3600), mm = Math.floor((t % 3600) / 60), ss = t % 60;
      var txt = (hh > 0 ? pad(hh) + ':' : '') + pad(mm) + ':' + pad(ss);
      for (var i = 0; i < spans.length; i++) spans[i].textContent = txt;
      if (ms > 0) setTimeout(tick, 1000);
    }
    tick();
  }

  // ---------- checkout plan selection ----------
  function planName(planId) {
    return (plans[planId] && plans[planId].name) || planId || '';
  }
  function setBilling(slot) {
    if (slot !== 'monthly' && slot !== 'yearly') return;
    var want = slot === 'yearly' ? /(yearly|annual)/i : /month/i;
    var els = document.querySelectorAll('button, [role="tab"], label, .toggle-option, [class*="toggle"] *');
    for (var i = 0; i < els.length; i++) {
      var el = els[i], txt = (el.textContent || '').trim();
      if (txt.length > 0 && txt.length < 30 && want.test(txt)) {
        try { el.click(); } catch (e) {}
        return;
      }
    }
  }
  function findPlanCard(name) {
    if (!name) return null;
    var lower = name.toLowerCase();
    var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_ELEMENT, null);
    var best = null, bestLen = Infinity, node;
    while ((node = walker.nextNode())) {
      if (node.closest && node.closest('[data-kkw-root]')) continue;
      if (node.children.length > 3) continue;
      var txt = (node.textContent || '').trim().toLowerCase();
      if (txt === lower && txt.length < bestLen + 1) {
        best = node; bestLen = txt.length;
      }
    }
    if (!best) return null;
    // climb to a card-like ancestor: sizeable box containing a CTA
    var cur = best, card = best;
    for (var d = 0; d < 8 && cur && cur !== document.body; d++) {
      cur = cur.parentElement;
      if (!cur) break;
      var r = cur.getBoundingClientRect();
      if (r.height > 160 && r.width > 200 && r.width < window.innerWidth * 0.92 &&
          cur.querySelector('a, button')) {
        card = cur;
      }
      if (r.width >= window.innerWidth * 0.92) break;
    }
    return card;
  }
  function selectPlan(planId, slot) {
    setBilling(slot);
    var attempts = 0;
    (function go() {
      attempts++;
      var card = findPlanCard(planName(planId));
      if (card) {
        try { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) { card.scrollIntoView(); }
        card.classList.remove('kkw-plan-flash');
        void card.offsetWidth;
        card.classList.add('kkw-plan-flash');
      } else if (attempts < 10) {
        setTimeout(go, 350); // React picker may still be mounting
      }
    })();
  }
  window.__kkwSelectPlan = selectPlan;

  function offerToken(offer) {
    var p = plans[offer.plan_id];
    return (p && p.tokens && p.tokens[offer.billing_slot]) || null;
  }

  // ---------- widget wiring ----------
  function wireCtas(w, root, hide) {
    var ctas = [];
    if (root.matches && root.matches('[data-kkw-cta]')) ctas.push(root); // root may itself be the CTA (social toast)
    var inner = root.querySelectorAll('[data-kkw-cta]');
    for (var n = 0; n < inner.length; n++) ctas.push(inner[n]);
    for (var i = 0; i < ctas.length; i++) {
      (function (a) {
        var which = a.getAttribute('data-kkw-offer') || 'primary';
        var offer = which === 'secondary' && w.secondary ? w.secondary : w;
        if (page === 'sales') {
          // anchor href is "checkout.html" in demo HTML, or the KK checkout URL
          // (with step1link query) after kk_format rewriting — append plan params.
          var base = a.getAttribute('href') || 'checkout.html';
          if (offer.plan_id) {
            var sep = base.indexOf('?') >= 0 ? '&' : '?';
            a.setAttribute('href', base + sep + 'plan=' + encodeURIComponent(offer.plan_id) +
              '&billing=' + encodeURIComponent(offer.billing_slot || 'monthly'));
          }
          a.addEventListener('click', function () { hide(); });
        } else {
          // checkout page
          var action = w.checkout_action || 'select_plan';
          var tok = offerToken(offer);
          var direct = action === 'direct_payment' && tok &&
            window.__KK_OFFER_LINKS && window.__KK_OFFER_LINKS[tok];
          if (direct) {
            a.setAttribute('href', window.__KK_OFFER_LINKS[tok]);
            a.addEventListener('click', function () { hide(); });
          } else {
            a.addEventListener('click', function (e) {
              e.preventDefault();
              hide();
              if (offer.plan_id) selectPlan(offer.plan_id, offer.billing_slot || 'monthly');
            });
          }
        }
      })(ctas[i]);
    }
  }

  function initPopup(w, root) {
    var opened = false, armedAt = Date.now(), ARM = preview ? 0 : 1200;
    function show() {
      if (opened) return;
      if (!gateOpen(w)) return;
      if (Date.now() - armedAt < ARM) return;
      opened = true;
      markShown(w);
      root.setAttribute('data-open', 'true');
      document.documentElement.style.overflow = 'hidden';
      startCountdown(w, root);
    }
    function hide() {
      root.removeAttribute('data-open');
      document.documentElement.style.overflow = '';
    }
    root.addEventListener('click', function (e) {
      if (e.target === root) hide();
      if (e.target.closest && e.target.closest('[data-kkw-close]')) hide();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && root.getAttribute('data-open') === 'true') hide();
    });
    wireCtas(w, root, hide);

    var tr = w.triggers || {};
    if (tr.exit_intent) {
      document.addEventListener('mouseout', function (e) {
        if (e.relatedTarget || e.toElement) return;
        if (e.clientY <= 0) show();
      });
      // mobile: fast swipe up at top of page + back button
      var lastY = 0;
      document.addEventListener('touchstart', function (e) {
        if (e.touches && e.touches.length) lastY = e.touches[0].clientY;
      }, { passive: true });
      document.addEventListener('touchmove', function (e) {
        if (!e.touches || !e.touches.length) return;
        var y = e.touches[0].clientY;
        if (window.scrollY <= 0 && (y - lastY) > 80) show();
        lastY = y;
      }, { passive: true });
      if (!preview) {
        var pushed = false;
        setTimeout(function () {
          try { history.pushState({ kkw: 1 }, ''); pushed = true; } catch (e) {}
        }, 800);
        window.addEventListener('popstate', function () {
          show();
          if (pushed) { try { history.pushState({ kkw: 1 }, ''); } catch (e) {} }
        });
      }
    }
    if (tr.scroll_pct > 0) {
      var fired = false;
      window.addEventListener('scroll', function () {
        if (fired) return;
        var h = document.documentElement.scrollHeight - window.innerHeight;
        if (h > 0 && (window.scrollY / h) * 100 >= tr.scroll_pct) { fired = true; show(); }
      }, { passive: true });
    }
    if (tr.delay_seconds > 0) setTimeout(show, tr.delay_seconds * 1000);
    if (preview) setTimeout(show, 350);
    return show;
  }

  function initSlider(w, root) {
    var opened = false;
    function show() {
      if (opened) return;
      if (!gateOpen(w)) return;
      opened = true;
      markShown(w);
      root.setAttribute('data-open', 'true');
      startCountdown(w, root);
    }
    function hide() {
      root.removeAttribute('data-open');
      try { store('s') && store('s').setItem(ns + w.id, '1'); } catch (e) {}
    }
    root.addEventListener('click', function (e) {
      if (e.target.closest && e.target.closest('[data-kkw-close]')) hide();
    });
    wireCtas(w, root, hide);

    var tr = w.triggers || {};
    if (tr.scroll_pct > 0) {
      var fired = false;
      var check = function () {
        if (fired) return;
        var h = document.documentElement.scrollHeight - window.innerHeight;
        if (h <= 0 || (window.scrollY / h) * 100 >= tr.scroll_pct) { fired = true; show(); }
      };
      window.addEventListener('scroll', check, { passive: true });
    } else if (tr.delay_seconds > 0) {
      setTimeout(show, tr.delay_seconds * 1000);
    } else {
      setTimeout(show, 600);
    }
    if (tr.scroll_pct > 0 && tr.delay_seconds > 0) setTimeout(show, tr.delay_seconds * 1000);
    if (preview) setTimeout(show, 350);
    return show;
  }

  // ---------- social proof toast ----------
  function initSocial(w, root) {
    var sp = w.social || {};
    var names  = (sp.names  && sp.names.length)  ? sp.names  : ['Maria','Jake','Aisha','Liam','Sofia','Noah','Chen','Priya','Marcus','Elena','Diego','Yuki'];
    var places = (sp.places && sp.places.length) ? sp.places : ['Texas','London','Toronto','Berlin','Sydney','Austin','Madrid','Tokyo','Dubai','Mumbai'];
    var msgs   = (sp.messages && sp.messages.length) ? sp.messages : ['{name} from {place} just upgraded'];
    var times  = ['just now', '1 min ago', '2 min ago', '4 min ago', '7 min ago'];

    var startDelay = (sp.start_delay != null ? sp.start_delay : 5) * 1000;
    var duration   = (sp.duration   != null ? sp.duration   : 5) * 1000;
    var interval   = (sp.interval   != null ? sp.interval   : 8) * 1000;
    if (preview) { startDelay = Math.min(startDelay, 700); interval = Math.min(interval, 4000); }

    var textEl = root.querySelector('.kkw-proof-text');
    var subEl  = root.querySelector('.kkw-proof-sub');
    if (!textEl || !subEl) return function () {};

    function rand(a) { return a[Math.floor(Math.random() * a.length)]; }
    function commas(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
    function fill(t) {
      return String(t)
        .replace(/\{name\}/g,  function () { return '<b>' + rand(names) + '</b>'; })
        .replace(/\{place\}/g, function () { return '<b>' + rand(places) + '</b>'; })
        .replace(/\{count\}/g, function () { return '<b>' + commas(900 + Math.floor(Math.random() * 1500)) + '</b>'; });
    }
    function nextMessage() {
      if (sp.show_count && Math.random() < 0.25) {
        return {
          text: '<b>' + commas(900 + Math.floor(Math.random() * 1500)) + '</b> ' + (sp.count_label || 'creations in the last hour'),
          sub:  sp.count_sub || 'across AIPU'
        };
      }
      return { text: fill(rand(msgs)), sub: rand(times) };
    }
    function render() { var m = nextMessage(); textEl.innerHTML = m.text; subEl.textContent = m.sub; }

    var hideTimer, nextTimer;
    function show() {
      render();
      root.classList.add('kkw-proof-show');
      clearTimeout(hideTimer);
      hideTimer = setTimeout(hide, duration);
    }
    function hide() {
      root.classList.remove('kkw-proof-show');
      clearTimeout(nextTimer);
      nextTimer = setTimeout(show, interval);
    }
    if (sp.link_checkout) wireCtas(w, root, function () {});
    clearTimeout(nextTimer);
    nextTimer = setTimeout(show, startDelay);
    return show;
  }

  var replays = [];
  function boot() {
    for (var i = 0; i < widgets.length; i++) {
      var w = widgets[i];
      var root = document.querySelector('[data-kkw-root="' + w.id + '"]');
      if (!root) continue;
      var mob = isMobile();
      if ((mob && !w.show_mobile) || (!mob && !w.show_desktop)) {
        root.setAttribute('data-kkw-hidden', '1');
        continue;
      }
      var showFn = w.type === 'slider' ? initSlider(w, root)
                 : w.type === 'social' ? initSocial(w, root)
                 : initPopup(w, root);
      replays.push(showFn);
    }
    // consume ?plan= on checkout pages (sales-page widget CTAs + lander links)
    if (page === 'checkout') {
      try {
        var q = new URLSearchParams(window.location.search);
        var pid = (q.get('plan') || '').toLowerCase();
        var slot = (q.get('billing') || 'monthly').toLowerCase();
        if (pid && plans[pid]) {
          setTimeout(function () { selectPlan(pid, slot); }, 600);
        }
      } catch (e) {}
    }
  }
  window.__kkwReplay = function () {
    try { var s = store('s'); if (s) for (var k in s) { if (String(k).indexOf(ns) === 0) s.removeItem(k); } } catch (e) {}
    for (var i = 0; i < replays.length; i++) replays[i]();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
JS;
    }

    /**
     * Render the full widget block (styles + markup + engine + config).
     */
    function widget_render_block(array $widgets, array $ctx): string
    {
        $page    = ($ctx['page'] ?? 'sales') === 'checkout' ? 'checkout' : 'sales';
        $flow    = (string)($ctx['flow'] ?? '');
        $plans   = is_array($ctx['plans'] ?? null) ? $ctx['plans'] : [];
        $preview = !empty($ctx['preview']);

        $markup = '';
        $jsWidgets = [];
        foreach ($widgets as $w) {
            if (!is_array($w) || empty($w['id'])) {
                continue;
            }
            $type = (string)($w['type'] ?? 'popup');
            $markup .= $type === 'slider' ? wr_slider_markup($w, $page)
                : ($type === 'social' ? wr_social_markup($w, $page)
                : wr_popup_markup($w, $page));

            $jsWidgets[] = [
                'id'              => (string)$w['id'],
                'type'            => $type,
                'plan_id'         => $w['plan_id'] ?? null,
                'billing_slot'    => (string)($w['billing_slot'] ?? 'monthly'),
                'checkout_action' => (string)($w['checkout_action'] ?? 'select_plan'),
                'show_desktop'    => !empty($w['show_desktop']),
                'show_mobile'     => !empty($w['show_mobile']),
                'countdown'       => $w['countdown'] ?? ['mode' => 'off', 'minutes' => 15],
                'triggers'        => $w['triggers'] ?? ['exit_intent' => false, 'scroll_pct' => 0, 'delay_seconds' => 0],
                'frequency'       => $w['frequency'] ?? ['once_per_session' => true, 'show_again_days' => 0],
                'secondary'       => is_array($w['secondary'] ?? null) ? [
                    'plan_id'      => $w['secondary']['plan_id'] ?? null,
                    'billing_slot' => (string)($w['secondary']['billing_slot'] ?? 'monthly'),
                ] : null,
                'social'          => $type === 'social' && is_array($w['social'] ?? null) ? $w['social'] : null,
            ];
        }

        // Slim plan map for the engine (name + tokens only).
        $jsPlans = [];
        foreach ($plans as $pid => $p) {
            $jsPlans[$pid] = [
                'name'   => (string)($p['name'] ?? $pid),
                'tokens' => is_array($p['tokens'] ?? null) ? $p['tokens'] : new stdClass(),
            ];
        }

        $config = json_encode([
            'page'    => $page,
            'flow'    => $flow,
            'preview' => $preview,
            'widgets' => $jsWidgets,
            'plans'   => $jsPlans ?: new stdClass(),
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $out  = wr_styles() . "\n";
        $out .= $markup . "\n";
        $out .= "<script>window.__KKW_CONFIG = " . $config . ";</script>\n";
        $out .= "<script>\n" . wr_engine_js() . "\n</script>\n";
        return $out;
    }

    /**
     * Helper-only block for checkout pages that have no widgets of their own
     * but must consume ?plan= links coming from sales-page widgets.
     */
    function widget_render_plan_select_only(array $ctx): string
    {
        $ctx['page'] = 'checkout';
        return widget_render_block([], $ctx);
    }
}
