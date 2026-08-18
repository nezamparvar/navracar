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

    <div class="bg-v2-bg px-4 py-8">
    <div class="mx-auto max-w-6xl">
        <nav class="mb-4 text-xs text-v2-text-muted">
            @foreach ($breadcrumbs as $i => $crumb)
                @if(!$loop->last)
                    <a href="{{ $crumb['url'] }}" class="hover:text-v2-primary">{{ $crumb['label'] }}</a>
                    <span class="mx-1">/</span>
                @else
                    <span class="font-bold text-v2-text">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>

        <h1 class="text-2xl font-black text-v2-text sm:text-3xl">{{ $heading }}</h1>
        <p class="mt-2 max-w-2xl text-sm text-v2-text-muted">{{ $description }}</p>

        {{--
            Only the filter dimensions that actually exist server-side (brand/category/price-bracket
            routes) are rendered. The reference's free-text search + fuel-type/engine-volume/price-range
            combinable filter bar needs new query-string filter support on CarPriceController — that's
            real Phase 4 backend work, not a restyle, so it isn't faked here. See GAP_REPORT.md §7.
        --}}
        <div class="mt-5 space-y-2.5">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-bold text-v2-text-muted">برند:</span>
                @foreach ($quickFilters['brands'] as $chip)
                    <a href="{{ $chip['url'] }}" class="rounded-full border border-v2-border bg-v2-elevated px-3 py-1 text-[11px] font-bold text-v2-text-muted hover:border-v2-primary hover:text-v2-text">{{ $chip['label'] }}</a>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-bold text-v2-text-muted">دسته موتور:</span>
                @foreach ($quickFilters['categories'] as $chip)
                    <a href="{{ $chip['url'] }}" class="rounded-full border border-v2-border bg-v2-elevated px-3 py-1 text-[11px] font-bold text-v2-text-muted hover:border-v2-primary hover:text-v2-text">{{ $chip['label'] }}</a>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-bold text-v2-text-muted">بازه قیمت:</span>
                @foreach ($quickFilters['priceBrackets'] as $chip)
                    <a href="{{ $chip['url'] }}" class="rounded-full border border-v2-border bg-v2-elevated px-3 py-1 text-[11px] font-bold text-v2-text-muted hover:border-v2-primary hover:text-v2-text">{{ $chip['label'] }}</a>
                @endforeach
            </div>
        </div>

        @if ($listings->isEmpty())
            <div class="mt-10 rounded-2xl border border-v2-border bg-v2-surface">
                <x-empty-state variant="v2" icon="car" title="در حال حاضر آگهی‌ای در این بخش منتشر نشده است." />
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($listings as $listing)
                    <a href="{{ route('public.car-prices.show', $listing) }}"
                       class="group relative overflow-hidden rounded-2xl border border-v2-border bg-v2-elevated shadow-soft-dark transition hover:-translate-y-1">
                        <span class="absolute end-3 top-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-v2-bg/60 text-v2-text backdrop-blur-sm" aria-hidden="true">
                            <x-icon name="heart" class="w-4 h-4" />
                        </span>
                        <div class="aspect-[4/3] overflow-hidden bg-v2-surface">
                            @if ($listing->coverImage())
                                <img src="{{ $listing->coverImage()->url() }}" alt="{{ $listing->title_fa }}"
                                     loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                            @else
                                <div class="flex h-full items-center justify-center text-v2-text-muted"><x-icon name="car" class="w-10 h-10" /></div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h2 class="line-clamp-2 text-sm font-extrabold text-v2-text">{{ $listing->title_fa }}</h2>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @if($listing->model_year)<span class="rounded-md bg-v2-bg px-1.5 py-0.5 text-[11px] font-bold text-v2-text-muted">مدل {{ $listing->model_year }}</span>@endif
                                @if($listing->kilometers)<span class="rounded-md bg-v2-bg px-1.5 py-0.5 text-[11px] font-bold text-v2-text-muted num-font">{{ $listing->kilometers }} کیلومتر</span>@endif
                            </div>
                            <div class="mt-3 text-lg font-black text-v2-text num-font">
                                {{ number_format((float) $listing->price_aed) }} <span class="text-xs font-bold text-v2-text-muted">درهم</span>
                            </div>
                            <div class="mt-1 text-xs text-v2-text-muted num-font">
                                قیمت تمام‌شده: {{ number_format($listing->estimatedLandedCostToman($freeRate, $customsRate) * 10) }} <span class="font-bold">ریال</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">{{ $listings->links() }}</div>
        @endif
    </div>
    </div>
</x-layouts.public>
