import { useEffect, useState } from 'react'
import { Save } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { classApi } from '../../api/classApi'
import { studentApi } from '../../api/studentApi'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Loading from '../../components/common/Loading'
import Select from '../../components/common/Select'
import { useToast } from '../../store/useToast'

function StudentCreatePage() {
  const navigate = useNavigate()
  const toast = useToast()
  const [classes, setClasses] = useState([])
  const [loadingClasses, setLoadingClasses] = useState(true)
  const [saving, setSaving] = useState(false)
  const [errors, setErrors] = useState({})
  const [form, setForm] = useState({
    full_name: '',
    student_code: '',
    classroom_id: '',
    date_of_birth: '',
    address: '',
  })

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
          setLoadingClasses(false)
        }
      }
    }

    loadClasses()

    return () => {
      mounted = false
    }
  }, [])

  const updateForm = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }))
    setErrors((current) => ({ ...current, [field]: undefined }))
  }

  const validateForm = () => {
    const nextErrors = {}

    if (!form.full_name.trim()) nextErrors.full_name = 'Vui lòng nhập họ tên học sinh.'
    if (!form.student_code.trim()) nextErrors.student_code = 'Vui lòng nhập mã học sinh.'
    if (!form.classroom_id) nextErrors.classroom_id = 'Vui lòng chọn lớp.'

    setErrors(nextErrors)

    if (Object.keys(nextErrors).length > 0) {
      toast.error('Thiếu thông tin học sinh', 'Vui lòng nhập đầy đủ họ tên, mã học sinh và lớp.')
      return false
    }

    return true
  }

  const handleSubmit = async (event) => {
    event.preventDefault()

    if (!validateForm()) return

    setSaving(true)

    try {
      await studentApi.create({
        ...form,
        full_name: form.full_name.trim(),
        student_code: form.student_code.trim(),
        classroom_id: Number(form.classroom_id),
        date_of_birth: form.date_of_birth || null,
        address: form.address.trim() || null,
      })
      toast.success('Đã lưu học sinh', 'Hồ sơ học sinh mới đã được tạo.')
      navigate('/hoc-sinh', { replace: true })
    } catch (error) {
      toast.error('Không lưu được học sinh', error.message || 'Vui lòng kiểm tra lại thông tin.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="mx-auto max-w-3xl space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Thêm học sinh</h1>
        <p className="mt-1 text-sm text-brand-muted">Tạo hồ sơ học sinh mới và phân lớp.</p>
      </div>

      {loadingClasses ? (
        <Loading label="Đang tải danh sách lớp" />
      ) : (
        <form className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm" onSubmit={handleSubmit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Input
              error={errors.full_name}
              id="student-name"
              label="Họ và tên"
              onChange={(event) => updateForm('full_name', event.target.value)}
              placeholder="Nhập họ tên học sinh"
              value={form.full_name}
            />
            <Input
              error={errors.student_code}
              id="student-code"
              label="Mã học sinh"
              onChange={(event) => updateForm('student_code', event.target.value)}
              placeholder="HS100001"
              value={form.student_code}
            />
            <Select
              error={errors.classroom_id}
              id="student-class"
              label="Lớp"
              onChange={(event) => updateForm('classroom_id', event.target.value)}
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
              id="student-dob"
              label="Ngày sinh"
              onChange={(event) => updateForm('date_of_birth', event.target.value)}
              type="date"
              value={form.date_of_birth}
            />
            <Input
              id="student-address"
              label="Địa chỉ"
              onChange={(event) => updateForm('address', event.target.value)}
              placeholder="Địa chỉ liên hệ"
              value={form.address}
            />
          </div>
          <div className="mt-5 flex justify-end">
            <Button disabled={saving || classes.length === 0} icon={Save} type="submit">
              {saving ? 'Đang lưu' : 'Lưu học sinh'}
            </Button>
          </div>
        </form>
      )}
    </div>
  )
}

export default StudentCreatePage
