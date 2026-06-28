import { Outlet } from 'react-router-dom'

function AuthLayout() {
  return (
    <main className="grid min-h-screen place-items-center bg-brand-bg px-4 py-10">
      <div className="w-full max-w-md">
        <div className="mb-6 text-center">
          <div className="mx-auto mb-3 grid size-12 place-items-center rounded-md bg-brand-teal text-xl font-bold text-brand-white">
            PT
          </div>
          <h1 className="text-2xl font-bold text-brand-text">PTConnect</h1>
          <p className="mt-1 text-sm text-brand-muted">Secure school portal access</p>
        </div>
        <Outlet />
      </div>
    </main>
  )
}

export default AuthLayout
