/** @jest-environment-options {"url":"https://www.dubicars.com/"} */
import fs from 'fs';
import path from 'path';

const extensionRoot = path.join(__dirname, '..');

function loadContentScript(html: string, url: string) {
  const source = fs.readFileSync(path.join(extensionRoot, 'src/content/content-script.js'), 'utf8');
  document.documentElement.innerHTML = `<head></head><body>${html}</body>`;
  window.history.replaceState({}, '', new URL(url).pathname + new URL(url).search);
  const listeners: Function[] = [];
  const chrome = {
    runtime: {
      onMessage: { addListener: (listener: Function) => listeners.push(listener) },
      sendMessage: jest.fn(),
    },
  };
  const exports = new Function(
    'window',
    'document',
    'chrome',
    'console',
    `${source}\nreturn { Extractors, captureDubizzle };`,
  )(window, document, chrome, { log() {}, error() {} });

  return {
    chrome,
    listener: listeners[0],
    Extractors: exports.Extractors,
    captureDubizzle: exports.captureDubizzle,
  };
}

function loadServiceWorker(fetchImpl: jest.Mock) {
  const source = fs.readFileSync(path.join(extensionRoot, 'src/background/service-worker.js'), 'utf8');
  const tabsCreate = jest.fn();
  const chrome = {
    runtime: {
      onMessage: { addListener: jest.fn() },
      getURL: jest.fn(),
    },
    storage: {
      local: {
        get: (_keys: string[], callback: Function) => callback({
          authToken: { token: 'a'.repeat(64), environment: 'staging' },
        }),
        set: jest.fn(),
        remove: (_keys: string[], callback: Function) => callback(),
      },
    },
    tabs: {
      create: tabsCreate,
      onActivated: { addListener: jest.fn() },
      onUpdated: { addListener: jest.fn() },
    },
    action: { setBadgeText: jest.fn(), setBadgeBackgroundColor: jest.fn() },
    commands: { onCommand: { addListener: jest.fn() } },
    notifications: { create: jest.fn() },
  };
  const { handleSendCapture } = new Function(
    'chrome',
    'fetch',
    'console',
    `${source}\nreturn { handleSendCapture };`,
  )(chrome, fetchImpl, { log() {}, error() {} });

  return { handleSendCapture, tabsCreate };
}

describe('capture delivery flow', () => {
  it('returns a preview payload without sending it before the user clicks Send', async () => {
    const html = `
      <h1>Toyota Camry</h1>
      <script type="application/ld+json">
        {"@type":["Product","Car"],"name":"Toyota Camry","brand":{"name":"Toyota"},"model":"Camry","offers":{"price":50000}}
      </script>`;
    const { chrome, listener } = loadContentScript(
      html,
      'https://www.dubicars.com/2020-toyota-camry-123.html',
    );
    const sendResponse = jest.fn();

    listener({ action: 'captureCurrentPage' }, {}, sendResponse);
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(chrome.runtime.sendMessage).not.toHaveBeenCalled();
    expect(sendResponse).toHaveBeenCalledWith(expect.objectContaining({
      status: 'success',
      payload: expect.objectContaining({ source: 'dubicars' }),
    }));
  });

  it('rejects a nominal API success that has no queue item or review URL', async () => {
    const fetchImpl = jest.fn().mockResolvedValue({
      ok: true,
      status: 200,
      headers: { get: () => 'application/json' },
      json: async () => ({ status: 'success', message: 'ok' }),
    });
    const { handleSendCapture, tabsCreate } = loadServiceWorker(fetchImpl);

    const result = await handleSendCapture({ source: 'dubicars' });

    expect(result.status).toBe('error');
    expect(tabsCreate).not.toHaveBeenCalled();
  });

  it('filters Dubizzle UI images that the API rejects', () => {
    const { Extractors } = loadContentScript(`
      <img data-testid="dpv-view-0" src="https://dbz-images.dubizzle.com/images/car.png">
      <img src="https://static.dubizzle.com/assets/logo.svg">
    `, 'https://dubai.dubizzle.com/motors/used-cars/1');

    expect(Extractors.extractImages('dubizzle')).toEqual([
      'https://dbz-images.dubizzle.com/images/car.png',
    ]);
  });

  it('unwraps YallaMotor image proxy URLs to the API-approved CDN URL', () => {
    const original = 'https://ymimg1.b8cdn.com/resized/used_car/2127210/car.webp';
    const proxy = `https://uae.yallamotor.com/_next/image?url=${encodeURIComponent(original)}&w=1920&q=75`;
    const { Extractors } = loadContentScript(
      `<img src="${proxy}">`,
      'https://uae.yallamotor.com/used-cars/hyundai/azera/2127210',
    );

    expect(Extractors.extractImages('yallamotor')).toEqual([original]);
  });

  it('keeps only images belonging to the current YallaMotor listing', () => {
    const current = 'https://ymimg1.b8cdn.com/resized/used_car/2127210/current.webp';
    const related = 'https://ymimg1.b8cdn.com/resized/used_car/9988776/related.webp';
    const { Extractors } = loadContentScript(
      `<div class="gallery"><img src="${current}"><img src="${related}"></div>`,
      'https://uae.yallamotor.com/used-cars/hyundai/azera/2127210',
    );

    expect(Extractors.extractImages('yallamotor', '2127210')).toEqual([current]);
  });

  it('uses the real Dubizzle vehicle heading instead of a promotional headline', () => {
    const html = `
      <h1 data-testid="listing-name">NO CONVENIENCE FEES | UNDER WARRANTY | 0% DOWN PAYMENT</h1>
      <div data-testid="listing-sub-heading">Mercedes-Benz G-Class G 63 AMG</div>
      <div data-testid="listing-year-value">2023</div>
      <script type="application/ld+json">
        {"@type":"Car","name":"Mercedes-Benz G-Class G 63 AMG","brand":{"name":"Mercedes-Benz"},"model":"G-Class","vehicleModelDate":"2023"}
      </script>`;
    const { captureDubizzle } = loadContentScript(
      html,
      'https://dubai.dubizzle.com/motors/used-cars/mercedes-benz/g-class/2023/1',
    ) as any;

    const payload = captureDubizzle();

    expect(payload.vehicle.title).toBe('Mercedes-Benz G-Class G 63 AMG');
    expect(payload.vehicle.title).not.toContain('NO CONVENIENCE FEES');
  });
});
