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

  const handleSubmit = async (event) => {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    try {
      await login({
        email: form.get('email'),
        password: form.get('password'),
      })
      toast.success('Login successful', 'Parent portal is ready.')
      navigate('/parent')
    } catch (error) {
      toast.error('Login failed', error.message || 'Please check your email and password.')
    }
  }

  return (
    <form className="rounded-lg border border-brand-border bg-brand-white p-6 shadow-sm" onSubmit={handleSubmit}>
      <div className="space-y-4">
        <Input id="email" label="Email" name="email" placeholder="parent@ptconnect.test" type="email" />
        <Input id="password" label="Password" name="password" placeholder="Enter password" type="password" />
      </div>
      <Button className="mt-6 w-full" icon={LogIn} type="submit">
        Open parent portal
      </Button>
      <Link className="mt-4 block text-center text-sm font-semibold text-brand-teal-dark" to="/login">
        Staff login
      </Link>
    </form>
  )
}

export default ParentLoginPage
