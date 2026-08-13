# Design system

## Foundations

- Direction: Persian-first RTL.
- Typography: `Vazirmatn`, then `Figtree` and the default sans-serif stack.
- Brand: indigo-blue `brand` scale; primary interactive color is `brand-600`.
- Accent: orange `amber` scale; use for emphasis and selected calls to action.
- Neutral: `ink` scale; use `ink-700` or darker for normal text on light surfaces.
- Surfaces: white/light neutral and `ink-900`/`ink-950` dark surfaces.
- Radius: standard Tailwind radii plus `xl2` at `1.25rem`.
- Elevation: `soft`, `soft-lg`, and matching dark/glow shadows.

## Interaction rules

Controls must retain visible keyboard focus, a minimum practical touch target, disabled feedback, and text or an accessible name. Color must not be the only carrier of status. Motion should be brief and functional; existing fade/toast animations are the baseline.

## Responsive rules

Build mobile-first and verify the repository's Playwright viewport matrix. Pages must not create horizontal document overflow. Public and admin navigation use their existing responsive shells; do not fork additional navigation patterns at page level.

## Dark mode

Every reusable component must pair light colors with a tested `dark:` treatment. Avoid hard-coded page colors when a `brand`, `amber`, or `ink` token already expresses the intent.
