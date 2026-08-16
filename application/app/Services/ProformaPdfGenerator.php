<?php

namespace App\Services;

use App\Models\CarListing;
use App\Models\Invoice;
use App\Models\QuoteRequest;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
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

        return $this->renderAndStore($html, 'proformas/quote-'.$lead->id.'.pdf', $lead->id, 'quote');
    }

    public function fromInvoice(Invoice $invoice, string $language = 'fa'): string
    {
        $language = $language === 'en' ? 'en' : 'fa';
        if ($language === 'en') {
            return $this->fromInvoiceEnglish($invoice);
        }
        $discount = (float) ($invoice->discount_amount ?? 0);
        $grandTotal = (float) $invoice->total_amount;
        $payable = $grandTotal - $discount;
        $currency = $invoice->currency ?? 'toman';
        $unitLabel = Invoice::CURRENCIES[$currency] ?? 'تومان';
        $exRate = (float) ($invoice->exchange_rate ?? 0);

        $breakdown = array_map(fn ($row) => [
            'label' => $row['label'] ?? '',
            'rate' => $row['rate'] ?? '',
            'amount' => $this->formatAmount($row['amount'] ?? '').' '.$unitLabel,
        ], $invoice->breakdown());

        $totalsSummary = [
            ['label' => 'جمع کل قبل از تخفیف', 'amount' => $this->formatAmount($grandTotal).' '.$unitLabel],
        ];
        if ($discount > 0) {
            $totalsSummary[] = ['label' => 'تخفیف', 'amount' => '- '.$this->formatAmount($discount).' '.$unitLabel];
        }
        $totalsSummary[] = ['label' => 'مبلغ قابل‌پرداخت', 'amount' => $this->formatAmount($payable).' '.$unitLabel, 'emphasis' => true];
        if ($currency !== 'toman' && $exRate > 0) {
            $totalsSummary[] = ['label' => 'معادل تقریبی به تومان (نرخ '.$this->formatAmount($exRate).')', 'amount' => $this->formatAmount($payable * $exRate).' تومان'];
        }

        $html = view('pdf.proforma', array_merge($this->fonts(), [
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

        return $this->renderAndStore($html, 'proformas/invoice-'.$invoice->id.'.pdf', $invoice->id, 'fa');
    }

    private function fromInvoiceEnglish(Invoice $invoice): string
    {
        $discount = (float) ($invoice->discount_amount ?? 0);
        $grandTotal = (float) $invoice->total_amount;
        $payable = $grandTotal - $discount;
        $currency = $invoice->currency ?? 'toman';
        $unitLabel = Invoice::CURRENCIES[$currency] ?? 'Toman';
        $exRate = (float) ($invoice->exchange_rate ?? 0);
        $breakdown = array_map(fn ($row) => [
            'label' => ProformaBreakdownLocalizer::label($row, 'en'),
            'rate' => $row['rate'] ?? '',
            'amount' => $this->formatAmount($row['amount'] ?? 0).' '.$unitLabel,
        ], $invoice->breakdown());
        $totalsSummary = [
            ['label' => 'Subtotal before discount', 'amount' => $this->formatAmount($grandTotal).' '.$unitLabel],
        ];
        if ($discount > 0) {
            $totalsSummary[] = ['label' => 'Discount', 'amount' => '- '.$this->formatAmount($discount).' '.$unitLabel];
        }
        $totalsSummary[] = ['label' => 'Total payable', 'amount' => $this->formatAmount($payable).' '.$unitLabel, 'emphasis' => true];
        if ($currency !== 'toman' && $exRate > 0) {
            $totalsSummary[] = ['label' => 'Approximate Toman equivalent (rate '.$this->formatAmount($exRate).')', 'amount' => $this->formatAmount($payable * $exRate).' Toman'];
        }
        $html = view('pdf.proforma-en', array_merge($this->fonts(), [
            'docTitle' => ($invoice->invoice_type ?? 'full') === 'single_item' ? 'Service Proforma Invoice' : 'Vehicle Import Proforma Invoice',
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
        ]))->render();
        $path = 'proformas/invoice-'.$invoice->id.'-en.pdf';

        return $this->renderAndStore($html, $path, $invoice->id, 'en');
    }

    private function renderAndStore(string $html, string $path, int $recordId, string $language): string
    {
        try {
            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
            Storage::disk('public')->makeDirectory('proformas');
            Storage::disk('public')->put($path, $pdf->output());

            return $path;
        } catch (\Throwable $exception) {
            $message = preg_replace(
                '/(password|secret|token|api[_-]?key|authorization)\s*[:=]\s*[^\s,;]+/i',
                '$1=[redacted]',
                $exception->getMessage()
            ) ?? 'PDF generation failed';
            Log::error('Proforma PDF generation failed', [
                'record_id' => $recordId,
                'language' => $language,
                'exception' => $exception::class,
                'message' => mb_substr($message, 0, 500),
            ]);

            throw $exception;
        }
    }

    private function formatAmount(mixed $value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        $number = (float) str_replace(',', '', (string) $value);
        $decimals = fmod($number, 1.0) === 0.0 ? 0 : 2;

        return number_format($number, $decimals, '.', ',');
    }
}
