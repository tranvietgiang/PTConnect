import { useEffect, useState } from 'react'
import { attendanceApi } from '../../api/attendanceApi'
import { scoreApi } from '../../api/scoreApi'
import Loading from '../../components/common/Loading'
import Table from '../../components/common/Table'
import { useAuth } from '../../store/useAuth'
import { useToast } from '../../store/useToast'
import { formatDate } from '../../utils/formatDate'

const statusLabels = {
  present: 'Có mặt',
  late: 'Đi muộn',
  absent: 'Vắng',
}

const statusTones = {
  present: 'text-brand-teal-dark',
  late: 'text-amber-700',
  absent: 'text-brand-red',
}

function formatScore(value) {
  if (value === null || value === undefined || value === '') return '-'
  return Number(value).toLocaleString('vi-VN', {
    maximumFractionDigits: 2,
    minimumFractionDigits: 0,
  })
}

function StudentScorePage() {
  const { user } = useAuth()
  const toast = useToast()
  const [attendance, setAttendance] = useState([])
  const [scores, setScores] = useState([])
  const [loading, setLoading] = useState(true)
  const [studentProfile, setStudentProfile] = useState(null)

  useEffect(() => {
    if (!user?.profile?.id) return
    let mounted = true

    async function loadData() {
      try {
        const [attendanceRes, scoresRes] = await Promise.all([
          attendanceApi.getStudentHistory({ student_id: user.profile.id }),
          scoreApi.getAll({ student_id: user.profile.id, per_page: 100 }),
        ])

        if (mounted) {
          setStudentProfile(user.profile)
          setAttendance(attendanceRes.data?.data || [])
          setScores(scoresRes.data?.data || [])
        }
      } catch (error) {
        if (mounted) {
          toast.error('Không tải được dữ liệu', error.message || 'Vui lòng thử lại sau.')
        }
      } finally {
        if (mounted) {
          setLoading(false)
        }
      }
    }

    loadData()
    return () => { mounted = false }
  }, [user])

  if (loading) {
    return <Loading label="Đang tải dữ liệu" />
  }

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Điểm của tôi</h1>
        <p className="mt-1 text-sm text-brand-muted">
          {studentProfile ? `${studentProfile.full_name} (${studentProfile.student_code})` : ''}
        </p>
      </div>

      <div className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
        <h2 className="mb-4 text-lg font-semibold text-brand-text">Lịch sử điểm danh</h2>
        <Table
          columns={[
            { header: 'Ngày', key: 'attendance_date', render: (row) => formatDate(row.attendance_date) },
            { header: 'Lớp', key: 'classroom_name' },
            { header: 'Buổi', key: 'lesson_number', render: (row) => `Buổi ${row.lesson_number}` },
            {
              header: 'Trạng thái',
              key: 'status',
              render: (row) => (
                <span className={`font-semibold ${statusTones[row.status] || ''}`}>
                  {statusLabels[row.status] || row.status}
                  {row.status === 'late' && row.late_minutes ? ` (${row.late_minutes} phút)` : ''}
                </span>
              ),
            },
          ]}
          data={attendance}
          emptyText="Chưa có dữ liệu điểm danh"
        />
      </div>

      <div className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
        <h2 className="mb-4 text-lg font-semibold text-brand-text">Điểm số</h2>
        <Table
          columns={[
            { header: 'Bài kiểm tra', key: 'column_name' },
            { header: 'Điểm', key: 'score', render: (row) => formatScore(row.score) },
          ]}
          data={scores}
          emptyText="Chưa có điểm số"
        />
      </div>
    </div>
  )
}

export default StudentScorePage