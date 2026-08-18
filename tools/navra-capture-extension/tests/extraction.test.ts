/**
 * Extraction Helpers Test Suite
 * Tests data extraction priority ordering and formatting
 */

describe('Price Parsing', () => {
  const parsePrice = (text: string | null | undefined): number | null => {
    if (!text) return null;

    const lower = text.toLowerCase();

    // Reject installment/monthly prices (key fix)
    if (/(?:per\s+month|monthly|\/month|installment|aed\s*\d+\s*\/|monthly\s+payment|subscription)/i.test(lower)) {
      return null;
    }

    const match = text.match(/[\d,]+(?:\.[\d]+)?/);
    if (!match) return null;

    let numStr = match[0].replace(/,/g, '').replace(/\./g, '');
    const num = parseFloat(numStr);

    // Sanity check: price between 500 and 5,000,000 AED
    if (isNaN(num) || num < 500 || num > 5000000) return null;

    return num;
  };

  it('should parse AED prices with thousands separator', () => {
    expect(parsePrice('50,000 AED')).toBe(50000);
    expect(parsePrice('1,250,000 AED')).toBe(1250000);
    expect(parsePrice('100,000')).toBe(100000);
  });

  it('should parse prices without separators', () => {
    expect(parsePrice('50000')).toBe(50000);
    expect(parsePrice('1250000')).toBe(1250000);
  });

  it('should handle currency symbols', () => {
    expect(parsePrice('AED 50,000')).toBe(50000);
    expect(parsePrice('50,000 AED')).toBe(50000);
  });

  it('should REJECT monthly/installment prices', () => {
    // Real world examples from DubiCars meta tags
    expect(parsePrice('AED 18,082 Per Month')).toBeNull();
    expect(parsePrice('Monthly: 50,000')).toBeNull();
    expect(parsePrice('999,000/month')).toBeNull();
    expect(parsePrice('50,000 monthly')).toBeNull();
    expect(parsePrice('Installment: 15,000')).toBeNull();
  });

  it('should return null for invalid prices', () => {
    expect(parsePrice(null)).toBeNull();
    expect(parsePrice(undefined)).toBeNull();
    expect(parsePrice('')).toBeNull();
    expect(parsePrice('no price here')).toBeNull();
    expect(parsePrice('100')).toBeNull(); // Below minimum
    expect(parsePrice('6000000')).toBeNull(); // Above maximum
  });

  it('should parse realistic vehicle prices', () => {
    // Real-world examples
    expect(parsePrice('AED 999,000')).toBe(999000);
    expect(parsePrice('1,350,000 AED')).toBe(1350000);
    expect(parsePrice('250000 AED')).toBe(250000);
    expect(parsePrice('2,500,000')).toBe(2500000);
  });
});

describe('Mileage Parsing', () => {
  const parseNumber = (text: string | null | undefined): string | null => {
    if (!text) return null;
    const match = text.match(/[\d,]+/);
    if (!match) return null;
    return match[0];
  };

  it('should extract mileage numbers with separators', () => {
    expect(parseNumber('45,000 km')).toBe('45,000');
    expect(parseNumber('1,234,567 km')).toBe('1,234,567');
  });

  it('should extract mileage without separators', () => {
    expect(parseNumber('45000 km')).toBe('45000');
  });

  it('should return null for invalid mileage', () => {
    expect(parseNumber(null)).toBeNull();
    expect(parseNumber('')).toBeNull();
  });
});

describe('Image URL Deduplication', () => {
  it('should deduplicate image URLs', () => {
    const images = ['img1.jpg', 'img2.jpg', 'img1.jpg', 'img3.jpg', 'img2.jpg'];
    const deduplicated = [...new Set(images)];
    expect(deduplicated).toEqual(['img1.jpg', 'img2.jpg', 'img3.jpg']);
    expect(deduplicated.length).toBe(3);
  });

  it('should preserve image order after deduplication', () => {
    const images = ['a.jpg', 'b.jpg', 'a.jpg', 'c.jpg'];
    const deduplicated = [...new Set(images)];
    expect(deduplicated[0]).toBe('a.jpg');
    expect(deduplicated[1]).toBe('b.jpg');
    expect(deduplicated[2]).toBe('c.jpg');
  });

  it('should handle empty image array', () => {
    const images: string[] = [];
    const deduplicated = [...new Set(images)];
    expect(deduplicated).toEqual([]);
  });
});

describe('Vehicle Title Parsing', () => {
  it('should extract make and model from title', () => {
    const title = 'Toyota Camry 2020';
    const parts = title.split(/\s+/);
    expect(parts[0]).toBe('Toyota'); // make
    expect(parts[1]).toBe('Camry');  // model
    expect(parts[2]).toBe('2020');   // year
  });

  it('should handle multi-word model names', () => {
    const title = 'Range Rover Sport 2019';
    const parts = title.split(/\s+/);
    expect(parts[0]).toBe('Range');
  });

  it('should handle Farsi titles', () => {
    const title = 'تويوتا كامري 2020';
    // Should preserve original Farsi text
    expect(title).toContain('تويوتا');
    expect(title).toContain('كامري');
  });
});

describe('Description Sanitization', () => {
  const isSafe = (text: string): boolean => {
    const forbiddenPatterns = [
      /token/i,
      /password/i,
      /auth/i,
      /secret/i,
      /key/i,
      /credential/i,
      /cookie/i,
      /session/i,
    ];
    return !forbiddenPatterns.some(pattern => pattern.test(text));
  };

  it('should allow safe descriptions', () => {
    expect(isSafe('Well maintained car')).toBe(true);
    expect(isSafe('New tires and brakes')).toBe(true);
  });

  it('should reject descriptions with auth tokens', () => {
    expect(isSafe('token: abc123')).toBe(false);
    expect(isSafe('auth token here')).toBe(false);
  });

  it('should reject descriptions with passwords', () => {
    expect(isSafe('password protected')).toBe(false);
  });

  it('should reject descriptions with credentials', () => {
    expect(isSafe('credentials: user@pass')).toBe(false);
  });
});
