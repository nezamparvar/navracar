<x-layouts.admin :page-title="$pageTitle" page-subtitle="کارت‌ها را بکشید و بین مراحل جابه‌جا کنید — تغییر بلافاصله ذخیره می‌شود.">

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-bold text-ink-500 dark:text-ink-400">جستجو (نام/تلفن)</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-bold text-ink-500 dark:text-ink-400">دما</label>
            <select name="temp" class="rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                <option value="">همه</option>
                <option value="hot" @selected(($filters['temp'] ?? '') === 'hot')>داغ 🔴</option>
                <option value="warm" @selected(($filters['temp'] ?? '') === 'warm')>معمولی 🟠</option>
                <option value="cold" @selected(($filters['temp'] ?? '') === 'cold')>سرد 🔵</option>
            </select>
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-bold text-ink-500 dark:text-ink-400">منبع</label>
            <select name="source" class="rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                <option value="">همه</option>
                @foreach ($sources as $s)
                    <option value="{{ $s }}" @selected(($filters['source'] ?? '') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        @if (auth()->user()->isAdmin())
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-ink-500 dark:text-ink-400">کارشناس</label>
                <select name="sales" class="rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                    <option value="all">همه</option>
                    @foreach ($staffList as $s)
                        <option value="{{ $s->id }}" @selected(($filters['sales'] ?? '') == $s->id)>{{ $s->displayName() }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <x-button type="submit" size="sm">اعمال فیلتر</x-button>
        <x-button :href="route('admin.kanban')" variant="secondary" size="sm">پاک کردن</x-button>
    </form>

    @if (auth()->user()->isAdmin())
        <form method="POST" action="{{ route('admin.pipeline-stages.store') }}" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-ink-200/70 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            @csrf
            <div class="flex min-w-64 flex-1 flex-col gap-1.5">
                <label for="pipeline-stage-name" class="text-xs font-bold text-ink-500 dark:text-ink-400">افزودن ستون پایپ‌لاین</label>
                <input id="pipeline-stage-name" type="text" name="name" maxlength="100" required placeholder="مثلاً: در انتظار مدارک" class="rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
            </div>
            <x-button type="submit" size="sm">افزودن ستون</x-button>
        </form>
        @error('stage')
            <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $message }}</div>
        @enderror
    @endif

    <div
        x-data="kanbanBoard"
        class="flex gap-4 overflow-x-auto pb-4"
    >
        @foreach ($stages as $stage)
            @php $leads = $leadsByStage[$stage->id]; @endphp
            <div
                class="w-72 shrink-0 rounded-2xl border border-ink-200/70 bg-ink-50/60 p-3 dark:border-white/10 dark:bg-white/5"
                data-stage-id="{{ $stage->id }}"
                data-stage-slug="{{ $stage->slug }}"
                data-stage-name="{{ $stage->name }}"
                @dragover.prevent="dragOver($event)"
                @dragleave="dragLeave($event)"
                @drop.prevent="drop($event)"
            >
                <div class="mb-2.5 flex items-center justify-between border-b-2 border-ink-200/70 px-1 pb-2.5 dark:border-white/10">
                    <div class="flex items-center gap-1.5">
                        <h3 class="text-sm font-extrabold text-brand-900 dark:text-white">{{ $stage->name }}</h3>
                        @if (auth()->user()->isAdmin())
                            <button
                                type="button"
                                @click="openStageNameEditor('{{ $stage->id }}', @js($stage->name))"
                                class="rounded p-1 text-xs text-ink-400 hover:bg-brand-100 hover:text-brand-600 dark:hover:bg-brand-500/15"
                                title="ویرایش نام"
                            >
                                ✏️
                            </button>
                            <form method="POST" action="{{ route('admin.pipeline-stages.destroy', $stage) }}" class="inline" @submit="!confirm('ستون «{{ $stage->name }}» حذف شود؟ ستون دارای کارت قابل حذف نیست.') && $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded p-1 text-xs text-ink-400 hover:bg-rose-100 hover:text-rose-600 dark:hover:bg-rose-500/15" title="حذف ستون">🗑️</button>
                            </form>
                        @endif
                    </div>
                    <span class="rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-extrabold text-brand-800 dark:bg-brand-500/15 dark:text-brand-300">{{ $leads->count() }}</span>
                </div>
                <div class="flex min-h-[40px] flex-col gap-2.5">
                    @forelse ($leads as $lead)
                        @php $t = ['hot' => ['داغ', '#DC2626', '#FEE2E2'], 'warm' => ['معمولی', '#D97706', '#FEF3C7'], 'cold' => ['سرد', '#2563EB', '#DBEAFE']][$lead->temperature ?: 'warm']; @endphp
                        <a
                            href="{{ route('admin.requests.show', $lead) }}"
                            draggable="true"
                            data-lead-id="{{ $lead->id }}"
                            @dragstart="dragStart($event)"
                            @dragend="dragEnd($event)"
                            @click="if (dragging) $event.preventDefault()"
                            class="block cursor-grab rounded-xl border border-ink-200/70 bg-white p-3 text-sm shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:shadow-soft active:cursor-grabbing dark:border-white/10 dark:bg-ink-900"
                        >
                            <div class="mb-1.5 flex items-start justify-between gap-1.5">
                                <span class="font-extrabold">{{ $lead->name }}</span>
                                <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-extrabold" style="background: {{ $t[2] }}; color: {{ $t[1] }}">{{ $t[0] }}</span>
                            </div>
                            <div class="num-font text-xs text-ink-500">{{ $lead->phone }}</div>
                            @if ($lead->car_label)
                                <div class="mt-1 truncate text-xs text-ink-500">🚗 {{ $lead->car_label }}</div>
                            @endif
                            <div class="mt-2 flex justify-between text-[11px] text-ink-400">
                                <span>{{ $lead->assignee?->displayName() ?? 'بدون الحاق' }}</span>
                                <span>{{ $lead->created_at->format('m-d') }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="py-4 text-center text-xs text-ink-400">کارتی نیست</div>
                    @endforelse
                </div>
            </div>
        @endforeach

        {{-- Loss-reason modal (replaces window.prompt) --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-ink-950/60 p-4" @keydown.escape.window="cancelMove">
            <div @click.outside="cancelMove" x-show="modalOpen" x-transition class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-soft-lg dark:bg-ink-900">
                <h3 class="mb-1 text-base font-extrabold">دلیل از دست رفتن سرنخ</h3>
                <p class="mb-4 text-xs text-ink-500">برای انتقال به «از دست رفته»، انتخاب دلیل الزامی است.</p>
                <div class="mb-4 flex flex-wrap gap-2">
                    <template x-for="reason in lossReasons" :key="reason">
                        <button type="button" @click="selectedReason = reason"
                            class="rounded-full border px-3 py-1.5 text-xs font-bold"
                            :class="selectedReason === reason ? 'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'border-ink-200 text-ink-600 dark:border-white/10 dark:text-ink-300'"
                            x-text="reason"></button>
                    </template>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="cancelMove" class="rounded-xl px-4 py-2 text-sm font-bold text-ink-500 hover:bg-ink-100 dark:hover:bg-white/10">انصراف</button>
                    <button type="button" @click="confirmMove" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700">ثبت و انتقال</button>
                </div>
            </div>
        </div>

        {{-- Stage name edit modal --}}
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-ink-950/60 p-4" @keydown.escape.window="cancelStageNameEdit">
            <div @click.outside="cancelStageNameEdit" x-show="editModalOpen" x-transition class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-soft-lg dark:bg-ink-900">
                <h3 class="mb-1 text-base font-extrabold">ویرایش نام مرحله</h3>
                <p class="mb-4 text-xs text-ink-500">نام مرحله را وارد کنید:</p>
                <input type="text" x-model="stageNameInput" @keydown.enter="saveStageName" class="mb-4 w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                <div class="flex justify-end gap-2">
                    <button type="button" @click="cancelStageNameEdit" class="rounded-xl px-4 py-2 text-sm font-bold text-ink-500 hover:bg-ink-100 dark:hover:bg-white/10">انصراف</button>
                    <button type="button" @click="saveStageName" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white hover:bg-brand-700">ذخیره</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function kanbanBoard(lossReasons = @json($lossReasons->pluck("reason"))) {
            return {
                dragging: false, draggedEl: null,
                modalOpen: false, selectedReason: '', pendingCol: null,
                editModalOpen: false, editStageId: null, stageNameInput: '',
                lossReasons,
                dragStart(e) { this.dragging = true; this.draggedEl = e.currentTarget; e.currentTarget.classList.add('opacity-40'); },
                dragEnd(e) { e.currentTarget.classList.remove('opacity-40'); setTimeout(() => { this.dragging = false; }, 50); },
                dragOver(e) { e.currentTarget.classList.add('ring-2', 'ring-brand-400'); },
                dragLeave(e) { e.currentTarget.classList.remove('ring-2', 'ring-brand-400'); },
                drop(e) {
                    e.currentTarget.classList.remove('ring-2', 'ring-brand-400');
                    if (!this.draggedEl) return;
                    const col = e.currentTarget;
                    if (col.dataset.stageSlug === 'lost') {
                        this.pendingCol = col;
                        this.selectedReason = '';
                        this.modalOpen = true;
                        return;
                    }
                    this.moveCard(col, '');
                },
                cancelMove() { this.modalOpen = false; this.pendingCol = null; },
                confirmMove() {
                    if (!this.selectedReason) { window.pushToast('یک دلیل انتخاب کنید.', 'error'); return; }
                    this.moveCard(this.pendingCol, this.selectedReason);
                    this.modalOpen = false;
                },
                async moveCard(col, lossReason) {
                    const leadId = this.draggedEl.dataset.leadId;
                    const stageId = col.dataset.stageId;
                    try {
                        const res = await fetch('{{ route('admin.kanban.change-stage') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ leadId, stageId, lossReason }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.pushToast('مرحله سرنخ به‌روزرسانی شد.', 'success');
                            setTimeout(() => location.reload(), 500);
                        } else {
                            window.pushToast(data.message || 'خطا در به‌روزرسانی.', 'error');
                        }
                    } catch (e) { window.pushToast('خطا در ارتباط با سرور.', 'error'); }
                },
                openStageNameEditor(stageId, currentName) {
                    this.editStageId = stageId;
                    this.stageNameInput = currentName;
                    this.editModalOpen = true;
                },
                cancelStageNameEdit() {
                    this.editModalOpen = false;
                    this.editStageId = null;
                    this.stageNameInput = '';
                },
                async saveStageName() {
                    if (!this.stageNameInput.trim()) {
                        window.pushToast('نام مرحله نمی‌تواند خالی باشد.', 'error');
                        return;
                    }
                    try {
                        const res = await fetch(`{{ route('admin.pipeline-stages.update-name', ['stage' => 'STAGE_ID']) }}`.replace('STAGE_ID', this.editStageId), {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ name: this.stageNameInput.trim() }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.pushToast('نام مرحله به‌روزرسانی شد.', 'success');
                            setTimeout(() => location.reload(), 500);
                        } else {
                            window.pushToast(data.message || 'خطا در به‌روزرسانی.', 'error');
                        }
                    } catch (e) { window.pushToast('خطا در ارتباط با سرور.', 'error'); }
                    this.cancelStageNameEdit();
                },
            };
        }
    </script>
</x-layouts.admin>
