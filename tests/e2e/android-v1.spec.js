import { expect, test } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import path from 'node:path';

const screenshots = path.join(process.cwd(), 'artifacts', 'android-v1', 'screenshots');
const capture = async (page, name, fullPage = true) => {
    await page.waitForTimeout(300);
    await page.evaluate(() => document.activeElement?.blur());
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: path.join(screenshots, name), fullPage });
};

const vehicle = {
    slug: 'bmw-x5', title: 'BMW X5 xDrive40i', make: 'BMW', model: 'X5', year: '2024',
    price_aed: 345000, price_toman: 7635000000, cover_image: null,
    specs: { engine_capacity_cc: '3000', fuel_type: 'بنزین', transmission: 'اتوماتیک', kilometers: '30000' },
};
const secondVehicle = { ...vehicle, slug: 'toyota-camry', title: 'Toyota Camry Hybrid', make: 'Toyota', model: 'Camry', price_aed: 128000 };

async function mockMobileApi(page, options = {}) {
    await page.route('https://navracar.com/**', async (route) => {
        const url = new URL(route.request().url());
        const path = url.pathname;
        const json = (body, status = 200) => route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(body) });
        if (path.endsWith('/bootstrap')) return json({
            environment: 'staging', featured_vehicles: [vehicle], categories: [{ id: 'c2000', label: 'بنزینی ۱۵۰۰ تا ۲۰۰۰ سی‌سی' }],
            rates: { aed_to_toman: 24870, customs_aed_to_toman: 18000, updated_at: '2026-08-18T08:00:00Z' },
            contact: { whatsapp_uae: '+971505158484', whatsapp_iran: '+989120512149', phone: '+982188870878' },
        });
        if (path.endsWith('/vehicles/bmw-x5')) return json({ ...vehicle, gallery: [], description: 'خودروی وارداتی آماده ثبت درخواست', location: 'Dubai', pricing: {
            category: { id: 'c2000', label: 'بنزینی ۱۵۰۰ تا ۲۰۰۰ سی‌سی' },
            public_summary: [
                { key: 'car_price', label: 'قیمت خودرو', value_toman: 7635000000 },
                { key: 'clearance', label: 'جمع هزینه‌های ترخیص', value_toman: 2900000000 },
                { key: 'plate', label: 'هزینه‌های پلاک', value_toman: 700000000 },
            ], grand_total_toman: 11235000000,
        } });
        if (path.endsWith('/vehicles')) return json({ data: [vehicle, secondVehicle], meta: { current_page: 1, last_page: 1, total: 2 }, facets: { makes: ['BMW', 'Toyota'], fuels: ['بنزین', 'هیبرید'] } });
        if (path.endsWith('/vehicle-pricing/calculate')) return json({ publicSummary: {
            car_price_toman: 7635000000, clearance_total_toman: 2900000000, plate_total_toman: 700000000, grand_total_toman: 11235000000,
        }, category: { id: 'c2000', label: 'بنزینی ۱۵۰۰ تا ۲۰۰۰ سی‌سی' } });
        if (path.endsWith('/requests')) return json({ data: [{ id: 128, reference: 'NR-00000128', car: 'BMW X5', status: 'در حال انجام', stage: 'پیگیری و اطلاعات', total_toman: 11235000000, created_at: '2026-08-18T08:00:00Z' }], meta: { total: 1 } });
        if (path.endsWith('/favorites')) return json({ data: [vehicle] });
        if (path.endsWith('/account')) return json({ customer: { id: 1, name: 'مریم احمدی', phone: '+989121234567', email: 'maryam@example.com' } });
        if (path.endsWith('/quote-requests')) return json({ success: true, id: 129, message: 'درخواست شما با موفقیت ثبت شد.' });
        if (path.endsWith('/auth/login') || path.endsWith('/auth/register')) return json({ token: '1|abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ', customer: { id: 1, name: 'مریم احمدی', phone: '+989121234567', email: 'maryam@example.com' } });
        if (path.endsWith('/auth/logout') && options.logoutStatus) return json({ message: 'سرویس خروج موقتاً در دسترس نیست.' }, options.logoutStatus);
        return json({}, 204);
    });
}

test('renders complete Persian RTL Android V1 screen inventory', async ({ page }) => {
    mkdirSync(screenshots, { recursive: true });
    await mockMobileApi(page);
    await page.goto('http://127.0.0.1:4173/');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.getByText('آمار ناشناس برای بهتر شدن اپ')).toBeVisible();
    await capture(page, '00-privacy-consent.png', false);
    await page.getByRole('button', { name: 'فعلاً نه' }).click();
    await expect(page.getByText('آمار ناشناس برای بهتر شدن اپ')).toBeHidden();
    await expect(page.getByRole('navigation', { name: 'ناوبری اصلی' }).getByRole('link')).toHaveCount(4);
    await expect(page.getByRole('heading', { name: /خودروی بعدی/ })).toBeVisible();
    await capture(page, '01-home-rtl.png');

    await page.getByRole('link', { name: 'خودروها' }).click();
    await expect(page.getByRole('heading', { name: 'خودروها' })).toBeVisible();
    await expect(page.getByRole('searchbox', { name: 'جستجوی خودرو' })).toBeVisible();
    await expect(page.getByLabel('مدل')).toBeVisible();
    await expect(page.getByLabel('حداقل حجم موتور')).toBeVisible();
    await expect(page.getByTestId('vehicle-card')).toHaveCount(2);
    await capture(page, '02-vehicle-listing.png');

    await page.getByRole('searchbox', { name: 'جستجوی خودرو' }).fill('BMW');
    await page.getByLabel('حداقل سال').fill('2023');
    await page.getByRole('button', { name: 'اعمال فیلتر' }).click();
    await expect(page).toHaveURL(/q=BMW/);
    await capture(page, '03-filter-search.png');

    await page.getByTestId('vehicle-card').first().click();
    await expect(page.getByRole('heading', { name: 'BMW X5 xDrive40i' })).toBeVisible();
    await expect(page.getByText('جمع هزینه‌های ترخیص')).toBeVisible();
    await expect(page.getByText('کارمزد ترخیص‌کار و کارگزار')).toHaveCount(0);
    await capture(page, '04-vehicle-detail-top.png', false);
    await page.getByRole('heading', { name: 'برآورد هزینه واردات' }).scrollIntoViewIfNeeded();
    await capture(page, '05-pricing-section.png', false);

    await page.getByRole('button', { name: 'محاسبه هزینه' }).click();
    await expect(page.getByRole('heading', { name: 'محاسبه هزینه' })).toBeVisible();
    await page.getByLabel('قیمت واقعی خودرو به درهم').fill('123456');
    await page.getByRole('button', { name: 'دریافت محاسبه' }).click();
    await expect(page.getByText('جمع کل برآوردشده')).toBeVisible();
    await capture(page, '06-pricing-calculator.png');

    await page.getByRole('button', { name: 'ثبت درخواست' }).click();
    await expect(page.getByRole('heading', { name: 'ثبت درخواست' })).toBeVisible();
    await expect(page.getByLabel('قیمت خودرو به درهم')).toHaveValue('123456');
    await capture(page, '07-quote-request.png', false);

    await page.evaluate(() => localStorage.setItem('navracar.mobile.token', '1|abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ'));
    await page.getByRole('link', { name: 'درخواست‌ها' }).click();
    await expect(page.getByText('NR-00000128')).toBeVisible();
    await capture(page, '08-requests.png');

    await page.getByRole('link', { name: 'حساب' }).click();
    await expect(page.getByText('مریم احمدی')).toBeVisible();
    await expect(page.getByRole('link', { name: 'علاقه‌مندی‌ها' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'ذخیره تغییرات' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'حریم خصوصی و اعلان‌ها' })).toBeVisible();
    await capture(page, '09-account.png');

    await page.getByRole('link', { name: 'علاقه‌مندی‌ها' }).click();
    await expect(page.getByRole('heading', { name: 'علاقه‌مندی‌ها' })).toBeVisible();
    await capture(page, '10-favorites.png');
});

test('guest favorites resolve the selected listing and failed logout preserves the token', async ({ page }) => {
    await mockMobileApi(page, { logoutStatus: 503 });
    await page.goto('http://127.0.0.1:4173/#/vehicles');
    await page.getByRole('button', { name: 'فعلاً نه' }).click();
    await expect(page.getByTestId('vehicle-card')).toHaveCount(2);
    await page.locator('[data-favorite="toyota-camry"]').click();
    await expect.poll(() => page.evaluate(() => JSON.parse(localStorage.getItem('navracar.local-favorites') || '[]')[0]?.slug)).toBe('toyota-camry');

    await page.evaluate(() => localStorage.setItem('navracar.mobile.token', '1|abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ'));
    await page.goto('http://127.0.0.1:4173/#/account');
    await expect(page.getByText('مریم احمدی')).toBeVisible();
    await page.getByRole('button', { name: 'خروج از حساب' }).click();
    await expect(page.getByText(/خروج انجام نشد/)).toBeVisible();
    await expect.poll(() => page.evaluate(() => localStorage.getItem('navracar.mobile.token'))).toContain('|');
});

test('admin mobile insights renders live, search, device, location, contact, and Push controls', async ({ page }) => {
    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.locator('input[name="username"]').fill('admin');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('button[type="submit"]').click();
    await page.goto('http://127.0.0.1:8000/admin/mobile-insights');

    await expect(page.getByRole('heading', { name: 'آمار اپلیکیشن' })).toBeVisible();
    await expect(page.getByText('BMW X5')).toBeVisible();
    await expect(page.getByText('Samsung SM-S928B')).toBeVisible();
    await expect(page.getByText(/Dubai/)).toBeVisible();
    await expect(page.getByText('WhatsApp', { exact: true })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'ارسال Push Notification' })).toBeVisible();
    await capture(page, '11-admin-mobile-insights.png');
});
