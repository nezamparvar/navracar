# Production-like Integration Staging Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Validate Navra Capture first, Android second, then deploy and prove one production-like integration candidate without touching production.

**Architecture:** Merge the current Android feature head into the latest `main` extension state, prove both versioned APIs coexist, exercise a temporary combined staging candidate, then lock the moving staging publisher to the exact current `main` HEAD. Merge only after the combined live gate, publish a fresh immutable main candidate, and repeat acceptance against the same composition intended for production.

**Tech Stack:** Laravel 12/PHP 8.3, Node.js 22, Playwright, Jest, Capacitor 8, JDK 21, Android SDK 36, GitHub Actions, immutable cPanel artifacts, Nginx/CloudPanel staging.

**Spec:** `docs/superpowers/specs/2026-08-18-production-like-integration-staging-design.md`

## Global Constraints

- Preserve Persian RTL and existing product behavior.
- Keep pricing exclusively in `App\Services\VehiclePricing\VehiclePricingService`.
- Keep backend, staging database, QuoteRequest, settings, CRM, and admin shared.
- Never commit credentials, `.env`, FCM secrets, tokens, or customer data.
- Production promotion and deployment are forbidden in this plan.
- Use locked Composer/npm dependencies, PHP 8.3, Node.js 22, JDK 21, and Android SDK 36.
- Production may later receive only the exact owner-accepted staging artifact.

---

### Task 1: Create the Combined Source

**Files:**
- Merge: `origin/main`
- Merge: `origin/feat/navracar-android-v1`
- Preserve: `tools/navra-capture-extension/`
- Preserve: `mobile/`, `android/`, `routes/api.php`

**Interfaces:**
- Consumes: latest extension fixes from `main` and Android/mobile API implementation from PR #46.
- Produces: one mergeable source commit containing both clients and both API route groups.

- [ ] **Step 1:** Record both source SHAs and verify `git merge-tree --write-tree origin/main origin/feat/navracar-android-v1` exits zero.
- [ ] **Step 2:** Merge the Android feature into the integration branch without squashing provenance.
- [ ] **Step 3:** Inspect the merged diff for route, workflow, package-lock, calculator, and extension overlaps.
- [ ] **Step 4:** Run `git diff --check` and PHP syntax checks for all merged PHP files.
- [ ] **Step 5:** Commit the documented integration source.

### Task 2: Add the Shared API Contract Gate with TDD

**Files:**
- Create: `tests/Feature/IntegratedClientContractTest.php`
- Modify: `routes/api.php`

**Interfaces:**
- Consumes: Laravel route registry and existing browser/mobile controllers.
- Produces: named browser routes `api.browser-capture.pairing.exchange` and `api.browser-capture.listings.store`, plus a test that requires both client surfaces.

- [ ] **Step 1: Write the failing contract test**

```php
public function test_android_and_browser_capture_contracts_coexist(): void
{
    $this->assertTrue(Route::has('api.mobile.bootstrap'));
    $this->assertTrue(Route::has('api.browser-capture.pairing.exchange'));
    $this->assertTrue(Route::has('api.browser-capture.listings.store'));
    $this->getJson('/api/mobile/v1/bootstrap')->assertOk();
    $this->postJson('/api/browser-capture/v1/listings', [])->assertUnauthorized();
}
```

- [ ] **Step 2: Verify RED**

Run: `php artisan test --compact tests/Feature/IntegratedClientContractTest.php`

Expected: failure because the browser-capture route names do not exist.

- [ ] **Step 3: Implement the minimal route names**

Add `->name('pairing.exchange')` and `->name('listings.store')` under a group named `api.browser-capture.` without changing URLs, middleware, controllers, or response behavior.

- [ ] **Step 4: Verify GREEN**

Run: `php artisan test --compact tests/Feature/IntegratedClientContractTest.php tests/Feature/BrowserExtensionFlowTest.php tests/Feature/MobileApiV1Test.php`

Expected: all tests pass with zero failures.

- [ ] **Step 5:** Commit the contract gate.

### Task 3: Verify Navra Capture Before Android

**Files:**
- Inspect: `tools/navra-capture-extension/src/`
- Inspect: `tools/navra-capture-extension/scripts/`
- Test: `tools/navra-capture-extension/__tests__/`
- Test: `tests/Feature/BrowserExtensionFlowTest.php`

**Interfaces:**
- Consumes: staging/production extension configuration and browser-capture API.
- Produces: checksum-verified ZIP packages and server-flow evidence.

- [ ] **Step 1:** Run locked install, high-severity audit, lint, and Jest in `tools/navra-capture-extension`.
- [ ] **Step 2:** Build staging and production ZIPs and verify both SHA-256 sidecars.
- [ ] **Step 3:** Inspect packaged manifests/config to prove staging targets `https://staging.nezamparvar.com/api` and production targets `https://navracar.com/api` with no credentials.
- [ ] **Step 4:** Run `BrowserExtensionFlowTest` and related import/security tests.
- [ ] **Step 5:** Record ZIP paths, sizes, versions, and hashes.

### Task 4: Verify Android and Mobile Backend

**Files:**
- Inspect/Test: `mobile/`, `android/`, `tests/mobile/`, `tests/Feature/Mobile*`, `tests/e2e/android-v1.spec.js`
- Build: `tools/build-android-variants.sh`

**Interfaces:**
- Consumes: `/api/mobile/v1`, centralized pricing, QuoteRequest, analytics consent, FCM foundation.
- Produces: staging debug APK, staging unsigned release APK, production debug inspection artifact, and test evidence.

- [ ] **Step 1:** Run mobile Node tests and Laravel mobile API/engagement/push suites.
- [ ] **Step 2:** Run Android/RTL Playwright QA with project fixtures.
- [ ] **Step 3:** Build all Android variants with Node.js 22, JDK 21, and Android SDK 36.
- [ ] **Step 4:** Verify package IDs, endpoints, signing status, alignment, packaged assets, no secrets, and SHA-256.
- [ ] **Step 5:** Record APK paths and hashes.

### Task 5: Exercise a Temporary Combined Staging Candidate

**Files:**
- Use: `.github/workflows/cpanel-staging.yml` at the combined branch head before the main-only lock.
- Verify: `DEPLOYMENT-METADATA.json`, `SHA256SUMS.txt`

**Interfaces:**
- Consumes: combined CI-green source commit.
- Produces: one immutable combined staging candidate deployed to `staging.nezamparvar.com`.

- [ ] **Step 1:** Push the combined branch and wait for all six protected CI jobs.
- [ ] **Step 2:** Dispatch a unique `rc-v1.4.0-N` candidate using the exact branch-head SHA.
- [ ] **Step 3:** Verify artifact metadata/checksums and deploy through the staging-only service.
- [ ] **Step 4:** Confirm candidate/source headers, both route groups, HTTPS, CORS, noindex, and production timer guards.
- [ ] **Step 5:** Run disposable live Navra Capture and Android API acceptance flows, then clean up test records through application-supported operations where available.

### Task 6: Lock the Integration Publisher to Current Main

**Files:**
- Create: `tools/test-cpanel-staging-source-policy.sh`
- Modify: `.github/workflows/cpanel-staging.yml`
- Modify: `.github/workflows/ci.yml`
- Modify: `docs/STAGING_ARCHITECTURE.md`
- Modify: `docs/CPANEL_GIT_DEPLOYMENT.md`

**Interfaces:**
- Consumes: GitHub workflow ref and exact source SHA.
- Produces: a staging publisher that rejects feature refs and stale main ancestors.

- [ ] **Step 1: Write the failing policy test**

The shell test must require `github.ref == 'refs/heads/main'`, an exact comparison between the input SHA and fetched `origin/main` HEAD, all six protected checks, and must reject the branch-generic `startsWith(github.ref, 'refs/heads/')` policy.

- [ ] **Step 2: Verify RED**

Run: `bash tools/test-cpanel-staging-source-policy.sh`

Expected: failure because the feature workflow accepts any branch head.

- [ ] **Step 3: Implement the minimal workflow lock**

Make the source job main-only, fetch `origin/main`, require `INPUT_COMMIT == MAIN_HEAD`, retain all six protected checks, and keep production promotion unchanged.

- [ ] **Step 4: Verify GREEN**

Run: `bash tools/test-cpanel-staging-source-policy.sh && bash tools/test-cpanel-staging-runtime.sh && bash tools/test-cpanel-production-controls.sh`

Expected: all policy/deployment tests pass.

- [ ] **Step 5:** Document integration-only staging and commit the lock.

### Task 7: Full Local and Remote Verification

**Files:**
- Verify all changed files and existing test suites.

**Interfaces:**
- Consumes: final PR head.
- Produces: evidence that the final combined change is mergeable and releasable to staging.

- [ ] **Step 1:** Run Composer validate/install/audit and npm ci/audit/build.
- [ ] **Step 2:** Run PHP lint, migration fresh/rollback/migrate, full Laravel tests, extension tests/build, mobile tests, Playwright, and Android builds.
- [ ] **Step 3:** Push final head and require every GitHub check to succeed.
- [ ] **Step 4:** Review the PR diff and confirm no secret, unrelated refactor, production config, or destructive migration is present.

### Task 8: Merge, Rebuild Main Candidate, and Repeat Live Acceptance

**Files:**
- Merge: PR #46 to `main` after the combined candidate passes.
- Deploy: a new immutable main staging candidate.

**Interfaces:**
- Consumes: CI-green, owner-approved combined PR.
- Produces: the exact production composition running on integration staging.

- [ ] **Step 1:** Merge through protected GitHub policy; do not bypass failed checks or unresolved reviews.
- [ ] **Step 2:** Wait for all six required checks on merged `main`.
- [ ] **Step 3:** Dispatch a new unique candidate from the exact current main HEAD and deploy only to staging.
- [ ] **Step 4:** Repeat Chrome, Android, web/admin, route, CORS, analytics consent, QuoteRequest, and candidate-header acceptance.
- [ ] **Step 5:** Verify production timer remains disabled/inactive and stop before promotion.
- [ ] **Step 6:** Report final SHAs, PR, workflows, URLs, hashes, artifacts, live results, blockers, and explicit production non-deployment.
