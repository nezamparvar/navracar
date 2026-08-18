# Production-like Integration Staging Design

## Objective

Make `https://staging.nezamparvar.com` the production-like integration environment for NavraCar web, Navra Capture, and Android. A staging candidate must contain all three surfaces together, use the shared Laravel backend and staging database, and be promotable only after combined acceptance. Production remains untouched until explicit owner approval.

## Problem

The generated `cpanel-staging` branch is a single moving deployment target. A feature-branch Android candidate can provide `/api/mobile/v1`, while a later `main` candidate for Navra Capture replaces the entire Laravel tree and removes those routes. The clients do not conflict at runtime; independent whole-application candidates conflict at deployment time.

## Chosen Architecture

`staging.nezamparvar.com` is the integration lane, not a feature-preview lane. The final workflow accepts only the exact current `main` HEAD and publishes one immutable artifact containing:

- web and admin routes;
- `/api/browser-capture/v1` for Navra Capture;
- `/api/mobile/v1` for Android;
- the centralized `VehiclePricingService`, QuoteRequest workflow, settings, staging database, and admin panel;
- production-targeted Chrome and Android packages built from the same source commit, without publishing them.

Feature work is validated by PR CI. For this migration only, the combined Android branch is deployed once to integration staging before merge. After acceptance, the staging workflow is locked to current `main`, the PR is merged, and a new main candidate is deployed and retested. This removes the recurring overwrite class.

## Release Flow

```text
Chrome tests -> Android tests -> combined PR CI
  -> temporary combined candidate on staging
  -> live combined acceptance
  -> lock staging publisher to current main HEAD
  -> merge to main
  -> immutable main candidate
  -> deploy same candidate to integration staging
  -> final acceptance
  -> stop; production requires a later explicit approval
```

The production promotion workflow remains main/tag-locked and must copy the accepted staging bytes rather than rebuild them.

## API Boundaries

- Web and admin retain their current routes.
- Navra Capture uses `/api/browser-capture/v1/pairing/exchange` and `/api/browser-capture/v1/listings`.
- Android uses the versioned `/api/mobile/v1` contract.
- Both route groups are registered in the same Laravel application.
- Vehicle pricing remains exclusively authoritative in `App\Services\VehiclePricing\VehiclePricingService`.
- No client embeds rates, customs percentages, pricing formulas, credentials, or production secrets.

## Contract Gate

CI must contain a combined Laravel contract test that proves both client route groups exist in the same application and exercise their public authentication boundaries. The staging workflow must also require the existing protected jobs: Dependencies, Backend tests, Frontend build, Browser QA, Android build, and Browser extension.

After the migration candidate is accepted, the staging workflow must reject:

- dispatches from any ref other than `main`;
- a source commit that is merely an ancestor rather than the exact remote `main` HEAD;
- a commit without every protected check passing;
- an invalid or reused immutable candidate identifier.

## Database and Migration Safety

Integration staging uses its existing isolated staging database. Mobile migrations must complete a fresh migrate, rollback, and re-migrate lifecycle in CI before deployment. Schema changes must be additive/backward-compatible for this release; no destructive production migration is authorized. Code rollback must not require a database restore.

## Validation Order

### Navra Capture first

- dependency audit, lint, Jest suite, reproducible staging/production ZIPs, and checksum verification;
- Laravel pairing exchange, one-time code, bearer authentication, marketplace host validation, sanitized capture, duplicate detection, draft creation, revoke/401 behavior;
- live staging pairing and capture API tests using disposable staging-only records.

### Android second

- mobile unit tests, Laravel mobile API/auth/pricing/QuoteRequest/analytics/push tests, RTL browser QA;
- staging debug and unsigned release builds plus production debug configuration inspection;
- APK package ID, environment URL, signing state, ZIP alignment, secrets scan, and SHA-256;
- live bootstrap, vehicles, pricing, registration/auth, QuoteRequest, analytics consent, and auth-expiry/error handling.

### Combined acceptance

- both route groups are present after the same deployment;
- Chrome staging package and Android staging APK point to `staging.nezamparvar.com`;
- production packages point only to `navracar.com` and are not published;
- web/admin critical flows remain green;
- candidate/source headers and artifact metadata match;
- production deploy timer remains disabled and inactive.

## Failure and Rollback

If any combined gate fails, do not merge or promote. Retain the immutable staging candidate for evidence, restore the last accepted staging candidate if needed, and fix the source branch. The staging deployer must bring Laravel out of maintenance mode on failure. Production is never used as a test environment.

## Acceptance Criteria

- Navra Capture and Android work simultaneously against one staging deployment.
- A later staging publication cannot silently remove either route group.
- All protected CI jobs pass on the combined commit and again on merged `main`.
- Live staging evidence is collected for both clients and shared backend workflows.
- The exact candidate metadata, APK/ZIP checksums, branch, PR, and remaining external blockers are documented.
- No production deployment, promotion, release publication, or destructive migration occurs.
