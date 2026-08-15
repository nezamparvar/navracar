@extends('layouts.admin')

@section('title', 'صف Import')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">صف Import</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium mb-2">وضعیت</label>
                <select name="status" class="px-4 py-2 border rounded-lg">
                    <option value="">همه</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @if(request('status') === $status) selected @endif>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">منبع</label>
                <select name="source" class="px-4 py-2 border rounded-lg">
                    <option value="">همه</option>
                    @foreach($sources as $source)
                        <option value="{{ $source }}" @if(request('source') === $source) selected @endif>
                            {{ ucfirst($source) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                فیلتر
            </button>
        </form>
    </div>

    <!-- Queue Items Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        @if($items->count())
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-right">عنوان</th>
                        <th class="px-4 py-3 text-right">منبع</th>
                        <th class="px-4 py-3 text-right">وضعیت</th>
                        <th class="px-4 py-3 text-right">تصاویر</th>
                        <th class="px-4 py-3 text-right">تاریخ</th>
                        <th class="px-4 py-3 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3">
                                {{ $item->captured_data['vehicle']['title'] ?? $item->captured_data['vehicle']['make'] . ' ' . $item->captured_data['vehicle']['model'] ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">
                                    {{ ucfirst($item->source) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded @php
                                    $statusColors = [
                                        'captured' => 'bg-gray-100 text-gray-800',
                                        'parsing' => 'bg-yellow-100 text-yellow-800',
                                        'needs_review' => 'bg-orange-100 text-orange-800',
                                        'images_pending' => 'bg-blue-100 text-blue-800',
                                        'ready' => 'bg-green-100 text-green-800',
                                        'published' => 'bg-purple-100 text-purple-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp {{ $statusColors[$item->status] ?? '' }}">
                                    {{ str_replace('_', ' ', $item->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                {{ $item->images_imported }}/{{ $item->image_count }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $item->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.import-queue.show', $item) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                    مشاهده
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="px-4 py-4 border-t">
                {{ $items->links() }}
            </div>
        @else
            <div class="text-center py-12 text-gray-500">
                <p class="text-lg">هیچ مورد در صف نیست</p>
            </div>
        @endif
    </div>
</div>
@endsection
