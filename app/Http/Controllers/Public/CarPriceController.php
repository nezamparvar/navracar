<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\Setting;
use App\Services\DubizzleTranslator;
use App\Services\VehiclePricing\VehiclePricingCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class CarPriceController extends Controller
{
    private const DISPLAY_SPEC_FIELDS = [
        'model_year', 'kilometers', 'body_type', 'fuel_type', 'transmission_type',
        'regional_specs', 'steering_side', 'warranty', 'exterior_color',
        'interior_color', 'horsepower', 'engine_capacity_cc', 'no_of_cylinders',
        'doors', 'seating_capacity', 'location_text',
    ];

    public function index()
    {
        return $this->renderIndex(
            CarListing::published(),
            title: 'قیمت خودروها | ناوراکار',
            heading: 'قیمت خودروها',
            description: 'خودروهای موجود در بازار امارات با قیمت روز درهم — همراه با جدول کامل محاسبه هزینهٔ ترخیص گمرکی، عوارض و پلاک انتظامی برای واردات به ایران.',
            canonicalRoute: ['public.car-prices.index'],
            breadcrumbs: [
                ['label' => 'ناوراکار', 'url' => route('public.home')],
                ['label' => 'قیمت خودروها', 'url' => route('public.car-prices.index')],
            ],
        );
    }

    public function brand(string $make)
    {
        $label = Str::of($make)->replace('-', ' ')->title();

        return $this->renderIndex(
            CarListing::published()->where('make', $make),
            title: "قیمت خودروهای {$label} | ناوراکار",
            heading: "خودروهای {$label}",
            description: "لیست آگهی‌های منتشرشدهٔ خودروهای {$label} به همراه قیمت درهم و جدول کامل هزینه واردات به ایران.",
            canonicalRoute: ['public.car-prices.brand', $make],
            breadcrumbs: [
                ['label' => 'ناوراکار', 'url' => route('public.home')],
                ['label' => 'قیمت خودروها', 'url' => route('public.car-prices.index')],
                ['label' => (string) $label, 'url' => route('public.car-prices.brand', $make)],
            ],
        );
    }

    public function category(string $categoryId)
    {
        abort_unless(array_key_exists($categoryId, VehiclePricingCatalog::CATEGORIES), 404);
        $label = VehiclePricingCatalog::CATEGORIES[$categoryId]['label'];

        return $this->renderIndex(
            CarListing::published()->where('category_id', $categoryId),
            title: "خودروهای {$label} | ناوراکار",
            heading: "خودروهای دسته «{$label}»",
            description: "لیست آگهی‌های منتشرشده در دستهٔ «{$label}» به همراه قیمت درهم و جدول کامل هزینه واردات به ایران.",
            canonicalRoute: ['public.car-prices.category', $categoryId],
            breadcrumbs: [
                ['label' => 'ناوراکار', 'url' => route('public.home')],
                ['label' => 'قیمت خودروها', 'url' => route('public.car-prices.index')],
                ['label' => $label, 'url' => route('public.car-prices.category', $categoryId)],
            ],
        );
    }

    public function price(string $bracket)
    {
        abort_unless(array_key_exists($bracket, CarListing::PRICE_BRACKETS), 404);
        $label = CarListing::PRICE_BRACKETS[$bracket]['label'];
        $freeRate = (float) Setting::get(Setting::FREE_RATE);
        $customsRate = (float) Setting::get(Setting::CUSTOMS_RATE);

        // بازهٔ قیمتی بر مبنای «قیمت تمام‌شده» (هزینهٔ کامل واردات) تعیین می‌شود،
        // نه فقط قیمت خام درهم — چون این فرمول برای هر آگهی به‌ازای دستهٔ خودرو
        // فرق می‌کند، ابتدا شناسهٔ آگهی‌های منطبق در PHP مشخص و بعد کوئری اصلی
        // (با صفحه‌بندی درست) روی همان شناسه‌ها فیلتر می‌شود.
        $matchingIds = CarListing::published()->get(['id', 'price_aed', 'category_id'])
            ->filter(fn (CarListing $l) => $l->priceBracketId($freeRate, $customsRate) === $bracket)
            ->pluck('id');

        return $this->renderIndex(
            CarListing::whereIn('id', $matchingIds),
            title: "خودروهای {$label} | ناوراکار",
            heading: "خودروهای {$label}",
            description: "لیست آگهی‌های منتشرشدهٔ خودرو در بازهٔ قیمتی «{$label}» (بر پایهٔ نرخ روز) به همراه جدول کامل هزینه واردات به ایران.",
            canonicalRoute: ['public.car-prices.price', $bracket],
            breadcrumbs: [
                ['label' => 'ناوراکار', 'url' => route('public.home')],
                ['label' => 'قیمت خودروها', 'url' => route('public.car-prices.index')],
                ['label' => $label, 'url' => route('public.car-prices.price', $bracket)],
            ],
        );
    }

    public function show(Request $request, CarListing $carListing, DubizzleTranslator $translator)
    {
        if ($carListing->status !== 'published' && ! $request->user()) {
            abort(404);
        }

        $specs = [];
        foreach (self::DISPLAY_SPEC_FIELDS as $field) {
            if (! empty($carListing->$field)) {
                $specs[] = ['label' => $translator->label($field), 'value' => $carListing->$field];
            }
        }

        $freeRate = (float) Setting::get(Setting::FREE_RATE);
        $customsRate = (float) Setting::get(Setting::CUSTOMS_RATE);
        $brandLabel = $carListing->make ? (string) Str::of($carListing->make)->replace('-', ' ')->title() : null;
        $priceBracketId = $carListing->priceBracketId($freeRate, $customsRate);

        return view('public.car-prices.show', [
            'title' => $carListing->meta_title ?: ($carListing->title_fa.' | ناوراکار'),
            'listing' => $carListing->load('images'),
            'specs' => $specs,
            'freeRate' => $freeRate,
            'customsRate' => $customsRate,
            'pricingTotals' => $carListing->pricingTotals($freeRate, $customsRate),
            'whatsappUae' => Setting::get(Setting::WHATSAPP_UAE),
            'whatsappIran' => Setting::get(Setting::WHATSAPP_IRAN),
            'brandLabel' => $brandLabel,
            'priceBracketId' => $priceBracketId,
            'priceBracketLabel' => $priceBracketId ? CarListing::PRICE_BRACKETS[$priceBracketId]['label'] : null,
        ]);
    }

    public function sitemap()
    {
        $listings = CarListing::published()->get(['slug', 'updated_at']);

        $xml = view('public.car-prices.sitemap', ['listings' => $listings])->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private const SORT_OPTIONS = [
        'newest' => 'جدیدترین',
        'price_asc' => 'قیمت: کم به زیاد',
        'price_desc' => 'قیمت: زیاد به کم',
    ];

    /**
     * @param  array{0: string, 1?: mixed}  $canonicalRoute
     * @param  array<int, array{label: string, url: string}>  $breadcrumbs
     */
    private function renderIndex(Builder $query, string $title, string $heading, string $description, array $canonicalRoute, array $breadcrumbs)
    {
        $q = (string) request()->string('q', '');
        if ($q !== '') {
            $query->where(function (Builder $w) use ($q) {
                $w->where('title_fa', 'like', "%{$q}%")
                    ->orWhere('make', 'like', "%{$q}%")
                    ->orWhere('model', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        $sort = request()->string('sort', 'newest')->value();
        $sort = array_key_exists($sort, self::SORT_OPTIONS) ? $sort : 'newest';
        match ($sort) {
            'price_asc' => $query->orderBy('price_aed'),
            'price_desc' => $query->orderByDesc('price_aed'),
            default => $query->latest('published_at'),
        };

        $listings = $query->with('images')->paginate(12)->withQueryString();

        return view('public.car-prices.index', [
            'title' => $title,
            'heading' => $heading,
            'description' => $description,
            'listings' => $listings,
            'canonicalUrl' => route(...$canonicalRoute),
            'breadcrumbs' => $breadcrumbs,
            'quickFilters' => $this->quickFilters(),
            'freeRate' => (float) Setting::get(Setting::FREE_RATE),
            'customsRate' => (float) Setting::get(Setting::CUSTOMS_RATE),
            'searchQuery' => $q,
            'sort' => $sort,
            'sortOptions' => self::SORT_OPTIONS,
        ]);
    }

    /**
     * برچسب‌های سریع سئو-محور: برند، دستهٔ خودرو، بازهٔ قیمت — برای صفحهٔ لیست و صفحات فیلترشده.
     */
    private function quickFilters(): array
    {
        $brands = CarListing::published()
            ->whereNotNull('make')->where('make', '!=', '')
            ->distinct()->orderBy('make')->pluck('make');

        return [
            'brands' => $brands->map(fn ($make) => [
                'label' => (string) Str::of($make)->replace('-', ' ')->title(),
                'url' => route('public.car-prices.brand', $make),
            ])->all(),
            'categories' => collect(VehiclePricingCatalog::CATEGORIES)->map(fn ($cat, $id) => [
                'label' => $cat['label'],
                'url' => route('public.car-prices.category', $id),
            ])->values()->all(),
            'priceBrackets' => collect(CarListing::PRICE_BRACKETS)->map(fn ($b, $id) => [
                'label' => $b['label'],
                'url' => route('public.car-prices.price', $id),
            ])->values()->all(),
        ];
    }
}
