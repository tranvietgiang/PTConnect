import { useMemo, useState } from 'react'
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

  const login = async (credentials) => {
    const response = await authApi.login(credentials.email, credentials.password)
    const authData = response.data

    setTokens(authData.access_token, authData.refresh_token)
    setToken(authData.access_token)
    setUser(authData.user)

    return response
  }

  const logout = async () => {
    const refreshToken = getRefreshToken()

    if (refreshToken) {
      try {
        await authApi.logout(refreshToken)
      } catch {
        // The session is cleared locally even if the server token is already invalid.
      }
    }

    clearTokens()
    setToken(null)
    setUser(null)
  }

  const value = useMemo(
    () => ({
      isAuthenticated: Boolean(token),
      login,
      logout,
      token,
      user,
    }),
    [token, user],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
