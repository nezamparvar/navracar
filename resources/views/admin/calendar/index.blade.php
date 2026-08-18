<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$pageSubtitle">

    @if (session('success'))
        <div role="status" class="mb-4 rounded-xl border border-v2-success/30 bg-v2-success/10 px-4 py-3 text-sm font-bold text-v2-success">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-xl border border-v2-error/30 bg-v2-error/10 px-4 py-3 text-sm font-bold text-v2-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div x-data="{ createOpen: false }">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 rounded-xl border border-v2-border bg-v2-elevated p-1">
                @foreach (['day' => 'روز', 'week' => 'هفته', 'list' => 'فهرست آینده'] as $v => $label)
                    <a href="{{ route('admin.calendar.index', ['view' => $v, 'date' => $anchor->toDateString()]) }}"
                       class="rounded-lg px-3.5 py-2 text-xs font-bold {{ $view === $v ? 'bg-v2-primary text-white' : 'text-v2-text-muted hover:text-v2-text' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2">
                @if ($view !== 'list')
                    <a href="{{ route('admin.calendar.index', ['view' => $view, 'date' => $anchor->copy()->sub($view === 'day' ? '1 day' : '1 week')->toDateString()]) }}"
                       class="flex h-10 w-10 items-center justify-center rounded-xl border border-v2-border bg-v2-elevated text-v2-text hover:border-v2-primary" aria-label="قبلی">
                        <x-icon name="chevron-down" class="w-4 h-4 rotate-90" />
                    </a>
                    <a href="{{ route('admin.calendar.index', ['view' => $view, 'date' => now()->toDateString()]) }}"
                       class="rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2 text-xs font-bold text-v2-text hover:border-v2-primary">امروز</a>
                    <a href="{{ route('admin.calendar.index', ['view' => $view, 'date' => $anchor->copy()->add($view === 'day' ? '1 day' : '1 week')->toDateString()]) }}"
                       class="flex h-10 w-10 items-center justify-center rounded-xl border border-v2-border bg-v2-elevated text-v2-text hover:border-v2-primary" aria-label="بعدی">
                        <x-icon name="chevron-down" class="w-4 h-4 -rotate-90" />
                    </a>
                @endif
                <x-button variant="v2-primary" @click="createOpen = true">
                    <x-icon name="calendar" class="w-4 h-4" /> جلسه یا تماس جدید
                </x-button>
            </div>
        </div>

        <p class="mb-4 text-xs text-v2-text-muted">
            بازه نمایش‌داده‌شده: <span class="num-font">{{ $rangeStart->translatedFormat('j M Y') }}</span> تا <span class="num-font">{{ $rangeEnd->translatedFormat('j M Y') }}</span> —
            منطقه زمانی: <span dir="ltr" class="num-font">Asia/Tehran</span>
        </p>

        @if ($view === 'list')
            <x-card variant="v2" title="رویدادهای پیش‌رو" icon="calendar">
                @if ($events->isEmpty())
                    <x-empty-state variant="v2" icon="calendar" title="رویدادی در ۱۴ روز آینده ثبت نشده است." />
                @else
                    <div class="divide-y divide-v2-border">
                        @foreach ($events as $event)
                            @include('admin.calendar._event-row', ['event' => $event])
                        @endforeach
                    </div>
                @endif
            </x-card>
        @else
            @php
                $days = [];
                $cursor = $rangeStart->copy();
                while ($cursor->lte($rangeEnd)) {
                    $days[] = $cursor->copy();
                    $cursor->addDay();
                }
                $eventsByDay = $events->groupBy(fn ($e) => $e->starts_at->toDateString());
            @endphp
            <div class="grid gap-3" style="grid-template-columns: repeat({{ count($days) }}, minmax(0, 1fr));">
                @foreach ($days as $day)
                    <div class="rounded-xl border border-v2-border bg-v2-surface p-2.5 {{ $day->isToday() ? 'ring-2 ring-v2-primary' : '' }}">
                        <div class="mb-2 text-center">
                            <div class="text-[11px] font-bold text-v2-text-muted">{{ $day->translatedFormat('D') }}</div>
                            <div class="num-font text-sm font-black text-v2-text">{{ $day->translatedFormat('j') }}</div>
                        </div>
                        <div class="space-y-1.5">
                            @forelse ($eventsByDay->get($day->toDateString(), collect()) as $event)
                                @include('admin.calendar._event-card', ['event' => $event])
                            @empty
                                <div class="rounded-lg py-3 text-center text-[11px] text-v2-text-muted/60">—</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Create modal --}}
        <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none" role="dialog" aria-modal="true" aria-label="رویداد جدید">
            <div class="absolute inset-0 bg-black/60" @click="createOpen = false"></div>
            <form method="POST" action="{{ route('admin.calendar.store') }}" class="relative z-10 w-full max-w-lg rounded-2xl border border-v2-border bg-v2-surface p-5 shadow-soft-dark">
                @csrf
                <h2 class="text-base font-extrabold text-v2-text">ثبت جلسه یا تماس جدید</h2>

                <div class="mt-4 space-y-3">
                    <div>
                        <label for="ce-type" class="mb-1 block text-xs font-bold text-v2-text-muted">نوع رویداد</label>
                        <select id="ce-type" name="type" required class="w-full min-h-[44px] rounded-xl border border-v2-border bg-v2-elevated px-3 text-sm text-v2-text">
                            @foreach ($types as $key => $t)
                                <option value="{{ $key }}">{{ $t['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="ce-request" class="mb-1 block text-xs font-bold text-v2-text-muted">درخواست مرتبط (اختیاری)</label>
                        <select id="ce-request" name="quote_request_id" class="w-full min-h-[44px] rounded-xl border border-v2-border bg-v2-elevated px-3 text-sm text-v2-text">
                            <option value="">— بدون ارتباط —</option>
                            @foreach ($requests as $r)
                                <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->name ?: 'بدون‌نام' }} ({{ $r->car_label }})</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($staffList->count() > 1)
                        <div>
                            <label for="ce-assignee" class="mb-1 block text-xs font-bold text-v2-text-muted">مسئول</label>
                            <select id="ce-assignee" name="assigned_to" required class="w-full min-h-[44px] rounded-xl border border-v2-border bg-v2-elevated px-3 text-sm text-v2-text">
                                @foreach ($staffList as $s)
                                    <option value="{{ $s->id }}">{{ $s->displayName() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="assigned_to" value="{{ $staffList->first()->id }}">
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="ce-start" class="mb-1 block text-xs font-bold text-v2-text-muted">شروع</label>
                            <input id="ce-start" type="datetime-local" name="starts_at" required class="w-full min-h-[44px] rounded-xl border border-v2-border bg-v2-elevated px-3 text-sm text-v2-text num-font">
                        </div>
                        <div>
                            <label for="ce-end" class="mb-1 block text-xs font-bold text-v2-text-muted">پایان</label>
                            <input id="ce-end" type="datetime-local" name="ends_at" required class="w-full min-h-[44px] rounded-xl border border-v2-border bg-v2-elevated px-3 text-sm text-v2-text num-font">
                        </div>
                    </div>

                    <div>
                        <label for="ce-notes" class="mb-1 block text-xs font-bold text-v2-text-muted">یادداشت</label>
                        <textarea id="ce-notes" name="notes" rows="2" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3 py-2 text-sm text-v2-text"></textarea>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <x-button type="button" variant="v2-ghost" @click="createOpen = false">انصراف</x-button>
                    <x-button type="submit" variant="v2-primary">ثبت رویداد</x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
