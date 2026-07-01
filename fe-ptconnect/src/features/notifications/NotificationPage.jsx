import { useEffect, useState } from 'react'
import { Send } from 'lucide-react'
import { emailNotificationApi } from '../../api/notificationApi'
import { studentApi } from '../../api/studentApi'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Loading from '../../components/common/Loading'
import Modal from '../../components/common/Modal'
import Table from '../../components/common/Table'
import { useToast } from '../../store/useToast'
import { formatDateTime } from '../../utils/formatDate'

const typeLabels = {
  attendance: 'Điểm danh',
  score: 'Điểm số',
  assignment: 'Bài tập',
  general: 'Chung',
}

const statusOptions = {
  sent: { label: 'Đã gửi', tone: 'bg-brand-teal-soft text-brand-teal-dark' },
  failed: { label: 'Thất bại', tone: 'bg-red-50 text-brand-red' },
}

function StatusBadge({ status }) {
  const option = statusOptions[status] || { label: status, tone: 'bg-gray-100 text-gray-500' }
  return (
    <span className={`inline-flex h-7 items-center rounded-md px-2.5 text-xs font-semibold ${option.tone}`}>
      {option.label}
    </span>
  )
}

function NotificationPage() {
  const toast = useToast()
  const [notifications, setNotifications] = useState([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [hasMore, setHasMore] = useState(false)

  const [showSendModal, setShowSendModal] = useState(false)
  const [students, setStudents] = useState([])
  const [selectedStudents, setSelectedStudents] = useState([])
  const [emailSubject, setEmailSubject] = useState('')
  const [emailContent, setEmailContent] = useState('')
  const [sending, setSending] = useState(false)

  useEffect(() => {
    let mounted = true

    async function loadNotifications() {
      try {
        const response = await emailNotificationApi.getAll({ page, per_page: 20 })
        const data = response.data || {}

        if (mounted) {
          const items = data.data || []
          setNotifications((prev) => (page === 1 ? items : [...prev, ...items]))
          setHasMore(data.next_page_url !== null)
        }
      } catch (error) {
        if (mounted) {
          toast.error('Không tải được thông báo', error.message || 'Vui lòng thử lại sau.')
        }
      } finally {
        if (mounted) {
          setLoading(false)
        }
      }
    }

    loadNotifications()
    return () => { mounted = false }
  }, [page])

  useEffect(() => {
    if (!showSendModal) return
    let mounted = true

    async function loadStudents() {
      try {
        const response = await studentApi.getAll({ per_page: 200 })
        if (mounted) {
          setStudents(response.data?.data || [])
        }
      } catch {
        // ignore
      }
    }

    loadStudents()
    return () => { mounted = false }
  }, [showSendModal])

  const handleSendCustomEmail = async (event) => {
    event.preventDefault()
    if (selectedStudents.length === 0) {
      toast.error('Chưa chọn học sinh', 'Vui lòng chọn ít nhất một học sinh.')
      return
    }
    if (!emailSubject.trim()) {
      toast.error('Thiếu tiêu đề', 'Vui lòng nhập tiêu đề email.')
      return
    }
    if (!emailContent.trim()) {
      toast.error('Thiếu nội dung', 'Vui lòng nhập nội dung email.')
      return
    }

    setSending(true)
    try {
      await emailNotificationApi.send({
        type: 'general',
        student_ids: selectedStudents,
        subject: emailSubject.trim(),
        content: emailContent.trim(),
      })
      toast.success('Đã gửi email', 'Email đã được gửi đến phụ huynh.')
      setShowSendModal(false)
      setSelectedStudents([])
      setEmailSubject('')
      setEmailContent('')
      setPage(1)
      setNotifications([])
      setLoading(true)
      const response = await emailNotificationApi.getAll({ page: 1, per_page: 20 })
      setNotifications(response.data?.data || [])
      setLoading(false)
    } catch (error) {
      toast.error('Gửi email thất bại', error.message || 'Vui lòng thử lại sau.')
    } finally {
      setSending(false)
    }
  }

  const toggleStudent = (id) => {
    setSelectedStudents((prev) =>
      prev.includes(id) ? prev.filter((s) => s !== id) : [...prev, id],
    )
  }

  const toggleAllStudents = () => {
    if (selectedStudents.length === students.length) {
      setSelectedStudents([])
    } else {
      setSelectedStudents(students.map((s) => s.id))
    }
  }

  return (
    <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
      <section className="space-y-5">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-brand-text">Lịch sử thông báo</h1>
            <p className="mt-1 text-sm text-brand-muted">
              Theo dõi trạng thái gửi email thông báo.
            </p>
          </div>
          <Button icon={Send} onClick={() => setShowSendModal(true)}>
            Gửi email
          </Button>
        </div>

        {loading ? (
          <Loading label="Đang tải thông báo" />
        ) : (
          <>
            <Table
              columns={[
                { header: 'Học sinh', key: 'student_name' },
                { header: 'Email người nhận', key: 'recipient_email' },
                { header: 'Tiêu đề', key: 'subject' },
                {
                  header: 'Loại',
                  key: 'type',
                  render: (row) => typeLabels[row.type] || row.type,
                },
                {
                  header: 'Trạng thái',
                  key: 'status',
                  render: (row) => <StatusBadge status={row.status} />,
                },
                {
                  header: 'Thời gian',
                  key: 'sent_at',
                  render: (row) => (row.sent_at ? formatDateTime(row.sent_at) : '-'),
                },
              ]}
              data={notifications}
              emptyText="Chưa có thông báo nào"
            />
            {hasMore && (
              <div className="flex justify-center">
                <Button onClick={() => setPage((p) => p + 1)} variant="secondary">
                  Xem thêm
                </Button>
              </div>
            )}
          </>
        )}
      </section>

      <Modal
        onClose={() => setShowSendModal(false)}
        open={showSendModal}
        title="Gửi email cho phụ huynh"
      >
        <form className="space-y-4" onSubmit={handleSendCustomEmail}>
          <div>
            <span className="mb-1.5 block text-sm font-medium text-brand-text">
              Chọn học sinh ({selectedStudents.length}/{students.length})
            </span>
            <div className="max-h-40 overflow-y-auto rounded-md border border-brand-border p-2">
              <label className="flex items-center gap-2 rounded px-2 py-1 text-sm font-medium hover:bg-brand-bg">
                <input
                  checked={selectedStudents.length === students.length}
                  className="size-4 accent-brand-teal"
                  onChange={toggleAllStudents}
                  type="checkbox"
                />
                Chọn tất cả
              </label>
              {students.map((student) => (
                <label
                  className="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-brand-bg"
                  key={student.id}
                >
                  <input
                    checked={selectedStudents.includes(student.id)}
                    className="size-4 accent-brand-teal"
                    onChange={() => toggleStudent(student.id)}
                    type="checkbox"
                  />
                  {student.full_name} ({student.student_code})
                </label>
              ))}
            </div>
          </div>

          <Input
            id="email-subject"
            label="Tiêu đề"
            onChange={(event) => setEmailSubject(event.target.value)}
            placeholder="Tiêu đề email"
            value={emailSubject}
          />

          <label className="block">
            <span className="mb-1.5 block text-sm font-medium text-brand-text">Nội dung</span>
            <textarea
              className="min-h-32 w-full rounded-md border border-brand-border px-3 py-2 text-sm outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft"
              onChange={(event) => setEmailContent(event.target.value)}
              placeholder="Nhập nội dung email"
              value={emailContent}
            />
          </label>

          <div className="flex justify-end gap-3">
            <Button onClick={() => setShowSendModal(false)} type="button" variant="secondary">
              Huỷ
            </Button>
            <Button disabled={sending} icon={Send} type="submit">
              {sending ? 'Đang gửi' : 'Gửi email'}
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  )
}

export default NotificationPage