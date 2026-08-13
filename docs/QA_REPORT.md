# QA report

## Automated scope

- Backend: unit and feature tests, including security regression coverage.
- Frontend: deterministic npm install, high-severity dependency audit, and production Vite build.
- Browser: Chromium flows for public pages, protected admin routing, failed and successful authentication, a core admin list, logout, mobile navigation, horizontal overflow, and serious/critical axe findings.
- Viewports: 360x800, 375x812, 390x844, 430x932, 768x1024, 820x1180, 1024x1366, 1280x800, 1440x900, 1536x960, and 1920x1080.

## Local result

- PHP syntax: 105 files passed.
- Pint: passed.
- PHPUnit: 42 tests passed with 271 assertions.
- Composer audit: no known advisories.
- npm audit: 0 vulnerabilities.
- Vite production build: passed.
- Playwright: 137 passed and 17 intentional skips out of 154 cases; no failures. Skips are limited to the mobile-toggle case above the 640px breakpoint and the single shared fixture submission on the other ten projects; no skipped case represents an untested viewport.

## Manual accessibility review

Automated checks cover serious/critical axe findings, label associations, keyboard focus visibility, heading count, and RTL semantics. Color perception, screen-reader announcement quality, long-form Persian copy, zoom/reflow beyond the configured matrix, and dialog focus trapping require manual review. No critical-path dialog exists in the current automated routes, so dialog focus behavior is not claimed as tested.

## Release interpretation

Local green checks demonstrate repeatability on the tested environment. GitHub Actions remains unverified until the branch is pushed and the pull request workflow completes. Production release also remains blocked until the exposed database credential is rotated and revoked.
