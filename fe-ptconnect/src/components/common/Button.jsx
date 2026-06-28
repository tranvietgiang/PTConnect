import { cn } from '../../utils/helpers'

const variants = {
  primary: 'bg-brand-teal text-brand-white hover:bg-brand-teal-dark focus-visible:ring-brand-teal',
  secondary:
    'border border-brand-border bg-brand-white text-brand-text hover:bg-brand-bg focus-visible:ring-brand-border',
  danger: 'bg-brand-red text-brand-white hover:bg-brand-red/90 focus-visible:ring-brand-red',
  ghost: 'text-brand-muted hover:bg-brand-bg focus-visible:ring-brand-border',
}

function Button({
  as,
  children,
  className = '',
  icon: Icon,
  iconClassName = 'size-4',
  type = 'button',
  variant = 'primary',
  ...props
}) {
  const Component = as || 'button'
  const componentProps = Component === 'button' ? { type, ...props } : props

  return (
    <Component
      className={cn(
        'inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-md px-4 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
        variants[variant],
        className,
      )}
      {...componentProps}
    >
      {Icon ? <Icon aria-hidden="true" className={iconClassName} /> : null}
      {children}
    </Component>
  )
}

export default Button
