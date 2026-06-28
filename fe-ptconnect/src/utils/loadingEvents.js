export const LOADING_EVENT = 'ptconnect:loading'

export function emitLoading(isLoading, label = 'Đang xử lý') {
  window.dispatchEvent(
    new CustomEvent(LOADING_EVENT, {
      detail: {
        isLoading,
        label,
      },
    }),
  )
}
