<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$pageSubtitle">

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card variant="v2" label="درخواست‌های امروز" icon="inbox" note="درخواست با مشخصات تماس">{{ number_format($todayRequests) }}</x-stat-card>
        <x-stat-card variant="v2" label="محاسبات امروز" icon="calculator" note="با یا بدون ثبت مشخصات">{{ number_format($todayCalcs) }}</x-stat-card>
        <x-stat-card variant="v2" label="درخواست‌های ۷ روز اخیر" icon="calendar" note="۳۰ روز اخیر: {{ number_format($monthRequests) }}">{{ number_format($weekRequests) }}</x-stat-card>
        <x-stat-card variant="v2" label="میانگین جمع کل هزینه" icon="trend-up" note="تومان — بر اساس درخواست‌های ثبت‌شده">{{ number_format($avgTotal) }}</x-stat-card>
        <x-stat-card variant="v2" label="استعلام شاسی امروز" icon="vin" :note="$isAdmin ? number_format($unassignedCount).' درخواست بدون الحاق' : 'گزارش کامل در صفحه شماره‌شاسی‌ها'">{{ number_format($todayVin) }}</x-stat-card>
        <x-stat-card variant="v2" label="تماس‌های امروز" icon="calendar" accent="amber">
            {{ number_format($callsToday) }}
            <x-slot:note><a href="{{ route('admin.requests.index') }}" class="font-bold text-v2-primary">مشاهده لیست</a></x-slot:note>
        </x-stat-card>
        <x-stat-card variant="v2" label="سرنخ‌های داغ" icon="flame" accent="red">
            {{ number_format($hotLeads) }}
            <x-slot:note><a href="{{ route('admin.kanban') }}?temp=hot" class="font-bold text-v2-primary">مشاهده در کانبان</a></x-slot:note>
        </x-stat-card>
    </div>

    {{-- Calendar widget + monthly performance chart, matching 02-admin-dashboard-calendar.png row 2 --}}
    <div class="mb-6 grid gap-5 lg:grid-cols-[1.3fr_.7fr]">
        <x-card variant="v2" title="تقویم جلسات و تماس‌ها" icon="calendar">
            <x-slot:subtitle>هفته جاری — <a href="{{ route('admin.calendar.index') }}" class="text-v2-primary hover:underline">مشاهده تقویم کامل</a></x-slot:subtitle>
            @php
                $weekDays = collect(range(0, 6))->map(fn ($i) => now()->startOfWeek(\Carbon\Carbon::SATURDAY)->addDays($i));
                $eventsByDay = $weekEvents->groupBy(fn ($e) => $e->starts_at->toDateString());
            @endphp
            <div class="grid grid-cols-7 gap-1.5">
                @foreach ($weekDays as $day)
                    <div class="rounded-lg border border-v2-border bg-v2-bg p-1.5 {{ $day->isToday() ? 'ring-2 ring-v2-primary' : '' }}" style="min-height: 90px">
                        <div class="text-center text-[10px] font-bold text-v2-text-muted">{{ $day->translatedFormat('D') }} <span class="num-font">{{ $day->translatedFormat('j') }}</span></div>
                        <div class="mt-1 space-y-1">
                            @foreach ($eventsByDay->get($day->toDateString(), collect())->take(2) as $event)
                                <div class="truncate rounded bg-v2-primary/15 px-1 py-0.5 text-[9px] font-bold text-v2-primary num-font">{{ $event->starts_at->format('H:i') }} {{ $event->typeLabel() }}</div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3">
                <x-button :href="route('admin.calendar.index')" variant="v2-secondary" size="sm"><x-icon name="calendar" class="w-4 h-4" /> جلسه یا تماس جدید</x-button>
            </div>
        </x-card>

        <x-card variant="v2" title="روند ۱۴ روز اخیر" icon="trend-up">
            @php $maxVal = max(1, collect($daily)->max(fn ($d) => max($d['requests'], $d['calcs']))); @endphp
            <div class="rounded-xl bg-v2-bg p-4">
                <div class="space-y-2.5">
                    @foreach ($daily as $d)
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="font-bold text-v2-text-muted">{{ \Illuminate\Support\Carbon::parse($d['date'])->translatedFormat('j M') }}</span>
                                <span class="num-font font-extrabold text-v2-text">{{ $d['requests'] }} / {{ $d['calcs'] }}</span>
                            </div>
                            <div class="flex gap-1">
                                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-v2-elevated">
                                    <div class="h-full rounded-full bg-v2-accent" style="width: {{ max(($d['requests'] / $maxVal) * 100, 2) }}%"></div>
                                </div>
                                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-v2-elevated">
                                    <div class="h-full rounded-full bg-v2-primary" style="width: {{ max(($d['calcs'] / $maxVal) * 100, 2) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex justify-center gap-5 text-xs">
                    <span class="flex items-center gap-1.5 font-bold text-v2-text-muted"><i class="h-2.5 w-2.5 rounded-sm bg-v2-accent"></i> درخواست استعلام</span>
                    <span class="flex items-center gap-1.5 font-bold text-v2-text-muted"><i class="h-2.5 w-2.5 rounded-sm bg-v2-primary"></i> محاسبه انجام‌شده</span>
                </div>
            </div>
        </x-card>
    </div>

    {{-- Sales pipeline mini-view + latest requests, matching reference row 3 --}}
    <div class="mb-6 grid gap-5 lg:grid-cols-[1.3fr_.7fr]">
        <x-card variant="v2" title="پایپ‌لاین فروش" icon="kanban">
            <x-slot:subtitle><a href="{{ route('admin.kanban') }}" class="text-v2-primary hover:underline">مشاهده کانبان کامل</a></x-slot:subtitle>
            @if ($pipelineByStage->isEmpty())
                <x-empty-state variant="v2" icon="kanban" title="سرنخی در پایپ‌لاین نیست." />
            @else
                <div class="flex gap-3 overflow-x-auto pb-1">
                    @foreach ($pipelineByStage as $row)
                        <div class="w-40 shrink-0 rounded-xl border border-v2-border bg-v2-bg p-2.5">
                            <div class="flex items-center justify-between text-[11px] font-bold text-v2-text-muted">
                                <span class="truncate">{{ $row['stage']->name }}</span>
                                <span class="num-font shrink-0 rounded-full bg-v2-elevated px-1.5 text-v2-text">{{ $row['count'] }}</span>
                            </div>
                            <div class="mt-1.5 space-y-1">
                                @foreach ($row['sample'] as $lead)
                                    <div class="truncate rounded-lg bg-v2-elevated px-2 py-1 text-[10px] font-semibold text-v2-text">{{ $lead->name ?: 'بدون‌نام' }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card variant="v2" title="آخرین درخواست‌های استعلام" icon="inbox">
            @if ($recentRequests->isEmpty())
                <x-empty-state variant="v2" icon="inbox" title="هنوز درخواستی ثبت نشده است." />
            @else
                <div class="space-y-2">
                    @foreach ($recentRequests->take(5) as $r)
                        <div class="flex items-center justify-between gap-2 rounded-lg bg-v2-bg px-2.5 py-2 text-xs">
                            <div class="min-w-0">
                                <div class="truncate font-bold text-v2-text">{{ $r->name }}</div>
                                <div class="truncate text-v2-text-muted">{{ $r->car_label }}</div>
                            </div>
                            <x-badge :color="$r->email_sent ? 'v2-success' : 'v2-error'">{{ $r->email_sent ? 'ارسال شد' : 'نامشخص' }}</x-badge>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3"><x-button :href="route('admin.requests.index')" variant="v2-secondary" size="sm">مشاهده همه</x-button></div>
            @endif
        </x-card>
    </div>

    @if ($isAdmin)
        <div class="mb-6 grid gap-5 lg:grid-cols-2">
            <x-card variant="v2" title="وضعیت ایمپورت" icon="inbox">
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-xl bg-v2-bg p-3">
                        <div class="flex items-center justify-center gap-1.5 text-v2-success"><x-icon name="check-circle" class="w-4 h-4" /> موفق</div>
                        <div class="mt-1 text-2xl font-black text-v2-text num-font">{{ $importStatus->succeeded ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl bg-v2-bg p-3">
                        <div class="flex items-center justify-center gap-1.5 text-v2-warning"><x-icon name="calendar" class="w-4 h-4" /> در صف</div>
                        <div class="mt-1 text-2xl font-black text-v2-text num-font">{{ $importStatus->queued ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl bg-v2-bg p-3">
                        <div class="flex items-center justify-center gap-1.5 text-v2-error"><x-icon name="x" class="w-4 h-4" /> خطا</div>
                        <div class="mt-1 text-2xl font-black text-v2-text num-font">{{ $importStatus->failed ?? 0 }}</div>
                    </div>
                </div>
            </x-card>

            <x-card variant="v2" title="نرخ‌های امروز" icon="target" subtitle="منبع: تنظیمات نرخ ارز پنل مدیریت.">
                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-xl bg-v2-bg p-3">
                        <div class="text-xs font-bold text-v2-text-muted">درهم (AED)</div>
                        <div class="mt-1 text-xl font-black text-v2-text num-font">{{ number_format($todayRates['aed']) }}</div>
                        <div class="text-[11px] text-v2-text-muted">تومان</div>
                    </div>
                    <div class="rounded-xl bg-v2-bg p-3">
                        <div class="text-xs font-bold text-v2-text-muted">دلار (USD)</div>
                        <div class="mt-1 text-xl font-black text-v2-text num-font">{{ number_format($todayRates['usd']) }}</div>
                        <div class="text-[11px] text-v2-text-muted">تومان</div>
                    </div>
                </div>
                <div class="mt-3">
                    <x-button :href="route('admin.settings.edit')" variant="v2-secondary" size="sm">بروزرسانی نرخ‌ها</x-button>
                </div>
            </x-card>
        </div>
    @endif

    <details class="mb-6 rounded-2xl border border-v2-border bg-v2-surface p-5">
        <summary class="cursor-pointer text-sm font-extrabold text-v2-text">جزئیات بیشتر: توزیع دسته خودرو و پرتکرارترین خودروها</summary>
        <div class="mt-4 grid gap-5 lg:grid-cols-2">
            <div>
                @php $palette = ['#1677FF', '#20C7E9', '#22C55E', '#9AAAC1', '#5B8DEF', '#0EA5E9']; @endphp
                <div class="rounded-xl bg-v2-bg p-4">
                    @if ($catDist->isEmpty())
                        <x-empty-state variant="v2" icon="calculator" title="هنوز داده‌ای ثبت نشده." />
                    @else
                        @php $totalCat = $catDist->sum('c'); @endphp
                        <div class="space-y-2">
                            @foreach ($catDist as $i => $row)
                                @php $pct = $totalCat ? round($row->c / $totalCat * 100, 1) : 0; @endphp
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-sm" style="background: {{ $palette[$i % count($palette)] }}"></span>
                                    <span class="flex-1 font-semibold text-v2-text-muted">{{ $row->category }}</span>
                                    <span class="num-font font-extrabold text-v2-text">{{ $row->c }} ({{ $pct }}٪)</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div>
                @if ($topCars->isEmpty())
                    <x-empty-state variant="v2" icon="car" title="داده‌ای موجود نیست." />
                @else
                    @php $maxCar = $topCars->max('c'); @endphp
                    <div class="space-y-2.5">
                        @foreach ($topCars as $i => $c)
                            <div>
                                <div class="mb-1 flex justify-between text-xs">
                                    <span class="font-bold text-v2-text-muted">{{ $c->car_label }}</span>
                                    <span class="num-font font-extrabold text-v2-text">{{ $c->c }}</span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-v2-elevated">
                                    <div class="h-full rounded-full" style="width: {{ max(($c->c / $maxCar) * 100, 3) }}%; background: {{ $palette[$i % count($palette)] }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </details>

    <x-card variant="v2" title="خروجی اکسل گزارش‌ها" icon="download" subtitle="خروجی فایل اکسل (CSV) از تعداد و جزئیات درخواست‌ها و محاسبات، برای بازه دلخواه.">
        <div class="flex flex-wrap gap-2.5">
            <x-button :href="route('admin.export', ['type' => 'requests'])" variant="v2-primary"><x-icon name="download" class="w-4 h-4" /> خروجی همه درخواست‌های استعلام</x-button>
            <x-button :href="route('admin.export', ['type' => 'calculations'])" variant="v2-primary"><x-icon name="download" class="w-4 h-4" /> خروجی همه محاسبات ثبت‌شده</x-button>
            <x-button :href="route('admin.export', ['type' => 'requests', 'range' => 'today'])" variant="v2-secondary">خروجی درخواست‌های امروز</x-button>
            <x-button :href="route('admin.export', ['type' => 'requests', 'range' => 'month'])" variant="v2-secondary">خروجی درخواست‌های ۳۰ روز اخیر</x-button>
        </div>
    </x-card>

</x-layouts.admin>
