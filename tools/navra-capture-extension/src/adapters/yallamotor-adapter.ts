import { SourceAdapter } from './base-adapter';
import { CapturePayload } from '../shared/types';

export class YallaMotorAdapter extends SourceAdapter {
  source: 'yallamotor' = 'yallamotor';

  supports(url: string): boolean {
    return /yallamotor\.com/i.test(url);
  }

  detectListingPage(): boolean {
    return this.supports(window.location.href) && this.hasMinimalListingIndicators();
  }

  private hasMinimalListingIndicators(): boolean {
    return !!(
      document.querySelector('[class*="listing"]') ||
      document.querySelector('[class*="vehicle"]') ||
      document.querySelector('[class*="car"]')
    );
  }

  capture(): CapturePayload | null {
    if (!this.detectListingPage()) {
      return null;
    }

    const payload = this.createBasePayload('yallamotor', window.location.href) as CapturePayload;

    payload.vehicle = this.extractVehicleData();
    payload.images = this.extractAndMapImages();
    payload.source_listing_id = this.extractListingId();
    payload.diagnostics = this.generateDiagnostics(payload);

    return payload;
  }

  private extractVehicleData() {
    const vehicle = this.extractStructuredData();

    vehicle.title ??= this.extractTitle();
    vehicle.make ??= this.extractFromDataAttribute('make') || this.extractTextWithLabel('Make');
    vehicle.model ??= this.extractFromDataAttribute('model') || this.extractTextWithLabel('Model');
    vehicle.year ??= this.extractFromDataAttribute('year') || this.extractTextWithLabel('Year');
    vehicle.price_aed ??= this.extractPrice();
    vehicle.mileage_km ??=
      this.extractFromDataAttribute('mileage') || this.extractTextWithLabel('Mileage');
    vehicle.fuel_type ??= this.extractFromDataAttribute('fuel') || this.extractTextWithLabel('Fuel');
    vehicle.transmission ??=
      this.extractFromDataAttribute('transmission') || this.extractTextWithLabel('Transmission');
    vehicle.body_type ??=
      this.extractFromDataAttribute('body') || this.extractTextWithLabel('Body Type');
    vehicle.color ??= this.extractFromDataAttribute('color') || this.extractTextWithLabel('Color');
    vehicle.seller_type ??=
      this.extractFromDataAttribute('seller') || this.extractTextWithLabel('Seller');
    vehicle.description ??= this.extractDescription();
    vehicle.engine ??=
      this.extractFromDataAttribute('engine') || this.extractTextWithLabel('Engine');

    return vehicle;
  }

  private extractTitle(): string | null {
    const h1 = document.querySelector('h1');
    if (h1) {
      const text = h1.textContent?.trim();
      if (text && text.length > 0) return text;
    }
    return null;
  }

  private extractPrice(): number | null {
    const priceSelector = '[class*="price"]';
    const element = document.querySelector(priceSelector);
    if (!element) return null;

    const text = element.textContent || '';
    const match = text.match(/([\d,]+)/);
    if (!match) return null;

    const numStr = match[1].replace(/,/g, '');
    const price = parseFloat(numStr);
    return !isNaN(price) ? price : null;
  }

  private extractFromDataAttribute(field: string): string | null {
    const element = document.querySelector(`[data-${field}]`);
    if (!element) return null;
    const text = element.getAttribute(`data-${field}`)?.trim();
    return text && text.length > 0 ? text : null;
  }

  private extractTextWithLabel(label: string): string | null {
    const labelElements = Array.from(document.querySelectorAll('*')).filter(
      (el) => el.textContent?.includes(label)
    );

    for (const labelEl of labelElements) {
      const parent = labelEl.parentElement;
      if (!parent) continue;

      const value = parent.querySelector('[class*="value"]') || parent.nextElementSibling;
      if (value) {
        const text = value.textContent?.trim();
        if (text && text.length > 0 && text !== label) return text;
      }
    }

    return null;
  }

  private extractDescription(): string | null {
    const selectors = [
      '[class*="description"]',
      '[class*="detail"]',
      '[class*="content"]',
    ];

    for (const selector of selectors) {
      const element = document.querySelector(selector);
      if (element) {
        let text = element.textContent || '';
        text = text.trim();
        if (text && text.length > 0) return text;
      }
    }

    return null;
  }

  private extractListingId(): string | null {
    const match = window.location.href.match(/\/(?:car|listing)\/(\d+)/i);
    return match ? match[1] : null;
  }

  private extractAndMapImages(): CapturePayload['images'] {
    const imageUrls = this.extractImages();
    return imageUrls.map((url) => ({
      url,
      confidence: 'high' as const,
    }));
  }

  private generateDiagnostics(payload: CapturePayload): Record<string, any> {
    const { vehicle } = payload;
    return {
      title: {
        found: !!vehicle.title,
        confidence: vehicle.title ? 'high' : 'low',
      },
      make: {
        found: !!vehicle.make,
        confidence: vehicle.make ? 'high' : 'medium',
      },
      model: {
        found: !!vehicle.model,
        confidence: vehicle.model ? 'high' : 'medium',
      },
      year: {
        found: !!vehicle.year,
        confidence: vehicle.year ? 'high' : 'medium',
      },
      price: {
        found: !!vehicle.price_aed,
        confidence: vehicle.price_aed ? 'high' : 'low',
      },
      mileage: {
        found: !!vehicle.mileage_km,
        confidence: vehicle.mileage_km ? 'high' : 'medium',
      },
      images: {
        found: payload.images.length > 0,
        confidence: payload.images.length > 0 ? 'high' : 'low',
        count: payload.images.length,
      },
    };
  }
}
