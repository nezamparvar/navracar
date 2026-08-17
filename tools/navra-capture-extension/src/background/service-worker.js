console.log('[Navra Capture] Service worker started');

// Configuration - Build-time environment
// Staging build: points ONLY to staging
// Production build: points ONLY to production
// No runtime switching allowed
const EXTENSION_ENVIRONMENT = 'staging'; // Will be replaced by build script

const CONFIG = {
  staging: {
    baseUrl: 'https://staging.nezamparvar.com',
    apiUrl: 'https://staging.nezamparvar.com/api',
  },
  production: {
    baseUrl: 'https://navracar.com',
    apiUrl: 'https://navracar.com/api',
  },
};

const CURRENT_CONFIG = CONFIG[EXTENSION_ENVIRONMENT];

// Listen for messages from content script and popup
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'sendCaptureToNavraCar') {
    handleSendCapture(request.payload)
      .then((response) => sendResponse(response))
      .catch((error) => sendResponse({ status: 'error', error: error.message }));
    return true;
  }

  if (request.action === 'getAuth') {
    getAuthToken()
      .then((token) => sendResponse({ token }))
      .catch((error) => sendResponse({ error: error.message }));
    return true;
  }

  if (request.action === 'setAuth') {
    setAuthToken(request.token)
      .then(() => sendResponse({ status: 'ok' }))
      .catch((error) => sendResponse({ error: error.message }));
    return true;
  }

  if (request.action === 'clearAuth') {
    clearAuthToken()
      .then(() => sendResponse({ status: 'ok' }))
      .catch((error) => sendResponse({ error: error.message }));
    return true;
  }

  if (request.action === 'getEnvironment') {
    // Environment is fixed at build time, not runtime
    sendResponse({ environment: EXTENSION_ENVIRONMENT });
    return true;
  }

  if (request.action === 'exchangePairingCode') {
    handlePairingExchange(request.pairingCode)
      .then((response) => sendResponse(response))
      .catch((error) => sendResponse({ status: 'error', error: error.message }));
    return true;
  }
});

async function handlePairingExchange(pairingCode) {
  try {
    const response = await fetch(`${CURRENT_CONFIG.apiUrl}/browser-capture/v1/pairing/exchange`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        pairing_code: pairingCode,
        environment: EXTENSION_ENVIRONMENT,
        device_name: 'Browser Extension',
      }),
    });

    const data = await parseApiResponse(response);

    if (!response.ok) {
      return { status: 'error', error: apiError(data, 'Failed to exchange pairing code') };
    }

    // Store the token
    await new Promise((resolve) => {
      chrome.storage.local.set(
        {
          authToken: {
            token: data.token,
            environment: EXTENSION_ENVIRONMENT,
            created_at: new Date().toISOString(),
          },
        },
        resolve
      );
    });

    return { status: 'success', message: 'Extension successfully paired' };
  } catch (error) {
    return { status: 'error', error: error.message };
  }
}

async function handleSendCapture(payload) {
  const token = await getAuthToken();
  if (!token) {
    return { status: 'error', error: 'Not authenticated. Connect to NavraCar first.' };
  }

  try {
    const response = await fetch(`${CURRENT_CONFIG.apiUrl}/browser-capture/v1/listings`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token.token}`,
      },
      body: JSON.stringify(payload),
    });

    const data = await parseApiResponse(response);

    if (!response.ok) {
      if (response.status === 401) await clearAuthToken();
      return { status: 'error', error: apiError(data, 'Failed to send capture') };
    }

    // Open review page
    if (data.review_url) {
      chrome.tabs.create({ url: data.review_url });
    }

    return { status: 'success', data };
  } catch (error) {
    return { status: 'error', error: error.message };
  }
}

function getAuthToken() {
  return new Promise((resolve) => {
    chrome.storage.local.get(['authToken'], (result) => {
      resolve(result.authToken || null);
    });
  });
}

function setAuthToken(token) {
  return new Promise((resolve) => {
    chrome.storage.local.set({ authToken: token }, resolve);
  });
}

function clearAuthToken() {
  return new Promise((resolve) => {
    chrome.storage.local.remove(['authToken'], resolve);
  });
}

function apiError(data, fallback) {
  const validation = data && data.errors ? Object.values(data.errors).flat()[0] : null;
  return validation || data?.message || data?.error || fallback;
}

async function parseApiResponse(response) {
  const contentType = response.headers.get('content-type') || '';
  if (contentType.includes('application/json')) {
    return response.json();
  }

  await response.text();
  return {
    message: response.status === 401
      ? 'Navracar staging API is blocked by HTTP authentication.'
      : `Navracar API returned HTTP ${response.status}.`,
  };
}

function marketplaceFromUrl(rawUrl) {
  try {
    const host = new URL(rawUrl).hostname.toLowerCase();
    for (const domain of ['dubizzle.com', 'dubicars.com', 'yallamotor.com']) {
      if (host === domain || host.endsWith(`.${domain}`)) return domain;
    }
  } catch (_) {}
  return null;
}

// Update extension icon badge based on capture capability
chrome.tabs.onActivated.addListener((activeInfo) => {
  chrome.tabs.get(activeInfo.tabId, (tab) => {
    updateBadge(tab);
  });
});

chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  if (changeInfo.status === 'complete') {
    updateBadge(tab);
  }
});

function updateBadge(tab) {
  if (!tab.url) return;

  const isSupported = marketplaceFromUrl(tab.url) !== null;

  if (isSupported) {
    chrome.action.setBadgeText({ text: '✓', tabId: tab.id });
    chrome.action.setBadgeBackgroundColor({ color: '#4CAF50' });
  } else {
    chrome.action.setBadgeText({ text: '' });
  }
}

// Keyboard Shortcut: Alt+Shift+N
chrome.commands.onCommand.addListener((command) => {
  if (command === 'capture-current-listing') {
    handleKeyboardCapture();
  }
});

async function handleKeyboardCapture() {
  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

    if (!tab) {
      showNotification('خطا', 'لطفاً یک صفحه فعال را انتخاب کنید');
      return;
    }

    // Check if tab is on a supported domain
    const isSupported = marketplaceFromUrl(tab.url) !== null;
    if (!isSupported) {
      showNotification('صفحه پشتیبانی نشده', 'لطفاً بر روی یک صفحهٔ Dubizzle، DubiCars یا YallaMotor باشید');
      return;
    }

    // Send capture request
    chrome.tabs.sendMessage(tab.id, { action: 'captureCurrentPage' }, (response) => {
      if (chrome.runtime.lastError) {
        showNotification('خطا', 'نتوانستم با صفحه ارتباط برقرار کنم');
        return;
      }

      // Listen for capture completion
      const timeoutId = setTimeout(() => {
        showNotification('خطا', 'زمان انتظار ختم شد');
      }, 10000);

      const listener = (request) => {
        if (request.action === 'sendCaptureToNavraCar') {
          clearTimeout(timeoutId);
          chrome.runtime.onMessage.removeListener(listener);

          // Send capture
          handleSendCapture(request.payload)
            .then((result) => {
              if (result.status === 'success') {
                showNotification('موفقیت', 'خودرو با موفقیت ارسال شد');
              } else {
                showNotification('خطا', result.error || 'خطا در ارسال');
              }
            })
            .catch((error) => {
              showNotification('خطا', error.message);
            });
        }
      };

      chrome.runtime.onMessage.addListener(listener);
    });
  } catch (error) {
    showNotification('خطا', error.message);
  }
}

function showNotification(title, message) {
  // Use Chrome's notifications API for actual user-visible notification
  chrome.notifications.create({
    type: 'basic',
    iconUrl: chrome.runtime.getURL('src/icons/icon-128.png'),
    title: title,
    message: message,
    priority: 1,
  });
  // Also log for debugging
  console.log(`[Navra Capture] ${title}: ${message}`);
}
