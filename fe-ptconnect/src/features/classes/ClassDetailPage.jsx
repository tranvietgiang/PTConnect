import { useEffect, useState } from 'react'
import { Save } from 'lucide-react'
import { useParams } from 'react-router-dom'
import { classApi } from '../../api/classApi'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Loading from '../../components/common/Loading'
import Select from '../../components/common/Select'
import Table from '../../components/common/Table'
import { useAuth } from '../../store/useAuth'
import { useToast } from '../../store/useToast'
import { formatDate } from '../../utils/formatDate'

function ClassDetailPage() {
  const { id } = useParams()
  const { user } = useAuth()
  const toast = useToast()
  const [classroom, setClassroom] = useState(null)
  const [form, setForm] = useState({
    name: '',
    grade_level: '',
    start_date: '',
    end_date: '',
    total_lessons: '30',
    description: '',
  })
  const [errors, setErrors] = useState({})
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)

  const canEdit = user?.role === 'admin'

  useEffect(() => {
    let mounted = true

    async function loadClassroom() {
      setLoading(true)

      try {
        const response = await classApi.getById(id)
        const data = response.data || null

        if (!mounted) return

        setClassroom(data)
        setForm({
          name: data?.name || '',
          grade_level: data?.grade_level ? String(data.grade_level) : '',
          start_date: data?.start_date || '',
          end_date: data?.end_date || '',
          total_lessons: data?.total_lessons ? String(data.total_lessons) : '30',
          description: data?.description || '',
        })
      } catch (error) {
        if (mounted) {
          toast.error('Không tải được lớp học', error.message || 'Vui lòng thử lại sau.')
        }
      } finally {
        if (mounted) {
          setLoading(false)
        }
      }
    }

    loadClassroom()

    return () => {
      mounted = false
    }
  }, [id])

  const updateForm = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }))
    setErrors((current) => ({ ...current, [field]: undefined }))
  }

  const validateForm = () => {
    const nextErrors = {}
    const totalLessons = Number(form.total_lessons)

    if (!form.name.trim()) nextErrors.name = 'Vui lòng nhập tên lớp.'
    if (!form.grade_level) nextErrors.grade_level = 'Vui lòng chọn khối.'
    if (!form.start_date) nextErrors.start_date = 'Vui lòng chọn ngày bắt đầu.'
    if (!form.end_date) nextErrors.end_date = 'Vui lòng chọn ngày kết thúc.'
    if (form.start_date && form.end_date && form.end_date < form.start_date) {
      nextErrors.end_date = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.'
    }
    if (!Number.isInteger(totalLessons) || totalLessons < 1 || totalLessons > 100) {
      nextErrors.total_lessons = 'Tổng số buổi phải từ 1 đến 100.'
    }

    setErrors(nextErrors)

    if (Object.keys(nextErrors).length) {
      toast.error('Thiếu thông tin lớp học', 'Vui lòng kiểm tra lại thông tin lớp.')
      return false
    }

    return true
  }

  const handleSubmit = async (event) => {
    event.preventDefault()

    if (!validateForm()) return

    setSaving(true)

    try {
      const response = await classApi.update(id, {
        name: form.name.trim(),
        grade_level: Number(form.grade_level),
        start_date: form.start_date,
        end_date: form.end_date,
        total_lessons: Number(form.total_lessons),
        description: form.description.trim() || null,
      })

      setClassroom((current) => ({
        ...current,
        ...(response.data || {}),
      }))
      toast.success('Đã cập nhật lớp học', 'Thông tin lớp học đã được lưu.')
    } catch (error) {
      toast.error('Không cập nhật được lớp học', error.message || 'Vui lòng kiểm tra lại thông tin.')
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return <Loading label="Đang tải lớp học" />
  }

  if (!classroom) {
    return (
      <div className="rounded-lg border border-brand-border bg-brand-white px-4 py-8 text-center text-sm text-brand-muted">
        Không tìm thấy lớp học.
      </div>
    )
  }

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Chi tiết lớp {classroom.name}</h1>
        <p className="mt-1 text-sm text-brand-muted">
          Khối {classroom.grade_level} · {classroom.total_lessons || 0} buổi ·{' '}
          {classroom.start_date && classroom.end_date
            ? `${formatDate(classroom.start_date)} - ${formatDate(classroom.end_date)}`
            : 'Chưa có thời gian'}
        </p>
      </div>

      {canEdit ? (
        <form className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm" onSubmit={handleSubmit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Input
              error={errors.name}
              id="class-detail-name"
              label="Tên lớp"
              onChange={(event) => updateForm('name', event.target.value)}
              value={form.name}
            />
            <Select
              error={errors.grade_level}
              id="class-detail-grade"
              label="Khối"
              onChange={(event) => updateForm('grade_level', event.target.value)}
              value={form.grade_level}
            >
              <option value="">Chọn khối</option>
              <option value="10">Khối 10</option>
              <option value="11">Khối 11</option>
              <option value="12">Khối 12</option>
            </Select>
            <Input
              error={errors.start_date}
              id="class-detail-start-date"
              label="Ngày bắt đầu"
              onChange={(event) => updateForm('start_date', event.target.value)}
              type="date"
              value={form.start_date}
            />
            <Input
              error={errors.end_date}
              id="class-detail-end-date"
              label="Ngày kết thúc"
              onChange={(event) => updateForm('end_date', event.target.value)}
              type="date"
              value={form.end_date}
            />
            <Input
              error={errors.total_lessons}
              id="class-detail-total-lessons"
              label="Tổng số buổi"
              max="100"
              min="1"
              onChange={(event) => updateForm('total_lessons', event.target.value)}
              type="number"
              value={form.total_lessons}
            />
            <Input
              className="sm:col-span-2"
              id="class-detail-description"
              label="Ghi chú"
              onChange={(event) => updateForm('description', event.target.value)}
              value={form.description}
            />
          </div>
          <div className="mt-5 flex justify-end">
            <Button disabled={saving} icon={Save} type="submit">
              {saving ? 'Đang lưu' : 'Lưu thay đổi'}
            </Button>
          </div>
        </form>
      ) : null}

      <Table
        columns={[
          { header: 'Mã học sinh', key: 'student_code' },
          { header: 'Học sinh', key: 'full_name' },
          { header: 'Trạng thái', key: 'status' },
        ]}
        data={classroom.students || []}
        emptyText="Lớp chưa có học sinh"
      />
    </div>
  )
}

export default ClassDetailPage
