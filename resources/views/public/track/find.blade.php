<x-layouts.public title="پیگیری درخواست | ناوراکار">

    <div class="bg-v2-bg px-4 py-10">
        <div class="mx-auto max-w-md">
            <h1 class="text-center text-xl font-black text-v2-text sm:text-2xl">پیگیری درخواست استعلام</h1>
            <p class="mt-2 text-center text-xs text-v2-text-muted sm:text-sm">
                شماره درخواست (همان کدی که هنگام ثبت درخواست دریافت کردید) و شماره موبایلی که با آن ثبت‌نام کرده‌اید را وارد کنید.
            </p>

            <x-card variant="v2" class="mt-6">
                <form method="GET" action="{{ route('public.track.find') }}" class="space-y-4">
                    <div>
                        <label for="number" class="mb-1.5 block text-xs font-bold text-v2-text-muted">شماره درخواست</label>
                        <input id="number" name="number" type="text" inputmode="numeric" required
                               value="{{ request('number') }}" placeholder="مثلاً 128"
                               class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3 py-3 text-sm text-v2-text placeholder:text-v2-text-muted focus:border-v2-primary focus:outline-none focus:ring-2 focus:ring-v2-primary/30 num-font">
                    </div>
                    <div>
                        <label for="phone" class="mb-1.5 block text-xs font-bold text-v2-text-muted">شماره موبایل ثبت‌شده</label>
                        <input id="phone" name="phone" type="tel" required
                               value="{{ request('phone') }}" placeholder="مثلاً 09121234567"
                               class="w-full rounded-xl border border-v2-border bg-v2-elevated px-3 py-3 text-sm text-v2-text placeholder:text-v2-text-muted focus:border-v2-primary focus:outline-none focus:ring-2 focus:ring-v2-primary/30 num-font">
                    </div>

                    @if($notFound)
                        <p class="text-xs font-bold text-v2-error">درخواستی با این شماره و موبایل پیدا نشد. لطفاً دوباره بررسی کنید.</p>
                    @endif

                    <button type="submit" class="w-full rounded-xl bg-v2-primary px-5 py-3 text-sm font-bold text-white shadow-glow-v2 hover:brightness-110">
                        پیگیری درخواست
                    </button>
                </form>
            </x-card>
        </div>
    </div>
</x-layouts.public>
