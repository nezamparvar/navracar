import { expect, test } from '@playwright/test';

async function loginAdmin(page) {
    await page.goto('/admin/login');
    await page.locator('input[name="username"]').fill('admin');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/admin$/);
}

// Sandbox-only network artifact: app.css @imports Vazirmatn from Google Fonts, which this
// test sandbox's network policy resets (net::ERR_CONNECTION_RESET) — the request is fine in
// every real deployment, this environment just has no egress to that host. Excluding it is
// scoped tightly to that exact host, not to the whole net::ERR_* class, so a real same-origin
// asset failure (a broken app.css/app.js path, a missing image route) still fails the test.
const IGNORED_FAILED_HOSTS = ['fonts.googleapis.com', 'fonts.gstatic.com'];

// Round-4-remediation-r2 finding (owner-reported): a route that 500s still renders a small,
// overflow-free error page, so a bare scrollWidth check silently passes against a broken page.
// This wraps every navigation used by the tests below with real health checks: HTTP 200, no
// Whoops/exception/connection-error/blank-page markers in the rendered text, and no console or
// page errors during load (aside from the one sandbox-network exclusion above) — any of those
// fail the test, not just a wide document.
async function gotoAndVerify(page, route) {
    const errors = [];
    const failedHosts = new Set();
    const onConsole = (msg) => {
        if (msg.type() === 'error') errors.push(`console error: ${msg.text()}`);
    };
    const onPageError = (err) => errors.push(`page error: ${err.message}`);
    const onRequestFailed = (req) => {
        try {
            failedHosts.add(new URL(req.url()).host);
        } catch {
            // ignore malformed URLs
        }
    };
    page.on('console', onConsole);
    page.on('pageerror', onPageError);
    page.on('requestfailed', onRequestFailed);

    let response;
    try {
        response = await page.goto(route);
        expect(response, `${route}: navigation produced no response`).not.toBeNull();
        expect(response.status(), `${route}: expected HTTP 200`).toBe(200);

        const bodyText = await page.evaluate(() => document.body?.innerText ?? '');
        const title = await page.title();

        const errorMarkers = ['Whoops', 'exception', 'ERR_CONNECTION', 'ConnectionRefused', 'Server Error', 'Fatal error'];
        for (const marker of errorMarkers) {
            expect(bodyText, `${route}: rendered body contains error marker "${marker}"`).not.toContain(marker);
        }
        expect(bodyText.trim().length, `${route}: rendered body is blank/near-empty`).toBeGreaterThan(20);
        expect(title.trim().length, `${route}: page has no <title>`).toBeGreaterThan(0);
    } finally {
        page.off('console', onConsole);
        page.off('pageerror', onPageError);
        page.off('requestfailed', onRequestFailed);
    }

    // A generic "Failed to load resource" console error is only excused when EVERY failed
    // request this navigation saw was to an ignored host — any other cause still fails.
    const onlyIgnoredHostsFailed = [...failedHosts].every((host) => IGNORED_FAILED_HOSTS.includes(host));
    const realErrors = errors.filter((e) => !(onlyIgnoredHostsFailed && failedHosts.size > 0 && /Failed to load resource/.test(e)));
    expect(realErrors, `${route}: console/page errors during load — ${realErrors.join(' | ')}`).toHaveLength(0);

    return response;
}

const publicRoutes = ['/', '/calculator', '/lead-form', '/admin/login', '/car-prices', '/car-prices/e2e-bmw-x4', '/track'];
const adminRoutes = [
    '/admin',
    '/admin/sales-dashboard',
    '/admin/content-dashboard',
    '/admin/calendar?view=day',
    '/admin/calendar?view=week',
    '/admin/calendar?view=list',
    '/admin/kanban',
    '/admin/requests',
];

// Regression coverage for the round-4 finding: the mobile off-canvas admin sidebar (and, before
// that, ungapped flex/grid items inside stat-card/x-card) contributed to
// document.documentElement.scrollWidth even while visually off-screen or internally scrolled,
// producing severe horizontal overflow that wasn't caught by the smaller route list this file
// used to check. Every route below is a page that was actually broken at some point this round.
// Runs across every viewport project configured in playwright.config.js, which includes the
// owner's mandatory 320/360/375/390/430/768/1024/1280/1440 set among others.
for (const route of publicRoutes) {
    test(`${route} has no horizontal page overflow`, async ({ page }) => {
        await gotoAndVerify(page, route);
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
        expect(overflow).toBeLessThanOrEqual(1);
    });
}

for (const route of adminRoutes) {
    test(`${route} has no horizontal page overflow (admin)`, async ({ page }) => {
        await loginAdmin(page);
        await gotoAndVerify(page, route);
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
        expect(overflow).toBeLessThanOrEqual(1);
    });
}

test('public mobile navigation opens without obscuring its links', async ({ page }, testInfo) => {
    test.skip(testInfo.project.use.viewport.width >= 640, 'The full navigation is already visible.');

    await gotoAndVerify(page, '/');
    const toggle = page.getByRole('button', { name: 'باز کردن منو' });
    await toggle.click();
    await expect(page.locator('header [x-show="mobileMenuOpen"]').getByRole('link').first()).toBeVisible();
});

// Regression coverage for the round-4 finding that screenshots of the calendar/request-tracking
// pages appeared to show the fixed bottom nav covering real content, and the round-4-remediation-r2
// finding that the original version of this test only walked <main>'s last-child chain — missing
// the footer that follows <main> on public pages, so a footer sitting under the nav could still
// pass. This version measures across the ENTIRE document body (any visible, non-fixed/sticky
// element, so it always includes the footer when one exists), converts to document coordinates,
// then re-projects to the current (fully-scrolled) viewport to compare against the fixed nav's top
// edge. Scrolls with an INSTANT scroll (html has scroll-behavior:smooth — an animated scrollTo
// measured too early reports a false, mid-animation position).
const bottomNavPages = [
    { route: '/track/1?phone=09120000000', nav: 'nav[aria-label="ناوبری پایین صفحه"]', auth: false },
    { route: '/admin/calendar?view=day', nav: 'nav[aria-label="ناوبری پایین پنل مدیریت"]', auth: true },
    { route: '/admin/calendar?view=week', nav: 'nav[aria-label="ناوبری پایین پنل مدیریت"]', auth: true },
    { route: '/admin/calendar?view=list', nav: 'nav[aria-label="ناوبری پایین پنل مدیریت"]', auth: true },
    { route: '/admin/kanban', nav: 'nav[aria-label="ناوبری پایین پنل مدیریت"]', auth: true },
    { route: '/admin/sales-dashboard', nav: 'nav[aria-label="ناوبری پایین پنل مدیریت"]', auth: true },
    { route: '/admin/content-dashboard', nav: 'nav[aria-label="ناوبری پایین پنل مدیریت"]', auth: true },
];
for (const { route, nav, auth } of bottomNavPages) {
    test(`${route} content (incl. footer) clears the fixed bottom nav with a real gap`, async ({ page }, testInfo) => {
        test.skip(testInfo.project.use.viewport.width >= 640, 'The fixed bottom nav only renders below the sm breakpoint.');

        if (auth) await loginAdmin(page);
        await gotoAndVerify(page, route);

        const overlap = await page.evaluate((navSelector) => {
            window.scrollTo({ top: document.documentElement.scrollHeight, left: 0, behavior: 'instant' });

            const navEl = document.querySelector(navSelector);
            if (!navEl) return null;

            // Walk the LAST visible, in-flow child at each level starting from <body> — not a
            // max-over-all-descendants scan. A max-scan picks up structural wrapper boxes (e.g.
            // <main> with pb-40 reserved for the fixed nav, or a min-h-screen flex shell) whose
            // own bottom edge sits at the reserved padding, not at real rendered content, which
            // produced false failures identical across every page (same navTop/bottom numbers
            // regardless of actual content). Walking to the deepest last child instead lands on
            // the true last piece of content — the footer's copyright line on public pages, or
            // the last real element inside <main> on admin pages — and is naturally immune to
            // ancestor padding since we never read an ancestor's own box, only descend into it.
            // Skips display:none/hidden and fixed/sticky elements (the nav itself, closed
            // Alpine x-show drawers, the sticky admin header) at every level.
            let el = document.body;
            while (el) {
                const visibleChildren = Array.from(el.children).filter((c) => {
                    const style = getComputedStyle(c);
                    return style.display !== 'none' && style.visibility !== 'hidden'
                        && style.position !== 'fixed' && style.position !== 'sticky';
                });
                if (visibleChildren.length === 0) break;
                el = visibleChildren[visibleChildren.length - 1];
            }

            const navTop = navEl.getBoundingClientRect().top;
            const contentBottomViewport = el.getBoundingClientRect().bottom;

            return { navTop, contentBottomViewport, overlap: contentBottomViewport - navTop };
        }, nav);

        expect(overlap, `${route}: bottom nav not found`).not.toBeNull();
        // A real, visible gap must exist (not just "not touching") — this is the safe-area
        // clearance the owner's review specifically asked to be provable, not merely non-overlap.
        expect(overlap.overlap, `${route}: content/footer must clear the fixed bottom nav by a real gap — got ${JSON.stringify(overlap)}`).toBeLessThanOrEqual(-4);
    });
}
