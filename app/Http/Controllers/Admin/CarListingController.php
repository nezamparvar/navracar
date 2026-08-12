<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\CarListingImage;
use App\Models\Setting;
use App\Services\CarImageDownloader;
use App\Services\CarListingMapper;
use App\Services\DubizzleParser;
use App\Services\DubizzleTranslator;
use App\Services\SocialPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CarListingController extends Controller
{
    public function __construct(
        private readonly DubizzleParser $parser,
        private readonly DubizzleTranslator $translator,
        private readonly CarListingMapper $mapper,
        private readonly CarImageDownloader $imageDownloader,
        private readonly SocialPublisher $socialPublisher,
    ) {}

    public function index()
    {
        $listings = CarListing::with('images')->latest()->paginate(20);

        return view('admin.car-listings.index', [
            'pageTitle' => 'آگهی‌های دابیزل (قیمت خودروها)',
            'listings' => $listings,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_url' => ['required', 'url', 'max:1000'],
            'html_source' => ['nullable', 'string'],
        ]);

        $html = $data['html_source'] ?? null;

        if (! $html) {
            $fetched = $this->parser->fetch($data['source_url']);
            if ($fetched['error']) {
                return back()->withInput()->with('error', $fetched['error']);
            }
            $html = $fetched['html'];
        }

        $raw = $this->parser->parse($html, $data['source_url']);

        if (empty($raw['title_en']) && empty($raw['price_aed'])) {
            return back()->withInput()->with('error', 'استخراج اطلاعات از این HTML ناموفق بود — ساختار صفحه ممکن است تغییر کرده باشد.');
        }

        $listing = $this->createFromRaw($data['source_url'], $raw, $request->user()->id);

        return redirect()->route('admin.car-listings.edit', $listing)
            ->with('success', 'آگهی با موفقیت دریافت شد. لطفاً قبل از انتشار، فیلدها و دسته‌بندی را بررسی کنید.');
    }

    public function create()
    {
        return view('admin.car-listings.create', [
            'pageTitle' => 'افزودن آگهی دستی',
            'listing' => new CarListing([
                'category_id' => 'c2000',
                'delivery_days' => (int) Setting::get(Setting::DEFAULT_DELIVERY_DAYS),
            ]),
            'categories' => CarListing::categoriesWithLiveRates(),
        ]);
    }

    public function storeManual(Request $request)
    {
        $data = $request->validate([
            'title_fa' => ['required', 'string', 'max:500'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:car_listings,slug'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'trim_level' => ['nullable', 'string', 'max:255'],
            'model_year' => ['nullable', 'string', 'max:10'],
            'price_aed' => ['required', 'numeric', 'min:0'],
            'kilometers' => ['nullable', 'string', 'max:50'],
            'category_id' => ['required', Rule::in(array_keys(CarListing::CATEGORIES))],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
            'body_type' => ['nullable', 'string', 'max:100'],
            'fuel_type' => ['nullable', 'string', 'max:100'],
            'transmission_type' => ['nullable', 'string', 'max:100'],
            'regional_specs' => ['nullable', 'string', 'max:100'],
            'steering_side' => ['nullable', 'string', 'max:100'],
            'seller_type' => ['nullable', 'string', 'max:100'],
            'warranty' => ['nullable', 'string', 'max:50'],
            'exterior_color' => ['nullable', 'string', 'max:100'],
            'interior_color' => ['nullable', 'string', 'max:100'],
            'horsepower' => ['nullable', 'string', 'max:100'],
            'engine_capacity_cc' => ['nullable', 'string', 'max:100'],
            'no_of_cylinders' => ['nullable', 'string', 'max:20'],
            'doors' => ['nullable', 'string', 'max:50'],
            'seating_capacity' => ['nullable', 'string', 'max:50'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = $data['slug'] ?? null;
        unset($data['slug']);
        if (empty($slug)) {
            $slug = $this->mapper->slugify($data);
        }

        $listing = CarListing::create([
            ...$data,
            'slug' => $slug,
            'source_url' => '',
            'source_site' => 'manual',
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.car-listings.edit', $listing)
            ->with('success', 'آگهی دستی ایجاد شد. حالا می‌توانید عکس اضافه کنید.');
    }

    public function edit(CarListing $carListing)
    {
        $carListing->load('images');
        $cover = $carListing->coverImage();

        $priceLine = number_format((float) $carListing->price_aed).' درهم';
        $caption = $this->socialPublisher->buildCaption(
            title: $carListing->title_fa,
            description: $carListing->meta_description,
            priceLine: $priceLine,
            url: route('public.car-prices.show', $carListing),
            hashtags: array_filter(['ناوراکار', 'واردات_خودرو', 'قیمت_خودرو', $carListing->make, $carListing->model]),
        );

        return view('admin.car-listings.edit', [
            'pageTitle' => 'ویرایش آگهی: '.($carListing->title_fa ?: $carListing->title_en),
            'listing' => $carListing,
            'categories' => CarListing::categoriesWithLiveRates(),
            'socialHasImage' => (bool) $cover,
            'socialWhatsappUrl' => $this->socialPublisher->whatsAppShareUrl($caption),
        ]);
    }

    public function update(Request $request, CarListing $carListing)
    {
        $data = $request->validate([
            'title_fa' => ['required', 'string', 'max:500'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:car_listings,slug,'.$carListing->id],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'trim_level' => ['nullable', 'string', 'max:255'],
            'model_year' => ['nullable', 'string', 'max:10'],
            'price_aed' => ['required', 'numeric', 'min:0'],
            'kilometers' => ['nullable', 'string', 'max:50'],
            'category_id' => ['required', Rule::in(array_keys(CarListing::CATEGORIES))],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
            'body_type' => ['nullable', 'string', 'max:100'],
            'fuel_type' => ['nullable', 'string', 'max:100'],
            'transmission_type' => ['nullable', 'string', 'max:100'],
            'regional_specs' => ['nullable', 'string', 'max:100'],
            'steering_side' => ['nullable', 'string', 'max:100'],
            'seller_type' => ['nullable', 'string', 'max:100'],
            'warranty' => ['nullable', 'string', 'max:50'],
            'exterior_color' => ['nullable', 'string', 'max:100'],
            'interior_color' => ['nullable', 'string', 'max:100'],
            'horsepower' => ['nullable', 'string', 'max:100'],
            'engine_capacity_cc' => ['nullable', 'string', 'max:100'],
            'no_of_cylinders' => ['nullable', 'string', 'max:20'],
            'doors' => ['nullable', 'string', 'max:50'],
            'seating_capacity' => ['nullable', 'string', 'max:50'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'cover_image_id' => ['nullable', 'integer', 'exists:car_listing_images,id'],
            'sort_order' => ['nullable', 'array'],
            'sort_order.*' => ['integer'],
        ]);

        $carListing->update(collect($data)->except(['cover_image_id', 'sort_order'])->toArray());

        if (! empty($data['sort_order'])) {
            foreach ($data['sort_order'] as $imageId => $order) {
                CarListingImage::where('id', $imageId)
                    ->where('car_listing_id', $carListing->id)
                    ->update(['sort_order' => (int) $order]);
            }
        }

        if (! empty($data['cover_image_id'])) {
            $carListing->images()->update(['is_cover' => false]);
            CarListingImage::where('id', $data['cover_image_id'])
                ->where('car_listing_id', $carListing->id)
                ->update(['is_cover' => true]);
        }

        return back()->with('success', 'تغییرات ذخیره شد.');
    }

    public function publish(CarListing $carListing)
    {
        $carListing->update([
            'status' => 'published',
            'published_at' => $carListing->published_at ?? now(),
        ]);

        return back()->with('success', 'آگهی منتشر شد.');
    }

    public function unpublish(CarListing $carListing)
    {
        $carListing->update(['status' => 'draft']);

        return back()->with('success', 'انتشار آگهی لغو شد.');
    }

    public function refetch(CarListing $carListing)
    {
        $fetched = $this->parser->fetch($carListing->source_url);
        if ($fetched['error']) {
            return back()->with('error', $fetched['error']);
        }

        $raw = $this->parser->parse($fetched['html'], $carListing->source_url);
        $translated = $this->translateRaw($raw);
        $translated['category_id'] = $this->mapper->detectCategory($raw['engine_capacity_cc'] ?? null, $raw['fuel_type'] ?? null);

        $carListing->update($translated);

        $this->imageDownloader->deleteAll($carListing->id);
        $carListing->images()->delete();
        $this->attachImages($carListing, $raw['images'] ?? []);

        return redirect()->route('admin.car-listings.edit', $carListing)
            ->with('success', 'اطلاعات از دابیزل به‌روزرسانی شد. لطفاً دوباره بررسی کنید.');
    }

    public function destroy(CarListing $carListing)
    {
        $this->imageDownloader->deleteAll($carListing->id);
        $carListing->delete();

        return redirect()->route('admin.car-listings.index')->with('success', 'آگهی حذف شد.');
    }

    public function storeImage(Request $request, CarListing $carListing)
    {
        $data = $request->validate([
            'image_url' => ['nullable', 'url'],
            'image_file' => ['nullable', 'image', 'max:8192'],
        ]);

        $maxOrder = (int) $carListing->images()->max('sort_order');

        if (! empty($data['image_file'])) {
            $path = $request->file('image_file')->store("car-listings/{$carListing->id}", 'public');
            $carListing->images()->create([
                'local_path' => $path,
                'source_url' => null,
                'sort_order' => $maxOrder + 1,
            ]);
        } elseif (! empty($data['image_url'])) {
            $saved = $this->imageDownloader->downloadOne($carListing->id, $data['image_url'], $maxOrder + 1);
            if ($saved) {
                $carListing->images()->create([...$saved, 'sort_order' => $maxOrder + 1]);
            } else {
                return back()->with('error', 'دانلود این عکس ناموفق بود.');
            }
        } else {
            return back()->with('error', 'لینک عکس یا فایل را وارد کنید.');
        }

        return back()->with('success', 'عکس اضافه شد.');
    }

    public function showImport()
    {
        return view('admin.car-listings.import', [
            'pageTitle' => 'ایمپورت گروهی از فایل کرالر',
        ]);
    }

    /**
     * ایمپورت گروهی — فایل JSON خروجی ابزار کرالر دسکتاپ را می‌خواند. هر ردیف
     * باید دقیقاً همان شکل خروجی DubizzleParser::parse() را داشته باشد (همان
     * منطقی که مسیر تک‌لینکی از قبل استفاده می‌کند، اینجا هم دوباره استفاده
     * می‌شود تا رفتار دو مسیر یکسان بماند).
     */
    public function import(Request $request)
    {
        $request->validate([
            'json_file' => ['required', 'file', 'max:20480'],
        ]);

        $rows = json_decode($request->file('json_file')->get(), true);
        if (! is_array($rows)) {
            return back()->with('error', 'فایل JSON معتبر نیست — باید یک آرایه از آگهی‌ها باشد.');
        }

        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($rows as $raw) {
            if (! is_array($raw) || empty($raw['source_url'])) {
                $failed++;

                continue;
            }

            if (CarListing::where('source_url', $raw['source_url'])->exists()) {
                $skipped++;

                continue;
            }

            if (empty($raw['title_en']) && empty($raw['price_aed'])) {
                $failed++;

                continue;
            }

            try {
                $this->createFromRaw($raw['source_url'], $raw, $request->user()->id);
                $created++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return redirect()->route('admin.car-listings.index')
            ->with('success', "ایمپورت انجام شد — {$created} آگهی جدید، {$skipped} تکراری (رد شد)، {$failed} ناموفق.");
    }

    public function destroyImage(CarListing $carListing, CarListingImage $image)
    {
        abort_if($image->car_listing_id !== $carListing->id, 404);

        Storage::disk('public')->delete($image->local_path);
        $image->delete();

        return back()->with('success', 'عکس حذف شد.');
    }

    public function publishSocial(Request $request, CarListing $carListing)
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['telegram', 'bale'])],
        ]);

        $image = $carListing->coverImage();
        if (! $image) {
            return response()->json(['ok' => false, 'error' => 'این آگهی عکسی ندارد — ابتدا یک عکس اضافه کنید.'], 422);
        }

        $priceLine = number_format((float) $carListing->price_aed).' درهم';
        $freeRate = (float) Setting::get(Setting::FREE_RATE);
        if ($freeRate > 0) {
            $priceLine .= ' (≈ '.number_format((float) $carListing->price_aed * $freeRate).' تومان)';
        }

        $caption = $this->socialPublisher->buildCaption(
            title: $carListing->title_fa,
            description: $carListing->meta_description,
            priceLine: $priceLine,
            url: route('public.car-prices.show', $carListing),
            hashtags: array_filter(['ناوراکار', 'واردات_خودرو', 'قیمت_خودرو', $carListing->make, $carListing->model]),
        );

        $result = $data['platform'] === 'telegram'
            ? $this->socialPublisher->publishToTelegram($image->url(), $caption)
            : $this->socialPublisher->publishToBale($image->url(), $caption);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    private function createFromRaw(string $sourceUrl, array $raw, int $adminId): CarListing
    {
        $translated = $this->translateRaw($raw);
        $translated['category_id'] = $this->mapper->detectCategory($raw['engine_capacity_cc'] ?? null, $raw['fuel_type'] ?? null);
        $translated['title_fa'] = $this->mapper->buildPersianTitle(array_merge($raw, $translated));
        $translated['meta_title'] = $translated['title_fa'].' | ناوراکار';
        $translated['meta_description'] = $this->mapper->buildMetaDescription($raw, $translated['title_fa']);
        $translated['slug'] = $this->mapper->slugify($raw);

        $listing = CarListing::create([
            ...$translated,
            'source_url' => $sourceUrl,
            'source_site' => 'dubizzle',
            'status' => 'draft',
            'title_en' => $raw['title_en'] ?? null,
            'make' => $raw['make'] ?? null,
            'model' => $raw['model'] ?? null,
            'model_year' => $raw['model_year'] ?? null,
            'description_en' => $raw['description_en'] ?? null,
            'specs_json' => json_encode($raw, JSON_UNESCAPED_UNICODE),
            'posted_on_dubizzle' => $raw['posted_on_dubizzle'] ?? null,
            'delivery_days' => (int) Setting::get(Setting::DEFAULT_DELIVERY_DAYS),
            'created_by' => $adminId,
        ]);

        $this->attachImages($listing, $raw['images'] ?? []);

        return $listing;
    }

    private function translateRaw(array $raw): array
    {
        $fields = [
            'trim_level', 'kilometers', 'regional_specs', 'steering_side', 'location_text',
            'body_type', 'fuel_type', 'transmission_type', 'seller_type', 'warranty',
            'exterior_color', 'interior_color', 'horsepower', 'engine_capacity_cc',
            'no_of_cylinders', 'doors', 'seating_capacity',
        ];

        $out = [];
        foreach ($fields as $field) {
            $out[$field] = $this->translator->value($field, $raw[$field] ?? null);
        }

        return array_merge($out, ['price_aed' => $raw['price_aed'] ?? 0]);
    }

    private function attachImages(CarListing $listing, array $imageUrls): void
    {
        $saved = $this->imageDownloader->downloadAll($listing->id, $imageUrls);

        foreach ($saved as $i => $img) {
            $listing->images()->create([
                'local_path' => $img['local_path'],
                'source_url' => $img['source_url'],
                'sort_order' => $i,
                'is_cover' => $i === 0,
            ]);
        }
    }
}
