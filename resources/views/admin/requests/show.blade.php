@php
    $breakdown = $lead->breakdownForDisplay();
    $totals = $lead->totals();
@endphp
<x-layouts.admin :page-title="$pageTitle">

    <p class="mb-5 text-sm"><a href="{{ route('admin.requests.index') }}" class="font-bold text-v2-primary">← بازگشت به لیست</a></p>

    <div class="grid gap-5 lg:grid-cols-[1.3fr_.7fr]">
        <div class="space-y-5">
            <x-card variant="v2" title="تفکیک هزینه‌های ارسال‌شده" icon="calculator">
                @if (empty($breakdown))
                    <x-empty-state icon="calculator" title="برآورد هزینه‌ای برای این درخواست ثبت نشده است." variant="v2" />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b-2 border-v2-border text-xs font-extrabold text-v2-text-muted">
                                    <th class="px-2.5 py-2 text-start">شرح</th><th class="px-2.5 py-2 text-start">نرخ</th><th class="px-2.5 py-2 text-start">مبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($breakdown as $row)
                                    <tr class="border-b border-v2-border">
                                        <td class="px-2.5 py-2">{{ $row['label'] ?? '' }}</td>
                                        <td class="px-2.5 py-2 text-xs text-v2-text-muted">{{ $row['rate'] ?? '' }}</td>
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
                                    <tr class="border-b border-dashed border-v2-border">
                                        <td class="px-2.5 py-2 font-extrabold">{{ $label }}</td>
                                        <td class="num-font px-2.5 py-2 font-extrabold text-v2-primary">{{ $val }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    <div class="mt-4">
                        <x-button :href="route('admin.invoices.create', ['request_id' => $lead->id])" variant="v2-primary">
                            <x-icon name="invoice" class="w-4 h-4" /> صدور پیش‌فاکتور از این درخواست
                        </x-button>
                    </div>
                @endif
            </x-card>

            <x-card variant="v2" title="جدول زمانی مکالمات" icon="clock">
                <div class="mb-5 space-y-4">
                    @if ($activities->isEmpty())
                        <x-empty-state icon="clock" title="هنوز فعالیتی ثبت نشده." variant="v2" />
                    @else
                        @foreach ($activities as $a)
                            @php
                                $typeIcons = [
                                    'note' => '📝',
                                    'status_change' => '🔄',
                                    'assign' => '👤',
                                ];
                                $typeLabels = [
                                    'note' => 'یادداشت',
                                    'status_change' => 'تغییر وضعیت',
                                    'assign' => 'الحاق',
                                ];
                            @endphp
                            <div class="border-e-4 border-v2-primary/30 bg-v2-elevated p-3.5">
                                <div class="mb-1.5 flex items-center gap-2">
                                    <span class="text-sm">{{ $typeIcons[$a->activity_type] ?? '•' }}</span>
                                    <span class="text-xs font-bold text-v2-primary">{{ $typeLabels[$a->activity_type] ?? 'سایر' }}</span>
                                    <span class="text-xs text-v2-text-muted">{{ $a->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                                <div class="mb-1.5 text-sm text-v2-text">{{ $a->note }}</div>
                                <div class="text-xs text-v2-text-muted">{{ $a->adminUser?->displayName() ?? 'سیستم' }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.requests.status', $lead) }}" class="space-y-3 border-t border-v2-border pt-5">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">تغییر وضعیت پیگیری</label>
                        <select name="follow_up_status" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">
                            <option value="">— بدون تغییر —</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s }}" @selected($lead->follow_up_status === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">تاریخ تماس بعدی</label>
                        <input type="date" name="next_call_date" value="{{ $lead->next_call_date?->format('Y-m-d') }}" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">یادداشت پیگیری (اختیاری)</label>
                        <textarea name="note" rows="3" placeholder="مثلاً: با مشتری تماس گرفته شد، قرار است فردا پاسخ بدهد..." class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text"></textarea>
                    </div>
                    <x-button type="submit" variant="v2-primary">ثبت به‌روزرسانی</x-button>
                </form>
            </x-card>
        </div>

        <div class="space-y-5">
            <x-card variant="v2" title="مشخصات مشتری" icon="user">
                <form method="POST" action="{{ route('admin.requests.temperature', $lead) }}" class="mb-4 flex flex-wrap items-center gap-2">
                    @csrf
                    <span class="text-sm font-bold">دمای سرنخ:</span>
                    @php $curTemp = $lead->temperature ?: 'warm'; @endphp
                    @foreach (['hot' => ['داغ 🔴', '#EF4444'], 'warm' => ['معمولی 🟠', '#EAB308'], 'cold' => ['سرد 🔵', '#20C7E9']] as $tk => $tv)
                        <button type="submit" name="temperature" value="{{ $tk }}"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold text-white"
                            style="background: {{ $curTemp === $tk ? $tv[1] : '#1A3554' }}">{{ $tv[0] }}</button>
                    @endforeach
                </form>
                <table class="w-full text-sm">
                    <tbody>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">نام</td><td class="px-2 py-2 font-extrabold">{{ $lead->name }}</td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">شماره تماس</td><td class="num-font px-2 py-2">{{ $lead->phone }}</td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">ایمیل</td><td class="px-2 py-2">{{ $lead->email ?: '-' }}</td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">خودرو</td><td class="px-2 py-2">{{ $lead->car_label }}</td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">دسته</td><td class="px-2 py-2"><x-badge color="v2-neutral">{{ $lead->category }}</x-badge></td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">بودجه تقریبی</td><td class="px-2 py-2">{{ $lead->budget_range ?: '-' }}</td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">منبع</td><td class="px-2 py-2"><x-badge color="v2-neutral">{{ $lead->source ?: 'سایت' }}</x-badge></td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">موقعیت</td><td class="px-2 py-2">{{ trim(($lead->city ?: '').(($lead->city && $lead->country) ? '، ' : '').($lead->country ?: '')) ?: 'نامشخص' }}</td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">تماس بعدی</td><td class="px-2 py-2">{{ $lead->next_call_date?->format('Y-m-d') ?? '-' }}</td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">ثبت‌کننده</td><td class="px-2 py-2">{{ $lead->creator?->displayName() ?? '-' }}</td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">تاریخ ثبت</td><td class="px-2 py-2 text-xs">{{ $lead->created_at }}</td></tr>
                        <tr class="border-b border-v2-border"><td class="px-2 py-2 text-v2-text-muted">وضعیت ایمیل</td><td class="px-2 py-2"><x-badge :color="$lead->email_sent ? 'v2-success' : 'v2-error'">{{ $lead->email_sent ? 'ارسال شد' : 'نامشخص/ناموفق' }}</x-badge></td></tr>
                        <tr><td class="px-2 py-2 text-v2-text-muted">IP</td><td class="num-font px-2 py-2 text-xs">{{ $lead->ip_address }}</td></tr>
                    </tbody>
                </table>
                @if ($lead->notes)
                    <div class="mt-3 rounded-xl bg-v2-elevated p-3.5 text-sm"><b>توضیحات مشتری:</b><br>{{ $lead->notes }}</div>
                @endif
            </x-card>

            <x-card variant="v2" title="بستن درخواست" icon="x-circle" class="border-v2-warning/30">
                <p class="mb-3 text-sm text-v2-text-muted">درخواست را به عنوان موفق یا ناموفق بسته کنید.</p>
                <form method="POST" action="{{ route('admin.requests.close', $lead) }}" class="flex flex-wrap gap-2">
                    @csrf
                    <input type="hidden" name="status" id="close-status" value="">
                    <x-button type="submit" onclick="document.getElementById('close-status').value = 'بسته - موفق'" variant="v2-primary" size="sm">✓ بسته - موفق</x-button>
                    <x-button type="submit" onclick="document.getElementById('close-status').value = 'بسته - ناموفق'" variant="v2-danger" size="sm">✕ بسته - ناموفق</x-button>
                </form>
            </x-card>

            <x-card variant="v2" title="{{ $lead->is_archived ? 'درخواست بایگانی شده' : 'بایگانی درخواست' }}" icon="archive" class="border-v2-border">
                <p class="mb-3 text-sm text-v2-text-muted">{{ $lead->is_archived ? 'این درخواست بایگانی شده است.' : 'درخواست را بایگانی کنید تا از لیست اصلی پنهان شود.' }}</p>
                @if ($lead->is_archived)
                    <form method="POST" action="{{ route('admin.requests.unarchive', $lead) }}">
                        @csrf
                        <x-button type="submit" variant="v2-secondary" size="sm">خارج کردن از بایگانی</x-button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.requests.archive', $lead) }}" x-data @submit="!confirm('این درخواست بایگانی شود و از لیست اصلی پنهان خواهد شد.') && $event.preventDefault()">
                        @csrf
                        <x-button type="submit" variant="v2-secondary" size="sm">بایگانی درخواست</x-button>
                    </form>
                @endif
            </x-card>

            @if (auth()->user()->isAdmin())
                <x-card variant="v2" title="الحاق به کارشناس (فقط مدیر)" icon="users">
                    <form method="POST" action="{{ route('admin.requests.assign', $lead) }}" class="space-y-3">
                        @csrf
                        <select name="assigned_to" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">
                            <option value="">— بدون الحاق —</option>
                            @foreach ($staffList as $s)
                                <option value="{{ $s->id }}" @selected($lead->assigned_to === $s->id)>{{ $s->displayName() }} ({{ $s->username }})</option>
                            @endforeach
                        </select>
                        <x-button type="submit" variant="v2-primary">ثبت الحاق</x-button>
                    </form>
                </x-card>

                <x-card variant="v2" title="حذف درخواست" icon="trash" class="border-v2-error/30">
                    <p class="mb-3 text-sm text-v2-text-muted">حذف درخواست از سیستم قابل بازگشت است.</p>
                    <form method="POST" action="{{ route('admin.requests.destroy', $lead) }}" x-data @submit="!confirm('آیا از حذف این درخواست اطمینان دارید؟') && $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="v2-danger">حذف درخواست</x-button>
                    </form>
                </x-card>
            @else
                <x-card variant="v2" title="الحاق" icon="users">
                    <p class="text-sm text-v2-text-muted">این درخواست به شما الحاق شده است. برای تغییر الحاق، با مدیر سیستم هماهنگ کنید.</p>
                </x-card>
            @endif

            <div x-data="templatePicker" class="rounded-2xl border border-v2-primary/30 bg-v2-elevated p-5 text-v2-text shadow-soft-dark sm:p-6">
                <h2 class="mb-1 flex items-center gap-2 text-base font-extrabold"><x-icon name="message" class="w-5 h-5" /> قالب‌های پیام آماده</h2>
                <p class="mb-3 text-xs text-v2-text-muted">یکی را انتخاب کنید تا متن با اطلاعات همین سرنخ پر شود، سپس «کپی متن» را بزنید و در واتساپ/تلگرام/بله بفرستید.</p>
                <select x-model="selected" @change="render" dir="rtl" class="mb-2.5 w-full rounded-xl border border-v2-border bg-v2-surface px-3.5 py-2.5 text-sm font-semibold text-v2-text shadow-sm outline-none transition-colors hover:border-v2-primary/60 focus:border-v2-primary focus:ring-2 focus:ring-v2-primary/30">
                    <option value="">— انتخاب قالب —</option>
                    @foreach ($templates as $t)
                        <option value="{{ $t->id }}">{{ $t->title }}</option>
                    @endforeach
                </select>
                <textarea x-model="preview" readonly rows="6" class="mb-2.5 w-full rounded-xl border border-v2-border bg-v2-surface px-3.5 py-3 text-sm text-v2-text"></textarea>
                <button type="button" @click="copy" class="w-full rounded-xl bg-v2-primary py-2.5 text-sm font-extrabold text-white shadow-glow-v2 hover:brightness-110">📋 کپی متن</button>
                @if ($templates->isEmpty())
                    <p class="mt-2 text-xs text-v2-text-muted">هنوز قالبی تعریف نشده — از منوی «قالب‌های پیام» بسازید.</p>
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
