# Refreshing staging data

Refresh is a separate, deliberate operation; it is not part of code deployment.

1. Disable staging outbound integrations and confirm the staging `.env` still has `APP_ENV=staging`, `APP_DEBUG=false`, staging database credentials, and a log/array mailer.
2. Export a reviewed production snapshot with cPanel/phpMyAdmin, sanitize it according to `STAGING_DATA_POLICY.md`, and import it into the staging database only.
3. If realistic images/PDFs are needed, copy a reviewed production upload snapshot into `/home/navrac/navracar-staging-app/storage/app/public` using File Manager. Do not copy into production and do not replace the staging storage link.
4. Restore staging-only settings, test users, integration flags, and Directory Privacy credentials. The staging `.env` is never overwritten by a data refresh.
5. Verify no mail, SMS, WhatsApp bot, payment, or webhook request can reach a real customer.
6. Run the acceptance checklist.

Monthly or before a major release is usually sufficient; refresh more often only when the test plan requires realistic data. Keep an encrypted local backup of the previous staging snapshot if rollback of test data matters.
