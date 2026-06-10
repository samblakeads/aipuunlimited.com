/* AI Editor — chatbox panel logic for /previews/ */
(function () {
  'use strict';

  const ENDPOINT = 'ai/editor.php';
  const overlay  = document.getElementById('ai-overlay');
  const panel    = document.getElementById('ai-panel');
  if (!panel) return;

  const titleEl    = panel.querySelector('[data-ai-title]');
  const scopeEl    = panel.querySelector('[data-ai-scope]');
  const providerEl = panel.querySelector('[data-ai-provider]');
  const modelEl    = panel.querySelector('[data-ai-model]');
  const streamEl   = panel.querySelector('[data-ai-stream]');
  const inputEl    = panel.querySelector('[data-ai-input]');
  const sendBtn    = panel.querySelector('[data-ai-send]');
  const closeBtns  = panel.querySelectorAll('[data-ai-close]');
  const statusEl   = panel.querySelector('[data-ai-status]');
  const previewBtn = panel.querySelector('[data-ai-preview]');
  const revertBtn  = panel.querySelector('[data-ai-revert]');
  const historyBtn = panel.querySelector('[data-ai-history]');

  let cfg = null;
  let scope = null;     // { kind, name, collection, brand, label, webRoot }
  let busy = false;
  const messages = []; // local transcript only — server runs are stateless

  /* ----------------------------------------------------------------- helpers */
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
  function status(text, cls) {
    statusEl.className = 'ai-status' + (cls ? ' ' + cls : '');
    statusEl.innerHTML = (cls === 'busy' ? '<span class="ai-spinner"></span>' : '') + esc(text || '');
  }
  function brandTag(brand) {
    const label = brand === 'aipu' ? 'AIPU' : 'OmniRogue';
    return `<span class="brand-tag ${brand}">${label}</span>`;
  }
  function prettySize(bytes) {
    if (bytes == null) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1024 / 1024).toFixed(2) + ' MB';
  }

  /* ----------------------------------------------------------------- render */
  function render() {
    if (!messages.length) {
      const samples = scope && scope.kind === 'checkout'
        ? ['Make the pricing card look more premium', 'Update the CTA button text to "Lock my plan"',
           'Add urgency under the headline', 'Tighten the headline copy', 'Fix mobile spacing on the plan grid']
        : (scope && scope.kind === 'flow'
          ? ['Rewrite the hero headline to be more bold', 'Add a guarantee section above the footer',
             'Change the CTA accent color to indigo', 'Update both index.html and checkout.html copy to match']
          : ['Rewrite the hero headline to be more bold', 'Update the offer copy to feel more premium',
             'Add a 60-day guarantee badge near the CTA', 'Make the navbar links match the offer']);

      streamEl.innerHTML = `
        <div class="ai-empty">
          <h3>Tell the AI what to change on this page.</h3>
          <p>It will read the files in this ${scope ? esc(scope.kind) : 'page'} and apply the smallest correct edit, then summarise.</p>
          <div class="ai-suggestions">
            ${samples.map(s => `<button type="button" class="ai-suggest">${esc(s)}</button>`).join('')}
          </div>
        </div>`;
      streamEl.querySelectorAll('.ai-suggest').forEach(btn => {
        btn.addEventListener('click', () => { inputEl.value = btn.textContent; inputEl.focus(); });
      });
      return;
    }
    streamEl.innerHTML = messages.map(renderMsg).join('');
    streamEl.scrollTop = streamEl.scrollHeight;
  }

  function renderMsg(m) {
    if (m.role === 'user') {
      return `<div class="ai-msg user"><div class="ai-msg-head">You</div><div class="ai-msg-body">${esc(m.text)}</div></div>`;
    }
    if (m.role === 'system') {
      return `<div class="ai-msg system"><div class="ai-msg-body">${esc(m.text)}</div></div>`;
    }
    if (m.role === 'error') {
      return `<div class="ai-msg error"><div class="ai-msg-head">Failed</div><div class="ai-msg-body">${esc(m.text)}</div></div>`;
    }
    // assistant
    const head = m.status === 'failed' ? 'Failed'
      : m.status === 'needs_confirmation' ? 'Needs confirmation'
      : m.status === 'needs_more_context' ? 'Needs more context'
      : 'Done';
    const cls = m.status === 'failed' ? 'error'
      : (m.status === 'needs_confirmation' || m.status === 'needs_more_context') ? 'system' : 'success';

    let changedHtml = '';
    const changedKeys = m.changed ? Object.keys(m.changed) : [];
    if (changedKeys.length) {
      changedHtml = `<div class="ai-changed">${
        changedKeys.map(p => {
          const c = m.changed[p];
          const delta = c && c.sizeAfter != null
            ? `<span class="delta">${esc(c.action || 'edit')} · ${prettySize(c.sizeBefore)} → ${prettySize(c.sizeAfter)}</span>`
            : '';
          return `<div class="ai-changed-row"><code>${esc(p)}</code>${delta}</div>`;
        }).join('')
      }</div>`;
    } else if (m.status !== 'needs_more_context' && m.status !== 'needs_confirmation') {
      changedHtml = `<div class="ai-changed"><div class="ai-changed-row"><span class="delta">No files changed (read-only run).</span></div></div>`;
    }

    let warnHtml = '';
    if (m.warnings && m.warnings.length) {
      warnHtml = `<div class="ai-warnings">⚠ ${m.warnings.map(esc).join('<br>⚠ ')}</div>`;
    }

    let actions = '';
    if (changedKeys.length && scope) {
      const previewUrl = scope.webRoot + (scope.kind === 'flow' ? '/index.html' : (scope.entryFile ? '/' + scope.entryFile : ''));
      actions = `<div class="ai-actions-row">
        <a class="btn btn-ghost" target="_blank" rel="noopener" href="${esc(previewUrl)}">Open preview ↗</a>
        <button type="button" class="btn btn-ghost" data-ai-revert-inline>Revert this run</button>
      </div>`;
    }

    const meta = `<div class="ai-msg-head">${esc(head)} · ${esc(m.provider || '')} ${esc(m.model || '')} · ${m.turns || 0} turns</div>`;
    return `<div class="ai-msg ${cls}">${meta}<div class="ai-msg-body">${esc(m.summary || '')}</div>${warnHtml}${changedHtml}${actions}</div>`;
  }

  /* -------------------------------------------------------------- providers */
  async function loadConfig() {
    // Always re-fetch (cache-busted) so .env edits — e.g. a new default model —
    // are reflected as soon as the panel is opened, no page reload needed.
    const res = await fetch(ENDPOINT + '?action=config&_=' + Date.now(), { cache: 'no-store' });
    cfg = await res.json();
    return cfg;
  }

  function fillProviderSelect() {
    if (!cfg || !cfg.providers) return;
    providerEl.innerHTML = cfg.providers.map(p => {
      const flag = p.available ? '' : ' (no key)';
      return `<option value="${esc(p.id)}" ${p.available ? '' : 'disabled'}>${esc(p.label)}${flag}</option>`;
    }).join('');
    providerEl.value = cfg.default || (cfg.providers.find(p => p.available) || {}).id || 'anthropic';
    fillModelSelect();
  }
  function fillModelSelect() {
    if (!cfg || !cfg.providers) return;
    const p = cfg.providers.find(x => x.id === providerEl.value);
    if (!p) { modelEl.innerHTML = '<option>—</option>'; return; }
    if (!p.available) {
      modelEl.innerHTML = `<option>Add ${esc(p.label)} key in .env</option>`;
      modelEl.disabled = true; return;
    }
    modelEl.disabled = false;
    modelEl.innerHTML = (p.models.length ? p.models : [p.defaultModel]).map(m =>
      `<option value="${esc(m)}">${esc(m)}</option>`).join('');
    modelEl.value = p.defaultModel;
  }
  providerEl && providerEl.addEventListener('change', fillModelSelect);

  /* ---------------------------------------------------------- panel control */
  function open(scopeIn) {
    scope = scopeIn;
    titleEl.textContent = scope.label || (scope.kind + ': ' + scope.name);
    scopeEl.innerHTML = `<strong>${esc(scope.kind)}</strong> · ${esc(scope.name)} ${brandTag(scope.brand)}<br>${esc(scope.webRoot)}`;
    previewBtn.href = scope.webRoot + (scope.kind === 'flow' ? '/index.html' : '/');
    messages.length = 0;
    render();
    overlay.classList.add('open');
    panel.classList.add('open');
    document.body.style.overflow = 'hidden';
    loadConfig().then(() => {
      fillProviderSelect();
      const p = cfg.providers.find(x => x.id === providerEl.value);
      status(p && p.available
        ? 'Ready · ' + (p.label) + ' / ' + (p.defaultModel)
        : 'Add an API key to /.env to enable this provider', '');
    }).catch(e => status('Config error: ' + e.message, 'err'));
    setTimeout(() => inputEl.focus(), 250);
  }
  function close() {
    overlay.classList.remove('open');
    panel.classList.remove('open');
    document.body.style.overflow = '';
  }
  closeBtns.forEach(b => b.addEventListener('click', close));
  overlay && overlay.addEventListener('click', close);
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && panel.classList.contains('open')) close();
  });

  /* --------------------------------------------------------------- sending */
  async function send() {
    if (busy) return null;
    const instruction = inputEl.value.trim();
    if (!instruction) return null;

    busy = true; sendBtn.disabled = true;
    messages.push({ role: 'user', text: instruction });
    render();
    inputEl.value = '';
    status('Running ' + (providerEl.value) + ' / ' + (modelEl.value) + '…', 'busy');

    let result = null;
    try {
      const res = await fetch(ENDPOINT, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          provider: providerEl.value,
          model: modelEl.value,
          scope: { kind: scope.kind, name: scope.name, collection: scope.collection || '' },
          instruction,
        }),
      });
      const json = await res.json();
      result = json;
      if (!json.ok) {
        messages.push({ role: 'error', text: json.error || 'Unknown error' });
        status('Failed', 'err');
      } else {
        messages.push({
          role: 'assistant',
          status: json.status,
          summary: json.summary,
          changed: json.changed,
          warnings: json.warnings,
          turns: json.turns,
          provider: json.provider,
          model: json.model,
          runId: json.runId,
        });
        const changedCount = json.changed ? Object.keys(json.changed).length : 0;
        status(json.status === 'done'
          ? `Done · ${changedCount} file${changedCount === 1 ? '' : 's'} changed`
          : json.status, json.status === 'done' ? 'ok' : (json.status === 'failed' ? 'err' : ''));
      }
    } catch (err) {
      messages.push({ role: 'error', text: err.message || String(err) });
      status('Request failed', 'err');
      result = { ok: false, error: err.message || String(err) };
    } finally {
      busy = false; sendBtn.disabled = false;
      render();
    }
    return result;
  }

  sendBtn && sendBtn.addEventListener('click', send);
  inputEl && inputEl.addEventListener('keydown', e => {
    if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) { e.preventDefault(); send(); }
  });

  /* ----------------------------------------------------------------- revert */
  async function revertLatest() {
    if (!scope || !confirm('Restore the most recent backup of this page? This overwrites recent edits.')) return;
    status('Reverting…', 'busy');
    try {
      const res = await fetch(ENDPOINT + '?action=revert', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ scope: { kind: scope.kind, name: scope.name, collection: scope.collection || '' } }),
      });
      const json = await res.json();
      if (json.ok) {
        messages.push({ role: 'system', text: 'Reverted ' + (json.restored || []).length + ' file(s) from run ' + (json.runId || '') });
        status('Reverted', 'ok');
      } else {
        messages.push({ role: 'error', text: json.error || 'Revert failed' });
        status('Revert failed', 'err');
      }
    } catch (e) {
      messages.push({ role: 'error', text: e.message }); status('Revert failed', 'err');
    }
    render();
  }
  revertBtn && revertBtn.addEventListener('click', revertLatest);
  panel.addEventListener('click', e => {
    if (e.target.matches('[data-ai-revert-inline]')) revertLatest();
  });

  /* ---------------------------------------------------------------- history */
  async function showHistory() {
    if (!scope) return;
    status('Loading history…', 'busy');
    try {
      const url = ENDPOINT + '?action=history&kind=' + encodeURIComponent(scope.kind)
        + '&name=' + encodeURIComponent(scope.name)
        + '&collection=' + encodeURIComponent(scope.collection || '');
      const res = await fetch(url);
      const json = await res.json();
      if (!json.ok) throw new Error(json.error || 'failed');
      const entries = json.entries || [];
      if (!entries.length) {
        messages.push({ role: 'system', text: 'No previous AI edits for this page.' });
      } else {
        const lines = entries.slice(0, 10).map(e =>
          `[${(e.ts || '').slice(0, 19)}] ${e.status || e.kind} · ${e.provider || ''} ${e.model || ''}\n  ${e.summary || ''}`
        ).join('\n');
        messages.push({ role: 'system', text: 'Recent edits:\n' + lines });
      }
      status('Ready', '');
    } catch (e) {
      status('History error: ' + e.message, 'err');
    }
    render();
  }
  historyBtn && historyBtn.addEventListener('click', showHistory);

  /* ------------------------------------------------------- per-card buttons */
  function scopeFromCard(card) {
    const kind = card.dataset.kind || '';
    const name = card.dataset.name || '';
    const collection = card.dataset.collection || '';
    const brand = card.dataset.brand || 'omni';
    const webRoot = kind === 'flow' ? '/flows/' + name : '/' + collection + '/' + name;
    const label = (kind === 'flow' ? 'Flow' : (kind === 'lander' ? 'Lander' : 'Checkout')) + ' · ' + name;
    return { kind, name, collection, brand, webRoot, label };
  }
  document.querySelectorAll('.btn-ai-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const card = btn.closest('.card');
      if (!card) return;
      open(scopeFromCard(card));
    });
  });

  /* ----------------------------------------------- programmatic entry point */
  /**
   * Open the editor for a scope, prefill an instruction, and (by default) run
   * it — awaiting the run so callers can react (e.g. re-run KK Format on the
   * fixed source). Used by the KK build "Fix with AI" button.
   *
   *   await window.aiEditor.run(scope, instruction, { autoSend: true });
   *
   * Returns the run result object, or {ok:false} if no provider is configured.
   */
  async function runFix(scopeIn, instruction, opts) {
    opts = opts || {};
    open(scopeIn);
    try { await loadConfig(); } catch (e) { /* open() reports config errors */ }
    fillProviderSelect();
    if (typeof instruction === 'string') inputEl.value = instruction;
    const p = cfg && cfg.providers ? cfg.providers.find(x => x.id === providerEl.value) : null;
    if (!p || !p.available) {
      status('Add an API key in /.env to enable AI fixing', 'err');
      inputEl.focus();
      return { ok: false, error: 'no_provider' };
    }
    if (opts.autoSend === false) { inputEl.focus(); return null; }
    return await send();
  }

  window.aiEditor = { open: open, run: runFix };
})();
