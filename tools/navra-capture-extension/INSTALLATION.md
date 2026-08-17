# Installing Navra Capture

## Staging acceptance

1. Download the `navra-capture-extension` artifact from the successful `Browser extension` GitHub Actions job.
2. Verify the downloaded Staging ZIP against `navra-capture-staging.zip.sha256`.
3. Extract the ZIP to a dedicated folder.
4. Open `chrome://extensions` or `edge://extensions`.
5. Enable Developer mode and choose **Load unpacked**.
6. Select the extracted folder containing `manifest.json`.
7. In Navracar Staging, sign in as an administrator and open **Extension pairing**.
8. Generate a Staging code (normally 24 hours) and enter it in the extension once.

The Staging package is fixed to `https://staging.nezamparvar.com/api` and has no runtime environment switch.

## Production boundary

The Production ZIP is a separate artifact fixed to `https://navracar.com/api`. Do not install or distribute it until the exact candidate has passed CI, Staging acceptance is recorded, and the owner has explicitly approved Production promotion.

## Revocation

Open **Admin → Extension pairing** and revoke the device. A revoked token receives HTTP 401 on its next submission. Also use **Disconnect** in the popup to remove the local token.

## Privacy

Capture only public, signed-out listing pages. Never submit cookies, headers, sessions, passwords, private messages, contact exports, or customer data through the extension.
