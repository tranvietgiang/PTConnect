import {
  Bell,
  BookOpen,
  CalendarCheck,
  GraduationCap,
  Home,
  LayoutDashboard,
  ShieldCheck,
  Users,
  X,
} from 'lucide-react'
import { NavLink } from 'react-router-dom'
import Button from '../common/Button'

const navItems = [
  { icon: LayoutDashboard, label: 'Dashboard', to: '/dashboard' },
  { icon: Users, label: 'Students', to: '/students' },
  { icon: BookOpen, label: 'Classes', to: '/classes' },
  { icon: CalendarCheck, label: 'Attendance', to: '/attendance' },
  { icon: GraduationCap, label: 'Scores', to: '/scores' },
  { icon: Bell, label: 'Notifications', to: '/notifications' },
  { icon: Home, label: 'Parent Portal', to: '/parent' },
]

function Sidebar({ isOpen, onClose }) {
  return (
    <>
      <div
        className={`fixed inset-0 z-30 bg-brand-text/40 transition lg:hidden ${isOpen ? 'block' : 'hidden'}`}
        onClick={onClose}
      />
      <aside
        className={`fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-brand-border bg-brand-white transition-transform lg:static lg:translate-x-0 ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="flex h-16 items-center gap-3 border-b border-brand-border px-5">
          <div className="grid size-10 place-items-center rounded-md bg-brand-teal text-brand-white">
            <ShieldCheck aria-hidden="true" className="size-5" />
          </div>
          <div>
            <p className="text-base font-bold text-brand-text">PTConnect</p>
            <p className="text-xs text-brand-muted">School management</p>
          </div>
          <Button
            aria-label="Close navigation"
            className="ml-auto size-9 px-0 lg:hidden"
            icon={X}
            onClick={onClose}
            variant="ghost"
          />
        </div>
        <nav className="flex-1 space-y-1 p-4">
          {navItems.map((item) => {
            const Icon = item.icon

            return (
              <NavLink
                className={({ isActive }) =>
                  `flex h-11 items-center gap-3 rounded-md px-3 text-sm font-medium transition ${
                    isActive
                      ? 'bg-brand-teal-soft text-brand-teal-dark'
                      : 'text-brand-muted hover:bg-brand-bg hover:text-brand-text'
                  }`
                }
                key={item.to}
                onClick={onClose}
                to={item.to}
              >
                <Icon aria-hidden="true" className="size-5" />
                {item.label}
              </NavLink>
            )
          })}
        </nav>
        <div className="border-t border-brand-border p-4 text-xs text-brand-muted">
          Frontend scaffold ready for API integration.
        </div>
      </aside>
    </>
  )
}

export default Sidebar
