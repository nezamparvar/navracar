<x-layouts.admin :page-title="$pageTitle" page-subtitle="کاربر «مدیر» به همه فرم‌ها دسترسی دارد. «مدیر محتوا» فقط آگهی خودرو/وبلاگ/اسلایدر/منو را می‌بیند. «کارشناس فروش» فقط فرم‌های الحاق‌شده به خودش را می‌بیند.">

    <x-card variant="v2" title="افزودن کاربر جدید" icon="plus" class="mb-5">
        @if (session('success'))
            <div role="status" data-testid="user-form-success" class="mb-4 rounded-xl border border-v2-success/30 bg-v2-success/15 px-4 py-3 text-sm font-bold text-v2-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div id="user-form-errors" role="alert" data-testid="user-form-errors" class="mb-4 rounded-xl border border-v2-error/30 bg-v2-error/15 px-4 py-3 text-sm text-v2-error">
                <p class="font-extrabold">کاربر ساخته نشد. موارد زیر را اصلاح کنید:</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-xs font-semibold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" class="flex flex-wrap items-start gap-3">
            @csrf
            <div class="flex flex-col gap-1.5">
                <label for="username" class="text-xs font-bold text-v2-text-muted">نام کاربری</label>
                <input id="username" name="username" value="{{ old('username') }}" required autocomplete="off" @error('username') aria-invalid="true" aria-describedby="username-error" @enderror class="rounded-lg border bg-v2-elevated px-3 py-2 text-sm text-v2-text {{ $errors->has('username') ? 'border-v2-error' : 'border-v2-border' }}">
                @error('username')<p id="username-error" class="max-w-48 text-xs font-semibold text-v2-error">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-xs font-bold text-v2-text-muted">رمز عبور</label>
                <input id="password" type="password" name="password" required minlength="6" autocomplete="new-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror class="rounded-lg border bg-v2-elevated px-3 py-2 text-sm text-v2-text {{ $errors->has('password') ? 'border-v2-error' : 'border-v2-border' }}">
                <p class="max-w-48 text-[11px] font-semibold text-v2-text-muted">حداقل ۶ نویسه</p>
                @error('password')<p id="password-error" class="max-w-48 text-xs font-semibold text-v2-error">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="full_name" class="text-xs font-bold text-v2-text-muted">نام کامل</label>
                <input id="full_name" name="full_name" value="{{ old('full_name') }}" @error('full_name') aria-invalid="true" aria-describedby="full-name-error" @enderror class="rounded-lg border bg-v2-elevated px-3 py-2 text-sm text-v2-text {{ $errors->has('full_name') ? 'border-v2-error' : 'border-v2-border' }}">
                @error('full_name')<p id="full-name-error" class="max-w-48 text-xs font-semibold text-v2-error">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="role" class="text-xs font-bold text-v2-text-muted">نقش</label>
                <select id="role" name="role" required @error('role') aria-invalid="true" aria-describedby="role-error" @enderror class="rounded-lg border bg-v2-elevated px-3 py-2 text-sm text-v2-text {{ $errors->has('role') ? 'border-v2-error' : 'border-v2-border' }}">
                    <option value="sales" @selected(old('role', 'sales') === 'sales')>کارشناس فروش</option>
                    <option value="content_manager" @selected(old('role') === 'content_manager')>مدیر محتوا</option>
                    <option value="admin" @selected(old('role') === 'admin')>مدیر</option>
                </select>
                @error('role')<p id="role-error" class="max-w-48 text-xs font-semibold text-v2-error">{{ $message }}</p>@enderror
            </div>
            <div class="pt-6"><x-button type="submit" variant="v2-primary">افزودن کاربر</x-button></div>
        </form>
    </x-card>

    <x-card variant="v2" title="فهرست کاربران" icon="users">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-v2-border text-xs font-extrabold text-v2-text-muted">
                        <th class="px-2.5 py-2 text-start">نام کاربری</th><th class="px-2.5 py-2 text-start">نام کامل</th>
                        <th class="px-2.5 py-2 text-start">نقش</th><th class="px-2.5 py-2 text-start">فرم‌های الحاق‌شده</th>
                        <th class="px-2.5 py-2 text-start">تغییر نقش</th><th class="px-2.5 py-2 text-start">بازنشانی رمز</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr class="border-b border-v2-border text-v2-text">
                            <td class="px-2.5 py-2.5 font-semibold">{{ $u->username }}{{ $u->id === auth()->id() ? ' (شما)' : '' }}</td>
                            <td class="px-2.5 py-2.5">{{ $u->full_name ?: '-' }}</td>
                            <td class="px-2.5 py-2.5"><x-badge :color="$u->role === 'admin' ? 'v2-success' : ($u->role === 'content_manager' ? 'v2-primary' : 'v2-neutral')">{{ \App\Models\AdminUser::ROLE_LABELS[$u->role] ?? $u->role }}</x-badge></td>
                            <td class="num-font px-2.5 py-2.5">{{ $u->assigned_count }}</td>
                            <td class="px-2.5 py-2.5">
                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.role', $u) }}">
                                        @csrf
                                        <select name="role" onchange="this.form.submit()" class="rounded-lg border border-v2-border bg-v2-elevated px-2 py-1.5 text-xs text-v2-text">
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
                                    <input type="password" name="new_password" placeholder="رمز جدید" class="w-32 rounded-lg border border-v2-border bg-v2-elevated px-2 py-1.5 text-xs text-v2-text">
                                    <x-button type="submit" size="sm" variant="v2-secondary">ثبت</x-button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts.admin>
