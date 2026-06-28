import { toast as sonnerToast } from 'sonner'

export const TOAST_EVENT = 'ptconnect:toast'

export function emitToast(toast) {
  const notify = sonnerToast[toast.type] || sonnerToast

  notify(toast.title, {
    description: toast.message,
    duration: toast.duration,
  })
}
