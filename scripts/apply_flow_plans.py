#!/usr/bin/env python3
"""Apply per-flow plan configuration to checkout bundle.js (prices, points, labels).

Reads:
  - data/plan-library.json (defaults)
  - flows/<slug>/flow.json plans_config (enabled plans + overrides)

Patches every plans-pick-your-plan/js/bundle.js under the flow directory.
"""

from __future__ import annotations

import json
import os
import re
from typing import Any


def _plan_library_path(docroot: str) -> str:
    return os.path.join(os.path.dirname(docroot.rstrip("/")), "data", "plan-library.json")


def load_plan_library(docroot: str) -> dict[str, Any]:
    path = _plan_library_path(docroot)
    if not os.path.isfile(path):
        return {"plans": []}
    with open(path, encoding="utf-8") as fh:
        return json.load(fh) or {"plans": []}


def _plan_block_span(text: str, plan_id: str) -> tuple[int, int]:
    marker = f'{{id:"{plan_id}"'
    start = text.index(marker)
    nxt = text.find('},{id:"', start + 1)
    if nxt != -1:
        return start, nxt
    end = text.index("],S=", start)
    return start, end


def _set_field_in_block(block: str, field: str, value: str) -> str:
    pat = rf'{re.escape(field)}:"[^"]*"'
    repl = f'{field}:"{value}"'
    if re.search(pat, block):
        return re.sub(pat, repl, block, count=1)
    return block


def _apply_plan_block(block: str, plan: dict[str, Any]) -> str:
    """Patch one minified plan object inside bundle.js."""
    out = block
    points = (plan.get("points") or "").strip()
    if points:
        out = _set_field_in_block(out, "credits", points)
        if 'mCredits:"' in out:
            out = _set_field_in_block(out, "mCredits", points)

    monthly = plan.get("monthly_price")
    if monthly:
        out = _set_field_in_block(out, "mPrice", str(monthly))

    yearly = plan.get("yearly_price")
    if yearly:
        out = _set_field_in_block(out, "yPrice", str(yearly))

    m_strike = plan.get("monthly_strike")
    if m_strike:
        if 'mStrike:"' in out:
            out = _set_field_in_block(out, "mStrike", str(m_strike))

    y_strike = plan.get("yearly_strike")
    if y_strike:
        if 'yStrike:"' in out:
            out = _set_field_in_block(out, "yStrike", str(y_strike))

    lifetime = plan.get("lifetime_price")
    if lifetime:
        if 'lPrice:"' in out:
            out = _set_field_in_block(out, "lPrice", str(lifetime))

    name = (plan.get("display_name") or "").strip()
    if name:
        out = _set_field_in_block(out, "name", name)

    tag = plan.get("tag")
    if tag is not None and str(tag).strip():
        out = _set_field_in_block(out, "tag", str(tag).strip())

    badge = plan.get("badge")
    if badge:
        if 'badge:"' in out:
            out = _set_field_in_block(out, "badge", str(badge))

    return out


def detect_plan_ids(bundle_text: str) -> list[str]:
    return re.findall(r'\{id:"([a-z0-9-]+)"', bundle_text)


def merge_plan_entry(library_by_id: dict[str, dict], entry: dict[str, Any]) -> dict[str, Any] | None:
    pid = (entry.get("id") or "").strip().lower()
    if not pid or pid not in library_by_id:
        return None
    base = dict(library_by_id[pid])
    for key in (
        "display_name", "tag", "badge", "points",
        "monthly_price", "monthly_strike", "yearly_price", "yearly_strike",
        "lifetime_price", "lifetime_strike", "offer_tokens",
    ):
        if entry.get(key) is not None and entry.get(key) != "":
            if key == "offer_tokens" and isinstance(entry.get(key), dict):
                merged = dict(base.get("offer_tokens") or {})
                for slot in ("monthly", "yearly", "lifetime"):
                    if entry["offer_tokens"].get(slot):
                        merged[slot] = entry["offer_tokens"][slot]
                base["offer_tokens"] = merged
            elif key != "offer_tokens":
                base[key] = entry[key]
    base["enabled"] = bool(entry.get("enabled", True))
    base["id"] = pid
    return base


def resolve_plans_config(
    manifest: dict[str, Any],
    library_doc: dict[str, Any],
    bundle_text: str | None = None,
) -> dict[str, Any]:
    """Build the effective plan list for a flow (library defaults + saved config)."""
    library_plans = library_doc.get("plans") or []
    library_by_id = {p["id"]: p for p in library_plans if p.get("id")}

    saved = manifest.get("plans_config") or {}
    saved_plans = saved.get("plans") if isinstance(saved, dict) else None

    detected: list[str] = []
    if bundle_text:
        detected = detect_plan_ids(bundle_text)

    if saved_plans:
        resolved = []
        for entry in saved_plans:
            if not isinstance(entry, dict):
                continue
            merged = merge_plan_entry(library_by_id, entry)
            if merged:
                resolved.append(merged)
        return {
            "index_register_token": saved.get("index_register_token") or "registercheckout",
            "plans": resolved,
            "detected_plan_ids": detected,
        }

    # No saved config — DO NOT auto-override the source bundle. The source
    # checkout's bundle.js is hand-authored (a "plans-v3-299" bundle may
    # repurpose the `agency` plan slot to show Pro Max $299.99 pricing). If
    # we seed defaults from the library here, _apply_plan_block would
    # clobber `mPrice` back to library values and rewire_checkout_order
    # would force `agencymonthly` into a slot that should fire
    # `promaxmonthly`. Returning an empty plans list keeps the source
    # bundle text intact and lets rewire_checkout_order preserve the
    # source ORDER tokens (its `old_toks[i]` fallback).
    return {
        "index_register_token": "registercheckout",
        "plans": [],
        "detected_plan_ids": detected,
    }


def apply_bundle_plans(bundle_text: str, plans: list[dict[str, Any]]) -> tuple[str, list[str], list[str]]:
    """Return (new_text, applied_ids, warnings)."""
    warnings: list[str] = []
    applied: list[str] = []
    text = bundle_text
    present = set(detect_plan_ids(text))

    for plan in plans:
        if not plan.get("enabled", True):
            continue
        pid = plan.get("id")
        if not pid:
            continue
        if pid not in present:
            warnings.append("bundle.js has no plan block for '%s' — skipped" % pid)
            continue
        try:
            start, end = _plan_block_span(text, pid)
            block = text[start:end]
            new_block = _apply_plan_block(block, plan)
            text = text[:start] + new_block + text[end:]
            applied.append(pid)
        except ValueError as exc:
            warnings.append(str(exc))

    return text, applied, warnings


def find_bundle_files(flow_dir: str) -> list[str]:
    out: list[str] = []
    for root, _dirs, files in os.walk(flow_dir):
        if "/kk/" in root.replace("\\", "/") + "/":
            continue
        for fn in files:
            if fn == "bundle.js" and "plans-pick-your-plan" in root.replace("\\", "/"):
                out.append(os.path.join(root, fn))
    return sorted(out)


def apply_flow_plans(flow_dir: str, docroot: str, manifest: dict[str, Any] | None = None) -> dict[str, Any]:
    flow_dir = os.path.realpath(flow_dir)
    if manifest is None:
        manifest_path = os.path.join(flow_dir, "flow.json")
        manifest = {}
        if os.path.isfile(manifest_path):
            with open(manifest_path, encoding="utf-8") as fh:
                manifest = json.load(fh) or {}

    library = load_plan_library(docroot)
    bundles = find_bundle_files(flow_dir)
    if not bundles:
        return {
            "ok": True,
            "applied": [],
            "bundles": [],
            "warnings": ["No plans-pick-your-plan/js/bundle.js found in flow"],
        }

    sample_text = open(bundles[0], encoding="utf-8", errors="replace").read()
    resolved = resolve_plans_config(manifest, library, sample_text)
    enabled_plans = [p for p in resolved["plans"] if p.get("enabled", True)]

    all_applied: list[str] = []
    all_warnings: list[str] = []
    patched_bundles: list[str] = []

    for bpath in bundles:
        raw = open(bpath, encoding="utf-8", errors="replace").read()
        new_text, applied, warnings = apply_bundle_plans(raw, enabled_plans)
        if new_text != raw:
            with open(bpath, "w", encoding="utf-8") as fh:
                fh.write(new_text)
        all_applied.extend(applied)
        all_warnings.extend(warnings)
        patched_bundles.append(os.path.relpath(bpath, flow_dir))

    return {
        "ok": True,
        "applied": sorted(set(all_applied)),
        "bundles": patched_bundles,
        "index_register_token": resolved.get("index_register_token"),
        "warnings": all_warnings,
    }
