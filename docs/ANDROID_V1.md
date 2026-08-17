# NavraCar Android V1

## Release boundary

Android V1 is a stage-first extension of the existing NavraCar product. It uses
the repository's established Capacitor 8 project (`mobile/` + `android/`) and
the existing Laravel application. It does not introduce another backend,
database, pricing engine, QuoteRequest pipeline, CRM, or admin panel.

The immutable visual authority is commit
`1cdab114920cdc2431f983a1c1ea9efb88e26f82`, specifically:

- `docs/design-v2/README.md` — product and Android direction
- `docs/design-v2/DESIGN_SPEC.md` — tokens, RTL, accessibility, components
- `docs/design-v2/IMPLEMENTATION_PLAN.md` — Android phase and quality gates
- `docs/design-v2/assets/07-android-app.png` — approved visual reference

The existing Android functional scope is in the Android sections of those
files and in the repository `README.md`; no guessed Android document name was
used. The requested four-item navigation (Home, Vehicles, Requests, Account)
takes precedence over the five-item illustrative image.

Production has not been deployed or modified. Promotion requires explicit
approval after staging acceptance.

The V2 reference describes native Material 3 screens, while the repository and
task explicitly require reuse of the already-approved Capacitor stack and
prohibit an unnecessary second mobile stack. V1 therefore packages its UI
locally in Capacitor and adds native Android Keystore, Share target, intent, and
deep-link integration; it is not a remote-site wrapper. Its controls reproduce
the V2 Material-like tokens and dimensions, but they remain HTML/CSS rather than
Android Material widgets. A full native-widget migration is a documented future
architecture decision, not silently represented as complete here.

## Architecture

```text
Capacitor Android app
  ├─ packaged Persian RTL UI (mobile/)
  ├─ Android Keystore encrypted bearer-token bridge
  ├─ Android SEND/deep-link bridge
  └─ HTTPS JSON
       └─ existing Laravel application
            ├─ published Vehicle inventory
            ├─ VehiclePricingService and central settings
            ├─ QuoteRequest and existing CRM/admin workflow
            ├─ mobile customer/token/favorite tables
            └─ existing import_queue review workflow
```

Customer accounts are deliberately separate from `admin_users`. Opaque mobile
tokens are returned once as `id|secret`, stored as SHA-256 digests on the
server, expire after 90 days, and grant no admin/CRM access. On Android, the
token is encrypted with an AES/GCM key held by Android Keystore; the packaged
client contains no API, signing, pricing, or service secrets.

## Screen inventory

| Screen | Delivered behavior |
|---|---|
| Home | V2 hero/search, four quick actions, featured vehicles, central rate freshness, contact action |
| Vehicles | Published inventory, search, make/fuel/year/AED filters, three sorts, pagination, empty/retry/image states |
| Vehicle detail | Gallery fallback, mixed-direction specs, AED/IRR, the three approved public cost blocks, quote/calculation CTAs |
| Pricing | Sends price/category to the existing central endpoint; renders only server-calculated public totals |
| Quote request | Guest or authenticated submission into the existing QuoteRequest/CRM workflow |
| Requests | Authenticated, customer-owned request history and status summary |
| Account | Register/login/logout, profile update, requests, favorites, existing web/contact links, push-readiness notice |
| Favorites | Server-backed after login; documented local-only fallback for guests |
| Share to NavraCar | Android text share/deep link for supported HTTPS marketplace URLs into the existing review queue |

Primary navigation contains exactly Home, Vehicles, Requests, and Account.
Pricing, quote, favorites, and share are contextual routes.

## API matrix

| Method and path | Auth | Source/reuse | Android use |
|---|---|---|---|
| `GET /api/mobile/v1/bootstrap` | Public | Existing settings, published vehicles, contact config | Categories, rates, featured vehicles, contact |
| `GET /api/mobile/v1/vehicles` | Public | Existing `Vehicle` records/scopes | Search, filters, sort, pagination |
| `GET /api/mobile/v1/vehicles/{slug}` | Public | Existing vehicle plus `VehiclePricingService` | Detail/specs/gallery/public pricing |
| `POST /api/vehicle-pricing/calculate` | Public | Existing endpoint unchanged | Authoritative import calculation |
| `POST /api/mobile/v1/quote-requests` | Optional bearer | Existing `QuoteController` and `QuoteRequest` | Create the same CRM record; attach customer when present |
| `POST /api/mobile/v1/auth/register` | Public | New mobile customer boundary | Customer registration and opaque token |
| `POST /api/mobile/v1/auth/login` | Public | New mobile customer boundary | Customer login and opaque token |
| `POST /api/mobile/v1/auth/logout` | Bearer | New token revocation | Logout |
| `GET/PATCH /api/mobile/v1/account` | Bearer | Mobile customer | Profile read/update |
| `GET /api/mobile/v1/requests` | Bearer | Existing QuoteRequest records | Customer-owned history only |
| `GET /api/mobile/v1/favorites` | Bearer | New pivot to existing vehicles | Synced favorites |
| `PUT/DELETE /api/mobile/v1/favorites/{slug}` | Bearer | New pivot to existing vehicles | Add/remove favorite |
| `POST /api/mobile/v1/shared-listings` | Bearer | Existing `import_queue` | Allowlisted URL capture for server review |

Backend additions are limited to the mobile customer/token/favorite persistence,
customer linkage on `quote_requests`, serializers/controllers, middleware, and
routes above. No financial formula, business setting, import parser, or CRM
workflow was duplicated.

## Design-token mapping and RTL

| V2 token/pattern | Android implementation |
|---|---|
| Background `#020B18` | app/body/safe-area background |
| Surface `#061426`; raised `#0A1B32` | cards, bottom navigation, inputs |
| Primary `#1677FF`; cyan `#20C7E9` | primary CTA, active state, accents |
| Text `#F8FAFC`; muted `#9AAAC1` | primary/secondary typography |
| Border `#1A3554` | cards, controls, separators |
| 4/8/12/16/24/32 spacing | compact mobile spacing scale |
| 12–16 px radius; 48 px control minimum | cards, buttons, form controls |
| Dark Material-like bottom navigation | fixed four-item safe-area navigation |

The root document is Persian `lang="fa" dir="rtl"`. Vehicle names, phone
numbers, URLs, kilometres, cc, and AED/IRR values use isolated LTR runs. Persian
and Arabic input digits are normalized before API calls. Large amounts are
rendered with Persian grouping separators. No CSS transforms are used to fake
RTL.

## Environment and setup

Requirements: PHP 8.3, Composer, Node.js 22, JDK 21, Android SDK/Build Tools 36,
and the normal Laravel environment configuration.

```bash
composer install
npm ci
php artisan migrate
npx cap sync android
```

The checked-in `mobile/index.html` targets production only as the repository
default. A staging package is generated transiently with:

```bash
./tools/build-android-variants.sh
```

For the acceptance build, the packaged meta configuration is set to:

```text
environment: staging
API base: https://navracar.com/staging
applicationId: com.navracar.mobile.staging
label: ناوراکار Staging
version: 1.0-staging (2)
```

The build script restores the source HTML after packaging. Production and
staging package IDs can coexist, and no environment credential is committed.

## Tests and verified commands

The feature suite covers bootstrap/listing mapping and filtering, login/token
isolation, favorites, request ownership, quote linkage, shared-URL allowlisting,
and reuse of server pricing. Node tests cover query/error behavior, secure-store
adapter behavior, number/direction formatting, routes, and auth expiry. The
Playwright fixture test traverses and captures the complete RTL screen set.

```bash
php artisan test --compact tests/Feature/MobileApiV1Test.php
php artisan test --compact
node --test tests/mobile/api.test.js tests/mobile/format.test.js tests/mobile/state.test.js
npx playwright test --config=playwright.android.config.js
cd android && ./gradlew testDebugUnitTest --no-daemon
cd android && ./gradlew assembleDebug assembleRelease -PstagingBuild=true --no-daemon
```

APK verification uses Build Tools 36 `aapt`, `apksigner`, and `zipalign`.
Expected release behavior is an aligned but unsigned release APK; production
signing material is intentionally absent. The debug APK is signed only with the
standard Android debug certificate.

## Staging acceptance

The staging package is built and internally points to the canonical repository
staging base. A live HTTP probe from the build runner on 2026-08-18 could not
resolve `navracar.com` (`curl` status `000`), so online API/CRM delivery is not
claimed. The deployment steps below remain the external acceptance boundary.

1. Apply migrations and this branch to an isolated staging deployment.
2. Confirm `https://navracar.com/staging/api/mobile/v1/bootstrap` returns JSON
   without a web Basic-Auth challenge.
3. Install the staging debug APK alongside any production app.
4. Exercise listing/detail/pricing/guest quote, then register/login and exercise
   profile/requests/favorites/share.
5. Confirm the created quote appears in the existing staging CRM and its pricing
   matches the web calculation for identical inputs.
6. Confirm no production record was created and the staging queue/log is clean.

## Known limitations and external requirements

- The repository exposes existing Home and Blog content but no canonical
  About, Terms, or Privacy content endpoint/page. Android links only to existing
  web sources and does not duplicate legal copy. Those three canonical web/API
  sources must be supplied before claiming the legal-content portion complete.
- The existing Capacitor stack is retained to avoid a second Android stack. The
  UI is packaged locally and has native security/share/deep-link bridges, but
  its screen widgets are not a full native Material 3 implementation.
- Push is an Android/backend integration foundation only. FCM sender credentials,
  device-token endpoints, consent UI, and server events are not present and were
  not invented as a new messaging platform.
- Guest favorites stay on one installation and are not merged automatically
  after login; authenticated favorites are server-backed.
- Shared links are accepted only for Dubizzle, DubiCars, and YallaMotor HTTPS
  hosts and enter the existing review/import queue. The APK does not scrape
  sessions or embed marketplace credentials.
- The release APK is intentionally unsigned. A protected production keystore
  and CI signing step are required after explicit production approval.
- Emulator/device installation and real FCM delivery require external devices
  and credentials and are separate from the successful local build/unit/browser
  verification.

## Production promotion checklist (not executed)

- [ ] User explicitly approves the accepted staging candidate.
- [ ] Staging database migration, API, CRM quote, favorites, and shared-link
      queue are verified with staging records only.
- [ ] Device installation, Persian fonts/RTL, deep links, Share target, offline
      handling, and supported Android API levels are manually accepted.
- [ ] Canonical About/Terms/Privacy sources and FCM requirements are resolved or
      consciously deferred.
- [ ] Protected CI variables provide the production signing keystore; no key or
      password is committed.
- [ ] Production API TLS, rate limits, backups, and rollback plan are confirmed.
- [ ] A production APK/AAB is rebuilt from the accepted SHA, signed, verified,
      and checksum-recorded.
- [ ] Production deployment is performed in a separate approved change window.
