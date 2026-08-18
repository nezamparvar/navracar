import { chromium } from '@playwright/test';
import { createHash } from 'crypto';
import { mkdirSync, writeFileSync, existsSync, rmSync } from 'fs';
import { join } from 'path';
import { tmpdir } from 'os';
import { randomUUID } from 'crypto';

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
      // Vehicle cards are <a> elements in the grid container
      const gridContainer = await page.locator('div.grid').first();
      const carLinks = await gridContainer.locator('a[href*="/car-prices/"]').count();
      if (carLinks < 2) throw new Error('Expected at least 2 vehicle cards, got ' + carLinks);
    }
  },
  {
    path: '/car-prices/e2e-bmw-x4',
    name: 'vehicle-detail',
    auth: false,
    sizes: [390, 1440],
    requiresHeading: 'بی‌ام‌و X4 تست',
    assertion: async (page) => {
      // Look for price information (typically shown as a number)
      const priceElements = await page.locator('div').filter({ has: page.locator('text=/\\d+\\s*(درهم|ریال)/')}).first().isVisible();
      if (!priceElements) throw new Error('Price not visible on vehicle detail page');
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
      // Admin dashboard should have main content (sidebar may be hidden on mobile)
      const main = await page.locator('main, [role="main"], [class*="main"]').isVisible().catch(() => false);
      if (!main) throw new Error('Main content area not visible');
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
      // Sales dashboard should have some content (non-empty page body after heading)
      const mainContent = await page.locator('main, [role="main"]').isVisible().catch(() => false);
      if (!mainContent) throw new Error('Sales dashboard main content not visible');
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
      // Content dashboard should have table with rows
      const tableRows = await page.locator('table tbody tr').count();
      if (tableRows < 1) throw new Error('Expected at least 1 content row in dashboard, got ' + tableRows);
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
      // Calendar view should have some visible content
      const calendarContent = await page.locator('main, [role="main"]').isVisible().catch(() => false);
      if (!calendarContent) throw new Error('Calendar view not visible');
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
      // Calendar view should have some visible content
      const calendarContent = await page.locator('main, [role="main"]').isVisible().catch(() => false);
      if (!calendarContent) throw new Error('Calendar view not visible');
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
      // Calendar view should have some visible content
      const calendarContent = await page.locator('main, [role="main"]').isVisible().catch(() => false);
      if (!calendarContent) throw new Error('Calendar view not visible');
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

async function captureScreenshot(browser, route, viewportSize, storageState, outputDir) {
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
      // Only treat as error if it's not a resource loading issue (403, 404, etc)
      const text = msg.text();
      if (!text.includes('403') && !text.includes('404') && !text.includes('CORS')) {
        consoleErrors.push(`Console error: ${text}`);
      }
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
      commit: process.env.GIT_COMMIT || 'unknown'
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
      dimensions: `${fullPageDims?.width}×${fullPageDims?.height}`,
      type: 'full-page',
      route: route.name,
      requestedUrl,
      finalUrl,
      authState: route.auth ? 'authenticated' : 'unauthenticated',
      httpStatus,
      viewport: `${viewport.width}x${viewport.width}`,
      blockedRequests: [...new Set(blockedRequests)],
      timestamp: new Date().toISOString(),
      commit: process.env.GIT_COMMIT || 'unknown'
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

  console.log(`\n📸 Starting Batch 1 screenshot generation\n`);
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
      const { results, hasError } = await captureScreenshot(browser, route, size, storageState, tempOutputDir);
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

  // Ensure final output directory exists
  mkdirSync(finalOutputDir, { recursive: true });

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

  // Copy all files from temp to final directory
  console.log(`\n🚀 Promoting ${totalCaptured} screenshots to final directory...`);
  const fs = await import('fs').then(m => m.promises);
  const allTempFiles = (await fs.readdir(tempOutputDir)).filter(f => f.endsWith('.png'));

  for (const file of allTempFiles) {
    const tempPath = join(tempOutputDir, file);
    const finalPath = join(finalOutputDir, file);
    const data = await fs.readFile(tempPath);
    await fs.writeFile(finalPath, data);
  }

  // Write manifest to final directory
  writeFileSync(manifestPath, manifestJson);
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
  }

  if (manifest.screenshots.length === 0) {
    errors.push('No screenshots in manifest');
  }

  // For full runs, expect 32 screenshots; for smoke tests, allow fewer
  if (isFullRun && manifest.screenshots.length !== 32) {
    errors.push(`Expected 32 screenshots for full run, got ${manifest.screenshots.length}`);
  } else if (!isFullRun && manifest.screenshots.length < 2) {
    errors.push(`Expected at least 2 screenshots for smoke test, got ${manifest.screenshots.length}`);
  }

  for (const ss of manifest.screenshots || []) {
    if (!ss.filename) errors.push('Missing filename');
    if (!ss.sha256) errors.push('Missing SHA-256 hash');
    if (!ss.route) errors.push('Missing route');
    if (!ss.final_url) errors.push('Missing final_url');
    if (!ss.capture_type) errors.push('Missing capture_type');
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
