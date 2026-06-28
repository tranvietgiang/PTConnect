import axiosClient from './axiosClient'

export const authApi = {
  login: (payload) =>
    axiosClient.post('/auth/login', payload, {
      loadingLabel: 'Đang đăng nhập',
      skipAuthRefresh: true,
      withCredentials: true,
    }),
  parentLogin: (payload) =>
    axiosClient.post('/auth/login', payload, {
      loadingLabel: 'Đang đăng nhập',
      skipAuthRefresh: true,
      withCredentials: true,
    }),
  refresh: (refreshToken) =>
    axiosClient.post(
      '/auth/refresh',
      refreshToken ? { refresh_token: refreshToken } : {},
      {
        showLoading: false,
        withCredentials: true,
      },
    ),
  logout: (refreshToken) =>
    axiosClient.post(
      '/auth/logout',
      refreshToken ? { refresh_token: refreshToken } : {},
      {
        skipAuthRefresh: true,
        withCredentials: true,
      },
    ),
  me: () =>
    axiosClient.get('/me', {
      showLoading: false,
      skipAuthRefresh: true,
      withCredentials: true,
    }),
}
