# KK Format Archive

This archive has been split into the **kk-master** folder by page type.

## Documents

| Doc | Page type | PHP header |
|-----|-----------|------------|
| [kk-master/single-step.md](kk-master/single-step.md) | Cloaked presell | `money.php` |
| [kk-master/multi-step.md](kk-master/multi-step.md) | Cloaked funnel (presell + checkout) | `money.php` |
| [kk-master/customer-facing.md](kk-master/customer-facing.md) | Brand / legal / Create Studio | `safe.php` |

**Rule:** Pages that load `money.php` are cloaked conversion pages. Pages that load `safe.php` are customer-facing and must not expose KK offer wiring.

See [kk-master/README.md](kk-master/README.md) for the index.
