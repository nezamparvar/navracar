# One-time cPanel staging setup

This procedure requires cPanel UI, File Manager, and phpMyAdmin only. It does not require owner SSH.

1. Use the existing production-domain subdirectory URL `https://navracar.com/staging`.
2. Use the document root `/home/navrac/public_html/staging`. Do not create a subdomain document root and do not point staging at `/home/navrac/public_html`.
3. Create a separate cPanel Git Version Control clone at `/home/navrac/navracar-staging-repo` from the repository and select branch `cpanel-staging`.
4. In File Manager, create `/home/navrac/navracar-staging-app` and its `storage/` tree. Create a separate `.env` there from `.env.example` with staging-only values.
   The deployment script prepares these persistent runtime directories before activating each release: `storage/app/public`, `storage/fonts`, `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views`, and `storage/logs`. They may be absent from the Git artifact because Git does not track empty directories. Existing files, uploads, logs, sessions, and caches are never copied, deleted, or replaced. The cPanel/PHP account must be able to write each path or deployment stops before the release swap.
5. Create the separate staging database using the cPanel-prefixed name and a dedicated database user. Do not grant the staging user access to the production database.
6. In phpMyAdmin, import a reviewed staging snapshot as described in `STAGING_DATA_POLICY.md`. Never import a staging database into production.
7. Create the normal writable directory `/home/navrac/public_html/staging/storage`. Set `PUBLIC_DISK_ROOT=/home/navrac/public_html/staging/storage` in the staging-only `.env`; Laravel then writes new public assets directly where cPanel serves them, so no SSH-only `storage:link` or one-time copy is required. Never point it at production storage.

8. In the staging-only `.env`, set `APP_URL=https://navracar.com/staging`, `ASSET_URL=https://navracar.com/staging`, `SESSION_COOKIE=navracar_staging_session`, `SESSION_PATH=/staging`, and `CACHE_PREFIX=navracar_staging_`. Use a staging-only database/schema and `APP_ENV=staging`; never copy production `.env` credentials.
9. Do not enable cPanel Directory Privacy or HTTP Basic Auth on the staging document root. Android, extension/API, and automated acceptance clients must reach Laravel without a browser password challenge. Keep staging limited to anonymized or synthetic data and retain Laravel authentication on admin routes.
10. Use **Update from Remote**, verify the candidate metadata, and only then use **Deploy HEAD Commit**.

The staging deployment task locates cPanel's PHP 8.3+ CLI, applies outstanding
Laravel migrations to the isolated staging database, clears stale caches, and
rebuilds configuration, route, and view caches. This is intentionally part of
**Deploy HEAD Commit** because the hosting plan provides neither SSH nor cPanel
Terminal. Any failed command marks the deployment failed; it never targets the
production application or database.

The first candidate deployment is intentionally not performed by this change. The owner must complete the one-time cPanel setup and explicitly deploy a candidate.

