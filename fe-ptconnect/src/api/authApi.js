import axiosClient from './axiosClient'

export const authApi = {
  login: (email, password) => axiosClient.post('/auth/login', { email, password }),
  parentLogin: (email, password) => axiosClient.post('/auth/login', { email, password }),
  refresh: (refreshToken) => axiosClient.post('/auth/refresh', { refresh_token: refreshToken }),
  logout: (refreshToken) => axiosClient.post('/auth/logout', { refresh_token: refreshToken }),
  me: () => axiosClient.get('/auth/me'),
}
