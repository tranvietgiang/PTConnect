import { useParams } from 'react-router-dom'
import Table from '../../components/common/Table'

const roster = [
  { id: 1, code: 'ST001', name: 'Minh Tran', attendance: 'Present' },
  { id: 2, code: 'ST004', name: 'Bao Pham', attendance: 'Present' },
  { id: 3, code: 'ST007', name: 'Vy Do', attendance: 'Late' },
]

function ClassDetailPage() {
  const { id } = useParams()

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Class detail #{id}</h1>
        <p className="mt-1 text-sm text-brand-muted">Roster and daily classroom status.</p>
      </div>
      <Table
        columns={[
          { header: 'Code', key: 'code' },
          { header: 'Student', key: 'name' },
          { header: 'Attendance', key: 'attendance' },
        ]}
        data={roster}
      />
    </div>
  )
}

export default ClassDetailPage
