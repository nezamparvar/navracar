<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$pageSubtitle">

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card variant="v2" label="آگهی‌های منتشرشده" icon="car" accent="green">{{ number_format($publishedListings) }}</x-stat-card>
        <x-stat-card variant="v2" label="آگهی‌های پیش‌نویس" icon="car">{{ number_format($draftListings) }}</x-stat-card>
        <x-stat-card variant="v2" label="ایمپورت‌های نیازمند بررسی" icon="upload" accent="amber">
            {{ number_format($needsReviewImports) }}
            <x-slot:note><a href="{{ route('admin.import-queue.index') }}" class="font-bold text-v2-primary">مشاهده صف ایمپورت</a></x-slot:note>
        </x-stat-card>
        <x-stat-card variant="v2" label="ایمپورت‌های ناموفق" icon="alert" accent="red">{{ number_format($failedImports) }}</x-stat-card>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card variant="v2" label="مقالات منتشرشده" icon="edit">{{ number_format($publishedPosts) }}</x-stat-card>
        <x-stat-card variant="v2" label="مقالات پیش‌نویس" icon="edit">{{ number_format($draftPosts) }}</x-stat-card>
        <x-stat-card variant="v2" label="اسلایدهای فعال صفحه اصلی" icon="image">{{ number_format($activeSlides) }}</x-stat-card>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-card variant="v2" title="آخرین آگهی‌های خودرو" icon="car">
            <x-slot:subtitle><a href="{{ route('admin.car-listings.index') }}" class="text-v2-primary hover:underline">مشاهده همه</a></x-slot:subtitle>
            @if ($recentListings->isEmpty())
                <x-empty-state variant="v2" icon="car" title="هنوز آگهی‌ای ثبت نشده است." />
            @else
                <div class="space-y-2">
                    @foreach ($recentListings as $listing)
                        <a href="{{ route('admin.car-listings.edit', $listing) }}" class="flex items-center justify-between gap-2 rounded-lg bg-v2-bg px-2.5 py-2 text-xs hover:bg-v2-elevated">
                            <span class="truncate font-bold text-v2-text">{{ $listing->title_fa }}</span>
                            <x-badge :color="$listing->status === 'published' ? 'v2-success' : 'v2-neutral'">{{ $listing->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}</x-badge>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card variant="v2" title="آخرین مقالات" icon="edit">
            <x-slot:subtitle><a href="{{ route('admin.posts.index') }}" class="text-v2-primary hover:underline">مشاهده همه</a></x-slot:subtitle>
            @if ($recentPosts->isEmpty())
                <x-empty-state variant="v2" icon="edit" title="هنوز مقاله‌ای ثبت نشده است." />
            @else
                <div class="space-y-2">
                    @foreach ($recentPosts as $post)
                        <a href="{{ route('admin.posts.edit', $post) }}" class="flex items-center justify-between gap-2 rounded-lg bg-v2-bg px-2.5 py-2 text-xs hover:bg-v2-elevated">
                            <span class="truncate font-bold text-v2-text">{{ $post->title }}</span>
                            <x-badge :color="$post->status === 'published' ? 'v2-success' : 'v2-neutral'">{{ $post->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}</x-badge>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
