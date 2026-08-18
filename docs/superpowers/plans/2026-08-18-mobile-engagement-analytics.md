# Navracar Mobile Engagement, Analytics, and Push Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver consented first-party Android analytics, live presence, device/search/contact reporting, FCM Push, and an RTL admin dashboard on the shared Navracar stack.

**Architecture:** A Laravel installation identity authenticates consented event batches and encrypted FCM tokens. Android emits semantic events through one isolated engagement module; the existing admin receives aggregated metrics and queued Push controls.

**Tech Stack:** Laravel 12/PHP 8.3, SQLite/MySQL-compatible migrations, Blade/Tailwind/Alpine CSP, Capacitor 8 Device and Push Notifications plugins, FCM HTTP v1, Node 22/JDK 21/Android SDK 36.

**Spec:** `docs/superpowers/specs/2026-08-18-mobile-engagement-analytics-design.md`

## Global Constraints

- Preserve Persian RTL and the existing shared Backend/DB/CRM/pricing engine.
- Default analytics and Push consent to false; collect no persistent hardware identifiers or precise location.
- Store installation secrets only as SHA-256 and Push tokens only encrypted plus a SHA-256 lookup hash.
- Keep FCM and Android credentials out of Git; staging and production credentials remain separate.
- Production stays disabled and unchanged until explicit staging acceptance.

---

### Task 1: Installation identity, consent, and event ingestion

**Files:**
- Create: `database/migrations/2026_08_18_000002_create_mobile_engagement_tables.php`
- Create: `app/Models/MobileAppInstallation.php`
- Create: `app/Models/MobileAnalyticsEvent.php`
- Create: `app/Services/MobileAnalyticsService.php`
- Create: `app/Http/Controllers/Api/Mobile/V1/InstallationController.php`
- Create: `app/Http/Controllers/Api/Mobile/V1/AnalyticsEventController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/MobileEngagementApiTest.php`

**Interfaces:**
- Produces: `MobileAnalyticsService::recordBatch(MobileAppInstallation $installation, array $events): int`
- Produces: authenticated installation endpoints using UUID plus client-secret hash.

- [ ] Write failing feature tests for create/update installation, invalid secret,
  consent-off rejection, allowlisted event batch, heartbeat, and consent-revoke deletion.
- [ ] Run `php artisan test --compact --filter=MobileEngagementApiTest` and confirm failures are missing tables/routes/classes.
- [ ] Add the migration, models, strict request validation, secret hashing, geography lookup, property sanitizer, and throttled routes.
- [ ] Re-run the focused tests and migration lifecycle until green.
- [ ] Commit with `feat: add consented mobile analytics ingestion`.

### Task 2: Admin insight aggregation and RTL dashboard

**Files:**
- Create: `app/Services/MobileInsightsService.php`
- Create: `app/Http/Controllers/Admin/MobileInsightsController.php`
- Create: `resources/views/admin/mobile-insights/index.blade.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/components/layouts/admin.blade.php`
- Test: `tests/Feature/MobileInsightsDashboardTest.php`

**Interfaces:**
- Consumes: engagement tables from Task 1.
- Produces: `MobileInsightsService::summary(CarbonInterface $from, CarbonInterface $to): array` and admin routes `admin.mobile-insights.index/live`.

- [ ] Write failing tests for admin authorization, online two-minute window,
  DAU/MAU, searches/zero-results, device/location/contact rankings, and RTL render.
- [ ] Run focused tests and verify expected missing route/service failures.
- [ ] Implement database-portable aggregates, date filters, admin-only controller,
  headline JSON refresh, and Persian dashboard cards/tables.
- [ ] Run focused and existing dashboard tests until green.
- [ ] Commit with `feat: add mobile insights dashboard`.

### Task 3: Push token storage and FCM delivery

**Files:**
- Create: `app/Models/MobilePushNotification.php`
- Create: `app/Models/MobilePushDelivery.php`
- Create: `app/Services/FcmAccessTokenProvider.php`
- Create: `app/Services/FcmClient.php`
- Create: `app/Jobs/SendMobilePushNotification.php`
- Create: `app/Http/Controllers/Api/Mobile/V1/PushTokenController.php`
- Create: `app/Http/Controllers/Admin/MobilePushController.php`
- Modify: `config/services.php`
- Modify: `.env.example`
- Modify: `routes/api.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/admin/mobile-insights/index.blade.php`
- Test: `tests/Feature/MobilePushNotificationTest.php`

**Interfaces:**
- Produces: `FcmClient::send(string $token, array $message): array`.
- Produces: token register/delete/open endpoints and admin broadcast route.

- [ ] Write failing tests for encrypted token rotation, missing credentials,
  signed OAuth request, FCM send success, invalid-token disable, admin-only
  broadcasts, delivery idempotency, and open counters.
- [ ] Run focused tests and verify missing implementation failures.
- [ ] Implement server-only credential loading, native OpenSSL JWT signing,
  cached OAuth token, HTTP v1 send, queued chunk delivery, aggregate counters,
  and Persian broadcast controls.
- [ ] Run focused tests with Laravel HTTP fakes and ensure no credential/token is logged.
- [ ] Commit with `feat: add FCM push delivery and reporting`.

### Task 4: Capacitor engagement client and consent UI

**Files:**
- Create: `mobile/js/engagement.js`
- Modify: `mobile/app.js`
- Modify: `mobile/js/api.js`
- Modify: `mobile/js/views.js`
- Modify: `mobile/css/app.css`
- Modify: `package.json`
- Modify: `package-lock.json`
- Modify generated Capacitor Android plugin files through `npx cap sync android`
- Test: `tests/mobile/engagement.test.js`

**Interfaces:**
- Consumes: APIs from Tasks 1 and 3.
- Produces: `engagement.initialize()`, `track(name, properties)`,
  `setAnalyticsConsent(bool)`, `setPushConsent(bool)`, and `recordPushOpen(id)`.

- [ ] Write failing Node tests for consent-off default, installation credentials,
  event batching/sanitization, heartbeat, retry isolation, device metadata, Push
  permission/token registration, and semantic search/contact events.
- [ ] Run `npm run test:mobile` and confirm failures are missing module behavior.
- [ ] Install locked official Capacitor Device/Push plugins, implement the module,
  wire screen/search/vehicle/contact/quote events, and add Persian consent settings.
- [ ] Run mobile tests, `npx cap sync android`, and verify generated native plugin registration.
- [ ] Commit with `feat: instrument Android engagement and push consent`.

### Task 5: Retention, documentation, and release verification

**Files:**
- Modify: `routes/console.php`
- Modify: `config/navaracar.php`
- Modify: `.env.example`
- Modify: `docs/ANDROID_V1.md`
- Modify: `docs/API_MATRIX.md` if present, otherwise document the matrix in `docs/ANDROID_V1.md`.
- Modify: `tests/Feature/StagingSafetyTest.php`

**Interfaces:**
- Consumes all prior tasks.
- Produces daily 180-day analytics cleanup and complete staging/FCM setup documentation.

- [ ] Write failing retention and configuration safety tests.
- [ ] Implement scheduled cleanup and environment-only FCM/retention settings.
- [ ] Document consent, event dictionary, dashboard, FCM setup, staging test,
  Data Safety declarations, external credentials, and production checklist.
- [ ] Run `composer validate --strict`, `composer audit --locked`, `npm ci`,
  `npm audit --audit-level=high`, `npm run test:mobile`, `npm run build`,
  `php artisan test --compact`, and `npm run test:e2e`.
- [ ] Run migration fresh/rollback/migrate, Android debug and unsigned release
  staging builds, APK alignment/signature/package/endpoint inspection, and SHA-256.
- [ ] Push the branch, require all six GitHub checks, publish a unique staging
  candidate, deploy via the staging-only service, and verify live analytics/API/admin routes.
- [ ] Confirm production deploy timer remains disabled/inactive and document the
  external FCM credential/device-delivery blocker without promoting production.
