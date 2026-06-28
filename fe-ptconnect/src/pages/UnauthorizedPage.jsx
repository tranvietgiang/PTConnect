import { Link } from 'react-router-dom'
import Button from '../components/common/Button'
import Loading from '../components/common/Loading'
import { useAuth } from '../store/useAuth'
import { getDefaultRouteByRole } from '../utils/roleRedirect'

function UnauthorizedPage() {
  const { checkingAuth, user } = useAuth()

  if (checkingAuth) {
    return <Loading label="Đang tải thông tin tài khoản" />
  }

  return (
    <main className="grid min-h-screen place-items-center bg-brand-bg px-4 text-center">
      <div>
        <p className="text-sm font-semibold uppercase tracking-wide text-brand-red">Từ chối truy cập</p>
        <h1 className="mt-2 text-3xl font-bold text-brand-text">Không có quyền</h1>
        <p className="mt-2 text-brand-muted">Tài khoản của bạn không có quyền xem trang này.</p>
        <Button as={Link} className="mt-6" to={getDefaultRouteByRole(user?.role)}>
          Về trang chính
        </Button>
      </div>
    </main>
  )
}

export default UnauthorizedPage
