import apiClient from './client';
import type { ApiSuccess, LoginResponse, User } from './types';

// ─── Auth ─────────────────────────────────────────────────────────────────────

export const authApi = {
  login: async (username: string, password: string) => {
    const res = await apiClient.post<ApiSuccess<LoginResponse>>('/login', {
      username,
      password,
    });
    return res.data;
  },

  getMe: async () => {
    const res = await apiClient.get<ApiSuccess<{ user: User }>>('/me');
    return res.data;
  },

  logout: async () => {
    // JWT is stateless — just clear local storage (handled by auth store)
    return Promise.resolve();
  },
};
