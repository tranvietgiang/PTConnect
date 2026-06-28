import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../store/useAuth'

function ProtectedRoute({ allowedRoles }) {
  const { isAuthenticated, user } = useAuth()
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate replace state={{ from: location }} to="/dang-nhap" />
  }

  if (allowedRoles?.length && !allowedRoles.includes(user?.role)) {
    return <Navigate replace to="/khong-co-quyen" />
  }

  return <Outlet />
}

export default ProtectedRoute
