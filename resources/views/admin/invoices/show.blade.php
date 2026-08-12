@php
    $discount = (float) ($invoice->discount_amount ?? 0);
    $grandTotal = (float) $invoice->total_amount;
    $payable = $grandTotal - $discount;
    $currency = $invoice->currency ?? 'toman';
    $unitLabel = $currency === 'aed' ? 'درهم (AED)' : 'تومان';
    $exRate = (float) ($invoice->exchange_rate ?? 0);
    $isSingleItem = ($invoice->invoice_type ?? 'full') === 'single_item';
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پیش‌فاکتور {{ $invoice->invoice_number }} | ناوراکار</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-100 p-4 font-sans text-ink-900 sm:p-8">

    <div class="no-print mx-auto mb-5 flex max-w-3xl flex-wrap justify-end gap-2.5">
        <a href="{{ route('admin.invoices.index') }}" class="rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm font-bold text-ink-600">← بازگشت به لیست</a>
        <a href="{{ route('admin.invoices.create', ['id' => $invoice->id]) }}" class="rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 px-4 py-2.5 text-sm font-bold text-ink-950">✏️ ویرایش پیش‌فاکتور</a>
        <form method="POST" action="{{ route('admin.invoices.status', $invoice) }}">
            @csrf
            <select name="status" onchange="this.form.submit()" class="rounded-xl border border-ink-200 bg-white px-3 py-2.5 text-sm">
                <option value="پیش‌نویس" @selected($invoice->status === 'پیش‌نویس')>پیش‌نویس</option>
                <option value="ارسال‌شده" @selected($invoice->status === 'ارسال‌شده')>ارسال‌شده</option>
                <option value="تایید شده" @selected($invoice->status === 'تایید شده')>تایید شده (فروخته شد)</option>
            </select>
        </form>
        <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white">دانلود فایل PDF</a>
        <button onclick="window.print()" class="rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white">چاپ / ذخیره PDF</button>
    </div>

    <div class="mx-auto max-w-3xl overflow-hidden rounded-2xl shadow-soft-lg print:rounded-none print:shadow-none">
        <div class="flex flex-wrap items-start justify-between gap-3.5 bg-gradient-to-l from-brand-950 to-brand-800 p-7 text-white">
            <div>
                <div class="text-2xl font-black">ناوراکار</div>
                <div class="mt-1 text-sm text-brand-200">پیش‌فاکتور رسمی {{ $isSingleItem ? '— خدمت مجزا' : 'هزینه واردات خودرو' }}</div>
            </div>
            <div class="text-left text-sm leading-8">
                شماره: <b class="text-amber-300">{{ $invoice->invoice_number }}</b><br>
                تاریخ: {{ $invoice->created_at->format('Y-m-d') }}<br>
                @if ($invoice->valid_until)اعتبار تا: <b class="text-amber-300">{{ $invoice->valid_until->format('Y-m-d') }}</b><br>@endif
                وضعیت: <span class="rounded-full bg-amber-500 px-3.5 py-1 text-xs font-extrabold text-ink-950">{{ $invoice->status }}</span>
            </div>
        </div>

        <div class="border border-t-0 border-ink-200 bg-white p-7">
            <div class="mb-5 rounded-xl bg-brand-50 p-4">
                <div class="mb-2.5 text-base font-extrabold text-brand-900">مشخصات مشتری{{ $isSingleItem ? '' : ' و خودرو' }}</div>
                <div class="grid grid-cols-2 gap-x-5 gap-y-2 text-sm">
                    <div><span class="block text-xs text-ink-500">نام مشتری</span>{{ $invoice->customer_name }}</div>
                    <div><span class="block text-xs text-ink-500">شماره تماس</span>{{ $invoice->customer_phone }}</div>
                    @if ($invoice->customer_email)<div><span class="block text-xs text-ink-500">ایمیل</span>{{ $invoice->customer_email }}</div>@endif
                    @unless ($isSingleItem)
                        <div><span class="block text-xs text-ink-500">خودرو</span>{{ $invoice->car_label ?: '-' }}</div>
                        <div><span class="block text-xs text-ink-500">دسته خودرو</span>{{ $invoice->category ?: '-' }}</div>
                    @endunless
                    @if ($invoice->customer_address)<div class="col-span-2"><span class="block text-xs text-ink-500">آدرس</span>{{ $invoice->customer_address }}</div>@endif
                </div>
            </div>

            <h3 class="mb-2 mt-6 border-b-2 border-brand-100 pb-1.5 text-base font-extrabold text-brand-900">تفکیک هزینه‌ها <span class="text-xs font-normal text-ink-500">(واحد: {{ $unitLabel }})</span></h3>
            <table class="w-full text-sm">
                <thead><tr class="bg-ink-50 text-xs text-ink-500"><th class="p-2.5 text-start">شرح</th><th class="p-2.5 text-start">نرخ / توضیح</th><th class="p-2.5 text-start">مبلغ</th></tr></thead>
                <tbody>
                    @foreach ($breakdown as $row)
                        <tr class="border-b border-ink-100"><td class="p-2.5">{{ $row['label'] ?? '' }}</td><td class="p-2.5 text-xs text-ink-500">{{ $row['rate'] ?? '' }}</td><td class="num-font p-2.5 font-bold">{{ $row['amount'] ?? '' }}</td></tr>
                    @endforeach
                </tbody>
            </table>

            <h3 class="mb-2 mt-6 border-b-2 border-brand-100 pb-1.5 text-base font-extrabold text-brand-900">جمع‌بندی</h3>
            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b border-ink-100"><td class="p-2.5" colspan="2">جمع کل قبل از تخفیف</td><td class="num-font p-2.5 font-bold">{{ number_format($grandTotal) }} {{ $unitLabel }}</td></tr>
                    @if ($discount > 0)
                        <tr class="border-b border-ink-100 text-amber-700"><td class="p-2.5" colspan="2">تخفیف</td><td class="num-font p-2.5 font-bold">− {{ number_format($discount) }} {{ $unitLabel }}</td></tr>
                    @endif
                    <tr class="bg-brand-50 text-brand-900"><td class="p-3" colspan="2 " style="font-weight:900;">مبلغ قابل‌پرداخت</td><td class="num-font p-3 text-base font-black">{{ number_format($payable) }} {{ $unitLabel }}</td></tr>
                    @if ($currency === 'aed' && $exRate > 0)
                        <tr><td class="p-2.5 text-xs text-ink-500" colspan="2">معادل تقریبی به تومان (نرخ {{ number_format($exRate) }})</td><td class="num-font p-2.5 text-sm text-ink-500">{{ number_format($payable * $exRate) }} تومان</td></tr>
                    @endif
                </tbody>
            </table>

            @if ($invoice->payment_terms)
                <div class="mt-4 text-xs text-ink-500"><b>شرایط پرداخت:</b> {{ $invoice->payment_terms }}</div>
            @endif

            <div class="mt-6 rounded-xl bg-ink-50 p-4 text-sm leading-8">
                <div class="mb-1 font-extrabold text-brand-900">📞 ارتباط با ما</div>
                🇮🇷 {{ $whatsappIran }} (واتس‌اپ | بله | تلگرام)<br>
                🇦🇪 {{ $whatsappUae }} (واتس‌اپ | تلگرام)<br>
                ☎️ {{ $tehranOfficePhone }} (دفتر تهران)<br>
                🌐 navaracar.com
            </div>

            <div class="mt-9 flex justify-between border-t border-dashed border-ink-200 pt-4.5 text-sm text-ink-500">
                <div>مهر و امضای ناوراکار</div>
                <div>امضای مشتری</div>
            </div>

            <div class="mt-6 border-t border-ink-200 pt-3.5 text-xs leading-7 text-ink-500">
                این پیش‌فاکتور بر اساس اطلاعات و نرخ‌های ثبت‌شده در تاریخ صدور تنظیم شده و ممکن است با تغییر مقررات گمرکی یا نرخ ارز به‌روزرسانی شود.
                این سند صرفاً جنبه برآوردی دارد و برای تعیین قطعی، قرارداد نهایی با کارشناسان ناوراکار ملاک عمل است.
            </div>
        </div>
    </div>
</body>
</html>
