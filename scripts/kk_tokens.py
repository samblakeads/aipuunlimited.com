#!/usr/bin/env python3
"""kk_tokens.py - canonical KowboyKit offer-token catalogue (single source of truth).

Loads data/kk-tokens.json (sibling of htdocs, next to plan-library.json) and
exposes:

    load_catalog(docroot)   -> {token: {label, kind, plan, period, price}}
    token_list(docroot)     -> [token, ...]  (stable JSON order)
    plan_price_index(docroot) -> {(period, normalized_price): [token, ...]}
    normalize_price(text)   -> "14.99" / "1499" style canonical price string

Used by kk_format.py, qa_checks.py and apply_flow_plans.py so the token
vocabulary and canonical prices can never drift between scripts. The PHP side
(previews/lib/kk-tokens.php) reads the same JSON file.
"""

from __future__ import annotations

import json
import os
import re

DEFAULT_DOCROOT = "/var/www/aipuunlimited.com/htdocs"

_CACHE: dict[str, dict] = {}


def catalog_path(docroot: str = DEFAULT_DOCROOT) -> str:
    return os.path.join(os.path.dirname(docroot.rstrip("/")), "data", "kk-tokens.json")


def load_catalog(docroot: str = DEFAULT_DOCROOT) -> dict:
    """Return {token: meta}. Raises if the catalogue is missing/invalid:
    a build without the canonical vocabulary must never proceed."""
    path = catalog_path(docroot)
    if path in _CACHE:
        return _CACHE[path]
    with open(path, encoding="utf-8") as fh:
        doc = json.load(fh)
    tokens = doc.get("tokens")
    if not isinstance(tokens, dict) or not tokens:
        raise ValueError("kk-tokens.json has no tokens map: %s" % path)
    _CACHE[path] = tokens
    return tokens


def token_list(docroot: str = DEFAULT_DOCROOT) -> list[str]:
    return list(load_catalog(docroot).keys())


def normalize_price(text) -> str:
    """'$1,499.00' -> '1499', '14.99' -> '14.99', '99' -> '99'."""
    s = str(text or "").strip().lstrip("$").replace(",", "").strip()
    if not s:
        return ""
    if not re.match(r"^[0-9]+(\.[0-9]+)?$", s):
        return ""
    if "." in s:
        s = s.rstrip("0").rstrip(".")
    return s


def plan_price_index(docroot: str = DEFAULT_DOCROOT) -> dict:
    """{(period, price): [token, ...]} for plan-kind tokens only."""
    idx: dict = {}
    for tok, meta in load_catalog(docroot).items():
        if (meta or {}).get("kind") != "plan":
            continue
        key = (meta.get("period"), normalize_price(meta.get("price")))
        idx.setdefault(key, []).append(tok)
    return idx


def canonical_price(token: str, docroot: str = DEFAULT_DOCROOT) -> str:
    meta = load_catalog(docroot).get(token) or {}
    return normalize_price(meta.get("price"))
