import { createApiClient, normalizeApiError, serializeQuery } from './js/api.js';
import { createTokenStore, preferredTokenStorage } from './js/auth.js';
import { normalizePersianDigits } from './js/format.js';
import { initialState, parseHash, reducer } from './js/state.js';
import { accountView, detailView, emptyView, errorView, favoritesView, homeView, loadingView, pricingResultView, pricingView, quoteView, requestsView, shareView, vehiclesView } from './js/views.js';

const apiBase = document.querySelector('meta[name="navracar-api-base"]').content.replace(/\/$/, '');
const environment = document.querySelector('meta[name="navracar-environment"]')?.content || 'production';
const app = document.getElementById('app');
const toast = document.getElementById('toast');
const tokens = createTokenStore(preferredTokenStorage(globalThis));
let state = { ...initialState, route: parseHash(location.hash) };
let authMode = 'login';
let retry = () => route();

const api = createApiClient(apiBase, {
  tokenProvider: tokens.get,
  onUnauthorized: () => {
    tokens.clear();
    state = reducer(state, { type: 'AUTH_EXPIRED' });
    location.hash = '#/account';
  },
});

function showToast(message) {
  toast.textContent = message;
  toast.hidden = false;
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => { toast.hidden = true; }, 4200);
}

function setActiveNav(name) {
  const primary = name === 'vehicle' ? 'vehicles' : ['pricing', 'quote', 'share'].includes(name) ? 'home' : name;
  document.querySelectorAll('[data-nav]').forEach(link => link.classList.toggle('active', link.dataset.nav === primary));
}

async function ensureBootstrap() {
  if (!state.bootstrap) state.bootstrap = await api.get('/api/mobile/v1/bootstrap');
  return state.bootstrap;
}

async function ensureCustomer() {
  if (!tokens.get()) return null;
  if (!state.customer) state.customer = (await api.get('/api/mobile/v1/account')).customer;
  return state.customer;
}

async function route() {
  state.route = parseHash(location.hash);
  setActiveNav(state.route.name);
  state = reducer(state, { type: 'REQUEST_START' });
  app.innerHTML = loadingView();
  retry = route;
  try {
    switch (state.route.name) {
      case 'home':
        app.innerHTML = homeView(await ensureBootstrap());
        break;
      case 'vehicles': {
        const search = serializeQuery(state.route.query);
        const payload = await api.get(`/api/mobile/v1/vehicles${search ? `?${search}` : ''}`);
        app.innerHTML = vehiclesView(payload, state.route.query);
        break;
      }
      case 'vehicle':
        state.vehicle = await api.get(`/api/mobile/v1/vehicles/${encodeURIComponent(state.route.params.slug)}`);
        app.innerHTML = detailView(state.vehicle);
        break;
      case 'pricing': {
        const bootstrap = await ensureBootstrap();
        app.innerHTML = pricingView(bootstrap.categories, { price: state.route.query.price, category: state.route.query.category });
        break;
      }
      case 'quote':
        app.innerHTML = quoteView({ car: state.route.query.car, price: state.route.query.price, category: state.route.query.category });
        break;
      case 'requests': {
        if (!await ensureCustomer()) { location.hash = '#/account'; return; }
        state.requests = (await api.get('/api/mobile/v1/requests')).data;
        app.innerHTML = requestsView(state.requests);
        break;
      }
      case 'account':
        await ensureCustomer();
        app.innerHTML = accountView(state.customer, authMode, (await ensureBootstrap()).contact);
        break;
      case 'favorites':
        state.favorites = await favoriteItems();
        app.innerHTML = favoritesView(state.favorites);
        break;
      case 'share':
        app.innerHTML = shareView(state.route.query.url || '');
        break;
      default:
        app.innerHTML = homeView(await ensureBootstrap());
    }
    state = reducer(state, { type: 'REQUEST_SUCCESS', payload: {} });
    bindImageFallbacks();
    window.scrollTo(0, 0);
    app.focus({ preventScroll: true });
  } catch (error) {
    const message = normalizeApiError(error);
    state = reducer(state, { type: 'REQUEST_ERROR', error: message });
    app.innerHTML = errorView(message);
  }
}

function formData(form) { return Object.fromEntries(new FormData(form).entries()); }
function pending(form, active = true) {
  const button = form.querySelector('button[type="submit"],button:not([type])');
  if (!button) return;
  button.disabled = active;
  button.dataset.label ||= button.textContent;
  button.textContent = active ? 'در حال انجام…' : button.dataset.label;
}
function bindImageFallbacks() {
  document.querySelectorAll('[data-image-fallback]').forEach(image => image.addEventListener('error', () => {
    image.replaceWith(Object.assign(document.createElement('span'), { className: 'vehicle-placeholder', textContent: '▱' }));
  }, { once: true }));
}

app.addEventListener('click', async (event) => {
  const retryButton = event.target.closest('[data-action="retry"]');
  if (retryButton) { retry(); return; }
  const favorite = event.target.closest('[data-favorite]');
  if (favorite) { event.preventDefault(); event.stopPropagation(); await toggleFavorite(favorite.dataset.favorite, favorite); return; }
  const tab = event.target.closest('[data-auth-tab]');
  if (tab) { authMode = tab.dataset.authTab; app.innerHTML = accountView(null, authMode); return; }
  const action = event.target.closest('[data-action]');
  if (!action) return;
  if (action.dataset.action === 'open-pricing') location.hash = `#/pricing?price=${encodeURIComponent(action.dataset.price || '')}&category=${encodeURIComponent(action.dataset.category || 'c2000')}`;
  if (action.dataset.action === 'open-quote') {
    const vehicle = state.vehicle || {};
    location.hash = `#/quote?car=${encodeURIComponent(vehicle.title || '')}&price=${encodeURIComponent(vehicle.price_aed || '')}&category=${encodeURIComponent(vehicle.pricing?.category?.id || 'c2000')}`;
  }
  if (action.dataset.action === 'logout') {
    try { await api.post('/api/mobile/v1/auth/logout', {}); } catch {}
    tokens.clear(); state.customer = null; authMode = 'login'; showToast('از حساب خارج شدید.'); route();
  }
});

app.addEventListener('submit', async (event) => {
  const form = event.target;
  event.preventDefault();
  pending(form);
  try {
    if (form.dataset.form === 'home-search') { location.hash = `#/vehicles?q=${encodeURIComponent(formData(form).q)}`; return; }
    if (form.dataset.form === 'vehicle-filter') { location.hash = `#/vehicles?${serializeQuery(formData(form))}`; return; }
    if (form.dataset.form === 'pricing') {
      const values = formData(form);
      const result = await api.post('/api/vehicle-pricing/calculate', { real_price_aed: Number(normalizePersianDigits(values.real_price_aed)), category: values.category });
      document.getElementById('pricing-result').innerHTML = pricingResultView(result);
      return;
    }
    if (form.dataset.form === 'quote') {
      const values = formData(form);
      const result = await api.post('/api/mobile/v1/quote-requests', { name: values.name, phone: values.phone, email: values.email || null, car: values.car || null, notes: values.notes || null, pricing: { real_price_aed: Number(normalizePersianDigits(values.real_price_aed)), category: values.category } });
      form.replaceWith(htmlNode(emptyView('درخواست ثبت شد', result.message, '<a class="btn btn-primary" href="#/requests">پیگیری درخواست</a>')));
      showToast(result.message);
      return;
    }
    if (form.dataset.form === 'login' || form.dataset.form === 'register') {
      const result = await api.post(`/api/mobile/v1/auth/${form.dataset.form}`, formData(form));
      tokens.set(result.token); state.customer = result.customer; showToast('ورود با موفقیت انجام شد.'); app.innerHTML = accountView(state.customer); return;
    }
    if (form.dataset.form === 'profile') {
      const result = await api.patch('/api/mobile/v1/account', formData(form));
      state.customer = result.customer;
      app.innerHTML = accountView(state.customer, authMode, (await ensureBootstrap()).contact);
      showToast('مشخصات حساب به‌روزرسانی شد.');
      return;
    }
    if (form.dataset.form === 'share') {
      const result = await api.post('/api/mobile/v1/shared-listings', formData(form));
      form.replaceWith(htmlNode(emptyView('لینک دریافت شد', `شماره صف بررسی: ${result.id}`)));
      showToast('آگهی برای بررسی ثبت شد.');
    }
  } catch (error) {
    showToast(normalizeApiError(error));
  } finally {
    if (form.isConnected) pending(form, false);
  }
});

function htmlNode(markup) {
  const template = document.createElement('template');
  template.innerHTML = markup.trim();
  return template.content.firstElementChild;
}

async function favoriteItems() {
  if (await ensureCustomer()) return (await api.get('/api/mobile/v1/favorites')).data;
  try { return JSON.parse(localStorage.getItem('navracar.local-favorites') || '[]'); } catch { return []; }
}

async function toggleFavorite(slug, button) {
  try {
    const active = button.textContent === '♥';
    if (tokens.get()) {
      if (active) await api.delete(`/api/mobile/v1/favorites/${encodeURIComponent(slug)}`);
      else await api.put(`/api/mobile/v1/favorites/${encodeURIComponent(slug)}`);
    } else {
      const current = await favoriteItems();
      const vehicle = state.vehicle || state.bootstrap?.featured_vehicles?.find(item => item.slug === slug);
      const next = active ? current.filter(item => item.slug !== slug) : [...current, vehicle].filter(Boolean);
      localStorage.setItem('navracar.local-favorites', JSON.stringify(next));
      showToast('برای همگام‌سازی علاقه‌مندی‌ها وارد حساب شوید.');
    }
    button.textContent = active ? '♡' : '♥';
    button.setAttribute('aria-label', active ? 'افزودن به علاقه‌مندی‌ها' : 'حذف از علاقه‌مندی‌ها');
  } catch (error) { showToast(normalizeApiError(error)); }
}

function updateNetwork() {
  const node = document.getElementById('network-status');
  node.classList.toggle('offline', !navigator.onLine);
  node.querySelector('b').textContent = navigator.onLine ? 'آنلاین' : 'آفلاین';
}

document.getElementById('environment-label').textContent = environment === 'staging' ? 'نسخه آزمایشی Staging' : 'Android V1';
window.addEventListener('hashchange', route);
window.addEventListener('online', () => { updateNetwork(); showToast('اتصال اینترنت برقرار شد.'); });
window.addEventListener('offline', updateNetwork);
updateNetwork();
if (globalThis.NavraShare?.consume) {
  const shared = globalThis.NavraShare.consume();
  if (shared) location.hash = `#/share?url=${encodeURIComponent(shared)}`;
}
window.addEventListener('navracar:share', () => {
  const shared = globalThis.NavraShare?.consume?.();
  if (shared) location.hash = `#/share?url=${encodeURIComponent(shared)}`;
});
if (!location.hash) location.hash = '#/home'; else route();
