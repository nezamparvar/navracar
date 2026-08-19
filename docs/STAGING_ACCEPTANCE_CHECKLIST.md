# Staging acceptance checklist

Use `https://staging.nezamparvar.com/` and record the candidate ID, source commit, and deployed artifact commit before testing.

Round 2 storage/document checks:

- Confirm uploads remain under `/home/navra-stage/htdocs/staging.nezamparvar.com/storage/app/public`; never point them at Production storage.
- Import pasted Dubizzle source and confirm each saved image returns from `/storage/car-listings/<id>/` with an image content type.
- If direct Dubizzle access is blocked, verify the UI says so and directs the owner to View Page Source; do not bypass access controls.
- Download both authorized invoice PDF routes (`/pdf/fa` and `/pdf/en`) and confirm language-specific filenames and identical persisted financial values.
- If PDF generation fails, inspect the staging Laravel log for `Proforma PDF generation failed`. The entry includes only record ID, language, exception class, and a capped/redacted exception message; do not enable `APP_DEBUG` or display the exception to users.
- Confirm thousands separators and verify that manual customs-price edits are preserved when the real AED price changes.

- [ ] Staging access controls allow only trusted testers.
- [ ] `STAGING` indicator is visible; production does not show it.
- [ ] Homepage and mobile homepage are visually correct.
- [ ] Admin login and logout work.
- [ ] Settings and exchange-rate settings work.
- [ ] Vehicle categories and Scrappage Settings work.
- [ ] Dubizzle/listing calculator matches the standalone vehicle calculator.
- [ ] Quote request flow works with a test address.
- [ ] Admin Proforma creation and automatic calculation work.
- [ ] Proforma PDF renders correctly.
- [ ] Existing staging vehicle images load.
- [ ] A new test upload is isolated to staging.
- [ ] Permissions and authorization behave correctly.
- [ ] Application error log has no unexpected errors.
- [ ] No real email, SMS, WhatsApp, payment, or webhook was sent.
- [ ] `robots.txt`, noindex metadata, and `X-Robots-Tag` are present.

Candidate: ____________________

Artifact ID: ____________________

Source commit: ____________________

Deployed artifact commit: ____________________

Result: **APPROVE FOR PRODUCTION** / **REJECT / FIX REQUIRED**

Owner/date: ____________________
