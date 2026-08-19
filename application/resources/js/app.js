import './bootstrap';
import { installMoneyInputs } from './money';

import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

Alpine.data('adminShell', () => ({ sidebarOpen: false }));
Alpine.data('publicHeader', () => ({ mobileMenuOpen: false }));
Alpine.data('homeSlider', () => ({
    active: 0,
    count: 0,
    timer: null,
    init() {
        this.count = Number(this.$el.dataset.slideCount || 0);
        if (this.count > 1) {
            this.timer = setInterval(() => { this.active = (this.active + 1) % this.count; }, 6000);
        }
    },
}));
Alpine.data('carGallery', () => ({
    active: 0,
    images: [],
    init() {
        this.images = JSON.parse(this.$el.dataset.images || '[]');
    },
}));

Alpine.data('carListingPricing', () => ({
    realPrice: 0,
    customsPrice: 0,
    discountPercent: 30,
    customsPriceTouched: false,

    get suggestedCustomsPrice() {
        return Math.max(0, this.realPrice * (1 - this.discountPercent / 100));
    },

    init() {
        const el = this.$el;
        this.discountPercent = Number(el.dataset.discountPercent ?? 30);
        this.realPrice = Number(el.dataset.realPrice ?? 0);
        const rawCustoms = el.dataset.customsPrice;
        const hasManualCustoms = rawCustoms !== undefined && rawCustoms !== '' && rawCustoms !== 'null';
        this.customsPrice = hasManualCustoms
            ? Number(rawCustoms)
            : this.suggestedCustomsPrice;
        this.customsPriceTouched = hasManualCustoms
            && Number(rawCustoms) !== this.suggestedCustomsPrice;

        this.$watch('realPrice', (newVal) => {
            if (!this.customsPriceTouched && newVal >= 0) {
                this.customsPrice = this.suggestedCustomsPrice;
            }
        });

        this.$watch('customsPrice', (newVal) => {
            this.customsPriceTouched = Number(newVal) !== this.suggestedCustomsPrice;
        });
    },

    restoreSuggestion() {
        this.customsPrice = this.suggestedCustomsPrice;
        this.customsPriceTouched = false;
    },
}));

for (const name of ['leadForm', 'templatePicker', 'kanbanBoard', 'carCalculatorApp', 'invoicePricingForm']) {
    if (typeof window[name] === 'function') {
        Alpine.data(name, window[name]);
    }
}

Alpine.store('toasts', {
    items: [],
    push(message, type = 'info') {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), 4200);
    },
    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

Alpine.store('theme', {
    dark: localStorage.getItem('theme') === 'dark'
        || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
    init() {
        this.apply();
    },
    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        this.apply();
    },
    apply() {
        document.documentElement.classList.toggle('dark', this.dark);
    },
});

Alpine.start();
installMoneyInputs();

window.pushToast = (message, type = 'info') => Alpine.store('toasts').push(message, type);

