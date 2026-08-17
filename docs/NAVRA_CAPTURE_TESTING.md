# Navra Capture verification

## Automated gates

- JavaScript adapter, extraction, production, and security suites: `npm test -- --runInBand`
- Reproducible Staging and Production bundles: `npm run build`
- Archive integrity: SHA-256 verification in `dist/`
- Laravel integration: `tests/Feature/BrowserExtensionFlowTest.php`
- Full repository PHP, migration, security, and E2E gates in GitHub Actions

## Required manual acceptance

Test each marketplace with a non-sensitive listing page:

1. Install the Staging ZIP unpacked in Chrome or Edge.
2. Generate a Staging pairing code as an administrator.
3. Confirm the code works once and a second exchange is rejected.
4. Capture one Dubizzle, one DubiCars, and one YallaMotor listing.
5. Confirm title, make, model, year, AED price, mileage, engine/fuel data, description, and image count in the review queue.
6. Confirm repeated capture of the same URL shows the duplicate warning.
7. Create a draft listing and verify it is not publicly published.
8. Verify its customs value remains unset so the centralized configured discount is applied.
9. Revoke the pairing and confirm further captures return HTTP 401.

Do not use real customer credentials, cookies, sessions, private messages, or unpublished personal data in fixtures or bug reports.
