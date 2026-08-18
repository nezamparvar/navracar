@php
    $stageColors = [
        'delivered' => 'v2-success',
        'lost' => 'v2-error',
    ];
    $stageColor = $stageColors[$quoteRequest->stage?->slug] ?? 'v2-primary';
@endphp
<x-layouts.public :title="$title">

    <div class="bg-v2-bg px-4 py-8">
    <div class="mx-auto max-w-2xl">
        <nav class="mb-4 text-xs text-v2-text-muted">
            <a href="{{ route('public.home') }}" class="hover:text-v2-primary">ناوراکار</a>
            <span class="mx-1">/</span>
            <span class="font-bold text-v2-text">پیگیری درخواست {{ $requestNumber }}</span>
        </nav>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-black text-v2-text sm:text-xl">درخواست‌ها و پیگیری</h1>
            <x-badge color="{{ $stageColor }}">{{ $isLost ? 'از دست رفته' : ($quoteRequest->stage?->name ?? 'بدون وضعیت') }}</x-badge>
        </div>

        <x-card variant="v2" class="mt-4">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-bold text-v2-text-muted">مبلغ کل (تقریبی)</dt>
                    <dd class="mt-0.5 text-sm font-extrabold text-v2-text num-font">{{ number_format((float) $quoteRequest->total_with_profit) }} <span class="text-[11px] font-bold text-v2-text-muted">تومان</span></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-v2-text-muted">خودرو</dt>
                    <dd class="mt-0.5 text-sm font-extrabold text-v2-text">{{ $quoteRequest->car_label ?: 'ثبت نشده' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-v2-text-muted">تاریخ ثبت</dt>
                    <dd class="mt-0.5 text-sm font-extrabold text-v2-text num-font">{{ $quoteRequest->created_at->format('Y-m-d') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-v2-text-muted">دسته‌بندی</dt>
                    <dd class="mt-0.5 text-sm font-extrabold text-v2-text">{{ $quoteRequest->categoryLabel() }}</dd>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <dt class="text-xs font-bold text-v2-text-muted">شماره درخواست</dt>
                    <dd class="mt-0.5 text-sm font-extrabold text-v2-text num-font">{{ $requestNumber }}</dd>
                </div>
            </dl>
        </x-card>

        {{--
            Real pipeline-stage stepper (same PipelineStage sequence the admin kanban/dashboard
            use — not the reference image's fixed payment-stage labels, since no such data model
            exists here). Only the first step ("ثبت درخواست") has a real per-step date; there is
            no stage-change-log table to source dates for the others honestly, so later steps
            show status only.
        --}}
        @if (! $isLost && $stages->isNotEmpty())
            <x-card variant="v2" title="مراحل پیگیری" icon="list" class="mt-4">
                <div class="flex overflow-x-auto pb-1">
                    @foreach ($stages as $i => $s)
                        @php
                            $done = $currentStageIndex !== null && $i < $currentStageIndex;
                            $current = $currentStageIndex !== null && $i === $currentStageIndex;
                        @endphp
                        <div class="flex w-[110px] shrink-0 flex-col items-center text-center">
                            <div class="flex w-full items-center">
                                <div class="h-0.5 flex-1 {{ $i > 0 && $i <= $currentStageIndex ? 'bg-v2-success' : 'bg-v2-border' }} {{ $i === 0 ? 'invisible' : '' }}"></div>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-black num-font
                                    {{ $done ? 'bg-v2-success text-white' : ($current ? 'bg-v2-primary text-white' : 'bg-v2-elevated text-v2-text-muted') }}">
                                    {{ $i + 1 }}
                                </span>
                                <div class="h-0.5 flex-1 {{ $i < $currentStageIndex ? 'bg-v2-success' : 'bg-v2-border' }} {{ $loop->last ? 'invisible' : '' }}"></div>
                            </div>
                            <div class="mt-2 text-[11px] font-bold text-v2-text">{{ $s->name }}</div>
                            <div class="mt-0.5 text-[10px] font-bold {{ $done ? 'text-v2-success' : ($current ? 'text-v2-primary' : 'text-v2-text-muted') }}">
                                {{ $done ? 'تکمیل شده' : ($current ? 'در حال انجام' : 'در انتظار') }}
                            </div>
                            @if ($i === 0)
                                <div class="mt-0.5 text-[10px] text-v2-text-muted num-font">{{ $quoteRequest->created_at->format('Y-m-d') }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        <x-card variant="v2" title="آخرین صورت‌حساب" icon="invoice" class="mt-4">
            @if ($latestInvoice)
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                    <div class="flex items-center justify-between border-b border-v2-border pb-2">
                        <dt class="text-xs font-bold text-v2-text-muted">شماره صورت‌حساب</dt>
                        <dd class="text-sm font-extrabold text-v2-text num-font">{{ $latestInvoice->invoice_number }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-b border-v2-border pb-2">
                        <dt class="text-xs font-bold text-v2-text-muted">وضعیت</dt>
                        <dd class="text-sm font-extrabold text-v2-text">{{ $latestInvoice->status }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-b border-v2-border pb-2">
                        <dt class="text-xs font-bold text-v2-text-muted">مبلغ قابل پرداخت</dt>
                        <dd class="text-sm font-extrabold text-v2-text num-font">{{ number_format($latestInvoice->payableAmount()) }} {{ \App\Models\Invoice::CURRENCIES[$latestInvoice->currency] ?? $latestInvoice->currency }}</dd>
                    </div>
                    @if ($latestInvoice->valid_until)
                        <div class="flex items-center justify-between border-b border-v2-border pb-2">
                            <dt class="text-xs font-bold text-v2-text-muted">اعتبار تا</dt>
                            <dd class="text-sm font-extrabold text-v2-text num-font">{{ $latestInvoice->valid_until->format('Y-m-d') }}</dd>
                        </div>
                    @endif
                </dl>
            @else
                <x-empty-state variant="v2" icon="invoice" title="هنوز صورت‌حسابی برای این درخواست صادر نشده است." />
            @endif
        </x-card>

        <x-card variant="v2" title="روند پیگیری" icon="clock" class="mt-4">
            <ol class="space-y-4">
                @foreach ($timeline as $event)
                    <li class="flex items-start gap-3">
                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-v2-primary"></span>
                        <div>
                            <div class="text-sm font-bold text-v2-text">{{ $event['label'] }}</div>
                            <div class="text-[11px] text-v2-text-muted num-font">{{ $event['at']->format('Y-m-d H:i') }}</div>
                        </div>
                    </li>
                @endforeach
            </ol>
            <p class="mt-4 text-[11px] text-v2-text-muted">
                آخرین به‌روزرسانی: <span class="num-font">{{ $timeline->last()['at']->diffForHumans() }}</span>
            </p>
        </x-card>
    </div>
    </div>
</x-layouts.public>
