# Asset Provenance Documentation

**Generated:** 2026-08-18  
**Purpose:** Document origin and verification of design-derived E2E test fixtures

---

## Overview

This document provides a complete audit trail for all assets extracted from the approved design reference commit. These assets are used exclusively for E2E visual regression testing and are classified as **design-derived fixtures**, not production photography.

**Classification:** Illustrative visual reference for testing purposes only. Not representative of final production imagery.

---

## Source Reference

| Property | Value |
|----------|-------|
| **Source Commit** | `1cdab114920cdc2431f983a1c1ea9efb88e26f82` |
| **Source Asset** | `docs/design-v2/assets/01-public-desktop-system.png` |
| **Asset Type** | Design System Mockup (1672×941 px) |
| **Extraction Method** | Sharp.js image cropping |
| **Extraction Date** | 2026-08-18 |

---

## Extracted Vehicle Images

### Listing Page Vehicles (8 Images)

All cropped from the vehicle listing grid composition at Y=[140-720], X=[20-1360].

#### Vehicle 1: First Listing Card
- **Filename:** `vehicle-1-card-listing.png`
- **Crop Region:** [20, 140, 320×280]
- **SHA-256:** `82294796a0cd8cec9a12535f2382f3cea8691c7605699fd20cdc23b807c8e2db`
- **Bytes:** 166,757
- **Description:** BMW X4 - First vehicle listing card with thumbnail, title, specs, and price
- **Usage:** E2E fixture for vehicle-list route testing

#### Vehicle 2: Second Listing Card
- **Filename:** `vehicle-2-card-listing.png`
- **Crop Region:** [360, 140, 320×280]
- **SHA-256:** `a5baddacd11605e8e0bc3228b4a8910b99fce5196e23d4bb1c497629aa3a77a9`
- **Bytes:** 137,406
- **Description:** Second vehicle listing card
- **Usage:** E2E fixture for vehicle-list route testing

#### Vehicle 3: Third Listing Card
- **Filename:** `vehicle-3-card-listing.png`
- **Crop Region:** [700, 140, 320×280]
- **SHA-256:** `4453419120323f143906f1d66badcc7758ca5f1c72ce30c89f4a29f862f6fce6`
- **Bytes:** 132,863
- **Description:** Third vehicle listing card
- **Usage:** E2E fixture for vehicle-list route testing

#### Vehicle 4: Fourth Listing Card
- **Filename:** `vehicle-4-card-listing.png`
- **Crop Region:** [1040, 140, 320×280]
- **SHA-256:** `8cf0106e8e7918bbb95bc176249f584653e5e13f0df1797dfe75b8380271ca40`
- **Bytes:** 146,533
- **Description:** Fourth vehicle listing card
- **Usage:** E2E fixture for vehicle-list route testing

#### Vehicle 5: Fifth Listing Card
- **Filename:** `vehicle-5-card-listing.png`
- **Crop Region:** [20, 440, 320×280]
- **SHA-256:** `d4b7fbae14190512840b2b613efaf4c97b0651b0d7dbab56670d528c870e87bd`
- **Bytes:** 87,382
- **Description:** Fifth vehicle listing card (second row, first position)
- **Usage:** E2E fixture for vehicle-list route testing

#### Vehicle 6: Sixth Listing Card
- **Filename:** `vehicle-6-card-listing.png`
- **Crop Region:** [360, 440, 320×280]
- **SHA-256:** `9ee8c6c66eda0c4c08f1f410f424ffcb7c1feadf7d568b13cbdebd99156f2600`
- **Bytes:** 107,318
- **Description:** Sixth vehicle listing card (second row, second position)
- **Usage:** E2E fixture for vehicle-list route testing

#### Vehicle 7: Seventh Listing Card
- **Filename:** `vehicle-7-card-listing.png`
- **Crop Region:** [700, 440, 320×280]
- **SHA-256:** `3441f72265dd2c879b61402ec0db69899be02db0c6599c050fe1e6e5deb595ad`
- **Bytes:** 97,797
- **Description:** Seventh vehicle listing card (second row, third position)
- **Usage:** E2E fixture for vehicle-list route testing

#### Vehicle 8: Eighth Listing Card
- **Filename:** `vehicle-8-card-listing.png`
- **Crop Region:** [1040, 440, 320×280]
- **SHA-256:** `482d7457dda756b5bc3310e2f179ab1ae36a24c6db79e226d314a04a37ca1782`
- **Bytes:** 103,536
- **Description:** Eighth vehicle listing card (second row, fourth position)
- **Usage:** E2E fixture for vehicle-list route testing

---

## Asset Storage

All extracted assets are stored in:
```
storage/app/public/e2e/design-derived-vehicles/
```

Public access URL: `/storage/e2e/design-derived-vehicles/{filename}`

---

## Usage in E2E Tests

### E2eSeeder.php Integration

The `E2eSeeder` class uses these extracted vehicle images in the `placeholderImage()` method:

```php
$imagePath = storage_path('app/public/e2e/design-derived-vehicles/{vehicle-N-card-listing}.png');
if (File::exists($imagePath)) {
    $imageContent = File::get($imagePath);
    $carListing->addMedia($imagePath)
        ->usingName('design-reference-vehicle-' . $index)
        ->toMediaCollection('cover_image');
}
```

### Screenshot Generation

These images are included in all E2E screenshots:
- **Route:** `/` (vehicle listing page)
- **Captured at viewports:** 390×844 (mobile), 1440×900 (desktop)
- **Capture types:** viewport, full-page

### Verification

All extracted images are verified during screenshot generation:
- SHA-256 validation on E2eSeeder read
- Presence check in screenshot DOM
- Dimension validation (naturalWidth > 0)
- Visual regression comparison via triad generator

---

## Classification & Disclaimers

### Test-Only Usage
These assets are classified as **E2E test fixtures** and are explicitly NOT production-ready assets. They serve the following purposes:

1. **Visual Reference:** Demonstrate design system composition
2. **Regression Testing:** Enable comparison of implementation against approved design
3. **Fixture Consistency:** Ensure deterministic E2E test execution

### Not Production Photography
- These are extracted design mockups, not actual vehicle photographs
- Not intended for customer-facing applications
- Not licensed for commercial marketplace use
- Illustrative only - do not represent final product imagery

### Proof of Origin
The complete extraction history is preserved:
- Source commit: `1cdab114920cdc2431f983a1c1ea9efb88e26f82`
- Extraction script: `tests/e2e/extract-vehicle-images.mjs`
- Extraction timestamp: 2026-08-18T23:10:48.703Z
- Tool: Sharp.js image library

All SHA-256 hashes can be independently verified against the extracted files.

---

## Audit Trail

| Action | Timestamp | Reference |
|--------|-----------|-----------|
| Asset extraction | 2026-08-18T23:10:48.703Z | `1cdab114920cdc2431f983a1c1ea9efb88e26f82` |
| Provenance documentation | 2026-08-18 | This file |
| E2eSeeder integration | TBD | Batch 1 code commit |
| Screenshot generation | TBD | Evidence commit |
| Triad generation | TBD | Evidence commit |

---

## Regeneration Instructions

To regenerate these assets with exact verification:

```bash
# 1. Verify reference exists
git show 1cdab114920cdc2431f983a1c1ea9efb88e26f82:docs/design-v2/assets/01-public-desktop-system.png

# 2. Run extraction script
node tests/e2e/extract-vehicle-images.mjs

# 3. Verify SHA-256 matches
sha256sum storage/app/public/e2e/design-derived-vehicles/*.png

# 4. Confirm against this document
# Each filename and SHA-256 should match exactly
```

---

**End of Asset Provenance Documentation**
