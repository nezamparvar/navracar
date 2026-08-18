# NavraCar Android V1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver the stage-first NavraCar Android V1 on the existing Capacitor/Laravel architecture.

**Architecture:** The packaged Persian RTL client in `mobile/` consumes a small `/api/mobile/v1` surface in the existing Laravel application. Pricing, listings, QuoteRequest records, settings, and CRM remain server-authoritative and shared with the web product.

**Tech Stack:** Laravel 12/PHP 8.3, plain modular ES2022/CSS, Capacitor 8, Android Gradle/JDK 21/SDK 36, PHPUnit, Node test runner, Playwright.

**Spec:** `docs/superpowers/specs/2026-08-18-navracar-android-v1-design.md`

## Global Constraints

- Immutable V2 reference: `1cdab114920cdc2431f983a1c1ea9efb88e26f82`.
- Do not deploy or mutate production.
- Never duplicate pricing formulas or business settings in the APK.
- Preserve Persian RTL, CRM authorization, and the current QuoteRequest workflow.
- No credentials, signing secrets, or production `.env` files in Git.

---

### Task 1: Mobile API contract and customer boundary

**Files:**
- Create: `tests/Feature/MobileApiV1Test.php`
- Create: `database/migrations/2026_08_18_000001_create_mobile_customer_tables.php`
- Create: `app/Models/MobileCustomer.php`
- Create: `app/Models/MobileAccessToken.php`
- Create: `app/Http/Middleware/AuthenticateMobileCustomer.php`
- Create: `app/Http/Controllers/Api/Mobile/V1/*.php`
- Modify: `bootstrap/app.php`, `routes/api.php`, `app/Models/QuoteRequest.php`, `app/Http/Controllers/Public/QuoteController.php`

**Interfaces:**
- Produces: JSON under `/api/mobile/v1`, bearer tokens in `id|secret` format,
  published vehicle resources, customer-owned requests, and server favorites.

- [ ] Write feature tests for public vehicle/bootstrap responses, filtering,
  auth lifecycle, token isolation, favorites, request ownership, quote linkage,
  and shared URL allowlisting.
- [ ] Run `php artisan test --compact tests/Feature/MobileApiV1Test.php` and
  verify failures are due to missing routes/classes.
- [ ] Implement the migration, models, middleware, controllers, routes, and
  QuoteRequest linkage with no new pricing math.
- [ ] Run the focused test and full backend suite.
- [ ] Commit as `feat: add Android mobile API v1`.

### Task 2: Client domain modules and tests

**Files:**
- Create: `mobile/js/api.js`, `mobile/js/auth.js`, `mobile/js/format.js`,
  `mobile/js/state.js`, `mobile/js/views.js`
- Create: `tests/mobile/*.test.js`
- Modify: `package.json`

**Interfaces:**
- Produces: `createApiClient`, `createTokenStore`, Persian/AED/IRR formatters,
  hash-route parsing, normalized API errors, and view renderers.

- [ ] Write Node tests with literal expected values for formatting, route
  parsing, query serialization, auth-expiry cleanup, and error normalization.
- [ ] Run `npm run test:mobile` with Node 22 and verify the expected failures.
- [ ] Implement the smallest modules that make the tests pass.
- [ ] Run `npm run test:mobile` and commit as `feat: add Android client core`.

### Task 3: V2 RTL application shell and screens

**Files:**
- Replace: `mobile/index.html`, `mobile/styles.css`, `mobile/app.js`
- Modify: `mobile/js/views.js`

**Interfaces:**
- Consumes: Task 1 JSON contract and Task 2 client modules.
- Produces: Home, Vehicles, Detail, Pricing, Quote, Requests, Account,
  Favorites, and Shared Listing screens with four-item bottom navigation.

- [ ] Add Playwright fixture tests for the required screen inventory and RTL.
- [ ] Run the focused browser spec and confirm it fails on the old calculator.
- [ ] Implement the accessible V2 shell, reusable cards/inputs/buttons/states,
  all screens, offline/retry/auth-expiry/image errors, and safe external actions.
- [ ] Run Node and browser tests; commit as `feat: build V2 Android experience`.

### Task 4: Android platform integration and stage separation

**Files:**
- Modify: `android/app/src/main/AndroidManifest.xml`,
  `android/app/src/main/java/com/navracar/mobile/MainActivity.java`,
  `android/app/build.gradle`, `capacitor.config.json`,
  `tools/build-android-variants.sh`
- Test: `tests/Feature/StagingSafetyTest.php` and Android unit/build checks.

**Interfaces:**
- Produces: share/deep-link intake, RTL/safe navigation, conditional staging
  package/label/version/endpoint, debug and unsigned release outputs.

- [ ] Add tests/checks for intent filters, package separation, and absence of
  embedded credentials.
- [ ] Implement Android integration and staging-safe variant configuration.
- [ ] Run Capacitor sync, Gradle tests, staging debug, and release builds.
- [ ] Commit as `feat: integrate Android intents and staging variants`.

### Task 5: Documentation, visual QA, and publication

**Files:**
- Create: `docs/ANDROID_V1.md`
- Create: `tests/e2e/android-v1.spec.js`
- Generate: `artifacts/android-v1/screenshots/*` and staging APK/checksum.

**Interfaces:**
- Produces: architecture/setup/API matrix/screen inventory/token mapping/RTL/
  tests/staging/APK/limitations/promotion checklist and reviewable artifacts.

- [ ] Run visual fixture capture for every required screen and compare against
  the immutable Android reference, correcting obvious token/spacing/RTL issues.
- [ ] Run lint/syntax, Composer/npm audits, backend, Node, Playwright,
  Capacitor/Gradle debug/release, APK metadata/signature/alignment, and secret
  scans with fresh output.
- [ ] Complete `docs/ANDROID_V1.md`, copy only user-facing artifacts to the
  workspace `outputs/`, and commit as `docs: document Android V1 release`.
- [ ] Push `feat/navracar-android-v1` and create a draft PR to the default branch.
