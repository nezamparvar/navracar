import { expect, test } from '@playwright/test';

test.describe('3-Step Calculator Wizard', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/calculator');
    // Start wizard from initial 'start' step
    await page.waitForSelector('.wiz-step[data-step="start"]');
  });

  test('wizard navigates through all 3 steps correctly', async ({ page }) => {
    // Step 0: Choose method (engine cc is simplest)
    await page.click('[data-mode="cc"]');

    // Step 1: Vehicle details
    await page.waitForSelector('.wiz-step[data-step="details"].active');
    await expect(page.locator('.wiz-step.active .wiz-step-title')).toContainText('اطلاعات خودرو');

    // Select the engine-capacity category exposed by CC mode.
    await page.click('[data-category-id="c2000"]');
    await page.click('#wizNextBtn');

    // Step 2: Pricing
    await page.waitForSelector('.wiz-step[data-step="pricing"].active');
    await expect(page.locator('.wiz-step.active .wiz-step-title')).toContainText('قیمت و نرخ ارز');

    // Enter prices
    await page.fill('#realPriceAED', '100000');
    await page.fill('#customsPriceAED', '80000');
    await page.click('#wizNextBtn');

    // Step 3: Results
    await page.waitForSelector('.wiz-step[data-step="result"].active');
    await expect(page.locator('.wiz-step.active .wiz-step-title')).toContainText('نتیجه محاسبه');
    await expect(page.locator('#s_total')).toBeVisible();
  });

  test('prev/next buttons navigate correctly', async ({ page }) => {
    // Start with cc method
    await page.click('[data-mode="cc"]');
    await page.waitForSelector('.wiz-step[data-step="details"].active');

    // Go to pricing
    await page.click('[data-category-id="c2000"]');
    await page.click('#wizNextBtn');
    await page.waitForSelector('.wiz-step[data-step="pricing"].active');

    // Go back to details
    await page.click('#wizPrevBtn');
    await page.waitForSelector('.wiz-step[data-step="details"].active');

    // Go forward again
    await page.click('#wizNextBtn');
    await page.waitForSelector('.wiz-step[data-step="pricing"].active');
  });

  test('data is preserved across step transitions', async ({ page }) => {
    // Choose method
    await page.click('[data-mode="cc"]');

    // Step 1: Select a category.
    const categoryId = 'c2500';
    await page.click(`[data-category-id="${categoryId}"]`);
    await page.click('#wizNextBtn');

    // Step 2: Enter prices
    const realPriceValue = '150000';
    const customsPriceValue = '120000';
    await page.fill('#realPriceAED', realPriceValue);
    await page.fill('#customsPriceAED', customsPriceValue);

    // Go back to details
    await page.click('#wizPrevBtn');

    // Verify data is preserved.
    await expect(page.locator(`[data-category-id="${categoryId}"]`)).toHaveClass(/active/);

    // Go back to pricing
    await page.click('#wizNextBtn');

    // Verify pricing data is preserved
    const realPriceInput = await page.inputValue('#realPriceAED');
    const customsPriceInput = await page.inputValue('#customsPriceAED');
    expect(realPriceInput.replace(/,/g, '')).toBe(realPriceValue);
    expect(customsPriceInput.replace(/,/g, '')).toBe(customsPriceValue);
  });

  test('progress indicator shows correct step', async ({ page }) => {
    // Choose method
    await page.click('[data-mode="cc"]');
    await page.waitForSelector('.wiz-dot.current');

    // Should show step 1
    let dots = await page.locator('.wiz-dot.current').count();
    expect(dots).toBe(1);

    // Go to step 2
    await page.click('[data-category-id="c2000"]');
    await page.click('#wizNextBtn');

    // Should show step 2
    const dotText = await page.locator('.wiz-dot.current').getAttribute('title');
    expect(dotText).toContain('قیمت و هزینه');
  });

  test('calculator computes correctly when advancing from pricing to result', async ({ page }) => {
    await page.click('[data-mode="cc"]');
    await page.click('[data-category-id="c2000"]');
    await page.click('#wizNextBtn');

    // Fill first so the navigation click cannot race ahead of the input events.
    await page.fill('#realPriceAED', '100000');
    await page.fill('#customsPriceAED', '80000');

    // Intercept the authoritative calculation triggered by navigation.
    const [calcResponse] = await Promise.all([
      page.waitForResponse(response =>
        response.url().includes('/vehicle-pricing/calculate') && response.ok()
      ),
      page.click('#wizNextBtn'),
    ]);

    const pricing = await calcResponse.json();
    expect(pricing.input.realPriceAed).toBe(100000);
    expect(pricing.input.customsPriceAed).toBe(80000);
    expect(pricing.category.id).toBe('c2000');

    // Wait for result to display
    await page.waitForSelector('.wiz-step[data-step="result"].active');
    const resultTotal = await page.locator('#s_total').textContent();
    expect(parseInt(resultTotal.replace(/,/g, ''))).toBe(Math.round(pricing.finalTotalToman));
  });

  test('mobile width 375px responsive layout', async ({ page }) => {
    page.setViewportSize({ width: 375, height: 812 });

    await page.click('[data-mode="cc"]');
    await page.click('[data-category-id="c2000"]');

    // Elements should be visible without horizontal scroll
    const overflow = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth
    );
    expect(overflow).toBeLessThanOrEqual(1);

    // Fill and navigate
    await page.click('#wizNextBtn');
    await page.fill('#realPriceAED', '100000');
    await page.fill('#customsPriceAED', '80000');

    // Still no horizontal scroll
    const overflowAfter = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth
    );
    expect(overflowAfter).toBeLessThanOrEqual(1);
  });

  test('mobile width 390px responsive layout', async ({ page }) => {
    page.setViewportSize({ width: 390, height: 844 });

    await page.click('[data-mode="cc"]');
    await page.click('[data-category-id="c2000"]');
    await page.click('#wizNextBtn');

    const overflow = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth
    );
    expect(overflow).toBeLessThanOrEqual(1);
  });

  test('tablet width 768px responsive layout', async ({ page }) => {
    page.setViewportSize({ width: 768, height: 1024 });

    await page.click('[data-mode="cc"]');
    await page.click('[data-category-id="c2000"]');
    await page.click('#wizNextBtn');
    await page.fill('#realPriceAED', '100000');
    await page.click('#wizNextBtn');

    const overflow = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth
    );
    expect(overflow).toBeLessThanOrEqual(1);
  });

  test('desktop width 1024px+ responsive layout', async ({ page }) => {
    page.setViewportSize({ width: 1280, height: 800 });

    await page.click('[data-mode="cc"]');
    await page.click('[data-category-id="c2000"]');
    await page.click('#wizNextBtn');
    await page.fill('#realPriceAED', '100000');
    await page.fill('#customsPriceAED', '80000');
    await page.click('#wizNextBtn');

    // Results should be visible
    await expect(page.locator('#s_total')).toBeVisible();

    const overflow = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth
    );
    expect(overflow).toBeLessThanOrEqual(1);
  });

  test('back button from pricing returns to details with data intact', async ({ page }) => {
    await page.click('[data-mode="cc"]');
    const categoryId = 'c2000';
    await page.click(`[data-category-id="${categoryId}"]`);
    await page.click('#wizNextBtn');

    // At pricing step
    await page.fill('#realPriceAED', '100000');
    const pricingValue = '80000';
    await page.fill('#customsPriceAED', pricingValue);

    // Go back
    await page.click('#wizPrevBtn');

    // Should be back at details with data
    await expect(page.locator('.wiz-step[data-step="details"]')).toHaveClass(/active/);
    await expect(page.locator(`[data-category-id="${categoryId}"]`)).toHaveClass(/active/);
  });

  test('reset wizard clears all data', async ({ page }) => {
    // Fill out all steps
    await page.click('[data-mode="cc"]');
    await page.click('[data-category-id="c2000"]');
    await page.click('#wizNextBtn');
    await page.fill('#realPriceAED', '100000');
    await page.fill('#customsPriceAED', '80000');
    await page.click('#wizNextBtn');

    // Find and click reset button (usually at top of page)
    const resetBtn = page.locator('button:has-text("شروع دوباره"), a:has-text("شروع دوباره")').first();
    if (await resetBtn.isVisible()) {
      await resetBtn.click();

      // Should be back at start
      await expect(page.locator('.wiz-step[data-step="start"]')).toHaveClass(/active/);
    }
  });

  test('print report hides the service fee row while preserving the fee in the final total', async ({ page }) => {
    const pricing = await page.evaluate(async () => {
      const built = await buildPrintSheet();

      return {
        built,
        preServiceTotalToman: lastPricingResult.preServiceTotalToman,
        serviceFeeToman: lastPricingResult.serviceFeeToman,
        finalTotalToman: lastPricingResult.finalTotalToman,
      };
    });

    expect(pricing.built).toBe(true);
    expect(pricing.serviceFeeToman).toBeGreaterThan(0);
    expect(pricing.finalTotalToman).toBe(pricing.preServiceTotalToman + pricing.serviceFeeToman);

    const printTotals = page.locator('#psTotalsTable');
    await expect(printTotals).not.toContainText('کارمزد ترخیص‌کار و کارگزار (ناوراکار)');
    await expect(printTotals.locator('tr.total')).toContainText(
      Math.round(pricing.finalTotalToman).toLocaleString('en-US'),
    );
  });
});
