import { expect, test } from '@playwright/test';

test('public home, calculator, and protected admin boundary are reachable', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/');
    await expect(page.locator('header')).toBeVisible();
    await expect(page.locator('h1')).toBeVisible();

    await page.goto('/calculator');
    await expect(page.locator('h1')).toBeVisible();

    await page.goto('/admin');
    await expect(page).toHaveURL(/\/admin\/login$/);
    expect(errors).toEqual([]);
});

test('standalone calculator renders the authoritative backend total', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== '375x812', 'One viewport covers the shared pricing API contract.');

    await page.goto('/calculator');
    const result = await page.evaluate(async () => {
        document.getElementById('realPriceAED').value = '100,000';
        document.getElementById('customsPriceAED').value = '80,000';
        selectCategoryById('c2000');
        return await calc();
    });

    expect(result.category.id).toBe('c2000');
    expect(result.settingsSnapshot.categories).toHaveProperty('phev');
    expect(result.settingsSnapshot.categories).toHaveProperty('hybrid');
    await expect(page.locator('#s_total')).toHaveText(Math.round(result.finalTotalToman).toLocaleString('en-US'));
});

test('listing calculator renders the same authoritative backend result', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== '1280x800', 'One representative Dubizzle listing covers the shared endpoint.');

    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const [pricingResponse] = await Promise.all([
        page.waitForResponse(response => response.url().includes('/vehicle-pricing/calculate') && response.ok()),
        page.goto('/car-prices/e2e-bmw-x4'),
    ]);
    const pricing = await pricingResponse.json();

    expect(pricing.input.realPriceAed).toBe(100000);
    expect(pricing.input.customsPriceAed).toBe(100000);
    expect(pricing.category.id).toBe('c2000');
    await expect(page.locator('[x-text*="results.totalWithProfit"]')).toHaveText(
        Math.round(pricing.finalTotalToman).toLocaleString('en-US') + ' تومان',
    );
    expect(errors).toEqual([]);
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

    if (page.viewportSize().width < 1024) {
        await page.getByRole('button', { name: 'باز کردن منوی مدیریت' }).click();
    }
    await page.locator('form[action$="/admin/logout"] button').click();
    await expect(page).toHaveURL(/\/admin\/login$/);
});

test('admin issues an automatic server-priced Proforma and downloads its PDF', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== '1280x800', 'One desktop staff flow covers the shared Proforma contract.');

    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/admin/login');
    await page.locator('input[name="username"]').fill('admin');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('button[type="submit"]').click();

    await page.goto('/admin/invoices/create');
    await expect(page.locator('input[name="real_price_aed"]')).toBeVisible();
    await page.locator('input[name="customer_name"]').fill('Pricing E2E Customer');
    await page.locator('input[name="customer_phone"]').fill('09124444444');
    await page.locator('input[name="car_label"]').fill('BMW X4');
    await page.locator('input[name="real_price_aed"]').fill('100000');
    await page.locator('input[name="customs_price_aed"]').fill('80000');
    await page.locator('select[name="category"]').selectOption('c2000');

    // Target the invoice form only (page also has logout form in the admin shell)
    const pricingResponsePromise = page.waitForResponse(
        (response) => response.url().includes('/vehicle-pricing/calculate')
    );
    await page.locator('form[x-data="invoicePricingForm"]').evaluate(async (form) => {
        const data = window.Alpine.$data(form);
        data.mode = 'automatic';
        data.realPriceAed = 100000;
        data.customsPriceAed = 80000;
        data.category = 'c2000';
        data.customsPriceTouched = true;
        await data.calculate();
    });
    const pricingResponse = await pricingResponsePromise;
    if (!pricingResponse.ok()) {
        throw new Error(`vehicle-pricing/calculate failed: HTTP ${pricingResponse.status()}`);
    }
    const pricing = await pricingResponse.json();
    await expect(page.getByText('گواهی اسقاط خودرو فرسوده')).toBeVisible();
    await expect(page.getByText(Math.round(pricing.finalTotalToman).toLocaleString('en-US') + ' تومان')).toBeVisible();

    await page.locator('input[name="discount_amount"]').fill('1000');
    await page.getByRole('button', { name: 'ذخیره و صدور پیش‌فاکتور' }).click();
    await expect(page).toHaveURL(/\/admin\/invoices\/\d+$/);
    await expect(page.getByText(Math.round(pricing.finalTotalToman).toLocaleString('en-US') + ' تومان')).toBeVisible();
    await expect(page.getByText('− 1,000 تومان')).toBeVisible();

    const downloadPromise = page.waitForEvent('download');
    await page.getByRole('link', { name: 'دانلود فایل PDF' }).click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/^NVK-\d{4}-\d+\.pdf$/);
    expect(errors).toEqual([]);
});
