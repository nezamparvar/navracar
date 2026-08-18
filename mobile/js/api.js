export class ApiError extends Error {
  constructor(message, status = 0, payload = null) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.payload = payload;
  }
}

export function serializeQuery(values = {}) {
  const query = new URLSearchParams();
  Object.entries(values).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) query.set(key, String(value));
  });
  return query.toString();
}

export function normalizeApiError(error, online) {
  if (online === undefined) online = typeof navigator !== 'undefined' && typeof navigator.onLine === 'boolean' ? navigator.onLine : true;
  if (!online || error instanceof TypeError) return 'اتصال اینترنت برقرار نیست.';
  const errors = error?.errors ?? error?.payload?.errors;
  if (errors && typeof errors === 'object') {
    const first = Object.values(errors).flat()[0];
    if (first) return String(first);
  }
  return error?.message || error?.payload?.message || 'پاسخ سرور قابل پردازش نبود.';
}

export function createApiClient(baseUrl, options = {}) {
  const base = String(baseUrl).replace(/\/$/, '');
  const fetchImpl = options.fetchImpl ?? fetch;
  const tokenProvider = options.tokenProvider ?? (() => null);

  async function request(path, init = {}) {
    const token = tokenProvider();
    const headers = { Accept: 'application/json', ...(init.body ? { 'Content-Type': 'application/json' } : {}), ...(init.headers ?? {}) };
    if (token) headers.Authorization = `Bearer ${token}`;
    let response;
    try {
      response = await fetchImpl(`${base}${path}`, { ...init, headers });
    } catch (error) {
      throw new ApiError(normalizeApiError(error, false));
    }
    const payload = response.status === 204 ? null : await response.json().catch(() => null);
    if (!response.ok) {
      if (response.status === 401) options.onUnauthorized?.();
      throw new ApiError(normalizeApiError(payload ?? { message: response.statusText }), response.status, payload);
    }
    return payload;
  }

  return {
    get: (path, options = {}) => request(path, options),
    post: (path, data, options = {}) => request(path, { ...options, method: 'POST', body: JSON.stringify(data) }),
    put: (path, data = null, options = {}) => request(path, { ...options, method: 'PUT', ...(data ? { body: JSON.stringify(data) } : {}) }),
    patch: (path, data, options = {}) => request(path, { ...options, method: 'PATCH', body: JSON.stringify(data) }),
    delete: (path, options = {}) => request(path, { ...options, method: 'DELETE' }),
  };
}
