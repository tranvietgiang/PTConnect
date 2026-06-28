import { ArrowRight } from 'lucide-react'
import { Link, Navigate } from 'react-router-dom'
import Button from '../components/common/Button'
import { useAuth } from '../store/useAuth'

function HomePage() {
  const { isAuthenticated } = useAuth()

  if (isAuthenticated) {
    return <Navigate replace to="/tong-quan" />
  }

  return (
    <main className="grid min-h-screen place-items-center bg-brand-bg px-4">
      <section className="w-full max-w-3xl rounded-lg border border-brand-border bg-brand-white p-8 shadow-sm">
        <p className="text-sm font-semibold uppercase tracking-wide text-brand-teal-dark">PTConnect</p>
        <h1 className="mt-3 text-4xl font-bold text-brand-text">Cổng quản lý học sinh</h1>
        <p className="mt-4 max-w-2xl text-brand-muted">
          Quản lý học sinh, lớp học, điểm danh, điểm số, tài khoản phụ huynh và thông báo trong một hệ thống.
        </p>
        <div className="mt-6 flex flex-wrap gap-3">
          <Button as={Link} icon={ArrowRight} to="/dang-nhap">
            Mở trang quản trị
          </Button>
          <Link
            className="inline-flex h-10 items-center rounded-md px-4 text-sm font-semibold text-brand-muted hover:bg-brand-bg"
            to="/phu-huynh/dang-nhap"
          >
            Phụ huynh đăng nhập
          </Link>
        </div>
      </section>
    </main>
  )
}

export default HomePage
