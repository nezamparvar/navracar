# Navracar completion status

Last updated: 2026-08-17

## Current release state

**READY FOR REVIEW — MERGE/DEPLOY NOT PERFORMED**

- Candidate branch: `claude/navracar-pr26-review-l2w2mz`
- Draft pull request: [#27 — Complete and harden Navracar candidate](https://github.com/nezamparvar/navracar/pull/27)
- Latest fully tested implementation SHA: `2720f0a02efe0a339e71b30eaa101b7ad5097ec3`
- Authoritative CI: [run #112](https://github.com/nezamparvar/navracar/actions/runs/32002027262), all six protected jobs successful
- PR is open, draft, mergeable, and targets `main`.
- No merge, Staging deployment, Production deployment, branch deletion, or PR closure was performed.

The source candidate and its automated acceptance evidence are complete. The remaining actions are release approvals and environment-specific owner acceptance; they are intentionally outside this candidate and require Mostafa's explicit approval.

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
| 0. Reconcile PR and candidate | Complete | Candidate published without force-push; replacement draft PR #27 targets `main`. |
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
| 11. Staging/Production gates | Awaiting explicit owner approval | No deployment was performed. Follow the release path below only after approval. |

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

## Approval-only release path

These are not unresolved source defects. They require protected environments, live accounts, or an owner decision:

1. Mostafa reviews PR #27 and explicitly approves merge and Staging deployment.
2. Deploy the exact accepted candidate artifact to Staging and record source SHA, candidate SHA, artifact IDs, and checksums.
3. On Staging, load the staging extension against authenticated Dubizzle, DubiCars, and YallaMotor pages; confirm the generated listing remains a draft in admin review.
4. Install the debug APK on a physical/emulated Android device and complete the documented online/offline/CORS acceptance checks against Staging.
5. Mostafa signs off Staging. Only then promote the exact accepted artifact to Production without rebuilding it.

Until those approvals are given: **READY FOR REVIEW — MERGE/DEPLOY NOT PERFORMED**.
