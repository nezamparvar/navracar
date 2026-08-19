import { readFileSync } from 'fs';
import { join } from 'path';

const manifestPath = join(process.cwd(), 'docs/design-v2/implementation/screenshots/round6-visual-parity/screenshot-manifest.json');

console.log('\n📋 Validating Screenshot Manifest\n');
console.log(`Manifest: ${manifestPath}\n`);

let manifest;
try {
  manifest = JSON.parse(readFileSync(manifestPath, 'utf-8'));
} catch (err) {
  console.error(`✗ Failed to read manifest: ${err.message}`);
  process.exit(1);
}

const EXPECTED_ROUTES = ['vehicle-list', 'vehicle-detail', 'admin-dashboard', 'sales-dashboard', 'content-dashboard', 'calendar-day', 'calendar-week', 'calendar-list'];
const EXPECTED_VIEWPORTS = ['390x844', '1440x900'];
const EXPECTED_TYPES = ['viewport', 'full-page'];
const REQUIRED_FIELDS = ['filename', 'sha256', 'route', 'final_url', 'capture_type', 'viewport_dimensions', 'actual_dimensions', 'http_status', 'authentication_state', 'source_commit', 'timestamp'];

let errors = 0;
let warnings = 0;

console.log('Validating manifest structure...');

// Check top-level fields
if (!manifest.screenshots || !Array.isArray(manifest.screenshots)) {
  console.error('✗ Missing "screenshots" array');
  errors++;
} else {
  console.log(`✓ Manifest contains ${manifest.screenshots.length} screenshots`);

  if (manifest.screenshots.length !== 32) {
    console.error(`✗ Expected 32 screenshots, got ${manifest.screenshots.length}`);
    errors++;
  } else {
    console.log('✓ Correct number of screenshots (32)');
  }
}

if (!manifest.batch) {
  console.warn('⚠ Missing batch field');
  warnings++;
} else {
  console.log(`✓ Batch: ${manifest.batch}`);
}

if (!manifest.generated_at) {
  console.warn('⚠ Missing generated_at timestamp');
  warnings++;
} else {
  console.log(`✓ Generated at: ${manifest.generated_at}`);
}

if (!manifest.status) {
  console.warn('⚠ Missing status field');
  warnings++;
} else {
  console.log(`✓ Status: ${manifest.status}`);
}

console.log('\nValidating per-screenshot metadata...\n');

const seenCombinations = new Set();
const seenFilenames = new Set();
let validScreenshots = 0;

for (const screenshot of manifest.screenshots || []) {
  let valid = true;

  // Check all required fields
  for (const field of REQUIRED_FIELDS) {
    if (screenshot[field] === undefined || screenshot[field] === null || screenshot[field] === '') {
      console.error(`✗ ${screenshot.filename || 'unknown'}: missing field "${field}"`);
      errors++;
      valid = false;
    }
  }

  if (!valid) continue;

  // Validate route
  if (!EXPECTED_ROUTES.includes(screenshot.route)) {
    console.error(`✗ ${screenshot.filename}: invalid route "${screenshot.route}"`);
    errors++;
    valid = false;
  }

  // Validate capture type
  if (!EXPECTED_TYPES.includes(screenshot.capture_type)) {
    console.error(`✗ ${screenshot.filename}: invalid capture_type "${screenshot.capture_type}"`);
    errors++;
    valid = false;
  }

  // Validate viewport dimensions format
  if (!/^\d+x\d+$/.test(screenshot.viewport_dimensions)) {
    console.error(`✗ ${screenshot.filename}: invalid viewport_dimensions "${screenshot.viewport_dimensions}"`);
    errors++;
    valid = false;
  }

  // Validate HTTP status
  if (screenshot.http_status !== 200) {
    console.error(`✗ ${screenshot.filename}: HTTP ${screenshot.http_status} (expected 200)`);
    errors++;
    valid = false;
  }

  // Validate SHA-256 format (64 hex chars)
  if (!/^[a-f0-9]{64}$/i.test(screenshot.sha256)) {
    console.error(`✗ ${screenshot.filename}: invalid SHA-256 hash`);
    errors++;
    valid = false;
  }

  // Validate no "unknown" commit SHA
  if (screenshot.source_commit === 'unknown' || !screenshot.source_commit) {
    console.error(`✗ ${screenshot.filename}: invalid source_commit "${screenshot.source_commit}"`);
    errors++;
    valid = false;
  }

  // Check for duplicate filenames
  if (seenFilenames.has(screenshot.filename)) {
    console.error(`✗ Duplicate filename: "${screenshot.filename}"`);
    errors++;
    valid = false;
  }
  seenFilenames.add(screenshot.filename);

  // Check for duplicate route/viewport/type combinations
  const combination = `${screenshot.route}:${screenshot.viewport_dimensions}:${screenshot.capture_type}`;
  if (seenCombinations.has(combination)) {
    console.error(`✗ Duplicate combination: ${combination}`);
    errors++;
    valid = false;
  }
  seenCombinations.add(combination);

  if (valid) {
    validScreenshots++;
  }
}

console.log(`✓ ${validScreenshots} valid screenshots out of ${manifest.screenshots?.length || 0}`);

// Validate cardinality
console.log('\nValidating cardinality (8 routes × 2 viewports × 2 types = 32)...\n');

const routeCounts = {};
const viewportCounts = {};
const typeCounts = {};

for (const screenshot of manifest.screenshots || []) {
  routeCounts[screenshot.route] = (routeCounts[screenshot.route] || 0) + 1;
  viewportCounts[screenshot.viewport_dimensions] = (viewportCounts[screenshot.viewport_dimensions] || 0) + 1;
  typeCounts[screenshot.capture_type] = (typeCounts[screenshot.capture_type] || 0) + 1;
}

// Check routes
const routeOk = EXPECTED_ROUTES.every(route => routeCounts[route] === 4); // 4 per route (2 viewports × 2 types)
if (routeOk) {
  console.log('✓ All 8 routes present with correct counts (4 each)');
} else {
  console.error('✗ Route cardinality mismatch:');
  for (const route of EXPECTED_ROUTES) {
    const count = routeCounts[route] || 0;
    if (count !== 4) {
      console.error(`  - ${route}: ${count} (expected 4)`);
      errors++;
    }
  }
}

// Check viewports
const viewportOk = EXPECTED_VIEWPORTS.every(vp => viewportCounts[vp] === 16); // 16 per viewport (8 routes × 2 types)
if (viewportOk) {
  console.log('✓ Both viewports present with correct counts (16 each)');
} else {
  console.error('✗ Viewport cardinality mismatch:');
  for (const vp of EXPECTED_VIEWPORTS) {
    const count = viewportCounts[vp] || 0;
    if (count !== 16) {
      console.error(`  - ${vp}: ${count} (expected 16)`);
      errors++;
    }
  }
}

// Check types
const typeOk = EXPECTED_TYPES.every(type => typeCounts[type] === 16); // 16 per type (8 routes × 2 viewports)
if (typeOk) {
  console.log('✓ Both capture types present with correct counts (16 each)');
} else {
  console.error('✗ Capture type cardinality mismatch:');
  for (const type of EXPECTED_TYPES) {
    const count = typeCounts[type] || 0;
    if (count !== 16) {
      console.error(`  - ${type}: ${count} (expected 16)`);
      errors++;
    }
  }
}

// Check authenticated routes
console.log('\nValidating authenticated route requirements...\n');

const adminRoutes = ['admin-dashboard', 'sales-dashboard', 'content-dashboard', 'calendar-day', 'calendar-week', 'calendar-list'];
let adminUrlsOk = true;

for (const screenshot of manifest.screenshots || []) {
  if (adminRoutes.includes(screenshot.route)) {
    if (!screenshot.final_url.includes('/admin')) {
      console.error(`✗ ${screenshot.filename}: admin route missing "/admin" in URL`);
      errors++;
      adminUrlsOk = false;
    }
  }
}

if (adminUrlsOk) {
  console.log('✓ All admin routes have /admin in final_url');
}

console.log('\n' + '='.repeat(60) + '\n');

if (errors === 0) {
  console.log('✅ Manifest validation passed\n');
  console.log('Summary:');
  console.log(`  ✓ 32 unique PNG filenames`);
  console.log(`  ✓ All 11 required fields present`);
  console.log(`  ✓ All screenshots HTTP 200`);
  console.log(`  ✓ All SHA-256 hashes valid`);
  console.log(`  ✓ Cardinality: 8 routes × 2 viewports × 2 types`);
  console.log(`  ✓ Admin routes contain /admin`);
  console.log(`  ✓ No "unknown" commit SHAs\n`);
  process.exit(0);
} else {
  console.error(`❌ Manifest validation failed with ${errors} error(s)` + (warnings > 0 ? ` and ${warnings} warning(s)` : '') + '\n');
  process.exit(1);
}
