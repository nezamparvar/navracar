<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$item->source_url">
    @if(!empty($payload['duplicate_queue_item_id']))
        <div class="mb-4 rounded-xl border border-v2-warning/30 bg-v2-warning/15 p-4 text-v2-warning">احتمال تکرار با مورد #{{ $payload['duplicate_queue_item_id'] }}</div>
    @endif
    <div class="grid gap-5 lg:grid-cols-[2fr_1fr]">
        <x-card variant="v2" title="اطلاعات خودرو" icon="edit">
            <form method="POST" action="{{ route('admin.import-queue.update', $item) }}" class="grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                @foreach(['title'=>'عنوان','make'=>'برند','model'=>'مدل','year'=>'سال','price_aed'=>'قیمت درهم','mileage_km'=>'کارکرد','fuel_type'=>'سوخت','engine_capacity_cc'=>'حجم موتور','transmission'=>'گیربکس','body_type'=>'بدنه','color'=>'رنگ'] as $key => $label)
                    <label class="text-sm text-v2-text-muted">{{ $label }}<input name="vehicle[{{ $key }}]" value="{{ old('vehicle.'.$key, $vehicle[$key] ?? '') }}" class="mt-1 w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text" @if(in_array($key,['title','price_aed'])) required @endif></label>
                @endforeach
                <label class="text-sm text-v2-text-muted sm:col-span-2">توضیحات<textarea name="vehicle[description]" rows="5" class="mt-1 w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">{{ old('vehicle.description', $vehicle['description'] ?? '') }}</textarea></label>
                <div class="sm:col-span-2"><x-button type="submit" variant="v2-primary">ذخیره بررسی</x-button></div>
            </form>
        </x-card>
        <div class="space-y-5">
            <x-card variant="v2" title="وضعیت" icon="info">
                <dl class="space-y-2 text-sm text-v2-text"><div class="flex justify-between"><dt class="text-v2-text-muted">منبع</dt><dd>{{ $item->source }}</dd></div><div class="flex justify-between"><dt class="text-v2-text-muted">وضعیت</dt><dd>{{ $item->status }}</dd></div><div class="flex justify-between"><dt class="text-v2-text-muted">تصاویر</dt><dd>{{ $item->images_imported }} / {{ count($payload['images'] ?? []) }}</dd></div></dl>
                <div class="mt-4 grid gap-2">
                    @if(in_array($item->status, ['needs_review','ready']))<form method="POST" action="{{ route('admin.import-queue.publish', $item) }}">@csrf<x-button type="submit" variant="v2-primary">ساخت پیش‌نویس آگهی</x-button></form>@endif
                    @if($item->status !== 'published')<form method="POST" action="{{ route('admin.import-queue.cancel', $item) }}">@csrf<x-button type="submit" variant="v2-danger">لغو ایمپورت</x-button></form>@endif
                    @if($item->publishedListing)<x-button :href="route('admin.car-listings.edit', $item->publishedListing)" variant="v2-secondary">مشاهده پیش‌نویس</x-button>@endif
                </div>
            </x-card>
            <x-card variant="v2" title="عیب‌یابی" icon="info"><pre class="max-h-80 overflow-auto whitespace-pre-wrap text-xs text-v2-text-muted" dir="ltr">{{ json_encode($payload['diagnostics'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></x-card>
        </div>
    </div>
</x-layouts.admin>
