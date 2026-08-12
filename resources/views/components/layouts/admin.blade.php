<!DOCTYPE html>
<html lang="fa" dir="rtl" x-data x-init="$store.theme.init()" :class="{ 'dark': $store.theme.dark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'پنل مدیریت' }} | ناوراکار</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-50 font-sans text-ink-900 dark:bg-ink-950 dark:text-ink-100">

@php
    $navItems = [
        ['route' => 'admin.dashboard', 'label' => 'داشبورد', 'icon' => 'dashboard'],
        ['route' => 'admin.kanban', 'label' => 'پایپ‌لاین (کانبان)', 'icon' => 'kanban'],
        ['route' => 'admin.requests.index', 'label' => 'درخواست‌ها (لیست)', 'icon' => 'inbox'],
        ['route' => 'admin.calculations.index', 'label' => 'محاسبات', 'icon' => 'calculator'],
        ['route' => 'admin.vin-checks.index', 'label' => 'شماره‌شاسی‌ها', 'icon' => 'vin'],
        ['route' => 'admin.invoices.index', 'label' => 'پیش‌فاکتورها', 'icon' => 'invoice'],
    ];
    $adminNavItems = [
        ['route' => 'admin.car-listings.index', 'label' => 'آگهی‌های دابیزل', 'icon' => 'car'],
        ['route' => 'admin.posts.index', 'label' => 'وبلاگ', 'icon' => 'message'],
        ['route' => 'admin.home-slides.index', 'label' => 'اسلایدر صفحه اصلی', 'icon' => 'image'],
        ['route' => 'admin.menu-items.index', 'label' => 'منوی سایت', 'icon' => 'menu'],
        ['route' => 'admin.settings.edit', 'label' => 'تنظیمات نرخ ارز', 'icon' => 'target'],
        ['route' => 'admin.templates.index', 'label' => 'قالب‌های پیام', 'icon' => 'message'],
        ['route' => 'admin.users.index', 'label' => 'کاربران', 'icon' => 'users'],
        ['route' => 'admin.activity-log.index', 'label' => 'لاگ سیستم', 'icon' => 'terminal'],
    ];
@endphp

<div x-data="{ sidebarOpen: false }" class="flex min-h-screen">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-ink-950/60 lg:hidden" style="display: none;"></div>

    {{-- Sidebar --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 right-0 z-50 flex w-72 shrink-0 flex-col bg-gradient-to-b from-ink-950 to-brand-950 text-white transition-transform duration-200 lg:static lg:translate-x-0"
    >
        <div class="flex items-center gap-3 px-6 py-6">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-ink-950 shadow-glow-amber">
                <x-icon name="car" class="w-6 h-6" />
            </div>
            <div>
                <div class="text-lg font-black">ناوراکار</div>
                <div class="text-[11px] font-bold text-brand-300">پنل مدیریت</div>
            </div>
        </div>

        <nav class="mt-2 flex-1 space-y-1 overflow-y-auto px-3 pb-4">
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['route'].'*'); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-bold transition-colors
                   {{ $active ? 'bg-amber-500 text-ink-950 shadow-glow-amber' : 'text-brand-100/80 hover:bg-white/10 hover:text-white' }}">
                    <x-icon :name="$item['icon']" class="w-[18px] h-[18px]" />
                    {{ $item['label'] }}
                </a>
            @endforeach

            @if (auth()->user()?->isAdmin())
                <div class="mt-4 mb-1 px-3.5 text-[11px] font-bold uppercase tracking-wider text-brand-300/70">فقط مدیر</div>
                @foreach ($adminNavItems as $item)
                    @php $active = request()->routeIs($item['route'].'*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-bold transition-colors
                       {{ $active ? 'bg-amber-500 text-ink-950 shadow-glow-amber' : 'text-brand-100/80 hover:bg-white/10 hover:text-white' }}">
                        <x-icon :name="$item['icon']" class="w-[18px] h-[18px]" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="border-t border-white/10 p-4">
            <a href="{{ route('public.lead-form') }}" target="_blank"
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-bold text-emerald-300 hover:bg-white/10">
                <x-icon name="external-link" class="w-[18px] h-[18px]" />
                فرم عمومی فروش ↗
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-bold text-rose-300 hover:bg-white/10">
                    <x-icon name="logout" class="w-[18px] h-[18px]" />
                    خروج
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-ink-200/70 bg-white/80 px-4 py-3.5 backdrop-blur-md dark:border-white/10 dark:bg-ink-900/70 sm:px-6">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="rounded-lg p-2 text-ink-500 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-white/10 lg:hidden">
                    <x-icon name="menu" class="w-5 h-5" />
                </button>
                <div>
                    <h1 class="text-base font-extrabold text-ink-900 dark:text-white sm:text-lg">{{ $pageTitle ?? '' }}</h1>
                    @isset($pageSubtitle)
                        <p class="hidden text-xs text-ink-500 dark:text-ink-400 sm:block">{{ $pageSubtitle }}</p>
                    @endisset
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button @click="$store.theme.toggle()" class="rounded-full p-2.5 text-ink-500 hover:bg-ink-100 dark:text-amber-300 dark:hover:bg-white/10" title="حالت تاریک/روشن">
                    <x-icon name="moon" x-show="!$store.theme.dark" class="w-[18px] h-[18px]" />
                    <x-icon name="sun" x-show="$store.theme.dark" class="w-[18px] h-[18px]" x-cloak />
                </button>
                <div class="mx-1 hidden h-8 w-px bg-ink-200 dark:bg-white/10 sm:block"></div>
                <div class="hidden text-left sm:block">
                    <div class="text-sm font-bold text-ink-900 dark:text-white">{{ auth()->user()?->displayName() }}</div>
                    <div class="text-[11px] font-semibold text-ink-500 dark:text-ink-400">{{ auth()->user()?->isAdmin() ? 'مدیر سیستم' : 'کارشناس فروش' }}</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-black text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                    {{ mb_substr(auth()->user()?->displayName() ?? '?', 0, 1) }}
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>
</div>

<x-toast-container />
</body>
</html>
