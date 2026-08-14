import { createContext, useContext, useEffect, useState, type ReactNode } from 'react'
import { authService } from '../services/authService'
import type { User } from '../types/voice'

interface AuthContextValue {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (name: string, email: string, password: string, passwordConfirmation: string) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!authService.isAuthenticated()) {
      setLoading(false)
      return
    }

    authService
      .getUser()
      .then(setUser)
      .catch(() => setUser(null))
      .finally(() => setLoading(false))
  }, [])

  async function login(email: string, password: string) {
    const loggedInUser = await authService.login(email, password)
    setUser(loggedInUser)
  }

  async function register(name: string, email: string, password: string, passwordConfirmation: string) {
    const newUser = await authService.register(name, email, password, passwordConfirmation)
    setUser(newUser)
  }

  async function logout() {
    await authService.logout()
    setUser(null)
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout }}>{children}</AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}
