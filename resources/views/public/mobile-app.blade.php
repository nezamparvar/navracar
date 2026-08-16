<!DOCTYPE html>
<html lang="fa" dir="rtl" x-data x-init="$store.theme.init()" :class="{ 'dark': $store.theme.dark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2D2657">
    <title>ناوراکار - محاسبه‌گر خودرو</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { --sk-color-primary: #2D2657; --sk-color-primary-dark: #171433; --sk-color-primary-light: #EDE9F7; --sk-color-primary-600: #2D2657; }
        .fab { position: fixed; bottom: 24px; right: 24px; width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(150deg, #C9A227, #9C7D14); box-shadow: 0 8px 18px -6px rgba(201, 162, 39, 0.55); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 50; transition: transform 0.2s; -webkit-touch-callout: none; user-select: none; }
        .fab:active { transform: scale(0.95); }
        .fab svg { width: 28px; height: 28px; color: #1A1200; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; display: none; }
        .modal-overlay.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 20px; max-width: 90vw; max-height: 90vh; overflow-y: auto; padding: 20px; box-shadow: 0 18px 40px -16px rgba(23,20,51,0.4); }
        .dark .modal-content { background: #111A2E; color: #E0E6FF; }
        @media (max-width: 480px) { .fab { bottom: 16px; right: 16px; width: 48px; height: 48px; } .fab svg { width: 24px; height: 24px; } }
    </style>
</head>
<body class="min-h-screen bg-ink-50 font-sans text-ink-900 dark:bg-ink-950 dark:text-ink-100">

<div x-data="mobileApp" class="min-h-screen flex flex-col">
    {{-- Header --}}
    <header class="sticky top-0 z-30 bg-gradient-to-l from-brand-900 to-brand-700 text-white px-4 py-4 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-ink-950">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13M6.5 17h11M3 13v5h18v-5"/></svg>
                </div>
                <div>
                    <div class="font-black text-lg">ناوراکار</div>
                    <div class="text-xs text-brand-200">محاسبه‌گر خودرو</div>
                </div>
            </div>
            <button @click="$store.theme.toggle()" class="p-2 hover:bg-white/10 rounded-lg">
                <svg x-show="!$store.theme.dark" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M21 12.88v2.5a9 9 0 11-6.62-8.58"/></svg>
                <svg x-show="$store.theme.dark" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5" x-cloak><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            </button>
        </div>
    </header>

    {{-- Main content --}}
    <main class="flex-1 px-4 py-6 pb-20">
        <div x-show="!showForm" class="space-y-4">
            <div class="bg-brand-100 dark:bg-brand-900/30 border border-brand-200 dark:border-brand-800 rounded-lg p-4 text-sm">
                <div class="font-bold text-brand-900 dark:text-brand-100 mb-2">خودروی خود را محاسبه کنید</div>
                <p class="text-brand-700 dark:text-brand-200">برای محاسبه هزینه واردات، دکمه زیر را بزنید یا از صفحه دابیزل/یالا موتور کپی کنید.</p>
            </div>

            <div class="space-y-2">
                <h2 class="text-sm font-bold text-ink-500 dark:text-ink-400">تاریخچه محاسبات</h2>
                <div id="history" class="space-y-2">
                    <div class="text-xs text-ink-500 dark:text-ink-400 text-center py-4">محاسبه‌ای ثبت نشده است</div>
                </div>
            </div>
        </div>

        <div x-show="showForm" class="max-w-md">
            <div class="bg-white dark:bg-ink-900 rounded-lg p-4 shadow-lg space-y-3">
                <h2 class="font-bold text-lg">محاسبه هزینه واردات</h2>

                <div>
                    <label class="block text-xs font-bold text-ink-500 dark:text-ink-400 mb-1">نام خودرو</label>
                    <input type="text" x-model="form.carLabel" placeholder="مثلاً Toyota Camry" class="w-full min-h-[44px] px-3 py-2 border border-ink-200 dark:border-white/10 rounded-lg bg-ink-50 dark:bg-white/5 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-ink-500 dark:text-ink-400 mb-1">سال</label>
                        <input type="number" x-model="form.year" placeholder="2023" class="w-full min-h-[44px] px-3 py-2 border border-ink-200 dark:border-white/10 rounded-lg bg-ink-50 dark:bg-white/5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-ink-500 dark:text-ink-400 mb-1">حجم موتور (cc)</label>
                        <input type="number" x-model="form.cc" placeholder="2000" class="w-full min-h-[44px] px-3 py-2 border border-ink-200 dark:border-white/10 rounded-lg bg-ink-50 dark:bg-white/5 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-ink-500 dark:text-ink-400 mb-1">قیمت (درهم)</label>
                    <input type="number" x-model="form.priceAED" placeholder="400000" class="w-full min-h-[44px] px-3 py-2 border border-ink-200 dark:border-white/10 rounded-lg bg-ink-50 dark:bg-white/5 text-sm">
                </div>

                <div id="loadingState" style="display: none;" class="text-center py-4">
                    <div class="inline-block">
                        <div class="w-6 h-6 border-3 border-brand-200 border-t-brand-600 rounded-full animate-spin"></div>
                    </div>
                </div>

                <div id="resultState" style="display: none;" class="bg-brand-100 dark:bg-brand-900/30 rounded-lg p-3 space-y-2 text-sm">
                    <div class="font-bold text-brand-900 dark:text-brand-100">جمع کل هزینه</div>
                    <div id="resultTotal" class="text-xl font-black text-brand-700 dark:text-brand-300"></div>
                </div>

                <div class="flex gap-2">
                    <button @click="submitCalculation()" class="flex-1 min-h-[44px] bg-gradient-to-r from-amber-500 to-amber-600 text-ink-950 font-bold rounded-lg hover:shadow-lg transition">محاسبه</button>
                    <button @click="showForm = false" class="flex-1 min-h-[44px] bg-ink-100 dark:bg-white/10 text-ink-700 dark:text-ink-200 font-bold rounded-lg">بستن</button>
                </div>
            </div>
        </div>
    </main>

    {{-- Floating Action Button --}}
    <div class="fab" @click="openCalculator()" title="محاسبه‌گر">
        <svg fill="currentColor" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="2"/><path fill="#1A1200" d="M7 7h3v3H7V7zm5 0h3v3h-3V7zm5 0h3v3h-3V7zM7 12h3v3H7v-3zm5 0h3v3h-3v-3zm5 0h3v3h-3v-3zM7 17h3v3H7v-3zm5 0h3v3h-3v-3zm5 0h3v3h-3v-3z"/></svg>
    </div>
</div>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const PRICING_URL = '{{ route('api.vehicle-pricing.calculate') }}';

document.addEventListener('alpine:init', () => {
    Alpine.data('mobileApp', () => ({
        showForm: false,
        form: { carLabel: '', year: new Date().getFullYear(), cc: '', priceAED: '' },
        history: [],

        openCalculator() {
            this.showForm = true;
            this.autoExtractDetails();
        },

        autoExtractDetails() {
            // Try to extract from page if on a product page (Dubizzle/YallaMotor)
            const title = document.title || '';
            const meta = document.querySelector('meta[property="og:title"]')?.content || '';

            if (title.includes('Toyota') || meta.includes('Toyota')) {
                this.form.carLabel = 'Toyota';
            }
            if (title.includes('BMW') || meta.includes('BMW')) {
                this.form.carLabel = 'BMW';
            }
            if (title.includes('Mercedes') || meta.includes('Mercedes')) {
                this.form.carLabel = 'Mercedes-Benz';
            }
        },

        async submitCalculation() {
            if (!this.form.carLabel || !this.form.priceAED || !this.form.cc) {
                alert('لطفاً تمام فیلدها را پر کنید');
                return;
            }

            document.getElementById('loadingState').style.display = 'block';
            document.getElementById('resultState').style.display = 'none';

            try {
                const response = await fetch(PRICING_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({
                        real_price_aed: this.form.priceAED,
                        customs_price_aed: this.form.priceAED * 0.8,
                        cc: this.form.cc,
                        fuel: 'petrol'
                    })
                });

                const data = await response.json();
                if (data.success) {
                    const result = data.result;
                    document.getElementById('resultTotal').textContent =
                        new Intl.NumberFormat('fa-IR').format(Math.round(result.finalTotalToman)) + ' تومان';

                    this.history.unshift({
                        car: this.form.carLabel,
                        year: this.form.year,
                        total: result.finalTotalToman,
                        timestamp: new Date().toLocaleTimeString('fa-IR')
                    });
                    localStorage.setItem('calculationHistory', JSON.stringify(this.history.slice(0, 10)));
                }
            } catch (error) {
                console.error('Calculation error:', error);
                document.getElementById('resultTotal').textContent = 'خطا در محاسبه';
            }

            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('resultState').style.display = 'block';
        }
    }));
});

// Load history on mount
document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('calculationHistory');
    if (saved) {
        const history = JSON.parse(saved);
        const historyEl = document.getElementById('history');
        if (history.length > 0) {
            historyEl.innerHTML = history.map(h => `
                <div class="bg-ink-50 dark:bg-white/5 rounded-lg p-3 text-sm">
                    <div class="font-bold">${h.car} (${h.year})</div>
                    <div class="text-ink-600 dark:text-ink-300 text-xs">${h.timestamp}</div>
                    <div class="font-black text-brand-600 dark:text-brand-300 mt-1">${new Intl.NumberFormat('fa-IR').format(Math.round(h.total))} تومان</div>
                </div>
            `).join('');
        }
    }
});
</script>
</body>
</html>
