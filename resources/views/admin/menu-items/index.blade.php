<x-layouts.admin :page-title="$pageTitle" page-subtitle="این آیتم‌ها در هدر و فوتر تمام صفحات سایت (به‌جز صفحه محاسبه‌گر) نمایش داده می‌شوند.">

    <x-card title="افزودن آیتم منو" icon="plus" class="mb-5">
        <form method="POST" action="{{ route('admin.menu-items.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="min-w-[160px] flex-1">
                <label class="mb-1 block text-xs font-bold text-ink-500">عنوان</label>
                <input type="text" name="label" required placeholder="مثلاً: وبلاگ" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
            </div>
            <div class="min-w-[220px] flex-1">
                <label class="mb-1 block text-xs font-bold text-ink-500">لینک</label>
                <input type="text" name="url" required dir="ltr" placeholder="/blog یا https://..." class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm text-left dark:border-white/10 dark:bg-white/5">
            </div>
            <div class="w-24">
                <label class="mb-1 block text-xs font-bold text-ink-500">ترتیب</label>
                <input type="number" name="sort_order" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
            </div>
            <label class="flex items-center gap-1.5 pb-2.5 text-xs font-bold text-ink-500">
                <input type="checkbox" name="opens_new_tab" value="1"> تب جدید
            </label>
            <x-button type="submit" variant="amber">افزودن</x-button>
        </form>
    </x-card>

    <x-card title="آیتم‌های منو ({{ $items->count() }})" icon="menu">
        @if ($items->isEmpty())
            <x-empty-state icon="menu" title="هنوز آیتمی اضافه نشده." />
        @else
            <div class="space-y-2">
                @foreach ($items as $item)
                    <div class="flex items-center gap-3 rounded-xl border border-ink-200/70 p-3 {{ $item->is_active ? '' : 'opacity-50' }} dark:border-white/10">
                        <div class="min-w-0 flex-1">
                            <span class="font-extrabold">{{ $item->label }}</span>
                            <span class="ms-2 text-xs text-ink-400 num-font" dir="ltr">{{ $item->url }}</span>
                        </div>
                        <form method="POST" action="{{ route('admin.menu-items.toggle', $item) }}">
                            @csrf
                            <x-button type="submit" size="sm" variant="secondary">{{ $item->is_active ? 'غیرفعال' : 'فعال' }}</x-button>
                        </form>
                        <form method="POST" action="{{ route('admin.menu-items.destroy', $item) }}" onsubmit="return confirm('حذف این آیتم؟');">
                            @csrf @method('DELETE')
                            <x-button type="submit" size="sm" variant="danger">حذف</x-button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-layouts.admin>
