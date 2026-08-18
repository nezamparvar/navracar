<x-layouts.admin :page-title="$pageTitle" page-subtitle="مجموع {{ $rows->total() }} محاسبه — شامل بازدیدکنندگانی که مشخصات تماس ثبت نکرده‌اند.">

    <x-card variant="v2">
        <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-v2-text-muted">از تاریخ</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-v2-text-muted">تا تاریخ</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-v2-text-muted">دسته خودرو</label>
                <select name="cat" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
                    <option value="">همه</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c }}" @selected(($filters['cat'] ?? '') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <x-button type="submit" size="sm" variant="v2-primary">اعمال فیلتر</x-button>
            <x-button :href="route('admin.calculations.index')" variant="v2-secondary" size="sm">پاک کردن</x-button>
            <x-button :href="route('admin.export', array_merge(['type' => 'calculations'], $filters))" variant="v2-primary" size="sm">خروجی اکسل همین لیست</x-button>
        </form>

        @if ($rows->isEmpty())
            <x-empty-state variant="v2" icon="calculator" title="محاسبه‌ای با این فیلتر یافت نشد." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-v2-border text-xs font-extrabold text-v2-text-muted">
                            <th class="px-2.5 py-2 text-start">تاریخ</th><th class="px-2.5 py-2 text-start">خودرو</th><th class="px-2.5 py-2 text-start">دسته</th>
                            <th class="px-2.5 py-2 text-start">موقعیت</th><th class="px-2.5 py-2 text-start">جمع بدون کارمزد</th>
                            <th class="px-2.5 py-2 text-start">کارمزد ترخیص‌کار</th><th class="px-2.5 py-2 text-start">جمع کل نهایی</th><th class="px-2.5 py-2 text-start">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr class="border-b border-v2-border text-v2-text hover:bg-v2-elevated">
                                <td class="px-2.5 py-2.5 text-xs text-v2-text-muted">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->car_label ?: '-' }}</td>
                                <td class="px-2.5 py-2.5"><x-badge color="v2-neutral">{{ $r->category }}</x-badge></td>
                                <td class="px-2.5 py-2.5 text-xs">{{ trim(($r->city ?: '').(($r->city && $r->country) ? '، ' : '').($r->country ?: '')) ?: '-' }}</td>
                                <td class="num-font px-2.5 py-2.5">{{ number_format($r->total_no_profit) }}</td>
                                <td class="num-font px-2.5 py-2.5">{{ number_format($r->service_profit) }}</td>
                                <td class="num-font px-2.5 py-2.5 font-extrabold text-v2-primary">{{ number_format($r->total_with_profit) }}</td>
                                <td class="px-2.5 py-2.5 text-xs text-v2-text-muted">{{ $r->ip_address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $rows->links() }}</div>
        @endif
    </x-card>
</x-layouts.admin>
