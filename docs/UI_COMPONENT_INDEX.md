# UI component index

This index describes the existing Blade/Tailwind UI. It is a documentation baseline, not a redesign.

| Component | Purpose | Primary options |
| --- | --- | --- |
| `x-layouts.public` | Public RTL shell, responsive navigation, footer, metadata | page title and description slots |
| `x-layouts.admin` | Authenticated admin shell, sidebar, mobile menu, dark mode | title, subtitle, actions slots |
| `x-button` | Links and buttons with shared focus/disabled styling | `primary`, `secondary`, `amber`, sizes |
| `x-card` | Bordered content panel | title, icon, body |
| `x-stat-card` | Dashboard KPI card | label, value, note, color |
| `x-badge` | Compact status marker | semantic color variants |
| `x-icon` | Inline SVG icon registry | icon name and CSS class |
| `x-empty-state` | Empty collection placeholder | icon and title |
| `x-toast-container` | Global transient messages | Alpine event-driven messages |
| `x-car-calculator` | Listing quotation UI; renders the authoritative central pricing endpoint response and retains loan/payment presentation | vehicle, pricing endpoint, quote endpoint configuration |
| `x-social-publish` | Admin social publishing controls | record-specific data |
| `x-schema-breadcrumbs` | Structured breadcrumb metadata | breadcrumb items |
| `x-skeleton` | Loading placeholder, sized like the content it stands in for | `lines`, `height`, `variant` |
| `x-spinner` | Inline loading indicator with an accessible label | `size`, `label` |
| `x-field` | Label + control + hint + inline validation-error scaffold with `aria-describedby` wiring | `name`, `label`, `hint`, `required`, `type`, `variant`; pass a slot (`<select>`, `<textarea>`) for non-`<input>` controls |

`x-button`, `x-badge`, and `x-card` additionally accept NavraCar V2 (`docs/design-v2/`) variants — `v2-primary`/`v2-secondary`/`v2-ghost`/`v2-danger` on `x-button`, `v2-neutral`/`v2-primary`/`v2-success`/`v2-warning`/`v2-error` on `x-badge`, and `variant="v2"` on `x-card` — for pages migrated to the V2 dark surface tokens (`tailwind.config.js` `v2.*` colors). Existing variants are unchanged; V2 variants are additive per `docs/design-v2/IMPLEMENTATION_PLAN.md`.

Before adding page-local markup, reuse an existing component when its semantics match. Keep form labels explicitly associated with their controls and give icon-only controls an accessible name.

The standalone calculator and admin Proforma form also call the central pricing endpoint. Their JavaScript may format or present returned values but must not contain a vehicle-pricing formula.
