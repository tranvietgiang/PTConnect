import { useEffect, useState } from 'react'
import { scoreApi } from '../../api/scoreApi'
import Loading from '../../components/common/Loading'
import Table from '../../components/common/Table'
import { useToast } from '../../store/useToast'

function formatAverage(value) {
  if (value === null || value === undefined) return '-'

  return Number(value).toLocaleString('vi-VN', {
    maximumFractionDigits: 2,
    minimumFractionDigits: 0,
  })
}

function ScoreReportPage() {
  const toast = useToast()
  const [reports, setReports] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let mounted = true

    async function loadReport() {
      try {
        const response = await scoreApi.getReport()

        if (mounted) {
          setReports(response.data || [])
        }
      } catch (error) {
        if (mounted) {
          toast.error('Không tải được báo cáo điểm', error.message || 'Vui lòng thử lại sau.')
        }
      } finally {
        if (mounted) {
          setLoading(false)
        }
      }
    }

    loadReport()

    return () => {
      mounted = false
    }
  }, [])

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Báo cáo điểm số</h1>
        <p className="mt-1 text-sm text-brand-muted">
          Tổng hợp điểm bài tập môn Sinh học theo lớp từ bảng assignment_submissions.
        </p>
      </div>

      {loading ? (
        <Loading label="Đang tải báo cáo điểm" />
      ) : (
        <Table
          columns={[
            { header: 'Lớp', key: 'className' },
            { header: 'Số bài đã chấm', key: 'total' },
            { header: 'Giỏi', key: 'excellent' },
            { header: 'Khá', key: 'good' },
            { header: 'Cần hỗ trợ', key: 'support' },
            { header: 'Trung bình', key: 'average', render: (row) => formatAverage(row.average) },
          ]}
          data={reports}
          emptyText="Chưa có dữ liệu điểm để báo cáo"
        />
      )}
    </div>
  )
}

export default ScoreReportPage
