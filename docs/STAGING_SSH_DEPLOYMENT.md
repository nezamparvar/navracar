# NavraCar staging deployment over SSH

This is the authoritative runbook for the live NavraCar staging environment.
The staging server is a CloudPanel-managed VPS, not a cPanel host. Deployment
is performed over SSH. The legacy branch name `cpanel-staging` identifies the
generated Git artifact only; it does not describe the server or deployment
transport.

## Live staging identity

| Item | Value |
| --- | --- |
| URL | `https://staging.nezamparvar.com/` |
| SSH host | `148.113.181.192` |
| SSH administrator | `ubuntu` with key-only authentication and non-interactive sudo |
| CloudPanel site user | `navra-stage` |
| Application root | `/home/navra-stage/htdocs/staging.nezamparvar.com` |
| Artifact checkout | `/home/navra-stage/.deploy/artifact` |
| Artifact branch | `cpanel-staging` |
| Deployment service | `navracar-staging-deploy.service` |
| Deployment timer | `navracar-staging-deploy.timer` |
| Deployment command | `/usr/local/bin/navracar-staging-deploy staging` |

Server addresses and service state can change. Verify this inventory over SSH
before every deployment. Never infer the staging target from an old cPanel
document or from the Production layout.

## Release flow

```text
feature branch -> protected CI -> merged main
  -> immutable staging candidate -> cpanel-staging artifact branch
  -> SSH deployment service -> staging.nezamparvar.com
  -> owner acceptance -> manual Production promotion
```

Production is not updated by the staging service. Before and after every
staging deployment, confirm that the Production deployment timer remains
disabled and inactive.

## Pre-deployment checks

1. Record the exact source commit, release candidate, artifact commit, and
   artifact ID.
2. Confirm all required CI checks passed for the exact source commit.
3. Confirm `cpanel-staging` contains `DEPLOYMENT-METADATA.json`,
   `SHA256SUMS.txt`, `application/vendor/autoload.php`, and the compiled Vite
   manifest.
4. Connect as `ubuntu` using SSH key authentication. Do not use the restricted
   `claude-audit` account for deployment.
5. Verify the live application root and service definitions without reading or
   printing `.env`, credentials, logs, session files, or private uploads.
6. Confirm Production deployment automation is disabled:

```bash
sudo systemctl is-enabled navracar-production-deploy.timer || true
sudo systemctl is-active navracar-production-deploy.timer || true
```

Expected values are `disabled` and `inactive`.

## Deploy the candidate

The timer checks for a new verified artifact every five minutes. To deploy an
approved staging candidate immediately over SSH:

```bash
sudo systemctl start navracar-staging-deploy.service
sudo systemctl status navracar-staging-deploy.service --no-pager -l
```

The service runs as `navra-stage`. It fetches `cpanel-staging`, validates the
environment metadata and SHA-256 manifest, creates an isolated staging database
backup, preserves the server-only `.env` and `storage/`, swaps only managed
application files, runs migrations and Laravel cache commands, restarts the
queue, and records the deployed artifact commit. A failed swap restores the
previous managed application files and exits Laravel maintenance mode.

Do not manually copy source files into the live application root. Do not run
the deployment service with a feature branch or a raw source checkout. Publish
and deploy a validated artifact.

## Post-deployment verification

Read only non-secret deployment identity and service state:

```bash
sudo -u navra-stage git -C /home/navra-stage/.deploy/artifact rev-parse HEAD
sudo cat /home/navra-stage/.deploy/last-deployment-metadata.json
sudo systemctl is-failed navracar-staging-deploy.service
```

Then verify:

- `https://staging.nezamparvar.com/`
- `https://staging.nezamparvar.com/up`
- `https://staging.nezamparvar.com/admin/login`
- public vehicle list and vehicle detail routes
- calculator and authenticated dashboards

Record the exact deployed artifact commit and source commit. Do not report a
deployment as successful when DNS, TLS, the health endpoint, or the deployment
service is failing.

## Rollback

The deployer keeps the previous managed application tree under
`/home/navra-stage/.deploy/previous` and stores a pre-deployment database dump
under `/home/navra-stage/backups`. Automatic rollback covers a failed managed
file swap. Any manual rollback must remain limited to Staging and must preserve
the staging `.env`, storage, uploads, and database unless a separate reviewed
database recovery is explicitly authorized.
