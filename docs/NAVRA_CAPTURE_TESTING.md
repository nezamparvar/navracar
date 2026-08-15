# Navra Capture Extension Testing Guide

## Installation

### Chrome

1. Open `chrome://extensions/`
2. Enable **Developer mode** (toggle in top right)
3. Click **Load unpacked**
4. Navigate to `/tools/navra-capture-extension` and select it
5. The extension should appear in your browser

### Microsoft Edge

1. Open `edge://extensions/`
2. Enable **Developer mode** (toggle on left sidebar)
3. Click **Load unpacked**
4. Navigate to `/tools/navra-capture-extension` and select it
5. The extension should appear in your browser

## Initial Setup

### Pairing

1. Open the extension popup
2. Navigate to **NavraCar admin** → **Settings** → **Browser Capture**
3. Click **Generate Pairing Code**
4. Copy the 6-digit code
5. In the extension popup, enter the code and click **Connect**
6. Select environment (Staging recommended for testing)

### Environment Selection

- **Staging**: `https://navracar.com/staging` (recommended for testing)
- **Production**: `https://navracar.com` (use only after final approval)

## Testing Workflow

### Dubizzle Listing

1. Open a real Dubizzle vehicle listing:
   - Example: `https://dubai.dubizzle.com/motors/used-cars/...`

2. Click the **Navra Capture** extension icon
   - Should show green checkmark if listing detected
   - Popup shows vehicle preview (title, make, model, year, price, images)

3. Verify preview data is correct:
   - Image from listing displays
   - Make/model extracted
   - Year extracted
   - Price in AED extracted
   - Image count shows all images

4. Click **ارسال به ناوراکار** (Send to NavraCar)
   - Should show success message
   - Browser should open NavraCar review page
   - URL should be: `https://navracar.com/staging/admin/import-queue/[id]`

5. In NavraCar, verify:
   - All captured fields appear in import queue item
   - Source shows as "Dubizzle"
   - Status is "captured"
   - Images are queued for import
   - Can edit fields before publishing

### DubiCars Listing

1. Open a DubiCars vehicle listing (if available in region)
2. Repeat same steps as Dubizzle
3. Verify extraction works for DubiCars-specific markup

### YallaMotor Listing

1. Open a YallaMotor vehicle listing
2. Repeat same steps as Dubizzle
3. Verify extraction works for YallaMotor-specific markup

## Edge Cases

### Unsupported Page

1. Navigate to a non-listing page (e.g., Dubizzle search results)
2. Click extension icon
3. Should show: "این صفحه یک آگهی خودروی قابل‌شناسایی نیست."
4. Should NOT offer send button

### Not Authenticated

1. Clear extension storage: `chrome://extensions` → Navra Capture → **Clear data**
2. Open any Dubizzle listing
3. Click extension icon
4. Should show authentication screen
5. Should NOT show vehicle preview

### Duplicate Detection

1. Open an existing listing on Dubizzle
2. Send via extension
3. NavraCar should detect if listing already imported
4. Review page should show:
   - "Existing listing found"
   - Option to update existing
   - Option to create separate

### Missing Required Fields

1. Open a listing with missing make/model or price
2. Click extension
3. Should show:
   - Yellow warning box
   - List of missing fields
   - "ارسال شود رغم" (Send anyway) button
   - Should still allow send (goes to needs_review status)

### Multiple Open Tabs Batch Capture

1. Open 3+ vehicle listings in different tabs
2. Right-click extension icon
3. Click "ارسال خودروهای باز" (Send open vehicles)
4. Should show popup with checkboxes for each tab
5. Select which ones to send
6. Should send all selected without navigating tabs

## Keyboard Shortcut

1. Press `Alt + Shift + N` while on a listing page
2. Should trigger same capture as clicking extension
3. Should show same preview/send workflow

## Connection Management

### Switch Environment

1. Open extension → Settings
2. Change environment from Staging to Production
3. Confirm warning dialog
4. Should update all future captures to Production endpoints

### Disconnect

1. Open extension → Settings
2. Click **قطع اتصال** (Disconnect)
3. Confirm warning
4. Should clear stored token
5. Next time opened, should show auth screen

## API Integration

### Backend Validation

1. Capture data should validate:
   - Required schema_version
   - Valid source (dubizzle/dubicars/yallamotor)
   - Valid source_url
   - At least title OR make/model
   - Valid price_aed

2. Send invalid payload (missing price):
   - Should return error
   - Extension should show error message
   - Should NOT create import queue item

### Duplicate Detection

1. Send same Dubizzle listing twice
2. First send: creates queue item
3. Second send: detects duplicate
4. Response includes:
   - `duplicate_detected` with existing listing slug
   - Existing price/make/model/year for comparison

### Image Import

1. Captured payload includes image URLs
2. Backend should enqueue image imports
3. Each image should be:
   - Downloaded by backend
   - Validated (MIME type, size)
   - Stored with safe filename
   - Linked to import queue item

## Staging vs Production

### Staging Markers

1. Open staging extension
2. Popup should display **STAGING** badge
3. All API calls go to `https://navracar.com/staging/api`
4. Review URLs point to staging

### Production Build

1. Extension built as `navra-capture-production.zip`
2. Contains no "STAGING" text in UI
3. API calls go to production
4. Only deployed after owner approval

## Manual Checklist

### Dubizzle

- [ ] Opens listing with image
- [ ] Shows make/model from URL
- [ ] Shows year from data-testid
- [ ] Shows AED price
- [ ] Shows mileage
- [ ] Shows transmission
- [ ] Shows body type
- [ ] Shows all images
- [ ] Send completes without error
- [ ] Review page opens
- [ ] Duplicate detection works
- [ ] Can edit all fields

### DubiCars

- [ ] Opens listing
- [ ] Extracts title/make/model/year
- [ ] Extracts price
- [ ] Shows images
- [ ] Send completes
- [ ] Review page opens

### YallaMotor

- [ ] Opens listing
- [ ] Extracts title
- [ ] Extracts price if available
- [ ] Shows images
- [ ] Send completes
- [ ] Review page opens

### General

- [ ] Popup shows green checkmark on supported sites
- [ ] Popup shows no badge on unsupported sites
- [ ] Authentication flow works
- [ ] Can disconnect and re-authenticate
- [ ] Environment switch works
- [ ] History clear works
- [ ] No browser errors in console
- [ ] No data sent without explicit user click

## Debugging

### View Extension Logs

1. Right-click extension icon → **Manage extension**
2. Open service worker console
3. View messages from background script

### Content Script Debugging

1. Inspect page on listing
2. Open DevTools
3. Go to **Sources** tab
4. Find content script in `chrome-extension://...`
5. Set breakpoints

### Storage Debugging

1. Right-click extension → **Manage extension**
2. Under extension name, click **Details**
3. Click **Cookies and site data**
4. View stored auth token and environment

## Troubleshooting

### "صفحه پشتیبانی‌نشده" on Dubizzle

- Check URL matches pattern (contains `/motors/`)
- Check page loaded completely (wait for images)
- Try refresh
- Check browser console for errors

### Send fails with "Not authenticated"

- Extension lost token (browser cleared storage)
- Re-authenticate with pairing code
- Check Internet connection
- Confirm staging API is reachable

### Images don't import

- Backend may fail to download images
- Check image URLs are publicly accessible
- Check image sizes (should be <20MB each)
- View import queue item for error details

### Duplicate not detected

- URL format may differ slightly
- Make/model normalization mismatch
- Check database for existing listing
- May need to adjust duplicate detection logic

## Performance

- Should respond within 1 second of clicking
- Preview should render within 500ms
- Send should complete within 5 seconds
- No noticeable memory leaks after 10+ sends
