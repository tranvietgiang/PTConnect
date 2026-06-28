import { useCallback, useEffect, useMemo, useState } from 'react'
import { CheckCircle2, Info, TriangleAlert, X } from 'lucide-react'
import Button from '../components/common/Button'
import { ToastContext } from './toast-context'
import { TOAST_EVENT } from '../utils/toastEvents'

const icons = {
  error: TriangleAlert,
  info: Info,
  success: CheckCircle2,
}

const styles = {
  error: 'border-brand-red/30 bg-brand-red-soft text-brand-red',
  info: 'border-brand-teal/25 bg-brand-teal-soft text-brand-teal-dark',
  success: 'border-brand-teal/25 bg-brand-teal-soft text-brand-teal-dark',
}

function ToastItem({ toast, onClose }) {
  const Icon = icons[toast.type] || Info

  return (
    <div
      className={`flex w-full items-start gap-3 rounded-lg border p-4 shadow-lg ${styles[toast.type] || styles.info}`}
      role="status"
    >
      <Icon aria-hidden="true" className="mt-0.5 size-5 shrink-0" />
      <div className="min-w-0 flex-1">
        <p className="text-sm font-semibold">{toast.title}</p>
        {toast.message ? (
          <p className="mt-1 text-sm text-brand-muted">{toast.message}</p>
        ) : null}
      </div>
      <Button
        aria-label="Close notification"
        className="size-7 shrink-0 px-0"
        icon={X}
        onClick={() => onClose(toast.id)}
        variant="ghost"
      />
    </div>
  )
}

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])

  const removeToast = useCallback((id) => {
    setToasts((current) => current.filter((toast) => toast.id !== id))
  }, [])

  const showToast = useCallback(
    ({ duration = 3500, message, title, type = 'info' }) => {
      const id = crypto.randomUUID()

      setToasts((current) => [
        ...current,
        {
          id,
          message,
          title,
          type,
        },
      ])

      if (duration > 0) {
        window.setTimeout(() => removeToast(id), duration)
      }

      return id
    },
    [removeToast],
  )

  useEffect(() => {
    const handleToast = (event) => showToast(event.detail)

    window.addEventListener(TOAST_EVENT, handleToast)

    return () => window.removeEventListener(TOAST_EVENT, handleToast)
  }, [showToast])

  const value = useMemo(
    () => ({
      error: (title, message, options) =>
        showToast({ ...options, message, title, type: 'error' }),
      info: (title, message, options) =>
        showToast({ ...options, message, title, type: 'info' }),
      success: (title, message, options) =>
        showToast({ ...options, message, title, type: 'success' }),
    }),
    [showToast],
  )

  return (
    <ToastContext.Provider value={value}>
      {children}
      <div className="fixed right-4 top-4 z-[60] flex w-[min(420px,calc(100vw-2rem))] flex-col gap-3">
        {toasts.map((toast) => (
          <ToastItem key={toast.id} onClose={removeToast} toast={toast} />
        ))}
      </div>
    </ToastContext.Provider>
  )
}
