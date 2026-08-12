<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Services\ProformaPdfGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public const CATEGORIES = ['هیبرید / برقی', 'زیر ۱۵۰۰ سی‌سی', '۱۵۰۰ تا ۲۰۰۰ سی‌سی', '۲۰۰۰ تا ۲۵۰۰ سی‌سی', '۲۵۰۰ تا ۳۰۰۰ سی‌سی', 'بالای ۳۰۰۰ سی‌سی'];

    public const QUICK_ROWS = [
        'full' => [
            ['سود بازرگانی', 'بر اساس دسته خودرو'],
            ['حقوق گمرکی ثابت', '۴٪ از ارزش گمرکی'],
            ['عوارض و مالیات گمرکی', ''],
            ['مالیات ارزش افزوده', '۱۰٪'],
            ['هزینه پلاک انتظامی', ''],
            ['صدور مجوزها', ''],
            ['حمل دریایی', ''],
            ['سود خدمات ناوراکار', ''],
        ],
        'single_item' => [
            ['صدور مجوز', 'خدمت مجزا'],
            ['حمل و نقل', 'خدمت مجزا'],
            ['ترخیص گمرکی', 'خدمت مجزا'],
            ['مشاوره و پیگیری اداری', 'خدمت مجزا'],
        ],
    ];

    public function index()
    {
        $rows = Invoice::with('creator')->orderByDesc('created_at')->paginate(15);

        return view('admin.invoices.index', [
            'pageTitle' => 'پیش‌فاکتورها و فروش',
            'rows' => $rows,
        ]);
    }

    public function create(Request $request)
    {
        $requestId = (int) $request->input('request_id', 0);
        $editId = (int) $request->input('id', 0);

        $prefill = [
            'name' => '', 'phone' => '', 'email' => '', 'address' => '', 'car' => '', 'category' => '',
            'breakdown' => [], 'total' => 0, 'discount' => 0, 'currency' => 'toman', 'exchange_rate' => '',
            'valid_until' => '', 'payment_terms' => '', 'invoice_type' => 'full',
        ];

        $invoice = null;
        if ($editId) {
            $invoice = Invoice::find($editId);
            if ($invoice) {
                $prefill = [
                    'name' => $invoice->customer_name, 'phone' => $invoice->customer_phone,
                    'email' => $invoice->customer_email ?? '', 'address' => $invoice->customer_address,
                    'car' => $invoice->car_label, 'category' => $invoice->category,
                    'breakdown' => $invoice->breakdown(), 'total' => $invoice->total_amount,
                    'discount' => $invoice->discount_amount ?? 0, 'currency' => $invoice->currency ?? 'toman',
                    'exchange_rate' => $invoice->exchange_rate ?? '', 'valid_until' => optional($invoice->valid_until)->format('Y-m-d') ?? '',
                    'payment_terms' => $invoice->payment_terms ?? '', 'invoice_type' => $invoice->invoice_type ?? 'full',
                ];
            }
        } elseif ($requestId) {
            $lead = QuoteRequest::find($requestId);
            if ($lead) {
                $breakdown = $lead->breakdown();
                foreach ($lead->totals() as $label => $val) {
                    if (mb_strpos((string) $label, 'سود خدمات') !== false) {
                        $breakdown[] = ['label' => $label, 'rate' => 'طبق نرخ سود خدمات ناوراکار', 'amount' => $val];
                    }
                }
                $prefill = array_merge($prefill, [
                    'name' => $lead->name, 'phone' => $lead->phone, 'email' => $lead->email ?? '',
                    'car' => $lead->car_label, 'category' => $lead->category,
                    'breakdown' => $breakdown, 'total' => $lead->total_with_profit,
                ]);
            }
        }

        return view('admin.invoices.create', [
            'pageTitle' => $editId ? 'ویرایش پیش‌فاکتور' : 'صدور پیش‌فاکتور جدید',
            'prefill' => $prefill,
            'editId' => $editId,
            'requestId' => $requestId,
            'categories' => self::CATEGORIES,
            'quickRows' => self::QUICK_ROWS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => ['nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:64'],
            'customer_email' => ['nullable', 'email'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'car_label' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'total_amount' => ['required', 'string'],
            'discount_amount' => ['nullable', 'string'],
            'currency' => [Rule::in(['toman', 'aed'])],
            'exchange_rate' => ['nullable', 'string'],
            'valid_until' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:500'],
            'invoice_type' => [Rule::in(['full', 'single_item'])],
            'request_id' => ['nullable', 'integer'],
            'b_label' => ['nullable', 'array'],
            'b_rate' => ['nullable', 'array'],
            'b_amount' => ['nullable', 'array'],
        ]);

        $breakdown = [];
        foreach ($data['b_label'] ?? [] as $i => $label) {
            if (trim($label) === '') {
                continue;
            }
            $breakdown[] = [
                'label' => trim($label),
                'rate' => trim($data['b_rate'][$i] ?? ''),
                'amount' => trim($data['b_amount'][$i] ?? ''),
            ];
        }

        $payload = [
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? null,
            'customer_address' => $data['customer_address'] ?? null,
            'car_label' => $data['car_label'] ?? null,
            'category' => $data['category'] ?? null,
            'breakdown_json' => json_encode($breakdown, JSON_UNESCAPED_UNICODE),
            'total_amount' => (float) preg_replace('/[^0-9.]/', '', $data['total_amount']),
            'discount_amount' => (float) preg_replace('/[^0-9.]/', '', $data['discount_amount'] ?? '0'),
            'currency' => $data['currency'],
            'exchange_rate' => ! empty($data['exchange_rate']) ? (float) preg_replace('/[^0-9.]/', '', $data['exchange_rate']) : null,
            'valid_until' => $data['valid_until'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'invoice_type' => $data['invoice_type'],
        ];

        if (! empty($data['invoice_id'])) {
            $invoice = Invoice::findOrFail($data['invoice_id']);
            $invoice->update($payload);
            $id = $invoice->id;
        } else {
            $invoice = Invoice::create($payload + [
                'request_id' => $data['request_id'] ?? null,
                'invoice_number' => '',
                'status' => 'پیش‌نویس',
                'created_by' => $request->user()->id,
            ]);
            $invoice->update(['invoice_number' => 'NVK-'.now()->year.'-'.str_pad($invoice->id, 5, '0', STR_PAD_LEFT)]);
            $id = $invoice->id;
        }

        return redirect()->route('admin.invoices.show', $id);
    }

    public function show(Invoice $invoice)
    {
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
        $data = $request->validate(['status' => [Rule::in(['پیش‌نویس', 'ارسال‌شده', 'تایید شده'])]]);
        $invoice->update(['status' => $data['status']]);

        return back();
    }

    public function downloadPdf(Invoice $invoice, ProformaPdfGenerator $pdfGenerator)
    {
        $path = $pdfGenerator->fromInvoice($invoice);

        return Storage::disk('public')->download($path, $invoice->invoice_number.'.pdf');
    }
}
