import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { studentApi } from '../../api/studentApi'
import Loading from '../../components/common/Loading'
import Table from '../../components/common/Table'
import { useToast } from '../../store/useToast'
import { formatDate } from '../../utils/formatDate'

const rows = [
  { id: 1, subject: 'Sinh học', score: 8.8, term: 'Học kỳ 1' },
]

function StudentDetailPage() {
  const { id } = useParams()
  const toast = useToast()
  const [student, setStudent] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let mounted = true

    async function loadStudent() {
      try {
        const response = await studentApi.getById(id)

        if (mounted) {
          setStudent(response.data)
        }
      } catch (error) {
        toast.error('Không tải được học sinh', error.message || 'Vui lòng thử lại sau.')
      } finally {
        if (mounted) {
          setLoading(false)
        }
      }
    }

    loadStudent()

    return () => {
      mounted = false
    }
  }, [id])

  if (loading) {
    return <Loading label="Đang tải hồ sơ học sinh" />
  }

  if (!student) {
    return <p className="text-brand-muted">Không tìm thấy học sinh.</p>
  }

  return (
    <div className="space-y-5">
      <div className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
          <div className="grid size-20 shrink-0 place-items-center overflow-hidden rounded-md bg-brand-bg text-2xl font-bold text-brand-teal-dark">
            {student.avatar_url ? (
              <img alt={student.full_name} className="size-full object-cover" src={student.avatar_url} />
            ) : (
              student.full_name?.charAt(0) || 'H'
            )}
          </div>
          <div>
            <p className="text-sm font-semibold uppercase tracking-wide text-brand-teal-dark">
              {student.student_code}
            </p>
            <h1 className="mt-2 text-2xl font-bold text-brand-text">{student.full_name}</h1>
            <div className="mt-2 grid gap-1 text-sm text-brand-muted sm:grid-cols-2">
              <p>Lớp: {student.class_name || '-'}</p>
              <p>SĐT: {student.phone || '-'}</p>
              <p>Ngày sinh: {student.date_of_birth ? formatDate(student.date_of_birth) : '-'}</p>
              <p>Trạng thái: {student.status === 'studying' ? 'Đang học' : student.status}</p>
              <p className="sm:col-span-2">Địa chỉ: {student.address || '-'}</p>
            </div>
          </div>
        </div>
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
