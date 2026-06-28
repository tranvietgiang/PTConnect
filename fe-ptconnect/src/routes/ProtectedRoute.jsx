import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../store/useAuth'

function ProtectedRoute({ allowedRoles }) {
  const { isAuthenticated, user } = useAuth()
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate replace state={{ from: location }} to="/login" />
  }

  if (allowedRoles?.length && !allowedRoles.includes(user?.role)) {
    return <Navigate replace to="/unauthorized" />
  }

  return <Outlet />
}

export default ProtectedRoute
