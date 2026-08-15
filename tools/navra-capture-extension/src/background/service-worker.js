console.log('[Navra Capture] Service worker started');

// Configuration - Build-time environment
// Staging build: points ONLY to staging
// Production build: points ONLY to production
// No runtime switching allowed
const EXTENSION_ENVIRONMENT = 'staging'; // Will be replaced by build script

const CONFIG = {
  staging: {
    baseUrl: 'https://navracar.com/staging',
    apiUrl: 'https://navracar.com/staging/api',
  },
  production: {
    baseUrl: 'https://navracar.com',
    apiUrl: 'https://navracar.com/api',
  },
};

const CURRENT_CONFIG = CONFIG[EXTENSION_ENVIRONMENT];

// Initialize default environment
chrome.storage.local.get(['environment'], (result) => {
  if (!result.environment) {
    chrome.storage.local.set({ environment: 'staging' });
  }
});

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

    const data = await response.json();

    if (!response.ok) {
      return { status: 'error', error: data.message || 'Failed to exchange pairing code' };
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

    const data = await response.json();

    if (!response.ok) {
      return { status: 'error', error: data.error || 'Failed to send capture' };
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

function getEnvironment() {
  return new Promise((resolve) => {
    chrome.storage.local.get(['environment'], (result) => {
      resolve(result.environment || 'staging');
    });
  });
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

  const isSupported =
    /dubizzle\.com|dubicars\.com|yallamotor\.com/i.test(tab.url);

  if (isSupported) {
    chrome.action.setBadgeText({ text: '✓', tabId: tab.id });
    chrome.action.setBadgeBackgroundColor({ color: '#4CAF50' });
  } else {
    chrome.action.setBadgeText({ text: '' });
  }
}
