# Navracar Mobile Engagement, Analytics, and Push Design

## Objective

Add privacy-conscious first-party mobile analytics, near-real-time presence,
device and acquisition reporting, contact/search conversion reporting, and FCM
push notifications to the existing Navracar Android application and Laravel
admin. The existing Laravel backend, database, authentication, CRM, pricing
engine, and release pipeline remain authoritative and shared with the website.

## Product scope

The Android client records consented `app_open`, `heartbeat`, `screen_view`,
`search`, `vehicle_view`, `favorite`, `share`, `pricing_calculate`,
`quote_submit`, `contact_click`, `whatsapp_click`, and `phone_click` events.
Each event is associated with a random app-scoped installation identifier and,
when logged in, the existing mobile customer. Search events record the trimmed
query, active filters, result count, and whether results were empty. Contact
events record the source screen, vehicle slug, campaign/referrer values, and
contact channel.

An installation heartbeat updates `last_seen_at`. The admin treats installations
seen in the previous two minutes as online. The dashboard reports online users,
total consented installations, daily/monthly active users, authenticated users,
push-enabled installations, top searches, zero-result searches, popular
vehicles/pages, device models, OS/app versions, countries/cities, acquisition
sources, contact channels, and a conversion funnel.

Push supports request-status messages, vehicle/price announcements, and manual
admin broadcasts. The client registers an FCM token only after explicit Android
notification permission. The server stores token ciphertext plus a SHA-256
lookup hash, sends through FCM HTTP v1, and records targeted, sent, failed,
opened, and disabled counts. Missing FCM credentials disable sending cleanly
without affecting analytics or the rest of the app.

## Privacy and security

- Analytics and Push have separate explicit opt-ins and can be disabled later.
- No IMEI, MAC address, serial number, contacts, precise GPS, advertising ID,
  microphone, camera, or call-log permission is collected.
- Device data is limited to manufacturer/model, Android version, app version,
  locale, and platform.
- Location is approximate country/city derived by the existing backend IP
  lookup. Raw IP is not stored in the new analytics tables.
- Installation identity is a random UUID plus a random client secret; the server
  stores only its SHA-256 hash. Event and token APIs require both values.
- FCM tokens use Laravel's encrypted cast and a separate lookup hash. Tokens,
  credentials, and event payloads are never logged.
- Event names and properties are allowlisted, length-limited, depth-limited,
  and rate-limited. Passwords, access tokens, VINs, phone numbers, and emails
  are rejected from analytics properties.
- Analytics events expire after 180 days by default. Revoking analytics consent
  deletes that installation's events. Disabling Push deletes its token.
- Privacy copy and Google Play Data Safety declarations must disclose analytics,
  approximate location, device/app identifiers, and developer communications.

## Architecture

### Laravel data model

`mobile_app_installations` stores installation identity, optional customer,
consents, device/app fields, approximate geography, acquisition metadata,
`last_seen_at`, and encrypted FCM token state. `mobile_analytics_events` stores
allowlisted events and JSON properties. `mobile_push_notifications` stores
message and aggregate delivery state; `mobile_push_deliveries` stores one row
per targeted installation without duplicating plaintext tokens.

### APIs

- `PUT /api/mobile/v1/installations/{uuid}` registers or refreshes an
  installation and returns current consent/server configuration.
- `PATCH /api/mobile/v1/installations/{uuid}/consent` changes analytics/Push
  consent and performs immediate deletion required by revocation.
- `POST /api/mobile/v1/analytics/events` accepts up to 25 consented events.
- `POST /api/mobile/v1/installations/{uuid}/push-token` registers/rotates a
  permitted token; `DELETE` disables Push.
- `POST /api/mobile/v1/push/opened/{notification}` records an open event using
  installation authentication.

Installation requests use `X-Navracar-Installation` and
`X-Navracar-Installation-Secret`. Logged-in requests additionally associate the
existing `mobile.auth` customer without requiring login for anonymous metrics.

### Android client

The existing Capacitor stack adds the official Device and Push Notifications
plugins. A small engagement module owns installation credentials, consent,
event batching, heartbeat, native device metadata, Push registration, and
notification-open deep links. Existing views emit semantic events and show a
Persian privacy/notification settings card. Analytics failure is non-blocking
and never prevents primary navigation, pricing, login, or quote submission.

### Admin

An admin-only `Mobile insights` page shows RTL metric cards, trends, ranked
tables, funnels, and Push history. A lightweight JSON endpoint refreshes online
and headline counts every 30 seconds. A Push form targets all Push-enabled
installations or selected country/app-version segments and dispatches a queued
job. Sales/content roles do not receive analytics or broadcast access.

### FCM configuration

FCM uses HTTP v1 with a server-only service-account JSON path and optional
project-id override. No credential or `google-services.json` is committed. The
Android build applies Google Services only when the environment-specific file
is provisioned by protected CI/server context. Staging and production Firebase
projects/credentials remain separate.

## Failure behavior

Analytics queues in memory/local storage and retries a bounded batch after the
next successful bootstrap. Invalid or revoked installation credentials cause a
local installation identity rotation. Push registration and delivery failures
update admin-visible status but do not fail user workflows. FCM 404/410 token
responses disable the affected token. Admin broadcasts are idempotent per
delivery row and safe to retry.

## Verification

Feature tests cover installation authentication, consent revocation, event
validation, heartbeat/online calculation, search/contact/device aggregation,
token encryption/rotation, FCM success/failure, authorization, and dashboard
rendering. Mobile tests cover consent defaults, batching, event payloads,
heartbeat, Push permission/token registration, and failure isolation. Existing
PHP, mobile, frontend, E2E, Android debug/release, migration lifecycle, security,
and staging checks must remain green before a new staging candidate is deployed.

## Release boundary

The new candidate is deployed only to `https://staging.nezamparvar.com`.
Production remains disabled and unchanged until explicit owner acceptance of
the exact staging artifact. Real Push delivery remains externally blocked until
staging Firebase credentials and an Android `google-services.json` are securely
provisioned.
