import { Link } from 'react-router-dom'
import Button from '../components/common/Button'

function NotFoundPage() {
  return (
    <main className="grid min-h-screen place-items-center bg-brand-bg px-4 text-center">
      <div>
        <p className="text-sm font-semibold uppercase tracking-wide text-brand-teal-dark">404</p>
        <h1 className="mt-2 text-3xl font-bold text-brand-text">Page not found</h1>
        <p className="mt-2 text-brand-muted">The page you opened does not exist.</p>
        <Button as={Link} className="mt-6" to="/dashboard">
          Back to dashboard
        </Button>
      </div>
    </main>
  )
}

export default NotFoundPage
