import axios from 'axios'
import { emitLoading } from '../utils/loadingEvents'
import {
  clearTokens,
  getAccessToken,
  getRefreshToken,
  setTokens,
} from '../utils/tokenStorage'
import { emitToast } from '../utils/toastEvents'

function resolveBaseURL() {
  const configuredURL = import.meta.env.VITE_API_URL
  const fallbackURL = `${window.location.protocol}//${window.location.hostname}:8000/api`

  if (!configuredURL) return fallbackURL

  try {
    const url = new URL(configuredURL)
    const devHosts = ['localhost', '127.0.0.1', '192.168.33.12']

    if (devHosts.includes(url.hostname) && window.location.hostname !== url.hostname) {
      url.hostname = window.location.hostname
      return url.toString().replace(/\/$/, '')
    }
  } catch {
    return fallbackURL
  }

  return configuredURL
}

const baseURL = resolveBaseURL()

const axiosClient = axios.create({
  baseURL,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

const refreshClient = axios.create({
  baseURL,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

let refreshPromise = null
let pendingRequests = 0

function startLoading(config) {
  if (config?.showLoading === false) return

  pendingRequests += 1
  emitLoading(true, config?.loadingLabel || 'Đang tải dữ liệu')
}

function stopLoading(config) {
  if (config?.showLoading === false) return

  pendingRequests = Math.max(0, pendingRequests - 1)

  if (pendingRequests === 0) {
    emitLoading(false)
  }
}

function shouldSkipRefresh(config) {
  const url = config?.url || ''

  return (
    config?.skipAuthRefresh ||
    url.includes('/auth/login') ||
    url.includes('/auth/logout') ||
    url.includes('/auth/refresh') ||
    url.endsWith('/me')
  )
}

axiosClient.interceptors.request.use((config) => {
  startLoading(config)

  const token = getAccessToken()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

axiosClient.interceptors.response.use(
  (response) => {
    stopLoading(response.config)
    return response.data
  },
  async (error) => {
    const originalRequest = error.config
    stopLoading(originalRequest)

    if (
      error.response?.status !== 401 ||
      originalRequest?._retry ||
      shouldSkipRefresh(originalRequest)
    ) {
      return Promise.reject(error.response?.data || error)
    }

    originalRequest._retry = true

    try {
      const refreshToken = getRefreshToken()
      const payload = refreshToken ? { refresh_token: refreshToken } : {}

      refreshPromise =
        refreshPromise ||
        refreshClient.post('/auth/refresh', payload, {
          showLoading: false,
          withCredentials: true,
        })

      const response = await refreshPromise
      const tokens = response.data.data

      setTokens(tokens.access_token, tokens.refresh_token)
      originalRequest.headers = originalRequest.headers || {}
      originalRequest.headers.Authorization = `Bearer ${tokens.access_token}`

      return axiosClient(originalRequest)
    } catch (refreshError) {
      clearTokens()
      emitToast({
        title: 'Phiên đăng nhập đã hết hạn',
        message: 'Vui lòng đăng nhập lại.',
        type: 'error',
      })
      return Promise.reject(refreshError.response?.data || refreshError)
    } finally {
      refreshPromise = null
    }
  },
)

export default axiosClient
