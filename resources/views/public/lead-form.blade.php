<x-layouts.public :title="$title">

    <div class="mx-auto max-w-xl px-4 pb-16 pt-8" x-data="leadForm">
        <div class="mb-5 text-center">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-ink-950 shadow-glow-amber">
                <x-icon name="user" class="w-7 h-7" />
            </div>
            <h1 class="text-xl font-black text-brand-900">فرم ثبت گزارش تماس فروش</h1>
            <p class="mt-1 text-sm text-ink-500">فرصت جدید</p>
        </div>

        <p class="mb-4 text-xs font-bold text-rose-500">* فیلدهای الزامی</p>

        <form @submit.prevent="submit" x-show="!success" class="space-y-3.5">
            <input type="text" name="website" x-model="form.website" autocomplete="off" tabindex="-1" class="absolute h-px w-px overflow-hidden opacity-0" aria-hidden="true">

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 flex items-center gap-2 text-sm font-extrabold"><x-icon name="user" class="w-4 h-4 text-brand-600" /> نام کارشناس <span class="text-rose-500">*</span></label>
                <select x-model="form.userId" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
                    <option value="">— انتخاب کنید —</option>
                    @foreach ($staff as $s)<option value="{{ $s->id }}">{{ $s->full_name }}</option>@endforeach
                </select>
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 flex items-center gap-2 text-sm font-extrabold"><x-icon name="user" class="w-4 h-4 text-brand-600" /> نام مشتری <span class="text-rose-500">*</span></label>
                <input x-model="form.name" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 flex items-center gap-2 text-sm font-extrabold"><x-icon name="phone" class="w-4 h-4 text-brand-600" /> شماره تماس <span class="text-rose-500">*</span></label>
                <input x-model="form.phone" required inputmode="tel" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 text-sm font-extrabold">ایمیل مشتری (اختیاری)</label>
                <input x-model="form.email" type="email" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 flex items-center gap-2 text-sm font-extrabold"><x-icon name="trend-up" class="w-4 h-4 text-brand-600" /> بودجه تقریبی <span class="text-rose-500">*</span></label>
                <select x-model="form.budget" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
                    <option value="">— انتخاب کنید —</option>
                    <option>زیر ۱۰ میلیارد تومان</option>
                    <option>۱۰ تا ۲۰ میلیارد تومان</option>
                    <option>۲۰ تا ۵۰ میلیارد تومان</option>
                    <option>۵۰ تا ۱۰۰ میلیارد تومان</option>
                    <option>۱۰۰ میلیارد تومان به بالا</option>
                    <option>نامشخص</option>
                </select>
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 flex items-center gap-2 text-sm font-extrabold"><x-icon name="car" class="w-4 h-4 text-brand-600" /> خودروی مورد نظر <span class="text-rose-500">*</span></label>
                <input x-model="form.carInterest" list="carBrandsList" required placeholder="مثلاً Toyota Land Cruiser یا Lexus LX" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
                <datalist id="carBrandsList">@foreach ($carBrands as $b)<option value="{{ $b }}">@endforeach</datalist>
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 text-sm font-extrabold">منبع مشتری <span class="text-rose-500">*</span></label>
                <select x-model="form.source" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
                    <option value="">— انتخاب کنید —</option>
                    <option>تماس تلفنی</option><option>حضوری</option><option>سایت</option>
                    <option>معرفی مشتری</option><option>شبکه‌های اجتماعی</option><option>سایر</option>
                </select>
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 text-sm font-extrabold">وضعیت <span class="text-rose-500">*</span></label>
                <select x-model="form.status" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
                    <option value="">— انتخاب کنید —</option>
                    <option>باز</option><option>در حال پیگیری</option><option>فروخته شد</option><option>بسته - ناموفق</option>
                </select>
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 text-sm font-extrabold">کشور</label>
                <select x-model="form.country" @change="form.city = ''" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
                    @foreach ($countries as $c)<option>{{ $c }}</option>@endforeach
                </select>
                <p class="mt-1.5 text-xs text-ink-500">ممکن است مشتری از خارج از ایران تماس گرفته باشد.</p>
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 text-sm font-extrabold">شهر <span class="text-rose-500">*</span></label>
                <select x-show="form.country === 'ایران'" x-model="form.city" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
                    <option value="">— انتخاب کنید —</option>
                    @foreach ($cities as $c)<option>{{ $c }}</option>@endforeach
                </select>
                <input x-show="form.country !== 'ایران'" x-model="form.city" placeholder="نام شهر" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-1 text-sm font-extrabold">تاریخ تماس بعدی (اختیاری)</label>
                <input x-model="form.nextCall" type="date" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base">
                <p class="mt-1.5 text-xs text-ink-500">اگر قرار است دوباره با مشتری تماس بگیرید، تاریخش را اینجا بگذارید.</p>
            </div>

            <div class="animate-fade-up rounded-2xl border border-ink-200/70 bg-white p-5 shadow-soft">
                <label class="mb-2.5 text-sm font-extrabold">توضیحات</label>
                <textarea x-model="form.notes" rows="4" placeholder="جزئیات گفتگو با مشتری..." class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-3 text-base"></textarea>
            </div>

            <button type="submit" :disabled="loading" class="w-full rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 py-4 text-base font-black text-ink-950 shadow-glow-amber disabled:opacity-60">
                <span x-show="!loading">ثبت گزارش</span>
                <span x-show="loading" x-cloak>در حال ثبت...</span>
            </button>
            <p x-show="error" x-cloak x-text="error" class="text-center text-sm font-bold text-rose-600"></p>
        </form>

        <div x-show="success" x-cloak class="rounded-2xl border border-ink-200/70 bg-white p-10 text-center shadow-soft">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <x-icon name="check-circle" class="w-7 h-7" />
            </div>
            <h2 class="mb-1 text-lg font-extrabold text-brand-900">گزارش با موفقیت ثبت شد</h2>
            <p class="mb-5 text-sm text-ink-500">می‌توانید یک فرصت جدید دیگر ثبت کنید.</p>
            <button @click="location.reload()" class="rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-bold text-white">ثبت گزارش جدید</button>
        </div>
    </div>

    <script>
        function leadForm() {
            return {
                loading: false, error: '', success: false,
                form: { website: '', userId: '', name: '', phone: '', email: '', budget: '', carInterest: '', source: '', status: '', country: 'ایران', city: '', nextCall: '', notes: '' },
                async submit() {
                    if (!this.form.userId || !this.form.name || !this.form.phone || !this.form.budget || !this.form.carInterest || !this.form.source || !this.form.status || !this.form.city) {
                        this.error = 'لطفاً همه فیلدهای الزامی (*) را پر کنید.'; return;
                    }
                    this.error = ''; this.loading = true;
                    try {
                        const res = await fetch('{{ route('public.lead-form.store') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}', 'Accept': 'application/json' },
                            body: JSON.stringify({ ...this.form, formLoadedAt: window.__pageLoadedAt || 0 }),
                        });
                        const data = await res.json();
                        if (data.success) { this.success = true; }
                        else { this.error = data.message || 'ثبت ناموفق بود، دوباره تلاش کنید.'; }
                    } catch (e) { this.error = 'خطا در ارتباط با سرور.'; }
                    finally { this.loading = false; }
                },
            };
        }
        window.__pageLoadedAt = Date.now();
    </script>
</x-layouts.public>
