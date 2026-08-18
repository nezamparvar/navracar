# cPanel Git deployment

This repository uses generated `cpanel-staging` and `cpanel-release` branches as deployment artifacts. They are not development branches and must never be edited manually.

The owner-facing flow is:

```text
feature branch
  → pull request
  → protected CI
  → merge to main
  → one verified release candidate build
  → cpanel-staging
  → cPanel staging acceptance
  → manual Promote accepted staging artifact
  → cpanel-release
  → cPanel production Update from Remote / Deploy HEAD
  → smoke test
```

The candidate workflow is `.github/workflows/cpanel-staging.yml`. It accepts only a manually supplied `rc-vX.Y.Z-N` candidate and the full commit at the exact head of the selected branch, verifies all protected checks, builds once, and publishes `cpanel-staging`. This permits a feature branch to be exercised on isolated Staging before merge. The production workflow `.github/workflows/cpanel-promote.yml` remains manual-only and main/tag-locked: a feature-branch candidate cannot be promoted. After acceptance and merge, publish and accept a main candidate before any production promotion. Promotion requires the owner-supplied candidate commit, artifact ID, source commit, release tag, and acceptance decision. It copies the already-built candidate payload; it does not run Composer, npm, or a second frontend build.

## Artifact identity and promotion

`DEPLOYMENT-METADATA.json` records the source commit, release candidate, artifact ID, application checksum, public-build checksum, and workflow run. The candidate’s application and compiled asset bytes are compared again during promotion. Production deployment controls and environment metadata are intentionally different, but application code, `vendor/`, and compiled assets are copied from the accepted staging artifact unchanged.

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

No Laravel application directories are copied into the web root. The artifact excludes `.env`, databases, uploads, logs, sessions, runtime caches, `node_modules`, tests, and `.git` metadata. Git does not track empty Laravel runtime directories, so the staging deployment script creates only missing runtime directories (`storage/app/public`, `storage/fonts`, `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views`, and `storage/logs`) and verifies they are writable before swapping code. It never copies or replaces their contents. `SHA256SUMS.txt` covers every deployable file except itself.

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

## One-time production cPanel setup

The existing cPanel repository is `/home/navrac/navracar-repo` and currently follows `main`.

After the first successful artifact workflow run:

1. Open **cPanel → Git Version Control**.
2. Select the repository and click **Manage**.
3. In the repository branch control, change the checked-out branch from `main` to `cpanel-release`.
4. Allow cPanel to pull the branch when changing it; do not click Deploy yet.
5. Confirm the displayed HEAD matches the workflow summary and `DEPLOYMENT-METADATA.json`.
6. Confirm the repository is clean and that cPanel now recognizes the root `.cpanel.yml`.

The one-time branch switch does not modify production. The existing live application and public root remain untouched until **Deploy HEAD Commit** is explicitly selected.

## One-time staging cPanel setup

Follow `docs/STAGING_SETUP_CPANEL.md`. Staging uses a separate Git clone, Laravel application, database, and storage tree while serving from the same production domain at `https://navracar.com/staging`. Its exact public path is `/home/navrac/public_html/staging`; never point the task at `/home/navrac/public_html` or production storage. Set `APP_URL`/`ASSET_URL` to the `/staging` URL and isolate `SESSION_COOKIE`, `SESSION_PATH`, and `CACHE_PREFIX` as documented.

## Staging candidate workflow

1. Merge the approved PR into protected `main`.
2. Dispatch **cPanel staging candidate** from the exact branch being tested with its current full HEAD SHA and a candidate such as `rc-v1.3.0-1`.
3. Verify the workflow summary, artifact, `DEPLOYMENT-METADATA.json`, and `cpanel-staging` HEAD.
4. In the staging cPanel clone, click **Update from Remote**, verify the candidate commit, then click **Deploy HEAD Commit**.
5. Complete `docs/STAGING_ACCEPTANCE_CHECKLIST.md`.

The staging-only deployment task automatically locates an available cPanel PHP
8.3+ CLI, runs outstanding migrations against the staging `.env` database, and
rebuilds Laravel's configuration, route, and view caches. This makes the flow
operable on hosting plans without SSH or Terminal. These Artisan commands are
not added to the production deployment task by this staging recovery change.

## Production promotion workflow

After explicit owner acceptance, dispatch **Promote accepted staging artifact** from `main`. Supply the existing release tag, release candidate, source commit, exact accepted `cpanel-staging` commit, and artifact ID from candidate metadata. The workflow rechecks protected CI and metadata, verifies application/build identity, and publishes the same payload to `cpanel-release` and an immutable `cpanel-release-vX.Y.Z` ref. It does not rebuild.

## Normal production owner workflow

1. Confirm staging acceptance and successful manual promotion.
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

Staging rollback uses `cpanel-staging-rc-vX.Y.Z-N` in the staging cPanel clone and never involves production. Production rollback continues to use immutable `cpanel-release-vX.Y.Z` refs.

## Never manually edit `cpanel-release`

Do not edit files, add secrets, run Composer/npm/Artisan on cPanel, or commit directly to this branch. Regenerate it only through the release workflow from an approved main commit. If the generated artifact fails validation, stop the release and fix the workflow or source PR.
