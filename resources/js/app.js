import './bootstrap';

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

window.pushToast = (message, type = 'info') => Alpine.store('toasts').push(message, type);
