@props(['lines' => 1, 'height' => 'h-4', 'variant' => 'default'])

@php
$bar = $variant === 'v2' ? 'bg-v2-border' : 'bg-ink-200 dark:bg-white/10';
@endphp

<div {{ $attributes->merge(['class' => 'animate-pulse space-y-2', 'role' => 'status', 'aria-label' => 'در حال بارگذاری']) }}>
    @for ($i = 0; $i < $lines; $i++)
        <div class="{{ $height }} {{ $bar }} rounded-lg {{ $i === $lines - 1 && $lines > 1 ? 'w-2/3' : 'w-full' }}"></div>
    @endfor
</div>
