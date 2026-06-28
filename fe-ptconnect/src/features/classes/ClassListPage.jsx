import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Plus } from 'lucide-react'
import { classApi } from '../../api/classApi'
import Button from '../../components/common/Button'
import Loading from '../../components/common/Loading'
import Table from '../../components/common/Table'
import { useAuth } from '../../store/useAuth'
import { useToast } from '../../store/useToast'

function ClassListPage() {
  const { user } = useAuth()
  const toast = useToast()
  const [classes, setClasses] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let mounted = true

    async function loadClasses() {
      try {
        const response = await classApi.getAll()

        if (mounted) {
          setClasses(response.data || [])
        }
      } catch (error) {
        toast.error('Không tải được danh sách lớp', error.message || 'Vui lòng thử lại sau.')
      } finally {
        if (mounted) {
          setLoading(false)
        }
      }
    }

    loadClasses()

    return () => {
      mounted = false
    }
  }, [])

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Lớp học</h1>
          <p className="mt-1 text-sm text-brand-muted">Xem danh sách lớp và sĩ số hiện tại.</p>
        </div>
        {user?.role === 'admin' ? (
          <Button as={Link} icon={Plus} to="/lop-hoc/them">
            Thêm lớp học
          </Button>
        ) : null}
      </div>

      {loading ? (
        <Loading label="Đang tải danh sách lớp" />
      ) : (
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
            { header: 'Khối', key: 'grade_level', render: (row) => `Khối ${row.grade_level}` },
            { header: 'Năm học', key: 'academic_year' },
            { header: 'Sĩ số', key: 'students_count' },
          ]}
          data={classes}
          emptyText="Chưa có lớp học"
        />
      )}
    </div>
  )
}

export default ClassListPage
