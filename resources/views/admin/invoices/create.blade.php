@php
    $sub = $requestId ? 'اطلاعات از درخواست #'.$requestId.' پیش‌پر شده است.' : ($editId ? 'در حال ویرایش پیش‌فاکتور موجود.' : 'اطلاعات مشتری و اقلام هزینه را وارد کنید.');
@endphp
<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$sub">

    <div x-data='invoiceForm(@json($prefill["breakdown"]), @json($prefill["invoice_type"]), @json($prefill["currency"]), @json($calcConfig))' class="mx-auto max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('admin.invoices.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="invoice_id" value="{{ $editId }}">
                <input type="hidden" name="request_id" value="{{ $requestId ?: '' }}">

                <div>
                    <label class="mb-2 block text-sm font-bold">نوع پیش‌فاکتور</label>
                    <div class="flex gap-3">
                        <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border-2 px-4 py-3 text-sm font-bold"
                               :class="type === 'full' ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-ink-200 dark:border-white/10'">
                            <input type="radio" name="invoice_type" value="full" x-model="type" class="text-brand-600"> واردات کامل خودرو
                        </label>
                        <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border-2 px-4 py-3 text-sm font-bold"
                               :class="type === 'single_item' ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-ink-200 dark:border-white/10'">
                            <input type="radio" name="invoice_type" value="single_item" x-model="type" class="text-brand-600"> خدمت مجزا (مثلاً فقط مجوز یا فقط حمل)
                        </label>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-1.5 block text-sm font-bold">نام مشتری *</label><input name="customer_name" required value="{{ $prefill['name'] }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                    <div><label class="mb-1.5 block text-sm font-bold">شماره تماس *</label><input name="customer_phone" required value="{{ $prefill['phone'] }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                    <div><label class="mb-1.5 block text-sm font-bold">ایمیل مشتری (اختیاری)</label><input type="email" name="customer_email" value="{{ $prefill['email'] }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                    <div><label class="mb-1.5 block text-sm font-bold">آدرس مشتری (اختیاری)</label><input name="customer_address" value="{{ $prefill['address'] }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                </div>

                <div x-show="type === 'full'" class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-1.5 block text-sm font-bold">خودرو</label><input name="car_label" x-model="carLabel" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">دسته خودرو</label>
                        <select name="category" x-model="categoryLabel" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                            <option value="">— نامشخص —</option>
                            @foreach ($categories as $c)<option>{{ $c }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div x-show="type === 'full'" class="rounded-2xl border border-ink-200/70 dark:border-white/10">
                    <button type="button" @click="autoOpen = !autoOpen" class="flex w-full items-center justify-between px-4 py-3 text-sm font-bold text-brand-800 dark:text-brand-300">
                        <span>🧮 محاسبهٔ خودکار هزینه‌ها (مثل صفحهٔ محاسبه‌گر سایت)</span>
                        <span x-text="autoOpen ? '−' : '+'"></span>
                    </button>
                    <div x-show="autoOpen" x-cloak class="space-y-3 border-t border-ink-200/70 p-4 dark:border-white/10">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-bold text-ink-500">قیمت خودرو (درهم)</label>
                                <input type="number" x-model.number="calc.priceAed" class="w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm num-font dark:border-white/10 dark:bg-ink-900">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-ink-500">دسته‌بندی خودرو</label>
                                <select x-model="calc.categoryId" class="w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-ink-900">
                                    <template x-for="(cat, key) in calcConfig.categories" :key="key">
                                        <option :value="key" x-text="cat.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-ink-500">نرخ ارز آزاد (تومان)</label>
                                <input type="number" x-model.number="calc.freeRate" class="w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm num-font dark:border-white/10 dark:bg-ink-900">
                            </div>
                        </div>
                        <div class="rounded-xl bg-amber-50 p-3 text-center text-sm font-black text-amber-900 dark:bg-amber-500/10 dark:text-amber-300">
                            قیمت تمام‌شدهٔ محاسبه‌شده: <span class="num-font" x-text="fmt(calcResults.totalWithProfit)"></span> تومان
                        </div>
                        <button type="button" @click="applyCalculated()" class="w-full rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white hover:brightness-105">
                            جایگزینی اقلام پیش‌فاکتور با نتیجهٔ این محاسبه
                        </button>
                        <p class="text-[11px] text-ink-400">با زدن این دکمه، لیست اقلام پایین با ردیف‌های محاسبه‌شده (عوارض گمرکی، پلاک، کارمزد) جایگزین می‌شود — بعد از آن هم می‌توانید دستی ویرایششان کنید.</p>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold">اقلام پیش‌فاکتور</label>
                    <div class="mb-3 flex flex-wrap gap-2" x-show="type === 'full'">
                        @foreach ($quickRows['full'] as $qr)
                            <button type="button" @click="addRow('{{ addslashes($qr[0]) }}', '{{ addslashes($qr[1]) }}', '')" class="rounded-full bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-800 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-300">+ {{ $qr[0] }}</button>
                        @endforeach
                    </div>
                    <div class="mb-3 flex flex-wrap gap-2" x-show="type === 'single_item'">
                        @foreach ($quickRows['single_item'] as $qr)
                            <button type="button" @click="addRow('{{ addslashes($qr[0]) }}', '{{ addslashes($qr[1]) }}', '')" class="rounded-full bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-800 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-300">+ {{ $qr[0] }}</button>
                        @endforeach
                    </div>

                    <div class="space-y-2">
                        <template x-for="(row, i) in rows" :key="i">
                            <div class="grid grid-cols-[2fr_2fr_1fr_auto] gap-2">
                                <input :name="'b_label[]'" x-model="row.label" placeholder="شرح هزینه" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5" :readonly="row.vat" :class="row.vat ? 'opacity-70' : ''">
                                <input :name="'b_rate[]'" x-model="row.rate" placeholder="نرخ / توضیح" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5" :readonly="row.vat" :class="row.vat ? 'opacity-70' : ''">
                                <input :name="'b_amount[]'" x-model="row.amount" @input="row.vat = false" placeholder="مبلغ" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm num-font dark:border-white/10 dark:bg-white/5">
                                <button type="button" @click="rows.splice(i, 1)" class="rounded-lg bg-rose-50 px-3 text-xs font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">حذف</button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addRow('', '', '')" class="mt-3 rounded-xl bg-ink-100 px-4 py-2 text-xs font-bold text-ink-600 dark:bg-white/10 dark:text-ink-300">+ ردیف خالی</button>

                    <div class="mt-4 rounded-xl bg-ink-50 p-3.5 dark:bg-white/5">
                        <label class="flex items-center gap-2 text-sm font-bold">
                            <input type="checkbox" x-model="vatEnabled" @change="syncVatRow()" class="rounded text-brand-600">
                            افزودن مالیات بر ارزش افزوده
                        </label>
                        <div x-show="vatEnabled" x-cloak class="mt-2 flex items-center gap-2">
                            <span class="text-xs text-ink-500">نرخ ارزش افزوده:</span>
                            <input type="number" step="0.1" x-model.number="vatPercent" @input="syncVatRow()" class="w-20 rounded-lg border border-ink-200 bg-white px-2 py-1.5 text-sm num-font dark:border-white/10 dark:bg-ink-900">
                            <span class="text-xs text-ink-500">٪</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">واحد پول صدور</label>
                        <select name="currency" x-model="currency" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                            @foreach ($currencies as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="currency !== 'toman'">
                        <label class="mb-1.5 block text-sm font-bold">نرخ ارز (تومان به ازای هر واحد)</label>
                        <input name="exchange_rate" value="{{ $prefill['exchange_rate'] }}" placeholder="مثلاً 51000" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                    </div>
                </div>

                <div class="rounded-2xl border border-ink-200/70 p-4 dark:border-white/10">
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between"><span class="text-ink-500">جمع اقلام</span><span class="num-font font-bold" x-text="fmt(itemsTotal)"></span></div>
                        <div class="flex justify-between" x-show="vatEnabled"><span class="text-ink-500">مالیات بر ارزش افزوده (<span x-text="vatPercent"></span>٪)</span><span class="num-font font-bold" x-text="fmt(vatAmount)"></span></div>
                        <div class="flex justify-between border-t border-ink-100 pt-1.5 font-extrabold dark:border-white/10"><span>جمع کل قبل از تخفیف</span><span class="num-font" x-text="fmt(grandTotal)"></span></div>
                        <div class="flex justify-between text-amber-700 dark:text-amber-400" x-show="discount > 0"><span>تخفیف</span><span class="num-font font-bold">− <span x-text="fmt(discount)"></span></span></div>
                        <div class="flex justify-between rounded-lg bg-brand-50 p-2 text-base font-black text-brand-900 dark:bg-brand-500/10 dark:text-brand-300"><span>مبلغ قابل‌پرداخت</span><span class="num-font" x-text="fmt(payable)"></span></div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-1.5 block text-sm font-bold">جمع کل قبل از تخفیف *</label><input name="total_amount" required readonly :value="grandTotal" class="w-full cursor-not-allowed rounded-xl border border-ink-200 bg-ink-100 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/10"></div>
                    <div><label class="mb-1.5 block text-sm font-bold">تخفیف (اختیاری)</label><input name="discount_amount" x-model.number="discount" placeholder="مثلاً 50000000" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5"></div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-1.5 block text-sm font-bold">اعتبار پیش‌فاکتور تا تاریخ (اختیاری)</label><input type="date" name="valid_until" value="{{ $prefill['valid_until'] }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                    <div><label class="mb-1.5 block text-sm font-bold">شرایط پرداخت (اختیاری)</label><input name="payment_terms" value="{{ $prefill['payment_terms'] }}" placeholder="مثلاً ۵۰٪ پیش‌پرداخت، مابقی هنگام تحویل" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                </div>

                <x-button type="submit" variant="amber">{{ $editId ? 'ذخیره تغییرات' : 'ثبت و مشاهده پیش‌فاکتور' }}</x-button>
            </form>
        </x-card>
    </div>

    <script>
        function invoiceForm(prefillBreakdown, initialType, initialCurrency, calcConfig) {
            const initialRows = prefillBreakdown && prefillBreakdown.length
                ? prefillBreakdown.map(r => ({ label: r.label || '', rate: r.rate || '', amount: r.amount || '', vat: r.label === 'مالیات بر ارزش افزوده (VAT)' }))
                : [{ label: '', rate: '', amount: '', vat: false }];
            const existingVat = initialRows.find(r => r.vat);

            return {
                type: initialType || 'full',
                currency: initialCurrency || 'toman',
                carLabel: @js($prefill['car']),
                categoryLabel: @js($prefill['category']),
                rows: initialRows,
                discount: {{ (float) ($prefill['discount'] ?: 0) }},
                vatEnabled: !!existingVat,
                vatPercent: existingVat ? (parseFloat(existingVat.rate) || 10) : 10,
                autoOpen: false,
                calcConfig: calcConfig,
                calc: {
                    priceAed: 0,
                    categoryId: Object.keys(calcConfig.categories)[0] || '',
                    freeRate: calcConfig.freeRate,
                },

                num(v) {
                    const n = parseFloat(String(v ?? '').replace(/[^0-9.-]/g, ''));
                    return isNaN(n) ? 0 : n;
                },
                fmt(n) { return Math.round(n || 0).toLocaleString('en-US'); },

                addRow(label, rate, amount) { this.rows.push({ label, rate, amount, vat: false }); },

                get itemsTotal() {
                    return this.rows.reduce((s, r) => s + this.num(r.amount), 0);
                },
                get vatBase() {
                    return this.rows.filter(r => !r.vat).reduce((s, r) => s + this.num(r.amount), 0);
                },
                get vatAmount() {
                    return this.vatEnabled ? Math.round(this.vatBase * this.num(this.vatPercent) / 100) : 0;
                },
                get grandTotal() {
                    return this.itemsTotal;
                },
                get payable() {
                    return this.grandTotal - this.num(this.discount);
                },
                syncVatRow() {
                    const idx = this.rows.findIndex(r => r.vat);
                    if (this.vatEnabled) {
                        const row = { label: 'مالیات بر ارزش افزوده (VAT)', rate: this.vatPercent + '٪', amount: this.vatAmount, vat: true };
                        if (idx >= 0) this.rows[idx] = row; else this.rows.push(row);
                    } else if (idx >= 0) {
                        this.rows.splice(idx, 1);
                    }
                },

                get calcResults() {
                    const num = v => { const n = parseFloat(v); return isNaN(n) || n < 0 ? 0 : n; };
                    const priceAed = num(this.calc.priceAed);
                    const freeRate = num(this.calc.freeRate);
                    const customsRate = num(calcConfig.customsRate);
                    const cat = calcConfig.categories[this.calc.categoryId] || { coef: 1.2, tier: 'cd' };
                    const CIF = priceAed * customsRate;
                    const realPriceToman = priceAed * freeRate;
                    const dutyProfit = cat.coef * CIF;
                    const base9 = dutyProfit + CIF;

                    const customsRows = [
                        { label: 'عوارض گمرکی بر اساس تعرفه', rate: `${(cat.coef * 100).toFixed(0)}٪ از ارزش گمرکی`, value: dutyProfit },
                        { label: 'حقوق گمرکی ثابت', rate: '۴٪ از ارزش گمرکی', value: 0.04 * CIF },
                        { label: 'عوارض بنزین‌سوز', rate: '۱۰٪ از ارزش گمرکی', value: 0.10 * CIF },
                        { label: 'عوارض ۵٪ فوب', rate: '۵٪ از ارزش فوب', value: 0.05 * CIF },
                        { label: 'مالیات ارزش افزوده (VAT)', rate: '۱۰٪ از (گمرکی+حقوق ورودی)', value: 0.10 * base9 },
                        { label: 'مالیات علی‌الحساب واردات', rate: '۲٪ از (گمرکی+حقوق ورودی)', value: 0.02 * base9 },
                        { label: 'عوارض هلال احمر', rate: '۱٪ از حقوق ورودی', value: 0.01 * dutyProfit },
                        { label: 'حق نظارت کارشناسان گمرک', rate: '۰.۵٪ از حقوق ورودی', value: 0.005 * dutyProfit },
                        { label: 'عوارض پسماند کالا', rate: '۰.۰۵٪ از ارزش گمرکی', value: 0.0005 * CIF },
                        { label: 'هزینه استاندارد', rate: '۰.۸٪ از ارزش گمرکی', value: 0.008 * CIF },
                    ];
                    const sumCustoms10 = customsRows.reduce((s, r) => s + r.value, 0);

                    const seaFreight = num(calcConfig.seaFreightAed) * freeRate;
                    const permits = num(calcConfig.licenseFeeAed) * freeRate;
                    const storage = num(calcConfig.storageToman);
                    customsRows.push({ label: 'حمل دریایی', rate: 'درهم × نرخ ارز آزاد', value: seaFreight });
                    customsRows.push({ label: 'هزینه صدور مجوزهای واردات', rate: 'درهم × نرخ ارز آزاد', value: permits });
                    customsRows.push({ label: 'انبارداری، دموراژ و THC', rate: 'مبلغ ثابت', value: storage });
                    const sumCustomsAll = sumCustoms10 + seaFreight + permits + storage;

                    const tier = cat.tier || 'cd';
                    const scrapThresholdAed = num(calcConfig.scrapThresholdAed);
                    const bracket = priceAed > scrapThresholdAed ? 'above' : 'upto';
                    const certCount = (calcConfig.scrapCertCounts[tier] || calcConfig.scrapCertCounts.cd)[bracket];
                    const scrapFee = certCount * num(calcConfig.scrapCertPriceToman);

                    const plateRows = [
                        { label: 'گواهی اسقاط خودرو فرسوده', rate: `${certCount} فقره گواهی × نرخ روز`, value: scrapFee },
                        { label: 'عوارض شماره‌گذاری راهور', rate: '۱۰٪ از ارزش گمرکی', value: 0.10 * CIF },
                        { label: 'مالیات نقل و انتقال', rate: '۳٪ از ارزش گمرکی', value: 0.03 * CIF },
                        { label: 'عوارض سالانه شهرداری', rate: '۱٪ از ارزش گمرکی', value: 0.01 * CIF },
                        { label: 'عوارض شخص حقیقی', rate: '۵٪ از ارزش گمرکی', value: 0.05 * CIF },
                    ];
                    const sumPlate = plateRows.reduce((s, r) => s + r.value, 0);

                    const totalNoProfit = sumCustomsAll + sumPlate + realPriceToman;
                    const serviceProfitAmt = 0.10 * (sumCustoms10 + sumPlate + seaFreight + permits);
                    const totalWithProfit = totalNoProfit + serviceProfitAmt;

                    return { customsRows, plateRows, realPriceToman, serviceProfitAmt, totalWithProfit };
                },

                applyCalculated() {
                    const r = this.calcResults;
                    const newRows = [{ label: 'قیمت خودرو (به تومان)', rate: 'قیمت درهم × نرخ ارز آزاد', amount: Math.round(r.realPriceToman) }];
                    [...r.customsRows, ...r.plateRows].forEach(row => {
                        newRows.push({ label: row.label, rate: row.rate, amount: Math.round(row.value), vat: false });
                    });
                    newRows.push({ label: 'کارمزد ترخیص‌کار و کارگزار (ناوراکار)', rate: '۱۰٪', amount: Math.round(r.serviceProfitAmt), vat: false });
                    this.rows = newRows;
                    if (this.vatEnabled) this.syncVatRow();
                },
            };
        }
    </script>
</x-layouts.admin>
