@props(['variant' => 'primary', 'href' => null, 'size' => 'md', 'type' => 'button'])

@php
$sizes = [
    'sm' => 'px-3 py-1.5 text-xs gap-1.5',
    'md' => 'px-4 py-2.5 text-sm gap-2',
    'lg' => 'px-5 py-3 text-sm gap-2',
];

$variants = [
    'primary' => 'bg-brand-600 text-white shadow-glow-brand hover:bg-brand-700 hover:-translate-y-0.5 active:translate-y-0 focus-visible:ring-brand-300',
    'amber' => 'bg-gradient-to-br from-amber-400 to-amber-600 text-ink-950 shadow-glow-amber hover:brightness-105 hover:-translate-y-0.5 active:translate-y-0 focus-visible:ring-amber-300',
    'secondary' => 'bg-white text-ink-700 border border-ink-200 hover:bg-ink-50 hover:-translate-y-0.5 active:translate-y-0 dark:bg-white/5 dark:text-ink-200 dark:border-white/10 dark:hover:bg-white/10 focus-visible:ring-ink-300',
    'ghost' => 'bg-transparent text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-white/10 focus-visible:ring-ink-300',
    'danger' => 'bg-rose-600 text-white hover:bg-rose-700 hover:-translate-y-0.5 active:translate-y-0 focus-visible:ring-rose-300',
];

$classes = 'inline-flex items-center justify-center rounded-xl font-bold transition-all duration-150 focus:outline-none focus-visible:ring-4 disabled:opacity-50 disabled:pointer-events-none disabled:translate-y-0 '
    .($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
