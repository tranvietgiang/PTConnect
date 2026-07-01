export function getDefaultRouteByRole(role) {
  switch (role) {
    case 'school_admin':
    case 'system_admin':
      return '/tong-quan'
    case 'teacher':
      return '/hoc-sinh'
    case 'assistant':
      return '/diem-danh'
    case 'student':
      return '/diem-cua-toi'
    default:
      return '/dang-nhap'
  }
}

export function getSafeRedirectPath(role, pathname) {
  if (!pathname) {
    return getDefaultRouteByRole(role)
  }

  if (['/', '/dang-nhap', '/login', '/khong-co-quyen'].includes(pathname)) {
    return getDefaultRouteByRole(role)
  }

  if (pathname === '/tong-quan' && role !== 'school_admin' && role !== 'system_admin') {
    return getDefaultRouteByRole(role)
  }

  return pathname
}
