import axios from 'axios'
import {
  clearTokens,
  getAccessToken,
  getRefreshToken,
  setTokens,
} from '../utils/tokenStorage'
import { emitToast } from '../utils/toastEvents'

const baseURL = import.meta.env.VITE_API_URL || 'http://192.168.33.12:8000/api'

const axiosClient = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

const refreshClient = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

let refreshPromise = null

axiosClient.interceptors.request.use((config) => {
  const token = getAccessToken()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

axiosClient.interceptors.response.use(
  (response) => response.data,
  async (error) => {
    const originalRequest = error.config

    if (
      error.response?.status !== 401 ||
      originalRequest?._retry ||
      originalRequest?.url?.includes('/auth/refresh')
    ) {
      return Promise.reject(error.response?.data || error)
    }

    const refreshToken = getRefreshToken()

    if (!refreshToken) {
      clearTokens()
      emitToast({
        title: 'Session expired',
        message: 'Please sign in again.',
        type: 'error',
      })
      window.location.assign('/login')
      return Promise.reject(error.response?.data || error)
    }

    originalRequest._retry = true

    try {
      refreshPromise =
        refreshPromise ||
        refreshClient.post('/auth/refresh', { refresh_token: refreshToken })

      const response = await refreshPromise
      const tokens = response.data.data

      setTokens(tokens.access_token, tokens.refresh_token)
      originalRequest.headers.Authorization = `Bearer ${tokens.access_token}`

      return axiosClient(originalRequest)
    } catch (refreshError) {
      clearTokens()
      emitToast({
        title: 'Session expired',
        message: 'Please sign in again.',
        type: 'error',
      })
      window.location.assign('/login')
      return Promise.reject(refreshError.response?.data || refreshError)
    } finally {
      refreshPromise = null
    }
  },
)

export default axiosClient
