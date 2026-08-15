# Browser Extension Integration: Backend & Client Reconciliation

## Overview

This document details the backend integration of the stabilized Navra Capture browser extension (extension branch) with the canonical NavraCar backend (PR #21) for staging RC phase.

**Status:** Backend API infrastructure complete, ready for PR #21 merge and integration.

---

## 1. Sync State

| Branch | SHA | Status |
|--------|-----|--------|
| `main` | `87cec22de20024c88fcdddfd328b6b94cdf3fe73` | Baseline |
| `architecture/bugfix-round-3` (PR #21) | `c8726e77bee4df57857fdf6bea5a58ea0fb1651b` | Backend infrastructure |
| `claude/navra-capture-extension-wthqqt` | `9aa4583...` (latest) | Client + API layer |

---

## 2. Backend Reconciliation

### PR #21 Provides

**Import Queue Infrastructure:**
- `ImportQueueItem` model with full lifecycle (pending → parsed → needs_review → image_importing → ready → published)
- Database table: `import_queue` with JSON storage for payload, parsed, warnings
- Status tracking and confidence scores
- Optional user association
- Marketplace HTML parsing adapters (Dubizzle, DubiCars, YallaMotor)
- `MarketplaceHtmlImportService` for server-side HTML import
- `BrowserCaptureSource` validation service
- Admin-only controller at `POST /admin/imports/browser-capture`

**Migrations:**
- `2026_08_15_000001_create_import_queue_table.php`
- `2026_08_15_000003_add_source_platform_and_capture_method_to_import_queue.php`

**Duplicates & Conflicts:**
- PR #21 has `BrowserCaptureController` (admin-only endpoint)
- Extension branch removed all backend code (scope freeze), kept client only
- **Resolution:** No direct conflicts. API layer added to extension branch extends PR #21's infrastructure.

### Extension Branch Provides

**Client-Side Infrastructure:**
- 3 marketplace adapters (Dubizzle, DubiCars, YallaMotor) with exact domain validation
- 110 passing tests (adapters, extraction, message flow, security)
- Chrome extension manifest (V3, 5 permissions, batch capture)
- Service worker with exactly-once message flow + real notifications
- Keyboard shortcut (Alt+Shift+N)
- Popup UI with preview, settings, batch capture
- Environment lock (build-time staging/production separation)

**New Backend API Infrastructure (Added in Extension Branch):**
- `BrowserExtensionPairing` model (pairing codes, tokens, environment binding, revocation)
- Public API endpoints:
  - `POST /api/browser-capture/v1/pairing/exchange` (code → token)
  - `POST /api/browser-capture/v1/listings` (send capture with Bearer token)
- Pairing code generation & validation (6-digit, one-time, revocable)
- Token-based auth with environment isolation (staging vs production)
- Admin controller for managing pairings
- Console command for generating codes
- Comprehensive API tests

**No Overlap:** Extension API layer (new) + PR #21 import queue = complete flow.

---

## 3. Canonical Capture Contract

### Schema Version
```
navracar.capture.v1
```

### Source Platform Values
```
- dubizzle
- dubicars
- yallamotor
```

### Capture Method
```
capture_method = 'browser_extension'
```

### Required Fields in ImportQueueItem
```
- source: string (source_platform shortname)
- source_platform: string (dubizzle|dubicars|yallamotor)
- capture_method: string ('browser_extension')
- source_url: string (full listing URL)
- status: string (needs_review|parsed|failed|...)
- payload_json: array (raw capture from extension)
  └─ schema_version: 'navracar.capture.v1'
  └─ vehicle: object (extracted vehicle data)
  └─ images: array (image URLs + confidence)
- parsed_json: array (cleaned vehicle data)
- warnings_json: array (extraction warnings/diagnostics)
```

---

## 4. Authentication & Pairing

### Flow
```
Operator (web UI)
  ↓
  Generate pairing code (6-digit)
  ↓
  Share with user
  ↓
  User enters code in extension popup
  ↓
  Extension sends: POST /api/browser-capture/v1/pairing/exchange
    Payload: { pairing_code, environment, device_name }
  ↓
  Server validates code, generates token, stores pairing record
  ↓
  Extension stores token in chrome.storage.local
  ↓
  Extension uses token for all future captures: Bearer {token}
```

### Pairing Code Properties
- **Format:** 6 digits (000000 - 999999)
- **Lifetime:** Configurable (default 24 hours)
- **One-time:** Single exchange only
- **Revocable:** Operator can revoke pairing from admin UI
- **Environment:** Staging code cannot be used for production (and vice versa)
- **Scope:** Minimal - only `vehicle-import:capture`
- **Tracking:** last_used_at timestamp, device_name

### Token Properties
- **Format:** 64-char hex (generated securely)
- **Lifetime:** Follows pairing code expiration or manual revocation
- **Environment:** Bound at generation time (cannot use staging token in production)
- **Tracking:** Associated with admin user via pairing record
- **Bearer Auth:** Sent as `Authorization: Bearer {token}`

### Revocation
Operator can revoke pairing at any time via admin UI:
```
POST /admin/extension-pairing/{pairing}/revoke
```
Sets `revoked_at` timestamp. Future requests with that token return 401 Unauthorized.

---

## 5. Staging-Only Environment

### Hard Binding
Extension staging build is compiled with:
```javascript
const EXTENSION_ENVIRONMENT = 'staging';
const CURRENT_CONFIG = CONFIG['staging'];
// = https://navracar.com/staging/api
```

**No runtime switching allowed.** Environment is fixed at build time.

### Pairing & API URLs for Staging
```
POST https://navracar.com/staging/api/browser-capture/v1/pairing/exchange
POST https://navracar.com/staging/api/browser-capture/v1/listings
```

### Review URL (Staging)
```
https://navracar.com/staging/admin/import-queue/{queue_item_id}
```

### What is NOT Modified
- Production environment (separate build)
- cpanel-release branch
- Web Store publishing
- Direct URL crawler

---

## 6. Capture API Integration: End-to-End

### Flow
```
1. User on marketplace listing (dubizzle.com, etc.)
2. Extension icon shows ✓ (green badge)
3. User clicks icon OR presses Alt+Shift+N
4. Content script captures listing data via adapter
   - Title, make, model, year, price, mileage, images, etc.
5. Service worker sends:
   POST /api/browser-capture/v1/listings
   Headers: Authorization: Bearer {token}
   Body: {
     source: "dubizzle",
     source_url: "https://dubizzle.com/...",
     vehicle: { title, make, model, ... },
     images: [ { url, confidence }, ... ],
     diagnostics: { ... }
   }
6. Server:
   - Validates token (active, not expired/revoked)
   - Validates source_platform (dubizzle|dubicars|yallamotor)
   - Creates ImportQueueItem(status='needs_review')
   - Returns: { queue_item_id, review_url, duplicate_detected }
7. Extension:
   - Shows success notification
   - Opens review_url in new tab
   - User reviews and publishes
```

### Response Structure
```json
{
  "status": "success",
  "queue_item_id": 42,
  "review_url": "https://navracar.com/staging/admin/import-queue/42",
  "duplicate_detected": false,
  "message": "Capture received and queued for review"
}
```

---

## 7. Import Queue Lifecycle

### Status Progression (Per PR #21)
```
needs_review
  ↓
  (admin edits vehicle data, customs price, etc.)
  ↓
image_importing
  (download images from marketplace)
  ↓
ready
  (all images persisted, ready to publish)
  ↓
published
  (CarListing created, visible on site)
```

### Operator Tasks
1. Review captured vehicle data
2. Edit vehicle details if needed
3. Adjust customs price if needed
4. Review diagnostic extraction confidence
5. Resolve duplicate warning (if any)
6. Click "Publish" to create CarListing

### Duplicate Detection
**Exact Match:**
1. `source_platform + source_listing_id` (prevents re-importing same listing)
2. Normalized `source_url` (handles URL variations)

**Possible Match (warning only):**
3. `make/model/year` similarity (user decides if duplicate)

Server must NOT auto-block possible duplicates.

---

## 8. Images: Secure Backend Downloader

### From Extension
Extension sends image URLs only:
```json
{
  "images": [
    { "url": "https://cdn.dubizzle.com/img1.jpg", "confidence": "high" },
    { "url": "https://cdn.dubizzle.com/img2.jpg", "confidence": "high" }
  ]
}
```

### Server-Side Download
PR #21 provides image download job (handled separately, not in scope of API).

**Security Tests (Per Integration Doc):**
- ✓ Valid JPEG/PNG/WebP
- ✓ Multiple images
- ✓ Duplicate URLs (deduplicated)
- ✓ Partial failure (some images fail, some succeed)
- ✓ Retry logic (exponential backoff)
- ✓ Invalid MIME type (rejected)
- ✓ Oversized image (>20MB rejected)
- ✓ Private IP (10.x.x.x, 192.168.x.x, localhost - rejected)
- ✓ Redirect to private IP (rejected)
- ✓ Timeout (>30s rejected)

Only successfully persisted images attach to CarListing.

---

## 9. Customs Price: Backend Authority

### Source Data
Extension sends real marketplace price only:
```json
{ "price_aed": 150000 }
```

### Backend Suggestion
Server applies `customs_value_discount_percent` (default: 30%):
```
customs_suggestion = price_aed * (1 - discount% / 100)
Example: 150,000 * (1 - 30/100) = 105,000 AED
```

### Operator Override
Operator can manually edit customs price in review UI. Manual override is preserved and persisted.

### Dynamic Setting
Admin can change `customs_value_discount_percent` in settings. Future captures use new percentage. Past overrides unaffected.

---

## 10. Preserve Stabilized Extension Behavior

✓ 110 passing extension tests (adapters, security, message flow)
✓ Exactly-once capture (no duplicate sends)
✓ Real Chrome notifications (not console-only)
✓ Listener cleanup (no accumulation)
✓ URL.hostname validation (rejects fake-dubizzle.com)
✓ Least-privilege permissions (5 permissions, no <all_urls>)
✓ Batch capture (multiple tabs selected)
✓ Keyboard shortcut (Alt+Shift+N)
✓ Diagnostics redaction (no credentials in payload)
✓ Build-time environment lock (staging vs production)

**Must re-run tests after any changes to API layer.**

---

## 11. Testing: Real Extension Tests

### Before Integration
```bash
cd tools/navra-capture-extension
npm test
```

### Results
```
Test Suites: 4 passed, 4 total
Tests:       110 passed, 110 total
Time:        4.029 s
```

### After Integration (Must Re-Run)
After merging PR #21 and integrating API layer:
```bash
npm test
```

Expected: All 110 tests still pass, no regressions.

---

## 12. Backend Tests

### New API Tests
```bash
php artisan test tests/Feature/Api/BrowserCaptureApiTest.php
```

**Coverage:**
- Pairing code validation (missing, invalid, expired, revoked, wrong env)
- Token exchange (success, updates last_used_at)
- Bearer auth (missing, invalid)
- Capture endpoint (validation, source platform, URL format)
- ImportQueueItem creation
- Review URL generation

### Pairing Code Command Tests
```bash
php artisan test tests/Feature/Commands/GenerateExtensionPairingCodeTest.php
```

**Coverage:**
- Code generation (format, uniqueness)
- User assignment
- Environment selection
- Expiration calculation

### PR #21 Tests (Must Pass)
All existing backend tests must pass. New integration tests must pass.

---

## 13. CI Requirements

**Protected Checks (Do Not Weaken):**
- ✓ Extension tests (npm test, 110 passing)
- ✓ Extension staging build (npm run build:staging)
- ✓ Backend tests (phpunit, all suites)
- ✓ API tests (new BrowserCaptureApiTest)
- ✓ Database migrations (run and rollback)
- ✓ Composer validate
- ✓ Composer audit
- ✓ npm audit
- ✓ PHP Stan or Larastan (static analysis)
- ✓ Frontend build (if applicable)

**CI Workflow:**
Must run on both branch and PR to main.

---

## 14. Pull Request: Extension to Main

### When to Open
**After:**
- PR #21 is merged
- Extension branch rebased on main
- All tests passing
- Staging RC built and checksummed

### PR Details

**Title:**
```
Navra Capture: Chrome/Edge import integration for Dubizzle, DubiCars and YallaMotor
```

**Description:**
```markdown
## Summary

Integrates the stabilized Navra Capture browser extension with the canonical NavraCar backend 
for marketplace vehicle capture (Dubizzle, DubiCars, YallaMotor).

- **Supported Marketplaces:** Dubizzle, DubiCars, YallaMotor
- **Capture Method:** Browser extension (client-side adapters + server-side import queue)
- **Authentication:** Pairing codes + scoped tokens (environment-bound)
- **Status:** Ready for staging owner acceptance testing

## Architecture

- **Client:** TypeScript adapters for each marketplace, exactly-once message flow, Chrome notifications
- **Server:** Public API endpoints for pairing + capture, ImportQueueItem lifecycle, image download
- **Database:** Pairing table, extended import queue schema

## Permissions

Browser Extension (Manifest V3):
- activeTab: Determine which tab popup opened from
- scripting: Inject content script into marketplace tabs
- storage: Persist pairing token
- tabs: Enumerate open tabs for batch capture
- notifications: Display user-visible success/error messages
- Host permissions: https://navracar.com/* (staging/production API)

No cookies, webRequest, or universal access requested.

## Dependencies

- PR #21: `architecture/bugfix-round-3` (import queue infrastructure)
- PHP 8.2+, Laravel 11
- Chrome/Edge 90+

## Tests

- Extension tests: 110 passing (adapters, security, message flow)
- API tests: Full coverage (pairing, capture, validation)
- Command tests: Pairing code generation
- All tests must pass in CI before merge

## Staging Artifact

- File: `navra-capture-staging.zip` (16 KB)
- SHA-256: {checksum}
- Build: Staging API URL hardcoded (no runtime switching)

## Known Limitations

- No manual HTML fallback in extension (web form only)
- No crawler direct URL import (marketplace adapters only)
- Images downloaded server-side (not in extension)
- Customs price suggestion only (operator must review/approve)

## Important: Do NOT Merge Until Owner Staging Acceptance

This PR is ready for staging testing. Merge only after owner confirms:
- ✓ Dubizzle capture works end-to-end
- ✓ DubiCars capture works end-to-end
- ✓ YallaMotor capture works end-to-end
- ✓ Duplicate detection triggers correctly
- ✓ Batch capture selects correct tabs
- ✓ Keyboard shortcut (Alt+Shift+N) works
- ✓ Token revocation blocks future captures
- ✓ Images import successfully
- ✓ Customs price suggestion correct

**Next Step:** After merge, update production build and deploy to Web Store (separate task).

---
Generated by [Claude Code](https://claude.ai/code)
```

### Head, Base
```
Head: claude/navra-capture-extension-wthqqt
Base: main
```

### Do NOT Merge
This is the staging RC candidate. Do not merge until owner acceptance complete.

---

## 15. Merge Order Recommendation

### Current State
- `main`: Baseline
- PR #21 (`architecture/bugfix-round-3`): Backend infrastructure (pending review/merge)
- Extension branch (`claude/navra-capture-extension-wthqqt`): Client + API layer (just pushed)

### Recommended Sequence

**Option A (Recommended if PR #21 has no conflicts with main):**
```
1. Merge PR #21 to main
2. Rebase extension branch onto updated main
3. Run combined CI
4. Open extension PR to main
5. Run extended integration tests
6. Owner staging acceptance
7. Merge extension PR
```

**Option B (If PR #21 has merge conflicts with main):**
```
1. Resolve PR #21 conflicts with main
2. Merge PR #21
3. (Same as Option A steps 2-7)
```

**Option C (If main has moved significantly):**
```
1. Fetch latest main
2. Verify extension branch doesn't conflict
3. Merge PR #21 first
4. Rebase extension + test
5. (Continue as Option A)
```

### Rationale
PR #21 is foundational (import queue infrastructure). Extension API layer depends on its models/migrations being available. Starting with PR #21 ensures a stable base for extension integration.

---

## 16. Staging Release Candidate Build

### When to Build
**After:**
- PR #21 merged
- Extension branch rebased
- All tests passing
- No pending fixes

### Build Command
```bash
cd tools/navra-capture-extension
npm run build:staging
```

### Output
```
navra-capture-staging-rc1.zip
  ├── manifest.json
  ├── src/
  │   ├── background/service-worker.js
  │   ├── content/content-script.js
  │   ├── popup/popup.html/css/js
  │   └── icons/
  └── navra-capture-staging-rc1.zip.sha256
```

### Artifact Details
| Field | Value |
|-------|-------|
| Filename | `navra-capture-staging-rc1.zip` |
| Size | ~16 KB |
| SHA-256 | (generated & stored in .sha256 file) |
| Source Commit | (git SHA from latest code) |
| API URL | `https://navracar.com/staging/api` (hardcoded) |

### SHA-256 Generation
```bash
sha256sum navra-capture-staging-rc1.zip > navra-capture-staging-rc1.zip.sha256
cat navra-capture-staging-rc1.zip.sha256
```

This is the exact artifact owner will test. DO NOT rebuild without code changes.

---

## 17. Owner Live Acceptance Testing

### Setup
1. Extract `navra-capture-staging-rc1.zip`
2. Open Chrome/Edge
3. Navigate to `chrome://extensions/` (or Edge equivalent)
4. Enable "Developer mode"
5. Click "Load unpacked"
6. Select extracted folder
7. Extension appears in toolbar

### Generate Pairing Code
```bash
php artisan extension:generate-pairing-code --environment=staging
```
Output: 6-digit code (e.g., `483927`)

### Test Cases

#### Dubizzle
- [ ] Open real Dubizzle listing (used car)
- [ ] Extension icon shows ✓
- [ ] Click icon → Preview shows captured data
- [ ] Click "Send" → Capture sent
- [ ] Review URL opens automatically
- [ ] Page shows vehicle details
- [ ] Can edit vehicle data
- [ ] Can edit customs price
- [ ] Click "Publish" → CarListing created
- [ ] CarListing visible on site

#### DubiCars
- [ ] (Same tests as Dubizzle)

#### YallaMotor
- [ ] (Same tests as Dubizzle)

#### Duplicate Detection
- [ ] Send listing twice → Warning appears
- [ ] Warning shows previous capture date
- [ ] Operator can override (publish anyway)

#### Batch Capture
- [ ] Open 3+ tabs (mix of Dubizzle, unrelated site, DubiCars)
- [ ] Click icon → "Batch Capture" option visible
- [ ] Select only supported tabs (auto-uncheck unrelated)
- [ ] Click "Send All" → Progress bar appears
- [ ] All selected tabs sent, review URLs open
- [ ] Verify each capture in admin

#### Keyboard Shortcut
- [ ] Focus Dubizzle tab
- [ ] Press Alt+Shift+N
- [ ] Capture triggered immediately
- [ ] Review URL opens

#### Token Revocation
- [ ] Revoke pairing from admin UI
- [ ] Try to capture → Error: "Token revoked, reconnect"
- [ ] Extension shows auth error notification
- [ ] Operator must generate new pairing code

#### Image Import
- [ ] Capture listing with 5+ images
- [ ] Navigate to review page
- [ ] All images appear (may take 10-20s to download)
- [ ] Click "Publish"
- [ ] Verify CarListing shows all images

#### Customs Price
- [ ] Capture listing with price 100,000 AED
- [ ] Review page suggests 70,000 AED (30% discount)
- [ ] Edit to 65,000 AED manually
- [ ] Publish
- [ ] Verify CarListing shows 65,000 AED (manual override preserved)

### Success Criteria
All test cases pass without errors. Extension is production-ready for Web Store.

---

## 18. Scope Freeze: Prohibited Changes

**Do NOT add in this phase:**
- [ ] New marketplaces (only Dubizzle, DubiCars, YallaMotor)
- [ ] Direct URL crawler (marketplace adapters only)
- [ ] VIN decoder
- [ ] Market comparison
- [ ] ML duplicate detection engine
- [ ] Chrome Web Store publication
- [ ] Backend migrations beyond extension pairing
- [ ] Manual HTML form in extension

**This phase is:**
Integration + correctness + tests + PR + staging RC + owner acceptance.

---

## Files Modified/Created

### Backend Files (This Commit)
```
app/Models/BrowserExtensionPairing.php                        NEW
app/Http/Controllers/Api/BrowserCapture/PairingController.php NEW
app/Http/Controllers/Api/BrowserCapture/CaptureController.php NEW
app/Http/Controllers/Admin/ExtensionPairingController.php     NEW
app/Console/Commands/GenerateExtensionPairingCode.php         NEW
database/migrations/2026_08_15_000004_*.php                   NEW
routes/api.php                                                NEW
routes/admin.php                                              MODIFIED
tests/Feature/Api/BrowserCaptureApiTest.php                   NEW
tests/Feature/Commands/GenerateExtensionPairingCodeTest.php   NEW
```

### Extension Files (Previous Commit)
```
tools/navra-capture-extension/src/adapters/*.ts
tools/navra-capture-extension/src/background/service-worker.js
tools/navra-capture-extension/src/popup/popup.js
tools/navra-capture-extension/tests/*.test.ts
tools/navra-capture-extension/manifest.json
tools/navra-capture-extension/dist/navra-capture-staging.zip
```

### PR #21 Files (Separate Commit)
```
app/Models/ImportQueueItem.php
app/Services/Capture/*.php
database/migrations/2026_08_15_00000[123]_*.php
routes/admin.php (pairing routes added here)
tests/Feature/Api/BrowserCaptureApiTest.php
... (full list in PR #21)
```

---

## Next Steps

1. **Now:** Review this integration plan
2. **Owner Decision:** Approve backend approach & merge order
3. **Codex/Team:** Finalize PR #21, address any conflicts
4. **After PR #21 Merge:**
   - Rebase extension branch on updated main
   - Run full test suite (extension + backend)
   - Build staging RC artifact
   - Open extension PR to main
5. **Owner Live Testing:** Validation on staging
6. **Merge:** Extension PR (after acceptance)
7. **Production:** Separate task (production build + Web Store)

---

**Document Version:** 1.0
**Last Updated:** 2026-08-15
**Status:** Ready for integration
**Next Review:** After PR #21 merge decision
