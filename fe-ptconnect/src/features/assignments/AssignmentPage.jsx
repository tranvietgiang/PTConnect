import { useEffect, useState } from 'react'
import { Download, Save, Upload } from 'lucide-react'
import { assignmentApi } from '../../api/assignmentApi'
import { classApi } from '../../api/classApi'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Loading from '../../components/common/Loading'
import Select from '../../components/common/Select'
import Table from '../../components/common/Table'
import { useAuth } from '../../store/useAuth'
import { useToast } from '../../store/useToast'
import { formatDate, formatDateTime } from '../../utils/formatDate'

function downloadBlob(blob, filename) {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

function StatusBadge({ submitted }) {
  return (
    <span
      className={`inline-flex h-7 items-center rounded-md px-2.5 text-xs font-semibold ${
        submitted
          ? 'bg-brand-teal-soft text-brand-teal-dark'
          : 'bg-brand-bg text-brand-muted'
      }`}
    >
      {submitted ? 'Đã nộp' : 'Chưa nộp'}
    </span>
  )
}

function getRowKey(row) {
  return row.row_key || row.id
}

function isSubmitted(row) {
  return row.submission_status === 'submitted' || Boolean(row.submission)
}

function AssignmentPage() {
  const { user } = useAuth()
  const toast = useToast()
  const canCreate = ['admin', 'teacher'].includes(user?.role)
  const canAssignByGrade = user?.role === 'admin'
  const isParent = user?.role === 'parent'
  const [assignments, setAssignments] = useState([])
  const [classes, setClasses] = useState([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [uploadingId, setUploadingId] = useState(null)
  const [fileInputVersion, setFileInputVersion] = useState(0)
  const [submissionFiles, setSubmissionFiles] = useState({})
  const [form, setForm] = useState({
    title: '',
    description: '',
    classroom_id: '',
    grade_level: '',
    due_date: '',
    attachment_file: null,
  })
  const [errors, setErrors] = useState({})

  async function loadData() {
    setLoading(true)

    try {
      const assignmentResponse = await assignmentApi.getAll()
      setAssignments(assignmentResponse.data || [])

      if (canCreate) {
        const classResponse = await classApi.getAll()
        setClasses(classResponse.data || [])
      }
    } catch (error) {
      toast.error('Không tải được bài tập', error.message || 'Vui lòng thử lại sau.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadData()
  }, [])

  const updateForm = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }))
    setErrors((current) => ({ ...current, [field]: undefined }))
  }

  const validateForm = () => {
    const nextErrors = {}

    if (!form.title.trim()) nextErrors.title = 'Vui lòng nhập tiêu đề bài tập.'
    if (!form.classroom_id && (!canAssignByGrade || !form.grade_level)) {
      nextErrors.scope = canAssignByGrade
        ? 'Vui lòng chọn lớp hoặc khối.'
        : 'Vui lòng chọn lớp được phân công.'
    }

    setErrors(nextErrors)

    if (Object.keys(nextErrors).length > 0) {
      toast.error('Thiếu thông tin bài tập', 'Vui lòng nhập tiêu đề và phạm vi giao bài.')
      return false
    }

    return true
  }

  const handleCreate = async (event) => {
    event.preventDefault()

    if (!validateForm()) return

    setSaving(true)

    try {
      const payload = new FormData()
      payload.append('title', form.title.trim())
      payload.append('description', form.description.trim())
      payload.append('status', 'published')
      if (form.classroom_id) payload.append('classroom_id', form.classroom_id)
      if (canAssignByGrade && form.grade_level) payload.append('grade_level', form.grade_level)
      if (form.due_date) payload.append('due_date', form.due_date)
      if (form.attachment_file) payload.append('attachment_file', form.attachment_file)

      await assignmentApi.create(payload)
      toast.success('Đã tạo bài tập', 'Bài tập đã được giao cho phụ huynh/học sinh.')
      setForm({
        title: '',
        description: '',
        classroom_id: '',
        grade_level: '',
        due_date: '',
        attachment_file: null,
      })
      event.currentTarget.reset()
      await loadData()
    } catch (error) {
      toast.error('Không tạo được bài tập', error.message || 'Vui lòng kiểm tra lại thông tin.')
    } finally {
      setSaving(false)
    }
  }

  const handleDownloadAttachment = async (assignment) => {
    try {
      const blob = await assignmentApi.downloadAttachment(assignment.id)
      downloadBlob(blob, assignment.attachment_name || `${assignment.title}.download`)
    } catch (error) {
      toast.error('Không tải được file', error.message || 'Vui lòng thử lại sau.')
    }
  }

  const handleDownloadSubmission = async (submission) => {
    try {
      const blob = await assignmentApi.downloadSubmission(submission.id)
      downloadBlob(blob, submission.file_name || 'bai-nop')
    } catch (error) {
      toast.error('Không tải được bài nộp', error.message || 'Vui lòng thử lại sau.')
    }
  }

  const handleSubmitAssignment = async (assignment) => {
    const rowKey = getRowKey(assignment)
    const file = submissionFiles[rowKey]

    if (assignment.is_overdue || assignment.can_submit === false) {
      toast.error('Đã quá hạn nộp bài', 'Bạn không thể nộp thêm hoặc cập nhật bài nộp.')
      return
    }

    if (!file) {
      toast.error('Chưa chọn file', 'Vui lòng chọn file bài làm trước khi nộp.')
      return
    }

    setUploadingId(rowKey)

    try {
      const payload = new FormData()
      payload.append('student_id', assignment.student_id)
      payload.append('submitted_file', file)

      await assignmentApi.submit(assignment.id, payload)
      toast.success('Đã nộp bài', 'File bài làm đã được cập nhật trên hệ thống.')
      setSubmissionFiles((current) => ({ ...current, [rowKey]: null }))
      setFileInputVersion((current) => current + 1)
      await loadData()
    } catch (error) {
      toast.error('Không nộp được bài', error.message || 'Vui lòng kiểm tra định dạng file.')
    } finally {
      setUploadingId(null)
    }
  }

  const renderScope = (row) => {
    if (row.class_name) return `Lớp ${row.class_name}`
    if (row.grade_level) return `Khối ${row.grade_level}`
    return 'Chưa chọn phạm vi'
  }

  const renderSubmissionInfo = (submission) => {
    if (!submission) return null

    return (
      <div className="space-y-1 text-sm">
        <p className="font-medium text-brand-text">{submission.file_name || 'File bài nộp'}</p>
        <p className="text-xs text-brand-muted">
          {submission.submitted_at ? `Nộp lúc ${formatDateTime(submission.submitted_at)}` : 'Chưa có thời gian nộp'}
        </p>
        <Button icon={Download} onClick={() => handleDownloadSubmission(submission)} variant="secondary">
          Tải bài nộp
        </Button>
      </div>
    )
  }

  const renderParentSubmission = (row) => {
    const rowKey = getRowKey(row)
    const submitted = isSubmitted(row)
    const disabled = row.is_overdue || row.can_submit === false
    const selectedFile = submissionFiles[rowKey]

    return (
      <div className="min-w-64 space-y-3">
        {submitted ? renderSubmissionInfo(row.submission) : <p className="text-sm text-brand-muted">Chưa có file bài nộp.</p>}

        <div className="space-y-2">
          {disabled ? (
            <p className="rounded-md bg-brand-bg px-3 py-2 text-sm font-medium text-brand-red">
              Đã quá hạn nộp bài, bạn không thể nộp thêm.
            </p>
          ) : null}
          <input
            className="block w-full text-sm text-brand-muted file:mr-3 file:rounded-md file:border-0 file:bg-brand-bg file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-text disabled:cursor-not-allowed disabled:opacity-60"
            disabled={disabled}
            key={`${rowKey}-${fileInputVersion}`}
            onChange={(event) =>
              setSubmissionFiles((current) => ({
                ...current,
                [rowKey]: event.target.files?.[0] || null,
              }))
            }
            type="file"
          />
          <Button
            disabled={disabled || uploadingId === rowKey || !selectedFile}
            icon={Upload}
            onClick={() => handleSubmitAssignment(row)}
            variant="secondary"
          >
            {uploadingId === rowKey ? 'Đang nộp' : submitted ? 'Cập nhật bài nộp' : 'Nộp bài'}
          </Button>
        </div>
      </div>
    )
  }

  const renderStaffSubmissions = (row) => {
    const submissions = row.submissions || []

    if (!submissions.length) {
      return <span className="text-brand-muted">Chưa có học sinh</span>
    }

    return (
      <div className="min-w-80 space-y-2">
        {submissions.map((submission) => {
          const submitted = submission.status === 'submitted'

          return (
            <div
              className="rounded-md border border-brand-border bg-brand-white p-3"
              key={`${row.id}-${submission.student_id}`}
            >
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <p className="font-medium text-brand-text">{submission.student_name}</p>
                  <p className="text-xs text-brand-muted">{submission.class_name || renderScope(row)}</p>
                </div>
                <StatusBadge submitted={submitted} />
              </div>
              {submitted ? (
                <div className="mt-2 space-y-1 text-sm">
                  <p className="text-brand-text">{submission.file_name || 'File bài nộp'}</p>
                  <p className="text-xs text-brand-muted">
                    {submission.submitted_at ? `Nộp lúc ${formatDateTime(submission.submitted_at)}` : 'Chưa có thời gian nộp'}
                  </p>
                  <Button icon={Download} onClick={() => handleDownloadSubmission(submission)} variant="secondary">
                    Tải bài nộp
                  </Button>
                </div>
              ) : null}
            </div>
          )
        })}
      </div>
    )
  }

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Bài tập</h1>
        <p className="mt-1 text-sm text-brand-muted">Giao bài, tải tài liệu và theo dõi bài nộp.</p>
      </div>

      {canCreate ? (
        <form className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm" onSubmit={handleCreate}>
          <div className="grid gap-4 md:grid-cols-2">
            <Input
              error={errors.title}
              id="assignment-title"
              label="Tiêu đề"
              onChange={(event) => updateForm('title', event.target.value)}
              placeholder="Ôn tập phần di truyền cơ bản"
              value={form.title}
            />
            <Input
              id="assignment-due-date"
              label="Hạn nộp"
              onChange={(event) => updateForm('due_date', event.target.value)}
              type="date"
              value={form.due_date}
            />
            <Select
              error={errors.scope}
              id="assignment-class"
              label="Giao theo lớp"
              onChange={(event) => updateForm('classroom_id', event.target.value)}
              value={form.classroom_id}
            >
              <option value="">Không chọn lớp</option>
              {classes.map((classroom) => (
                <option key={classroom.id} value={classroom.id}>
                  {classroom.name}
                </option>
              ))}
            </Select>
            {canAssignByGrade ? (
              <Select
                error={errors.scope}
                id="assignment-grade"
                label="Hoặc giao theo khối"
                onChange={(event) => updateForm('grade_level', event.target.value)}
                value={form.grade_level}
              >
                <option value="">Không chọn khối</option>
                <option value="10">Khối 10</option>
                <option value="11">Khối 11</option>
                <option value="12">Khối 12</option>
              </Select>
            ) : null}
            <Input
              id="assignment-description"
              label="Mô tả"
              onChange={(event) => updateForm('description', event.target.value)}
              placeholder="Nội dung cần hoàn thành"
              value={form.description}
            />
            <Input
              id="assignment-file"
              label="File đính kèm"
              onChange={(event) => updateForm('attachment_file', event.target.files?.[0] || null)}
              type="file"
            />
          </div>
          <div className="mt-5 flex justify-end">
            <Button disabled={saving} icon={Save} type="submit">
              {saving ? 'Đang lưu' : 'Giao bài tập'}
            </Button>
          </div>
        </form>
      ) : null}

      {loading ? (
        <Loading label="Đang tải bài tập" />
      ) : (
        <Table
          columns={[
            {
              header: 'Bài tập',
              key: 'title',
              render: (row) => (
                <div className="min-w-56">
                  <p className="font-semibold text-brand-text">{row.title}</p>
                  <p className="text-xs text-brand-muted">{renderScope(row)}</p>
                  {isParent && row.student_name ? (
                    <p className="text-xs text-brand-muted">Học sinh: {row.student_name}</p>
                  ) : null}
                </div>
              ),
            },
            {
              header: 'Hạn nộp',
              key: 'due_date',
              render: (row) => (
                <div className="min-w-28">
                  <p>{row.due_date ? formatDate(row.due_date) : 'Không có'}</p>
                  {row.is_overdue ? <p className="mt-1 text-xs font-semibold text-brand-red">Quá hạn</p> : null}
                </div>
              ),
            },
            {
              header: 'Trạng thái',
              key: 'submission_status',
              render: (row) =>
                isParent ? (
                  <StatusBadge submitted={isSubmitted(row)} />
                ) : (
                  <div className="space-y-1">
                    <p className="font-semibold text-brand-text">
                      {row.submitted_count || 0}/{row.student_count || 0} đã nộp
                    </p>
                    <p className="text-xs text-brand-muted">
                      {(row.student_count || 0) - (row.submitted_count || 0)} chưa nộp
                    </p>
                  </div>
                ),
            },
            {
              header: 'Tài liệu',
              key: 'attachment',
              render: (row) =>
                row.has_attachment ? (
                  <Button icon={Download} onClick={() => handleDownloadAttachment(row)} variant="secondary">
                    Tải file
                  </Button>
                ) : (
                  <span className="text-brand-muted">Không có</span>
                ),
            },
            {
              header: isParent ? 'Nộp bài' : 'Theo dõi bài nộp',
              key: 'submission',
              render: (row) => (isParent ? renderParentSubmission(row) : renderStaffSubmissions(row)),
            },
          ]}
          data={assignments}
          emptyText="Chưa có bài tập"
        />
      )}
    </div>
  )
}

export default AssignmentPage
