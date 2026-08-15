@extends('layouts.admin')

@section('title', 'بررسی Import')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.import-queue.index') }}" class="text-blue-600 hover:text-blue-800">← بازگشت</a>
    </div>

    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold mb-2">
                    {{ $item->captured_data['vehicle']['title'] ?? $item->captured_data['vehicle']['make'] . ' ' . $item->captured_data['vehicle']['model'] }}
                </h1>
                <p class="text-gray-600">
                    منبع: <strong>{{ ucfirst($item->source) }}</strong> |
                    وضعیت: <strong>{{ str_replace('_', ' ', $item->status) }}</strong>
                </p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-green-600">
                    {{ number_format($item->captured_data['vehicle']['price_aed'] ?? 0) }} AED
                </p>
                @if($suggestedCustomsPrice)
                    <p class="text-sm text-gray-600 mt-1">
                        قیمت گمرکی پیشنهادی: <strong>{{ number_format($suggestedCustomsPrice, 0) }}</strong> AED ({{ $customsDiscountPercent }}% تخفیف)
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-4 mb-6 border-b">
        <button class="tab-btn active px-4 py-2 border-b-2 border-blue-600" data-tab="info">
            اطلاعات
        </button>
        <button class="tab-btn px-4 py-2" data-tab="images">
            تصاویر ({{ $item->image_count }})
        </button>
        <button class="tab-btn px-4 py-2" data-tab="diagnostics">
            تشخیص
        </button>
    </div>

    <!-- Info Tab -->
    <div id="info-tab" class="tab-content">
        <div class="bg-white rounded-lg shadow-md p-6">
            @if($item->duplicate_detected_with)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <p class="font-bold text-yellow-800 mb-2">⚠️ احتمال تکرار</p>
                    <p class="text-sm text-yellow-700">یک فهرست مشابه قبلاً وجود دارد:</p>
                    @if($item->duplicatesWith)
                        <p class="text-sm mt-2">
                            <strong>{{ $item->duplicatesWith->title_en }}</strong><br>
                            قیمت: {{ number_format($item->duplicatesWith->price_aed, 0) }} AED |
                            کارکرد: {{ $item->duplicatesWith->kilometers }} کیلومتر
                        </p>
                    @endif
                </div>
            @endif

            <form action="{{ route('admin.import-queue.update', $item) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">عنوان</label>
                        <input type="text" name="vehicle[title]" value="{{ old('vehicle.title', $item->parsed_data['vehicle']['title'] ?? $item->captured_data['vehicle']['title'] ?? '') }}" class="w-full px-4 py-2 border rounded-lg @error('vehicle.title') border-red-500 @enderror" />
                        @error('vehicle.title') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">قیمت AED</label>
                        <input type="number" name="vehicle[price_aed]" value="{{ old('vehicle.price_aed', $item->parsed_data['vehicle']['price_aed'] ?? $item->captured_data['vehicle']['price_aed'] ?? '') }}" class="w-full px-4 py-2 border rounded-lg @error('vehicle.price_aed') border-red-500 @enderror" />
                        @error('vehicle.price_aed') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">برند</label>
                        <input type="text" name="vehicle[make]" value="{{ old('vehicle.make', $item->parsed_data['vehicle']['make'] ?? $item->captured_data['vehicle']['make'] ?? '') }}" class="w-full px-4 py-2 border rounded-lg" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">مدل</label>
                        <input type="text" name="vehicle[model]" value="{{ old('vehicle.model', $item->parsed_data['vehicle']['model'] ?? $item->captured_data['vehicle']['model'] ?? '') }}" class="w-full px-4 py-2 border rounded-lg" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">سال</label>
                        <input type="text" name="vehicle[year]" value="{{ old('vehicle.year', $item->parsed_data['vehicle']['year'] ?? $item->captured_data['vehicle']['year'] ?? '') }}" class="w-full px-4 py-2 border rounded-lg" maxlength="4" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">کارکرد (کیلومتر)</label>
                        <input type="text" name="vehicle[mileage_km]" value="{{ old('vehicle.mileage_km', $item->parsed_data['vehicle']['mileage_km'] ?? $item->captured_data['vehicle']['mileage_km'] ?? '') }}" class="w-full px-4 py-2 border rounded-lg" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">نوع موتور</label>
                        <input type="text" name="vehicle[fuel_type]" value="{{ old('vehicle.fuel_type', $item->parsed_data['vehicle']['fuel_type'] ?? $item->captured_data['vehicle']['fuel_type'] ?? '') }}" class="w-full px-4 py-2 border rounded-lg" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">گیربکس</label>
                        <input type="text" name="vehicle[transmission]" value="{{ old('vehicle.transmission', $item->parsed_data['vehicle']['transmission'] ?? $item->captured_data['vehicle']['transmission'] ?? '') }}" class="w-full px-4 py-2 border rounded-lg" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">توضیحات</label>
                    <textarea name="vehicle[description]" rows="6" class="w-full px-4 py-2 border rounded-lg">{{ old('vehicle.description', $item->parsed_data['vehicle']['description'] ?? $item->captured_data['vehicle']['description'] ?? '') }}</textarea>
                </div>

                <div class="flex gap-3">
                    @if($item->status !== 'published')
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            ذخیره تغییرات
                        </button>
                    @endif

                    @if($item->status === 'needs_review' || $item->status === 'ready')
                        <form action="{{ route('admin.import-queue.publish', $item) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                                انتشار
                            </button>
                        </form>
                    @endif

                    @if($item->status !== 'published')
                        <form action="{{ route('admin.import-queue.cancel', $item) }}" method="POST" class="inline" onsubmit="return confirm('آیا این import را لغو کنید؟')">
                            @csrf
                            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                                لغو
                            </button>
                        </form>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Images Tab -->
    <div id="images-tab" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow-md p-6">
            @if($item->image_count > 0)
                <p class="text-sm text-gray-600 mb-4">
                    {{ $item->images_imported }}/{{ $item->image_count }} تصویر وارد شده است
                </p>

                <div class="grid grid-cols-3 gap-4">
                    @foreach($item->captured_data['images'] ?? [] as $image)
                        <div class="bg-gray-100 rounded-lg overflow-hidden">
                            <img src="{{ $image['url'] ?? $image }}" alt="vehicle" class="w-full h-40 object-cover" onerror="this.src='https://via.placeholder.com/300x200?text=Image'">
                        </div>
                    @endforeach
                </div>

                @if($item->images_imported < $item->image_count)
                    <form action="{{ route('admin.import-queue.retry-images', $item) }}" method="POST" class="mt-6">
                        @csrf
                        <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
                            تلاش دوباره برای دانلود تصاویر
                        </button>
                    </form>
                @endif
            @else
                <p class="text-gray-500 text-center py-12">هیچ تصویری وجود ندارد</p>
            @endif
        </div>
    </div>

    <!-- Diagnostics Tab -->
    <div id="diagnostics-tab" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow-md p-6">
            @if($item->diagnostics)
                <div class="space-y-3">
                    @foreach($item->diagnostics as $field => $diagnostic)
                        <div class="flex items-center gap-4 p-3 bg-gray-50 rounded">
                            <div class="flex-1">
                                <p class="font-medium">{{ $field }}</p>
                                <p class="text-sm text-gray-600">
                                    @if($diagnostic['found'] ?? false)
                                        <span class="text-green-600">✓ پیدا شد</span>
                                    @else
                                        <span class="text-red-600">✗ پیدا نشد</span>
                                    @endif
                                    - اطمینان: <strong>{{ ucfirst($diagnostic['confidence'] ?? 'unknown') }}</strong>
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">اطلاعات تشخیصی موجود نیست</p>
            @endif
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById(tab + '-tab').classList.remove('hidden');
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('border-b-2', 'border-blue-600'));
        btn.classList.add('border-b-2', 'border-blue-600');
    });
});
</script>
@endsection
