# Navracar completion status

Last updated: 2026-08-17

## Current release state

**STAGING REJECTED — REMEDIATION READY FOR MERGE APPROVAL**

- PR #27 was merged to `main` as `0a73ff0e29093ab47b863d7427bdc7c7c4788b1c` after Mostafa's explicit approval.
- Candidate `rc-v1.3.0-1` was built successfully and published to `cpanel-staging` as `ebd36599e41af80ad7e1c3fb250a2a28bc37a0e3` (artifact `9279049851`).
- The candidate was deployed to the isolated cPanel Staging environment. Production was not promoted or deployed.
- Owner acceptance found reproducible HTTP 500 failures. The Staging log proves the imported database had not applied the candidate migrations (`quote_requests.deleted_at`, `car_listings.customs_price_aed`, and `import_queue` were missing). PDF generation also failed because persistent `storage/fonts` was absent.
- Remediation tested SHA: `21afee63d5bc195db0acb2587fb4b3b9b24bc3d9` on `agent/staging-runtime-migrations` in [PR #28](https://github.com/nezamparvar/navracar/pull/28).
- Protected [CI run #115](https://github.com/nezamparvar/navracar/actions/runs/32005784098) completed successfully: Dependencies, Backend tests, Frontend build, Browser QA, Browser extension, and Android build all passed.
- The remediation makes Staging **Deploy HEAD Commit** locate cPanel PHP 8.3+, apply outstanding migrations to the isolated Staging database, create the PDF font runtime, and rebuild Laravel caches without SSH or Terminal. Production deployment logic is unchanged.
- Staging acceptance remains rejected until the remediation is merged, rebuilt as a new immutable candidate, deployed, and retested.

## Staging incident evidence — 2026-08-17

| Check | Result |
|---|---|
| WCDN / admin browsing | FAIL — intermittent upstream HTTP 500 observed by owner. |
| Laravel database schema | FAIL — SQLSTATE 42S22/42S02 for missing `deleted_at`, `customs_price_aed`, and `import_queue`. |
| Persian/English PDF | FAIL — Dompdf could not create/read metrics under missing `storage/fonts`. |
| cPanel web-server errors | No matching application fault; visible `wp-login.php`/`xmlrpc.php` entries were unrelated bot scans. |
| cPanel Resource Usage | Unavailable on this hosting plan; host directs the owner to support. |
| Local remediation validation | PASS — `bash -n`, `git diff --check`, PHP resolver simulation, and runtime-directory creation including `storage/fonts`. |
| Protected remediation CI | PASS — all six required jobs, including PHP tests and migration lifecycle, succeeded on `21afee63d5bc195db0acb2587fb4b3b9b24bc3d9`. |

The original source candidate passed its automated gates, but live Staging
acceptance correctly found deployment-runtime defects that CI did not model.
The remediation must pass a new protected CI run before merge.

## Completed implementation

- Customs declared value defaults to a Settings-controlled percentage below real vehicle value. Decimal percentages and 0% are supported, and an explicitly entered zero remains zero.
- Every web, admin, and mobile pricing entry point uses `VehiclePricingService`; no pricing percentage was duplicated in a page or controller.
- Persian and English proforma labels, currencies, category names, calculation basis, single-item/full variants, layout, and PDF failure diagnostics were corrected.
- CRM authorization, archive lifecycle, and soft-deleted restore/force-delete route binding are policy scoped.
- Persian slugs, mobile pricing API/settings, public calculator wizard, reset/data preservation, accessibility, and responsive layouts were repaired and regression tested.
- Capacitor Android source is buildable and obtains server-authoritative pricing instead of embedding business formulas.
- Navra Capture supports hashed single-use pairing, hashed bearer tokens, bounded validation, marketplace/image host allowlists, sensitive-diagnostic rejection, duplicate detection, review/edit/cancel, and draft-only publication.
- Separate staging/production extension bundles are produced with environment verification and SHA-256 checksums.
- CI, branch protection, cPanel staging/production controls, release promotion, Android, extension installation, and test documentation are present.

## Phase tracker

| Phase | Status | Evidence |
|---|---|---|
| 0. Reconcile PR and candidate | Complete | PR #27 merged with owner approval; immutable `rc-v1.3.0-1` identity recorded. |
| 1. Pricing and customs value | Complete | Central service and Settings-backed discount covered by PHP and Browser QA. |
| 2. CRM/auth/lifecycle/archive | Complete | Policy and soft-delete regression tests pass. |
| 3. Public UX/calculator/catalog/responsive | Complete | Browser QA: 79 passed, 11 intentionally project-scoped skips, 0 failures. |
| 4. PDF acceptance | Complete | Four FA/EN full/single A4 PDFs generated; one-page invariant tested and final artifact visually inspected. |
| 5. Mobile/Capacitor/Android | Complete | `assembleDebug` succeeds and APK artifact is retained. |
| 6. Browser capture/marketplaces | Complete | Backend flow tests and 110/110 extension tests pass; three sanitized marketplace fixtures parse. |
| 7. Database/migrations | Complete | Explicit SQLite `migrate:fresh -> rollback --step=1 -> migrate` lifecycle succeeds. |
| 8. Security/privacy | Complete | Composer/npm audits and Gitleaks pass; no leak found. |
| 9. Full automated gate | Complete | Dependencies, Backend tests, Frontend build, Browser QA, Browser extension, and Android build all pass on the exact SHA. |
| 10. Docs/release preparation | Complete | Runbooks, protected checks, artifact links, SHA, and promotion guardrails recorded. |
| 11. Staging/Production gates | Staging rejected; remediation CI passed | `rc-v1.3.0-1` exposed stale-schema and PDF-runtime defects. PR #28 is ready for owner merge approval; Production remains unchanged. |

## Validation log — CI run #112

| Command / check | Result | Evidence |
|---|---|---|
| `composer validate --strict` | PASS | `composer.json is valid`. |
| `composer audit --locked` | PASS | No security vulnerability advisories found. |
| Root `npm ci` / `npm audit --audit-level=high` | PASS | Locked install; 0 vulnerabilities. |
| PHP syntax scan | PASS | `app`, `routes`, `database`, and `tests` scanned with `php -l`. |
| Database fresh/rollback/upgrade | PASS | All three migration lifecycle commands completed. |
| `php artisan test --compact` | PASS | 137 tests, 726 assertions. |
| Frontend Vite build | PASS | Production build completed. |
| Browser QA | PASS | 79 passed, 11 project-scoped skips, 0 failures. Functional mobile/desktop, accessibility, and 11 responsive viewport projects are covered. |
| Extension Jest | PASS | 4 suites, 110/110 tests. |
| Extension audit/build/checksums | PASS | 0 vulnerabilities; staging and production ZIP checksums verified. |
| Android build | PASS | Vite build, Capacitor sync, and Gradle `assembleDebug` completed. |
| Gitleaks | PASS | No leaks detected. |
| cPanel control tests | PASS | Staging runtime preparation and Production public-root controls validated. |
| PDF binary/layout acceptance | PASS | Four valid PDF 1.7 files, each one A4 page and over 10 KB; all four rendered to PNG and visually checked for clipping, overlap, and blank pages. |

The 11 Browser QA skips are intentional matrix scoping: functional scenarios run on representative mobile/desktop projects, accessibility runs on representative mobile/desktop projects, and `responsive.spec.js` runs across all 11 configured viewports. The selected suite contains 90 test instances in total.

## Build artifacts

Artifacts were generated from SHA `2720f0a02efe0a339e71b30eaa101b7ad5097ec3` and expire according to their GitHub retention policy.

- [Four proforma PDF acceptance files](https://github.com/nezamparvar/navracar/actions/runs/32002027262/artifacts/9278724541) — artifact `9278724541`, SHA-256 `851c9b9e60cfb72980e51e068069d4f7b3b2f98e9f9ea669612301a770080e59`
- [Android debug APK](https://github.com/nezamparvar/navracar/actions/runs/32002027262/artifacts/9278739144) — artifact `9278739144`, SHA-256 `df4c7a3dc812df3e841199ad981febf72d9fcd9e9a73010784e5ba9b4cc3169b`
- [Browser extension staging/production bundles](https://github.com/nezamparvar/navracar/actions/runs/32002027262/artifacts/9278718860) — artifact `9278718860`, SHA-256 `61a104be145ef4257b95ddd463dbe5b6ffe84136edcccd289588e9dc7f04749e`
- [Gitleaks SARIF](https://github.com/nezamparvar/navracar/actions/runs/32002027262/artifacts/9278723633) — artifact `9278723633`, SHA-256 `afde940558f1a2852a89f59069f6c80865cb38e919b59c01e349ccf02519a3ed`

## Remediation release path

1. Run protected CI on the remediation PR and merge only after every required check passes.
2. Build a new immutable candidate from the remediation merge commit; never reuse or mutate `rc-v1.3.0-1`.
3. In the Staging cPanel clone, use **Update from Remote** and **Deploy HEAD Commit**. The deployment must report successful migrations and cache generation.
4. Repeat the full Staging acceptance checklist, including the formerly failing admin, import queue, customs-price persistence, and four PDF variants.
5. Test the extension and Android client against the accepted Staging candidate.
6. Mostafa signs off Staging. Only then may the exact accepted artifact be promoted to Production without rebuilding it.

Current gate: **STAGING REJECTED — PRODUCTION UNCHANGED**.
