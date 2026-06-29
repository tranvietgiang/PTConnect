import { useEffect, useMemo, useState } from 'react'
import { Check, Clock, History, Save, X } from 'lucide-react'
import { Link } from 'react-router-dom'
import { attendanceApi } from '../../api/attendanceApi'
import { classApi } from '../../api/classApi'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Loading from '../../components/common/Loading'
import Select from '../../components/common/Select'
import Table from '../../components/common/Table'
import { useToast } from '../../store/useToast'

const statusOptions = {
  present: { label: 'Có mặt', tone: 'bg-brand-teal-soft text-brand-teal-dark' },
  late: { label: 'Đi muộn', tone: 'bg-amber-50 text-amber-700' },
  absent: { label: 'Vắng', tone: 'bg-red-50 text-brand-red' },
}

function today() {
  const date = new Date()
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function StatusBadge({ status }) {
  const option = statusOptions[status] || statusOptions.present

  return (
    <span className={`inline-flex h-7 items-center rounded-md px-2.5 text-xs font-semibold ${option.tone}`}>
      {option.label}
    </span>
  )
}

function normalizeSearch(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd')
    .toLowerCase()
    .trim()
}

function AttendancePage() {
  const toast = useToast()
  const [classes, setClasses] = useState([])
  const [selectedGradeLevel, setSelectedGradeLevel] = useState('all')
  const [selectedClass, setSelectedClass] = useState('')
  const [studentSearch, setStudentSearch] = useState('')
  const [attendanceDate, setAttendanceDate] = useState(today())
  const [lessonNumber, setLessonNumber] = useState('1')
  const [sessionName, setSessionName] = useState('Lesson 1')
  const [records, setRecords] = useState([])
  const [loadingClasses, setLoadingClasses] = useState(true)
  const [loadingRecords, setLoadingRecords] = useState(false)
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    let mounted = true

    async function loadClasses() {
      try {
        const response = await classApi.getAll()
        const data = response.data || []

        if (mounted) {
          setClasses(data)
          const firstClass = data[0]
          setSelectedGradeLevel(firstClass ? String(firstClass.grade_level) : 'all')
          setSelectedClass((current) => {
            if (current) return current

            return firstClass ? String(firstClass.id) : ''
          })
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

  const filteredClasses = useMemo(() => {
    if (selectedGradeLevel === 'all') {
      return classes
    }

    return classes.filter((classroom) => String(classroom.grade_level) === String(selectedGradeLevel))
  }, [classes, selectedGradeLevel])

  const selectedClassroom = useMemo(
    () => classes.find((classroom) => String(classroom.id) === String(selectedClass)) || null,
    [classes, selectedClass],
  )

  const lessonOptions = useMemo(() => {
    const totalLessons = Number(selectedClassroom?.total_lessons || 1)
    const safeTotalLessons = Number.isInteger(totalLessons) && totalLessons > 0 ? totalLessons : 1

    return Array.from({ length: safeTotalLessons }, (_, index) => index + 1)
  }, [selectedClassroom])

  const visibleRecords = useMemo(() => {
    const keyword = normalizeSearch(studentSearch)

    if (!keyword) {
      return records
    }

    return records.filter((record) => normalizeSearch(record.student_name).includes(keyword))
  }, [records, studentSearch])

  useEffect(() => {
    if (!selectedClass) {
      return
    }

    if (selectedClassroom && Number(lessonNumber) > Number(selectedClassroom.total_lessons || 1)) {
      return
    }

    let mounted = true

    async function loadAttendance() {
      setLoadingRecords(true)

      try {
        const response = await attendanceApi.getToday({
          classroom_id: selectedClass,
          date: attendanceDate,
          lesson_number: Number(lessonNumber),
        })
        const data = response.data || {}

        if (mounted) {
          setSessionName(data.session?.session_name || `Lesson ${lessonNumber}`)
          setRecords(
            (data.records || []).map((record) => ({
              ...record,
              row_key: record.student_id,
              status: record.status || 'present',
              late_minutes: record.status === 'late' ? record.late_minutes || 15 : 0,
            })),
          )
        }
      } catch (error) {
        toast.error('Không tải được dữ liệu điểm danh', 'Vui lòng thử lại sau.')
      } finally {
        if (mounted) {
          setLoadingRecords(false)
        }
      }
    }

    loadAttendance()

    return () => {
      mounted = false
    }
  }, [selectedClass, selectedClassroom, attendanceDate, lessonNumber])

  const summary = useMemo(
    () =>
      records.reduce(
        (result, record) => ({
          ...result,
          [record.status]: (result[record.status] || 0) + 1,
        }),
        { present: 0, late: 0, absent: 0 },
      ),
    [records],
  )

  const updateRecord = (studentId, patch) => {
    setRecords((current) =>
      current.map((record) =>
        record.student_id === studentId
          ? {
              ...record,
              ...patch,
            }
          : record,
      ),
    )
  }

  const setStatus = (studentId, status) => {
    updateRecord(studentId, {
      status,
      late_minutes: status === 'late' ? 15 : 0,
    })
  }

  const handleGradeChange = (value) => {
    const nextClasses =
      value === 'all' ? classes : classes.filter((classroom) => String(classroom.grade_level) === String(value))

    setSelectedGradeLevel(value)

    if (value === 'all') {
      return
    }

    const currentClass = classes.find((classroom) => String(classroom.id) === String(selectedClass))
    const nextClass = currentClass && String(currentClass.grade_level) === String(value) ? currentClass : nextClasses[0]
    const nextClassId = String(nextClass?.id || '')

    setSelectedClass(nextClassId)
    setLessonNumber('1')
    setSessionName('Lesson 1')

    if (!nextClassId) {
      setRecords([])
    }
  }

  const handleClassChange = (value) => {
    setSelectedClass(value)
    setLessonNumber('1')
    setSessionName('Lesson 1')

    if (!value) {
      setRecords([])
    }
  }

  const handleSubmit = async () => {
    if (!selectedClass) {
      toast.error('Chưa chọn lớp', 'Vui lòng chọn lớp trước khi gửi điểm danh.')
      return
    }

    setSaving(true)

    try {
      const response = await attendanceApi.submit({
        classroom_id: Number(selectedClass),
        attendance_date: attendanceDate,
        lesson_number: Number(lessonNumber),
        session_name: sessionName.trim() || `Lesson ${lessonNumber}`,
        records: records.map((record) => ({
          student_id: record.student_id,
          status: record.status,
          late_minutes: record.status === 'late' ? Number(record.late_minutes || 0) : 0,
          note: record.note || null,
        })),
      })

      setRecords(
        (response.data?.records || records).map((record) => ({
          ...record,
          row_key: record.student_id,
          status: record.status || 'present',
        })),
      )
      toast.success('Đã gửi điểm danh', 'Hệ thống đã lưu trạng thái và ghi nhận thông báo cho phụ huynh nếu có vắng/đi muộn.')
    } catch (error) {
      toast.error('Không gửi được điểm danh', error.message || 'Vui lòng kiểm tra lại dữ liệu.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Điểm danh</h1>
          <p className="mt-1 text-sm text-brand-muted">Trợ giảng điểm danh hằng ngày theo từng lớp.</p>
        </div>
        <Button as={Link} icon={History} to="/diem-danh/lich-su" variant="secondary">
          Lịch sử
        </Button>
      </div>

      {loadingClasses ? (
        <Loading label="Đang tải danh sách lớp" />
      ) : (
        <>
          <div className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
            <div className="grid gap-4 md:grid-cols-[120px_140px_minmax(220px,1fr)_minmax(220px,1fr)_minmax(180px,220px)_minmax(180px,220px)] md:items-end">
              <label className="block">
                <span className="mb-1.5 block text-sm font-medium text-brand-text">Môn học</span>
                <span className="flex h-10 items-center rounded-md border border-brand-border bg-brand-bg px-3 text-sm font-semibold text-brand-teal-dark">
                  Sinh học
                </span>
              </label>
              <Select
                id="attendance-grade"
                label="Khối"
                onChange={(event) => handleGradeChange(event.target.value)}
                value={selectedGradeLevel}
              >
                <option value="all">Tất cả khối</option>
                <option value="10">Khối 10</option>
                <option value="11">Khối 11</option>
                <option value="12">Khối 12</option>
              </Select>
              <Select
                id="attendance-class"
                label="Lớp"
                onChange={(event) => handleClassChange(event.target.value)}
                value={selectedClass}
              >
                <option value="">Chọn lớp</option>
                {filteredClasses.map((classroom) => (
                  <option key={classroom.id} value={classroom.id}>
                    {classroom.name} - Khối {classroom.grade_level}
                  </option>
                ))}
              </Select>
              <Input
                id="attendance-student-search"
                label="Tìm học sinh"
                onChange={(event) => setStudentSearch(event.target.value)}
                placeholder="Nhập tên học sinh"
                value={studentSearch}
              />
              <Input
                id="attendance-date"
                label="Ngày học"
                onChange={(event) => setAttendanceDate(event.target.value)}
                type="date"
                value={attendanceDate}
              />
              <Select
                id="attendance-lesson"
                label="Buổi học"
                onChange={(event) => {
                  setLessonNumber(event.target.value)
                  setSessionName(`Lesson ${event.target.value}`)
                }}
                value={lessonNumber}
              >
                {lessonOptions.map((lesson) => (
                  <option key={lesson} value={lesson}>
                    Lesson {lesson}
                  </option>
                ))}
              </Select>
            </div>
            {selectedClassroom ? (
              <p className="mt-3 text-sm text-brand-muted">
                Đang điểm danh lớp <span className="font-semibold text-brand-text">{selectedClassroom.name}</span>
                {' '}
                - Khối {selectedClassroom.grade_level}
              </p>
            ) : null}
            <div className="mt-4 grid gap-3 text-sm sm:grid-cols-3">
              <div className="rounded-md bg-brand-teal-soft px-3 py-2 font-semibold text-brand-teal-dark">
                Có mặt: {summary.present}
              </div>
              <div className="rounded-md bg-amber-50 px-3 py-2 font-semibold text-amber-700">
                Đi muộn: {summary.late}
              </div>
              <div className="rounded-md bg-red-50 px-3 py-2 font-semibold text-brand-red">
                Vắng: {summary.absent}
              </div>
            </div>
          </div>

          {loadingRecords ? (
            <Loading label="Đang tải danh sách học sinh" />
          ) : (
            <Table
              columns={[
                { header: 'Mã', key: 'student_code' },
                { header: 'Học sinh', key: 'student_name' },
                {
                  header: 'Trạng thái',
                  key: 'status',
                  render: (row) => <StatusBadge status={row.status} />,
                },
                {
                  header: 'Thao tác',
                  key: 'actions',
                  render: (row) => (
                    <div className="flex flex-wrap items-center gap-2">
                      <Button
                        className="h-9 px-3"
                        icon={Check}
                        onClick={() => setStatus(row.student_id, 'present')}
                        variant={row.status === 'present' ? 'primary' : 'secondary'}
                      >
                        Có mặt
                      </Button>
                      <Button
                        className="h-9 px-3"
                        icon={Clock}
                        onClick={() => setStatus(row.student_id, 'late')}
                        variant={row.status === 'late' ? 'primary' : 'secondary'}
                      >
                        Đi muộn
                      </Button>
                      {row.status === 'late' && (
                        <div className="flex items-center gap-1">
                          <Input
                            className="w-20"
                            id={`late-${row.student_id}`}
                            min="0"
                            onChange={(event) =>
                              updateRecord(row.student_id, {
                                late_minutes: event.target.value,
                              })
                            }
                            type="number"
                            value={row.late_minutes}
                          />
                          <span className="text-xs text-brand-muted">phút</span>
                          {Number(row.late_minutes) >= 60 && (
                            <span className="text-xs font-medium text-brand-muted">
                              ({Math.floor(Number(row.late_minutes) / 60)} giờ{Number(row.late_minutes) % 60 > 0 ? ` ${Number(row.late_minutes) % 60} phút` : ''})
                            </span>
                          )}
                        </div>
                      )}
                      <Button
                        className="h-9 px-3"
                        icon={X}
                        onClick={() => setStatus(row.student_id, 'absent')}
                        variant={row.status === 'absent' ? 'danger' : 'secondary'}
                      >
                        Vắng
                      </Button>
                    </div>
                  ),
                },
              ]}
              data={visibleRecords}
              emptyText={studentSearch ? 'Không tìm thấy học sinh phù hợp' : 'Chưa có học sinh trong lớp'}
            />
          )}

          <div className="flex justify-end">
            <Button disabled={saving || !selectedClass || records.length === 0} icon={Save} onClick={handleSubmit}>
              {saving ? 'Đang gửi' : 'Gửi điểm danh'}
            </Button>
          </div>
        </>
      )}
    </div>
  )
}

export default AttendancePage
