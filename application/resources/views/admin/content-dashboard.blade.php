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

    <div class="mb-6 grid gap-5 lg:grid-cols-3">
        <x-card variant="v2" title="صف بررسی آگهی‌ها" icon="list" class="min-w-0 lg:col-span-2">
            <x-slot:subtitle><a href="{{ route('admin.import-queue.index') }}" class="text-v2-primary hover:underline">مشاهده صف کامل</a></x-slot:subtitle>
            @if (count($reviewQueue) === 0)
                <x-empty-state variant="v2" icon="list" title="آیتمی در صف بررسی نیست." />
            @else
                <div class="-mx-2 overflow-x-auto">
                    <table class="w-full min-w-[560px] text-xs">
                        <thead>
                            <tr class="text-[10px] font-bold text-v2-text-muted">
                                <th class="px-2 py-1.5 text-start">عنوان</th>
                                <th class="px-2 py-1.5 text-start">منبع</th>
                                <th class="px-2 py-1.5 text-start">کیفیت اطلاعات</th>
                                <th class="px-2 py-1.5 text-start">متا</th>
                                <th class="px-2 py-1.5 text-start">تصاویر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reviewQueue as $row)
                                <tr class="border-t border-v2-border">
                                    <td class="max-w-[160px] truncate px-2 py-2 font-bold text-v2-text">
                                        <a href="{{ route('admin.import-queue.index') }}" class="hover:text-v2-primary">{{ $row['title'] ?: 'بدون عنوان' }}</a>
                                    </td>
                                    <td class="px-2 py-2 text-v2-text-muted">{{ $row['source'] }}</td>
                                    <td class="px-2 py-2">
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 w-16 overflow-hidden rounded-full bg-v2-elevated">
                                                <div class="h-full rounded-full {{ $row['score'] >= 70 ? 'bg-v2-success' : ($row['score'] >= 40 ? 'bg-v2-warning' : 'bg-v2-error') }}" style="width: {{ $row['score'] }}%"></div>
                                            </div>
                                            <span class="num-font font-bold text-v2-text">{{ $row['score'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <x-badge :color="$row['metaState'] === 'کامل' ? 'v2-success' : ($row['metaState'] === 'نیازمند اصلاح' ? 'v2-warning' : 'v2-error')">{{ $row['metaState'] }}</x-badge>
                                    </td>
                                    <td class="num-font px-2 py-2 text-v2-text-muted">{{ $row['imagesImported'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card variant="v2" title="سلامت محتوای آگهی‌ها" icon="target" class="min-w-0">
            <div class="mb-3 text-center">
                <div class="num-font text-3xl font-black text-v2-primary">{{ $healthOverall }}%</div>
                <div class="text-[11px] text-v2-text-muted">میانگین سلامت محتوا در کل آگهی‌ها</div>
            </div>
            @if (empty($healthFields))
                <x-empty-state variant="v2" icon="target" title="هنوز آگهی‌ای برای محاسبه سلامت محتوا وجود ندارد." />
            @else
                <div class="space-y-2.5">
                    @foreach ($healthFields as $label => $percent)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-[11px] font-bold">
                                <span class="text-v2-text">{{ $label }}</span>
                                <span class="num-font text-v2-text-muted">{{ $percent }}%</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-v2-elevated">
                                <div class="h-full rounded-full {{ $percent >= 70 ? 'bg-v2-success' : ($percent >= 40 ? 'bg-v2-warning' : 'bg-v2-error') }}" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    <div class="mb-6 grid gap-5 lg:grid-cols-3">
        <x-card variant="v2" title="کارهای فوری" icon="alert" class="min-w-0">
            @php $activeTasks = array_filter($urgentTasks, fn ($t) => $t['count'] > 0); @endphp
            @if (empty($activeTasks))
                <x-empty-state variant="v2" icon="check-circle" title="کار فوری‌ای باقی نمانده است." />
            @else
                <div class="space-y-2">
                    @foreach ($activeTasks as $task)
                        <a href="{{ $task['route'] }}" class="flex items-center justify-between gap-2 rounded-lg bg-v2-bg px-2.5 py-2 text-xs hover:bg-v2-elevated">
                            <span class="font-bold text-v2-text">{{ $task['label'] }}</span>
                            <span class="num-font shrink-0 rounded-md bg-v2-warning/15 px-2 py-0.5 font-black text-v2-warning">{{ $task['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card variant="v2" title="انتشار محتوا (۷ روز اخیر)" icon="dashboard" class="min-w-0 lg:col-span-2">
            @php $maxPublish = max(1, collect($publishActivity)->flatMap(fn ($d) => [$d['listings'], $d['posts'], $d['slides']])->max()); @endphp
            <div class="flex items-end justify-between gap-2" style="height: 120px;">
                @foreach ($publishActivity as $day)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <div class="flex h-[88px] w-full items-end justify-center gap-0.5">
                            <div class="w-2 rounded-t bg-v2-primary" style="height: {{ max(2, $day['listings'] / $maxPublish * 88) }}px" title="{{ $day['listings'] }} آگهی"></div>
                            <div class="w-2 rounded-t bg-v2-accent" style="height: {{ max(2, $day['posts'] / $maxPublish * 88) }}px" title="{{ $day['posts'] }} وبلاگ"></div>
                            <div class="w-2 rounded-t bg-v2-text-muted/50" style="height: {{ max(2, $day['slides'] / $maxPublish * 88) }}px" title="{{ $day['slides'] }} اسلایدر"></div>
                        </div>
                        <span class="text-[10px] text-v2-text-muted">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 flex items-center justify-center gap-4 text-[11px] text-v2-text-muted">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-v2-primary"></span> آگهی</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-v2-accent"></span> وبلاگ</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-v2-text-muted/50"></span> اسلایدر</span>
            </div>
        </x-card>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card variant="v2" label="کل آگهی‌ها" icon="car">{{ number_format($contentSummary['listings']) }}</x-stat-card>
        <x-stat-card variant="v2" label="وبلاگ" icon="edit">{{ number_format($contentSummary['posts']) }}</x-stat-card>
        <x-stat-card variant="v2" label="اسلایدر" icon="image">{{ number_format($contentSummary['slides']) }}</x-stat-card>
        <x-stat-card variant="v2" label="رسانه‌ها" icon="image">{{ number_format($contentSummary['media']) }}</x-stat-card>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-card variant="v2" title="آخرین آگهی‌های خودرو" icon="car" class="min-w-0">
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

        <x-card variant="v2" title="آخرین مقالات" icon="edit" class="min-w-0">
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
