import { expect, test } from '@playwright/test';

async function loginAdmin(page) {
    await page.goto('/admin/login');
    await page.locator('input[name="username"]').fill('admin');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/admin$/);
}

const publicRoutes = ['/', '/calculator', '/lead-form', '/admin/login', '/car-prices', '/car-prices/e2e-bmw-x4', '/track'];
const adminRoutes = ['/admin', '/admin/calendar?view=week', '/admin/calendar?view=list', '/admin/kanban', '/admin/content-dashboard', '/admin/requests'];

// Regression coverage for the round-4 finding: the mobile off-canvas admin sidebar (and, before
// that, ungapped flex/grid items inside stat-card/x-card) contributed to
// document.documentElement.scrollWidth even while visually off-screen or internally scrolled,
// producing severe horizontal overflow that wasn't caught by the smaller route list this file
// used to check. Every route below is a page that was actually broken at some point this round.
for (const route of publicRoutes) {
    test(`${route} has no horizontal page overflow`, async ({ page }) => {
        await page.goto(route);
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
        expect(overflow).toBeLessThanOrEqual(1);
    });
}

for (const route of adminRoutes) {
    test(`${route} has no horizontal page overflow (admin)`, async ({ page }) => {
        await loginAdmin(page);
        await page.goto(route);
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
        expect(overflow).toBeLessThanOrEqual(1);
    });
}

test('public mobile navigation opens without obscuring its links', async ({ page }, testInfo) => {
    test.skip(testInfo.project.use.viewport.width >= 640, 'The full navigation is already visible.');

    await page.goto('/');
    const toggle = page.getByRole('button', { name: 'باز کردن منو' });
    await toggle.click();
    await expect(page.locator('header [x-show="mobileMenuOpen"]').getByRole('link').first()).toBeVisible();
});

// Regression coverage for the round-4 finding that screenshots of the calendar/request-tracking
// pages appeared to show the fixed bottom nav covering real content. Scrolls to the true document
// end with an INSTANT scroll (html has scroll-behavior:smooth — an animated scrollTo measured too
// early reports a false, mid-animation position) and asserts the last real content element still
// clears the fixed nav's top edge.
const bottomNavPages = [
    { route: '/track/1?phone=09120000000', nav: 'nav[aria-label="ناوبری پایین صفحه"]', auth: false },
    { route: '/admin/calendar?view=list', nav: 'nav[aria-label="ناوبری پایین پنل مدیریت"]', auth: true },
    { route: '/admin/calendar?view=week', nav: 'nav[aria-label="ناوبری پایین پنل مدیریت"]', auth: true },
];
for (const { route, nav, auth } of bottomNavPages) {
    test(`${route} content clears the fixed bottom nav at full scroll`, async ({ page }, testInfo) => {
        test.skip(testInfo.project.use.viewport.width >= 640, 'The fixed bottom nav only renders below the sm breakpoint.');

        if (auth) await loginAdmin(page);
        await page.goto(route);

        const overlap = await page.evaluate((navSelector) => {
            window.scrollTo({ top: document.documentElement.scrollHeight, left: 0, behavior: 'instant' });
            const navEl = document.querySelector(navSelector);
            if (!navEl) return null;
            const navTop = navEl.getBoundingClientRect().top;
            // Deepest visible last-child chain inside <main>, skipping display:none nodes
            // (e.g. closed modals) so it reflects real, visible content only.
            let el = document.querySelector('main');
            while (el) {
                const visibleChildren = Array.from(el.children).filter(c => getComputedStyle(c).display !== 'none');
                if (visibleChildren.length === 0) break;
                el = visibleChildren[visibleChildren.length - 1];
            }
            const contentBottom = el.getBoundingClientRect().bottom;
            return { navTop, contentBottom, overlap: contentBottom - navTop };
        }, nav);

        expect(overlap).not.toBeNull();
        expect(overlap.overlap).toBeLessThanOrEqual(2);
    });
}
