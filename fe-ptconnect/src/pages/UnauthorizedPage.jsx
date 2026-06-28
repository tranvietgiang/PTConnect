import { Link } from 'react-router-dom'
import Button from '../components/common/Button'

function UnauthorizedPage() {
  return (
    <main className="grid min-h-screen place-items-center bg-brand-bg px-4 text-center">
      <div>
        <p className="text-sm font-semibold uppercase tracking-wide text-brand-red">Từ chối truy cập</p>
        <h1 className="mt-2 text-3xl font-bold text-brand-text">Không có quyền</h1>
        <p className="mt-2 text-brand-muted">Tài khoản của bạn không có quyền xem trang này.</p>
        <Button as={Link} className="mt-6" to="/tong-quan">
          Về trang tổng quan
        </Button>
      </div>
    </main>
  )
}

export default UnauthorizedPage
