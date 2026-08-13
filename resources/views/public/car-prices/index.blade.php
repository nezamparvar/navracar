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
                '@context' => 'https://schema.org',
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

    <div class="mx-auto max-w-6xl px-4 py-8">
        <nav class="mb-4 text-xs text-ink-500">
            @foreach ($breadcrumbs as $i => $crumb)
                @if(!$loop->last)
                    <a href="{{ $crumb['url'] }}" class="hover:text-brand-700">{{ $crumb['label'] }}</a>
                    <span class="mx-1">/</span>
                @else
                    <span class="font-bold text-ink-800">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>

        <h1 class="text-2xl font-black text-ink-900 sm:text-3xl">{{ $heading }}</h1>
        <p class="mt-2 max-w-2xl text-sm text-ink-500">{{ $description }}</p>

        <div class="mt-5 space-y-2.5">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-bold text-ink-400">برند:</span>
                @foreach ($quickFilters['brands'] as $chip)
                    <a href="{{ $chip['url'] }}" class="rounded-full border border-ink-200 bg-white px-3 py-1 text-[11px] font-bold text-ink-600 hover:border-brand-400 hover:text-brand-700 dark:border-white/10">{{ $chip['label'] }}</a>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-bold text-ink-400">دسته موتور:</span>
                @foreach ($quickFilters['categories'] as $chip)
                    <a href="{{ $chip['url'] }}" class="rounded-full border border-ink-200 bg-white px-3 py-1 text-[11px] font-bold text-ink-600 hover:border-brand-400 hover:text-brand-700 dark:border-white/10">{{ $chip['label'] }}</a>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-bold text-ink-400">بازه قیمت:</span>
                @foreach ($quickFilters['priceBrackets'] as $chip)
                    <a href="{{ $chip['url'] }}" class="rounded-full border border-ink-200 bg-white px-3 py-1 text-[11px] font-bold text-ink-600 hover:border-brand-400 hover:text-brand-700 dark:border-white/10">{{ $chip['label'] }}</a>
                @endforeach
            </div>
        </div>

        @if ($listings->isEmpty())
            <div class="mt-10 rounded-2xl border border-ink-200/70 bg-white p-10 text-center text-ink-400">
                در حال حاضر آگهی‌ای در این بخش منتشر نشده است.
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($listings as $listing)
                    <a href="{{ route('public.car-prices.show', $listing) }}"
                       class="group overflow-hidden rounded-2xl border border-ink-200/70 bg-white shadow-soft transition hover:-translate-y-1 hover:shadow-soft-lg">
                        <div class="aspect-[4/3] overflow-hidden bg-ink-100">
                            @if ($listing->coverImage())
                                <img src="{{ $listing->coverImage()->url() }}" alt="{{ $listing->title_fa }}"
                                     loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                            @else
                                <div class="flex h-full items-center justify-center text-ink-300"><x-icon name="car" class="w-10 h-10" /></div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h2 class="line-clamp-2 text-sm font-extrabold text-ink-900">{{ $listing->title_fa }}</h2>
                            <div class="mt-2 flex flex-wrap gap-x-2 gap-y-1 text-[11px] text-ink-500">
                                @if($listing->model_year)<span>مدل {{ $listing->model_year }}</span>@endif
                                @if($listing->kilometers)<span>· {{ $listing->kilometers }}</span>@endif
                            </div>
                            <div class="mt-3 text-lg font-black text-brand-700 num-font">
                                {{ number_format((float) $listing->price_aed) }} <span class="text-xs font-bold">درهم</span>
                            </div>
                            <div class="mt-1 text-xs text-ink-500 num-font">
                                قیمت تمام‌شده: {{ number_format($listing->estimatedLandedCostToman($freeRate, $customsRate) * 10) }} <span class="font-bold">ریال</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">{{ $listings->links() }}</div>
        @endif
    </div>
</x-layouts.public>
