<x-layouts.admin :page-title="$pageTitle" page-subtitle="افزودن آگهی به‌صورت دستی، بدون نیاز به لینک دابیزل.">

    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('admin.car-listings.store-manual') }}" class="space-y-5">
            @csrf

            @include('admin.car-listings._fields', ['l' => $listing, 'categories' => $categories])

            <x-button type="submit" variant="v2-primary" size="lg">
                <x-icon name="check" class="w-4 h-4" /> ایجاد آگهی (پیش‌نویس)
            </x-button>
            <p class="text-[11px] text-v2-text-muted">بعد از ذخیره، به صفحه ویرایش منتقل می‌شوید تا عکس اضافه کنید.</p>
        </form>
    </div>
</x-layouts.admin>
