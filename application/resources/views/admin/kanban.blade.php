<x-layouts.admin :page-title="$pageTitle" page-subtitle="کارت‌ها را بکشید و بین مراحل جابه‌جا کنید — تغییر بلافاصله ذخیره می‌شود.">

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-bold text-v2-text-muted">جستجو (نام/تلفن)</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-bold text-v2-text-muted">دما</label>
            <select name="temp" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
                <option value="">همه</option>
                <option value="hot" @selected(($filters['temp'] ?? '') === 'hot')>داغ 🔴</option>
                <option value="warm" @selected(($filters['temp'] ?? '') === 'warm')>معمولی 🟠</option>
                <option value="cold" @selected(($filters['temp'] ?? '') === 'cold')>سرد 🔵</option>
            </select>
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-bold text-v2-text-muted">منبع</label>
            <select name="source" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
                <option value="">همه</option>
                @foreach ($sources as $s)
                    <option value="{{ $s }}" @selected(($filters['source'] ?? '') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        @if (auth()->user()->isAdmin())
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-v2-text-muted">کارشناس</label>
                <select name="sales" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
                    <option value="all">همه</option>
                    @foreach ($staffList as $s)
                        <option value="{{ $s->id }}" @selected(($filters['sales'] ?? '') == $s->id)>{{ $s->displayName() }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <x-button type="submit" variant="v2-primary" size="sm">اعمال فیلتر</x-button>
        <x-button :href="route('admin.kanban')" variant="v2-secondary" size="sm">پاک کردن</x-button>
    </form>

    @if (auth()->user()->isAdmin())
        <form method="POST" action="{{ route('admin.pipeline-stages.store') }}" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-v2-border bg-v2-surface p-4">
            @csrf
            <div class="flex min-w-64 flex-1 flex-col gap-1.5">
                <label for="pipeline-stage-name" class="text-xs font-bold text-v2-text-muted">افزودن ستون پایپ‌لاین</label>
                <input id="pipeline-stage-name" type="text" name="name" maxlength="100" required placeholder="مثلاً: در انتظار مدارک" class="rounded-lg border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text">
            </div>
            <x-button type="submit" variant="v2-primary" size="sm">افزودن ستون</x-button>
        </form>
        @error('stage')
            <div class="mb-5 rounded-xl bg-v2-error/15 px-4 py-3 text-sm font-bold text-v2-error">{{ $message }}</div>
        @enderror
    @endif

    <div x-data="kanbanBoard" x-init="initScrollTracking($refs.strip, {{ count($stages) }})">
        <div
            x-ref="strip"
            @scroll.passive="onStripScroll($refs.strip)"
            class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-4"
        >
        @foreach ($stages as $stage)
            @php $leads = $leadsByStage[$stage->id]; @endphp
            <div
                class="w-[calc(100vw-2rem)] shrink-0 snap-center snap-always rounded-2xl border border-v2-border bg-v2-surface p-3 sm:w-72"
                data-stage-id="{{ $stage->id }}"
                data-stage-slug="{{ $stage->slug }}"
                data-stage-name="{{ $stage->name }}"
                @dragover.prevent="dragOver($event)"
                @dragleave="dragLeave($event)"
                @drop.prevent="drop($event)"
            >
                <div class="mb-2.5 flex items-center justify-between border-b-2 border-v2-border px-1 pb-2.5">
                    <div class="flex items-center gap-1.5">
                        <h3 class="text-sm font-extrabold text-v2-text">{{ $stage->name }}</h3>
                        @if (auth()->user()->isAdmin())
                            <button
                                type="button"
                                @click="openStageNameEditor('{{ $stage->id }}', @js($stage->name))"
                                class="rounded p-1 text-xs text-v2-text-muted hover:bg-v2-primary/15 hover:text-v2-primary"
                                title="ویرایش نام"
                            >
                                ✏️
                            </button>
                            <form method="POST" action="{{ route('admin.pipeline-stages.destroy', $stage) }}" class="inline" @submit="!confirm('ستون «{{ $stage->name }}» حذف شود؟ ستون دارای کارت قابل حذف نیست.') && $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded p-1 text-xs text-v2-text-muted hover:bg-v2-error/15 hover:text-v2-error" title="حذف ستون">🗑️</button>
                            </form>
                        @endif
                    </div>
                    <span class="rounded-full bg-v2-primary/15 px-2.5 py-0.5 text-xs font-extrabold text-v2-primary">{{ $leads->count() }}</span>
                </div>
                <div class="flex min-h-[40px] flex-col gap-2.5">
                    @forelse ($leads as $lead)
                        @php $t = ['hot' => ['داغ', '#EF4444', 'rgba(239,68,68,.15)'], 'warm' => ['معمولی', '#EAB308', 'rgba(234,179,8,.15)'], 'cold' => ['سرد', '#20C7E9', 'rgba(32,199,233,.15)']][$lead->temperature ?: 'warm']; @endphp
                        <a
                            href="{{ route('admin.requests.show', $lead) }}"
                            draggable="true"
                            data-lead-id="{{ $lead->id }}"
                            @dragstart="dragStart($event)"
                            @dragend="dragEnd($event)"
                            @click="if (dragging) $event.preventDefault()"
                            class="block cursor-grab rounded-xl border border-v2-border bg-v2-elevated p-3 text-sm text-v2-text shadow-soft-dark transition-all duration-150 hover:-translate-y-0.5 active:cursor-grabbing"
                        >
                            <div class="mb-1.5 flex items-start justify-between gap-1.5">
                                <span class="font-extrabold">{{ $lead->name }}</span>
                                <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-extrabold" style="background: {{ $t[2] }}; color: {{ $t[1] }}">{{ $t[0] }}</span>
                            </div>
                            <div class="num-font text-xs text-v2-text-muted">{{ $lead->phone }}</div>
                            @if ($lead->car_label)
                                <div class="mt-1 truncate text-xs text-v2-text-muted">🚗 {{ $lead->car_label }}</div>
                            @endif
                            <div class="mt-2 flex justify-between text-[11px] text-v2-text-muted">
                                <span>{{ $lead->assignee?->displayName() ?? 'بدون الحاق' }}</span>
                                <span>{{ $lead->created_at->format('m-d') }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="py-4 text-center text-xs text-v2-text-muted">کارتی نیست</div>
                    @endforelse
                </div>
            </div>
        @endforeach
        </div>

        {{--
            Mobile swipe affordance: dot indicators + a hint on first load, so "move between
            columns" is discoverable rather than only guessable from the small edge-peek.
        --}}
        <div class="mt-1 flex items-center justify-center gap-1.5 sm:hidden">
            @foreach ($stages as $i => $stage)
                <span class="h-1.5 rounded-full transition-all" :class="activeIndex === {{ $i }} ? 'w-5 bg-v2-primary' : 'w-1.5 bg-v2-border'"></span>
            @endforeach
        </div>
        <p class="mt-1 text-center text-[11px] text-v2-text-muted sm:hidden">
            <x-icon name="chevron-left" class="inline w-3 h-3" /> برای دیدن مراحل دیگر پایپ‌لاین بکشید
        </p>

        {{-- Loss-reason modal (replaces window.prompt) --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-black/60 p-4" @keydown.escape.window="cancelMove">
            <div @click.outside="cancelMove" x-show="modalOpen" x-transition class="w-full max-w-sm rounded-2xl border border-v2-border bg-v2-surface p-6 shadow-soft-lg">
                <h3 class="mb-1 text-base font-extrabold text-v2-text">دلیل از دست رفتن سرنخ</h3>
                <p class="mb-4 text-xs text-v2-text-muted">برای انتقال به «از دست رفته»، انتخاب دلیل الزامی است.</p>
                <div class="mb-4 flex flex-wrap gap-2">
                    <template x-for="reason in lossReasons" :key="reason">
                        <button type="button" @click="selectedReason = reason"
                            class="rounded-full border px-3 py-1.5 text-xs font-bold"
                            :class="selectedReason === reason ? 'border-v2-primary bg-v2-primary/15 text-v2-primary' : 'border-v2-border text-v2-text-muted'"
                            x-text="reason"></button>
                    </template>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="cancelMove" class="rounded-xl px-4 py-2 text-sm font-bold text-v2-text-muted hover:bg-v2-elevated">انصراف</button>
                    <button type="button" @click="confirmMove" class="rounded-xl bg-v2-error px-4 py-2 text-sm font-bold text-white hover:brightness-110">ثبت و انتقال</button>
                </div>
            </div>
        </div>

        {{-- Stage name edit modal --}}
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-black/60 p-4" @keydown.escape.window="cancelStageNameEdit">
            <div @click.outside="cancelStageNameEdit" x-show="editModalOpen" x-transition class="w-full max-w-sm rounded-2xl border border-v2-border bg-v2-surface p-6 shadow-soft-lg">
                <h3 class="mb-1 text-base font-extrabold text-v2-text">ویرایش نام مرحله</h3>
                <p class="mb-4 text-xs text-v2-text-muted">نام مرحله را وارد کنید:</p>
                <input type="text" x-model="stageNameInput" @keydown.enter="saveStageName" class="mb-4 w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">
                <div class="flex justify-end gap-2">
                    <button type="button" @click="cancelStageNameEdit" class="rounded-xl px-4 py-2 text-sm font-bold text-v2-text-muted hover:bg-v2-elevated">انصراف</button>
                    <button type="button" @click="saveStageName" class="rounded-xl bg-v2-primary-action px-4 py-2 text-sm font-bold text-white hover:brightness-110">ذخیره</button>
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
                activeIndex: 0, stripEl: null,
                lossReasons,
                initScrollTracking(stripEl) {
                    this.stripEl = stripEl;
                },
                onStripScroll(stripEl) {
                    if (!stripEl || !stripEl.firstElementChild) return;
                    const colWidth = stripEl.firstElementChild.offsetWidth + 16;
                    // RTL scrollLeft sign convention differs by browser engine — use the
                    // absolute value so the index calculation works either way.
                    this.activeIndex = Math.round(Math.abs(stripEl.scrollLeft) / colWidth);
                },
                dragStart(e) { this.dragging = true; this.draggedEl = e.currentTarget; e.currentTarget.classList.add('opacity-40'); },
                dragEnd(e) { e.currentTarget.classList.remove('opacity-40'); setTimeout(() => { this.dragging = false; }, 50); },
                dragOver(e) { e.currentTarget.classList.add('ring-2', 'ring-v2-primary'); },
                dragLeave(e) { e.currentTarget.classList.remove('ring-2', 'ring-v2-primary'); },
                drop(e) {
                    e.currentTarget.classList.remove('ring-2', 'ring-v2-primary');
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
