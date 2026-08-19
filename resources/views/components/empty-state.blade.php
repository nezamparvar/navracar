@props(['icon' => 'inbox', 'title' => 'داده‌ای موجود نیست', 'variant' => 'default'])

@php
$textClass = $variant === 'v2' ? 'text-v2-text-muted' : 'text-ink-400 dark:text-ink-500';
$iconWrapClass = $variant === 'v2' ? 'bg-v2-elevated text-v2-text-muted' : 'bg-ink-100 text-ink-400 dark:bg-white/5 dark:text-ink-500';
@endphp

<div class="flex flex-col items-center justify-center gap-3 py-14 text-center {{ $textClass }}">
    <span class="flex h-14 w-14 items-center justify-center rounded-2xl {{ $iconWrapClass }}">
        <x-icon :name="$icon" class="w-7 h-7" />
    </span>
    <p class="text-sm font-semibold">{{ $title }}</p>
    @isset($slot)
        <div class="text-xs">{{ $slot }}</div>
    @endisset
</div>
