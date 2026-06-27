import { create } from 'zustand';
import { storage } from '../utils/storage';
import { authApi } from '../api/auth.api';
import type { User } from '../api/types';

// ─── Keys ─────────────────────────────────────────────────────────────────────
const TOKEN_KEY = 'access_token';

// ─── State ────────────────────────────────────────────────────────────────────
interface AuthState {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;

  // Actions
  login: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  rehydrate: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  token: null,
  isLoading: false,
  isAuthenticated: false,

  // ─── Login ─────────────────────────────────────────────────────────────────
  login: async (username: string, password: string) => {
    set({ isLoading: true });
    try {
      const response = await authApi.login(username, password);

      if (!response.success) {
        throw new Error(response.message ?? 'Login failed');
      }

      // Backend only returns token on login — fetch user details separately
      const { token } = response.data;
      await storage.setItem(TOKEN_KEY, token);

      // Fetch user profile
      const meResponse = await authApi.getMe();
      if (!meResponse.success) {
        throw new Error('Failed to fetch user profile');
      }

      set({
        user: meResponse.data.user,
        token,
        isAuthenticated: true,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },

  // ─── Logout ────────────────────────────────────────────────────────────────
  logout: async () => {
    await storage.deleteItem(TOKEN_KEY);
    set({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,
    });
  },

  // ─── Rehydrate (called on app boot) ────────────────────────────────────────
  rehydrate: async () => {
    set({ isLoading: true });
    try {
      const token = await storage.getItem(TOKEN_KEY);
      if (!token) {
        set({ isLoading: false, isAuthenticated: false });
        return;
      }

      // Validate token by fetching /me
      const meResponse = await authApi.getMe();
      if (meResponse.success) {
        set({
          user: meResponse.data.user,
          token,
          isAuthenticated: true,
          isLoading: false,
        });
      } else {
        // Token is invalid or expired
        await storage.deleteItem(TOKEN_KEY);
        set({ isLoading: false, isAuthenticated: false });
      }
    } catch {
      // Network error or invalid token — clear and go to login
      await storage.deleteItem(TOKEN_KEY);
      set({ isLoading: false, isAuthenticated: false });
    }
  },
}));

// ─── Selector helpers ─────────────────────────────────────────────────────────
export const selectUser = (s: AuthState) => s.user;
export const selectRole = (s: AuthState) => s.user?.role_name?.toLowerCase() ?? null;
export const selectIsAuthenticated = (s: AuthState) => s.isAuthenticated;
export const selectIsLoading = (s: AuthState) => s.isLoading;
