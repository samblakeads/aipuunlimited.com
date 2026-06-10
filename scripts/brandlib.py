#!/usr/bin/env python3
"""
brandlib.py - shared brand-conversion engine for the previews tooling.

Provides a deterministic, bidirectional OmniRogue <-> AIPU rebrand plus the
checkout "chrome" (header / footer / popups) injection used by
``brand_convert.py`` (Duplicate to AIPU / Duplicate to OmniRogue) and
``import_page.py`` (Import from URL).

Nothing here talks to the network or to PHP; it only transforms text + files
on disk so it can be unit-tested from the CLI.
"""
from __future__ import annotations

import os
import re
import shutil
from pathlib import Path

DOCROOT = Path(os.environ.get("AIPU_DOCROOT", "/var/www/aipuunlimited.com/htdocs"))

CHROME_BUST = "20260609a"  # bump to force browsers to refetch chrome assets

# Collection folder names, per brand + kind ------------------------------------
COLLECTIONS = {
    ("aipu", "landers"): "aipu-landers",
    ("omni", "landers"): "omnirogue-landers",
    ("aipu", "checkouts"): "aipu-checkouts",
    ("omni", "checkouts"): "omnirogue-checkouts",
}

# Where each brand's checkout chrome lives, and the web base used to reference it
CHROME_DIR = {"aipu": "aipu-checkouts/_chrome", "omni": "omnirogue-checkouts/_chrome"}
CHROME_WEB_BASE = {"aipu": "/aipu-checkouts", "omni": "/checkouts-omni"}

BRAND_LABEL = {"aipu": "AIPU", "omni": "OmniRogue"}


def other_brand(brand: str) -> str:
    return "omni" if brand == "aipu" else "aipu"


# --------------------------------------------------------------------------- #
# Brand token map  (canonical pairs written as omni_value -> aipu_value)
# Ordered most-specific first; applied in this order so substrings never clobber
# a more specific token. The reverse direction reuses the same list.
# --------------------------------------------------------------------------- #
# Each entry: (omni, aipu, is_regex_flag, direction)
#   direction: "both" reversible, "o2a" only omni->aipu, "a2o" only aipu->omni.
# All-caps OMNIROGUE collapses to "AIPU" going forward; the reverse can't know
# which AIPU was styled all-caps, so those pairs are forward-only and the bare
# "AIPU" -> "OmniRogue" rule covers the reverse with natural casing.
_PAIRS: list[tuple[str, str, bool, str]] = [
    # ---- filesystem / web paths (must come before bare brand words) ----
    ("/omnirogue-pages", "/aipu-pages", False, "both"),
    ("/omnirogue-checkouts", "/aipu-checkouts", False, "both"),
    ("/checkouts-omni", "/aipu-checkouts", False, "both"),
    ("/omnirogue-landers", "/aipu-landers", False, "both"),
    ("/omnirogue-newpages", "/aipu-pages", False, "o2a"),
    ("omnirogue-pages", "aipu-pages", False, "both"),
    # ---- logos ----
    ("logo-omnirogue-h.png", "logo-aipu-h.png", False, "both"),
    ("logo-omnirogue-h.webp", "logo-aipu-h.webp", False, "both"),
    ("logo-omnirogue.png", "logo-aipu.png", False, "both"),
    ("logo-omnirogue.webp", "logo-aipu.webp", False, "both"),
    # ---- contact / urls ----
    ("support@omnirogue.com", "support@aiprofessionalsuniversity.com", False, "both"),
    ("1-888-777-4675", "1-(844) 402-4236", False, "both"),
    ("https://omnirogue.com/billing", "https://app.aiprofessionalsuniversity.com/billing", False, "both"),
    ("https://omnirogue.com/register", "https://app.aiprofessionalsuniversity.com/register", False, "both"),
    ("https://omnirogue.com/login", "https://app.aiprofessionalsuniversity.com/login", False, "both"),
    ("https://omnirogue.com/contact", "https://aiprofessionalsuniversity.com/contact", False, "both"),
    ("https://omnirogue.com", "https://app.aiprofessionalsuniversity.com", False, "both"),
    ("omnirogueapp.com", "app.aiprofessionalsuniversity.com", False, "both"),
    # ---- legal entity / company ----
    ("OmniRogue Inc.", "AI Profit University LLC", False, "both"),
    ("OmniRogue Billing", "AIPU Billing", False, "both"),
    # ---- address block (Tampa -> Orlando) ----
    ("400 N Tampa St Ste 1550 #767523", "5036 Dr Phillips Blvd Unit #5224", False, "both"),
    ("Tampa, FL 33602-4719", "Orlando, FL 32819", False, "both"),
    # ---- product names ----
    ("OmniReels", "AIPU Reels", False, "both"),
    # ---- css ids / cosmetic prefixes (note: omni- not omnirogue-) ----
    ("omni-static-fixes", "aipu-static-fixes", False, "both"),
    ("omni-static-footer", "aipu-static-footer", False, "both"),
    ("omni-voice-fixes", "aipu-voice-fixes", False, "both"),
    ("omni-library-", "aipu-library-", False, "both"),
    ("OmniRogue static pages", "AIPU static pages", False, "both"),
    # ---- bare brand words (last) ----
    ("OMNIROGUE AI", "AIPU AI", False, "o2a"),
    ("OMNIROGUE", "AIPU", False, "o2a"),
    ("OmniRogue", "AIPU", False, "both"),
    # bare lowercase omnirogue <-> aipu. The aipu->omni side must not touch
    # "aipuunlimited" (the host domain); handled with a guarded regex below.
    ("omnirogue", "aipu", False, "both"),
    # Catch any odd-cased / mis-spelled leftovers ("OMniRogue", "Omni Rogue").
    # Forward-only: "omnirogue" in any casing is always the brand word.
    (r"(?i)omni\s*rogue", "AIPU", True, "o2a"),
]

# Host domain that must survive in BOTH directions (it is brand-neutral infra).
PROTECT_TOKENS = ["aipuunlimited.com"]


def _pairs_for(src: str, dst: str) -> list[tuple[str, str, bool]]:
    """Return ordered (pattern, replacement, is_regex) for src->dst."""
    out: list[tuple[str, str, bool]] = []
    if src == "omni" and dst == "aipu":
        for (o, a, rgx, direction) in _PAIRS:
            if direction in ("both", "o2a"):
                out.append((o, a, rgx))
    elif src == "aipu" and dst == "omni":
        for (o, a, rgx, direction) in _PAIRS:
            if direction not in ("both", "a2o"):
                continue
            if o == "omnirogue" and a == "aipu" and not rgx:
                out.append((r"aipu(?!unlimited)", "omnirogue", True))  # guarded
            else:
                out.append((a, o, rgx))
    return out


def rebrand_text(text: str, src: str, dst: str) -> str:
    if src == dst:
        return text
    # Shield protected tokens behind placeholders so brand passes can't touch them
    shields: dict[str, str] = {}
    for i, tok in enumerate(PROTECT_TOKENS):
        ph = f"\x00PROTECT{i}\x00"
        if tok in text:
            shields[ph] = tok
            text = text.replace(tok, ph)

    for pattern, repl, is_regex in _pairs_for(src, dst):
        if is_regex:
            text = re.sub(pattern, repl, text)
        else:
            text = text.replace(pattern, repl)

    for ph, tok in shields.items():
        text = text.replace(ph, tok)
    return text


# --------------------------------------------------------------------------- #
# self-referential directory rewriting
# --------------------------------------------------------------------------- #
SELF_PLACEHOLDER = "\x00SELFDIR\x00"


def self_web_aliases(src_dir: Path, docroot: Path = DOCROOT) -> list[str]:
    """Every web path that can address `src_dir`, including via top-level
    symlinks (e.g. /omnirogue -> omnirogue-landers, /checkouts-omni ->
    omnirogue-checkouts). Longest first so prefixes never clobber each other."""
    src_real = src_dir.resolve()
    collection = src_real.parent
    item = src_real.name
    webs: set[str] = set()
    try:
        webs.add("/" + os.path.relpath(str(src_real), str(docroot)).replace(os.sep, "/").strip("/"))
    except Exception:
        pass
    try:
        for entry in docroot.iterdir():
            if not entry.is_symlink():
                continue
            try:
                target = entry.resolve()
            except Exception:
                continue
            if target == collection.resolve():
                webs.add("/" + entry.name + "/" + item)
            elif target == src_real:
                webs.add("/" + entry.name)
    except Exception:
        pass
    return sorted((w for w in webs if w and w != "/"), key=len, reverse=True)


def swap_self_dir(text: str, src_dir: Path, new_web_dir: str, src: str, dst: str,
                  docroot: Path = DOCROOT) -> str:
    """Rewrite the page's own paths (e.g. /aipu-landers/foo, /omnirogue/foo)
    without letting the brand pass corrupt the literal folder name. Returns
    rebranded text with self references pointed at new_web_dir."""
    new_web_dir = "/" + new_web_dir.strip("/")
    for alias in self_web_aliases(src_dir, docroot):
        text = text.replace(alias, SELF_PLACEHOLDER)
    text = rebrand_text(text, src, dst)
    text = text.replace(SELF_PLACEHOLDER, new_web_dir)
    return text


# --------------------------------------------------------------------------- #
# filesystem helpers
# --------------------------------------------------------------------------- #

def slugify(value: str) -> str:
    value = (value or "").strip().lower()
    value = re.sub(r"[^a-z0-9._-]+", "-", value)
    value = re.sub(r"-{2,}", "-", value).strip("-_.")
    return value or "page"


def web_dir_for(abs_dir: Path, docroot: Path = DOCROOT) -> str:
    rel = os.path.relpath(str(abs_dir), str(docroot))
    return "/" + rel.replace(os.sep, "/").strip("/")


def unique_dir(parent: Path, name: str) -> Path:
    """Return parent/name, appending -2, -3 ... if it already exists."""
    candidate = parent / name
    if not candidate.exists():
        return candidate
    i = 2
    while True:
        candidate = parent / f"{name}-{i}"
        if not candidate.exists():
            return candidate
        i += 1


def copy_tree(src: Path, dst: Path) -> None:
    """Copy a folder, skipping chrome bundles / dotfiles that are per-collection."""
    def _ignore(_dir, names):
        return [n for n in names if n in ("_chrome",) or n == ".DS_Store"]
    shutil.copytree(src, dst, ignore=_ignore)


def mirror_collection_assets(src_item_dir: Path, dst_collection_dir: Path,
                             warnings: list[str] | None = None) -> int:
    """Checkout pages reference collection-level shared assets via ``../assets/..``.
    Copy any files from the source collection's assets/ into the destination
    collection's assets/ (only when missing) so those refs don't 404."""
    warnings = warnings if warnings is not None else []
    src_assets = src_item_dir.parent / "assets"
    if not src_assets.is_dir():
        return 0
    dst_assets = dst_collection_dir / "assets"
    copied = 0
    for entry in src_assets.iterdir():
        target = dst_assets / entry.name
        if target.exists():
            continue
        dst_assets.mkdir(parents=True, exist_ok=True)
        try:
            if entry.is_dir():
                shutil.copytree(entry, target)
            else:
                shutil.copyfile(entry, target)
            copied += 1
        except Exception as exc:  # pragma: no cover
            warnings.append(f"asset mirror failed for {entry.name}: {exc}")
    return copied


# --------------------------------------------------------------------------- #
# canonical brand logos (used to heal missing references after conversion)
# --------------------------------------------------------------------------- #
def _canonical_logo(brand: str, filename: str) -> Path | None:
    """Find a real file on disk to satisfy a referenced brand logo."""
    ext = os.path.splitext(filename)[1].lower()
    candidates: list[Path] = []
    if brand == "omni":
        base = DOCROOT / "omnirogue-checkouts"
        candidates += [base / "logo-omnirogue.png", base / "logo-omnirogue.webp"]
        # landers ship -h / -mark variants
        for lander in (DOCROOT / "omnirogue-landers").glob("*/assets/img/logo-omnirogue*"):
            candidates.append(lander)
        for lander in (DOCROOT / "omnirogue-landers").glob("*/assets/logo-omnirogue*"):
            candidates.append(lander)
    else:
        for p in (DOCROOT / "omnirogue-checkouts").glob("*/logo-aipu.png"):
            candidates.append(p)
        for lander in (DOCROOT / "aipu-landers").glob("*/assets/logo-aipu*"):
            candidates.append(lander)
        for lander in (DOCROOT / "aipu-landers").glob("*/assets/img/logo-aipu*"):
            candidates.append(lander)
        for lander in (DOCROOT / "omnirogue-landers").glob("*/assets/logo-aipu*"):
            candidates.append(lander)
        for lander in (DOCROOT / "omnirogue-landers").glob("*/assets/img/logo-aipu*"):
            candidates.append(lander)
    # exact filename match first, then same extension
    for c in candidates:
        if c.name == filename and c.is_file():
            return c
    for c in candidates:
        if c.suffix.lower() == ext and c.is_file():
            return c
    return None


def heal_missing_logos(out_dir: Path, brand: str, warnings: list[str],
                       self_web: str | None = None) -> int:
    """Find brand-logo references that resolve inside out_dir but are missing on
    disk, and copy in a canonical brand logo so they don't 404. `self_web` is the
    item's own web dir (e.g. /aipu-landers/foo) used to map absolute refs to disk."""
    healed = 0
    self_web = ("/" + self_web.strip("/")) if self_web else None
    seen: set[Path] = set()
    for ext in ("html", "php", "css", "js"):
        for f in out_dir.rglob(f"*.{ext}"):
            try:
                text = f.read_text(encoding="utf-8", errors="replace")
            except Exception:
                continue
            for m in re.findall(r"[\"'(]([^\"')\s]*logo-[^\"')\s]+\.(?:png|webp|svg|jpg|jpeg))", text):
                rel = m.split("?")[0].split("#")[0]
                # Drop a leading scheme://host so on-host absolute URLs (e.g. og:image)
                # are treated like absolute web paths.
                host_m = re.match(r"^https?://[^/]+(/.*)$", rel)
                if host_m:
                    rel = host_m.group(1)
                # Skip anything that still looks like a URL (scheme present but the
                # host didn't parse -- e.g. a PHP-templated og:image whose
                # <?= HTTP_HOST ?> was stripped to an empty host, leaving
                # "https:///path"). Treating those as relative paths would mkdir a
                # bogus "https:" folder inside the output.
                if "://" in rel or rel.lower().startswith(("http:", "https:")):
                    continue
                target: Path | None = None
                if rel.startswith("/"):
                    if self_web and (rel == self_web or rel.startswith(self_web + "/")):
                        target = out_dir / rel[len(self_web):].lstrip("/")
                    else:
                        continue  # ref into another collection/chrome; not ours to heal
                else:
                    target = (f.parent / rel).resolve()
                    try:
                        target.relative_to(out_dir.resolve())
                    except ValueError:
                        continue
                if target is None or target in seen:
                    continue
                seen.add(target)
                if target.exists():
                    continue
                name = os.path.basename(rel)
                src_logo = _canonical_logo(brand, name)
                target.parent.mkdir(parents=True, exist_ok=True)
                if src_logo is not None:
                    try:
                        shutil.copyfile(src_logo, target)
                        healed += 1
                        continue
                    except Exception as exc:  # pragma: no cover
                        warnings.append(f"logo heal failed for {target}: {exc}")
                        continue
                # No same-extension canonical. For .webp, transcode a PNG canonical.
                if target.suffix.lower() == ".webp":
                    png_src = _canonical_logo(brand, os.path.splitext(name)[0] + ".png") \
                        or _canonical_logo(brand, "logo-omnirogue.png" if brand == "omni" else "logo-aipu.png")
                    if png_src is not None and _make_webp(png_src, target):
                        healed += 1
                        continue
                warnings.append(f"no canonical {brand} logo for {name}")
    return healed


def _make_webp(png_path: Path, webp_path: Path) -> bool:
    """Transcode a PNG into a WEBP using the cwebp CLI, if available."""
    if shutil.which("cwebp") is None:
        return False
    try:
        import subprocess
        r = subprocess.run(
            ["cwebp", "-quiet", str(png_path), "-o", str(webp_path)],
            capture_output=True, timeout=30,
        )
        return r.returncode == 0 and webp_path.is_file()
    except Exception:
        return False


# --------------------------------------------------------------------------- #
# checkout chrome
# --------------------------------------------------------------------------- #
HEAD_RE = re.compile(r"<!-- chrome:head -->.*?<!-- /chrome:head -->", re.S)
HEADER_RE = re.compile(r"<!-- chrome:header -->.*?<!-- /chrome:header -->", re.S)
FOOTER_RE = re.compile(r"<!-- chrome:footer -->.*?<!-- /chrome:footer -->", re.S)


def ensure_chrome_bundle(brand: str, docroot: Path = DOCROOT, warnings: list[str] | None = None) -> Path:
    """Return the chrome dir for `brand`, generating the AIPU bundle from the
    OmniRogue one (rebranded) the first time it is needed."""
    warnings = warnings if warnings is not None else []
    chrome_dir = docroot / CHROME_DIR[brand]
    if (chrome_dir / "header.html").is_file():
        return chrome_dir
    if brand != "aipu":
        raise FileNotFoundError(f"chrome bundle missing for {brand}: {chrome_dir}")

    # Generate AIPU chrome by rebranding the OmniRogue chrome bundle.
    omni_dir = docroot / CHROME_DIR["omni"]
    if not (omni_dir / "header.html").is_file():
        raise FileNotFoundError(f"cannot seed AIPU chrome; omni chrome missing at {omni_dir}")
    chrome_dir.mkdir(parents=True, exist_ok=True)
    for f in omni_dir.iterdir():
        if not f.is_file():
            continue
        if f.suffix.lower() in (".html", ".css", ".js"):
            chrome_dir.joinpath(f.name).write_text(
                rebrand_text(f.read_text(encoding="utf-8", errors="replace"), "omni", "aipu"),
                encoding="utf-8",
            )
        else:
            shutil.copyfile(f, chrome_dir / f.name)
    # AIPU chrome header references /aipu-checkouts/logo-aipu.png
    logo_dst = docroot / "aipu-checkouts" / "logo-aipu.png"
    if not logo_dst.is_file():
        src_logo = _canonical_logo("aipu", "logo-aipu.png")
        if src_logo is not None:
            logo_dst.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(src_logo, logo_dst)
        else:
            warnings.append("AIPU chrome generated but no canonical logo-aipu.png found")
    return chrome_dir


def chrome_blocks(brand: str, docroot: Path = DOCROOT, warnings: list[str] | None = None) -> tuple[str, str, str]:
    """Return (head_block, header_block, footer_block) for a brand's chrome."""
    chrome_dir = ensure_chrome_bundle(brand, docroot, warnings)
    web_base = CHROME_WEB_BASE[brand]
    header_html = (chrome_dir / "header.html").read_text(encoding="utf-8")
    footer_html = (chrome_dir / "footer.html").read_text(encoding="utf-8")
    popups_html = (chrome_dir / "popups.html").read_text(encoding="utf-8")
    head_block = (
        "<!-- chrome:head -->\n"
        f'<link rel="stylesheet" href="{web_base}/_chrome/chrome.css?v={CHROME_BUST}">\n'
        "<!-- /chrome:head -->"
    )
    header_block = "<!-- chrome:header -->\n" + header_html + "\n<!-- /chrome:header -->"
    footer_block = (
        "<!-- chrome:footer -->\n"
        + footer_html + "\n" + popups_html + "\n"
        + f'<script src="{web_base}/_chrome/chrome.js?v={CHROME_BUST}" defer></script>\n'
        + "<!-- /chrome:footer -->"
    )
    return head_block, header_block, footer_block


def inject_chrome(html: str, brand: str, docroot: Path = DOCROOT, warnings: list[str] | None = None) -> str:
    """Replace (or insert) the chrome head/header/footer blocks for `brand`."""
    head_block, header_block, footer_block = chrome_blocks(brand, docroot, warnings)

    if HEAD_RE.search(html):
        html = HEAD_RE.sub(lambda _m: head_block, html, count=1)
    elif "</head>" in html:
        html = html.replace("</head>", head_block + "\n</head>", 1)

    if HEADER_RE.search(html):
        html = HEADER_RE.sub(lambda _m: header_block, html, count=1)
    else:
        m = re.search(r"(<body[^>]*>)", html, re.I)
        if m:
            html = html[: m.end()] + "\n" + header_block + "\n" + html[m.end():]

    if FOOTER_RE.search(html):
        html = FOOTER_RE.sub(lambda _m: footer_block, html, count=1)
    elif "</body>" in html:
        html = html.replace("</body>", footer_block + "\n</body>", 1)
    else:
        html = html + "\n" + footer_block
    return html


def has_chrome_markers(html: str) -> bool:
    return bool(HEADER_RE.search(html) or FOOTER_RE.search(html))
