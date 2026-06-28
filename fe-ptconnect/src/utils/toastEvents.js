export const TOAST_EVENT = 'ptconnect:toast'

export function emitToast(toast) {
  window.dispatchEvent(new CustomEvent(TOAST_EVENT, { detail: toast }))
}
