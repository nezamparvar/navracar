import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

for (const route of ['/', '/calculator', '/admin/login']) {
    test(`${route} has no serious or critical axe violations`, async ({ page }) => {
        await page.goto(route);
        const results = await new AxeBuilder({ page }).analyze();
        const blocking = results.violations.filter(({ impact }) => impact === 'serious' || impact === 'critical');
        expect(blocking, JSON.stringify(blocking, null, 2)).toEqual([]);
    });
}

test('public document preserves RTL semantics and a single primary heading', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.locator('h1')).toHaveCount(1);
});

test('login labels are associated and keyboard focus remains visible', async ({ page }) => {
    await page.goto('/admin/login');
    await expect(page.locator('label[for="username"]')).toBeVisible();
    await expect(page.locator('label[for="password"]')).toBeVisible();

    await page.keyboard.press('Tab');
    const focusIsVisible = await page.evaluate(() => {
        const element = document.activeElement;
        if (!element || element === document.body) return false;
        const style = getComputedStyle(element);
        return style.outlineStyle !== 'none' || style.boxShadow !== 'none';
    });
    expect(focusIsVisible).toBe(true);
});
