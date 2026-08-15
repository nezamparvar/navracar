# Navra Capture Browser Extension Guide

## Overview

Navra Capture is a browser extension for Chrome and Edge that allows users to quickly capture vehicle listing data from marketplace websites (Dubizzle, DubiCars, YallaMotor) and import them directly into NavraCar.

**Status**: Phase 2/3 Complete - Ready for Staging

## Supported Marketplaces

| Site | Domain | Detection |
|------|--------|-----------|
| Dubizzle | dubizzle.com | `[data-testid="listing-name"]` |
| DubiCars | dubicars.com | `h1` (title) |
| YallaMotor | yallamotor.com | `[class*="listing"]` |

## Features

### Phase 1: Core Functionality ✅
- **Pairing System**: 6-digit codes, environment-isolated tokens
- **Single Capture**: Alt+Shift+N keyboard shortcut, popup button
- **Image Handling**: Automatic download with retry logic (3 attempts, 20MB limit)
- **Queue Workflow**: captured → needs_review → published

### Phase 2: Enhanced Features ✅
- **Batch Capture**: Select and send multiple open listings without auto-navigation
- **Keyboard Shortcut**: Alt+Shift+N for quick capture on any supported marketplace
- **Diagnostics**: Field-level extraction tracking with source and confidence
- **Security**: Source spoofing prevention, payload size limits (50 images, 2000 char URLs)
- **Duplicate Detection**: Three-tier precedence (listing_id > URL > make/model/year)

### Phase 3: Quality Assurance ✅
- **Fixture Tests**: Real HTML samples for all three marketplaces
- **Isolation Tests**: Adapter independence and state management
- **Security Regression**: SSRF, SQL injection, XSS prevention
- **Documentation**: Setup guides, acceptance criteria, deployment steps

## Installation

### Manual Installation (Staging)

1. **Extract Extension Files**
   ```bash
   cd tools/navra-capture-extension
   ```

2. **Chrome Installation**
   - Open `chrome://extensions/`
   - Enable "Developer mode" (top right)
   - Click "Load unpacked"
   - Select `tools/navra-capture-extension` folder

3. **Edge Installation**
   - Open `edge://extensions/`
   - Enable "Developer mode"
   - Click "Load unpacked"
   - Select `tools/navra-capture-extension` folder

### Configuration

The extension is **build-time environment-locked**:
- **Staging Build**: Points ONLY to `https://navracar.com/staging`
- **Production Build**: Points ONLY to `https://navracar.com` (not yet built)

No runtime switching allowed.

## Usage

### Pairing Extension to Account

1. Open NavraCar admin panel: https://navracar.com/staging/admin
2. Navigate to **Settings → Browser Capture Pairing**
3. Click **Generate Pairing Code**
4. Note the 6-digit code
5. Open extension popup
6. Paste code into "Pairing Code" field
7. Click "Connect to NavraCar"
8. Success message confirms pairing

### Single Capture (Keyboard Shortcut)

1. Navigate to a vehicle listing on supported marketplace
2. Press **Alt+Shift+N** (Windows/Linux) or **⌥⇧N** (macOS)
3. Extension captures the page
4. Preview appears in popup
5. Click **Send** to upload

**Fallback**: If keyboard shortcut doesn't work, click extension popup → **Send Capture**

### Batch Capture (Multiple Listings)

1. Open multiple vehicle listings in separate tabs
2. Open extension popup
3. Click **Batch Capture** button
4. See all open supported listings with checkboxes
5. Select listings to capture (or select all)
6. Click **Send Multiple**
7. Progress bar shows: "3/5 ارسال شد" (3 of 5 sent)
8. Results show per-tab status:
   - ✓ Success (green)
   - ✗ Error (red)
   - ⚠ Duplicate (orange)

## Architecture

### Extension Structure

```
tools/navra-capture-extension/
├── manifest.json              # V3 manifest, permissions
├── src/
│   ├── background/
│   │   └── service-worker.js  # Token auth, API calls, keyboard shortcuts
│   ├── content/
│   │   └── content-script.js  # Page extraction, DiagnosticTracker
│   ├── popup/
│   │   ├── popup.html         # UI with batch capture
│   │   ├── popup.js           # State management, event handlers
│   │   └── popup.css          # RTL-friendly Farsi UI
│   └── icons/
│       ├── icon-16.png
│       ├── icon-48.png
│       └── icon-128.png
└── build/                     # Built output (generated)
```

### Data Flow

```
1. Content Script (marketplave)
   ├─ Detect listing type
   ├─ Extract via JSON-LD/meta/microdata/selector
   ├─ Track in DiagnosticTracker (no credentials)
   └─ Send to Service Worker

2. Service Worker
   ├─ Validate auth token
   ├─ Send to API: POST /api/browser-capture/v1/listings
   └─ Receive queue_item_id & review URL

3. Backend (BrowserCaptureController)
   ├─ Validate source ≠ URL spoofing
   ├─ Check payload size limits
   ├─ Detect duplicates (3-tier precedence)
   ├─ Create ImportQueue record
   ├─ Queue image imports (ImportCaptureImages job)
   └─ Return review URL

4. Image Import Job
   ├─ Download each image (retry 3x, max 20MB)
   ├─ Validate content type & SSRF
   ├─ Store to `storage/captures/{queue_id}/`
   └─ Update queue status → needs_review
```

### Extraction Helpers

**Priority Order**: JSON-LD → OpenGraph Meta → Microdata → Site-Specific Selectors

```javascript
// JSON-LD (most reliable)
script[type="application/ld+json"]

// OpenGraph Meta Tags
<meta property="og:title">
<meta property="og:price">

// Microdata
<span itemprop="title">
<div itemprop="price" content="50000">

// Site-Specific Selectors (fallback)
[data-testid="listing-name"]  // Dubizzle
<h1>                           // DubiCars
[class*="listing"]             // YallaMotor
```

## Security

### Safe by Design

✅ **No Credentials in Captures**
- Diagnostics isSafe() filter blocks token/password/auth/secret/key fields
- Authorization header never included in payload

✅ **SSRF Prevention**
- Image URLs validated against localhost and private IPs
- Only http(s) schemes allowed
- Content-Type checked before download

✅ **SQL Injection Protection**
- All vehicle data validated with Laravel rules
- Stored as JSON, escaped on output

✅ **XSS Prevention**
- Marketplace content treated as untrusted
- HTML stored safely, escaped in templates
- No inline scripts in popup

✅ **Token Security**
- Environment-bound (staging ≠ production)
- 32-char random hex token
- Rate-limited exchange endpoint (5 per minute)
- Revocable by admin

## Testing

### Unit Tests
```bash
# Extension JavaScript behavior (documented in tests/Unit/ExtensionBatchCaptureTest.php)
# Would run with Jest/Mocha against compiled extension
```

### Feature Tests
```bash
./vendor/bin/phpunit tests/Feature/BrowserCaptureApiTest.php
./vendor/bin/phpunit tests/Feature/BrowserCaptureSecurityTest.php
./vendor/bin/phpunit tests/Feature/AdapterFixtureTest.php
./vendor/bin/phpunit tests/Feature/AdapterIsolationTest.php
./vendor/bin/phpunit tests/Feature/BrowserCaptureRegressionTest.php
```

## Development

### Building Extension (Staging)

```bash
cd tools/navra-capture-extension
npm install
npm run build:staging
```

This creates the `build/` directory with compiled files.

### Environment Configuration

Edit `src/background/service-worker.js`:
```javascript
const EXTENSION_ENVIRONMENT = 'staging'; // Change to 'production' for prod build
const CONFIG = {
  staging: {
    baseUrl: 'https://navracar.com/staging',
    apiUrl: 'https://navracar.com/staging/api',
  },
  production: {
    baseUrl: 'https://navracar.com',
    apiUrl: 'https://navracar.com/api',
  },
};
```

### Browser Support

| Browser | Version | Status |
|---------|---------|--------|
| Chrome | 90+ | ✅ Supported |
| Edge | 90+ | ✅ Supported |
| Firefox | - | ⚠ Requires manifest adaptation |
| Safari | - | ⚠ Requires manifest adaptation |

## API Reference

### POST /api/browser-capture/v1/listings

**Authentication**: Bearer token in Authorization header

**Request Body**:
```json
{
  "schema_version": "navracar.capture.v1",
  "source": "dubizzle|dubicars|yallamotor",
  "source_url": "https://...",
  "source_listing_id": "abc123",
  "captured_at": "2026-08-15T10:00:00Z",
  "page_title": "Listing Title",
  "vehicle": {
    "title": "Toyota Camry 2020",
    "make": "Toyota",
    "model": "Camry",
    "year": "2020",
    "price_aed": 50000,
    "mileage_km": "45000",
    "fuel_type": "Petrol",
    "transmission": "Automatic",
    "body_type": "Sedan",
    "color": "Silver",
    "engine": "2.5L",
    "description": "Well maintained...",
    "regional_specs": "GCC"
  },
  "images": [
    {
      "url": "https://example.com/image1.jpg",
      "confidence": "high"
    }
  ],
  "diagnostics": {
    "title": {
      "found": true,
      "source": "json-ld",
      "confidence": "high",
      "extracted_at": "2026-08-15T10:00:00Z"
    }
  }
}
```

**Validation Rules**:
- `schema_version`: Required string
- `source`: Required, must be dubizzle/dubicars/yallamotor
- `source_url`: Required, must match source domain (spoofing check)
- `source_listing_id`: Optional, used for duplicate detection
- `captured_at`: Required, ISO 8601 format
- `vehicle.title`: Optional, required if make not provided
- `vehicle.make`: Optional, required if title not provided
- `vehicle.price_aed`: Required, numeric ≥ 0
- `images`: Array, max 50, URLs max 2000 chars
- `diagnostics`: Array, filtered to safe fields only

**Response**:
```json
{
  "status": "success",
  "queue_item_id": 42,
  "duplicate_detected": {
    "slug": "toyota-camry-2020",
    "make": "Toyota",
    "model": "Camry",
    "year": "2020",
    "price_aed": 50000
  },
  "review_url": "https://navracar.com/staging/admin/import-queue/42"
}
```

## Troubleshooting

### "Missing authentication token" Error

**Problem**: Extension shows 401 error
**Solution**: 
1. Admin must generate pairing code
2. Re-paste the code within 10 minutes (codes expire)
3. Check clock sync on device

### "Source mismatch" Error

**Problem**: Capture rejected with source validation error
**Solution**:
- Ensure URL is actually from claimed marketplace
- www.dubizzle.com and dubizzle.com both accepted
- Check for marketplace redirects (may redirect to different domain)

### Images Not Importing

**Problem**: Status stuck on "images_pending"
**Solution**:
1. Check if image URLs are publicly accessible
2. Verify images aren't behind authentication
3. Try manual retry from admin panel
4. Check server logs for download errors

### Batch Capture Shows No Tabs

**Problem**: "هیچ صفحهٔ پشتیبانی‌شده‌ای باز نیست"
**Solution**:
- Ensure you have open tabs on supported marketplaces
- Check if tab URLs contain domain correctly
- Refresh tabs if recently navigated

## Deployment

### Staging Deployment

1. Build staging extension:
   ```bash
   npm run build:staging
   ```

2. Test locally (load unpacked in Chrome/Edge)

3. Run test suite:
   ```bash
   ./vendor/bin/phpunit tests/Feature/Browser* --no-coverage
   ```

4. Commit to branch:
   ```bash
   git add tools/navra-capture-extension/build/
   git commit -m "build(staging): Extension ready for staging"
   ```

5. Deploy alongside staging backend

### Production Deployment (Future)

1. Build production extension:
   ```bash
   npm run build:production
   ```

2. Submit to Chrome Web Store:
   - Category: Productivity
   - Content rating: General (no adult content)
   - Screenshots showing UI and workflow

3. Version numbering follows semantic versioning:
   - Major: Large feature sets
   - Minor: New features
   - Patch: Bug fixes

## Support & Maintenance

### Monitoring

Admin panel shows:
- **Browser Capture Settings**: Active pairings, last used time
- **Import Queue**: Queue status, image import errors
- **Diagnostics**: Extraction confidence by marketplace, field success rates

### Common Maintenance Tasks

**Disable Problematic Pairing**:
```php
BrowserExtensionPairing::find($id)->revoke();
```

**Check Token Usage**:
```php
BrowserExtensionPairing::where('environment', 'staging')
    ->orderBy('last_used_at', 'desc')
    ->get();
```

**Clear Old Captures** (script):
```php
ImportQueue::where('status', 'failed')
    ->where('created_at', '<', now()->subDays(30))
    ->forceDelete();
```

## License & Attribution

Navra Capture Browser Extension
- Part of NavraCar Platform
- Developed by NavraCar Team
- License: Internal Use Only (Staging)

---

**Version**: 1.0.0 (Phase 2/3)
**Last Updated**: 2026-08-15
**Status**: Staging Ready
