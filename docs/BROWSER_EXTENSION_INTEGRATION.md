# Navra Capture browser extension

Navra Capture imports a listing from Dubizzle, DubiCars, or YallaMotor into an admin review queue. It never publishes directly. An administrator reviews the captured fields and then creates a normal draft `CarListing`.

## Security model

- Only administrators can create or revoke extension pairings and review imports.
- Pairing codes are six-digit, expire within 168 hours, and can be exchanged once.
- Pairing codes and bearer tokens are stored only as SHA-256 hashes. The raw bearer token is returned once to the extension.
- Capture endpoints are throttled. A valid bearer token is required for every listing submission.
- Listing hosts must exactly match the chosen marketplace or one of its subdomains.
- Image hosts are allow-listed per marketplace and are checked again by `OutboundUrlGuard` before download.
- Cookies, authorization material, sessions, passwords, and sensitive diagnostics are rejected.
- Captures are bounded to 20 images and constrained field sizes.
- The extension has host permissions only for the three marketplaces and Navracar.

## Server flow

1. An admin opens **Admin → Extension pairing** and creates a one-time code for Staging or Production.
2. The extension exchanges that code at `POST /api/browser-capture/v1/pairing/exchange`.
3. The extension sends sanitized listing data to `POST /api/browser-capture/v1/listings`.
4. The server creates an `ImportQueueItem` with status `needs_review` and flags repeated source URLs.
5. The admin opens **Admin → Import queue**, edits the fields, and chooses **Create draft listing**.
6. The server creates a draft `CarListing`; normal listing review and publication rules continue to apply.

## Builds

Source lives in `tools/navra-capture-extension`. Run:

```bash
cd tools/navra-capture-extension
npm ci
npm audit --audit-level=high
npm test -- --runInBand
npm run build
cd dist
sha256sum -c navra-capture-staging.zip.sha256 navra-capture-production.zip.sha256
```

The `Browser extension` CI job uploads both ZIP files and their SHA-256 checksums. Staging and Production packages are separate and have fixed API targets; there is no runtime environment switch.

## Release boundary

Building an extension package does not authorize server deployment, Chrome Web Store publication, Staging deployment, Production deployment, or merging. Those actions remain subject to the repository release policy and owner approval.
