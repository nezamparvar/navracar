<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;

class RequestTrackingController extends Controller
{
    /**
     * Lookup form + handler. A request number alone would let anyone enumerate other
     * customers' requests, so lookup requires the exact phone number submitted with the
     * original request too (same two-factor pattern couriers use for order tracking).
     */
    public function find(Request $request)
    {
        $number = trim((string) $request->query('number', ''));
        $phone = trim((string) $request->query('phone', ''));

        if ($number === '' || $phone === '') {
            return view('public.track.find', ['notFound' => false]);
        }

        $quoteRequest = QuoteRequest::where('id', $number)->where('phone', $phone)->first();

        if (! $quoteRequest) {
            return view('public.track.find', ['notFound' => true]);
        }

        return redirect()->route('public.track.show', ['quoteRequest' => $quoteRequest->id, 'phone' => $phone]);
    }

    public function show(Request $request, QuoteRequest $quoteRequest)
    {
        abort_unless($quoteRequest->phone === trim((string) $request->query('phone', '')), 404);

        $quoteRequest->load(['stage', 'invoices' => fn ($q) => $q->latest('created_at')]);

        // A real, honest timeline built only from timestamped facts safe to show a customer —
        // request creation and invoice issuance. LeadActivity notes are internal CRM
        // commentary (freeform staff notes) and are never surfaced here. There is no
        // stage-change history table, so intermediate pipeline-stage transitions cannot be
        // listed individually; the current stage is shown separately instead.
        $timeline = collect([
            ['label' => 'ثبت درخواست استعلام قیمت', 'at' => $quoteRequest->created_at],
        ])->merge(
            $quoteRequest->invoices->map(fn ($invoice) => [
                'label' => 'صدور صورت‌حساب ('.$invoice->status.')',
                'at' => $invoice->created_at,
            ])
        )->filter(fn ($row) => $row['at'] !== null)->sortBy('at')->values();

        return view('public.track.show', [
            'title' => 'پیگیری درخواست #'.$quoteRequest->id.' | ناوراکار',
            'quoteRequest' => $quoteRequest,
            'timeline' => $timeline,
            'latestInvoice' => $quoteRequest->invoices->first(),
        ]);
    }
}
