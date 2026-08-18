<x-layouts.admin :page-title="$pageTitle">

    @php
        $formConfig = [
            'prefill' => $prefill,
            'categories' => $categories,
            'quickRows' => $quickRows,
            'pricingUrl' => $pricingUrl,
            'csrfToken' => csrf_token(),
            'customsValueDiscountPercent' => (float) \App\Models\Setting::get(\App\Models\Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT),
        ];
    @endphp

    <form method="POST" action="{{ route('admin.invoices.store') }}"
          x-data="invoicePricingForm"
          class="mx-auto max-w-5xl space-y-5">

        @csrf
        <input type="hidden" name="invoice_id" value="{{ $editId ?: '' }}">
        <input type="hidden" name="request_id" value="{{ $requestId ?: '' }}">
        <input type="hidden" name="total_amount" :value="displayTotal">

        @if ($errors->any())
            <div class="rounded-xl border border-v2-error/30 bg-v2-error/15 p-4 text-sm text-v2-error">
                <div class="font-extrabold">پیش‌فاکتور ذخیره نشد. موارد زیر را اصلاح کنید:</div>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <x-card variant="v2" title="اطلاعات مشتری و سند" icon="invoice">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">نام مشتری *</label><input name="customer_name" value="{{ old('customer_name', $prefill['name']) }}" required class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text"></div>
                <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">شماره تماس *</label><input name="customer_phone" value="{{ old('customer_phone', $prefill['phone']) }}" required class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm num-font text-v2-text"></div>
                <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">ایمیل</label><input type="email" name="customer_email" value="{{ old('customer_email', $prefill['email']) }}" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text"></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-v2-text-muted">خودرو</label><input name="car_label" value="{{ old('car_label', $prefill['car']) }}" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text"></div>
                <div class="sm:col-span-2 lg:col-span-3"><label class="mb-1 block text-xs font-bold text-v2-text-muted">آدرس</label><input name="customer_address" value="{{ old('customer_address', $prefill['address']) }}" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text"></div>
            </div>
        </x-card>

        <x-card variant="v2" title="روش قیمت‌گذاری" icon="target" subtitle="محاسبه خودکار روش اصلی است؛ ورود دستی فقط با دلیل ثبت‌شده مجاز است.">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start rounded-xl border border-v2-border p-3">
                    <input type="radio" name="pricing_mode" value="automatic" x-model="mode" class="ms-2">
                    <div>
                        <span class="font-extrabold">محاسبه خودکار</span>
                        <span class="mt-1 block text-xs text-v2-text-muted">موتور مرکزی، تنظیمات زنده و جمع سرور</span>
                    </div>
                </label>
                <label class="flex cursor-pointer items-start rounded-xl border border-v2-border p-3">
                    <input type="radio" name="pricing_mode" value="manual" x-model="mode" class="ms-2">
                    <div>
                        <span class="font-extrabold">ویرایش دستی / خدمت خاص</span>
                        <span class="mt-1 block text-xs text-v2-text-muted">جمع سرور از روی ردیف‌ها؛ دلیل اجباری</span>
                    </div>
                </label>
            </div>
            <input type="hidden" name="invoice_type" x-model="invoiceType">
        </x-card>

        <div x-show="mode === 'automatic'">
            <x-card variant="v2" title="ورودی‌های محاسبه خودکار" icon="car" subtitle="نرخ‌ها، درصدها، تعرفه و اسقاط فقط از تنظیمات جاری سرور خوانده می‌شوند.">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">قیمت واقعی خودرو (درهم) *</label><input type="number" min="0" step="0.01" name="real_price_aed" x-model.number="realPriceAed" @input.debounce.500ms="calculate" :required="mode === 'automatic'" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 num-font text-v2-text"></div>
                    <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">قیمت گمرکی خودرو (درهم) *</label><input type="number" min="0" step="0.01" name="customs_price_aed" x-model.number="customsPriceAed" @input.debounce.500ms="calculate" :required="mode === 'automatic'" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 num-font text-v2-text"><button type="button" x-show="customsPriceTouched" @click="restoreCustomsSuggestion" class="mt-1 text-xs font-bold text-v2-primary">استفاده از مقدار پیشنهادی</button></div>
                    <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">دسته خودرو *</label><select name="category" x-model="category" @change="calculate" :required="mode === 'automatic'" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-v2-text">@foreach($categories as $id => $item)<option value="{{ $id }}">{{ $item['label'] }}</option>@endforeach</select></div>
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <button type="button" @click="calculate" :disabled="loading" class="rounded-xl bg-v2-primary px-5 py-2.5 text-sm font-bold text-white hover:brightness-110 disabled:opacity-60">
                        <span x-show="!loading">محاسبه خودکار</span>
                        <span x-show="loading">در حال محاسبه…</span>
                    </button>
                    <span class="text-xs text-v2-error" x-text="error"></span>
                </div>
            </x-card>

            <x-card variant="v2" title="خلاصه محاسبه سرور" icon="trend-up" class="mt-5">
                <template x-if="!result">
                    <p class="text-sm text-v2-text-muted">برای مشاهده تفکیک، قیمت‌ها را وارد و محاسبه کنید.</p>
                </template>
                <template x-if="result">
                    <div class="space-y-2">
                        <template x-for="row in previewRows" :key="row.key">
                            <div class="flex items-start justify-between gap-3 border-b border-v2-border py-2 text-sm">
                                <div>
                                    <div class="font-bold" x-text="row.label"></div>
                                    <div class="text-xs text-v2-text-muted" x-text="row.rate"></div>
                                </div>
                                <div class="num-font font-bold" x-text="fmt(row.value) + ' تومان'"></div>
                            </div>
                        </template>
                        <div class="flex justify-between pt-2 text-sm font-extrabold">
                            <span>جمع کل نهایی</span>
                            <span class="num-font" x-text="fmt(result.finalTotalToman) + ' تومان'"></span>
                        </div>
                    </div>
                </template>
            </x-card>

            <x-card variant="v2" title="تعدیل اختیاری مورد تأیید" icon="edit" class="mt-5" subtitle="اصل محاسبه حفظ می‌شود؛ مبلغ و دلیل تعدیل جداگانه در سابقه سند ثبت خواهد شد.">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">مبلغ تعدیل (تومان)</label><input type="number" step="1" name="adjustment_amount" x-model.number="adjustmentAmount" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 num-font text-v2-text"></div>
                    <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">دلیل تعدیل</label><input name="adjustment_reason" x-model="adjustmentReason" :required="hasAdjustment" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-v2-text"></div>
                </div>
            </x-card>
        </div>

        <div x-show="mode === 'manual'">
            <x-card variant="v2" title="ردیف‌های دستی" icon="list" subtitle="جمع کل ارسالی مرورگر قابل اعتماد نیست؛ سرور مبلغ ردیف‌ها را دوباره جمع می‌کند.">
                <div class="space-y-3">
                    <template x-for="(row, index) in rows" :key="row.id">
                        <div class="grid grid-cols-1 gap-2 rounded-xl border border-v2-border p-3 sm:grid-cols-[2fr_2fr_1fr_auto]">
                            <input name="b_label[]" x-model="row.label" placeholder="شرح هزینه" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
                            <input name="b_rate[]" x-model="row.rate" placeholder="نرخ / توضیح" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
                            <input name="b_amount[]" x-model="row.amount" inputmode="decimal" placeholder="مبلغ" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm num-font text-v2-text">
                            <button type="button" @click="rows.splice(index, 1)" class="rounded-lg border border-v2-error/30 px-3 py-2 text-v2-error">حذف</button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addRow" class="mt-3 rounded-xl border border-v2-primary/30 px-4 py-2 text-sm font-bold text-v2-primary">افزودن ردیف</button>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">واحد پول</label><select name="currency" x-model="currency" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-v2-text">@foreach($currencies as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">نرخ تبدیل به تومان</label><input name="exchange_rate" x-model="exchangeRate" :required="mode === 'manual' && currency !== 'toman'" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 num-font text-v2-text"></div>
                    <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">دلیل ورود دستی *</label><input name="adjustment_reason" x-model="adjustmentReason" :disabled="mode !== 'manual'" :required="mode === 'manual'" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-v2-text"></div>
                </div>
            </x-card>
        </div>

        <x-card variant="v2" title="تخفیف و نهایی‌سازی" icon="check">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">تخفیف</label><input data-money-input name="discount_amount" value="{{ old('discount_amount', $prefill['discount']) }}" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 num-font text-v2-text"></div>
                <div><label class="mb-1 block text-xs font-bold text-v2-text-muted">اعتبار تا</label><input type="date" name="valid_until" value="{{ old('valid_until', $prefill['valid_until']) }}" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-v2-text"></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-v2-text-muted">جمع کل محاسبه‌شده توسط سرور/ردیف‌ها</label><div class="rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 num-font font-extrabold text-v2-text" x-text="fmt(displayTotal) + ' تومان'"></div></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-v2-text-muted">شرایط پرداخت</label><textarea name="payment_terms" rows="2" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-v2-text">{{ old('payment_terms', $prefill['payment_terms']) }}</textarea></div>
            </div>
            <button type="submit" class="mt-5 w-full rounded-xl bg-v2-success px-5 py-3 text-sm font-extrabold text-white hover:brightness-110">ذخیره و صدور پیش‌فاکتور</button>
        </x-card>
    </form>

    @once
        <script>
            window.invoicePricingForm = function (config = @js($formConfig)) {
                const prefill = config.prefill;
                const discountPercent = config.customsValueDiscountPercent ?? 30;
                const realPrice = Number(prefill.real_price_aed || 0);
                const hasCustomsPrice = prefill.has_customs_price === true;
                const customsPrice = Number(prefill.customs_price_aed ?? 0);
                const suggestedCustomsPrice = Math.max(0, realPrice * (1 - discountPercent / 100));
                return {
                    categories: config.categories,
                    invoiceType: prefill.invoice_type || 'full',
                    mode: prefill.pricing_mode || 'automatic',
                    realPriceAed: realPrice,
                    customsPriceAed: hasCustomsPrice ? customsPrice : suggestedCustomsPrice,
                    customsValueDiscountPercent: discountPercent,
                    customsPriceTouched: hasCustomsPrice && customsPrice !== suggestedCustomsPrice,
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

                        this.$watch('realPriceAed', (newVal) => {
                            if (!this.customsPriceTouched && newVal >= 0) {
                                this.customsPriceAed = Math.max(0, newVal * (1 - this.customsValueDiscountPercent / 100));
                            }
                        });

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
                        this.calculate();
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
