import { useEffect, useMemo, useState } from "react";
import { Save, Upload } from "lucide-react";
import { useNavigate } from "react-router-dom";
import { classApi } from "../../api/classApi";
import { studentApi } from "../../api/studentApi";
import Button from "../../components/common/Button";
import Input from "../../components/common/Input";
import Loading from "../../components/common/Loading";
import Select from "../../components/common/Select";
import { useToast } from "../../store/useToast";

const defaultForm = {
  full_name: "",
  student_email: "",
  parent_email: "",
  high_school_name: "",
  classroom_id: "",
  cccd: "",
  date_of_birth: "",
  student_phone: "",
  address: "",
  parent_phone: "",
  parent_full_name: "",
  parent_relation: "",
};

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function normalizeValidationErrors(apiErrors = {}) {
  return Object.entries(apiErrors).reduce((result, [field, messages]) => {
    result[field] = Array.isArray(messages) ? messages[0] : messages;
    return result;
  }, {});
}

function StudentCreatePage() {
  const navigate = useNavigate();
  const toast = useToast();
  const [classes, setClasses] = useState([]);
  const [loadingClasses, setLoadingClasses] = useState(true);
  const [saving, setSaving] = useState(false);
  const [importing, setImporting] = useState(false);
  const [errors, setErrors] = useState({});
  const [importResult, setImportResult] = useState(null);
  const [form, setForm] = useState(defaultForm);
  const [importForm, setImportForm] = useState({
    file: null,
  });

  useEffect(() => {
    let mounted = true;

    async function loadClasses() {
      try {
        const response = await classApi.getAll();

        if (mounted) {
          setClasses(response.data || []);
        }
      } catch (error) {
        toast.error(
          "Không tải được danh sách lớp",
          error.message || "Vui lòng thử lại sau.",
        );
      } finally {
        if (mounted) {
          setLoadingClasses(false);
        }
      }
    }

    loadClasses();

    return () => {
      mounted = false;
    };
  }, []);

  const classroomOptions = useMemo(
    () =>
      classes
        .filter((classroom) => classroom.is_active !== false)
        .sort((a, b) => {
          const gradeDiff = Number(a.grade_level || 0) - Number(b.grade_level || 0);
          return gradeDiff || String(a.name).localeCompare(String(b.name));
        }),
    [classes],
  );

  const updateForm = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: undefined }));
  };

  const validateForm = () => {
    const nextErrors = {};

    if (!form.full_name.trim()) {
      nextErrors.full_name = "Vui lòng nhập họ tên học sinh.";
    }

    if (!form.student_email.trim()) {
      nextErrors.student_email = "Vui lòng nhập email đăng nhập của học sinh.";
    } else if (!emailPattern.test(form.student_email.trim())) {
      nextErrors.student_email = "Email học sinh không hợp lệ.";
    }

    if (!form.parent_email.trim()) {
      nextErrors.parent_email = "Vui lòng nhập email phụ huynh.";
    } else if (!emailPattern.test(form.parent_email.trim())) {
      nextErrors.parent_email = "Email phụ huynh không hợp lệ.";
    }

    if (!form.high_school_name.trim()) {
      nextErrors.high_school_name = "Vui lòng nhập tên trường THPT.";
    }

    if (!form.classroom_id) {
      nextErrors.classroom_id = "Vui lòng chọn lớp.";
    }

    setErrors(nextErrors);

    if (Object.keys(nextErrors).length > 0) {
      toast.error(
        "Thiếu thông tin học sinh",
        "Vui lòng nhập đầy đủ các trường bắt buộc trước khi tạo học sinh.",
      );
      return false;
    }

    return true;
  };

  const buildPayload = () =>
    Object.entries(form).reduce((payload, [key, value]) => {
      if (key === "classroom_id") {
        payload[key] = Number(value);
        return payload;
      }

      const normalized = String(value || "").trim();
      payload[key] = normalized || null;
      return payload;
    }, {});

  const handleSubmit = async (event) => {
    event.preventDefault();

    if (!validateForm()) return;

    setSaving(true);

    try {
      const response = await studentApi.create(buildPayload());
      const student = response.data;

      toast.success(
        "Đã tạo học sinh",
        `Mã học sinh: ${student.student_code}. Email đăng nhập: ${student.account?.email || student.student_email}.`,
      );
      navigate("/hoc-sinh", { replace: true });
    } catch (error) {
      if (error.errors) {
        setErrors(normalizeValidationErrors(error.errors));
      }

      toast.error(
        "Không tạo được học sinh",
        error.message || "Vui lòng kiểm tra lại thông tin học sinh.",
      );
    } finally {
      setSaving(false);
    }
  };

  const handleImport = async (event) => {
    event.preventDefault();

    if (!importForm.file) {
      toast.error(
        "Chưa chọn file",
        "Vui lòng chọn file Excel hoặc CSV để import.",
      );
      return;
    }

    setImporting(true);
    setImportResult(null);

    try {
      const payload = new FormData();
      payload.append("file", importForm.file);

      const response = await studentApi.importExcel(payload);
      setImportResult(response.data);
      toast.success(
        "Import hoàn tất",
        `Đã thêm ${response.data.created} học sinh, bỏ qua ${response.data.skipped} dòng.`,
      );
    } catch (error) {
      toast.error(
        "Import thất bại",
        error.message || "Vui lòng kiểm tra lại file import.",
      );
    } finally {
      setImporting(false);
    }
  };

  return (
    <div className="mx-auto max-w-4xl space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">Thêm học sinh</h1>
        <p className="mt-1 text-sm text-brand-muted">
          Tạo tài khoản học sinh bằng email và liên kết vào lớp đang học.
        </p>
      </div>

      {loadingClasses ? (
        <Loading label="Đang tải danh sách lớp" />
      ) : (
        <>
          <form
            className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm"
            onSubmit={handleSubmit}
          >
            <div className="grid gap-4 sm:grid-cols-2">
              <Input
                error={errors.full_name}
                id="student-full-name"
                label="Họ và tên"
                onChange={(event) => updateForm("full_name", event.target.value)}
                placeholder="Nguyễn Văn A"
                value={form.full_name}
              />
              <Select
                error={errors.classroom_id}
                id="student-class"
                label="Lớp"
                onChange={(event) => updateForm("classroom_id", event.target.value)}
                value={form.classroom_id}
              >
                <option value="">Chọn lớp</option>
                {classroomOptions.map((classroom) => (
                  <option key={classroom.id} value={classroom.id}>
                    {classroom.name} - Khối {classroom.grade_level || "?"}
                  </option>
                ))}
              </Select>
              <Input
                autoComplete="email"
                error={errors.student_email}
                id="student-email"
                label="Email học sinh"
                onChange={(event) => updateForm("student_email", event.target.value)}
                placeholder="hoc.sinh@example.com"
                type="email"
                value={form.student_email}
              />
              <Input
                autoComplete="email"
                error={errors.parent_email}
                id="parent-email"
                label="Email phụ huynh"
                onChange={(event) => updateForm("parent_email", event.target.value)}
                placeholder="phu.huynh@example.com"
                type="email"
                value={form.parent_email}
              />
              <Input
                error={errors.high_school_name}
                id="student-school"
                label="Trường THPT"
                onChange={(event) =>
                  updateForm("high_school_name", event.target.value)
                }
                placeholder="THPT Nguyễn Trãi"
                value={form.high_school_name}
              />
              <Input
                error={errors.cccd}
                id="student-cccd"
                label="CCCD"
                onChange={(event) => updateForm("cccd", event.target.value)}
                placeholder="Không bắt buộc"
                value={form.cccd}
              />
              <Input
                error={errors.date_of_birth}
                id="student-dob"
                label="Ngày sinh"
                onChange={(event) => updateForm("date_of_birth", event.target.value)}
                type="date"
                value={form.date_of_birth}
              />
              <Input
                error={errors.student_phone}
                id="student-phone"
                label="SĐT học sinh"
                onChange={(event) => updateForm("student_phone", event.target.value)}
                placeholder="Không bắt buộc"
                value={form.student_phone}
              />
              <Input
                error={errors.parent_phone}
                id="parent-phone"
                label="SĐT phụ huynh"
                onChange={(event) => updateForm("parent_phone", event.target.value)}
                placeholder="Không bắt buộc"
                value={form.parent_phone}
              />
              <Input
                error={errors.parent_full_name}
                id="parent-full-name"
                label="Họ tên phụ huynh"
                onChange={(event) =>
                  updateForm("parent_full_name", event.target.value)
                }
                placeholder="Không bắt buộc"
                value={form.parent_full_name}
              />
              <Input
                error={errors.parent_relation}
                id="parent-relation"
                label="Quan hệ"
                onChange={(event) =>
                  updateForm("parent_relation", event.target.value)
                }
                placeholder="Cha, mẹ, người giám hộ..."
                value={form.parent_relation}
              />
              <Input
                className="sm:col-span-2"
                error={errors.address}
                id="student-address"
                label="Địa chỉ"
                onChange={(event) => updateForm("address", event.target.value)}
                placeholder="Không bắt buộc"
                value={form.address}
              />
            </div>

            <div className="mt-5 flex justify-end">
              <Button
                disabled={saving || classroomOptions.length === 0}
                icon={Save}
                type="submit"
              >
                {saving ? "Đang tạo" : "Tạo học sinh"}
              </Button>
            </div>
          </form>

          <form
            className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm"
            onSubmit={handleImport}
          >
            <div className="mb-4">
              <h2 className="text-lg font-semibold text-brand-text">
                Import danh sách học sinh bằng Excel
              </h2>
              <p className="mt-1 text-sm text-brand-muted">
                Phần import giữ nguyên để xử lý ở task riêng khi cần cập nhật theo cấu trúc mới.
              </p>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Input
                accept=".xlsx,.csv"
                id="student-import-file"
                label="File Excel/CSV"
                onChange={(event) =>
                  setImportForm((current) => ({
                    ...current,
                    file: event.target.files?.[0] || null,
                  }))
                }
                type="file"
              />
            </div>
            <div className="mt-5 flex justify-end">
              <Button
                disabled={importing}
                icon={Upload}
                type="submit"
                variant="secondary"
              >
                {importing ? "Đang import" : "Import danh sách"}
              </Button>
            </div>

            {importResult ? (
              <div className="mt-4 rounded-md border border-brand-border bg-brand-bg p-4 text-sm text-brand-text">
                <p>
                  Đã thêm <strong>{importResult.created}</strong> học sinh, bỏ qua{" "}
                  <strong>{importResult.skipped}</strong> dòng.
                </p>
                {importResult.errors?.length ? (
                  <ul className="mt-2 space-y-1 text-brand-red">
                    {importResult.errors.map((error) => (
                      <li key={error}>{error}</li>
                    ))}
                  </ul>
                ) : null}
              </div>
            ) : null}
          </form>
        </>
      )}
    </div>
  );
}

export default StudentCreatePage;
