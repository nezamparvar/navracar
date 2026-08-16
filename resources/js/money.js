export function parseMoney(value) {
    const raw = String(value ?? '').replace(/,/g, '').trim();
    return raw === '' || raw === '-' ? raw : raw;
}

export function formatMoney(value) {
    const raw = parseMoney(value);
    if (raw === '' || raw === '-') return raw;
    const [whole, decimal] = raw.split('.');
    const formatted = Number(whole || 0).toLocaleString('en-US');
    return decimal === undefined ? formatted : `${formatted}.${decimal}`;
}

export function installMoneyInputs(root = document) {
    root.querySelectorAll('[data-money-input]').forEach((input) => {
        if (input.dataset.moneyReady) return;
        input.dataset.moneyReady = '1';
        input.value = formatMoney(input.value);
        input.addEventListener('focus', () => { input.value = parseMoney(input.value); });
        input.addEventListener('blur', () => { input.value = formatMoney(input.value); });
        input.form?.addEventListener('submit', () => { input.value = parseMoney(input.value); }, { once: true });
    });
}

window.NavraMoney = { parseMoney, formatMoney, installMoneyInputs };

