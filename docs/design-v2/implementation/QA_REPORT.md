# NavraCar V2 — QA Report

## Locked source

- Design source branch: `agent/navracar-v2-design-docs`
- Approved design commit (verified via `git rev-parse`, matches exactly): `1cdab114920cdc2431f983a1c1ea9efb88e26f82`
- Implementation branch: `claude/navracar-v2-complete-ui`, rebased onto `origin/main` @ `17ef799` (see `PAGE_INVENTORY.md` header for the rebase rationale/evidence)

## Phase status

| Phase | Status | Notes |
|---|---|---|
| 1 — Inventory & baseline | Accepted | `PAGE_INVENTORY.md`, baseline build/test/audit results recorded |
| 2 — Design tokens & shared components | Accepted (as a token/component groundwork step; not independently visually reviewed) | `COMPONENT_MAP.md` |
| **3 — App shell / navigation** | **REJECTED — visual mismatch** (commit `fa89a3c`), remediation in progress in a separate commit | See below |

## Phase 3 — REJECTED — visual mismatch

**Reviewed by:** project owner.
**Rejected commit:** `fa89a3c` ("feat(ui-v2): implement app shell and navigation") — **not deleted or hidden**; it remains in branch history. Remediation lands as new, separate commit(s) on top.

**Owner's findings:**
1. The public pages' light body background contradicted the V2 design's single unified dark visual language — restyling only `<header>`/`<footer>` while leaving page content on the old light theme does not constitute a V2 implementation of that page.
2. Navigation items present in the approved design (`درخواست‌ها`, `تماس با ما`, `حساب`) were silently dropped or relabeled because their backing routes/pages don't exist yet, without owner sign-off.
3. "Readable" was treated as the acceptance bar instead of actual visual parity — composition, color, typography, spacing, density, card style, sidebar, and navigation must match the approved reference images, not just avoid being broken.
4. The reference images in `docs/design-v2/assets/` are the **official layout and visual-language reference**, not merely inspirational.

**Root cause (self-assessed):** the first Phase 3 attempt scoped itself to "shell only, no content" specifically *to avoid* the contrast problem it had just caused (see `COMPONENT_MAP.md`'s original note about the public `<body>` staying light) — but that scoping decision itself was never put to the owner, and it produced exactly the "shell-only, content untouched" result the owner rejected. Silently trimming design-approved nav items for the same reason (missing backend) compounded it — both were engineering-convenience choices made unilaterally on a document whose own README states images are official reference, not inspiration.

**Remediation approach:**
- Public shell nav restored to all items/labels/order from `01-public-desktop-system.png`/`05-public-mobile.png` (`خودروها`, `محاسبه هزینه`, `درخواست‌ها`, `تماس با ما`, account icon). Items without a real backing page are **kept visible, not removed**, rendered as an honest disabled state with a reason (not a fabricated working page) — logged in `GAP_REPORT.md` §1 with a concrete implementation plan for the ones that are real, buildable Phase 4 scope (`درخواست‌ها` tracking-by-number) versus the one that is a genuinely separate subsystem decision (`حساب` — public auth, blocked on explicit product authorization, not something a UI mission invents).
- Public `home.blade.php` and `car-prices/index.blade.php` page content (not just the shell) migrated to the V2 dark tokens — card style, chips, hero, and typography restyled to match the reference's structure, not just made "not broken." Filter/search elements that don't exist server-side (free-text search, fuel/engine/price-range as combinable query filters) were **not** fabricated as dead UI — restyled only what's real, gap logged in `GAP_REPORT.md` §7.
- Admin shell header gained the notification-bell icon, global search (wired to the real `admin.requests.index?q=` filter), and profile dropdown visible in all three reference dashboard screenshots (`02`, `03`, `04`) — previously missing entirely. `x-stat-card`/`x-card`/`x-empty-state` gained a `variant="v2"` path and `admin/dashboard.blade.php` was fully switched to it (was previously only wrapped by the v2 shell while its own content stayed on the pre-V2 component styling).
- See the Visual Parity tables below for the field-by-field comparison against the reference images.

**Verification before re-submitting Phase 3 for owner review:**
- `npm run build`: pass
- `php artisan test --compact`: 152/152 passed (the 2 tests that failed at baseline were a `today()`/timezone-boundary flake unrelated to any UI change — confirmed by re-running in isolation once the boundary passed; not something this branch fixed or broke)
- Screenshots at the same representative pages, `1440×900` (desktop) / `390×844` (mobile) — the mission's own mandatory viewport list; the reference PNGs are fixed-size design-tool composite exports (`1672×941`, multiple device panels tiled on one canvas) rather than raw single-viewport screenshots, so literal pixel-for-pixel viewport matching against them isn't meaningful — matching is by structure/color/typography/spacing against each panel, not by canvas dimensions. This is stated explicitly rather than silently assumed.
- Side-by-side comparisons delivered for: Public desktop, Admin desktop, Public mobile, Admin mobile (see `SCREENSHOT_MANIFEST.md` and the delivered image files).

**Not yet re-approved.** Per owner instruction, Phase 4 has not been started, and Phase 3 will not be marked complete until the owner confirms visual parity from the delivered comparison images.

## Visual Parity — Public (home + vehicle list vs. `01-public-desktop-system.png` / `05-public-mobile.png`)

| Dimension | Reference | Before remediation (`fa89a3c`) | After remediation |
|---|---|---|---|
| Color/background | `#020B18`-family dark navy body throughout every panel | Header/footer dark; **body light gradient** | Body, header, footer, cards all on `v2-bg`/`v2-surface`/`v2-elevated` |
| Structure/grid | Centered content column, header nav row, card grid (2–4 up) | Centered column matched; card grid unchanged (already grid-based) | Unchanged (was already correct) — now consistently dark |
| Header | Logo right, 4 nav labels, phone+account icon buttons left, primary CTA | Logo + 3 relabeled nav items (`ثبت درخواست` instead of `درخواست‌ها`), no account icon, phone icon present | Logo + 4 nav labels exact match, phone icon + disabled account icon restored |
| Footer | Not shown in reference (composite crops above the fold) | Dark v2 footer (pre-existing from Phase 3 v1) | Unchanged, still dark v2 |
| Typography | Bold white/light headings, muted secondary text, Vazirmatn | Header/footer typography correct; page headings were `text-ink-900` (dark-on-dark, near-invisible) | Page headings now `text-v2-text`/`text-v2-text-muted`, legible and matching weight/scale used in the reference |
| Spacing/dimensions | Generous card padding, rounded-2xl, consistent gap rhythm | Unchanged from pre-V2 (already close) | Unchanged (already close to reference) |
| Cards/KPIs | Dark elevated card, image top, title, year+engine chips, bold price + secondary currency, heart icon | Light `bg-white` card, no chips, no heart icon | Dark `v2-elevated` card, model-year/engine chips added, heart icon added, price hierarchy matches (bold AED, muted secondary line) |
| Navigation/active states | Active nav item filled `v2-primary` pill | Present, correct in v1 already | Unchanged (already correct) |
| Iconography | Line icons, consistent stroke | Consistent (existing `x-icon` set) | Added `heart` icon (was missing from the icon registry entirely) |
| Desktop/mobile adaptation | Bottom nav 5 items (`خانه`/`خودروها`/`محاسبه`/`درخواست‌ها`/`حساب`) | 4 items, `حساب` dropped silently | 5 items restored, `حساب` shown disabled with reason (not silently dropped, not faked as working) |

## Visual Parity — Admin (dashboard vs. `02-admin-dashboard-calendar.png`, cross-checked against `03-sales-dashboard.png`/`04-content-dashboard.png` for shell-level elements consistent across all three)

| Dimension | Reference | Before remediation (`fa89a3c`) | After remediation |
|---|---|---|---|
| Color/background | Dark navy sidebar+header+body+cards throughout | Sidebar/header dark v2; **KPI/content cards still light** (`x-stat-card`/`x-card` default) because those components had no v2 variant applied on this page | `x-stat-card`, `x-card`, `x-empty-state` given a `variant="v2"` path; `admin/dashboard.blade.php` fully switched to it — cards, chart panel, table all dark now |
| Structure/grid | Fixed dark sidebar right, 4-up KPI row, 2-column chart/list grid below | Already matched (existing grid structure) | Unchanged (already correct), now consistently dark |
| Sidebar | Grouped nav (عمومی/فروش/مدیریت محتوا/فقط مدیر), logo top | Already matched — grouping/labels/routes untouched from V1 | Unchanged (already correct) |
| Header | Page title, notification bell w/ badge, date-range selector, user profile + chevron, global search (present in 2 of 3 reference dashboards) | Only page title + plain user name/avatar — **no bell, no chevron, no search** | Bell icon added (no fabricated badge count — no notification backend exists, see `GAP_REPORT.md` §6), profile now a chevron dropdown (real logout action inside), search box added wired to the real `admin.requests.index?q=` filter |
| Typography | Bold white values, muted labels, consistent hierarchy | Correct hierarchy, but on light cards (dark text on white, not the reference's light text on dark) | Now light-on-dark matching the reference exactly |
| Spacing/dimensions | KPI card padding/radius, consistent 4-up grid gap | Already matched | Unchanged |
| Cards/KPIs | Dark `elevated` card, label top-left + icon chip top-right, big bold value, trend/note below | Structure matched; colors were light-card (pre-V2) | Same structure, now on `v2-elevated` with `v2-primary`/`v2-success`/`v2-warning`/`v2-error` icon chips instead of amber/brand |
| Navigation/active states | Active sidebar item filled `v2-primary`/blue pill | Already correct (Phase 3 v1 sidebar) | Unchanged (already correct) |
| Iconography | Consistent line icons; bell, chevron-down, search present | `bell`/`chevron-down`/`search` icons existed in the registry but were unused in the shell | Now used in the header exactly where the reference shows them |
| Desktop/mobile adaptation | Same dark language on mobile; sidebar becomes drawer **and** a 5-item bottom nav appears (`داشبورد`/`فروش`/`محتوا`/`تقویم`/`منو` — `06-admin-mobile.png`) | Drawer existed; **bottom nav was entirely missing** — a real gap caught during this remediation's own side-by-side review, not something the owner had to flag separately | Bottom nav added, permission-aware (فروش/محتوا only for roles that can see them), `تقویم` shown disabled with reason (no calendar backend, `GAP_REPORT.md` §2) rather than dropped; dashboard content now dark on mobile too since the page itself migrated |

**Known remaining gaps (not faked, tracked in `GAP_REPORT.md`):** notification bell has no live unread count (no backend), header search only covers requests (no unified cross-entity search backend), sales/content dashboards (`03`/`04`) and every other admin page besides the main dashboard are **not yet migrated** — that is real Phase 5 scope and is not claimed as done here. Vehicle-detail page, calculator restyle, and full brand/model/year/engine/fuel/price filter bar on the public side are real Phase 4 scope and are not claimed as done here either — only the two representative pages needed for this parity re-review (`home`, `car-prices/index`) were migrated as part of the Phase 3 correction, per the owner's instruction to migrate the shell together with its page's own content rather than leave it inconsistent.
