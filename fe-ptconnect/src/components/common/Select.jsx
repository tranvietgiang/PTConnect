function Select({ children, error, id, label, className = '', ...props }) {
  return (
    <label className={`block ${className}`} htmlFor={id}>
      {label ? <span className="mb-1.5 block text-sm font-medium text-brand-text">{label}</span> : null}
      <select
        id={id}
        className="h-10 w-full cursor-pointer rounded-md border border-brand-border bg-brand-white px-3 text-sm text-brand-text outline-none transition disabled:cursor-not-allowed disabled:opacity-60 focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft"
        {...props}
      >
        {children}
      </select>
      {error ? <span className="mt-1 block text-sm text-brand-red">{error}</span> : null}
    </label>
  )
}

export default Select
