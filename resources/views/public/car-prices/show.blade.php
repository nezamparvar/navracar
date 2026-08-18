@php
    $l = $listing;
    $priceToman = (float) $l->price_aed * $freeRate;
    $breadcrumbItems = [
        ['label' => 'ناوراکار', 'url' => route('public.home')],
        ['label' => 'قیمت خودروها', 'url' => route('public.car-prices.index')],
    ];
    if ($brandLabel) {
        $breadcrumbItems[] = ['label' => $brandLabel, 'url' => route('public.car-prices.brand', $l->make)];
    }
    $breadcrumbItems[] = ['label' => $l->title_fa, 'url' => route('public.car-prices.show', $l)];
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
                '@'.'context' => 'https://schema.org',
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
        <x-schema-breadcrumbs :items="$breadcrumbItems" />
    @endpush

    <div class="bg-v2-bg px-4 py-8">
    <div class="mx-auto max-w-6xl">
        <nav class="mb-4 text-xs text-v2-text-muted">
            @foreach ($breadcrumbItems as $i => $crumb)
                @if ($i > 0)<span class="mx-1">/</span>@endif
                @if ($i === count($breadcrumbItems) - 1)
                    <span class="font-bold text-v2-text">{{ $crumb['label'] }}</span>
                @else
                    <a href="{{ $crumb['url'] }}" class="hover:text-v2-primary">{{ $crumb['label'] }}</a>
                @endif
            @endforeach
        </nav>

        @if($l->status !== 'published')
            <div class="mb-4 rounded-xl bg-v2-error/15 px-4 py-2.5 text-xs font-bold text-v2-error">
                این صفحه هنوز منتشر نشده — فقط برای پیش‌نمایش ادمین قابل مشاهده است.
            </div>
        @endif

        {{--
            RTL grid track order is mirrored: the first DOM child of a multi-column grid renders
            as the RIGHTMOST visual column. The reference shows the gallery on the left and the
            info column on the right, so the info block is placed FIRST in DOM order here.
        --}}
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <div class="space-y-5">
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <h1 class="text-xl font-black text-v2-text sm:text-2xl">{{ $l->title_fa }}</h1>
                        @if ($brandLabel)
                            <span class="shrink-0 rounded-full bg-v2-elevated px-3 py-1 text-[11px] font-bold text-v2-text-muted">{{ $brandLabel }}</span>
                        @endif
                    </div>

                    @if (! empty($specs))
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach (array_slice($specs, 0, 6) as $spec)
                                <span class="rounded-md bg-v2-elevated px-2 py-1 text-[11px] font-bold text-v2-text-muted">{{ $spec['value'] }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 rounded-2xl border border-v2-border bg-v2-surface p-4">
                        <div class="text-xs font-bold text-v2-text-muted">قیمت خودرو</div>
                        <div class="mt-1 text-2xl font-black text-v2-text num-font">{{ number_format((float) $l->price_aed) }} <span class="text-sm font-bold text-v2-text-muted">درهم</span></div>
                        <div class="mt-0.5 text-xs text-v2-text-muted num-font">≈ {{ number_format($priceToman) }} تومان (نرخ روز)</div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($l->category_id)
                            <a href="{{ route('public.car-prices.category', $l->category_id) }}" class="rounded-full border border-v2-border bg-v2-elevated px-3 py-1.5 text-[11px] font-bold text-v2-text-muted hover:border-v2-primary hover:text-v2-text">
                                {{ $l->categoryLabel() }}
                            </a>
                        @endif
                        @if ($priceBracketId)
                            <a href="{{ route('public.car-prices.price', $priceBracketId) }}" class="rounded-full border border-v2-border bg-v2-elevated px-3 py-1.5 text-[11px] font-bold text-v2-text-muted hover:border-v2-primary hover:text-v2-text">
                                {{ $priceBracketLabel }}
                            </a>
                        @endif
                    </div>

                    @if($l->delivery_days)
                        <div class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-v2-success/15 px-3 py-1.5 text-xs font-bold text-v2-success">
                            <x-icon name="check-circle" class="w-4 h-4" />
                            مدت زمان تحویل تخمینی: <span class="num-font">{{ $l->delivery_days }}</span> روز کاری
                        </div>
                    @endif

                    <p class="mt-2 text-[11px] text-v2-text-muted">
                        منبع:
                        <a href="{{ $l->source_url }}" target="_blank" rel="nofollow noopener" class="text-v2-primary hover:underline">دابیزل امارات</a>
                        @if($l->posted_on_dubizzle) · تاریخ ثبت آگهی: {{ $l->posted_on_dubizzle }} @endif
                    </p>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('public.lead-form') }}" class="inline-flex items-center gap-2 rounded-xl bg-v2-primary px-5 py-3 text-sm font-bold text-white shadow-glow-v2 hover:brightness-110">
                            ثبت درخواست
                        </a>
                        <a href="{{ route('public.calculator') }}" class="inline-flex items-center gap-2 rounded-xl border border-v2-border bg-v2-elevated px-5 py-3 text-sm font-bold text-v2-text hover:border-v2-primary">
                            <x-icon name="calculator" class="w-4 h-4" /> محاسبه هزینه
                        </a>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                @if ($l->images->isNotEmpty())
                    <div x-data="carGallery" data-images='@json($l->images->map(fn($i) => $i->url())->values())' class="space-y-2">
                        <div class="aspect-[4/3] overflow-hidden rounded-2xl bg-v2-surface">
                            <img :src="images[active]" alt="{{ $l->title_fa }}" class="h-full w-full object-cover">
                        </div>
                        @if ($l->images->count() > 1)
                            <div class="flex gap-2 overflow-x-auto pb-1">
                                <template x-for="(img, i) in images" :key="i">
                                    <button type="button" @click="active = i"
                                            class="h-16 w-20 shrink-0 overflow-hidden rounded-lg border-2"
                                            :class="active === i ? 'border-v2-primary' : 'border-transparent'">
                                        <img :src="img" class="h-full w-full object-cover">
                                    </button>
                                </template>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex aspect-[4/3] items-center justify-center rounded-2xl bg-v2-surface text-v2-text-muted">
                        <x-icon name="car" class="w-14 h-14" />
                    </div>
                @endif
            </div>
        </div>

        {{--
            مشخصات فنی: real structured spec columns (DubizzleTranslator-labelled, same $specs
            the controller already builds — no data duplicated here).
            تجهیزات و امکانات: CarListing has no curated features/amenities field (specs_json is
            just the raw, uncurated import payload, not fit for public display) — an honest empty
            state is shown instead of fabricating a list. See GAP_REPORT.md.
            معرفی خودرو: real description_en when the import provided one, else an honest empty
            state.
        --}}
        <div x-data="{ tab: 'specs' }" class="mt-8">
            <div class="flex gap-1 overflow-x-auto rounded-xl bg-v2-elevated p-1">
                <button type="button" @click="tab = 'specs'" class="shrink-0 rounded-lg px-4 py-2 text-xs font-bold transition" :class="tab === 'specs' ? 'bg-v2-primary text-white' : 'text-v2-text-muted'">مشخصات فنی</button>
                <button type="button" @click="tab = 'features'" class="shrink-0 rounded-lg px-4 py-2 text-xs font-bold transition" :class="tab === 'features' ? 'bg-v2-primary text-white' : 'text-v2-text-muted'">تجهیزات و امکانات</button>
                <button type="button" @click="tab = 'about'" class="shrink-0 rounded-lg px-4 py-2 text-xs font-bold transition" :class="tab === 'about' ? 'bg-v2-primary text-white' : 'text-v2-text-muted'">معرفی خودرو</button>
            </div>

            <div x-show="tab === 'specs'" class="mt-4 rounded-2xl border border-v2-border bg-v2-surface p-4 sm:p-5">
                @if (! empty($specs))
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                        @foreach ($specs as $spec)
                            <div class="flex items-center justify-between border-b border-v2-border pb-2">
                                <dt class="text-xs font-bold text-v2-text-muted">{{ $spec['label'] }}</dt>
                                <dd class="text-sm font-extrabold text-v2-text num-font">{{ $spec['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <x-empty-state variant="v2" icon="list" title="مشخصات فنی برای این خودرو ثبت نشده است." />
                @endif
            </div>

            <div x-show="tab === 'features'" class="mt-4 rounded-2xl border border-v2-border bg-v2-surface p-4 sm:p-5">
                <x-empty-state variant="v2" icon="check-circle" title="تجهیزات و امکانات این خودرو هنوز ثبت نشده است." />
            </div>

            <div x-show="tab === 'about'" class="mt-4 rounded-2xl border border-v2-border bg-v2-surface p-4 sm:p-5">
                @if (! empty($l->description_en))
                    <p class="text-sm leading-7 text-v2-text-muted">{{ $l->description_en }}</p>
                @else
                    <x-empty-state variant="v2" icon="info" title="توضیحاتی برای این خودرو ثبت نشده است." />
                @endif
            </div>
        </div>

        {{--
            Real 3-category summary only (DESIGN_SPEC.md §4): vehicle price / total customs
            clearance costs / plate costs. Values come straight from
            CarListing::pricingTotals() -> VehiclePricingService, the single source of the
            formula — nothing here recomputes anything. The service-fee row is never read from
            $pricingTotals for display, matching the existing breakdownForDisplay() precedent
            (see QuoteRequest::breakdownForDisplay). The full interactive multi-field calculator
            (x-car-calculator) is intentionally not embedded here — the reference and
            DESIGN_SPEC.md §4 show only this summary plus the "محاسبه هزینه" secondary CTA to the
            dedicated calculator page, which still owns the full interactive form.
        --}}
        <div class="mt-8">
            <x-card variant="v2" title="خلاصه هزینه‌های واردات (تقریبی)" icon="calculator" subtitle="بر اساس نرخ ارز امروز؛ برای محاسبه دقیق‌تر یا تغییر مفروضات از «محاسبه هزینه» استفاده کنید.">
                @php
                    $costRows = [
                        ['label' => 'قیمت خودرو', 'value' => $pricingTotals['realPriceToman'], 'color' => '#1677FF'],
                        ['label' => 'جمع هزینه‌های ترخیص', 'value' => $pricingTotals['customsSubtotalToman'], 'color' => '#20C7E9'],
                        ['label' => 'هزینه‌های پلاک', 'value' => $pricingTotals['plateSubtotalToman'], 'color' => '#8B5CF6'],
                    ];
                    $costTotal = max(1, array_sum(array_column($costRows, 'value')));
                @endphp
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach ($costRows as $row)
                        @php $pct = round($row['value'] / $costTotal * 100); @endphp
                        <div class="flex flex-col items-center rounded-xl bg-v2-bg p-4 text-center">
                            <div class="relative flex h-20 w-20 items-center justify-center rounded-full"
                                 style="background: conic-gradient({{ $row['color'] }} {{ $pct * 3.6 }}deg, #0A1B32 0deg)">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-v2-surface text-sm font-black text-v2-text num-font">{{ $pct }}%</div>
                            </div>
                            <div class="mt-3 text-xs font-bold text-v2-text-muted">{{ $row['label'] }}</div>
                            <div class="mt-1 text-sm font-black text-v2-text num-font">{{ number_format($row['value']) }} <span class="text-[11px] font-bold text-v2-text-muted">تومان</span></div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-[11px] text-v2-text-muted">
                    نرخ ارز: <span class="num-font">{{ number_format($freeRate) }}</span> تومان — آخرین به‌روزرسانی طبق تنظیمات نرخ ارز پنل مدیریت.
                </p>
            </x-card>
        </div>
    </div>
    </div>
</x-layouts.public>
