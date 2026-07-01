import { useEffect, useState } from "react";
import { Save } from "lucide-react";
import { Navigate, useNavigate } from "react-router-dom";
import { classApi } from "../../api/classApi";
import { userApi } from "../../api/userApi";
import { academicYearApi } from "../../api/academicYearApi";
import Button from "../../components/common/Button";
import Input from "../../components/common/Input";
import Select from "../../components/common/Select";
import { useAuth } from "../../store/useAuth";
import { useToast } from "../../store/useToast";

const DAYS_OF_WEEK = [
  "Thứ 2", "Thứ 3", "Thứ 4", "Thứ 5", "Thứ 6", "Thứ 7", "CN",
];

function ClassCreatePage() {
  const { user } = useAuth();
  const toast = useToast();
  const navigate = useNavigate();
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});
  const [academicYears, setAcademicYears] = useState([]);
  const [teachers, setTeachers] = useState([]);
  const [assistants, setAssistants] = useState([]);
  const [form, setForm] = useState({
    name: "",
    grade_level: "",
    academic_year_id: "",
    status: "upcoming",
    start_date: "",
    end_date: "",
    total_lessons: "30",
    schedule_days: [],
    start_time: "",
    end_time: "",
    teacher_id: "",
    assistant_id: "",
    max_students: "30",
    description: "",
  });

  useEffect(() => {
    Promise.all([
      academicYearApi.getAll({ active: true }),
      userApi.getByRole(["teacher"]),
      userApi.getByRole(["assistant"]),
    ]).then(([yearsRes, teachersRes, assistantsRes]) => {
      if (yearsRes?.data) setAcademicYears(yearsRes.data);
      if (teachersRes?.data) setTeachers(teachersRes.data);
      if (assistantsRes?.data) setAssistants(assistantsRes.data);
    }).catch(() => {});
  }, []);

  if (!["system_admin", "school_admin"].includes(user?.role)) {
    return <Navigate replace to="/khong-co-quyen" />;
  }

  const updateForm = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: undefined }));
  };

  const toggleDay = (day) => {
    setForm((current) => {
      const days = current.schedule_days.includes(day)
        ? current.schedule_days.filter((d) => d !== day)
        : [...current.schedule_days, day];
      return { ...current, schedule_days: days };
    });
    setErrors((current) => ({ ...current, schedule_days: undefined }));
  };

  const validateForm = () => {
    const nextErrors = {};
    const totalLessons = Number(form.total_lessons);
    const maxStudents = Number(form.max_students);

    if (!form.name.trim()) nextErrors.name = "Vui lòng nhập tên lớp.";
    if (!form.grade_level) nextErrors.grade_level = "Vui lòng chọn khối lớp.";
    if (!form.academic_year_id) nextErrors.academic_year_id = "Vui lòng chọn năm học.";
    if (!form.start_date) nextErrors.start_date = "Vui lòng chọn ngày bắt đầu.";
    if (!form.end_date) nextErrors.end_date = "Vui lòng chọn ngày kết thúc.";
    if (form.start_date && form.end_date && form.end_date < form.start_date) {
      nextErrors.end_date = "Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.";
    }
    if (!Number.isInteger(totalLessons) || totalLessons < 1 || totalLessons > 100) {
      nextErrors.total_lessons = "Tổng số buổi phải từ 1 đến 100.";
    }
    if (form.start_time && form.end_time && form.start_time >= form.end_time) {
      nextErrors.end_time = "Giờ kết thúc phải sau giờ bắt đầu.";
    }
    if (maxStudents && (!Number.isInteger(maxStudents) || maxStudents < 1 || maxStudents > 200)) {
      nextErrors.max_students = "Sĩ số tối đa từ 1 đến 200.";
    }

    setErrors(nextErrors);

    if (Object.keys(nextErrors).length > 0) {
      toast.error(
        "Thiếu thông tin lớp học",
        "Vui lòng kiểm tra lại các trường bắt buộc.",
      );
      return false;
    }

    return true;
  };

  const handleSubmit = async (event) => {
    event.preventDefault();

    if (!validateForm()) return;

    setSaving(true);

    try {
      await classApi.create({
        name: form.name.trim(),
        grade_level: Number(form.grade_level),
        academic_year_id: form.academic_year_id ? Number(form.academic_year_id) : undefined,
        teacher_id: form.teacher_id ? Number(form.teacher_id) : null,
        start_date: form.start_date,
        end_date: form.end_date,
        total_lessons: Number(form.total_lessons),
        description: form.description.trim() || null,
        status: form.status,
        is_active: form.status !== "inactive",
      });
      toast.success("Đã thêm lớp học", "Lớp học mới đã được tạo.");
      navigate("/lop-hoc", { replace: true });
    } catch (error) {
      toast.error(
        "Không thêm được lớp học",
        error.message || "Vui lòng kiểm tra lại thông tin.",
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="mx-auto max-w-3xl space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Thêm lớp học</h1>
        <p className="mt-1 text-sm text-brand-muted">
          Nhập thông tin chi tiết để tạo lớp học mới.
        </p>
      </div>

      <form
        className="rounded-lg border border-brand-border bg-brand-white p-6 shadow-sm"
        onSubmit={handleSubmit}
      >
        <div className="grid gap-5 sm:grid-cols-2">
          <Select
            error={errors.grade_level}
            id="class-grade"
            label="Khối"
            onChange={(event) => updateForm("grade_level", event.target.value)}
            value={form.grade_level}
          >
            <option value="">Chọn khối</option>
            <option value="10">Khối 10</option>
            <option value="11">Khối 11</option>
            <option value="12">Khối 12</option>
          </Select>

          <Input
            error={errors.name}
            id="class-name"
            label="Tên lớp"
            onChange={(event) => updateForm("name", event.target.value)}
            placeholder="10A1"
            value={form.name}
          />

          <Select
            error={errors.academic_year_id}
            id="class-academic-year"
            label="Năm học"
            onChange={(event) => updateForm("academic_year_id", event.target.value)}
            value={form.academic_year_id}
          >
            <option value="">Chọn năm học</option>
            {academicYears.map((year) => (
              <option key={year.id} value={year.id}>
                {year.name}
              </option>
            ))}
          </Select>

          <Select
            error={errors.status}
            id="class-status"
            label="Trạng thái"
            onChange={(event) => updateForm("status", event.target.value)}
            value={form.status}
          >
            <option value="upcoming">Sắp khai giảng</option>
            <option value="active">Đang học</option>
            <option value="inactive">Tạm dừng</option>
          </Select>

          <Input
            error={errors.start_date}
            id="class-start-date"
            label="Ngày bắt đầu"
            onChange={(event) => updateForm("start_date", event.target.value)}
            type="date"
            value={form.start_date}
          />

          <Input
            error={errors.end_date}
            id="class-end-date"
            label="Ngày kết thúc dự kiến"
            onChange={(event) => updateForm("end_date", event.target.value)}
            type="date"
            value={form.end_date}
          />

          <Input
            error={errors.total_lessons}
            id="class-total-lessons"
            label="Tổng số buổi"
            max="100"
            min="1"
            onChange={(event) => updateForm("total_lessons", event.target.value)}
            type="number"
            value={form.total_lessons}
          />

          <Select
            error={errors.teacher_id}
            id="class-teacher"
            label="Giáo viên phụ trách"
            onChange={(event) => updateForm("teacher_id", event.target.value)}
            value={form.teacher_id}
          >
            <option value="">Chọn giáo viên</option>
            {teachers.map((t) => (
              <option key={t.id} value={t.id}>
                {t.name}
              </option>
            ))}
          </Select>

          <div className="sm:col-span-2">
            <span className="mb-1.5 block text-sm font-medium text-brand-text">
              Lịch học
            </span>
            <div className="flex flex-wrap gap-2">
              {DAYS_OF_WEEK.map((day) => (
                <label
                  key={day}
                  className={`flex cursor-pointer items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm transition ${
                    form.schedule_days.includes(day)
                      ? "border-brand-teal bg-brand-teal-soft text-brand-teal"
                      : "border-brand-border text-brand-text hover:border-brand-teal"
                  }`}
                >
                  <input
                    checked={form.schedule_days.includes(day)}
                    className="sr-only"
                    onChange={() => toggleDay(day)}
                    type="checkbox"
                  />
                  {day}
                </label>
              ))}
            </div>
          </div>

          <Input
            error={errors.start_time}
            id="class-start-time"
            label="Giờ bắt đầu"
            onChange={(event) => updateForm("start_time", event.target.value)}
            type="time"
            value={form.start_time}
          />

          <Input
            error={errors.end_time}
            id="class-end-time"
            label="Giờ kết thúc"
            onChange={(event) => updateForm("end_time", event.target.value)}
            type="time"
            value={form.end_time}
          />

          <Select
            error={errors.assistant_id}
            id="class-assistant"
            label="Trợ giảng"
            onChange={(event) => updateForm("assistant_id", event.target.value)}
            value={form.assistant_id}
          >
            <option value="">Chọn trợ giảng</option>
            {assistants.map((a) => (
              <option key={a.id} value={a.id}>
                {a.name}
              </option>
            ))}
          </Select>

          <Input
            error={errors.max_students}
            id="class-max-students"
            label="Sĩ số tối đa"
            max="200"
            min="1"
            onChange={(event) => updateForm("max_students", event.target.value)}
            type="number"
            value={form.max_students}
          />

          <div className="sm:col-span-2">
            <label className="block" htmlFor="class-description">
              <span className="mb-1.5 block text-sm font-medium text-brand-text">
                Ghi chú
              </span>
              <textarea
                className="h-24 w-full resize-y rounded-md border border-brand-border bg-brand-white px-3 py-2 text-sm text-brand-text outline-none transition placeholder:text-brand-muted focus:border-brand-teal focus:ring-2 focus:ring-brand-teal-soft"
                id="class-description"
                onChange={(event) => updateForm("description", event.target.value)}
                placeholder="Ghi chú thêm nếu cần"
                value={form.description}
              />
            </label>
          </div>
        </div>

        <div className="mt-6 flex justify-end gap-3">
          <Button
            disabled={saving}
            onClick={() => navigate("/lop-hoc")}
            type="button"
            variant="ghost"
          >
            Huỷ
          </Button>
          <Button disabled={saving} icon={Save} type="submit">
            {saving ? "Đang lưu" : "Lưu lớp học"}
          </Button>
        </div>
      </form>
    </div>
  );
}

export default ClassCreatePage;
