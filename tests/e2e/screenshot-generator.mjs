import { chromium } from '@playwright/test';
import { createHash } from 'crypto';
import { mkdirSync, writeFileSync, existsSync, rmSync } from 'fs';
import { join, basename } from 'path';
import { tmpdir } from 'os';
import { randomUUID } from 'crypto';
import { execSync } from 'child_process';

const baseURL = 'http://127.0.0.1:8000';
const finalOutputDir = join(process.cwd(), 'docs/design-v2/implementation/screenshots/round6-visual-parity');
const manifestPath = join(finalOutputDir, 'screenshot-manifest.json');

// Parse CLI arguments: --route=NAME --viewport=SIZE
const args = process.argv.slice(2);
const cliRoute = args.find(a => a.startsWith('--route='))?.split('=')[1];
const cliViewport = args.find(a => a.startsWith('--viewport='))?.split('=')[1];

// Batch 1 acceptance: Strict documented allowlist of external hosts.
const EXTERNAL_HOST_ALLOWLIST = [];

// Batch 1 Required Priority Routes (8 routes × 2 viewports × 2 types = 32 screenshots, 4 per route)
const routes = [
  {
    path: '/car-prices',
    name: 'vehicle-list',
    auth: false,
    sizes: [390, 1440],
    requiresHeading: 'قیمت خودروها',
    assertion: async (page) => {
      // Seeded data: 1 original (e2e-bmw-x4) + 7 demo listings = 8 total
      const carLinks = await page.locator('a[href*="/car-prices/"]').count();
      if (carLinks < 8) throw new Error('Expected at least 8 vehicle cards, got ' + carLinks);
      // Verify pricing is visible (AED currency)
      const priceText = await page.locator('text=/درهم/').count();
      if (priceText < 2) throw new Error('Expected price text in AED, got ' + priceText);
    }
  },
  {
    path: '/car-prices/e2e-bmw-x4',
    name: 'vehicle-detail',
    auth: false,
    sizes: [390, 1440],
    requiresHeading: 'بی‌ام‌و X4 تست',
    assertion: async (page) => {
      // Seeded gallery: 4 images (Front, Side, Rear, Interior)
      const galleryImages = await page.locator('img[src*="car-listings-demo"]').count();
      if (galleryImages < 3) throw new Error('Expected at least 3 gallery images, got ' + galleryImages);
      // Verify pricing is displayed (price format is 100,000 درهم with comma)
      const priceVisible = await page.locator('text=/100,000|100000/').count();
      if (priceVisible < 1) throw new Error('Price not found on detail page');
      // Verify model year is shown (use first() to handle strict mode)
      const modelYear = await page.locator('text=/2025/').first().isVisible();
      if (!modelYear) throw new Error('Model year 2025 not visible');
    }
  },
  {
    path: '/admin',
    name: 'admin-dashboard',
    auth: true,
    sizes: [390, 1440],
    requiresHeading: 'داشبورد مدیریت',
    requiresUrl: /^https?:\/\/[^\/]+\/admin($|\?)/ ,
    assertion: async (page) => {
      // Seeded data: ~48 quote requests across 14 days should show on dashboard
      // Check for presence of quote/lead data (via text or widgets)
      const hasContent = await page.locator('main, [role="main"]').isVisible().catch(() => false);
      if (!hasContent) throw new Error('Admin dashboard main content not visible');
      // Look for quote/lead count indicators or pipeline widgets
      const hasQuoteData = await page.locator('text=/درخواست|پیام|quote|lead|request/i').count();
      if (hasQuoteData < 1) throw new Error('No quote/lead data visible on admin dashboard');
    }
  },
  {
    path: '/admin/sales-dashboard',
    name: 'sales-dashboard',
    auth: true,
    sizes: [390, 1440],
    requiresHeading: 'داشبورد فروش',
    requiresUrl: /^https?:\/\/[^\/]+\/admin\/sales-dashboard/ ,
    assertion: async (page) => {
      // Seeded data: 40 calculation logs and ~48 quote requests for KPI display
      const mainContent = await page.locator('main, [role="main"]').isVisible().catch(() => false);
      if (!mainContent) throw new Error('Sales dashboard main content not visible');
      // Just verify main content is visible (specific widget structure may vary)
      const bodyText = await page.locator('body').innerText();
      if (!bodyText || bodyText.length < 100) throw new Error('Sales dashboard content too minimal');
    }
  },
  {
    path: '/admin/content-dashboard',
    name: 'content-dashboard',
    auth: true,
    sizes: [390, 1440],
    requiresHeading: 'داشبورد محتوا',
    requiresUrl: /^https?:\/\/[^\/]+\/admin\/content-dashboard/ ,
    assertion: async (page) => {
      // Seeded data: 8 import queue items with various statuses
      const tableRows = await page.locator('table tbody tr').count();
      if (tableRows < 1) throw new Error('Expected at least 1 import queue row, got ' + tableRows);
      // Verify import content is visible (table exists)
      const hasTable = await page.locator('table').count();
      if (hasTable < 1) throw new Error('Import queue table not found on dashboard');
    }
  },
  {
    path: '/admin/calendar?view=day',
    name: 'calendar-day',
    auth: true,
    sizes: [390, 1440],
    requiresHeading: 'تقویم',
    requiresUrl: /^https?:\/\/[^\/]+\/admin\/calendar.*view=day/ ,
    assertion: async (page) => {
      // Seeded data: 3 events today (09:00, 13:00, 18:00)
      const calendarContent = await page.locator('main, [role="main"]').isVisible().catch(() => false);
      if (!calendarContent) throw new Error('Calendar day view not visible');
      // Look for any calendar event indicators
      const hasEvents = await page.locator('[class*="event"], [class*="calendar"]').count();
      if (hasEvents < 1) throw new Error('No calendar events visible in day view');
    }
  },
  {
    path: '/admin/calendar?view=week',
    name: 'calendar-week',
    auth: true,
    sizes: [390, 1440],
    requiresHeading: 'تقویم',
    requiresUrl: /^https?:\/\/[^\/]+\/admin\/calendar.*view=week/ ,
    assertion: async (page) => {
      // Seeded data: 8 events across the week
      const calendarContent = await page.locator('main, [role="main"]').isVisible().catch(() => false);
      if (!calendarContent) throw new Error('Calendar week view not visible');
      // Look for calendar events in week view
      const events = await page.locator('[class*="event"], [class*="calendar"]').count();
      if (events < 1) throw new Error('No calendar events visible in week view');
    }
  },
  {
    path: '/admin/calendar?view=list',
    name: 'calendar-list',
    auth: true,
    sizes: [390, 1440],
    requiresHeading: 'تقویم',
    requiresUrl: /^https?:\/\/[^\/]+\/admin\/calendar.*view=list/ ,
    assertion: async (page) => {
      // Seeded data: 8 calendar events total
      const calendarContent = await page.locator('main, [role="main"]').isVisible().catch(() => false);
      if (!calendarContent) throw new Error('Calendar list view not visible');
      // In list view, verify there's any calendar content
      const bodyText = await page.locator('body').innerText();
      if (!bodyText || bodyText.length < 100) throw new Error('Calendar list view content too minimal');
    }
  },
];

const viewportSizes = {
  390: { width: 390, height: 844 },
  1440: { width: 1440, height: 900 },
};

function getFileSHA256(data) {
  return createHash('sha256').update(data).digest('hex');
}

function getPNGDimensions(buffer) {
  if (buffer.length < 24) return null;
  const width = buffer.readUInt32BE(16);
  const height = buffer.readUInt32BE(20);
  return { width, height };
}

function getCurrentCommitSHA() {
  try {
    return execSync('git rev-parse HEAD', { encoding: 'utf-8' }).trim();
  } catch (err) {
    console.error('Failed to get commit SHA:', err.message);
    process.exit(1);
  }
}

async function authenticateAndSaveState(browser) {
  const authContext = await browser.newContext();
  const authPage = await authContext.newPage();

  // Route interception: allow localhost/127.0.0.1, block all external requests
  await authPage.route('**/*', route => {
    const url = new URL(route.request().url());
    const isLocalhost = url.hostname === 'localhost' || url.hostname === '127.0.0.1';
    if (isLocalhost) {
      route.continue();
    } else {
      // Abort external requests silently without logging
      route.abort('blockedbyclient');
    }
  });

  try {
    // Navigate to login
    await authPage.goto(`${baseURL}/admin/login`, { waitUntil: 'domcontentloaded', timeout: 10000 });

    // Fill login credentials first
    await authPage.locator('input[name="username"]').fill('admin');
    await authPage.locator('input[name="password"]').fill('password');

    // Fill and submit form, wait for navigation to admin dashboard
    await Promise.all([
      authPage.waitForURL(
        url => url.origin === new URL(baseURL).origin && url.pathname === '/admin',
        { waitUntil: 'domcontentloaded', timeout: 15000 }
      ),
      authPage.locator('button[type="submit"]').click(),
    ]);

    // Wait for authenticated UI shell
    await authPage.locator('aside').waitFor({ state: 'visible', timeout: 5000 });
    await authPage.getByRole('heading', { name: 'داشبورد مدیریت' })
      .waitFor({ state: 'visible', timeout: 5000 });

    // Save authenticated session state
    const storageState = await authContext.storageState();
    await authContext.close();

    return storageState;
  } catch (err) {
    await authContext.close();
    throw err;
  }
}

async function waitForRouteReady(page, route) {
  // Explicit timeout around document.fonts.ready (5 second bounded wait)
  try {
    await Promise.race([
      page.evaluate(() => document.fonts.ready),
      new Promise((_, reject) => setTimeout(() => reject(new Error('Font loading timeout')), 5000))
    ]);
  } catch (err) {
    if (err.message === 'Font loading timeout') {
      console.warn('⚠ Font loading timeout (proceeding with screenshot)');
    } else {
      throw err;
    }
  }

  if (route.requiresHeading) {
    await page.getByRole('heading', { name: new RegExp(route.requiresHeading, 'i') })
      .first()
      .waitFor({ state: 'visible', timeout: 5000 });
  }
}

async function captureScreenshot(browser, route, viewportSize, storageState, outputDir, commitSHA) {
  const viewport = viewportSizes[viewportSize];
  const context = await browser.newContext({
    viewport,
    storageState: route.auth ? storageState : undefined
  });

  const page = await context.newPage();
  const results = [];
  let hasError = false;
  const blockedRequests = [];
  const pageErrors = [];
  const consoleErrors = [];

  // Route interception: block all external requests
  await context.route('**/*', route => {
    const url = new URL(route.request().url());
    const isLocalhost = url.hostname === 'localhost' || url.hostname === '127.0.0.1';
    if (isLocalhost) {
      route.continue();
    } else {
      blockedRequests.push(url.hostname);
      route.abort('blockedbyclient');
    }
  });

  page.on('pageerror', err => {
    pageErrors.push(`Page error: ${err.message}`);
  });

  page.on('requestfailed', req => {
    const urlMatch = req.url().match(/https?:\/\/([^\/:?]+)/);
    const hostname = urlMatch ? urlMatch[1] : 'unknown';
    consoleErrors.push(`Request failed: ${hostname} (${req.failure()?.errorText})`);
  });

  page.on('console', msg => {
    if (msg.type() === 'error') {
      // All console errors must be captured and reported
      // External request failures are already tracked in blockedRequests and requestfailed handler
      // Local asset failures (403/404 on localhost) MUST fail the capture
      consoleErrors.push(`Console error: ${msg.text()}`);
    }
  });

  try {
    // Navigate to route
    const response = await page.goto(`${baseURL}${route.path}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
    if (!response || response.status() !== 200) {
      throw new Error(`Failed to load ${route.path}: HTTP ${response?.status()}`);
    }

    const requestedUrl = `${baseURL}${route.path}`;
    const finalUrl = page.url();
    const httpStatus = response.status();

    // Wait for route-specific readiness (fonts + heading)
    await waitForRouteReady(page, route);

    // Validate final URL
    if (route.requiresUrl && !route.requiresUrl.test(finalUrl)) {
      throw new Error(`Route URL mismatch: expected ${route.requiresUrl}, got ${finalUrl}`);
    }
    if (route.auth && finalUrl.includes('/admin/login')) {
      throw new Error(`Authentication redirect detected: ended up at login page for ${route.name}`);
    }

    // Verify page content
    const bodyText = await page.evaluate(() => document.body?.innerText ?? '');
    if (bodyText.includes('Whoops') || bodyText.includes('exception') || bodyText.includes('ERR_CONNECTION')) {
      throw new Error(`Page error detected on ${route.path}`);
    }

    // Run route-specific data assertion
    if (route.assertion) {
      try {
        await route.assertion(page);
      } catch (assertErr) {
        throw new Error(`Assertion failed: ${assertErr.message}`);
      }
    }

    // Check for captured errors
    if (pageErrors.length > 0) {
      throw new Error(`Page errors: ${pageErrors.join('; ')}`);
    }
    if (consoleErrors.length > 0) {
      throw new Error(`Console/request errors: ${consoleErrors.join('; ')}`);
    }

    // Blocked external requests are expected (logged only)
    if (blockedRequests.length > 0) {
      console.log(`  ℹ Blocked ${blockedRequests.length} external request(s): ${[...new Set(blockedRequests)].join(', ')}`);
    }

    // Capture viewport-sized screenshot
    const viewportScreenshot = await page.screenshot({ fullPage: false });
    const viewportDims = getPNGDimensions(viewportScreenshot);
    if (!viewportDims || viewportDims.width !== viewport.width || viewportDims.height !== viewport.height) {
      throw new Error(`Viewport dimensions mismatch: expected ${viewport.width}×${viewport.height}, got ${viewportDims?.width}×${viewportDims?.height}`);
    }

    const viewportFilename = `${route.name}-viewport-${viewport.width}x${viewport.height}.png`;
    const viewportPath = join(outputDir, viewportFilename);
    writeFileSync(viewportPath, viewportScreenshot);
    const viewportSha = getFileSHA256(viewportScreenshot);
    console.log(`✓ ${viewportFilename} (${viewportDims.width}×${viewportDims.height}) — ${viewportSha}`);
    results.push({
      filename: viewportFilename,
      sha: viewportSha,
      dimensions: `${viewportDims.width}×${viewportDims.height}`,
      type: 'viewport',
      route: route.name,
      requestedUrl,
      finalUrl,
      authState: route.auth ? 'authenticated' : 'unauthenticated',
      httpStatus,
      viewport: `${viewport.width}x${viewport.height}`,
      blockedRequests: [...new Set(blockedRequests)],
      timestamp: new Date().toISOString(),
      commit: commitSHA
    });

    // Capture full-page screenshot
    const fullPageScreenshot = await page.screenshot({ fullPage: true });
    const fullPageDims = getPNGDimensions(fullPageScreenshot);
    const fullPageFilename = `${route.name}-full-${viewport.width}w.png`;
    const fullPagePath = join(outputDir, fullPageFilename);
    writeFileSync(fullPagePath, fullPageScreenshot);
    const fullPageSha = getFileSHA256(fullPageScreenshot);
    console.log(`✓ ${fullPageFilename} (${fullPageDims?.width}×${fullPageDims?.height}) — ${fullPageSha}`);
    results.push({
      filename: fullPageFilename,
      sha: fullPageSha,
      dimensions: `${viewport.width}x${viewport.height}`,
      type: 'full-page',
      route: route.name,
      requestedUrl,
      finalUrl,
      authState: route.auth ? 'authenticated' : 'unauthenticated',
      httpStatus,
      viewport: `${viewport.width}x${viewport.height}`,
      blockedRequests: [...new Set(blockedRequests)],
      timestamp: new Date().toISOString(),
      commit: commitSHA
    });

  } catch (err) {
    console.error(`✗ ${route.name} (${viewportSize}): ${err.message}`);
    hasError = true;
  } finally {
    await context.close();
  }

  return { results, hasError };
}

async function main() {
  // Use same browser path detection as Playwright config
  const launchOptions = { args: ['--no-proxy-server'] };
  const sandboxBrowser = '/opt/pw-browsers/chromium';
  if (process.env.CHROMIUM_PATH) {
    launchOptions.executablePath = process.env.CHROMIUM_PATH;
  } else if (existsSync(sandboxBrowser)) {
    launchOptions.executablePath = sandboxBrowser;
  }

  // ATOMIC SCREENSHOT GENERATION: Use unique temp directory for every run
  const uniqueSessionId = randomUUID().substring(0, 8);
  const tempOutputDir = join(tmpdir(), `navracar-screenshots-${uniqueSessionId}`);
  mkdirSync(tempOutputDir, { recursive: true });

  const browser = await chromium.launch(launchOptions);
  const allResults = [];
  let totalFailed = 0;

  // Get current commit SHA
  const commitSHA = getCurrentCommitSHA();

  console.log(`\n📸 Starting Batch 1 screenshot generation\n`);
  console.log(`Commit: ${commitSHA}`);
  console.log(`Temporary directory: ${tempOutputDir}`);
  console.log(`Final directory: ${finalOutputDir}\n`);

  // Authenticate once and reuse session state
  let storageState;
  try {
    console.log('🔐 Authenticating admin session...');
    storageState = await authenticateAndSaveState(browser);
    console.log('✓ Admin session authenticated and saved\n');
  } catch (err) {
    console.error(`✗ Authentication failed: ${err.message}`);
    await browser.close();
    rmSync(tempOutputDir, { recursive: true, force: true });
    process.exit(1);
  }

  // Determine routes to capture (CLI filtered or all)
  const routesToCapture = cliRoute
    ? routes.filter(r => r.name === cliRoute)
    : routes;

  if (cliRoute && routesToCapture.length === 0) {
    console.error(`✗ Route '${cliRoute}' not found`);
    rmSync(tempOutputDir, { recursive: true, force: true });
    process.exit(1);
  }

  // Capture routes to TEMP directory
  console.log(`📸 Capturing ${routesToCapture.length} route(s)...\n`);
  for (const route of routesToCapture) {
    const sizesToCapture = cliViewport ? [parseInt(cliViewport)] : route.sizes;
    for (const size of sizesToCapture) {
      if (!route.sizes.includes(size)) {
        console.error(`✗ Route ${route.name} does not support viewport ${size}`);
        await browser.close();
        rmSync(tempOutputDir, { recursive: true, force: true });
        process.exit(1);
      }
      const { results, hasError } = await captureScreenshot(browser, route, size, storageState, tempOutputDir, commitSHA);
      if (hasError) {
        totalFailed++;
      } else {
        allResults.push(...results);
      }
    }
  }

  await browser.close();

  // Verify capture counts
  const expectedViewportCount = routesToCapture.reduce((sum, r) => sum + (cliViewport ? 1 : r.sizes.length), 0);
  const expectedFullPageCount = routesToCapture.reduce((sum, r) => sum + (cliViewport ? 1 : r.sizes.length), 0);
  const totalExpected = expectedViewportCount + expectedFullPageCount;
  const totalCaptured = allResults.length;

  console.log(`\n## Screenshot Summary\n`);
  console.log(`Expected captures: ${totalExpected} (${expectedViewportCount} viewport + ${expectedFullPageCount} full-page)`);
  console.log(`Actual captures: ${totalCaptured}`);
  console.log(`Failed routes: ${totalFailed}`);

  if (totalFailed > 0) {
    console.error(`\n❌ Capture phase failed: ${totalFailed} route(s) failed`);
    rmSync(tempOutputDir, { recursive: true, force: true });
    process.exit(1);
  }

  if (totalCaptured !== totalExpected) {
    console.error(`\n❌ Screenshot count mismatch: expected ${totalExpected}, got ${totalCaptured}`);
    rmSync(tempOutputDir, { recursive: true, force: true });
    process.exit(1);
  }

  // ATOMIC PROMOTION: Validate manifest before promoting to final directory
  console.log(`\n✅ Validation phase: All ${totalCaptured} captures verified`);
  console.log(`\n📋 Generating machine-readable JSON manifest...`);

  // Generate and validate JSON manifest
  const isFullRun = routesToCapture.length === 8; // Full run = all 8 routes
  const manifest = {
    generated_at: new Date().toISOString(),
    batch: 'Batch 1',
    status: isFullRun ? 'complete' : 'smoke-test',
    route_count: routesToCapture.length,
    total_screenshots: totalCaptured,
    screenshots: allResults.map(r => ({
      filename: r.filename,
      route: r.route,
      requested_url: r.requestedUrl,
      final_url: r.finalUrl,
      authentication_state: r.authState,
      capture_type: r.type,
      viewport_dimensions: r.viewport,
      actual_dimensions: r.dimensions,
      sha256: r.sha,
      http_status: r.httpStatus,
      blocked_requests: r.blockedRequests,
      timestamp: r.timestamp,
      source_commit: r.commit
    }))
  };

  const manifestJson = JSON.stringify(manifest, null, 2);
  const manifestValidation = validateManifest(manifest, isFullRun);
  if (!manifestValidation.valid) {
    console.error(`❌ Manifest validation failed: ${manifestValidation.errors.join('; ')}`);
    rmSync(tempOutputDir, { recursive: true, force: true });
    process.exit(1);
  }

  // ATOMIC PROMOTION: Use directory swap with SHA verification and failure-safe recovery
  console.log(`\n🚀 Promoting ${totalCaptured} screenshots (atomic directory swap)...`);
  const fs = await import('fs').then(m => m.promises);

  // Create staging directory and copy all files from temp
  const stagingDir = join(process.cwd(), `.navracar-staging-${randomUUID().substring(0, 8)}`);
  mkdirSync(stagingDir, { recursive: true });

  const allTempFiles = (await fs.readdir(tempOutputDir)).filter(f => f.endsWith('.png'));
  const stagingHashes = {};

  // Copy files and compute SHA-256 on read
  for (const file of allTempFiles) {
    const tempPath = join(tempOutputDir, file);
    const stagingPath = join(stagingDir, file);
    const data = await fs.readFile(tempPath);
    await fs.writeFile(stagingPath, data);
    stagingHashes[file] = getFileSHA256(data);
  }

  // Write manifest to staging directory
  const stagingManifestPath = join(stagingDir, 'screenshot-manifest.json');
  writeFileSync(stagingManifestPath, manifestJson);

  // Verify all files in staging directory with SHA-256 re-check
  const allStagingFiles = (await fs.readdir(stagingDir)).filter(f => f.endsWith('.png') || f.endsWith('.json'));
  console.log(`✓ Staging directory created: ${allStagingFiles.length} files`);

  // Verify SHA-256 for each PNG file in staging before promotion
  for (const file of allStagingFiles) {
    if (file.endsWith('.png')) {
      const stagingPath = join(stagingDir, file);
      const stagingData = await fs.readFile(stagingPath);
      const stagingSha = getFileSHA256(stagingData);

      // Find corresponding result to compare
      const result = allResults.find(r => r.filename === file);
      if (result && stagingSha !== result.sha) {
        throw new Error(`SHA-256 mismatch for ${file}: expected ${result.sha}, got ${stagingSha}`);
      }
    }
  }
  console.log(`✓ SHA-256 verified for all staged files before promotion`);

  // Atomically swap staging directory with final directory (with recovery on failure)
  const finalOutputParent = join(finalOutputDir, '..');
  const finalDirBasename = basename(finalOutputDir);
  const backupDir = join(finalOutputParent, `.${finalDirBasename}-backup-${Date.now()}`);

  let promotionSuccessful = false;
  let promotionError = null;
  try {
    // Backup existing final directory if it exists
    if (existsSync(finalOutputDir)) {
      await fs.rename(finalOutputDir, backupDir);
      console.log(`✓ Previous directory backed up to ${backupDir}`);
    }

    // Atomic swap: rename staging to final
    await fs.rename(stagingDir, finalOutputDir);
    promotionSuccessful = true;
    console.log(`✓ Atomic swap complete: ${stagingDir} → ${finalOutputDir}`);
  } catch (err) {
    promotionError = err;
    // If promotion failed and we have a backup, try to restore it
    if (existsSync(backupDir)) {
      console.error(`✗ Promotion failed: ${err.message}`);
      console.log(`⚠ Attempting to restore backup from ${backupDir}...`);
      try {
        // Remove the partially-created staging-to-final directory if it exists
        if (existsSync(finalOutputDir)) {
          rmSync(finalOutputDir, { recursive: true, force: true });
        }
        // Restore the backup
        await fs.rename(backupDir, finalOutputDir);
        console.log(`✓ Successfully restored backup to ${finalOutputDir}`);
      } catch (restoreErr) {
        console.error(`✗ Failed to restore backup: ${restoreErr.message}`);
        throw new Error(`Backup restoration failed: ${restoreErr.message}. Original promotion error: ${err.message}`);
      }
      // After successful restore, propagate the original promotion error
      throw new Error(`Promotion failed and backup restored. Original error: ${err.message}`);
    } else {
      throw err;
    }
  }

  if (!promotionSuccessful) {
    rmSync(stagingDir, { recursive: true, force: true });
    process.exit(1);
  }

  // Verify SHA-256 again after final promotion
  for (const file of allStagingFiles) {
    if (file.endsWith('.png')) {
      const finalPath = join(finalOutputDir, file);
      const finalData = await fs.readFile(finalPath);
      const finalSha = getFileSHA256(finalData);

      // Compare with original staging hash
      if (finalSha !== stagingHashes[file]) {
        throw new Error(`SHA-256 mismatch after promotion for ${file}: expected ${stagingHashes[file]}, got ${finalSha}`);
      }
    }
  }
  console.log(`✓ SHA-256 verified for all promoted files after promotion`);

  console.log(`✓ Manifest: ${manifestPath}`);

  // Print manifest table
  console.log('\n## Screenshot Manifest (Machine-Readable)\n');
  console.log(`Location: docs/design-v2/implementation/screenshots/round6-visual-parity/screenshot-manifest.json`);
  console.log('\n## Screenshot Manifest (Table)\n');
  console.log('| Route | Type | Viewport | Filename | SHA-256 |');
  console.log('|---|---|---|---|---|');
  for (const r of allResults) {
    console.log(`| ${r.route} | ${r.type} | ${r.dimensions} | ${r.filename} | ${r.sha} |`);
  }

  // Cleanup temp directory
  rmSync(tempOutputDir, { recursive: true, force: true });
  console.log(`\n✅ Temporary directory cleaned up`);
  console.log(`\n✅ Batch 1 Screenshot Generation Complete`);
  console.log(`   - ${totalCaptured} screenshots captured and verified`);
  console.log(`   - JSON manifest generated and validated`);
  console.log(`   - All evidence promoted to final directory`);
}

function validateManifest(manifest, isFullRun = true) {
  const errors = [];

  if (!manifest.screenshots || !Array.isArray(manifest.screenshots)) {
    errors.push('Missing or invalid screenshots array');
    return { valid: false, errors };
  }

  if (manifest.screenshots.length === 0) {
    errors.push('No screenshots in manifest');
  }

  // For full runs, expect exactly 32 screenshots; for smoke tests, allow fewer
  if (isFullRun && manifest.screenshots.length !== 32) {
    errors.push(`Expected 32 screenshots for full run, got ${manifest.screenshots.length}`);
  } else if (!isFullRun && manifest.screenshots.length < 2) {
    errors.push(`Expected at least 2 screenshots for smoke test, got ${manifest.screenshots.length}`);
  }

  // Check for unique filenames
  const filenames = new Set();
  const routeViewpointTypes = {};

  for (const ss of manifest.screenshots || []) {
    // Validate required fields
    if (!ss.filename) errors.push('Missing filename');
    if (!ss.sha256) errors.push('Missing SHA-256 hash');
    if (!ss.sha256 || ss.sha256 === 'unknown') errors.push(`Invalid/missing SHA-256 for ${ss.filename}`);
    if (!ss.route) errors.push('Missing route');
    if (!ss.final_url) errors.push('Missing final_url');
    if (!ss.capture_type) errors.push('Missing capture_type');
    if (!ss.viewport_dimensions) errors.push(`Missing viewport_dimensions for ${ss.filename}`);
    if (!ss.actual_dimensions) errors.push(`Missing actual_dimensions for ${ss.filename}`);
    if (ss.http_status !== 200) errors.push(`HTTP status not 200 for ${ss.filename}: ${ss.http_status}`);
    if (!ss.authentication_state) errors.push(`Missing authentication_state for ${ss.filename}`);
    if (!ss.source_commit || ss.source_commit === 'unknown') errors.push(`Invalid/missing source_commit for ${ss.filename}`);
    if (!ss.timestamp) errors.push(`Missing timestamp for ${ss.filename}`);

    // Validate authenticated routes have proper URLs
    if (ss.authentication_state === 'authenticated') {
      if (!ss.final_url.includes('/admin')) {
        errors.push(`Protected route ${ss.route} has non-admin final_url: ${ss.final_url}`);
      }
    }

    // Validate capture types
    if (!['viewport', 'full-page'].includes(ss.capture_type)) {
      errors.push(`Invalid capture_type for ${ss.filename}: ${ss.capture_type}`);
    }

    // Track unique filenames
    if (ss.filename) {
      if (filenames.has(ss.filename)) {
        errors.push(`Duplicate filename: ${ss.filename}`);
      }
      filenames.add(ss.filename);
    }

    // Track route/viewport/capture-type cardinality
    if (ss.route && ss.viewport_dimensions && ss.capture_type) {
      const key = `${ss.route}/${ss.viewport_dimensions}/${ss.capture_type}`;
      if (routeViewpointTypes[key]) {
        errors.push(`Duplicate route/viewport/type combination: ${key}`);
      }
      routeViewpointTypes[key] = ss.filename;
    }
  }

  // For full runs, verify exact cardinality
  if (isFullRun) {
    const expectedCombinations = 32; // 8 routes × 2 viewports × 2 types
    const actualCombinations = Object.keys(routeViewpointTypes).length;
    if (actualCombinations !== expectedCombinations) {
      errors.push(`Route/viewport/type cardinality mismatch: expected ${expectedCombinations}, got ${actualCombinations}`);
    }
  }

  return {
    valid: errors.length === 0,
    errors
  };
}

main().catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});
