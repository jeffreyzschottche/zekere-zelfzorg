import { defineStore } from 'pinia';
import type { User } from '~/types/User';
import type { LoginResponse, RegisterResponse, MessageResponse } from '~/types/ApiResponse';

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null);
  const user = ref<User | null>(null);

  const isLoggedIn = computed(() => !!token.value);
  const isPremium = computed(() => !!user.value?.premium);

  function setSession(newToken: string, newUser: User) {
    token.value = newToken;
    user.value = newUser;
    if (import.meta.client) {
      sessionStorage.setItem('auth_token', newToken);
      sessionStorage.setItem('auth_user', JSON.stringify(newUser));
    }
  }

  function logout() {
    token.value = null;
    user.value = null;
    if (import.meta.client) {
      sessionStorage.removeItem('auth_token');
      localStorage.removeItem('auth_token');
      sessionStorage.removeItem('auth_user');
      localStorage.removeItem('auth_user');
    }
  }

  async function restore() {
    if (import.meta.client) {
      const storedToken = sessionStorage.getItem('auth_token');
      const storedUser = sessionStorage.getItem('auth_user');
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
      if (storedToken && storedUser) {
        try {
          user.value = JSON.parse(storedUser);
          token.value = storedToken;
        } catch {
          logout();
        }
      }
    }
  }

  async function login(email: string, password: string) {
    const api = useApi();
    const response = await api.post<LoginResponse>('/login', { email, password });
    setSession(response.token, response.user);
    return response.user;
  }

  async function register(
    name: string,
    email: string,
    password: string,
    password_confirmation: string
  ) {
    const api = useApi();
    const response = await api.post<RegisterResponse>('/register', {
      name,
      email,
      password,
      password_confirmation,
    });
    setSession(response.token, response.user);
    return response.user;
  }

  async function forgotPassword(email: string) {
    const api = useApi();
    return await api.post<MessageResponse>('/forgot-password', { email });
  }

  async function resetPassword(
    resetToken: string,
    email: string,
    password: string,
    password_confirmation: string
  ) {
    const api = useApi();
    return await api.post<MessageResponse>('/reset-password', {
      token: resetToken,
      email,
      password,
      password_confirmation,
    });
  }

  async function resendVerification() {
    const api = useApi();
    return await api.post<MessageResponse>('/email/resend');
  }

  return {
    token,
    user,
    isLoggedIn,
    setSession,
    logout,
    restore,
    login,
    register,
    forgotPassword,
    resetPassword,
    resendVerification,
    isPremium,
  };
});
