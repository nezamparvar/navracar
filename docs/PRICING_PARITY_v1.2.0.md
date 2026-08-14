# v1.2.0 pricing parity report

## Reference

The migration baseline is the correct v1.1 Dubizzle/listing formula documented in `PRICINGAUDIT.md`: certificate-count scrappage, eight categories, full-precision intermediate values, and the established service-fee base.

`VehiclePricingEngineTest::legacyDubizzleTotal()` is an independent test-only restatement of that reference. Production code does not call it.

## Matrix executed

The automated comparison covers every canonical category:

- `ev`
- `phev`
- `hybrid`
- `c1500`
- `c2000`
- `c2500`
- `c3000`
- `c3001`

Each category is checked at AED 25,000, 60,000, and 150,000. For all 24 cases:

1. the independent v1.1 reference total equals `VehiclePricingService` within 0.01 Toman;
2. `CarListing::estimatedLandedCostToman()` delegates to the service and returns the same result.

The exact threshold suite covers AB below/above, CD below/exactly/above, and EFG below/above. AED 60,000 remains in `upto`; AED 60,001 is `above`. An invalid stored tier falls back to the category's validated catalog default.

## Representative exact result

With real AED 100,000, customs AED 80,000, category `c2000`, free rate 50,000, customs rate 35,000, freight AED 1,500, permits AED 60,000, storage Toman 100,000,000, certificate price Toman 25,000,000, and threshold AED 60,000:

| Value | Toman/count |
| --- | ---: |
| CIF | 2,800,000,000 |
| Tariff duty | 3,360,000,000 |
| Percentage customs subtotal | 4,705,400,000 |
| Scrap certificates | 7 |
| Scrappage cost | 175,000,000 |
| Customs subtotal | 7,880,400,000 |
| Plate subtotal | 707,000,000 |
| Service fee | 848,740,000 |
| Final total | 14,436,140,000 |

## Settings propagation

Tests warm/read Settings, update one key, and immediately recalculate without clearing application caches. Verified changes include free rate, customs rate, certificate price, threshold, category tariff, VAT, service fee, and CD-above certificate count. Every new calculation changes predictably; previously stored Quote/Invoice snapshots remain unchanged.

## Persisted-flow parity and tamper resistance

- Public calculator display is compared to its backend response in Playwright.
- A seeded listing calculator display is compared to the same backend response in Playwright.
- Quote and CalculationLog tests submit forged totals and prove stored totals equal the central service.
- Automatic Invoice tests submit a forged total/currency and prove the stored Toman total and snapshot are authoritative.
- The Proforma browser flow calculates, reviews scrappage/breakdown, applies a discount, stores, opens, and downloads the PDF.

## Result

No unexplained pricing difference was found. The obsolete 1.5%-of-CIF scrappage rule is absent from active calculation paths.

