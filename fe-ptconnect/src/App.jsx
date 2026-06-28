import { Toaster } from 'sonner'
import LoadingOverlay from './components/common/LoadingOverlay'
import AppRoutes from './routes/AppRoutes'
import { AuthProvider } from './store/AuthContext'

function App() {
  return (
    <>
      <AuthProvider>
        <AppRoutes />
      </AuthProvider>
      <Toaster
        closeButton
        position="top-right"
        richColors
        toastOptions={{
          classNames: {
            toast: 'border-brand-border bg-brand-white text-brand-text',
            description: 'text-brand-muted',
          },
        }}
      />
      <LoadingOverlay />
    </>
  )
}

export default App
