@php
    $menuItems = \App\Models\MenuItem::active()->get();
    $footerContactUae = \App\Models\Setting::get(\App\Models\Setting::WHATSAPP_UAE);
    $footerContactIran = \App\Models\Setting::get(\App\Models\Setting::WHATSAPP_IRAN);
    $footerContactTehran = \App\Models\Setting::get(\App\Models\Setting::TEHRAN_OFFICE_PHONE);
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ناوراکار' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => 'ناوراکار',
                'url' => url('/'),
                'contactPoint' => [
                    ['@type' => 'ContactPoint', 'telephone' => $footerContactIran, 'contactType' => 'sales', 'areaServed' => 'IR'],
                    ['@type' => 'ContactPoint', 'telephone' => $footerContactUae, 'contactType' => 'sales', 'areaServed' => 'AE'],
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'name' => 'ناوراکار',
                'url' => url('/'),
                'publisher' => ['@id' => url('/').'#organization'],
                'inLanguage' => 'fa-IR',
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-amber-50/40 font-sans text-ink-900">

<header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-40 bg-gradient-to-l from-brand-950 via-brand-900 to-brand-800 py-3.5 text-white shadow-soft-lg">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4">
        <a href="{{ route('public.home') }}" class="flex min-w-0 items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-ink-950 shadow-glow-amber">
                <x-icon name="car" class="w-6 h-6" />
            </div>
            <div class="min-w-0">
                <div class="text-lg font-black">ناوراکار</div>
                <div class="truncate text-xs font-semibold text-brand-200">محاسبه‌گر رسمی هزینه واردات خودرو</div>
            </div>
        </a>
        <div class="flex shrink-0 items-center gap-2">
            <a href="{{ route('public.calculator') }}" class="hidden rounded-full bg-amber-500 px-4 py-2 text-xs font-bold text-ink-950 hover:brightness-105 sm:inline-block">
                محاسبه قیمت خودرو
            </a>
            <a href="{{ route('public.car-prices.index') }}" class="hidden rounded-full bg-white/10 px-4 py-2 text-xs font-bold text-white hover:bg-white/20 sm:inline-block">
                قیمت خودروها
            </a>
            @foreach ($menuItems as $item)
                <a href="{{ $item->url }}" @if($item->opens_new_tab) target="_blank" rel="noopener" @endif
                   class="hidden rounded-full bg-white/10 px-4 py-2 text-xs font-bold text-white hover:bg-white/20 sm:inline-block">
                    {{ $item->label }}
                </a>
            @endforeach
            {{ $headerActions ?? '' }}
            <button type="button" @click="mobileMenuOpen = !mobileMenuOpen"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:hidden"
                    :aria-expanded="mobileMenuOpen"
                    aria-label="باز کردن منو">
                <x-icon name="menu" class="w-5 h-5" x-show="!mobileMenuOpen" />
                <x-icon name="x" class="w-5 h-5" x-show="mobileMenuOpen" x-cloak />
            </button>
        </div>
    </div>

    <div x-show="mobileMenuOpen" x-cloak x-transition @click.outside="mobileMenuOpen = false"
         class="mx-4 mt-3 space-y-1.5 rounded-2xl border border-white/10 bg-brand-950/95 p-3 sm:hidden">
        <a href="{{ route('public.calculator') }}" class="block rounded-xl bg-amber-500 px-4 py-2.5 text-center text-sm font-bold text-ink-950">
            محاسبه قیمت خودرو
        </a>
        <a href="{{ route('public.car-prices.index') }}" class="block rounded-xl bg-white/10 px-4 py-2.5 text-center text-sm font-bold text-white hover:bg-white/20">
            قیمت خودروها
        </a>
        @foreach ($menuItems as $item)
            <a href="{{ $item->url }}" @if($item->opens_new_tab) target="_blank" rel="noopener" @endif
               class="block rounded-xl bg-white/10 px-4 py-2.5 text-center text-sm font-bold text-white hover:bg-white/20">
                {{ $item->label }}
            </a>
        @endforeach
    </div>
</header>

{{ $slot }}

<footer class="mt-10 border-t border-ink-200/70 bg-white py-10">
    <div class="mx-auto max-w-6xl px-4">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-3">
            <div>
                <div class="text-sm font-black text-brand-900">ناوراکار</div>
                <p class="mt-2 text-xs leading-6 text-ink-500">
                    محاسبه‌گر رسمی هزینه واردات خودرو — این ابزار صرفاً یک برآورد اولیه ارائه می‌دهد.
                    برای تعیین قیمت قطعی با کارشناسان تماس بگیرید.
                </p>
            </div>
            <div>
                <div class="text-xs font-extrabold text-ink-700">دسترسی سریع</div>
                <ul class="mt-2 space-y-1.5 text-xs text-ink-500">
                    <li><a href="{{ route('public.home') }}" class="hover:text-brand-700">صفحه اصلی</a></li>
                    <li><a href="{{ route('public.calculator') }}" class="hover:text-brand-700">محاسبه‌گر هزینه واردات</a></li>
                    <li><a href="{{ route('public.car-prices.index') }}" class="hover:text-brand-700">قیمت خودروها</a></li>
                    @foreach ($menuItems as $item)
                        <li><a href="{{ $item->url }}" @if($item->opens_new_tab) target="_blank" rel="noopener" @endif class="hover:text-brand-700">{{ $item->label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="text-xs font-extrabold text-ink-700">تماس با ما</div>
                <ul class="mt-2 space-y-1.5 text-xs text-ink-500 num-font" dir="ltr">
                    <li>🇮🇷 {{ $footerContactIran }}</li>
                    <li>🇦🇪 {{ $footerContactUae }}</li>
                    <li>☎️ {{ $footerContactTehran }}</li>
                </ul>
            </div>
        </div>
        <p class="mt-8 border-t border-ink-100 pt-6 text-center text-[11px] text-ink-600">
            © {{ now()->format('Y') }} ناوراکار — تمامی حقوق محفوظ است.
        </p>
    </div>
</footer>

<x-toast-container />
</body>
</html>
