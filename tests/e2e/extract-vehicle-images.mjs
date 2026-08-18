import sharp from 'sharp';
import { writeFileSync, mkdirSync, existsSync, readFileSync } from 'fs';
import { join } from 'path';
import { createHash } from 'crypto';
import { execSync } from 'child_process';

const LOCKED_COMMIT = '1cdab114920cdc2431f983a1c1ea9efb88e26f82';
const REFERENCE_ASSET = 'docs/design-v2/assets/01-public-desktop-system.png';
const OUTPUT_DIR = join(process.cwd(), 'storage/app/public/e2e/design-derived-vehicles');

// Vehicle card and image crop coordinates extracted from the design asset
// Format: { name, description, crop: { left, top, width, height } }
const VEHICLE_CROPS = [
  {
    name: 'vehicle-1-card-listing',
    description: 'First vehicle listing card - BMW X4',
    crop: { left: 20, top: 140, width: 320, height: 280 }
  },
  {
    name: 'vehicle-2-card-listing',
    description: 'Second vehicle listing card',
    crop: { left: 360, top: 140, width: 320, height: 280 }
  },
  {
    name: 'vehicle-3-card-listing',
    description: 'Third vehicle listing card',
    crop: { left: 700, top: 140, width: 320, height: 280 }
  },
  {
    name: 'vehicle-4-card-listing',
    description: 'Fourth vehicle listing card',
    crop: { left: 1040, top: 140, width: 320, height: 280 }
  },
  {
    name: 'vehicle-5-card-listing',
    description: 'Fifth vehicle listing card',
    crop: { left: 20, top: 440, width: 320, height: 280 }
  },
  {
    name: 'vehicle-6-card-listing',
    description: 'Sixth vehicle listing card',
    crop: { left: 360, top: 440, width: 320, height: 280 }
  },
  {
    name: 'vehicle-7-card-listing',
    description: 'Seventh vehicle listing card',
    crop: { left: 700, top: 440, width: 320, height: 280 }
  },
  {
    name: 'vehicle-8-card-listing',
    description: 'Eighth vehicle listing card',
    crop: { left: 1040, top: 440, width: 320, height: 280 }
  },
];

async function extractVehicleImages() {
  console.log('\n🚗 Extracting vehicle images from locked design reference\n');
  console.log(`Reference: ${LOCKED_COMMIT}:${REFERENCE_ASSET}`);
  console.log(`Output: ${OUTPUT_DIR}\n`);

  // Create output directory
  mkdirSync(OUTPUT_DIR, { recursive: true });

  // Fetch reference image from git
  let referenceBuffer;
  try {
    const tempPath = '/tmp/ref-design.png';
    execSync(`git show ${LOCKED_COMMIT}:${REFERENCE_ASSET} > ${tempPath}`, { stdio: 'pipe' });
    referenceBuffer = readFileSync(tempPath);
    console.log(`✓ Fetched reference image (${referenceBuffer.length} bytes)`);
  } catch (err) {
    console.error(`✗ Failed to fetch reference image: ${err.message}`);
    process.exit(1);
  }

  const extractedImages = [];

  for (const spec of VEHICLE_CROPS) {
    try {
      const outputPath = join(OUTPUT_DIR, `${spec.name}.png`);

      // Extract crop from reference image
      const croppedBuffer = await sharp(referenceBuffer)
        .extract(spec.crop)
        .png()
        .toBuffer();

      // Write to file
      writeFileSync(outputPath, croppedBuffer);

      // Calculate SHA-256
      const sha256 = createHash('sha256').update(croppedBuffer).digest('hex');

      console.log(`✓ ${spec.name}`);
      console.log(`  Crop: [${spec.crop.left}, ${spec.crop.top}, ${spec.crop.width}×${spec.crop.height}]`);
      console.log(`  SHA-256: ${sha256}`);

      extractedImages.push({
        filename: `${spec.name}.png`,
        name: spec.name,
        description: spec.description,
        crop: spec.crop,
        sha256: sha256,
        bytes: croppedBuffer.length,
        source_commit: LOCKED_COMMIT,
        source_asset: REFERENCE_ASSET,
      });
    } catch (err) {
      console.error(`✗ Failed to extract ${spec.name}: ${err.message}`);
    }
  }

  if (extractedImages.length === 0) {
    console.error('✗ No vehicle images extracted');
    process.exit(1);
  }

  // Generate provenance record
  const provenance = {
    extracted_at: new Date().toISOString(),
    source_commit: LOCKED_COMMIT,
    source_asset: REFERENCE_ASSET,
    reference_image_size: { width: 1672, height: 941 },
    output_directory: OUTPUT_DIR.replace(process.cwd() + '/', ''),
    classification: 'design-derived E2E fixture - illustrative visual reference, not production photography',
    purpose: 'E2E test fixtures for visual regression testing against approved design reference',
    images: extractedImages,
  };

  return provenance;
}

async function main() {
  try {
    const provenance = await extractVehicleImages();

    console.log(`\n✅ Successfully extracted ${provenance.images.length} vehicle images\n`);
    console.log('Provenance data:');
    console.log(JSON.stringify(provenance, null, 2));

    // Output for consumption by subsequent scripts
    console.log('\n📝 Ready for ASSET_PROVENANCE.md generation');
    process.exit(0);
  } catch (err) {
    console.error('Fatal error:', err);
    process.exit(1);
  }
}

await main();
