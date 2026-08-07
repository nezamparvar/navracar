@php($l = $listing)

<x-layouts.admin :page-title="$pageTitle" :page-subtitle="'منبع: '.$l->source_url">

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <x-badge :color="$l->status === 'published' ? 'green' : 'slate'">
            {{ $l->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}
        </x-badge>

        @if ($l->status === 'published')
            <x-button :href="route('public.car-prices.show', $l)" target="_blank" size="sm" variant="secondary">
                <x-icon name="external-link" class="w-4 h-4" /> مشاهده در سایت
            </x-button>
            <form method="POST" action="{{ route('admin.car-listings.unpublish', $l) }}">
                @csrf
                <x-button type="submit" size="sm" variant="secondary">
                    <x-icon name="x-circle" class="w-4 h-4" /> لغو انتشار
                </x-button>
            </form>
        @else
            <x-button :href="route('public.car-prices.show', $l)" target="_blank" size="sm" variant="secondary">
                <x-icon name="eye" class="w-4 h-4" /> پیش‌نمایش
            </x-button>
            <form method="POST" action="{{ route('admin.car-listings.publish', $l) }}">
                @csrf
                <x-button type="submit" size="sm" variant="amber">
                    <x-icon name="check-circle" class="w-4 h-4" /> انتشار در سایت
                </x-button>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.car-listings.refetch', $l) }}"
              onsubmit="return confirm('اطلاعات و عکس‌ها دوباره از دابیزل خوانده می‌شود و مقادیر فعلی جای‌گزین می‌شوند. مطمئنید؟');">
            @csrf
            <x-button type="submit" size="sm" variant="secondary">
                <x-icon name="refresh" class="w-4 h-4" /> بازخوانی از دابیزل
            </x-button>
        </form>

        <form method="POST" action="{{ route('admin.car-listings.destroy', $l) }}"
              onsubmit="return confirm('حذف این آگهی مطمئنید؟ عکس‌ها هم حذف می‌شوند.');" class="ms-auto">
            @csrf @method('DELETE')
            <x-button type="submit" size="sm" variant="danger">
                <x-icon name="trash" class="w-4 h-4" /> حذف آگهی
            </x-button>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-5">
            <form method="POST" action="{{ route('admin.car-listings.update', $l) }}" class="space-y-5">
                @csrf @method('PUT')

                <x-card title="عنوان و شناسه" icon="car">
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-bold text-ink-500">عنوان فارسی</label>
                            <input type="text" name="title_fa" value="{{ old('title_fa', $l->title_fa) }}" required
                                   class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-ink-500">اسلاگ (آدرس صفحه)</label>
                            <input type="text" name="slug" value="{{ old('slug', $l->slug) }}" required dir="ltr"
                                   class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm text-left dark:border-white/10 dark:bg-white/5">
                            <p class="mt-1 text-[11px] text-ink-400">{{ url('/car-prices/'.$l->slug) }}</p>
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
                        subtitle="دسته‌بندی مستقیم روی درصد سود بازرگانی در جدول محاسبات اثر می‌گذارد — حتماً بررسی کنید.">
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
                                        {{ $cat['label'] }} (سود {{ $cat['coef'] * 100 }}٪)
                                    </option>
                                @endforeach
                            </select>
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

                <x-card title="عکس‌ها" icon="image">
                    @if ($l->images->isEmpty())
                        <x-empty-state icon="image" title="عکسی دریافت نشد — از فرم زیر عکس اضافه کنید." />
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                            @foreach ($l->images as $img)
                                <div class="overflow-hidden rounded-xl border border-ink-200/70 dark:border-white/10">
                                    <img src="{{ $img->url() }}" alt="" class="h-28 w-full object-cover">
                                    <div class="space-y-1.5 p-2">
                                        <label class="flex items-center gap-1.5 text-[11px] font-bold text-ink-500">
                                            <input type="radio" name="cover_image_id" value="{{ $img->id }}" @checked($img->is_cover)>
                                            عکس کاور
                                        </label>
                                        <input type="number" name="sort_order[{{ $img->id }}]" value="{{ $img->sort_order }}"
                                               class="w-full rounded-lg border border-ink-200 bg-ink-50 px-2 py-1 text-[11px] dark:border-white/10 dark:bg-white/5">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <x-button type="submit" variant="amber" size="lg">
                    <x-icon name="check" class="w-4 h-4" /> ذخیره تغییرات
                </x-button>
            </form>

            <x-card title="افزودن عکس دستی" icon="upload">
                <form method="POST" action="{{ route('admin.car-listings.images.store', $l) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="min-w-[220px] flex-1">
                        <label class="mb-1 block text-xs font-bold text-ink-500">لینک مستقیم عکس</label>
                        <input type="url" name="image_url" placeholder="https://..." dir="ltr" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3 py-2 text-sm text-left dark:border-white/10 dark:bg-white/5">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-ink-500">یا آپلود فایل</label>
                        <input type="file" name="image_file" accept="image/*" class="text-xs">
                    </div>
                    <x-button type="submit" size="sm" variant="secondary">
                        <x-icon name="plus" class="w-4 h-4" /> افزودن
                    </x-button>
                </form>
            </x-card>
        </div>

        <div class="space-y-5">
            @if ($l->images->isNotEmpty())
                <x-card title="حذف عکس" icon="trash">
                    <div class="space-y-2">
                        @foreach ($l->images as $img)
                            <div class="flex items-center gap-2">
                                <img src="{{ $img->url() }}" class="h-10 w-14 shrink-0 rounded-lg object-cover">
                                <form method="POST" action="{{ route('admin.car-listings.images.destroy', [$l, $img]) }}"
                                      onsubmit="return confirm('حذف این عکس؟');">
                                    @csrf @method('DELETE')
                                    <x-button type="submit" size="sm" variant="danger">حذف</x-button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <x-card title="توضیحات اصلی (انگلیسی، فقط مرجع)" icon="info">
                <p class="max-h-72 overflow-y-auto whitespace-pre-wrap text-xs leading-6 text-ink-500 dark:text-ink-400" dir="ltr">
                    {{ $l->description_en }}
                </p>
            </x-card>
        </div>
    </div>
</x-layouts.admin>
