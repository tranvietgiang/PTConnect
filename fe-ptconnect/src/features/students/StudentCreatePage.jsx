import { Save } from 'lucide-react'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'

function StudentCreatePage() {
  return (
    <div className="mx-auto max-w-3xl space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Create student</h1>
        <p className="mt-1 text-sm text-brand-muted">Add a new student profile.</p>
      </div>
      <form className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
        <div className="grid gap-4 sm:grid-cols-2">
          <Input id="student-name" label="Full name" placeholder="Student full name" />
          <Input id="student-code" label="Student code" placeholder="ST001" />
          <Input id="student-class" label="Class" placeholder="10A" />
          <Input id="student-dob" label="Date of birth" type="date" />
          <Input className="sm:col-span-2" id="student-address" label="Address" placeholder="Home address" />
        </div>
        <div className="mt-5 flex justify-end">
          <Button icon={Save} type="submit">
            Save student
          </Button>
        </div>
      </form>
    </div>
  )
}

export default StudentCreatePage
