# One-time cPanel staging setup

This procedure requires cPanel UI, File Manager, and phpMyAdmin only. It does not require owner SSH.

1. Use the existing production-domain subdirectory URL `https://navracar.com/staging`.
2. Use the document root `/home/navrac/public_html/staging`. Do not create a subdomain document root and do not point staging at `/home/navrac/public_html`.
3. Create a separate cPanel Git Version Control clone at `/home/navrac/navracar-staging-repo` from the repository and select branch `cpanel-staging`.
4. In File Manager, create `/home/navrac/navracar-staging-app` and its `storage/` tree. Create a separate `.env` there from `.env.example` with staging-only values.
5. Create the separate staging database using the cPanel-prefixed name and a dedicated database user. Do not grant the staging user access to the production database.
6. In phpMyAdmin, import a reviewed staging snapshot as described in `STAGING_DATA_POLICY.md`. Never import a staging database into production.
7. Create the staging public storage link/path at `/home/navrac/public_html/staging/storage`, pointing only to `/home/navrac/navracar-staging-app/storage/app/public`. If File Manager cannot create a symlink, use a cPanel-supported public alias or a one-time copy of the staging upload directory; never point it at production storage and do not repeat a large copy during normal releases.

8. In the staging-only `.env`, set `APP_URL=https://navracar.com/staging`, `ASSET_URL=https://navracar.com/staging`, `SESSION_COOKIE=navracar_staging_session`, `SESSION_PATH=/staging`, and `CACHE_PREFIX=navracar_staging_`. Use a staging-only database/schema and `APP_ENV=staging`; never copy production `.env` credentials.
9. Enable cPanel Directory Privacy for the complete staging document root and issue credentials only to trusted testers.
10. Use **Update from Remote**, verify the candidate metadata, and only then use **Deploy HEAD Commit**.

The first candidate deployment is intentionally not performed by this change. The owner must complete the one-time cPanel setup and explicitly deploy a candidate.
