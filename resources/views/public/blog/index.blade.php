<x-layouts.public :title="$title">

    @push('head')
        <meta name="description" content="مقالات و راهنمای واردات خودرو، عوارض گمرکی و ترخیص در وبلاگ ناوراکار.">
        <link rel="canonical" href="{{ route('public.blog.index') }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ route('public.blog.index') }}">
        <x-schema-breadcrumbs :items="[
            ['label' => 'ناوراکار', 'url' => route('public.home')],
            ['label' => 'وبلاگ', 'url' => route('public.blog.index')],
        ]" />
    @endpush

    <div class="mx-auto max-w-6xl px-4 py-8">
        <nav class="mb-4 text-xs text-ink-500">
            <a href="{{ route('public.home') }}" class="hover:text-brand-700">ناوراکار</a>
            <span class="mx-1">/</span>
            <span class="font-bold text-ink-800">وبلاگ</span>
        </nav>

        <h1 class="text-2xl font-black text-ink-900 sm:text-3xl">وبلاگ ناوراکار</h1>

        @if ($posts->isEmpty())
            <div class="mt-10 rounded-2xl border border-ink-200/70 bg-white p-10 text-center text-ink-400">
                هنوز مطلبی منتشر نشده است.
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ route('public.blog.show', $post) }}" class="overflow-hidden rounded-2xl border border-ink-200/70 bg-white shadow-soft hover:shadow-soft-lg">
                        @if($post->coverUrl())
                            <div class="aspect-[16/9] overflow-hidden bg-ink-100">
                                <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" loading="lazy" class="h-full w-full object-cover">
                            </div>
                        @endif
                        <div class="p-4">
                            <h2 class="line-clamp-2 text-sm font-extrabold text-ink-900">{{ $post->title }}</h2>
                            @if($post->excerpt)
                                <p class="mt-1.5 line-clamp-3 text-xs text-ink-500">{{ $post->excerpt }}</p>
                            @endif
                            <div class="mt-2 text-[11px] text-ink-400">{{ $post->published_at?->format('Y-m-d') }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $posts->links() }}</div>
        @endif
    </div>
</x-layouts.public>
