<x-layouts.admin :page-title="$pageTitle" page-subtitle="لینک آگهی دابیزل را وارد کنید تا اطلاعات و عکس‌ها خودکار دریافت و برای انتشار در سایت آماده شود.">

    <x-card title="افزودن آگهی از دابیزل" icon="link" class="mb-5">
        <form method="POST" action="{{ route('admin.car-listings.store') }}" class="space-y-3">
            @csrf
            <input type="url" name="source_url" placeholder="https://dubai.dubizzle.com/motors/used-cars/..." required
                   value="{{ old('source_url') }}"
                   class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm ltr text-left dark:border-white/10 dark:bg-white/5">

            <details class="rounded-xl border border-ink-200/70 p-3.5 text-xs dark:border-white/10">
                <summary class="cursor-pointer font-bold text-ink-600 dark:text-ink-300">اگر دریافت خودکار خطا داد، HTML صفحه را اینجا پیست کنید (اختیاری)</summary>
                <textarea name="html_source" rows="5" placeholder="کل HTML صفحه (View Page Source) را اینجا پیست کنید..."
                          class="mt-3 w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 font-mono text-[11px] ltr text-left dark:border-white/10 dark:bg-white/5">{{ old('html_source') }}</textarea>
            </details>

            <div class="flex flex-wrap items-center gap-3">
                <x-button type="submit" variant="amber">
                    <x-icon name="download" class="w-4 h-4" /> دریافت اطلاعات و ساخت پیش‌نویس
                </x-button>
                <x-button :href="route('admin.car-listings.create')" variant="secondary">
                    <x-icon name="plus" class="w-4 h-4" /> افزودن آگهی دستی (بدون دابیزل)
                </x-button>
            </div>
        </form>
    </x-card>

    <x-card title="آگهی‌های ثبت‌شده ({{ $listings->total() }})" icon="car">
        @if ($listings->isEmpty())
            <x-empty-state icon="car" title="هنوز آگهی‌ای ثبت نشده." />
        @else
            <div class="space-y-3">
                @foreach ($listings as $listing)
                    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-ink-200/70 p-3.5 dark:border-white/10">
                        <div class="h-16 w-24 shrink-0 overflow-hidden rounded-lg bg-ink-100 dark:bg-white/5">
                            @if ($listing->images->first())
                                <img src="{{ $listing->images->first()->url() }}" alt="" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center text-ink-300"><x-icon name="image" class="w-6 h-6" /></div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="truncate font-extrabold">{{ $listing->title_fa ?: $listing->title_en }}</span>
                                <x-badge :color="$listing->status === 'published' ? 'green' : 'slate'">
                                    {{ $listing->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}
                                </x-badge>
                                @if($listing->source_site === 'manual')
                                    <x-badge color="blue">دستی</x-badge>
                                @endif
                            </div>
                            <div class="mt-1 text-xs text-ink-500 dark:text-ink-400 num-font">
                                {{ number_format((float) $listing->price_aed) }} درهم
                                @if($listing->model_year) · مدل {{ $listing->model_year }} @endif
                                @if($listing->kilometers) · {{ $listing->kilometers }} @endif
                                · {{ $listing->created_at->format('Y-m-d') }}
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            @if ($listing->status === 'published')
                                <x-button :href="route('public.car-prices.show', $listing)" target="_blank" size="sm" variant="secondary">
                                    <x-icon name="external-link" class="w-4 h-4" /> مشاهده
                                </x-button>
                            @endif
                            <x-button :href="route('admin.car-listings.edit', $listing)" size="sm" variant="primary">
                                <x-icon name="edit" class="w-4 h-4" /> ویرایش
                            </x-button>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $listings->links() }}</div>
        @endif
    </x-card>
</x-layouts.admin>
