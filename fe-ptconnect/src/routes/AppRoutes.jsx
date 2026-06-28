import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import AuthLayout from '../layouts/AuthLayout'
import MainLayout from '../layouts/MainLayout'
import AttendanceHistoryPage from '../features/attendance/AttendanceHistoryPage'
import AttendancePage from '../features/attendance/AttendancePage'
import LoginPage from '../features/auth/LoginPage'
import ClassDetailPage from '../features/classes/ClassDetailPage'
import ClassListPage from '../features/classes/ClassListPage'
import NotificationPage from '../features/notifications/NotificationPage'
import ParentDashboardPage from '../features/parents/ParentDashboardPage'
import ParentLoginPage from '../features/parents/ParentLoginPage'
import ScoreListPage from '../features/scores/ScoreListPage'
import ScoreReportPage from '../features/scores/ScoreReportPage'
import StudentCreatePage from '../features/students/StudentCreatePage'
import StudentDetailPage from '../features/students/StudentDetailPage'
import StudentListPage from '../features/students/StudentListPage'
import DashboardPage from '../pages/DashboardPage'
import HomePage from '../pages/HomePage'
import NotFoundPage from '../pages/NotFoundPage'
import UnauthorizedPage from '../pages/UnauthorizedPage'
import ProtectedRoute from './ProtectedRoute'

function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<AuthLayout />}>
          <Route element={<LoginPage />} path="/login" />
          <Route element={<ParentLoginPage />} path="/parent-login" />
        </Route>

        <Route element={<HomePage />} path="/" />
        <Route element={<UnauthorizedPage />} path="/unauthorized" />

        <Route element={<ProtectedRoute />}>
          <Route element={<MainLayout />}>
            <Route element={<DashboardPage />} path="/dashboard" />
            <Route element={<StudentListPage />} path="/students" />
            <Route element={<StudentCreatePage />} path="/students/create" />
            <Route element={<StudentDetailPage />} path="/students/:id" />
            <Route element={<ClassListPage />} path="/classes" />
            <Route element={<ClassDetailPage />} path="/classes/:id" />
            <Route element={<AttendancePage />} path="/attendance" />
            <Route element={<AttendanceHistoryPage />} path="/attendance/history" />
            <Route element={<ScoreListPage />} path="/scores" />
            <Route element={<ScoreReportPage />} path="/scores/report" />
            <Route element={<NotificationPage />} path="/notifications" />
            <Route element={<ParentDashboardPage />} path="/parent" />
          </Route>
        </Route>

        <Route element={<Navigate replace to="/dashboard" />} path="/home" />
        <Route element={<NotFoundPage />} path="*" />
      </Routes>
    </BrowserRouter>
  )
}

export default AppRoutes
