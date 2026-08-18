<x-layouts.admin :page-title="$pageTitle" page-subtitle="درخواست‌های حذف‌شده — می‌توانید آن‌ها را بازیابی یا به‌طور دائمی حذف کنید.">

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-bold text-v2-text-muted">جستجو (نام/تلفن/نام خودرو)</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
        </div>
        <x-button type="submit" variant="v2-primary" size="sm">جستجو</x-button>
        <x-button :href="route('admin.requests.index')" variant="v2-secondary" size="sm">بازگشت</x-button>
    </form>

    <x-card variant="v2">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b-2 border-v2-border bg-v2-elevated">
                    <tr>
                        <th class="px-4 py-3 text-right font-bold text-v2-text-muted">نام</th>
                        <th class="px-4 py-3 text-right font-bold text-v2-text-muted">تلفن</th>
                        <th class="px-4 py-3 text-right font-bold text-v2-text-muted">خودرو</th>
                        <th class="px-4 py-3 text-right font-bold text-v2-text-muted">تاریخ حذف</th>
                        <th class="px-4 py-3 text-center font-bold text-v2-text-muted">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-v2-border">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-v2-elevated">
                            <td class="px-4 py-3">{{ $row->name }}</td>
                            <td class="px-4 py-3 font-mono text-v2-text-muted">{{ $row->phone }}</td>
                            <td class="px-4 py-3 text-v2-text-muted">{{ $row->car_label ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-v2-text-muted">{{ $row->deleted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-2">
                                    <form method="POST" action="{{ route('admin.requests.restore', $row) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-v2-primary px-3 py-1.5 text-xs font-bold text-white hover:brightness-110">
                                            بازیابی
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.requests.force-delete', $row) }}" class="inline" onsubmit="return confirm('آیا از حذف دائمی مطمئن هستید؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-v2-error px-3 py-1.5 text-xs font-bold text-white hover:brightness-110">
                                            حذف دائمی
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-v2-text-muted">
                                درخواست حذف‌شده‌ای وجود ندارد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $rows->links() }}</div>
    </x-card>
</x-layouts.admin>
