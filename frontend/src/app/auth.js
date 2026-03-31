import { reactive } from 'vue';
import { apiFetch } from '../services/apiClient';

const TOKEN_KEY = 'stripedesk_token';

export const auth = reactive({
  token: localStorage.getItem(TOKEN_KEY) || '',
  profile: null,
});

export function setToken(token) {
  auth.token = token || '';
  if (auth.token) {
    localStorage.setItem(TOKEN_KEY, auth.token);
  } else {
    localStorage.removeItem(TOKEN_KEY);
  }
}

export function clearAuth() {
  setToken('');
  auth.profile = null;
}

export function restoreSession() {
  auth.token = localStorage.getItem(TOKEN_KEY) || '';
}

export async function ensureProfile() {
  if (!auth.token) return null;
  if (auth.profile) return auth.profile;
  const me = await apiFetch('/api/v1/auth/me');
  auth.profile = me.data;
  return auth.profile;
}

export async function login(email, password) {
  const res = await apiFetch('/api/v1/auth/login', {
    method: 'POST',
    body: { email, password },
  });
  if (res?.success && res?.data?.access_token) {
    return loginWithTokenPayload(res.data);
  }
  throw new Error('Unexpected response');
}

/** After verify-OTP (registration) or reset-password; same shape as login response `data`. */
export async function loginWithTokenPayload(data) {
  if (!data?.access_token) {
    throw new Error('Missing access token');
  }
  setToken(data.access_token);
  auth.token = data.access_token;
  const me = await apiFetch('/api/v1/auth/me');
  auth.profile = me.data;
  return auth.profile;
}

export function logout() {
  clearAuth();
}

