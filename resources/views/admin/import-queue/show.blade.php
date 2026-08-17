<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$item->source_url">
    @if(!empty($payload['duplicate_queue_item_id']))
        <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900">احتمال تکرار با مورد #{{ $payload['duplicate_queue_item_id'] }}</div>
    @endif
    <div class="grid gap-5 lg:grid-cols-[2fr_1fr]">
        <x-card title="اطلاعات خودرو" icon="edit">
            <form method="POST" action="{{ route('admin.import-queue.update', $item) }}" class="grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                @foreach(['title'=>'عنوان','make'=>'برند','model'=>'مدل','year'=>'سال','price_aed'=>'قیمت درهم','mileage_km'=>'کارکرد','fuel_type'=>'سوخت','engine_capacity_cc'=>'حجم موتور','transmission'=>'گیربکس','body_type'=>'بدنه','color'=>'رنگ'] as $key => $label)
                    <label class="text-sm">{{ $label }}<input name="vehicle[{{ $key }}]" value="{{ old('vehicle.'.$key, $vehicle[$key] ?? '') }}" class="mt-1 w-full rounded-xl border-ink-200" @if(in_array($key,['title','price_aed'])) required @endif></label>
                @endforeach
                <label class="text-sm sm:col-span-2">توضیحات<textarea name="vehicle[description]" rows="5" class="mt-1 w-full rounded-xl border-ink-200">{{ old('vehicle.description', $vehicle['description'] ?? '') }}</textarea></label>
                <div class="sm:col-span-2"><x-button type="submit">ذخیره بررسی</x-button></div>
            </form>
        </x-card>
        <div class="space-y-5">
            <x-card title="وضعیت" icon="info">
                <dl class="space-y-2 text-sm"><div class="flex justify-between"><dt>منبع</dt><dd>{{ $item->source }}</dd></div><div class="flex justify-between"><dt>وضعیت</dt><dd>{{ $item->status }}</dd></div><div class="flex justify-between"><dt>تصاویر</dt><dd>{{ $item->images_imported }} / {{ count($payload['images'] ?? []) }}</dd></div></dl>
                <div class="mt-4 grid gap-2">
                    @if(in_array($item->status, ['needs_review','ready']))<form method="POST" action="{{ route('admin.import-queue.publish', $item) }}">@csrf<x-button type="submit" variant="amber">ساخت پیش‌نویس آگهی</x-button></form>@endif
                    @if($item->status !== 'published')<form method="POST" action="{{ route('admin.import-queue.cancel', $item) }}">@csrf<x-button type="submit" variant="danger">لغو ایمپورت</x-button></form>@endif
                    @if($item->publishedListing)<x-button :href="route('admin.car-listings.edit', $item->publishedListing)" variant="secondary">مشاهده پیش‌نویس</x-button>@endif
                </div>
            </x-card>
            <x-card title="عیب‌یابی" icon="info"><pre class="max-h-80 overflow-auto whitespace-pre-wrap text-xs" dir="ltr">{{ json_encode($payload['diagnostics'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></x-card>
        </div>
    </div>
</x-layouts.admin>
