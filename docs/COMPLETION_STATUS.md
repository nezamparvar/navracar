# Navracar completion status

Last updated: 2026-08-17

## Current release state

**STAGING REJECTED — THIRD REMEDIATION MERGED; CANDIDATE `rc-v1.3.0-4` PENDING**

- PR #27 was merged to `main` as `0a73ff0e29093ab47b863d7427bdc7c7c4788b1c` after Mostafa's explicit approval.
- Candidate `rc-v1.3.0-1` was built successfully and published to `cpanel-staging` as `ebd36599e41af80ad7e1c3fb250a2a28bc37a0e3` (artifact `9279049851`).
- The candidate was deployed to the isolated cPanel Staging environment. Production was not promoted or deployed.
- Owner acceptance found reproducible HTTP 500 failures. The Staging log proves the imported database had not applied the candidate migrations (`quote_requests.deleted_at`, `car_listings.customs_price_aed`, and `import_queue` were missing). PDF generation also failed because persistent `storage/fonts` was absent.
- PR #28 merged as `c957eb0175a222a555f07a9f98652ac6c35632ca`; immutable candidate `rc-v1.3.0-2` was published as `3013e1892f36c631f3268ce356b6a72f17264a33` by [workflow run 32006524997](https://github.com/nezamparvar/navracar/actions/runs/32006524997).
- Live retesting of `rc-v1.3.0-2` found a subdirectory-root HTTP 405, a request-list HTTP 500 caused by the nonexistent `pipeline_stages.order` column, a post-delete redirect to the deleted detail URL, and missing pipeline-column create/delete operations.
- PR #29 fixed those four defects. Protected [PR CI run #118](https://github.com/nezamparvar/navracar/actions/runs/32007681683) and [main CI run #119](https://github.com/nezamparvar/navracar/actions/runs/32007837193) both passed all six jobs.
- PR #29 merged to `main` as `3bb23c084eb52b205ae0dc850c01dae8e18cbc72`. Production deployment files and Production runtime remain unchanged.
- Candidate `rc-v1.3.0-3` was published successfully, but a live route probe still returned 404 for the new pipeline-stage endpoint. This proves the cPanel working copy had not activated that candidate, so the previously reported 405/delete/pipeline symptoms were still from the older deployed code.
- The next remediation also separates clearance/payment/timeline presentation per owner acceptance, exposes all three marketplace import paths, normalizes read permissions only inside Staging public media, and emits candidate/source response headers so the active deployment can be verified remotely.
- PR #31 merged to `main` as `551fcfb59b27a496adda1631b9fb3c0d31a1168c`. Protected [PR CI run #122](https://github.com/nezamparvar/navracar/actions/runs/32009758004) and [main CI run #123](https://github.com/nezamparvar/navracar/actions/runs/32009908365) passed all six jobs.
- Staging acceptance remains rejected until the next immutable candidate is built, its exact commit is activated through both **Update from Remote** and **Deploy HEAD Commit**, and the response headers plus full acceptance checklist are verified.

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

## Second Staging incident evidence — 2026-08-17

| Check | Result |
|---|---|
| `/staging/` | FAIL — WCDN/LiteSpeed returned HTTP 405 while explicit `/staging/index.php` returned HTTP 200; the Staging `.htaccess` now rewrites the subdirectory root before the directory guard. |
| Request list | FAIL — `PipelineStage::orderBy('order')` referenced a nonexistent column; corrected to `sort_order` with regression coverage. |
| Request deletion | FAIL — successful soft deletion redirected back to the deleted detail URL and produced HTTP 404; corrected to redirect to the request index. |
| Pipeline columns | FAIL — only rename existed; admin-only create and safe delete are now implemented. Occupied columns must be emptied before deletion. |
| Performance probe | HOST/CDN CONCERN — uncached and cached static files plus `/up` showed roughly 3–4 seconds TTFB, so the remaining latency is not isolated to Laravel or database queries. |
| PR #29 / main validation | PASS — all six protected jobs passed on both the PR head and merge commit. |

## Third Staging acceptance findings — 2026-08-17

| Check | Result |
|---|---|
| Candidate activation | FAIL — live `POST /staging/admin/pipeline-stages` returned 404 after `rc-v1.3.0-3` publication, proving the new route was not active on cPanel. |
| Vehicle media | FAIL — all 20 persisted image URLs returned HTTP 403 and rendered with zero natural dimensions. The Staging deployment now normalizes public-media directories to 0755 and files to 0644 without touching Production or private storage. |
| Clearance/payment presentation | OWNER CHANGE — customs clearance moved out of payment conditions into the clearance-cost table; broker fee remains in payment conditions but its two-day timeline segment is removed. |
| Marketplace import discoverability | OWNER CHANGE — Admin now shows explicit Dubizzle, DubiCars, and YallaMotor links plus Extension Pairing and Import Queue entry points. |
| Release observability | ADDED — Staging responses expose validated `X-Navracar-Candidate` and `X-Navracar-Source` headers from the deployed release metadata. |

The original source candidate passed its automated gates, but live Staging
acceptance correctly found deployment-runtime defects that CI did not model.
The remediation passed protected CI and was merged. Live acceptance remains pending on the newly built and explicitly activated candidate.

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
| 11. Staging/Production gates | Staging rejected; candidate 4 pending | PR #31 is merged and fully green. `rc-v1.3.0-4` must be built from `551fcfb59b27a496adda1631b9fb3c0d31a1168c`, activated on cPanel, and live-tested. Production remains unchanged. |

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

Artifacts were generated from exact merged SHA `551fcfb59b27a496adda1631b9fb3c0d31a1168c` by main CI run #123 and expire according to GitHub retention policy.

- [Four proforma PDF acceptance files](https://github.com/nezamparvar/navracar/actions/runs/32009908365/artifacts/9281381510) — artifact `9281381510`, SHA-256 `6ebcb8ffb1e17dc6c50e08958fdaad7819c26c3b0d9bfe18629e3554bdcb3fa6`
- [Android debug APK](https://github.com/nezamparvar/navracar/actions/runs/32009908365/artifacts/9281391437) — artifact `9281391437`, SHA-256 `3a2a4f912a075fcc48ab3897d3f602a9a1bf58bd8001a09016e547e3c232a91d`
- [Browser extension staging/production bundles](https://github.com/nezamparvar/navracar/actions/runs/32009908365/artifacts/9281363196) — artifact `9281363196`, SHA-256 `64e200a9dfd4fc9542f190196c5508b88c5d311fbccafdc067eeb9b1d3214e21`
- [Gitleaks SARIF](https://github.com/nezamparvar/navracar/actions/runs/32009908365/artifacts/9281366331) — artifact `9281366331`, SHA-256 `9131c6773e14cd2ae0b60c7a79043b65f51b7278f236398fe793e240bcbda2a5`

## Remediation release path

1. Protected CI has passed and PR #31 is merged.
2. Build immutable candidate `rc-v1.3.0-4` from `551fcfb59b27a496adda1631b9fb3c0d31a1168c`; never reuse or mutate an earlier candidate.
3. In the Staging cPanel clone, use **Update from Remote** and **Deploy HEAD Commit**. The deployment must report successful migrations and cache generation.
4. Repeat the full Staging acceptance checklist, including the formerly failing admin, import queue, customs-price persistence, and four PDF variants.
5. Test the extension and Android client against the accepted Staging candidate.
6. Mostafa signs off Staging. Only then may the exact accepted artifact be promoted to Production without rebuilding it.

Current gate: **STAGING REJECTED — PRODUCTION UNCHANGED**.
