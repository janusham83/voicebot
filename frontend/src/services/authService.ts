import api from './api'
import type { ApiResponse, User } from '../types/voice'

interface AuthData {
  user: User
  token: string
}

export const authService = {
  async register(name: string, email: string, password: string, passwordConfirmation: string) {
    const { data } = await api.post<ApiResponse<AuthData>>('/register', {
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
    })
    localStorage.setItem('auth_token', data.data.token)
    return data.data.user
  },

  async login(email: string, password: string) {
    const { data } = await api.post<ApiResponse<AuthData>>('/login', { email, password })
    localStorage.setItem('auth_token', data.data.token)
    return data.data.user
  },

  async logout() {
    try {
      await api.post('/logout')
    } finally {
      localStorage.removeItem('auth_token')
    }
  },

  async getUser() {
    const { data } = await api.get<ApiResponse<{ user: User }>>('/user')
    return data.data.user
  },

  isAuthenticated() {
    return Boolean(localStorage.getItem('auth_token'))
  },
}
