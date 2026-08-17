import test from 'node:test';
import assert from 'node:assert/strict';
import { createTokenStore } from '../../mobile/js/auth.js';
import { initialState, parseHash, reducer } from '../../mobile/js/state.js';

test('parses primary and detail hash routes with query data', () => {
  assert.deepEqual(parseHash('#/vehicles?q=BMW'), { name: 'vehicles', params: {}, query: { q: 'BMW' } });
  assert.deepEqual(parseHash('#/vehicle/bmw-x5'), { name: 'vehicle', params: { slug: 'bmw-x5' }, query: {} });
});

test('auth expiry clears customer state and moves to account', () => {
  const state = reducer({ ...initialState, customer: { id: 9 }, route: { name: 'requests' } }, { type: 'AUTH_EXPIRED' });
  assert.equal(state.customer, null);
  assert.equal(state.route.name, 'account');
  assert.equal(state.notice, 'نشست شما منقضی شده است؛ دوباره وارد شوید.');
});

test('token store supports native secure-store compatible interface', () => {
  const values = new Map();
  const storage = {
    getItem: (key) => values.get(key) ?? null,
    setItem: (key, value) => values.set(key, value),
    removeItem: (key) => values.delete(key),
  };
  const tokens = createTokenStore(storage);
  tokens.set('12|secret');
  assert.equal(tokens.get(), '12|secret');
  tokens.clear();
  assert.equal(tokens.get(), null);
});
