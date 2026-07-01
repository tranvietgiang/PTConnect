import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import AuthLayout from '../layouts/AuthLayout'
import MainLayout from '../layouts/MainLayout'
import AttendanceHistoryPage from '../features/attendance/AttendanceHistoryPage'
import AttendancePage from '../features/attendance/AttendancePage'
import AssignmentPage from '../features/assignments/AssignmentPage'
import LoginPage from '../features/auth/LoginPage'
import ClassCreatePage from '../features/classes/ClassCreatePage'
import ClassDetailPage from '../features/classes/ClassDetailPage'
import ClassListPage from '../features/classes/ClassListPage'
import ScoreListPage from '../features/scores/ScoreListPage'
import ScoreReportPage from '../features/scores/ScoreReportPage'
import StudentCreatePage from '../features/students/StudentCreatePage'
import StudentDetailPage from '../features/students/StudentDetailPage'
import StudentListPage from '../features/students/StudentListPage'
import Loading from '../components/common/Loading'
import DashboardPage from '../pages/DashboardPage'
import HomePage from '../pages/HomePage'
import NotFoundPage from '../pages/NotFoundPage'
import UnauthorizedPage from '../pages/UnauthorizedPage'
import { useAuth } from '../store/useAuth'
import { getDefaultRouteByRole } from '../utils/roleRedirect'
import ProtectedRoute from './ProtectedRoute'

const schoolRoles = ['school_admin', 'system_admin']
const staffRoles = ['school_admin', 'system_admin', 'teacher', 'assistant']
const attendanceRoles = ['school_admin', 'system_admin', 'teacher', 'assistant']

function RoleRedirect() {
  const { checkingAuth, isAuthenticated, user } = useAuth()

  if (checkingAuth) {
    return <Loading label="Đang kiểm tra đăng nhập" />
  }

  return (
    <Navigate
      replace
      to={isAuthenticated ? getDefaultRouteByRole(user?.role) : '/dang-nhap'}
    />
  )
}

function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<AuthLayout />}>
          <Route element={<LoginPage />} path="/dang-nhap" />
        </Route>

        <Route element={<HomePage />} path="/" />
        <Route element={<UnauthorizedPage />} path="/khong-co-quyen" />

        <Route element={<ProtectedRoute />}>
          <Route element={<MainLayout />}>
            <Route element={<ProtectedRoute allowedRoles={schoolRoles} />}>
              <Route element={<DashboardPage />} path="/tong-quan" />
            </Route>

            <Route element={<ProtectedRoute allowedRoles={staffRoles} />}>
              <Route element={<StudentListPage />} path="/hoc-sinh" />
              <Route element={<StudentDetailPage />} path="/hoc-sinh/:id" />
              <Route element={<ClassListPage />} path="/lop-hoc" />
              <Route element={<ClassDetailPage />} path="/lop-hoc/:id" />
            </Route>

            <Route element={<ProtectedRoute allowedRoles={attendanceRoles} />}>
              <Route element={<AttendancePage />} path="/diem-danh" />
              <Route element={<AttendanceHistoryPage />} path="/diem-danh/lich-su" />
            </Route>

            <Route element={<ProtectedRoute allowedRoles={schoolRoles} />}>
              <Route element={<StudentCreatePage />} path="/hoc-sinh/them" />
              <Route element={<ClassCreatePage />} path="/lop-hoc/them" />
            </Route>

            <Route element={<ProtectedRoute allowedRoles={['school_admin', 'system_admin', 'teacher', 'assistant']} />}>
              <Route element={<ScoreListPage />} path="/diem-so" />
              <Route element={<ScoreReportPage />} path="/diem-so/bao-cao" />
            </Route>

            <Route element={<ProtectedRoute allowedRoles={['school_admin', 'system_admin', 'teacher', 'assistant', 'student']} />}>
              <Route element={<AssignmentPage />} path="/bai-tap" />
            </Route>

            <Route element={<ProtectedRoute allowedRoles={['student']} />}>
              <Route element={<ScoreListPage />} path="/diem-cua-toi" />
            </Route>
          </Route>
        </Route>

        <Route element={<RoleRedirect />} path="/home" />
        <Route element={<Navigate replace to="/dang-nhap" />} path="/login" />
        <Route element={<RoleRedirect />} path="/dashboard" />
        <Route element={<NotFoundPage />} path="*" />
      </Routes>
    </BrowserRouter>
  )
}

export default AppRoutes
