<x-layouts.admin :page-title="$pageTitle" page-subtitle="درخواست‌های حذف‌شده — می‌توانید آن‌ها را بازیابی یا به‌طور دائمی حذف کنید.">

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-bold text-ink-500 dark:text-ink-400">جستجو (نام/تلفن/نام خودرو)</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
        </div>
        <x-button type="submit" size="sm">جستجو</x-button>
        <x-button :href="route('admin.requests.index')" variant="secondary" size="sm">بازگشت</x-button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b-2 border-ink-200 bg-ink-50 dark:border-white/10 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-right font-bold text-ink-700 dark:text-ink-300">نام</th>
                    <th class="px-4 py-3 text-right font-bold text-ink-700 dark:text-ink-300">تلفن</th>
                    <th class="px-4 py-3 text-right font-bold text-ink-700 dark:text-ink-300">خودرو</th>
                    <th class="px-4 py-3 text-right font-bold text-ink-700 dark:text-ink-300">تاریخ حذف</th>
                    <th class="px-4 py-3 text-center font-bold text-ink-700 dark:text-ink-300">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-200 dark:divide-white/10">
                @forelse ($rows as $row)
                    <tr class="hover:bg-ink-50 dark:hover:bg-white/5">
                        <td class="px-4 py-3">{{ $row->name }}</td>
                        <td class="px-4 py-3 font-mono text-ink-500">{{ $row->phone }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ $row->car_label ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-ink-500">{{ $row->deleted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center gap-2">
                                <form method="POST" action="{{ route('admin.requests.restore', $row) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-brand-700">
                                        بازیابی
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.requests.force-delete', $row) }}" class="inline" onsubmit="return confirm('آیا از حذف دائمی مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-700">
                                        حذف دائمی
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-ink-500">
                            درخواست حذف‌شده‌ای وجود ندارد.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $rows->links() }}
</x-layouts.admin>
