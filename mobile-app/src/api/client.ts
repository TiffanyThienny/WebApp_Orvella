import axios from 'axios';
import { Platform } from 'react-native';
import Constants from 'expo-constants';
import { storage } from '../utils/storage';

// ─── Base URL ──────────────────────────────────────────────────────────────────
// Automatically resolved to the computer's IP when testing on a physical device in development
const getBaseUrl = () => {
  if (Platform.OS === 'web') {
    return 'http://localhost:8080';
  }

  // Get host IP from Expo bundler (useful when using physical device or custom emulator on LAN)
  const hostUri = Constants.expoConfig?.hostUri; // e.g. "192.168.1.100:8081"
  if (hostUri) {
    const ip = hostUri.split(':')[0];
    return `http://${ip}:8080`;
  }

  // Fallback for default Android emulator or physical device on LAN
  return 'http://10.216.28.237:8080';
};

export const BASE_URL = getBaseUrl();

const apiClient = axios.create({
  baseURL: BASE_URL,
  timeout: 20000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Client-Type': 'mobile', // Required by RequirePlatform middleware
  },
});

// ─── Request Interceptor — attach JWT ─────────────────────────────────────────
apiClient.interceptors.request.use(
  async (config) => {
    const token = await storage.getItem('access_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// ─── Response Interceptor — unwrap envelope & handle 401 ─────────────────────
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Token expired — clear storage and force re-login
      await storage.deleteItem('access_token');
      await storage.deleteItem('refresh_token');
      // Navigation to login is handled by the auth store listener
    }
    return Promise.reject(error);
  }
);

export default apiClient;
