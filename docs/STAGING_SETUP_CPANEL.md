# One-time cPanel staging setup

This procedure requires cPanel UI, File Manager, and phpMyAdmin only. It does not require owner SSH.

1. Create a separate cPanel subdomain named `staging.<production-domain>`.
2. Choose a document root outside `/home/navrac/public_html`, preferably `/home/navrac/staging.navracar.com`. Record the actual path; if it differs from the default, update the reviewed staging deployment path before the first candidate deployment.
3. Create a separate cPanel Git Version Control clone at `/home/navrac/navracar-staging-repo` from the repository and select branch `cpanel-staging`.
4. In File Manager, create `/home/navrac/navracar-staging-app` and its `storage/` tree. Create a separate `.env` there from `.env.example` with staging-only values.
5. Create the separate staging database using the cPanel-prefixed name and a dedicated database user. Do not grant the staging user access to the production database.
6. In phpMyAdmin, import a reviewed staging snapshot as described in `STAGING_DATA_POLICY.md`. Never import a staging database into production.
7. Create the staging public storage link/path at the cPanel document root as `/home/navrac/staging.navracar.com/storage`, pointing only to `/home/navrac/navracar-staging-app/storage/app/public`. If File Manager cannot create a symlink, use a cPanel-supported public alias or a one-time copy of the staging upload directory; never point it at production storage and do not repeat a large copy during normal releases.
8. Enable cPanel Directory Privacy for the complete staging document root and issue credentials only to trusted testers.
9. Use **Update from Remote**, verify the candidate metadata, and only then use **Deploy HEAD Commit**.

The first candidate deployment is intentionally not performed by this change. The owner must complete the one-time cPanel setup and explicitly deploy a candidate.
