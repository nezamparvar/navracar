console.log('[Navra Capture] Content script loaded');

// Establish connection with popup
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'captureCurrentPage') {
    captureAndSend();
    sendResponse({ status: 'capture_started' });
  } else if (request.action === 'canCapture') {
    sendResponse({ canCapture: canCaptureCurrentPage() });
  }
});

function canCaptureCurrentPage() {
  const url = window.location.href;

  if (/dubizzle\.com/i.test(url)) {
    return !!document.querySelector('[data-testid="listing-name"]');
  }
  if (/dubicars\.com/i.test(url)) {
    return !!document.querySelector('h1');
  }
  if (/yallamotor\.com/i.test(url)) {
    return !!document.querySelector('[class*="listing"]');
  }

  return false;
}

function captureAndSend() {
  const url = window.location.href;
  let capturePayload = null;

  if (/dubizzle\.com/i.test(url)) {
    capturePayload = captureDubizzle();
  } else if (/dubicars\.com/i.test(url)) {
    capturePayload = captureDubiCars();
  } else if (/yallamotor\.com/i.test(url)) {
    capturePayload = captureYallaMotor();
  }

  if (capturePayload) {
    chrome.runtime.sendMessage({ action: 'sendCaptureToNavraCar', payload: capturePayload });
  }
}

function captureDubizzle() {
  const extractSimpleTestid = (testid) => {
    const el = document.querySelector(`[data-testid="${testid}"]`);
    return el?.textContent?.trim() || null;
  };

  const extractPrice = () => {
    const el = document.querySelector('[data-testid="listing-price"]');
    if (!el) return null;
    const match = (el.textContent || '').match(/([\d,]+)/);
    return match ? parseFloat(match[1].replace(/,/g, '')) : null;
  };

  const extractImages = () => {
    const images = [];
    const seen = new Set();
    document.querySelectorAll('img').forEach((img) => {
      const src = img.src;
      if (src && !seen.has(src)) {
        seen.add(src);
        images.push(src);
      }
    });
    return images;
  };

  const extractListingId = () => {
    const match = url.match(/-([a-f0-9]{32})/i);
    return match ? match[1] : null;
  };

  const extractDescription = () => {
    const el = document.querySelector('[data-testid="description"]');
    if (!el) return null;
    let text = el.textContent || '';
    text = text.replace(/<!--.*?-->/g, '').replace(/<br\s*\/?>/gi, '\n').trim();
    return text || null;
  };

  return {
    schema_version: 'navracar.capture.v1',
    source: 'dubizzle',
    source_url: url,
    source_listing_id: extractListingId(),
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle: {
      title: extractSimpleTestid('listing-name'),
      make: url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/([a-z0-9-]+)/i)?.[1],
      model: url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/[a-z0-9-]+\/([a-z0-9-]+)/i)?.[1],
      year: extractSimpleTestid('listing-year-value'),
      price_aed: extractPrice(),
      mileage_km: extractSimpleTestid('listing-kilometers-value'),
      fuel_type: extractSimpleTestid('overview-fuel_type-value'),
      transmission: extractSimpleTestid('overview-transmission_type-value'),
      body_type: extractSimpleTestid('overview-body_type-value'),
      regional_specs: extractSimpleTestid('listing-regional_specs-value'),
      steering_side: extractSimpleTestid('listing-steering_side-value'),
      exterior_color: extractSimpleTestid('overview-exterior_color-value'),
      interior_color: extractSimpleTestid('overview-interior_color-value'),
      seller_type: extractSimpleTestid('overview-seller_type-value'),
      warranty: extractSimpleTestid('overview-warranty-value'),
      horsepower: extractSimpleTestid('overview-horsepower-value'),
      no_of_cylinders: extractSimpleTestid('overview-no_of_cylinders-value'),
      doors: extractSimpleTestid('overview-doors-value'),
      seating_capacity: extractSimpleTestid('overview-seating_capacity-value'),
      engine: extractSimpleTestid('overview-engine_capacity_cc-value'),
      trim: extractSimpleTestid('overview-motors_trim-value'),
      description: extractDescription(),
      posted_on: extractSimpleTestid('posted-on'),
    },
    images: extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: {},
  };
}

function captureDubiCars() {
  const extractFieldValue = (field) => {
    return document.querySelector(`[data-field="${field}"]`)?.textContent?.trim() || null;
  };

  const extractPrice = () => {
    const el = document.querySelector('[data-field="price"]');
    if (!el) return null;
    const match = (el.textContent || '').match(/([\d,]+)/);
    return match ? parseFloat(match[1].replace(/,/g, '')) : null;
  };

  const extractImages = () => {
    const images = [];
    const seen = new Set();
    document.querySelectorAll('img').forEach((img) => {
      const src = img.src;
      if (src && !seen.has(src)) {
        seen.add(src);
        images.push(src);
      }
    });
    return images;
  };

  const extractListingId = () => {
    const match = url.match(/\/(?:car|listing)\/(\d+)/i);
    return match ? match[1] : null;
  };

  return {
    schema_version: 'navracar.capture.v1',
    source: 'dubicars',
    source_url: url,
    source_listing_id: extractListingId(),
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle: {
      title: document.querySelector('h1')?.textContent?.trim(),
      make: extractFieldValue('make'),
      model: extractFieldValue('model'),
      year: extractFieldValue('year'),
      price_aed: extractPrice(),
      mileage_km: extractFieldValue('mileage'),
      fuel_type: extractFieldValue('fuel'),
      transmission: extractFieldValue('transmission'),
      body_type: extractFieldValue('body'),
      color: extractFieldValue('color'),
      seller_type: extractFieldValue('seller'),
      description: document.querySelector('[class*="description"]')?.textContent?.trim() || null,
      engine: extractFieldValue('engine'),
    },
    images: extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: {},
  };
}

function captureYallaMotor() {
  const extractTitle = () => {
    return document.querySelector('h1')?.textContent?.trim() || null;
  };

  const extractPrice = () => {
    const el = document.querySelector('[class*="price"]');
    if (!el) return null;
    const match = (el.textContent || '').match(/([\d,]+)/);
    return match ? parseFloat(match[1].replace(/,/g, '')) : null;
  };

  const extractImages = () => {
    const images = [];
    const seen = new Set();
    document.querySelectorAll('img').forEach((img) => {
      const src = img.src;
      if (src && !seen.has(src)) {
        seen.add(src);
        images.push(src);
      }
    });
    return images;
  };

  const extractListingId = () => {
    const match = url.match(/\/(?:car|listing)\/(\d+)/i);
    return match ? match[1] : null;
  };

  return {
    schema_version: 'navracar.capture.v1',
    source: 'yallamotor',
    source_url: url,
    source_listing_id: extractListingId(),
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle: {
      title: extractTitle(),
      description: document.querySelector('[class*="description"]')?.textContent?.trim() || null,
    },
    images: extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: {},
  };
}
