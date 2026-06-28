import { useState } from 'react'
import { LogIn } from 'lucide-react'
import { Link, useNavigate } from 'react-router-dom'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import { useAuth } from '../../store/useAuth'
import { useToast } from '../../store/useToast'

function ParentLoginPage() {
  const { login } = useAuth()
  const toast = useToast()
  const navigate = useNavigate()
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setLoading(true)
    const form = new FormData(event.currentTarget)

    try {
      await login({
        email: form.get('email'),
        password: form.get('password'),
      })
      toast.success('Đăng nhập thành công', 'Trình duyệt có thể đề xuất lưu mật khẩu cho lần sau.')
      navigate('/phu-huynh')
    } catch (error) {
      toast.error('Đăng nhập thất bại', error.message || 'Vui lòng kiểm tra email và mật khẩu.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form
      autoComplete="on"
      className="rounded-lg border border-brand-border bg-brand-white p-6 shadow-sm"
      onSubmit={handleSubmit}
    >
      <div className="space-y-4">
        <Input
          autoComplete="username"
          id="email"
          label="Email hoặc mã học sinh"
          name="email"
          placeholder="HS100001 hoặc parent@ptconnect.test"
          type="text"
        />
        <Input
          autoComplete="current-password"
          id="password"
          label="Mật khẩu"
          name="password"
          placeholder="Nhập mật khẩu"
          showPasswordToggle
          type="password"
        />
        <label className="flex items-start gap-2 text-sm text-brand-muted">
          <input
            className="mt-1 size-4 rounded border-brand-border text-brand-teal focus:ring-brand-teal"
            name="remember"
            type="checkbox"
          />
          <span>Ghi nhớ tài khoản và cho phép trình duyệt lưu mật khẩu</span>
        </label>
      </div>
      <Button className="mt-6 w-full" disabled={loading} icon={LogIn} type="submit">
        {loading ? 'Đang đăng nhập' : 'Vào cổng phụ huynh'}
      </Button>
      <Link className="mt-4 block text-center text-sm font-semibold text-brand-teal-dark" to="/dang-nhap">
        Đăng nhập nhân sự
      </Link>
    </form>
  )
}

export default ParentLoginPage
