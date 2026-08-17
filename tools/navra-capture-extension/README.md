# Navra Capture

Chrome/Edge Manifest V3 extension for capturing public vehicle-listing data from Dubizzle, DubiCars, and YallaMotor and sending it to Navracar's administrator review queue.

## Commands

```bash
npm ci
npm audit --audit-level=high
npm test -- --runInBand
npm run build
cd dist
sha256sum -c navra-capture-staging.zip.sha256 navra-capture-production.zip.sha256
```

`npm run build` creates separate fixed-environment packages:

- `dist/navra-capture-staging.zip`
- `dist/navra-capture-production.zip`

The build fails before checksum generation if either package points to the wrong API environment.

## Usage

1. Ask a Navracar administrator for a six-digit one-time pairing code for the same environment as the installed package.
2. Enter the code in the popup.
3. Open a supported public vehicle listing and review the extracted preview.
4. Send it to Navracar.
5. Review and edit it in **Admin → Import queue** before creating a draft listing.

Pairing codes are single-use. The extension stores only its bearer token in `chrome.storage.local`; disconnecting removes it locally. An administrator can revoke it server-side at any time.

See [`../../docs/BROWSER_EXTENSION_INTEGRATION.md`](../../docs/BROWSER_EXTENSION_INTEGRATION.md) for the server flow and security boundaries.
