<?php

namespace App\Http\Controllers\Admin;

use App\Models\CarListing;
use App\Models\ImportQueue;
use App\Services\VehiclePricing\VehiclePricingSettings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ImportQueueController extends Controller
{
    public function index(Request $request)
    {
        $query = ImportQueue::with('carListing', 'duplicatesWith')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->get('source'));
        }

        $items = $query->paginate(20);

        return view('admin.import-queue.index', [
            'items' => $items,
            'statuses' => ImportQueue::STATUSES,
            'sources' => ImportQueue::SOURCES,
        ]);
    }

    public function show(ImportQueue $importQueue)
    {
        $importQueue->load('carListing', 'duplicatesWith');

        $settings = VehiclePricingSettings::current();
        $customsDiscount = $settings->categories['default']['tariffPercent'] ?? 30;

        $suggestedCustomsPrice = null;
        if ($importQueue->captured_data['vehicle']['price_aed'] ?? null) {
            $suggestedCustomsPrice = $importQueue->captured_data['vehicle']['price_aed'] * (1 - $customsDiscount / 100);
        }

        return view('admin.import-queue.show', [
            'item' => $importQueue,
            'suggestedCustomsPrice' => $suggestedCustomsPrice,
            'customsDiscountPercent' => $customsDiscount,
        ]);
    }

    public function update(Request $request, ImportQueue $importQueue)
    {
        if ($importQueue->status === 'published') {
            return redirect()->back()->with('error', 'Cannot edit published imports');
        }

        $validated = $request->validate([
            'vehicle.title' => 'nullable|string|max:255',
            'vehicle.make' => 'nullable|string|max:100',
            'vehicle.model' => 'nullable|string|max:100',
            'vehicle.trim' => 'nullable|string|max:100',
            'vehicle.year' => 'nullable|string|max:4',
            'vehicle.price_aed' => 'nullable|numeric|min:0',
            'vehicle.mileage_km' => 'nullable|string|max:100',
            'vehicle.fuel_type' => 'nullable|string|max:100',
            'vehicle.engine' => 'nullable|string|max:100',
            'vehicle.transmission' => 'nullable|string|max:100',
            'vehicle.body_type' => 'nullable|string|max:100',
            'vehicle.description' => 'nullable|string|max:5000',
        ]);

        $parsed = $importQueue->parsed_data ?? $importQueue->captured_data;

        foreach ($validated as $key => $value) {
            if (is_array($value)) {
                $parsed[$key] = array_merge($parsed[$key] ?? [], $value);
            }
        }

        $importQueue->update([
            'parsed_data' => $parsed,
            'status' => 'needs_review',
        ]);

        return redirect()->route('admin.import-queue.show', $importQueue)
            ->with('success', 'Import updated');
    }

    public function publish(ImportQueue $importQueue)
    {
        if ($importQueue->status === 'published') {
            return redirect()->back()->with('error', 'Already published');
        }

        if ($importQueue->images_imported < $importQueue->image_count) {
            return redirect()->back()->with('warning', 'Not all images have been imported yet');
        }

        $data = $importQueue->parsed_data ?? $importQueue->captured_data;

        $listing = CarListing::create([
            'source_url' => $data['source_url'],
            'source_site' => $data['source'],
            'status' => 'published',
            'title_en' => $data['vehicle']['title'],
            'make' => $data['vehicle']['make'],
            'model' => $data['vehicle']['model'],
            'trim_level' => $data['vehicle']['trim'] ?? null,
            'model_year' => $data['vehicle']['year'],
            'price_aed' => $data['vehicle']['price_aed'],
            'kilometers' => $data['vehicle']['mileage_km'],
            'body_type' => $data['vehicle']['body_type'],
            'fuel_type' => $data['vehicle']['fuel_type'],
            'transmission_type' => $data['vehicle']['transmission'],
            'engine_capacity_cc' => $data['vehicle']['engine'],
            'description_en' => $data['vehicle']['description'],
            'created_by' => auth()->id(),
            'published_at' => now(),
        ]);

        $importQueue->update([
            'car_listing_id' => $listing->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        return redirect()->route('admin.car-listings.edit', $listing)
            ->with('success', 'Listing published successfully');
    }

    public function cancel(ImportQueue $importQueue)
    {
        if ($importQueue->status === 'published') {
            return redirect()->back()->with('error', 'Cannot cancel published import');
        }

        $importQueue->update(['status' => 'failed']);

        return redirect()->back()->with('success', 'Import cancelled');
    }

    public function retryImages(ImportQueue $importQueue)
    {
        // This will be implemented with the image import system
        // For now, queue the retry
        $importQueue->update(['status' => 'images_pending']);

        return redirect()->back()->with('success', 'Image import retry queued');
    }
}
