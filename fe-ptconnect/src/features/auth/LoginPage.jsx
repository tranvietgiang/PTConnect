import { useState } from 'react'
import { LogIn } from 'lucide-react'
import { useLocation, useNavigate } from 'react-router-dom'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import { useAuth } from '../../store/useAuth'
import { useToast } from '../../store/useToast'

function LoginPage() {
  const { login } = useAuth()
  const toast = useToast()
  const location = useLocation()
  const navigate = useNavigate()
  const [form, setForm] = useState({ email: '', password: '' })
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setLoading(true)

    try {
      await login(form)
      toast.success('Đăng nhập thành công', 'Trình duyệt có thể đề xuất lưu mật khẩu cho lần sau.')
      navigate(location.state?.from?.pathname || '/tong-quan', { replace: true })
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
          label="Email"
          name="email"
          onChange={(event) => setForm({ ...form, email: event.target.value })}
          placeholder="admin@ptconnect.test"
          type="email"
          value={form.email}
        />
        <Input
          autoComplete="current-password"
          id="password"
          label="Mật khẩu"
          name="password"
          onChange={(event) => setForm({ ...form, password: event.target.value })}
          placeholder="Nhập mật khẩu"
          showPasswordToggle
          type="password"
          value={form.password}
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
        {loading ? 'Đang đăng nhập' : 'Đăng nhập'}
      </Button>
    </form>
  )
}

export default LoginPage
