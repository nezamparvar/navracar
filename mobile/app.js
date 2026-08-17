const apiBase = document.querySelector('meta[name="navracar-api-base"]').content.replace(/\/$/, '');
const appEnvironment = document.querySelector('meta[name="navracar-environment"]')?.content || 'production';
const environmentLabel = document.getElementById('environment-label');
environmentLabel.textContent = appEnvironment === 'staging'
  ? 'نسخه آزمایشی Staging'
  : 'محاسبه هزینه واردات خودرو';
const form = document.getElementById('pricing-form');
const submit = document.getElementById('submit');
const message = document.getElementById('message');
const result = document.getElementById('result');
const total = document.getElementById('total');
const customsPrice = document.getElementById('customs-price');
const historyContainer = document.getElementById('history');
const formatter = new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 0 });

document.getElementById('year').value = new Date().getFullYear();

function readHistory() {
  try { return JSON.parse(localStorage.getItem('navracar.calculations') || '[]'); }
  catch { return []; }
}

function renderHistory() {
  const history = readHistory();
  historyContainer.replaceChildren();
  if (!history.length) {
    const empty = document.createElement('p');
    empty.className = 'empty';
    empty.textContent = 'هنوز محاسبه‌ای ثبت نشده است.';
    historyContainer.append(empty);
    return;
  }
  for (const item of history) {
    const row = document.createElement('div');
    row.className = 'history-item';
    const title = document.createElement('strong');
    title.textContent = item.car || 'خودرو';
    const value = document.createElement('span');
    value.textContent = `${formatter.format(item.total)} تومان`;
    const time = document.createElement('small');
    time.textContent = item.createdAt;
    row.append(title, value, time);
    historyContainer.append(row);
  }
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  submit.disabled = true;
  message.textContent = 'در حال دریافت محاسبه از سرور…';
  result.hidden = true;
  try {
    const response = await fetch(`${apiBase}/api/vehicle-pricing/calculate`, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({
        real_price_aed: Number(document.getElementById('real-price').value),
        category: document.getElementById('category').value,
      }),
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'پاسخ سرور معتبر نبود.');
    total.textContent = `${formatter.format(data.finalTotalToman)} تومان`;
    customsPrice.textContent = `قیمت گمرکی محاسبه‌شده: ${formatter.format(data.input.customsPriceAed)} درهم`;
    result.hidden = false;
    message.textContent = '';
    const history = readHistory();
    history.unshift({
      car: document.getElementById('car-label').value.trim(),
      total: data.finalTotalToman,
      createdAt: new Date().toLocaleString('fa-IR'),
    });
    localStorage.setItem('navracar.calculations', JSON.stringify(history.slice(0, 10)));
    renderHistory();
  } catch (error) {
    message.textContent = navigator.onLine ? (error.message || 'محاسبه انجام نشد.') : 'اتصال اینترنت برقرار نیست.';
  } finally {
    submit.disabled = false;
  }
});

renderHistory();
