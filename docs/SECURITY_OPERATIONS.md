# Security operations

## MANUAL SECURITY ACTION REQUIRED

A database password was previously committed in `DEPLOY-ROOT-INSTALL.md`. The tracked file is redacted in this baseline, but redaction does not revoke a credential or remove it from Git history.

Before release, an authorized operator must:

1. Rotate the database password at the hosting/database provider.
2. Update the production secret store or `.env` outside Git.
3. Revoke the old credential and verify it no longer authenticates.
4. Review access logs from the first exposure date through rotation.
5. If policy requires history removal, use `git-filter-repo` or BFG in a coordinated maintenance window, force-push all affected refs, invalidate old clones/forks, and have every contributor re-clone. Rotation is required even if history is rewritten.

Do not paste either old or new secret into an issue, pull request, CI log, commit, or chat transcript.
