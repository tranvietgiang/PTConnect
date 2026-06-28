const ACCESS_TOKEN_KEY = 'ptconnect_access_token'
const REFRESH_TOKEN_KEY = 'ptconnect_refresh_token'

export function setTokens(accessToken, refreshToken) {
  if (accessToken) {
    sessionStorage.setItem(ACCESS_TOKEN_KEY, accessToken)
  }

  if (refreshToken) {
    sessionStorage.setItem(REFRESH_TOKEN_KEY, refreshToken)
  } else {
    sessionStorage.removeItem(REFRESH_TOKEN_KEY)
  }
}

export function getAccessToken() {
  return sessionStorage.getItem(ACCESS_TOKEN_KEY)
}

export function getRefreshToken() {
  return sessionStorage.getItem(REFRESH_TOKEN_KEY)
}

export function clearTokens() {
  sessionStorage.removeItem(ACCESS_TOKEN_KEY)
  sessionStorage.removeItem(REFRESH_TOKEN_KEY)
}
