#!/usr/bin/env python3
"""One-shot: correct the Creator yearly price 179 -> 99 (canonical creatoryearly=$99)
across every active checkout source. Layout-specific, orthogonal rules so the
pricing-v1 strikethrough ('$179/yr' as the crossed-out 'was' price) is never touched.
Run with --apply to write; default is a dry-run report."""
import os, re, glob, sys

DOCROOT = "/var/www/aipuunlimited.com/htdocs"
SCOPE = ['checkout-pages','omnirogue-checkouts','omnirogue-checkouts-kk',
         'aipu-multistep','aipu-multistep-kk','multistep','multistep-kk','kk-june8']
APPLY = '--apply' in sys.argv

def inscope(p):
    if '/.archive/' in p or p.startswith('.archive/'): return False
    if '.bak' in os.path.basename(p): return False
    return any(p.startswith(s + '/') for s in SCOPE)

# (label, compiled regex, replacement)  -- each keyed to one layout, mutually exclusive
RULES = [
    ("bundle.yPrice",  re.compile(r'yPrice:"179"'),                 'yPrice:"99"'),
    ("pcol.amt",       re.compile(r'(pcol-amt">)\$179'),            r'\g<1>$99'),
    ("pcol.renew",     re.compile(r'renews at \$179/yr'),           'renews at $99/yr'),
    ("obj.price",      re.compile(r"(price:\s*')\$179'"),           r"\g<1>$99'"),
    ("obj.cta",        re.compile(r"(cta:\s*'[^']*?)\$179/yr'"),    r"\g<1>$99/yr'"),
]

os.chdir(DOCROOT)
files = []
for pat in ['**/checkout.html','**/checkout.php','**/index.html','**/index.php',
            '**/plans-pick-your-plan*/js/bundle.js']:
    files += glob.glob(pat, recursive=True)
files = sorted(set(f for f in files if inscope(f)))

changed, untouched_with_179 = [], []
for f in files:
    t = open(f, encoding='utf-8', errors='replace').read()
    if '179' not in t:
        continue
    new = t
    fired = {}
    for label, rx, rep in RULES:
        new2, n = rx.subn(rep, new)
        if n:
            fired[label] = n
            new = new2
    if fired:
        # what 179 survives? (should be only legitimate strike/colors/urls)
        remaining = [m for m in re.finditer(r'\$179', new)]
        changed.append((f, fired, len(remaining)))
        if APPLY:
            open(f, 'w', encoding='utf-8').write(new)
    else:
        # had 179 but nothing fired -> confirm it's a strike or non-price
        ctx = re.findall(r".{0,12}\$179.{0,8}", t)
        untouched_with_179.append((f, ctx))

print(f"{'APPLIED' if APPLY else 'DRY-RUN'} — {len(changed)} files changed\n")
for f, fired, rem in changed:
    print(f"  CHANGED {fired}  remaining$179={rem}  {f}")
print(f"\n{len(untouched_with_179)} files had '$179' but NO rule fired (must be strike / non-price):")
for f, ctx in untouched_with_179:
    print(f"  SKIP  {ctx}  {f}")
