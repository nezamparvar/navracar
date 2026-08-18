import { chromium } from '@playwright/test';
import { createHash } from 'crypto';
import { mkdirSync, writeFileSync, readFileSync, existsSync } from 'fs';
import { join } from 'path';

const baseURL = 'http://127.0.0.1:8000';
const outputDir = join(process.cwd(), 'docs/design-v2/implementation/screenshots/round6-visual-parity');
mkdirSync(outputDir, { recursive: true });

// Batch 1 acceptance: Strict documented allowlist of external hosts expected to load.
// Certificate errors only allowed from these exact hosts; unknown hosts always fail.
// Localhost/127.0.0.1 cert errors ALWAYS fail (no exception).
const EXTERNAL_HOST_ALLOWLIST = [
  // No external hosts currently required for Batch 1 priority routes.
  // This list must remain finite and explicitly documented.
  // Add hosts ONLY with explicit business justification + PR review.
];

// Batch 1 Priority Routes (visual parity matrix) — 6 routes × 2 sizes × 2 viewport types = 24 screenshots
const routes = [
  // Public routes (no auth required)
  { path: '/', name: 'homepage', auth: false, sizes: [390, 1440] },
  { path: '/car-prices', name: 'vehicle-list', auth: false, sizes: [390, 1440] },
  { path: '/car-prices/e2e-bmw-x4', name: 'vehicle-detail', auth: false, sizes: [390, 1440] },
  { path: '/calculator', name: 'calculator', auth: false, sizes: [390, 1440] },
  // Admin routes (require login)
  { path: '/admin', name: 'admin-dashboard', auth: true, sizes: [390, 1440] },
  { path: '/admin/sales-dashboard', name: 'sales-dashboard', auth: true, sizes: [390, 1440] },
];

const viewportSizes = {
  390: { width: 390, height: 844 },
  1440: { width: 1440, height: 900 },
};

async function loginAdmin(page) {
  await page.goto(`${baseURL}/admin/login`);
  await page.locator('input[name="username"]').fill('admin');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/admin$/);
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
    requestFailures.push(`Request failed: ${req.url()} (${req.failure()?.errorText})`);
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
    // Reject only real page/JS errors that would affect rendering.
    const filteredConsoleErrors = consoleErrors.filter(e => {
      // Allow "Failed to load resource" cert errors (proxy-level, external resources)
      if (e.includes('Failed to load resource') && e.includes('ERR_CERT_AUTHORITY_INVALID')) return false;
      // Reject actual JS errors or page execution errors
      return true;
    });
    if (filteredConsoleErrors.length > 0) {
      throw new Error(`Console errors: ${filteredConsoleErrors.join('; ')}`);
    }

    // Batch 1 enforcement: only allow cert errors from explicitly allowlisted external hosts.
    // Localhost/127.0.0.1 cert errors always fail. Unknown hosts always fail.
    const filteredRequestFailures = requestFailures.filter(f => {
      // Cert error from localhost/127.0.0.1 always fails (these should work).
      if ((f.includes('127.0.0.1') || f.includes('localhost')) && f.includes('ERR_CERT_AUTHORITY_INVALID')) {
        return true; // Reject (fail the screenshot).
      }
      // Cert error from allowlisted external host is acceptable.
      if (f.includes('ERR_CERT_AUTHORITY_INVALID')) {
        const hostMatches = EXTERNAL_HOST_ALLOWLIST.some(host => f.includes(host));
        if (hostMatches) {
          return false; // Allow (don't reject).
        }
      }
      // All other failures rejected (non-cert errors, or cert errors from unknown hosts).
      return true;
    });
    if (filteredRequestFailures.length > 0) {
      throw new Error(`Request failures (cert errors only allowed from allowlist: ${EXTERNAL_HOST_ALLOWLIST.join(', ') || 'NONE'}): ${filteredRequestFailures.join('; ')}`);
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
