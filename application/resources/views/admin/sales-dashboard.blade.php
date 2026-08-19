<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$pageSubtitle">

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card variant="v2" label="سرنخ‌های جدید" icon="users" accent="brand">{{ number_format($newLeads) }}</x-stat-card>
        <x-stat-card variant="v2" label="پیگیری امروز" icon="check-circle" accent="brand">{{ number_format($todayFollowUps) }}</x-stat-card>
        <x-stat-card variant="v2" label="جلسات امروز" icon="calendar" accent="brand">{{ number_format($todayMeetings) }}</x-stat-card>
        <x-stat-card variant="v2" label="پیش‌فاکتور باز" icon="invoice" accent="amber">{{ number_format($openProforma) }}</x-stat-card>
    </div>

    <div class="mb-6 grid gap-5 lg:grid-cols-3">
        <x-card variant="v2" title="{{ $isAdmin ? 'پایپ‌لاین (همه)' : 'پایپ‌لاین من' }}" icon="kanban" class="min-w-0 lg:col-span-2">
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
                                    <a href="{{ route('admin.requests.show', $lead) }}" class="block truncate rounded-lg bg-v2-elevated px-2 py-1 text-[10px] font-semibold text-v2-text hover:bg-v2-primary/20">{{ $lead->name ?: 'بدون‌نام' }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card variant="v2" title="برنامه امروز" icon="clock" class="min-w-0">
            @if ($todaySchedule->isEmpty())
                <x-empty-state variant="v2" icon="clock" title="برای امروز رویدادی برنامه‌ریزی نشده است." />
            @else
                <div class="space-y-2">
                    @foreach ($todaySchedule as $event)
                        <div class="flex items-center gap-3 rounded-lg bg-v2-bg px-2.5 py-2 text-xs">
                            <span class="shrink-0 rounded-md bg-v2-primary/15 px-2 py-1 font-black text-v2-primary num-font">{{ $event->starts_at->format('H:i') }}</span>
                            <div class="min-w-0">
                                <div class="truncate font-bold text-v2-text">{{ $event->displayTitle() }}</div>
                                @if ($event->quoteRequest)
                                    <div class="truncate text-v2-text-muted">{{ $event->quoteRequest->name }} — {{ $event->quoteRequest->car_label }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="mt-3"><x-button :href="route('admin.calendar.index')" variant="v2-secondary" size="sm">مشاهده تقویم</x-button></div>
        </x-card>
    </div>

    <div class="mb-6 grid gap-5 lg:grid-cols-3">
        <x-card variant="v2" title="پیگیری‌های عقب‌افتاده" icon="alert" class="min-w-0">
            @if ($overdueFollowUps->isEmpty())
                <x-empty-state variant="v2" icon="check-circle" title="پیگیری عقب‌افتاده‌ای وجود ندارد." />
            @else
                <div class="space-y-2">
                    @foreach ($overdueFollowUps as $r)
                        <a href="{{ route('admin.requests.show', $r) }}" class="flex items-center justify-between gap-2 rounded-lg bg-v2-bg px-2.5 py-2 text-xs hover:bg-v2-elevated">
                            <div class="min-w-0">
                                <div class="truncate font-bold text-v2-text">{{ $r->name ?: 'بدون‌نام' }}</div>
                                <div class="truncate text-v2-text-muted">{{ $r->car_label }}</div>
                            </div>
                            <span class="shrink-0 font-bold text-v2-error num-font">{{ $r->next_call_date->format('Y-m-d') }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card variant="v2" title="پیش‌فاکتورهای اخیر" icon="invoice" class="min-w-0">
            <x-slot:subtitle><a href="{{ route('admin.invoices.index') }}" class="text-v2-primary hover:underline">مشاهده همه</a></x-slot:subtitle>
            @if ($recentProformas->isEmpty())
                <x-empty-state variant="v2" icon="invoice" title="هنوز پیش‌فاکتوری صادر نشده است." />
            @else
                <div class="space-y-2">
                    @foreach ($recentProformas as $invoice)
                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="flex items-center justify-between gap-2 rounded-lg bg-v2-bg px-2.5 py-2 text-xs hover:bg-v2-elevated">
                            <div class="min-w-0">
                                <div class="truncate font-bold text-v2-text">{{ $invoice->car_label ?: $invoice->invoice_number }}</div>
                                <div class="truncate text-v2-text-muted">{{ $invoice->invoice_number }}</div>
                            </div>
                            <x-badge :color="$invoice->status === 'تایید شده' ? 'v2-success' : ($invoice->status === 'پیش‌نویس' ? 'v2-neutral' : 'v2-primary')">{{ $invoice->status }}</x-badge>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>

        {{--
            Real funnel: current-lead-count per real, configured PipelineStage (not the reference
            image's fictional 4-stage "سرنخ/مشاوره/پیش‌فاکتور/فروش" labels — no matching data model
            exists for that). Bars are proportional to the largest stage's count so the shape is
            still readable even though there may be more than 4 stages.
        --}}
        <x-card variant="v2" title="پایپ‌لاین بر اساس مرحله" icon="target" class="min-w-0">
            @if ($funnel->isEmpty())
                <x-empty-state variant="v2" icon="target" title="مرحله‌ای تعریف نشده است." />
            @else
                <div class="space-y-2">
                    @foreach ($funnel as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-[11px] font-bold">
                                <span class="truncate text-v2-text">{{ $row['stage']->name }}</span>
                                <span class="num-font shrink-0 text-v2-text-muted">{{ $row['count'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-v2-elevated">
                                <div class="h-full rounded-full bg-v2-primary" style="width: {{ max(2, $row['count'] / $funnelMax * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

</x-layouts.admin>
