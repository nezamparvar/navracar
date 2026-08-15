export type VehicleSource = 'dubizzle' | 'dubicars' | 'yallamotor';

export interface CapturePayload {
  schema_version: string;
  source: VehicleSource;
  source_url: string;
  source_listing_id: string | null;
  captured_at: string;
  page_title: string | null;

  vehicle: {
    title?: string | null;
    make?: string | null;
    model?: string | null;
    trim?: string | null;
    year?: string | null;
    price_aed?: number | null;
    mileage_km?: string | null;
    fuel_type?: string | null;
    engine?: string | null;
    transmission?: string | null;
    body_type?: string | null;
    regional_specs?: string | null;
    color?: string | null;
    seller_type?: string | null;
    description?: string | null;
    steering_side?: string | null;
    horsepower?: string | null;
    no_of_cylinders?: string | null;
    doors?: string | null;
    seating_capacity?: string | null;
    exterior_color?: string | null;
    interior_color?: string | null;
    warranty?: string | null;
    posted_on?: string | null;
  };

  images: {
    url: string;
    confidence: 'high' | 'medium' | 'low';
  }[];

  diagnostics: {
    [key: string]: {
      found: boolean;
      confidence: 'high' | 'medium' | 'low';
    };
  };

  source_metadata?: {
    [key: string]: unknown;
  };
}

export interface ExtensionAuthToken {
  token: string;
  navracar_base_url: string;
  environment: 'staging' | 'production';
  created_at: string;
  pairing_code?: string;
}

export interface ExtensionState {
  authenticated: boolean;
  token?: ExtensionAuthToken;
  selectedEnvironment: 'staging' | 'production';
}

export interface CaptureResponse {
  status: 'success' | 'error';
  queue_item_id?: number;
  duplicate_detected?: {
    slug: string;
    make?: string;
    model?: string;
    year?: string;
    price_aed?: number;
  };
  review_url?: string;
  error?: string;
  message?: string;
}
