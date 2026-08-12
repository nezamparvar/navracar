<x-card title="عنوان و شناسه" icon="car">
    <div class="space-y-3">
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">عنوان فارسی</label>
            <input type="text" name="title_fa" value="{{ old('title_fa', $l->title_fa) }}" required
                   class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">اسلاگ (آدرس صفحه)</label>
            <input type="text" name="slug" value="{{ old('slug', $l->slug) }}" @if($l->exists) required @endif dir="ltr"
                   placeholder="{{ $l->exists ? '' : 'خالی بگذارید تا خودکار ساخته شود' }}"
                   class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm text-left dark:border-white/10 dark:bg-white/5">
            @if($l->exists)
                <p class="mt-1 text-[11px] text-ink-400">{{ url('/car-prices/'.$l->slug) }}</p>
            @endif
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-bold text-ink-500">مارک</label>
                <input type="text" name="make" value="{{ old('make', $l->make) }}" dir="ltr" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3 py-2 text-sm text-left dark:border-white/10 dark:bg-white/5">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-ink-500">مدل</label>
                <input type="text" name="model" value="{{ old('model', $l->model) }}" dir="ltr" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3 py-2 text-sm text-left dark:border-white/10 dark:bg-white/5">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-ink-500">تیریم</label>
                <input type="text" name="trim_level" value="{{ old('trim_level', $l->trim_level) }}" dir="ltr" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3 py-2 text-sm text-left dark:border-white/10 dark:bg-white/5">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-ink-500">سال ساخت</label>
                <input type="text" name="model_year" value="{{ old('model_year', $l->model_year) }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
            </div>
        </div>
    </div>
</x-card>

<x-card title="قیمت و دسته‌بندی خودرو" icon="target"
        subtitle="دسته‌بندی مستقیم روی درصد عوارض گمرکی بر اساس تعرفه در جدول محاسبات اثر می‌گذارد — حتماً بررسی کنید.">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">قیمت (درهم امارات)</label>
            <input type="number" step="1" name="price_aed" value="{{ old('price_aed', (float) $l->price_aed) }}" required
                   class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">دسته‌بندی خودرو</label>
            <select name="category_id" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                @foreach ($categories as $key => $cat)
                    <option value="{{ $key }}" @selected(old('category_id', $l->category_id) === $key)>
                        {{ $cat['label'] }} (تعرفه {{ rtrim(rtrim(number_format($cat['coef'] * 100, 2), '0'), '.') }}٪)
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-[11px] text-ink-400">درصدها از <a href="{{ route('admin.settings.edit') }}" class="text-brand-600 hover:underline">تنظیمات نرخ‌ها</a> خوانده می‌شوند.</p>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">مدت زمان تحویل (روز کاری)</label>
            <input type="number" step="1" min="1" name="delivery_days" value="{{ old('delivery_days', $l->delivery_days) }}" required
                   class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
        </div>
    </div>
</x-card>

<x-card title="مشخصات فنی" icon="list">
    @php
        $specFields = [
            'kilometers' => 'کارکرد', 'body_type' => 'نوع بدنه', 'fuel_type' => 'نوع سوخت',
            'transmission_type' => 'نوع گیربکس', 'regional_specs' => 'اسپک منطقه‌ای',
            'steering_side' => 'فرمان', 'seller_type' => 'نوع فروشنده', 'warranty' => 'گارانتی',
            'exterior_color' => 'رنگ بدنه', 'interior_color' => 'رنگ داخل',
            'horsepower' => 'قدرت موتور', 'engine_capacity_cc' => 'حجم موتور',
            'no_of_cylinders' => 'تعداد سیلندر', 'doors' => 'تعداد درب',
            'seating_capacity' => 'ظرفیت صندلی', 'location_text' => 'موقعیت مکانی',
        ];
    @endphp
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @foreach ($specFields as $field => $label)
            <div>
                <label class="mb-1 block text-xs font-bold text-ink-500">{{ $label }}</label>
                <input type="text" name="{{ $field }}" value="{{ old($field, $l->$field) }}"
                       class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
            </div>
        @endforeach
    </div>
</x-card>

<x-card title="سئو" icon="globe">
    <div class="space-y-3">
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">متا-تایتل</label>
            <input type="text" name="meta_title" value="{{ old('meta_title', $l->meta_title) }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">متا-دیسکریپشن</label>
            <textarea name="meta_description" rows="2" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">{{ old('meta_description', $l->meta_description) }}</textarea>
        </div>
    </div>
</x-card>
