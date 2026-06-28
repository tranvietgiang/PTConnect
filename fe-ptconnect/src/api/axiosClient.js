import axios from 'axios'
import { emitLoading } from '../utils/loadingEvents'
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
      originalRequest?.url?.includes('/auth/refresh')
    ) {
      return Promise.reject(error.response?.data || error)
    }

    const refreshToken = getRefreshToken()

    if (!refreshToken) {
      clearTokens()
      emitToast({
        title: 'Phiên đăng nhập đã hết hạn',
        message: 'Vui lòng đăng nhập lại.',
        type: 'error',
      })
      window.location.assign('/dang-nhap')
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
        title: 'Phiên đăng nhập đã hết hạn',
        message: 'Vui lòng đăng nhập lại.',
        type: 'error',
      })
      window.location.assign('/dang-nhap')
      return Promise.reject(refreshError.response?.data || refreshError)
    } finally {
      refreshPromise = null
    }
  },
)

export default axiosClient
