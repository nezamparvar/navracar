@php
    $statusColor = match($event->status) {
        'completed' => 'text-v2-success border-v2-success/30 bg-v2-success/10',
        'cancelled' => 'text-v2-text-muted border-v2-border bg-v2-bg opacity-60',
        default => 'text-v2-primary border-v2-primary/30 bg-v2-primary/10',
    };
@endphp
<div class="rounded-lg border px-2 py-1.5 text-[11px] {{ $statusColor }}">
    <div class="flex items-center gap-1 font-bold num-font">
        <x-icon :name="$event->typeIcon()" class="w-3 h-3" />
        {{ $event->starts_at->format('H:i') }}
    </div>
    <div class="mt-0.5 truncate font-semibold">{{ $event->displayTitle() }}</div>
</div>
