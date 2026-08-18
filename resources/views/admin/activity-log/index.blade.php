<x-layouts.admin :page-title="$pageTitle" page-subtitle="همه رویدادها و خطاهای مهم سیستم اینجا ثبت می‌شوند — ۳۰۰ رکورد آخر.">

    <x-card variant="v2">
        <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-v2-text-muted">جستجو در متن</label>
                <input type="text" name="q" value="{{ $search }}" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-v2-text-muted">سطح</label>
                <select name="level" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
                    <option value="">همه</option>
                    <option value="error" @selected($filter === 'error')>فقط خطاها</option>
                    <option value="info" @selected($filter === 'info')>فقط اطلاعاتی</option>
                </select>
            </div>
            <x-button type="submit" size="sm" variant="v2-primary">اعمال فیلتر</x-button>
            <x-button :href="route('admin.activity-log.index')" variant="v2-secondary" size="sm">پاک کردن</x-button>
        </form>

        @if ($lines->isEmpty())
            <x-empty-state variant="v2" icon="terminal" title="هنوز رویدادی ثبت نشده." />
        @else
            <div class="space-y-1.5 font-mono text-xs">
                @foreach ($lines as $l)
                    @php $isErr = stripos($l, '[ERROR]') !== false; $isInfo = stripos($l, '[INFO]') !== false; @endphp
                    <div class="break-all rounded-lg p-2.5
                        {{ $isErr ? 'bg-v2-error/15 text-v2-error' : ($isInfo ? 'bg-v2-primary/15 text-v2-primary' : 'bg-v2-elevated text-v2-text-muted') }}">
                        {{ $l }}
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-layouts.admin>
