import { Bell, CalendarCheck, GraduationCap, Users } from 'lucide-react'
import { Link } from 'react-router-dom'
import Button from '../components/common/Button'
import Table from '../components/common/Table'

const stats = [
  { icon: Users, label: 'Students', value: '1,248', tone: 'bg-brand-teal-soft text-brand-teal-dark' },
  { icon: CalendarCheck, label: 'Present today', value: '96%', tone: 'bg-brand-teal-soft text-brand-teal-dark' },
  { icon: GraduationCap, label: 'Average score', value: '8.4', tone: 'bg-brand-red-soft text-brand-red' },
  { icon: Bell, label: 'New messages', value: '18', tone: 'bg-brand-red-soft text-brand-red' },
]

const activities = [
  { id: 1, action: 'Attendance submitted', owner: 'Grade 10A', time: '08:05' },
  { id: 2, action: 'Score report updated', owner: 'Math 11B', time: '09:20' },
  { id: 3, action: 'Parent notification sent', owner: 'Grade 9C', time: '10:15' },
]

function DashboardPage() {
  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Dashboard</h1>
          <p className="mt-1 text-sm text-brand-muted">Overview of school operations.</p>
        </div>
        <Button as={Link} to="/students/create">
          Add student
        </Button>
      </div>

      <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {stats.map((item) => {
          const Icon = item.icon

          return (
            <article className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm" key={item.label}>
              <div className={`mb-4 grid size-10 place-items-center rounded-md ${item.tone}`}>
                <Icon aria-hidden="true" className="size-5" />
              </div>
              <p className="text-sm text-brand-muted">{item.label}</p>
              <p className="mt-1 text-2xl font-bold text-brand-text">{item.value}</p>
            </article>
          )
        })}
      </section>

      <section>
        <div className="mb-3 flex items-center justify-between">
          <h2 className="text-lg font-semibold text-brand-text">Recent activity</h2>
          <Link className="text-sm font-semibold text-brand-teal-dark" to="/notifications">
            View all
          </Link>
        </div>
        <Table
          columns={[
            { header: 'Action', key: 'action' },
            { header: 'Owner', key: 'owner' },
            { header: 'Time', key: 'time' },
          ]}
          data={activities}
        />
      </section>
    </div>
  )
}

export default DashboardPage
