<x-layouts.admin :page-title="$pageTitle" page-subtitle="برای مشتریانی که تلفنی یا حضوری تماس می‌گیرند، اینجا ثبتشان کنید تا وارد چرخه پیگیری CRM شوند.">

    <div x-data="leadForm()" class="mx-auto max-w-3xl">
        <x-card x-show="!success">
            <div x-show="error" x-cloak class="mb-4 rounded-xl bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" x-text="error"></div>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">نام و نام خانوادگی <span class="text-rose-500">*</span></label>
                        <input x-model="form.name" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">شماره تماس <span class="text-rose-500">*</span></label>
                        <input x-model="form.phone" required class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">ایمیل (اختیاری)</label>
                        <input x-model="form.email" type="email" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">منبع تماس</label>
                        <select x-model="form.source" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                            <option>تماس تلفنی</option><option>حضوری</option><option>سایت</option>
                            <option>معرفی مشتری</option><option>شبکه‌های اجتماعی</option><option>سایر</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">خودروی مورد نظر</label>
                        <input x-model="form.car_label" placeholder="مثلاً Toyota Land Cruiser 300" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">دسته خودرو</label>
                        <select x-model="form.category" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                            <option value="">— نامشخص —</option>
                            <option>هیبرید / برقی</option><option>زیر ۱۵۰۰ سی‌سی</option><option>۱۵۰۱ تا ۲۰۰۰</option>
                            <option>۲۰۰۱ تا ۲۵۰۰</option><option>۲۵۰۱ تا ۳۰۰۰</option><option>بالای ۳۰۰۱</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">بودجه تقریبی</label>
                        <select x-model="form.budget_range" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                            <option value="">— نامشخص —</option>
                            <option>زیر ۱۰ میلیارد تومان</option>
                            <option>۱۰ تا ۲۰ میلیارد تومان</option>
                            <option>۲۰ تا ۵۰ میلیارد تومان</option>
                            <option>۵۰ تا ۱۰۰ میلیارد تومان</option>
                            <option>۱۰۰ میلیارد تومان به بالا</option>
                            <option>نامشخص</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">شهر</label>
                        <select x-model="form.city" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                            <option value="">— نامشخص —</option>
                            @foreach ($cities as $c)<option>{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">برآورد جمع کل (تومان — اختیاری)</label>
                        <input x-model="form.total_with_profit" placeholder="مثلاً 900000000" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-bold">تاریخ تماس بعدی (اختیاری)</label>
                        <input x-model="form.next_call_date" type="date" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                    </div>
                    @if (auth()->user()->isAdmin())
                        <div>
                            <label class="mb-1.5 block text-sm font-bold">الحاق به</label>
                            <select x-model="form.assigned_to" class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                                <option value="">— خودم —</option>
                                @foreach ($staffList as $s)<option value="{{ $s->id }}">{{ $s->displayName() }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-bold">توضیحات</label>
                    <textarea x-model="form.notes" rows="3" placeholder="جزئیات گفتگو با مشتری..." class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5"></textarea>
                </div>
                <x-button type="submit" variant="amber" :disabled="false" x-bind:disabled="loading">
                    <span x-show="!loading">ثبت مشتری</span>
                    <span x-show="loading" x-cloak>در حال ثبت...</span>
                </x-button>
            </form>
        </x-card>

        <x-card x-show="success" x-cloak class="text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                <x-icon name="check-circle" class="w-7 h-7" />
            </div>
            <h2 class="mb-1 text-lg font-extrabold text-brand-900 dark:text-white">مشتری با موفقیت ثبت شد</h2>
            <p class="mb-5 text-sm text-ink-500">می‌توانید فرصت جدید دیگری ثبت کنید یا جزئیات همین یکی را ببینید.</p>
            <div class="flex justify-center gap-3">
                <x-button variant="amber" @click="reset">ثبت مشتری جدید</x-button>
                <a :href="'{{ route('admin.requests.index') }}/' + newId" class="inline-flex items-center justify-center rounded-xl border border-ink-200 px-4 py-2.5 text-sm font-bold text-ink-700 hover:bg-ink-50 dark:border-white/10 dark:text-ink-200 dark:hover:bg-white/10">مشاهده جزئیات →</a>
            </div>
        </x-card>
    </div>

    <script>
        function leadForm() {
            return {
                loading: false, error: '', success: false, newId: null,
                form: { name: '', phone: '', email: '', source: 'تماس تلفنی', car_label: '', category: '', budget_range: '', city: '', total_with_profit: '', next_call_date: '', notes: '', assigned_to: '' },
                async submit() {
                    if (!this.form.name || !this.form.phone) { this.error = 'نام و شماره تماس الزامی است.'; return; }
                    this.error = ''; this.loading = true;
                    try {
                        const res = await fetch('{{ route('admin.requests.store') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                            body: JSON.stringify(this.form),
                        });
                        const data = await res.json();
                        if (data.success) { this.success = true; this.newId = data.id; }
                        else { this.error = data.message || 'ثبت ناموفق بود.'; }
                    } catch (e) { this.error = 'خطا در ارتباط با سرور.'; }
                    finally { this.loading = false; }
                },
                reset() {
                    this.success = false;
                    this.form = { name: '', phone: '', email: '', source: 'تماس تلفنی', car_label: '', category: '', budget_range: '', city: '', total_with_profit: '', next_call_date: '', notes: '', assigned_to: '' };
                },
            };
        }
    </script>
</x-layouts.admin>
