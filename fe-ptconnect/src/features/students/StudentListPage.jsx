import { Plus, Search } from 'lucide-react'
import { Link } from 'react-router-dom'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Table from '../../components/common/Table'

const students = [
  { id: 1, className: '10A', code: 'ST001', name: 'Minh Tran', status: 'Active' },
  { id: 2, className: '11B', code: 'ST002', name: 'Lan Nguyen', status: 'Active' },
  { id: 3, className: '9C', code: 'ST003', name: 'An Le', status: 'Review' },
]

function StudentListPage() {
  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Students</h1>
          <p className="mt-1 text-sm text-brand-muted">Manage student profiles and class assignment.</p>
        </div>
        <Button as={Link} icon={Plus} to="/students/create">
          New student
        </Button>
      </div>
      <div className="max-w-md">
        <Input id="student-search" placeholder="Search by name or code" />
      </div>
      <Table
        columns={[
          { header: 'Code', key: 'code' },
          {
            header: 'Name',
            key: 'name',
            render: (row) => (
              <Link className="font-semibold text-brand-teal-dark" to={`/students/${row.id}`}>
                {row.name}
              </Link>
            ),
          },
          { header: 'Class', key: 'className' },
          { header: 'Status', key: 'status' },
          {
            header: '',
            key: 'action',
            render: () => <Search aria-hidden="true" className="size-4 text-brand-muted" />,
          },
        ]}
        data={students}
      />
    </div>
  )
}

export default StudentListPage
