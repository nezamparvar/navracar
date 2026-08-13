# Local testing

## Requirements

- PHP 8.3 with curl, fileinfo, GD, intl, mbstring, OpenSSL, PDO SQLite, SQLite, and zip.
- Composer 2.
- Node.js 20 and npm.
- Chromium installed through Playwright.

## Reproducible checks

```sh
composer install
composer validate --strict
composer audit --locked
cp .env.example .env
npm ci
npm audit --audit-level=high
npm run build
php artisan test --compact
npx playwright install chromium
npm run test:e2e
```

The E2E server creates and reseeds an isolated `database/e2e.sqlite`; it does not use the production database. On Windows, set `PHP_BINARY` to the absolute PHP executable when `php` is not on `PATH`.
