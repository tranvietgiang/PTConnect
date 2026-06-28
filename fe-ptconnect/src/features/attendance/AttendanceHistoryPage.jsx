import { useEffect, useState } from 'react'
import { attendanceApi } from '../../api/attendanceApi'
import Loading from '../../components/common/Loading'
import Table from '../../components/common/Table'
import { useToast } from '../../store/useToast'
import { formatDate } from '../../utils/formatDate'

function AttendanceHistoryPage() {
  const toast = useToast()
  const [history, setHistory] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let mounted = true

    async function loadHistory() {
      try {
        const response = await attendanceApi.getHistory()

        if (mounted) {
          setHistory(response.data || [])
        }
      } catch (error) {
        toast.error('Không tải được lịch sử điểm danh', error.message || 'Vui lòng thử lại sau.')
      } finally {
        if (mounted) {
          setLoading(false)
        }
      }
    }

    loadHistory()

    return () => {
      mounted = false
    }
  }, [])

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Lịch sử điểm danh</h1>
        <p className="mt-1 text-sm text-brand-muted">Xem lại các buổi học đã điểm danh.</p>
      </div>
      {loading ? (
        <Loading label="Đang tải lịch sử điểm danh" />
      ) : (
        <Table
          columns={[
            { header: 'Ngày', key: 'attendance_date', render: (row) => formatDate(row.attendance_date) },
            { header: 'Lớp', key: 'class_name' },
            { header: 'Buổi học', key: 'session_name' },
            { header: 'Có mặt', key: 'present' },
            { header: 'Đi muộn', key: 'late' },
            { header: 'Vắng', key: 'absent' },
          ]}
          data={history}
          emptyText="Chưa có lịch sử điểm danh"
        />
      )}
    </div>
  )
}

export default AttendanceHistoryPage
