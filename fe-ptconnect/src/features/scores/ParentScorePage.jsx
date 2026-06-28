import { Download } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { assignmentApi } from '../../api/assignmentApi'
import { scoreApi } from '../../api/scoreApi'
import Button from '../../components/common/Button'
import Input from '../../components/common/Input'
import Loading from '../../components/common/Loading'
import Select from '../../components/common/Select'
import { useToast } from '../../store/useToast'
import { formatDateTime } from '../../utils/formatDate'

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

function normalizeSearch(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd')
    .toLowerCase()
    .trim()
}

function hasScore(value) {
  return value !== null && value !== undefined && value !== ''
}

function formatScore(value) {
  if (!hasScore(value)) return 'Chưa chấm'

  const numericValue = Number(value)

  if (Number.isNaN(numericValue)) return value

  return numericValue.toLocaleString('vi-VN', {
    maximumFractionDigits: 2,
    minimumFractionDigits: 0,
  })
}

function getSubmittedTime(score) {
  if (!score.submitted_at) return 0

  const timestamp = Date.parse(score.submitted_at)
  return Number.isNaN(timestamp) ? 0 : timestamp
}

function matchesScoreFilter(score, filter) {
  const value = score.score

  if (filter === 'all') return true
  if (filter === 'graded') return hasScore(value)
  if (filter === 'ungraded') return !hasScore(value)
  if (!hasScore(value)) return false

  const numericScore = Number(value)

  if (Number.isNaN(numericScore)) return false

  if (filter === 'excellent') return numericScore >= 8
  if (filter === 'good') return numericScore >= 6.5 && numericScore < 8
  if (filter === 'average') return numericScore >= 5 && numericScore < 6.5
  if (filter === 'weak') return numericScore < 5

  return true
}

function ParentScorePage() {
  const toast = useToast()
  const [scores, setScores] = useState([])
  const [assignmentId, setAssignmentId] = useState('all')
  const [submittedDate, setSubmittedDate] = useState('')
  const [scoreFilter, setScoreFilter] = useState('all')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let mounted = true

    async function loadData() {
      try {
        const response = await scoreApi.getAll()

        if (!mounted) return

        setScores(response.data || [])
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

  const assignmentOptions = useMemo(() => {
    const options = new Map()

    scores.forEach((score) => {
      const key = score.assignment_id ? String(score.assignment_id) : score.assignment_title

      if (!key) return

      options.set(key, score.assignment_title || `Bài tập #${key}`)
    })

    return Array.from(options.entries()).sort((first, second) =>
      normalizeSearch(first[1]).localeCompare(normalizeSearch(second[1]), 'vi'),
    )
  }, [scores])

  const filteredScores = useMemo(() => {
    return scores
      .filter((score) => assignmentId === 'all' || String(score.assignment_id || score.assignment_title) === assignmentId)
      .filter((score) => !submittedDate || String(score.submitted_at || '').slice(0, 10) === submittedDate)
      .filter((score) => matchesScoreFilter(score, scoreFilter))
      .sort((first, second) => {
        const timeDiff = getSubmittedTime(second) - getSubmittedTime(first)

        if (timeDiff !== 0) return timeDiff

        return normalizeSearch(first.assignment_title).localeCompare(normalizeSearch(second.assignment_title), 'vi')
      })
  }, [assignmentId, scoreFilter, scores, submittedDate])

  const handleDownloadSubmission = async (score) => {
    try {
      const blob = await assignmentApi.downloadSubmission(score.submission_id || score.id)
      downloadBlob(blob, score.file_name || 'bai-nop')
    } catch (error) {
      toast.error('Không tải được bài nộp', error.message || 'Vui lòng thử lại sau.')
    }
  }

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Điểm số</h1>
        <p className="mt-1 text-sm text-brand-muted">Phụ huynh chỉ xem điểm, file bài nộp và nhận xét của con.</p>
      </div>

      <div className="grid gap-3 rounded-lg border border-brand-border bg-brand-white p-4 shadow-sm md:grid-cols-2 md:items-end xl:grid-cols-[120px_minmax(220px,1fr)_160px_170px]">
        <label className="block">
          <span className="mb-1.5 block text-sm font-medium text-brand-text">Môn học</span>
          <span className="flex h-10 items-center rounded-md border border-brand-border bg-brand-bg px-3 text-sm font-semibold text-brand-teal-dark">
            Sinh học
          </span>
        </label>
        <Select
          id="parent-score-assignment-filter"
          label="Bài tập"
          onChange={(event) => setAssignmentId(event.target.value)}
          value={assignmentId}
        >
          <option value="all">Tất cả bài tập</option>
          {assignmentOptions.map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </Select>
        <Input
          id="parent-score-submitted-date-filter"
          label="Ngày nộp"
          onChange={(event) => setSubmittedDate(event.target.value)}
          type="date"
          value={submittedDate}
        />
        <Select
          id="parent-score-value-filter"
          label="Điểm"
          onChange={(event) => setScoreFilter(event.target.value)}
          value={scoreFilter}
        >
          <option value="all">Tất cả điểm</option>
          <option value="graded">Đã chấm</option>
          <option value="ungraded">Chưa chấm</option>
          <option value="excellent">Từ 8 trở lên</option>
          <option value="good">6.5 - dưới 8</option>
          <option value="average">5 - dưới 6.5</option>
          <option value="weak">Dưới 5</option>
        </Select>
      </div>

      {loading ? (
        <Loading label="Đang tải điểm số" />
      ) : filteredScores.length ? (
        <div className="space-y-3">
          {filteredScores.map((score) => (
            <article className="rounded-lg border border-brand-border bg-brand-white p-4 shadow-sm" key={score.row_key || score.id}>
              <div className="grid gap-4 lg:grid-cols-[minmax(220px,1.4fr)_minmax(180px,1fr)_160px_110px] lg:items-start">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wide text-brand-muted">Bài tập</p>
                  <h2 className="mt-1 font-bold text-brand-text">{score.assignment_title || '-'}</h2>
                  <p className="mt-1 text-sm text-brand-muted">
                    {score.student_name || '-'} · {score.class_name || '-'}
                  </p>
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wide text-brand-muted">File bài nộp</p>
                  <p className="mt-1 break-words text-sm font-medium text-brand-text">{score.file_name || '-'}</p>
                  {score.file_name ? (
                    <Button
                      className="mt-2 h-9 px-3"
                      icon={Download}
                      onClick={() => handleDownloadSubmission(score)}
                      variant="secondary"
                    >
                      Tải bài nộp
                    </Button>
                  ) : null}
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wide text-brand-muted">Ngày nộp</p>
                  <p className="mt-1 text-sm font-medium text-brand-text">
                    {score.submitted_at ? formatDateTime(score.submitted_at) : '-'}
                  </p>
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wide text-brand-muted">Điểm</p>
                  <span className="mt-1 inline-flex h-9 min-w-16 items-center justify-center rounded-md bg-brand-teal-soft px-3 text-base font-bold text-brand-teal-dark">
                    {formatScore(score.score)}
                  </span>
                </div>
              </div>
              <div className="mt-4 rounded-md bg-brand-bg p-3">
                <p className="text-xs font-semibold uppercase tracking-wide text-brand-muted">Nhận xét</p>
                <p className="mt-1 text-sm text-brand-text">{score.comment || 'Chưa có nhận xét'}</p>
              </div>
            </article>
          ))}
        </div>
      ) : (
        <div className="rounded-lg border border-brand-border bg-brand-white px-4 py-8 text-center text-sm text-brand-muted">
          Chưa có điểm bài tập phù hợp.
        </div>
      )}
    </div>
  )
}

export default ParentScorePage
