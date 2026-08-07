<x-layouts.admin :page-title="$pageTitle" page-subtitle="این قالب‌ها در صفحه جزئیات هر سرنخ، آماده کپی برای کارشناسان فروش نمایش داده می‌شوند.">

    <x-card title="افزودن قالب جدید" icon="plus" class="mb-5">
        <form method="POST" action="{{ route('admin.templates.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="id" value="0">
            <div class="rounded-xl bg-ink-50 p-3.5 text-xs leading-7 text-ink-500 dark:bg-white/5">
                متغیرهای قابل‌استفاده: <code>@{{customer_name}}</code> <code>@{{phone}}</code> <code>@{{car_model}}</code>
                <code>@{{total_price}}</code> <code>@{{salesperson_name}}</code> <code>@{{official_channels}}</code> <code>@{{company_name}}</code>
            </div>
            <input type="text" name="title" placeholder="عنوان قالب" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
            <select name="category" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                @foreach ($categories as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
            <textarea name="body" rows="4" placeholder="متن پیام..." required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></textarea>
            <x-button type="submit" variant="amber">ثبت قالب</x-button>
        </form>
    </x-card>

    <x-card title="قالب‌های موجود ({{ $templates->count() }})" icon="message">
        @if ($templates->isEmpty())
            <x-empty-state icon="message" title="هنوز قالبی ثبت نشده." />
        @else
            <div class="space-y-3">
                @foreach ($templates as $t)
                    <div class="rounded-xl border border-ink-200/70 p-4 {{ $t->is_active ? '' : 'opacity-50' }} dark:border-white/10">
                        <div class="mb-1.5 flex items-center justify-between">
                            <span class="font-extrabold">{{ $t->title }}</span>
                            <x-badge>{{ $categories[$t->category] ?? $t->category }}</x-badge>
                        </div>
                        <div class="my-2 whitespace-pre-wrap rounded-lg bg-ink-50 p-3 text-xs text-ink-600 dark:bg-white/5 dark:text-ink-300">{{ $t->body }}</div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.templates.toggle', $t) }}">
                                @csrf
                                <x-button type="submit" size="sm" variant="secondary">{{ $t->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}</x-button>
                            </form>
                            <form method="POST" action="{{ route('admin.templates.destroy', $t) }}" onsubmit="return confirm('حذف این قالب مطمئنید؟');">
                                @csrf @method('DELETE')
                                <x-button type="submit" size="sm" variant="danger">حذف</x-button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-layouts.admin>
