# Staging acceptance checklist

Use the protected staging URL and record the candidate ID and cPanel commit before testing.

- [ ] Directory Privacy works and only trusted testers can enter.
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

Result: **APPROVE FOR PRODUCTION** / **REJECT / FIX REQUIRED**

Owner/date: ____________________
