<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\CarListingImage;
use App\Models\ImportQueueItem;
use App\Models\Setting;
use App\Services\CarImageDownloader;
use App\Services\CarListingMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ImportQueueController extends Controller
{
    public function __construct(
        private readonly CarListingMapper $mapper,
        private readonly CarImageDownloader $images,
    ) {}

    public function index(Request $request): View
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(ImportQueueItem::STATUSES)],
            'source' => ['nullable', Rule::in(['dubizzle', 'dubicars', 'yallamotor'])],
        ]);
        $query = ImportQueueItem::with('publishedListing')->latest();
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! empty($data['source'])) {
            $query->where('source', $data['source']);
        }

        return view('admin.import-queue.index', [
            'pageTitle' => 'صف بررسی ایمپورت',
            'rows' => $query->paginate(30)->withQueryString(),
            'filters' => $data,
            'statuses' => ImportQueueItem::STATUSES,
        ]);
    }

    public function show(ImportQueueItem $importQueue): View
    {
        return view('admin.import-queue.show', [
            'pageTitle' => 'بررسی ایمپورت #'.$importQueue->id,
            'item' => $importQueue,
            'vehicle' => $importQueue->parsed_json ?? [],
            'payload' => $importQueue->payload_json ?? [],
        ]);
    }

    public function update(Request $request, ImportQueueItem $importQueue): RedirectResponse
    {
        if ($importQueue->status === 'published') {
            return back()->with('error', 'مورد منتشرشده قابل ویرایش نیست.');
        }
        $validatedVehicle = $request->validate([
            'vehicle' => ['required', 'array:title,make,model,year,price_aed,mileage_km,fuel_type,transmission,body_type,color,description,engine_capacity_cc'],
            'vehicle.title' => ['required', 'string', 'max:500'],
            'vehicle.make' => ['nullable', 'string', 'max:100'],
            'vehicle.model' => ['nullable', 'string', 'max:100'],
            'vehicle.year' => ['nullable', 'string', 'max:10'],
            'vehicle.price_aed' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'vehicle.mileage_km' => ['nullable', 'string', 'max:50'],
            'vehicle.fuel_type' => ['nullable', 'string', 'max:100'],
            'vehicle.transmission' => ['nullable', 'string', 'max:100'],
            'vehicle.body_type' => ['nullable', 'string', 'max:100'],
            'vehicle.color' => ['nullable', 'string', 'max:100'],
            'vehicle.description' => ['nullable', 'string', 'max:10000'],
            'vehicle.engine_capacity_cc' => ['nullable', 'string', 'max:100'],
        ])['vehicle'];
        $vehicle = array_merge($importQueue->parsed_json ?? [], $validatedVehicle);
        $payload = $importQueue->payload_json ?? [];
        $payload['vehicle'] = $vehicle;
        $importQueue->update(['parsed_json' => $vehicle, 'payload_json' => $payload, 'status' => 'needs_review']);

        return back()->with('success', 'اطلاعات ذخیره شد.');
    }

    public function publish(ImportQueueItem $importQueue): RedirectResponse
    {
        if (! in_array($importQueue->status, ['needs_review', 'ready'], true)) {
            return back()->with('error', 'وضعیت فعلی قابل انتشار نیست.');
        }
        $data = $importQueue->parsed_json ?? [];
        if (empty($data['title']) || ! isset($data['price_aed'])) {
            return back()->with('error', 'عنوان و قیمت برای ساخت پیش‌نویس الزامی است.');
        }
        $meta = $this->mapper->resolveMeta($data, $data['title']);

        $listing = DB::transaction(function () use ($importQueue, $data, $meta) {
            $slugData = $data;
            $slugData['model_year'] = $data['year'] ?? null;
            $listing = CarListing::create([
                'source_url' => $importQueue->source_url,
                'source_site' => $importQueue->source,
                'status' => 'draft',
                'slug' => $this->mapper->slugify($slugData),
                'title_en' => $data['title'],
                'title_fa' => $data['title'],
                'make' => $data['make'] ?? null,
                'model' => $data['model'] ?? null,
                'trim_level' => $data['trim'] ?? null,
                'model_year' => $data['year'] ?? null,
                'price_aed' => $data['price_aed'],
                'customs_price_aed' => null,
                'kilometers' => $data['mileage_km'] ?? null,
                'body_type' => $data['body_type'] ?? null,
                'fuel_type' => $data['fuel_type'] ?? null,
                'engine_capacity_cc' => $data['engine_capacity_cc'] ?? null,
                'transmission_type' => $data['transmission'] ?? null,
                'regional_specs' => $data['regional_specs'] ?? null,
                'steering_side' => $data['steering_side'] ?? null,
                'seller_type' => $data['seller_type'] ?? null,
                'warranty' => $data['warranty'] ?? null,
                'exterior_color' => $data['exterior_color'] ?? $data['color'] ?? null,
                'interior_color' => $data['interior_color'] ?? null,
                'horsepower' => $data['horsepower'] ?? null,
                'no_of_cylinders' => $data['no_of_cylinders'] ?? null,
                'doors' => $data['doors'] ?? null,
                'seating_capacity' => $data['seating_capacity'] ?? null,
                'category_id' => $this->mapper->detectCategory($data['engine_capacity_cc'] ?? null, $data['fuel_type'] ?? null),
                'delivery_days' => (int) Setting::get(Setting::DEFAULT_DELIVERY_DAYS),
                'description_en' => $data['description'] ?? null,
                'posted_on_dubizzle' => $data['posted_on'] ?? null,
                'meta_title' => $meta['meta_title'],
                'meta_description' => $meta['meta_description'],
                'created_by' => $importQueue->user_id,
            ]);
            $importQueue->update(['status' => 'published', 'published_listing_id' => $listing->id]);

            return $listing;
        });

        $urls = collect($importQueue->payload_json['images'] ?? [])->pluck('url')->filter()->values()->all();
        $saved = $this->images->downloadAll($listing->id, $urls);
        foreach ($saved as $index => $image) {
            CarListingImage::create([
                'car_listing_id' => $listing->id,
                'source_url' => $image['source_url'],
                'local_path' => $image['local_path'],
                'sort_order' => $index,
                'is_cover' => $index === 0,
            ]);
        }
        $importQueue->update(['images_imported' => count($saved)]);

        return redirect()->route('admin.car-listings.edit', $listing)
            ->with('success', 'پیش‌نویس آگهی ساخته شد؛ پیش از انتشار بررسی کنید.');
    }

    public function cancel(ImportQueueItem $importQueue): RedirectResponse
    {
        if ($importQueue->status !== 'published') {
            $importQueue->update(['status' => 'cancelled']);
        }

        return back()->with('success', 'ایمپورت لغو شد.');
    }
}
