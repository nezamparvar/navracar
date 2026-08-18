import test from 'node:test';
import assert from 'node:assert/strict';
import { createEngagement } from '../../mobile/js/engagement.js';

function memoryStorage() {
  const values = new Map();
  return {
    getItem: key => values.get(key) ?? null,
    setItem: (key, value) => values.set(key, String(value)),
    removeItem: key => values.delete(key),
  };
}

function harness() {
  const calls = [];
  const api = {};
  for (const method of ['put', 'patch', 'post', 'delete']) {
    api[method] = async (...args) => { calls.push({ method, args }); return { accepted: 1 }; };
  }
  const engagement = createEngagement({
    api,
    storage: memoryStorage(),
    cryptoImpl: {
      randomUUID: () => '018f55ce-3d62-7d81-a0c3-7f5e05f2a111',
      getRandomValues: value => { value.fill(7); return value; },
    },
    deviceProvider: async () => ({ manufacturer: 'Samsung', model: 'SM-S928B', platform: 'android', osVersion: '14' }),
    setIntervalImpl: () => 9,
    clearIntervalImpl: () => {},
  });
  return { engagement, calls };
}

test('registers an installation with analytics and notifications disabled by default', async () => {
  const { engagement, calls } = harness();
  await engagement.init();

  assert.equal(calls[0].method, 'put');
  assert.equal(calls[0].args[1].analytics_consent, false);
  assert.equal(calls[0].args[1].device.model, 'SM-S928B');
  assert.equal(calls.some(call => call.args[0] === '/api/mobile/v1/analytics/events'), false);
  assert.equal(engagement.status().analyticsConsent, false);
});

test('queues only allowlisted events after consent and sends installation authentication headers', async () => {
  const { engagement, calls } = harness();
  await engagement.init();
  await engagement.setAnalyticsConsent(true);
  await engagement.track('search', { query: 'BMW X5' }, 'vehicles');
  await engagement.track('not_allowed', { anything: true });
  await engagement.flush();

  const eventCalls = calls.filter(call => call.args[0] === '/api/mobile/v1/analytics/events');
  assert.equal(eventCalls.length >= 1, true);
  const allEvents = eventCalls.flatMap(call => call.args[1].events);
  assert.equal(allEvents.some(event => event.name === 'search' && event.properties.query === 'BMW X5'), true);
  assert.equal(allEvents.some(event => event.name === 'not_allowed'), false);
  assert.equal(eventCalls[0].args[2].headers['X-Navracar-Installation'], '018f55ce-3d62-7d81-a0c3-7f5e05f2a111');
});

test('push registration follows explicit consent and reports notification opens', async () => {
  const listeners = {};
  let channel = null;
  const pushProvider = {
    checkPermissions: async () => ({ receive: 'prompt' }),
    requestPermissions: async () => ({ receive: 'granted' }),
    addListener: async (name, callback) => { listeners[name] = callback; },
    createChannel: async options => { channel = options; },
    register: async () => {},
  };
  const { engagement, calls } = harness();
  engagement.setPushProvider(pushProvider);
  await engagement.init();
  await engagement.setNotificationsConsent(true);
  await listeners.registration({ value: 'firebase-device-token-value' });
  await listeners.pushNotificationActionPerformed({ notification: { data: { notification_id: '42', url: '/vehicles' } } });

  assert.equal(calls.some(call => call.args[0].endsWith('/push-token') && call.args[1].token === 'firebase-device-token-value'), true);
  assert.equal(calls.some(call => call.args[0] === '/api/mobile/v1/push/opened/42'), true);
  assert.equal(engagement.status().notificationsConsent, true);
  assert.equal(channel.id, 'navracar_updates');
});

test('denied Android notification permission rolls Push consent back to false', async () => {
  const { engagement, calls } = harness();
  let permission = 'denied';
  let registrations = 0;
  engagement.setPushProvider({
    addListener: async () => {},
    createChannel: async () => {},
    checkPermissions: async () => ({ receive: permission }),
    register: async () => { registrations += 1; },
  });
  await engagement.init();

  await assert.rejects(() => engagement.setNotificationsConsent(true), /اجازه اعلان/);

  assert.equal(engagement.status().notificationsConsent, false);
  const consentCalls = calls.filter(call => call.args[0].endsWith('/consent'));
  assert.equal(consentCalls.at(-1).args[1].notifications_consent, false);

  permission = 'granted';
  await engagement.setNotificationsConsent(true);
  assert.equal(registrations, 1);
});

test('native FCM registration error revokes unusable Push consent', async () => {
  const listeners = {};
  let reported = null;
  const { engagement, calls } = harness();
  engagement.setPushProvider({
    addListener: async (name, callback) => { listeners[name] = callback; },
    createChannel: async () => {},
    checkPermissions: async () => ({ receive: 'granted' }),
    register: async () => {},
  });
  engagement.setPushErrorHandler(message => { reported = message; });
  await engagement.init();
  await engagement.setNotificationsConsent(true);

  await listeners.registrationError({ error: 'SERVICE_NOT_AVAILABLE' });

  assert.equal(engagement.status().notificationsConsent, false);
  assert.match(reported, /Firebase/);
  const consentCalls = calls.filter(call => call.args[0].endsWith('/consent'));
  assert.equal(consentCalls.at(-1).args[1].notifications_consent, false);
});
