<x-card title="محتوا" icon="message">
    <div class="space-y-3">
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">عنوان</label>
            <input type="text" name="title" value="{{ old('title', $post->title) }}" required
                   class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">خلاصه (در کارت‌ها و پیش‌نمایش نمایش داده می‌شود)</label>
            <textarea name="excerpt" rows="2" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">{{ old('excerpt', $post->excerpt) }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">متن کامل (HTML ساده مجاز است)</label>
            <textarea name="body" rows="14" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 font-mono text-xs dark:border-white/10 dark:bg-white/5">{{ old('body', $post->body) }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">عکس کاور</label>
            @if($post->coverUrl())
                <img src="{{ $post->coverUrl() }}" class="mb-2 h-28 w-44 rounded-lg object-cover">
            @endif
            <input type="file" name="cover_image" accept="image/*" class="text-xs">
        </div>
    </div>
</x-card>

<x-card title="سئو" icon="globe">
    <div class="space-y-3">
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">متا-تایتل</label>
            <input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-ink-500">متا-دیسکریپشن</label>
            <textarea name="meta_description" rows="2" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">{{ old('meta_description', $post->meta_description) }}</textarea>
        </div>
    </div>
</x-card>
