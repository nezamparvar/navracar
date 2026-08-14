<x-layouts.admin :page-title="$pageTitle" page-subtitle="مطالب وبلاگ در صفحه اصلی و /blog سایت نمایش داده می‌شوند.">

    <div class="mb-5">
        <x-button :href="route('admin.posts.create')" variant="amber">
            <x-icon name="plus" class="w-4 h-4" /> نوشتن مطلب جدید
        </x-button>
    </div>

    <x-card title="مطالب ({{ $posts->total() }})" icon="message">
        @if ($posts->isEmpty())
            <x-empty-state icon="message" title="هنوز مطلبی نوشته نشده." />
        @else
            <div class="space-y-3">
                @foreach ($posts as $post)
                    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-ink-200/70 p-3.5 dark:border-white/10">
                        <div class="h-14 w-20 shrink-0 overflow-hidden rounded-lg bg-ink-100 dark:bg-white/5">
                            @if ($post->coverUrl())
                                <img src="{{ $post->coverUrl() }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center text-ink-300"><x-icon name="image" class="w-5 h-5" /></div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="truncate font-extrabold">{{ $post->title }}</span>
                                <x-badge :color="$post->status === 'published' ? 'green' : 'slate'">
                                    {{ $post->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}
                                </x-badge>
                            </div>
                            <div class="mt-1 text-xs text-ink-500 dark:text-ink-400">{{ $post->created_at->format('Y-m-d') }}</div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            @if ($post->status === 'published')
                                <x-button :href="route('public.blog.show', $post)" target="_blank" size="sm" variant="secondary">
                                    <x-icon name="external-link" class="w-4 h-4" /> مشاهده
                                </x-button>
                            @endif
                            <x-button :href="route('admin.posts.edit', $post)" size="sm" variant="primary">
                                <x-icon name="edit" class="w-4 h-4" /> ویرایش
                            </x-button>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $posts->links() }}</div>
        @endif
    </x-card>
</x-layouts.admin>
