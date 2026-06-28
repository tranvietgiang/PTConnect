import { useParams } from 'react-router-dom'
import Table from '../../components/common/Table'

const rows = [
  { id: 1, subject: 'Math', score: 8.8, term: 'Term 1' },
  { id: 2, subject: 'English', score: 8.1, term: 'Term 1' },
  { id: 3, subject: 'Physics', score: 7.9, term: 'Term 1' },
]

function StudentDetailPage() {
  const { id } = useParams()

  return (
    <div className="space-y-5">
      <div className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
        <p className="text-sm font-semibold uppercase tracking-wide text-brand-teal-dark">Student #{id}</p>
        <h1 className="mt-2 text-2xl font-bold text-brand-text">Student detail</h1>
        <p className="mt-1 text-sm text-brand-muted">Profile, attendance, and score summary.</p>
      </div>
      <Table
        columns={[
          { header: 'Subject', key: 'subject' },
          { header: 'Score', key: 'score' },
          { header: 'Term', key: 'term' },
        ]}
        data={rows}
      />
    </div>
  )
}

export default StudentDetailPage
