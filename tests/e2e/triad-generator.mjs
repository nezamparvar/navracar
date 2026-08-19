import { readFileSync, writeFileSync, mkdirSync, existsSync, rmSync } from 'fs';
import { join } from 'path';
import { createHash } from 'crypto';
import { execSync } from 'child_process';
import sharp from 'sharp';

/**
 * Smoke-test triad generator: Creates side-by-side comparison artifacts
 * for visual regression testing. Uses locked reference assets from the
 * approved design commit (1cdab114920cdc2431f983a1c1ea9efb88e26f82) and
 * compares them against current screenshots.
 *
 * Generates three artifacts per route:
 *   1. Reference crop (from locked commit)
 *   2. Current crop (from current implementation)
 *   3. Aligned overlay (semi-transparent reference over current, showing delta)
 *
 * Requires: PIL/Pillow (Python image library)
 */

const LOCKED_REFERENCE_COMMIT = '1cdab114920cdc2431f983a1c1ea9efb88e26f82';
const CURRENT_SCREENSHOTS_DIR = join(process.cwd(), 'docs/design-v2/implementation/screenshots/round6-visual-parity');
const TRIAD_OUTPUT_DIR = join(process.cwd(), 'docs/design-v2/implementation/triads');

const TRIAD_SPEC = [
  {
    name: 'vehicle-list-desktop',
    referenceAsset: 'docs/design-v2/assets/01-public-desktop-system.png',
    currentFile: 'vehicle-list-viewport-1440x900.png',
    referenceCrop: { x: 0, y: 120, w: 1440, h: 720 },
    currentCrop: { x: 0, y: 0, w: 1440, h: 900 },
    description: 'Vehicle listing grid - desktop view',
  },
  {
    name: 'admin-dashboard-desktop',
    referenceAsset: 'docs/design-v2/assets/02-admin-dashboard-calendar.png',
    currentFile: 'admin-dashboard-viewport-1440x900.png',
    referenceCrop: { x: 0, y: 60, w: 1440, h: 720 },
    currentCrop: { x: 0, y: 0, w: 1440, h: 900 },
    description: 'Admin dashboard with calendar - desktop view',
  },
  {
    name: 'sales-dashboard-desktop',
    referenceAsset: 'docs/design-v2/assets/03-sales-dashboard.png',
    currentFile: 'sales-dashboard-viewport-1440x900.png',
    referenceCrop: { x: 0, y: 60, w: 1440, h: 720 },
    currentCrop: { x: 0, y: 0, w: 1440, h: 900 },
    description: 'Sales dashboard with metrics - desktop view',
  },
  {
    name: 'content-dashboard-desktop',
    referenceAsset: 'docs/design-v2/assets/04-content-dashboard.png',
    currentFile: 'content-dashboard-viewport-1440x900.png',
    referenceCrop: { x: 0, y: 60, w: 1440, h: 720 },
    currentCrop: { x: 0, y: 0, w: 1440, h: 900 },
    description: 'Content dashboard with import queue - desktop view',
  },
];

async function generateTriads() {
  console.log('\n📸 Generating smoke-test triads from locked reference assets\n');

  // Ensure output directory exists
  mkdirSync(TRIAD_OUTPUT_DIR, { recursive: true });

  const triads = [];

  for (const spec of TRIAD_SPEC) {
    console.log(`📋 Generating triad: ${spec.name}`);

    // Fetch reference asset from locked commit
    const referenceData = await fetchAssetFromCommit(LOCKED_REFERENCE_COMMIT, spec.referenceAsset);
    if (!referenceData) {
      console.error(`✗ Failed to fetch reference asset from commit ${LOCKED_REFERENCE_COMMIT}: ${spec.referenceAsset}`);
      continue;
    }

    // Load current screenshot
    const currentPath = join(CURRENT_SCREENSHOTS_DIR, spec.currentFile);
    let currentData;
    try {
      currentData = readFileSync(currentPath);
    } catch (err) {
      console.error(`✗ Failed to read current screenshot: ${currentPath}`);
      continue;
    }

    try {
      // Generate triads using Sharp
      const refCrop = spec.referenceCrop;
      const currCrop = spec.currentCrop;

      // Crop reference image
      const refCroppedBuffer = await sharp(referenceData)
        .extract({
          left: refCrop.x,
          top: refCrop.y,
          width: refCrop.w,
          height: refCrop.h,
        })
        .png()
        .toBuffer();

      const refCropPath = join(TRIAD_OUTPUT_DIR, `${spec.name}-reference-crop.png`);
      writeFileSync(refCropPath, refCroppedBuffer);
      console.log(`  ✓ ${spec.name}-reference-crop.png`);
      triads.push(refCropPath);

      // Crop current image
      const currCroppedBuffer = await sharp(currentData)
        .extract({
          left: currCrop.x,
          top: currCrop.y,
          width: currCrop.w,
          height: currCrop.h,
        })
        .png()
        .toBuffer();

      const currCropPath = join(TRIAD_OUTPUT_DIR, `${spec.name}-current-crop.png`);
      writeFileSync(currCropPath, currCroppedBuffer);
      console.log(`  ✓ ${spec.name}-current-crop.png`);
      triads.push(currCropPath);

      // Create overlay: semi-transparent reference over current
      // First get dimensions of cropped images
      const refMetadata = await sharp(refCroppedBuffer).metadata();
      const currMetadata = await sharp(currCroppedBuffer).metadata();

      // Resize reference to match current dimensions if needed
      let refForOverlay = refCroppedBuffer;
      if (refMetadata.width !== currMetadata.width || refMetadata.height !== currMetadata.height) {
        refForOverlay = await sharp(refCroppedBuffer)
          .resize(currMetadata.width, currMetadata.height, { fit: 'cover' })
          .toBuffer();
      }

      // Create semi-transparent overlay
      const refAlpha = await sharp(refForOverlay)
        .ensureAlpha(0.5)  // 50% opacity
        .toBuffer();

      const overlayBuffer = await sharp(currCroppedBuffer)
        .composite([{ input: refAlpha, blend: 'over' }])
        .png()
        .toBuffer();

      const overlayPath = join(TRIAD_OUTPUT_DIR, `${spec.name}-overlay.png`);
      writeFileSync(overlayPath, overlayBuffer);
      console.log(`  ✓ ${spec.name}-overlay.png`);
      triads.push(overlayPath);
    } catch (err) {
      console.error(`✗ Failed to generate triads for ${spec.name}: ${err.message}`);
      continue;
    }
  }

  // Generate triad manifest
  if (triads.length > 0) {
    const manifest = {
      generated_at: new Date().toISOString(),
      reference_commit: LOCKED_REFERENCE_COMMIT,
      triads: triads.map(path => ({
        path: path.replace(process.cwd() + '/', ''),
        filename: path.split('/').pop(),
        sha256: getFileSHA256(readFileSync(path)),
      })),
    };

    const manifestPath = join(TRIAD_OUTPUT_DIR, 'triad-manifest.json');
    writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
    console.log(`\n✓ Triad manifest: ${manifestPath}`);
  }

  console.log(`\n✅ Generated ${triads.length} triad artifacts`);
  return triads;
}

async function fetchAssetFromCommit(commitSha, filePath) {
  try {
    const tempPath = `/tmp/triad-asset-${Date.now()}.png`;
    execSync(`git show ${commitSha}:${filePath} > ${tempPath}`, { stdio: 'pipe' });
    const data = readFileSync(tempPath);
    rmSync(tempPath, { force: true });
    return data;
  } catch (err) {
    return null;
  }
}

function getFileSHA256(data) {
  return createHash('sha256').update(data).digest('hex');
}

await generateTriads().catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});
