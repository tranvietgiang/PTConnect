import { Link } from 'react-router-dom'
import Button from '../../components/common/Button'
import Table from '../../components/common/Table'

const scores = [
  { id: 1, className: '10A', name: 'Minh Tran', score: 8.8, subject: 'Math' },
  { id: 2, className: '11B', name: 'Lan Nguyen', score: 8.4, subject: 'English' },
  { id: 3, className: '9C', name: 'An Le', score: 7.7, subject: 'Physics' },
]

function ScoreListPage() {
  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Scores</h1>
          <p className="mt-1 text-sm text-brand-muted">Track scores by student and subject.</p>
        </div>
        <Button as={Link} to="/scores/report" variant="secondary">
          View report
        </Button>
      </div>
      <Table
        columns={[
          { header: 'Student', key: 'name' },
          { header: 'Subject', key: 'subject' },
          { header: 'Class', key: 'className' },
          { header: 'Score', key: 'score' },
        ]}
        data={scores}
      />
    </div>
  )
}

export default ScoreListPage
