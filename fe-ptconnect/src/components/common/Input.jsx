import { Eye, EyeOff } from 'lucide-react'
import { useState } from 'react'

function Input({
  error,
  id,
  label,
  className = '',
  showPasswordToggle = false,
  type = 'text',
  ...props
}) {
  const [showPassword, setShowPassword] = useState(false)
  const inputType = showPasswordToggle && type === 'password' && showPassword ? 'text' : type

  return (
    <label className="block" htmlFor={id}>
      {label ? (
        <span className="mb-1.5 block text-sm font-medium text-brand-text">
          {label}
        </span>
      ) : null}
      <span className="relative block">
        <input
          id={id}
          type={inputType}
          className={`h-10 w-full rounded-md border border-brand-border bg-brand-white px-3 text-sm text-brand-text outline-none transition placeholder:text-brand-muted focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft ${showPasswordToggle ? 'pr-11' : ''} ${className}`}
          {...props}
        />
        {showPasswordToggle && type === 'password' ? (
          <button
            aria-label={showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'}
            className="absolute inset-y-0 right-0 grid w-10 place-items-center text-brand-muted transition hover:text-brand-text"
            onClick={(event) => {
              event.preventDefault()
              setShowPassword((current) => !current)
            }}
            type="button"
          >
            {showPassword ? (
              <EyeOff aria-hidden="true" className="size-4" />
            ) : (
              <Eye aria-hidden="true" className="size-4" />
            )}
          </button>
        ) : null}
      </span>
      {error ? <span className="mt-1 block text-sm text-brand-red">{error}</span> : null}
    </label>
  )
}

export default Input
