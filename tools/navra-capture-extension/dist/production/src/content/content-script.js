console.log('[Navra Capture] Content script loaded');

// Wait for page to be ready
function waitForPageReady(maxWait = 5000) {
  return new Promise((resolve) => {
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      resolve();
      return;
    }

    const timeout = setTimeout(() => resolve(), maxWait);
    document.addEventListener('DOMContentLoaded', () => {
      clearTimeout(timeout);
      resolve();
    });
    window.addEventListener('load', () => {
      clearTimeout(timeout);
      resolve();
    });
  });
}

// Message listener for popup/service-worker
chrome.runtime.onMessage.addListener(async (request, sender, sendResponse) => {
  try {
    if (request.action === 'captureCurrentPage') {
      await waitForPageReady();
      await captureAndSend();
      sendResponse({ status: 'capture_started' });
    } else if (request.action === 'canCapture') {
      sendResponse({ canCapture: canCaptureCurrentPage() });
    }
  } catch (error) {
    console.error('[Navra Capture] Error:', error);
    sendResponse({ status: 'error', error: error.message });
  }
});

function getMarketplaceSource() {
  const host = window.location.hostname.toLowerCase();
  if (host === 'dubizzle.com' || host.endsWith('.dubizzle.com')) return 'dubizzle';
  if (host === 'dubicars.com' || host.endsWith('.dubicars.com')) return 'dubicars';
  if (host === 'yallamotor.com' || host.endsWith('.yallamotor.com')) return 'yallamotor';
  return null;
}

function canCaptureCurrentPage() {
  const source = getMarketplaceSource();
  if (!source) return false;

  // Check for minimal listing indicators
  if (source === 'dubizzle') {
    return !!(document.querySelector('[data-testid="listing-name"]') ||
              document.querySelector('[data-testid="listing-price"]'));
  }
  if (source === 'dubicars') {
    return !!document.querySelector('h1');
  }
  if (source === 'yallamotor') {
    return !!document.querySelector('[class*="listing"]');
  }

  return false;
}

async function captureAndSend() {
  const source = getMarketplaceSource();
  if (!source) return;

  let payload = null;
  if (source === 'dubizzle') {
    payload = captureDubizzle();
  } else if (source === 'dubicars') {
    payload = captureDubiCars();
  } else if (source === 'yallamotor') {
    payload = captureYallaMotor();
  }

  if (payload) {
    chrome.runtime.sendMessage({ action: 'sendCaptureToNavraCar', payload });
  }
}

// Shared extraction helpers
const Extractors = {
  parsePrice(text) {
    if (!text) return null;
    const lower = text.toLowerCase();

    // Reject installment/monthly prices
    if (/(?:per\s+month|monthly|\/month|installment|aed\s*\d+\s*\/|monthly\s+payment)/i.test(lower)) {
      return null;
    }

    const match = text.match(/[\d,]+(?:\.[\d]+)?/);
    if (!match) return null;

    let numStr = match[0].replace(/,/g, '').replace(/\./g, '');
    const num = parseFloat(numStr);

    // Sanity check: price 500-5,000,000 AED
    if (isNaN(num) || num < 500 || num > 5000000) return null;

    return num;
  },

  extractTextFrom(selector) {
    const el = document.querySelector(selector);
    if (!el) return null;
    const text = el.textContent?.trim();
    return text && text.length > 0 ? text : null;
  },

  extractAttrFrom(selector, attr) {
    const el = document.querySelector(selector);
    if (!el) return null;
    const value = el.getAttribute(attr);
    return value && value.length > 0 ? value : null;
  },

  trySelectors(selectors) {
    for (const selector of selectors) {
      const el = document.querySelector(selector);
      if (el) {
        if (el.tagName === 'META') return el.getAttribute('content');
        if (el.tagName === 'A') return el.getAttribute('href');
        if (el.tagName === 'IMG') return el.getAttribute('src');
        const text = el.textContent?.trim();
        if (text && text.length > 0) return text;
      }
    }
    return null;
  },

  extractFromMeta(property) {
    const og = document.querySelector(`meta[property="og:${property}"]`);
    if (og) return og.getAttribute('content');
    const meta = document.querySelector(`meta[name="${property}"]`);
    if (meta) return meta.getAttribute('content');
    return null;
  },

  extractJsonLd() {
    const scripts = document.querySelectorAll('script[type="application/ld+json"]');
    for (const script of scripts) {
      try {
        const data = JSON.parse(script.textContent || '{}');
        const nodes = Array.isArray(data) ? data : [data];
        for (const node of nodes) {
          const type = (node['@type'] || '').toLowerCase();
          if (type.includes('vehicle') || type.includes('product') || node['offers']) {
            return node;
          }
        }
      } catch (e) {
        // Skip invalid JSON
      }
    }
    return null;
  },

  extractImages() {
    const images = [];
    const seen = new Set();
    const MAX_IMAGES = 20;

    const selectors = [
      'picture img',
      'img[data-testid*="image"]',
      'img[class*="gallery"]',
      'img[class*="carousel"]',
      'img[src*="listing"]',
      'img[src*="product"]',
      'figure img',
      'img[alt*="car"]',
      'img[alt*="vehicle"]',
      'img',
    ];

    for (const selector of selectors) {
      if (images.length >= MAX_IMAGES) break;

      document.querySelectorAll(selector).forEach((img) => {
        if (images.length >= MAX_IMAGES) return;

        const src = img.src || img.getAttribute('data-src') || img.getAttribute('data-srcset');
        if (!src || seen.has(src)) return;

        if (!src.startsWith('http') && !src.startsWith('//')) return;
        if (src.includes('pixel') || src.includes('blank') || src.includes('1x1')) return;
        if (src.startsWith('data:')) return;

        seen.add(src);
        images.push(src);
      });
    }

    return images;
  },
};

// DubizzleCar extraction
function captureDubizzle() {
  const url = window.location.href;
  const jsonLd = Extractors.extractJsonLd();

  const vehicle = {
    title: Extractors.trySelectors(['[data-testid="listing-name"]', 'h1']),
    make: null,
    model: null,
    year: Extractors.extractTextFrom('[data-testid="listing-year-value"]'),
    price_aed: null,
    mileage_km: Extractors.extractTextFrom('[data-testid="listing-kilometers-value"]'),
    fuel_type: Extractors.extractTextFrom('[data-testid="overview-fuel_type-value"]'),
    transmission: Extractors.extractTextFrom('[data-testid="overview-transmission_type-value"]'),
    body_type: Extractors.extractTextFrom('[data-testid="overview-body_type-value"]'),
    regional_specs: Extractors.extractTextFrom('[data-testid="listing-regional_specs-value"]'),
    steering_side: Extractors.extractTextFrom('[data-testid="listing-steering_side-value"]'),
    exterior_color: Extractors.extractTextFrom('[data-testid="overview-exterior_color-value"]'),
    interior_color: Extractors.extractTextFrom('[data-testid="overview-interior_color-value"]'),
    seller_type: Extractors.extractTextFrom('[data-testid="overview-seller_type-value"]'),
    warranty: Extractors.extractTextFrom('[data-testid="overview-warranty-value"]'),
    horsepower: Extractors.extractTextFrom('[data-testid="overview-horsepower-value"]'),
    no_of_cylinders: Extractors.extractTextFrom('[data-testid="overview-no_of_cylinders-value"]'),
    doors: Extractors.extractTextFrom('[data-testid="overview-doors-value"]'),
    seating_capacity: Extractors.extractTextFrom('[data-testid="overview-seating_capacity-value"]'),
    engine: Extractors.extractTextFrom('[data-testid="overview-engine_capacity_cc-value"]'),
    trim: Extractors.extractTextFrom('[data-testid="overview-motors_trim-value"]'),
    description: null,
    posted_on: null,
  };

  // Try JSON-LD first
  if (jsonLd) {
    if (jsonLd.name && !vehicle.title) vehicle.title = jsonLd.name;
    if (jsonLd.brand && !vehicle.make) {
      vehicle.make = typeof jsonLd.brand === 'object' ? jsonLd.brand.name : jsonLd.brand;
    }
    if (jsonLd.model && !vehicle.model) vehicle.model = jsonLd.model;
    if (jsonLd.description && !vehicle.description) vehicle.description = jsonLd.description;
    if (jsonLd.offers?.price && !vehicle.price_aed) {
      vehicle.price_aed = Extractors.parsePrice(String(jsonLd.offers.price));
    }
  }

  // Extract make/model from URL
  if (!vehicle.make) {
    const makeMatch = url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/([a-z0-9-]+)/i);
    if (makeMatch) vehicle.make = makeMatch[1].replace(/-/g, ' ');
  }
  if (!vehicle.model) {
    const modelMatch = url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/[a-z0-9-]+\/([a-z0-9-]+)/i);
    if (modelMatch) vehicle.model = modelMatch[1].replace(/-/g, ' ');
  }

  // Extract price
  if (!vehicle.price_aed) {
    const priceEl = document.querySelector('[data-testid="listing-price"]');
    if (priceEl) {
      vehicle.price_aed = Extractors.parsePrice(priceEl.textContent || '');
    }
  }

  // Extract description
  if (!vehicle.description) {
    const descEl = document.querySelector('[data-testid="description"]');
    if (descEl) {
      const cloned = descEl.cloneNode(true);
      Array.from(cloned.querySelectorAll('button')).forEach((btn) => btn.remove());
      let text = cloned.textContent || '';
      text = text.replace(/<br\s*\/?>/gi, '\n').replace(/<!--.*?-->/g, '').trim();
      if (text && text.length > 0) vehicle.description = text;
    }
  }

  // Extract posted date
  if (!vehicle.posted_on) {
    const postEl = document.querySelector('[data-testid="posted-on"]');
    if (postEl) {
      let text = postEl.textContent || '';
      text = text.replace(/^posted\s+on\s*:?\s*/i, '').trim();
      if (text && text.length > 0) vehicle.posted_on = text;
    }
  }

  // Extract listing ID
  const listingIdMatch = url.match(/-([a-f0-9]{32})/i);
  const listingId = listingIdMatch ? listingIdMatch[1].toLowerCase() : null;

  return {
    schema_version: 'navracar.capture.v1',
    source: 'dubizzle',
    source_url: url,
    source_listing_id: listingId,
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle,
    images: Extractors.extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: generateDiagnostics(vehicle),
  };
}

// DubiCars extraction
function captureDubiCars() {
  const url = window.location.href;
  const jsonLd = Extractors.extractJsonLd();

  const vehicle = {
    title: Extractors.trySelectors(['h1']),
    make: Extractors.extractTextFrom('[data-field="make"]'),
    model: Extractors.extractTextFrom('[data-field="model"]'),
    year: Extractors.extractTextFrom('[data-field="year"]'),
    price_aed: null,
    mileage_km: Extractors.extractTextFrom('[data-field="mileage"]'),
    fuel_type: Extractors.extractTextFrom('[data-field="fuel"]'),
    transmission: Extractors.extractTextFrom('[data-field="transmission"]'),
    body_type: Extractors.extractTextFrom('[data-field="body"]'),
    color: Extractors.extractTextFrom('[data-field="color"]'),
    seller_type: Extractors.extractTextFrom('[data-field="seller"]'),
    description: Extractors.extractTextFrom('[class*="description"]'),
    engine: Extractors.extractTextFrom('[data-field="engine"]'),
  };

  // Try JSON-LD
  if (jsonLd) {
    if (jsonLd.name && !vehicle.title) vehicle.title = jsonLd.name;
    if (jsonLd.brand && !vehicle.make) {
      vehicle.make = typeof jsonLd.brand === 'object' ? jsonLd.brand.name : jsonLd.brand;
    }
    if (jsonLd.model && !vehicle.model) vehicle.model = jsonLd.model;
    if (jsonLd.description && !vehicle.description) vehicle.description = jsonLd.description;
    if (jsonLd.offers?.price && !vehicle.price_aed) {
      vehicle.price_aed = Extractors.parsePrice(String(jsonLd.offers.price));
    }
  }

  // Extract price
  if (!vehicle.price_aed) {
    const priceEl = document.querySelector('[data-field="price"]');
    if (priceEl) {
      vehicle.price_aed = Extractors.parsePrice(priceEl.textContent || '');
    }
  }

  // Extract listing ID
  const listingIdMatch = url.match(/\/(?:car|listing)\/(\d+)/i);
  const listingId = listingIdMatch ? listingIdMatch[1] : null;

  return {
    schema_version: 'navracar.capture.v1',
    source: 'dubicars',
    source_url: url,
    source_listing_id: listingId,
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle,
    images: Extractors.extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: generateDiagnostics(vehicle),
  };
}

// YallaMotor extraction
function captureYallaMotor() {
  const url = window.location.href;
  const jsonLd = Extractors.extractJsonLd();

  const vehicle = {
    title: Extractors.trySelectors(['h1']),
    make: null,
    model: null,
    year: null,
    price_aed: null,
    mileage_km: null,
    fuel_type: null,
    transmission: null,
    body_type: null,
    color: null,
    seller_type: null,
    description: Extractors.trySelectors(['[class*="description"]', '[class*="detail"]']),
    engine: null,
  };

  // Try structured data first
  if (jsonLd) {
    if (jsonLd.name && !vehicle.title) vehicle.title = jsonLd.name;
    if (jsonLd.brand && !vehicle.make) {
      vehicle.make = typeof jsonLd.brand === 'object' ? jsonLd.brand.name : jsonLd.brand;
    }
    if (jsonLd.model && !vehicle.model) vehicle.model = jsonLd.model;
    if (jsonLd.description && !vehicle.description) vehicle.description = jsonLd.description;
    if (jsonLd.offers?.price && !vehicle.price_aed) {
      vehicle.price_aed = Extractors.parsePrice(String(jsonLd.offers.price));
    }
  }

  // Try data attributes
  if (!vehicle.make) vehicle.make = Extractors.extractAttrFrom('[data-make]', 'data-make');
  if (!vehicle.model) vehicle.model = Extractors.extractAttrFrom('[data-model]', 'data-model');
  if (!vehicle.year) vehicle.year = Extractors.extractAttrFrom('[data-year]', 'data-year');
  if (!vehicle.mileage_km) vehicle.mileage_km = Extractors.extractAttrFrom('[data-mileage]', 'data-mileage');
  if (!vehicle.fuel_type) vehicle.fuel_type = Extractors.extractAttrFrom('[data-fuel]', 'data-fuel');
  if (!vehicle.transmission) vehicle.transmission = Extractors.extractAttrFrom('[data-transmission]', 'data-transmission');
  if (!vehicle.body_type) vehicle.body_type = Extractors.extractAttrFrom('[data-body]', 'data-body');
  if (!vehicle.color) vehicle.color = Extractors.extractAttrFrom('[data-color]', 'data-color');
  if (!vehicle.engine) vehicle.engine = Extractors.extractAttrFrom('[data-engine]', 'data-engine');

  // Extract price from price selector
  if (!vehicle.price_aed) {
    const priceEl = document.querySelector('[class*="price"]');
    if (priceEl) {
      vehicle.price_aed = Extractors.parsePrice(priceEl.textContent || '');
    }
  }

  // Extract listing ID
  const listingIdMatch = url.match(/\/(?:car|listing)\/(\d+)/i);
  const listingId = listingIdMatch ? listingIdMatch[1] : null;

  return {
    schema_version: 'navracar.capture.v1',
    source: 'yallamotor',
    source_url: url,
    source_listing_id: listingId,
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle,
    images: Extractors.extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: generateDiagnostics(vehicle),
  };
}

// Generate diagnostic report
function generateDiagnostics(vehicle) {
  const diagnostics = {};
  for (const [key, value] of Object.entries(vehicle)) {
    if (typeof value === 'string' || typeof value === 'number') {
      diagnostics[key] = {
        found: !!value,
        confidence: value ? 'high' : 'low',
      };
    }
  }
  return diagnostics;
}
