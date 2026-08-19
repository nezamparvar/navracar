@php
    $badgeColor = match($event->status) {
        'completed' => 'v2-success',
        'cancelled' => 'v2-error',
        default => 'v2-primary',
    };
@endphp
<div class="flex flex-wrap items-center justify-between gap-3 py-3">
    <div class="flex items-center gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-v2-primary/15 text-v2-primary">
            <x-icon :name="$event->typeIcon()" class="w-[18px] h-[18px]" />
        </span>
        <div>
            <div class="text-sm font-extrabold text-v2-text">{{ $event->displayTitle() }}</div>
            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-v2-text-muted">
                <span class="num-font">{{ $event->starts_at->translatedFormat('j M، H:i') }} – {{ $event->ends_at->format('H:i') }}</span>
                <span>·</span>
                <span>{{ $event->assignee?->displayName() }}</span>
                @if ($event->quoteRequest)
                    <span>·</span>
                    <a href="{{ route('admin.requests.show', $event->quoteRequest) }}" class="text-v2-primary hover:underline">درخواست #{{ $event->quoteRequest->id }}</a>
                @endif
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <x-badge :color="$badgeColor">{{ $event->statusLabel() }}</x-badge>
        @if ($event->status === 'scheduled')
            <form method="POST" action="{{ route('admin.calendar.complete', $event) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-v2-success/30 bg-v2-success/10 px-2.5 py-1.5 text-[11px] font-bold text-v2-success hover:brightness-110">انجام شد</button>
            </form>
            <form method="POST" action="{{ route('admin.calendar.cancel', $event) }}" onsubmit="return confirm('این رویداد لغو شود؟');">
                @csrf
                <button type="submit" class="rounded-lg border border-v2-error/30 bg-v2-error/10 px-2.5 py-1.5 text-[11px] font-bold text-v2-error hover:brightness-110">لغو</button>
            </form>
        @endif
    </div>
</div>
