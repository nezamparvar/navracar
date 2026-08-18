# Batch 1 Visual Parity — Corrective WIP

**Status:** IN PROGRESS

## Scope

Eight required priority routes for visual parity verification:

1. `/car-prices` — Public vehicle list
2. `/car-prices/e2e-bmw-x4` — Public vehicle detail
3. `/admin` — Admin dashboard
4. `/admin/sales-dashboard` — Sales dashboard
5. `/admin/content-dashboard` — Content dashboard
6. `/admin/calendar?view=day` — Calendar (day view)
7. `/admin/calendar?view=week` — Calendar (week view)
8. `/admin/calendar?view=list` — Calendar (list view)

**Evidence required:** 32 screenshots (8 routes × 2 viewports × 2 capture types)

**Locked reference assets:** Only real assets committed to repository:
- `01-public-desktop-system.png`
- `02-admin-dashboard-calendar.png`
- `03-sales-dashboard.png`
- `04-content-dashboard.png`
- `05-public-mobile.png`
- `06-admin-mobile.png`

## Corrections Applied

### Code Changes

- **ContentDashboardController.php** — Added `slug` column to Post selection. Post model uses slug as route key; missing it caused HTTP 500 "Missing required parameter for Route: admin.posts.edit".
- **DatabaseSeeder.php** — Added `E2eSeeder::class` to seeder call chain. Without it, fixture data (8 CarListings, calendar events, posts) did not seed.
- **screenshot-generator.mjs** — Updated route definitions from 6 to 8 required routes. Added Persian heading validation and final URL pattern matching to prevent login-page capture misidentification.
- **app.css** — Commented out external Google Fonts import to ensure deterministic rendering within strict hostname allowlist.
- **package.json** — Added `test:screenshots` npm script for reproducible capture execution.

### Validation

- ✅ Strict hostname allowlist enforcement (empty for Batch 1 — no external dependencies)
- ✅ Certificate error filtering: suppresses only localhost cert errors; rejects unknown external hosts
- ✅ Authentication validation: verifies login success, final URL pattern, authenticated UI shell presence
- ✅ Route-specific heading validation: ensures captured page matches requested route
- ✅ 8 priority routes defined with Persian text and URL pattern matching

### Invalid Evidence Removed

- Removed all partial/incomplete screenshots (26–28 of 32 attempted captures)
- Removed `BATCH_1_ACCEPTANCE_REPORT.md` (contained false claims of completion)
- Removed `SCREENSHOT_MANIFEST.md` and `manifest.json` (false evidence metadata)
- Removed Sales Dashboard login-page substitution evidence

## Known Issues

### Authentication Timeout (Unresolved)

Calendar-day and calendar-list routes intermittently fail authentication during screenshot capture despite successful login verification. SSL handshake errors in Chromium logs suggest external connection attempts (exact source unconfirmed).

**Instrumentation needed:**
- Distinguish page-initiated external requests from Chromium internal background traffic
- Log every application-initiated non-local request with URL
- Abort unknown external requests via `context.route()`

### Readiness Detection (Unresolved)

Current `networkidle` approach may timeout on dashboard pages with persistent or dynamic activity.

**Improvements needed:**
- Use `domcontentloaded` + `document.fonts.ready`
- Add route-specific widget population assertions
- Replace arbitrary sleeps

### Session Reuse (Unresolved)

Current implementation recreates authentication for each route context.

**Improvement:**
- Authenticate once, save `storageState`
- Reuse verified session for protected routes

## Next Steps (WIP Continuation)

1. **Self-host Persian font** — Remove external dependency; ensure deterministic rendering
2. **Implement route interception** — Block uncontrolled external traffic via `context.route('**/*', ...)`
3. **Refactor authentication** — Authenticate once, reuse session state across routes
4. **Improve readiness detection** — Replace `networkidle` with deterministic conditions
5. **Regenerate evidence set** — 32 required current screenshots with complete metadata manifest
6. **Implement proof-of-concept tooling** — Reference/current/overlay smoke-test triad
7. **Submit Batch 1 review** — Only when all 32 screenshots verified and tooling demonstrated

## Acceptance Checklist (Batch 1 Gate)

- [ ] All eight required routes capture successfully
- [ ] Sales Dashboard evidence is authenticated dashboard (not login page)
- [ ] Content Dashboard returns HTTP 200 and captures correctly
- [ ] Calendar day/week/list modes all capture successfully
- [ ] 32 required current screenshots exist with verified hashes
- [ ] Complete metadata manifest includes route, URL, auth status, capture type, dimensions, SHA-256, ignored requests
- [ ] Parity matrix references only real locked assets and real implementation paths
- [ ] Strict hostname enforcement has no bypass; external traffic blocked or fails route
- [ ] Realistic asset requirement honestly satisfied or explicitly marked incomplete
- [ ] Crop/overlay tooling produces at least one valid smoke-test triad
- [ ] All hashes verify; no login-page substitutions
- [ ] Deterministic readiness conditions prevent timeout races

**Do not begin Batch 2.** Correct Batch 1 completely, then submit for review.
