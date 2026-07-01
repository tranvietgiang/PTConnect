import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { classApi } from '../../api/classApi'
import { scoreApi } from '../../api/scoreApi'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Loading from '../../components/common/Loading'
import Select from '../../components/common/Select'
import Table from '../../components/common/Table'
import { useToast } from '../../store/useToast'
import { formatDateTime } from '../../utils/formatDate'

function normalizeSearch(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd')
    .toLowerCase()
    .trim()
}

function getRecordTime(score) {
  if (!score.updated_at && !score.created_at) return 0

  const timestamp = Date.parse(score.updated_at || score.created_at)
  return Number.isNaN(timestamp) ? 0 : timestamp
}

function formatScore(value) {
  if (value === null || value === undefined || value === '') return 'Chưa chấm'

  const numericValue = Number(value)

  if (Number.isNaN(numericValue)) return value

  return numericValue.toLocaleString('vi-VN', {
    maximumFractionDigits: 2,
    minimumFractionDigits: 0,
  })
}

function ScoreListPage() {
  const toast = useToast()
  const [scores, setScores] = useState([])
  const [classes, setClasses] = useState([])
  const [gradeLevel, setGradeLevel] = useState('all')
  const [classroomId, setClassroomId] = useState('all')
  const [studentName, setStudentName] = useState('')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let mounted = true

    async function loadData() {
      try {
        const [scoreResponse, classResponse] = await Promise.all([
          scoreApi.getAll(),
          classApi.getAll(),
        ])

        if (!mounted) return

        setScores(scoreResponse.data || [])
        setClasses(classResponse.data || [])
      } catch (error) {
        if (mounted) {
          toast.error('Không tải được điểm số', error.message || 'Vui lòng thử lại sau.')
        }
      } finally {
        if (mounted) {
          setLoading(false)
        }
      }
    }

    loadData()

    return () => {
      mounted = false
    }
  }, [])

  const filteredClasses = useMemo(() => {
    if (gradeLevel === 'all') {
      return classes
    }

    return classes.filter((classroom) => Number(classroom.grade_level) === Number(gradeLevel))
  }, [classes, gradeLevel])

  const filteredScores = useMemo(() => {
    const keyword = normalizeSearch(studentName)

    return scores
      .filter((score) => gradeLevel === 'all' || Number(score.grade_level) === Number(gradeLevel))
      .filter((score) => classroomId === 'all' || String(score.classroom_id) === classroomId)
      .filter((score) => !keyword || normalizeSearch(score.student_name).includes(keyword))
      .sort((first, second) => {
        const timeDiff = getSubmittedTime(second) - getSubmittedTime(first)

        if (timeDiff !== 0) return timeDiff

        return normalizeSearch(first.student_name).localeCompare(normalizeSearch(second.student_name), 'vi')
      })
  }, [classroomId, gradeLevel, scores, studentName])

  const handleGradeChange = (event) => {
    const nextGradeLevel = event.target.value

    setGradeLevel(nextGradeLevel)
    setClassroomId((current) => {
      if (nextGradeLevel === 'all') return current

      const currentClass = classes.find((classroom) => String(classroom.id) === current)
      return currentClass && Number(currentClass.grade_level) === Number(nextGradeLevel) ? current : 'all'
    })
  }

  const columns = [
    { header: 'Học sinh', key: 'student_name' },
    { header: 'Lớp', key: 'class_name' },
    { header: 'Bài tập', key: 'assignment_title' },
    { header: 'Môn học', key: 'subject' },
    { header: 'Điểm', key: 'score', render: (row) => formatScore(row.score) },
    { header: 'Nhận xét', key: 'comment', render: (row) => row.comment || '-' },
    {
      header: 'Ngày nộp',
      key: 'submitted_at',
      render: (row) => (row.submitted_at ? formatDateTime(row.submitted_at) : '-'),
    },
  ]

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Điểm số</h1>
          <p className="mt-1 text-sm text-brand-muted">Điểm bài tập lấy trực tiếp từ bảng bài nộp.</p>
        </div>
        <Button as={Link} to="/diem-so/bao-cao" variant="secondary">
          Xem báo cáo
        </Button>
      </div>

      <div className="grid gap-3 rounded-lg border border-brand-border bg-brand-white p-4 shadow-sm md:grid-cols-[120px_140px_180px_minmax(220px,1fr)] md:items-end">
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
          onChange={(event) => setClassroomId(event.target.value)}
          value={classroomId}
        >
          <option value="all">Tất cả lớp</option>
          {filteredClasses.map((classroom) => (
            <option key={classroom.id} value={classroom.id}>
              {classroom.name}
            </option>
          ))}
        </Select>
        <Input
          id="score-student-filter"
          label="Tìm học sinh"
          onChange={(event) => setStudentName(event.target.value)}
          placeholder="Nhập tên học sinh"
          value={studentName}
        />
      </div>

      {loading ? (
        <Loading label="Đang tải điểm số" />
      ) : (
        <Table columns={columns} data={filteredScores} emptyText="Chưa có điểm bài tập phù hợp" />
      )}
    </div>
  )
}

export default ScoreListPage
