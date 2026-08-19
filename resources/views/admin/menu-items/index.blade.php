<x-layouts.admin :page-title="$pageTitle" page-subtitle="این آیتم‌ها در هدر و فوتر تمام صفحات سایت (به‌جز صفحه محاسبه‌گر) نمایش داده می‌شوند.">

    <x-card title="افزودن آیتم منو" icon="plus" variant="v2" class="mb-5">
        <form method="POST" action="{{ route('admin.menu-items.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="min-w-[160px] flex-1">
                <label class="mb-1 block text-xs font-bold text-v2-text-muted">عنوان</label>
                <input type="text" name="label" required placeholder="مثلاً: وبلاگ" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">
            </div>
            <div class="min-w-[220px] flex-1">
                <label class="mb-1 block text-xs font-bold text-v2-text-muted">لینک</label>
                <input type="text" name="url" required dir="ltr" placeholder="/blog یا https://..." class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-left text-v2-text">
            </div>
            <div class="w-24">
                <label class="mb-1 block text-xs font-bold text-v2-text-muted">ترتیب</label>
                <input type="number" name="sort_order" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3 py-2.5 text-sm num-font text-v2-text">
            </div>
            <label class="flex items-center gap-1.5 pb-2.5 text-xs font-bold text-v2-text-muted">
                <input type="checkbox" name="opens_new_tab" value="1"> تب جدید
            </label>
            <x-button type="submit" variant="v2-primary">افزودن</x-button>
        </form>
    </x-card>

    <x-card title="آیتم‌های منو ({{ $items->count() }})" icon="menu" variant="v2">
        @if ($items->isEmpty())
            <x-empty-state icon="menu" title="هنوز آیتمی اضافه نشده." variant="v2" />
        @else
            <div class="space-y-2">
                @foreach ($items as $item)
                    <div class="flex items-center gap-3 rounded-xl border border-v2-border p-3 {{ $item->is_active ? '' : 'opacity-50' }}">
                        <div class="min-w-0 flex-1">
                            <span class="font-extrabold">{{ $item->label }}</span>
                            <span class="ms-2 text-xs text-v2-text-muted num-font" dir="ltr">{{ $item->url }}</span>
                        </div>
                        <form method="POST" action="{{ route('admin.menu-items.toggle', $item) }}">
                            @csrf
                            <x-button type="submit" size="sm" variant="v2-secondary">{{ $item->is_active ? 'غیرفعال' : 'فعال' }}</x-button>
                        </form>
                        <form method="POST" action="{{ route('admin.menu-items.destroy', $item) }}" onsubmit="return confirm('حذف این آیتم؟');">
                            @csrf @method('DELETE')
                            <x-button type="submit" size="sm" variant="v2-danger">حذف</x-button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-layouts.admin>
