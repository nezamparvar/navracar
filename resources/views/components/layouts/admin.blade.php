<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (app()->environment('staging'))
        <meta name="robots" content="noindex, nofollow, noarchive">
    @endif
    <title>{{ $title ?? 'پنل مدیریت' }} | ناوراکار</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-v2-bg font-sans text-v2-text">

<x-staging-banner />

@php
    $navItems = [
        ['route' => 'admin.dashboard', 'label' => 'داشبورد', 'icon' => 'dashboard'],
        ['route' => 'admin.calculations.index', 'label' => 'محاسبات', 'icon' => 'calculator'],
        ['route' => 'admin.vin-checks.index', 'label' => 'شماره‌شاسی‌ها', 'icon' => 'vin'],
    ];
    $salesNavItems = [
        ['route' => 'admin.kanban', 'label' => 'پایپ‌لاین (کانبان)', 'icon' => 'kanban'],
        ['route' => 'admin.requests.index', 'label' => 'درخواست‌ها (لیست)', 'icon' => 'inbox'],
        ['route' => 'admin.invoices.index', 'label' => 'پیش‌فاکتورها', 'icon' => 'invoice'],
    ];
    $contentNavItems = [
        ['route' => 'admin.car-listings.index', 'label' => 'آگهی‌ها و ایمپورت', 'icon' => 'car'],
        ['route' => 'admin.posts.index', 'label' => 'وبلاگ', 'icon' => 'message'],
        ['route' => 'admin.home-slides.index', 'label' => 'اسلایدر صفحه اصلی', 'icon' => 'image'],
        ['route' => 'admin.menu-items.index', 'label' => 'منوی سایت', 'icon' => 'menu'],
    ];
    $adminNavItems = [
        ['route' => 'admin.import-queue.index', 'label' => 'صف ایمپورت', 'icon' => 'inbox'],
        ['route' => 'admin.extension-pairing.index', 'label' => 'اتصال افزونه', 'icon' => 'link'],
        ['route' => 'admin.settings.edit', 'label' => 'تنظیمات نرخ ارز', 'icon' => 'target'],
        ['route' => 'admin.templates.index', 'label' => 'قالب‌های پیام', 'icon' => 'message'],
        ['route' => 'admin.users.index', 'label' => 'کاربران', 'icon' => 'users'],
        ['route' => 'admin.activity-log.index', 'label' => 'لاگ سیستم', 'icon' => 'terminal'],
    ];
@endphp

<div x-data="adminShell" class="flex min-h-screen">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/60 lg:hidden" style="display: none;"></div>

    {{-- Sidebar --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 right-0 z-50 flex w-72 shrink-0 flex-col border-e border-v2-border bg-v2-surface text-v2-text transition-transform duration-200 lg:static lg:translate-x-0"
    >
        <div class="flex items-center gap-3 px-6 py-6">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-v2-primary/15 text-v2-primary">
                <x-icon name="car" class="w-6 h-6" />
            </div>
            <div>
                <div class="text-lg font-black text-v2-text">ناوراکار</div>
                <div class="text-[11px] font-bold text-v2-text-muted">پنل مدیریت</div>
            </div>
        </div>

        <nav class="mt-2 flex-1 space-y-1 overflow-y-auto px-3 pb-4">
            @if (auth()->user()?->canManageSales())
                {{-- Dashboard available to both admin and sales --}}
                @php $active = request()->routeIs('admin.dashboard*'); @endphp
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 min-h-[44px] rounded-xl px-3.5 py-2.5 text-sm font-bold transition-colors
                   {{ $active ? 'bg-v2-primary text-white' : 'text-v2-text-muted hover:bg-v2-elevated hover:text-v2-text' }}">
                    <x-icon name="dashboard" class="w-[18px] h-[18px]" />
                    داشبورد
                </a>
            @endif

            @if (auth()->user()?->isAdmin())
                @foreach (array_slice($navItems, 1) as $item)
                    @php $active = request()->routeIs($item['route'].'*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 min-h-[44px] rounded-xl px-3.5 py-2.5 text-sm font-bold transition-colors
                       {{ $active ? 'bg-v2-primary text-white' : 'text-v2-text-muted hover:bg-v2-elevated hover:text-v2-text' }}">
                        <x-icon :name="$item['icon']" class="w-[18px] h-[18px]" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endif

            @if (auth()->user()?->canManageSales())
                @if (auth()->user()?->isAdmin())
                    <div class="mt-4 mb-1 px-3.5 text-[11px] font-bold uppercase tracking-wider text-v2-text-muted/70">فروش</div>
                @endif
                @foreach ($salesNavItems as $item)
                    @php $active = request()->routeIs($item['route'].'*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 min-h-[44px] rounded-xl px-3.5 py-2.5 text-sm font-bold transition-colors
                       {{ $active ? 'bg-v2-primary text-white' : 'text-v2-text-muted hover:bg-v2-elevated hover:text-v2-text' }}">
                        <x-icon :name="$item['icon']" class="w-[18px] h-[18px]" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endif

            @if (auth()->user()?->canManageContent())
                <div class="mt-4 mb-1 px-3.5 text-[11px] font-bold uppercase tracking-wider text-v2-text-muted/70">مدیریت محتوا</div>
                @foreach ($contentNavItems as $item)
                    @php $active = request()->routeIs($item['route'].'*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 min-h-[44px] rounded-xl px-3.5 py-2.5 text-sm font-bold transition-colors
                       {{ $active ? 'bg-v2-primary text-white' : 'text-v2-text-muted hover:bg-v2-elevated hover:text-v2-text' }}">
                        <x-icon :name="$item['icon']" class="w-[18px] h-[18px]" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endif

            @if (auth()->user()?->isAdmin())
                <div class="mt-4 mb-1 px-3.5 text-[11px] font-bold uppercase tracking-wider text-v2-text-muted/70">فقط مدیر</div>
                @foreach ($adminNavItems as $item)
                    @php $active = request()->routeIs($item['route'].'*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 min-h-[44px] rounded-xl px-3.5 py-2.5 text-sm font-bold transition-colors
                       {{ $active ? 'bg-v2-primary text-white' : 'text-v2-text-muted hover:bg-v2-elevated hover:text-v2-text' }}">
                        <x-icon :name="$item['icon']" class="w-[18px] h-[18px]" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="border-t border-v2-border p-4">
            <a href="{{ route('public.lead-form') }}" target="_blank"
               class="flex items-center gap-3 min-h-[44px] rounded-xl px-3.5 py-2.5 text-sm font-bold text-v2-success hover:bg-v2-elevated">
                <x-icon name="external-link" class="w-[18px] h-[18px]" />
                فرم عمومی فروش ↗
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full min-h-[44px] items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-bold text-v2-error hover:bg-v2-elevated">
                    <x-icon name="logout" class="w-[18px] h-[18px]" />
                    خروج
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-v2-border bg-v2-bg/85 px-4 py-3.5 backdrop-blur-md sm:px-6">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" aria-label="باز کردن منوی مدیریت" class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg p-2 text-v2-text-muted hover:bg-v2-elevated lg:hidden">
                    <x-icon name="menu" class="w-5 h-5" />
                </button>
                <div>
                    <h1 class="text-base font-extrabold text-v2-text sm:text-lg">{{ $pageTitle ?? '' }}</h1>
                    @isset($pageSubtitle)
                        <p class="hidden text-xs text-v2-text-muted sm:block">{{ $pageSubtitle }}</p>
                    @endisset
                </div>
            </div>

            {{-- Real search: submits to the existing admin.requests.index ?q= filter. There is no unified
                 cross-entity (customers/proformas/content) search backend yet — see GAP_REPORT.md §6. --}}
            @if (auth()->user()?->canManageSales())
            <form method="GET" action="{{ route('admin.requests.index') }}" class="hidden max-w-xs flex-1 md:block">
                <label for="admin-shell-search" class="sr-only">جستجو در درخواست‌ها</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute inset-y-0 right-3 my-auto h-4 w-4 text-v2-text-muted" />
                    <input id="admin-shell-search" type="search" name="q" placeholder="جستجو در درخواست‌ها..."
                           class="min-h-[40px] w-full rounded-xl border border-v2-border bg-v2-elevated py-2 pe-9 ps-3 text-xs text-v2-text placeholder:text-v2-text-muted focus:border-v2-primary focus:outline-none focus:ring-2 focus:ring-v2-primary/30">
                </div>
            </form>
            @endif

            <div class="flex items-center gap-2">
                {{-- Icon only: no live notification backend exists yet (no fabricated unread count) — see GAP_REPORT.md §6. --}}
                <span class="flex h-10 w-10 items-center justify-center rounded-full text-v2-text-muted" aria-hidden="true">
                    <x-icon name="bell" class="w-[18px] h-[18px]" />
                </span>

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" @click.outside="open = false" :aria-expanded="open"
                            class="flex min-h-[44px] items-center gap-2 rounded-xl px-1.5 py-1 hover:bg-v2-elevated">
                        <div class="hidden text-left sm:block">
                            <div class="text-sm font-bold text-v2-text">{{ auth()->user()?->displayName() }}</div>
                            <div class="text-[11px] font-semibold text-v2-text-muted">
                                {{ match(auth()->user()?->role) { 'admin' => 'مدیر سیستم', 'content_manager' => 'مدیر محتوا', default => 'کارشناس فروش' } }}
                            </div>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-v2-primary/15 text-sm font-black text-v2-primary">
                            {{ mb_substr(auth()->user()?->displayName() ?? '?', 0, 1) }}
                        </div>
                        <x-icon name="chevron-down" class="hidden w-4 h-4 text-v2-text-muted sm:block" />
                    </button>
                    <div x-show="open" x-cloak x-transition
                         class="absolute left-0 top-full z-40 mt-2 w-48 rounded-xl border border-v2-border bg-v2-surface p-1.5 shadow-soft-dark"
                         style="display:none">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full min-h-[44px] items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-bold text-v2-error hover:bg-v2-elevated">
                                <x-icon name="logout" class="w-[18px] h-[18px]" />
                                خروج
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-6 pb-24 sm:px-6 lg:px-8 lg:pb-6">
            {{ $slot }}
        </main>
    </div>

    {{--
        Admin mobile bottom nav per DESIGN_SPEC.md §3 (پنل مدیریت موبایل) and
        docs/design-v2/assets/06-admin-mobile.png: داشبورد/فروش/محتوا/تقویم/منو.
        Permission-aware like the sidebar (فروش/محتوا only for roles that can see them).
        "تقویم" has no backing route yet — no calendar/meeting subsystem exists in the
        repo (see GAP_REPORT.md §2) — kept visible per the reference, disabled with a
        reason rather than silently dropped or faked. "منو" opens the same drawer as
        the header hamburger button (real, not a dead end) — needs the adminShell
        Alpine scope, so this nav stays inside that root div.
    --}}
    <nav aria-label="ناوبری پایین پنل مدیریت" class="fixed inset-x-0 bottom-0 z-40 flex border-t border-v2-border bg-v2-surface/95 backdrop-blur-md lg:hidden" style="padding-bottom: env(safe-area-inset-bottom)">
        @if (auth()->user()?->canManageSales())
            @php $active = request()->routeIs('admin.dashboard*'); @endphp
            <a href="{{ route('admin.dashboard') }}" class="flex min-h-[48px] flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-bold {{ $active ? 'text-v2-primary' : 'text-v2-text-muted' }}" @if($active) aria-current="page" @endif>
                <x-icon name="dashboard" class="w-5 h-5" /> داشبورد
            </a>
            @php $active = request()->routeIs('admin.kanban*') || request()->routeIs('admin.requests.*') || request()->routeIs('admin.invoices.*'); @endphp
            <a href="{{ route('admin.kanban') }}" class="flex min-h-[48px] flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-bold {{ $active ? 'text-v2-primary' : 'text-v2-text-muted' }}" @if($active) aria-current="page" @endif>
                <x-icon name="kanban" class="w-5 h-5" /> فروش
            </a>
        @endif
        @if (auth()->user()?->canManageContent())
            @php $active = request()->routeIs('admin.car-listings.*') || request()->routeIs('admin.posts.*'); @endphp
            <a href="{{ route('admin.car-listings.index') }}" class="flex min-h-[48px] flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-bold {{ $active ? 'text-v2-primary' : 'text-v2-text-muted' }}" @if($active) aria-current="page" @endif>
                <x-icon name="car" class="w-5 h-5" /> محتوا
            </a>
        @endif
        <span aria-disabled="true" title="تقویم جلسات و تماس‌ها هنوز پیاده‌سازی نشده" class="flex min-h-[48px] flex-1 cursor-not-allowed flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-bold text-v2-text-muted/50">
            <x-icon name="calendar" class="w-5 h-5" /> تقویم
        </span>
        <button type="button" @click="sidebarOpen = true" class="flex min-h-[48px] flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-bold text-v2-text-muted">
            <x-icon name="menu" class="w-5 h-5" /> منو
        </button>
    </nav>
</div>

<x-toast-container />
</body>
</html>
