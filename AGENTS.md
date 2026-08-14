# Repository guidance

- Preserve the existing Persian RTL interface and product behavior; prefer focused fixes over page redesigns.
- PHP baseline is 8.3. Use the locked Composer and npm dependencies.
- Before proposing a merge, run Composer install/audit, npm ci/audit/build, `php artisan test --compact`, and `npm run test:e2e`.
- Never commit credentials or production `.env` files. Treat outbound HTTP, HTML rendering, and uploads as security boundaries.
- Add regression tests with every security-sensitive change and keep the GitHub Actions check names documented in `docs/BRANCH_PROTECTION.md` stable.

## Pricing Rule

- Never implement vehicle pricing formulas directly inside a page or controller. All landed-cost pricing must use `App\Services\VehiclePricing\VehiclePricingService`.
- Never introduce a vehicle-pricing percentage outside Settings without explicit business approval.

## Release Policy

No significant feature or business-rule change goes directly from CI to Production.

Required path:

```text
CI -> Staging -> owner acceptance -> Production promotion
```

Production must receive the exact accepted staging artifact whenever technically possible. Promotion must identify the source commit, candidate commit, artifact ID, and checksums; it must not rebuild after staging acceptance.
