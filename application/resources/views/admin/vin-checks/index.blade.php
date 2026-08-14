<x-layouts.admin :page-title="$pageTitle" page-subtitle="مجموع {{ $rows->total() }} استعلام — از این تعداد {{ number_format($usCount) }} مورد ساخت/وارداتی آمریکا تشخیص داده شده (غیرمجاز).">

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
            <x-button type="submit" size="sm">اعمال فیلتر</x-button>
            <x-button :href="route('admin.vin-checks.index')" variant="secondary" size="sm">پاک کردن</x-button>
        </form>

        @if ($rows->isEmpty())
            <x-empty-state icon="vin" title="هنوز استعلامی ثبت نشده." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-ink-100 text-xs font-extrabold text-ink-400 dark:border-white/10 dark:text-ink-500">
                            <th class="px-2.5 py-2 text-start">تاریخ</th><th class="px-2.5 py-2 text-start">VIN</th><th class="px-2.5 py-2 text-start">برند</th>
                            <th class="px-2.5 py-2 text-start">مدل</th><th class="px-2.5 py-2 text-start">سال</th><th class="px-2.5 py-2 text-start">کشور</th>
                            <th class="px-2.5 py-2 text-start">نتیجه</th><th class="px-2.5 py-2 text-start">موقعیت کاربر</th><th class="px-2.5 py-2 text-start">منبع تشخیص</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr class="border-b border-ink-100 hover:bg-ink-50 dark:border-white/5 dark:hover:bg-white/5">
                                <td class="px-2.5 py-2.5 text-xs text-ink-500">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                                <td class="num-font px-2.5 py-2.5 text-xs">{{ $r->vin }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->make ?: '-' }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->model ?: '-' }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->model_year ?: '-' }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->plant_country ?: '-' }}</td>
                                <td class="px-2.5 py-2.5">
                                    <x-badge :color="str_contains($r->verdict ?? '', 'غیرمجاز') ? 'red' : ($r->verdict === 'مجاز' ? 'green' : 'slate')">{{ $r->verdict }}</x-badge>
                                </td>
                                <td class="px-2.5 py-2.5 text-xs">{{ trim(($r->city ?: '').(($r->city && $r->country) ? '، ' : '').($r->country ?: '')) ?: '-' }}</td>
                                <td class="px-2.5 py-2.5 text-xs">{{ $r->source === 'nhtsa' ? 'NHTSA' : 'کد شاسی (تخمینی)' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $rows->links() }}</div>
        @endif
    </x-card>
</x-layouts.admin>
