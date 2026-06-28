import { Send } from 'lucide-react'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Table from '../../components/common/Table'

const notifications = [
  { id: 1, audience: 'Parents 10A', subject: 'Meeting reminder', time: '2026-06-27 09:00' },
  { id: 2, audience: 'Teachers', subject: 'Weekly schedule', time: '2026-06-27 11:00' },
]

function NotificationPage() {
  return (
    <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
      <section className="space-y-5">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Notifications</h1>
          <p className="mt-1 text-sm text-brand-muted">Send and review school messages.</p>
        </div>
        <Table
          columns={[
            { header: 'Subject', key: 'subject' },
            { header: 'Audience', key: 'audience' },
            { header: 'Time', key: 'time' },
          ]}
          data={notifications}
        />
      </section>
      <aside className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
        <h2 className="text-lg font-semibold text-brand-text">New notification</h2>
        <div className="mt-4 space-y-4">
          <Input id="audience" label="Audience" placeholder="Parents 10A" />
          <Input id="subject" label="Subject" placeholder="Message subject" />
          <label className="block">
            <span className="mb-1.5 block text-sm font-medium text-brand-text">Message</span>
            <textarea
              className="min-h-32 w-full rounded-md border border-brand-border px-3 py-2 text-sm outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft"
              placeholder="Write message"
            />
          </label>
          <Button className="w-full" icon={Send}>
            Send notification
          </Button>
        </div>
      </aside>
    </div>
  )
}

export default NotificationPage
