<x-card title="محتوا" icon="message" variant="v2">
    <div class="space-y-3">
        <div>
            <label class="mb-1 block text-xs font-bold text-v2-text-muted">عنوان</label>
            <input type="text" name="title" value="{{ old('title', $post->title) }}" required
                   class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-v2-text-muted">خلاصه (در کارت‌ها و پیش‌نمایش نمایش داده می‌شود)</label>
            <textarea name="excerpt" rows="2" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">{{ old('excerpt', $post->excerpt) }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-v2-text-muted">متن کامل (HTML ساده مجاز است)</label>
            <textarea name="body" rows="14" required class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 font-mono text-xs text-v2-text">{{ old('body', $post->body) }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-v2-text-muted">عکس کاور</label>
            @if($post->coverUrl())
                <img src="{{ $post->coverUrl() }}" class="mb-2 h-28 w-44 rounded-lg object-cover">
            @endif
            <input type="file" name="cover_image" accept="image/*" class="text-xs">
        </div>
    </div>
</x-card>

<x-card title="سئو" icon="globe" variant="v2">
    <div class="space-y-3">
        <div>
            <label class="mb-1 block text-xs font-bold text-v2-text-muted">متا-تایتل</label>
            <input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-v2-text-muted">متا-دیسکریپشن</label>
            <textarea name="meta_description" rows="2" class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3.5 py-2.5 text-sm text-v2-text">{{ old('meta_description', $post->meta_description) }}</textarea>
        </div>
    </div>
</x-card>
