export function getDefaultRouteByRole(role) {
  switch (role) {
    case 'admin':
      return '/tong-quan'
    case 'teacher':
      return '/hoc-sinh'
    case 'assistant':
      return '/diem-danh'
    case 'parent':
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

  if (pathname === '/tong-quan' && role !== 'admin') {
    return getDefaultRouteByRole(role)
  }

  return pathname
}
