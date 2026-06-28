function Input({ error, id, label, className = '', ...props }) {
  return (
    <label className="block" htmlFor={id}>
      {label ? (
        <span className="mb-1.5 block text-sm font-medium text-brand-text">
          {label}
        </span>
      ) : null}
      <input
        id={id}
        className={`h-10 w-full rounded-md border border-brand-border bg-brand-white px-3 text-sm text-brand-text outline-none transition placeholder:text-brand-muted focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft ${className}`}
        {...props}
      />
      {error ? <span className="mt-1 block text-sm text-brand-red">{error}</span> : null}
    </label>
  )
}

export default Input
