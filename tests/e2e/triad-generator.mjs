import { readFileSync, writeFileSync, mkdirSync, existsSync, rmSync } from 'fs';
import { join } from 'path';
import { createHash } from 'crypto';
import { execSync } from 'child_process';

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

  // Check if Python with PIL is available
  try {
    execSync('python3 -c "from PIL import Image"', { stdio: 'pipe' });
  } catch (err) {
    console.error('✗ Python PIL (Pillow) not available. Install with: pip install Pillow');
    process.exit(1);
  }

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

    // Write temporary files for Python image processing
    const tempDir = join(process.cwd(), `.triad-temp-${Date.now()}`);
    mkdirSync(tempDir, { recursive: true });

    const refTempPath = join(tempDir, 'reference.png');
    const currTempPath = join(tempDir, 'current.png');
    writeFileSync(refTempPath, referenceData);
    writeFileSync(currTempPath, currentData);

    // Generate triads using Python
    const pythonScript = `
import sys
from PIL import Image
import os

def generate_triad(ref_path, curr_path, out_dir, spec, name):
    """Generate reference crop, current crop, and overlay"""
    ref_img = Image.open(ref_path)
    curr_img = Image.open(curr_path)

    ref_crop = spec['referenceCrop']
    curr_crop = spec['currentCrop']

    # Crop reference image
    ref_cropped = ref_img.crop((
        ref_crop['x'],
        ref_crop['y'],
        ref_crop['x'] + ref_crop['w'],
        ref_crop['y'] + ref_crop['h']
    ))
    ref_crop_path = os.path.join(out_dir, f'{name}-reference-crop.png')
    ref_cropped.save(ref_crop_path)
    print(f'✓ {name}-reference-crop.png')

    # Crop current image
    curr_cropped = curr_img.crop((
        curr_crop['x'],
        curr_crop['y'],
        curr_crop['x'] + curr_crop['w'],
        curr_crop['y'] + curr_crop['h']
    ))
    curr_crop_path = os.path.join(out_dir, f'{name}-current-crop.png')
    curr_cropped.save(curr_crop_path)
    print(f'✓ {name}-current-crop.png')

    # Create overlay: semi-transparent reference over current
    # Resize reference to match current dimensions if needed
    if ref_cropped.size != curr_cropped.size:
        ref_cropped = ref_cropped.resize(curr_cropped.size, Image.Resampling.LANCZOS)

    overlay = curr_cropped.copy()
    ref_overlay = ref_cropped.convert('RGBA')
    ref_overlay.putalpha(128)  # 50% transparency
    overlay.paste(ref_overlay, (0, 0), ref_overlay)

    overlay_path = os.path.join(out_dir, f'{name}-overlay.png')
    overlay.save(overlay_path)
    print(f'✓ {name}-overlay.png')

    return [ref_crop_path, curr_crop_path, overlay_path]

spec = ${JSON.stringify(spec)}
paths = generate_triad('${refTempPath}', '${currTempPath}', '${TRIAD_OUTPUT_DIR}', spec, '${spec.name}')
for p in paths:
    print(f'artifact:{p}')
`;

    try {
      const output = execSync(`python3 -c "${pythonScript.replace(/"/g, '\\"')}"`, { encoding: 'utf-8' });
      // Extract artifact paths from output
      for (const line of output.split('\n')) {
        if (line.startsWith('artifact:')) {
          triads.push(line.substring(9).trim());
        }
      }
    } catch (err) {
      console.error(`✗ Failed to generate triads for ${spec.name}: ${err.message}`);
      rmSync(tempDir, { recursive: true, force: true });
      continue;
    }

    rmSync(tempDir, { recursive: true, force: true });
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
    const data = execSync(`git show ${commitSha}:${filePath}`, { encoding: 'binary' });
    return Buffer.from(data, 'binary');
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
