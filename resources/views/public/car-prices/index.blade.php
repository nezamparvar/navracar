<x-layouts.public :title="$title">

    @push('head')
        <meta name="description" content="{{ $description }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <x-schema-breadcrumbs :items="$breadcrumbs" />
        <script type="application/ld+json">
            {!! json_encode([
                '@'.'context' => 'https://schema.org',
                '@type' => 'ItemList',
                'itemListElement' => $listings->values()->map(fn ($l, $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'url' => route('public.car-prices.show', $l),
                    'name' => $l->title_fa,
                ])->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endpush

    <div class="bg-v2-bg px-4 py-5">
    <div class="mx-auto max-w-6xl">
        <nav class="mb-2.5 text-xs text-v2-text-muted">
            @foreach ($breadcrumbs as $i => $crumb)
                @if(!$loop->last)
                    <a href="{{ $crumb['url'] }}" class="hover:text-v2-primary">{{ $crumb['label'] }}</a>
                    <span class="mx-1">/</span>
                @else
                    <span class="font-bold text-v2-text">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>

        <h1 class="text-lg font-black text-v2-text sm:text-xl">{{ $heading }}</h1>
        <p class="mt-1 max-w-2xl text-xs text-v2-text-muted sm:text-sm">{{ $description }}</p>

        {{--
            Compact filter bar: search (real q param) + sort (real sort param) always visible;
            brand/engine-category/price-bracket are real routes (not query params), wired as
            selects that navigate on change instead of one-row-per-option chip lists, matching
            the reference's compact control density. On mobile, brand/category/price collapse
            behind a "فیلترها" toggle (Alpine) so the card grid isn't pushed below the fold —
            search+sort stay visible either way. There is still no combinable fuel-type/
            engine-volume/year filter — those columns don't exist on CarListing yet, so they are
            not rendered as dead controls. See GAP_REPORT.md §7.
        --}}
        <div x-data="{ filtersOpen: false }" class="mt-4">
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" class="flex min-w-[200px] flex-1 items-center gap-2">
                    <label for="car-search" class="sr-only">جستجو بر اساس برند، مدل یا کد خودرو</label>
                    <div class="relative min-w-0 flex-1">
                        <x-icon name="search" class="pointer-events-none absolute inset-y-0 right-3 my-auto h-4 w-4 text-v2-text-muted" />
                        <input id="car-search" type="search" name="q" value="{{ $searchQuery }}" placeholder="جست‌وجو بر اساس برند، مدل یا کد خودرو"
                               class="min-h-[40px] w-full rounded-xl border border-v2-border bg-v2-elevated py-2 pe-9 ps-3 text-xs text-v2-text placeholder:text-v2-text-muted focus:border-v2-primary focus:outline-none focus:ring-2 focus:ring-v2-primary/30 sm:text-sm">
                    </div>
                    <label for="car-sort" class="sr-only">مرتب‌سازی</label>
                    <select id="car-sort" name="sort" onchange="this.form.submit()"
                            class="min-h-[40px] shrink-0 rounded-xl border border-v2-border bg-v2-elevated px-2.5 text-xs text-v2-text focus:border-v2-primary focus:outline-none sm:text-sm">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                <button type="button" @click="filtersOpen = !filtersOpen"
                        class="flex min-h-[40px] shrink-0 items-center gap-1.5 rounded-xl border border-v2-border bg-v2-elevated px-3 text-xs font-bold text-v2-text sm:hidden">
                    فیلترها
                    <x-icon name="chevron-down" class="w-3.5 h-3.5 transition-transform" x-bind:class="{ 'rotate-180': filtersOpen }" />
                </button>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-2" :class="{ 'hidden sm:flex': !filtersOpen }">
                <label for="car-brand" class="sr-only">برند</label>
                <select id="car-brand" onchange="if(this.value) location.href=this.value"
                        class="min-h-[40px] rounded-xl border border-v2-border bg-v2-elevated px-2.5 text-xs text-v2-text focus:border-v2-primary focus:outline-none sm:text-sm">
                    <option value="">برند: همه</option>
                    @foreach ($quickFilters['brands'] as $chip)
                        <option value="{{ $chip['url'] }}">{{ $chip['label'] }}</option>
                    @endforeach
                </select>
                <label for="car-category" class="sr-only">دسته موتور</label>
                <select id="car-category" onchange="if(this.value) location.href=this.value"
                        class="min-h-[40px] rounded-xl border border-v2-border bg-v2-elevated px-2.5 text-xs text-v2-text focus:border-v2-primary focus:outline-none sm:text-sm">
                    <option value="">دسته موتور: همه</option>
                    @foreach ($quickFilters['categories'] as $chip)
                        <option value="{{ $chip['url'] }}">{{ $chip['label'] }}</option>
                    @endforeach
                </select>
                <label for="car-price" class="sr-only">بازه قیمت</label>
                <select id="car-price" onchange="if(this.value) location.href=this.value"
                        class="min-h-[40px] rounded-xl border border-v2-border bg-v2-elevated px-2.5 text-xs text-v2-text focus:border-v2-primary focus:outline-none sm:text-sm">
                    <option value="">بازه قیمت: همه</option>
                    @foreach ($quickFilters['priceBrackets'] as $chip)
                        <option value="{{ $chip['url'] }}">{{ $chip['label'] }}</option>
                    @endforeach
                </select>
                @if ($searchQuery !== '' || $sort !== 'newest')
                    <a href="{{ route('public.car-prices.index') }}" class="text-xs font-bold text-v2-text-muted hover:text-v2-text">پاک کردن</a>
                @endif
            </div>
        </div>

        @if ($listings->isEmpty())
            <div class="mt-10 rounded-2xl border border-v2-border bg-v2-surface">
                <x-empty-state variant="v2" icon="car" title="در حال حاضر آگهی‌ای در این بخش منتشر نشده است." />
            </div>
        @else
            <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($listings as $listing)
                    <a href="{{ route('public.car-prices.show', $listing) }}"
                       class="group relative overflow-hidden rounded-2xl border border-v2-border bg-v2-elevated shadow-soft-dark transition hover:-translate-y-1">
                        <span class="absolute end-2.5 top-2.5 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-v2-bg/60 text-v2-text backdrop-blur-sm" aria-hidden="true">
                            <x-icon name="heart" class="w-3.5 h-3.5" />
                        </span>
                        <div class="aspect-[16/11] overflow-hidden bg-v2-surface">
                            @if ($listing->coverImage())
                                <img src="{{ $listing->coverImage()->url() }}" alt="{{ $listing->title_fa }}"
                                     loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                            @else
                                <div class="flex h-full items-center justify-center text-v2-text-muted"><x-icon name="car" class="w-10 h-10" /></div>
                            @endif
                        </div>
                        <div class="p-2.5 sm:p-3">
                            <h2 class="line-clamp-2 text-xs font-extrabold text-v2-text sm:text-sm">{{ $listing->title_fa }}</h2>
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                @if($listing->model_year)<span class="rounded-md bg-v2-bg px-1.5 py-0.5 text-[10px] font-bold text-v2-text-muted">مدل {{ $listing->model_year }}</span>@endif
                                @if($listing->kilometers)<span class="rounded-md bg-v2-bg px-1.5 py-0.5 text-[10px] font-bold text-v2-text-muted num-font">{{ $listing->kilometers }} کیلومتر</span>@endif
                            </div>
                            <div class="mt-2 text-sm font-black text-v2-text num-font sm:text-base">
                                {{ number_format((float) $listing->price_aed) }} <span class="text-[10px] font-bold text-v2-text-muted">درهم</span>
                            </div>
                            <div class="mt-0.5 text-[10px] text-v2-text-muted num-font sm:text-xs">
                                قیمت تمام‌شده: {{ number_format($listing->estimatedLandedCostToman($freeRate, $customsRate) * 10) }} <span class="font-bold">ریال</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">{{ $listings->links() }}</div>
        @endif
    </div>
    </div>
</x-layouts.public>
