import axios from 'axios';
import { clearAuth } from '../app/auth';

const baseURL = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '');

export const apiClient = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
  },
});

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('stripedesk_token');
  if (token) {
    config.headers = config.headers || {};
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

apiClient.interceptors.response.use(
  (res) => res,
  (err) => {
    const status = err?.response?.status;
    if (status === 401) {
      clearAuth();
    }
    return Promise.reject(err);
  }
);

export async function apiFetch(path, options = {}) {
  const method = (options.method || 'GET').toUpperCase();
  const config = {
    url: path,
    method,
    params: options.params,
    headers: options.headers,
    data: options.body,
  };
  try {
    const res = await apiClient.request(config);
    return res.data;
  } catch (e) {
    const data = e?.response?.data;
    const msg = data?.error?.message || e?.message || 'Request failed';
    const err = new Error(msg);
    err.status = e?.response?.status;
    err.body = data;
    throw err;
  }
}

