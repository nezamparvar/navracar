export const initialState = {
  route: { name: 'home', params: {}, query: {} },
  bootstrap: null,
  vehicles: [],
  vehicle: null,
  requests: [],
  favorites: [],
  customer: null,
  loading: false,
  error: null,
  notice: null,
};

export function parseHash(hash = '') {
  const raw = hash.replace(/^#\/?/, '');
  const [path = '', search = ''] = raw.split('?');
  const parts = path.split('/').filter(Boolean);
  const query = Object.fromEntries(new URLSearchParams(search));
  if (parts[0] === 'vehicle' && parts[1]) return { name: 'vehicle', params: { slug: decodeURIComponent(parts[1]) }, query };
  const supported = ['home', 'vehicles', 'pricing', 'quote', 'requests', 'account', 'favorites', 'share'];
  return { name: supported.includes(parts[0]) ? parts[0] : 'home', params: {}, query };
}

export function reducer(state, action) {
  switch (action.type) {
    case 'NAVIGATE': return { ...state, route: action.route, error: null, notice: null };
    case 'REQUEST_START': return { ...state, loading: true, error: null };
    case 'REQUEST_SUCCESS': return { ...state, loading: false, error: null, ...action.payload };
    case 'REQUEST_ERROR': return { ...state, loading: false, error: action.error };
    case 'NOTICE': return { ...state, notice: action.notice };
    case 'AUTH_EXPIRED': return {
      ...state,
      customer: null,
      requests: [],
      favorites: [],
      loading: false,
      route: { name: 'account', params: {}, query: {} },
      notice: 'نشست شما منقضی شده است؛ دوباره وارد شوید.',
    };
    default: return state;
  }
}
