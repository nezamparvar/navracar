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
  document.getElementById('send-btn').addEventListener('click', sendCapture);
  document.getElementById('preview-btn').addEventListener('click', showFullPreview);
  document.getElementById('settings-btn').addEventListener('click', showSettings);
  document.getElementById('disconnect-btn').addEventListener('click', disconnect);
  document.getElementById('back-btn').addEventListener('click', hideSettings);
  document.getElementById('clear-history-btn').addEventListener('click', clearHistory);
  document.getElementById('batch-btn').addEventListener('click', showBatchCapture);
  document.getElementById('batch-back-btn').addEventListener('click', hideBatchCapture);
  document.getElementById('batch-cancel-btn').addEventListener('click', hideBatchCapture);
  document.getElementById('batch-send-btn').addEventListener('click', sendBatchCapture);
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

    if (response?.status === 'success' && response.data?.queue_item_id && response.data?.review_url) {
      alert('آگهی با موفقیت ارسال شد!');
      window.close();
    } else {
      alert('خطا: ' + (response?.error || 'ارسال در صف ناوراکار تأیید نشد.'));
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
