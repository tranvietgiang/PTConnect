import AppRoutes from './routes/AppRoutes'
import { AuthProvider } from './store/AuthContext'
import { ToastProvider } from './store/ToastContext'

function App() {
  return (
    <ToastProvider>
      <AuthProvider>
        <AppRoutes />
      </AuthProvider>
    </ToastProvider>
  )
}

export default App
