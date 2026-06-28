import { Link } from 'react-router-dom'
import Button from '../../components/common/Button'
import Table from '../../components/common/Table'

const scores = [
  { id: 1, className: '10A1', name: 'Trần Minh', score: 8.8, subject: 'Toán' },
  { id: 2, className: '11A1', name: 'Nguyễn Lan', score: 8.4, subject: 'Tiếng Anh' },
  { id: 3, className: '12A1', name: 'Lê An', score: 7.7, subject: 'Vật lý' },
]

function ScoreListPage() {
  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Điểm số</h1>
          <p className="mt-1 text-sm text-brand-muted">Theo dõi điểm theo học sinh và môn học.</p>
        </div>
        <Button as={Link} to="/diem-so/bao-cao" variant="secondary">
          Xem báo cáo
        </Button>
      </div>
      <Table
        columns={[
          { header: 'Học sinh', key: 'name' },
          { header: 'Môn học', key: 'subject' },
          { header: 'Lớp', key: 'className' },
          { header: 'Điểm', key: 'score' },
        ]}
        data={scores}
      />
    </div>
  )
}

export default ScoreListPage
