@php
    $sub = $requestId ? 'اطلاعات از درخواست #'.$requestId.' پیش‌پر شده است.' : ($editId ? 'در حال ویرایش پیش‌فاکتور موجود.' : 'اطلاعات مشتری و اقلام هزینه را وارد کنید.');
@endphp
<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$sub">

    <div x-data="invoiceForm(@json($prefill['breakdown']), @json($prefill['invoice_type']), @json($prefill['currency']))" class="mx-auto max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('admin.invoices.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="invoice_id" value="{{ $editId }}">
                <input type="hidden" name="request_id" value="{{ $requestId }}">

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
                    <div><label class="mb-1.5 block text-sm font-bold">خودرو</label><input name="car_label" value="{{ $prefill['car'] }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">دسته خودرو</label>
                        <select name="category" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                            <option value="">— نامشخص —</option>
                            @foreach ($categories as $c)<option @selected($prefill['category'] === $c)>{{ $c }}</option>@endforeach
                        </select>
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
                                <input :name="'b_label[]'" x-model="row.label" placeholder="شرح هزینه" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                                <input :name="'b_rate[]'" x-model="row.rate" placeholder="نرخ / توضیح" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                                <input :name="'b_amount[]'" x-model="row.amount" placeholder="مبلغ" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                                <button type="button" @click="rows.splice(i, 1)" class="rounded-lg bg-rose-50 px-3 text-xs font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">حذف</button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addRow('', '', '')" class="mt-3 rounded-xl bg-ink-100 px-4 py-2 text-xs font-bold text-ink-600 dark:bg-white/10 dark:text-ink-300">+ ردیف خالی</button>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">واحد پول صدور</label>
                        <select name="currency" x-model="currency" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                            <option value="toman">تومان</option>
                            <option value="aed">درهم امارات (AED)</option>
                        </select>
                    </div>
                    <div x-show="currency === 'aed'">
                        <label class="mb-1.5 block text-sm font-bold">نرخ ارز (تومان به ازای هر درهم)</label>
                        <input name="exchange_rate" value="{{ $prefill['exchange_rate'] }}" placeholder="مثلاً 51000" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-1.5 block text-sm font-bold">جمع کل قبل از تخفیف *</label><input name="total_amount" required value="{{ $prefill['total'] }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
                    <div><label class="mb-1.5 block text-sm font-bold">تخفیف (اختیاری)</label><input name="discount_amount" value="{{ $prefill['discount'] ?: 0 }}" placeholder="مثلاً 50000000" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></div>
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
        function invoiceForm(prefillBreakdown, initialType, initialCurrency) {
            return {
                type: initialType || 'full',
                currency: initialCurrency || 'toman',
                rows: prefillBreakdown && prefillBreakdown.length ? prefillBreakdown.map(r => ({ label: r.label || '', rate: r.rate || '', amount: r.amount || '' })) : [{ label: '', rate: '', amount: '' }],
                addRow(label, rate, amount) { this.rows.push({ label, rate, amount }); },
            };
        }
    </script>
</x-layouts.admin>
