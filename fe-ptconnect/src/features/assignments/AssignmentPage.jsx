import { useEffect, useMemo, useState } from "react";
import { Download, Save, Upload } from "lucide-react";
import { assignmentApi } from "../../api/assignmentApi";
import { classApi } from "../../api/classApi";
import Button from "../../components/common/Button";
import Input from "../../components/common/Input";
import Loading from "../../components/common/Loading";
import Select from "../../components/common/Select";
import { useAuth } from "../../store/useAuth";
import { useToast } from "../../store/useToast";
import { formatDate, formatDateTime } from "../../utils/formatDate";

const assignmentGridClass =
  "grid min-w-[820px] grid-cols-[minmax(240px,2fr)_130px_150px_120px_150px] items-center gap-4";
const submissionGridClass =
  "grid min-w-[820px] grid-cols-[minmax(240px,1.3fr)_minmax(260px,1fr)_130px] items-center gap-4";

function downloadBlob(blob, filename) {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
}

function StatusBadge({ submitted }) {
  return (
    <span
      className={`inline-flex h-7 items-center rounded-md px-2.5 text-xs font-semibold ${
        submitted
          ? "bg-brand-teal-soft text-brand-teal-dark"
          : "bg-brand-bg text-brand-muted"
      }`}
    >
      {submitted ? "Đã nộp" : "Chưa nộp"}
    </span>
  );
}

function SubmissionStatusText({ submitted }) {
  return (
    <span
      className={`text-sm font-semibold ${submitted ? "text-brand-teal-dark" : "text-brand-muted"}`}
    >
      {submitted ? "Đã nộp" : "Chưa nộp"}
    </span>
  );
}

function EmptyList({ children }) {
  return (
    <div className="rounded-lg border border-brand-border bg-brand-white px-4 py-8 text-center text-sm text-brand-muted">
      {children}
    </div>
  );
}

function normalizeSearch(value) {
  return String(value || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[đĐ]/g, "d")
    .toLowerCase()
    .trim();
}

function isValidScoreInput(value) {
  const rawScore = String(value ?? "").trim();

  return (
    rawScore === "" ||
    /^(10([,.]0{1,2})?|[0-9]([,.][0-9]{1,2})?)$/.test(rawScore)
  );
}

function isSubmissionSubmitted(submission) {
  return submission.status === "submitted";
}

function getSubmittedTime(submission) {
  if (!submission.submitted_at) return 0;

  const timestamp = Date.parse(submission.submitted_at);
  return Number.isNaN(timestamp) ? 0 : timestamp;
}

function sortSubmissionRows(first, second) {
  const firstSubmitted = isSubmissionSubmitted(first);
  const secondSubmitted = isSubmissionSubmitted(second);

  if (firstSubmitted !== secondSubmitted) {
    return firstSubmitted ? -1 : 1;
  }

  if (firstSubmitted && secondSubmitted) {
    const timeDiff = getSubmittedTime(second) - getSubmittedTime(first);

    if (timeDiff !== 0) {
      return timeDiff;
    }
  }

  return normalizeSearch(first.student_name).localeCompare(
    normalizeSearch(second.student_name),
    "vi",
  );
}

function getRowKey(row) {
  return row.row_key || row.id;
}

function isSubmitted(row) {
  return row.submission_status === "submitted" || Boolean(row.submission);
}

function AssignmentPage() {
  const { user } = useAuth();
  const toast = useToast();
  const canCreate = ["school_admin", "system_admin", "teacher"].includes(user?.role);
  const canAssignByGrade = ["school_admin", "system_admin"].includes(user?.role);
  const isStudent = user?.role === "student";
  const [assignments, setAssignments] = useState([]);
  const [classes, setClasses] = useState([]);
  const [selectedGradeLevel, setSelectedGradeLevel] = useState("all");
  const [selectedClassId, setSelectedClassId] = useState("");
  const [studentSearch, setStudentSearch] = useState("");
  const [submissionStatusFilter, setSubmissionStatusFilter] = useState("all");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [savingGradeId, setSavingGradeId] = useState(null);
  const [uploadingId, setUploadingId] = useState(null);
  const [fileInputVersion, setFileInputVersion] = useState(0);
  const [gradingForms, setGradingForms] = useState({});
  const [submissionFiles, setSubmissionFiles] = useState({});
  const [form, setForm] = useState({
    title: "",
    description: "",
    classroom_id: "",
    grade_level: "",
    due_date: "",
    attachment_file: null,
  });
  const [errors, setErrors] = useState({});

  async function loadData() {
    setLoading(true);

    try {
      const assignmentResponse = await assignmentApi.getAll();
      setAssignments(assignmentResponse.data || []);

      if (canCreate) {
        const classResponse = await classApi.getAll();
        const classData = classResponse.data || [];

        setClasses(classData);
        setSelectedClassId(
          (current) =>
            current || (classData[0]?.id ? String(classData[0].id) : ""),
        );
      }
    } catch (error) {
      toast.error(
        "Không tải được bài tập",
        error.message || "Vui lòng thử lại sau.",
      );
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, []);

  const selectedClass = useMemo(
    () =>
      classes.find((classroom) => String(classroom.id) === selectedClassId) ||
      null,
    [classes, selectedClassId],
  );

  const filteredClasses = useMemo(() => {
    if (selectedGradeLevel === "all") {
      return classes;
    }

    return classes.filter(
      (classroom) =>
        Number(classroom.grade_level) === Number(selectedGradeLevel),
    );
  }, [classes, selectedGradeLevel]);

  const visibleAssignments = useMemo(() => {
    const searchTerm = normalizeSearch(studentSearch);
    const selectedStatus = submissionStatusFilter;
    const selectedGrade =
      selectedGradeLevel === "all" ? null : Number(selectedGradeLevel);
    const gradeClassNames = selectedGrade
      ? new Set(
          classes
            .filter(
              (classroom) => Number(classroom.grade_level) === selectedGrade,
            )
            .map((classroom) => classroom.name),
        )
      : null;

    if (isStudent) {
      return assignments;
    }

    if (selectedClassId && !selectedClass) {
      return [];
    }

    if (
      selectedClass &&
      selectedGrade &&
      Number(selectedClass.grade_level) !== selectedGrade
    ) {
      return [];
    }

    return assignments
      .map((assignment) => {
        let submissions = assignment.submissions || [];
        const assignmentClass = assignment.classroom_id
          ? classes.find(
              (classroom) =>
                String(classroom.id) === String(assignment.classroom_id),
            )
          : null;

        if (selectedClass) {
          const matchesClass =
            String(assignment.classroom_id || "") === selectedClassId;
          const matchesGrade =
            assignment.grade_level &&
            Number(assignment.grade_level) ===
              Number(selectedClass.grade_level);

          if (!matchesClass && !matchesGrade) {
            return null;
          }

          submissions = submissions.filter(
            (submission) => submission.class_name === selectedClass.name,
          );
        } else if (selectedGrade) {
          const matchesGrade =
            Number(assignment.grade_level) === selectedGrade ||
            Number(assignmentClass?.grade_level) === selectedGrade;

          if (!matchesGrade) {
            return null;
          }

          submissions = submissions.filter((submission) =>
            gradeClassNames.has(submission.class_name),
          );
        }

        const submittedCount = submissions.filter(
          (submission) => submission.status === "submitted",
        ).length;
        let visibleSubmissions = submissions;

        if (selectedStatus === "graded") {
          visibleSubmissions = visibleSubmissions.filter(
            (submission) =>
              submission.status === "submitted" &&
              submission.score !== null &&
              submission.score !== undefined &&
              submission.score !== "",
          );
        } else if (selectedStatus === "ungraded") {
          visibleSubmissions = visibleSubmissions.filter(
            (submission) =>
              submission.status === "submitted" &&
              (submission.score === null ||
                submission.score === undefined ||
                submission.score === ""),
          );
        } else if (selectedStatus !== "all") {
          visibleSubmissions = visibleSubmissions.filter(
            (submission) => submission.status === selectedStatus,
          );
        }

        if (searchTerm) {
          visibleSubmissions = visibleSubmissions.filter((submission) =>
            normalizeSearch(submission.student_name).includes(searchTerm),
          );
        }

        if (
          (searchTerm || selectedStatus !== "all") &&
          !visibleSubmissions.length
        ) {
          return null;
        }

        visibleSubmissions = [...visibleSubmissions].sort(sortSubmissionRows);

        return {
          ...assignment,
          submissions: visibleSubmissions,
          submitted_count: submittedCount,
          student_count: submissions.length,
          matched_student_count: visibleSubmissions.length,
        };
      })
      .filter(Boolean);
  }, [
    assignments,
    classes,
    isStudent,
    selectedClass,
    selectedClassId,
    selectedGradeLevel,
    studentSearch,
    submissionStatusFilter,
  ]);

  const updateForm = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: undefined }));
  };

  const validateForm = () => {
    const nextErrors = {};

    if (!form.title.trim()) nextErrors.title = "Vui lòng nhập tiêu đề bài tập.";
    if (!form.classroom_id && (!canAssignByGrade || !form.grade_level)) {
      nextErrors.scope = canAssignByGrade
        ? "Vui lòng chọn lớp hoặc khối."
        : "Vui lòng chọn lớp được phân công.";
    }

    setErrors(nextErrors);

    if (Object.keys(nextErrors).length > 0) {
      toast.error(
        "Thiếu thông tin bài tập",
        "Vui lòng nhập tiêu đề và phạm vi giao bài.",
      );
      return false;
    }

    return true;
  };

  const handleCreate = async (event) => {
    event.preventDefault();

    if (!validateForm()) return;

    setSaving(true);

    try {
      const payload = new FormData();
      payload.append("title", form.title.trim());
      payload.append("description", form.description.trim());
      payload.append("status", "published");
      if (form.classroom_id) payload.append("classroom_id", form.classroom_id);
      if (canAssignByGrade && form.grade_level)
        payload.append("grade_level", form.grade_level);
      if (form.due_date) payload.append("due_date", form.due_date);
      if (form.attachment_file)
        payload.append("attachment_file", form.attachment_file);

      await assignmentApi.create(payload);
      toast.success(
        "Đã tạo bài tập",
        "Bài tập đã được giao cho phụ huynh/học sinh.",
      );
      setForm({
        title: "",
        description: "",
        classroom_id: "",
        grade_level: "",
        due_date: "",
        attachment_file: null,
      });
      event.currentTarget.reset();
      await loadData();
    } catch (error) {
      toast.error(
        "Không tạo được bài tập",
        error.message || "Vui lòng kiểm tra lại thông tin.",
      );
    } finally {
      setSaving(false);
    }
  };

  const handleDownloadAttachment = async (assignment) => {
    try {
      const blob = await assignmentApi.downloadAttachment(assignment.id);
      downloadBlob(
        blob,
        assignment.attachment_name || `${assignment.title}.download`,
      );
    } catch (error) {
      toast.error(
        "Không tải được file",
        error.message || "Vui lòng thử lại sau.",
      );
    }
  };

  const handleDownloadSubmission = async (submission) => {
    try {
      const blob = await assignmentApi.downloadSubmission(submission.id);
      downloadBlob(blob, submission.file_name || "bai-nop");
    } catch (error) {
      toast.error(
        "Không tải được bài nộp",
        error.message || "Vui lòng thử lại sau.",
      );
    }
  };

  const handleSubmitAssignment = async (assignment) => {
    const rowKey = getRowKey(assignment);
    const file = submissionFiles[rowKey];

    if (assignment.is_overdue || assignment.can_submit === false) {
      toast.error(
        "Đã quá hạn nộp bài",
        "Bạn không thể nộp thêm hoặc cập nhật bài nộp.",
      );
      return;
    }

    if (!file) {
      toast.error(
        "Chưa chọn file",
        "Vui lòng chọn file bài làm trước khi nộp.",
      );
      return;
    }

    setUploadingId(rowKey);

    try {
      const payload = new FormData();
      payload.append("student_id", assignment.student_id);
      payload.append("submitted_file", file);

      await assignmentApi.submit(assignment.id, payload);
      toast.success(
        "Đã nộp bài",
        "File bài làm đã được cập nhật trên hệ thống.",
      );
      setSubmissionFiles((current) => ({ ...current, [rowKey]: null }));
      setFileInputVersion((current) => current + 1);
      await loadData();
    } catch (error) {
      toast.error(
        "Không nộp được bài",
        error.message || "Vui lòng kiểm tra định dạng file.",
      );
    } finally {
      setUploadingId(null);
    }
  };

  const getGradingForm = (submission) =>
    gradingForms[submission.id] || {
      score: submission.score ?? "",
      teacher_comment: submission.teacher_comment ?? "",
    };

  const updateGradingForm = (submission, field, value) => {
    setGradingForms((current) => ({
      ...current,
      [submission.id]: {
        ...getGradingForm(submission),
        [field]: value,
      },
    }));
  };

  const handleSaveGrade = async (submission) => {
    const formData = getGradingForm(submission);
    const rawScore = String(formData.score ?? "").trim();
    const score = rawScore === "" ? null : rawScore.replace(",", ".");

    if (!isValidScoreInput(rawScore)) {
      toast.error(
        "Điểm không hợp lệ",
        "Vui lòng nhập điểm từ 0 đến 10, tối đa 2 chữ số thập phân.",
      );
      return;
    }

    setSavingGradeId(submission.id);

    try {
      await assignmentApi.gradeSubmission(submission.id, {
        score,
        teacher_comment: String(formData.teacher_comment || "").trim(),
      });
      toast.success(
        "Đã lưu điểm",
        "Điểm và nhận xét bài nộp đã được cập nhật.",
      );
      setGradingForms((current) => {
        const next = { ...current };
        delete next[submission.id];
        return next;
      });
      await loadData();
    } catch (error) {
      toast.error(
        "Không lưu được điểm",
        error.message || "Vui lòng kiểm tra lại điểm và nhận xét.",
      );
    } finally {
      setSavingGradeId(null);
    }
  };

  const renderScope = (row) => {
    if (row.class_name) return `Lớp ${row.class_name}`;
    if (!isStudent && selectedClass && row.grade_level && !row.classroom_id) {
      return `Khối ${row.grade_level} - lớp ${selectedClass.name}`;
    }
    if (row.grade_level) return `Khối ${row.grade_level}`;
    return "Chưa chọn phạm vi";
  };

  const renderSubmissionInfo = (submission) => {
    if (!submission) return null;

    return (
      <div className="space-y-2 text-sm">
        <div>
          <p className="font-medium text-brand-text">
            {submission.file_name || "File bài nộp"}
          </p>
          <p className="text-xs text-brand-muted">
            {submission.submitted_at
              ? `Nộp lúc ${formatDateTime(submission.submitted_at)}`
              : "Chưa có thời gian nộp"}
          </p>
        </div>
        <Button
          icon={Download}
          onClick={() => handleDownloadSubmission(submission)}
          variant="secondary"
        >
          Tải bài nộp
        </Button>
      </div>
    );
  };

  const renderParentSubmission = (row) => {
    const rowKey = getRowKey(row);
    const submitted = isSubmitted(row);
    const disabled = row.is_overdue || row.can_submit === false;
    const selectedFile = submissionFiles[rowKey];

    return (
      <div className="space-y-3">
        {submitted ? (
          renderSubmissionInfo(row.submission)
        ) : (
          <p className="text-sm text-brand-muted">Chưa có file bài nộp</p>
        )}

        <div className="space-y-2">
          {disabled ? (
            <p className="rounded-md bg-brand-bg px-3 py-2 text-sm font-medium text-brand-red">
              Đã quá hạn nộp bài, bạn không thể nộp thêm hoặc cập nhật bài nộp.
            </p>
          ) : null}
          <p className="text-sm font-medium text-brand-text">
            {submitted ? "Cập nhật file bài nộp" : "Chọn file để nộp bài"}
          </p>
          <input
            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.rar,.7z,.txt,.csv"
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
            {uploadingId === rowKey
              ? "Đang nộp"
              : submitted
                ? "Cập nhật bài nộp"
                : "Nộp bài"}
          </Button>
        </div>
      </div>
    );
  };

  const renderAttachmentCell = (assignment) =>
    assignment.has_attachment ? (
      <Button
        icon={Download}
        onClick={() => handleDownloadAttachment(assignment)}
        variant="secondary"
      >
        Tải file
      </Button>
    ) : (
      <span className="text-sm text-brand-muted">Không có</span>
    );

  const renderGradingBox = (submission) => {
    const formData = getGradingForm(submission);

    return (
      <div className="mt-3 rounded-md border border-brand-border bg-brand-bg p-3">
        <div className="flex items-end gap-2">
          <label className="block w-24 shrink-0">
            <span className="mb-1 block text-xs font-semibold text-brand-muted">
              Điểm
            </span>
            <input
              className="h-9 w-full rounded-md border border-brand-border bg-brand-white px-2 text-sm text-brand-text outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft"
              inputMode="decimal"
              maxLength={4}
              onChange={(event) =>
                updateGradingForm(submission, "score", event.target.value)
              }
              placeholder="VD: 9,5"
              type="text"
              value={formData.score}
            />
          </label>
          <Button
            className="h-9 shrink-0 px-3"
            disabled={savingGradeId === submission.id}
            icon={Save}
            onClick={() => handleSaveGrade(submission)}
            variant="secondary"
          >
            {savingGradeId === submission.id ? "Đang lưu" : "Lưu điểm"}
          </Button>
        </div>
        <label className="mt-2 block">
          <span className="mb-1 block text-xs font-semibold text-brand-muted">
            Nhận xét
          </span>
          <textarea
            className="w-full rounded-md border border-brand-border bg-brand-white px-2 py-1.5 text-sm text-brand-text outline-none focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft"
            onChange={(event) =>
              updateGradingForm(
                submission,
                "teacher_comment",
                event.target.value,
              )
            }
            placeholder="Nhập nhận xét"
            rows={2}
            value={formData.teacher_comment}
          />
        </label>
      </div>
    );
  };

  const renderStaffAssignmentList = () => {
    const searchActive = Boolean(normalizeSearch(studentSearch));
    const filterActive = searchActive || submissionStatusFilter !== "all";

    if (!visibleAssignments.length) {
      return (
        <EmptyList>
          {filterActive
            ? "Không tìm thấy bài nộp phù hợp với bộ lọc hiện tại."
            : "Chưa có bài tập"}
        </EmptyList>
      );
    }

    return (
      <div className="rounded-lg border border-brand-border bg-brand-white p-4 shadow-sm">
        <div className="overflow-x-auto">
          <div className="min-w-[820px]">
            <div
              className={`${assignmentGridClass} rounded-t-md bg-brand-bg px-4 py-3 text-xs font-bold uppercase tracking-wide text-brand-muted`}
            >
              <span>Bài tập</span>
              <span>Hạn nộp</span>
              <span>Trạng thái</span>
              <span>Tài liệu</span>
              <span>Theo dõi bài nộp</span>
            </div>

            <div className="space-y-5 pt-3">
              {visibleAssignments.map((assignment) => {
                const submissions = assignment.submissions || [];

                return (
                  <section
                    className="space-y-3"
                    key={assignment.row_key || assignment.id}
                  >
                    <div
                      className={`${assignmentGridClass} rounded-md border border-brand-border bg-brand-white px-4 py-3`}
                    >
                      <div>
                        <p className="font-semibold text-brand-text">
                          {assignment.title}
                        </p>
                        <p className="text-sm text-brand-text">
                          {renderScope(assignment)}
                        </p>
                      </div>
                      <div className="text-sm text-brand-text">
                        {assignment.due_date
                          ? formatDate(assignment.due_date)
                          : "Không có"}
                        {assignment.is_overdue ? (
                          <p className="mt-1 text-xs font-semibold text-brand-red">
                            Quá hạn
                          </p>
                        ) : null}
                      </div>
                      <div className="space-y-1">
                        <p className="font-semibold text-brand-text">
                          {assignment.submitted_count || 0}/
                          {assignment.student_count || 0} đã nộp
                        </p>
                        <p className="text-sm text-brand-muted">
                          {(assignment.student_count || 0) -
                            (assignment.submitted_count || 0)}{" "}
                          chưa nộp
                        </p>
                      </div>
                      <div>{renderAttachmentCell(assignment)}</div>
                      <div className="text-sm font-medium text-brand-muted">
                        {filterActive
                          ? `${submissions.length} kết quả`
                          : `${submissions.length} học sinh`}
                      </div>
                    </div>

                    <div className="space-y-2">
                      {submissions.length ? (
                        submissions.map((submission) => {
                          const submitted = submission.status === "submitted";

                          return (
                            <div
                              className={`${submissionGridClass} rounded-md border border-brand-border bg-brand-white px-4 py-3`}
                              key={`${assignment.id}-${submission.student_id}`}
                            >
                              <div>
                                <p className="font-medium text-brand-text">
                                  {submission.student_name}
                                </p>
                                <p className="text-sm text-brand-text">
                                  {submission.class_name ||
                                    renderScope(assignment)}
                                </p>
                              </div>
                              <div>
                                {submitted ? (
                                  <div className="space-y-2 text-sm">
                                    <div>
                                      <p className="font-medium text-brand-text">
                                        {submission.file_name || "File bài nộp"}
                                      </p>
                                      <p className="text-xs text-brand-muted">
                                        {submission.submitted_at
                                          ? `Nộp lúc ${formatDateTime(submission.submitted_at)}`
                                          : "Chưa có thời gian nộp"}
                                      </p>
                                    </div>
                                    <Button
                                      className="h-9 px-3"
                                      icon={Download}
                                      onClick={() =>
                                        handleDownloadSubmission(submission)
                                      }
                                      variant="secondary"
                                    >
                                      Tải bài nộp
                                    </Button>
                                    {renderGradingBox(submission)}
                                  </div>
                                ) : (
                                  <span className="text-sm text-brand-muted">
                                    -
                                  </span>
                                )}
                              </div>
                              <div className="justify-self-end">
                                <SubmissionStatusText submitted={submitted} />
                              </div>
                            </div>
                          );
                        })
                      ) : (
                        <div className="rounded-md border border-dashed border-brand-border bg-brand-bg px-4 py-4 text-sm text-brand-muted">
                          Chưa có học sinh trong lớp này.
                        </div>
                      )}
                    </div>
                  </section>
                );
              })}
            </div>
          </div>
        </div>
      </div>
    );
  };

  const renderParentAssignmentList = () => {
    if (!visibleAssignments.length) {
      return <EmptyList>Chưa có bài tập</EmptyList>;
    }

    return (
      <div className="space-y-3">
        {visibleAssignments.map((assignment) => {
          const submitted = isSubmitted(assignment);

          return (
            <article
              className="rounded-lg border border-brand-border bg-brand-white p-4 shadow-sm"
              key={getRowKey(assignment)}
            >
              <div className="grid gap-4 md:grid-cols-[minmax(220px,1fr)_130px_120px_120px] md:items-center">
                <div>
                  <p className="font-semibold text-brand-text">
                    {assignment.title}
                  </p>
                  <p className="text-sm text-brand-muted">
                    {renderScope(assignment)}
                  </p>
                  {assignment.student_name ? (
                    <p className="text-sm text-brand-muted">
                      Học sinh: {assignment.student_name}
                    </p>
                  ) : null}
                </div>
                <div className="text-sm text-brand-text">
                  {assignment.due_date
                    ? formatDate(assignment.due_date)
                    : "Không có"}
                  {assignment.is_overdue ? (
                    <p className="mt-1 text-xs font-semibold text-brand-red">
                      Quá hạn
                    </p>
                  ) : null}
                </div>
                <StatusBadge submitted={submitted} />
                <div>{renderAttachmentCell(assignment)}</div>
              </div>
              <div className="mt-4 border-t border-brand-border pt-4">
                {renderParentSubmission(assignment)}
              </div>
            </article>
          );
        })}
      </div>
    );
  };

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Bài tập</h1>
        <p className="mt-1 text-sm text-brand-muted">
          Giao bài, tải tài liệu và theo dõi bài nộp.
        </p>
      </div>

      {canCreate ? (
        <form
          className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm"
          onSubmit={handleCreate}
        >
          <div className="grid gap-4 md:grid-cols-2">
            <Input
              error={errors.title}
              id="assignment-title"
              label="Tiêu đề"
              onChange={(event) => updateForm("title", event.target.value)}
              placeholder="Ôn tập phần di truyền cơ bản"
              value={form.title}
            />
            <Input
              id="assignment-due-date"
              label="Hạn nộp"
              onChange={(event) => updateForm("due_date", event.target.value)}
              type="date"
              value={form.due_date}
            />
            <Select
              error={errors.scope}
              id="assignment-class"
              label="Giao theo lớp"
              onChange={(event) =>
                updateForm("classroom_id", event.target.value)
              }
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
                onChange={(event) =>
                  updateForm("grade_level", event.target.value)
                }
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
              onChange={(event) =>
                updateForm("description", event.target.value)
              }
              placeholder="Nội dung cần hoàn thành"
              value={form.description}
            />
            <Input
              id="assignment-file"
              label="File đính kèm"
              onChange={(event) =>
                updateForm("attachment_file", event.target.files?.[0] || null)
              }
              type="file"
            />
          </div>
          <div className="mt-5 flex justify-end">
            <Button disabled={saving} icon={Save} type="submit">
              {saving ? "Đang lưu" : "Giao bài tập"}
            </Button>
          </div>
        </form>
      ) : null}

      {loading ? (
        <Loading label="Đang tải bài tập" />
      ) : (
        <div className="space-y-4">
          {!isStudent ? (
            <div className="flex flex-col gap-3 rounded-lg border border-brand-border bg-brand-white p-4 shadow-sm md:flex-row md:items-end md:justify-between">
              <div className="grid w-full gap-3 md:grid-cols-[120px_130px_minmax(190px,240px)_minmax(220px,300px)_minmax(180px,220px)] md:items-end">
                <label className="block">
                  <span className="mb-1.5 block text-sm font-medium text-brand-text">
                    Môn học
                  </span>
                  <span className="flex h-10 items-center rounded-md border border-brand-border bg-brand-bg px-3 text-sm font-semibold text-brand-teal-dark">
                    Sinh học
                  </span>
                </label>
                <Select
                  id="assignment-grade-filter"
                  label="Khối"
                  onChange={(event) => {
                    const nextGrade = event.target.value;
                    setSelectedGradeLevel(nextGrade);
                    setSelectedClassId((current) => {
                      if (nextGrade === "all") return current;

                      const classroom = classes.find(
                        (item) => String(item.id) === current,
                      );
                      return classroom &&
                        Number(classroom.grade_level) === Number(nextGrade)
                        ? current
                        : "";
                    });
                  }}
                  value={selectedGradeLevel}
                >
                  <option value="all">Tất cả</option>
                  <option value="10">Khối 10</option>
                  <option value="11">Khối 11</option>
                  <option value="12">Khối 12</option>
                </Select>
                <Select
                  id="assignment-class-filter"
                  label="Xem theo lớp"
                  onChange={(event) => setSelectedClassId(event.target.value)}
                  value={selectedClassId}
                >
                  <option value="">Tất cả lớp</option>
                  {filteredClasses.map((classroom) => (
                    <option key={classroom.id} value={classroom.id}>
                      {classroom.name} - Khối {classroom.grade_level}
                    </option>
                  ))}
                </Select>
                <Input
                  id="assignment-student-search"
                  label="Tìm học sinh"
                  onChange={(event) => setStudentSearch(event.target.value)}
                  placeholder="Nhập tên học sinh"
                  value={studentSearch}
                />
                <Select
                  id="assignment-status-filter"
                  label="Trạng thái"
                  onChange={(event) =>
                    setSubmissionStatusFilter(event.target.value)
                  }
                  value={submissionStatusFilter}
                >
                  <option value="all">Tất cả trạng thái</option>
                  <option value="submitted">Đã nộp</option>
                  <option value="not_submitted">Chưa nộp</option>
                  <option value="graded">Đã chấm</option>
                  <option value="ungraded">Chưa chấm</option>
                </Select>
              </div>
              <p className="text-sm font-medium text-brand-muted">
                {visibleAssignments.length} bài tập
                {selectedClass ? ` của lớp ${selectedClass.name}` : ""}
              </p>
            </div>
          ) : null}

          {isStudent
            ? renderParentAssignmentList()
            : renderStaffAssignmentList()}
        </div>
      )}
    </div>
  );
}

export default AssignmentPage;
