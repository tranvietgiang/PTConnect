import { lazy, Suspense } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import AuthLayout from '../layouts/AuthLayout'
import MainLayout from '../layouts/MainLayout'
import Loading from '../components/common/Loading'
import { useAuth } from '../store/useAuth'
import { getDefaultRouteByRole } from '../utils/roleRedirect'
import ProtectedRoute from './ProtectedRoute'

const AttendanceHistoryPage = lazy(() => import('../features/attendance/AttendanceHistoryPage'))
const AttendancePage = lazy(() => import('../features/attendance/AttendancePage'))
const AttendanceSessionManagePage = lazy(() => import('../features/attendance/AttendanceSessionManagePage'))
const AssignmentPage = lazy(() => import('../features/assignments/AssignmentPage'))
const LoginPage = lazy(() => import('../features/auth/LoginPage'))
const ClassCreatePage = lazy(() => import('../features/classes/ClassCreatePage'))
const ClassDetailPage = lazy(() => import('../features/classes/ClassDetailPage'))
const ClassListPage = lazy(() => import('../features/classes/ClassListPage'))
const NotificationPage = lazy(() => import('../features/notifications/NotificationPage'))
const ParentDashboardPage = lazy(() => import('../features/parents/ParentDashboardPage'))
const ParentLoginPage = lazy(() => import('../features/parents/ParentLoginPage'))
const ParentScorePage = lazy(() => import('../features/scores/ParentScorePage'))
const ScoreListPage = lazy(() => import('../features/scores/ScoreListPage'))
const ScoreReportPage = lazy(() => import('../features/scores/ScoreReportPage'))
const StudentCreatePage = lazy(() => import('../features/students/StudentCreatePage'))
const StudentDetailPage = lazy(() => import('../features/students/StudentDetailPage'))
const StudentListPage = lazy(() => import('../features/students/StudentListPage'))
const DashboardPage = lazy(() => import('../pages/DashboardPage'))
const HomePage = lazy(() => import('../pages/HomePage'))
const NotFoundPage = lazy(() => import('../pages/NotFoundPage'))
const UnauthorizedPage = lazy(() => import('../pages/UnauthorizedPage'))

const adminRoles = ['system_admin', 'school_admin']
const staffRoles = [...adminRoles, 'teacher', 'assistant']
const studentManageRoles = [...adminRoles, 'teacher']
const gradeRoles = [...adminRoles, 'teacher', 'assistant']
const attendanceRoles = [...adminRoles, 'teacher', 'assistant']

function RoleRedirect() {
  const { checkingAuth, isAuthenticated, user } = useAuth()

  if (checkingAuth) {
    return <Loading label="Đang kiểm tra đăng nhập" />
  }

  return <Navigate replace to={isAuthenticated ? getDefaultRouteByRole(user?.role) : '/dang-nhap'} />
}

function AppRoutes() {
  return (
    <BrowserRouter>
      <Suspense fallback={<Loading label="Đang tải trang" />}>
        <Routes>
          <Route element={<AuthLayout />}>
            <Route element={<LoginPage />} path="/dang-nhap" />
            <Route element={<ParentLoginPage />} path="/phu-huynh/dang-nhap" />
          </Route>

          <Route element={<HomePage />} path="/" />
          <Route element={<UnauthorizedPage />} path="/khong-co-quyen" />

          <Route element={<ProtectedRoute />}>
            <Route element={<MainLayout />}>
              <Route element={<ProtectedRoute allowedRoles={adminRoles} />}>
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
                <Route element={<AttendanceSessionManagePage />} path="/buoi-hoc" />
              </Route>

              <Route element={<ProtectedRoute allowedRoles={studentManageRoles} />}>
                <Route element={<StudentCreatePage />} path="/hoc-sinh/them" />
              </Route>

              <Route element={<ProtectedRoute allowedRoles={adminRoles} />}>
                <Route element={<ClassCreatePage />} path="/lop-hoc/them" />
              </Route>

              <Route element={<ProtectedRoute allowedRoles={gradeRoles} />}>
                <Route element={<ScoreListPage />} path="/diem-so" />
              </Route>

              <Route element={<ProtectedRoute allowedRoles={gradeRoles} />}>
                <Route element={<ScoreReportPage />} path="/diem-so/bao-cao" />
              </Route>

              <Route element={<ProtectedRoute allowedRoles={[...adminRoles, 'teacher']} />}>
                <Route element={<NotificationPage />} path="/thong-bao" />
              </Route>

              <Route element={<ProtectedRoute allowedRoles={[...adminRoles, 'teacher', 'student']} />}>
                <Route element={<AssignmentPage />} path="/bai-tap" />
              </Route>

              <Route element={<ProtectedRoute allowedRoles={['student']} />}>
                <Route element={<ParentDashboardPage />} path="/phu-huynh" />
                <Route element={<ParentScorePage />} path="/phu-huynh/diem-so" />
              </Route>
            </Route>
          </Route>

          <Route element={<RoleRedirect />} path="/home" />
          <Route element={<Navigate replace to="/dang-nhap" />} path="/login" />
          <Route element={<Navigate replace to="/phu-huynh/dang-nhap" />} path="/parent-login" />
          <Route element={<RoleRedirect />} path="/dashboard" />
          <Route element={<NotFoundPage />} path="*" />
        </Routes>
      </Suspense>
    </BrowserRouter>
  )
}

export default AppRoutes
