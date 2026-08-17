import { DubizzleAdapter } from './dubizzle-adapter';
import { DubiCarsAdapter } from './dubicars-adapter';
import { YallaMotorAdapter } from './yallamotor-adapter';
import { SourceAdapter } from './base-adapter';

export class AdapterRegistry {
  private static adapters: SourceAdapter[] = [
    new DubizzleAdapter(),
    new DubiCarsAdapter(),
    new YallaMotorAdapter(),
  ];

  static getAdapterForUrl(url: string): SourceAdapter | null {
    for (const adapter of this.adapters) {
      if (adapter.supports(url)) {
        return adapter;
      }
    }
    return null;
  }

  static getAdapterForCurrentPage(): SourceAdapter | null {
    return this.getAdapterForUrl(window.location.href);
  }

  static canCaptureCurrentPage(): boolean {
    const adapter = this.getAdapterForCurrentPage();
    return adapter !== null && adapter.detectListingPage();
  }

  static captureCurrentPage() {
    const adapter = this.getAdapterForCurrentPage();
    if (!adapter) {
      return null;
    }
    return adapter.capture();
  }
}
