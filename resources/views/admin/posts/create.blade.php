@php $post = new \App\Models\Post; @endphp

<x-layouts.admin :page-title="$pageTitle" page-subtitle="بعد از ذخیره، مطلب به‌صورت پیش‌نویس ثبت می‌شود و باید منتشر شود.">
    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('admin.posts.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @include('admin.posts._fields')
            <x-button type="submit" variant="amber" size="lg">
                <x-icon name="check" class="w-4 h-4" /> ایجاد مطلب (پیش‌نویس)
            </x-button>
        </form>
    </div>
</x-layouts.admin>
