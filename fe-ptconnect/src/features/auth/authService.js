import { authApi } from '../../api/authApi'

export const authService = {
  login: async (credentials) => authApi.login(credentials.email, credentials.password),
  parentLogin: async (credentials) => authApi.parentLogin(credentials.email, credentials.password),
  logout: async (refreshToken) => authApi.logout(refreshToken),
}
