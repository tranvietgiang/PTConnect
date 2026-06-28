import { useEffect, useMemo, useRef, useState } from 'react'
import { authApi } from '../api/authApi'
import { AuthContext } from './auth-context'
import {
  clearTokens,
  getAccessToken,
  getRefreshToken,
  setTokens,
} from '../utils/tokenStorage'

export function AuthProvider({ children }) {
  const storedToken = getAccessToken()
  const [token, setToken] = useState(storedToken)
  const [user, setUser] = useState(null)
  const [checkingAuth, setCheckingAuth] = useState(true)
  const authVersionRef = useRef(0)

  useEffect(() => {
    let mounted = true

    async function restoreSession() {
      const restoreVersion = authVersionRef.current
      const tokenBeforeRestore = getAccessToken()

      try {
        const response = await authApi.me()
        const authData = response.data

        if (!mounted) return

        if (authData?.access_token) {
          setTokens(authData.access_token, authData.refresh_token)
          setToken(authData.access_token)
        } else {
          setToken(getAccessToken())
        }

        setUser(authData?.user || null)
      } catch {
        if (!mounted) return

        if (restoreVersion !== authVersionRef.current) {
          return
        }

        const currentAccessToken = getAccessToken()

        if (currentAccessToken && currentAccessToken !== tokenBeforeRestore) {
          setToken(currentAccessToken)
          return
        }

        const refreshToken = getRefreshToken()

        if (refreshToken) {
          try {
            const refreshResponse = await authApi.refresh(refreshToken)
            const refreshData = refreshResponse.data
            setTokens(refreshData.access_token, refreshData.refresh_token)
            setToken(refreshData.access_token)

            const meResponse = await authApi.me()
            setUser(meResponse.data?.user || null)
            return
          } catch {
            // Fall through and clear the expired local session.
          }
        }

        if (restoreVersion === authVersionRef.current) {
          clearTokens()
          setToken(null)
          setUser(null)
        }
      } finally {
        if (mounted) {
          setCheckingAuth(false)
        }
      }
    }

    restoreSession()

    return () => {
      mounted = false
    }
  }, [])

  const login = async (credentials) => {
    authVersionRef.current += 1
    clearTokens()
    setToken(null)
    setUser(null)

    const response = await authApi.login({
      email: credentials.email,
      username: credentials.username,
      password: credentials.password,
      remember_me: Boolean(credentials.remember_me),
    })
    const authData = response.data

    setTokens(authData.access_token, authData.refresh_token)
    setToken(authData.access_token)
    setUser(authData.user)
    setCheckingAuth(false)

    return response
  }

  const logout = async () => {
    authVersionRef.current += 1
    const refreshToken = getRefreshToken()

    try {
      await authApi.logout(refreshToken)
    } catch {
      // Clear local state even when the server token was already invalid.
    }

    clearTokens()
    setToken(null)
    setUser(null)
  }

  const value = useMemo(
    () => ({
      checkingAuth,
      isAuthenticated: Boolean(token || user || getAccessToken()),
      login,
      logout,
      token,
      user,
    }),
    [checkingAuth, token, user],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
