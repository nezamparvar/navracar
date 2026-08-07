import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

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
