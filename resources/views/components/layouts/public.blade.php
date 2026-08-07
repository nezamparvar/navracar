<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ناوراکار' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-amber-50/40 font-sans text-ink-900">

<header class="sticky top-0 z-40 bg-gradient-to-l from-brand-950 via-brand-900 to-brand-800 py-3.5 text-white shadow-soft-lg">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4">
        <a href="{{ route('public.calculator') }}" class="flex min-w-0 items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-ink-950 shadow-glow-amber">
                <x-icon name="car" class="w-6 h-6" />
            </div>
            <div class="min-w-0">
                <div class="text-lg font-black">ناوراکار</div>
                <div class="truncate text-xs font-semibold text-brand-200">محاسبه‌گر رسمی هزینه واردات خودرو</div>
            </div>
        </a>
        <div class="flex shrink-0 items-center gap-2">
            {{ $headerActions ?? '' }}
        </div>
    </div>
</header>

{{ $slot }}

<footer class="py-10 text-center">
    <p class="mx-auto max-w-xl px-4 text-xs text-ink-500">
        <span class="font-extrabold text-brand-900">ناوراکار</span> — این ابزار صرفاً یک برآورد اولیه ارائه می‌دهد.
        برای تعیین قیمت قطعی با کارشناسان تماس بگیرید.
    </p>
</footer>

<x-toast-container />
</body>
</html>
