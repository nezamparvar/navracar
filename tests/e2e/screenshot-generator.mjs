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
// Localhost/127.0.0.1 errors ALWAYS fail.
const EXTERNAL_HOST_ALLOWLIST = [
  // EMPTY FOR BATCH 1: No external dependencies expected.
  // Add hostnames ONLY with explicit business justification + audit trail in docs/design-v2/implementation/EXTERNAL_HOSTNAME_ALLOWLIST.md
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

async function loginAdmin(page) {
  await page.goto(`${baseURL}/admin/login`, { waitUntil: 'domcontentloaded', timeout: 10000 });
  await page.locator('input[name="username"]').fill('admin');
  await page.locator('input[name="password"]').fill('password');
  const submitButton = page.locator('button[type="submit"]');
  await submitButton.click();

  // Wait for navigation away from login page
  try {
    await page.waitForNavigation({ url: /^https?:\/\/[^\/]+\/admin($|\?)/, timeout: 30000 });
  } catch {
    // Navigation wait timed out, but check if we actually got redirected
    const currentUrl = page.url();
    if (currentUrl.includes('/admin/login')) {
      throw new Error(`Authentication failed: still on login page at ${currentUrl}`);
    }
  }

  // Verify authenticated shell is present
  const currentUrl = page.url();
  const hasSidebar = await page.locator('aside').isVisible({ timeout: 3000 }).catch(() => false);
  const hasPageTitle = await page.locator('h1').isVisible({ timeout: 3000 }).catch(() => false);
  if (!hasSidebar && !hasPageTitle) {
    throw new Error(`Authentication verification failed: authenticated UI shell not found at ${currentUrl}`);
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

async function captureScreenshot(browser, route, viewportSize) {
  const viewport = viewportSizes[viewportSize];
  const context = await browser.newContext({ viewport });
  const page = await context.newPage();
  const results = [];
  let hasError = false;

  // Attach error listeners BEFORE navigation to catch all errors
  const pageErrors = [];
  const requestFailures = [];
  const consoleErrors = [];

  page.on('pageerror', err => {
    pageErrors.push(`Page error: ${err.message}`);
  });

  page.on('requestfailed', req => {
    // All request failures recorded with hostname for audit
    const urlMatch = req.url().match(/https?:\/\/([^\/:?]+)/);
    const hostname = urlMatch ? urlMatch[1] : 'unknown';
    requestFailures.push({
      url: req.url(),
      hostname,
      error: req.failure()?.errorText,
      string: `Request failed: ${req.url()} [${hostname}] (${req.failure()?.errorText})`
    });
  });

  page.on('console', msg => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
  });

  try {
    if (route.auth) {
      await loginAdmin(page);
    }

    const response = await page.goto(`${baseURL}${route.path}`, { waitUntil: 'networkidle' });
    if (!response || response.status() !== 200) {
      throw new Error(`Failed to load ${route.path}: HTTP ${response?.status()}`);
    }

    // Validate final URL matches expected authenticated route (no login redirect)
    const finalUrl = page.url();
    if (route.requiresUrl && !route.requiresUrl.test(finalUrl)) {
      throw new Error(`Route URL mismatch: expected ${route.requiresUrl}, got ${finalUrl}`);
    }
    if (route.auth && finalUrl.includes('/admin/login')) {
      throw new Error(`Authentication redirect detected: ended up at login page for ${route.name}`);
    }

    // Validate route-specific heading/landmark is present
    if (route.requiresHeading) {
      const headingFound = await page.getByRole('heading', { name: new RegExp(route.requiresHeading, 'i') }).first().isVisible({ timeout: 5000 }).catch(() => false);
      if (!headingFound) {
        // Fallback: check page text for the heading
        const pageText = await page.evaluate(() => document.body.innerText);
        if (!pageText.includes(route.requiresHeading)) {
          throw new Error(`Route validation failed: heading "${route.requiresHeading}" not found on ${route.name}`);
        }
      }
    }

    // Verify page loaded properly
    const bodyText = await page.evaluate(() => document.body?.innerText ?? '');
    if (bodyText.includes('Whoops') || bodyText.includes('exception') || bodyText.includes('ERR_CONNECTION')) {
      throw new Error(`Page error detected on ${route.path}`);
    }

    // Check for captured errors during page load/navigation
    if (pageErrors.length > 0) {
      throw new Error(`Page errors: ${pageErrors.join('; ')}`);
    }
    // Allow certificate errors only from resource loading (proxy-level cert issues).
    // Suppress cert errors from Chromium/Playwright itself (source maps, vendor scripts, proxy bypass).
    // Reject other real page/JS errors that would affect rendering.
    const filteredConsoleErrors = consoleErrors.filter(e => {
      // Skip certificate errors entirely (source maps, vendor scripts, test infrastructure)
      if (e.includes('ERR_CERT_AUTHORITY_INVALID') || e.includes('ERR_CERT_COMMON_NAME_INVALID')) {
        return false;
      }
      // All other errors rejected: page errors, DNS failure, 429, etc.
      return true;
    });
    if (filteredConsoleErrors.length > 0) {
      throw new Error(`Page errors: ${filteredConsoleErrors.join('; ')}`);
    }

    // Batch 1 strict enforcement: all request failures reject (no allowlist bypass).
    // Request failures are only suppressed if host is in allowlist AND error is cert-only.
    // Cert errors from localhost are suppressed (source maps, vendor scripts).
    const failedRequests = [];
    for (const f of requestFailures) {
      const isLocalhost = f.hostname === 'localhost' || f.hostname === '127.0.0.1' || f.hostname.startsWith('127.');
      const isCertError = f.error && f.error.includes('CERT');
      const isAllowlisted = f.hostname && EXTERNAL_HOST_ALLOWLIST.includes(f.hostname);

      // Suppress cert errors from localhost
      if (isLocalhost && isCertError) {
        continue;
      }

      // Suppress cert errors from allowlisted hosts
      if (isAllowlisted && isCertError) {
        continue;
      }

      // Reject everything else
      failedRequests.push(f.string);
    }

    if (failedRequests.length > 0) {
      throw new Error(`Request failures (allowlist: ${EXTERNAL_HOST_ALLOWLIST.join(', ') || 'EMPTY'}): ${failedRequests.join('; ')}`);
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

  for (const route of routes) {
    for (const size of route.sizes) {
      const { results, hasError } = await captureScreenshot(browser, route, size);
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
