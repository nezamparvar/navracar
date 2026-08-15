import { SourceAdapter } from './base-adapter';
import { CapturePayload } from '../shared/types';

export class DubizzleAdapter extends SourceAdapter {
  source: 'dubizzle' = 'dubizzle';

  supports(url: string): boolean {
    try {
      const urlObj = new URL(url);
      const hostname = urlObj.hostname.toLowerCase();
      // Must be dubizzle.com or a subdomain (*.dubizzle.com), not fake-dubizzle.com
      return hostname === 'dubizzle.com' || hostname.endsWith('.dubizzle.com');
    } catch {
      return false;
    }
  }

  detectListingPage(): boolean {
    return this.supports(window.location.href) && this.hasMinimalListingIndicators();
  }

  private hasMinimalListingIndicators(): boolean {
    return !!(
      document.querySelector('[data-testid="listing-name"]') ||
      document.querySelector('[data-testid="listing-price"]') ||
      document.querySelector('[data-testid="overview-body_type-value"]')
    );
  }

  capture(): CapturePayload | null {
    if (!this.detectListingPage()) {
      return null;
    }

    const payload = this.createBasePayload('dubizzle', window.location.href) as CapturePayload;

    payload.vehicle = this.extractVehicleData();
    payload.images = this.extractAndMapImages();
    payload.source_listing_id = this.extractListingId();
    payload.diagnostics = this.generateDiagnostics(payload);

    return payload;
  }

  private extractVehicleData() {
    const vehicle = this.extractStructuredData();

    vehicle.title ??= this.extractSimpleTestid('listing-name');
    vehicle.make ??= this.extractMakeFromUrl();
    vehicle.model ??= this.extractModelFromUrl();
    vehicle.year ??= this.extractSimpleTestid('listing-year-value');
    vehicle.price_aed ??= this.extractPrice();
    vehicle.mileage_km ??= this.extractSimpleTestid('listing-kilometers-value');
    vehicle.fuel_type ??= this.extractSimpleTestid('overview-fuel_type-value');
    vehicle.transmission ??= this.extractSimpleTestid('overview-transmission_type-value');
    vehicle.body_type ??= this.extractSimpleTestid('overview-body_type-value');
    vehicle.regional_specs ??= this.extractSimpleTestid('listing-regional_specs-value');
    vehicle.steering_side ??= this.extractSimpleTestid('listing-steering_side-value');
    vehicle.exterior_color ??= this.extractSimpleTestid('overview-exterior_color-value');
    vehicle.interior_color ??= this.extractSimpleTestid('overview-interior_color-value');
    vehicle.seller_type ??= this.extractSimpleTestid('overview-seller_type-value');
    vehicle.warranty ??= this.extractSimpleTestid('overview-warranty-value');
    vehicle.horsepower ??= this.extractSimpleTestid('overview-horsepower-value');
    vehicle.no_of_cylinders ??= this.extractSimpleTestid('overview-no_of_cylinders-value');
    vehicle.doors ??= this.extractSimpleTestid('overview-doors-value');
    vehicle.seating_capacity ??= this.extractSimpleTestid('overview-seating_capacity-value');
    vehicle.engine ??= this.extractSimpleTestid('overview-engine_capacity_cc-value');
    vehicle.trim ??= this.extractSimpleTestid('overview-motors_trim-value');
    vehicle.description ??= this.extractDescription();
    vehicle.posted_on ??= this.extractPostedOn();

    return vehicle;
  }

  private extractSimpleTestid(testid: string): string | null {
    const element = document.querySelector(`[data-testid="${testid}"]`);
    if (!element) return null;
    const text = element.textContent?.trim();
    return text && text.length > 0 ? text : null;
  }

  private extractPrice(): number | null {
    const element = document.querySelector('[data-testid="listing-price"]');
    if (!element) return null;

    const text = element.textContent || '';
    const match = text.match(/([\d,]+)/);
    if (!match) return null;

    const numStr = match[1].replace(/,/g, '');
    const price = parseFloat(numStr);
    return !isNaN(price) ? price : null;
  }

  private extractDescription(): string | null {
    const element = document.querySelector('[data-testid="description"]');
    if (!element) return null;

    const cloned = element.cloneNode(true) as HTMLElement;
    Array.from(cloned.querySelectorAll('button')).forEach((btn) => btn.remove());

    let text = cloned.textContent || '';
    text = text
      .replace(/<br\s*\/?>/gi, '\n')
      .replace(/<!--.*?-->/g, '')
      .trim();

    return text && text.length > 0 ? text : null;
  }

  private extractPostedOn(): string | null {
    const element = document.querySelector('[data-testid="posted-on"]');
    if (!element) return null;
    let text = element.textContent || '';
    text = text.replace(/^posted\s+on\s*:?\s*/i, '').trim();
    return text && text.length > 0 ? text : null;
  }

  private extractMakeFromUrl(): string | null {
    const match = window.location.href.match(
      /\/motors\/(?:used-cars|new-cars|export-cars)\/([a-z0-9-]+)/i
    );
    return match ? match[1].replace(/-/g, ' ') : null;
  }

  private extractModelFromUrl(): string | null {
    const match = window.location.href.match(
      /\/motors\/(?:used-cars|new-cars|export-cars)\/[a-z0-9-]+\/([a-z0-9-]+)/i
    );
    return match ? match[1].replace(/-/g, ' ') : null;
  }

  private extractListingId(): string | null {
    const match = window.location.href.match(/-([a-f0-9]{32})/i);
    return match ? match[1].toLowerCase() : null;
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
        confidence: vehicle.mileage_km ? 'high' : 'low',
      },
      fuel_type: {
        found: !!vehicle.fuel_type,
        confidence: vehicle.fuel_type ? 'high' : 'medium',
      },
      transmission: {
        found: !!vehicle.transmission,
        confidence: vehicle.transmission ? 'high' : 'medium',
      },
      body_type: {
        found: !!vehicle.body_type,
        confidence: vehicle.body_type ? 'high' : 'medium',
      },
      images: {
        found: payload.images.length > 0,
        confidence: payload.images.length > 0 ? 'high' : 'low',
        count: payload.images.length,
      },
    };
  }
}
