<x-layouts.admin :page-title="$pageTitle">

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-500 dark:text-ink-400">
            مجموع {{ $rows->total() }} درخواست{{ auth()->user()->isAdmin() ? '' : ' الحاق‌شده به شما' }}.
        </p>
        <div class="flex flex-wrap gap-2">
            <x-button :href="route('admin.requests.create')" variant="amber" size="sm"><x-icon name="plus" class="w-4 h-4" /> ثبت دستی مشتری تماس‌گرفته</x-button>
            <x-button :href="route('public.lead-form')" variant="secondary" size="sm" target="_blank"><x-icon name="external-link" class="w-4 h-4" /> لینک فرم عمومی فروشنده‌ها</x-button>
        </div>
    </div>

    <x-card>
        <form method="GET" class="mb-5 space-y-3">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-ink-500 dark:text-ink-400">نام</label>
                    <input type="text" name="name" value="{{ $filters['name'] ?? '' }}" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-ink-500 dark:text-ink-400">تلفن</label>
                    <input type="text" name="phone" value="{{ $filters['phone'] ?? '' }}" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-ink-500 dark:text-ink-400">ایمیل</label>
                    <input type="text" name="email" value="{{ $filters['email'] ?? '' }}" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-ink-500 dark:text-ink-400">خودرو</label>
                    <input type="text" name="car_label" value="{{ $filters['car_label'] ?? '' }}" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-ink-500 dark:text-ink-400">از تاریخ</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-ink-500 dark:text-ink-400">تا تاریخ</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-ink-500 dark:text-ink-400">وضعیت پیگیری</label>
                    <select name="status" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                        <option value="">همه</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-ink-500 dark:text-ink-400">مرحله خط لوله</label>
                    <select name="stage" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                        <option value="">همه</option>
                        @foreach ($pipelineStages as $ps)
                            <option value="{{ $ps->id }}" @selected((string) ($filters['stage'] ?? '') === (string) $ps->id)>{{ $ps->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (auth()->user()->isAdmin())
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-2">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-ink-500 dark:text-ink-400">الحاق‌شده به</label>
                        <select name="assigned" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                            <option value="all">همه</option>
                            <option value="unassigned" @selected(($filters['assigned'] ?? '') === 'unassigned')>بدون الحاق</option>
                            @foreach ($staffList as $s)
                                <option value="{{ $s->id }}" @selected((string) ($filters['assigned'] ?? '') === (string) $s->id)>{{ $s->displayName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-ink-500 dark:text-ink-400">&nbsp;</label>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="show_all" value="1" id="show_all" @checked(($filters['show_all'] ?? false))>
                            <label for="show_all" class="text-sm">نمایش تمام درخواست‌ها</label>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                <x-button type="submit" size="sm">اعمال فیلتر</x-button>
                <x-button :href="route('admin.requests.index')" variant="secondary" size="sm">پاک کردن</x-button>
                <x-button :href="route('admin.export', array_merge(['type' => 'requests'], $filters))" variant="amber" size="sm">خروجی اکسل</x-button>
            </div>
        </form>

        @if ($rows->isEmpty())
            <x-empty-state icon="inbox" title="هیچ درخواستی با این فیلتر یافت نشد." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-ink-100 text-xs font-extrabold text-ink-400 dark:border-white/10 dark:text-ink-500">
                            <th class="whitespace-nowrap px-2.5 py-2 text-start">تاریخ</th>
                            <th class="whitespace-nowrap px-2.5 py-2 text-start">نام</th>
                            <th class="whitespace-nowrap px-2.5 py-2 text-start">تلفن</th>
                            <th class="whitespace-nowrap px-2.5 py-2 text-start">خودرو</th>
                            <th class="whitespace-nowrap px-2.5 py-2 text-start">بودجه</th>
                            <th class="whitespace-nowrap px-2.5 py-2 text-start">منبع</th>
                            <th class="whitespace-nowrap px-2.5 py-2 text-start">موقعیت</th>
                            <th class="whitespace-nowrap px-2.5 py-2 text-start">الحاق به</th>
                            <th class="whitespace-nowrap px-2.5 py-2 text-start">وضعیت</th>
                            <th class="whitespace-nowrap px-2.5 py-2 text-start">تماس بعدی</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr class="border-b border-ink-100 hover:bg-ink-50 dark:border-white/5 dark:hover:bg-white/5">
                                <td class="whitespace-nowrap px-2.5 py-2.5 text-xs text-ink-500">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-2.5 py-2.5 font-semibold">{{ $r->name }}</td>
                                <td class="num-font px-2.5 py-2.5">{{ $r->phone }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->car_label }}</td>
                                <td class="px-2.5 py-2.5 text-xs">{{ $r->budget_range ?: '-' }}</td>
                                <td class="px-2.5 py-2.5"><x-badge>{{ $r->source ?: 'سایت' }}</x-badge></td>
                                <td class="px-2.5 py-2.5 text-xs">{{ trim(($r->city ?: '').(($r->city && $r->country) ? '، ' : '').($r->country ?: '')) ?: '-' }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->assignee?->displayName() ?? '—' }}</td>
                                <td class="px-2.5 py-2.5">
                                    <x-badge :color="$r->follow_up_status === 'فروخته شد' ? 'green' : ($r->follow_up_status === 'بسته - ناموفق' ? 'red' : 'slate')">
                                        {{ $r->follow_up_status ?: 'باز' }}
                                    </x-badge>
                                </td>
                                <td class="px-2.5 py-2.5 text-xs">{{ $r->next_call_date?->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-2.5 py-2.5 space-x-1">
                                    <x-button :href="route('admin.requests.show', $r)" size="sm" variant="secondary">جزئیات</x-button>
                                    @if (in_array($r->follow_up_status, ['باز', 'در حال پیگیری']))
                                        <div x-data="{ open: false }" class="inline-block">
                                            <button @click="open = true" class="rounded-lg border border-ink-200 bg-ink-50 px-2 py-1 text-xs font-semibold text-ink-700 hover:bg-ink-100 dark:border-white/10 dark:bg-white/5 dark:text-ink-200 dark:hover:bg-white/10">بستن</button>
                                            <div x-show="open" @click.outside="open = false" class="absolute z-50 mt-2 space-y-2 rounded-xl border border-ink-200 bg-white p-3 shadow-lg dark:border-white/10 dark:bg-ink-900">
                                                <form method="POST" :action="'{{ route('admin.requests.close', '') }}/' + {{ $r->id }}" class="space-y-2">
                                                    @csrf
                                                    <button type="submit" name="status" value="بسته - موفق" class="block w-full rounded-lg bg-green-600 px-2 py-1.5 text-xs font-semibold text-white hover:bg-green-700">موفق</button>
                                                    <button type="submit" name="status" value="بسته - ناموفق" class="block w-full rounded-lg bg-red-600 px-2 py-1.5 text-xs font-semibold text-white hover:bg-red-700">ناموفق</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $rows->links() }}</div>
        @endif
    </x-card>
</x-layouts.admin>
