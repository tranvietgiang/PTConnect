import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { Mail, MailCheck, Plus, Save, Send } from 'lucide-react'
import { classApi } from '../../api/classApi'
import { scoreApi } from '../../api/scoreApi'
import { emailNotificationApi } from '../../api/notificationApi'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Loading from '../../components/common/Loading'
import Modal from '../../components/common/Modal'
import Select from '../../components/common/Select'
import Table from '../../components/common/Table'
import { useAuth } from '../../store/useAuth'
import { useToast } from '../../store/useToast'

const emailStatusOptions = {
  not_required: { label: 'Không cần', tone: 'bg-gray-100 text-gray-500' },
  pending: { label: 'Chờ gửi', tone: 'bg-amber-50 text-amber-700' },
  sent: { label: 'Đã gửi', tone: 'bg-brand-teal-soft text-brand-teal-dark' },
  failed: { label: 'Thất bại', tone: 'bg-red-50 text-brand-red' },
}

function StatusBadge({ status, options }) {
  const option = options[status] || options.not_required
  return (
    <span className={`inline-flex h-7 items-center rounded-md px-2.5 text-xs font-semibold ${option.tone}`}>
      {option.label}
    </span>
  )
}

function formatScoreValue(value) {
  if (value === null || value === undefined || value === '') return ''
  const numericValue = Number(value)
  if (Number.isNaN(numericValue)) return value
  return numericValue.toLocaleString('vi-VN', {
    maximumFractionDigits: 2,
    minimumFractionDigits: 0,
  })
}

function ScoreListPage() {
  const { user } = useAuth()
  const toast = useToast()
  const [classes, setClasses] = useState([])
  const [gradeLevel, setGradeLevel] = useState('all')
  const [classroomId, setClassroomId] = useState('all')
  const [columns, setColumns] = useState([])
  const [selectedColumnId, setSelectedColumnId] = useState('')
  const [scoreRecords, setScoreRecords] = useState([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [sendingEmail, setSendingEmail] = useState(false)

  const [showCreateColumn, setShowCreateColumn] = useState(false)
  const [newColumn, setNewColumn] = useState({
    name: '',
    max_score: 10,
    weight: 1,
    test_date: '',
    note: '',
  })

  useEffect(() => {
    let mounted = true

    async function loadData() {
      try {
        const [classResponse] = await Promise.all([
          classApi.getAll(),
        ])

        if (!mounted) return
        setClasses(classResponse.data || [])
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
  }, [])

  useEffect(() => {
    if (!classroomId || classroomId === 'all') {
      return
    }

    let mounted = true

    async function loadColumns() {
      try {
        const response = await scoreApi.getColumns({ classroom_id: classroomId })
        if (mounted) {
          const data = response.data?.data || []
          setColumns(data)
          if (data.length > 0 && !selectedColumnId) {
            setSelectedColumnId(String(data[0].id))
          }
        }
      } catch (error) {
        if (mounted) {
          toast.error('Không tải được cột điểm', error.message || 'Vui lòng thử lại sau.')
        }
      }
    }

    loadColumns()
    return () => { mounted = false }
  }, [classroomId])

  useEffect(() => {
    if (!selectedColumnId) {
      return
    }

    let mounted = true

    async function loadScores() {
      try {
        const response = await scoreApi.getAll({ score_column_id: selectedColumnId })
        if (mounted) {
          const data = response.data?.data || []
          setScoreRecords(
            data.map((record) => ({
              ...record,
              row_key: record.student_id,
              score: record.score !== null && record.score !== undefined ? String(record.score) : '',
              email_status: record.email_status || 'not_required',
            })),
          )
        }
      } catch (error) {
        if (mounted) {
          toast.error('Không tải được điểm', error.message || 'Vui lòng thử lại sau.')
        }
      }
    }

    loadScores()
    return () => { mounted = false }
  }, [selectedColumnId])

  const filteredClasses = useMemo(() => {
    if (gradeLevel === 'all') return classes
    return classes.filter((c) => Number(c.grade_level) === Number(gradeLevel))
  }, [classes, gradeLevel])

  const selectedColumn = useMemo(
    () => columns.find((c) => String(c.id) === String(selectedColumnId)),
    [columns, selectedColumnId],
  )

  const handleGradeChange = (event) => {
    const nextGradeLevel = event.target.value
    setGradeLevel(nextGradeLevel)
    setClassroomId((current) => {
      if (nextGradeLevel === 'all') return current
      const currentClass = classes.find((c) => String(c.id) === current)
      return currentClass && Number(currentClass.grade_level) === Number(nextGradeLevel) ? current : 'all'
    })
  }

  const updateScore = (studentId, value) => {
    setScoreRecords((current) =>
      current.map((r) =>
        r.student_id === studentId ? { ...r, score: value } : r,
      ),
    )
  }

  const handleSaveScores = async () => {
    if (!selectedColumnId) return
    setSaving(true)
    try {
      await scoreApi.saveRecords({
        score_column_id: Number(selectedColumnId),
        records: scoreRecords.map((r) => ({
          student_id: r.student_id,
          score: r.score !== '' ? Number(r.score) : null,
          note: r.note || null,
        })),
      })
      setScoreRecords((current) =>
        current.map((r) => ({
          ...r,
          email_status: r.score !== '' && r.score !== null && r.score !== undefined ? 'pending' : r.email_status,
        })),
      )
      toast.success('Đã lưu điểm', 'Điểm số đã được cập nhật.')
    } catch (error) {
      toast.error('Lưu điểm thất bại', error.message || 'Vui lòng thử lại sau.')
    } finally {
      setSaving(false)
    }
  }

  const sendEmailForStudent = async (studentId) => {
    const record = scoreRecords.find((r) => r.student_id === studentId)
    if (record?.email_status === 'sent') return

    setSendingEmail(true)
    try {
      await emailNotificationApi.send({
        type: 'score',
        student_ids: [studentId],
        reference_id: Number(selectedColumnId),
      })
      setScoreRecords((current) =>
        current.map((r) =>
          r.student_id === studentId ? { ...r, email_status: 'sent' } : r,
        ),
      )
      toast.success('Đã gửi email', 'Email thông báo điểm đã được gửi đến phụ huynh.')
    } catch (error) {
      toast.error('Gửi email thất bại', error.message || 'Vui lòng thử lại sau.')
    } finally {
      setSendingEmail(false)
    }
  }

  const sendEmailAll = async () => {
    const pendingIds = scoreRecords
      .filter((r) => r.score !== '' && r.email_status !== 'sent')
      .map((r) => r.student_id)

    if (pendingIds.length === 0) {
      toast.error('Không có học sinh nào cần gửi', 'Tất cả đã được gửi hoặc chưa có điểm.')
      return
    }

    setSendingEmail(true)
    try {
      await emailNotificationApi.send({
        type: 'score',
        student_ids: pendingIds,
        reference_id: Number(selectedColumnId),
      })
      setScoreRecords((current) =>
        current.map((r) =>
          pendingIds.includes(r.student_id) ? { ...r, email_status: 'sent' } : r,
        ),
      )
      toast.success('Đã gửi email', `Đã gửi email cho ${pendingIds.length} học sinh.`)
    } catch (error) {
      toast.error('Gửi email thất bại', error.message || 'Vui lòng thử lại sau.')
    } finally {
      setSendingEmail(false)
    }
  }

  const handleCreateColumn = async (event) => {
    event.preventDefault()
    if (!newColumn.name.trim()) {
      toast.error('Thiếu tên cột điểm', 'Vui lòng nhập tên cột điểm.')
      return
    }
    if (!classroomId || classroomId === 'all') {
      toast.error('Chưa chọn lớp', 'Vui lòng chọn lớp trước khi tạo cột điểm.')
      return
    }

    try {
      await scoreApi.createColumn({
        classroom_id: Number(classroomId),
        name: newColumn.name.trim(),
        max_score: Number(newColumn.max_score),
        weight: Number(newColumn.weight),
        test_date: newColumn.test_date || null,
        note: newColumn.note || null,
      })
      toast.success('Đã tạo cột điểm', 'Cột điểm mới đã được tạo.')
      setShowCreateColumn(false)
      setNewColumn({ name: '', max_score: 10, weight: 1, test_date: '', note: '' })

      const response = await scoreApi.getColumns({ classroom_id: classroomId })
      const data = response.data?.data || []
      setColumns(data)
      if (data.length > 0) {
        setSelectedColumnId(String(data[data.length - 1].id))
      }
    } catch (error) {
      toast.error('Tạo cột điểm thất bại', error.message || 'Vui lòng thử lại sau.')
    }
  }

  const canEdit = user?.role === 'school_admin' || user?.role === 'system_admin' || user?.role === 'teacher' || user?.role === 'assistant'

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Điểm số</h1>
          <p className="mt-1 text-sm text-brand-muted">Quản lý cột điểm và nhập điểm cho từng lớp.</p>
        </div>
        <div className="flex gap-2">
          <Button as={Link} to="/diem-so/bao-cao" variant="secondary">
            Xem báo cáo
          </Button>
          {canEdit && classroomId && classroomId !== 'all' && (
            <Button icon={Plus} onClick={() => setShowCreateColumn(true)}>
              Tạo cột điểm
            </Button>
          )}
        </div>
      </div>

      <div className="grid gap-3 rounded-lg border border-brand-border bg-brand-white p-4 shadow-sm md:grid-cols-[120px_140px_200px_1fr] md:items-end">
        <label className="block">
          <span className="mb-1.5 block text-sm font-medium text-brand-text">Môn học</span>
          <span className="flex h-10 items-center rounded-md border border-brand-border bg-brand-bg px-3 text-sm font-semibold text-brand-teal-dark">
            Sinh học
          </span>
        </label>
        <Select id="score-grade-filter" label="Khối" onChange={handleGradeChange} value={gradeLevel}>
          <option value="all">Tất cả</option>
          <option value="10">Khối 10</option>
          <option value="11">Khối 11</option>
          <option value="12">Khối 12</option>
        </Select>
        <Select
          id="score-class-filter"
          label="Lớp"
          onChange={(event) => {
            setClassroomId(event.target.value)
            setSelectedColumnId('')
            setScoreRecords([])
          }}
          value={classroomId}
        >
          <option value="all">Tất cả lớp</option>
          {filteredClasses.map((classroom) => (
            <option key={classroom.id} value={classroom.id}>
              {classroom.name}
            </option>
          ))}
        </Select>
        <Select
          id="score-column-filter"
          label="Cột điểm"
          onChange={(event) => setSelectedColumnId(event.target.value)}
          value={selectedColumnId}
        >
          <option value="">Chọn cột điểm</option>
          {columns.map((col) => (
            <option key={col.id} value={col.id}>
              {col.name} (tối đa {col.max_score})
            </option>
          ))}
        </Select>
      </div>

      {selectedColumn && (
        <div className="rounded-lg border border-brand-border bg-brand-white p-4 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-lg font-semibold text-brand-text">{selectedColumn.name}</h2>
              <p className="text-sm text-brand-muted">
                Điểm tối đa: {selectedColumn.max_score} | Hệ số: {selectedColumn.weight}
                {selectedColumn.test_date ? ` | Ngày: ${selectedColumn.test_date}` : ''}
              </p>
            </div>
          </div>
        </div>
      )}

      {loading ? (
        <Loading label="Đang tải dữ liệu" />
      ) : (
        <>
          <Table
            columns={[
              { header: 'Mã HS', key: 'student_code' },
              { header: 'Học sinh', key: 'student_name' },
              {
                header: 'Điểm',
                key: 'score',
                render: (row) => (
                  canEdit ? (
                    <Input
                      className="w-24"
                      id={`score-${row.student_id}`}
                      max={selectedColumn?.max_score || 10}
                      min="0"
                      onChange={(event) => updateScore(row.student_id, event.target.value)}
                      placeholder="Nhập điểm"
                      step="0.25"
                      type="number"
                      value={row.score}
                    />
                  ) : (
                    <span>{formatScoreValue(row.score) || '-'}</span>
                  )
                ),
              },
              {
                header: 'Email',
                key: 'email_status',
                render: (row) => <StatusBadge options={emailStatusOptions} status={row.email_status} />,
              },
              {
                header: 'Thao tác',
                key: 'actions',
                render: (row) => (
                  canEdit && row.score !== '' && (
                    <Button
                      disabled={sendingEmail || row.email_status === 'sent'}
                      icon={row.email_status === 'sent' ? MailCheck : Mail}
                      onClick={() => sendEmailForStudent(row.student_id)}
                      size="sm"
                      variant={row.email_status === 'sent' ? 'ghost' : 'secondary'}
                    >
                      {row.email_status === 'sent' ? 'Đã gửi' : 'Gửi email'}
                    </Button>
                  )
                ),
              },
            ]}
            data={scoreRecords}
            emptyText="Chưa có dữ liệu điểm"
          />

          {canEdit && scoreRecords.length > 0 && (
            <div className="flex items-center justify-end gap-3">
              {scoreRecords.some((r) => r.score !== '' && r.email_status !== 'sent') && (
                <Button
                  disabled={sendingEmail}
                  icon={Send}
                  onClick={sendEmailAll}
                  variant="secondary"
                >
                  Gửi email tất cả
                </Button>
              )}
              <Button disabled={saving || !selectedColumnId} icon={Save} onClick={handleSaveScores}>
                {saving ? 'Đang lưu' : 'Lưu điểm'}
              </Button>
            </div>
          )}
        </>
      )}

      <Modal
        onClose={() => setShowCreateColumn(false)}
        open={showCreateColumn}
        title="Tạo cột điểm mới"
      >
        <form className="space-y-4" onSubmit={handleCreateColumn}>
          <Input
            id="column-name"
            label="Tên cột điểm"
            onChange={(event) => setNewColumn((c) => ({ ...c, name: event.target.value }))}
            placeholder="VD: Kiểm tra 1 tiết"
            value={newColumn.name}
          />
          <div className="grid grid-cols-2 gap-4">
            <Input
              id="column-max-score"
              label="Điểm tối đa"
              min="0"
              onChange={(event) => setNewColumn((c) => ({ ...c, max_score: event.target.value }))}
              step="0.5"
              type="number"
              value={newColumn.max_score}
            />
            <Input
              id="column-weight"
              label="Hệ số"
              min="0"
              onChange={(event) => setNewColumn((c) => ({ ...c, weight: event.target.value }))}
              step="0.5"
              type="number"
              value={newColumn.weight}
            />
          </div>
          <Input
            id="column-test-date"
            label="Ngày kiểm tra"
            onChange={(event) => setNewColumn((c) => ({ ...c, test_date: event.target.value }))}
            type="date"
            value={newColumn.test_date}
          />
          <label className="block">
            <span className="mb-1.5 block text-sm font-medium text-brand-text">Ghi chú</span>
            <textarea
              className="min-h-20 w-full rounded-md border border-brand-border px-3 py-2 text-sm outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft"
              onChange={(event) => setNewColumn((c) => ({ ...c, note: event.target.value }))}
              placeholder="Ghi chú (tuỳ chọn)"
              value={newColumn.note}
            />
          </label>
          <div className="flex justify-end gap-3">
            <Button onClick={() => setShowCreateColumn(false)} type="button" variant="secondary">
              Huỷ
            </Button>
            <Button icon={Plus} type="submit">
              Tạo cột điểm
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  )
}

export default ScoreListPage