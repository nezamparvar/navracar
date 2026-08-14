<x-layouts.admin :page-title="$pageTitle" page-subtitle="کاربر «مدیر» به همه فرم‌ها دسترسی دارد. «مدیر محتوا» فقط آگهی خودرو/وبلاگ/اسلایدر/منو را می‌بیند. «کارشناس فروش» فقط فرم‌های الحاق‌شده به خودش را می‌بیند.">

    <x-card title="افزودن کاربر جدید" icon="plus" class="mb-5">
        <form method="POST" action="{{ route('admin.users.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex flex-col gap-1.5"><label class="text-xs font-bold text-ink-500 dark:text-ink-400">نام کاربری</label><input name="username" required class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5"></div>
            <div class="flex flex-col gap-1.5"><label class="text-xs font-bold text-ink-500 dark:text-ink-400">رمز عبور</label><input type="password" name="password" required class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5"></div>
            <div class="flex flex-col gap-1.5"><label class="text-xs font-bold text-ink-500 dark:text-ink-400">نام کامل</label><input name="full_name" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5"></div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-ink-500 dark:text-ink-400">نقش</label>
                <select name="role" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                    <option value="sales">کارشناس فروش</option>
                    <option value="content_manager">مدیر محتوا</option>
                    <option value="admin">مدیر</option>
                </select>
            </div>
            <x-button type="submit" variant="amber">افزودن کاربر</x-button>
        </form>
    </x-card>

    <x-card title="فهرست کاربران" icon="users">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-ink-100 text-xs font-extrabold text-ink-400 dark:border-white/10 dark:text-ink-500">
                        <th class="px-2.5 py-2 text-start">نام کاربری</th><th class="px-2.5 py-2 text-start">نام کامل</th>
                        <th class="px-2.5 py-2 text-start">نقش</th><th class="px-2.5 py-2 text-start">فرم‌های الحاق‌شده</th>
                        <th class="px-2.5 py-2 text-start">تغییر نقش</th><th class="px-2.5 py-2 text-start">بازنشانی رمز</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr class="border-b border-ink-100 dark:border-white/5">
                            <td class="px-2.5 py-2.5 font-semibold">{{ $u->username }}{{ $u->id === auth()->id() ? ' (شما)' : '' }}</td>
                            <td class="px-2.5 py-2.5">{{ $u->full_name ?: '-' }}</td>
                            @php
                                $roleLabels = ['admin' => 'مدیر', 'content_manager' => 'مدیر محتوا', 'sales' => 'کارشناس فروش'];
                            @endphp
                            <td class="px-2.5 py-2.5"><x-badge :color="$u->role === 'admin' ? 'green' : ($u->role === 'content_manager' ? 'blue' : 'slate')">{{ $roleLabels[$u->role] ?? $u->role }}</x-badge></td>
                            <td class="num-font px-2.5 py-2.5">{{ $u->assigned_count }}</td>
                            <td class="px-2.5 py-2.5">
                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.role', $u) }}">
                                        @csrf
                                        <select name="role" onchange="this.form.submit()" class="rounded-lg border border-ink-200 bg-white px-2 py-1.5 text-xs dark:border-white/10 dark:bg-ink-900">
                                            <option value="sales" @selected($u->role === 'sales')>کارشناس فروش</option>
                                            <option value="content_manager" @selected($u->role === 'content_manager')>مدیر محتوا</option>
                                            <option value="admin" @selected($u->role === 'admin')>مدیر</option>
                                        </select>
                                    </form>
                                @else — @endif
                            </td>
                            <td class="px-2.5 py-2.5">
                                <form method="POST" action="{{ route('admin.users.reset-password', $u) }}" class="flex items-center gap-1.5" onsubmit="return this.new_password.value.length >= 6;">
                                    @csrf
                                    <input type="password" name="new_password" placeholder="رمز جدید" class="w-32 rounded-lg border border-ink-200 bg-white px-2 py-1.5 text-xs dark:border-white/10 dark:bg-ink-900">
                                    <x-button type="submit" size="sm" variant="secondary">ثبت</x-button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts.admin>
