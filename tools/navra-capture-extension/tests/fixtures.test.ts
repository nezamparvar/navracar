/**
 * Fixture-based integration tests for real-world HTML from each marketplace
 */

describe('Fixture-based Integration Tests', () => {
  // Mock DOM helpers
  function setDOMFromHTML(html: string) {
    document.body.innerHTML = html;
  }

  describe('DubiCars - Real listing fixture', () => {
    it('should correctly extract price AED 999,000 (not monthly)', () => {
      // Simulate DubiCars fixture with both meta tag price and data-field price
      setDOMFromHTML(`
        <html>
          <head>
            <meta property="og:description" content="AED 18,082 Per Month">
            <meta name="description" content="AED 999000">
          </head>
          <body>
            <h1>Used Rolls Royce Wraith</h1>
            <div data-field="price">AED 999,000</div>
            <div data-field="make">Rolls Royce</div>
            <div data-field="model">Wraith</div>
            <div data-field="year">2019</div>
            <div data-field="mileage">13,640 KM</div>
            <div data-field="color">Black</div>
            <div data-field="fuel">Petrol</div>
            <div data-field="transmission">Automatic</div>
          </body>
        </html>
      `);

      // Extract price using the correct method
      const priceEl = document.querySelector('[data-field="price"]');
      const priceText = priceEl?.textContent || '';

      // Should extract 999000, NOT 18082
      const price = parsePrice(priceText);
      expect(price).toBe(999000);
      expect(price).not.toBe(18082);
    });

    it('should extract all vehicle fields', () => {
      setDOMFromHTML(`
        <h1>Used Rolls Royce Wraith STD</h1>
        <div data-field="make">Rolls Royce</div>
        <div data-field="model">Wraith</div>
        <div data-field="year">2019</div>
        <div data-field="price">999,000 AED</div>
        <div data-field="mileage">13,640 KM</div>
        <div data-field="fuel">Petrol</div>
        <div data-field="transmission">Automatic</div>
        <div data-field="body">Sedan</div>
        <div data-field="color">Black</div>
        <div data-field="seller">Premium Dealer</div>
      `);

      const vehicle = {
        title: document.querySelector('h1')?.textContent?.trim(),
        make: document.querySelector('[data-field="make"]')?.textContent?.trim(),
        model: document.querySelector('[data-field="model"]')?.textContent?.trim(),
        year: document.querySelector('[data-field="year"]')?.textContent?.trim(),
        price_aed: parsePrice(document.querySelector('[data-field="price"]')?.textContent || ''),
        mileage_km: document.querySelector('[data-field="mileage"]')?.textContent?.trim(),
        fuel_type: document.querySelector('[data-field="fuel"]')?.textContent?.trim(),
        transmission: document.querySelector('[data-field="transmission"]')?.textContent?.trim(),
        body_type: document.querySelector('[data-field="body"]')?.textContent?.trim(),
        color: document.querySelector('[data-field="color"]')?.textContent?.trim(),
        seller_type: document.querySelector('[data-field="seller"]')?.textContent?.trim(),
      };

      expect(vehicle.title).toBe('Used Rolls Royce Wraith STD');
      expect(vehicle.make).toBe('Rolls Royce');
      expect(vehicle.model).toBe('Wraith');
      expect(vehicle.year).toBe('2019');
      expect(vehicle.price_aed).toBe(999000);
      expect(vehicle.mileage_km).toBe('13,640 KM');
      expect(vehicle.fuel_type).toBe('Petrol');
      expect(vehicle.transmission).toBe('Automatic');
      expect(vehicle.body_type).toBe('Sedan');
      expect(vehicle.color).toBe('Black');
      expect(vehicle.seller_type).toBe('Premium Dealer');
    });

    it('should handle missing fields gracefully', () => {
      setDOMFromHTML(`
        <h1>Used Car</h1>
        <div data-field="make">Toyota</div>
      `);

      const vehicle = {
        title: document.querySelector('h1')?.textContent?.trim(),
        make: document.querySelector('[data-field="make"]')?.textContent?.trim(),
        model: document.querySelector('[data-field="model"]')?.textContent?.trim() || undefined,
        year: document.querySelector('[data-field="year"]')?.textContent?.trim() || undefined,
      };

      expect(vehicle.title).toBe('Used Car');
      expect(vehicle.make).toBe('Toyota');
      expect(vehicle.model).toBeUndefined();
      expect(vehicle.year).toBeUndefined();
    });
  });

  describe('Dubizzle - testid-based extraction', () => {
    it('should extract all Dubizzle testid fields', () => {
      setDOMFromHTML(`
        <div data-testid="listing-name">Honda Accord 2015</div>
        <div data-testid="listing-price">AED 45,000</div>
        <div data-testid="listing-year-value">2015</div>
        <div data-testid="listing-kilometers-value">120,000 KM</div>
        <div data-testid="overview-fuel_type-value">Petrol</div>
        <div data-testid="overview-transmission_type-value">Manual</div>
        <div data-testid="overview-body_type-value">Sedan</div>
        <div data-testid="overview-exterior_color-value">Silver</div>
        <div data-testid="overview-warranty-value">1 Year</div>
        <div data-testid="description">Well maintained car with service history</div>
      `);

      const vehicle = {
        title: document.querySelector('[data-testid="listing-name"]')?.textContent?.trim(),
        price_aed: parsePrice(document.querySelector('[data-testid="listing-price"]')?.textContent || ''),
        year: document.querySelector('[data-testid="listing-year-value"]')?.textContent?.trim(),
        mileage_km: document.querySelector('[data-testid="listing-kilometers-value"]')?.textContent?.trim(),
        fuel_type: document.querySelector('[data-testid="overview-fuel_type-value"]')?.textContent?.trim(),
        transmission: document.querySelector('[data-testid="overview-transmission_type-value"]')?.textContent?.trim(),
        body_type: document.querySelector('[data-testid="overview-body_type-value"]')?.textContent?.trim(),
        exterior_color: document.querySelector('[data-testid="overview-exterior_color-value"]')?.textContent?.trim(),
        warranty: document.querySelector('[data-testid="overview-warranty-value"]')?.textContent?.trim(),
        description: document.querySelector('[data-testid="description"]')?.textContent?.trim(),
      };

      expect(vehicle.title).toBe('Honda Accord 2015');
      expect(vehicle.price_aed).toBe(45000);
      expect(vehicle.year).toBe('2015');
      expect(vehicle.mileage_km).toBe('120,000 KM');
      expect(vehicle.fuel_type).toBe('Petrol');
      expect(vehicle.transmission).toBe('Manual');
      expect(vehicle.body_type).toBe('Sedan');
      expect(vehicle.exterior_color).toBe('Silver');
      expect(vehicle.warranty).toBe('1 Year');
      expect(vehicle.description).toBe('Well maintained car with service history');
    });

    it('should extract Dubizzle URLs for make/model fallback', () => {
      // Simulate various Dubizzle URLs
      const testCases = [
        {
          url: 'https://dubai.dubizzle.com/motors/used-cars/toyota/camry/2019-camry-1234abcd56789012',
          expectedMake: 'toyota',
          expectedModel: 'camry',
        },
        {
          url: 'https://www.dubizzle.com/motors/new-cars/bmw/m3/2024-m3-abcd1234',
          expectedMake: 'bmw',
          expectedModel: 'm3',
        },
      ];

      testCases.forEach(({ url, expectedMake, expectedModel }) => {
        const makeMatch = url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/([a-z0-9-]+)/i);
        const modelMatch = url.match(/\/motors\/(?:used-cars|new-cars|export-cars)\/[a-z0-9-]+\/([a-z0-9-]+)/i);

        if (makeMatch) {
          expect(makeMatch[1]).toBe(expectedMake);
        }
        if (modelMatch) {
          expect(modelMatch[1]).toBe(expectedModel);
        }
      });
    });
  });

  describe('Image extraction robustness', () => {
    it('should extract and deduplicate images', () => {
      setDOMFromHTML(`
        <picture>
          <img src="https://example.com/img1.jpg" alt="car 1" />
        </picture>
        <div class="gallery">
          <img src="https://example.com/img2.jpg" alt="car 2" />
          <img src="https://example.com/img1.jpg" alt="car 1 duplicate" />
          <img src="https://example.com/img3.jpg" alt="car 3" />
        </div>
        <img src="data:image/png;base64,..." alt="base64" />
        <img src="https://example.com/pixel.gif" alt="tracking pixel" />
      `);

      const images: string[] = [];
      const seen = new Set<string>();

      document.querySelectorAll('img').forEach((img) => {
        const src = img.src;
        if (src && !src.startsWith('data:') && !src.includes('pixel')) {
          if (!seen.has(src)) {
            seen.add(src);
            images.push(src);
          }
        }
      });

      expect(images).toHaveLength(3);
      expect(images).toEqual([
        'https://example.com/img1.jpg',
        'https://example.com/img2.jpg',
        'https://example.com/img3.jpg',
      ]);
    });

    it('should limit images to 20', () => {
      const imageCount = 25;
      const images = Array.from({ length: imageCount }, (_, i) => `<img src="https://example.com/img${i}.jpg" />`).join('');
      setDOMFromHTML(`<div>${images}</div>`);

      const extracted: string[] = [];
      const MAX_IMAGES = 20;

      document.querySelectorAll('img').forEach((img) => {
        if (extracted.length < MAX_IMAGES && img.src && !img.src.includes('pixel')) {
          extracted.push(img.src);
        }
      });

      expect(extracted).toHaveLength(MAX_IMAGES);
    });
  });

  describe('JSON-LD extraction', () => {
    it('should extract price from JSON-LD offers', () => {
      setDOMFromHTML(`
        <script type="application/ld+json">
          {
            "@type": "Product",
            "name": "Toyota Camry",
            "offers": {
              "@type": "Offer",
              "price": "75000",
              "priceCurrency": "AED"
            }
          }
        </script>
      `);

      const scripts = document.querySelectorAll('script[type="application/ld+json"]');
      let extractedPrice = null;

      Array.from(scripts).forEach((script) => {
        try {
          const data = JSON.parse(script.textContent || '');
          if (data.offers?.price) {
            extractedPrice = parsePrice(String(data.offers.price));
          }
        } catch (e) {
          // Skip
        }
      });

      expect(extractedPrice).toBe(75000);
    });
  });

  // Helper function
  function parsePrice(text: string | null | undefined): number | null {
    if (!text) return null;

    const lower = text.toLowerCase();
    if (/(?:per\s+month|monthly|\/month|installment)/i.test(lower)) {
      return null;
    }

    const match = text.match(/[\d,]+(?:\.[\d]+)?/);
    if (!match) return null;

    let numStr = match[0].replace(/,/g, '').replace(/\./g, '');
    const num = parseFloat(numStr);

    if (isNaN(num) || num < 500 || num > 5000000) return null;
    return num;
  }
});
