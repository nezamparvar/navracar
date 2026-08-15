<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Services\ProformaPdfGenerator;
use App\Services\VehiclePricing\VehiclePricingCatalog;
use App\Services\VehiclePricing\VehiclePricingInput;
use App\Services\VehiclePricing\VehiclePricingService;
use App\Services\VehiclePricing\VehiclePricingSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public const QUICK_ROWS = [
        'full' => [
            ['هزینه خدمات اختصاصی', 'توضیح خدمت'],
            ['هزینه حمل تکمیلی', 'مبلغ مورد تأیید'],
        ],
        'single_item' => [
            ['صدور مجوز', 'خدمت مجزا'],
            ['حمل و نقل', 'خدمت مجزا'],
            ['ترخیص گمرکی', 'خدمت مجزا'],
            ['مشاوره و پیگیری اداری', 'خدمت مجزا'],
        ],
    ];

    private function ownsInvoice(Invoice $invoice, $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $invoice->created_by === $user->id
            || ($invoice->request_id && $invoice->request?->assigned_to === $user->id);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Invoice::with('creator');

        if (! $user->isAdmin()) {
            $query->where(function ($builder) use ($user) {
                $builder->where('created_by', $user->id)
                    ->orWhereHas('request', fn ($requestQuery) => $requestQuery->where('assigned_to', $user->id));
            });
        }

        return view('admin.invoices.index', [
            'pageTitle' => 'پیش‌فاکتورها و فروش',
            'rows' => $query->orderByDesc('created_at')->paginate(15),
        ]);
    }

    public function create(Request $request)
    {
        $requestId = (int) $request->input('request_id', 0);
        $editId = (int) $request->input('id', 0);
        $prefill = $this->emptyPrefill();

        if ($editId) {
            $invoice = Invoice::findOrFail($editId);
            abort_unless($this->ownsInvoice($invoice, $request->user()), 403);
            $metadata = $invoice->pricingMetadata();
            $pricingInput = $metadata['pricing_input'] ?? [];
            $prefill = [
                'name' => $invoice->customer_name,
                'phone' => $invoice->customer_phone,
                'email' => $invoice->customer_email ?? '',
                'address' => $invoice->customer_address ?? '',
                'car' => $invoice->car_label ?? '',
                'category' => $invoice->category ?? VehiclePricingCatalog::FALLBACK_CATEGORY,
                'breakdown' => $invoice->breakdown(),
                'total' => $invoice->total_amount,
                'discount' => $invoice->discount_amount ?? 0,
                'currency' => $invoice->currency ?? 'toman',
                'exchange_rate' => $invoice->exchange_rate ?? '',
                'valid_until' => optional($invoice->valid_until)->format('Y-m-d') ?? '',
                'payment_terms' => $invoice->payment_terms ?? '',
                'invoice_type' => $invoice->invoice_type ?? 'full',
                'pricing_mode' => $metadata['pricing_mode'] ?? 'manual',
                'real_price_aed' => $pricingInput['realPriceAed'] ?? 0,
                'customs_price_aed' => $pricingInput['customsPriceAed'] ?? 0,
                'adjustment_amount' => $metadata['adjustment_amount'] ?? 0,
                'adjustment_reason' => $metadata['adjustment_reason'] ?? '',
            ];
        } elseif ($requestId) {
            $lead = QuoteRequest::findOrFail($requestId);
            abort_unless($request->user()->isAdmin() || $lead->assigned_to === $request->user()->id, 403);
            $metadata = $lead->pricingMetadata();
            $pricingInput = $metadata['pricing_input'] ?? [];
            $prefill = array_merge($prefill, [
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email ?? '',
                'car' => $lead->car_label ?? '',
                'category' => $pricingInput['categoryId'] ?? $lead->category ?? VehiclePricingCatalog::FALLBACK_CATEGORY,
                'breakdown' => $lead->breakdown(),
                'total' => $lead->total_with_profit,
                'pricing_mode' => empty($metadata) ? 'manual' : 'automatic',
                'real_price_aed' => $pricingInput['realPriceAed'] ?? 0,
                'customs_price_aed' => $pricingInput['customsPriceAed'] ?? 0,
            ]);
        }

        return view('admin.invoices.create', [
            'pageTitle' => $editId ? 'ویرایش پیش‌فاکتور' : 'صدور پیش‌فاکتور جدید',
            'prefill' => $prefill,
            'editId' => $editId,
            'requestId' => $requestId,
            'categories' => VehiclePricingSettings::current()->categories,
            'quickRows' => self::QUICK_ROWS,
            'currencies' => Invoice::CURRENCIES,
            'pricingUrl' => route('public.vehicle-pricing.calculate'),
        ]);
    }

    public function store(Request $request, VehiclePricingService $pricing)
    {
        $data = $request->validate([
            'invoice_id' => ['nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:64'],
            'customer_email' => ['nullable', 'email'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'car_label' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(VehiclePricingCatalog::categoryIds())],
            'pricing_mode' => ['required', Rule::in(['automatic', 'manual'])],
            'real_price_aed' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'customs_price_aed' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'adjustment_amount' => ['nullable', 'numeric', 'min:-1000000000000000', 'max:1000000000000000'],
            'adjustment_reason' => ['nullable', 'string', 'max:1000'],
            'discount_amount' => ['nullable', 'string', 'max:50'],
            'currency' => ['required', Rule::in(array_keys(Invoice::CURRENCIES))],
            'exchange_rate' => ['nullable', 'string', 'max:50'],
            'valid_until' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:500'],
            'invoice_type' => ['required', Rule::in(['full', 'single_item'])],
            'request_id' => ['nullable', 'integer'],
            'b_label' => ['nullable', 'array'],
            'b_rate' => ['nullable', 'array'],
            'b_amount' => ['nullable', 'array'],
            // Deliberately ignored; the server always derives the total.
            'total_amount' => ['nullable', 'string'],
        ]);

        $mode = $data['pricing_mode'];
        if ($mode === 'automatic' && $data['invoice_type'] !== 'full') {
            throw ValidationException::withMessages(['pricing_mode' => 'محاسبه خودکار فقط برای پیش‌فاکتور کامل خودرو قابل استفاده است.']);
        }

        if ($mode === 'automatic') {
            $calculated = $this->automaticCalculation($data, $pricing);
        } else {
            $calculated = $this->manualCalculation($data);
        }

        $discount = $this->money($data['discount_amount'] ?? '0');
        if ($discount < 0 || $discount > $calculated['total']) {
            throw ValidationException::withMessages(['discount_amount' => 'تخفیف باید بین صفر و جمع کل پیش‌فاکتور باشد.']);
        }

        $payload = [
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? null,
            'customer_address' => $data['customer_address'] ?? null,
            'car_label' => $data['car_label'] ?? null,
            'category' => $calculated['category'],
            'breakdown_json' => json_encode($calculated['breakdown_payload'], JSON_UNESCAPED_UNICODE),
            'total_amount' => $calculated['total'],
            'discount_amount' => $discount,
            'currency' => $calculated['currency'],
            'exchange_rate' => $calculated['exchange_rate'],
            'valid_until' => $data['valid_until'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'invoice_type' => $data['invoice_type'],
        ];

        if (! empty($data['invoice_id'])) {
            $invoice = Invoice::findOrFail($data['invoice_id']);
            abort_unless($this->ownsInvoice($invoice, $request->user()), 403);
            $invoice->update($payload);
        } else {
            $requestId = ! empty($data['request_id']) ? (int) $data['request_id'] : null;
            if ($requestId && ! $request->user()->isAdmin()) {
                $lead = QuoteRequest::find($requestId);
                abort_unless($lead && $lead->assigned_to === $request->user()->id, 403);
            }

            $invoice = Invoice::create($payload + [
                'request_id' => $requestId,
                'invoice_number' => '',
                'status' => 'پیش‌نویس',
                'created_by' => $request->user()->id,
            ]);
            $invoice->update(['invoice_number' => 'NVK-'.now()->year.'-'.str_pad($invoice->id, 5, '0', STR_PAD_LEFT)]);
        }

        return redirect()->route('admin.invoices.show', $invoice);
    }

    public function show(Request $request, Invoice $invoice)
    {
        abort_unless($this->ownsInvoice($invoice, $request->user()), 403);

        return view('admin.invoices.show', [
            'pageTitle' => 'پیش‌فاکتور '.$invoice->invoice_number,
            'invoice' => $invoice,
            'breakdown' => $invoice->breakdown(),
            'whatsappIran' => Setting::get(Setting::WHATSAPP_IRAN),
            'whatsappUae' => Setting::get(Setting::WHATSAPP_UAE),
            'tehranOfficePhone' => Setting::get(Setting::TEHRAN_OFFICE_PHONE),
        ]);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        abort_unless($this->ownsInvoice($invoice, $request->user()), 403);
        $data = $request->validate(['status' => [Rule::in(['پیش‌نویس', 'ارسال‌شده', 'تایید شده'])]]);
        $invoice->update(['status' => $data['status']]);

        return back();
    }

    public function downloadPdf(Request $request, Invoice $invoice, ProformaPdfGenerator $pdfGenerator, string $language = 'fa')
    {
        abort_unless($this->ownsInvoice($invoice, $request->user()), 403);
        $language = $language === 'en' || $request->string('lang')->lower()->value() === 'en' ? 'en' : 'fa';
        $path = $pdfGenerator->fromInvoice($invoice, $language);

        $downloadName = $language === 'fa'
            ? $invoice->invoice_number.'.pdf'
            : $invoice->invoice_number.'-en.pdf';

        return Storage::disk('public')->download($path, $downloadName);
    }

    private function automaticCalculation(array $data, VehiclePricingService $pricing): array
    {
        foreach (['real_price_aed', 'customs_price_aed', 'category'] as $key) {
            if (! isset($data[$key]) || $data[$key] === '') {
                throw ValidationException::withMessages([$key => 'این فیلد برای محاسبه خودکار الزامی است.']);
            }
        }

        $result = $pricing->calculate(VehiclePricingInput::fromArray($data));
        $adjustment = (float) ($data['adjustment_amount'] ?? 0);
        $reason = trim((string) ($data['adjustment_reason'] ?? ''));
        if ($adjustment !== 0.0 && $reason === '') {
            throw ValidationException::withMessages(['adjustment_reason' => 'برای تعدیل مبلغ، ثبت دلیل الزامی است.']);
        }

        $total = $result->totals['finalTotalToman'] + $adjustment;
        if ($total < 0) {
            throw ValidationException::withMessages(['adjustment_amount' => 'تعدیل نمی‌تواند جمع کل را منفی کند.']);
        }

        $rows = array_map(fn (array $row) => [
            'key' => $row['key'],
            'label' => $row['label'],
            'rate' => $row['rate'],
            'amount' => $row['value'],
        ], $result->breakdownRows());
        if ($adjustment !== 0.0) {
            $rows[] = ['key' => 'approved_adjustment', 'label' => 'تعدیل مورد تأیید', 'rate' => $reason, 'amount' => $adjustment];
        }

        return [
            'category' => $result->category['id'],
            'total' => $total,
            'currency' => 'toman',
            'exchange_rate' => null,
            'breakdown_payload' => [
                'rows' => $rows,
                'pricing_mode' => 'automatic',
                'pricing_input' => $result->input,
                'pricing_snapshot' => $result->settingsSnapshot,
                'pricing_result' => $result->toArray(),
                'calculated_total' => $result->totals['finalTotalToman'],
                'adjustment_amount' => $adjustment,
                'adjustment_reason' => $reason,
                'engine_version' => 'v1.2.0',
            ],
        ];
    }

    private function manualCalculation(array $data): array
    {
        $reason = trim((string) ($data['adjustment_reason'] ?? ''));
        if ($reason === '') {
            throw ValidationException::withMessages(['adjustment_reason' => 'دلیل استفاده از ویرایش دستی الزامی است.']);
        }

        $rows = [];
        $total = 0.0;
        foreach ($data['b_label'] ?? [] as $index => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $amount = $this->money($data['b_amount'][$index] ?? '0');
            if ($amount < 0) {
                throw ValidationException::withMessages(['b_amount.'.$index => 'مبلغ ردیف نمی‌تواند منفی باشد.']);
            }
            $rows[] = [
                'key' => 'manual_'.$index,
                'label' => $label,
                'rate' => trim((string) ($data['b_rate'][$index] ?? '')),
                'amount' => $amount,
            ];
            $total += $amount;
        }
        if (empty($rows)) {
            throw ValidationException::withMessages(['b_label' => 'حداقل یک ردیف هزینه دستی الزامی است.']);
        }

        $currency = $data['currency'];
        $exchangeRate = $currency === 'toman' ? null : $this->money($data['exchange_rate'] ?? '0');
        if ($currency !== 'toman' && $exchangeRate <= 0) {
            throw ValidationException::withMessages(['exchange_rate' => 'برای واحد پول غیرتومان، نرخ تبدیل مثبت الزامی است.']);
        }

        return [
            'category' => $data['category'] ?? null,
            'total' => $total,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'breakdown_payload' => [
                'rows' => $rows,
                'pricing_mode' => 'manual',
                'adjustment_reason' => $reason,
                'calculated_total' => null,
                'engine_version' => 'v1.2.0',
            ],
        ];
    }

    private function emptyPrefill(): array
    {
        return [
            'name' => '', 'phone' => '', 'email' => '', 'address' => '', 'car' => '',
            'category' => VehiclePricingCatalog::FALLBACK_CATEGORY,
            'breakdown' => [], 'total' => 0, 'discount' => 0, 'currency' => 'toman', 'exchange_rate' => '',
            'valid_until' => '', 'payment_terms' => '', 'invoice_type' => 'full',
            'pricing_mode' => 'automatic', 'real_price_aed' => 0, 'customs_price_aed' => 0,
            'adjustment_amount' => 0, 'adjustment_reason' => '',
        ];
    }

    private function money(string|int|float|null $value): float
    {
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    }
}

