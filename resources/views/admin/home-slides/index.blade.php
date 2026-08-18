<x-layouts.admin :page-title="$pageTitle" page-subtitle="این اسلایدها در بالای صفحه اصلی سایت به‌صورت چرخشی نمایش داده می‌شوند.">

    <x-card title="افزودن اسلاید" icon="plus" variant="v2" class="mb-5">
        <form method="POST" action="{{ route('admin.home-slides.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-bold text-v2-text-muted">عکس (پیشنهاد: عریض، حداقل ۱۶۰۰ پیکسل)</label>
                <input type="file" name="image" accept="image/*" required class="text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-v2-text-muted">عنوان</label>
                <input type="text" name="title" required class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-v2-text-muted">زیرعنوان</label>
                <input type="text" name="subtitle" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-v2-text-muted">متن دکمه</label>
                <input type="text" name="cta_label" placeholder="مثلاً: مشاهده خودروها" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-v2-text-muted">لینک دکمه</label>
                <input type="text" name="cta_url" dir="ltr" placeholder="/car-prices" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-left text-v2-text">
            </div>
            <div class="sm:col-span-2">
                <x-button type="submit" variant="v2-primary">افزودن اسلاید</x-button>
            </div>
        </form>
    </x-card>

    <x-card title="اسلایدهای موجود ({{ $slides->count() }})" icon="image" variant="v2">
        @if ($slides->isEmpty())
            <x-empty-state icon="image" title="هنوز اسلایدی اضافه نشده." variant="v2" />
        @else
            <div class="space-y-3">
                @foreach ($slides as $slide)
                    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-v2-border p-3.5 {{ $slide->is_active ? '' : 'opacity-50' }}">
                        <img src="{{ $slide->url() }}" class="h-16 w-28 shrink-0 rounded-lg object-cover">
                        <form method="POST" action="{{ route('admin.home-slides.update', $slide) }}" class="grid min-w-0 flex-1 grid-cols-2 gap-2 sm:grid-cols-4">
                            @csrf @method('PUT')
                            <input type="text" name="title" value="{{ $slide->title }}" class="rounded-lg border border-v2-border bg-v2-elevated px-2 py-1.5 text-xs text-v2-text">
                            <input type="text" name="subtitle" value="{{ $slide->subtitle }}" placeholder="زیرعنوان" class="rounded-lg border border-v2-border bg-v2-elevated px-2 py-1.5 text-xs text-v2-text">
                            <input type="text" name="cta_label" value="{{ $slide->cta_label }}" placeholder="متن دکمه" class="rounded-lg border border-v2-border bg-v2-elevated px-2 py-1.5 text-xs text-v2-text">
                            <input type="text" name="cta_url" value="{{ $slide->cta_url }}" dir="ltr" placeholder="لینک دکمه" class="rounded-lg border border-v2-border bg-v2-elevated px-2 py-1.5 text-xs text-left text-v2-text">
                            <input type="number" name="sort_order" value="{{ $slide->sort_order }}" class="w-16 rounded-lg border border-v2-border bg-v2-elevated px-2 py-1.5 text-xs num-font text-v2-text">
                            <x-button type="submit" size="sm" variant="v2-secondary">ذخیره</x-button>
                        </form>
                        <form method="POST" action="{{ route('admin.home-slides.toggle', $slide) }}">
                            @csrf
                            <x-button type="submit" size="sm" variant="v2-secondary">{{ $slide->is_active ? 'غیرفعال' : 'فعال' }}</x-button>
                        </form>
                        <form method="POST" action="{{ route('admin.home-slides.destroy', $slide) }}" onsubmit="return confirm('حذف این اسلاید؟');">
                            @csrf @method('DELETE')
                            <x-button type="submit" size="sm" variant="v2-danger">حذف</x-button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-layouts.admin>
