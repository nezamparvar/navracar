import { DubizzleAdapter } from '../src/adapters/dubizzle-adapter';
import { DubiCarsAdapter } from '../src/adapters/dubicars-adapter';
import { YallaMotorAdapter } from '../src/adapters/yallamotor-adapter';
import { AdapterRegistry } from '../src/adapters/adapter-registry';

describe('AdapterRegistry', () => {
  it('should detect Dubizzle URLs', () => {
    const url = 'https://dubai.dubizzle.com/motors/used-cars/...';
    const adapter = AdapterRegistry.getAdapterForUrl(url);
    expect(adapter).toBeInstanceOf(DubizzleAdapter);
  });

  it('should detect DubiCars URLs', () => {
    const url = 'https://www.dubicars.com/car/...';
    const adapter = AdapterRegistry.getAdapterForUrl(url);
    expect(adapter).toBeInstanceOf(DubiCarsAdapter);
  });

  it('should detect YallaMotor URLs', () => {
    const url = 'https://www.yallamotor.com/...';
    const adapter = AdapterRegistry.getAdapterForUrl(url);
    expect(adapter).toBeInstanceOf(YallaMotorAdapter);
  });

  it('should return null for unknown URLs', () => {
    const url = 'https://example.com/...';
    const adapter = AdapterRegistry.getAdapterForUrl(url);
    expect(adapter).toBeNull();
  });
});

describe('DubizzleAdapter', () => {
  const adapter = new DubizzleAdapter();

  it('should support Dubizzle URLs', () => {
    expect(adapter.supports('https://dubai.dubizzle.com/motors/used-cars/...')).toBe(true);
    expect(adapter.supports('https://www.dubizzle.com/...')).toBe(true);
  });

  it('should not support non-Dubizzle URLs', () => {
    expect(adapter.supports('https://example.com')).toBe(false);
  });
});

describe('DubiCarsAdapter', () => {
  const adapter = new DubiCarsAdapter();

  it('should support DubiCars URLs', () => {
    expect(adapter.supports('https://www.dubicars.com/car/...')).toBe(true);
  });

  it('should not support non-DubiCars URLs', () => {
    expect(adapter.supports('https://example.com')).toBe(false);
  });
});

describe('YallaMotorAdapter', () => {
  const adapter = new YallaMotorAdapter();

  it('should support YallaMotor URLs', () => {
    expect(adapter.supports('https://www.yallamotor.com/...')).toBe(true);
  });

  it('should not support non-YallaMotor URLs', () => {
    expect(adapter.supports('https://example.com')).toBe(false);
  });
});
