<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ProformaInvoiceMail;
use App\Mail\QuoteRequestReceived;
use App\Models\QuoteRequest;
use App\Services\GeoLookupService;
use App\Services\ProformaPdfGenerator;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class QuoteController extends Controller
{
    public function store(Request $request, GeoLookupService $geo, ProformaPdfGenerator $pdfGenerator)
    {
        // Honeypot: bots fill hidden fields — silently report success without saving.
        if (! empty($request->input('website'))) {
            return response()->json(['success' => true, 'id' => 0, 'message' => 'درخواست با موفقیت ثبت و ارسال شد.']);
        }

        $loadedAt = (float) $request->input('pageLoadedAt', 0);
        if ($loadedAt > 0 && (microtime(true) * 1000 - $loadedAt) < 1500) {
            return response()->json(['success' => false, 'message' => 'لطفاً کمی صبر کنید و دوباره تلاش کنید.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'email'],
            'notes' => ['nullable', 'string'],
            'car' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'breakdown' => ['nullable', 'array'],
            'totals' => ['nullable', 'array'],
        ]);

        $breakdown = $data['breakdown'] ?? [];
        $totals = $data['totals'] ?? [];

        $totalWithProfit = 0.0;
        foreach ($totals as $label => $val) {
            if (mb_strpos((string) $label, 'نهایی') !== false) {
                $digits = preg_replace('/[^0-9.]/', '', (string) $val);
                $totalWithProfit = $digits !== '' ? (float) $digits : 0;
            }
        }

        $geoData = $geo->lookup($request->ip());

        $lead = QuoteRequest::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'car_label' => $data['car'] ?? null,
            'category' => $data['category'] ?? null,
            'breakdown_json' => json_encode($breakdown, JSON_UNESCAPED_UNICODE),
            'totals_json' => json_encode($totals, JSON_UNESCAPED_UNICODE),
            'total_with_profit' => $totalWithProfit,
            'email_sent' => false,
            'source' => 'سایت',
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
        if (! empty($breakdown)) {
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
        }

        $message = 'درخواست شما با موفقیت ثبت شد.';
        if ($pdfUrl) {
            $message = $customerEmailOk
                ? 'درخواست شما ثبت شد؛ پیش‌فاکتور PDF هم برای شما ایمیل شد و هم می‌توانید همین‌جا دانلود کنید.'
                : 'درخواست شما ثبت شد؛ پیش‌فاکتور PDF آماده شد — می‌توانید همین‌جا دانلود کنید.';
        } elseif (! $emailOk) {
            $message = 'درخواست شما ثبت شد؛ اما ارسال ایمیل با تأخیر مواجه شد. کارشناسان از پنل مدیریت آن را می‌بینند.';
        }

        return response()->json(['success' => true, 'id' => $lead->id, 'message' => $message, 'pdfUrl' => $pdfUrl]);
    }

    public function downloadPdf(QuoteRequest $quoteRequest)
    {
        $path = 'proformas/quote-'.$quoteRequest->id.'.pdf';

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path, 'proforma-navracar-'.$quoteRequest->id.'.pdf');
    }
}
