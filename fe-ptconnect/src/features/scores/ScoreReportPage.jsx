import Table from '../../components/common/Table'

const reports = [
  { id: 1, className: '10A', excellent: 12, good: 19, support: 7 },
  { id: 2, className: '11B', excellent: 10, good: 18, support: 7 },
  { id: 3, className: '9C', excellent: 9, good: 24, support: 8 },
]

function ScoreReportPage() {
  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Score report</h1>
        <p className="mt-1 text-sm text-brand-muted">Class-level academic summary.</p>
      </div>
      <Table
        columns={[
          { header: 'Class', key: 'className' },
          { header: 'Excellent', key: 'excellent' },
          { header: 'Good', key: 'good' },
          { header: 'Needs support', key: 'support' },
        ]}
        data={reports}
      />
    </div>
  )
}

export default ScoreReportPage
