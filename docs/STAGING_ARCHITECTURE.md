# NavraCar staging architecture

Staging is an isolated CloudPanel site on the NavraCar VPS. It is deployed over
SSH by a dedicated systemd service. It is not a cPanel installation and it is
not served from a Production-domain subdirectory.

```text
feature branch -> protected CI -> merged main
  -> one candidate build -> cpanel-staging artifact
  -> SSH deployment service -> owner acceptance
  -> manual promotion of the same artifact -> Production
```

## Isolation contract

| Resource | Production | Staging |
| --- | --- | --- |
| Public URL | Production domain | `https://staging.nezamparvar.com/` |
| Deployment control | Manual Production promotion | SSH + `navracar-staging-deploy.service` |
| Git artifact branch | `cpanel-release` | `cpanel-staging` (legacy branch name only) |
| CloudPanel site user | Separate Production user | `navra-stage` |
| Laravel application | Separate Production root | `/home/navra-stage/htdocs/staging.nezamparvar.com` |
| Artifact checkout | Separate Production artifact | `/home/navra-stage/.deploy/artifact` |
| Database | Production database | `navracarstage` with staging-only credentials |
| Writable storage | Production storage/uploads | `/home/navra-stage/htdocs/staging.nezamparvar.com/storage` |

The staging deployer hard-codes the `navra-stage` account, staging domain,
staging database name, and `cpanel-staging` artifact branch. It validates
metadata and checksums before changing the application. It preserves the
server-only staging `.env`, storage, uploads, logs, sessions, and caches.

The Production deployment timer must remain disabled and inactive during all
staging work. Staging acceptance never authorizes Production deployment.

## Environment contract

The server-only staging `.env` must use `APP_ENV=staging`, `APP_DEBUG=false`,
`APP_URL=https://staging.nezamparvar.com`, a staging `APP_KEY`, staging-only
database credentials, and isolated cache/session/integration settings. It must
never be committed, displayed, or copied from Production.

Public uploads use the application's own isolated storage path. The deployer
recreates `public/storage` as a link to `../storage/app/public` inside the same
staging application tree.

## Security controls

The staging `robots.txt`, HTML metadata, and `X-Robots-Tag` request no indexing.
Only trusted testers should have access. Never store SSH keys, passwords,
database credentials, or integration tokens in GitHub, artifacts, or reports.

See `docs/STAGING_SSH_DEPLOYMENT.md` for the authoritative deployment procedure.
