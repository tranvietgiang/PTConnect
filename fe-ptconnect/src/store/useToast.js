import { toast } from 'sonner'

export function useToast() {
  return {
    error: (title, message, options) =>
      toast.error(title, { ...options, description: message }),
    info: (title, message, options) =>
      toast.info(title, { ...options, description: message }),
    success: (title, message, options) =>
      toast.success(title, { ...options, description: message }),
  }
}
