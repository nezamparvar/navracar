@php
    $l = $listing;
    $priceToman = (float) $l->price_aed * $freeRate;
    $waMessage = rawurlencode("سلام، درباره خودروی «{$l->title_fa}» (قیمت ".number_format((float) $l->price_aed)." درهم) توضیحات بیشتری می‌خوام: ".route('public.car-prices.show', $l));
    $waUae = 'https://wa.me/'.str_replace([' ', '+'], '', $whatsappUae).'?text='.$waMessage;
    $waIran = 'https://wa.me/'.str_replace([' ', '+'], '', $whatsappIran).'?text='.$waMessage;
@endphp

<x-layouts.public :title="$title">

    @push('head')
        <meta name="description" content="{{ $l->meta_description }}">
        <link rel="canonical" href="{{ route('public.car-prices.show', $l) }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $l->meta_description }}">
        <meta property="og:type" content="product">
        <meta property="og:url" content="{{ route('public.car-prices.show', $l) }}">
        @if($l->coverImage())
            <meta property="og:image" content="{{ $l->coverImage()->url() }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
        @if($l->status !== 'published')
            <meta name="robots" content="noindex, nofollow">
        @endif
        <script type="application/ld+json">
            {!! json_encode(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Vehicle',
                'name' => $l->title_fa,
                'brand' => $l->make,
                'model' => $l->model,
                'vehicleModelDate' => $l->model_year,
                'mileageFromOdometer' => $l->kilometers,
                'fuelType' => $l->fuel_type,
                'bodyType' => $l->body_type,
                'vehicleTransmission' => $l->transmission_type,
                'color' => $l->exterior_color,
                'vehicleInteriorColor' => $l->interior_color,
                'numberOfDoors' => $l->doors,
                'vehicleSeatingCapacity' => $l->seating_capacity,
                'vehicleEngine' => $l->engine_capacity_cc ? ['@type' => 'EngineSpecification', 'engineDisplacement' => $l->engine_capacity_cc] : null,
                'itemCondition' => 'https://schema.org/UsedCondition',
                'sku' => $l->slug,
                'image' => $l->images->map(fn($img) => $img->url())->all(),
                'offers' => [
                    '@type' => 'Offer',
                    'priceCurrency' => 'AED',
                    'price' => (float) $l->price_aed,
                    'availability' => 'https://schema.org/InStock',
                    'url' => route('public.car-prices.show', $l),
                ],
            ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
        <x-schema-breadcrumbs :items="[
            ['label' => 'ناوراکار', 'url' => route('public.home')],
            ['label' => 'قیمت خودروها', 'url' => route('public.car-prices.index')],
            ['label' => $l->title_fa, 'url' => route('public.car-prices.show', $l)],
        ]" />
    @endpush

    <div class="mx-auto max-w-6xl px-4 py-8">
        <nav class="mb-4 text-xs text-ink-500">
            <a href="{{ route('public.home') }}" class="hover:text-brand-700">ناوراکار</a>
            <span class="mx-1">/</span>
            <a href="{{ route('public.car-prices.index') }}" class="hover:text-brand-700">قیمت خودروها</a>
            <span class="mx-1">/</span>
            <span class="font-bold text-ink-800">{{ $l->title_fa }}</span>
        </nav>

        @if($l->status !== 'published')
            <div class="mb-4 rounded-xl bg-rose-100 px-4 py-2.5 text-xs font-bold text-rose-700">
                این صفحه هنوز منتشر نشده — فقط برای پیش‌نمایش ادمین قابل مشاهده است.
            </div>
        @endif

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <div class="space-y-6">
                @if ($l->images->isNotEmpty())
                    <div x-data="{ active: 0, images: @js($l->images->map(fn($i) => $i->url())->values()) }" class="space-y-2">
                        <div class="aspect-[4/3] overflow-hidden rounded-2xl bg-ink-100">
                            <img :src="images[active]" alt="{{ $l->title_fa }}" class="h-full w-full object-cover">
                        </div>
                        @if ($l->images->count() > 1)
                            <div class="flex gap-2 overflow-x-auto pb-1">
                                <template x-for="(img, i) in images" :key="i">
                                    <button type="button" @click="active = i"
                                            class="h-16 w-20 shrink-0 overflow-hidden rounded-lg border-2"
                                            :class="active === i ? 'border-amber-500' : 'border-transparent'">
                                        <img :src="img" class="h-full w-full object-cover">
                                    </button>
                                </template>
                            </div>
                        @endif
                    </div>
                @endif

                <div>
                    <h1 class="text-xl font-black text-ink-900 sm:text-2xl">{{ $l->title_fa }}</h1>
                    <div class="mt-3 flex flex-wrap items-baseline gap-3">
                        <span class="text-2xl font-black text-brand-700 num-font">{{ number_format((float) $l->price_aed) }} <span class="text-sm font-bold">درهم</span></span>
                        <span class="text-sm text-ink-500 num-font">≈ {{ number_format($priceToman) }} تومان (نرخ روز)</span>
                    </div>
                    <p class="mt-2 text-[11px] text-ink-400">
                        منبع:
                        <a href="{{ $l->source_url }}" target="_blank" rel="nofollow noopener" class="text-brand-600 hover:underline">دابیزل امارات</a>
                        @if($l->posted_on_dubizzle) · تاریخ ثبت آگهی: {{ $l->posted_on_dubizzle }} @endif
                    </p>
                    @if($l->delivery_days)
                        <div class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                            <x-icon name="check-circle" class="w-4 h-4" />
                            مدت زمان تحویل تخمینی: <span class="num-font">{{ $l->delivery_days }}</span> روز کاری
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ $waUae }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-5 py-3 text-sm font-bold text-white shadow-soft hover:brightness-105">
                        <x-icon name="whatsapp-fill" class="w-4 h-4" /> مشاوره واتساپ (امارات)
                    </a>
                    <a href="{{ $waIran }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-5 py-3 text-sm font-bold text-white shadow-soft hover:brightness-105">
                        <x-icon name="whatsapp-fill" class="w-4 h-4" /> مشاوره واتساپ (ایران)
                    </a>
                </div>
            </div>

            <div>
                @if (! empty($specs))
                    <x-card title="مشخصات فنی" icon="list">
                        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                            @foreach ($specs as $spec)
                                <div class="flex items-center justify-between border-b border-ink-100 pb-2 dark:border-white/5">
                                    <dt class="text-xs font-bold text-ink-500">{{ $spec['label'] }}</dt>
                                    <dd class="text-sm font-extrabold text-ink-900 dark:text-white">{{ $spec['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-card>
                @endif
            </div>
        </div>

        <div class="mt-8">
            <x-card title="جدول محاسبه هزینه واردات" icon="calculator" subtitle="قیمت خودرو به‌صورت خودکار از قیمت درهم این آگهی پر شده — همه مقادیر قابل ویرایش هستند.">
                <x-car-calculator :listing="$l" :free-rate="$freeRate" :customs-rate="$customsRate" />
            </x-card>
        </div>
    </div>
</x-layouts.public>
