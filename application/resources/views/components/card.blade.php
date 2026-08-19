@props(['title' => null, 'icon' => null, 'subtitle' => null, 'padded' => true, 'variant' => 'default'])

@php
// 'v2' opts a card into the DESIGN_SPEC.md §2 dark surface tokens; 'default'
// keeps the existing light-card look used across all unmigrated pages.
//
// min-w-0: a grid/flex ITEM defaults to min-width:auto, which lets its intrinsic content
// (e.g. an internal overflow-x-auto row, or a long unwrapped string) force the item — and with
// it the whole row/document — wider than the parent grid/flex container allows. No-op when the
// card isn't a flex/grid item. See the round-4 responsive-overflow fixes for the concrete bugs
// this caused (mini-kanban card, content-dashboard recent-lists card).
$shell = $variant === 'v2'
    ? 'min-w-0 rounded-2xl border border-v2-border bg-v2-surface shadow-soft-dark'
    : 'min-w-0 rounded-2xl border border-ink-200/70 bg-white shadow-soft dark:border-white/10 dark:bg-ink-900/60 dark:shadow-soft-dark';
$iconWrap = $variant === 'v2'
    ? 'bg-v2-primary/15 text-v2-primary'
    : 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300';
$titleClass = $variant === 'v2' ? 'text-base font-extrabold text-v2-text' : 'text-base font-extrabold text-ink-900 dark:text-white';
$subtitleClass = $variant === 'v2' ? 'text-xs text-v2-text-muted' : 'text-xs text-ink-500 dark:text-ink-400';
@endphp

<div {{ $attributes->merge(['class' => $shell.' '.($padded ? 'p-5 sm:p-6' : '')]) }}>
    @if($title)
        <div class="mb-4 flex items-center gap-2.5">
            @if($icon)
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $iconWrap }}">
                    <x-icon :name="$icon" class="w-5 h-5" />
                </span>
            @endif
            <div>
                <h2 class="{{ $titleClass }}">{{ $title }}</h2>
                @if($subtitle)
                    <p class="{{ $subtitleClass }}">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    @endif

    {{ $slot }}
</div>
