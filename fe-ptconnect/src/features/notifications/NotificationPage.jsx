import { Send } from 'lucide-react'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Table from '../../components/common/Table'

const notifications = [
  { id: 1, audience: 'Phụ huynh lớp 10A1', subject: 'Nhắc lịch họp phụ huynh', time: '2026-06-27 09:00' },
  { id: 2, audience: 'Giáo viên', subject: 'Lịch tuần', time: '2026-06-27 11:00' },
]

function NotificationPage() {
  return (
    <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
      <section className="space-y-5">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Thông báo</h1>
          <p className="mt-1 text-sm text-brand-muted">Gửi và theo dõi thông báo của nhà trường.</p>
        </div>
        <Table
          columns={[
            { header: 'Nội dung', key: 'subject' },
            { header: 'Người nhận', key: 'audience' },
            { header: 'Thời gian', key: 'time' },
          ]}
          data={notifications}
        />
      </section>
      <aside className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
        <h2 className="text-lg font-semibold text-brand-text">Tạo thông báo</h2>
        <div className="mt-4 space-y-4">
          <Input id="audience" label="Người nhận" placeholder="Phụ huynh lớp 10A1" />
          <Input id="subject" label="Tiêu đề" placeholder="Tiêu đề thông báo" />
          <label className="block">
            <span className="mb-1.5 block text-sm font-medium text-brand-text">Nội dung</span>
            <textarea
              className="min-h-32 w-full rounded-md border border-brand-border px-3 py-2 text-sm outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft"
              placeholder="Nhập nội dung thông báo"
            />
          </label>
          <Button className="w-full" icon={Send}>
            Gửi thông báo
          </Button>
        </div>
      </aside>
    </div>
  )
}

export default NotificationPage
