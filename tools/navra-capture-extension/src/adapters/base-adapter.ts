import { CapturePayload, VehicleSource } from '../shared/types';

export abstract class SourceAdapter {
  abstract source: VehicleSource;

  abstract supports(url: string): boolean;

  abstract detectListingPage(): boolean;

  abstract capture(): CapturePayload | null;

  protected createBasePayload(source: VehicleSource, url: string): Partial<CapturePayload> {
    return {
      schema_version: 'navracar.capture.v1',
      source,
      source_url: url,
      captured_at: new Date().toISOString(),
      page_title: document.title,
      vehicle: {},
      images: [],
      diagnostics: {},
    };
  }

  protected extractTextContent(selector: string): string | null {
    const element = document.querySelector(selector);
    if (!element) return null;
    const text = element.textContent?.trim();
    return text && text.length > 0 ? text : null;
  }

  protected extractAttributeValue(selector: string, attr: string): string | null {
    const element = document.querySelector(selector);
    if (!element) return null;
    const value = element.getAttribute(attr);
    return value && value.length > 0 ? value : null;
  }

  protected parsePrice(priceText: string | null | undefined): number | null {
    if (!priceText) return null;

    const text = priceText.toLowerCase();

    // Reject installment/monthly prices (common pitfall from meta tags)
    if (
      /(?:per\s+month|monthly|\/month|installment|aed\s*\d+\s*\/|monthly\s+payment|subscription)/i.test(
        text
      )
    ) {
      return null;
    }

    // Extract numeric value
    const match = text.match(/[\d,]+(?:\.[\d]+)?/);
    if (!match) return null;

    let numStr = match[0].replace(/,/g, '').replace(/\./g, '');

    // Sanity check: price should be between 500 and 5,000,000 AED
    const num = parseFloat(numStr);
    if (isNaN(num) || num < 500 || num > 5000000) {
      return null;
    }

    return num;
  }

  protected parseNumber(text: string | null | undefined): string | null {
    if (!text) return null;
    const match = text.match(/[\d,]+/);
    if (!match) return null;
    return match[0];
  }

  protected extractImages(): string[] {
    const images: string[] = [];
    const seenUrls = new Set<string>();
    const MAX_IMAGES = 20;

    // Priority order for image extraction
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

      document.querySelectorAll(selector).forEach((el) => {
        if (images.length >= MAX_IMAGES) return;

        const img = el as HTMLImageElement;
        const src = img.src || img.getAttribute('data-src') || img.getAttribute('data-srcset');
        if (!src || seenUrls.has(src)) return;

        // Filter invalid URLs
        if (!src.startsWith('http') && !src.startsWith('//')) return;
        // Skip placeholder/tracking pixels
        if (src.includes('pixel') || src.includes('blank') || src.includes('1x1')) return;
        // Skip data URLs
        if (src.startsWith('data:')) return;

        seenUrls.add(src);
        images.push(src);
      });
    }

    return images;
  }

  protected extractStructuredData(): Partial<CapturePayload['vehicle']> {
    const result: Partial<CapturePayload['vehicle']> = {};

    const scripts = document.querySelectorAll('script[type="application/ld+json"]');
    scripts.forEach((script) => {
      try {
        const jsonData = JSON.parse(script.textContent || '{}');
        const nodes = Array.isArray(jsonData) ? jsonData : [jsonData];

        nodes.forEach((node: any) => {
          if (node['@graph']) {
            const graphNodes = node['@graph'];
            graphNodes.forEach((graphNode: any) => this.extractFromJsonLd(graphNode, result));
          } else {
            this.extractFromJsonLd(node, result);
          }
        });
      } catch (e) {
        console.debug('JSON-LD parsing error:', e);
      }
    });

    return result;
  }

  private extractFromJsonLd(node: any, result: Partial<CapturePayload['vehicle']>): void {
    const type = (node['@type'] || '').toLowerCase();

    if (!type.includes('vehicle') && !type.includes('product') && !node['offers']) {
      return;
    }

    if (node.name && !result.title) result.title = node.name;
    if (node.description && !result.description) result.description = node.description;
    if (node.brand && !result.make) {
      result.make = typeof node.brand === 'object' ? node.brand.name : node.brand;
    }
    if (node.model && !result.model) result.model = node.model;
    if (node.vehicleModelDate && !result.year) result.year = String(node.vehicleModelDate);
    if (node.bodyType && !result.body_type) result.body_type = node.bodyType;
    if (node.fuelType && !result.fuel_type) result.fuel_type = node.fuelType;
    if (node.vehicleTransmission && !result.transmission) {
      result.transmission = node.vehicleTransmission;
    }

    if (node.offers?.price && !result.price_aed) {
      result.price_aed = parseFloat(node.offers.price);
    }

    if (node.image && !result.title) {
      if (Array.isArray(node.image)) {
        result.title = node.image.find((img: any) => typeof img === 'string');
      } else if (typeof node.image === 'string') {
        result.title = node.image;
      }
    }
  }
}
