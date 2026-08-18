# NavraCar V2 — Gap Report

Living document. Each entry: what the design wants, what the repo currently has, what was implemented, what's genuinely missing and why, evidence, and an honest follow-up (never a vague "later").

## 1. Public nav: "درخواست‌ها" (my requests) and "حساب" (account)

**Design wants:** public desktop/mobile nav with خودروها / محاسبه هزینه / درخواست‌ها / تماس با ما / حساب (DESIGN_SPEC.md §3, all reference screenshots).
**Repo has:** no public user-authentication system at all (only staff `AdminUser`; "no public registration" is an explicit existing business rule per `AGENTS.md`).
**RESOLVED (round 4, commit `b9df433`):** a real request-tracking-by-number page now exists — `GET /track` (lookup form: request number + phone) and `GET /track/{quoteRequest}` (vehicle, current pipeline stage, latest invoice/payment status, a real timeline built from creation+invoice timestamps). Lookup requires the exact phone number submitted with the original request (two-factor, same pattern couriers use), so it cannot be used to enumerate other customers' requests. Linked from the public footer rather than the header nav, so the reference's exact header nav item set is left unchanged. 6 Feature tests cover it (`RequestTrackingTest.php`).
**Still genuinely missing:** a public account/auth subsystem (registration/login/session for end users) — this remains a new subsystem, explicitly out of scope to invent under the existing "no public registration" rule without separate authorization. "حساب" stays a disabled nav placeholder with a reason.
**Evidence:** `routes/public.php`, `app/Http/Controllers/Public/RequestTrackingController.php`, `tests/Feature/RequestTrackingTest.php`.
**Follow-up:** public accounts remain a genuinely separate initiative (auth, registration flow, security review) that needs explicit product/business authorization before any implementation.

## 2. Admin dashboard calendar (meetings & calls)

**Design wants:** day/week/upcoming-list calendar of meetings/calls, prominent on both the admin and sales dashboards (DESIGN_SPEC.md §5, `02-admin-dashboard-calendar.png`).
**RESOLVED (round 4, commit `ef9d971`, explicitly authorized by the owner):** a real calendar subsystem now exists — `calendar_events` migration/`CalendarEvent` model (type, quote-request link, assignee, creator, start/end time, timezone, status, notes), `CalendarEventPolicy`, `CalendarController` (day/week/list views, create/reschedule/complete/cancel, overlap validation via `CalendarEvent::overlapping()`), and views for admin/sales. Embedded as a 7-day mini-widget on the admin dashboard and linked from the sidebar and mobile bottom nav. 15 Feature tests (`CalendarEventTest.php`) plus 1 Playwright E2E test (create-event flow, `critical-flows.spec.js`) cover it. No pricing/financial rules were touched building this, per the explicit constraint given alongside the authorization.
**Evidence:** `app/Models/CalendarEvent.php`, `app/Http/Controllers/Admin/CalendarController.php`, `resources/views/admin/calendar/*`, `tests/Feature/CalendarEventTest.php`.
**Follow-up:** none outstanding for the core subsystem.

## 3. Content-manager dashboard

**Design wants:** a content dashboard with KPIs (pending review, active listings, incomplete metadata, import errors) and a per-source review queue (DESIGN_SPEC.md §5, `04-content-dashboard.png`).
**RESOLVED (round 4, commit `71362bd`):** a new `content.role`-gated `ContentDashboardController`/`admin.content-dashboard` route/view now exists, with real KPIs from existing data (published/draft `CarListing` counts, `ImportQueueItem` counts needing review or failed, published/draft `Post` counts, active `HomeSlide` count) and recent-listings/recent-posts lists. Content managers now land here after login (previously they landed directly on the car-listings table with no overview at all) and it's linked from the sidebar.
**Same round, also resolved:** sales users previously landed on the raw requests list after login, bypassing `admin.dashboard` entirely even though it was already correctly data-scoped for them (per `SalesDashboardScopingTest`). Sales now lands on `admin.dashboard` after login too — see the "Admin dashboard: no sales-pipeline mini-view" entry below for the dashboard content itself.
**Evidence:** `app/Http/Controllers/Admin/ContentDashboardController.php`, `resources/views/admin/content-dashboard.blade.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`.
**Follow-up:** none outstanding.

## 4. Android app is a Capacitor WebView, not a native Material 3 UI

**Design wants:** "نرم‌افزار Android یک تجربه بومی Android است و WebView صرف محسوب نمی‌شود" — a native Android experience, explicitly not a plain WebView (DESIGN_SPEC.md §3, `07-android-app.png`).
**Repo has:** `capacitor.config.json` (`webDir: "mobile"`) wraps `mobile/index.html` + `mobile/app.js` — a Capacitor WebView shell, not a Kotlin/Java native UI. `android/` contains the Capacitor-generated Gradle project, not hand-written native screens.
**Implemented:** not yet reached (Phase 5 in the mission's own phase list per `IMPLEMENTATION_PLAN.md`).
**Genuinely missing:** an actual native Android UI (Kotlin/Jetpack Compose or XML+Material Components) is a different engineering discipline and toolchain than this Laravel/Blade/Tailwind session covers — this is not a restyle, it's a from-scratch native app.
**Evidence:** `capacitor.config.json`, `mobile/app.js`, `mobile/index.html`, `android/` directory listing (Phase 1).
**Follow-up:** in-scope work here is restyling the Capacitor WebView content (`mobile/*`, `public/mobile-app.blade.php`) to V2 tokens so the wrapped web content matches the approved visual language — planned for a later phase. A true native rewrite needs a dedicated Android engineering effort/team and is out of scope for this session to fabricate; flagging honestly rather than producing a fake "native-looking" WebView.

## 5. Admin dark-mode toggle removed on V2-migrated shell (Phase 3 decision)

**Design wants:** DESIGN_SPEC.md §2 declares one primary visual language — very dark navy / blue-black surfaces / cobalt accent — with no light-mode reference pictured anywhere in the approved assets.
**Repo had:** a working light/dark toggle (`$store.theme`) on the admin shell, defaulting to system preference.
**Decision:** the V2 admin shell (`resources/views/components/layouts/admin.blade.php`) no longer renders the toggle and no longer applies the `.dark` class — it always renders the approved dark-navy language. The underlying `Alpine.store('theme')` in `resources/js/app.js` was left untouched (still used by `public/mobile-app.blade.php`, unaffected by this change) since removing it entirely wasn't necessary and keeps the diff minimal.
**Why this isn't "faked":** a toggle with only one real (dark) side and a fabricated, never-designed "light" side would be worse than no toggle — it would ship an unapproved, invented light theme. This is a deliberate, documented product decision, not an oversight.
**Follow-up:** if a light mode is ever wanted for V2, it needs an actual approved design reference first, not an improvised one.

## 6. Admin header notification bell and unified search

**Design wants:** a notification bell with an unread-count badge, and a search box, in the admin header on every dashboard variant (`02-admin-dashboard-calendar.png`, `03-sales-dashboard.png`, `04-content-dashboard.png`).
**Repo has:** no notification/alert model or delivery mechanism anywhere in `app/Models`. A real, working `?q=` filter exists on `admin.requests.index` (`RequestController.php:38,330`); no equivalent exists on `CarListingController` (content search) or anywhere that spans customers+proformas+content at once the way the reference implies.
**Implemented (Phase 3 remediation):** the bell icon is now rendered in the shell — matching the reference's visual composition — but with **no badge/count**, since a fabricated unread number would be exactly the kind of fake sample data the mission rules forbid. The search box is wired to the one real search that exists (`admin.requests.index?q=`).
**Genuinely missing:** a notification system (events, delivery, read/unread state) and a unified cross-entity search endpoint. Both are backend subsystems, not restyles.
**Evidence:** `app/Models/*` listing (Phase 1), `grep` for `'q'` across `app/Http/Controllers/Admin/*.php`.
**Follow-up:** out of scope to invent here. If wanted, needs its own scoped backend task (what counts as a notification, retention, read state for a notification system; which entities and how for search) before any UI is built against it.

## 7. Public vehicle-list filter/search bar

**Design wants:** a single search box (search by brand/model/code) plus brand/model/year/engine-volume/fuel-type/price-range as **combinable** filters with sort, on the vehicle list page (DESIGN_SPEC.md §4 "فهرست خودروها", `01-public-desktop-system.png` right panel).
**Repo has:** `CarPriceController` exposes brand/category/price-bracket only as **separate routes** (`/car-prices/brand/{make}`, `/car-prices/category/{id}`, `/car-prices/price/{bracket}`), not as combinable query-string filters, and has no free-text search, no year/engine/fuel filters, and no sort parameter at all.
**Implemented (Phase 3 round 2):** `car-prices/index.blade.php` was restyled to V2 tokens using exactly the filter chips that exist today (brand, category, price bracket) in the reference's chip visual style. No search input or extra filter dropdowns were added to the markup, since they would submit to nothing.
**Implemented (Phase 3 round 3 — RESOLVED for search + sort):** the owner's round-2 rejection specifically flagged the missing search/sort bar as a structural gap on this page, so it was built for real: `CarPriceController::renderIndex` now accepts `q` (matches title_fa/make/model/slug) and `sort` (newest/price_asc/price_desc) query params, and the view has a real search input + sort `<select>` wired to them. This works on the base index and on the brand/category/price-bracket filtered variants via `withQueryString()`.
**Still genuinely missing:** year/engine-volume/fuel-type as combinable filters — `CarListing` has no dedicated indexed columns wired for this kind of query yet (fuel_type exists as a free-text field, not a filterable enum with a UI dropdown), and building that combinable multi-dimension filter bar (plus deciding how it interacts with the existing brand/category/price *routes*, which are a different mechanism) is real Phase 4 scope, not a quick addition.
**Evidence:** `app/Http/Controllers/Public/CarPriceController.php`, `routes/public.php`.
**Follow-up:** the remaining filter dimensions are Phase 4 scope as originally planned.

## 8. Admin dashboard: no sales-pipeline mini-view

**Design wants:** the main admin dashboard (`02-admin-dashboard-calendar.png`) includes a condensed sales-pipeline column view (stage counts + a few request cards per stage) alongside the KPIs and calendar.
**RESOLVED (round 4, commit `bf0225b`):** `DashboardController` now computes `pipelineByStage` (real `PipelineStage`/`QuoteRequest` data, scoped the same way as the kanban page, grouped to stage counts + first 3 sample leads per non-empty stage), rendered as a horizontal mini-kanban card on the dashboard linking to the full `/admin/kanban`. The unreferenced "فرم عمومی ثبت تماس فروش" CTA banner (not present in the reference composition) was also removed from the top of the dashboard in the same pass — the link remains in the sidebar footer.
**Evidence:** `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`.
**Follow-up:** none outstanding.

## 9. Pricing bug found and fixed during round-4 re-verification (self-disclosed)

While re-verifying the vehicle-detail page against `DESIGN_SPEC.md`'s "only 3 cost categories" rule, two real correctness bugs from the round-3 redesign were found and fixed in the same round (commit `419a872`), not carried forward silently:

1. `CarListing::pricingTotals()` constructed `VehiclePricingInput` directly with `customsPriceAed` hardcoded to the full `price_aed` (i.e. 0% discount), instead of going through `VehiclePricingService::inputFromArray()`, which applies the real `suggestCustomsPrice()` default discount when a listing has no explicit `customs_price_aed`. This overstated the estimated landed cost shown on both the vehicle-list and vehicle-detail pages for every listing without an explicit customs price (which is most seeded/imported listings).
2. The vehicle-detail page's 3-category cost summary read the raw `customsSubtotalToman` (service fee excluded entirely) instead of `VehiclePricingResult::publicDisplaySummary()`, the tested contract (`PublicCostDisplayTest`) that folds the service fee into the clearance-total row so the 3 publicly-shown categories still sum to the real grand total. The previous number under-stated the clearance-cost category by the hidden service-fee amount.

**Fix:** `CarListing::pricingTotals()` now goes through `inputFromArray()` (fixes bug 1); a new `CarListing::publicPricingSummary()` wraps `publicDisplaySummary()` and is now what the vehicle-detail page reads (fixes bug 2). Verified with a live curl check comparing the rendered page's numbers against an independent `tinker` computation using the same real listing/settings, plus the full PHPUnit and Playwright suites (both green).
**Evidence:** `app/Models/CarListing.php`, `app/Services/VehiclePricing/VehiclePricingResult.php`, `tests/Feature/PublicCostDisplayTest.php`, `tests/Feature/VehiclePricingEngineTest.php`.
**Follow-up:** none outstanding — flagged here per the standing "never silently duplicate or drift from `VehiclePricingService`" rule, since this is exactly the kind of drift that rule exists to catch.
