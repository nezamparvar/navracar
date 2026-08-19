<x-layouts.admin :page-title="$pageTitle" page-subtitle="خودروهای ارسال‌شده از افزونه را پیش از ساخت پیش‌نویس آگهی بررسی کنید.">
    <x-card variant="v2" class="mb-5">
        <form method="GET" class="grid gap-3 sm:grid-cols-3">
            <select name="status" class="rounded-xl border border-v2-border bg-v2-elevated text-v2-text"><option value="">همه وضعیت‌ها</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select>
            <select name="source" class="rounded-xl border border-v2-border bg-v2-elevated text-v2-text"><option value="">همه منابع</option>@foreach(['dubizzle','dubicars','yallamotor'] as $source)<option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ $source }}</option>@endforeach</select>
            <x-button type="submit" variant="v2-primary">اعمال فیلتر</x-button>
        </form>
    </x-card>
    <x-card variant="v2" title="صف بررسی" icon="car">
        <div class="grid gap-3">
            @forelse($rows as $row)
                @php($vehicle = $row->parsed_json ?? [])
                <a href="{{ route('admin.import-queue.show', $row) }}" class="rounded-xl border border-v2-border p-4 transition hover:border-v2-primary/60">
                    <div class="flex flex-wrap items-center justify-between gap-2"><strong class="text-v2-text">{{ $vehicle['title'] ?? ('مورد #'.$row->id) }}</strong><x-badge :color="$row->status === 'published' ? 'v2-success' : ($row->status === 'cancelled' ? 'v2-neutral' : 'v2-warning')">{{ $row->status }}</x-badge></div>
                    <div class="mt-2 flex flex-wrap gap-4 text-xs text-v2-text-muted"><span>{{ $row->source }}</span><span>{{ number_format((float)($vehicle['price_aed'] ?? 0)) }} درهم</span><span>{{ $row->created_at->format('Y-m-d H:i') }}</span></div>
                </a>
            @empty
                <x-empty-state variant="v2" icon="car" title="موردی در صف بررسی نیست." />
            @endforelse
        </div>
        <div class="mt-4">{{ $rows->links() }}</div>
    </x-card>
</x-layouts.admin>
