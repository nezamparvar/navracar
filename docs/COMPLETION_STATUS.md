# Navracar completion status

Last updated: 2026-08-17

## Release guardrails

- Candidate branch: `claude/navracar-pr26-review-l2w2mz`
- Initial candidate SHA: `e15e942c6bf94e0d0a57674a930f2c411c71bb42`
- Pull request: #26 currently points to `claude/new-session-ml3z1h` at `1ed6ec29675c938385b8c2aed243de6c0e01807d`.
- No merge, Staging deployment, Production deployment, branch deletion, or PR closure without Mostafa's explicit approval.
- Final status remains `NOT READY FOR MERGE` until every applicable acceptance gate is verified on the exact PR candidate SHA.

## Source-of-truth findings

- The review branch is six commits ahead of the current PR #26 head.
- The six review commits cover Other Costs consistency, public cost grouping, calculator wizard E2E coverage, Persian slug/mobile pricing fixes, route generation, and test/authorization repairs.
- PR #26's description and checkboxes are stale and no CI run is attached to the review branch SHA.
- Repository instructions require PHP 8.3, locked Composer/npm dependencies, the centralized `VehiclePricingService`, and the release path `CI -> Staging -> owner acceptance -> Production`.
- This execution environment currently has Node.js and Java but no PHP, Composer, Docker, Gradle, or browser binary. PHP/Composer checks are therefore environment-blocked locally until a suitable runtime is available; GitHub Actions remains the authoritative remote execution path.

## Phase tracker

| Phase | Status | Evidence / next action |
|---|---|---|
| 0. Reconcile PR #26 and review branch | In progress | Audit six commits, verify locally, then make the reviewed candidate reviewable without force-push. |
| 1. Pricing and customs value | In progress | Server-side missing-value fallback and explicit-zero preservation implemented with regression tests; CI execution still required. Imports and every UI entry path remain to audit. |
| 2. CRM/auth/lifecycle/archive | In progress / runtime blocked | Static audit centralized Kanban/template mutations on `QuoteRequestPolicy` and restricted soft-deleted model binding to restore/force-delete routes only; regression test added. Re-run policy, IDOR, dashboard, close/lost, archive, restore, and force-delete tests in PHP CI. |
| 3. Public UX/calculator/catalog/responsive | Pending verification | Run E2E suite and direct viewport inspection. |
| 4. PDF acceptance | In progress / runtime blocked | English currency, category, row-label, and calculation-basis localization repaired and tested in code. Generate, render, and visually inspect all four FA/EN full/single variants in CI/artifact-capable runtime. |
| 5. Mobile/Capacitor/Android | In progress | Replaced the non-buildable Laravel `public/` webDir with a local static mobile shell; added stateless/CORS-restricted API, real native Android project, Capacitor 8.5, Node 22/SDK 36 CI job. `cap sync android` passes; local Gradle download is network-blocked, so `Android build` CI must verify assemble. |
| 6. Browser capture/marketplaces | Pending | Reconcile extension branch after the main candidate is stable; test all three sanitized fixtures. |
| 7. Database/migrations | Blocked pending PHP/database runtime | Fresh, upgrade, rollback, precision, index, and soft-delete checks. |
| 8. Security/privacy | In progress | Recheck candidate diff, dependency audits, authorization, SSRF/import/upload/log/artifact boundaries. |
| 9. Full automated gate | In progress | Run available npm gates locally and all four required GitHub Actions checks on the exact candidate SHA. |
| 10. Docs/release preparation | Pending | Update runtime docs, deployment/rollback, artifacts, hashes, and release notes. |
| 11. Staging/Production gates | Approval required | Prepare approval packet only; do not deploy or merge. |

## Required validation log

| Command / check | Result | Evidence |
|---|---|---|
| `composer validate` | BLOCKED BY ENVIRONMENT | `composer`/PHP unavailable locally. |
| `composer audit` | BLOCKED BY ENVIRONMENT | `composer`/PHP unavailable locally. |
| `php artisan test --compact` | BLOCKED BY ENVIRONMENT | PHP unavailable locally. |
| `vendor/bin/pint --test` | BLOCKED BY ENVIRONMENT | PHP/vendor unavailable locally. |
| `npm ci` | PASS | 236 locked packages installed after using a writable workspace cache; package lock updated for Capacitor 8.5. |
| `npm audit` | PASS | `found 0 vulnerabilities` after Capacitor upgrade and a scoped `xcode -> uuid ^11.1.1` override. |
| `npm run build` | PASS | Vite 6.4.3, 58 modules transformed, production assets emitted successfully. |
| `npx cap sync android` | PASS | Local `mobile/` assets copied and Capacitor Android project updated successfully. |
| Static syntax/integrity checks | PASS | `git diff --check`, `node --check mobile/app.js`, JSON parsing for npm/Capacitor files, and `bash -n android/gradlew` pass after the CRM route-binding hardening. |
| `android/gradlew assembleDebug` | BLOCKED BY ENVIRONMENT | Wrapper could not download Gradle 8.14.3 because `services.gradle.org` is unreachable in this sandbox; new GitHub `Android build` job performs the authoritative build. |
| `npm run test:e2e` | Not run yet | Requires browser installation/runtime. |
| GitHub Actions: Dependencies | Not run on candidate | PR head must be updated or replacement PR opened. |
| GitHub Actions: Backend tests | Not run on candidate | PR head must be updated or replacement PR opened. |
| GitHub Actions: Frontend build | Not run on candidate | PR head must be updated or replacement PR opened. |
| GitHub Actions: Browser QA | Not run on candidate | PR head must be updated or replacement PR opened. |
| GitHub Actions: Android build | Not run on candidate | New required candidate check; produces a debug APK artifact. |

## Resume rule

Resume at the first incomplete row above. Fix ordinary failures and continue. Stop only at an explicit approval/access boundary, and never report completion while any required item is failed, unverified, skipped, or environment-blocked without Mostafa's explicit acceptance.
