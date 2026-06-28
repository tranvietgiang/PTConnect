import { Check, Clock, X } from 'lucide-react'
import Button from '../../components/common/Button'
import Table from '../../components/common/Table'

const attendance = [
  { id: 1, className: '10A1', name: 'Trần Minh', status: 'Có mặt' },
  { id: 2, className: '10A1', name: 'Phạm Bảo', status: 'Vắng' },
  { id: 3, className: '10A1', name: 'Đỗ Vy', status: 'Đi muộn' },
]

function AttendancePage() {
  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Điểm danh</h1>
          <p className="mt-1 text-sm text-brand-muted">Điểm danh hằng ngày theo từng lớp.</p>
        </div>
        <Button>Gửi điểm danh</Button>
      </div>
      <Table
        columns={[
          { header: 'Học sinh', key: 'name' },
          { header: 'Lớp', key: 'className' },
          {
            header: 'Trạng thái',
            key: 'status',
            render: (row) => <span className="font-medium text-brand-text">{row.status}</span>,
          },
          {
            header: 'Thao tác',
            key: 'actions',
            render: () => (
              <div className="flex gap-2">
                <Button aria-label="Có mặt" className="size-8 px-0" icon={Check} variant="secondary" />
                <Button aria-label="Đi muộn" className="size-8 px-0" icon={Clock} variant="secondary" />
                <Button aria-label="Vắng" className="size-8 px-0" icon={X} variant="secondary" />
              </div>
            ),
          },
        ]}
        data={attendance}
      />
    </div>
  )
}

export default AttendancePage
