import { Link } from 'react-router-dom'
import Button from '../components/common/Button'

function UnauthorizedPage() {
  return (
    <main className="grid min-h-screen place-items-center bg-brand-bg px-4 text-center">
      <div>
        <p className="text-sm font-semibold uppercase tracking-wide text-brand-red">Access denied</p>
        <h1 className="mt-2 text-3xl font-bold text-brand-text">Unauthorized</h1>
        <p className="mt-2 text-brand-muted">Your account does not have permission for this page.</p>
        <Button as={Link} className="mt-6" to="/dashboard">
          Back to dashboard
        </Button>
      </div>
    </main>
  )
}

export default UnauthorizedPage
