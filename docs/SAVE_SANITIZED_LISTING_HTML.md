# Saving Sanitized Marketplace HTML Evidence

PR #21 requires one provenance-backed, sanitized HTML fixture for each marketplace:

- `tests/Fixtures/real/dubizzle_real_sanitized.html`
- `tests/Fixtures/real/dubicars_real_sanitized.html`
- `tests/Fixtures/real/yallamotor_real_sanitized.html`

This handoff is required because automated acquisition is intentionally not part of the importer and public listing pages may render differently or challenge automated clients.

## Owner procedure

For each site, open one representative public listing in Chrome or Edge while signed out:

1. Navigate manually to a single public listing (not a search-results page).
2. Wait until the listing details are visibly rendered.
3. Open DevTools Console and run only this DOM serialization command:

```js
copy("<!doctype html>\n" + document.documentElement.outerHTML)
```

4. Paste into a new local text file using the filename above.
5. Remove cookies, authorization headers, account identifiers, phone numbers, email addresses, tokens, tracking query parameters, and unrelated personal data. Keep the listing fields and markup needed by the parser.
6. Do not include browser exports, HAR files, screenshots containing personal data, or page source fetched by a script.
7. Record the exact public listing URL, UTC capture timestamp, and a SHA-256 hash in a sidecar file with the same basename and `.json` extension. The sidecar must contain only:

```json
{
  "source_url": "https://...",
  "captured_at_utc": "YYYY-MM-DDTHH:MM:SSZ",
  "sha256": "64 lowercase hex characters",
  "sanitized": true
}
```

8. Add the three HTML files and sidecars to this branch, then run the marketplace fixture tests and the repository secret scan.

Synthetic fixtures remain synthetic and must not be relabeled as real evidence. Do not expand the importer to crawl direct URLs as part of this handoff.
