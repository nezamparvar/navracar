@props(['label', 'icon' => 'trend-up', 'note' => null, 'accent' => 'brand'])

@php
$accents = [
    'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300',
    'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
    'red' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
    'green' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
];
@endphp

<div class="group rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft transition-all duration-200 hover:-translate-y-1 hover:shadow-soft-lg dark:border-white/10 dark:bg-ink-900/60 dark:shadow-soft-dark">
    <div class="mb-3 flex items-center justify-between">
        <span class="text-xs font-bold text-ink-500 dark:text-ink-400">{{ $label }}</span>
        <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $accents[$accent] ?? $accents['brand'] }}">
            <x-icon :name="$icon" class="w-[18px] h-[18px]" />
        </span>
    </div>
    <div class="num-font text-2xl font-black text-ink-900 dark:text-white">{{ $slot }}</div>
    @if($note)
        <div class="mt-1 text-xs text-ink-500 dark:text-ink-400">{{ $note }}</div>
    @endif
</div>
