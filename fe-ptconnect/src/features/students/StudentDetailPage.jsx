import { useParams } from 'react-router-dom'
import Table from '../../components/common/Table'

const rows = [
  { id: 1, subject: 'Toán', score: 8.8, term: 'Học kỳ 1' },
  { id: 2, subject: 'Tiếng Anh', score: 8.1, term: 'Học kỳ 1' },
  { id: 3, subject: 'Vật lý', score: 7.9, term: 'Học kỳ 1' },
]

function StudentDetailPage() {
  const { id } = useParams()

  return (
    <div className="space-y-5">
      <div className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
        <p className="text-sm font-semibold uppercase tracking-wide text-brand-teal-dark">Học sinh #{id}</p>
        <h1 className="mt-2 text-2xl font-bold text-brand-text">Chi tiết học sinh</h1>
        <p className="mt-1 text-sm text-brand-muted">Thông tin hồ sơ, điểm danh và điểm số.</p>
      </div>
      <Table
        columns={[
          { header: 'Môn học', key: 'subject' },
          { header: 'Điểm', key: 'score' },
          { header: 'Học kỳ', key: 'term' },
        ]}
        data={rows}
      />
    </div>
  )
}

export default StudentDetailPage
