import { chromium } from '@playwright/test';
import { createHash } from 'crypto';
import { mkdirSync, writeFileSync, existsSync } from 'fs';
import { join } from 'path';
import { tmpdir } from 'os';

const baseURL = 'http://127.0.0.1:8000';
const outputDir = join(process.cwd(), 'docs/design-v2/implementation/screenshots/round6-visual-parity');

// Parse CLI arguments: --route=NAME --viewport=SIZE
const args = process.argv.slice(2);
const cliRoute = args.find(a => a.startsWith('--route='))?.split('=')[1];
const cliViewport = args.find(a => a.startsWith('--viewport='))?.split('=')[1];

// Batch 1 acceptance: Strict documented allowlist of external hosts.
const EXTERNAL_HOST_ALLOWLIST = [];

// Batch 1 Required Priority Routes (8 routes × 2 sizes × 2 viewport types = 32 screenshots)
const routes = [
  { path: '/car-prices', name: 'vehicle-list', auth: false, sizes: [390, 1440], requiresHeading: 'قیمت خودروها' },
  { path: '/car-prices/e2e-bmw-x4', name: 'vehicle-detail', auth: false, sizes: [390, 1440], requiresHeading: 'بی‌ام‌و X4 تست' },
  { path: '/admin', name: 'admin-dashboard', auth: true, sizes: [390, 1440], requiresHeading: 'داشبورد مدیریت', requiresUrl: /^https?:\/\/[^\/]+\/admin($|\?)/ },
  { path: '/admin/sales-dashboard', name: 'sales-dashboard', auth: true, sizes: [390, 1440], requiresHeading: 'داشبورد فروش', requiresUrl: /^https?:\/\/[^\/]+\/admin\/sales-dashboard/ },
  { path: '/admin/content-dashboard', name: 'content-dashboard', auth: true, sizes: [390, 1440], requiresHeading: 'داشبورد محتوا', requiresUrl: /^https?:\/\/[^\/]+\/admin\/content-dashboard/ },
  { path: '/admin/calendar?view=day', name: 'calendar-day', auth: true, sizes: [390, 1440], requiresHeading: 'تقویم', requiresUrl: /^https?:\/\/[^\/]+\/admin\/calendar.*view=day/ },
  { path: '/admin/calendar?view=week', name: 'calendar-week', auth: true, sizes: [390, 1440], requiresHeading: 'تقویم', requiresUrl: /^https?:\/\/[^\/]+\/admin\/calendar.*view=week/ },
  { path: '/admin/calendar?view=list', name: 'calendar-list', auth: true, sizes: [390, 1440], requiresHeading: 'تقویم', requiresUrl: /^https?:\/\/[^\/]+\/admin\/calendar.*view=list/ },
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

  // Route interception: block all external requests
  await authPage.route('**/*', route => {
    const url = new URL(route.request().url());
    const isLocalhost = url.hostname === 'localhost' || url.hostname === '127.0.0.1';
    if (isLocalhost) {
      route.continue();
    } else {
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
  // Wait for fonts and heading only - no arbitrary sleeps or networkidle
  await page.evaluate(() => document.fonts.ready);

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
      consoleErrors.push(`Console error: ${msg.text()}`);
    }
  });

  try {
    // Navigate to route
    const response = await page.goto(`${baseURL}${route.path}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
    if (!response || response.status() !== 200) {
      throw new Error(`Failed to load ${route.path}: HTTP ${response?.status()}`);
    }

    // Wait for route-specific readiness (fonts + heading)
    await waitForRouteReady(page, route);

    // Validate final URL
    const finalUrl = page.url();
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
    results.push({ filename: viewportFilename, sha: viewportSha, dimensions: `${viewportDims.width}×${viewportDims.height}`, type: 'viewport' });

    // Capture full-page screenshot
    const fullPageScreenshot = await page.screenshot({ fullPage: true });
    const fullPageDims = getPNGDimensions(fullPageScreenshot);
    const fullPageFilename = `${route.name}-full-${viewport.width}w.png`;
    const fullPagePath = join(outputDir, fullPageFilename);
    writeFileSync(fullPagePath, fullPageScreenshot);
    const fullPageSha = getFileSHA256(fullPageScreenshot);
    console.log(`✓ ${fullPageFilename} (${fullPageDims?.width}×${fullPageDims?.height}) — ${fullPageSha}`);
    results.push({ filename: fullPageFilename, sha: fullPageSha, dimensions: `${fullPageDims?.width}×${fullPageDims?.height}`, type: 'full-page' });

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

  // Determine output directory (temp for smoke tests, final for full run)
  const finalOutputDir = outputDir;
  const workingOutputDir = cliRoute ? join(tmpdir(), 'navracar-smoke-test') : finalOutputDir;
  mkdirSync(workingOutputDir, { recursive: true });

  const browser = await chromium.launch(launchOptions);
  const allResults = [];
  let totalFailed = 0;

  console.log(`\nCapturing screenshots to: ${workingOutputDir}\n`);

  // Authenticate once and reuse session state
  let storageState;
  try {
    console.log('Authenticating admin session...');
    storageState = await authenticateAndSaveState(browser);
    console.log('✓ Admin session authenticated and saved\n');
  } catch (err) {
    console.error(`✗ Authentication failed: ${err.message}`);
    await browser.close();
    process.exit(1);
  }

  // Determine routes to capture (CLI filtered or all)
  const routesToCapture = cliRoute
    ? routes.filter(r => r.name === cliRoute)
    : routes;

  if (cliRoute && routesToCapture.length === 0) {
    console.error(`✗ Route '${cliRoute}' not found`);
    process.exit(1);
  }

  // Capture routes
  for (const route of routesToCapture) {
    const sizesToCapture = cliViewport ? [parseInt(cliViewport)] : route.sizes;
    for (const size of sizesToCapture) {
      if (!route.sizes.includes(size)) {
        console.error(`✗ Route ${route.name} does not support viewport ${size}`);
        process.exit(1);
      }
      const { results, hasError } = await captureScreenshot(browser, route, size, storageState, workingOutputDir);
      if (hasError) {
        totalFailed++;
      } else {
        allResults.push(...results.map(r => ({ route: route.name, ...r })));
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
    console.error(`\n❌ Screenshot generation failed: ${totalFailed} route(s) failed`);
    process.exit(1);
  }

  if (totalCaptured !== totalExpected) {
    console.error(`\n❌ Screenshot count mismatch: expected ${totalExpected}, got ${totalCaptured}`);
    process.exit(1);
  }

  // Print manifest table
  console.log('\n## Screenshot Manifest\n');
  console.log('| Page | Type | Viewport | Filename | SHA-256 |');
  console.log('|---|---|---|---|---|');
  for (const r of allResults) {
    console.log(`| ${r.route} | ${r.type} | ${r.dimensions} | ${r.filename} | ${r.sha} |`);
  }

  console.log(`\n✅ All ${totalCaptured} screenshots captured successfully`);
}

main().catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});
