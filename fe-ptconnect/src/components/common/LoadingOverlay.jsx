import { useEffect, useState } from 'react'
import { LOADING_EVENT } from '../../utils/loadingEvents'

function LoadingOverlay() {
  const [state, setState] = useState({
    isLoading: false,
    label: 'Đang xử lý',
  })

  useEffect(() => {
    const handleLoading = (event) => {
      setState({
        isLoading: Boolean(event.detail?.isLoading),
        label: event.detail?.label || 'Đang xử lý',
      })
    }

    window.addEventListener(LOADING_EVENT, handleLoading)

    return () => window.removeEventListener(LOADING_EVENT, handleLoading)
  }, [])

  if (!state.isLoading) return null

  return (
    <div className="fixed inset-0 z-[70] grid place-items-center bg-brand-text/35 p-4 backdrop-blur-sm">
      <div className="flex min-w-48 flex-col items-center gap-3 rounded-lg border border-brand-border bg-brand-white px-6 py-5 text-center shadow-xl">
        <span className="size-9 animate-spin rounded-full border-4 border-brand-border border-t-brand-teal" />
        <p className="text-sm font-semibold text-brand-text">{state.label}</p>
      </div>
    </div>
  )
}

export default LoadingOverlay
