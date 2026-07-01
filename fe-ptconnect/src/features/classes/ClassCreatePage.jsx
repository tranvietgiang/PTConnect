import { useState } from "react";
import { Save } from "lucide-react";
import { Navigate, useNavigate } from "react-router-dom";
import { classApi } from "../../api/classApi";
import Button from "../../components/common/Button";
import Input from "../../components/common/Input";
import Select from "../../components/common/Select";
import { useAuth } from "../../store/useAuth";
import { useToast } from "../../store/useToast";

function ClassCreatePage() {
  const { user } = useAuth();
  const toast = useToast();
  const navigate = useNavigate();
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});
  const [form, setForm] = useState({
    name: "",
    grade_level: "",
    start_date: "",
    end_date: "",
    total_lessons: "30",
    description: "",
  });

  if (!["system_admin", "school_admin"].includes(user?.role)) {
    return <Navigate replace to="/khong-co-quyen" />;
  }

  const updateForm = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: undefined }));
  };

  const validateForm = () => {
    const nextErrors = {};
    const totalLessons = Number(form.total_lessons);

    if (!form.name.trim()) nextErrors.name = "Vui lòng nhập tên lớp.";
    if (!form.grade_level) nextErrors.grade_level = "Vui lòng chọn khối lớp.";
    if (!form.start_date) nextErrors.start_date = "Vui lòng chọn ngày bắt đầu.";
    if (!form.end_date) nextErrors.end_date = "Vui lòng chọn ngày kết thúc.";
    if (form.start_date && form.end_date && form.end_date < form.start_date) {
      nextErrors.end_date = "Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.";
    }
    if (
      !Number.isInteger(totalLessons) ||
      totalLessons < 1 ||
      totalLessons > 100
    ) {
      nextErrors.total_lessons = "Tổng số buổi phải từ 1 đến 100.";
    }

    setErrors(nextErrors);

    if (Object.keys(nextErrors).length > 0) {
      toast.error(
        "Thiếu thông tin lớp học",
        "Vui lòng kiểm tra tên lớp, khối, thời gian và tổng số buổi.",
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
        start_date: form.start_date,
        end_date: form.end_date,
        total_lessons: Number(form.total_lessons),
        description: form.description.trim() || null,
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
    <div className="mx-auto max-w-2xl space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Thêm lớp học</h1>
        <p className="mt-1 text-sm text-brand-muted">
          Tạo lớp mới cho khối 10, 11 hoặc 12.
        </p>
      </div>

      <form
        className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm"
        onSubmit={handleSubmit}
      >
        <div className="grid gap-4 sm:grid-cols-2">
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
          </Select>{" "}
          <Input
            error={errors.name}
            id="class-name"
            label="Tên lớp"
            onChange={(event) => updateForm("name", event.target.value)}
            placeholder="10A1"
            value={form.name}
          />
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
            label="Ngày kết thúc"
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
            onChange={(event) =>
              updateForm("total_lessons", event.target.value)
            }
            type="number"
            value={form.total_lessons}
          />
          <Input
            className="sm:col-span-2"
            id="class-description"
            label="Ghi chú"
            onChange={(event) => updateForm("description", event.target.value)}
            placeholder="Ghi chú thêm nếu cần"
            value={form.description}
          />
        </div>

        <div className="mt-5 flex justify-end">
          <Button disabled={saving} icon={Save} type="submit">
            {saving ? "Đang lưu" : "Lưu lớp học"}
          </Button>
        </div>
      </form>
    </div>
  );
}

export default ClassCreatePage;
