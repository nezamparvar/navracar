let currentCapture = null;
let sendInProgress = false;

// Bind once at the stable document boundary. Buttons may be recreated by the
// popup UI without losing their actions.
setupAuthenticatedListeners();

// Initialize popup
document.addEventListener('DOMContentLoaded', async () => {
  const authenticated = await checkAuthentication();

  if (!authenticated) {
    showAuthState();
  } else {
    showAuthenticatedState();
    await checkCurrentPage();
  }
});

async function checkAuthentication() {
  return new Promise((resolve, reject) => {
    const timeout = setTimeout(() => {
      reject(new Error('Authentication check timeout'));
    }, 5000);

    chrome.runtime.sendMessage({ action: 'getAuth' }, (response) => {
      clearTimeout(timeout);
      if (chrome.runtime.lastError) {
        resolve(null);
      } else {
        resolve(response && response.token);
      }
    });
  }).catch(() => null);
}

function showAuthState() {
  document.getElementById('auth-state').style.display = 'block';
  document.getElementById('authenticated-state').style.display = 'none';
}

function showAuthenticatedState() {
  document.getElementById('auth-state').style.display = 'none';
  document.getElementById('authenticated-state').style.display = 'block';
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
  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (!tab) {
      showUnsupportedPage();
      return;
    }

    const canCapture = await new Promise((resolve) => {
      const timeout = setTimeout(() => {
        resolve(false);
      }, 3000);

      chrome.tabs.sendMessage(tab.id, { action: 'canCapture' }, (response) => {
        clearTimeout(timeout);
        if (chrome.runtime.lastError) {
          resolve(false);
        } else {
          resolve(response && response.canCapture);
        }
      });
    });

    if (canCapture) {
      await captureCurrentPage(tab);
    } else {
      showUnsupportedPage();
    }
  } catch (error) {
    showUnsupportedPage();
  }
}

function showUnsupportedPage() {
  document.getElementById('listing-detected-state').style.display = 'none';
  document.getElementById('unsupported-page-state').style.display = 'block';
}

async function captureCurrentPage(tab) {
  return new Promise((resolve) => {
    const timeout = setTimeout(() => {
      showUnsupportedPage();
      resolve();
    }, 10000);

    chrome.tabs.sendMessage(tab.id, { action: 'captureCurrentPage' }, (response) => {
      clearTimeout(timeout);
      if (chrome.runtime.lastError || response?.status !== 'success' || !response.payload) {
        showUnsupportedPage();
      } else {
        currentCapture = response.payload;
        displayCapturePreview(response.payload);
        document.getElementById('listing-detected-state').style.display = 'block';
        document.getElementById('unsupported-page-state').style.display = 'none';
      }
      resolve();
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
  if (document.documentElement.dataset.navraPopupBound === 'true') return;

  const actions = {
    'connect-btn': connectToNavraCar,
    'send-btn': sendCapture,
    'preview-btn': showFullPreview,
    'settings-btn': showSettings,
    'disconnect-btn': disconnect,
    'back-btn': hideSettings,
    'clear-history-btn': clearHistory,
    'batch-btn': showBatchCapture,
    'batch-back-btn': hideBatchCapture,
    'batch-cancel-btn': hideBatchCapture,
    'batch-send-btn': sendBatchCapture,
  };

  document.addEventListener('click', (event) => {
    const button = event.target.closest('button[id]');
    const action = button ? actions[button.id] : null;
    if (!action) return;

    event.preventDefault();
    Promise.resolve(action()).catch((error) => {
      alert('خطا: ' + (error?.message || 'عملیات افزونه ناموفق بود.'));
    });
  });
  document.documentElement.dataset.navraPopupBound = 'true';
}

function sendRuntimeMessage(message, timeoutMs = 15000) {
  return new Promise((resolve, reject) => {
    let settled = false;
    const timeout = setTimeout(() => {
      if (settled) return;
      settled = true;
      reject(new Error('Message timeout'));
    }, timeoutMs);

    chrome.runtime.sendMessage(message, (response) => {
      if (settled) return;
      settled = true;
      clearTimeout(timeout);
      if (chrome.runtime.lastError) {
        reject(new Error(chrome.runtime.lastError.message || 'Extension message failed'));
        return;
      }
      resolve(response);
    });
  });
}

async function sendCapture() {
  if (!currentCapture || sendInProgress) return;

  const btn = document.getElementById('send-btn');
  sendInProgress = true;
  btn.classList.add('loading');
  btn.disabled = true;

  try {
    const response = await sendRuntimeMessage({
      action: 'sendCaptureToNavraCar',
      payload: currentCapture,
    });

    if (response?.status === 'success' && response.data?.queue_item_id && response.data?.review_url) {
      alert('آگهی با موفقیت ارسال شد!');
      window.close();
    } else {
      alert('خطا: ' + (response?.error || 'ارسال در صف ناوراکار تأیید نشد.'));
    }
  } catch (error) {
    alert('خطا: ' + error.message);
  } finally {
    sendInProgress = false;
    btn.classList.remove('loading');
    btn.disabled = false;
  }
}

function showFullPreview() {
  if (!currentCapture) {
    alert('اطلاعات آگهی هنوز آماده نشده است؛ چند لحظه بعد دوباره تلاش کنید.');
    return;
  }

  const vehicle = currentCapture.vehicle || {};
  alert([
    vehicle.title || 'بدون عنوان',
    `${vehicle.make || '-'} / ${vehicle.model || '-'}`,
    `سال: ${vehicle.year || '-'}`,
    `قیمت: ${vehicle.price_aed ? Number(vehicle.price_aed).toLocaleString() + ' AED' : '-'}`,
    `تصاویر: ${(currentCapture.images || []).length}`,
  ].join('\n'));
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
  }
}

function clearHistory() {
  if (confirm('آیا تاریخچه کپچر را حذف کنید؟')) {
    chrome.storage.local.set({ captureHistory: [] }, () => {
      alert('تاریخچه حذف شد');
    });
  }
}

// Batch Capture Functions
async function showBatchCapture() {
  document.getElementById('listing-detected-state').style.display = 'none';
  document.getElementById('unsupported-page-state').style.display = 'none';
  document.getElementById('batch-capture-state').style.display = 'block';

  // Get all supported tabs
  const tabs = await getAllSupportedTabs();
  displayBatchTabs(tabs);
}

function hideBatchCapture() {
  document.getElementById('batch-capture-state').style.display = 'none';
  if (currentCapture) {
    document.getElementById('listing-detected-state').style.display = 'block';
  } else {
    document.getElementById('unsupported-page-state').style.display = 'block';
  }
}

async function getAllSupportedTabs() {
  const allTabs = await chrome.tabs.query({});

  const supportedTabs = [];
  for (const tab of allTabs) {
    let url;
    try { url = new URL(tab.url); } catch (_) { continue; }
    const host = url.hostname.toLowerCase();
    const supported = ['dubizzle.com', 'dubicars.com', 'yallamotor.com']
      .some(domain => host === domain || host.endsWith(`.${domain}`));
    if (supported) {
      supportedTabs.push(tab);
    }
  }

  return supportedTabs;
}

function displayBatchTabs(tabs) {
  const list = document.getElementById('batch-tabs-list');
  list.innerHTML = '';

  if (tabs.length === 0) {
    list.innerHTML = '<p style="padding: 20px; text-align: center; color: #999;">هیچ صفحهٔ پشتیبانی‌شده‌ای باز نیست</p>';
    document.getElementById('batch-send-btn').disabled = true;
    return;
  }

  tabs.forEach((tab, index) => {
    const item = document.createElement('div');
    item.className = 'batch-tab-item';

    const url = new URL(tab.url);
    const source = extractSourceFromUrl(url.hostname);

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.id = `batch-tab-${index}`;
    checkbox.dataset.tabId = String(tab.id);
    const info = document.createElement('div');
    info.className = 'batch-tab-info';
    const title = document.createElement('label');
    title.className = 'batch-tab-title';
    title.htmlFor = checkbox.id;
    title.textContent = tab.title || 'بدون عنوان';
    const sourceLine = document.createElement('div');
    sourceLine.className = 'batch-tab-source';
    sourceLine.textContent = `${source} • ${url.hostname}`;
    const status = document.createElement('span');
    status.className = 'batch-tab-status supported';
    status.textContent = 'پشتیبانی‌شده';
    info.append(title, sourceLine);
    item.append(checkbox, info, status);

    item.addEventListener('change', updateBatchSendButton);
    list.appendChild(item);
  });

  document.getElementById('batch-send-btn').disabled = false;
}

function extractSourceFromUrl(hostname) {
  if (hostname.includes('dubizzle')) return 'Dubizzle';
  if (hostname.includes('dubicars')) return 'DubiCars';
  if (hostname.includes('yallamotor')) return 'YallaMotor';
  return 'نامعلوم';
}

function updateBatchSendButton() {
  const checkboxes = document.querySelectorAll('.batch-tabs-list input[type="checkbox"]:checked');
  document.getElementById('batch-send-btn').disabled = checkboxes.length === 0;
}

async function sendBatchCapture() {
  const checkboxes = Array.from(document.querySelectorAll('.batch-tabs-list input[type="checkbox"]:checked'));
  const selectedTabIds = checkboxes.map(cb => parseInt(cb.getAttribute('data-tab-id')));

  if (selectedTabIds.length === 0) return;

  // Show progress
  document.getElementById('batch-progress').style.display = 'block';
  document.getElementById('batch-tabs-list').style.display = 'none';
  document.getElementById('batch-send-btn').disabled = true;
  document.getElementById('batch-cancel-btn').disabled = true;

  const results = [];
  let sent = 0;

  for (const tabId of selectedTabIds) {
    try {
      await captureAndSendTab(tabId);
      results.push({ tabId, status: 'success' });
      sent++;
    } catch (error) {
      results.push({ tabId, status: 'error', error: error.message });
    }

    // Update progress
    const progress = (sent / selectedTabIds.length) * 100;
    document.getElementById('batch-progress-fill').style.width = progress + '%';
    document.getElementById('batch-progress-text').textContent = `${sent}/${selectedTabIds.length} ارسال شد`;
  }

  // Show results
  displayBatchResults(results);
  document.getElementById('batch-progress').style.display = 'none';
  document.getElementById('batch-send-btn').disabled = false;
  document.getElementById('batch-cancel-btn').disabled = false;
}

async function captureAndSendTab(tabId) {
  return new Promise((resolve, reject) => {
    const timeoutId = setTimeout(() => {
      reject(new Error('Capture timeout'));
    }, 10000);

    chrome.tabs.sendMessage(tabId, { action: 'captureCurrentPage' }, (response) => {
      clearTimeout(timeoutId);
      if (chrome.runtime.lastError) {
        reject(new Error('Failed to communicate with tab'));
      } else if (response?.status !== 'success' || !response.payload) {
        reject(new Error(response?.error || 'Failed to capture listing'));
      } else {
        chrome.runtime.sendMessage(
          { action: 'sendCaptureToNavraCar', payload: response.payload },
          (sendResult) => {
            if (chrome.runtime.lastError) reject(new Error('Failed to send capture'));
            else if (sendResult?.status === 'success') resolve(sendResult);
            else reject(new Error(sendResult?.error || 'Failed to send'));
          }
        );
      }
    });
  });
}

function displayBatchResults(results) {
  const resultsList = document.getElementById('batch-results-list');
  resultsList.innerHTML = '';
  document.getElementById('batch-results').style.display = 'block';

  results.forEach((result) => {
    const li = document.createElement('li');
    li.className = result.status === 'success' ? 'success' : 'error';

    if (result.status === 'success') {
      li.textContent = `✓ صفحه ${result.tabId} با موفقیت ارسال شد`;
    } else {
      li.textContent = `✗ صفحه ${result.tabId}: ${result.error}`;
    }

    resultsList.appendChild(li);
  });
}
