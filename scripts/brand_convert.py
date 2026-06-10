#!/usr/bin/env python3
"""
brand_convert.py - Duplicate a lander / checkout / flow into the *other* brand
(OmniRogue <-> AIPU), automatically rewriting brand text, asset paths, logos,
legal links and (for checkouts) the shared header/footer chrome.

Usage:
  python3 brand_convert.py --src-dir <abs> --kind lander|checkout|flow \
      [--src-brand aipu|omni] [--dst-brand aipu|omni] \
      [--name <slug>] [--docroot <abs>]

Prints a single JSON line on stdout: { ok, item, brand, web_path, ... }.
"""
from __future__ import annotations

import argparse
import json
import os
import sys
from pathlib import Path

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import brandlib as B  # noqa: E402
import qa_checks  # noqa: E402  (automatic QC gate, merged into the convert result)


def detect_brand_from_path(path: Path, docroot: Path) -> str:
    rel = os.path.relpath(str(path), str(docroot)).replace(os.sep, "/").lower()
    first = rel.strip("/").split("/")[0]
    if first.startswith("omni") or "omnirogue" in first:
        return "omni"
    return "aipu"


def _read(path: Path) -> str:
    return path.read_text(encoding="utf-8", errors="replace")


def _entry_file(d: Path) -> str | None:
    for cand in ("index.html", "index.php"):
        if (d / cand).is_file():
            return cand
    return None


# --------------------------------------------------------------------------- #
def convert_lander(src_dir: Path, src_brand: str, dst_brand: str, name: str,
                   docroot: Path, warnings: list[str]) -> dict:
    dst_collection = docroot / B.COLLECTIONS[(dst_brand, "landers")]
    dst_collection.mkdir(parents=True, exist_ok=True)
    out_dir = B.unique_dir(dst_collection, name)
    B.copy_tree(src_dir, out_dir)

    new_web = B.web_dir_for(out_dir, docroot)

    entry = _entry_file(out_dir)
    if entry is None:
        raise FileNotFoundError("lander has no index.html / index.php")
    page = out_dir / entry
    page.write_text(B.swap_self_dir(_read(page), src_dir, new_web, src_brand, dst_brand, docroot),
                    encoding="utf-8")

    healed = B.heal_missing_logos(out_dir, dst_brand, warnings, self_web=new_web)
    return {
        "kind": "lander",
        "item": out_dir.name,
        "collection": dst_collection.name,
        "web_path": f"{new_web}/{entry}",
        "fs_path": str(out_dir),
        "logos_healed": healed,
        "qc": qa_checks.safe_validate("lander", str(out_dir), str(docroot), dst_brand),
    }


def convert_checkout(src_dir: Path, src_brand: str, dst_brand: str, name: str,
                     docroot: Path, warnings: list[str]) -> dict:
    dst_collection = docroot / B.COLLECTIONS[(dst_brand, "checkouts")]
    dst_collection.mkdir(parents=True, exist_ok=True)
    out_dir = B.unique_dir(dst_collection, name)
    B.copy_tree(src_dir, out_dir)

    new_web = B.web_dir_for(out_dir, docroot)

    page = out_dir / "index.html"
    if not page.is_file():
        raise FileNotFoundError("checkout has no index.html")
    html = B.swap_self_dir(_read(page), src_dir, new_web, src_brand, dst_brand, docroot)
    # Authoritative chrome for the destination brand (overwrites marked regions).
    html = B.inject_chrome(html, dst_brand, docroot, warnings)
    page.write_text(html, encoding="utf-8")

    # Checkout pages reference collection-level ../assets/* — mirror them over.
    B.mirror_collection_assets(src_dir, dst_collection, warnings)
    healed = B.heal_missing_logos(out_dir, dst_brand, warnings, self_web=new_web)
    return {
        "kind": "checkout",
        "item": out_dir.name,
        "collection": dst_collection.name,
        "web_path": f"{new_web}/index.html",
        "fs_path": str(out_dir),
        "logos_healed": healed,
        "qc": qa_checks.safe_validate("checkout", str(out_dir), str(docroot), dst_brand),
    }


def convert_flow(src_dir: Path, src_brand: str, dst_brand: str, name: str,
                 docroot: Path, warnings: list[str]) -> dict:
    """Rebuild a flow under the other brand by converting its source lander +
    checkout, then recombining them with build_flow."""
    import build_flow  # local import; lives in same scripts/ dir

    meta_path = src_dir / "flow.json"
    meta = {}
    if meta_path.is_file():
        try:
            meta = json.loads(_read(meta_path)) or {}
        except Exception:
            meta = {}

    lander_dir = meta.get("lander_dir")
    checkout_dir = meta.get("checkout_dir")
    if not lander_dir or not checkout_dir or not Path(lander_dir).is_dir() or not Path(checkout_dir).is_dir():
        # Fallback: in-place text rebrand of the built pages (best effort).
        flows_dir = docroot / "flows"
        out_dir = B.unique_dir(flows_dir, name)
        B.copy_tree(src_dir, out_dir)
        for fn in ("index.html", "checkout.html"):
            f = out_dir / fn
            if f.is_file():
                f.write_text(B.rebrand_text(_read(f), src_brand, dst_brand), encoding="utf-8")
        B.heal_missing_logos(out_dir, dst_brand, warnings)
        meta["brand"] = dst_brand
        (out_dir / "flow.json").write_text(json.dumps(meta, indent=2), encoding="utf-8")
        new_web = B.web_dir_for(out_dir, docroot)
        warnings.append("flow.json sources unavailable; used in-place text rebrand")
        return {
            "kind": "flow", "item": out_dir.name, "collection": "flows",
            "web_path": f"{new_web}/index.html",
            "checkout_url": f"{new_web}/checkout.html",
            "fs_path": str(out_dir),
        }

    base = B.slugify(meta.get("name") or src_dir.name)
    new_lander = convert_lander(Path(lander_dir), src_brand, dst_brand,
                                f"{base}-{dst_brand}", docroot, warnings)
    new_checkout = convert_checkout(Path(checkout_dir), src_brand, dst_brand,
                                    f"{base}-{dst_brand}", docroot, warnings)

    flows_dir = docroot / "flows"
    flows_dir.mkdir(parents=True, exist_ok=True)
    result = build_flow.build(
        lander_dir=new_lander["fs_path"],
        checkout_dir=new_checkout["fs_path"],
        name=name,
        brand=dst_brand,
        flows_dir=str(flows_dir),
        docroot=str(docroot),
    )
    result["kind"] = "flow"
    result["item"] = result.get("flow")
    result["collection"] = "flows"
    result["web_path"] = result.get("url")
    result["converted_lander"] = new_lander["item"]
    result["converted_checkout"] = new_checkout["item"]
    result["warnings"] = warnings + result.get("warnings", [])
    return result


# --------------------------------------------------------------------------- #
def main() -> int:
    ap = argparse.ArgumentParser(description="Duplicate a page into the other brand.")
    ap.add_argument("--src-dir", required=True)
    ap.add_argument("--kind", required=True, choices=["lander", "checkout", "flow"])
    ap.add_argument("--src-brand", choices=["aipu", "omni"], default=None)
    ap.add_argument("--dst-brand", choices=["aipu", "omni"], default=None)
    ap.add_argument("--name", default=None)
    ap.add_argument("--docroot", default=str(B.DOCROOT))
    args = ap.parse_args()

    docroot = Path(args.docroot).resolve()
    B.DOCROOT = docroot  # keep module helpers in sync
    src_dir = Path(args.src_dir).resolve()
    warnings: list[str] = []

    try:
        if not src_dir.is_dir():
            raise FileNotFoundError(f"source not found: {src_dir}")
        src_brand = args.src_brand
        if src_brand is None and args.kind == "flow":
            meta_path = src_dir / "flow.json"
            if meta_path.is_file():
                try:
                    src_brand = (json.loads(_read(meta_path)) or {}).get("brand")
                except Exception:
                    src_brand = None
        if src_brand not in ("aipu", "omni"):
            src_brand = detect_brand_from_path(src_dir, docroot)
        dst_brand = args.dst_brand or B.other_brand(src_brand)
        if src_brand == dst_brand:
            raise ValueError("source and destination brand are the same")
        name = B.slugify(args.name or src_dir.name)

        if args.kind == "lander":
            result = convert_lander(src_dir, src_brand, dst_brand, name, docroot, warnings)
        elif args.kind == "checkout":
            result = convert_checkout(src_dir, src_brand, dst_brand, name, docroot, warnings)
        else:
            result = convert_flow(src_dir, src_brand, dst_brand, name, docroot, warnings)

        result.setdefault("warnings", warnings)
        result["ok"] = True
        result["brand"] = dst_brand
        print(json.dumps(result))
        return 0
    except Exception as exc:  # surface a clean JSON error to the PHP caller
        print(json.dumps({"ok": False, "error": str(exc), "warnings": warnings}))
        return 1


if __name__ == "__main__":
    sys.exit(main())
