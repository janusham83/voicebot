import { useEffect, useState, type ReactNode } from 'react'
import { authService } from '../services/authService'
import { AuthContext } from './authContext.ts'
import type { User } from '../types/voice'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const fetchUser = async () => {
      if (!authService.isAuthenticated()) {
        return
      }

      try {
        const authenticatedUser = await authService.getUser()
        setUser(authenticatedUser)
      } catch {
        setUser(null)
      }
    }

    fetchUser().finally(() => setLoading(false))
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
