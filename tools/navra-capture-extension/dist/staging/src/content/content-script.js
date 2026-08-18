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
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'captureCurrentPage') {
    (async () => {
      try {
        await waitForPageReady();
        const payload = captureCurrentPagePayload();
        sendResponse({ status: 'success', payload });
      } catch (error) {
        console.error('[Navra Capture] Capture error:', error);
        sendResponse({ status: 'error', error: error.message });
      }
    })();
    return true; // Indicate async response
  } else if (request.action === 'canCapture') {
    sendResponse({ canCapture: canCaptureCurrentPage() });
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

function captureCurrentPagePayload() {
  const source = getMarketplaceSource();
  if (!source) throw new Error('Unsupported marketplace');

  let payload = null;
  if (source === 'dubizzle') {
    payload = captureDubizzle();
  } else if (source === 'dubicars') {
    payload = captureDubiCars();
  } else if (source === 'yallamotor') {
    payload = captureYallaMotor();
  }

  if (!payload) throw new Error('Failed to capture listing data');

  return payload;
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
        const roots = Array.isArray(data) ? data : [data];
        const nodes = roots.flatMap((root) => {
          const graph = Array.isArray(root?.['@graph']) ? root['@graph'] : [];
          const mainEntity = root?.mainEntity?.itemOffered || root?.mainEntity;
          return [root, ...graph, mainEntity].filter(Boolean);
        });
        for (const node of nodes) {
          const types = Array.isArray(node['@type']) ? node['@type'] : [node['@type']];
          const type = types.filter(Boolean).join(' ').toLowerCase();
          if (type.includes('vehicle') || type.includes('car') || type.includes('product') || node['offers']) {
            return node;
          }
        }
      } catch (e) {
        // Skip invalid JSON
      }
    }
    return null;
  },

  schemaText(value) {
    if (value === null || value === undefined) return null;
    if (typeof value === 'string' || typeof value === 'number') return String(value);
    if (value.name !== undefined) return String(value.name);
    if (value.value !== undefined) return String(value.value);
    return null;
  },

  schemaReferenceLabel(value) {
    const direct = this.schemaText(value);
    if (direct) return direct;
    if (!value?.['@id']) return null;

    try {
      const url = new URL(value['@id']);
      const segment = url.pathname.split('/').filter(Boolean).pop();
      if (!segment) return null;
      return segment
        .split('-')
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
    } catch (e) {
      return null;
    }
  },

  extractVehicleSchema(node) {
    if (!node) return {};
    const engine = node.vehicleEngine?.engineDisplacement || node.vehicleEngine;

    return {
      title: this.schemaText(node.name),
      make: this.schemaReferenceLabel(node.brand),
      model: this.schemaReferenceLabel(node.model),
      year: this.schemaText(node.vehicleModelDate),
      price_aed: this.parsePrice(this.schemaText(node.offers?.price)),
      mileage_km: this.schemaText(node.mileageFromOdometer),
      fuel_type: this.schemaText(node.fuelType || node.vehicleEngine?.fuelType),
      transmission: this.schemaText(node.vehicleTransmission),
      body_type: this.schemaText(node.bodyType),
      color: this.schemaText(node.color),
      description: this.schemaText(node.description),
      engine: this.schemaText(engine),
    };
  },

  extractSchemaImages(node) {
    const values = Array.isArray(node?.image) ? node.image : [node?.image];

    return values
      .map((image) => typeof image === 'string' ? image : image?.url || image?.contentUrl)
      .filter(Boolean);
  },

  buildVehicleTitle(vehicle, fallback = null) {
    const makeModel = [vehicle.year, vehicle.make, vehicle.model, vehicle.trim]
      .map((value) => typeof value === 'string' ? value.trim() : value)
      .filter(Boolean)
      .join(' ');

    return makeModel || fallback || null;
  },

  extractListingId(url) {
    const routeMatch = url.match(/\/(?:car|listing)\/(\d+)(?:[/?#]|$)/i);
    if (routeMatch) return routeMatch[1];

    const slugMatch = url.match(/-(\d+)(?:\.html)?\/?(?:[?#].*)?$/i);
    return slugMatch ? slugMatch[1] : null;
  },

  extractDubizzleNextDataImages(listingId) {
    if (!listingId) return [];

    const script = document.getElementById('__NEXT_DATA__');
    if (!script?.textContent) return [];

    try {
      const root = JSON.parse(script.textContent);
      const normalizedListingId = String(listingId).toLowerCase();
      const results = [];
      const seenNodes = new Set();
      const photoUrl = (photo) => {
        if (typeof photo === 'string') return photo;
        if (!photo || typeof photo !== 'object') return null;
        return photo.url || photo.src || photo.image_url || photo.imageUrl || null;
      };

      const visit = (node) => {
        if (!node || typeof node !== 'object' || seenNodes.has(node)) return;
        seenNodes.add(node);

        const nodeId = node.uuid || node.id || node.listing_id || node.listingId;
        if (nodeId && String(nodeId).toLowerCase() === normalizedListingId) {
          const photos = Array.isArray(node.photos)
            ? node.photos
            : (Array.isArray(node.photos_combined) ? node.photos_combined : []);
          photos.map(photoUrl).filter(Boolean).forEach((url) => results.push(url));
        }

        Object.values(node).forEach(visit);
      };

      visit(root);
      return [...new Set(results)];
    } catch (error) {
      console.error('[Navra Capture] Invalid Dubizzle __NEXT_DATA__:', error);
      return [];
    }
  },

  extractImages(source, listingId = null, preferredImages = []) {
    const images = [];
    const seen = new Set();
    const MAX_IMAGES = 20;

    const selectorsBySource = {
      dubizzle: ['img[data-testid^="dpv-view-"]'],
      dubicars: ['[class*="gallery"] img', '[class*="carousel"] img', 'figure[class*="gallery"] img'],
      yallamotor: ['[class*="gallery"] img', '[class*="carousel"] img', 'img[src*="used_car"]'],
    };
    const selectors = selectorsBySource[source] || [];

    const addImage = (candidate) => {
      if (!candidate || images.length >= MAX_IMAGES) return;
      let src = candidate;

      try {
        let parsed = new URL(src, window.location.href);
        if (source === 'yallamotor' && parsed.hostname.endsWith('yallamotor.com') && parsed.pathname === '/_next/image') {
          const original = parsed.searchParams.get('url');
          if (!original) return;
          parsed = new URL(original);
        }

        const host = parsed.hostname.toLowerCase();
        const allowed = source === 'dubizzle'
          ? host === 'dbz-images.dubizzle.com'
          : source === 'dubicars'
            ? host === 'dubicars.com' || host.endsWith('.dubicars.com')
            : source === 'yallamotor'
              ? host === 'b8cdn.com' || host.endsWith('.b8cdn.com')
              : true;
        if (!allowed) return;
        if (source === 'yallamotor' && listingId && !parsed.href.includes(`/${listingId}/`)) return;
        src = parsed.href;
      } catch (e) {
        return;
      }

      if (seen.has(src) || src.includes('pixel') || src.includes('blank') || src.includes('1x1')) return;
      seen.add(src);
      images.push(src);
    };

    preferredImages.forEach(addImage);

    for (const selector of selectors) {
      if (images.length >= MAX_IMAGES) break;

      document.querySelectorAll(selector).forEach((img) => {
        if (images.length >= MAX_IMAGES) return;

        addImage(img.src || img.getAttribute('data-src') || img.getAttribute('data-srcset'));
      });
    }

    return images;
  },
};

// DubizzleCar extraction
function captureDubizzle() {
  console.log('[Navra Capture] Starting Dubizzle extraction');
  const url = window.location.href;
  const jsonLd = Extractors.extractJsonLd();
  const schema = Extractors.extractVehicleSchema(jsonLd);

  const vehicle = {
    title: Extractors.trySelectors(['[data-testid="listing-sub-heading"]']) || schema.title,
    make: schema.make || null,
    model: schema.model || null,
    year: Extractors.trySelectors(['[data-testid="listing-year-value"]', '[class*="year"]']) || schema.year,
    price_aed: null,
    mileage_km: Extractors.trySelectors(['[data-testid="listing-kilometers-value"]', '[class*="mileage"]', '[class*="kilometers"]']),
    fuel_type: Extractors.trySelectors(['[data-testid="overview-fuel_type-value"]', '[class*="fuel"]']),
    transmission: Extractors.trySelectors(['[data-testid="overview-transmission_type-value"]', '[class*="transmission"]']),
    body_type: Extractors.trySelectors(['[data-testid="overview-body_type-value"]', '[class*="body"]']),
    regional_specs: Extractors.trySelectors(['[data-testid="listing-regional_specs-value"]', '[class*="regional"]']),
    steering_side: Extractors.trySelectors(['[data-testid="listing-steering_side-value"]', '[class*="steering"]']),
    exterior_color: Extractors.trySelectors(['[data-testid="overview-exterior_color-value"]', '[class*="color"]']),
    interior_color: Extractors.trySelectors(['[data-testid="overview-interior_color-value"]', '[class*="interior"]']),
    seller_type: Extractors.trySelectors(['[data-testid="overview-seller_type-value"]', '[class*="seller"]']),
    warranty: Extractors.trySelectors(['[data-testid="overview-warranty-value"]', '[class*="warranty"]']),
    horsepower: Extractors.trySelectors(['[data-testid="overview-horsepower-value"]', '[class*="horsepower"]']),
    no_of_cylinders: Extractors.trySelectors(['[data-testid="overview-no_of_cylinders-value"]', '[class*="cylinder"]']),
    doors: Extractors.trySelectors(['[data-testid="overview-doors-value"]', '[class*="door"]']),
    seating_capacity: Extractors.trySelectors(['[data-testid="overview-seating_capacity-value"]', '[class*="seating"]']),
    engine: Extractors.trySelectors(['[data-testid="overview-engine_capacity_cc-value"]', '[class*="engine"]']),
    trim: Extractors.trySelectors(['[data-testid="overview-motors_trim-value"]', '[class*="trim"]']),
    description: null,
    posted_on: null,
  };

  console.log('[Navra Capture] Extracted initial fields:', {
    title: vehicle.title,
    year: vehicle.year,
    mileage: vehicle.mileage_km,
  });

  // Try JSON-LD first for fallback data
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

  // Extract make/model from URL (most reliable on Dubizzle)
  if (!vehicle.make) {
    const makeMatch = url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/([a-z0-9-]+)/i);
    if (makeMatch) vehicle.make = makeMatch[1].replace(/-/g, ' ');
  }
  if (!vehicle.model) {
    const modelMatch = url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/[a-z0-9-]+\/([a-z0-9-]+)/i);
    if (modelMatch) vehicle.model = modelMatch[1].replace(/-/g, ' ');
  }
  vehicle.title = vehicle.title || Extractors.buildVehicleTitle(vehicle, schema.title);

  // Extract price with fallback to meta tags
  if (!vehicle.price_aed) {
    const priceEl = document.querySelector('[data-testid="listing-price"]');
    if (priceEl) {
      vehicle.price_aed = Extractors.parsePrice(priceEl.textContent || '');
    }
  }
  if (!vehicle.price_aed) {
    const metaPrice = Extractors.extractFromMeta('price');
    if (metaPrice) {
      vehicle.price_aed = Extractors.parsePrice(metaPrice);
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

  const payload = {
    schema_version: 'navracar.capture.v1',
    source: 'dubizzle',
    source_url: url,
    source_listing_id: listingId,
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle,
    images: Extractors.extractImages('dubizzle', listingId, [
      ...Extractors.extractDubizzleNextDataImages(listingId),
      ...Extractors.extractSchemaImages(jsonLd),
    ]).map((url) => ({ url, confidence: 'high' })),
    diagnostics: generateDiagnostics(vehicle),
  };

  console.log('[Navra Capture] Dubizzle extraction complete:', payload);
  return payload;
}

// DubiCars extraction
function captureDubiCars() {
  console.log('[Navra Capture] Starting DubiCars extraction');
  const url = window.location.href;
  const jsonLd = Extractors.extractJsonLd();
  const schema = Extractors.extractVehicleSchema(jsonLd);

  const vehicle = {
    title: schema.title || Extractors.trySelectors(['h1', '[class*="title"]']),
    make: Extractors.trySelectors(['[data-field="make"]', '[data-make]', '[class*="make"]']) || schema.make,
    model: Extractors.trySelectors(['[data-field="model"]', '[data-model]', '[class*="model"]']) || schema.model,
    year: Extractors.trySelectors(['[data-field="year"]', '[data-year]', '[class*="year"]']) || schema.year,
    price_aed: schema.price_aed || null,
    mileage_km: Extractors.trySelectors(['[data-field="mileage"]', '[data-mileage]', '[class*="mileage"]']) || schema.mileage_km,
    fuel_type: Extractors.trySelectors(['[data-field="fuel"]', '[data-fuel]', '[class*="fuel"]']) || schema.fuel_type,
    transmission: Extractors.trySelectors(['[data-field="transmission"]', '[data-transmission]', '[class*="transmission"]']) || schema.transmission,
    body_type: Extractors.trySelectors(['[data-field="body"]', '[data-body]', '[class*="body-type"]']) || schema.body_type,
    color: Extractors.trySelectors(['[data-field="color"]', '[data-color]', '[class*="color"]']) || schema.color,
    seller_type: Extractors.extractTextFrom('[data-field="seller"]'),
    description: schema.description || Extractors.trySelectors(['[class*="description"]', '[class*="detail"]']),
    engine: Extractors.trySelectors(['[data-field="engine"]', '[data-engine]', '[class*="engine"]']) || schema.engine,
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
  const listingId = Extractors.extractListingId(url);
  vehicle.title = Extractors.buildVehicleTitle(vehicle, vehicle.title);

  const payload = {
    schema_version: 'navracar.capture.v1',
    source: 'dubicars',
    source_url: url,
    source_listing_id: listingId,
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle,
    images: Extractors.extractImages('dubicars', listingId, Extractors.extractSchemaImages(jsonLd)).map((url) => ({ url, confidence: 'high' })),
    diagnostics: generateDiagnostics(vehicle),
  };

  console.log('[Navra Capture] DubiCars extraction complete:', payload);
  return payload;
}

// YallaMotor extraction
function captureYallaMotor() {
  console.log('[Navra Capture] Starting YallaMotor extraction');
  const url = window.location.href;
  const jsonLd = Extractors.extractJsonLd();
  const schema = Extractors.extractVehicleSchema(jsonLd);

  const vehicle = {
    title: schema.title || Extractors.trySelectors(['h1', '[class*="title"]']),
    make: schema.make || null,
    model: schema.model || null,
    year: schema.year || null,
    price_aed: schema.price_aed || null,
    mileage_km: schema.mileage_km || null,
    fuel_type: schema.fuel_type || null,
    transmission: schema.transmission || null,
    body_type: schema.body_type || null,
    color: schema.color || null,
    seller_type: null,
    description: schema.description || Extractors.trySelectors(['[class*="description"]', '[class*="detail"]']),
    engine: schema.engine || null,
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
  const listingId = Extractors.extractListingId(url);
  vehicle.title = Extractors.buildVehicleTitle(vehicle, vehicle.title);

  const payload = {
    schema_version: 'navracar.capture.v1',
    source: 'yallamotor',
    source_url: url,
    source_listing_id: listingId,
    captured_at: new Date().toISOString(),
    page_title: document.title,
    vehicle,
    images: Extractors.extractImages('yallamotor', listingId, Extractors.extractSchemaImages(jsonLd)).map((url) => ({ url, confidence: 'high' })),
    diagnostics: generateDiagnostics(vehicle),
  };

  console.log('[Navra Capture] YallaMotor extraction complete:', payload);
  return payload;
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
