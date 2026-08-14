<div
    x-data
    class="pointer-events-none fixed inset-x-0 bottom-5 z-[100] flex flex-col items-center gap-2 px-4"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-bold text-white shadow-soft-lg"
            :class="{
                'bg-ink-900': toast.type === 'info',
                'bg-emerald-600': toast.type === 'success',
                'bg-rose-600': toast.type === 'error',
            }"
        >
            <span x-text="toast.message"></span>
            <button type="button" @click="$store.toasts.remove(toast.id)" class="opacity-70 hover:opacity-100">
                <x-icon name="x" class="w-4 h-4" />
            </button>
        </div>
    </template>
</div>

@if (session('success'))
    <script>document.addEventListener('DOMContentLoaded', () => window.pushToast(@json(session('success')), 'success'));</script>
@endif
@if (session('error'))
    <script>document.addEventListener('DOMContentLoaded', () => window.pushToast(@json(session('error')), 'error'));</script>
@endif
@if (session('status'))
    <script>document.addEventListener('DOMContentLoaded', () => window.pushToast(@json(session('status')), 'info'));</script>
@endif
