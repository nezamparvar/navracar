@props(['listing', 'freeRate', 'customsRate'])

@php
    $categories = \App\Models\CarListing::categoriesWithLiveRates();
    $config = [
        'priceAed' => (float) $listing->price_aed,
        'categoryId' => $listing->category_id,
        'categories' => $categories,
        'freeRate' => $freeRate,
        'customsRate' => $customsRate,
        'licenseFeeAed' => (float) \App\Models\Setting::get(\App\Models\Setting::LICENSE_FEE_AED),
        'seaFreightAed' => (float) \App\Models\Setting::get(\App\Models\Setting::SEA_FREIGHT_AED),
        'storageToman' => (float) \App\Models\Setting::get(\App\Models\Setting::STORAGE_TOMAN),
        'scrapCertPriceToman' => (float) \App\Models\Setting::get(\App\Models\Setting::SCRAP_CERT_PRICE_TOMAN),
        'scrapThresholdAed' => (float) \App\Models\Setting::get(\App\Models\Setting::SCRAP_THRESHOLD_AED),
        'customsValueDiscountPercent' => (float) \App\Models\Setting::get(\App\Models\Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT),
        'usdToAedRate' => (float) \App\Models\Setting::get(\App\Models\Setting::USD_TO_AED_RATE),
        'loanMaxAmountToman' => (float) \App\Models\Setting::get(\App\Models\Setting::LOAN_MAX_AMOUNT_TOMAN),
        'loanTermYearsOptions' => collect(explode(',', \App\Models\Setting::get(\App\Models\Setting::LOAN_TERM_YEARS)))
            ->map(fn ($y) => (int) trim($y))->filter()->values()->all(),
        'loanInterestRatePercent' => (float) \App\Models\Setting::get(\App\Models\Setting::LOAN_INTEREST_RATE_PERCENT),
        'carLabel' => $listing->title_fa,
        'quoteUrl' => route('public.quote-requests.store'),
        'pricingUrl' => route('public.vehicle-pricing.calculate'),
        'csrfToken' => csrf_token(),
    ];
@endphp

<div x-data="carCalculatorApp" class="space-y-5">

    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
        نرخ درهم امروز: <span class="num-font" x-text="fmt(freeRate)"></span> تومان
        <span class="font-normal text-amber-800/70 dark:text-amber-300/70">(به‌روزرسانی زنده از پنل مدیریت ناوراکار)</span>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <div class="mb-1 flex items-center justify-between">
                <label class="text-xs font-bold text-ink-500 dark:text-ink-400">قیمت واقعی خودرو</label>
                <div class="flex overflow-hidden rounded-lg border border-ink-200 text-[10px] font-bold dark:border-white/10">
                    <button type="button" @click="priceCurrency = 'aed'" :class="priceCurrency === 'aed' ? 'bg-brand-700 text-white' : 'bg-white text-ink-500 dark:bg-ink-900'" class="px-2 py-1">درهم</button>
                    <button type="button" @click="priceCurrency = 'usd'" :class="priceCurrency === 'usd' ? 'bg-brand-700 text-white' : 'bg-white text-ink-500 dark:bg-ink-900'" class="px-2 py-1">دلار</button>
                </div>
            </div>
            <input type="number" x-model.number="realPriceDisplay" class="w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-ink-900">
        </div>
        <div>
            <div class="mb-1 flex items-center justify-between">
                <label class="text-xs font-bold text-ink-500 dark:text-ink-400">قیمت گمرکی خودرو</label>
                <div class="flex overflow-hidden rounded-lg border border-ink-200 text-[10px] font-bold dark:border-white/10">
                    <button type="button" @click="customsPriceCurrency = 'aed'" :class="customsPriceCurrency === 'aed' ? 'bg-brand-700 text-white' : 'bg-white text-ink-500 dark:bg-ink-900'" class="px-2 py-1">درهم</button>
                    <button type="button" @click="customsPriceCurrency = 'usd'" :class="customsPriceCurrency === 'usd' ? 'bg-brand-700 text-white' : 'bg-white text-ink-500 dark:bg-ink-900'" class="px-2 py-1">دلار</button>
                </div>
            </div>
            <input type="number" x-model.number="customsPriceDisplay" class="w-full rounded-xl border border-ink-200 bg-white px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-ink-900">
            <p class="mt-1 text-[11px] text-ink-400" x-text="`پیشنهاد اولیه: ${fmt(suggestedCustomsPrice())} درهم (${customsValueDiscountPercent}% کاهش)`"></p>
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
            <input type="number" x-model.number="freeRate" readonly class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">نرخ ارز گمرک (تومان)</label>
            <input type="number" x-model.number="customsRate" readonly class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">حمل دریایی (درهم)</label>
            <input type="number" x-model.number="seaFreightAED" readonly class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">صدور مجوزها (درهم)</label>
            <input type="number" x-model.number="permitsAED" readonly class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">انبارداری و دموراژ (تومان)</label>
            <input type="number" x-model.number="storage" readonly class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500 dark:text-ink-400">نرخ هر گواهی اسقاط (تومان)</label>
            <input type="number" x-model.number="scrapCertPriceToman" readonly class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
        </div>
    </div>

    <div>
        <button type="button" @click="recalc()"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-bold text-white shadow-soft hover:brightness-105">
            <x-icon name="refresh" class="w-4 h-4" /> محاسبه مجدد
        </button>
        <span x-show="recalced" x-transition.opacity.duration.600ms x-text="'محاسبه به‌روزرسانی شد.'" class="ms-2 text-xs font-bold text-emerald-600"></span>
    </div>

    <div class="rounded-xl border border-ink-200/70 bg-ink-50 px-3.5 py-3 text-xs leading-6 text-ink-600 dark:border-white/10 dark:bg-white/5 dark:text-ink-300">
        نرخ‌ها، عوارض، تعداد گواهی اسقاط و هزینه‌های پایه از تنظیمات مرکزی ناوراکار خوانده می‌شوند و در مرورگر قابل تغییر نیستند.
        <span x-show="loading" class="ms-2 font-bold text-brand-700">در حال محاسبه…</span>
        <span x-show="error" x-text="error" class="ms-2 font-bold text-red-600"></span>
    </div>

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
            <div class="text-xs font-bold text-ink-500 dark:text-ink-400">جمع کل بدون کارمزد ترخیص‌کار و کارگزار</div>
            <div class="mt-1 text-lg font-extrabold num-font" x-text="fmt(results.totalNoProfit) + ' تومان'"></div>
        </div>
        <div class="rounded-2xl border border-ink-200/70 p-4 text-center dark:border-white/10">
            <div class="text-xs font-bold text-ink-500 dark:text-ink-400">کارمزد ترخیص‌کار و کارگزار (ناوراکار)</div>
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

    <div class="rounded-2xl border border-ink-200/70 p-4 dark:border-white/10">
        <h3 class="mb-3 text-sm font-extrabold text-ink-900 dark:text-white">جدول اقساط وام خرید خارجی خودرو</h3>
        <p class="mb-3 text-xs leading-6 text-ink-500 dark:text-ink-400">
            این تسهیلات صرفاً بابت بخشی از <strong>هزینه‌های ترخیص گمرکی</strong> این خودرو محاسبه می‌شود. سقف وام برابر است با کمترین مقدار از: حداکثر وام مصوب (<span class="num-font" x-text="fmt(loanMaxAmountToman)"></span> تومان) و حداکثر ۵۰٪ قیمت تمام‌شدهٔ نهایی همین خودرو.
        </p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-xl bg-ink-50 p-3 text-center dark:bg-white/5">
                <div class="text-[11px] font-bold text-ink-500 dark:text-ink-400">مبلغ وام قابل دریافت</div>
                <div class="mt-1 text-sm font-extrabold num-font" x-text="fmt(results.loan.amount) + ' تومان'"></div>
            </div>
            <div class="rounded-xl bg-ink-50 p-3 text-center dark:bg-white/5">
                <div class="text-[11px] font-bold text-ink-500 dark:text-ink-400">پیش‌پرداخت لازم (نقدی)</div>
                <div class="mt-1 text-sm font-extrabold num-font" x-text="fmt(results.loan.downPayment) + ' تومان'"></div>
            </div>
            <div class="rounded-xl bg-ink-50 p-3 text-center dark:bg-white/5">
                <div class="text-[11px] font-bold text-ink-500 dark:text-ink-400">نرخ بهره سالانه</div>
                <div class="mt-1 text-sm font-extrabold num-font" x-text="loanInterestRatePercent + '٪'"></div>
            </div>
        </div>
        <div class="mt-3 overflow-x-auto rounded-xl border border-ink-200/70 dark:border-white/10">
            <table class="w-full text-xs sm:text-sm">
                <thead class="bg-ink-50 text-ink-500 dark:bg-white/5 dark:text-ink-400">
                    <tr>
                        <th class="p-2.5 text-right">شرایط پرداخت</th>
                        <th class="p-2.5 text-right">تعداد اقساط</th>
                        <th class="p-2.5 text-right">مبلغ هر قسط ماهانه</th>
                        <th class="p-2.5 text-left">جمع کل بازپرداخت (اصل + سود)</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="plan in results.loan.plans" :key="plan.years">
                        <tr class="border-t border-ink-100 dark:border-white/5">
                            <td class="p-2.5 font-semibold" x-text="'وام ' + plan.years + ' ساله'"></td>
                            <td class="p-2.5 num-font" x-text="plan.months + ' ماه'"></td>
                            <td class="p-2.5 num-font font-bold" x-text="fmt(plan.monthlyInstallment) + ' تومان'"></td>
                            <td class="p-2.5 text-left num-font" x-text="fmt(plan.totalRepayment) + ' تومان'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-[11px] leading-6 text-ink-400 dark:text-ink-500" x-show="results.loan.amount > 0">
            برای استفاده از تسهیلات اقساطی باید «فرم درخواست خرید خودروی خارجی با وام» تکمیل شود؛ کارشناسان ناوراکار پس از بررسی، شرایط نهایی وام را با شما هماهنگ می‌کنند.
        </p>
    </div>

    <div class="rounded-2xl border border-ink-200/70 p-4 dark:border-white/10">
        <h3 class="mb-3 text-sm font-extrabold text-ink-900 dark:text-white">شرایط و مراحل پرداخت</h3>
        <div class="overflow-x-auto rounded-xl border border-ink-200/70 dark:border-white/10">
            <table class="w-full text-xs sm:text-sm">
                <thead class="bg-ink-50 text-ink-500 dark:bg-white/5 dark:text-ink-400">
                    <tr>
                        <th class="p-2.5 text-right">مرحله</th>
                        <th class="p-2.5 text-right">شرح</th>
                        <th class="p-2.5 text-right">زمان‌بندی</th>
                        <th class="p-2.5 text-left">مبلغ (تومان)</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="step in results.paymentSteps" :key="step.no">
                        <tr class="border-t border-ink-100 dark:border-white/5">
                            <td class="p-2.5 text-ink-400" x-text="step.no"></td>
                            <td class="p-2.5 font-semibold" x-text="step.label"></td>
                            <td class="p-2.5 text-ink-500 dark:text-ink-400" x-text="step.duration"></td>
                            <td class="p-2.5 text-left num-font font-bold" x-text="fmt(step.value)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-ink-200/70 p-4 dark:border-white/10">
        <h3 class="mb-1 text-sm font-extrabold text-ink-900 dark:text-white">چارت زمان‌بندی پرداخت‌ها و انجام کار</h3>
        <p class="mb-5 text-xs leading-6 text-ink-500 dark:text-ink-400">
            طول هر بخش متناسب با مدت‌زمان تقریبی همان مرحله است — زمان‌بندی واقعی بسته به شرایط ممکن است کمی متفاوت باشد.
        </p>
        <div class="overflow-x-auto">
            <div class="flex min-w-[640px] items-end gap-0.5 pb-1">
                <template x-for="(step, i) in results.timeline" :key="step.no">
                    <div class="flex flex-col items-stretch" :style="'flex: ' + step.weight + ' 0 0'">
                        <div class="mb-1.5 truncate text-center text-[10px] font-black text-ink-700 num-font dark:text-ink-200" x-text="fmt(step.value)" :title="fmt(step.value) + ' تومان'"></div>
                        <div class="flex h-10 items-center justify-center text-xs font-black text-white ring-2 ring-white dark:ring-ink-950"
                             :class="[step.colorClass, i === 0 ? 'rounded-s-xl' : '', i === results.timeline.length - 1 ? 'rounded-e-xl' : '']"
                             x-text="step.no"></div>
                        <div class="mt-1.5 px-0.5 text-center text-[10px] font-bold leading-tight text-ink-600 dark:text-ink-300" x-text="step.shortLabel"></div>
                        <div class="mt-0.5 px-0.5 text-center text-[9px] leading-tight text-ink-400 dark:text-ink-500" x-text="step.duration"></div>
                    </div>
                </template>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-2 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
            <x-icon name="check" class="w-4 h-4" />
            تحویل خودرو ترخیص‌شده و دارای پلاک انتظامی
        </div>
    </div>

    <div>
        <button type="button" @click="showProforma = true; pfStatus = ''; pfPdfUrl = ''"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-700 px-5 py-3 text-sm font-bold text-white shadow-soft hover:brightness-105">
            <x-icon name="invoice" class="w-4 h-4" /> درخواست پیش‌فاکتور
        </button>
    </div>

    <div x-show="showProforma" x-cloak style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="showProforma = false">
        <div @click.outside="showProforma = false" class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-soft-lg dark:bg-ink-900">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-ink-900 dark:text-white">درخواست پیش‌فاکتور</h3>
                <button type="button" @click="showProforma = false" class="text-ink-400 hover:text-ink-700">✕</button>
            </div>
            <p class="mb-3 text-xs text-ink-500 dark:text-ink-400">نام و شماره تماس را وارد کنید — فایل PDF پیش‌فاکتور اولیهٔ این خودرو بلافاصله ساخته می‌شود و در صورت وارد کردن ایمیل، برایتان ارسال هم می‌شود.</p>
            <div class="space-y-3" x-show="!pfPdfUrl">
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">نام و نام خانوادگی</label>
                    <input type="text" x-model="pfName" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">شماره تماس</label>
                    <input type="text" x-model="pfPhone" dir="ltr" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">ایمیل (اختیاری)</label>
                    <input type="email" x-model="pfEmail" dir="ltr" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                </div>
                <p x-show="pfStatus" x-text="pfStatus" :class="pfOk ? 'text-emerald-600' : 'text-rose-600'" class="text-xs font-bold"></p>
                <button type="button" @click="submitProforma()" :disabled="pfSubmitting"
                        class="w-full rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-ink-900 disabled:opacity-60">
                    <span x-show="!pfSubmitting">ثبت درخواست</span>
                    <span x-show="pfSubmitting">در حال ارسال...</span>
                </button>
            </div>
            <div x-show="pfPdfUrl" class="space-y-3">
                <p class="text-xs font-bold text-emerald-600" x-text="pfStatus"></p>
                <a :href="pfPdfUrl" target="_blank"
                   class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white hover:brightness-105">
                    <x-icon name="invoice" class="w-4 h-4" /> دانلود PDF پیش‌فاکتور
                </a>
                <button type="button" @click="showProforma = false" class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm font-bold text-ink-600 dark:border-white/10 dark:text-ink-300">بستن</button>
            </div>
        </div>
    </div>
</div>

@once
<script>
window.carCalculatorApp = function (config = @js($config)) {
    const emptyResults = () => ({
        customsRows: [], plateRows: [], sumCustomsAll: 0, sumPlate: 0,
        totalNoProfit: 0, serviceProfitAmt: 0, totalWithProfit: 0,
        loan: { amount: 0, downPayment: 0, plans: [] },
        paymentSteps: [], timeline: [],
    });

    return {
        realPriceAED: config.priceAed,
        customsPriceAED: Math.max(0, config.priceAed * (1 - (config.customsValueDiscountPercent || 30) / 100)),
        customsValueDiscountPercent: config.customsValueDiscountPercent || 30,
        customsPriceTouched: false,
        usdToAedRate: config.usdToAedRate || 3.6725,
        priceCurrency: 'aed',
        customsPriceCurrency: 'aed',
        categoryId: config.categoryId,
        categories: config.categories,
        freeRate: config.freeRate,
        customsRate: config.customsRate,
        seaFreightAED: config.seaFreightAed,
        permitsAED: config.licenseFeeAed,
        storage: config.storageToman,
        scrapCertPriceToman: config.scrapCertPriceToman,
        loanMaxAmountToman: config.loanMaxAmountToman || 0,
        loanTermYearsOptions: (config.loanTermYearsOptions && config.loanTermYearsOptions.length) ? config.loanTermYearsOptions : [5],
        loanInterestRatePercent: config.loanInterestRatePercent || 0,
        pricingResult: null,
        loading: false,
        error: '',
        recalced: false,
        recalcTimer: null,
        requestSequence: 0,
        pageLoadedAt: Date.now(),

        showProforma: false,
        pfName: '', pfPhone: '', pfEmail: '', pfStatus: '', pfOk: false, pfSubmitting: false, pfPdfUrl: '',

        init() {
            this.$watch('realPriceAED', () => {
                if (!this.customsPriceTouched) this.customsPriceAED = this.suggestedCustomsPrice();
                this.scheduleRecalc();
            });
            this.$watch('customsPriceAED', () => this.scheduleRecalc());
            this.$watch('categoryId', () => this.scheduleRecalc());
            this.recalc();
        },

        get realPriceDisplay() {
            return this.priceCurrency === 'usd' ? Math.round((this.realPriceAED / this.usdToAedRate) * 100) / 100 : this.realPriceAED;
        },
        set realPriceDisplay(v) {
            const n = parseFloat(v) || 0;
            this.realPriceAED = this.priceCurrency === 'usd' ? n * this.usdToAedRate : n;
        },
        get customsPriceDisplay() {
            return this.customsPriceCurrency === 'usd' ? Math.round((this.customsPriceAED / this.usdToAedRate) * 100) / 100 : this.customsPriceAED;
        },
        set customsPriceDisplay(v) {
            const n = parseFloat(v) || 0;
            this.customsPriceTouched = true;
            this.customsPriceAED = this.customsPriceCurrency === 'usd' ? n * this.usdToAedRate : n;
        },

        suggestedCustomsPrice() {
            return Math.max(0, (parseFloat(this.realPriceAED) || 0) * (1 - this.customsValueDiscountPercent / 100));
        },

        scheduleRecalc() {
            clearTimeout(this.recalcTimer);
            this.recalcTimer = setTimeout(() => this.recalc(), 350);
        },

        async recalc() {
            const sequence = ++this.requestSequence;
            this.loading = true;
            this.error = '';
            try {
                const response = await fetch(config.pricingUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: JSON.stringify(this.pricingInput),
                });
                if (!response.ok) throw new Error('pricing request failed');
                const result = await response.json();
                if (sequence !== this.requestSequence) return null;
                this.pricingResult = result;
                const settings = result.settingsSnapshot;
                this.freeRate = settings.freeRate;
                this.customsRate = settings.customsRate;
                this.seaFreightAED = settings.seaFreightAed;
                this.permitsAED = settings.licenseFeeAed;
                this.storage = settings.storageToman;
                this.scrapCertPriceToman = settings.scrapCertificatePriceToman;
                this.recalced = true;
                setTimeout(() => { this.recalced = false; }, 1500);
                return result;
            } catch (e) {
                if (sequence === this.requestSequence) {
                    this.error = 'محاسبه قیمت در دسترس نیست. لطفاً دوباره تلاش کنید.';
                }
                return null;
            } finally {
                if (sequence === this.requestSequence) this.loading = false;
            }
        },

        get pricingInput() {
            return {
                real_price_aed: Math.max(0, parseFloat(this.realPriceAED) || 0),
                customs_price_aed: Math.max(0, parseFloat(this.customsPriceAED) || 0),
                category: this.categoryId,
            };
        },

        fmt(n) {
            return Math.round(n || 0).toLocaleString('en-US');
        },

        async submitProforma() {
            if (!this.pfName.trim() || !this.pfPhone.trim()) {
                this.pfOk = false;
                this.pfStatus = 'لطفاً نام و شماره تماس را وارد کنید.';
                return;
            }
            this.pfSubmitting = true;
            this.pfStatus = '';
            if (!await this.recalc()) {
                this.pfOk = false;
                this.pfStatus = this.error;
                this.pfSubmitting = false;
                return;
            }
            try {
                const res = await fetch(config.quoteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: JSON.stringify({
                        name: this.pfName.trim(),
                        phone: this.pfPhone.trim(),
                        email: this.pfEmail.trim() || null,
                        notes: 'درخواست پیش‌فاکتور از صفحه خودرو',
                        car: config.carLabel,
                        pricing: this.pricingInput,
                        website: '',
                        pageLoadedAt: this.pageLoadedAt,
                    }),
                });
                const data = await res.json();
                this.pfOk = !!data.success;
                this.pfPdfUrl = data.pdfUrl || '';
                this.pfStatus = data.message || (data.success
                    ? 'درخواست شما ثبت شد؛ پیش‌فاکتور رسمی به‌زودی برایتان ارسال می‌شود.'
                    : 'ثبت درخواست ناموفق بود. لطفاً دوباره تلاش کنید.');
            } catch (e) {
                this.pfOk = false;
                this.pfStatus = 'خطا در ارتباط با سرور. لطفاً بعداً دوباره تلاش کنید.';
            } finally {
                this.pfSubmitting = false;
            }
        },

        get results() {
            const source = this.pricingResult;
            if (!source) return emptyResults();

            const num = value => Math.max(0, parseFloat(value) || 0);
            const totalWithProfit = num(source.finalTotalToman);
            const realPriceToman = num(source.realPriceToman);
            const permits = num(source.licenseFeeToman);
            const sumCustomsAll = num(source.customsSubtotalToman);
            const sumPlate = num(source.plateSubtotalToman);
            const serviceProfitAmt = num(source.serviceFeeToman);
            const loanAmount = Math.max(0, Math.min(num(this.loanMaxAmountToman), 0.5 * totalWithProfit));
            const downPayment = totalWithProfit - loanAmount;
            const annualRate = num(this.loanInterestRatePercent) / 100;
            const monthlyRate = annualRate / 12;
            const loanPlans = this.loanTermYearsOptions.map(years => {
                const months = Math.max(0, Math.round(num(years) * 12));
                let monthlyInstallment = 0;
                if (loanAmount > 0 && months > 0) {
                    monthlyInstallment = monthlyRate > 0
                        ? loanAmount * monthlyRate * Math.pow(1 + monthlyRate, months) / (Math.pow(1 + monthlyRate, months) - 1)
                        : loanAmount / months;
                }
                const totalRepayment = monthlyInstallment * months;
                return { years, months, monthlyInstallment, totalRepayment, totalInterest: totalRepayment - loanAmount };
            });
            const loan = { amount: loanAmount, downPayment, plans: loanPlans };

            const bookingAmt = 0.10 * realPriceToman;
            const paymentSteps = [
                { no: 1, label: 'پرداخت ۱۰٪ مبلغ خودرو برای بوک کردن خودرو', duration: '۱ روز کاری', value: bookingAmt },
                { no: 2, label: 'پرداخت هزینه صدور مجوز', duration: 'صدور مجوز حدود ۱ هفته', value: permits },
                { no: 3, label: 'پرداخت الباقی مبلغ خودرو در امارات', duration: 'ارسال به ایران معمولاً ۳ روز کاری', value: realPriceToman - bookingAmt },
                { no: 4, label: 'پرداخت هزینه‌های ترخیص خودرو (جمع کل هزینه‌های گمرکی)', duration: 'معمولاً ۲۰ الی ۴۰ روز کاری', value: sumCustomsAll - permits },
                { no: 5, label: 'پرداخت کارمزد ترخیص‌کار و کارگزار (ناوراکار)', duration: '', value: serviceProfitAmt },
                { no: 6, label: 'پرداخت هزینه‌های پلاک انتظامی', duration: '', value: sumPlate },
            ];
            const stageColors = ['bg-brand-500', 'bg-brand-600', 'bg-brand-700', 'bg-brand-800', 'bg-brand-900', 'bg-brand-950'];
            const shortLabels = ['بوک کردن خودرو', 'صدور مجوز', 'باقی‌ماندهٔ قیمت خودرو', 'ترخیص گمرکی', 'کارمزد کارگزار', 'پلاک انتظامی'];
            const stageDays = [1, 7, 3, 30, 2, 2];
            const timeline = paymentSteps.map((step, i) => ({
                no: step.no, shortLabel: shortLabels[i],
                duration: step.duration || (stageDays[i] + ' روز کاری'),
                value: step.value, days: stageDays[i], weight: Math.sqrt(stageDays[i]),
                colorClass: stageColors[i],
            }));

            return {
                customsRows: source.customsRows,
                plateRows: source.plateRows,
                sumCustomsAll,
                sumPlate,
                totalNoProfit: num(source.preServiceTotalToman),
                serviceProfitAmt,
                totalWithProfit,
                loan,
                paymentSteps,
                timeline,
            };
        },
    };
};
</script>
@endonce

