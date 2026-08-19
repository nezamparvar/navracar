# Visual Parity Matrix — Round 6 (CORRECTED)

**Purpose:** Map 8 required Batch 1 priority routes to locked reference assets with strict evidence acceptance criteria.

**Status:** ❌ IN PROGRESS — Corrected for Batch 1 resubmission

---

## Priority Route 1: Vehicle List (`/car-prices`)

| Field | Details |
|---|---|
| **Locked Reference Asset** | `docs/design-v2/assets/01-public-desktop-system.png` (right panel: grid layout, filter bar, search) |
| **Implementation** | `resources/views/public/car-prices/index.blade.php` |
| **Route** | `GET /car-prices` (no auth) |
| **Desktop Requirement** | Filter sidebar (left) + 2–3 column card grid (right), search bar top, sort/pagination at bottom |
| **Mobile Requirement** | Single-column stacked layout, collapsible filters drawer, search prominent |
| **Fixture Data** | 8 published CarListing records (verified in E2eSeeder) |
| **Screenshots Required** | 4 total: viewport 390px + 1440px, full-page 390px + 1440px |
| **Evidence Required** | `vehicle-list-reference-crop.png`, `vehicle-list-current-390x844.png`, `vehicle-list-current-1440x900.png`, overlays with alignment markers |
| **Acceptance Criteria** | HTTP 200, fixture data renders, layout matches reference proportions (no unauthorized changes) |

---

## Priority Route 2: Vehicle Detail (`/car-prices/e2e-bmw-x4`)

| Field | Details |
|---|---|
| **Locked Reference Asset** | `docs/design-v2/assets/01-public-desktop-system.png` (detail view section) |
| **Implementation** | `resources/views/public/car-prices/show.blade.php` |
| **Route** | `GET /car-prices/{slug}` (no auth) |
| **Desktop Requirement** | Hero image full-width, thumbnail gallery (vertical or horizontal per reference), info panel, specs section, pricing card |
| **Mobile Requirement** | Hero full-width, thumbnails as carousel or stacked, info and pricing stacked below |
| **Fixture Data** | 1 CarListing (`e2e-bmw-x4`) with 4 gallery images (verified in E2eSeeder) |
| **Screenshots Required** | 4 total: viewport 390px + 1440px, full-page 390px + 1440px |
| **Evidence Required** | `vehicle-detail-reference-crop.png`, current screenshots, gallery layout overlay, alignment markers |
| **Acceptance Criteria** | HTTP 200, fixture data and images render, layout matches reference (exact thumbnail arrangement to be determined via overlay) |

---

## Priority Route 3: Admin Dashboard (`/admin`)

| Field | Details |
|---|---|
| **Locked Reference Asset** | `docs/design-v2/assets/02-admin-dashboard-calendar.png` |
| **Implementation** | `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php` |
| **Route** | `GET /admin` (admin auth required) |
| **Desktop Requirement** | 4 KPI cards (newRequests, underFollowUp, activeListings, failedImports), 14-day chart, calendar widget, sales pipeline, recent requests, overdue follow-ups, import status, exchange rates |
| **Mobile Requirement** | Single-column stacked KPIs, responsive charts, scrollable tables |
| **Fixture Data** | 5+ QuoteRequest (today), 3+ CalculationLog, 2+ VinCheck, 8 CarListing, admin user (verified in E2eSeeder) |
| **Screenshots Required** | 4 total: viewport 390px + 1440px, full-page 390px + 1440px |
| **Authentication** | Must verify: login succeeds, session valid, authenticated shell visible, URL is `/admin` (not `/admin/login`) |
| **Evidence Required** | `admin-dashboard-reference-crop.png`, authenticated current screenshots, KPI overlay, alignment markers |
| **Acceptance Criteria** | HTTP 200, user authenticated, fixture data renders, layout matches reference |

---

## Priority Route 4: Sales Dashboard (`/admin/sales-dashboard`)

| Field | Details |
|---|---|
| **Locked Reference Asset** | `docs/design-v2/assets/03-sales-dashboard.png` |
| **Implementation** | `app/Http/Controllers/Admin/SalesDashboardController.php`, `resources/views/admin/sales-dashboard.blade.php` |
| **Route** | `GET /admin/sales-dashboard` (admin/sales auth required) |
| **Desktop Requirement** | 4 KPI cards (newLeads, todayFollowUps, todayMeetings, openProforma), pipeline mini-kanban, today's schedule, overdue follow-ups, funnel chart |
| **Mobile Requirement** | Single-column stacked, full-width schedule/overdue |
| **Fixture Data** | 3+ QuoteRequest, 2+ CalendarEvent assigned, authenticated sales/admin user (verified in E2eSeeder) |
| **Screenshots Required** | 4 total: viewport 390px + 1440px, full-page 390px + 1440px |
| **Authentication** | Must verify: login succeeds, authenticated shell visible, final URL is `/admin/sales-dashboard` (NOT login page) |
| **Evidence Required** | `sales-dashboard-reference-crop.png`, authenticated current screenshots, KPI and pipeline overlays |
| **Acceptance Criteria** | HTTP 200, user authenticated, fixture data renders, no login page capture, layout matches reference |

---

## Priority Route 5: Content Dashboard (`/admin/content-dashboard`)

| Field | Details |
|---|---|
| **Locked Reference Asset** | `docs/design-v2/assets/04-content-dashboard.png` |
| **Implementation** | `app/Http/Controllers/Admin/ContentDashboardController.php`, `resources/views/admin/content-dashboard.blade.php` |
| **Route** | `GET /admin/content-dashboard` (admin auth required) |
| **Desktop Requirement** | Blog posts widget, import queue status, media library, recent activity |
| **Mobile Requirement** | Single-column stacked, scrollable widgets |
| **Fixture Data** | 5 Post records, 8 ImportQueueItem (varied statuses), authenticated admin user (verified in E2eSeeder) |
| **Screenshots Required** | 4 total: viewport 390px + 1440px, full-page 390px + 1440px |
| **Authentication** | Must verify: authenticated shell visible, URL is `/admin/content-dashboard` |
| **Evidence Required** | `content-dashboard-reference-crop.png`, authenticated current screenshots, widget overlays |
| **Acceptance Criteria** | HTTP 200, user authenticated, fixture data renders, layout matches reference |

---

## Priority Routes 6–8: Calendar Views (`/admin/calendar`)

| Field | Details |
|---|---|
| **Locked Reference Asset** | `docs/design-v2/assets/02-admin-dashboard-calendar.png` (calendar section) |
| **Implementation** | `app/Http/Controllers/Admin/CalendarController.php`, `resources/views/admin/calendar.blade.php` |
| **Base Route** | `GET /admin/calendar` (admin auth required) |
| **View Modes** | `?view=day`, `?view=week`, `?view=list` |
| **Desktop Requirement (Day)** | Single-day view with hourly time slots, events listed chronologically |
| **Desktop Requirement (Week)** | 7-day grid view (Saturday-Friday per design), events placed in time slots |
| **Desktop Requirement (List)** | Events listed by date, with duration and type indicators |
| **Mobile Requirement** | Responsive time slots, scrollable event list |
| **Fixture Data** | 8 CalendarEvent records (today + next week, varied types/statuses) (verified in E2eSeeder) |
| **Screenshots Required** | 12 total (3 modes × 2 viewports × 2 types) |
| **Authentication** | Must verify: authenticated shell visible, URL matches `?view=*` param |
| **Evidence Required** | 3 reference crops (day/week/list), 6 current screenshots (all viewports), alignment overlays |
| **Acceptance Criteria** | HTTP 200, user authenticated, fixture events render, time slot layout matches reference |

---

## Batch 1 Acceptance Checklist

- [ ] 8 priority routes all return HTTP 200 (public) or HTTP 200 (authenticated)
- [ ] Authentication strictly enforced: login reaches authenticated URL, shell visible, no login page captured
- [ ] 32 screenshots generated (8 routes × 2 viewports × 2 types)
- [ ] SHA-256 manifest includes complete metadata: route, URL, auth status, capture type, dimensions, locked reference, ignored requests
- [ ] Deterministic fixture data renders on every route (8 vehicles, 5+ requests, calendar events, etc.)
- [ ] Visual parity matrix references ONLY real locked assets (01–06.png)
- [ ] Strict hostname allowlist enforced (currently empty; no external resources)
- [ ] Crop/overlay tooling generates at least one smoke-test triad
- [ ] No Batch 2+ work performed; no unauthorized layout changes
- [ ] Honest documentation: vehicle assets marked as "placeholder" (gradient + silhouette), not "realistic"

---

## Out of Batch 1 Scope

- ❌ Reference crop extraction (manual for now; tooling in Batch 1 as smoke test only)
- ❌ Full overlay suite (generated smoke test only; real overlays during implementation batches)
- ❌ Layout corrections (all changes deferred to Batch 2 with overlay evidence)
- ❌ Realistic vehicle photographs (Batch 1 uses generated placeholders)
- ❌ Typography tuning (system fonts only; no Google Fonts in Batch 1)

---

**Status:** Ready for test run  
**Target:** 32 screenshots, strict authentication, complete metadata, smoke-test tooling  
**Next:** Run screenshot generator, build crop/overlay smoke test, generate honest manifest
