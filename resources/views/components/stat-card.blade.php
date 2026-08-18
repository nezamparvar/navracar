@props(['label', 'icon' => 'trend-up', 'note' => null, 'accent' => 'brand', 'variant' => 'default'])

@php
$accents = [
    'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300',
    'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
    'red' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
    'green' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
];
// V2 tokens (DESIGN_SPEC.md §2): green/amber/red stay status-only (success/warning/error),
// never decorative — 'brand'/default accent maps to v2-primary blue.
$v2Accents = [
    'brand' => 'bg-v2-primary/15 text-v2-primary',
    'amber' => 'bg-v2-warning/15 text-v2-warning',
    'red' => 'bg-v2-error/15 text-v2-error',
    'green' => 'bg-v2-success/15 text-v2-success',
];
$shell = $variant === 'v2'
    ? 'rounded-2xl border border-v2-border bg-v2-elevated p-5 shadow-soft-dark'
    : 'rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft dark:border-white/10 dark:bg-ink-900/60 dark:shadow-soft-dark';
$labelClass = $variant === 'v2' ? 'text-xs font-bold text-v2-text-muted' : 'text-xs font-bold text-ink-500 dark:text-ink-400';
$valueClass = $variant === 'v2' ? 'num-font text-2xl font-black text-v2-text' : 'num-font text-2xl font-black text-ink-900 dark:text-white';
$noteClass = $variant === 'v2' ? 'mt-1 text-xs text-v2-text-muted' : 'mt-1 text-xs text-ink-500 dark:text-ink-400';
$iconWrap = $variant === 'v2' ? ($v2Accents[$accent] ?? $v2Accents['brand']) : ($accents[$accent] ?? $accents['brand']);
@endphp

<div class="group {{ $shell }} transition-all duration-200 hover:-translate-y-1 hover:shadow-soft-lg">
    <div class="mb-3 flex items-center justify-between">
        <span class="{{ $labelClass }}">{{ $label }}</span>
        <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $iconWrap }}">
            <x-icon :name="$icon" class="w-[18px] h-[18px]" />
        </span>
    </div>
    <div class="{{ $valueClass }}">{{ $slot }}</div>
    @if($note)
        <div class="{{ $noteClass }}">{{ $note }}</div>
    @endif
</div>
