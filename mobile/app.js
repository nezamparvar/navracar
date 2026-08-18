import { createApiClient, normalizeApiError, serializeQuery } from './js/api.js';
import { createTokenStore, preferredTokenStorage } from './js/auth.js';
import { createEngagement } from './js/engagement.js';
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
const engagement = createEngagement({
  api,
  storage: preferredTokenStorage(globalThis),
  appVersion: document.querySelector('meta[name="navracar-app-version"]')?.content || '1.1.0',
  deviceProvider: async () => globalThis.Capacitor?.Plugins?.Device?.getInfo?.() || {},
  onNotificationOpen: data => openInternalPushDestination(data?.url),
  onPushError: message => showToast(message),
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
        state.vehicleList = (await ensureBootstrap()).featured_vehicles || [];
        app.innerHTML = homeView(state.bootstrap, (await favoriteItems()).map(item => item.slug));
        break;
      case 'vehicles': {
        const search = serializeQuery(state.route.query);
        const payload = await api.get(`/api/mobile/v1/vehicles${search ? `?${search}` : ''}`);
        state.vehicleList = payload.data || [];
        app.innerHTML = vehiclesView(payload, state.route.query, (await favoriteItems()).map(item => item.slug));
        break;
      }
      case 'vehicle':
        state.vehicle = await api.get(`/api/mobile/v1/vehicles/${encodeURIComponent(state.route.params.slug)}`);
        app.innerHTML = detailView(state.vehicle);
        engagement.track('vehicle_view', { slug: state.vehicle.slug, make: state.vehicle.make, model: state.vehicle.model, year: state.vehicle.year }, 'vehicle');
        break;
      case 'pricing': {
        const bootstrap = await ensureBootstrap();
        state.pricingDraft = { car: state.route.query.car || '', price: state.route.query.price || '', category: state.route.query.category || 'c2000' };
        app.innerHTML = pricingView(bootstrap.categories, state.pricingDraft);
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
        app.innerHTML = accountView(state.customer, authMode, (await ensureBootstrap()).contact, engagement.status());
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
    engagement.track('screen_view', { route: state.route.name }, state.route.name);
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
  const contact = event.target.closest('[data-contact-action]');
  if (contact) engagement.track(contact.dataset.contactAction === 'phone' ? 'phone_click' : 'whatsapp_click', { placement: state.route.name }, state.route.name);
  const tab = event.target.closest('[data-auth-tab]');
  if (tab) { authMode = tab.dataset.authTab; app.innerHTML = accountView(null, authMode, state.bootstrap?.contact || {}, engagement.status()); return; }
  const action = event.target.closest('[data-action]');
  if (!action) return;
  if (action.dataset.action === 'enable-analytics' || action.dataset.action === 'disable-analytics') {
    try {
      await engagement.setAnalyticsConsent(action.dataset.action === 'enable-analytics');
      showToast(action.dataset.action === 'enable-analytics' ? 'آمار ناشناس فعال شد.' : 'آمار و داده‌های این نصب حذف شد.');
      route();
    } catch (error) { showToast(normalizeApiError(error)); }
    return;
  }
  if (action.dataset.action === 'enable-push' || action.dataset.action === 'disable-push') {
    try {
      await engagement.setNotificationsConsent(action.dataset.action === 'enable-push');
      showToast(action.dataset.action === 'enable-push' ? 'اعلان‌ها فعال شد.' : 'اعلان‌ها غیرفعال شد.');
      route();
    } catch (error) { showToast(normalizeApiError(error)); }
    return;
  }
  if (action.dataset.action === 'open-pricing') location.hash = `#/pricing?car=${encodeURIComponent(action.dataset.car || '')}&price=${encodeURIComponent(action.dataset.price || '')}&category=${encodeURIComponent(action.dataset.category || 'c2000')}`;
  if (action.dataset.action === 'open-quote') {
    const vehicle = state.vehicle || {};
    const source = action.dataset.quoteSource === 'pricing' ? state.pricingDraft || {} : { car: vehicle.title, price: vehicle.price_aed, category: vehicle.pricing?.category?.id };
    location.hash = `#/quote?car=${encodeURIComponent(source.car || '')}&price=${encodeURIComponent(source.price || '')}&category=${encodeURIComponent(source.category || 'c2000')}`;
  }
  if (action.dataset.action === 'logout') {
    try {
      await api.post('/api/mobile/v1/auth/logout', {});
      tokens.clear(); state.customer = null; authMode = 'login'; showToast('از حساب خارج شدید.'); route();
    } catch (error) {
      showToast(`خروج انجام نشد: ${normalizeApiError(error)}`);
    }
  }
});

app.addEventListener('submit', async (event) => {
  const form = event.target;
  event.preventDefault();
  pending(form);
  try {
    if (form.dataset.form === 'home-search') { const values = formData(form); await engagement.track('search', values, 'home'); location.hash = `#/vehicles?q=${encodeURIComponent(values.q)}`; return; }
    if (form.dataset.form === 'vehicle-filter') { const values = formData(form); await engagement.track('search', values, 'vehicles'); location.hash = `#/vehicles?${serializeQuery(values)}`; return; }
    if (form.dataset.form === 'pricing') {
      const values = formData(form);
      const price = Number(normalizePersianDigits(values.real_price_aed));
      const result = await api.post('/api/vehicle-pricing/calculate', { real_price_aed: price, category: values.category });
      state.pricingDraft = { car: state.pricingDraft?.car || '', price, category: values.category, result };
      document.getElementById('pricing-result').innerHTML = pricingResultView(result);
      engagement.track('pricing_calculate', { category: values.category }, 'pricing');
      return;
    }
    if (form.dataset.form === 'quote') {
      const values = formData(form);
      const result = await api.post('/api/mobile/v1/quote-requests', { name: values.name, phone: values.phone, email: values.email || null, car: values.car || null, notes: values.notes || null, pricing: { real_price_aed: Number(normalizePersianDigits(values.real_price_aed)), category: values.category } });
      form.replaceWith(htmlNode(emptyView('درخواست ثبت شد', result.message, '<a class="btn btn-primary" href="#/requests">پیگیری درخواست</a>')));
      engagement.track('quote_submit', { source: 'android', status: 'accepted' }, 'quote');
      showToast(result.message);
      return;
    }
    if (form.dataset.form === 'login' || form.dataset.form === 'register') {
      const result = await api.post(`/api/mobile/v1/auth/${form.dataset.form}`, formData(form));
      tokens.set(result.token); state.customer = result.customer; engagement.init().catch(() => {}); showToast('ورود با موفقیت انجام شد.'); app.innerHTML = accountView(state.customer, authMode, state.bootstrap?.contact || {}, engagement.status()); return;
    }
    if (form.dataset.form === 'profile') {
      const result = await api.patch('/api/mobile/v1/account', formData(form));
      state.customer = result.customer;
      app.innerHTML = accountView(state.customer, authMode, (await ensureBootstrap()).contact, engagement.status());
      showToast('مشخصات حساب به‌روزرسانی شد.');
      return;
    }
    if (form.dataset.form === 'share') {
      const result = await api.post('/api/mobile/v1/shared-listings', formData(form));
      form.replaceWith(htmlNode(emptyView('لینک دریافت شد', `شماره صف بررسی: ${result.id}`)));
      engagement.track('share', { source: result.source || 'unknown' }, 'share');
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
      const vehicle = state.vehicle?.slug === slug ? state.vehicle : state.vehicleList?.find(item => item.slug === slug) || state.bootstrap?.featured_vehicles?.find(item => item.slug === slug);
      const next = active ? current.filter(item => item.slug !== slug) : [...current, vehicle].filter(Boolean);
      localStorage.setItem('navracar.local-favorites', JSON.stringify(next));
      showToast('برای همگام‌سازی علاقه‌مندی‌ها وارد حساب شوید.');
    }
    button.textContent = active ? '♡' : '♥';
    button.setAttribute('aria-label', active ? 'افزودن به علاقه‌مندی‌ها' : 'حذف از علاقه‌مندی‌ها');
    engagement.track('favorite', { slug, action: active ? 'remove' : 'add' }, state.route.name);
  } catch (error) { showToast(normalizeApiError(error)); }
}

function updateNetwork() {
  const node = document.getElementById('network-status');
  node.classList.toggle('offline', !navigator.onLine);
  node.querySelector('b').textContent = navigator.onLine ? 'آنلاین' : 'آفلاین';
}

function openInternalPushDestination(value) {
  if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//')) return;
  const path = value.split(/[?#]/, 1)[0];
  const vehicle = path.match(/^\/vehicles\/([A-Za-z0-9_-]+)$/);
  if (vehicle) location.hash = `#/vehicle/${vehicle[1]}`;
  else if (['/home', '/vehicles', '/requests', '/account', '/favorites'].includes(path)) location.hash = `#${path}`;
}

async function initializeEngagement() {
  const nativePlugins = globalThis.Capacitor?.Plugins;
  if (globalThis.Capacitor?.isNativePlatform?.() && nativePlugins?.PushNotifications) {
    engagement.setPushProvider(nativePlugins.PushNotifications);
  }
  try {
    await engagement.init();
    const notice = document.getElementById('privacy-notice');
    notice.hidden = engagement.status().choiceSeen;
    notice.addEventListener('click', async event => {
      const choice = event.target.closest('[data-privacy-choice]');
      if (!choice) return;
      try {
        await engagement.setAnalyticsConsent(choice.dataset.privacyChoice === 'accept');
        notice.hidden = true;
        showToast(choice.dataset.privacyChoice === 'accept' ? 'آمار ناشناس فعال شد؛ هر زمان از حساب قابل تغییر است.' : 'آمار ناشناس غیرفعال ماند.');
      } catch (error) { showToast(normalizeApiError(error)); }
    });
  } catch {
    // Engagement is optional and must never block core vehicle and quote flows.
  }
}

document.getElementById('environment-label').textContent = environment === 'staging' ? 'نسخه آزمایشی Staging' : 'Android V1';
window.addEventListener('hashchange', route);
window.addEventListener('online', () => { updateNetwork(); showToast('اتصال اینترنت برقرار شد.'); });
window.addEventListener('offline', updateNetwork);
updateNetwork();
await initializeEngagement();
if (globalThis.NavraShare?.consume) {
  const shared = globalThis.NavraShare.consume();
  if (shared) location.hash = `#/share?url=${encodeURIComponent(shared)}`;
}
window.addEventListener('navracar:share', () => {
  const shared = globalThis.NavraShare?.consume?.();
  if (shared) location.hash = `#/share?url=${encodeURIComponent(shared)}`;
});
if (!location.hash) location.hash = '#/home'; else route();
