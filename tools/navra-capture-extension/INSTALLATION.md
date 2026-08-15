# Navra Capture Extension - Installation & Deployment Guide

## Quick Start (Staging)

### Prerequisites

- Google Chrome v90+ or Microsoft Edge v90+
- Node.js v18+
- npm v9+
- NavraCar running (staging preferred for testing)

### Installation Steps

#### 1. Build the Extension

```bash
# Navigate to extension directory
cd tools/navra-capture-extension

# Install dependencies
npm install

# Build both staging and production versions
npm run build
```

This creates:
- `dist/staging/` - Staging build (connects to https://navracar.com/staging)
- `dist/production/` - Production build (connects to https://navracar.com)

#### 2. Load in Chrome

1. Open `chrome://extensions/`
2. Enable **Developer mode** (toggle in top right)
3. Click **Load unpacked**
4. Select `/tools/navra-capture-extension/dist/staging`
5. Extension should appear in Chrome with Navra Capture icon

#### 3. Load in Edge

1. Open `edge://extensions/`
2. Enable **Developer mode** (toggle on left sidebar)
3. Click **Load unpacked**
4. Select `/tools/navra-capture-extension/dist/staging`
5. Extension should appear in Edge

### First-Time Setup

1. **Navigate to Dubizzle listing** (e.g., https://dubai.dubizzle.com/motors/used-cars/...)
2. **Click extension icon** - Should show vehicle preview
3. **Open NavraCar admin** → Settings → Browser Capture
4. **Generate pairing code** - Get 6-digit code
5. **Enter pairing code** in extension popup
6. **Select "Staging" environment** (for testing)
7. **Click "اتصال"** (Connect)
8. **Extension is now authenticated!**

## Development Build

For local development with hot reload:

```bash
# In extension directory
npm run dev

# Watch for file changes and rebuild
npm run build:watch  # (if implemented)
```

Then reload extension in Chrome:
1. Go to `chrome://extensions`
2. Find Navra Capture
3. Click the refresh icon

## Building for Production

### Production Build Process

```bash
# Build production version
npm run build:production

# Output: dist/production/
```

Production build differs from staging:
- Name: "Navra Capture" (no "Staging" label)
- API URL: https://navracar.com/api (not staging)
- No staging markers in UI

### Packaging for Chrome Web Store

```bash
# Create distributable package
cd dist/production && zip -r ../navra-capture-production.zip .

# Output: dist/navra-capture-production.zip (ready for Web Store upload)
```

### Deployment Checklist

Before deploying to production:

- [ ] All tests passing: `npm test`
- [ ] No console errors in staging
- [ ] Tested with real Dubizzle listing
- [ ] Tested with real DubiCars listing
- [ ] Tested with real YallaMotor listing
- [ ] Duplicate detection working
- [ ] Image import queued correctly
- [ ] Review page opens after send
- [ ] Disconnect/reconnect works
- [ ] Environment switching works
- [ ] No data transmitted to staging after switch to production
- [ ] Owner approval obtained
- [ ] Documentation updated

## Backend Setup

### 1. Run Database Migration

```bash
# Wait for composer to finish
composer install

# Run migration (creates import_queues table)
php artisan migrate

# Verify table created
php artisan tinker
>>> Schema::hasTable('import_queues')
=> true
```

### 2. Add API Route

Extension already adds route via `routes/api.php`:

```php
POST /api/browser-capture/v1/listings
```

Verify by checking routes:
```bash
php artisan route:list | grep browser-capture
```

Should show:
```
POST /api/browser-capture/v1/listings
```

### 3. Create Admin UI for Import Queue (Future)

Additional work needed to display ImportQueue items in admin:
- Add ImportQueueController
- Create admin/import-queue views
- Add menu items
- Implement status transitions

## Environment Management

### Configuration

Edit `src/background/service-worker.js`:

```javascript
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

### Runtime Switching

Users can switch environments in extension:
1. Click extension icon
2. Click **Settings** (gear icon)
3. Select environment dropdown
4. Change between Staging/Production
5. All future captures use new environment

### Detecting Current Environment

In staging build, popup shows **STAGING** badge to prevent accidental production data pollution.

In production build, no staging indicator is shown.

## Troubleshooting

### Extension doesn't load

**Problem**: Error message when trying to load unpacked

**Solution**:
1. Ensure `manifest.json` exists in selected directory
2. Verify `dist/staging` or `dist/production` was fully built
3. Check file permissions (should be readable by Chrome)
4. Try refreshing `chrome://extensions`

### Can't connect to NavraCar

**Problem**: Authentication fails after entering pairing code

**Solution**:
1. Verify NavraCar is running and accessible
2. Confirm URL is correct for environment
3. Check browser console for network errors
4. Verify pairing code is exactly 6 digits
5. Check NavraCar can reach backend API

### Listings not detected

**Problem**: Extension shows "unsupported page" on real Dubizzle listing

**Solution**:
1. Wait for page to fully load (including images)
2. Verify URL matches pattern (contains `/motors/`)
3. Check browser console for JavaScript errors
4. Try refreshing the page
5. Try different listing (may be site-specific structure)

### Images not importing

**Problem**: ImportQueue shows images but they don't download

**Solution**:
1. Verify image URLs are publicly accessible
2. Check image file sizes (should be <20MB)
3. Check backend error logs
4. Verify Laravel can reach external URLs (may need SSRF check)
5. Try with different images first

## File Structure After Build

```
dist/
├── staging/
│   ├── manifest.json
│   ├── src/
│   │   ├── background/service-worker.js
│   │   ├── content/content-script.js
│   │   ├── popup/
│   │   │   ├── popup.html
│   │   │   ├── popup.css
│   │   │   └── popup.js
│   │   └── icons/
│   │       ├── icon-16.png
│   │       ├── icon-48.png
│   │       └── icon-128.png
│   └── navra-capture-staging.zip (for Web Store)
│
└── production/
    ├── manifest.json
    ├── src/
    │   ├── background/service-worker.js
    │   ├── content/content-script.js
    │   ├── popup/
    │   ├── icons/
    └── navra-capture-production.zip (for Web Store)
```

## Updating the Extension

### For Developers

1. Make changes to source files in `src/`
2. Run `npm run build` to rebuild
3. Reload extension in Chrome (`chrome://extensions` refresh icon)
4. Test changes

### For Users (After Deployment)

Once deployed to Chrome Web Store:
1. Chrome auto-updates extension
2. No action required from users
3. New features available immediately

## Support & Monitoring

### Logs to Monitor

**Backend**:
```php
// Laravel logs
storage/logs/laravel.log

// Check for capture errors
Log::error('Browser capture error', ['source' => 'dubizzle'])
```

**Extension**:
```javascript
// Service worker logs
Right-click extension → Manage extension → Service worker
```

**Page**:
```javascript
// Content script logs
Inspect page → Console tab
```

### Common Issues to Monitor

1. **429 Too Many Requests** - Rate limiting kicked in
2. **Authentication errors** - Token expired or invalid
3. **Network timeouts** - NavraCar API unreachable
4. **Duplicate detection fails** - Logic needs refinement
5. **Images don't download** - Check image URL validity

## Performance Notes

- **Capture**: <500ms
- **Preview render**: <200ms
- **Send**: <5 seconds
- **Memory usage**: ~10MB per capture

## Next Steps

1. Run database migrations
2. Build staging extension
3. Load unpacked in Chrome
4. Test with real marketplace listings
5. Get owner approval
6. Build production version
7. Package for Chrome Web Store
8. Deploy production build
9. Create ImportQueue admin UI
10. Enable automatic image imports

## Questions?

Refer to:
- `README.md` - Architecture overview
- `docs/NAVRA_CAPTURE_TESTING.md` - Detailed testing guide
- Extension console logs - Debug information
- Backend logs - API errors
