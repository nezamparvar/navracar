# Navra Capture and import architecture

Manual HTML is the supported fallback for Dubizzle, DubiCars, and YallaMotor. Server-side direct URL retrieval is deprecated and is not expanded; it remains only for backward compatibility. A 401/403/429, challenge page, timeout, or blocked response is reported as `REMOTE_ACCESS_BLOCKED`; it is not reported as a parser defect and no anti-bot or proxy circumvention is attempted.

All sources use the same pipeline:

`CAPTURE → PARSE → NORMALIZE → VALIDATE → IMAGE IMPORT → REVIEW → SAVE/PUBLISH`

`ListingCaptureSource` separates acquisition from `DubizzleParser`. The current sources are direct URL, manual HTML, and browser-assisted capture. Browser capture accepts only an explicitly submitted URL plus sanitized structured data/HTML (5 MB maximum); cookies, session credentials, authorization headers, and browser secrets are rejected. The authenticated endpoint is `POST /admin/imports/browser-capture`.

The endpoint records a reviewable queue item rather than publishing automatically. Low-confidence or failed captures remain in `needs_review`/`failed` states. A future extension or authorized feed can implement `ListingCaptureSource` without changing parser or domain code.

The owner workflow is: open the listing in Chrome/Edge, invoke an explicit Navra Capture helper (future extension/bookmarklet), and submit the listing URL and structured fields to NavraCar. If a helper is not installed, paste saved page source into the existing manual import flow.

Persistent image import protections remain unchanged: approved hosts, redirect checks, MIME and size validation, safe filenames, timeouts, and no successful image record until persistence succeeds.



## Revised capture direction

Navra Capture is a separate browser-extension project. This backend PR does not modify extension files or duplicate extension DOM extraction logic. The backend accepts a versioned structured payload from that extension through the browser-capture contract.

Until the extension is available, operators submit saved page HTML. Marketplace adapters are independent and identify source_platform (dubizzle, dubicars, yallamotor) separately from capture_method (manual_html, browser_extension). Submitted scripts are never executed, URLs are never fetched as a side effect of manual import, and credential/session/header material is rejected.
