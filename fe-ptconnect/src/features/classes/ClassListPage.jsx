import { Link } from 'react-router-dom'
import Table from '../../components/common/Table'

const classes = [
  { id: 1, homeroom: 'Ms. Hoa', name: '10A', students: 38 },
  { id: 2, homeroom: 'Mr. Nam', name: '11B', students: 35 },
  { id: 3, homeroom: 'Ms. Linh', name: '9C', students: 41 },
]

function ClassListPage() {
  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Classes</h1>
        <p className="mt-1 text-sm text-brand-muted">Browse class rosters and homeroom teachers.</p>
      </div>
      <Table
        columns={[
          {
            header: 'Class',
            key: 'name',
            render: (row) => (
              <Link className="font-semibold text-brand-teal-dark" to={`/classes/${row.id}`}>
                {row.name}
              </Link>
            ),
          },
          { header: 'Homeroom', key: 'homeroom' },
          { header: 'Students', key: 'students' },
        ]}
        data={classes}
      />
    </div>
  )
}

export default ClassListPage
