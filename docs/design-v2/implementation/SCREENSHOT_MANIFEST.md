# NavraCar V2 — Screenshot Manifest

Full-resolution screenshots and any not committed here are reproducible from the scripts referenced per section; this file lists what's committed to the repo plus checksums.

## Baseline (locked commit `1cdab114920cdc2431f983a1c1ea9efb88e26f82`, before any V2 change)

Directory: `docs/design-v2/implementation/screenshots/baseline/`

| File | Route | Viewport | Role | State | SHA-256 |
|---|---|---|---|---|---|
| `home-desktop-1440x900.png` | `/` | 1440×900 | public | populated | `6542992d7c72ac4820e7b006eebed28da8003ba9a01886b8f2f3df506b958c7a` |
| `home-mobile-390x844.png` | `/` | 390×844 | public | populated | `9f7967328e116c9e67357eba1ec6090c79a002b532ad81a10b4aacb4c7907df7` |
| `calculator-desktop-1440x900.png` | `/calculator` | 1440×900 | public | initial form | `c511f59f4c18fcbed0856ac405bd856ce02928efb342ffb1a1e0fe12383c1b1c` |
| `calculator-mobile-390x844.png` | `/calculator` | 390×844 | public | initial form | `72bdd907e407dcaa978b3ec6fafdd70b1d5f2dc82d368d65040b85388e6b9b79` |
| `car-prices-index-desktop-1440x900.png` | `/car-prices` | 1440×900 | public | populated | `e307bd480d7a63b05607299b5f7efc58b04fa59012559683509b853ca9f2ed5f` |
| `car-prices-index-mobile-390x844.png` | `/car-prices` | 390×844 | public | populated | `5a2e8b986f02d4c732df68ba3893f508d783c4c7980409ee808c13fdf3ff675e` |
| `admin-login-desktop-1440x900.png` | `/admin/login` | 1440×900 | guest | form | `5d53e2a9db73208f468f51f897967076fb22389d391e93d65140c86344377c01` |
| `admin-login-mobile-390x844.png` | `/admin/login` | 390×844 | guest | form | `a84c4f24d3ed7af120305dd797c1d54efd56e6c0a9b0ddbadc5787468e47a856` |

## Phase 3 remediation (visual-parity re-review, post-rejection)

Directory: `docs/design-v2/implementation/screenshots/phase3-remediation/`

Captured at the mission's mandatory desktop (1440×900) and mobile (390×844) viewports. The reference PNGs in `docs/design-v2/assets/` are fixed `1672×941` design-tool composite exports (multiple device panels tiled on one canvas), not raw single-viewport screenshots, so these are matched by structure/color/typography against each reference panel rather than by literal canvas dimensions — see `QA_REPORT.md` for that note in full.

| File | Route | Viewport | Role | State | SHA-256 |
|---|---|---|---|---|---|
| `public-desktop-1440x900.png` | `/` | 1440×900 | public | populated (1 seeded listing) | `a3d0e3aaec204160e2bcf73044689d1b69b5c1166b9f12f300917d019f748d36` |
| `public-car-prices-desktop-1440x900.png` | `/car-prices` | 1440×900 | public | populated | `348f1103b7c1f0e1368607c60283816f48554fa9a48cf647c6d9bdfa73c6a146` |
| `public-mobile-390x844.png` | `/` | 390×844 | public | populated | `2a28a9cb8f33647d2a815126c0f92b22822065522bc2110e232917e1ac3f7d18` |
| `admin-desktop-1440x900.png` | `/admin` (dashboard) | 1440×900 | admin | populated (KPIs at 0 — fresh seed) | `86f638f42d815cc3a49d422b94d8875420375288bbc4958bdcb32e9a147afc7f` |
| `admin-mobile-390x844.png` | `/admin` (dashboard) | 390×844 | admin | populated | `d16a932ec4eff14976582981807bf3ff1eed6fd624ca35f1026866efd5d969eb` |

### Side-by-side comparisons vs. approved reference

Directory: `docs/design-v2/implementation/screenshots/phase3-remediation/side-by-side/`

| File | Reference | Implementation side | SHA-256 |
|---|---|---|---|
| `public-desktop-compare.png` | `01-public-desktop-system.png` | public desktop (home) | `a1e63a971fb02cf2f0e3f270f33067d1f137ba2dd5e28fb0b32de761da9dfa97` |
| `public-car-prices-compare.png` | `01-public-desktop-system.png` | public desktop (car-prices/vehicle list) | `0226e204c90babb58bd9b2d7b7c99f1c81b47a1c24497946686af8a988a8a4fd` |
| `admin-desktop-compare.png` | `02-admin-dashboard-calendar.png` | admin desktop (dashboard) | `358f090951085d1ff51eb653ec178c869d6106beb9d34a26dc6f0fe66c1919ff` |
| `public-mobile-compare.png` | `05-public-mobile.png` | public mobile (home) | `59a9ec78f4273279a45f6d580c3f47600623940b831d7731cb572364eaf7cc78` |
| `admin-mobile-compare.png` | `06-admin-mobile.png` | admin mobile (dashboard) | `ec746518f6f9e932e1390b81ea0391c333ece907d4c6b92afd2eef2520acb56c` |

**Note on the `public-desktop-compare.png` / `public-desktop-1440x900.png` pair:** the reference composite `01-public-desktop-system.png` depicts a vehicle-detail page (left panel) and a vehicle-list page (right panel) — it does not depict a homepage at all. There is no dedicated home-page composition in the approved reference set. The home-page screenshot is therefore a consistent application of the V2 visual language (color/typography/card style) rather than a match against a specific reference layout, and `public-car-prices-compare.png` is the structurally comparable pair (vehicle list vs. the reference's right panel). This is stated explicitly rather than silently claiming a layout match that isn't there.

## Capture method

Both sets used the repo's own e2e seed/server bootstrap (`tests/e2e/serve.mjs`, `php artisan migrate:fresh --seed` + `E2eSeeder`) and Playwright Chromium, with a local, **uncommitted** `launchOptions.executablePath` override in `playwright.config.js` to work around this sandbox's pre-installed-browser-revision mismatch (Playwright expects `chromium_headless_shell-1234`, the sandbox ships `1194`) — reverted after each capture session, never part of any commit. See `PAGE_INVENTORY.md`'s baseline section for the original diagnosis.
