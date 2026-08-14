@php $p = $post; @endphp

<x-layouts.public :title="$title">

    @push('head')
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ route('public.blog.show', $p) }}">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:type" content="article">
        <meta property="og:url" content="{{ route('public.blog.show', $p) }}">
        @if($p->coverUrl())
            <meta property="og:image" content="{{ $p->coverUrl() }}">
        @endif
        @if($p->status !== 'published')
            <meta name="robots" content="noindex, nofollow">
        @endif
        <script type="application/ld+json">
            {!! json_encode(array_filter([
                '@'.'context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $p->title,
                'image' => $p->coverUrl(),
                'datePublished' => $p->published_at?->toAtomString(),
                'dateModified' => $p->updated_at->toAtomString(),
                'author' => ['@type' => 'Organization', 'name' => 'ناوراکار'],
                'publisher' => ['@type' => 'Organization', 'name' => 'ناوراکار'],
                'mainEntityOfPage' => route('public.blog.show', $p),
            ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
        <x-schema-breadcrumbs :items="[
            ['label' => 'ناوراکار', 'url' => route('public.home')],
            ['label' => 'وبلاگ', 'url' => route('public.blog.index')],
            ['label' => $p->title, 'url' => route('public.blog.show', $p)],
        ]" />
    @endpush

    <div class="mx-auto max-w-3xl px-4 py-8">
        <nav class="mb-4 text-xs text-ink-500">
            <a href="{{ route('public.home') }}" class="hover:text-brand-700">ناوراکار</a>
            <span class="mx-1">/</span>
            <a href="{{ route('public.blog.index') }}" class="hover:text-brand-700">وبلاگ</a>
            <span class="mx-1">/</span>
            <span class="font-bold text-ink-800">{{ $p->title }}</span>
        </nav>

        @if($p->status !== 'published')
            <div class="mb-4 rounded-xl bg-rose-100 px-4 py-2.5 text-xs font-bold text-rose-700">
                این مطلب هنوز منتشر نشده — فقط برای پیش‌نمایش ادمین قابل مشاهده است.
            </div>
        @endif

        @if($p->coverUrl())
            <div class="aspect-[16/9] overflow-hidden rounded-2xl bg-ink-100">
                <img src="{{ $p->coverUrl() }}" alt="{{ $p->title }}" class="h-full w-full object-cover">
            </div>
        @endif

        <h1 class="mt-6 text-2xl font-black text-ink-900 sm:text-3xl">{{ $p->title }}</h1>
        @if($p->published_at)
            <div class="mt-2 text-xs text-ink-400">{{ $p->published_at->format('Y-m-d') }}</div>
        @endif

        <div class="prose prose-sm mt-6 max-w-none leading-8 text-ink-700">
            {!! $safeBody !!}
        </div>
    </div>
</x-layouts.public>
