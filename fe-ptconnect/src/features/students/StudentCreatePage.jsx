import { Save } from 'lucide-react'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'

function StudentCreatePage() {
  return (
    <div className="mx-auto max-w-3xl space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Thêm học sinh</h1>
        <p className="mt-1 text-sm text-brand-muted">Tạo hồ sơ học sinh mới.</p>
      </div>
      <form className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
        <div className="grid gap-4 sm:grid-cols-2">
          <Input id="student-name" label="Họ và tên" placeholder="Nhập họ tên học sinh" />
          <Input id="student-code" label="Mã học sinh" placeholder="HS100001" />
          <Input id="student-class" label="Lớp" placeholder="10A1" />
          <Input id="student-dob" label="Ngày sinh" type="date" />
          <Input className="sm:col-span-2" id="student-address" label="Địa chỉ" placeholder="Địa chỉ liên hệ" />
        </div>
        <div className="mt-5 flex justify-end">
          <Button icon={Save} type="submit">
            Lưu học sinh
          </Button>
        </div>
      </form>
    </div>
  )
}

export default StudentCreatePage
