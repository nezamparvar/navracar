<x-layouts.admin :page-title="$pageTitle" page-subtitle="مجموع {{ $rows->total() }} استعلام — از این تعداد {{ number_format($usCount) }} مورد ساخت/وارداتی آمریکا تشخیص داده شده (غیرمجاز).">

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
            <x-button type="submit" size="sm" variant="v2-primary">اعمال فیلتر</x-button>
            <x-button :href="route('admin.vin-checks.index')" variant="v2-secondary" size="sm">پاک کردن</x-button>
        </form>

        @if ($rows->isEmpty())
            <x-empty-state variant="v2" icon="vin" title="هنوز استعلامی ثبت نشده." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-v2-border text-xs font-extrabold text-v2-text-muted">
                            <th class="px-2.5 py-2 text-start">تاریخ</th><th class="px-2.5 py-2 text-start">VIN</th><th class="px-2.5 py-2 text-start">برند</th>
                            <th class="px-2.5 py-2 text-start">مدل</th><th class="px-2.5 py-2 text-start">سال</th><th class="px-2.5 py-2 text-start">کشور</th>
                            <th class="px-2.5 py-2 text-start">نتیجه</th><th class="px-2.5 py-2 text-start">موقعیت کاربر</th><th class="px-2.5 py-2 text-start">منبع تشخیص</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr class="border-b border-v2-border text-v2-text hover:bg-v2-elevated">
                                <td class="px-2.5 py-2.5 text-xs text-v2-text-muted">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                                <td class="num-font px-2.5 py-2.5 text-xs">{{ $r->vin }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->make ?: '-' }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->model ?: '-' }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->model_year ?: '-' }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->plant_country ?: '-' }}</td>
                                <td class="px-2.5 py-2.5">
                                    <x-badge :color="str_contains($r->verdict ?? '', 'غیرمجاز') ? 'v2-error' : ($r->verdict === 'مجاز' ? 'v2-success' : 'v2-neutral')">{{ $r->verdict }}</x-badge>
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
