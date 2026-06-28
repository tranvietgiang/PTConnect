import { useParams } from 'react-router-dom'
import Table from '../../components/common/Table'

const roster = [
  { id: 1, code: 'HS100001', name: 'Trần Minh', attendance: 'Có mặt' },
  { id: 2, code: 'HS100004', name: 'Phạm Bảo', attendance: 'Có mặt' },
  { id: 3, code: 'HS100007', name: 'Đỗ Vy', attendance: 'Đi muộn' },
]

function ClassDetailPage() {
  const { id } = useParams()

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Chi tiết lớp #{id}</h1>
        <p className="mt-1 text-sm text-brand-muted">Danh sách học sinh và trạng thái điểm danh.</p>
      </div>
      <Table
        columns={[
          { header: 'Mã học sinh', key: 'code' },
          { header: 'Học sinh', key: 'name' },
          { header: 'Điểm danh', key: 'attendance' },
        ]}
        data={roster}
      />
    </div>
  )
}

export default ClassDetailPage
