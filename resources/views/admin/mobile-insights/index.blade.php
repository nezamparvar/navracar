<x-layouts.admin :page-title="$pageTitle" page-subtitle="آمار رضایت‌محور نسخه Android در ۳۰ روز اخیر">
    <div class="space-y-6" x-data="adminMobileInsights" data-online-now="{{ $summary['online_now'] }}" data-summary-url="{{ route('admin.mobile-insights.summary') }}">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                ['آنلاین اکنون', $summary['online_now'], 'online-now'],
                ['نصب‌ها', $summary['total_installations'], null],
                ['کاربران فعال', $summary['active_installations'], null],
                ['رضایت آمار', $summary['analytics_opt_in'], null],
                ['رضایت اعلان', $summary['push_opt_in'], null],
                ['رویدادها', $summary['event_count'], null],
            ] as [$label, $value, $id])
                <x-card>
                    <div class="text-xs font-bold text-ink-500 dark:text-ink-400">{{ $label }}</div>
                    <div @if($id) id="{{ $id }}" x-text="onlineNow" @endif class="num-font mt-2 text-3xl font-black text-brand-700 dark:text-brand-300">{{ number_format($value) }}</div>
                </x-card>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            @php
                $rankings = [
                    ['جستجوهای پرتکرار', $summary['top_searches']],
                    ['مدل گوشی', $summary['top_devices']],
                    ['موقعیت تقریبی', $summary['top_locations']],
                    ['منبع نصب/ورود', $summary['top_sources']],
                    ['تعامل با تماس', $summary['contact_actions']],
                    ['رویدادهای پرتکرار', $summary['top_events']],
                ];
            @endphp
            @foreach ($rankings as [$title, $rows])
                <x-card>
                    <h2 class="mb-4 text-base font-black">{{ $title }}</h2>
                    @forelse ($rows as $row)
                        <div class="flex items-center justify-between border-b border-ink-100 py-2.5 last:border-0 dark:border-white/5">
                            <span class="text-sm">{{ $row['label'] }}</span>
                            <span class="num-font rounded-full bg-brand-100 px-2.5 py-1 text-xs font-black text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">{{ number_format($row['count']) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">هنوز داده‌ای با رضایت کاربر ثبت نشده است.</p>
                    @endforelse
                </x-card>
            @endforeach
        </div>

        <x-card>
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black">ارسال Push Notification</h2>
                    <p class="mt-1 text-sm text-ink-500">فقط برای نصب‌هایی که اعلان را فعال کرده‌اند.</p>
                </div>
                <x-badge>{{ $pushConfigured ? 'FCM فعال' : 'FCM نیازمند تنظیم استیج' }}</x-badge>
            </div>
            <form method="POST" action="{{ route('admin.mobile-insights.push.store') }}" class="grid gap-4 lg:grid-cols-2">
                @csrf
                <input type="hidden" name="target" value="all">
                <label class="space-y-1.5 text-sm font-bold">عنوان
                    <input name="title" required maxlength="120" class="mt-1 w-full rounded-xl border border-ink-200 bg-ink-50 px-3 py-2.5 dark:border-white/10 dark:bg-white/5">
                </label>
                <label class="space-y-1.5 text-sm font-bold">لینک امن داخل اپ (اختیاری)
                    <input name="url" placeholder="/vehicles" class="mt-1 w-full rounded-xl border border-ink-200 bg-ink-50 px-3 py-2.5 text-left dark:border-white/10 dark:bg-white/5" dir="ltr">
                </label>
                <label class="space-y-1.5 text-sm font-bold lg:col-span-2">متن اعلان
                    <textarea name="body" required maxlength="1000" rows="3" class="mt-1 w-full rounded-xl border border-ink-200 bg-ink-50 px-3 py-2.5 dark:border-white/10 dark:bg-white/5"></textarea>
                </label>
                <div class="lg:col-span-2"><x-button type="submit">ثبت و ارسال به کاربران رضایت‌داده</x-button></div>
            </form>
        </x-card>

        <x-card>
            <h2 class="mb-4 text-lg font-black">تاریخچه اعلان‌ها</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b text-xs text-ink-500"><th class="p-2 text-start">عنوان</th><th class="p-2 text-start">وضعیت</th><th class="p-2 text-start">هدف</th><th class="p-2 text-start">ارسال</th><th class="p-2 text-start">بازشده</th><th class="p-2 text-start">تاریخ</th></tr></thead>
                    <tbody>
                    @forelse($notifications as $notification)
                        <tr class="border-b border-ink-100 dark:border-white/5"><td class="p-2 font-bold">{{ $notification->title }}</td><td class="p-2">{{ $notification->status }}</td><td class="num-font p-2">{{ number_format($notification->targeted_count) }}</td><td class="num-font p-2">{{ number_format($notification->sent_count) }}</td><td class="num-font p-2">{{ number_format($notification->opened_count) }}</td><td class="p-2 text-xs">{{ $notification->created_at->format('Y-m-d H:i') }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-ink-500">هنوز اعلانی ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <p class="text-xs leading-6 text-ink-500">موقعیت فقط در سطح تقریبی کشور/شهر از IP و بدون ذخیره IP در جداول جدید ثبت می‌شود. شناسه سخت‌افزاری، GPS، مخاطبین یا محتوای شخصی جمع‌آوری نمی‌شود.</p>
    </div>

</x-layouts.admin>
