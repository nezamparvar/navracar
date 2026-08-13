# UI library maintenance

The UI library is the set of Blade components under `resources/views/components`, the Tailwind tokens in `tailwind.config.js`, and the conventions in this documentation set.

For a component change:

1. Prefer variants over near-duplicate components.
2. Preserve RTL, keyboard, dark-mode, and mobile behavior.
3. Add or update a focused feature or Playwright test when behavior changes.
4. Run `npm run build`, `npm run test:e2e:responsive`, and the relevant accessibility suite.
5. Update `UI_COMPONENT_INDEX.md` when the public component contract changes.

This baseline intentionally leaves the current visual language and page information architecture intact.
