import fs from 'fs';
import path from 'path';

type CaptureFunctions = {
  captureDubizzle: () => any;
  captureDubiCars: () => any;
  captureYallaMotor: () => any;
};

function loadContentScript(): CaptureFunctions {
  const source = fs.readFileSync(
    path.join(__dirname, '../src/content/content-script.js'),
    'utf8',
  );
  const chrome = {
    runtime: {
      onMessage: { addListener: jest.fn() },
      sendMessage: jest.fn(),
    },
  };

  return new Function(
    'chrome',
    `${source}\nreturn { captureDubizzle, captureDubiCars, captureYallaMotor };`,
  )(chrome);
}

describe('content script extraction from supplied marketplace HTML structures', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
  });

  it('excludes Dubizzle recommended-listing images from the captured vehicle gallery', () => {
    document.head.innerHTML = `
      <script type="application/ld+json">
        {
          "@type": "Vehicle",
          "name": "2024 Mazda 3",
          "image": "https://dbz-images.dubizzle.com/images/main-cover.jpg",
          "offers": {"price": "73000"}
        }
      </script>`;
    document.body.innerHTML = `
      <img data-testid="dpv-view-main-image" src="https://dbz-images.dubizzle.com/images/main-1.jpg">
      <img data-testid="dpv-view-secondary-image-1" src="https://dbz-images.dubizzle.com/images/main-2.jpg">
      <section data-testid="recommended-gallery">
        <img src="https://dbz-images.dubizzle.com/images/other-listing.jpg">
      </section>`;
    window.history.replaceState(
      {},
      '',
      '/motors/used-cars/mazda/3/2024/8/17/2024-mazda-3-0123456789abcdef0123456789abcdef/',
    );

    const { captureDubizzle } = loadContentScript();
    const payload = captureDubizzle();

    expect(payload.images.map((image: { url: string }) => image.url)).toEqual([
      'https://dbz-images.dubizzle.com/images/main-cover.jpg',
      'https://dbz-images.dubizzle.com/images/main-1.jpg',
      'https://dbz-images.dubizzle.com/images/main-2.jpg',
    ]);
  });

  it('extracts the DubiCars vehicle from its @graph when data-field elements are absent', () => {
    document.head.innerHTML = `
      <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@graph": [
            {
              "@type": "ItemPage",
              "mainEntity": {"@id": "https://www.dubicars.com/car.html#car"}
            },
            {
              "@type": ["Product", "Car"],
              "@id": "https://www.dubicars.com/car.html#car",
              "name": "Rolls Royce Wraith STD UAE's Very Best Example | AED 18,082 Per Month",
              "brand": {"@id": "https://www.dubicars.com/new-cars/rolls-royce#brand"},
              "model": {"@id": "https://www.dubicars.com/new-cars/rolls-royce/wraith#model"},
              "vehicleModelDate": "2019",
              "offers": {"@type": "Offer", "price": "999000", "priceCurrency": "AED"},
              "mileageFromOdometer": {"@type": "QuantitativeValue", "value": "13640", "unitCode": "KMT"},
              "bodyType": "Coupe",
              "color": "Black",
              "fuelType": "Petrol",
              "vehicleTransmission": "Automatic",
              "vehicleInteriorColor": "Tan",
              "description": "Approved certified vehicle"
            }
          ]
        }
      </script>`;
    document.body.innerHTML = '<div class="description-card">Price and year summary</div>';
    window.history.replaceState({}, '', '/2019-rolls-royce-wraith-846872.html');

    const { captureDubiCars } = loadContentScript();
    const payload = captureDubiCars();
    const vehicle = payload.vehicle;

    expect(vehicle).toMatchObject({
      title: '2019 Rolls Royce Wraith',
      make: 'Rolls Royce',
      model: 'Wraith',
      year: '2019',
      price_aed: 999000,
      mileage_km: '13640',
      fuel_type: 'Petrol',
      transmission: 'Automatic',
      body_type: 'Coupe',
      color: 'Black',
      description: 'Approved certified vehicle',
    });
    expect(payload.source_listing_id).toBe('846872');
  });

  it('extracts the YallaMotor vehicle from Product Car JSON-LD without data attributes', () => {
    document.head.innerHTML = `
      <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": ["Product", "Car"],
          "name": "Used Hyundai Azera 2.4L 2014",
          "brand": {"@type": "Brand", "name": "Hyundai"},
          "model": "Azera",
          "vehicleModelDate": "2014",
          "description": "Used Hyundai Azera 2014 for sale in Dubai",
          "fuelType": "Petrol",
          "bodyType": "Sedan",
          "color": "White",
          "vehicleTransmission": "Automatic",
          "mileageFromOdometer": {"@type": "QuantitativeValue", "value": 162000, "unitCode": "KMT"},
          "vehicleEngine": {
            "@type": "EngineSpecification",
            "engineDisplacement": {"@type": "QuantitativeValue", "value": "2000", "unitCode": "CMQ"}
          },
          "offers": {"@type": "Offer", "price": 21000, "priceCurrency": "AED"}
        }
      </script>`;
    document.body.innerHTML = '<div class="description-toggle">Show more (18)Show less</div>';
    window.history.replaceState(
      {},
      '',
      '/used-cars/hyundai/azera/2014/used-hyundai-azera-2014-dubai-2127210',
    );

    const { captureYallaMotor } = loadContentScript();
    const payload = captureYallaMotor();
    const vehicle = payload.vehicle;

    expect(vehicle).toMatchObject({
      title: '2014 Hyundai Azera',
      make: 'Hyundai',
      model: 'Azera',
      year: '2014',
      price_aed: 21000,
      mileage_km: '162000',
      fuel_type: 'Petrol',
      transmission: 'Automatic',
      body_type: 'Sedan',
      color: 'White',
      description: 'Used Hyundai Azera 2014 for sale in Dubai',
      engine: '2000',
    });
    expect(payload.source_listing_id).toBe('2127210');
  });
});
