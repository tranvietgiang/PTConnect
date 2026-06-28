import Table from '../../components/common/Table'

const children = [
  { id: 1, attendance: 'Có mặt', className: '10A', name: 'Trần Minh', score: 8.8 },
]

function ParentDashboardPage() {
  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Trang phụ huynh</h1>
        <p className="mt-1 text-sm text-brand-muted">Tổng hợp điểm danh và học tập của học sinh.</p>
      </div>
      <Table
        columns={[
          { header: 'Học sinh', key: 'name' },
          { header: 'Lớp', key: 'className' },
          { header: 'Điểm danh', key: 'attendance' },
          { header: 'Điểm trung bình', key: 'score' },
        ]}
        data={children}
      />
    </div>
  )
}

export default ParentDashboardPage
