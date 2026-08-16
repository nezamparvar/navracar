@php
    $breakdown = $lead->breakdown();
    $totals = $lead->totals();
@endphp
<x-layouts.admin :page-title="$pageTitle">

    <p class="mb-5 text-sm"><a href="{{ route('admin.requests.index') }}" class="font-bold text-brand-600 dark:text-brand-300">← بازگشت به لیست</a></p>

    <div class="grid gap-5 lg:grid-cols-[1.3fr_.7fr]">
        <div class="space-y-5">
            <x-card title="تفکیک هزینه‌های ارسال‌شده" icon="calculator">
                @if (empty($breakdown))
                    <x-empty-state icon="calculator" title="برآورد هزینه‌ای برای این درخواست ثبت نشده است." />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b-2 border-ink-100 text-xs font-extrabold text-ink-400 dark:border-white/10 dark:text-ink-500">
                                    <th class="px-2.5 py-2 text-start">شرح</th><th class="px-2.5 py-2 text-start">نرخ</th><th class="px-2.5 py-2 text-start">مبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($breakdown as $row)
                                    <tr class="border-b border-ink-100 dark:border-white/5">
                                        <td class="px-2.5 py-2">{{ $row['label'] ?? '' }}</td>
                                        <td class="px-2.5 py-2 text-xs text-ink-500">{{ $row['rate'] ?? '' }}</td>
                                        <td class="num-font px-2.5 py-2 font-bold">{{ $row['amount'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (!empty($totals))
                        <table class="mt-4 w-full text-sm">
                            <tbody>
                                @foreach ($totals as $label => $val)
                                    <tr class="border-b border-dashed border-ink-100 dark:border-white/5">
                                        <td class="px-2.5 py-2 font-extrabold">{{ $label }}</td>
                                        <td class="num-font px-2.5 py-2 font-extrabold text-brand-700 dark:text-brand-300">{{ $val }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    <div class="mt-4">
                        <x-button :href="route('admin.invoices.create', ['request_id' => $lead->id])" variant="amber">
                            <x-icon name="invoice" class="w-4 h-4" /> صدور پیش‌فاکتور از این درخواست
                        </x-button>
                    </div>
                @endif
            </x-card>

            <x-card title="تاریخچه پیگیری (CRM)" icon="clock">
                @if ($activities->isEmpty())
                    <x-empty-state icon="clock" title="هنوز فعالیتی ثبت نشده." />
                @else
                    <div class="space-y-3">
                        @foreach ($activities as $a)
                            <div class="border-e-[3px] border-brand-100 py-1 pe-3.5 dark:border-brand-500/30">
                                <div class="text-sm">{{ $a->note }}</div>
                                <div class="mt-0.5 text-xs text-ink-400">{{ $a->adminUser?->username ?? 'سیستم' }} — {{ $a->created_at->format('Y-m-d H:i') }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.requests.status', $lead) }}" class="mt-5 space-y-3 border-t border-ink-100 pt-5 dark:border-white/10">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">تغییر وضعیت پیگیری</label>
                        <select name="follow_up_status" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                            <option value="">— بدون تغییر —</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s }}" @selected($lead->follow_up_status === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">تاریخ تماس بعدی</label>
                        <input type="date" name="next_call_date" value="{{ $lead->next_call_date?->format('Y-m-d') }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">یادداشت پیگیری (اختیاری)</label>
                        <textarea name="note" rows="3" placeholder="مثلاً: با مشتری تماس گرفته شد، قرار است فردا پاسخ بدهد..." class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></textarea>
                    </div>
                    <x-button type="submit">ثبت به‌روزرسانی</x-button>
                </form>
            </x-card>
        </div>

        <div class="space-y-5">
            <x-card title="مشخصات مشتری" icon="user">
                <form method="POST" action="{{ route('admin.requests.temperature', $lead) }}" class="mb-4 flex flex-wrap items-center gap-2">
                    @csrf
                    <span class="text-sm font-bold">دمای سرنخ:</span>
                    @php $curTemp = $lead->temperature ?: 'warm'; @endphp
                    @foreach (['hot' => ['داغ 🔴', '#DC2626'], 'warm' => ['معمولی 🟠', '#D97706'], 'cold' => ['سرد 🔵', '#2563EB']] as $tk => $tv)
                        <button type="submit" name="temperature" value="{{ $tk }}"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold text-white"
                            style="background: {{ $curTemp === $tk ? $tv[1] : '#9CA3AF' }}">{{ $tv[0] }}</button>
                    @endforeach
                </form>
                <table class="w-full text-sm">
                    <tbody>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">نام</td><td class="px-2 py-2 font-extrabold">{{ $lead->name }}</td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">شماره تماس</td><td class="num-font px-2 py-2">{{ $lead->phone }}</td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">ایمیل</td><td class="px-2 py-2">{{ $lead->email ?: '-' }}</td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">خودرو</td><td class="px-2 py-2">{{ $lead->car_label }}</td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">دسته</td><td class="px-2 py-2"><x-badge>{{ $lead->category }}</x-badge></td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">بودجه تقریبی</td><td class="px-2 py-2">{{ $lead->budget_range ?: '-' }}</td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">منبع</td><td class="px-2 py-2"><x-badge>{{ $lead->source ?: 'سایت' }}</x-badge></td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">موقعیت</td><td class="px-2 py-2">{{ trim(($lead->city ?: '').(($lead->city && $lead->country) ? '، ' : '').($lead->country ?: '')) ?: 'نامشخص' }}</td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">تماس بعدی</td><td class="px-2 py-2">{{ $lead->next_call_date?->format('Y-m-d') ?? '-' }}</td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">ثبت‌کننده</td><td class="px-2 py-2">{{ $lead->creator?->displayName() ?? '-' }}</td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">تاریخ ثبت</td><td class="px-2 py-2 text-xs">{{ $lead->created_at }}</td></tr>
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="px-2 py-2 text-ink-500">وضعیت ایمیل</td><td class="px-2 py-2"><x-badge :color="$lead->email_sent ? 'green' : 'red'">{{ $lead->email_sent ? 'ارسال شد' : 'نامشخص/ناموفق' }}</x-badge></td></tr>
                        <tr><td class="px-2 py-2 text-ink-500">IP</td><td class="num-font px-2 py-2 text-xs">{{ $lead->ip_address }}</td></tr>
                    </tbody>
                </table>
                @if ($lead->notes)
                    <div class="mt-3 rounded-xl bg-ink-50 p-3.5 text-sm dark:bg-white/5"><b>توضیحات مشتری:</b><br>{{ $lead->notes }}</div>
                @endif
            </x-card>

            @if (auth()->user()->isAdmin())
                <x-card title="الحاق به کارشناس (فقط مدیر)" icon="users">
                    <form method="POST" action="{{ route('admin.requests.assign', $lead) }}" class="space-y-3">
                        @csrf
                        <select name="assigned_to" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                            <option value="">— بدون الحاق —</option>
                            @foreach ($staffList as $s)
                                <option value="{{ $s->id }}" @selected($lead->assigned_to === $s->id)>{{ $s->displayName() }} ({{ $s->username }})</option>
                            @endforeach
                        </select>
                        <x-button type="submit" variant="amber">ثبت الحاق</x-button>
                    </form>
                </x-card>

                <x-card title="حذف درخواست" icon="trash" class="border-red-200 dark:border-red-900/30">
                    <p class="mb-3 text-sm text-ink-600 dark:text-ink-400">حذف درخواست از سیستم قابل بازگشت است.</p>
                    <form method="POST" action="{{ route('admin.requests.destroy', $lead) }}" x-data @submit="!confirm('آیا از حذف این درخواست اطمینان دارید؟') && $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger">حذف درخواست</x-button>
                    </form>
                </x-card>
            @else
                <x-card title="الحاق" icon="users">
                    <p class="text-sm text-ink-500">این درخواست به شما الحاق شده است. برای تغییر الحاق، با مدیر سیستم هماهنگ کنید.</p>
                </x-card>
            @endif

            <div x-data="templatePicker" class="rounded-2xl bg-gradient-to-br from-emerald-950 to-ink-950 p-5 text-white shadow-soft-lg sm:p-6">
                <h2 class="mb-1 flex items-center gap-2 text-base font-extrabold"><x-icon name="message" class="w-5 h-5" /> قالب‌های پیام آماده</h2>
                <p class="mb-3 text-xs text-emerald-200">یکی را انتخاب کنید تا متن با اطلاعات همین سرنخ پر شود، سپس «کپی متن» را بزنید و در واتساپ/تلگرام/بله بفرستید.</p>
                <select x-model="selected" @change="render" dir="rtl" class="mb-2.5 w-full rounded-xl border border-emerald-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-ink-950 shadow-sm outline-none transition-colors hover:border-emerald-300 focus:border-amber-400 focus:ring-2 focus:ring-amber-300">
                    <option value="">— انتخاب قالب —</option>
                    @foreach ($templates as $t)
                        <option value="{{ $t->id }}">{{ $t->title }}</option>
                    @endforeach
                </select>
                <textarea x-model="preview" readonly rows="6" class="mb-2.5 w-full rounded-xl border border-white/20 bg-white/10 px-3.5 py-3 text-sm text-white"></textarea>
                <button type="button" @click="copy" class="w-full rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 py-2.5 text-sm font-extrabold text-ink-950 shadow-glow-amber">📋 کپی متن</button>
                @if ($templates->isEmpty())
                    <p class="mt-2 text-xs text-emerald-200/70">هنوز قالبی تعریف نشده — از منوی «قالب‌های پیام» بسازید.</p>
                @endif
            </div>
        </div>
    </div>

    <script>
        function templatePicker() {
            return {
                selected: '', preview: '',
                templates: @json($templates->map(fn ($t) => ['id' => $t->id, 'body' => $t->body])),
                vars: {
                    customer_name: @json($lead->name),
                    phone: @json($lead->phone),
                    car_model: @json($lead->car_label ?: 'خودروی مدنظر'),
                    total_price: @json($lead->total_with_profit ? number_format($lead->total_with_profit) : 'برآورد اولیه'),
                    salesperson_name: @json(auth()->user()->displayName()),
                    official_channels: 'navracar.com — واتساپ +971505158484',
                    company_name: 'ناوراکار',
                },
                render() {
                    const t = this.templates.find(x => x.id == this.selected);
                    this.preview = t ? t.body.replace(/\{\{(\w+)\}\}/g, (m, key) => this.vars[key] !== undefined ? this.vars[key] : m) : '';
                },
                async copy() {
                    if (!this.preview) { window.pushToast('اول یک قالب انتخاب کنید.', 'error'); return; }
                    try {
                        await navigator.clipboard.writeText(this.preview);
                        window.pushToast('متن با موفقیت کپی شد', 'success');
                        if (this.selected) {
                            fetch('{{ route('admin.template-use') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                                body: JSON.stringify({ leadId: {{ $lead->id }}, templateId: this.selected }),
                            }).catch(() => {});
                        }
                    } catch (e) { window.pushToast('کپی خودکار پشتیبانی نشد — متن را دستی انتخاب و کپی کنید.', 'error'); }
                },
            };
        }
    </script>
</x-layouts.admin>
