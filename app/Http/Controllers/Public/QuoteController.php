<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ProformaInvoiceMail;
use App\Mail\QuoteRequestReceived;
use App\Models\QuoteRequest;
use App\Services\GeoLookupService;
use App\Services\MobileTokenAuthenticator;
use App\Services\ProformaPdfGenerator;
use App\Services\VehiclePricing\VehiclePricingCatalog;
use App\Services\VehiclePricing\VehiclePricingService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class QuoteController extends Controller
{
    public function store(
        Request $request,
        GeoLookupService $geo,
        ProformaPdfGenerator $pdfGenerator,
        VehiclePricingService $pricing,
        MobileTokenAuthenticator $mobileTokens,
    ) {
        if (! empty($request->input('website'))) {
            return response()->json(['success' => true, 'id' => 0, 'message' => 'درخواست با موفقیت ثبت شد.']);
        }

        $loadedAt = (float) $request->input('pageLoadedAt', 0);
        if ($loadedAt > 0 && (microtime(true) * 1000 - $loadedAt) < 1500) {
            return response()->json(['success' => false, 'message' => 'لطفاً کمی صبر کنید و دوباره تلاش کنید.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'email'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'car' => ['nullable', 'string', 'max:255'],
            'pricing' => ['required', 'array'],
            'pricing.real_price_aed' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'pricing.customs_price_aed' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'pricing.category' => ['required', Rule::in(VehiclePricingCatalog::categoryIds())],
            // Accepted only for backward compatibility and deliberately ignored.
            'breakdown' => ['nullable', 'array'],
            'totals' => ['nullable', 'array'],
        ]);

        $result = $pricing->calculate($pricing->inputFromArray($data['pricing']));
        $breakdown = $result->breakdownRows(formatted: true, excludeServiceFee: true);
        $totals = $result->displayTotals();
        $geoData = $geo->lookup($request->ip());
        $mobileCustomer = $mobileTokens->resolve($request->bearerToken())['customer'] ?? null;

        $lead = QuoteRequest::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'car_label' => $data['car'] ?? null,
            'category' => $result->category['id'],
            'breakdown_json' => json_encode($breakdown, JSON_UNESCAPED_UNICODE),
            'totals_json' => json_encode([
                'display' => $totals,
                'pricing_input' => $result->input,
                'pricing_snapshot' => $result->settingsSnapshot,
                'pricing_result' => $result->toArray(),
                'engine_version' => 'v1.2.0',
            ], JSON_UNESCAPED_UNICODE),
            'total_with_profit' => $result->totals['finalTotalToman'],
            'email_sent' => false,
            'source' => $request->is('api/mobile/*') ? 'Android' : 'سایت',
            'mobile_customer_id' => $mobileCustomer?->id,
            'country' => $geoData['country'],
            'city' => $geoData['city'],
            'ip_address' => $request->ip(),
        ]);

        ActivityLogger::info('درخواست استعلام قیمت ثبت شد', ['id' => $lead->id, 'name' => $lead->name, 'phone' => $lead->phone]);

        $emailOk = false;
        try {
            Mail::to(config('navaracar.notify_email'))->send(new QuoteRequestReceived($lead, $breakdown, $totals));
            $emailOk = true;
            $lead->update(['email_sent' => true]);
        } catch (\Throwable $e) {
            ActivityLogger::error('ارسال ایمیل درخواست استعلام ناموفق بود', ['error' => $e->getMessage(), 'id' => $lead->id]);
        }

        $pdfUrl = null;
        $customerEmailOk = null;
        try {
            $pdfPath = $pdfGenerator->fromQuoteRequest($lead);
            $pdfUrl = URL::signedRoute('public.quote-requests.pdf', ['quoteRequest' => $lead->id]);

            if ($lead->email) {
                try {
                    Mail::to($lead->email)->send(new ProformaInvoiceMail($lead, Storage::disk('public')->path($pdfPath)));
                    $customerEmailOk = true;
                } catch (\Throwable $e) {
                    $customerEmailOk = false;
                    ActivityLogger::error('ارسال ایمیل پیش‌فاکتور به مشتری ناموفق بود', ['error' => $e->getMessage(), 'id' => $lead->id]);
                }
            }
        } catch (\Throwable $e) {
            ActivityLogger::error('ساخت فایل PDF پیش‌فاکتور ناموفق بود', ['error' => $e->getMessage(), 'id' => $lead->id]);
        }

        $message = 'درخواست شما با موفقیت ثبت شد.';
        if ($pdfUrl) {
            $message = $customerEmailOk
                ? 'درخواست ثبت شد؛ پیش‌فاکتور PDF ایمیل شده و آماده دانلود است.'
                : 'درخواست ثبت شد و پیش‌فاکتور PDF آماده دانلود است.';
        } elseif (! $emailOk) {
            $message = 'درخواست ثبت شد؛ ارسال ایمیل با تأخیر مواجه شد و کارشناسان آن را در پنل می‌بینند.';
        }

        return response()->json([
            'success' => true,
            'id' => $lead->id,
            'message' => $message,
            'pdfUrl' => $pdfUrl,
            'pricing' => $result->toArray(),
        ]);
    }

    public function downloadPdf(QuoteRequest $quoteRequest)
    {
        $path = 'proformas/quote-'.$quoteRequest->id.'.pdf';

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path, 'proforma-navracar-'.$quoteRequest->id.'.pdf');
    }
}
