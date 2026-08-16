<x-layouts.admin :page-title="$pageTitle">
    @php
        $formConfig = [
            'prefill' => $prefill,
            'categories' => $categories,
            'quickRows' => $quickRows,
            'pricingUrl' => $pricingUrl,
            'csrfToken' => csrf_token(),
        ];
    @endphp

    <form method="POST" action="{{ route('admin.invoices.store') }}"
          x-data="invoicePricingForm(@js($formConfig + ['customsValueDiscountPercent' => (float) \App\Models\Setting::get(\App\Models\Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT)]))"
          class="mx-auto max-w-5xl space-y-5">

        @csrf
        <input type="hidden" name="invoice_id" value="{{ $editId ?: '' }}">
        <input type="hidden" name="request_id" value="{{ $requestId ?: '' }}">
        <input type="hidden" name="total_amount" :value="displayTotal">

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <div class="font-extrabold">پیش‌فاکتور ذخیره نشد. موارد زیر را اصلاح کنید:</div>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <x-card title="اطلاعات مشتری و سند" icon="invoice">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><label class="mb-1 block text-xs font-bold text-ink-500">نام مشتری *</label><input name="customer_name" value="{{ old('customer_name', $prefill['name']) }}" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                <div><label class="mb-1 block text-xs font-bold text-ink-500">شماره تماس *</label><input name="customer_phone" value="{{ old('customer_phone', $prefill['phone']) }}" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5"></div>
                <div><label class="mb-1 block text-xs font-bold text-ink-500">ایمیل</label><input type="email" name="customer_email" value="{{ old('customer_email', $prefill['email']) }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-ink-500">خودرو</label><input name="car_label" value="{{ old('car_label', $prefill['car']) }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">نوع پیش‌فاکتور</label>
                    <select name="invoice_type" x-model="invoiceType" @change="onInvoiceTypeChanged" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                        <option value="full">کامل خودرو</option>
                        <option value="single_item">خدمت مجزا</option>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-3"><label class="mb-1 block text-xs font-bold text-ink-500">آدرس</label><input name="customer_address" value="{{ old('customer_address', $prefill['address']) }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
            </div>
        </x-card>

        <x-card title="روش قیمت‌گذاری" icon="target" subtitle="محاسبه خودکار روش اصلی است؛ ورود دستی فقط با دلیل ثبت‌شده مجاز است.">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="cursor-pointer rounded-xl border p-4" :class="mode === 'automatic' ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-ink-200 dark:border-white/10'">
                    <input type="radio" name="pricing_mode" value="automatic" x-model="mode" :disabled="invoiceType !== 'full'" class="ms-2">
                    <span class="font-extrabold">محاسبه خودکار</span>
                    <span class="mt-1 block text-xs text-ink-500">موتور مرکزی، تنظیمات زنده و جمع سرور</span>
                </label>
                <label class="cursor-pointer rounded-xl border p-4" :class="mode === 'manual' ? 'border-amber-500 bg-amber-50 dark:bg-amber-500/10' : 'border-ink-200 dark:border-white/10'">
                    <input type="radio" name="pricing_mode" value="manual" x-model="mode" class="ms-2">
                    <span class="font-extrabold">ویرایش دستی / خدمت خاص</span>
                    <span class="mt-1 block text-xs text-ink-500">جمع سرور از روی ردیف‌ها؛ دلیل اجباری</span>
                </label>
            </div>
        </x-card>

        <div x-show="mode === 'automatic'" x-cloak class="space-y-5">
            <x-card title="ورودی‌های محاسبه خودکار" icon="car" subtitle="نرخ‌ها، درصدها، تعرفه و اسقاط فقط از تنظیمات جاری سرور خوانده می‌شوند.">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div><label class="mb-1 block text-xs font-bold text-ink-500">قیمت واقعی خودرو (درهم) *</label><input type="number" min="0" step="0.01" name="real_price_aed" x-model.number="realPriceAed" @input.debounce.500ms="calculate" :required="mode === 'automatic'" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 num-font dark:border-white/10 dark:bg-white/5"></div>
                    <div><label class="mb-1 block text-xs font-bold text-ink-500">قیمت گمرکی خودرو (درهم) *</label><input type="number" min="0" step="0.01" name="customs_price_aed" x-model.number="customsPriceAed" @input.debounce.500ms="calculate" :required="mode === 'automatic'" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 num-font dark:border-white/10 dark:bg-white/5"><button type="button" x-show="customsPriceTouched" @click="restoreCustomsSuggestion" class="mt-1 text-xs font-bold text-brand-700">استفاده از مقدار پیشنهادی</button></div>
                    <div><label class="mb-1 block text-xs font-bold text-ink-500">دسته خودرو *</label><select name="category" x-model="category" @change="calculate" :required="mode === 'automatic'" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 dark:border-white/10 dark:bg-white/5">@foreach($categories as $id => $item)<option value="{{ $id }}">{{ $item['label'] }}</option>@endforeach</select></div>
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <button type="button" @click="calculate" :disabled="loading" class="rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60"><span x-text="loading ? 'در حال محاسبه…' : 'محاسبه خودکار'"></span></button>
                    <span class="text-xs text-red-600" x-text="error"></span>
                </div>
            </x-card>

            <x-card title="خلاصه محاسبه سرور" icon="invoice">
                <template x-if="result">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-ink-50 p-4 dark:bg-white/5"><div class="text-xs text-ink-500">جمع گمرک</div><div class="mt-1 font-extrabold num-font" x-text="fmt(result.customsSubtotalToman) + ' تومان'"></div></div>
                            <div class="rounded-xl bg-ink-50 p-4 dark:bg-white/5"><div class="text-xs text-ink-500">جمع پلاک و اسقاط</div><div class="mt-1 font-extrabold num-font" x-text="fmt(result.plateSubtotalToman) + ' تومان'"></div></div>
                            <div class="rounded-xl border-2 border-amber-400 bg-amber-50 p-4 dark:bg-amber-500/10"><div class="text-xs text-amber-800">جمع نهایی موتور مرکزی</div><div class="mt-1 text-lg font-black num-font" x-text="fmt(result.finalTotalToman) + ' تومان'"></div></div>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-ink-200 dark:border-white/10">
                            <table class="w-full text-xs sm:text-sm"><thead class="bg-ink-50 dark:bg-white/5"><tr><th class="p-2 text-right">شرح</th><th class="p-2 text-right">نرخ / پایه</th><th class="p-2 text-left">مبلغ</th></tr></thead><tbody><template x-for="row in previewRows" :key="row.key"><tr class="border-t border-ink-100 dark:border-white/5"><td class="p-2" x-text="row.label"></td><td class="p-2 text-xs text-ink-500" x-text="row.rate"></td><td class="p-2 text-left num-font font-bold" x-text="fmt(row.value)"></td></tr></template></tbody></table>
                        </div>
                    </div>
                </template>
                <p x-show="!result" class="text-sm text-ink-500">برای مشاهده تفکیک، قیمت‌ها را وارد و محاسبه کنید.</p>
            </x-card>

            <x-card title="تعدیل اختیاری مورد تأیید" icon="target" subtitle="اصل محاسبه حفظ می‌شود؛ مبلغ و دلیل تعدیل جداگانه در سابقه سند ثبت خواهد شد.">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-bold text-ink-500">مبلغ تعدیل (تومان؛ مثبت یا منفی)</label><input type="number" step="0.01" name="adjustment_amount" x-model.number="adjustmentAmount" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 num-font dark:border-white/10 dark:bg-white/5"></div>
                    <div><label class="mb-1 block text-xs font-bold text-ink-500">دلیل تعدیل</label><input name="adjustment_reason" x-model="adjustmentReason" :disabled="mode !== 'automatic'" :required="mode === 'automatic' && hasAdjustment" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 dark:border-white/10 dark:bg-white/5"></div>
                </div>
            </x-card>
        </div>

        <div x-show="mode === 'manual'" x-cloak class="space-y-5">
            <x-card title="ردیف‌های دستی" icon="invoice" subtitle="جمع کل ارسالی مرورگر قابل اعتماد نیست؛ سرور مبلغ ردیف‌ها را دوباره جمع می‌کند.">
                <div class="space-y-3">
                    <template x-for="(row, index) in rows" :key="row.id">
                        <div class="grid grid-cols-1 gap-2 rounded-xl border border-ink-200 p-3 sm:grid-cols-[2fr_2fr_1fr_auto] dark:border-white/10">
                            <input name="b_label[]" x-model="row.label" placeholder="شرح هزینه" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                            <input name="b_rate[]" x-model="row.rate" placeholder="نرخ / توضیح" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                            <input name="b_amount[]" x-model="row.amount" inputmode="decimal" placeholder="مبلغ" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm num-font dark:border-white/10 dark:bg-white/5">
                            <button type="button" @click="rows.splice(index, 1)" class="rounded-lg border border-red-200 px-3 py-2 text-red-600">حذف</button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addRow" class="mt-3 rounded-xl border border-brand-200 px-4 py-2 text-sm font-bold text-brand-700">افزودن ردیف</button>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div><label class="mb-1 block text-xs font-bold text-ink-500">واحد پول</label><select name="currency" x-model="currency" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 dark:border-white/10 dark:bg-white/5">@foreach($currencies as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-xs font-bold text-ink-500">نرخ تبدیل به تومان</label><input name="exchange_rate" x-model="exchangeRate" :required="mode === 'manual' && currency !== 'toman'" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 num-font dark:border-white/10 dark:bg-white/5"></div>
                    <div><label class="mb-1 block text-xs font-bold text-ink-500">دلیل ورود دستی *</label><input name="adjustment_reason" x-model="adjustmentReason" :disabled="mode !== 'manual'" :required="mode === 'manual'" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 dark:border-white/10 dark:bg-white/5"></div>
                </div>
            </x-card>
        </div>

        <x-card title="جمع‌بندی و صدور" icon="check">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div><label class="mb-1 block text-xs font-bold text-ink-500">جمع کل محاسبه‌شده توسط سرور/ردیف‌ها</label><div class="rounded-xl bg-ink-100 px-3.5 py-2.5 font-black num-font dark:bg-white/10" x-text="fmt(displayTotal)"></div></div>
                <div><label class="mb-1 block text-xs font-bold text-ink-500">تخفیف</label><input data-money-input name="discount_amount" value="{{ old('discount_amount', $prefill['discount']) }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 num-font dark:border-white/10 dark:bg-white/5"></div>
                <div><label class="mb-1 block text-xs font-bold text-ink-500">اعتبار تا</label><input type="date" name="valid_until" value="{{ old('valid_until', $prefill['valid_until']) }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 dark:border-white/10 dark:bg-white/5"></div>
                <div class="sm:col-span-3"><label class="mb-1 block text-xs font-bold text-ink-500">شرایط پرداخت</label><textarea name="payment_terms" rows="2" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 dark:border-white/10 dark:bg-white/5">{{ old('payment_terms', $prefill['payment_terms']) }}</textarea></div>
            </div>
            <button type="submit" class="mt-5 w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-extrabold text-white">ذخیره و صدور پیش‌فاکتور</button>
        </x-card>
    </form>

    @once
        <script>
            window.invoicePricingForm = function (config = @js($formConfig + ['customsValueDiscountPercent' => (float) \App\Models\Setting::get(\App\Models\Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT)])) {
                const prefill = config.prefill;
                const discountPercent = config.customsValueDiscountPercent || 30;
                const realPrice = Number(prefill.real_price_aed || 0);
                const customsPrice = Number(prefill.customs_price_aed || 0);
                const suggestedCustomsPrice = Math.max(0, realPrice * (1 - discountPercent / 100));
                return {
                    categories: config.categories,
                    invoiceType: prefill.invoice_type || 'full',
                    mode: prefill.pricing_mode || 'automatic',
                    realPriceAed: realPrice,
                    customsPriceAed: customsPrice || suggestedCustomsPrice,
                    customsValueDiscountPercent: discountPercent,
                    customsPriceTouched: customsPrice > 0,
                    category: prefill.category || 'c2000',
                    adjustmentAmount: Number(prefill.adjustment_amount || 0),
                    adjustmentReason: prefill.adjustment_reason || '',
                    currency: prefill.currency || 'toman',
                    exchangeRate: prefill.exchange_rate || '',
                    rows: (prefill.breakdown || []).map((row, index) => ({id: index + 1, label: row.label || '', rate: row.rate || '', amount: row.amount ?? row.value ?? ''})),
                    result: null,
                    loading: false,
                    error: '',
                    nextId: 1000,

                    init() {
                        if (!this.rows.length) this.addRow();
                        if (this.mode === 'automatic' && this.realPriceAed > 0 && this.customsPriceAed > 0) this.calculate();

                        // Watch for real price changes to auto-update customs price unless manually edited
                        this.$watch('realPriceAed', (newVal) => {
                            if (!this.customsPriceTouched && newVal >= 0) {
                                this.customsPriceAed = Math.max(0, newVal * (1 - this.customsValueDiscountPercent / 100));
                            }
                        });

                        // Mark customs price as touched when user edits it
                        this.$watch('customsPriceAed', (newVal) => {
                            if (newVal !== (this.realPriceAed * (1 - this.customsValueDiscountPercent / 100))) {
                                this.customsPriceTouched = true;
                            }
                        });
                    },

                    suggestedCustomsPrice() {
                        return Math.max(0, this.realPriceAed * (1 - this.customsValueDiscountPercent / 100));
                    },

                    restoreCustomsSuggestion() {
                        this.customsPriceAed = this.suggestedCustomsPrice();
                        this.customsPriceTouched = false;
                    },
                    onInvoiceTypeChanged() {
                        if (this.invoiceType !== 'full') this.mode = 'manual';
                    },
                    addRow() {
                        this.rows.push({id: this.nextId++, label: '', rate: '', amount: ''});
                    },
                    numeric(value) {
                        const normalized = String(value ?? '').replace(/[^0-9.-]/g, '');
                        const number = Number(normalized);
                        return Number.isFinite(number) ? number : 0;
                    },
                    get displayTotal() {
                        if (this.mode === 'automatic') return Math.max(0, this.numeric(this.result?.finalTotalToman) + this.numeric(this.adjustmentAmount));
                        return this.rows.reduce((sum, row) => sum + Math.max(0, this.numeric(row.amount)), 0);
                    },
                    get previewRows() {
                        if (!this.result) return [];
                        return [...this.result.customsRows, ...this.result.plateRows];
                    },
                    get hasAdjustment() {
                        return Number(this.adjustmentAmount) !== 0;
                    },
                    fmt(value) {
                        return Math.round(this.numeric(value)).toLocaleString('en-US');
                    },
                    async calculate() {
                        if (this.mode !== 'automatic' || this.realPriceAed < 0 || this.customsPriceAed < 0) return;
                        this.loading = true;
                        this.error = '';
                        try {
                            const response = await fetch(config.pricingUrl, {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrfToken, 'Accept': 'application/json'},
                                body: JSON.stringify({real_price_aed: this.realPriceAed, customs_price_aed: this.customsPriceAed, category: this.category}),
                            });
                            if (!response.ok) throw new Error('محاسبه سرور ناموفق بود.');
                            this.result = await response.json();
                        } catch (error) {
                            this.result = null;
                            this.error = error.message || 'خطا در محاسبه سرور';
                        } finally {
                            this.loading = false;
                        }
                    },
                };
            };
        </script>
    @endonce
</x-layouts.admin>

