# Navra Car staging architecture

Staging is a separate, owner-accepted environment. The flow is:

```text
feature branch -> PR -> protected CI -> merged main
  -> one candidate build -> cpanel-staging -> owner acceptance
  -> manual promotion of the same artifact -> cpanel-release -> production
```

## Isolation contract

| Resource | Production | Staging default |
| --- | --- | --- |
| Git deployment branch | `cpanel-release` | `cpanel-staging` |
| cPanel Git clone | `/home/navrac/navracar-repo` | `/home/navrac/navracar-staging-repo` |
| Laravel application | `/home/navrac/navracar-app` | `/home/navrac/navracar-staging-app` |
| Public document root | `/home/navrac/public_html` | `/home/navrac/public_html/staging` |
| Database | production database | separate staging database |
| Writable storage | production storage/uploads | separate staging storage/uploads |

The staging script hard-fails for either production destination. It preserves the staging `.env`, storage, uploads, logs, sessions, public storage path, and unrelated web-root files.

The candidate metadata records the source commit, release candidate, artifact ID, payload checksums, and build run. Promotion copies the candidate application and compiled asset bytes; it does not run Composer, npm, or a second build.

## Environment contract

The server-only staging `.env` must use `APP_ENV=staging`, `APP_DEBUG=false`, a staging `APP_URL`, a staging `APP_KEY`, staging database credentials, staging cache/session/filesystem prefixes, and staging-only mail/integration settings. It must never be committed or copied from production.

Staging defaults disable real outbound mail and social publishing in application code. Configure a log/array mailer, disable SMS and payment actions, and use sandbox/test webhook destinations where a provider is required for acceptance testing.

The application displays a visible `STAGING` banner only when `APP_ENV=staging`.

### Public uploads without SSH

The staging `.env` must set `PUBLIC_DISK_ROOT=/home/navrac/public_html/staging/storage`.
The `public` Laravel disk then writes new uploads directly into the directory
served by cPanel at `/staging/storage`; no `storage:link` command or one-time
copy is required. This is staging-only and must not be added to the production
`.env`. The deployment helper creates and validates this directory without
copying or deleting any existing staging uploads.

## Security controls

The staging `robots.txt`, HTML metadata, and `X-Robots-Tag` header all request no indexing. Protect the entire staging document root with cPanel Directory Privacy in addition to Laravel authentication. Do not put the directory password in GitHub, this repository, or the artifact.
The staging URL is `https://navracar.com/staging`. It is a same-domain subdirectory, not a subdomain. Staging uses its own `.env`, database/schema, cache prefix, session cookie (`SESSION_COOKIE=navracar_staging_session`) and cookie path (`SESSION_PATH=/staging`). Set `APP_URL=https://navracar.com/staging` and `ASSET_URL=https://navracar.com/staging` in the staging-only environment so generated links, public uploads, and Vite assets remain under `/staging`.
