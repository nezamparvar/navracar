# Navra Capture Browser Extension

Navra Capture is a production-quality Chrome/Edge browser extension that enables users to capture vehicle listing information from major marketplaces and import it into NavraCar with a single click.

## Supported Platforms

- **Dubizzle** (dubizzle.com)
- **DubiCars** (dubicars.com)
- **YallaMotor** (yallamotor.com)

## Features

### One-Click Capture

Users can click the extension icon on any supported vehicle listing to:
- Extract structured vehicle information
- Capture all listing images
- Display a preview before sending
- Send to NavraCar import queue with one click

### Smart Detection

- Automatically detects when viewing a supported vehicle listing
- Shows green badge when listing is detected
- Displays appropriate message for unsupported pages

### Secure Authentication

- Pairing code-based authentication
- No marketplace cookies transmitted
- Scoped extension-only token storage
- Ability to disconnect and re-authenticate

### Environment Management

- Support for Staging and Production environments
- Visual indicator (STAGING badge) in test builds
- Easy environment switching via settings

### Duplicate Detection

- Identifies if vehicle is already in system
- Shows existing listing details for comparison
- Allows update or separate import decision

## Architecture

### Extension Structure

```
src/
├── adapters/           # Site-specific extraction adapters
│   ├── base-adapter.ts
│   ├── dubizzle-adapter.ts
│   ├── dubicars-adapter.ts
│   └── yallamotor-adapter.ts
├── background/         # Service worker & API communication
│   └── service-worker.js
├── content/            # Page content extraction
│   └── content-script.js
├── popup/              # User interface
│   ├── popup.html
│   ├── popup.css
│   └── popup.js
└── shared/             # Shared types & constants
    └── types.ts
```

### Capture Payload (v1)

```typescript
{
  schema_version: "navracar.capture.v1",
  source: "dubizzle" | "dubicars" | "yallamotor",
  source_url: string,
  source_listing_id: string | null,
  captured_at: ISO8601 timestamp,
  page_title: string,

  vehicle: {
    title?: string,
    make?: string,
    model?: string,
    trim?: string,
    year?: string,
    price_aed?: number,
    mileage_km?: string,
    fuel_type?: string,
    engine?: string,
    transmission?: string,
    body_type?: string,
    // ... additional fields
  },

  images: { url: string, confidence: 'high'|'medium'|'low' }[],

  diagnostics: { [field]: { found: boolean, confidence: 'high'|'medium'|'low' } }
}
```

### Data Flow

```
Browser Extension                NavraCar Backend
     ↓                                  ↓
Content Script extracts        POST /api/browser-capture/v1/listings
    data (site-specific)            ↓
     ↓                         Validate payload
Service Worker                      ↓
    ↓                          Check for duplicates
Authenticate                        ↓
    ↓                          Create ImportQueue item
Send to API                         ↓
    ↓                          Queue image imports
Return review URL                   ↓
    ↓                          Return response
Open review page                NavraCar admin views
    in browser                 captured data for review
```

## Security Features

### No Compromised Permissions

- ✅ No `<all_urls>` permission
- ✅ No history access
- ✅ No downloads permission
- ✅ No clipboardRead
- ✅ No webRequest interception

### Host Permissions

Only exact marketplace domains:

```json
"host_permissions": [
  "*://*.dubizzle.com/*",
  "*://*.dubicars.com/*",
  "*://*.yallamotor.com/*"
]
```

### Token Security

- Scoped to `vehicle-import:capture` only
- Never logged or transmitted over HTTP
- Stored locally via `chrome.storage.local`
- Revocable from NavraCar admin
- No access to page JavaScript

### Data Protection

- No browser passwords extracted
- No authentication cookies reused
- No browser fingerprinting
- Only already-rendered page content captured
- No autonomous background scraping

## Installation

### Unpacked Development

```bash
# 1. Navigate to extension directory
cd tools/navra-capture-extension

# 2. Build extension
npm install
npm run build:staging

# 3. Load in Chrome/Edge
# Chrome: chrome://extensions → Developer mode → Load unpacked
# Edge: edge://extensions → Developer mode → Load unpacked
# Select: dist/staging directory
```

### Production Deployment

```bash
# Build production version
npm run build:production

# Generated: dist/production directory
# Can be packaged for Chrome Web Store deployment
```

## Backend Integration

### API Endpoint

```
POST /api/browser-capture/v1/listings
Authorization: Bearer <extension-token>
Content-Type: application/json

Body: CapturePayload
```

### Response

```json
{
  "status": "success",
  "queue_item_id": 42,
  "duplicate_detected": {
    "slug": "listing-slug",
    "make": "Toyota",
    "model": "Corolla",
    "year": "2020",
    "price_aed": 50000
  },
  "review_url": "https://navracar.com/admin/import-queue/42"
}
```

### Database Schema

```sql
CREATE TABLE import_queues (
  id INT PRIMARY KEY AUTO_INCREMENT,
  source ENUM('dubizzle', 'dubicars', 'yallamotor'),
  source_listing_id VARCHAR(255),
  source_url VARCHAR(2000),
  source_method ENUM('browser_extension', 'direct_url', 'manual_html'),
  status ENUM('captured', 'parsing', 'needs_review', 'images_pending', 'ready', 'failed', 'published'),
  car_listing_id INT FOREIGN KEY,
  captured_data JSON,
  parsed_data JSON,
  image_count INT,
  images_imported INT,
  duplicate_detected_with VARCHAR(255) FOREIGN KEY,
  created_at TIMESTAMP,
  published_at TIMESTAMP
);
```

## Testing

### Manual Testing

See `docs/NAVRA_CAPTURE_TESTING.md` for comprehensive testing guide.

### Unit Tests

```bash
npm test
```

### Test Coverage

- Adapter detection (URL matching)
- Capture payload validation
- Duplicate detection logic
- API communication
- Authentication flow

## Configuration

### Staging Build

```json
{
  "name": "Navra Capture — Staging",
  "baseUrl": "https://navracar.com/staging",
  "apiUrl": "https://navracar.com/staging/api"
}
```

### Production Build

```json
{
  "name": "Navra Capture",
  "baseUrl": "https://navracar.com",
  "apiUrl": "https://navracar.com/api"
}
```

## Development Workflow

### Modify Adapters

When a marketplace updates its page structure:

1. **DubizzleAdapter** - Update `extractVehicleData()`
2. **DubiCarsAdapter** - Update selectors and extraction
3. **YallaMotorAdapter** - Update extraction methods

Only the specific adapter needs updating. Others are unaffected.

### Add New Marketplace

1. Create `src/adapters/new-adapter.ts`
2. Extend `SourceAdapter` base class
3. Register in `adapter-registry.ts`
4. Update manifest.json host permissions
5. Add tests in `tests/adapters.test.ts`
6. Update documentation

## Future Enhancements

### Keyboard Shortcuts

```
Alt + Shift + N: Capture current page
Alt + Shift + B: Batch capture open tabs
```

### Context Menu

```
Right-click → "Send vehicle to NavraCar"
```

### Batch Capture

```
- Scan all open tabs for supported listings
- Show checklist of detected vehicles
- Send selected in single operation
- No page navigation required
```

### Advanced Features

- Automatic price conversion (AED → Toman)
- VIN decoding integration
- Damage history lookup
- Market price comparison
- Automatic categorization

## Troubleshooting

### "This page is not a supported vehicle listing"

- Ensure page fully loaded (wait for images)
- Check URL matches marketplace pattern
- Try refreshing the page
- Check browser console for errors

### Authentication fails

- Verify pairing code is correct (6 digits)
- Confirm NavraCar is accessible
- Check Internet connection
- Clear extension storage and re-authenticate

### Images don't import

- Verify image URLs are publicly accessible
- Check image file sizes
- Verify backend image download permissions
- Check ImportQueue item error logs

### Duplicate not detected

- URL format may differ slightly
- Make/model normalization mismatch
- Check database for existing listing
- May need duplicate detection logic adjustment

## Support

For issues or feature requests:

1. Check `docs/NAVRA_CAPTURE_TESTING.md` troubleshooting section
2. View service worker console logs
3. Inspect network requests in DevTools
4. Contact development team with error logs

## License

Proprietary - NavraCar
