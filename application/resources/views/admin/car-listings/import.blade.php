<x-layouts.admin :page-title="$pageTitle" page-subtitle="فایل JSON خروجی ابزار کرالر دسکتاپ ناوراکار را اینجا آپلود کنید تا آگهی‌ها به‌صورت گروهی (پیش‌نویس) ساخته شوند.">

    <div class="mx-auto max-w-xl">
        <x-card variant="v2" title="آپلود فایل کرالر" icon="upload">
            <form method="POST" action="{{ route('admin.car-listings.import.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-bold text-v2-text-muted">فایل JSON</label>
                    <input type="file" name="json_file" accept=".json,application/json" required class="text-sm text-v2-text">
                </div>
                <p class="rounded-xl bg-v2-elevated p-3.5 text-[11px] leading-6 text-v2-text-muted">
                    فایل باید یک آرایه JSON از آگهی‌ها باشد (خروجی مستقیم ابزار navracar-crawler). آگهی‌هایی که
                    قبلاً با همان لینک منبع ثبت شده باشند، دوباره ساخته نمی‌شوند (رد می‌شوند). همه آگهی‌های جدید
                    به‌صورت پیش‌نویس ساخته می‌شوند و باید قبل از انتشار در پنل بررسی شوند.
                </p>
                <x-button type="submit" variant="v2-primary">
                    <x-icon name="upload" class="w-4 h-4" /> شروع ایمپورت
                </x-button>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
