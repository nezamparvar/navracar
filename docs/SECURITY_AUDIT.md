# Security audit

## Remediated in this baseline

- Replaced the tracked deployment password value with a non-secret placeholder.
- Sanitized persisted post HTML through a maintained allowlist.
- Restricted cover uploads by size/type and retained randomized storage names.
- Added consistent CSP, clickjacking, MIME-sniffing, referrer, permissions, opener, and production HSTS headers.
- Added explicit throttles to public write/calculation routes and the admin login.
- Reduced the public sales-staff query to the minimum eligible fields and records.
- Restricted outbound requests to HTTPS, exact allowed hosts, port 443, bounded responses, and no redirects; private/reserved geo targets are rejected.
- Upgraded Laravel and frontend dependencies until locked Composer and npm audits report no known advisories.
- Added regression coverage for sanitization, uploads, rate limiting, headers, authorization, authentication, and SSRF boundaries.

## Residual/manual risk

The previously committed database credential must be rotated and revoked by an authorized operator. Redacting the current tree does not revoke it or remove historical objects. See `SECURITY_OPERATIONS.md`. Branch protection also remains an administrator action after the first CI run registers the required contexts.

## Threat-model boundary

This is an application/repository baseline, not an infrastructure penetration test. It does not attest to host patching, firewall policy, database grants, provider audit logs, backups, or production secret-store state.
