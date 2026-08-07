<x-layouts.admin :page-title="$pageTitle" page-subtitle="مجموع {{ $rows->total() }} محاسبه — شامل بازدیدکنندگانی که مشخصات تماس ثبت نکرده‌اند.">

    <x-card>
        <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-ink-500 dark:text-ink-400">از تاریخ</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-ink-500 dark:text-ink-400">تا تاریخ</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-ink-500 dark:text-ink-400">دسته خودرو</label>
                <select name="cat" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                    <option value="">همه</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c }}" @selected(($filters['cat'] ?? '') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <x-button type="submit" size="sm">اعمال فیلتر</x-button>
            <x-button :href="route('admin.calculations.index')" variant="secondary" size="sm">پاک کردن</x-button>
            <x-button :href="route('admin.export', array_merge(['type' => 'calculations'], $filters))" variant="amber" size="sm">خروجی اکسل همین لیست</x-button>
        </form>

        @if ($rows->isEmpty())
            <x-empty-state icon="calculator" title="محاسبه‌ای با این فیلتر یافت نشد." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-ink-100 text-xs font-extrabold text-ink-400 dark:border-white/10 dark:text-ink-500">
                            <th class="px-2.5 py-2 text-start">تاریخ</th><th class="px-2.5 py-2 text-start">خودرو</th><th class="px-2.5 py-2 text-start">دسته</th>
                            <th class="px-2.5 py-2 text-start">موقعیت</th><th class="px-2.5 py-2 text-start">جمع بدون سود</th>
                            <th class="px-2.5 py-2 text-start">سود خدمات</th><th class="px-2.5 py-2 text-start">جمع کل نهایی</th><th class="px-2.5 py-2 text-start">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr class="border-b border-ink-100 hover:bg-ink-50 dark:border-white/5 dark:hover:bg-white/5">
                                <td class="px-2.5 py-2.5 text-xs text-ink-500">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->car_label ?: '-' }}</td>
                                <td class="px-2.5 py-2.5"><x-badge>{{ $r->category }}</x-badge></td>
                                <td class="px-2.5 py-2.5 text-xs">{{ trim(($r->city ?: '').(($r->city && $r->country) ? '، ' : '').($r->country ?: '')) ?: '-' }}</td>
                                <td class="num-font px-2.5 py-2.5">{{ number_format($r->total_no_profit) }}</td>
                                <td class="num-font px-2.5 py-2.5">{{ number_format($r->service_profit) }}</td>
                                <td class="num-font px-2.5 py-2.5 font-extrabold text-brand-700 dark:text-brand-300">{{ number_format($r->total_with_profit) }}</td>
                                <td class="px-2.5 py-2.5 text-xs text-ink-400">{{ $r->ip_address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $rows->links() }}</div>
        @endif
    </x-card>
</x-layouts.admin>
