# Baseline screenshots

Captured on locked commit `1cdab114920cdc2431f983a1c1ea9efb88e26f82`, before any V2 visual change, using the repo's own e2e seed/server bootstrap (`tests/e2e/serve.mjs`) and Playwright Chromium (with a local, uncommitted `executablePath` override to work around this sandbox's browser-revision mismatch — see `PAGE_INVENTORY.md` baseline notes).

| File | Route | Viewport | State |
|---|---|---|---|
| `home-desktop-1440x900.png` | `/` | 1440×900 | populated (seeded data) |
| `home-mobile-390x844.png` | `/` | 390×844 | populated |
| `calculator-desktop-1440x900.png` | `/calculator` | 1440×900 | initial form |
| `calculator-mobile-390x844.png` | `/calculator` | 390×844 | initial form |
| `car-prices-index-desktop-1440x900.png` | `/car-prices` | 1440×900 | populated |
| `car-prices-index-mobile-390x844.png` | `/car-prices` | 390×844 | populated |
| `admin-login-desktop-1440x900.png` | `/admin/login` | 1440×900 | form |
| `admin-login-mobile-390x844.png` | `/admin/login` | 390×844 | form |

These establish the pre-V2 visual baseline (current `brand`/`amber`/`ink` Tailwind tokens) for later before/after comparison. A fuller state/viewport/role matrix will be captured in Phase 12 once V2 pages exist.
