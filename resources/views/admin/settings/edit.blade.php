<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$pageSubtitle">
    <x-card title="نرخ ارز" icon="target" class="max-w-lg">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-bold text-ink-500">نرخ ارز آزاد (تومان به ازای هر درهم)</label>
                <input type="number" step="1" name="free_rate" value="{{ old('free_rate', $freeRate) }}" required
                       class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                <p class="mt-1 text-[11px] text-ink-400">همین عدد به‌عنوان «نرخ درهم امروز» به مشتری در صفحات قیمت خودروها نمایش داده می‌شود.</p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-ink-500">نرخ ارز گمرک (تومان به ازای هر درهم)</label>
                <input type="number" step="1" name="customs_rate" value="{{ old('customs_rate', $customsRate) }}" required
                       class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
            </div>
            <x-button type="submit" variant="amber">
                <x-icon name="check" class="w-4 h-4" /> ذخیره نرخ‌ها
            </x-button>
        </form>
    </x-card>
</x-layouts.admin>
