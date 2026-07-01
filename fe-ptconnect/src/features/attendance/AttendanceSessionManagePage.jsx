import { useEffect, useMemo, useState } from 'react'
import { Eye, Layers, Lock, Pencil, Plus, RefreshCcw, Save, Trash2, X } from 'lucide-react'
import { attendanceApi } from '../../api/attendanceApi'
import { classApi } from '../../api/classApi'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Loading from '../../components/common/Loading'
import Modal from '../../components/common/Modal'
import Select from '../../components/common/Select'
import Table from '../../components/common/Table'
import { useToast } from '../../store/useToast'
import { formatDate } from '../../utils/formatDate'

function today() {
  const date = new Date()
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function emptyForm() {
  return {
    classroom_id: '',
    attendance_date: today(),
    lesson_number: '1',
    session_name: 'Lesson 1',
    note: '',
  }
}

function emptyBulkForm() {
  return {
    classroom_id: '',
    start_date: today(),
    start_lesson_number: '1',
    lesson_count: '30',
    day_interval: '7',
    session_name_prefix: 'Lesson',
    note: '',
  }
}

const statusLabels = {
  absent: 'Vắng',
  late: 'Đi muộn',
  present: 'Có mặt',
  unknown: 'Chưa điểm danh',
}

const statusTones = {
  absent: 'bg-red-50 text-brand-red',
  late: 'bg-amber-50 text-amber-700',
  present: 'bg-brand-teal-soft text-brand-teal-dark',
  unknown: 'bg-brand-bg text-brand-muted',
}

const sessionStatusLabels = {
  open: 'Đang mở',
  closed: 'Đã đóng',
}

const sessionStatusTones = {
  open: 'bg-brand-teal-soft text-brand-teal-dark',
  closed: 'bg-brand-bg text-brand-muted',
}

const emailStatusLabels = {
  not_sent: 'Chưa gửi',
  sent: 'Đã gửi',
  failed: 'Gửi lỗi',
}

function AttendanceSessionManagePage() {
  const toast = useToast()
  const [classes, setClasses] = useState([])
  const [sessions, setSessions] = useState([])
  const [filters, setFilters] = useState({
    grade_level: 'all',
    classroom_id: 'all',
    date: '',
  })
  const [form, setForm] = useState(emptyForm)
  const [bulkForm, setBulkForm] = useState(emptyBulkForm)
  const [errors, setErrors] = useState({})
  const [bulkErrors, setBulkErrors] = useState({})
  const [editingId, setEditingId] = useState(null)
  const [detailSession, setDetailSession] = useState(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [bulkSaving, setBulkSaving] = useState(false)

  const filteredClasses = useMemo(() => {
    if (filters.grade_level === 'all') {
      return classes
    }

    return classes.filter((classroom) => String(classroom.grade_level) === String(filters.grade_level))
  }, [classes, filters.grade_level])

  const selectedClassroom = useMemo(
    () => classes.find((classroom) => String(classroom.id) === String(form.classroom_id)) || null,
    [classes, form.classroom_id],
  )

  const selectedBulkClassroom = useMemo(
    () => classes.find((classroom) => String(classroom.id) === String(bulkForm.classroom_id)) || null,
    [classes, bulkForm.classroom_id],
  )

  const lessonOptions = useMemo(() => {
    const totalLessons = Number(selectedClassroom?.total_lessons || 1)
    const safeTotalLessons = Number.isInteger(totalLessons) && totalLessons > 0 ? totalLessons : 1

    return Array.from({ length: safeTotalLessons }, (_, index) => index + 1)
  }, [selectedClassroom])

  const detailGroups = useMemo(() => {
    const records = detailSession?.records || []

    return records.reduce(
      (groups, record) => {
        const key = record.status || 'unknown'
        groups[key] = [...(groups[key] || []), record]
        return groups
      },
      { absent: [], late: [], present: [], unknown: [] },
    )
  }, [detailSession])

  async function loadSessions(nextFilters = filters) {
    const params = {}

    if (nextFilters.grade_level !== 'all') params.grade_level = nextFilters.grade_level
    if (nextFilters.classroom_id !== 'all') params.classroom_id = nextFilters.classroom_id
    if (nextFilters.date) params.date = nextFilters.date

    const response = await attendanceApi.getSessions(params)
    setSessions(response.data || [])
  }

  useEffect(() => {
    let mounted = true

    async function loadInitialData() {
      try {
        const [classResponse, sessionResponse] = await Promise.all([
          classApi.getAll(),
          attendanceApi.getSessions(),
        ])

        if (!mounted) return

        const nextClasses = classResponse.data || []
        const firstClass = nextClasses[0]

        setClasses(nextClasses)
        setSessions(sessionResponse.data || [])
        setForm((current) => ({
          ...current,
          classroom_id: firstClass ? String(firstClass.id) : '',
        }))
        setBulkForm((current) => ({
          ...current,
          classroom_id: firstClass ? String(firstClass.id) : '',
          lesson_count: firstClass?.total_lessons ? String(firstClass.total_lessons) : '30',
        }))
      } catch (error) {
        toast.error('Không tải được buổi học', error.message || 'Vui lòng thử lại sau.')
      } finally {
        if (mounted) {
          setLoading(false)
        }
      }
    }

    loadInitialData()

    return () => {
      mounted = false
    }
  }, [])

  const updateFilter = (field, value) => {
    setFilters((current) => {
      const next = { ...current, [field]: value }

      if (field === 'grade_level') {
        next.classroom_id = 'all'
      }

      return next
    })
  }

  const updateForm = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }))
    setErrors((current) => ({ ...current, [field]: undefined }))
  }

  const updateBulkForm = (field, value) => {
    setBulkForm((current) => ({ ...current, [field]: value }))
    setBulkErrors((current) => ({ ...current, [field]: undefined }))
  }

  const handleClassChange = (value) => {
    setForm((current) => ({
      ...current,
      classroom_id: value,
      lesson_number: '1',
      session_name: 'Lesson 1',
    }))
    setErrors((current) => ({ ...current, classroom_id: undefined, lesson_number: undefined }))
  }

  const handleBulkClassChange = (value) => {
    const classroom = classes.find((item) => String(item.id) === String(value))

    setBulkForm((current) => ({
      ...current,
      classroom_id: value,
      start_lesson_number: '1',
      lesson_count: classroom?.total_lessons ? String(classroom.total_lessons) : '30',
    }))
    setBulkErrors((current) => ({ ...current, classroom_id: undefined, lesson_count: undefined }))
  }

  const handleLessonChange = (value) => {
    setForm((current) => ({
      ...current,
      lesson_number: value,
      session_name: current.session_name?.startsWith('Lesson ') ? `Lesson ${value}` : current.session_name,
    }))
    setErrors((current) => ({ ...current, lesson_number: undefined }))
  }

  const validateForm = () => {
    const nextErrors = {}
    const lessonNumber = Number(form.lesson_number)

    if (!form.classroom_id) nextErrors.classroom_id = 'Vui lòng chọn lớp.'
    if (!form.attendance_date) nextErrors.attendance_date = 'Vui lòng chọn ngày học.'
    if (!Number.isInteger(lessonNumber) || lessonNumber < 1) {
      nextErrors.lesson_number = 'Vui lòng chọn buổi học hợp lệ.'
    }
    if (selectedClassroom && lessonNumber > Number(selectedClassroom.total_lessons || 1)) {
      nextErrors.lesson_number = 'Buổi học vượt quá tổng số buổi của lớp.'
    }

    setErrors(nextErrors)

    if (Object.keys(nextErrors).length > 0) {
      toast.error('Thiếu thông tin buổi học', 'Vui lòng kiểm tra lớp, ngày học và buổi học.')
      return false
    }

    return true
  }

  const resetForm = () => {
    setEditingId(null)
    setErrors({})
    setForm({
      ...emptyForm(),
      classroom_id: classes[0] ? String(classes[0].id) : '',
    })
  }

  const validateBulkForm = () => {
    const nextErrors = {}
    const startLessonNumber = Number(bulkForm.start_lesson_number)
    const lessonCount = Number(bulkForm.lesson_count)
    const dayInterval = Number(bulkForm.day_interval)
    const totalLessons = Number(selectedBulkClassroom?.total_lessons || 1)

    if (!bulkForm.classroom_id) nextErrors.classroom_id = 'Vui lòng chọn lớp.'
    if (!bulkForm.start_date) nextErrors.start_date = 'Vui lòng chọn ngày bắt đầu.'
    if (!Number.isInteger(startLessonNumber) || startLessonNumber < 1) {
      nextErrors.start_lesson_number = 'Buổi bắt đầu không hợp lệ.'
    }
    if (!Number.isInteger(lessonCount) || lessonCount < 1 || lessonCount > 100) {
      nextErrors.lesson_count = 'Số buổi phải từ 1 đến 100.'
    }
    if (!Number.isInteger(dayInterval) || dayInterval < 0 || dayInterval > 30) {
      nextErrors.day_interval = 'Khoảng cách ngày phải từ 0 đến 30.'
    }
    if (
      Number.isInteger(startLessonNumber) &&
      Number.isInteger(lessonCount) &&
      startLessonNumber + lessonCount - 1 > totalLessons
    ) {
      nextErrors.lesson_count = 'Số buổi vượt quá tổng số buổi của lớp.'
    }

    setBulkErrors(nextErrors)

    if (Object.keys(nextErrors).length > 0) {
      toast.error('Không tạo được nhiều buổi', 'Vui lòng kiểm tra lớp, ngày bắt đầu và số buổi.')
      return false
    }

    return true
  }

  const handleSubmit = async (event) => {
    event.preventDefault()

    if (!validateForm()) return

    setSaving(true)

    try {
      const payload = {
        classroom_id: Number(form.classroom_id),
        attendance_date: form.attendance_date,
        lesson_number: Number(form.lesson_number),
        session_name: form.session_name.trim() || `Lesson ${form.lesson_number}`,
        note: form.note.trim() || null,
      }

      if (editingId) {
        await attendanceApi.updateSession(editingId, payload)
        toast.success('Đã cập nhật buổi học', 'Thông tin buổi học đã được lưu.')
      } else {
        await attendanceApi.createSession(payload)
        toast.success('Đã thêm buổi học', 'Buổi học mới đã được tạo.')
      }

      await loadSessions()
      resetForm()
    } catch (error) {
      toast.error('Không lưu được buổi học', error.message || 'Vui lòng kiểm tra lại thông tin.')
    } finally {
      setSaving(false)
    }
  }

  const handleBulkSubmit = async (event) => {
    event.preventDefault()

    if (!validateBulkForm()) return

    setBulkSaving(true)

    try {
      const response = await attendanceApi.createSessionsBulk({
        classroom_id: Number(bulkForm.classroom_id),
        start_date: bulkForm.start_date,
        start_lesson_number: Number(bulkForm.start_lesson_number),
        lesson_count: Number(bulkForm.lesson_count),
        day_interval: Number(bulkForm.day_interval),
        session_name_prefix: bulkForm.session_name_prefix.trim() || 'Lesson',
        note: bulkForm.note.trim() || null,
      })
      const data = response.data || {}

      toast.success(
        'Đã tạo nhiều buổi',
        `Tạo mới ${data.created_count || 0} buổi, bỏ qua ${data.skipped_count || 0} buổi đã tồn tại.`,
      )
      await loadSessions()
    } catch (error) {
      toast.error('Không tạo được nhiều buổi', error.message || 'Vui lòng kiểm tra lại thông tin.')
    } finally {
      setBulkSaving(false)
    }
  }

  const handleEdit = (session) => {
    setEditingId(session.id)
    setErrors({})
    setForm({
      classroom_id: String(session.classroom_id),
      attendance_date: session.attendance_date || today(),
      lesson_number: String(session.lesson_number || 1),
      session_name: session.session_name || `Lesson ${session.lesson_number || 1}`,
      note: session.note || '',
    })
  }

  const handleDelete = async (session) => {
    const ok = window.confirm(`Xóa ${session.session_name || `Lesson ${session.lesson_number}`} của lớp ${session.class_name}?`)

    if (!ok) return

    try {
      await attendanceApi.deleteSession(session.id)
      setSessions((current) => current.filter((item) => item.id !== session.id))
      if (editingId === session.id) {
        resetForm()
      }
      toast.success('Đã xóa buổi học', 'Buổi học đã được xóa khỏi hệ thống.')
    } catch (error) {
      toast.error('Không xóa được buổi học', error.message || 'Vui lòng thử lại sau.')
    }
  }

  const handleCloseSession = async (session) => {
    const ok = window.confirm(`Đóng ${session.session_name || `Lesson ${session.lesson_number}`} của lớp ${session.class_name}?`)

    if (!ok) return

    try {
      const response = await attendanceApi.closeSession(session.id)
      const updatedSession = response.data || { ...session, status: 'closed' }

      setSessions((current) => current.map((item) => (item.id === session.id ? updatedSession : item)))
      if (detailSession?.id === session.id) {
        setDetailSession((current) => (current ? { ...current, status: 'closed' } : current))
      }
      toast.success('Đã đóng phiên điểm danh', 'Phiên điểm danh không thể cập nhật thêm.')
    } catch (error) {
      toast.error('Không đóng được phiên điểm danh', error.message || 'Vui lòng thử lại sau.')
    }
  }

  const handleViewDetail = async (session) => {
    try {
      const response = await attendanceApi.getSession(session.id)
      setDetailSession(response.data || null)
    } catch (error) {
      toast.error('Không tải được chi tiết buổi học', error.message || 'Vui lòng thử lại sau.')
    }
  }

  const handleFilter = async () => {
    setLoading(true)

    try {
      await loadSessions()
    } catch (error) {
      toast.error('Không lọc được buổi học', error.message || 'Vui lòng thử lại sau.')
    } finally {
      setLoading(false)
    }
  }

  if (loading && !sessions.length) {
    return <Loading label="Đang tải buổi học" />
  }

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Buổi học</h1>
          <p className="mt-1 text-sm text-brand-muted">Admin quản lý buổi học cho toàn bộ hệ thống.</p>
        </div>
        <Button icon={Plus} onClick={resetForm} variant="secondary">
          Thêm mới
        </Button>
      </div>

      <form className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm" onSubmit={handleSubmit}>
        <div className="mb-4">
          <h2 className="text-base font-semibold text-brand-text">Thêm từng buổi</h2>
          <p className="mt-1 text-sm text-brand-muted">Dùng khi cần tạo hoặc sửa một buổi học cụ thể.</p>
        </div>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Select
            error={errors.classroom_id}
            id="lesson-classroom"
            label="Lớp"
            onChange={(event) => handleClassChange(event.target.value)}
            value={form.classroom_id}
          >
            <option value="">Chọn lớp</option>
            {classes.map((classroom) => (
              <option key={classroom.id} value={classroom.id}>
                {classroom.name} - Khối {classroom.grade_level}
              </option>
            ))}
          </Select>
          <Input
            error={errors.attendance_date}
            id="lesson-date"
            label="Ngày học"
            onChange={(event) => updateForm('attendance_date', event.target.value)}
            type="date"
            value={form.attendance_date}
          />
          <Select
            error={errors.lesson_number}
            id="lesson-number"
            label="Buổi học"
            onChange={(event) => handleLessonChange(event.target.value)}
            value={form.lesson_number}
          >
            {lessonOptions.map((lesson) => (
              <option key={lesson} value={lesson}>
                Lesson {lesson}
              </option>
            ))}
          </Select>
          <Input
            id="lesson-name"
            label="Tên buổi"
            onChange={(event) => updateForm('session_name', event.target.value)}
            value={form.session_name}
          />
          <Input
            className="lg:col-span-3"
            id="lesson-note"
            label="Ghi chú"
            onChange={(event) => updateForm('note', event.target.value)}
            value={form.note}
          />
        </div>
        <div className="mt-5 flex flex-wrap justify-end gap-2">
          {editingId ? (
            <Button icon={X} onClick={resetForm} variant="secondary">
              Hủy sửa
            </Button>
          ) : null}
          <Button disabled={saving || classes.length === 0} icon={Save} type="submit">
            {saving ? 'Đang lưu' : editingId ? 'Lưu thay đổi' : 'Thêm buổi học'}
          </Button>
        </div>
      </form>

      <form className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm" onSubmit={handleBulkSubmit}>
        <div className="mb-4">
          <h2 className="text-base font-semibold text-brand-text">Tạo nhiều buổi</h2>
          <p className="mt-1 text-sm text-brand-muted">
            Sinh nhanh nhiều buổi liên tiếp, ví dụ Lesson 1 đến Lesson 30. Buổi đã tồn tại sẽ được bỏ qua.
          </p>
        </div>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Select
            error={bulkErrors.classroom_id}
            id="bulk-lesson-classroom"
            label="Lớp"
            onChange={(event) => handleBulkClassChange(event.target.value)}
            value={bulkForm.classroom_id}
          >
            <option value="">Chọn lớp</option>
            {classes.map((classroom) => (
              <option key={classroom.id} value={classroom.id}>
                {classroom.name} - Khối {classroom.grade_level}
              </option>
            ))}
          </Select>
          <Input
            error={bulkErrors.start_date}
            id="bulk-lesson-start-date"
            label="Ngày bắt đầu"
            onChange={(event) => updateBulkForm('start_date', event.target.value)}
            type="date"
            value={bulkForm.start_date}
          />
          <Input
            error={bulkErrors.start_lesson_number}
            id="bulk-start-lesson"
            label="Buổi bắt đầu"
            min="1"
            onChange={(event) => updateBulkForm('start_lesson_number', event.target.value)}
            type="number"
            value={bulkForm.start_lesson_number}
          />
          <Input
            error={bulkErrors.lesson_count}
            id="bulk-lesson-count"
            label="Số buổi cần tạo"
            max="100"
            min="1"
            onChange={(event) => updateBulkForm('lesson_count', event.target.value)}
            type="number"
            value={bulkForm.lesson_count}
          />
          <Input
            error={bulkErrors.day_interval}
            id="bulk-day-interval"
            label="Cách nhau bao nhiêu ngày"
            max="30"
            min="0"
            onChange={(event) => updateBulkForm('day_interval', event.target.value)}
            type="number"
            value={bulkForm.day_interval}
          />
          <Input
            id="bulk-session-prefix"
            label="Tiền tố tên buổi"
            onChange={(event) => updateBulkForm('session_name_prefix', event.target.value)}
            value={bulkForm.session_name_prefix}
          />
          <Input
            className="lg:col-span-2"
            id="bulk-lesson-note"
            label="Ghi chú"
            onChange={(event) => updateBulkForm('note', event.target.value)}
            value={bulkForm.note}
          />
        </div>
        <div className="mt-5 flex justify-end">
          <Button disabled={bulkSaving || classes.length === 0} icon={Layers} type="submit">
            {bulkSaving ? 'Đang tạo' : 'Tạo nhiều buổi'}
          </Button>
        </div>
      </form>

      <div className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Select
            id="lesson-filter-grade"
            label="Khối"
            onChange={(event) => updateFilter('grade_level', event.target.value)}
            value={filters.grade_level}
          >
            <option value="all">Tất cả khối</option>
            <option value="10">Khối 10</option>
            <option value="11">Khối 11</option>
            <option value="12">Khối 12</option>
          </Select>
          <Select
            id="lesson-filter-class"
            label="Lớp"
            onChange={(event) => updateFilter('classroom_id', event.target.value)}
            value={filters.classroom_id}
          >
            <option value="all">Tất cả lớp</option>
            {filteredClasses.map((classroom) => (
              <option key={classroom.id} value={classroom.id}>
                {classroom.name}
              </option>
            ))}
          </Select>
          <Input
            id="lesson-filter-date"
            label="Ngày học"
            onChange={(event) => updateFilter('date', event.target.value)}
            type="date"
            value={filters.date}
          />
          <div className="flex items-end">
            <Button className="w-full" icon={RefreshCcw} onClick={handleFilter} variant="secondary">
              Lọc buổi học
            </Button>
          </div>
        </div>
      </div>

      <Table
        columns={[
          { header: 'Ngày học', key: 'attendance_date', render: (row) => formatDate(row.attendance_date) },
          { header: 'Lớp', key: 'class_name' },
          { header: 'Khối', key: 'grade_level', render: (row) => `Khối ${row.grade_level}` },
          { header: 'Buổi', key: 'lesson_number', render: (row) => `Lesson ${row.lesson_number}` },
          { header: 'Tên buổi', key: 'session_name' },
          {
            header: 'Điểm danh',
            key: 'attendance_summary',
            render: (row) => `${row.present || 0} có mặt · ${row.late || 0} muộn · ${row.absent || 0} vắng`,
          },
          {
            header: 'Thao tác',
            key: 'actions',
            render: (row) => (
              <div className="flex flex-wrap gap-2">
                <span className={`inline-flex h-9 items-center rounded-md px-2.5 text-xs font-semibold ${sessionStatusTones[row.status] || sessionStatusTones.open}`}>
                  {sessionStatusLabels[row.status] || sessionStatusLabels.open}
                </span>
                <Button className="h-9 px-3" icon={Eye} onClick={() => handleViewDetail(row)} variant="secondary">
                  Chi tiết
                </Button>
                <Button className="h-9 px-3" icon={Pencil} onClick={() => handleEdit(row)} variant="secondary">
                  Sửa
                </Button>
                {row.status !== 'closed' ? (
                  <Button className="h-9 px-3" icon={Lock} onClick={() => handleCloseSession(row)} variant="secondary">
                    Đóng
                  </Button>
                ) : null}
                <Button className="h-9 px-3" icon={Trash2} onClick={() => handleDelete(row)} variant="danger">
                  Xóa
                </Button>
              </div>
            ),
          },
        ]}
        data={sessions}
        emptyText="Chưa có buổi học"
      />

      <Modal
        isOpen={Boolean(detailSession)}
        onClose={() => setDetailSession(null)}
        title={
          detailSession
            ? `${detailSession.class_name} - Lesson ${detailSession.lesson_number}`
            : 'Chi tiết buổi học'
        }
      >
        {detailSession ? (
          <div className="max-h-[70vh] space-y-4 overflow-y-auto pr-1">
            <div className="text-sm text-brand-muted">
              <p>
                Ngày học: <span className="font-semibold text-brand-text">{formatDate(detailSession.attendance_date)}</span>
              </p>
              <p>
                Điểm danh: {detailSession.present || 0} có mặt · {detailSession.late || 0} muộn ·{' '}
                {detailSession.absent || 0} vắng
              </p>
            </div>

            {['absent', 'late', 'present', 'unknown'].map((status) => (
              <div key={status} className="rounded-lg border border-brand-border">
                <div className="flex items-center justify-between border-b border-brand-border bg-brand-bg px-3 py-2">
                  <span className={`rounded-md px-2 py-1 text-xs font-semibold ${statusTones[status]}`}>
                    {statusLabels[status]}
                  </span>
                  <span className="text-xs font-semibold text-brand-muted">
                    {detailGroups[status]?.length || 0} học sinh
                  </span>
                </div>
                <div className="divide-y divide-brand-border">
                  {detailGroups[status]?.length ? (
                    detailGroups[status].map((record) => (
                      <div className="flex items-center justify-between gap-3 px-3 py-2 text-sm" key={record.student_id}>
                        <div>
                          <p className="font-semibold text-brand-text">{record.student_name}</p>
                          <p className="text-xs text-brand-muted">{record.student_code}</p>
                          <p className="text-xs font-semibold text-brand-muted">
                            Email: {record.email_status_label || emailStatusLabels[record.email_status] || emailStatusLabels.not_sent}
                          </p>
                        </div>
                        {status === 'late' ? (
                          <span className="text-xs font-semibold text-amber-700">
                            {record.late_minutes || 0} phút
                          </span>
                        ) : null}
                      </div>
                    ))
                  ) : (
                    <p className="px-3 py-3 text-sm text-brand-muted">Không có học sinh.</p>
                  )}
                </div>
              </div>
            ))}
          </div>
        ) : null}
      </Modal>
    </div>
  )
}

export default AttendanceSessionManagePage
