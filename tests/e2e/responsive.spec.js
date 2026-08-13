import { expect, test } from '@playwright/test';

for (const route of ['/', '/calculator', '/lead-form', '/admin/login']) {
    test(`${route} has no horizontal page overflow`, async ({ page }) => {
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
