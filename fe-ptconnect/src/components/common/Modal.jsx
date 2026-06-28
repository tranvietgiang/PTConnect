import { X } from 'lucide-react'
import Button from './Button'

function Modal({ children, isOpen, onClose, title }) {
  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-brand-text/40 p-4">
      <div className="w-full max-w-lg rounded-lg bg-brand-white shadow-xl">
        <div className="flex items-center justify-between border-b border-brand-border px-5 py-4">
          <h2 className="text-lg font-semibold text-brand-text">{title}</h2>
          <Button
            aria-label="Close modal"
            className="size-9 px-0"
            icon={X}
            onClick={onClose}
            variant="ghost"
          />
        </div>
        <div className="p-5">{children}</div>
      </div>
    </div>
  )
}

export default Modal
