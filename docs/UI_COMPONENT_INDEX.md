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

Before adding page-local markup, reuse an existing component when its semantics match. Keep form labels explicitly associated with their controls and give icon-only controls an accessible name.

The standalone calculator and admin Proforma form also call the central pricing endpoint. Their JavaScript may format or present returned values but must not contain a vehicle-pricing formula.
