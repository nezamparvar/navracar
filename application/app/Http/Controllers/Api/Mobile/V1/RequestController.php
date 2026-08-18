<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QuoteRequest::query()->with('stage')->where('mobile_customer_id', $request->attributes->get('mobile_customer')->id)
            ->latest('created_at')->paginate(20);

        return response()->json(['data' => $items->getCollection()->map(fn (QuoteRequest $quote) => [
            'id' => $quote->id,
            'reference' => 'NR-'.str_pad((string) $quote->id, 8, '0', STR_PAD_LEFT),
            'car' => $quote->car_label,
            'category' => $quote->categoryLabel(),
            'status' => $quote->follow_up_status,
            'stage' => $quote->stage?->name,
            'total_toman' => (float) $quote->total_with_profit,
            'created_at' => $quote->created_at?->toIso8601String(),
        ])->values(), 'meta' => ['total' => $items->total()]]);
    }
}
