# Browser Extension Manual Acceptance Testing Guide

## Pre-Test Setup

### Environment
- [ ] Chrome or Edge browser (version 90+)
- [ ] Staging instance running: https://navracar.com/staging
- [ ] Admin account created and logged in
- [ ] Extension build: `npm run build:staging`
- [ ] Extension loaded in Chrome/Edge (Developer mode)

### Test Marketplace Accounts
- [ ] Dubizzle account (optional, can test without)
- [ ] DubiCars links ready
- [ ] YallaMotor links ready

## Test Scenarios

---

## Part 1: Installation & Pairing

### Test 1.1: Install Extension (Chrome)

**Steps**:
1. Open Chrome
2. Navigate to `chrome://extensions/`
3. Enable "Developer mode" (top right toggle)
4. Click "Load unpacked"
5. Select `tools/navra-capture-extension` folder
6. Verify extension appears in toolbar

**Expected Result**:
- [ ] Extension icon visible in toolbar
- [ ] "Navra Capture" name shown in extensions list
- [ ] Version "1.0.0" displayed
- [ ] No error messages in console

**Notes**: Repeat for Edge using `edge://extensions/`

---

### Test 1.2: Install Extension (Edge)

**Steps**: Same as Test 1.1 but for Edge browser

**Expected Result**:
- [ ] Extension loads successfully
- [ ] Keyboard shortcut Alt+Shift+N works

---

### Test 1.3: Generate Pairing Code

**Steps**:
1. Open NavraCar staging admin: https://navracar.com/staging/admin
2. Navigate to **Settings → Browser Capture**
3. Click **Generate Pairing Code** button
4. Copy the 6-digit code that appears

**Expected Result**:
- [ ] Code is exactly 6 digits (0-9)
- [ ] Code can be copied to clipboard
- [ ] Code remains valid for 10 minutes
- [ ] Admin panel shows "Code generated at [time]"

---

### Test 1.4: Pair Extension with Staging Account

**Steps**:
1. Click Navra Capture extension icon in toolbar
2. Select "دریافت کنید" (Connect) tab (or "Auth" tab)
3. Paste the 6-digit pairing code
4. Click "Connect" button
5. Wait for response

**Expected Result**:
- [ ] Success message in Farsi: "اتصال با موفقیت برقرار شد"
- [ ] Popup closes or transitions to main capture view
- [ ] Extension UI shows "متصل" or "Connected" state
- [ ] Admin panel shows extension as "active"

**Error Scenarios**:
- [ ] Invalid code (5 digits): "Invalid code format"
- [ ] Expired code (>10 min): "Code expired"
- [ ] Wrong code: "Invalid pairing code"

---

### Test 1.5: Disconnect Pairing

**Steps**:
1. Open extension popup
2. Click **Settings** (gear icon)
3. Click **Disconnect** button
4. Confirm disconnect

**Expected Result**:
- [ ] Extension shows pairing form again
- [ ] Admin panel shows extension as "revoked"
- [ ] Cannot send captures until re-paired

---

## Part 2: Single Capture

### Test 2.1: Capture from Dubizzle

**Steps**:
1. Navigate to a used car listing on https://dubizzle.com/motors/used-cars/
2. Click extension icon → preview should appear
3. Verify all fields are populated:
   - [ ] Title (e.g., "Toyota Camry 2020")
   - [ ] Make/Model (e.g., "Toyota / Camry")
   - [ ] Year (e.g., "2020")
   - [ ] Price in AED (e.g., "50,000 AED")
   - [ ] Mileage (e.g., "45,000 km")
   - [ ] Fuel type (e.g., "Petrol")
   - [ ] Engine (e.g., "2.5L")
   - [ ] Images count (e.g., "8 تصویر")
4. Click **Send Capture**
5. Popup shows success message in Farsi

**Expected Result**:
- [ ] All vehicle fields extracted correctly
- [ ] At least 1 image URL detected
- [ ] Send succeeds with status 200
- [ ] Review URL opens in new tab (staging admin)
- [ ] ImportQueue item created with status "images_pending"

**Error Scenarios**:
- [ ] Unsupported page: "صفحه پشتیبانی نشده"
- [ ] Not authenticated: "لطفا ابتدا جفت سازی را انجام دهید"
- [ ] Network error: "خطا در ارسال" (Error sending)

---

### Test 2.2: Capture from DubiCars

**Steps**:
1. Navigate to a car listing on https://dubicars.com/car/[ID]
2. Open extension popup
3. Verify detection and extraction

**Expected Result**:
- [ ] Title detected correctly
- [ ] Make/model fields populated
- [ ] At least 1 image URL found
- [ ] Send succeeds

---

### Test 2.3: Capture from YallaMotor

**Steps**:
1. Navigate to a car listing on https://yallamotor.com/
2. Open extension popup
3. Verify detection and extraction

**Expected Result**:
- [ ] Listing detected
- [ ] Vehicle data extracted
- [ ] Send succeeds

---

### Test 2.4: Keyboard Shortcut Alt+Shift+N

**Steps**:
1. Navigate to any supported marketplace listing
2. Press **Alt+Shift+N** (or **⌥⇧N** on Mac)
3. Wait ~2 seconds

**Expected Result**:
- [ ] Capture triggered automatically
- [ ] Notification appears (browser notification or console)
- [ ] Success: "خودرو با موفقیت ارسال شد"
- [ ] Error: "خطا: [reason]"

**Edge Cases**:
- [ ] On unsupported page: "صفحه پشتیبانی نشده"
- [ ] Not authenticated: "لطفا اتصال را برقرار کنید"
- [ ] Timeout (>10s): "زمان انتظار ختم شد"

---

### Test 2.5: Missing Required Fields

**Steps**:
1. Go to a marketplace that's missing price info
2. Open extension popup
3. Attempt to send

**Expected Result**:
- [ ] Warning appears: "فیلدهای الزامی موجود نیستند"
- [ ] Show missing fields list: title, make, price
- [ ] Send button disabled or shows warning
- [ ] API rejects with 422: "price_aed is required"

---

## Part 3: Batch Capture

### Test 3.1: Batch Capture Multiple Tabs

**Setup**:
1. Open 5 different car listings in 5 separate tabs:
   - 2 from Dubizzle
   - 2 from DubiCars
   - 1 from YallaMotor

**Steps**:
1. Click extension icon in any tab
2. Click **Batch Capture** (multiple cars icon)
3. Verify all 5 tabs listed with checkboxes
4. Select first 3 tabs
5. Click **Send Multiple**
6. Watch progress bar: "0/3", "1/3", "2/3", "3/3"

**Expected Result**:
- [ ] All 5 tabs detected and listed
- [ ] Source correctly identified (Dubizzle/DubiCars/YallaMotor)
- [ ] Domain shown for each tab
- [ ] Progress updates as captures complete
- [ ] Results show per-tab status
- [ ] At least 2 succeed (✓ green)
- [ ] Admin panel shows 3 new ImportQueue items

**Error Handling**:
- [ ] If 1 tab fails, other 2 still succeed
- [ ] Per-tab error message shown: "✗ صفحه N: error"

---

### Test 3.2: Batch Capture with Mix of Success/Failure

**Setup**:
1. Open 3 tabs:
   - Tab 1: Valid Dubizzle listing
   - Tab 2: Invalid/incomplete listing (no price)
   - Tab 3: Valid DubiCars listing

**Steps**:
1. Open batch capture
2. Select all 3
3. Send

**Expected Result**:
- [ ] Batch progress shows: "0/3", "1/3", "2/3", "3/3"
- [ ] Results:
  - Tab 1: ✓ Success
  - Tab 2: ✗ Error (missing required fields)
  - Tab 3: ✓ Success
- [ ] Admin panel shows 2 successful imports (not 3)

---

### Test 3.3: Batch Capture Empty List

**Setup**:
1. Close all marketplace tabs
2. Open tabs on non-marketplace sites (news, gmail, etc.)

**Steps**:
1. Open batch capture

**Expected Result**:
- [ ] Message shown: "هیچ صفحهٔ پشتیبانی‌شده‌ای باز نیست"
- [ ] Send button disabled (greyed out)

---

### Test 3.4: Batch Capture Select All / Deselect All

**Setup**:
1. Open 4 supported marketplace tabs

**Steps**:
1. Open batch capture
2. See all 4 tabs listed
3. Click "Select All" (if available) or manually check all
4. Verify send button enabled
5. Uncheck all
6. Verify send button disabled

**Expected Result**:
- [ ] Toggling selection enables/disables send button
- [ ] Can select individual tabs or all at once
- [ ] Logic is consistent

---

## Part 4: Admin Panel Review

### Test 4.1: ImportQueue List View

**Steps**:
1. Send several captures from extension
2. Go to admin panel: https://navracar.com/staging/admin/import-queue
3. Verify list shows recent captures

**Expected Result**:
- [ ] All captures listed with:
  - Source (Dubizzle/DubiCars/YallaMotor)
  - Title
  - Status (images_pending, needs_review, published)
  - Date created
  - Image count
- [ ] Can filter by source or status
- [ ] Can sort by date

---

### Test 4.2: ImportQueue Detail View

**Steps**:
1. Click on a capture from the list
2. Verify all data displayed:
   - [ ] Vehicle title and specs
   - [ ] All vehicle fields (make, model, year, price)
   - [ ] Extracted images
   - [ ] Diagnostics (extraction sources)
   - [ ] Suggested customs price

**Expected Result**:
- [ ] All captured data visible and editable
- [ ] Images show thumbnails
- [ ] Edit form allows updating any field
- [ ] Save button works
- [ ] Publish button available if status is needs_review

---

### Test 4.3: Publish to CarListing

**Steps**:
1. Find a completed import (all images downloaded, status = needs_review)
2. Click **Publish** button

**Expected Result**:
- [ ] New CarListing created
- [ ] ImportQueue status changed to "published"
- [ ] Redirects to CarListing edit page
- [ ] Images linked to listing
- [ ] First image marked as cover

---

### Test 4.4: Duplicate Detection

**Steps**:
1. Send same listing twice from extension
2. Check second capture shows duplicate warning

**Expected Result**:
- [ ] First capture: "duplicate_detected: null"
- [ ] Second capture: "duplicate_detected: {slug, make, model, year, price}"
- [ ] Warning shown in admin panel
- [ ] Can still publish or skip

---

## Part 5: Security Testing

### Test 5.1: Authentication Required

**Steps**:
1. Disconnect extension pairing
2. Try to send a capture

**Expected Result**:
- [ ] Capture rejected: 401 Unauthorized
- [ ] Error message: "لطفا ابتدا جفت سازی را انجام دهید"
- [ ] No data stored in database

---

### Test 5.2: Malformed Authorization Header

**Manual API Test**:
```bash
curl -X POST https://navracar.com/staging/api/browser-capture/v1/listings \
  -H "Authorization: InvalidHeader token-here" \
  -H "Content-Type: application/json" \
  -d '{"schema_version": "navracar.capture.v1", ...}'
```

**Expected Result**:
- [ ] 401 response: "Missing authentication token"

---

### Test 5.3: Source Domain Spoofing Prevention

**Manual API Test**:
```bash
# Claim dubizzle but use dubicars URL
curl -X POST https://navracar.com/staging/api/browser-capture/v1/listings \
  -H "Authorization: Bearer [valid-token]" \
  -H "Content-Type: application/json" \
  -d '{
    "source": "dubizzle",
    "source_url": "https://dubicars.com/car/123",
    ...
  }'
```

**Expected Result**:
- [ ] 422 response: "Source mismatch"
- [ ] No ImportQueue created

---

### Test 5.4: Payload Size Limits

**Manual API Test** (too many images):
```bash
# Send 51 images (limit is 50)
curl -X POST https://navracar.com/staging/api/browser-capture/v1/listings \
  -H "Authorization: Bearer [valid-token]" \
  -H "Content-Type: application/json" \
  -d '{
    "images": [
      {"url": "https://example.com/1.jpg", "confidence": "high"},
      ... (51 times)
    ],
    ...
  }'
```

**Expected Result**:
- [ ] 422 response: "Too many images"

---

### Test 5.5: SQL Injection Attempt

**Manual API Test**:
```bash
curl -X POST https://navracar.com/staging/api/browser-capture/v1/listings \
  -H "Authorization: Bearer [valid-token]" \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle": {
      "title": "'\'' DROP TABLE car_listings; --",
      ...
    }
  }'
```

**Expected Result**:
- [ ] 200 response (accepted)
- [ ] Dangerous string stored safely in database
- [ ] No SQL injection executed
- [ ] String displayed as-is in admin (not executed)

---

### Test 5.6: XSS Prevention

**Manual API Test**:
```bash
curl -X POST https://navracar.com/staging/api/browser-capture/v1/listings \
  -H "Authorization: Bearer [valid-token]" \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle": {
      "title": "<script>alert(\"XSS\")</script>",
      ...
    }
  }'
```

**Expected Result**:
- [ ] 200 response (accepted)
- [ ] Script tag stored as literal text
- [ ] When displayed in admin panel, shows as text (escaped)
- [ ] No script execution in browser

---

## Part 6: Diagnostics & Error Handling

### Test 6.1: Diagnostics Recording

**Steps**:
1. Send a capture with mixed extraction sources:
   - Some fields from JSON-LD
   - Some from meta tags
   - Some from selectors
2. Check admin panel detail view

**Expected Result**:
- [ ] Diagnostics tab shows extraction metadata:
  - Field name
  - Found (true/false)
  - Source (json-ld/meta/microdata/selector)
  - Confidence (high/medium/low)
- [ ] Can identify which extraction worked best

---

### Test 6.2: Handling Marketplace Changes

**Steps**:
1. Go to a real Dubizzle listing
2. Open extension
3. If page HTML changes unexpectedly, observe behavior

**Expected Result**:
- [ ] Extension still attempts extraction
- [ ] Falls back to other sources if primary unavailable
- [ ] Shows warning if insufficient data
- [ ] Doesn't crash browser

---

### Test 6.3: Image Import Retry

**Steps**:
1. Send a capture with 3 images
2. If one image fails (e.g., 404), check progress

**Expected Result**:
- [ ] Other 2 images still downloaded
- [ ] Partial success OK (2/3 images)
- [ ] Status moves to needs_review (not failed)
- [ ] Admin panel shows image_count vs images_imported

---

## Part 7: UI/UX Testing

### Test 7.1: UI Language Consistency

**Steps**:
1. Open extension in different scenarios
2. Verify all text is in Farsi (RTL-friendly)

**Expected Result**:
- [ ] Buttons labeled in Farsi
- [ ] Status messages in Farsi
- [ ] Error messages in Farsi
- [ ] UI flows RTL (right-to-left)

**Sample Farsi Text**:
- Connect: "اتصال به NavraCar"
- Send: "ارسال"
- Error: "خطا"
- Success: "موفقیت"
- Batch: "ارسال چندگانه"

---

### Test 7.2: Loading States

**Steps**:
1. Send a capture
2. While processing, observe UI

**Expected Result**:
- [ ] Send button shows loading spinner
- [ ] Button becomes disabled while processing
- [ ] Text fades during loading
- [ ] After 5-10 seconds, loading state clears
- [ ] Success message appears

---

### Test 7.3: Responsive Design

**Steps**:
1. Open extension popup
2. Verify popup size is reasonable (not too large)
3. Try with different screen sizes

**Expected Result**:
- [ ] Popup width: 420px
- [ ] Content scrolls if needed (not cut off)
- [ ] Images scale properly
- [ ] Fields align correctly in RTL

---

## Part 8: Performance Testing

### Test 8.1: Batch Capture Performance

**Steps**:
1. Open 10 supported marketplace tabs
2. Run batch capture on all 10
3. Monitor time and responsiveness

**Expected Result**:
- [ ] All 10 processed within 30 seconds
- [ ] Each capture takes ~2-3 seconds
- [ ] Progress updates smoothly
- [ ] Browser doesn't freeze
- [ ] Admin panel updated correctly

---

### Test 8.2: Image Download Performance

**Steps**:
1. Send a capture with 20 images
2. Monitor download progress

**Expected Result**:
- [ ] Images download in parallel
- [ ] Completes within 30-60 seconds
- [ ] Partial success if some fail
- [ ] No timeout errors

---

## Part 9: Environment Isolation

### Test 9.1: Staging Token Isolation

**Steps**:
1. Generate pairing code in staging
2. Pair extension to staging token
3. Verify token works only for staging

**Expected Result**:
- [ ] Token authenticated successfully
- [ ] Captures sent to staging API
- [ ] Admin panel (staging) shows imports
- [ ] Cannot access production environment

---

## Test Summary

### Test Results Template

| Test ID | Title | Status | Notes |
|---------|-------|--------|-------|
| 1.1 | Install Chrome | ✅/❌ | |
| 1.2 | Install Edge | ✅/❌ | |
| 1.3 | Generate Code | ✅/❌ | |
| 1.4 | Pair Extension | ✅/❌ | |
| 1.5 | Disconnect | ✅/❌ | |
| 2.1 | Capture Dubizzle | ✅/❌ | |
| 2.2 | Capture DubiCars | ✅/❌ | |
| 2.3 | Capture YallaMotor | ✅/❌ | |
| 2.4 | Keyboard Shortcut | ✅/❌ | |
| 2.5 | Missing Fields | ✅/❌ | |
| 3.1 | Batch Capture | ✅/❌ | |
| 3.2 | Mixed Success/Fail | ✅/❌ | |
| 3.3 | Empty List | ✅/❌ | |
| 3.4 | Select/Deselect | ✅/❌ | |
| 4.1 | Admin List View | ✅/❌ | |
| 4.2 | Admin Detail View | ✅/❌ | |
| 4.3 | Publish | ✅/❌ | |
| 4.4 | Duplicate Detection | ✅/❌ | |
| 5.1 | Auth Required | ✅/❌ | |
| 5.2 | Malformed Header | ✅/❌ | |
| 5.3 | Source Spoofing | ✅/❌ | |
| 5.4 | Payload Limits | ✅/❌ | |
| 5.5 | SQL Injection | ✅/❌ | |
| 5.6 | XSS Prevention | ✅/❌ | |
| 6.1 | Diagnostics | ✅/❌ | |
| 6.2 | Page Changes | ✅/❌ | |
| 6.3 | Image Retry | ✅/❌ | |
| 7.1 | Language | ✅/❌ | |
| 7.2 | Loading States | ✅/❌ | |
| 7.3 | Responsive | ✅/❌ | |
| 8.1 | Batch Perf | ✅/❌ | |
| 8.2 | Image Perf | ✅/❌ | |
| 9.1 | Env Isolation | ✅/❌ | |

### Sign-Off

**Tester Name**: ___________________

**Date**: ___________________

**Overall Status**: ✅ PASS / ⚠️ PASS WITH ISSUES / ❌ FAIL

**Issues Found**:
- [ ] Critical (blocks release)
- [ ] Major (functionality broken)
- [ ] Minor (cosmetic/edge case)

**Issues List**:
1. ...
2. ...

**Approved for Staging**: ✅ YES / ❌ NO

**Signature**: ___________________

---

**Version**: 1.0.0
**Last Updated**: 2026-08-15
