export function getDefaultRouteByRole(role) {
  switch (role) {
    case 'system_admin':
    case 'school_admin':
      return '/tong-quan'
    case 'teacher':
      return '/hoc-sinh'
    case 'assistant':
      return '/diem-danh'
    case 'student':
      return '/phu-huynh'
    default:
      return '/dang-nhap'
  }
}

export function getSafeRedirectPath(role, pathname) {
  if (!pathname) {
    return getDefaultRouteByRole(role)
  }

  if (['/', '/dang-nhap', '/phu-huynh/dang-nhap', '/login', '/parent-login', '/khong-co-quyen'].includes(pathname)) {
    return getDefaultRouteByRole(role)
  }

  if (pathname === '/tong-quan' && !['system_admin', 'school_admin'].includes(role)) {
    return getDefaultRouteByRole(role)
  }

  return pathname
}
