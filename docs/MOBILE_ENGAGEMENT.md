# Android analytics, live presence, and Push

## Boundary and architecture

This release adds engagement capabilities to the existing Capacitor Android
client and the existing Laravel application. It does not add another backend,
database, pricing engine, CRM, or admin. Analytics is first-party and stored in
the shared database; Push is sent from Laravel through Firebase Cloud Messaging
(FCM) HTTP v1.

```text
Android 1.1.0
  ├─ explicit analytics consent (off by default)
  ├─ explicit notification consent + Android permission (off by default)
  ├─ Keystore-encrypted installation secret and customer bearer token
  └─ HTTPS event batches / encrypted FCM token
       └─ existing Laravel + database
            ├─ two-minute live presence and 30-day insight aggregation
            ├─ existing IP-to-country/city lookup (raw IP not retained here)
            ├─ RTL admin dashboard and Push history
            └─ FCM HTTP v1 job using server-only credentials
```

The installation UUID is random and is not a hardware or advertising ID. Its
43-character secret is kept in the existing Android Keystore bridge and only a
SHA-256 digest is stored by Laravel. FCM tokens use Laravel's encrypted cast;
only a SHA-256 token hash is queryable. No IMEI, MAC address, serial number,
advertising ID, contacts, GPS, phone, email, password, customer token, or VIN is
accepted in analytics event properties.

## Consent and retained data

| Control | Default | Data while enabled | Revocation |
|---|---:|---|---|
| Anonymous usage analytics | Off | semantic event, page, allowlisted vehicle/search/filter attributes, manufacturer/model, Android/app version, locale, approximate country/city | deletes that installation's events immediately and clears presence |
| Push Notification | Off | encrypted FCM token and delivery/open state | removes the token immediately; no further targeting |

Analytics events expire after `MOBILE_ANALYTICS_RETENTION_DAYS` (180 by
default). `mobile:prune-engagement` runs daily at 03:30 through the normal
Laravel scheduler. Online means a consented installation whose server-side
`last_seen_at` is within the last two minutes; the app heartbeat is 60 seconds.
Approximate geography is derived by the existing `GeoLookupService`; the new
engagement tables do not store raw IP.

Event names are `app_open`, `heartbeat`, `screen_view`, `search`,
`vehicle_view`, `favorite`, `share`, `pricing_calculate`, `quote_submit`,
`contact_click`, `whatsapp_click`, and `phone_click`. Both client and server use
allowlists. Search values resembling a phone number, email, VIN, token-sized
value, or other personal content are removed before sending; forbidden property
keys are rejected by the API.

## API matrix

All paths reuse the existing API origin. Installation-authenticated requests
send `X-Navracar-Installation` and `X-Navracar-Installation-Secret`. An optional
existing mobile bearer token links an installation to its customer without
granting admin access.

| Method and path | Auth | Purpose |
|---|---|---|
| `PUT /api/mobile/v1/installations/{uuid}` | secret on create; installation headers thereafter; optional bearer | create/update safe device and acquisition metadata |
| `PATCH /api/mobile/v1/installations/{uuid}/consent` | installation headers | independently enable/revoke analytics or Push |
| `POST /api/mobile/v1/analytics/events` | installation headers + analytics consent | accept up to 25 allowlisted events |
| `POST /api/mobile/v1/installations/{uuid}/push-token` | installation headers + Push consent | encrypt/rotate one FCM token |
| `DELETE /api/mobile/v1/installations/{uuid}/push-token` | installation headers | revoke the token |
| `POST /api/mobile/v1/push/opened/{notification}` | installation headers | count a delivery open idempotently |
| `GET /admin/mobile-insights` | existing admin session + admin role | RTL insight dashboard, Push composer/history |
| `GET /admin/mobile-insights/summary` | existing admin session + admin role | 30-second online-count refresh JSON |
| `POST /admin/mobile-insights/push` | existing admin session + admin role | enqueue a consented all-installations broadcast |

All vehicle, pricing, QuoteRequest, customer, favorite, and shared-listing APIs
remain as documented in `docs/ANDROID_V1.md`; no business formula or setting is
present in the APK.

## Dashboard inventory

`/admin/mobile-insights` displays online now, installations, active
installations, analytics opt-ins, Push opt-ins, event volume, searches, phone
manufacturer/model, approximate city/country, acquisition source, WhatsApp /
phone / contact behavior, event ranking, and Push delivery/open history. The
headline online count refreshes every 30 seconds. Only the existing `admin`
role can access the page; sales and content roles receive HTTP 403.

## FCM staging configuration

Two separate external inputs are required for real device delivery:

1. Put the staging Android `google-services.json`, containing the staging
   package `com.navracar.mobile.staging`, at `android/app/google-services.json`
   only for the protected build. The file is gitignored.
2. Put a least-privilege Firebase service-account JSON outside the web root on
   the staging server and configure only its absolute path and project ID:

```dotenv
FIREBASE_PROJECT_ID=the-staging-firebase-project
FIREBASE_CREDENTIALS=/protected/path/navracar-staging-firebase.json
```

Never put the service-account JSON, private key, FCM token, keystore, or signing
password in Git, the APK, Laravel logs, or public storage. The server signs a
short-lived OAuth JWT with native OpenSSL and caches only the returned access
token. If either setting is absent, notification creation is recorded as
`disabled`; core Android screens and APIs continue normally.

The queue worker and scheduler must be running on staging:

```bash
php artisan queue:work --tries=3 --timeout=120
php artisan schedule:run
```

## Build and test

Requirements are Node 22+, JDK 21, Android SDK 36, PHP 8.3, and Composer 2.

```bash
composer install
npm ci
npm run test:mobile
npm run build
php artisan test --compact
npx cap sync android
bash tools/build-android-variants.sh
```

The CI artifact `navracar-android-builds` contains:

- `android/app/build/outputs/navracar-staging-debug.apk`
- `android/app/build/outputs/navracar-staging-release-unsigned.apk`
- `android/app/build/outputs/navracar-production-debug.apk`

Version 1.1.0 staging is `com.navracar.mobile.staging` and points only to
`https://staging.nezamparvar.com`. The unsigned release is deliberately not a
production-signed distributable.

## Staging acceptance

1. Deploy the exact green source SHA to staging and run migrations.
2. Verify installation registration, consent off/on/revoke, event batching, and
   admin access; confirm revoke deletes events.
3. With a real staging device, verify Android notification permission, token
   registration, foreground/background delivery, safe internal destination,
   delivery/open counts, and token removal after uninstall/FCM rejection.
4. Verify online changes within two minutes, searches/device/location/contact
   rankings, RTL and large-number formatting, and absence of personal data.
5. Confirm existing listing, pricing, QuoteRequest, requests, favorites, share,
   offline/retry/auth-expiry, and contact flows still pass.
6. Confirm Production deploy services and Production credentials were not used.

## Known external blockers and production checklist

Real Push delivery cannot be claimed without the protected staging Firebase
files and a physical/emulated device. Local/CI builds without these inputs are
expected to compile with Push unavailable, not fail. Precise location and
cross-device identity are intentionally not collected.

- [ ] Owner accepts the exact staging SHA and APK.
- [ ] Staging Firebase package/project/service account and FCM device delivery are verified.
- [ ] Google Play Data Safety and privacy text reflect the consented data above.
- [ ] Retention scheduler, queue worker, backups, TLS, rate limits, and rollback are verified.
- [ ] A protected Production Firebase project and `google-services.json` are supplied separately.
- [ ] A protected Production keystore signs an APK/AAB rebuilt from the accepted SHA.
- [ ] Production deployment occurs only after a new explicit user approval.
