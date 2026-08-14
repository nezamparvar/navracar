@props(['title' => null, 'icon' => null, 'subtitle' => null, 'padded' => true])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-ink-200/70 bg-white shadow-soft dark:border-white/10 dark:bg-ink-900/60 dark:shadow-soft-dark '.($padded ? 'p-5 sm:p-6' : '')]) }}>
    @if($title)
        <div class="mb-4 flex items-center gap-2.5">
            @if($icon)
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                    <x-icon :name="$icon" class="w-5 h-5" />
                </span>
            @endif
            <div>
                <h2 class="text-base font-extrabold text-ink-900 dark:text-white">{{ $title }}</h2>
                @if($subtitle)
                    <p class="text-xs text-ink-500 dark:text-ink-400">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    @endif

    {{ $slot }}
</div>
