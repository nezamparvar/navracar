<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\CarListingImage;
use App\Models\HomeSlide;
use App\Models\ImportQueueItem;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ContentDashboardController extends Controller
{
    /**
     * The review-queue item quality score's text/numeric criteria — 7 of the 8 fields
     * DESIGN_SPEC.md calls out for a marketplace-imported listing (title, brand/model, year,
     * price, mileage, engine volume). The 8th criterion, "at least one image", is NOT a
     * parsed_json key so it is checked separately (ImportQueueItem::images_imported > 0) in
     * decorateQueueItem() — both are counted in the single formula there:
     * score = (present criteria out of count(QUALITY_FIELDS) + 1 image criterion) * 100, rounded.
     * Deterministic, no randomness, no fabricated figures. See ContentDashboardWidgetsTest for
     * the exact contract this formula must match.
     */
    private const QUALITY_FIELDS = ['title', 'make', 'model', 'year', 'price_aed', 'mileage_km', 'engine_capacity_cc'];

    public function __invoke(Request $request)
    {
        $needsReviewStatuses = ['pending', 'captured', 'parsed', 'needs_review', 'image_importing'];

        $incompleteMetaListings = CarListing::query()
            ->where(function ($q) {
                $q->whereNull('meta_title')->orWhere('meta_title', '')
                    ->orWhereNull('meta_description')->orWhere('meta_description', '');
            })
            ->count();

        $reviewQueue = ImportQueueItem::where('status', 'needs_review')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (ImportQueueItem $item) => $this->decorateQueueItem($item));

        [$healthFields, $healthOverall] = $this->contentHealth();

        $urgentTasks = [
            [
                'count' => $incompleteMetaListings,
                'label' => 'آگهی متای ناقص دارند',
                'route' => route('admin.car-listings.index'),
            ],
            [
                'count' => $failedImports = ImportQueueItem::where('status', 'failed')->count(),
                'label' => 'ایمپورت ناموفق نیاز به بررسی دارند',
                'route' => route('admin.import-queue.index'),
            ],
            [
                'count' => $noImageListings = CarListing::doesntHave('images')->count(),
                'label' => 'آگهی بدون تصویر منتشر شده‌اند',
                'route' => route('admin.car-listings.index'),
            ],
            [
                'count' => $draftListings = CarListing::where('status', 'draft')->count(),
                'label' => 'آگهی پیش‌نویس منتظر انتشار هستند',
                'route' => route('admin.car-listings.index'),
            ],
        ];

        return view('admin.content-dashboard', [
            'pageTitle' => 'داشبورد محتوا',
            'pageSubtitle' => 'نمای کلی از آگهی‌های خودرو، مقالات و اسلایدهای صفحه اصلی.',
            'publishedListings' => CarListing::where('status', 'published')->count(),
            'draftListings' => $draftListings,
            'needsReviewImports' => ImportQueueItem::whereIn('status', $needsReviewStatuses)->count(),
            'failedImports' => $failedImports,
            'publishedPosts' => Post::where('status', 'published')->count(),
            'draftPosts' => Post::where('status', 'draft')->count(),
            'activeSlides' => HomeSlide::where('is_active', true)->count(),
            'recentListings' => CarListing::latest('created_at')->limit(5)->get(['id', 'slug', 'title_fa', 'status', 'created_at']),
            'recentPosts' => Post::latest('created_at')->limit(5)->get(['id', 'title', 'status', 'created_at']),
            'incompleteMetaListings' => $incompleteMetaListings,
            'reviewQueue' => $reviewQueue,
            'healthFields' => $healthFields,
            'healthOverall' => $healthOverall,
            'urgentTasks' => $urgentTasks,
            'publishActivity' => $this->publishActivity(),
            'contentSummary' => [
                'listings' => CarListing::count(),
                'posts' => Post::count(),
                'slides' => HomeSlide::count(),
                'media' => CarListingImage::count(),
            ],
        ]);
    }

    /**
     * Per-item review-queue quality score from the real marketplace-import payload
     * (parsed_json) plus the real imported-image count — no fabricated numbers, see
     * self::QUALITY_FIELDS's doc comment for the exact 8-criterion formula.
     */
    private function decorateQueueItem(ImportQueueItem $item): array
    {
        $data = $item->parsed_json ?? [];
        $present = 0;
        foreach (self::QUALITY_FIELDS as $field) {
            if (! empty($data[$field]) || (is_numeric($data[$field] ?? null) && $data[$field] != 0)) {
                $present++;
            }
        }
        $imagesImported = (int) $item->images_imported;
        if ($imagesImported > 0) {
            $present++;
        }
        $totalCriteria = count(self::QUALITY_FIELDS) + 1;
        $score = (int) round($present / $totalCriteria * 100);

        $description = trim((string) ($data['description'] ?? ''));
        $metaState = $description === '' ? 'بدون متا' : (mb_strlen($description) < 80 ? 'نیازمند اصلاح' : 'کامل');

        return [
            'item' => $item,
            'title' => $data['title'] ?? ($data['make'] ?? '').' '.($data['model'] ?? ''),
            'source' => match ($item->source_platform) {
                'dubicars' => 'DubiCars',
                'yallamotor' => 'YallaMotor',
                'dubizzle' => 'Dubizzle',
                default => $item->source_platform ?: 'دستی',
            },
            'imagesImported' => $imagesImported,
            'score' => $score,
            'metaState' => $metaState,
        ];
    }

    /**
     * Deterministic per-field completeness across the real catalog (CarListing), matching
     * 04-content-dashboard.png's "سلامت محتوای آگهی‌ها" widget field set. Overall = simple
     * average of the per-field percentages. Returns [0, 0] with no listings to avoid a
     * division-by-zero / misleading 100% on an empty catalog.
     */
    private function contentHealth(): array
    {
        $total = CarListing::count();
        if ($total === 0) {
            return [[], 0];
        }

        $fields = [
            'عنوان' => CarListing::whereNotNull('title_fa')->where('title_fa', '!=', '')->count(),
            'برند و مدل' => CarListing::whereNotNull('make')->where('make', '!=', '')->whereNotNull('model')->where('model', '!=', '')->count(),
            'حجم موتور' => CarListing::whereNotNull('engine_capacity_cc')->count(),
            'قیمت' => CarListing::where('price_aed', '>', 0)->count(),
            'متا تایتل' => CarListing::whereNotNull('meta_title')->where('meta_title', '!=', '')->count(),
            'متا دیسکریپشن' => CarListing::whereNotNull('meta_description')->where('meta_description', '!=', '')->count(),
            'تصاویر' => CarListing::has('images')->count(),
        ];

        $percents = [];
        foreach ($fields as $label => $count) {
            $percents[$label] = (int) round($count / $total * 100);
        }

        $overall = (int) round(array_sum($percents) / count($percents));

        return [$percents, $overall];
    }

    /**
     * Real weekly publish activity — CarListing/Post grouped by their actual published_at date,
     * HomeSlide by created_at (slides have no separate publish concept). Last 7 days, oldest
     * first, Persian weekday labels for the chart.
     */
    private function publishActivity(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));
        // Carbon::dayOfWeek is 0 (Sunday) .. 6 (Saturday) regardless of app locale.
        $weekdayLabels = [0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه'];

        return $days->map(function (Carbon $day) use ($weekdayLabels) {
            return [
                'label' => $weekdayLabels[$day->dayOfWeek],
                'date' => $day->toDateString(),
                'listings' => CarListing::whereDate('published_at', $day)->count(),
                'posts' => Post::whereDate('published_at', $day)->count(),
                'slides' => HomeSlide::whereDate('created_at', $day)->count(),
            ];
        })->values()->all();
    }
}
