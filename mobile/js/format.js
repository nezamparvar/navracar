const persian = '۰۱۲۳۴۵۶۷۸۹';
const arabic = '٠١٢٣٤٥٦٧٨٩';

export function normalizePersianDigits(value) {
  return String(value ?? '')
    .replace(/[۰-۹]/g, (digit) => String(persian.indexOf(digit)))
    .replace(/[٠-٩]/g, (digit) => String(arabic.indexOf(digit)))
    .replace(/[٬,\s]/g, '')
    .replace('٫', '.');
}

export function formatNumber(value, maximumFractionDigits = 0) {
  const numeric = Number(value);
  if (!Number.isFinite(numeric)) return '—';
  return new Intl.NumberFormat('fa-IR', { maximumFractionDigits }).format(numeric);
}

export function formatMoney(value, currency) {
  const unit = currency === 'AED' ? 'درهم' : 'تومان';
  return `${formatNumber(value)} ${unit}`;
}

export function bidiLtr(value) {
  return `\u2066${String(value ?? '')}\u2069`;
}

export function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>'"]/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;',
  }[character]));
}
