@props(['color' => 'slate'])

@php
$map = [
    'slate' => 'bg-ink-100 text-ink-600 dark:bg-white/10 dark:text-ink-300',
    'brand' => 'bg-brand-100 text-brand-800 dark:bg-brand-500/15 dark:text-brand-300',
    'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    'green' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    'red' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
    'blue' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
];
$classes = $map[$color] ?? $map['slate'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold whitespace-nowrap $classes"]) }}>
    {{ $slot }}
</span>
