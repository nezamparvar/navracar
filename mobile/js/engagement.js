const IDENTITY_KEY = 'navracar.engagement.identity.v1';
const ANALYTICS_KEY = 'navracar.engagement.analytics-consent';
const NOTIFICATIONS_KEY = 'navracar.engagement.notifications-consent';
const CHOICE_KEY = 'navracar.engagement.choice-seen';
const QUEUE_KEY = 'navracar.engagement.queue.v1';

const EVENT_PROPERTIES = {
  app_open: [],
  heartbeat: [],
  screen_view: ['route'],
  search: ['query', 'make', 'model', 'fuel', 'year_min', 'year_max', 'price_min', 'price_max', 'sort'],
  vehicle_view: ['slug', 'make', 'model', 'year'],
  favorite: ['slug', 'action'],
  share: ['source'],
  pricing_calculate: ['category'],
  quote_submit: ['source', 'status'],
  contact_click: ['placement'],
  whatsapp_click: ['placement'],
  phone_click: ['placement'],
};

function randomSecret(cryptoImpl) {
  const bytes = cryptoImpl.getRandomValues(new Uint8Array(32));
  const binary = Array.from(bytes, value => String.fromCharCode(value)).join('');
  return btoa(binary).replaceAll('+', '-').replaceAll('/', '_').replaceAll('=', '');
}

function bool(storage, key) { return storage?.getItem(key) === 'true'; }

function safeJson(storage, key, fallback) {
  try { return JSON.parse(storage?.getItem(key) || '') ?? fallback; } catch { return fallback; }
}

function suspicious(value) {
  const text = String(value);
  const digits = text.replace(/\D/g, '');
  return /\S+@\S+\.\S+/.test(text) || digits.length >= 8 || /^[A-HJ-NPR-Z0-9]{17}$/i.test(text) || text.length > 160;
}

function cleanProperties(name, properties = {}) {
  const allowed = EVENT_PROPERTIES[name] || [];
  return Object.fromEntries(allowed.flatMap(key => {
    const value = properties[key];
    if (value === undefined || value === null || typeof value === 'object' || suspicious(value)) return [];
    return [[key, String(value).slice(0, 120)]];
  }));
}

function defaultAcquisition() {
  if (typeof location === 'undefined') return { source: 'direct' };
  const params = new URL(location.href).searchParams;
  const source = params.get('utm_source') || 'direct';
  const campaign = params.get('utm_campaign');
  return { source: source.slice(0, 80), ...(campaign ? { campaign: campaign.slice(0, 120) } : {}) };
}

export function createEngagement(options) {
  const api = options.api;
  const storage = options.storage;
  const cryptoImpl = options.cryptoImpl ?? crypto;
  const deviceProvider = options.deviceProvider ?? (async () => ({}));
  const acquisitionProvider = options.acquisitionProvider ?? defaultAcquisition;
  const now = options.now ?? (() => new Date());
  const setIntervalImpl = options.setIntervalImpl ?? setInterval;
  const clearIntervalImpl = options.clearIntervalImpl ?? clearInterval;
  const appVersion = options.appVersion ?? '1.2.0';
  let identity = safeJson(storage, IDENTITY_KEY, null);
  let analyticsConsent = bool(storage, ANALYTICS_KEY);
  let notificationsConsent = bool(storage, NOTIFICATIONS_KEY);
  let pushProvider = options.pushProvider ?? null;
  let pushErrorHandler = options.onPushError ?? null;
  let pushListenersBound = false;
  let heartbeatTimer = null;

  if (!identity?.installationId || !identity?.secret) {
    identity = { installationId: cryptoImpl.randomUUID(), secret: randomSecret(cryptoImpl) };
    storage?.setItem(IDENTITY_KEY, JSON.stringify(identity));
  }

  const headers = () => ({
    'X-Navracar-Installation': identity.installationId,
    'X-Navracar-Installation-Secret': identity.secret,
  });
  const installationPath = () => `/api/mobile/v1/installations/${encodeURIComponent(identity.installationId)}`;

  function status() {
    return {
      analyticsConsent,
      notificationsConsent,
      choiceSeen: bool(storage, CHOICE_KEY),
      pushSupported: Boolean(pushProvider),
    };
  }

  async function init() {
    const device = await deviceProvider().catch(() => ({}));
    await api.put(installationPath(), {
      secret: identity.secret,
      analytics_consent: analyticsConsent,
      device: {
        manufacturer: device.manufacturer || null,
        model: device.model || null,
        platform: device.platform || device.operatingSystem || 'android',
        os_version: device.osVersion || null,
        app_version: appVersion,
        locale: typeof navigator !== 'undefined' ? navigator.language : 'fa-IR',
      },
      acquisition: acquisitionProvider(),
    }, { headers: headers() });
    if (analyticsConsent) {
      startHeartbeat();
      await track('app_open');
    }
    if (notificationsConsent && pushProvider) await configurePush();
    return status();
  }

  async function setAnalyticsConsent(enabled) {
    analyticsConsent = Boolean(enabled);
    storage?.setItem(ANALYTICS_KEY, String(analyticsConsent));
    storage?.setItem(CHOICE_KEY, 'true');
    if (!analyticsConsent) {
      storage?.removeItem(QUEUE_KEY);
      if (heartbeatTimer) clearIntervalImpl(heartbeatTimer);
      heartbeatTimer = null;
    }
    await api.patch(`${installationPath()}/consent`, { analytics_consent: analyticsConsent }, { headers: headers() });
    if (analyticsConsent) {
      startHeartbeat();
      await track('app_open');
    }
    return status();
  }

  async function track(name, properties = {}, page = null) {
    if (!analyticsConsent || !(name in EVENT_PROPERTIES)) return false;
    const queue = safeJson(storage, QUEUE_KEY, []);
    queue.push({
      name,
      ...(page ? { page: String(page).slice(0, 100) } : {}),
      occurred_at: now().toISOString(),
      properties: cleanProperties(name, properties),
    });
    storage?.setItem(QUEUE_KEY, JSON.stringify(queue.slice(-100)));
    await flush();
    return true;
  }

  async function flush() {
    if (!analyticsConsent) return 0;
    const queue = safeJson(storage, QUEUE_KEY, []);
    if (!queue.length) return 0;
    const batch = queue.slice(0, 25);
    try {
      await api.post('/api/mobile/v1/analytics/events', { events: batch }, { headers: headers() });
      storage?.setItem(QUEUE_KEY, JSON.stringify(queue.slice(batch.length)));
      return batch.length;
    } catch {
      return 0;
    }
  }

  function startHeartbeat() {
    if (heartbeatTimer) return;
    heartbeatTimer = setIntervalImpl(() => { track('heartbeat').catch(() => {}); }, 60000);
  }

  async function configurePush() {
    if (!pushProvider) return;
    if (!pushListenersBound) {
      await pushProvider.addListener('registration', async token => {
        if (!notificationsConsent || !token?.value) return;
        await api.post(`${installationPath()}/push-token`, { token: token.value }, { headers: headers() });
      });
      await pushProvider.addListener('registrationError', async () => {
        notificationsConsent = false;
        storage?.setItem(NOTIFICATIONS_KEY, 'false');
        await api.patch(`${installationPath()}/consent`, { notifications_consent: false }, { headers: headers() }).catch(() => {});
        pushErrorHandler?.('ثبت دستگاه در Firebase انجام نشد؛ تنظیمات استیج را بررسی کنید.');
      });
      await pushProvider.addListener('pushNotificationActionPerformed', async action => {
        const data = action?.notification?.data || {};
        if (data.notification_id && /^\d+$/.test(String(data.notification_id))) {
          await api.post(`/api/mobile/v1/push/opened/${data.notification_id}`, {}, { headers: headers() }).catch(() => {});
        }
        options.onNotificationOpen?.(data);
      });
      await pushProvider.createChannel?.({
        id: 'navracar_updates',
        name: 'به‌روزرسانی‌های ناوراکار',
        description: 'مدل‌های جدید و وضعیت خدمات ناوراکار',
        importance: 5,
        visibility: 1,
        sound: 'default',
      });
      pushListenersBound = true;
    }
    let permission = await pushProvider.checkPermissions();
    if (permission.receive === 'prompt') permission = await pushProvider.requestPermissions();
    if (permission.receive !== 'granted') throw new Error('اجازه اعلان در تنظیمات Android داده نشد.');
    await pushProvider.register();
  }

  async function setNotificationsConsent(enabled) {
    notificationsConsent = Boolean(enabled);
    storage?.setItem(NOTIFICATIONS_KEY, String(notificationsConsent));
    if (!notificationsConsent) {
      await api.delete(`${installationPath()}/push-token`, { headers: headers() }).catch(() => {});
    }
    await api.patch(`${installationPath()}/consent`, { notifications_consent: notificationsConsent }, { headers: headers() });
    if (notificationsConsent) {
      try {
        await configurePush();
      } catch (error) {
        notificationsConsent = false;
        storage?.setItem(NOTIFICATIONS_KEY, 'false');
        await api.patch(`${installationPath()}/consent`, { notifications_consent: false }, { headers: headers() }).catch(() => {});
        throw error;
      }
    }
    return status();
  }

  return {
    init, status, track, flush, setAnalyticsConsent, setNotificationsConsent,
    setPushProvider(provider) { pushProvider = provider; },
    setPushErrorHandler(handler) { pushErrorHandler = handler; },
  };
}
