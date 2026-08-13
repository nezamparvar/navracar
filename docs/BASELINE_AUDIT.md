# Baseline audit

Baseline source: `main` at `31c3ae8fde999a0465171577d9f0b9e370ca5100`.

At the start of this work, the repository had one stale open pull request, no newer release candidate, no workflow runs, no deployments, and no `main` branch protection. No later branch superseded the prepared baseline.

The remediation branch is `security/release-baseline-v1.1`. Its scope is security hardening, dependency remediation, repeatable tests/CI, and documentation of the existing UI library. It intentionally does not redesign the application, create a `v1.1.0` tag, deploy production, or merge its own pull request.

Release readiness requires all of the following: a reviewed and merged pull request, green required CI checks, enabled branch protection, and verified rotation/revocation of the exposed database credential. Until then the release decision is blocked.
