# NavraCar Android V1 Design

## Authority and scope

This implementation is governed by the immutable design reference
`1cdab114920cdc2431f983a1c1ea9efb88e26f82`, especially
`docs/design-v2/DESIGN_SPEC.md`, `docs/design-v2/IMPLEMENTATION_PLAN.md`, and
`docs/design-v2/assets/07-android-app.png`. Written requirements override image
decoration and fixture data. Production promotion is explicitly excluded.

The Android client remains the repository's existing Capacitor 8 application:
local packaged UI in `mobile/`, native Android integration in `android/`, and
the existing Laravel application as the only backend, database, pricing engine,
QuoteRequest workflow, and CRM.

## Product architecture

- Four primary destinations: Home, Vehicles, Requests, Account.
- Pricing is an in-context flow opened from Home or a vehicle, not a fifth
  primary destination.
- Persian and RTL are first-class. Latin vehicle identifiers, VINs, phone
  numbers, AED/IRR values, and URLs are isolated with `dir="ltr"` where needed.
- The UI uses the approved V2 tokens: `#020B18`, `#061426`, `#0A1B32`,
  `#1677FF`, `#20C7E9`, `#F8FAFC`, `#9AAAC1`, and `#1A3554`; 4/8/12/16/24/32
  spacing; 12-16 px cards; and 48 px minimum controls.
- The packaged client contains no pricing rules, rates, API credentials, or
  production secrets. All financial results come from
  `VehiclePricingService` through the central API.

## Data and API boundaries

The current pricing endpoint is reused. New `/api/mobile/v1` endpoints expose
only published listings, mobile bootstrap/contact data, customer account data,
favorites, customer-owned requests, and a guarded shared-listing intake. Staff
accounts remain in `admin_users`; customer credentials and opaque hashed bearer
tokens use separate tables and never grant CRM access.

Quote creation continues through the existing `QuoteController` workflow. A
customer ID is attached when a valid mobile token is present, allowing the
Requests screen to show only that customer's records. Guest quote submission
remains supported.

## Security

- Customer passwords use Laravel hashing.
- Bearer secrets are generated randomly, returned once, and stored only as a
  SHA-256 digest; tokens expire and can be revoked on logout.
- Mobile token resolution is separate from the web/session guard.
- Shared marketplace URLs are HTTPS-only and allowlisted for Dubizzle,
  DubiCars, and YallaMotor. No cookies, headers, credentials, or arbitrary HTML
  are accepted from the Android app.
- The client stores only the opaque token. Native secure-storage integration is
  preferred when available; browser local storage is a documented fallback for
  the packaged web runtime and must never contain other secrets.

## Screen inventory

1. Home: V2 hero/search, supported calculation entry points, featured vehicles,
   exchange-rate freshness, contact actions, and offline/retry states.
2. Vehicles: search, supported filters/sorts, loading skeleton, empty/error,
   pagination, favorites, and responsive two-column cards.
3. Vehicle detail: gallery, specs, AED/IRR values, the three public pricing
   blocks only, calculate, quote request, share, and image fallback.
4. Pricing: server-authoritative category and price input with three approved
   public totals and freshness metadata.
5. Quote request: validated contact/details form and CRM record creation.
6. Requests: authenticated customer request history and status/timeline summary.
7. Account: register, login, profile edit, logout, favorites, legal/contact
   links, and push-notification readiness notice.
8. Shared listing: receives Android `SEND` text/deep-link URL, validates the
   marketplace, and submits it to the existing review/import workflow.

## Failure behavior

Every data screen has same-size loading skeletons, a Persian empty state, an
actionable error with retry, authentication-expired handling, and offline
messaging. Failed network operations are never presented as successful cached
results. Images receive an accessible fallback.

## Testing and release

Backend feature tests cover listing mapping/filtering, auth/token isolation,
favorites, customer request ownership, shared URL guarding, quote linkage, and
pricing reuse. Node tests cover number/direction formatting, filters, token
handling, reducer/navigation behavior, and error normalization. Playwright
captures the required RTL screens with deterministic API fixtures. Android
builds produce separated staging and production debug artifacts, while only the
staging artifact is presented for acceptance. Production remains unmodified.
