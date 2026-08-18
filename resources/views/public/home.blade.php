<x-layouts.public :title="$title">

    @push('head')
        <meta name="description" content="ناوراکار — واردات تخصصی خودرو از امارات به ایران با محاسبه دقیق هزینه ترخیص گمرکی، عوارض و پلاک، و بزرگ‌ترین بانک آگهی خودروهای وارداتی.">
        <link rel="canonical" href="{{ route('public.home') }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ route('public.home') }}">
    @endpush

    @if ($slides->isNotEmpty())
        <div x-data="homeSlider" data-slide-count="{{ $slides->count() }}" class="relative overflow-hidden bg-v2-bg">
            @foreach ($slides as $i => $slide)
                <div x-show="active === {{ $i }}" x-cloak style="display:none" class="relative">
                    <div class="aspect-[16/7] w-full overflow-hidden bg-v2-surface sm:aspect-[16/5]">
                        <img src="{{ $slide->url() }}" alt="{{ $slide->title }}" class="h-full w-full object-cover opacity-70">
                    </div>
                    <div class="absolute inset-0 flex items-center bg-gradient-to-l from-v2-bg/90 via-v2-bg/50 to-transparent">
                        <div class="mx-auto w-full max-w-6xl px-4">
                            <div class="max-w-lg">
                                <h1 class="text-2xl font-black text-v2-text sm:text-4xl">{{ $slide->title }}</h1>
                                @if($slide->subtitle)
                                    <p class="mt-3 text-sm text-v2-text-muted sm:text-base">{{ $slide->subtitle }}</p>
                                @endif
                                @if($slide->cta_label && $slide->cta_url)
                                    <a href="{{ $slide->cta_url }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-v2-primary-action px-5 py-3 text-sm font-bold text-white shadow-glow-v2 hover:brightness-110">
                                        {{ $slide->cta_label }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            @if ($slides->count() > 1)
                <div class="absolute inset-x-0 bottom-3 flex justify-center gap-1.5">
                    <template x-for="i in count" :key="i">
                        <button type="button" @click="active = i - 1" class="h-1.5 w-6 rounded-full" :class="active === i - 1 ? 'bg-v2-primary' : 'bg-white/30'"></button>
                    </template>
                </div>
            @endif
        </div>
    @else
        <div class="bg-v2-surface py-16 text-center">
            <h1 class="text-2xl font-black text-v2-text sm:text-4xl">واردات تخصصی خودرو از امارات به ایران</h1>
            <p class="mx-auto mt-3 max-w-xl px-4 text-sm text-v2-text-muted">محاسبه دقیق هزینه ترخیص گمرکی، عوارض و پلاک — همراه با بانک آگهی خودروهای وارداتی.</p>
        </div>
    @endif

    <div class="bg-v2-bg px-4 py-10">
    <div class="mx-auto max-w-6xl">

        <div class="rounded-2xl border border-v2-border bg-v2-surface p-6 text-center sm:p-8">
            <h2 class="text-lg font-black text-v2-text sm:text-xl">محاسبه آنلاین هزینه واردات خودرو</h2>
            <p class="mx-auto mt-2 max-w-xl text-xs text-v2-text-muted sm:text-sm">قیمت خودروی دلخواه را وارد کنید تا جدول کامل هزینه ترخیص گمرکی، عوارض و پلاک انتظامی را ببینید.</p>
            <a href="{{ route('public.calculator') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-v2-primary-action px-6 py-3 text-sm font-bold text-white shadow-glow-v2 hover:brightness-110">
                <x-icon name="calculator" class="w-4 h-4" /> شروع محاسبه
            </a>
        </div>

        @if ($latestListings->isNotEmpty())
            <div class="mt-12">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-black text-v2-text sm:text-xl">آخرین خودروهای موجود</h2>
                    <a href="{{ route('public.car-prices.index') }}" class="text-xs font-bold text-v2-primary hover:underline">مشاهده همه ←</a>
                </div>
                <div class="mt-4 flex gap-4 overflow-x-auto pb-3">
                    @foreach ($latestListings as $listing)
                        <a href="{{ route('public.car-prices.show', $listing) }}"
                           class="group relative w-64 shrink-0 overflow-hidden rounded-2xl border border-v2-border bg-v2-elevated shadow-soft-dark transition hover:-translate-y-1">
                            <span class="absolute end-3 top-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-v2-bg/60 text-v2-text backdrop-blur-sm" aria-hidden="true">
                                <x-icon name="heart" class="w-4 h-4" />
                            </span>
                            <div class="aspect-[4/3] overflow-hidden bg-v2-surface">
                                @if ($listing->coverImage())
                                    <img src="{{ $listing->coverImage()->url() }}" alt="{{ $listing->title_fa }}" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center text-v2-text-muted"><x-icon name="car" class="w-10 h-10" /></div>
                                @endif
                            </div>
                            <div class="p-3.5">
                                <h3 class="line-clamp-2 text-sm font-extrabold text-v2-text">{{ $listing->title_fa }}</h3>
                                @if ($listing->model_year || $listing->engine_capacity_cc)
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        @if ($listing->model_year)
                                            <span class="rounded-md bg-v2-bg px-1.5 py-0.5 text-[11px] font-bold text-v2-text-muted">مدل {{ $listing->model_year }}</span>
                                        @endif
                                        @if ($listing->engine_capacity_cc)
                                            <span class="rounded-md bg-v2-bg px-1.5 py-0.5 text-[11px] font-bold text-v2-text-muted num-font">{{ number_format($listing->engine_capacity_cc / 1000, 1) }}L</span>
                                        @endif
                                    </div>
                                @endif
                                <div class="mt-2 text-base font-black text-v2-text num-font">
                                    {{ number_format((float) $listing->price_aed) }} <span class="text-xs font-bold text-v2-text-muted">درهم</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-12">
            <h2 class="text-lg font-black text-v2-text sm:text-xl">مرور خودروها بر اساس دسته و بازه قیمت</h2>
            <div class="mt-4 flex flex-wrap gap-1.5">
                @foreach ($categories as $id => $cat)
                    <a href="{{ route('public.car-prices.category', $id) }}" class="rounded-full border border-v2-border bg-v2-elevated px-3.5 py-1.5 text-xs font-bold text-v2-text-muted hover:border-v2-primary hover:text-v2-text">{{ $cat['label'] }}</a>
                @endforeach
            </div>
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @foreach ($priceBrackets as $id => $bracket)
                    <a href="{{ route('public.car-prices.price', $id) }}" class="rounded-full border border-v2-border bg-v2-elevated px-3.5 py-1.5 text-xs font-bold text-v2-text-muted hover:border-v2-primary hover:text-v2-text">{{ $bracket['label'] }}</a>
                @endforeach
            </div>
        </div>

        @if ($latestPosts->isNotEmpty())
            <div class="mt-12">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-black text-v2-text sm:text-xl">مطالب وبلاگ</h2>
                    <a href="{{ route('public.blog.index') }}" class="text-xs font-bold text-v2-primary hover:underline">مشاهده همه ←</a>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                    @foreach ($latestPosts as $post)
                        <a href="{{ route('public.blog.show', $post) }}" class="overflow-hidden rounded-2xl border border-v2-border bg-v2-elevated shadow-soft-dark hover:-translate-y-1 transition">
                            @if($post->coverUrl())
                                <div class="aspect-[16/9] overflow-hidden bg-v2-surface">
                                    <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" loading="lazy" class="h-full w-full object-cover">
                                </div>
                            @endif
                            <div class="p-4">
                                <h3 class="line-clamp-2 text-sm font-extrabold text-v2-text">{{ $post->title }}</h3>
                                @if($post->excerpt)
                                    <p class="mt-1.5 line-clamp-2 text-xs text-v2-text-muted">{{ $post->excerpt }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    </div>
</x-layouts.public>
