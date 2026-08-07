<x-layouts.admin :page-title="$pageTitle" page-subtitle="همه رویدادها و خطاهای مهم سیستم اینجا ثبت می‌شوند — ۳۰۰ رکورد آخر.">

    <x-card>
        <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-ink-500 dark:text-ink-400">جستجو در متن</label>
                <input type="text" name="q" value="{{ $search }}" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-ink-500 dark:text-ink-400">سطح</label>
                <select name="level" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                    <option value="">همه</option>
                    <option value="error" @selected($filter === 'error')>فقط خطاها</option>
                    <option value="info" @selected($filter === 'info')>فقط اطلاعاتی</option>
                </select>
            </div>
            <x-button type="submit" size="sm">اعمال فیلتر</x-button>
            <x-button :href="route('admin.activity-log.index')" variant="secondary" size="sm">پاک کردن</x-button>
        </form>

        @if ($lines->isEmpty())
            <x-empty-state icon="terminal" title="هنوز رویدادی ثبت نشده." />
        @else
            <div class="space-y-1.5 font-mono text-xs">
                @foreach ($lines as $l)
                    @php $isErr = stripos($l, '[ERROR]') !== false; $isInfo = stripos($l, '[INFO]') !== false; @endphp
                    <div class="break-all rounded-lg p-2.5
                        {{ $isErr ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' : ($isInfo ? 'bg-brand-50 text-brand-800 dark:bg-brand-500/10 dark:text-brand-300' : 'bg-ink-50 dark:bg-white/5') }}">
                        {{ $l }}
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-layouts.admin>
