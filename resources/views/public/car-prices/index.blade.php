<x-layouts.public :title="$title">

    @push('head')
        <meta name="description" content="لیست خودروهای موجود در دابیزل امارات به همراه قیمت درهم و جدول کامل هزینه ترخیص، عوارض گمرکی و پلاک برای واردات به ایران — ناوراکار.">
        <link rel="canonical" href="{{ route('public.car-prices.index') }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ route('public.car-prices.index') }}">
    @endpush

    <div class="mx-auto max-w-6xl px-4 py-8">
        <nav class="mb-4 text-xs text-ink-500">
            <a href="{{ route('public.calculator') }}" class="hover:text-brand-700">ناوراکار</a>
            <span class="mx-1">/</span>
            <span class="font-bold text-ink-800">قیمت خودروها</span>
        </nav>

        <h1 class="text-2xl font-black text-ink-900 sm:text-3xl">قیمت خودروها</h1>
        <p class="mt-2 max-w-2xl text-sm text-ink-500">
            خودروهای موجود در بازار امارات با قیمت روز درهم — همراه با جدول کامل محاسبه هزینهٔ ترخیص گمرکی، عوارض و پلاک انتظامی برای واردات به ایران.
        </p>

        @if ($listings->isEmpty())
            <div class="mt-10 rounded-2xl border border-ink-200/70 bg-white p-10 text-center text-ink-400">
                در حال حاضر آگهی‌ای منتشر نشده است.
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
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">{{ $listings->links() }}</div>
        @endif
    </div>
</x-layouts.public>
