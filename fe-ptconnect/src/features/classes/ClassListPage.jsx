import { Link } from 'react-router-dom'
import Table from '../../components/common/Table'

const classes = [
  { id: 1, homeroom: 'Cô Hoa', name: '10A1', students: 38 },
  { id: 2, homeroom: 'Thầy Nam', name: '11A1', students: 35 },
  { id: 3, homeroom: 'Cô Linh', name: '12A1', students: 41 },
]

function ClassListPage() {
  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Lớp học</h1>
        <p className="mt-1 text-sm text-brand-muted">Xem danh sách lớp và giáo viên chủ nhiệm.</p>
      </div>
      <Table
        columns={[
          {
            header: 'Lớp',
            key: 'name',
            render: (row) => (
              <Link className="font-semibold text-brand-teal-dark" to={`/lop-hoc/${row.id}`}>
                {row.name}
              </Link>
            ),
          },
          { header: 'Giáo viên chủ nhiệm', key: 'homeroom' },
          { header: 'Sĩ số', key: 'students' },
        ]}
        data={classes}
      />
    </div>
  )
}

export default ClassListPage
