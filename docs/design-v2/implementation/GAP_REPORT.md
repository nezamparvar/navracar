# NavraCar V2 — Gap Report

Living document. Each entry: what the design wants, what the repo currently has, what was implemented, what's genuinely missing and why, evidence, and an honest follow-up (never a vague "later").

## 1. Public nav: "درخواست‌ها" (my requests) and "حساب" (account)

**Design wants:** public desktop/mobile nav with خودروها / محاسبه هزینه / درخواست‌ها / تماس با ما / حساب (DESIGN_SPEC.md §3, all reference screenshots).
**Repo has:** no public user-authentication system at all (only staff `AdminUser`; "no public registration" is an explicit existing business rule per `AGENTS.md`), and no request-tracking-by-number lookup page — `public.lead-form` only *submits* a request, it doesn't look one up.
**Implemented (Phase 3):** the shell nav links to the four real destinations that exist — خودروها (`public.car-prices.index`), محاسبه هزینه (`public.calculator`), a real "ثبت درخواست" link (`public.lead-form`, submit — not track), and وبلاگ (`public.blog.index`). "تماس با ما" links to a real `#contact` anchor in the footer (existing phone numbers), not a fabricated page. "حساب" was **not** added — there is nothing to link it to.
**Genuinely missing:** (a) a request-tracking page/route (`GET /quote-requests/{number}` equivalent, public-facing, showing status/timeline like the reference's "درخواست‌ها" panel) — this needs a new controller + view, not just a restyle, since no such route exists in `routes/public.php`. (b) a public account/auth subsystem — this is a new subsystem (registration/login/session for end users), explicitly out of scope to invent under the existing "no public registration" rule without separate authorization.
**Evidence:** `routes/public.php` (read in full during Phase 1 inventory), `AGENTS.md` "no public registration" line, `docs/design-v2/implementation/PAGE_INVENTORY.md` section A.
**Follow-up:** building the request-tracking page is realistic scope for Phase 4 (it reuses existing `QuoteRequest` data — no new subsystem, just a new route + controller + view) and will be added there. Public accounts are a genuinely separate initiative (auth, registration flow, security review) that needs explicit product/business authorization before any implementation — not assumed here.

## 2. Admin dashboard calendar (meetings & calls)

**Design wants:** day/week/upcoming-list calendar of meetings/calls, prominent on both the admin and sales dashboards (DESIGN_SPEC.md §5, `02-admin-dashboard-calendar.png`).
**Repo has:** no calendar/event/meeting model, controller, or view anywhere. `LeadActivity` logs past actions on a lead; it is not a schedulable calendar event with a time slot, type, or conflict rules.
**Implemented:** nothing yet — this is shell/nav phase; the dashboards themselves are Phase 5.
**Genuinely missing:** the entire calendar subsystem — a migration for an events table (type, request link, owner, time, status, notes), a controller (day/week/list views, create/edit/reschedule/cancel/complete, conflict detection, timezone display), and views for both admin and mobile. This is a real new feature, not a UI restyle.
**Evidence:** `app/Models/*` (full listing, Phase 1), `routes/admin.php` (no calendar routes), `PAGE_INVENTORY.md` section C.
**Follow-up:** out of scope for this UI-implementation mission to invent unilaterally — it's a genuine new backend subsystem (data model + business rules like conflict prevention) that needs its own design/authorization pass, not something a UI mission should silently create. Recommend a separate, explicitly-scoped follow-up task once the product owner confirms the calendar's data model and conflict rules.

## 3. Content-manager dashboard

**Design wants:** a content dashboard with KPIs (pending review, active listings, incomplete metadata, import errors) and a per-source review queue (DESIGN_SPEC.md §5, `04-content-dashboard.png`).
**Repo has:** `admin.dashboard` (the only dashboard route) is gated by `sales.role` middleware, which `content_manager` does not satisfy (`AdminUser::canManageSales()` is `isAdmin() || isSales()`) — a content_manager hitting `/admin/` gets a 403 today, V1 and V2 alike. `CarListingController`/`ImportQueueController` exist and have the underlying data to power the KPIs.
**Implemented:** nothing yet — Phase 5 scope.
**Genuinely missing:** a new `content.role`-gated dashboard route/controller/view. Unlike the calendar, this does **not** require new data — `CarListing` and `ImportQueueItem` already carry everything the KPIs need (status, meta fields, import errors), so this is buildable as a real UI+controller feature in Phase 5, not a new subsystem.
**Evidence:** `routes/admin.php`, `app/Http/Middleware/EnsureContentManagerRole.php`, `app/Models/AdminUser.php`.
**Follow-up:** build in Phase 5 alongside the other dashboards.

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
**Implemented (Phase 3 remediation):** `car-prices/index.blade.php` was restyled to V2 tokens using exactly the filter chips that exist today (brand, category, price bracket) in the reference's chip visual style. No search input or extra filter dropdowns were added to the markup, since they would submit to nothing.
**Genuinely missing:** real controller support for combinable query-string filtering, free-text search, and sorting — this is the actual scope of DESIGN_SPEC §4's "فهرست خودروها" requirement and is real Phase 4 backend+UI work, not a restyle.
**Evidence:** `app/Http/Controllers/Public/CarPriceController.php` (full read, Phase 1 and this remediation), `routes/public.php`.
**Follow-up:** build in Phase 4 as originally planned — this gap was surfaced now only because the owner asked for full page-content parity on this specific page as part of the Phase 3 correction; the filter/search backend itself was correctly out of scope for that correction and remains Phase 4.
