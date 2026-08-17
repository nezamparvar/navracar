@props(['name', 'label' => null, 'hint' => null, 'required' => false, 'variant' => 'default', 'type' => 'text', 'value' => null])

@php
// Reusable label + control + hint + inline-error scaffold so every form
// field keeps a connected label and an aria-describedby error association
// (DESIGN_SPEC.md §6/§7). Renders a plain <input> for the common text/
// email/tel/number/date/password case; pass a slot (e.g. <select>,
// <textarea>) for anything else and give it a matching id/aria-describedby
// yourself using $fieldId/$describedBy below — Blade cannot inject
// attributes into arbitrary slot HTML.
$fieldId = $attributes->get('id') ?? $name;
$error = $errors->first($name);
$describedBy = collect([
    $hint ? "{$fieldId}-hint" : null,
    $error ? "{$fieldId}-error" : null,
])->filter()->implode(' ') ?: null;

$labelClass = $variant === 'v2' ? 'text-v2-text' : 'text-ink-700 dark:text-ink-200';
$hintClass = $variant === 'v2' ? 'text-v2-text-muted' : 'text-ink-500 dark:text-ink-400';
$inputClass = $variant === 'v2'
    ? 'w-full min-h-[44px] rounded-xl border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text placeholder:text-v2-text-muted focus:outline-none focus-visible:ring-4 focus-visible:ring-v2-primary/30 '.($error ? 'border-v2-error' : 'border-v2-border focus:border-v2-primary')
    : 'w-full min-h-[44px] rounded-xl border bg-white px-3.5 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200 dark:bg-white/5 dark:text-white '.($error ? 'border-rose-400 dark:border-rose-500' : 'border-ink-200 focus:border-brand-400 dark:border-white/10');
@endphp

<div {{ $attributes->except(['id', 'class'])->merge(['class' => 'space-y-1.5 '.$attributes->get('class')]) }}>
    @if($label)
        <label for="{{ $fieldId }}" class="block text-xs font-bold {{ $labelClass }}">
            {{ $label }}
            @if($required)
                <span class="text-rose-500" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        <input
            type="{{ $type }}"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            @if($required) required @endif
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if($error) aria-invalid="true" @endif
            class="{{ $inputClass }}"
        >
    @endif

    @if($hint && ! $error)
        <p id="{{ $fieldId }}-hint" class="text-xs {{ $hintClass }}">{{ $hint }}</p>
    @endif

    @if($error)
        <p id="{{ $fieldId }}-error" class="flex items-center gap-1 text-xs font-semibold text-rose-600 dark:text-rose-400">
            <x-icon name="alert" class="w-3.5 h-3.5 shrink-0" />
            {{ $error }}
        </p>
    @endif
</div>
