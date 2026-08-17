# NavraCar V2 — Component Map

Living document, updated as each phase lands. Tracks tokens, component variants, their consumers, accessibility behavior, mobile adaptation, and remaining legacy (pre-V2) components.

## Tokens

Added in `tailwind.config.js` under `theme.extend.colors.v2` and `theme.extend.boxShadow['glow-v2']`, additive alongside the existing `brand`/`amber`/`ink` scales (not removed — see `docs/design-v2/IMPLEMENTATION_PLAN.md` فاز یک: "افزودن توکن‌های V2 بدون حذف ناگهانی توکن‌های فعلی"):

| Token | Value | DESIGN_SPEC role |
|---|---|---|
| `v2-bg` | `#020B18` | page/app background |
| `v2-surface` | `#061426` | cards and panels |
| `v2-elevated` | `#0A1B32` | active controls, emphasized sections |
| `v2-primary` | `#1677FF` | CTA, links, active state |
| `v2-accent` | `#20C7E9` | charts, limited data differentiation |
| `v2-text` | `#F8FAFC` | headings and key data |
| `v2-text-muted` | `#9AAAC1` | descriptions and labels |
| `v2-border` | `#1A3554` | dividers and separators |
| `v2-success` / `v2-warning` / `v2-error` | `#22C55E` / `#EAB308` / `#EF4444` | status only — never decorative |

Orange/purple are intentionally not part of this palette (DESIGN_SPEC.md §2). Spacing (4/8/12/16/24/32px) and radii (12–16px card, ≤24px modal/sheet) already match Tailwind's default scale and the existing `rounded-xl`/`rounded-2xl`/`rounded-3xl` utilities, so no new spacing/radius tokens were needed.

## Component variants (additive — existing variants unchanged)

| Component | File | New V2 variant(s) | Notes |
|---|---|---|---|
| `x-button` | `resources/views/components/button.blade.php` | `v2-primary`, `v2-secondary`, `v2-ghost`, `v2-danger` | existing `primary`/`amber`/`secondary`/`ghost`/`danger` untouched |
| `x-badge` | `resources/views/components/badge.blade.php` | `v2-neutral`, `v2-primary`, `v2-success`, `v2-warning`, `v2-error` | status colors map 1:1 to DESIGN_SPEC semantic colors |
| `x-card` | `resources/views/components/card.blade.php` | `variant="v2"` | swaps light-card shell for `v2-surface`/`v2-border`; icon wrap and title/subtitle text follow |

## New components (Phase 2)

| Component | File | Purpose | Accessibility | Mobile |
|---|---|---|---|---|
| `x-skeleton` | `resources/views/components/skeleton.blade.php` | Loading placeholder sized to the content it replaces (no layout shift) | `role="status"`, `aria-label="در حال بارگذاری"` | full-width by default, same component both breakpoints |
| `x-spinner` | `resources/views/components/spinner.blade.php` | Inline loading indicator for buttons/inline actions | `role="status"` + visually-hidden label | `sm`/`md`/`lg` sizes |
| `x-field` | `resources/views/components/field.blade.php` | Label + control + hint + inline validation error, wired with `for`/`id` and `aria-describedby` | connected label, `aria-invalid`, error text linked via `aria-describedby` | same markup; renders a native `<input>` (44px min height) or accepts a slot for `<select>`/`<textarea>` |

`x-field` covers the common `<input>` case directly; for `<select>`/`<textarea>` pass them as the slot and set a matching `id`/`aria-describedby` yourself (documented in the component's own comment) since Blade cannot inject attributes into arbitrary slot HTML.

## Consumers so far

None yet — Phase 2 only prepares tokens/components. Phases 3–9 will migrate actual pages (public shell, admin shell, then each page family) onto these tokens/components and this table will list each page as it migrates.

## Remaining legacy (pre-V2) components

Everything not listed above is still on the original `brand`/`amber`/`ink` design language: `x-stat-card`, `x-empty-state`, `x-toast-container`, `x-car-calculator`, `x-social-publish`, `x-schema-breadcrumbs`, `x-staging-banner`, and both `x-layouts.public`/`x-layouts.admin` shells themselves. These migrate in Phase 3 (shells/navigation) onward; kept as-is for now so no page's current visual behavior changes yet. Components still needed and not yet built: tabs/segmented control, dropdown/menu/tooltip/popover, modal/dialog/drawer/bottom-sheet, pagination control, search/filter panel, table↔card-list responsive wrapper, chart shell with text/table fallback, timeline/stepper, file/image upload, sticky action bar/mobile bottom nav. These land alongside the pages that need them in Phases 3–9 rather than speculatively now, to keep each component's contract driven by a real consumer.
