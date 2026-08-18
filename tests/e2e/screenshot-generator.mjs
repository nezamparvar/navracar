import { chromium } from '@playwright/test';
import { createHash } from 'crypto';
import { mkdirSync, writeFileSync } from 'fs';
import { join } from 'path';

const baseURL = 'http://127.0.0.1:8000';
const outputDir = join(process.cwd(), 'docs/design-v2/implementation/screenshots/round4-remediation-r2');
mkdirSync(outputDir, { recursive: true });

const routes = [
  // Public routes
  { path: '/', name: 'homepage', auth: false, viewports: [375, 1280] },
  { path: '/car-prices', name: 'vehicle-list', auth: false, viewports: [375, 1280] },
  { path: '/car-prices/e2e-bmw-x4', name: 'vehicle-detail', auth: false, viewports: [375, 1280] },
  { path: '/calculator', name: 'calculator', auth: false, viewports: [375, 1280] },
  { path: '/lead-form', name: 'lead-form', auth: false, viewports: [375, 1280] },
  { path: '/track/1?phone=09120000000', name: 'request-tracking', auth: false, viewports: [375] },
  // Admin routes (need login)
  { path: '/admin', name: 'admin-dashboard', auth: true, viewports: [375, 1280] },
  { path: '/admin/sales-dashboard', name: 'sales-dashboard', auth: true, viewports: [375, 1280] },
  { path: '/admin/content-dashboard', name: 'content-dashboard', auth: true, viewports: [375, 1280] },
  { path: '/admin/calendar?view=day', name: 'calendar-day', auth: true, viewports: [375] },
  { path: '/admin/calendar?view=week', name: 'calendar-week', auth: true, viewports: [375, 1280] },
  { path: '/admin/calendar?view=list', name: 'calendar-list', auth: true, viewports: [375, 1280] },
  { path: '/admin/kanban', name: 'kanban', auth: true, viewports: [375, 1280] },
];

const viewportSizes = {
  375: { width: 375, height: 812 },
  1280: { width: 1280, height: 800 },
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

async function captureScreenshot(browser, route, viewportWidth) {
  const viewport = viewportSizes[viewportWidth];
  const context = await browser.newContext({ viewport });
  const page = await context.newPage();

  try {
    if (route.auth) {
      await loginAdmin(page);
    }

    const response = await page.goto(`${baseURL}${route.path}`);
    if (!response || response.status() !== 200) {
      throw new Error(`Failed to load ${route.path}: HTTP ${response?.status()}`);
    }

    // Verify page loaded properly
    const bodyText = await page.evaluate(() => document.body?.innerText ?? '');
    if (bodyText.includes('Whoops') || bodyText.includes('exception') || bodyText.includes('ERR_CONNECTION')) {
      throw new Error(`Page error detected on ${route.path}`);
    }

    // Capture viewport-sized screenshot (not full-page)
    const screenshotBuffer = await page.screenshot({ fullPage: false });

    const filename = `${route.name}-viewport-${viewport.width}x${viewport.height}.png`;
    const filepath = join(outputDir, filename);
    writeFileSync(filepath, screenshotBuffer);

    const sha = getFileSHA256(screenshotBuffer);
    console.log(`✓ ${filename} — ${sha}`);

    return { filename, sha, dimensions: `${viewport.width}×${viewport.height}` };
  } finally {
    await context.close();
  }
}

async function main() {
  const browser = await chromium.launch();
  const results = [];

  console.log(`\nCapturing screenshots to: ${outputDir}\n`);

  for (const route of routes) {
    for (const viewportWidth of route.viewports) {
      try {
        const result = await captureScreenshot(browser, route, viewportWidth);
        results.push({ route: route.name, ...result });
      } catch (err) {
        console.error(`✗ ${route.name} (${viewportWidth}): ${err.message}`);
      }
    }
  }

  await browser.close();

  // Print manifest table
  console.log('\n## Screenshot Manifest\n');
  console.log('| Page | Viewport | Filename | SHA-256 |');
  console.log('|---|---|---|---|');
  for (const r of results) {
    console.log(`| ${r.route} | ${r.dimensions} | ${r.filename} | ${r.sha} |`);
  }
}

main().catch(console.error);
