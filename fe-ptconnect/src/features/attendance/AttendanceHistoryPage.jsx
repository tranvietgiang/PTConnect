import Table from '../../components/common/Table'

const history = [
  { id: 1, className: '10A', date: '2026-06-27', present: 36, absent: 2 },
  { id: 2, className: '11B', date: '2026-06-27', present: 34, absent: 1 },
  { id: 3, className: '9C', date: '2026-06-27', present: 39, absent: 2 },
]

function AttendanceHistoryPage() {
  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Lịch sử điểm danh</h1>
        <p className="mt-1 text-sm text-brand-muted">Xem lại các lần điểm danh đã gửi.</p>
      </div>
      <Table
        columns={[
          { header: 'Ngày', key: 'date' },
          { header: 'Lớp', key: 'className' },
          { header: 'Có mặt', key: 'present' },
          { header: 'Vắng', key: 'absent' },
        ]}
        data={history}
      />
    </div>
  )
}

export default AttendanceHistoryPage
