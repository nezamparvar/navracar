<x-layouts.public :title="$title">

    @push('head')
        <meta name="description" content="ناوراکار — واردات تخصصی خودرو از امارات به ایران با محاسبه دقیق هزینه ترخیص گمرکی، عوارض و پلاک، و بزرگ‌ترین بانک آگهی خودروهای وارداتی.">
        <link rel="canonical" href="{{ route('public.home') }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ route('public.home') }}">
    @endpush

    @if ($slides->isNotEmpty())
        <div x-data="homeSlider" data-slide-count="{{ $slides->count() }}" class="relative overflow-hidden">
            @foreach ($slides as $i => $slide)
                <div x-show="active === {{ $i }}" x-cloak style="display:none" class="relative">
                    <div class="aspect-[16/7] w-full overflow-hidden bg-ink-900 sm:aspect-[16/5]">
                        <img src="{{ $slide->url() }}" alt="{{ $slide->title }}" class="h-full w-full object-cover opacity-70">
                    </div>
                    <div class="absolute inset-0 flex items-center bg-gradient-to-l from-brand-950/80 via-brand-950/40 to-transparent">
                        <div class="mx-auto w-full max-w-6xl px-4">
                            <div class="max-w-lg">
                                <h1 class="text-2xl font-black text-white sm:text-4xl">{{ $slide->title }}</h1>
                                @if($slide->subtitle)
                                    <p class="mt-3 text-sm text-brand-100 sm:text-base">{{ $slide->subtitle }}</p>
                                @endif
                                @if($slide->cta_label && $slide->cta_url)
                                    <a href="{{ $slide->cta_url }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-3 text-sm font-bold text-ink-950 shadow-soft hover:brightness-105">
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
                        <button type="button" @click="active = i - 1" class="h-1.5 w-6 rounded-full" :class="active === i - 1 ? 'bg-amber-400' : 'bg-white/40'"></button>
                    </template>
                </div>
            @endif
        </div>
    @else
        <div class="bg-gradient-to-l from-brand-950 via-brand-900 to-brand-800 py-16 text-center text-white">
            <h1 class="text-2xl font-black sm:text-4xl">واردات تخصصی خودرو از امارات به ایران</h1>
            <p class="mx-auto mt-3 max-w-xl px-4 text-sm text-brand-100">محاسبه دقیق هزینه ترخیص گمرکی، عوارض و پلاک — همراه با بانک آگهی خودروهای وارداتی.</p>
        </div>
    @endif

    <div class="mx-auto max-w-6xl px-4 py-10">

        <div class="rounded-2xl border-2 border-amber-400 bg-amber-50 p-6 text-center dark:border-amber-500/40 dark:bg-amber-500/10 sm:p-8">
            <h2 class="text-lg font-black text-amber-900 dark:text-amber-200 sm:text-xl">محاسبه آنلاین هزینه واردات خودرو</h2>
            <p class="mx-auto mt-2 max-w-xl text-xs text-amber-800/80 dark:text-amber-300/70 sm:text-sm">قیمت خودروی دلخواه را وارد کنید تا جدول کامل هزینه ترخیص گمرکی، عوارض و پلاک انتظامی را ببینید.</p>
            <a href="{{ route('public.calculator') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand-800 px-6 py-3 text-sm font-bold text-white shadow-soft hover:brightness-110">
                <x-icon name="calculator" class="w-4 h-4" /> شروع محاسبه
            </a>
        </div>

        @if ($latestListings->isNotEmpty())
            <div class="mt-12">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-black text-ink-900 sm:text-xl">آخرین خودروهای موجود</h2>
                    <a href="{{ route('public.car-prices.index') }}" class="text-xs font-bold text-brand-700 hover:underline">مشاهده همه ←</a>
                </div>
                <div class="mt-4 flex gap-4 overflow-x-auto pb-3">
                    @foreach ($latestListings as $listing)
                        <a href="{{ route('public.car-prices.show', $listing) }}"
                           class="group w-64 shrink-0 overflow-hidden rounded-2xl border border-ink-200/70 bg-white shadow-soft transition hover:-translate-y-1 hover:shadow-soft-lg">
                            <div class="aspect-[4/3] overflow-hidden bg-ink-100">
                                @if ($listing->coverImage())
                                    <img src="{{ $listing->coverImage()->url() }}" alt="{{ $listing->title_fa }}" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center text-ink-300"><x-icon name="car" class="w-10 h-10" /></div>
                                @endif
                            </div>
                            <div class="p-3.5">
                                <h3 class="line-clamp-2 text-sm font-extrabold text-ink-900">{{ $listing->title_fa }}</h3>
                                <div class="mt-2 text-base font-black text-brand-700 num-font">
                                    {{ number_format((float) $listing->price_aed) }} <span class="text-xs font-bold">درهم</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-12">
            <h2 class="text-lg font-black text-ink-900 sm:text-xl">مرور خودروها بر اساس دسته و بازه قیمت</h2>
            <div class="mt-4 flex flex-wrap gap-1.5">
                @foreach ($categories as $id => $cat)
                    <a href="{{ route('public.car-prices.category', $id) }}" class="rounded-full border border-ink-200 bg-white px-3.5 py-1.5 text-xs font-bold text-ink-600 hover:border-brand-400 hover:text-brand-700">{{ $cat['label'] }}</a>
                @endforeach
            </div>
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @foreach ($priceBrackets as $id => $bracket)
                    <a href="{{ route('public.car-prices.price', $id) }}" class="rounded-full border border-ink-200 bg-white px-3.5 py-1.5 text-xs font-bold text-ink-600 hover:border-brand-400 hover:text-brand-700">{{ $bracket['label'] }}</a>
                @endforeach
            </div>
        </div>

        @if ($latestPosts->isNotEmpty())
            <div class="mt-12">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-black text-ink-900 sm:text-xl">مطالب وبلاگ</h2>
                    <a href="{{ route('public.blog.index') }}" class="text-xs font-bold text-brand-700 hover:underline">مشاهده همه ←</a>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                    @foreach ($latestPosts as $post)
                        <a href="{{ route('public.blog.show', $post) }}" class="overflow-hidden rounded-2xl border border-ink-200/70 bg-white shadow-soft hover:shadow-soft-lg">
                            @if($post->coverUrl())
                                <div class="aspect-[16/9] overflow-hidden bg-ink-100">
                                    <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" loading="lazy" class="h-full w-full object-cover">
                                </div>
                            @endif
                            <div class="p-4">
                                <h3 class="line-clamp-2 text-sm font-extrabold text-ink-900">{{ $post->title }}</h3>
                                @if($post->excerpt)
                                    <p class="mt-1.5 line-clamp-2 text-xs text-ink-500">{{ $post->excerpt }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.public>
