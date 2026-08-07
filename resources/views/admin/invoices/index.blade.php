<x-layouts.admin :page-title="$pageTitle">

    <p class="mb-5 text-sm text-ink-500 dark:text-ink-400">مجموع {{ $rows->total() }} پیش‌فاکتور صادرشده.</p>

    <x-card>
        <div class="mb-5"><x-button :href="route('admin.invoices.create')" variant="amber"><x-icon name="plus" class="w-4 h-4" /> صدور پیش‌فاکتور جدید</x-button></div>

        @if ($rows->isEmpty())
            <x-empty-state icon="invoice" title="هنوز پیش‌فاکتوری صادر نشده." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-ink-100 text-xs font-extrabold text-ink-400 dark:border-white/10 dark:text-ink-500">
                            <th class="px-2.5 py-2 text-start">شماره</th><th class="px-2.5 py-2 text-start">تاریخ</th>
                            <th class="px-2.5 py-2 text-start">مشتری</th><th class="px-2.5 py-2 text-start">خودرو</th>
                            <th class="px-2.5 py-2 text-start">مبلغ قابل‌پرداخت</th><th class="px-2.5 py-2 text-start">وضعیت</th>
                            <th class="px-2.5 py-2 text-start">صادرکننده</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr class="border-b border-ink-100 hover:bg-ink-50 dark:border-white/5 dark:hover:bg-white/5">
                                <td class="num-font px-2.5 py-2.5">{{ $r->invoice_number }}</td>
                                <td class="px-2.5 py-2.5 text-xs text-ink-500">{{ $r->created_at->format('Y-m-d') }}</td>
                                <td class="px-2.5 py-2.5 font-semibold">{{ $r->customer_name }}</td>
                                <td class="px-2.5 py-2.5">{{ $r->car_label ?: '-' }}</td>
                                <td class="num-font px-2.5 py-2.5 font-extrabold text-brand-700 dark:text-brand-300">{{ number_format($r->payableAmount()) }} {{ $r->currency === 'aed' ? 'AED' : 'تومان' }}</td>
                                <td class="px-2.5 py-2.5">
                                    <x-badge :color="$r->status === 'تایید شده' ? 'green' : ($r->status === 'پیش‌نویس' ? 'slate' : 'blue')">{{ $r->status }}</x-badge>
                                </td>
                                <td class="px-2.5 py-2.5">{{ $r->creator?->username ?? '-' }}</td>
                                <td class="px-2.5 py-2.5"><x-button :href="route('admin.invoices.show', $r)" size="sm" variant="secondary">مشاهده / چاپ</x-button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $rows->links() }}</div>
        @endif
    </x-card>
</x-layouts.admin>
