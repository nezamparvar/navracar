# Visual Parity Matrix — Round 6

Maps locked reference assets to current implementation. Each row: exact mismatch requiring proof of resolution via overlay before Batch completion.

## Priority Route: Homepage (`/`)

| Field | Details |
|---|---|
| **Reference Asset** | `docs/design-v2/assets/homepage.png` (locked, sections: hero + CTA + feature cards + footer) |
| **Implementation** | `resources/views/public/index.blade.php` |
| **Desktop Requirement** | Hero banner (16:9 video/image), centered CTA buttons, 3-column feature grid below fold, full-width footer |
| **Mobile Requirement** | Single-column stacked layout, hero 2:3 aspect, CTAs block-stacked, 1-column features, drawer footer |
| **Fixture Data Required** | None (static page) |
| **Current Status** | Requires overlay proof of section alignment, typography scale, button positioning |
| **Batch 2** | If layout corrections needed |
| **Evidence Required** | `homepage-reference-crop.png`, `homepage-current-1440x900.png`, `homepage-overlay.png`, `homepage-mobile-reference-crop.png`, `homepage-current-390x844.png`, `homepage-overlay-mobile.png`, SHA-256 for each |

## Priority Route: Vehicle List (`/car-prices`)

| Field | Details |
|---|---|
| **Reference Asset** | `docs/design-v2/assets/01-public-desktop-system.png` (right panel: vehicle cards grid, filter bar, search, pagination) |
| **Implementation** | `resources/views/public/car-prices/index.blade.php`, `app/Http/Controllers/Public/CarPriceController.php` |
| **Desktop Requirement** | Filter sidebar (left) + card grid (right): 2-3 columns, card proportions 4:3 hero + price/specs bottom section, search bar at top, sort dropdown, pagination controls |
| **Mobile Requirement** | Single-column stacked cards, collapsible filters drawer, search bar prominent, pagination at bottom |
| **Fixture Data Required** | 8 published CarListing records with images, metadata, prices in different brackets |
| **Current Status** | Filter chips present; search/sort implemented; card layout TBD via overlay; no deterministic realistic vehicle photos |
| **Batch 2** | Card density, spacing, image gallery, filter bar final layout |
| **Evidence Required** | `vehicle-list-reference-crop.png`, `vehicle-list-current-1440x900.png`, `vehicle-list-overlay.png`, mobile variants, gallery overlay, SHA-256 for each |

## Priority Route: Vehicle Detail (`/car-prices/:slug`)

| Field | Details |
|---|---|
| **Reference Asset** | `docs/design-v2/assets/01-public-desktop-system.png` (vehicle detail page: hero + thumbnails + specs + pricing) |
| **Implementation** | `resources/views/public/car-prices/show.blade.php` |
| **Desktop Requirement** | **LOCKED REFERENCE COMPOSITION DETERMINES LAYOUT** — hero position, thumbnail arrangement (vertical/horizontal), info panel placement, cost-summary layout, specs density, tabs/accordion for additional details |
| **Mobile Requirement** | Single-column stacked: hero full-width, thumbnails carousel or grid below, info column, pricing card, specs in tabs |
| **Fixture Data Required** | 1 CarListing with 4-6 high-quality vehicle images, full specs (make/model/year/engine/fuel/mileage), pricing, condition, delivery days |
| **Current Status** | Layout changed from reference without overlay proof; needs deterministic realistic images |
| **Batch 2** | **COMPLETE OVERLAY COMPARISON** against locked reference; gallery structure; image density; typography alignment |
| **Evidence Required** | `vehicle-detail-reference-crop.png`, `vehicle-detail-current-1440x900.png`, `vehicle-detail-overlay.png` (with grid/alignment markers), `vehicle-detail-gallery-overlay.png` (thumbnails vs. hero vs. info placement), mobile variants, SHA-256 for each |

## Priority Route: Calculator (`/calculator`)

| Field | Details |
|---|---|
| **Reference Asset** | `docs/design-v2/assets/calculator.png` (locked: form layout, input fields, calculation display, results density) |
| **Implementation** | `resources/views/public/calculator.blade.php` |
| **Desktop Requirement** | Two-column: inputs (left), results (right); fieldset grouping; real-time calculation feedback; three-category cost summary |
| **Mobile Requirement** | Single column stacked; inputs first, results below; full-width calculations |
| **Fixture Data Required** | None (pure calculation, no DB queries); Settings for FreeRate, USDtoAED must be populated in seeders |
| **Current Status** | Requires overlay proof of field alignment, result typography, category boundaries |
| **Batch 2** | If layout corrections needed |
| **Evidence Required** | `calculator-reference-crop.png`, `calculator-current-1440x900.png`, `calculator-overlay.png`, mobile variants, SHA-256 for each |

## Priority Route: Admin Dashboard (`/admin`)

| Field | Details |
|---|---|
| **Reference Asset** | `docs/design-v2/assets/02-admin-dashboard-calendar.png` (locked: 4 KPI cards, 14-day chart, calendar, pipeline, recent requests, overdue follow-ups, import status, rates) |
| **Implementation** | `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php` |
| **Desktop Requirement** | **EXACTLY 4 KPI cards** (newRequests, underFollowUp, activeListings, failedImports), calendar 7-day week grid + mini-events, 14-day performance dual-series chart, pipeline mini-kanban, recent requests list (5 rows), overdue follow-ups (5 rows), import status 3-box layout, exchange rates 2-box layout |
| **Mobile Requirement** | Single-column stacked cards, calendar day-view or list, charts responsive, tables scrollable |
| **Fixture Data Required** | 5 QuoteRequest (assigned to admin, various stages), 3 CalculationLog (today), 2 VinCheck (today), 8 CarListing (published), 1 ImportQueueItem (failed), 3 CalendarEvent (this week), 2 Invoice (open), 1 Post (published), 1 HomeSlide (active) |
| **Current Status** | 4 KPI cards implemented; all widgets present; requires fixture population for deterministic rendering |
| **Batch 1 Blocker** | Must return HTTP 200 with populated content; no HTTP 500 |
| **Evidence Required** | `admin-dashboard-reference-crop.png`, `admin-dashboard-current-1440x900.png`, `admin-dashboard-overlay.png`, mobile variants, fixture counts in metadata, SHA-256 for each |

## Priority Route: Sales Dashboard (`/admin/sales-dashboard`)

| Field | Details |
|---|---|
| **Reference Asset** | `docs/design-v2/assets/03-sales-dashboard.png` (locked: 4 KPI cards, pipeline kanban, today's schedule, overdue follow-ups, proformas, funnel chart) |
| **Implementation** | `app/Http/Controllers/Admin/SalesDashboardController.php`, `resources/views/admin/sales-dashboard.blade.php` |
| **Desktop Requirement** | **EXACTLY 4 KPI cards** (newLeads, todayFollowUps, todayMeetings, openProforma), 2-column layout with pipeline mini-kanban (left 2/3) and schedule (right 1/3), overdue follow-ups + proformas (2-column), sales funnel bar chart |
| **Mobile Requirement** | Single-column stacked, schedule/overdue full-width, funnel stacked vertically |
| **Fixture Data Required** | For sales rep: 3 QuoteRequest (various stages), 2 assigned CalendarEvent (today), 1 overdue follow-up, 2 open Invoice; for admin: all across all reps |
| **Current Status** | All widgets present; requires fixture population for deterministic rendering |
| **Batch 1 Blocker** | Must return HTTP 200 with populated content |
| **Evidence Required** | `sales-dashboard-reference-crop.png`, `sales-dashboard-current-1440x900.png`, `sales-dashboard-overlay.png`, mobile variants, fixture counts in metadata, SHA-256 for each |

## Batch 1 Acceptance Criteria

- [ ] All 6 priority routes return HTTP 200 (no 500 errors, no error pages)
- [ ] Deterministic fixture data renders (no empty states for populated databases)
- [ ] Visual parity matrix committed
- [ ] Round 6 evidence directory created with all reference crops, overlays, triads
- [ ] Strict external hostname allowlist implemented and documented
- [ ] SHA-256 manifest generated and verified
- [ ] Targeted fixture tests pass (no full Playwright suite yet)
- [ ] Realistic vehicle assets committed with documented provenance
- [ ] No unauthorized layout changes since Round 5

## Next Steps (After Batch 1)

- Batch 2: Overlay-driven layout corrections for Vehicle List and Vehicle Detail
- Batch 3: Admin Dashboard visual tuning (if needed)
- Batch 4: Sales Dashboard and Content Dashboard (if needed)
