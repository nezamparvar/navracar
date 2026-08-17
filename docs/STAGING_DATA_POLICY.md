# Staging data policy

The safest default is **an anonymized staging snapshot**. Customer names, phone numbers, email addresses, addresses, identifiers, contracts, quotes, invoice/proforma records, and uploaded documents may be personal or commercially sensitive.

## Recommended process

1. Export the production database from cPanel/phpMyAdmin to a local, access-controlled file.
2. Review the schema and identify every customer, contact, lead, quote, invoice, notification, and integration table.
3. Prefer a sanitized export that replaces names, phone numbers, emails, addresses, identifiers, and message content with deterministic test values while preserving relationships and numeric pricing inputs.
4. Import the sanitized export into the separate staging database using phpMyAdmin.
5. Rotate or remove tokens, webhook URLs, mail credentials, payment credentials, and bot credentials before staging starts.
6. Verify the staging application connects only to the staging database.

Do not place a full snapshot or realistic customer data on staging. The staging endpoint intentionally has no perimeter password because browser challenges break Android, extension/API, and automated acceptance flows. Use only anonymized or synthetic data, even with owner approval. Never connect staging directly to the live database and never run anonymization against production.

## Reset boundary

A staging reset may overwrite only the staging database and staging copied uploads. It must never write production database tables, production `.env`, production storage, or production public files.

