import { useMemo, useState } from 'react'
import { Plus, Search } from 'lucide-react'
import { Link } from 'react-router-dom'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Table from '../../components/common/Table'

const students = [
  { id: 1, className: '10A1', code: 'HS100001', name: 'Trần Minh', status: 'Đang học' },
  { id: 2, className: '11A1', code: 'HS110001', name: 'Nguyễn Lan', status: 'Đang học' },
  { id: 3, className: '12A1', code: 'HS120001', name: 'Lê An', status: 'Cần rà soát' },
]

const classOptions = ['10A1', '11A1', '12A1']

function StudentListPage() {
  const [keyword, setKeyword] = useState('')
  const [selectedClass, setSelectedClass] = useState('')

  const filteredStudents = useMemo(() => {
    const normalizedKeyword = keyword.trim().toLowerCase()

    return students.filter((student) => {
      const matchesKeyword =
        !normalizedKeyword ||
        student.name.toLowerCase().includes(normalizedKeyword) ||
        student.code.toLowerCase().includes(normalizedKeyword)
      const matchesClass = !selectedClass || student.className === selectedClass

      return matchesKeyword && matchesClass
    })
  }, [keyword, selectedClass])

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Học sinh</h1>
          <p className="mt-1 text-sm text-brand-muted">Quản lý hồ sơ học sinh và phân lớp.</p>
        </div>
        <Button as={Link} icon={Plus} to="/hoc-sinh/them">
          Thêm học sinh
        </Button>
      </div>

      <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px]">
        <Input
          id="student-search"
          onChange={(event) => setKeyword(event.target.value)}
          placeholder="Tìm theo tên hoặc mã học sinh"
          value={keyword}
        />
        <label className="block">
          <span className="mb-1.5 block text-sm font-medium text-brand-text">Lọc theo lớp</span>
          <select
            className="h-10 w-full rounded-md border border-brand-border bg-brand-white px-3 text-sm text-brand-text outline-none transition focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft"
            onChange={(event) => setSelectedClass(event.target.value)}
            value={selectedClass}
          >
            <option value="">Tất cả lớp</option>
            {classOptions.map((className) => (
              <option key={className} value={className}>
                {className}
              </option>
            ))}
          </select>
        </label>
      </div>

      <Table
        columns={[
          { header: 'Mã', key: 'code' },
          {
            header: 'Họ tên',
            key: 'name',
            render: (row) => (
              <Link className="font-semibold text-brand-teal-dark" to={`/hoc-sinh/${row.id}`}>
                {row.name}
              </Link>
            ),
          },
          { header: 'Lớp', key: 'className' },
          { header: 'Trạng thái', key: 'status' },
          {
            header: '',
            key: 'action',
            render: () => <Search aria-hidden="true" className="size-4 text-brand-muted" />,
          },
        ]}
        data={filteredStudents}
      />
    </div>
  )
}

export default StudentListPage
