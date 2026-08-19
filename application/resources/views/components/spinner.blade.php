@props(['size' => 'md', 'label' => 'در حال پردازش'])

@php
$sizes = [
    'sm' => 'w-4 h-4 border-2',
    'md' => 'w-6 h-6 border-[3px]',
    'lg' => 'w-9 h-9 border-4',
];
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center']) }}
    role="status"
>
    <span class="{{ $sizes[$size] ?? $sizes['md'] }} animate-spin rounded-full border-current border-t-transparent opacity-80"></span>
    <span class="sr-only">{{ $label }}</span>
</span>
