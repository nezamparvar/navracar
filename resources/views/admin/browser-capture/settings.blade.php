@extends('layouts.admin')

@section('title', 'تنظیمات Navra Capture')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">تنظیمات Navra Capture</h1>

    <!-- Generate Pairing Code Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-semibold mb-4">ایجاد کد جفت‌سازی</h2>
        <p class="text-gray-600 mb-6">کد جفت‌سازی برای اتصال افزونه مرورگر ایجاد کنید</p>

        <form id="pairing-form" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-2">محیط</label>
                <select name="environment" class="w-full px-4 py-2 border rounded-lg">
                    <option value="staging">Staging (برای آزمایش)</option>
                    <option value="production">Production (برای تولید)</option>
                </select>
            </div>

            <button type="button" onclick="generatePairingCode()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                ایجاد کد
            </button>
        </form>

        <div id="pairing-result" class="mt-6 hidden bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-sm text-gray-600 mb-2">کد جفت‌سازی:</p>
            <p class="text-2xl font-mono font-bold text-green-600 mb-4" id="pairing-code-display"></p>
            <p class="text-sm text-gray-600">این کد برای 60 دقیقه معتبر است. در افزونه مرورگر وارد کنید.</p>
            <button type="button" onclick="copyToClipboard('pairing-code-display')" class="mt-3 bg-gray-200 px-4 py-1 rounded text-sm hover:bg-gray-300">
                کپی کنید
            </button>
        </div>
    </div>

    <!-- Active Pairings Section -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold mb-4">اتصالات فعال</h2>

        @if($activePairings->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-right">دستگاه</th>
                            <th class="px-4 py-2 text-right">محیط</th>
                            <th class="px-4 py-2 text-right">تاریخ اتصال</th>
                            <th class="px-4 py-2 text-right">آخرین استفاده</th>
                            <th class="px-4 py-2 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activePairings as $pairing)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $pairing->device_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded @if($pairing->environment === 'staging') bg-yellow-100 text-yellow-800 @else bg-blue-100 text-blue-800 @endif">
                                        {{ $pairing->environment }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $pairing->paired_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">{{ $pairing->last_used_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <form action="{{ route('admin.browser-capture.revoke', $pairing) }}" method="POST" class="inline" onsubmit="return confirm('آیا این اتصال را لغو کنید؟')">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                            لغو
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-center py-8">هیچ اتصال فعال وجود ندارد</p>
        @endif
    </div>

    <!-- Pending Codes Section -->
    @if($pendingCodes->count())
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h2 class="text-2xl font-semibold mb-4">کدهای در انتظار</h2>
            <div class="space-y-2">
                @foreach($pendingCodes as $code)
                    <div class="bg-gray-50 p-3 rounded flex justify-between items-center">
                        <div>
                            <p class="font-mono text-lg font-bold">{{ $code->pairing_code }}</p>
                            <p class="text-xs text-gray-500">ایجاد شده: {{ $code->created_at->format('H:i') }}</p>
                        </div>
                        <p class="text-xs text-gray-500">انقضا: {{ $code->created_at->addHour()->format('H:i') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
async function generatePairingCode() {
    const environment = document.querySelector('select[name="environment"]').value;

    try {
        const response = await fetch('{{ route("admin.browser-capture.generate-code") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ environment }),
        });

        const data = await response.json();

        if (data.status === 'success') {
            document.getElementById('pairing-code-display').textContent = data.pairing_code;
            document.getElementById('pairing-result').classList.remove('hidden');
        } else {
            alert('خطا: ' + (data.message || 'ناموفق'));
        }
    } catch (error) {
        alert('خطا: ' + error.message);
    }
}

function copyToClipboard(elementId) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert('کپی شد');
    });
}
</script>
@endsection
