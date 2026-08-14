# cPanel Git deployment

This repository uses a generated `cpanel-release` branch as a deployment artifact. It is not a development branch and must never be edited manually.

The owner-facing flow is:

```text
development branch
  → pull request
  → protected CI
  → approval and merge to main
  → published GitHub Release
  → verified cpanel-release artifact
  → cPanel Update from Remote
  → verify HEAD
  → cPanel Deploy HEAD Commit
  → smoke test
```

The release workflow is `.github/workflows/cpanel-release.yml`. It accepts only a published `vX.Y.Z` release (or a manual dispatch naming that exact tag and full commit), verifies that the tagged commit is contained in `origin/main`, and requires the four protected checks to be successful on that commit before publishing anything.

## What the generated branch contains

The branch contains only a deployment tree and provenance files:

```text
.cpanel.yml
DEPLOYMENT-METADATA.json
SHA256SUMS.txt
application/
  .cpanel-release.json
  .env.example
  artisan
  composer.json
  composer.lock
  app/
  bootstrap/                 # no generated cache files
  config/
  database/                  # migrations/factories/seeders only
  public/                    # application copy, including compiled build
  resources/
  routes/
  vendor/                    # production Composer dependencies
public_html/
  build/                     # compiled Vite assets
  index.php                  # split-layout entry point
  .htaccess
  favicon.ico
  robots.txt
deployment/
  deploy.sh
```

The generated public entry point references:

```text
../navracar-app/vendor/autoload.php
../navracar-app/bootstrap/app.php
```

No Laravel application directories are copied into the web root. The artifact excludes `.env`, databases, uploads, logs, sessions, runtime caches, `node_modules`, tests, and `.git` metadata. `SHA256SUMS.txt` covers every deployable file except itself.

## cPanel deployment task safety

The root `.cpanel.yml` on `cpanel-release` invokes `deployment/deploy.sh` with the fixed production paths:

```text
/home/navrac/navracar-app
/home/navrac/public_html
```

The script stages and validates the complete replacement set before changing live code. It manages only the explicit application items and these five public items:

- Application: `.env.example`, `.cpanel-release.json`, `artisan`, Composer files, `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `vendor/`
- Web root: `build/`, `index.php`, `.htaccess`, `favicon.ico`, `robots.txt`

It never copies or moves:

- `/home/navrac/navracar-app/.env`
- `/home/navrac/navracar-app/storage/`
- `/home/navrac/navracar-app/storage/app/public/`
- `/home/navrac/public_html/storage`
- Any unrelated file in `public_html`

It does not run Composer, npm, Artisan, migrations, or cache commands on cPanel. Production dependencies and assets are already present in the generated branch.

## One-time cPanel setup

The existing cPanel repository is `/home/navrac/navracar-repo` and currently follows `main`.

After the first successful artifact workflow run:

1. Open **cPanel → Git Version Control**.
2. Select the repository and click **Manage**.
3. In the repository branch control, change the checked-out branch from `main` to `cpanel-release`.
4. Allow cPanel to pull the branch when changing it; do not click Deploy yet.
5. Confirm the displayed HEAD matches the workflow summary and `DEPLOYMENT-METADATA.json`.
6. Confirm the repository is clean and that cPanel now recognizes the root `.cpanel.yml`.

The one-time branch switch does not modify production. The existing live application and public root remain untouched until **Deploy HEAD Commit** is explicitly selected.

## Normal owner workflow

1. Confirm the GitHub Release is published and protected CI is green.
2. Open cPanel **Git Version Control**.
3. Click **Update from Remote**.
4. Verify the `cpanel-release` HEAD commit and source main commit in `DEPLOYMENT-METADATA.json`.
5. Click **Deploy HEAD Commit**.
6. Run the smoke tests below.

The owner does not run Composer, npm, Artisan, or upload release ZIP files.

## Smoke tests

Check the HTTPS home page, admin login, desktop/mobile navigation, Admin Settings, standalone calculator, listing calculator, quote creation, automatic Proforma/PDF generation, uploaded images under `/storage`, and cPanel/Laravel error logs. Confirm the preserved production `.env` still has `APP_DEBUG=false` and the expected database connection.

## Rollback

Every artifact is published to both:

- Moving branch: `cpanel-release`
- Immutable branch: `cpanel-release-vX.Y.Z`

The immutable branch and the corresponding cPanel deployment commit remain on GitHub; rollback does not depend on a developer's local ZIP file.

To roll back:

1. Identify the last accepted immutable branch, for example `cpanel-release-v1.2.0`.
2. In cPanel Git Version Control, switch the checked-out branch to that immutable branch.
3. Click **Update from Remote** and verify its HEAD and metadata.
4. Click **Deploy HEAD Commit**.
5. Repeat the smoke tests.
6. After recovery, switch cPanel back to `cpanel-release` before the next normal release.

The deployment script also retains scoped previous-item backups under `/home/navrac/.navracar-app-cpanel-previous` and `/home/navrac/.public-html-cpanel-previous` until the next deployment. It never backs up or replaces `.env`, Laravel `storage/`, or `public_html/storage`.

Do not import a database backup for a normal code rollback. Database restoration is a separate incident procedure.

## Never manually edit `cpanel-release`

Do not edit files, add secrets, run Composer/npm/Artisan on cPanel, or commit directly to this branch. Regenerate it only through the release workflow from an approved main commit. If the generated artifact fails validation, stop the release and fix the workflow or source PR.
