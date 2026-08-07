<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$pageSubtitle">

    <div class="mb-6 overflow-hidden rounded-2xl bg-gradient-to-l from-brand-900 to-brand-700 p-6 text-white shadow-soft-lg sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-base font-extrabold"><x-icon name="phone" class="w-5 h-5" /> فرم عمومی ثبت تماس فروش</h2>
                <p class="mt-1 max-w-lg text-sm text-brand-100">این لینک را برای فروشنده‌ها بفرستید تا بدون نیاز به ورود، گزارش تماس‌های جدید را ثبت کنند.</p>
            </div>
            <x-button :href="route('public.lead-form')" variant="amber" target="_blank">مشاهده فرم ↗</x-button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="درخواست‌های امروز" icon="inbox" note="درخواست با مشخصات تماس">{{ number_format($todayRequests) }}</x-stat-card>
        <x-stat-card label="محاسبات امروز" icon="calculator" note="با یا بدون ثبت مشخصات">{{ number_format($todayCalcs) }}</x-stat-card>
        <x-stat-card label="درخواست‌های ۷ روز اخیر" icon="calendar" note="۳۰ روز اخیر: {{ number_format($monthRequests) }}">{{ number_format($weekRequests) }}</x-stat-card>
        <x-stat-card label="میانگین جمع کل هزینه" icon="trend-up" note="تومان — بر اساس درخواست‌های ثبت‌شده">{{ number_format($avgTotal) }}</x-stat-card>
        <x-stat-card label="استعلام شاسی امروز" icon="vin" :note="$isAdmin ? number_format($unassignedCount).' درخواست بدون الحاق' : 'گزارش کامل در صفحه شماره‌شاسی‌ها'">{{ number_format($todayVin) }}</x-stat-card>
        <x-stat-card label="تماس‌های امروز" icon="calendar" accent="amber">
            {{ number_format($callsToday) }}
            <x-slot:note><a href="{{ route('admin.requests.index') }}" class="font-bold text-brand-600 dark:text-brand-300">مشاهده لیست</a></x-slot:note>
        </x-stat-card>
        <x-stat-card label="سرنخ‌های داغ 🔴" icon="flame" accent="red">
            {{ number_format($hotLeads) }}
            <x-slot:note><a href="{{ route('admin.kanban') }}?temp=hot" class="font-bold text-brand-600 dark:text-brand-300">مشاهده در کانبان</a></x-slot:note>
        </x-stat-card>
    </div>

    <div class="mb-6 grid gap-5 lg:grid-cols-[1.3fr_.7fr]">
        <x-card title="روند ۱۴ روز اخیر" icon="trend-up">
            @php $maxVal = max(1, collect($daily)->max(fn ($d) => max($d['requests'], $d['calcs']))); @endphp
            <div class="rounded-xl bg-ink-50 p-4 dark:bg-white/5">
                <div class="space-y-2.5">
                    @foreach ($daily as $d)
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="font-bold text-ink-500 dark:text-ink-400">{{ \Illuminate\Support\Carbon::parse($d['date'])->translatedFormat('j M') }}</span>
                                <span class="num-font font-extrabold text-ink-700 dark:text-ink-200">درخواست: {{ $d['requests'] }} | محاسبه: {{ $d['calcs'] }}</span>
                            </div>
                            <div class="flex gap-1">
                                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-ink-200 dark:bg-white/10">
                                    <div class="h-full rounded-full bg-amber-500" style="width: {{ max(($d['requests'] / $maxVal) * 100, 2) }}%"></div>
                                </div>
                                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-ink-200 dark:bg-white/10">
                                    <div class="h-full rounded-full bg-brand-600" style="width: {{ max(($d['calcs'] / $maxVal) * 100, 2) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex justify-center gap-5 text-xs">
                    <span class="flex items-center gap-1.5 font-bold text-ink-500 dark:text-ink-400"><i class="h-2.5 w-2.5 rounded-sm bg-amber-500"></i> درخواست استعلام</span>
                    <span class="flex items-center gap-1.5 font-bold text-ink-500 dark:text-ink-400"><i class="h-2.5 w-2.5 rounded-sm bg-brand-600"></i> محاسبه انجام‌شده</span>
                </div>
            </div>
        </x-card>

        <x-card title="توزیع دسته خودرو" icon="calculator">
            @php $palette = ['#2952E0', '#FF8A1E', '#8B5CF6', '#16A34A', '#5B6478', '#9FB2FF', '#D9690A', '#0EA5E9']; @endphp
            <div class="rounded-xl bg-ink-50 p-4 dark:bg-white/5">
                @if ($catDist->isEmpty())
                    <x-empty-state icon="calculator" title="هنوز داده‌ای ثبت نشده." />
                @else
                    @php $totalCat = $catDist->sum('c'); @endphp
                    <div class="space-y-2">
                        @foreach ($catDist as $i => $row)
                            @php $pct = $totalCat ? round($row->c / $totalCat * 100, 1) : 0; @endphp
                            <div class="flex items-center gap-2 text-xs">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-sm" style="background: {{ $palette[$i % count($palette)] }}"></span>
                                <span class="flex-1 font-semibold text-ink-500 dark:text-ink-400">{{ $row->category }}</span>
                                <span class="num-font font-extrabold">{{ $row->c }} ({{ $pct }}٪)</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-card>
    </div>

    <div class="mb-6 grid gap-5 lg:grid-cols-[1.3fr_.7fr]">
        <x-card title="آخرین درخواست‌های استعلام" icon="inbox">
            @if ($recentRequests->isEmpty())
                <x-empty-state icon="inbox" title="هنوز درخواستی ثبت نشده است." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-ink-100 text-xs font-extrabold text-ink-400 dark:border-white/10 dark:text-ink-500">
                                <th class="whitespace-nowrap px-2.5 py-2 text-start">تاریخ</th>
                                <th class="whitespace-nowrap px-2.5 py-2 text-start">نام</th>
                                <th class="whitespace-nowrap px-2.5 py-2 text-start">تلفن</th>
                                <th class="whitespace-nowrap px-2.5 py-2 text-start">خودرو</th>
                                <th class="whitespace-nowrap px-2.5 py-2 text-start">جمع کل</th>
                                <th class="whitespace-nowrap px-2.5 py-2 text-start">ایمیل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentRequests as $r)
                                <tr class="border-b border-ink-100 hover:bg-ink-50 dark:border-white/5 dark:hover:bg-white/5">
                                    <td class="px-2.5 py-2.5 text-xs text-ink-500">{{ $r->created_at->translatedFormat('j M') }}</td>
                                    <td class="px-2.5 py-2.5 font-semibold">{{ $r->name }}</td>
                                    <td class="num-font px-2.5 py-2.5">{{ $r->phone }}</td>
                                    <td class="px-2.5 py-2.5">{{ $r->car_label }}</td>
                                    <td class="num-font px-2.5 py-2.5 font-extrabold text-brand-700 dark:text-brand-300">{{ number_format($r->total_with_profit) }}</td>
                                    <td class="px-2.5 py-2.5"><x-badge :color="$r->email_sent ? 'green' : 'red'">{{ $r->email_sent ? 'ارسال شد' : 'نامشخص' }}</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4"><x-button :href="route('admin.requests.index')" variant="secondary">مشاهده همه درخواست‌ها</x-button></div>
            @endif
        </x-card>

        <x-card title="پرتکرارترین خودروها" icon="car">
            @if ($topCars->isEmpty())
                <x-empty-state icon="car" title="داده‌ای موجود نیست." />
            @else
                @php $maxCar = $topCars->max('c'); @endphp
                <div class="space-y-2.5">
                    @foreach ($topCars as $i => $c)
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="font-bold text-ink-500 dark:text-ink-400">{{ $c->car_label }}</span>
                                <span class="num-font font-extrabold">{{ $c->c }}</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-ink-100 dark:bg-white/10">
                                <div class="h-full rounded-full" style="width: {{ max(($c->c / $maxCar) * 100, 3) }}%; background: {{ $palette[$i % count($palette)] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    <x-card title="خروجی اکسل گزارش‌ها" icon="download" subtitle="خروجی فایل اکسل (CSV) از تعداد و جزئیات درخواست‌ها و محاسبات، برای بازه دلخواه.">
        <div class="flex flex-wrap gap-2.5">
            <x-button :href="route('admin.export', ['type' => 'requests'])" variant="amber"><x-icon name="download" class="w-4 h-4" /> خروجی همه درخواست‌های استعلام</x-button>
            <x-button :href="route('admin.export', ['type' => 'calculations'])" variant="amber"><x-icon name="download" class="w-4 h-4" /> خروجی همه محاسبات ثبت‌شده</x-button>
            <x-button :href="route('admin.export', ['type' => 'requests', 'range' => 'today'])" variant="secondary">خروجی درخواست‌های امروز</x-button>
            <x-button :href="route('admin.export', ['type' => 'requests', 'range' => 'month'])" variant="secondary">خروجی درخواست‌های ۳۰ روز اخیر</x-button>
        </div>
    </x-card>

</x-layouts.admin>
