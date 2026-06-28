import { Check, Clock, X } from 'lucide-react'
import Button from '../../components/common/Button'
import Table from '../../components/common/Table'

const attendance = [
  { id: 1, className: '10A', name: 'Minh Tran', status: 'Present' },
  { id: 2, className: '10A', name: 'Bao Pham', status: 'Absent' },
  { id: 3, className: '10A', name: 'Vy Do', status: 'Late' },
]

function AttendancePage() {
  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Attendance</h1>
          <p className="mt-1 text-sm text-brand-muted">Mark daily attendance by class.</p>
        </div>
        <Button>Submit attendance</Button>
      </div>
      <Table
        columns={[
          { header: 'Student', key: 'name' },
          { header: 'Class', key: 'className' },
          {
            header: 'Status',
            key: 'status',
            render: (row) => <span className="font-medium text-brand-text">{row.status}</span>,
          },
          {
            header: 'Actions',
            key: 'actions',
            render: () => (
              <div className="flex gap-2">
                <Button aria-label="Present" className="size-8 px-0" icon={Check} variant="secondary" />
                <Button aria-label="Late" className="size-8 px-0" icon={Clock} variant="secondary" />
                <Button aria-label="Absent" className="size-8 px-0" icon={X} variant="secondary" />
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
