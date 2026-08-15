console.log('[Navra Capture] Content script loaded');

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

  // Try JSON-LD first
  const jsonLd = helpers.extractFromJsonLd();

  const extractField = (jsonLdKey, metaKey, microdata, siteSelectors) => {
    // Try JSON-LD
    if (jsonLd && jsonLdKey && jsonLd[jsonLdKey]) return jsonLd[jsonLdKey];

    // Try meta tags
    if (metaKey) {
      const meta = helpers.extractFromMeta(metaKey);
      if (meta) return meta;
    }

    // Try microdata
    if (microdata) {
      const micro = helpers.extractFromMicrodata(microdata);
      if (micro) return micro;
    }

    // Fall back to site-specific selectors
    if (siteSelectors) {
      return helpers.trySelectors(siteSelectors);
    }

    return null;
  };

  const extractListingId = () => {
    const match = url.match(/-([a-f0-9]{32})/i);
    return match ? match[1] : null;
  };

  const extractPrice = () => {
    // Try JSON-LD
    if (jsonLd && jsonLd.price) {
      return helpers.parsePrice(jsonLd.price.toString());
    }

    // Try meta/microdata
    let priceStr = helpers.extractFromMeta('price') || helpers.extractFromMicrodata('price');
    if (priceStr) return helpers.parsePrice(priceStr);

    // Fall back to Dubizzle testid
    const el = document.querySelector('[data-testid="listing-price"]');
    if (el) return helpers.parsePrice(el.textContent);

    return null;
  };

  const extractDescription = () => {
    // Try JSON-LD
    if (jsonLd && jsonLd.description) return jsonLd.description;

    // Try microdata
    const micro = helpers.extractFromMicrodata('description');
    if (micro) return micro;

    // Fall back to Dubizzle testid
    const el = document.querySelector('[data-testid="description"]');
    if (el) {
      let text = el.textContent || '';
      text = text.replace(/<!--.*?-->/g, '').replace(/<br\s*\/?>/gi, '\n').trim();
      return text || null;
    }

    return null;
  };

  return {
    schema_version: 'navracar.capture.v1',
    source: 'dubizzle',
    source_url: url,
    source_listing_id: extractListingId(),
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle: {
      title: extractField('name', 'product_title', 'name', ['[data-testid="listing-name"]', 'h1']),
      make: extractField('brand', 'brand', 'brand', null) || url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/([a-z0-9-]+)/i)?.[1],
      model: extractField('model', 'model', 'model', null) || url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/[a-z0-9-]+\/([a-z0-9-]+)/i)?.[1],
      year: extractField(null, 'production_date', 'year', ['[data-testid="listing-year-value"]']),
      price_aed: extractPrice(),
      mileage_km: extractField(null, 'mileage', 'mileage', ['[data-testid="listing-kilometers-value"]']),
      fuel_type: extractField(null, 'fuel_type', 'fuelType', ['[data-testid="overview-fuel_type-value"]']),
      transmission: extractField(null, 'transmission', 'transmission', ['[data-testid="overview-transmission_type-value"]']),
      body_type: extractField(null, 'body_type', 'bodyType', ['[data-testid="overview-body_type-value"]']),
      regional_specs: extractField(null, null, null, ['[data-testid="listing-regional_specs-value"]']),
      steering_side: extractField(null, null, null, ['[data-testid="listing-steering_side-value"]']),
      exterior_color: extractField(null, 'color', 'color', ['[data-testid="overview-exterior_color-value"]']),
      interior_color: extractField(null, null, null, ['[data-testid="overview-interior_color-value"]']),
      seller_type: extractField(null, null, null, ['[data-testid="overview-seller_type-value"]']),
      warranty: extractField(null, null, null, ['[data-testid="overview-warranty-value"]']),
      horsepower: extractField(null, 'horsepower', 'horsepower', ['[data-testid="overview-horsepower-value"]']),
      no_of_cylinders: extractField(null, null, null, ['[data-testid="overview-no_of_cylinders-value"]']),
      doors: extractField(null, 'doors', 'numberOfDoors', ['[data-testid="overview-doors-value"]']),
      seating_capacity: extractField(null, 'seating', 'seatingCapacity', ['[data-testid="overview-seating_capacity-value"]']),
      engine: extractField(null, 'engine', 'engineCapacity', ['[data-testid="overview-engine_capacity_cc-value"]']),
      trim: extractField(null, 'trim', 'trim', ['[data-testid="overview-motors_trim-value"]']),
      description: extractDescription(),
      posted_on: extractField(null, 'date_published', 'datePublished', ['[data-testid="posted-on"]']),
    },
    images: helpers.extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: {},
  };
}

function captureDubiCars() {
  const helpers = ExtractionHelpers;
  const url = window.location.href;

  // Try JSON-LD first
  const jsonLd = helpers.extractFromJsonLd();

  const extractField = (jsonLdKey, metaKey, microdata, siteSelectors) => {
    // Try JSON-LD
    if (jsonLd && jsonLdKey && jsonLd[jsonLdKey]) return jsonLd[jsonLdKey];

    // Try meta tags
    if (metaKey) {
      const meta = helpers.extractFromMeta(metaKey);
      if (meta) return meta;
    }

    // Try microdata
    if (microdata) {
      const micro = helpers.extractFromMicrodata(microdata);
      if (micro) return micro;
    }

    // Fall back to site-specific selectors
    if (siteSelectors) {
      return helpers.trySelectors(siteSelectors);
    }

    return null;
  };

  const extractListingId = () => {
    const match = url.match(/\/(?:car|listing)\/(\d+)/i);
    return match ? match[1] : null;
  };

  const extractPrice = () => {
    // Try JSON-LD
    if (jsonLd && jsonLd.price) {
      return helpers.parsePrice(jsonLd.price.toString());
    }

    // Try meta/microdata
    let priceStr = helpers.extractFromMeta('price') || helpers.extractFromMicrodata('price');
    if (priceStr) return helpers.parsePrice(priceStr);

    // Fall back to DubiCars data-field
    return helpers.parsePrice(document.querySelector('[data-field="price"]')?.textContent);
  };

  return {
    schema_version: 'navracar.capture.v1',
    source: 'dubicars',
    source_url: url,
    source_listing_id: extractListingId(),
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle: {
      title: extractField('name', 'product_title', 'name', ['h1']),
      make: extractField('brand', 'brand', 'brand', ['[data-field="make"]']),
      model: extractField('model', 'model', 'model', ['[data-field="model"]']),
      year: extractField(null, 'production_date', 'year', ['[data-field="year"]']),
      price_aed: extractPrice(),
      mileage_km: extractField(null, 'mileage', 'mileage', ['[data-field="mileage"]']),
      fuel_type: extractField(null, 'fuel_type', 'fuelType', ['[data-field="fuel"]']),
      transmission: extractField(null, 'transmission', 'transmission', ['[data-field="transmission"]']),
      body_type: extractField(null, 'body_type', 'bodyType', ['[data-field="body"]']),
      color: extractField(null, 'color', 'color', ['[data-field="color"]']),
      seller_type: extractField(null, 'seller_type', 'seller', ['[data-field="seller"]']),
      description: extractField('description', 'description', 'description', ['[class*="description"]']),
      engine: extractField(null, 'engine', 'engineCapacity', ['[data-field="engine"]']),
    },
    images: helpers.extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: {},
  };
}

function captureYallaMotor() {
  const helpers = ExtractionHelpers;
  const url = window.location.href;

  // Try JSON-LD first
  const jsonLd = helpers.extractFromJsonLd();

  const extractField = (jsonLdKey, metaKey, microdata, siteSelectors) => {
    // Try JSON-LD
    if (jsonLd && jsonLdKey && jsonLd[jsonLdKey]) return jsonLd[jsonLdKey];

    // Try meta tags
    if (metaKey) {
      const meta = helpers.extractFromMeta(metaKey);
      if (meta) return meta;
    }

    // Try microdata
    if (microdata) {
      const micro = helpers.extractFromMicrodata(microdata);
      if (micro) return micro;
    }

    // Fall back to site-specific selectors
    if (siteSelectors) {
      return helpers.trySelectors(siteSelectors);
    }

    return null;
  };

  const extractListingId = () => {
    const match = url.match(/\/(?:car|listing)\/(\d+)/i);
    return match ? match[1] : null;
  };

  const extractPrice = () => {
    // Try JSON-LD
    if (jsonLd && jsonLd.price) {
      return helpers.parsePrice(jsonLd.price.toString());
    }

    // Try meta/microdata
    let priceStr = helpers.extractFromMeta('price') || helpers.extractFromMicrodata('price');
    if (priceStr) return helpers.parsePrice(priceStr);

    // Fall back to YallaMotor price class
    return helpers.parsePrice(document.querySelector('[class*="price"]')?.textContent);
  };

  return {
    schema_version: 'navracar.capture.v1',
    source: 'yallamotor',
    source_url: url,
    source_listing_id: extractListingId(),
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle: {
      title: extractField('name', 'product_title', 'name', ['h1']),
      make: extractField('brand', 'brand', 'brand', null),
      model: extractField('model', 'model', 'model', null),
      year: extractField(null, 'production_date', 'year', null),
      price_aed: extractPrice(),
      mileage_km: extractField(null, 'mileage', 'mileage', null),
      fuel_type: extractField(null, 'fuel_type', 'fuelType', null),
      transmission: extractField(null, 'transmission', 'transmission', null),
      body_type: extractField(null, 'body_type', 'bodyType', null),
      color: extractField(null, 'color', 'color', null),
      description: extractField('description', 'description', 'description', ['[class*="description"]']),
      engine: extractField(null, 'engine', 'engineCapacity', null),
    },
    images: helpers.extractImages().map((url) => ({ url, confidence: 'high' })),
    diagnostics: {},
  };
}
