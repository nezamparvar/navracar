# Central vehicle pricing engine

`App\Services\VehiclePricing\VehiclePricingService` is the only authoritative implementation of Navra Car landed-cost pricing. Page scripts may request and render its result, but must not reproduce the formula.

## API and call sites

The service accepts `VehiclePricingInput`:

- `realPriceAed`: actual purchase price in AED.
- `customsPriceAed`: customs valuation in AED; it may differ from the purchase price.
- `categoryId`: one of `ev`, `phev`, `hybrid`, `c1500`, `c2000`, `c2500`, `c3000`, `c3001`.

`POST /vehicle-pricing/calculate` exposes the same service to interactive public/admin pages. It accepts `real_price_aed`, `customs_price_aed`, and `category`; all rates and rules are read on the server.

Callers are:

- `CarListing::estimatedLandedCostToman()` for listing cards and filters. A listing maps its one AED price to both price inputs to preserve historical Dubizzle behavior.
- `resources/views/components/car-calculator.blade.php` for listing detail pages.
- `resources/views/public/calculator.blade.php` for the standalone calculator, which preserves distinct real/customs price inputs.
- `QuoteController`, `CalculationLogController`, and automatic `InvoiceController` flows for persisted records.

## Result

`VehiclePricingResult::toArray()` contains the normalized input, canonical category, `customsRows`, `plateRows`, all intermediate bases/subtotals, scrappage count/cost, service fee, final total, and the complete Settings snapshot. No display rounding is applied during calculation. Formatting rounds only when rendering.

## Calculation sequence and bases

1. `CIF = customsPriceAed × customs_rate`.
2. `realPriceToman = realPriceAed × free_rate`.
3. Category tariff duty is its configured percentage of CIF.
4. Fixed customs, gasoline, FOB, waste, and standard percentages use CIF.
5. VAT and advance import tax use `CIF + tariff duty`.
6. Red Crescent and customs supervision use tariff duty.
7. Freight and permit amounts use AED values multiplied by `free_rate`; storage is a fixed Toman amount.
8. Scrappage uses certificate count × certificate price.
9. Registration, transfer tax, municipal levy, and individual-person levy use CIF.
10. Pre-service total is real vehicle price + customs subtotal + plate subtotal.
11. The service fee base is customs percentage subtotal + plate subtotal + freight + permits. It deliberately excludes storage and the real vehicle price, preserving the Dubizzle reference formula.
12. Final total is pre-service total + service fee.

## Pricing Settings keys

Base/rate keys:

- `free_rate`
- `customs_rate`
- `usd_to_aed_rate`
- `sea_freight_aed`
- `license_fee_aed`
- `storage_toman`
- `scrap_cert_price_toman`
- `scrap_threshold_aed`

Percentage keys:

- `customs_fixed_percent`
- `gasoline_levy_percent`
- `fob_levy_percent`
- `vat_percent`
- `advance_import_tax_percent`
- `red_crescent_percent`
- `customs_supervision_percent`
- `waste_levy_percent`
- `standard_fee_percent`
- `registration_percent`
- `transfer_tax_percent`
- `municipal_percent`
- `individual_person_percent`
- `service_fee_percent`

Category keys are `tariff_percent_{categoryId}` and `scrap_tier_{categoryId}`. Scrappage counts are `scrap_cert_count_{tier}_{bracket}`, where tier is `ab`, `cd`, or `efg`, and bracket is `upto` or `above`.

Defaults preserve the v1.1 reference behavior. Because Settings are key/value records with application defaults, this release requires no database migration and does not overwrite existing production values. `Setting::set()` invalidates the affected forever-cache entry immediately.

## Categories and scrappage

The canonical category catalog is `VehiclePricingCatalog::CATEGORIES`. Each category has a stable id, Persian label, default tariff percentage, and default scrap tier. Live tariff/tier values come from Settings.

Scrappage threshold semantics are exact:

- customs price `<= scrap_threshold_aed`: `upto`
- customs price `> scrap_threshold_aed`: `above`

Default certificate counts are AB 1/1, CD 5/7, and EFG 6/9. The obsolete 1.5%-of-CIF calculation is not an active rule.

## Persistence and historical integrity

Quotes and calculation logs ignore client-provided breakdowns/totals and calculate from structured inputs. Quotes store authoritative rows, result, input, Settings snapshot, and engine version.

Automatic invoices recalculate on the server, force Toman currency, ignore the posted total, and store the authoritative rows/result/snapshot. An optional adjustment must carry a reason and is stored separately from the calculated total. Manual invoices are explicit, require a reason, and are totaled from validated rows on the server.

PDF generation only renders stored data. Viewing or downloading an existing Quote/Invoice never recalculates it using current Settings, so later Settings changes affect new calculations only.

## Changing a pricing rule

1. Obtain explicit business approval for the new rule/default.
2. Add or reuse a Settings key and admin validation; do not put a percentage in a page/controller.
3. Change only the central pricing domain service/catalog/settings objects.
4. Add exact base, boundary, parity, persistence-tampering, and historical-snapshot tests.
5. Update this document and release notes.

