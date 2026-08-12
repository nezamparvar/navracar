<x-layouts.admin :page-title="$pageTitle" :page-subtitle="url('/blog/'.$post->slug)">

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <x-badge :color="$post->status === 'published' ? 'green' : 'slate'">
            {{ $post->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}
        </x-badge>

        @if ($post->status === 'published')
            <x-button :href="route('public.blog.show', $post)" target="_blank" size="sm" variant="secondary">
                <x-icon name="external-link" class="w-4 h-4" /> مشاهده در سایت
            </x-button>
            <form method="POST" action="{{ route('admin.posts.unpublish', $post) }}">
                @csrf
                <x-button type="submit" size="sm" variant="secondary">
                    <x-icon name="x-circle" class="w-4 h-4" /> لغو انتشار
                </x-button>
            </form>
        @else
            <x-button :href="route('public.blog.show', $post)" target="_blank" size="sm" variant="secondary">
                <x-icon name="eye" class="w-4 h-4" /> پیش‌نمایش
            </x-button>
            <form method="POST" action="{{ route('admin.posts.publish', $post) }}">
                @csrf
                <x-button type="submit" size="sm" variant="amber">
                    <x-icon name="check-circle" class="w-4 h-4" /> انتشار در سایت
                </x-button>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.posts.destroy', $post) }}"
              onsubmit="return confirm('حذف این مطلب مطمئنید؟');" class="ms-auto">
            @csrf @method('DELETE')
            <x-button type="submit" size="sm" variant="danger">
                <x-icon name="trash" class="w-4 h-4" /> حذف مطلب
            </x-button>
        </form>
    </div>

    <div class="mx-auto max-w-3xl space-y-5">
        <form method="POST" action="{{ route('admin.posts.update', $post) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')
            @include('admin.posts._fields')
            <x-button type="submit" variant="amber" size="lg">
                <x-icon name="check" class="w-4 h-4" /> ذخیره تغییرات
            </x-button>
        </form>

        <x-social-publish
            :publish-url="route('admin.posts.publish-social', $post)"
            :whatsapp-url="$socialWhatsappUrl"
            :has-image="$socialHasImage" />
    </div>
</x-layouts.admin>
