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
    test.skip(testInfo.project.use.viewport.width !== 375, 'One viewport covers the shared pricing API contract.');

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

test('listing detail page renders the same authoritative backend cost categories', async ({ page }, testInfo) => {
    test.skip(testInfo.project.use.viewport.width !== 1280, 'One representative Dubizzle listing covers the shared endpoint.');

    // The vehicle-detail page renders its 3-category cost summary server-side (no client-side
    // calculator/XHR since the V2 redesign — see CarListing::publicPricingSummary()). This test
    // proves the displayed numbers still come from the single authoritative pricing service by
    // independently calling the same public endpoint with the listing's real inputs and
    // asserting the page shows matching formatted totals.
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));

    await page.goto('/car-prices/e2e-bmw-x4');
    const pricing = await page.evaluate(async () => {
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const res = await fetch('/vehicle-pricing/calculate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ real_price_aed: 100000, category: 'c2000' }),
        });
        if (!res.ok) throw new Error(`vehicle-pricing/calculate failed: HTTP ${res.status}`);
        return res.json();
    });

    expect(pricing.input.realPriceAed).toBe(100000);
    // customs_price_aed is omitted above (listing.customs_price_aed is NULL), so the service
    // applies its settings discount (30%): 100000 * (1 - 30/100) = 70000.
    expect(pricing.input.customsPriceAed).toBe(70000);
    expect(pricing.category.id).toBe('c2000');

    const carPriceText = Math.round(pricing.publicSummary.car_price_toman).toLocaleString('en-US');
    const clearanceText = Math.round(pricing.publicSummary.clearance_total_toman).toLocaleString('en-US');
    const plateText = Math.round(pricing.publicSummary.plate_total_toman).toLocaleString('en-US');
    await expect(page.getByText(carPriceText, { exact: false }).first()).toBeVisible();
    await expect(page.getByText(clearanceText, { exact: false }).first()).toBeVisible();
    await expect(page.getByText(plateText, { exact: false }).first()).toBeVisible();
    expect(errors).toEqual([]);
});

test('public lead form completes its core local-only submission flow', async ({ page }, testInfo) => {
    test.skip(testInfo.project.use.viewport.width !== 375, 'One isolated fixture submission covers the shared server flow.');

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

test('admin creates a calendar event and sees it in the list view', async ({ page }, testInfo) => {
    test.skip(testInfo.project.use.viewport.width !== 1280, 'One desktop flow covers the calendar create/list contract.');

    await page.goto('/admin/login');
    await page.locator('input[name="username"]').fill('admin');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/admin$/);

    await page.goto('/admin/calendar?view=list');
    await page.getByRole('button', { name: 'جلسه یا تماس جدید' }).click();

    const dialog = page.getByRole('dialog', { name: 'رویداد جدید' });
    await expect(dialog).toBeVisible();
    await dialog.locator('#ce-type').selectOption({ index: 0 });
    const assigneeSelect = dialog.locator('#ce-assignee');
    if (await assigneeSelect.count()) {
        await assigneeSelect.selectOption({ index: 0 });
    }
    await dialog.locator('#ce-start').fill('2026-12-01T10:00');
    await dialog.locator('#ce-end').fill('2026-12-01T10:30');
    await dialog.locator('#ce-notes').fill('Playwright calendar event');
    await dialog.getByRole('button', { name: 'ثبت رویداد' }).click();

    await expect(page).toHaveURL(/\/admin\/calendar/);
    await expect(page.getByText('رویداد با موفقیت ثبت شد.')).toBeVisible();
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
    test.skip(testInfo.project.use.viewport.width !== 1280, 'One desktop staff flow covers the shared Proforma contract.');

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
    const totalText = Math.round(pricing.finalTotalToman).toLocaleString('en-US') + ' تومان';
    await expect(page.getByText('گواهی اسقاط خودرو فرسوده')).toBeVisible();
    // Total appears in both the server summary and the displayTotal box
    await expect(page.getByText(totalText).first()).toBeVisible();

    await page.locator('input[name="discount_amount"]').fill('1000');
    await page.getByRole('button', { name: 'ذخیره و صدور پیش‌فاکتور' }).click();
    await expect(page).toHaveURL(/\/admin\/invoices\/\d+$/);
    await expect(page.getByText(totalText).first()).toBeVisible();
    await expect(page.getByText('− 1,000 تومان')).toBeVisible();

    const downloadPromise = page.waitForEvent('download');
    await page.getByRole('link', { name: 'دانلود فایل PDF' }).click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/^NVK-\d{4}-\d+\.pdf$/);
    expect(errors).toEqual([]);
});
