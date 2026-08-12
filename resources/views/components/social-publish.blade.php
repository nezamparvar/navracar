@props(['publishUrl', 'whatsappUrl', 'hasImage'])

<x-card title="انتشار در شبکه‌های اجتماعی" icon="message"
        subtitle="تلگرام و بله با یک کلیک و به‌صورت خودکار ارسال می‌شوند (نیاز به تنظیم توکن ربات در تنظیمات پنل). واتساپ به‌دلیل نبود API رایگان، متن آماده را در واتساپ باز می‌کند — عکس را خودتان دستی پیوست کنید.">
    @if (! $hasImage)
        <p class="text-xs font-bold text-rose-600">این مورد عکسی ندارد — برای انتشار در تلگرام/بله ابتدا یک عکس اضافه کنید.</p>
    @endif
    <div x-data="{ sending: null, results: {} }" class="flex flex-wrap gap-3">
        <button type="button" :disabled="sending === 'telegram'"
                @click="sending = 'telegram'; fetch('{{ $publishUrl }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ platform: 'telegram' }) }).then(r => r.json()).then(d => { results.telegram = d; sending = null; }).catch(() => { results.telegram = { ok: false, error: 'خطا در ارتباط' }; sending = null; })"
                class="inline-flex items-center gap-2 rounded-xl bg-[#229ED9] px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60">
            <span x-show="sending !== 'telegram'">ارسال به تلگرام</span>
            <span x-show="sending === 'telegram'">در حال ارسال...</span>
        </button>
        <button type="button" :disabled="sending === 'bale'"
                @click="sending = 'bale'; fetch('{{ $publishUrl }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ platform: 'bale' }) }).then(r => r.json()).then(d => { results.bale = d; sending = null; }).catch(() => { results.bale = { ok: false, error: 'خطا در ارتباط' }; sending = null; })"
                class="inline-flex items-center gap-2 rounded-xl bg-[#00AAF1] px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60">
            <span x-show="sending !== 'bale'">ارسال به بله</span>
            <span x-show="sending === 'bale'">در حال ارسال...</span>
        </button>
        <a href="{{ $whatsappUrl }}" target="_blank"
           class="inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-4 py-2.5 text-sm font-bold text-white">
            <x-icon name="whatsapp-fill" class="w-4 h-4" /> اشتراک در واتساپ
        </a>

        <template x-if="results.telegram">
            <p class="w-full text-xs font-bold" :class="results.telegram.ok ? 'text-emerald-600' : 'text-rose-600'" x-text="results.telegram.ok ? 'در تلگرام ارسال شد.' : ('تلگرام: ' + results.telegram.error)"></p>
        </template>
        <template x-if="results.bale">
            <p class="w-full text-xs font-bold" :class="results.bale.ok ? 'text-emerald-600' : 'text-rose-600'" x-text="results.bale.ok ? 'در بله ارسال شد.' : ('بله: ' + results.bale.error)"></p>
        </template>
    </div>
</x-card>
