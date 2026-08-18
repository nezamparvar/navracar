import test from 'node:test';
import assert from 'node:assert/strict';
import { createApiClient, normalizeApiError, serializeQuery } from '../../mobile/js/api.js';

test('serializes only supported non-empty filter values', () => {
  assert.equal(serializeQuery({ q: 'BMW X5', make: '', year_min: 2023, page: null }), 'q=BMW+X5&year_min=2023');
});

test('adds bearer token and clears auth on 401', async () => {
  let expired = false;
  const requests = [];
  const api = createApiClient('https://stage.example.test/', {
    tokenProvider: () => '4|token',
    onUnauthorized: () => { expired = true; },
    fetchImpl: async (url, options) => {
      requests.push({ url, options });
      return new Response(JSON.stringify({ message: 'expired' }), { status: 401, headers: { 'content-type': 'application/json' } });
    },
  });

  await assert.rejects(() => api.get('/api/mobile/v1/account'), /expired/);
  assert.equal(requests[0].options.headers.Authorization, 'Bearer 4|token');
  assert.equal(expired, true);
});

test('normalizes validation and offline errors into actionable Persian messages', () => {
  assert.equal(normalizeApiError({ errors: { phone: ['شماره معتبر نیست.'] } }), 'شماره معتبر نیست.');
  assert.equal(normalizeApiError(new TypeError('Failed to fetch'), false), 'اتصال اینترنت برقرار نیست.');
});
