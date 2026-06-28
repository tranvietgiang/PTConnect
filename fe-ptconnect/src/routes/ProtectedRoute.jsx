import { Navigate, Outlet, useLocation } from 'react-router-dom'
import Loading from '../components/common/Loading'
import { useAuth } from '../store/useAuth'
import { getAccessToken } from '../utils/tokenStorage'

function ProtectedRoute({ allowedRoles }) {
  const { checkingAuth, isAuthenticated, user } = useAuth()
  const location = useLocation()
  const hasStoredToken = Boolean(getAccessToken())

  if (checkingAuth) {
    return <Loading label="Đang kiểm tra đăng nhập" />
  }

  if (!isAuthenticated && !hasStoredToken) {
    return <Navigate replace state={{ from: location }} to="/dang-nhap" />
  }

  if (allowedRoles?.length && !user) {
    return <Loading label="Đang tải thông tin tài khoản" />
  }

  if (allowedRoles?.length && !allowedRoles.includes(user?.role)) {
    return <Navigate replace to="/khong-co-quyen" />
  }

  return <Outlet />
}

export default ProtectedRoute
