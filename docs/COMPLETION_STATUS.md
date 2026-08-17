# Navracar completion status

Last updated: 2026-08-17

## Release guardrails

- Candidate branch: `claude/navracar-pr26-review-l2w2mz`
- Latest tested implementation commit: `47ddeb7a` (`Integrate and harden Navra Capture extension`)
- Pull request #26 still points to `claude/new-session-ml3z1h` at `1ed6ec29675c938385b8c2aed243de6c0e01807d`; it does not contain this candidate.
- No merge, Staging deployment, Production deployment, branch deletion, or PR closure is permitted without Mostafa's explicit approval.
- Final state is `NOT READY FOR MERGE`: exact-candidate PHP/Android/E2E CI and manual PDF/browser acceptance are still outstanding.

## Completed implementation in the candidate

- Customs declared value now defaults to a configurable percentage below the real vehicle value, while an explicit zero remains zero. All pricing entry points use the centralized pricing service.
- FA/EN PDF currency, category, row labels, and calculation basis were corrected and regression coverage was added.
- CRM authorization and soft-deleted route binding were narrowed to policy-protected restore/force-delete flows.
- Persian slugs, mobile pricing API/settings, responsive calculator flow, and reset/data-preservation behavior were repaired.
- A buildable Capacitor Android project and a static mobile shell were added, with a dedicated Android CI check.
- Navra Capture is integrated end to end: single-use hashed pairing, hashed bearer tokens, bounded validation, marketplace and image host allowlists, sensitive-diagnostic rejection, duplicate detection, review/edit/cancel queue, and draft-only publication.
- Separate staging/production extension packages are generated with environment verification, SHA-256 files, and real 16/48/128 icons.
- CI, release, staging, promotion, branch-protection documentation, extension installation, integration, and testing documentation were updated.

## Phase tracker

| Phase | Status | Evidence / next action |
|---|---|---|
| 0. Reconcile PR and candidate | Blocked on publishing tool | Candidate is committed locally; install/authenticate `gh`, push the branch, and open a replacement draft PR because PR #26 has a different head. |
| 1. Pricing and customs value | Implemented; candidate CI pending | Configurable fallback and explicit-zero preservation implemented with regression tests. |
| 2. CRM/auth/lifecycle/archive | Implemented; candidate CI pending | Policy scope and soft-delete binding hardened; PHP regression execution required. |
| 3. Public UX/calculator/catalog/responsive | Previously passed; candidate rerun pending | Reported E2E coverage passed before extension integration; rerun on exact candidate in Browser QA. |
| 4. PDF acceptance | Implemented; visual acceptance pending | Code/tests cover four FA/EN full/single variants; render and visually inspect CI artifacts. |
| 5. Mobile/Capacitor/Android | Implemented; Android CI pending | Vite and `cap sync android` pass locally; network-restricted local Gradle cannot fetch the distribution. |
| 6. Browser capture/marketplaces | Implemented; PHP/manual acceptance pending | 110/110 extension tests pass; all three fixtures parse; secure backend flow and feature test added. |
| 7. Database/migrations | Runtime blocked | Fresh/upgrade/rollback and schema precision checks need PHP/database CI. |
| 8. Security/privacy | Locally reviewed; CI pending | Root and extension dependency audits report zero vulnerabilities; authorization, SSRF/host allowlists, token storage, diagnostics, and artifact boundaries hardened. |
| 9. Full automated gate | In progress | Local Node gates pass; exact candidate needs GitHub Dependencies, Backend, Frontend, Browser QA, Browser extension, and Android checks. |
| 10. Docs/release preparation | Implemented; final artifact links pending | Runbooks and extension docs updated; add CI artifact URLs and exact final candidate SHA after publishing. |
| 11. Staging/Production gates | Approval required | Prepare review packet only. Do not merge or deploy. |

## Validation log

| Command / check | Result | Evidence |
|---|---|---|
| `composer validate` / `composer audit` | BLOCKED LOCALLY | PHP and Composer are unavailable in this runtime; run in GitHub Dependencies job. |
| `php artisan test --compact` / Pint | BLOCKED LOCALLY | PHP/vendor runtime unavailable; candidate adds `BrowserExtensionFlowTest`. |
| Root `npm ci` | PASS | 236 locked packages installed. |
| Root `npm audit` | PASS | `found 0 vulnerabilities`. |
| Root `npm run build` | PASS | Vite 6.4.3 built 58 modules. |
| `npx cap sync android` | PASS | Mobile assets and Android plugins synchronized. |
| `android/gradlew assembleDebug` | BLOCKED LOCALLY | Gradle 8.14.3 download host is inaccessible from the sandbox; dedicated CI job is authoritative. |
| Extension Jest | PASS | 4 suites, 110/110 tests. |
| Extension dependency audit | PASS | Offline audit reported zero vulnerabilities. |
| Extension staging/production build | PASS | Both bundles generated; each worker contains the correct fixed environment. |
| Extension package integrity | PASS | Both ZIP SHA-256 files verify; PNG dimensions are 16x16, 48x48, and 128x128. |
| Source integrity/security scan | PASS | `git diff --check`, conflict-marker scan, and common committed-secret signature scan passed. |
| Exact-candidate GitHub Actions | NOT RUN | Branch has not been pushed and no PR targets it. |

## Exact resume point

1. Install GitHub CLI, authenticate it, then push `claude/navracar-pr26-review-l2w2mz` without force-pushing.
2. Open a replacement draft PR against the repository default branch; do not merge or deploy.
3. Run and fix every exact-SHA required check: Dependencies, Backend tests, Frontend build, Browser QA, Browser extension, and Android build.
4. Render and inspect all four PDF variants, manually load the staging extension against the three supported marketplaces, and verify the generated draft in admin review.
5. Record the final candidate SHA, CI/artifact URLs, and acceptance results here. Only then report `READY FOR REVIEW — MERGE/DEPLOY NOT PERFORMED`.
