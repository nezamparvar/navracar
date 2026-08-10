@props(['listing', 'freeRate', 'customsRate'])

@php
    $categories = \App\Models\CarListing::CATEGORIES;
    $config = [
        'priceAed' => (float) $listing->price_aed,
        'categoryId' => $listing->category_id,
        'categories' => $categories,
        'freeRate' => $freeRate,
        'customsRate' => $customsRate,
    ];
@endphp

<div x-data="carCalculatorApp(@js($config))" class="space-y-5">

    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
        نرخ درهم امروز: <span class="num-font" x-text="fmt(freeRate)"></span> تومان
        <span class="font-normal text-amber-800/70 dark:text-amber-300/70">(به‌روزرسانی زنده از پنل مدیریت ناوراکار)</span>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">قیمت واقعی خودرو (درهم)</label>
            <input type="number" x-model.number="realPriceAED" class="w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-ink-900">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">قیمت گمرکی خودرو (درهم)</label>
            <input type="number" x-model.number="customsPriceAED" class="w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-ink-900">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">دسته‌بندی خودرو</label>
            <select x-model="categoryId" class="w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-ink-900">
                <template x-for="(cat, key) in categories" :key="key">
                    <option :value="key" x-text="cat.label" :selected="key === categoryId"></option>
                </template>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">نرخ ارز آزاد (تومان)</label>
            <input type="number" x-model.number="freeRate" class="w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-ink-900">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">نرخ ارز گمرک (تومان)</label>
            <input type="number" x-model.number="customsRate" class="w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-ink-900">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">حمل دریایی (درهم)</label>
            <input type="number" x-model.number="seaFreightAED" class="w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-ink-900">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">صدور مجوزها (درهم)</label>
            <input type="number" x-model.number="permitsAED" class="w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-ink-900">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">انبارداری و دموراژ (تومان)</label>
            <input type="number" x-model.number="storage" class="w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-ink-900">
        </div>
    </div>

    <details class="rounded-xl border border-ink-200/70 p-3.5 text-xs dark:border-white/10">
        <summary class="cursor-pointer font-bold text-ink-600 dark:text-ink-300">تنظیمات پیشرفته نرخ‌ها و عوارض</summary>
        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
            <template x-for="key in Object.keys(rates)" :key="key">
                <label class="flex items-center justify-between gap-2 rounded-lg bg-ink-50 px-2.5 py-1.5 dark:bg-white/5">
                    <span x-text="rateLabels[key]"></span>
                    <input type="number" step="0.01" x-model.number="rates[key]" class="w-16 rounded-md border border-ink-200 bg-white px-1.5 py-0.5 text-left num-font dark:border-white/10 dark:bg-ink-900">
                </label>
            </template>
        </div>
    </details>

    <div class="overflow-x-auto rounded-2xl border border-ink-200/70 dark:border-white/10">
        <table class="w-full text-xs sm:text-sm">
            <thead class="bg-ink-50 text-ink-500 dark:bg-white/5 dark:text-ink-400">
                <tr>
                    <th class="p-2.5 text-right">ردیف</th>
                    <th class="p-2.5 text-right">شرح هزینه ترخیص گمرکی</th>
                    <th class="p-2.5 text-right">نرخ</th>
                    <th class="p-2.5 text-left">مبلغ (تومان)</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, i) in results.customsRows" :key="i">
                    <tr class="border-t border-ink-100 dark:border-white/5">
                        <td class="p-2.5 text-ink-400" x-text="i + 1"></td>
                        <td class="p-2.5 font-semibold" x-text="row.label"></td>
                        <td class="p-2.5 text-ink-500 dark:text-ink-400" x-text="row.rate"></td>
                        <td class="p-2.5 text-left num-font font-bold" x-text="fmt(row.value)"></td>
                    </tr>
                </template>
            </tbody>
            <tfoot>
                <tr class="border-t border-ink-200 bg-ink-50 font-extrabold dark:border-white/10 dark:bg-white/5">
                    <td class="p-2.5" colspan="3">جمع هزینه‌های ترخیص گمرکی</td>
                    <td class="p-2.5 text-left num-font" x-text="fmt(results.sumCustomsAll)"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-ink-200/70 dark:border-white/10">
        <table class="w-full text-xs sm:text-sm">
            <thead class="bg-ink-50 text-ink-500 dark:bg-white/5 dark:text-ink-400">
                <tr>
                    <th class="p-2.5 text-right">ردیف</th>
                    <th class="p-2.5 text-right">شرح هزینه پلاک انتظامی</th>
                    <th class="p-2.5 text-right">نرخ</th>
                    <th class="p-2.5 text-left">مبلغ (تومان)</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, i) in results.plateRows" :key="i">
                    <tr class="border-t border-ink-100 dark:border-white/5">
                        <td class="p-2.5 text-ink-400" x-text="i + 1"></td>
                        <td class="p-2.5 font-semibold" x-text="row.label"></td>
                        <td class="p-2.5 text-ink-500 dark:text-ink-400" x-text="row.rate"></td>
                        <td class="p-2.5 text-left num-font font-bold" x-text="fmt(row.value)"></td>
                    </tr>
                </template>
            </tbody>
            <tfoot>
                <tr class="border-t border-ink-200 bg-ink-50 font-extrabold dark:border-white/10 dark:bg-white/5">
                    <td class="p-2.5" colspan="3">جمع هزینه‌های پلاک انتظامی</td>
                    <td class="p-2.5 text-left num-font" x-text="fmt(results.sumPlate)"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-200/70 p-4 text-center dark:border-white/10">
            <div class="text-xs font-bold text-ink-500 dark:text-ink-400">جمع کل بدون سود خدمات</div>
            <div class="mt-1 text-lg font-extrabold num-font" x-text="fmt(results.totalNoProfit) + ' تومان'"></div>
        </div>
        <div class="rounded-2xl border border-ink-200/70 p-4 text-center dark:border-white/10">
            <div class="text-xs font-bold text-ink-500 dark:text-ink-400">سود خدمات ناوراکار</div>
            <div class="mt-1 text-lg font-extrabold num-font" x-text="fmt(results.serviceProfitAmt) + ' تومان'"></div>
        </div>
        <div class="rounded-2xl border-2 border-amber-400 bg-amber-50 p-4 text-center dark:border-amber-500/40 dark:bg-amber-500/10">
            <div class="text-xs font-bold text-amber-800 dark:text-amber-300">قیمت تمام‌شده نهایی</div>
            <div class="mt-1 text-xl font-black num-font text-amber-900 dark:text-amber-200" x-text="fmt(results.totalWithProfit) + ' تومان'"></div>
        </div>
    </div>

    <p class="text-[11px] leading-6 text-ink-400 dark:text-ink-500">
        این گزارش صرفاً یک برآورد اولیه بر اساس نرخ‌های ثبت‌شده در سیستم ناوراکار است و ممکن است با تغییر مقررات گمرکی یا نرخ ارز به‌روزرسانی شود. برای قیمت قطعی با کارشناسان ناوراکار تماس بگیرید.
    </p>
</div>

@once
<script>
window.carCalculatorApp = function (config) {
    const rateLabels = {
        customsFixed: 'حقوق گمرکی ثابت', gasoline: 'عوارض بنزین‌سوز', fob: 'عوارض ۵٪ فوب',
        vat: 'مالیات ارزش افزوده', advanceTax: 'مالیات علی‌الحساب', redCrescent: 'عوارض هلال احمر',
        supervision: 'حق نظارت کارشناسان', waste: 'عوارض پسماند کالا', standard: 'هزینه استاندارد',
        scrapCert: 'گواهی اسقاط', plateReg: 'عوارض شماره‌گذاری', transferTax: 'مالیات نقل و انتقال',
        municipal: 'عوارض سالانه شهرداری', individual: 'عوارض شخص حقیقی', serviceProfit: 'سود خدمات ناوراکار',
    };

    return {
        realPriceAED: config.priceAed,
        customsPriceAED: config.priceAed,
        categoryId: config.categoryId,
        categories: config.categories,
        freeRate: config.freeRate,
        customsRate: config.customsRate,
        seaFreightAED: 1500,
        permitsAED: 60000,
        storage: 0,
        rateLabels,
        rates: {
            customsFixed: 4, gasoline: 10, fob: 5, vat: 10, advanceTax: 2, redCrescent: 1,
            supervision: 0.5, waste: 0.05, standard: 0.8, scrapCert: 1.5, plateReg: 10,
            transferTax: 3, municipal: 1, individual: 5, serviceProfit: 10,
        },

        fmt(n) {
            return Math.round(n || 0).toLocaleString('en-US');
        },

        get results() {
            const num = v => { const n = parseFloat(v); return isNaN(n) || n < 0 ? 0 : n; };
            const pct = key => num(this.rates[key]) / 100;

            const realPriceAED = num(this.realPriceAED);
            const customsPriceAED = num(this.customsPriceAED);
            const freeRate = num(this.freeRate);
            const customsRate = num(this.customsRate);
            const seaFreightAED = num(this.seaFreightAED);
            const permitsAED = num(this.permitsAED);
            const storage = num(this.storage);
            const coef = (this.categories[this.categoryId] || { coef: 1.2 }).coef;

            const CIF = customsPriceAED * customsRate;
            const realPriceToman = realPriceAED * freeRate;
            const dutyProfit = coef * CIF;
            const base9 = dutyProfit + CIF;

            const customsRows = [
                { label: 'سود بازرگانی', rate: `${(coef * 100).toFixed(0)}٪ از ارزش گمرکی (دسته خودرو)`, value: dutyProfit },
                { label: 'حقوق گمرکی ثابت', rate: `${this.rates.customsFixed}٪ از ارزش گمرکی`, value: pct('customsFixed') * CIF },
                { label: 'عوارض بنزین‌سوز', rate: `${this.rates.gasoline}٪ از ارزش گمرکی`, value: pct('gasoline') * CIF },
                { label: 'عوارض ۵٪ فوب', rate: `${this.rates.fob}٪ از ارزش فوب`, value: pct('fob') * CIF },
                { label: 'مالیات ارزش افزوده (VAT)', rate: `${this.rates.vat}٪ از (گمرکی+حقوق ورودی)`, value: pct('vat') * base9 },
                { label: 'مالیات علی‌الحساب واردات', rate: `${this.rates.advanceTax}٪ از (گمرکی+حقوق ورودی)`, value: pct('advanceTax') * base9 },
                { label: 'عوارض هلال احمر', rate: `${this.rates.redCrescent}٪ از حقوق ورودی`, value: pct('redCrescent') * dutyProfit },
                { label: 'حق نظارت کارشناسان گمرک', rate: `${this.rates.supervision}٪ از حقوق ورودی`, value: pct('supervision') * dutyProfit },
                { label: 'عوارض پسماند کالا', rate: `${this.rates.waste}٪ از ارزش گمرکی`, value: pct('waste') * CIF },
                { label: 'هزینه استاندارد', rate: `${this.rates.standard}٪ از ارزش گمرکی`, value: pct('standard') * CIF },
            ];
            const sumCustoms10 = customsRows.reduce((s, r) => s + r.value, 0);
            customsRows.push({ label: 'انبارداری، دموراژ و THC', rate: 'مبلغ دستی وارد شده', value: storage });
            const sumCustomsAll = sumCustoms10 + storage;

            const plateRows = [
                { label: 'خرید گواهی اسقاط', rate: `${this.rates.scrapCert}٪ از ارزش گمرکی`, value: pct('scrapCert') * CIF },
                { label: 'عوارض شماره‌گذاری راهور', rate: `${this.rates.plateReg}٪ از ارزش گمرکی`, value: pct('plateReg') * CIF },
                { label: 'مالیات نقل و انتقال', rate: `${this.rates.transferTax}٪ از ارزش گمرکی`, value: pct('transferTax') * CIF },
                { label: 'عوارض سالانه شهرداری', rate: `${this.rates.municipal}٪ از ارزش گمرکی`, value: pct('municipal') * CIF },
                { label: 'عوارض شخص حقیقی', rate: `${this.rates.individual}٪ از ارزش گمرکی`, value: pct('individual') * CIF },
            ];
            const sumPlate = plateRows.reduce((s, r) => s + r.value, 0);

            const seaFreight = seaFreightAED * freeRate;
            const permits = permitsAED * freeRate;

            const totalNoProfit = sumCustomsAll + sumPlate + realPriceToman + seaFreight + permits;
            const serviceProfitAmt = pct('serviceProfit') * (sumCustoms10 + sumPlate + seaFreight + permits);
            const totalWithProfit = totalNoProfit + serviceProfitAmt;

            return { customsRows, plateRows, sumCustomsAll, sumPlate, totalNoProfit, serviceProfitAmt, totalWithProfit };
        },
    };
};
</script>
@endonce
