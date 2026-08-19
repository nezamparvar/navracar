<x-layouts.admin :page-title="$pageTitle">

    <p class="mb-5 text-sm text-v2-text-muted">مجموع {{ $rows->total() }} پیش‌فاکتور صادرشده.</p>

    <x-card variant="v2">
        <div class="mb-5"><x-button :href="route('admin.invoices.create')" variant="v2-primary"><x-icon name="plus" class="w-4 h-4" /> صدور پیش‌فاکتور جدید</x-button></div>

        @if ($rows->isEmpty())
            <x-empty-state icon="invoice" title="هنوز پیش‌فاکتوری صادر نشده." variant="v2" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-v2-border text-xs font-extrabold text-v2-text-muted">
                            <th class="px-2.5 py-2 text-start">شماره</th><th class="px-2.5 py-2 text-start">تاریخ</th>
                            <th class="px-2.5 py-2 text-start">مشتری</th><th class="px-2.5 py-2 text-start">خودرو</th>
                            <th class="px-2.5 py-2 text-start">مبلغ قابل‌پرداخت</th><th class="px-2.5 py-2 text-start">وضعیت</th>
                            <th class="px-2.5 py-2 text-start">صادرکننده</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr class="border-b border-v2-border hover:bg-v2-elevated">
                                <td class="num-font px-2.5 py-2.5">{{ $r->invoice_number }}</td>
                                <td class="px-2.5 py-2.5 text-xs text-v2-text-muted">{{ $r->created_at->format('Y-m-d') }}</td>
                                <td class="px-2.5 py-2.5 font-semibold">{{ $r->customer_name }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->car_label ?: '-' }}</td>
                                <td class="num-font px-2.5 py-2.5 font-extrabold text-v2-primary">{{ number_format($r->payableAmount()) }} {{ \App\Models\Invoice::CURRENCIES[$r->currency] ?? 'تومان' }}</td>
                                <td class="px-2.5 py-2.5">
                                    <x-badge :color="$r->status === 'تایید شده' ? 'v2-success' : ($r->status === 'پیش‌نویس' ? 'v2-neutral' : 'v2-primary')">{{ $r->status }}</x-badge>
                                </td>
                                <td class="px-2.5 py-2.5">{{ $r->creator?->username ?? '-' }}</td>
                                <td class="px-2.5 py-2.5"><x-button :href="route('admin.invoices.show', $r)" size="sm" variant="v2-secondary">مشاهده / چاپ</x-button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $rows->links() }}</div>
        @endif
    </x-card>
</x-layouts.admin>
