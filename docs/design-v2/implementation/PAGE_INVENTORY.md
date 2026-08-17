# NavraCar V2 — Page Inventory

**Locked design source:** `agent/navracar-v2-design-docs` @ `1cdab114920cdc2431f983a1c1ea9efb88e26f82` (verified with `git rev-parse` — matches the branch tip and `docs/design-v2/*` at that commit).
**Implementation branch:** `claude/navracar-v2-complete-ui`
**Method:** derived from `routes/*.php`, controller `view(...)` calls (grepped across `app/Http/Controllers`), `resources/views/components/layouts/*`, `AdminUser` role helpers, and the existing docs in `docs/DESIGN_SYSTEM.md`, `docs/UI_*.md`. Not derived from filenames alone — every row below traces to a real route + controller method + view file.

Legend for **Status**: `not started` / `in progress` / `implemented` / `blocked (see GAP_REPORT)`.

## A. Public web

| Route/Screen | Controller/Handler | View/Component | Role | Data Dependencies | Current States | Required V2 States | Desktop | Mobile | Reference | Test Coverage | Screenshot | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `GET /` (`public.home`) | `Public\HomeController@index` | `public/home.blade.php` | public | `HomeSlide::active()`, `CarListing::published()->take(8)`, `Post::published()->take(3)`, `VehiclePricingCatalog::CATEGORIES`, `CarListing::PRICE_BRACKETS` | populated, empty (no slides/listings) | + loading skeleton for slider, explicit empty state per section | 01-public-desktop-system.png | 05-public-mobile.png | DESIGN_SPEC §4 | none dedicated (covered indirectly by `SiteExpansionTest`) | pending | not started |
| `GET /calculator` (`public.calculator`) | `Public\CalculatorController@index` | `public/calculator.blade.php` + `x-car-calculator` | public | `VehiclePricingCatalog`, central pricing endpoint (`public.vehicle-pricing.calculate` / `api.vehicle-pricing.calculate`) via `VehiclePricingService` | form, loading, result, validation error | same four-entry-method layout (VIN / brand+model / engine cc / expert contact) all visible on first screen per spec; rate source + last-update time on result — **do not rewrite the Blade**, only restyle tokens/layout chrome | 01-public-desktop-system.png | 05-public-mobile.png | DESIGN_SPEC §4 (محاسبه‌گر) | `VehiclePricingEngineTest`, `PublicCostDisplayTest`, `tests/e2e/calculator-wizard.spec.js` | pending | not started |
| `GET /app` (`public.mobile-app`) | closure → `public/mobile-app.blade.php` | same | public | `Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT` (client-side display multiplier only — final total still comes from the central API), `api.vehicle-pricing.calculate` | form, loading, result, local history (localStorage) | restyle to V2 tokens; this is the Capacitor `webDir` shell — see GAP_REPORT re: WebView vs native | n/a (embedded) | 06-admin-mobile.png style but public | DESIGN_SPEC §3 Android note | none | pending | not started |
| `POST /vehicle-pricing/calculate` | `Public\VehiclePricingController` (invokable) | JSON, no view | public | `VehiclePricingService` | success, validation error, rate-source error | n/a — API only | n/a | n/a | DESIGN_SPEC §4 | `VehiclePricingEngineTest` | n/a | n/a |
| `POST /quote-requests` (`public.quote-requests.store`) | `Public\QuoteController@store` | redirect/JSON, uses `public/lead-form.blade.php` for re-render on error | public | `QuoteRequest`, `ProformaPdfGenerator`, `ProformaInvoiceMail` | success, validation error | preserve entered values on error (existing behavior) | — | — | DESIGN_SPEC §4 (پیگیری درخواست) | `LeadLifecycleTest`, `QuoteRequestAuthorizationTest` | pending | not started |
| `GET /quote-requests/{id}/pdf` (signed) | `Public\QuoteController@downloadPdf` | file download of `pdf.proforma[-en]` | public (signed URL) | `Storage::disk('public')` | success, invalid/expired signature (403) | see Phase 11 | — | — | DESIGN_SPEC §4 | `ProformaPdfTest`, `PdfAcceptanceArtifactTest` | pending | not started |
| `POST /calculation-logs`, `POST /vin-checks` | `Public\CalculationLogController@store`, `Public\VinLogController@store` | none (logging only) | public | `CalculationLog`, `VinCheck` | n/a | n/a | n/a | n/a | — | n/a | n/a | n/a |
| `GET/POST /lead-form` (`public.lead-form*`) | `Public\LeadFormController` | `public/lead-form.blade.php` | public | `QuoteRequest` | form, validation error, success | request-tracking states per spec §4 (status, timeline, next action) — **currently this form has no tracking-by-number view; see GAP_REPORT** | 01-public-desktop-system.png | 05-public-mobile.png | DESIGN_SPEC §4 (پیگیری درخواست) | `LeadLifecycleTest` | pending | not started |
| `GET /sitemap.xml`, `GET /car-prices/sitemap.xml` | `SitemapController`, `CarPriceController@sitemap` | XML, not a UI page | public | `CarListing` | n/a | out of visual scope | n/a | n/a | n/a | n/a | n/a | n/a |
| `GET /blog` (`public.blog.index`) | `Public\BlogController@index` | `public/blog/index.blade.php` | public | `Post::published()` paginated | populated, empty, pagination | list→card grid per V2 tokens; loading/empty/error | 01-public-desktop-system.png | 05-public-mobile.png | DESIGN_SPEC (implied by content dashboard companion) | none dedicated | pending | not started |
| `GET /blog/{post}` (`public.blog.show`) | `Public\BlogController@show` | `public/blog/show.blade.php` | public | `Post` | populated, 404 | article layout, meta title/description surfaced | same | same | same | none | pending | not started |
| `GET /car-prices` + `/brand/{make}` + `/category/{id}` + `/price/{bracket}` (`public.car-prices.index/brand/category/price`) | `Public\CarPriceController@index/brand/category/price` | `public/car-prices/index.blade.php` (shared) | public | `CarListing::published()`, `VehiclePricingCatalog`, `Setting::FREE_RATE`/`CUSTOMS_RATE` | populated, empty/no-results, pagination | search by brand/model/code, filters (brand, model, year, engine, fuel, price range), sort — per spec §4 (فهرست خودروها); card shows image/title/year/engine/key feature/AED/Toman | 01-public-desktop-system.png | 05-public-mobile.png | DESIGN_SPEC §4 (فهرست خودروها) | `PublicCostDisplayTest`, `OtherCostsConsistencyTest` | pending | not started |
| `GET /car-prices/{carListing}` (`public.car-prices.show`) | `Public\CarPriceController@show` | `public/car-prices/show.blade.php` | public | `CarListing`, `DubizzleTranslator`, `Setting::FREE_RATE`/`CUSTOMS_RATE` | populated, 404/unpublished | gallery, specs, **only the 3 allowed cost categories**, primary CTA "ثبت درخواست" + secondary "محاسبه هزینه", meta title/description fallback | 01-public-desktop-system.png | 05-public-mobile.png | DESIGN_SPEC §4 (جزئیات خودرو) | `PublicCostDisplayTest` | pending | not started |

## B. Auth

| Route/Screen | Controller/Handler | View/Component | Role | Data Dependencies | Current States | Required V2 States | Reference | Test Coverage | Status |
|---|---|---|---|---|---|---|---|---|---|
| `GET/POST admin/login` (`login`) | `Auth\AuthenticatedSessionController@create/store` | `auth/login.blade.php` | guest | `AdminUser` | form, validation error, throttled (6/min) | field-level errors, focus management, password-manager support; no public registration (unchanged) | DESIGN_SPEC (auth not pictured — text rules only) | `AdminRolePermissionsTest`, `SecurityBaselineTest` | not started |
| `POST admin/logout` | `Auth\AuthenticatedSessionController@destroy` | none | auth | — | n/a | n/a | — | — | n/a |
| 401/403/404/419/422/429/500/503 | Laravel default exception handler | **no custom `resources/views/errors/*` exist today** | any | — | framework default Blade | V2-styled error pages, safe return path, no stack traces | DESIGN_SPEC §... (implied by "system pages" scope) | none | not started — **new views, not a restyle** |

## C. Admin — dashboards

| Route/Screen | Controller/Handler | View/Component | Role | Data Dependencies | Current States | Required V2 States | Reference | Test Coverage | Status |
|---|---|---|---|---|---|---|---|---|---|
| `GET admin/` (`admin.dashboard`) | `Admin\DashboardController` (invokable) | `admin/dashboard.blade.php` | `sales.role` (admin or sales; **content_manager cannot reach this route today** — see GAP_REPORT) | `QuoteRequest`, `CalculationLog`, `VinCheck`, `PipelineStage` — role-scoped via `assigned_to` when not admin | populated KPIs, 14-day chart, category/top-car distribution (admin only), recent requests list | KPIs (new/in-progress requests, active listings, failed imports — **listings/imports KPIs not currently on this dashboard**, see GAP_REPORT), monthly performance, sales pipeline, latest requests, import status + daily rates, **meetings/calls calendar (does not exist — see GAP_REPORT)** | 02-admin-dashboard-calendar.png | `SalesDashboardScopingTest`, `AdminReportsFilterTest` | not started |
| `GET admin/kanban` (`admin.kanban`) | `Admin\KanbanController@index` | `admin/kanban.blade.php` | `sales.role` | `PipelineStage`, `QuoteRequest` (role-scoped), `AdminUser`, `LossReason` | populated columns, empty column, filters (temp/source/q/sales) | personal kanban view matches spec §5 (داشبورد فروش); today's plan / overdue follow-ups / conversion funnel are **not present today** — see GAP_REPORT | 03-sales-dashboard.png | `PipelineStageManagementTest`, `SalesDashboardScopingTest` | not started |
| Calendar (day/week/list, meetings & calls) | **no controller** | **no view** | — | **no model** (`LeadActivity` logs actions but is not a schedulable calendar event) | — | full calendar subsystem per DESIGN_SPEC §5 | 02-admin-dashboard-calendar.png | none | **blocked — genuine backend gap, see GAP_REPORT** |
| Content dashboard (KPIs: pending review / active listings / incomplete metadata / import errors) | **no dedicated controller/route** | **no view** | `content.role` has no landing page at all | `CarListing`, `ImportQueueItem` exist and could back this | admin/content today land on `admin.car-listings.index` directly, no KPI summary | dashboard per DESIGN_SPEC §5 (داشبورد محتوا) | 04-content-dashboard.png | none | **blocked — new route/controller needed, see GAP_REPORT** |

## D. Admin — vehicles / content management

| Route/Screen | Controller/Handler | View | Role | Data Dependencies | Current States | Required V2 States | Test Coverage | Status |
|---|---|---|---|---|---|---|---|---|
| `admin/car-listings` index/create/edit + import + publish/unpublish/refetch/images/publish-social | `Admin\CarListingController` | `admin/car-listings/{index,create,edit,import,_fields}.blade.php` | `content.role` | `CarListing`, `CarListingImage`, marketplace import services | list w/ filters, create, edit, import wizard, image mgmt | review queue semantics from spec §5: title/brand-model/year/price/mileage/engine/meta title/meta description/images visible; distinct "بررسی/اصلاح/پیش‌نمایش/انتشار" actions; block publish when required fields missing | `CarListingFlowTest`, `MarketplaceImportTest`, `DubizzleParserTest`, `CarListingMapperCategoryTest` | not started |
| `admin/import-queue` index/show/update/publish/cancel | `Admin\ImportQueueController` | `admin/import-queue/{index,show}.blade.php` | `admin.role` | `ImportQueueItem` | queue list, item detail, publish/cancel | import history/failure detail/retry/source badge per spec | `MarketplaceImportTest` | not started |
| `admin/posts` index/create/edit + publish/unpublish/publish-social | `Admin\PostController` | `admin/posts/{index,create,edit,_fields}.blade.php` | `content.role` | `Post` | list, create, edit | CRUD states: validation, delete-confirmation (n/a, no destroy route), empty, pagination | none dedicated | not started |
| `admin/home-slides` index + store/update/toggle/destroy | `Admin\HomeSlideController` | `admin/home-slides/index.blade.php` | `content.role` | `HomeSlide` | list w/ inline create/edit | destructive-action confirmation for delete | none | not started |
| `admin/menu-items` index + store/toggle/destroy | `Admin\MenuItemController` | `admin/menu-items/index.blade.php` | `content.role` | `MenuItem` | list w/ inline create | same | none | not started |

## E. Admin — CRM / sales

| Route/Screen | Controller/Handler | View | Role | Data Dependencies | Current States | Required V2 States | Test Coverage | Status |
|---|---|---|---|---|---|---|---|---|
| `admin/requests` index/deleted/create/show + assign/temperature/status/close/archive/unarchive/destroy/restore/force-delete | `Admin\RequestController` | `admin/requests/{index,create,show,deleted}.blade.php` | `sales.role` (destroy/restore/force-delete are `admin.role`) | `QuoteRequest`, `LeadActivity`, `AdminUser` | list (role-scoped), create, detail w/ activity timeline, soft-deleted list | table→card list on mobile; status shown with text+icon, not color alone | `LeadLifecycleTest`, `ArchiveBehaviorTest`, `SoftDeleteAndRestoreTest`, `QuoteRequestAuthorizationTest` | not started |
| `admin/invoices` index/create/show + pdf/status | `Admin\InvoiceController` | `admin/invoices/{index,create,show}.blade.php` | `sales.role` | `Invoice`, `QuoteRequest`, `ProformaPdfGenerator` | list, create, detail, status update | preview before PDF generation; status badges | `ProformaPdfTest` | not started |
| `admin/calculations` (`admin.calculations.index`) | `Admin\CalculationLogController@index` | `admin/calculations/index.blade.php` | `admin.role` | `CalculationLog` | list w/ pagination | table→card mobile | none | not started |
| `admin/vin-checks` (`admin.vin-checks.index`) | `Admin\VinCheckController@index` | `admin/vin-checks/index.blade.php` | `admin.role` | `VinCheck` | list | same | none | not started |
| `admin/template-use` | `Admin\TemplateUseController` (invokable) | none (logging) | `sales.role` | `MessageTemplate` | n/a | n/a | none | n/a |

## F. Admin — settings / system (admin-only)

| Route/Screen | Controller/Handler | View | Role | Data Dependencies | Current States | Required V2 States | Test Coverage | Status |
|---|---|---|---|---|---|---|---|---|
| `admin/settings` (`admin.settings.edit/update`) | `Admin\SettingController` | `admin/settings/edit.blade.php` | `admin.role` | `Setting` (exchange rates, customs %, contact numbers) | form | rate + last-updated display; **no formula/percentage values change** | none dedicated | not started |
| `admin/templates` | `Admin\MessageTemplateController` | `admin/templates/index.blade.php` | `admin.role` | `MessageTemplate` | list w/ inline create/toggle/destroy | confirmation on destroy | none | not started |
| `admin/users` | `Admin\UserController` | `admin/users/index.blade.php` | `admin.role` | `AdminUser` | list w/ create/role-update/reset-password | role select constrained to admin/sales/content_manager | `AdminRolePermissionsTest` | not started |
| `admin/activity-log` | `Admin\ActivityLogController@index` | `admin/activity-log/index.blade.php` | `admin.role` | `ActivityLogger`/audit records | list, filters | table→card mobile | none | not started |
| `admin/extension-pairing` | `Admin\ExtensionPairingController` | `admin/extension-pairing/index.blade.php` | `admin.role` | `BrowserExtensionPairing` | list w/ create/revoke | confirmation on revoke | `BrowserExtensionFlowTest` | not started |
| `admin/export` | `Admin\ExportController` (invokable) | file download, no view | `admin.role` | — | n/a | n/a | none | n/a |
| `admin/pipeline-stages` (store/update-name/destroy) | `Admin\KanbanController` | inline within `admin/kanban.blade.php` | `admin.role` | `PipelineStage` | inline CRUD | confirmation on destroy | `PipelineStageManagementTest` | not started |

## G. PDF / print / email

| Item | Generator | View | Notes | Test Coverage | Status |
|---|---|---|---|---|---|
| Proforma PDF (fa) | `ProformaPdfGenerator::fromQuoteRequest` / `fromInvoice` | `resources/views/pdf/proforma.blade.php` | RTL, must not clip; page numbers | `ProformaPdfTest`, `PdfAcceptanceArtifactTest` | not started |
| Proforma PDF (en) | `ProformaPdfGenerator` | `resources/views/pdf/proforma-en.blade.php` | LTR variant | `ProformaPdfTest` | not started |
| Emails | Mailables | `emails/lead-form-submitted.blade.php`, `emails/proforma-invoice.blade.php`, `emails/quote-request-received.blade.php` | only touch if V2 tokens affect brand visuals; no logic change | none dedicated | not started |

## H. Android / Capacitor

| Item | Path | Notes | Status |
|---|---|---|---|
| Capacitor shell | `android/`, `capacitor.config.json` (`webDir: "mobile"`) | This is a **WebView wrapper around `mobile/index.html` + `mobile/app.js`**, not a native Material 3 UI. See GAP_REPORT for the native-vs-WebView gap; in-scope work is restyling `mobile/*` and `public/mobile-app.blade.php` to V2 tokens, not a Kotlin rewrite. | not started (restyle only) |

## I. Shared layout / components (cross-cutting)

| Component | File | Consumers |
|---|---|---|
| Public shell | `resources/views/components/layouts/public.blade.php` | all public pages |
| Admin shell | `resources/views/components/layouts/admin.blade.php` | all admin pages |
| `x-button`, `x-card`, `x-stat-card`, `x-badge`, `x-icon`, `x-empty-state`, `x-toast-container` | `resources/views/components/*.blade.php` | app-wide |
| `x-car-calculator` | `resources/views/components/car-calculator.blade.php` | public calculator, admin invoice create (pricing display — do not touch logic) |
| `x-social-publish`, `x-schema-breadcrumbs`, `x-staging-banner` | same dir | content pages, public SEO, all pages (staging banner) |

---

**Baseline status as of this inventory:** `composer install` / `npm ci` were run in this session; `npm ci` completed successfully. `composer install` was still resolving dependencies via git-mirror fallback at the time this file was written — baseline `php artisan test --compact` / `npm run build` / `npm run test:e2e` results will be appended once install completes (see `QA_REPORT.md`, Phase 13/14). No production build or test run has occurred yet on this branch, so no visual or functional change has shipped.
