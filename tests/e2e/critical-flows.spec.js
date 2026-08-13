import { expect, test } from '@playwright/test';

test('public home, calculator, and protected admin boundary are reachable', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('header')).toBeVisible();
    await expect(page.locator('h1')).toBeVisible();

    await page.goto('/calculator');
    await expect(page.locator('h1')).toBeVisible();

    await page.goto('/admin');
    await expect(page).toHaveURL(/\/admin\/login$/);
});

test('public lead form completes its core local-only submission flow', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== '375x812', 'One isolated fixture submission covers the shared server flow.');

    await page.goto('/lead-form');
    await page.locator('[x-model="form.userId"]').selectOption({ index: 1 });
    await page.locator('[x-model="form.name"]').fill('Playwright Lead');
    await page.locator('[x-model="form.phone"]').fill('+971500000000');
    await page.locator('[x-model="form.budget"]').selectOption({ index: 1 });
    await page.locator('[x-model="form.carInterest"]').fill('Toyota Land Cruiser');
    await page.locator('[x-model="form.source"]').selectOption({ index: 1 });
    await page.locator('[x-model="form.status"]').selectOption({ index: 1 });
    await page.locator('[x-model="form.city"]').first().selectOption({ index: 1 });
    await page.evaluate(() => { window.__pageLoadedAt = 0; });
    await page.locator('button[type="submit"]').click();

    await expect(page.locator('[x-show="success"] h2')).toBeVisible();
});

test('admin login rejects invalid credentials without disclosing an account', async ({ page }) => {
    await page.goto('/admin/login');
    await page.locator('input[name="username"]').fill('not-a-real-account');
    await page.locator('input[name="password"]').fill('not-a-real-password');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/admin\/login$/);
    await expect(page.locator('.bg-rose-50')).toBeVisible();
});

test('admin can authenticate, use a core list, and log out', async ({ page }) => {
    await page.goto('/admin/login');
    await page.locator('input[name="username"]').fill('admin');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.locator('main')).toBeVisible();

    await page.goto('/admin/requests');
    await expect(page).toHaveURL(/\/admin\/requests$/);
    await expect(page.locator('main')).toBeVisible();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(1);

    await page.locator('form[action$="/admin/logout"] button').click();
    await expect(page).toHaveURL(/\/admin\/login$/);
});
