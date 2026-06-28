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
      toast.success('Login successful', 'Welcome back to PTConnect.')
      navigate(location.state?.from?.pathname || '/dashboard', { replace: true })
    } catch (error) {
      toast.error('Login failed', error.message || 'Please check your email and password.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form className="rounded-lg border border-brand-border bg-brand-white p-6 shadow-sm" onSubmit={handleSubmit}>
      <div className="space-y-4">
        <Input
          autoComplete="email"
          id="email"
          label="Email"
          onChange={(event) => setForm({ ...form, email: event.target.value })}
          placeholder="admin@ptconnect.test"
          type="email"
          value={form.email}
        />
        <Input
          autoComplete="current-password"
          id="password"
          label="Password"
          onChange={(event) => setForm({ ...form, password: event.target.value })}
          placeholder="Enter password"
          type="password"
          value={form.password}
        />
      </div>
      <Button className="mt-6 w-full" disabled={loading} icon={LogIn} type="submit">
        {loading ? 'Signing in' : 'Sign in'}
      </Button>
    </form>
  )
}

export default LoginPage
