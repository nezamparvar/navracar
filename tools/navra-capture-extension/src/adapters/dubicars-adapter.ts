import { SourceAdapter } from './base-adapter';
import { CapturePayload } from '../shared/types';

export class DubiCarsAdapter extends SourceAdapter {
  source: 'dubicars' = 'dubicars';

  supports(url: string): boolean {
    return /dubicars\.com/i.test(url);
  }

  detectListingPage(): boolean {
    return this.supports(window.location.href) && this.hasMinimalListingIndicators();
  }

  private hasMinimalListingIndicators(): boolean {
    return !!(
      document.querySelector('h1') ||
      document.querySelector('[data-field="price"]') ||
      document.querySelector('[class*="listing"]')
    );
  }

  capture(): CapturePayload | null {
    if (!this.detectListingPage()) {
      return null;
    }

    const payload = this.createBasePayload('dubicars', window.location.href) as CapturePayload;

    payload.vehicle = this.extractVehicleData();
    payload.images = this.extractAndMapImages();
    payload.source_listing_id = this.extractListingId();
    payload.diagnostics = this.generateDiagnostics(payload);

    return payload;
  }

  private extractVehicleData() {
    const vehicle = this.extractStructuredData();

    vehicle.title ??= this.extractTitle();
    vehicle.make ??= this.extractFieldValue('make');
    vehicle.model ??= this.extractFieldValue('model');
    vehicle.year ??= this.extractFieldValue('year');
    vehicle.price_aed ??= this.extractPrice();
    vehicle.mileage_km ??= this.extractFieldValue('mileage');
    vehicle.fuel_type ??= this.extractFieldValue('fuel');
    vehicle.transmission ??= this.extractFieldValue('transmission');
    vehicle.body_type ??= this.extractFieldValue('body');
    vehicle.color ??= this.extractFieldValue('color');
    vehicle.seller_type ??= this.extractFieldValue('seller');
    vehicle.description ??= this.extractDescription();
    vehicle.engine ??= this.extractFieldValue('engine');

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
    const priceElement = document.querySelector('[data-field="price"]');
    if (!priceElement) return null;

    const text = priceElement.textContent || '';
    const match = text.match(/([\d,]+)/);
    if (!match) return null;

    const numStr = match[1].replace(/,/g, '');
    const price = parseFloat(numStr);
    return !isNaN(price) ? price : null;
  }

  private extractFieldValue(field: string): string | null {
    const element = document.querySelector(`[data-field="${field}"]`);
    if (!element) return null;
    const text = element.textContent?.trim();
    return text && text.length > 0 ? text : null;
  }

  private extractDescription(): string | null {
    const element = document.querySelector('[class*="description"]');
    if (!element) return null;

    let text = element.textContent || '';
    text = text.trim();

    return text && text.length > 0 ? text : null;
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
        confidence: vehicle.make ? 'high' : 'low',
      },
      model: {
        found: !!vehicle.model,
        confidence: vehicle.model ? 'high' : 'low',
      },
      year: {
        found: !!vehicle.year,
        confidence: vehicle.year ? 'high' : 'low',
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
