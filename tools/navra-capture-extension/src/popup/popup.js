let currentCapture = null;

// Initialize popup
document.addEventListener('DOMContentLoaded', async () => {
  const authenticated = await checkAuthentication();

  if (!authenticated) {
    showAuthState();
    setupAuthListeners();
  } else {
    showAuthenticatedState();
    await checkCurrentPage();
    setupAuthenticatedListeners();
  }
});

async function checkAuthentication() {
  return new Promise((resolve) => {
    chrome.runtime.sendMessage({ action: 'getAuth' }, (response) => {
      resolve(response && response.token);
    });
  });
}

function showAuthState() {
  document.getElementById('auth-state').style.display = 'block';
  document.getElementById('authenticated-state').style.display = 'none';
}

function showAuthenticatedState() {
  document.getElementById('auth-state').style.display = 'none';
  document.getElementById('authenticated-state').style.display = 'block';
}

function setupAuthListeners() {
  document.getElementById('connect-btn').addEventListener('click', connectToNavraCar);
}

async function connectToNavraCar() {
  const pairingCode = document.getElementById('pairing-code').value.trim();

  if (!pairingCode || pairingCode.length !== 6) {
    showAuthError('لطفاً کد جفت‌سازی ۶ رقمی را وارد کنید');
    return;
  }

  const btn = document.getElementById('connect-btn');
  btn.classList.add('loading');

  try {
    await new Promise((resolve, reject) => {
      chrome.runtime.sendMessage({ action: 'exchangePairingCode', pairingCode }, (response) => {
        if (response && response.status === 'success') {
          resolve();
        } else {
          reject(new Error(response?.error || 'Failed to exchange pairing code'));
        }
      });
    });

    document.getElementById('pairing-code').value = '';
    document.getElementById('auth-error').classList.remove('show');

    showAuthenticatedState();
    await checkCurrentPage();
    setupAuthenticatedListeners();
  } catch (error) {
    showAuthError('خطا در اتصال: ' + error.message);
  } finally {
    btn.classList.remove('loading');
  }
}

function showAuthError(message) {
  const errorDiv = document.getElementById('auth-error');
  errorDiv.textContent = message;
  errorDiv.classList.add('show');
}

async function checkCurrentPage() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

  chrome.tabs.sendMessage(tab.id, { action: 'canCapture' }, (response) => {
    if (response && response.canCapture) {
      captureCurrentPage(tab);
    } else {
      showUnsupportedPage();
    }
  });
}

function showUnsupportedPage() {
  document.getElementById('listing-detected-state').style.display = 'none';
  document.getElementById('unsupported-page-state').style.display = 'block';
}

async function captureCurrentPage(tab) {
  return new Promise((resolve) => {
    chrome.tabs.sendMessage(tab.id, { action: 'captureCurrentPage' }, (response) => {
      chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
        if (request.action === 'sendCaptureToNavraCar') {
          currentCapture = request.payload;
          displayCapturePreview(request.payload);
          document.getElementById('listing-detected-state').style.display = 'block';
          document.getElementById('unsupported-page-state').style.display = 'none';
          resolve();
        }
      });
    });
  });
}

function displayCapturePreview(capture) {
  const { vehicle, images, source } = capture;

  document.getElementById('source-name').textContent = source.charAt(0).toUpperCase() + source.slice(1);
  document.getElementById('preview-title').textContent = vehicle.title || '-';
  document.getElementById('preview-make-model').textContent =
    (vehicle.make || '-') + ' / ' + (vehicle.model || '-');
  document.getElementById('preview-year').textContent = vehicle.year || '-';
  document.getElementById('preview-price').textContent = vehicle.price_aed
    ? vehicle.price_aed.toLocaleString() + ' AED'
    : '-';
  document.getElementById('preview-mileage').textContent = vehicle.mileage_km || '-';
  document.getElementById('preview-engine').textContent = vehicle.engine || '-';
  document.getElementById('preview-image-count').textContent = (images || []).length + ' تصویر';

  if (images && images.length > 0) {
    const img = document.querySelector('#preview-image img');
    img.src = images[0].url || images[0];
  }

  checkMissingFields(capture);
}

function checkMissingFields(capture) {
  const requiredFields = ['title', 'make', 'model', 'price_aed'];
  const missing = requiredFields.filter((field) => !capture.vehicle[field]);

  if (missing.length > 0) {
    const missingDiv = document.getElementById('missing-fields');
    const list = document.getElementById('missing-fields-list');
    list.innerHTML = '';

    missing.forEach((field) => {
      const li = document.createElement('li');
      li.textContent = field;
      list.appendChild(li);
    });

    missingDiv.style.display = 'block';
  } else {
    document.getElementById('missing-fields').style.display = 'none';
  }
}

function setupAuthenticatedListeners() {
  document.getElementById('send-btn').addEventListener('click', sendCapture);
  document.getElementById('preview-btn').addEventListener('click', showFullPreview);
  document.getElementById('settings-btn').addEventListener('click', showSettings);
  document.getElementById('disconnect-btn').addEventListener('click', disconnect);
  document.getElementById('back-btn').addEventListener('click', hideSettings);
  document.getElementById('clear-history-btn').addEventListener('click', clearHistory);
}

async function sendCapture() {
  if (!currentCapture) return;

  const btn = document.getElementById('send-btn');
  btn.classList.add('loading');

  try {
    const response = await new Promise((resolve) => {
      chrome.runtime.sendMessage({ action: 'sendCaptureToNavraCar', payload: currentCapture }, (response) => {
        resolve(response);
      });
    });

    if (response.status === 'error') {
      alert('خطا: ' + response.error);
    } else {
      alert('آگهی با موفقیت ارسال شد!');
      window.close();
    }
  } catch (error) {
    alert('خطا: ' + error.message);
  } finally {
    btn.classList.remove('loading');
  }
}

function showFullPreview() {
  if (!currentCapture) return;
  // This would open a new tab with full preview
  console.log('Full preview:', currentCapture);
}

function showSettings() {
  document.getElementById('listing-detected-state').style.display = 'none';
  document.getElementById('unsupported-page-state').style.display = 'none';
  document.getElementById('settings-panel').style.display = 'block';
}

function hideSettings() {
  document.getElementById('settings-panel').style.display = 'none';
  if (currentCapture) {
    document.getElementById('listing-detected-state').style.display = 'block';
  } else {
    document.getElementById('unsupported-page-state').style.display = 'block';
  }
}

async function disconnect() {
  if (confirm('آیا می‌خواهید اتصال را قطع کنید؟')) {
    await new Promise((resolve) => {
      chrome.runtime.sendMessage({ action: 'clearAuth' }, () => {
        resolve();
      });
    });

    hideSettings();
    showAuthState();
    setupAuthListeners();
  }
}

function clearHistory() {
  if (confirm('آیا تاریخچه کپچر را حذف کنید؟')) {
    chrome.storage.local.set({ captureHistory: [] }, () => {
      alert('تاریخچه حذف شد');
    });
  }
}
