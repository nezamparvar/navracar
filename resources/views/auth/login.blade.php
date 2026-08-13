<!DOCTYPE html>
<html lang="fa" dir="rtl" x-data x-init="$store.theme.init()" :class="{ 'dark': $store.theme.dark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به پنل مدیریت | ناوراکار</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-ink-950 p-5 font-sans"
      style="background-image: radial-gradient(120% 140% at 20% 0%, #1c2a5e 0%, #0a0f24 60%);">

    <div class="w-full max-w-sm animate-fade-up rounded-3xl border border-white/10 bg-white p-8 shadow-soft-lg dark:bg-ink-900">
        <div class="mb-7 flex flex-col items-center gap-3 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-ink-950 shadow-glow-amber">
                <x-icon name="car" class="w-7 h-7" />
            </div>
            <div>
                <h1 class="text-lg font-black text-brand-900 dark:text-white">پنل مدیریت ناوراکار</h1>
                <p class="mt-1 text-xs font-semibold text-ink-500 dark:text-ink-400">این بخش فقط برای مدیر و کارشناسان سامانه است</p>
            </div>
        </div>

        @if ($expired)
            <div class="mb-4 rounded-xl bg-amber-50 px-4 py-2.5 text-center text-xs font-bold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                نشست شما منقضی شد، دوباره وارد شوید.
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-xl bg-rose-50 px-4 py-2.5 text-center text-xs font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="username" class="mb-1.5 block text-xs font-bold text-ink-700 dark:text-ink-200">نام کاربری</label>
                <input id="username" type="text" name="username" required autofocus autocomplete="username"
                       class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-sm focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:bg-white/10">
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-xs font-bold text-ink-700 dark:text-ink-200">رمز عبور</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-sm focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-100 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:bg-white/10">
            </div>
            <x-button type="submit" class="w-full justify-center !py-3.5">ورود به پنل</x-button>
        </form>

        <p class="mt-6 text-center text-[11px] font-semibold text-ink-600 dark:text-ink-300">
            حساب کاربری از طریق دستور <code class="rounded bg-ink-100 px-1.5 py-0.5 dark:bg-white/10">admin:create-user</code> ساخته می‌شود
        </p>
    </div>
</body>
</html>
