import { authApi } from '../../api/authApi'

export const authService = {
  login: async (credentials) => authApi.login(credentials),
  parentLogin: async (credentials) => authApi.parentLogin(credentials),
  logout: async (refreshToken) => authApi.logout(refreshToken),
}
