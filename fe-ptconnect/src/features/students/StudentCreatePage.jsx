import { useEffect, useState } from "react";
import { Save, Upload } from "lucide-react";
import { useNavigate } from "react-router-dom";
import { classApi } from "../../api/classApi";
import { studentApi } from "../../api/studentApi";
import Button from "../../components/common/Button";
import Input from "../../components/common/Input";
import Loading from "../../components/common/Loading";
import Select from "../../components/common/Select";
import { useToast } from "../../store/useToast";

function StudentCreatePage() {
  const navigate = useNavigate();
  const toast = useToast();
  const [classes, setClasses] = useState([]);
  const [loadingClasses, setLoadingClasses] = useState(true);
  const [saving, setSaving] = useState(false);
  const [importing, setImporting] = useState(false);
  const [errors, setErrors] = useState({});
  const [importResult, setImportResult] = useState(null);
  const [form, setForm] = useState({
    full_name: "",
    student_code: "",
    classroom_id: "",
    phone: "",
    date_of_birth: "",
    avatar: null,
    address: "",
  });
  const [importForm, setImportForm] = useState({
    classroom_id: "",
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

  const updateForm = (field, value) => {
    setForm((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: undefined }));
  };

  const validateForm = () => {
    const nextErrors = {};

    if (!form.full_name.trim())
      nextErrors.full_name = "Vui lòng nhập họ tên học sinh.";
    if (!form.student_code.trim())
      nextErrors.student_code = "Vui lòng nhập mã học sinh.";
    if (!form.classroom_id) nextErrors.classroom_id = "Vui lòng chọn lớp.";

    setErrors(nextErrors);

    if (Object.keys(nextErrors).length > 0) {
      toast.error(
        "Thiếu thông tin học sinh",
        "Vui lòng nhập đầy đủ họ tên, mã học sinh và lớp.",
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
      const payload = new FormData();
      payload.append("full_name", form.full_name.trim());
      payload.append("student_code", form.student_code.trim());
      payload.append("classroom_id", Number(form.classroom_id));
      if (form.phone.trim()) payload.append("phone", form.phone.trim());
      if (form.date_of_birth)
        payload.append("date_of_birth", form.date_of_birth);
      if (form.avatar) payload.append("avatar", form.avatar);
      if (form.address.trim()) payload.append("address", form.address.trim());

      await studentApi.create(payload);
      toast.success("Đã lưu học sinh", "Hồ sơ học sinh mới đã được tạo.");
      navigate("/hoc-sinh", { replace: true });
    } catch (error) {
      toast.error(
        "Không lưu được học sinh",
        error.message || "Vui lòng kiểm tra lại thông tin.",
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
      if (importForm.classroom_id) {
        payload.append("classroom_id", importForm.classroom_id);
      }

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
          Tạo hồ sơ học sinh mới, phân lớp hoặc import danh sách từ Excel.
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
                id="student-name"
                label="Họ và tên"
                onChange={(event) =>
                  updateForm("full_name", event.target.value)
                }
                placeholder="Nhập họ tên học sinh"
                value={form.full_name}
              />
              <Input
                error={errors.student_code}
                id="student-code"
                label="Mã học sinh"
                onChange={(event) =>
                  updateForm("student_code", event.target.value)
                }
                placeholder="HS100001"
                value={form.student_code}
              />
              <Select
                error={errors.classroom_id}
                id="student-class"
                label="Lớp"
                onChange={(event) =>
                  updateForm("classroom_id", event.target.value)
                }
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
                onChange={(event) =>
                  updateForm("date_of_birth", event.target.value)
                }
                type="date"
                value={form.date_of_birth}
              />
              <Input
                id="student-phone"
                label="Số điện thoại"
                onChange={(event) => updateForm("phone", event.target.value)}
                placeholder="0901000001"
                value={form.phone}
              />
              <Input
                accept="image/png,image/jpeg,image/webp"
                id="student-avatar"
                label="Chọn ảnh"
                onChange={(event) =>
                  updateForm("avatar", event.target.files?.[0] || null)
                }
                type="file"
              />
              <Input
                id="student-address"
                label="Địa chỉ"
                onChange={(event) => updateForm("address", event.target.value)}
                placeholder="Địa chỉ liên hệ"
                value={form.address}
              />
            </div>
            <div className="mt-5 flex justify-end">
              <Button
                disabled={saving || classes.length === 0}
                icon={Save}
                type="submit"
              >
                {saving ? "Đang lưu" : "Lưu học sinh"}
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
                File hỗ trợ .xlsx hoặc .csv. Các cột nên có: student_code,
                full_name, class_name, date_of_birth, student_phone, avatar,
                address.
              </p>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Select
                id="import-class"
                label="Lớp mặc định"
                onChange={(event) =>
                  setImportForm((current) => ({
                    ...current,
                    classroom_id: event.target.value,
                  }))
                }
                value={importForm.classroom_id}
              >
                <option value="">Dùng cột class_name trong file</option>
                {classes.map((classroom) => (
                  <option key={classroom.id} value={classroom.id}>
                    {classroom.name} - Khối {classroom.grade_level}
                  </option>
                ))}
              </Select>
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
                  Đã thêm <strong>{importResult.created}</strong> học sinh, bỏ
                  qua <strong>{importResult.skipped}</strong> dòng.
                </p>
                {importResult.errors?.length ? (
                  <ul className="mt-2 space-y-1 text-brand-muted">
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
