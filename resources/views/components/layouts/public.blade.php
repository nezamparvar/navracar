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
    @if (app()->environment('staging'))
        <meta name="robots" content="noindex, nofollow, noarchive">
    @endif
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
{{--
    Body intentionally NOT on v2-bg yet: most public page bodies (Phase 4)
    still render bare text-ink-900/text-ink-500 headings with no card
    wrapper, which goes near-invisible directly on the v2 dark background.
    Header/footer/bottom-nav below are self-contained v2-surface regions
    (explicit background + text colors) so they're safe regardless of the
    body's own background. The body itself migrates to bg-v2-bg in Phase 4
    together with the page content it wraps, not before.
--}}

<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:right-2 focus:z-[110] focus:rounded-lg focus:bg-v2-primary focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-white">
    رفتن به محتوای اصلی
</a>

<x-staging-banner />

<header x-data="publicHeader" class="sticky top-0 z-40 border-b border-v2-border bg-v2-surface/95 py-3.5 backdrop-blur-md">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4">
        <a href="{{ route('public.home') }}" class="flex min-w-0 items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-v2-primary/15 text-v2-primary">
                <x-icon name="car" class="w-6 h-6" />
            </div>
            <div class="min-w-0">
                <div class="text-lg font-black text-v2-text">ناوراکار</div>
                <div class="truncate text-xs font-semibold text-v2-text-muted">محاسبه‌گر رسمی هزینه واردات خودرو</div>
            </div>
        </a>

        {{--
            Nav order/labels match docs/design-v2/assets/01-public-desktop-system.png exactly:
            خودروها, محاسبه هزینه, درخواست‌ها, تماس با ما (text nav) + phone/account icon buttons.
            "درخواست‌ها" links to the real submission page (public.lead-form); the real
            request-tracking-by-number page (public.track.find) is linked from the footer
            instead of the header, so the reference's exact nav item set stays unchanged.
            "حساب" is the reference's account icon button; kept visible (not removed) but
            disabled with a reason, since no public-account backend exists — GAP_REPORT.md §1.
        --}}
        <nav aria-label="ناوبری اصلی" class="hidden items-center gap-1 sm:flex">
            <a href="{{ route('public.car-prices.index') }}"
               class="rounded-full px-4 py-2 text-xs font-bold transition-colors {{ request()->routeIs('public.car-prices.*') ? 'bg-v2-primary text-white' : 'text-v2-text-muted hover:bg-v2-elevated hover:text-v2-text' }}">
                خودروها
            </a>
            <a href="{{ route('public.calculator') }}"
               class="rounded-full px-4 py-2 text-xs font-bold transition-colors {{ request()->routeIs('public.calculator') ? 'bg-v2-primary text-white' : 'text-v2-text-muted hover:bg-v2-elevated hover:text-v2-text' }}">
                محاسبه هزینه
            </a>
            <a href="{{ route('public.lead-form') }}"
               class="rounded-full px-4 py-2 text-xs font-bold transition-colors {{ request()->routeIs('public.lead-form') ? 'bg-v2-primary text-white' : 'text-v2-text-muted hover:bg-v2-elevated hover:text-v2-text' }}">
                درخواست‌ها
            </a>
            <a href="{{ route('public.home') }}#contact"
               class="rounded-full px-4 py-2 text-xs font-bold text-v2-text-muted hover:bg-v2-elevated hover:text-v2-text">
                تماس با ما
            </a>
            @foreach ($menuItems as $item)
                <a href="{{ $item->url }}" @if($item->opens_new_tab) target="_blank" rel="noopener" @endif
                   class="rounded-full px-4 py-2 text-xs font-bold text-v2-text-muted hover:bg-v2-elevated hover:text-v2-text">
                    {{ $item->label }}
                </a>
            @endforeach
        </nav>

        <div class="flex shrink-0 items-center gap-2">
            <span tabindex="0" aria-disabled="true" title="حساب کاربری هنوز راه‌اندازی نشده — ناوراکار ثبت‌نام عمومی ندارد"
                  class="hidden h-10 w-10 cursor-not-allowed items-center justify-center rounded-full bg-v2-elevated text-v2-text-muted/50 sm:flex">
                <x-icon name="user" class="w-[18px] h-[18px]" />
                <span class="sr-only">حساب کاربری (هنوز راه‌اندازی نشده)</span>
            </span>
            <a href="tel:{{ str_replace(' ', '', $footerContactIran) }}" aria-label="تماس تلفنی با ناوراکار"
               class="hidden h-10 w-10 items-center justify-center rounded-full bg-v2-elevated text-v2-text-muted hover:text-v2-text sm:flex">
                <x-icon name="phone" class="w-[18px] h-[18px]" />
            </a>
            <a href="{{ route('public.calculator') }}" class="hidden rounded-full bg-v2-primary-action px-4 py-2 text-xs font-bold text-white shadow-glow-v2 hover:brightness-110 sm:inline-block">
                محاسبه قیمت خودرو
            </a>
            <button type="button" @click="mobileMenuOpen = !mobileMenuOpen"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-v2-elevated text-v2-text sm:hidden"
                    :aria-expanded="mobileMenuOpen"
                    aria-label="باز کردن منو">
                <x-icon name="menu" class="w-5 h-5" x-show="!mobileMenuOpen" />
                <x-icon name="x" class="w-5 h-5" x-show="mobileMenuOpen" x-cloak />
            </button>
        </div>
    </div>

    <div x-show="mobileMenuOpen" x-cloak x-transition @click.outside="mobileMenuOpen = false"
         class="mx-4 mt-3 space-y-1.5 rounded-2xl border border-v2-border bg-v2-surface p-3 sm:hidden">
        <a href="{{ route('public.car-prices.index') }}" class="block rounded-xl px-4 py-2.5 text-center text-sm font-bold text-v2-text hover:bg-v2-elevated">خودروها</a>
        <a href="{{ route('public.calculator') }}" class="block rounded-xl bg-v2-primary-action px-4 py-2.5 text-center text-sm font-bold text-white">محاسبه قیمت خودرو</a>
        <a href="{{ route('public.lead-form') }}" class="block rounded-xl px-4 py-2.5 text-center text-sm font-bold text-v2-text hover:bg-v2-elevated">درخواست‌ها</a>
        <a href="{{ route('public.home') }}#contact" class="block rounded-xl px-4 py-2.5 text-center text-sm font-bold text-v2-text hover:bg-v2-elevated">تماس با ما</a>
        <span aria-disabled="true" title="حساب کاربری هنوز راه‌اندازی نشده" class="block cursor-not-allowed rounded-xl px-4 py-2.5 text-center text-sm font-bold text-v2-text-muted/50">حساب (به‌زودی)</span>
        @foreach ($menuItems as $item)
            <a href="{{ $item->url }}" @if($item->opens_new_tab) target="_blank" rel="noopener" @endif
               class="block rounded-xl px-4 py-2.5 text-center text-sm font-bold text-v2-text hover:bg-v2-elevated">
                {{ $item->label }}
            </a>
        @endforeach
    </div>
</header>

<main id="main-content">
    {{ $slot }}
</main>

{{--
    Bottom clearance for the fixed mobile nav belongs at the true end of the page (after the
    footer), not between main and footer — the nav is position:fixed and only ever risks covering
    whatever content is currently at the bottom of the scrollable page. Padding on <main> created a
    large empty gap here instead of protecting the real page end.
--}}
<footer class="mt-10 border-t border-v2-border bg-v2-surface pt-10 pb-24 sm:pb-10">
    <div class="mx-auto max-w-6xl px-4">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-3">
            <div>
                <div class="text-sm font-black text-v2-text">ناوراکار</div>
                <p class="mt-2 text-xs leading-6 text-v2-text-muted">
                    محاسبه‌گر رسمی هزینه واردات خودرو — این ابزار صرفاً یک برآورد اولیه ارائه می‌دهد.
                    برای تعیین قیمت قطعی با کارشناسان تماس بگیرید.
                </p>
            </div>
            <div>
                <div class="text-xs font-extrabold text-v2-text">دسترسی سریع</div>
                <ul class="mt-2 space-y-1.5 text-xs text-v2-text-muted">
                    <li><a href="{{ route('public.home') }}" class="hover:text-v2-primary">صفحه اصلی</a></li>
                    <li><a href="{{ route('public.calculator') }}" class="hover:text-v2-primary">محاسبه‌گر هزینه واردات</a></li>
                    <li><a href="{{ route('public.car-prices.index') }}" class="hover:text-v2-primary">قیمت خودروها</a></li>
                    <li><a href="{{ route('public.track.find') }}" class="hover:text-v2-primary">پیگیری درخواست</a></li>
                    @foreach ($menuItems as $item)
                        <li><a href="{{ $item->url }}" @if($item->opens_new_tab) target="_blank" rel="noopener" @endif class="hover:text-v2-primary">{{ $item->label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div id="contact">
                <div class="text-xs font-extrabold text-v2-text">تماس با ما</div>
                <ul class="mt-2 space-y-1.5 text-xs text-v2-text-muted num-font" dir="ltr">
                    <li><a href="tel:{{ str_replace(' ', '', $footerContactIran) }}" class="hover:text-v2-primary">🇮🇷 {{ $footerContactIran }}</a></li>
                    <li><a href="tel:{{ str_replace(' ', '', $footerContactUae) }}" class="hover:text-v2-primary">🇦🇪 {{ $footerContactUae }}</a></li>
                    <li><a href="tel:{{ str_replace(' ', '', $footerContactTehran) }}" class="hover:text-v2-primary">☎️ {{ $footerContactTehran }}</a></li>
                </ul>
            </div>
        </div>
        <p class="mt-8 border-t border-v2-border pt-6 text-center text-[11px] text-v2-text-muted">
            © {{ now()->format('Y') }} ناوراکار — تمامی حقوق محفوظ است.
        </p>
    </div>
</footer>

{{--
    Mobile bottom nav — 5 items matching docs/design-v2/assets/05-public-mobile.png exactly
    (خانه/خودروها/محاسبه/درخواست‌ها/حساب). "درخواست‌ها" links to the real submission page
    (public.lead-form); "حساب" is kept visible but disabled — no public-account backend
    exists yet. See GAP_REPORT.md §1 for the plan, not silently dropped.
--}}
<nav aria-label="ناوبری پایین صفحه" class="fixed inset-x-0 bottom-0 z-40 flex border-t border-v2-border bg-v2-surface/95 backdrop-blur-md sm:hidden" style="padding-bottom: env(safe-area-inset-bottom)">
    @php
        $bottomNavItems = [
            ['route' => 'public.home', 'label' => 'خانه', 'icon' => 'dashboard', 'match' => 'public.home'],
            ['route' => 'public.car-prices.index', 'label' => 'خودروها', 'icon' => 'car', 'match' => 'public.car-prices.*'],
            ['route' => 'public.calculator', 'label' => 'محاسبه', 'icon' => 'calculator', 'match' => 'public.calculator'],
            ['route' => 'public.lead-form', 'label' => 'درخواست‌ها', 'icon' => 'inbox', 'match' => 'public.lead-form*'],
        ];
    @endphp
    @foreach ($bottomNavItems as $item)
        @php $active = request()->routeIs($item['match']); @endphp
        <a href="{{ route($item['route']) }}"
           class="flex min-h-[48px] flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-bold {{ $active ? 'text-v2-primary' : 'text-v2-text-muted' }}"
           @if($active) aria-current="page" @endif>
            <x-icon :name="$item['icon']" class="w-5 h-5" />
            {{ $item['label'] }}
        </a>
    @endforeach
    <span aria-disabled="true" title="حساب کاربری هنوز راه‌اندازی نشده"
          class="flex min-h-[48px] flex-1 cursor-not-allowed flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-bold text-v2-text-muted/50">
        <x-icon name="user" class="w-5 h-5" />
        حساب
    </span>
</nav>

<x-toast-container />
</body>
</html>
