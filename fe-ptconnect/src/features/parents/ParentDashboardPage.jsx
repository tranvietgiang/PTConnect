import Table from '../../components/common/Table'

const children = [
  { id: 1, attendance: 'Present', className: '10A', name: 'Minh Tran', score: 8.8 },
]

function ParentDashboardPage() {
  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Parent dashboard</h1>
        <p className="mt-1 text-sm text-brand-muted">Student learning and attendance summary.</p>
      </div>
      <Table
        columns={[
          { header: 'Student', key: 'name' },
          { header: 'Class', key: 'className' },
          { header: 'Attendance', key: 'attendance' },
          { header: 'Average score', key: 'score' },
        ]}
        data={children}
      />
    </div>
  )
}

export default ParentDashboardPage
