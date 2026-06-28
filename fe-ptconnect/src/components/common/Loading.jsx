function Loading({ label = 'Đang tải' }) {
  return (
    <div className="flex items-center justify-center gap-3 py-10 text-sm text-brand-muted">
      <span className="size-5 animate-spin rounded-full border-2 border-brand-border border-t-brand-teal" />
      <span>{label}</span>
    </div>
  )
}

export default Loading
