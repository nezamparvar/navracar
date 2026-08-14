@if (app()->environment('staging'))
    <div class="staging-banner fixed inset-x-0 top-0 z-[100] border-b border-amber-300 bg-amber-100 px-3 py-1.5 text-center text-xs font-black tracking-wide text-amber-950 shadow-sm" role="status">
        STAGING — تست و داده‌ها از محیط تولید جدا هستند
    </div>
@endif
