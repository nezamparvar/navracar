console.log('[Navra Capture] Content script loaded');

// Diagnostic categories
const DIAGNOSTICS = {
  UNSUPPORTED_PAGE: 'صفحه پشتیبانی‌نشده است',
  LISTING_NOT_DETECTED: 'آگهی شناسایی نشد',
  STRUCTURED_DATA_NOT_FOUND: 'داده ساختاری پیدا نشد',
  MINIMUM_FIELDS_MISSING: 'فیلدهای الزامی موجود نیستند',
  EXTRACTION_PARTIAL: 'برخی از فیلدها کامل استخراج نشدند',
};

// Safe diagnostic tracking
class DiagnosticTracker {
  constructor() {
    this.records = {};
  }

  recordExtraction(field, found, source, confidence = 'high') {
    this.records[field] = {
      found,
      source, // 'json-ld', 'meta', 'microdata', 'selector'
      confidence, // 'high', 'medium', 'low'
      extracted_at: new Date().toISOString(),
    };
  }

  getReport() {
    return this.records;
  }

  isSafe() {
    // Never include sensitive data
    const sensitiveKeys = ['token', 'password', 'auth', 'secret', 'key', 'credential'];
    for (const key in this.records) {
      if (sensitiveKeys.some(s => key.toLowerCase().includes(s))) {
        return false;
      }
    }
    return true;
  }
}

// Extraction priority helpers: JSON-LD → structured data → semantic DOM → site-specific
const ExtractionHelpers = {
  // Extract JSON-LD structured data
  extractFromJsonLd(selector = 'script[type="application/ld+json"]') {
    const scripts = Array.from(document.querySelectorAll(selector));
    for (const script of scripts) {
      try {
        const data = JSON.parse(script.textContent);
        if (data['@type'] && (data['@type'].includes('Product') || data['@type'].includes('Vehicle'))) {
          return data;
        }
      } catch (e) {
        continue;
      }
    }
    return null;
  },

  // Extract from Open Graph and meta tags
  extractFromMeta(property) {
    const og = document.querySelector(`meta[property="og:${property}"]`);
    if (og) return og.getAttribute('content');
    const meta = document.querySelector(`meta[name="${property}"]`);
    if (meta) return meta.getAttribute('content');
    return null;
  },

  // Extract from microdata attributes
  extractFromMicrodata(itemProp) {
    const el = document.querySelector(`[itemprop="${itemProp}"]`);
    if (el) {
      if (el.tagName === 'META') return el.getAttribute('content');
      if (el.tagName === 'A') return el.getAttribute('href');
      if (el.tagName === 'IMG') return el.getAttribute('src');
      return el.textContent?.trim();
    }
    return null;
  },

  // Try multiple selectors and return first non-null result
  trySelectors(selectors) {
    for (const selector of selectors) {
      const el = document.querySelector(selector);
      if (el) {
        if (el.tagName === 'META') return el.getAttribute('content');
        if (el.tagName === 'A') return el.getAttribute('href');
        if (el.tagName === 'IMG') return el.getAttribute('src');
        return el.textContent?.trim();
      }
    }
    return null;
  },

  // Clean and normalize price strings
  parsePrice(priceStr) {
    if (!priceStr) return null;
    const match = priceStr.match(/([\d,]+)/);
    return match ? parseFloat(match[1].replace(/,/g, '')) : null;
  },

  // Extract all images with deduplication
  extractImages() {
    const images = [];
    const seen = new Set();
    const prioritySelectors = [
      'picture img',
      'img[data-testid*="image"]',
      'img[class*="gallery"]',
      'img[src*="listing"]',
      'img',
    ];

    for (const selector of prioritySelectors) {
      document.querySelectorAll(selector).forEach((img) => {
        const src = img.src || img.getAttribute('data-src');
        if (src && !seen.has(src) && src.includes('http')) {
          seen.add(src);
          images.push(src);
        }
      });
    }

    return images;
  },
};

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
  const helpers = ExtractionHelpers;
  const url = window.location.href;
  const diagnostics = new DiagnosticTracker();

  // Try JSON-LD first
  const jsonLd = helpers.extractFromJsonLd();

  const extractField = (field, jsonLdKey, metaKey, microdata, siteSelectors) => {
    let source = 'unknown';
    let found = false;
    let value = null;

    // Try JSON-LD
    if (jsonLd && jsonLdKey && jsonLd[jsonLdKey]) {
      value = jsonLd[jsonLdKey];
      source = 'json-ld';
      found = true;
    }
    // Try meta tags
    else if (metaKey) {
      const meta = helpers.extractFromMeta(metaKey);
      if (meta) {
        value = meta;
        source = 'meta';
        found = true;
      }
    }
    // Try microdata
    if (!found && microdata) {
      const micro = helpers.extractFromMicrodata(microdata);
      if (micro) {
        value = micro;
        source = 'microdata';
        found = true;
      }
    }
    // Fall back to site-specific selectors
    if (!found && siteSelectors) {
      value = helpers.trySelectors(siteSelectors);
      if (value) {
        source = 'selector';
        found = true;
      }
    }

    diagnostics.recordExtraction(field, found, source, found ? 'high' : 'low');
    return value;
  };

  const extractListingId = () => {
    const match = url.match(/-([a-f0-9]{32})/i);
    return match ? match[1] : null;
  };

  const extractPrice = () => {
    let source = 'unknown';
    let found = false;
    let value = null;

    // Try JSON-LD
    if (jsonLd && jsonLd.price) {
      value = helpers.parsePrice(jsonLd.price.toString());
      if (value) {
        source = 'json-ld';
        found = true;
      }
    }

    // Try meta/microdata
    if (!found) {
      let priceStr = helpers.extractFromMeta('price') || helpers.extractFromMicrodata('price');
      if (priceStr) {
        value = helpers.parsePrice(priceStr);
        source = priceStr === helpers.extractFromMeta('price') ? 'meta' : 'microdata';
        found = true;
      }
    }

    // Fall back to Dubizzle testid
    if (!found) {
      const el = document.querySelector('[data-testid="listing-price"]');
      if (el) {
        value = helpers.parsePrice(el.textContent);
        if (value) {
          source = 'selector';
          found = true;
        }
      }
    }

    diagnostics.recordExtraction('price_aed', found, source, found ? 'high' : 'low');
    return value;
  };

  const extractDescription = () => {
    let source = 'unknown';
    let found = false;
    let value = null;

    // Try JSON-LD
    if (jsonLd && jsonLd.description) {
      value = jsonLd.description;
      source = 'json-ld';
      found = true;
    }

    // Try microdata
    if (!found) {
      const micro = helpers.extractFromMicrodata('description');
      if (micro) {
        value = micro;
        source = 'microdata';
        found = true;
      }
    }

    // Fall back to Dubizzle testid
    if (!found) {
      const el = document.querySelector('[data-testid="description"]');
      if (el) {
        let text = el.textContent || '';
        text = text.replace(/<!--.*?-->/g, '').replace(/<br\s*\/?>/gi, '\n').trim();
        if (text) {
          value = text;
          source = 'selector';
          found = true;
        }
      }
    }

    diagnostics.recordExtraction('description', found, source, found ? 'high' : 'low');
    return value;
  };

  return {
    schema_version: 'navracar.capture.v1',
    source: 'dubizzle',
    source_url: url,
    source_listing_id: extractListingId(),
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle: {
      title: extractField('title', 'product_title', 'name', ['[data-testid="listing-name"]', 'h1']),
      make: extractField('make', 'brand', 'brand', null) || url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/([a-z0-9-]+)/i)?.[1],
      model: extractField('model', 'model', 'model', null) || url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/[a-z0-9-]+\/([a-z0-9-]+)/i)?.[1],
      year: extractField('year', 'production_date', 'year', ['[data-testid="listing-year-value"]']),
      price_aed: extractPrice(),
      mileage_km: extractField('mileage_km', 'mileage', 'mileage', ['[data-testid="listing-kilometers-value"]']),
      fuel_type: extractField('fuel_type', 'fuel_type', 'fuelType', ['[data-testid="overview-fuel_type-value"]']),
      transmission: extractField('transmission', 'transmission', 'transmission', ['[data-testid="overview-transmission_type-value"]']),
      body_type: extractField('body_type', 'body_type', 'bodyType', ['[data-testid="overview-body_type-value"]']),
      regional_specs: extractField('regional_specs', null, null, ['[data-testid="listing-regional_specs-value"]']),
      steering_side: extractField('steering_side', null, null, ['[data-testid="listing-steering_side-value"]']),
      exterior_color: extractField('exterior_color', 'color', 'color', ['[data-testid="overview-exterior_color-value"]']),
      interior_color: extractField('interior_color', null, null, ['[data-testid="overview-interior_color-value"]']),
      seller_type: extractField('seller_type', null, null, ['[data-testid="overview-seller_type-value"]']),
      warranty: extractField('warranty', null, null, ['[data-testid="overview-warranty-value"]']),
      horsepower: extractField('horsepower', 'horsepower', 'horsepower', ['[data-testid="overview-horsepower-value"]']),
      no_of_cylinders: extractField('no_of_cylinders', null, null, ['[data-testid="overview-no_of_cylinders-value"]']),
      doors: extractField('doors', 'doors', 'numberOfDoors', ['[data-testid="overview-doors-value"]']),
      seating_capacity: extractField('seating_capacity', 'seating', 'seatingCapacity', ['[data-testid="overview-seating_capacity-value"]']),
      engine: extractField('engine', 'engine', 'engineCapacity', ['[data-testid="overview-engine_capacity_cc-value"]']),
      trim: extractField('trim', 'trim', 'trim', ['[data-testid="overview-motors_trim-value"]']),
      description: extractDescription(),
      posted_on: extractField('posted_on', 'date_published', 'datePublished', ['[data-testid="posted-on"]']),
    },
    images: helpers.extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: diagnostics.isSafe() ? diagnostics.getReport() : {},
  };
}

function captureDubiCars() {
  const helpers = ExtractionHelpers;
  const url = window.location.href;
  const diagnostics = new DiagnosticTracker();

  // Try JSON-LD first
  const jsonLd = helpers.extractFromJsonLd();

  const extractField = (field, jsonLdKey, metaKey, microdata, siteSelectors) => {
    let source = 'unknown';
    let found = false;
    let value = null;

    // Try JSON-LD
    if (jsonLd && jsonLdKey && jsonLd[jsonLdKey]) {
      value = jsonLd[jsonLdKey];
      source = 'json-ld';
      found = true;
    }
    // Try meta tags
    else if (metaKey) {
      const meta = helpers.extractFromMeta(metaKey);
      if (meta) {
        value = meta;
        source = 'meta';
        found = true;
      }
    }
    // Try microdata
    if (!found && microdata) {
      const micro = helpers.extractFromMicrodata(microdata);
      if (micro) {
        value = micro;
        source = 'microdata';
        found = true;
      }
    }
    // Fall back to site-specific selectors
    if (!found && siteSelectors) {
      value = helpers.trySelectors(siteSelectors);
      if (value) {
        source = 'selector';
        found = true;
      }
    }

    diagnostics.recordExtraction(field, found, source, found ? 'high' : 'low');
    return value;
  };

  const extractListingId = () => {
    const match = url.match(/\/(?:car|listing)\/(\d+)/i);
    return match ? match[1] : null;
  };

  const extractPrice = () => {
    let source = 'unknown';
    let found = false;
    let value = null;

    // Try JSON-LD
    if (jsonLd && jsonLd.price) {
      value = helpers.parsePrice(jsonLd.price.toString());
      if (value) {
        source = 'json-ld';
        found = true;
      }
    }

    // Try meta/microdata
    if (!found) {
      let priceStr = helpers.extractFromMeta('price') || helpers.extractFromMicrodata('price');
      if (priceStr) {
        value = helpers.parsePrice(priceStr);
        source = priceStr === helpers.extractFromMeta('price') ? 'meta' : 'microdata';
        found = true;
      }
    }

    // Fall back to DubiCars data-field
    if (!found) {
      const el = document.querySelector('[data-field="price"]');
      if (el) {
        value = helpers.parsePrice(el.textContent);
        if (value) {
          source = 'selector';
          found = true;
        }
      }
    }

    diagnostics.recordExtraction('price_aed', found, source, found ? 'high' : 'low');
    return value;
  };

  return {
    schema_version: 'navracar.capture.v1',
    source: 'dubicars',
    source_url: url,
    source_listing_id: extractListingId(),
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle: {
      title: extractField('title', 'name', 'product_title', 'name', ['h1']),
      make: extractField('make', 'brand', 'brand', 'brand', ['[data-field="make"]']),
      model: extractField('model', 'model', 'model', 'model', ['[data-field="model"]']),
      year: extractField('year', null, 'production_date', 'year', ['[data-field="year"]']),
      price_aed: extractPrice(),
      mileage_km: extractField('mileage_km', null, 'mileage', 'mileage', ['[data-field="mileage"]']),
      fuel_type: extractField('fuel_type', null, 'fuel_type', 'fuelType', ['[data-field="fuel"]']),
      transmission: extractField('transmission', null, 'transmission', 'transmission', ['[data-field="transmission"]']),
      body_type: extractField('body_type', null, 'body_type', 'bodyType', ['[data-field="body"]']),
      color: extractField('color', null, 'color', 'color', ['[data-field="color"]']),
      seller_type: extractField('seller_type', null, 'seller_type', 'seller', ['[data-field="seller"]']),
      description: extractField('description', 'description', 'description', 'description', ['[class*="description"]']),
      engine: extractField('engine', null, 'engine', 'engineCapacity', ['[data-field="engine"]']),
    },
    images: helpers.extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: diagnostics.isSafe() ? diagnostics.getReport() : {},
  };
}

function captureYallaMotor() {
  const helpers = ExtractionHelpers;
  const url = window.location.href;
  const diagnostics = new DiagnosticTracker();

  // Try JSON-LD first
  const jsonLd = helpers.extractFromJsonLd();

  const extractField = (field, jsonLdKey, metaKey, microdata, siteSelectors) => {
    let source = 'unknown';
    let found = false;
    let value = null;

    // Try JSON-LD
    if (jsonLd && jsonLdKey && jsonLd[jsonLdKey]) {
      value = jsonLd[jsonLdKey];
      source = 'json-ld';
      found = true;
    }
    // Try meta tags
    else if (metaKey) {
      const meta = helpers.extractFromMeta(metaKey);
      if (meta) {
        value = meta;
        source = 'meta';
        found = true;
      }
    }
    // Try microdata
    if (!found && microdata) {
      const micro = helpers.extractFromMicrodata(microdata);
      if (micro) {
        value = micro;
        source = 'microdata';
        found = true;
      }
    }
    // Fall back to site-specific selectors
    if (!found && siteSelectors) {
      value = helpers.trySelectors(siteSelectors);
      if (value) {
        source = 'selector';
        found = true;
      }
    }

    diagnostics.recordExtraction(field, found, source, found ? 'high' : 'low');
    return value;
  };

  const extractListingId = () => {
    const match = url.match(/\/(?:car|listing)\/(\d+)/i);
    return match ? match[1] : null;
  };

  const extractPrice = () => {
    let source = 'unknown';
    let found = false;
    let value = null;

    // Try JSON-LD
    if (jsonLd && jsonLd.price) {
      value = helpers.parsePrice(jsonLd.price.toString());
      if (value) {
        source = 'json-ld';
        found = true;
      }
    }

    // Try meta/microdata
    if (!found) {
      let priceStr = helpers.extractFromMeta('price') || helpers.extractFromMicrodata('price');
      if (priceStr) {
        value = helpers.parsePrice(priceStr);
        source = priceStr === helpers.extractFromMeta('price') ? 'meta' : 'microdata';
        found = true;
      }
    }

    // Fall back to YallaMotor price class
    if (!found) {
      const el = document.querySelector('[class*="price"]');
      if (el) {
        value = helpers.parsePrice(el.textContent);
        if (value) {
          source = 'selector';
          found = true;
        }
      }
    }

    diagnostics.recordExtraction('price_aed', found, source, found ? 'high' : 'low');
    return value;
  };

  return {
    schema_version: 'navracar.capture.v1',
    source: 'yallamotor',
    source_url: url,
    source_listing_id: extractListingId(),
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle: {
      title: extractField('title', 'name', 'product_title', 'name', ['h1']),
      make: extractField('make', 'brand', 'brand', 'brand', null),
      model: extractField('model', 'model', 'model', 'model', null),
      year: extractField('year', null, 'production_date', 'year', null),
      price_aed: extractPrice(),
      mileage_km: extractField('mileage_km', null, 'mileage', 'mileage', null),
      fuel_type: extractField('fuel_type', null, 'fuel_type', 'fuelType', null),
      transmission: extractField('transmission', null, 'transmission', 'transmission', null),
      body_type: extractField('body_type', null, 'body_type', 'bodyType', null),
      color: extractField('color', null, 'color', 'color', null),
      description: extractField('description', 'description', 'description', 'description', ['[class*="description"]']),
      engine: extractField('engine', null, 'engine', 'engineCapacity', null),
    },
    images: helpers.extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: diagnostics.isSafe() ? diagnostics.getReport() : {},
  };
}
