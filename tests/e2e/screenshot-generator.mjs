import { chromium } from '@playwright/test';
import { createHash } from 'crypto';
import { mkdirSync, writeFileSync, readFileSync, existsSync } from 'fs';
import { join } from 'path';

const baseURL = 'http://127.0.0.1:8000';
const outputDir = join(process.cwd(), 'docs/design-v2/implementation/screenshots/round6-visual-parity');
mkdirSync(outputDir, { recursive: true });

// Batch 1 acceptance: Strict documented allowlist of external hosts.
// Currently EMPTY: all external resources must be self-hosted or unavailable.
// No bypasses, no generalized suppression. Certificate or DNS errors from ANY host fail immediately.
const EXTERNAL_HOST_ALLOWLIST = [
  // EMPTY FOR BATCH 1: No external dependencies expected.
];

// Batch 1 Required Priority Routes (8 routes × 2 sizes × 2 viewport types = 32 screenshots)
const routes = [
  // Public routes (no auth required)
  { path: '/car-prices', name: 'vehicle-list', auth: false, sizes: [390, 1440], requiresHeading: 'قیمت خودروها' },
  { path: '/car-prices/e2e-bmw-x4', name: 'vehicle-detail', auth: false, sizes: [390, 1440], requiresHeading: 'بی‌ام‌و X4 تست' },
  // Admin routes (require strict authentication)
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

async function authenticateAndSaveState(browser) {
  const authContext = await browser.newContext();
  const authPage = await authContext.newPage();

  try {
    // Block all external requests during authentication
    await authPage.route('**/*', route => {
      const url = new URL(route.request().url());
      const isLocalhost = url.hostname === 'localhost' || url.hostname === '127.0.0.1';

      if (isLocalhost) {
        route.continue();
      } else {
        route.abort('blockedbyclient');
      }
    });

    // Navigate to login
    await authPage.goto(`${baseURL}/admin/login`, { waitUntil: 'domcontentloaded', timeout: 10000 });

    // Fill login form
    await authPage.locator('input[name="username"]').fill('admin');
    await authPage.locator('input[name="password"]').fill('password');

    // Click submit and wait for URL change (not navigation event which might not fire)
    const submitButton = authPage.locator('button[type="submit"]');
    await submitButton.click();

    // Wait for URL to change away from login page with fast polling
    let attempts = 0;
    const maxAttempts = 200; // 10 seconds at 50ms intervals
    const pollInterval = 50;
    while (attempts < maxAttempts) {
      const currentUrl = authPage.url();
      if (!currentUrl.includes('/admin/login')) {
        break;
      }
      await authPage.waitForTimeout(pollInterval);
      attempts++;
    }

    // Verify authenticated state
    const finalUrl = authPage.url();
    if (finalUrl.includes('/admin/login')) {
      throw new Error(`Authentication failed: still on login page after ${attempts * pollInterval}ms at ${finalUrl}`);
    }

    // Wait for authenticated UI shell with longer timeout
    await authPage.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
    const hasSidebar = await authPage.locator('aside').isVisible({ timeout: 5000 }).catch(() => false);
    const hasPageTitle = await authPage.locator('h1').isVisible({ timeout: 5000 }).catch(() => false);
    if (!hasSidebar && !hasPageTitle) {
      throw new Error(`Authentication verification failed: authenticated UI shell not found at ${finalUrl}`);
    }

    // Save authenticated session state
    const storageState = await authContext.storageState();
    await authContext.close();

    return storageState;
  } catch (err) {
    await authContext.close();
    throw err;
  }
}

function getFileSHA256(data) {
  return createHash('sha256').update(data).digest('hex');
}

function getPNGDimensions(buffer) {
  if (buffer.length < 24) return null;
  const width = buffer.readUInt32BE(16);
  const height = buffer.readUInt32BE(20);
  return { width, height };
}

async function waitForRouteReady(page, route) {
  // Wait for essential DOM and fonts to be ready
  await page.evaluate(() => {
    return Promise.all([
      document.fonts.ready,
      new Promise(resolve => {
        if (document.readyState === 'complete') {
          resolve();
        } else {
          document.addEventListener('load', resolve, { once: true });
        }
      })
    ]);
  });

  // Route-specific readiness assertions
  if (route.requiresHeading) {
    await page.getByRole('heading', { name: new RegExp(route.requiresHeading, 'i') }).first().waitFor({ timeout: 5000 });
  }

  // Wait for any dynamic content to stabilize (dashboard widgets, lists, etc.)
  await page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
}

async function captureScreenshot(browser, route, viewportSize, storageState) {
  const viewport = viewportSizes[viewportSize];
  const context = await browser.newContext({
    viewport,
    storageState: route.auth ? storageState : undefined
  });

  const page = await context.newPage();
  const results = [];
  let hasError = false;

  // Track all blocked/failed requests
  const blockedRequests = [];
  const failedRequests = [];

  // Route interception: block all external requests
  await context.route('**/*', route => {
    const url = new URL(route.request().url());
    const isLocalhost = url.hostname === 'localhost' || url.hostname === '127.0.0.1';

    if (isLocalhost) {
      route.continue();
    } else {
      blockedRequests.push({ url: url.href, hostname: url.hostname });
      route.abort('blockedbyclient');
    }
  });

  // Track request failures (network errors, timeouts)
  page.on('requestfailed', req => {
    const urlMatch = req.url().match(/https?:\/\/([^\/:?]+)/);
    const hostname = urlMatch ? urlMatch[1] : 'unknown';
    failedRequests.push({
      url: req.url(),
      hostname,
      error: req.failure()?.errorText
    });
  });

  try {
    // Navigate to route
    const response = await page.goto(`${baseURL}${route.path}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
    if (!response || response.status() !== 200) {
      throw new Error(`Failed to load ${route.path}: HTTP ${response?.status()}`);
    }

    // Wait for route-specific readiness
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

    // Record any blocked external requests (informational, not a failure)
    if (blockedRequests.length > 0) {
      console.log(`  ℹ Blocked ${blockedRequests.length} external request(s) (expected): ${blockedRequests.map(r => r.hostname).join(', ')}`);
    }

    // Failed requests that aren't blocked are a real error
    if (failedRequests.length > 0) {
      const failedHostnames = failedRequests.map(r => `${r.hostname} (${r.error})`).join('; ');
      throw new Error(`Request failures on external hosts: ${failedHostnames}. Allowlist: ${EXTERNAL_HOST_ALLOWLIST.join(', ') || 'EMPTY'}`);
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

  const browser = await chromium.launch(launchOptions);
  const allResults = [];
  let totalFailed = 0;

  console.log(`\nCapturing screenshots to: ${outputDir}\n`);

  // Authenticate once and reuse session state
  let storageState;
  try {
    console.log('Authenticating admin session...');
    storageState = await authenticateAndSaveState(browser);
    console.log('✓ Admin session authenticated and saved\n');
  } catch (err) {
    console.error(`✗ Authentication failed: ${err.message}`);
    process.exit(1);
  }

  // Capture each route
  for (const route of routes) {
    for (const size of route.sizes) {
      const { results, hasError } = await captureScreenshot(browser, route, size, storageState);
      if (hasError) {
        totalFailed++;
      } else {
        allResults.push(...results.map(r => ({ route: route.name, ...r })));
      }
    }
  }

  await browser.close();

  // Verify capture counts
  const expectedViewportCount = routes.reduce((sum, r) => sum + r.sizes.length, 0);
  const expectedFullPageCount = routes.reduce((sum, r) => sum + r.sizes.length, 0);
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
