const TOKEN_KEY = 'navracar.mobile.token';

export function createTokenStore(storage, key = TOKEN_KEY) {
  return {
    get: () => storage?.getItem(key) || null,
    set: (token) => storage?.setItem(key, token),
    clear: () => storage?.removeItem(key),
  };
}

export function preferredTokenStorage(scope = globalThis) {
  return scope.NavraSecureStore ?? scope.localStorage;
}
