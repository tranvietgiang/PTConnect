import { useState } from 'react'
import { LogIn } from 'lucide-react'
import { Navigate, useLocation, useNavigate } from 'react-router-dom'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Loading from '../../components/common/Loading'
import { useAuth } from '../../store/useAuth'
import { useToast } from '../../store/useToast'

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

function LoginPage() {
  const { checkingAuth, isAuthenticated, login, user } = useAuth()
  const toast = useToast()
  const location = useLocation()
  const navigate = useNavigate()
  const [form, setForm] = useState({
    email: '',
    password: '',
    remember_me: false,
  })
  const [errors, setErrors] = useState({})
  const [loading, setLoading] = useState(false)

  if (checkingAuth) {
    return <Loading label="Đang kiểm tra đăng nhập" />
  }

  if (isAuthenticated) {
    return <Navigate replace to={user?.role === 'parent' ? '/phu-huynh' : '/tong-quan'} />
  }

  const validateForm = () => {
    const nextErrors = {}
    const email = form.email.trim()

    if (!email) {
      nextErrors.email = 'Vui lòng nhập email.'
    } else if (!emailPattern.test(email)) {
      nextErrors.email = 'Email không đúng định dạng.'
    }

    if (!form.password.trim()) {
      nextErrors.password = 'Vui lòng nhập mật khẩu.'
    }

    setErrors(nextErrors)

    if (Object.keys(nextErrors).length > 0) {
      toast.error('Thiếu thông tin đăng nhập', 'Vui lòng nhập email và mật khẩu hợp lệ.')
      return false
    }

    return true
  }

  const handleSubmit = async (event) => {
    event.preventDefault()

    if (!validateForm()) return

    setLoading(true)

    try {
      await login({
        ...form,
        email: form.email.trim(),
      })
      toast.success('Đăng nhập thành công', 'Hệ thống đã ghi nhớ phiên đăng nhập theo lựa chọn của bạn.')
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
          autoComplete="email"
          error={errors.email}
          id="email"
          label="Email"
          name="email"
          onChange={(event) => {
            setForm({ ...form, email: event.target.value })
            setErrors({ ...errors, email: undefined })
          }}
          placeholder="admin@ptconnect.test"
          type="email"
          value={form.email}
        />
        <Input
          autoComplete="current-password"
          error={errors.password}
          id="password"
          label="Mật khẩu"
          name="password"
          onChange={(event) => {
            setForm({ ...form, password: event.target.value })
            setErrors({ ...errors, password: undefined })
          }}
          placeholder="Nhập mật khẩu"
          showPasswordToggle
          type="password"
          value={form.password}
        />
        <label className="flex items-start gap-2 text-sm text-brand-muted">
          <input
            checked={form.remember_me}
            className="mt-1 size-4 rounded border-brand-border text-brand-teal focus:ring-brand-teal"
            name="remember_me"
            onChange={(event) => setForm({ ...form, remember_me: event.target.checked })}
            type="checkbox"
          />
          <span>Ghi nhớ đăng nhập trên thiết bị này</span>
        </label>
      </div>
      <Button className="mt-6 w-full" disabled={loading} icon={LogIn} type="submit">
        {loading ? 'Đang đăng nhập' : 'Đăng nhập'}
      </Button>
    </form>
  )
}

export default LoginPage
