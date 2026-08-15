<?php

namespace App\Services;

use App\Models\CarListing;
use App\Models\Invoice;
use App\Models\QuoteRequest;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * تولید فایل PDF پیش‌فاکتور — هم برای برآورد آنی که مشتری از صفحهٔ آگهی خودرو
 * درخواست می‌کند (از QuoteRequest) و هم برای پیش‌فاکتور رسمی که کارشناسان از
 * پنل مدیریت صادر می‌کنند (از Invoice) — هر دو از یک قالب Blade مشترک استفاده
 * می‌کنند تا ظاهر یکسان و کامل داشته باشند.
 */
class ProformaPdfGenerator
{
    private function contact(): array
    {
        return [
            'iran' => Setting::get(Setting::WHATSAPP_IRAN),
            'uae' => Setting::get(Setting::WHATSAPP_UAE),
            'tehran' => Setting::get(Setting::TEHRAN_OFFICE_PHONE),
        ];
    }

    private function fonts(): array
    {
        return [
            'fontRegular' => 'file://'.resource_path('fonts/NotoSansArabic-Regular.ttf'),
            'fontBold' => 'file://'.resource_path('fonts/NotoSansArabic-Bold.ttf'),
        ];
    }

    public function fromQuoteRequest(QuoteRequest $lead, ?CarListing $listing = null): string
    {
        $breakdown = $lead->breakdown();
        $totals = $lead->totals();

        $totalsSummary = [];
        foreach ($totals as $label => $amount) {
            $totalsSummary[] = [
                'label' => $label,
                'amount' => $amount,
                'emphasis' => mb_strpos((string) $label, 'نهایی') !== false,
            ];
        }

        $html = view('pdf.proforma', array_merge($this->fonts(), [
            'docTitle' => 'برآورد اولیهٔ هزینهٔ واردات خودرو',
            'docNumber' => 'EST-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT),
            'docDate' => $lead->created_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'validUntil' => now()->addDays(3)->format('Y-m-d'),
            'customerName' => $lead->name,
            'customerPhone' => $lead->phone,
            'customerEmail' => $lead->email,
            'carLabel' => $lead->car_label,
            'categoryLabel' => $lead->categoryLabel(),
            'breakdown' => $breakdown,
            'totalsSummary' => $totalsSummary,
            'contact' => $this->contact(),
            'footerNote' => 'این سند یک برآورد آنیِ اولیه بر اساس نرخ‌های ثبت‌شده در سیستم ناوراکار در لحظهٔ محاسبه است و ماهیت الزام‌آور ندارد. '
                .'مقادیر ممکن است با تغییر مقررات گمرکی یا نرخ ارز به‌روزرسانی شوند. برای پیش‌فاکتور رسمی و قطعی، کارشناسان ناوراکار به‌زودی با شما تماس می‌گیرند.',
        ]))->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $path = 'proformas/quote-'.$lead->id.'.pdf';
        Storage::disk('public')->makeDirectory('proformas');
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function fromInvoice(Invoice $invoice, string $locale = 'fa'): string
    {
        $discount = (float) ($invoice->discount_amount ?? 0);
        $grandTotal = (float) $invoice->total_amount;
        $payable = $grandTotal - $discount;
        $currency = $invoice->currency ?? 'toman';
        $unitLabel = Invoice::CURRENCIES[$currency] ?? 'تومان';
        $exRate = (float) ($invoice->exchange_rate ?? 0);

        $breakdown = array_map(fn ($row) => [
            'key' => $row['key'] ?? null,
            'label' => ProformaBreakdownLocalizer::label($row, $locale),
            'rate' => $row['rate'] ?? '',
            'amount' => ($row['amount'] ?? '').' '.$unitLabel,
        ], $invoice->breakdown());

        $totalsSummary = [
            ['label' => 'جمع کل قبل از تخفیف', 'amount' => number_format($grandTotal).' '.$unitLabel],
        ];
        if ($discount > 0) {
            $totalsSummary[] = ['label' => 'تخفیف', 'amount' => '- '.number_format($discount).' '.$unitLabel];
        }
        $totalsSummary[] = ['label' => 'مبلغ قابل‌پرداخت', 'amount' => number_format($payable).' '.$unitLabel, 'emphasis' => true];
        if ($currency !== 'toman' && $exRate > 0) {
            $totalsSummary[] = ['label' => 'معادل تقریبی به تومان (نرخ '.number_format($exRate).')', 'amount' => number_format($payable * $exRate).' تومان'];
        }

        $html = view('pdf.proforma', array_merge($this->fonts(), [
            'locale' => $locale,
            'docTitle' => 'پیش‌فاکتور رسمی'.(($invoice->invoice_type ?? 'full') === 'single_item' ? ' — خدمت مجزا' : ' هزینه واردات خودرو'),
            'docNumber' => $invoice->invoice_number,
            'docDate' => $invoice->created_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'validUntil' => optional($invoice->valid_until)->format('Y-m-d'),
            'customerName' => $invoice->customer_name,
            'customerPhone' => $invoice->customer_phone,
            'customerEmail' => $invoice->customer_email,
            'carLabel' => $invoice->car_label,
            'categoryLabel' => $invoice->categoryLabel(),
            'breakdown' => $breakdown,
            'totalsSummary' => $totalsSummary,
            'contact' => $this->contact(),
            'footerNote' => 'این پیش‌فاکتور بر اساس اطلاعات و نرخ‌های ثبت‌شده در تاریخ صدور تنظیم شده و ممکن است با تغییر مقررات گمرکی یا نرخ ارز به‌روزرسانی شود. '
                .'این سند صرفاً جنبهٔ برآوردی دارد و برای تعیین قطعی، قرارداد نهایی با کارشناسان ناوراکار ملاک عمل است.',
        ]))->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $path = 'proformas/invoice-'.$invoice->id.'.pdf';
        Storage::disk('public')->makeDirectory('proformas');
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}

