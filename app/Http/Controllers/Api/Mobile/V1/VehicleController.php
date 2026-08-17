<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Support\MobileVehiclePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request, MobileVehiclePresenter $presenter): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'], 'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'], 'fuel' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:20'], 'year_min' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'year_max' => ['nullable', 'integer', 'min:1900', 'max:2100'], 'engine_min' => ['nullable', 'integer', 'min:0'],
            'engine_max' => ['nullable', 'integer', 'min:0'], 'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'], 'sort' => ['nullable', 'in:newest,price_asc,price_desc,year_desc'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);
        $query = CarListing::published()->with('images');
        $this->filters($query, $data);
        match ($data['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price_aed'),
            'price_desc' => $query->orderByDesc('price_aed'),
            'year_desc' => $query->orderByDesc('model_year'),
            default => $query->latest('published_at'),
        };
        $page = $query->paginate($data['per_page'] ?? 12);
        $facets = CarListing::published();

        return response()->json([
            'data' => $page->getCollection()->map(fn (CarListing $listing) => $presenter->summary($listing))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'facets' => [
                'makes' => (clone $facets)->whereNotNull('make')->distinct()->orderBy('make')->pluck('make'),
                'fuels' => (clone $facets)->whereNotNull('fuel_type')->distinct()->orderBy('fuel_type')->pluck('fuel_type'),
            ],
        ]);
    }

    public function show(string $slug, MobileVehiclePresenter $presenter): JsonResponse
    {
        $listing = CarListing::published()->with('images')->where('slug', $slug)->firstOrFail();

        return response()->json($presenter->detail($listing));
    }

    private function filters(Builder $query, array $data): void
    {
        if ($q = $data['q'] ?? null) {
            $query->where(fn (Builder $builder) => $builder
                ->where('title_fa', 'like', "%{$q}%")->orWhere('title_en', 'like', "%{$q}%")
                ->orWhere('make', 'like', "%{$q}%")->orWhere('model', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%"));
        }
        foreach (['make' => 'make', 'model' => 'model', 'fuel' => 'fuel_type', 'category' => 'category_id'] as $input => $column) {
            if (isset($data[$input])) $query->where($column, $data[$input]);
        }
        if (isset($data['year_min'])) $query->where('model_year', '>=', $data['year_min']);
        if (isset($data['year_max'])) $query->where('model_year', '<=', $data['year_max']);
        if (isset($data['engine_min'])) $query->whereRaw('CAST(engine_capacity_cc AS INTEGER) >= ?', [$data['engine_min']]);
        if (isset($data['engine_max'])) $query->whereRaw('CAST(engine_capacity_cc AS INTEGER) <= ?', [$data['engine_max']]);
        if (isset($data['price_min'])) $query->where('price_aed', '>=', $data['price_min']);
        if (isset($data['price_max'])) $query->where('price_aed', '<=', $data['price_max']);
    }
}
