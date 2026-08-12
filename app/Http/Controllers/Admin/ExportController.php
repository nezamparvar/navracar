<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalculationLog;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $type = (string) $request->string('type', 'requests');
        $range = (string) $request->string('range', '');
        $from = (string) $request->string('from', '');
        $to = (string) $request->string('to', '');

        if ($range === 'today') {
            $from = $to = now()->toDateString();
        } elseif ($range === 'month') {
            $from = now()->subDays(30)->toDateString();
            $to = now()->toDateString();
        }

        if ($type === 'calculations') {
            return $this->exportCalculations($request, $from, $to);
        }

        return $this->exportRequests($request, $from, $to);
    }

    protected function exportRequests(Request $request, string $from, string $to): StreamedResponse
    {
        $query = QuoteRequest::query();

        if ($q = (string) $request->string('q', '')) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('car_label', 'like', "%{$q}%");
            });
        }
        if ($from) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }
        if (! $request->user()->isAdmin()) {
            $query->where('assigned_to', $request->user()->id);
        }

        $rows = $query->orderByDesc('created_at')->get();
        $filename = 'navarakar-requests-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows) {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, ['شماره', 'تاریخ ثبت', 'نام', 'تلفن', 'ایمیل', 'خودرو', 'دسته', 'منبع', 'کشور', 'شهر', 'جمع کل نهایی (تومان)', 'وضعیت ایمیل', 'وضعیت پیگیری', 'توضیحات', 'IP']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id, $r->created_at, $r->name, $r->phone, $r->email,
                    $r->car_label, $r->category, $r->source, $r->country, $r->city, $r->total_with_profit,
                    $r->email_sent ? 'ارسال شد' : 'نامشخص', $r->follow_up_status, $r->notes, $r->ip_address,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    protected function exportCalculations(Request $request, string $from, string $to): StreamedResponse
    {
        $query = CalculationLog::query();

        if ($cat = (string) $request->string('cat', '')) {
            $query->where('category', $cat);
        }
        if ($from) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        $rows = $query->orderByDesc('created_at')->get();
        $filename = 'navarakar-calculations-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows) {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'شماره', 'تاریخ', 'خودرو', 'دسته', 'قیمت واقعی (درهم)', 'قیمت گمرکی (درهم)',
                'نرخ ارز آزاد', 'نرخ ارز گمرک', 'حمل دریایی (درهم)', 'مجوزها (درهم)', 'انبارداری (تومان)',
                'جمع ترخیص گمرکی', 'جمع پلاک', 'جمع بدون سود', 'سود خدمات', 'جمع کل نهایی', 'IP',
            ]);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id, $r->created_at, $r->car_label, $r->category,
                    $r->real_price_aed, $r->customs_price_aed, $r->free_rate, $r->customs_rate,
                    $r->sea_freight_aed, $r->permits_aed, $r->storage_toman,
                    $r->sum_customs, $r->sum_plate, $r->total_no_profit, $r->service_profit,
                    $r->total_with_profit, $r->ip_address,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
