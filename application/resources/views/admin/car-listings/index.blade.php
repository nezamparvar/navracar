<x-layouts.admin :page-title="$pageTitle" page-subtitle="آگهی عمومی Dubizzle، DubiCars یا YallaMotor را با افزونه Chrome یا HTML ذخیره‌شده وارد کنید.">

    <x-card title="ایمپورت از سایت‌های خودرو" icon="link" variant="v2" class="mb-5">
        <div class="mb-4 grid gap-3 sm:grid-cols-3">
            <a href="https://dubai.dubizzle.com/motors/used-cars/" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-v2-border p-3 text-center text-sm font-extrabold hover:border-v2-primary hover:text-v2-primary">باز کردن Dubizzle</a>
            <a href="https://www.dubicars.com/" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-v2-border p-3 text-center text-sm font-extrabold hover:border-v2-primary hover:text-v2-primary">باز کردن DubiCars</a>
            <a href="https://uae.yallamotor.com/used-cars" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-v2-border p-3 text-center text-sm font-extrabold hover:border-v2-primary hover:text-v2-primary">باز کردن YallaMotor</a>
        </div>
        <p class="mb-3 rounded-xl bg-v2-primary/15 p-3 text-xs leading-6 text-v2-primary">
            روش پیشنهادی: افزونه Navra Capture را نصب کنید و روی صفحه آگهی بزنید؛ اطلاعات وارد «صف ایمپورت» می‌شود. روش جایگزین: آدرس آگهی و View Page Source کامل آن را در فرم زیر قرار دهید.
        </p>
        <form method="POST" action="{{ route('admin.car-listings.store') }}" class="space-y-3">
            @csrf
            <input type="url" name="source_url" placeholder="لینک آگهی Dubizzle، DubiCars یا YallaMotor" required
                   value="{{ old('source_url') }}"
                   class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm ltr text-left text-v2-text">

            <details class="rounded-xl border border-v2-border p-3.5 text-xs">
                <summary class="cursor-pointer font-bold text-v2-text">HTML صفحه را اینجا پیست کنید (الزامی)</summary>
                <textarea name="html_source" rows="5" required placeholder="کل HTML صفحه (View Page Source) را اینجا پیست کنید..."
                          class="mt-3 w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 font-mono text-[11px] ltr text-left text-v2-text">{{ old('html_source') }}</textarea>
            </details>

            <div class="flex flex-wrap items-center gap-3">
                <x-button type="submit" variant="v2-primary">
                    <x-icon name="download" class="w-4 h-4" /> دریافت اطلاعات و ساخت پیش‌نویس
                </x-button>
                @if(auth()->user()->isAdmin())
                    <x-button :href="route('admin.extension-pairing.index')" variant="v2-secondary">
                        <x-icon name="link" class="w-4 h-4" /> اتصال افزونه Chrome
                    </x-button>
                    <x-button :href="route('admin.import-queue.index')" variant="v2-secondary">
                        <x-icon name="inbox" class="w-4 h-4" /> مشاهده صف ایمپورت
                    </x-button>
                @endif
                <x-button :href="route('admin.car-listings.create')" variant="v2-secondary">
                    <x-icon name="plus" class="w-4 h-4" /> افزودن آگهی دستی (بدون دابیزل)
                </x-button>
                <x-button :href="route('admin.car-listings.import')" variant="v2-secondary">
                    <x-icon name="upload" class="w-4 h-4" /> ایمپورت گروهی از فایل کرالر
                </x-button>
            </div>
        </form>
    </x-card>

    <x-card title="آگهی‌های ثبت‌شده ({{ $listings->total() }})" icon="car" variant="v2">
        @if ($listings->isEmpty())
            <x-empty-state icon="car" title="هنوز آگهی‌ای ثبت نشده." variant="v2" />
        @else
            <div class="space-y-3">
                @foreach ($listings as $listing)
                    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-v2-border p-3.5">
                        <div class="h-16 w-24 shrink-0 overflow-hidden rounded-lg bg-v2-elevated">
                            @if ($listing->images->first())
                                <img src="{{ $listing->images->first()->url() }}" alt="" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center text-v2-text-muted"><x-icon name="image" class="w-6 h-6" /></div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="truncate font-extrabold">{{ $listing->title_fa ?: $listing->title_en }}</span>
                                <x-badge :color="$listing->status === 'published' ? 'v2-success' : 'v2-neutral'">
                                    {{ $listing->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}
                                </x-badge>
                                @if($listing->source_site === 'manual')
                                    <x-badge color="v2-primary">دستی</x-badge>
                                @endif
                            </div>
                            <div class="mt-1 text-xs text-v2-text-muted num-font">
                                {{ number_format((float) $listing->price_aed) }} درهم
                                @if($listing->model_year) · مدل {{ $listing->model_year }} @endif
                                @if($listing->kilometers) · {{ $listing->kilometers }} @endif
                                · {{ $listing->created_at->format('Y-m-d') }}
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            @if ($listing->status === 'published')
                                <x-button :href="route('public.car-prices.show', $listing)" target="_blank" size="sm" variant="v2-secondary">
                                    <x-icon name="external-link" class="w-4 h-4" /> مشاهده
                                </x-button>
                            @endif
                            <x-button :href="route('admin.car-listings.edit', $listing)" size="sm" variant="v2-primary">
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
