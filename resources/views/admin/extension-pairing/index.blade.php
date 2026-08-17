<x-layouts.admin :page-title="$pageTitle" page-subtitle="برای اتصال امن افزونه Chrome یک کد شش‌رقمی یک‌بارمصرف بسازید.">
    @if(session('pairing_code'))
        <x-card title="کد اتصال جدید" icon="key" class="mb-5">
            <p class="mb-2 text-sm text-ink-600">این کد فقط همین یک‌بار نمایش داده می‌شود و پس از اتصال قابل استفاده مجدد نیست.</p>
            <div class="select-all rounded-xl bg-ink-950 px-5 py-4 text-center font-mono text-3xl tracking-[.35em] text-white" dir="ltr">{{ session('pairing_code') }}</div>
        </x-card>
    @endif

    <x-card title="ساخت اتصال" icon="plus" class="mb-5">
        <form method="POST" action="{{ route('admin.extension-pairing.store') }}" class="grid gap-4 sm:grid-cols-3">
            @csrf
            <label class="text-sm">محیط
                <select name="environment" class="mt-1 w-full rounded-xl border-ink-200">
                    <option value="staging">Staging</option>
                    <option value="production">Production</option>
                </select>
            </label>
            <label class="text-sm">اعتبار (ساعت)
                <input name="expires_in_hours" type="number" min="1" max="168" value="24" class="mt-1 w-full rounded-xl border-ink-200">
            </label>
            <div class="flex items-end"><x-button type="submit" variant="amber">ساخت کد اتصال</x-button></div>
        </form>
    </x-card>

    <x-card title="اتصال‌های ثبت‌شده" icon="users">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b text-right text-ink-500"><th class="p-2">دستگاه</th><th class="p-2">محیط</th><th class="p-2">وضعیت</th><th class="p-2">آخرین استفاده</th><th class="p-2"></th></tr></thead>
                <tbody>
                @forelse($pairings as $pairing)
                    <tr class="border-b border-ink-100">
                        <td class="p-2">{{ $pairing->device_name }} @if($pairing->token_last_four)<span class="font-mono text-xs">••••{{ $pairing->token_last_four }}</span>@endif</td>
                        <td class="p-2">{{ $pairing->environment }}</td>
                        <td class="p-2"><x-badge :color="$pairing->isActive() ? 'green' : 'slate'">{{ $pairing->status }}</x-badge></td>
                        <td class="p-2">{{ optional($pairing->last_used_at)->format('Y-m-d H:i') ?: '—' }}</td>
                        <td class="p-2">
                            @if($pairing->status !== 'revoked')
                                <form method="POST" action="{{ route('admin.extension-pairing.revoke', $pairing) }}">@csrf<x-button type="submit" size="sm" variant="danger">لغو</x-button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-ink-500">اتصالی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $pairings->links() }}</div>
    </x-card>
</x-layouts.admin>
