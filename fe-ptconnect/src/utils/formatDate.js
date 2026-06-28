function parseDate(value) {
  if (!value) return ''

  const normalizedValue = typeof value === 'string' ? value.replace(' ', 'T') : value
  const date = new Date(normalizedValue)

  return Number.isNaN(date.getTime()) ? '' : date
}

export function formatDate(value) {
  const date = parseDate(value)

  if (!date) return ''

  return new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

export function formatDateTime(value) {
  const date = parseDate(value)

  if (!date) return ''

  return new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}
