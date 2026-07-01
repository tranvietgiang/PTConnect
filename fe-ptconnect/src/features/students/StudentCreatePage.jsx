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
    student_email: "",
    parent_email: "",
    high_school_name: "",
    grade_level: "",
    classroom_id: "",
    cccd: "",
    date_of_birth: "",
    student_phone: "",
    address: "",
    parent_full_name: "",
    parent_phone: "",
    parent_relation: "",
  });
  const [importForm, setImportForm] = useState({
    grade_level: "",
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

  const studentClasses = useMemo(() => {
    if (!form.grade_level) {
      return [];
    }

    return classes.filter(
      (classroom) => String(classroom.grade_level) === String(form.grade_level),
    );
  }, [classes, form.grade_level]);

  const importClasses = useMemo(() => {
    if (!importForm.grade_level) {
      return [];
    }

    return classes.filter(
      (classroom) =>
        String(classroom.grade_level) === String(importForm.grade_level),
    );
  }, [classes, importForm.grade_level]);

  const updateForm = (field, value) => {
    setForm((current) => {
      const next = { ...current, [field]: value };

      if (field === "grade_level") {
        next.classroom_id = "";
      }

      return next;
    });
    setErrors((current) => ({
      ...current,
      [field]: undefined,
      classroom_id: undefined,
      grade_level: undefined,
    }));
  };

  const updateImportForm = (field, value) => {
    setImportForm((current) => {
      const next = { ...current, [field]: value };

      if (field === "grade_level") {
        next.classroom_id = "";
      }

      return next;
    });
  };

  const validateForm = () => {
    const nextErrors = {};

    if (!form.full_name.trim())
      nextErrors.full_name = "Vui lòng nhập họ tên học sinh.";
    if (!form.student_email.trim())
      nextErrors.student_email = "Vui lòng nhập email học sinh.";
    if (!form.parent_email.trim())
      nextErrors.parent_email = "Vui lòng nhập email phụ huynh.";
    if (!form.high_school_name.trim())
      nextErrors.high_school_name = "Vui lòng nhập tên trường.";
    if (!form.grade_level) nextErrors.grade_level = "Vui lòng chọn khối.";
    if (!form.classroom_id) {
      nextErrors.classroom_id = form.grade_level
        ? "Vui lòng chọn lớp."
        : "Vui lòng chọn khối trước, sau đó chọn lớp.";
    }

    setErrors(nextErrors);

    if (Object.keys(nextErrors).length > 0) {
      toast.error(
        "Thiếu thông tin học sinh",
        "Vui lòng nhập đầy đủ họ tên, email và lớp.",
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
      const payload = { ...form };
      delete payload.grade_level;

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
        `Đã thêm ${response.data.created || 0} học sinh, bỏ qua ${response.data.skipped || 0} dòng.`,
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
                error={errors.student_email}
                id="student-email"
                label="Email học sinh"
                onChange={(event) =>
                  updateForm("student_email", event.target.value)
                }
                placeholder="email@school.edu.vn"
                type="email"
                value={form.student_email}
              />
              <Input
                error={errors.parent_email}
                id="parent-email"
                label="Email phụ huynh"
                onChange={(event) =>
                  updateForm("parent_email", event.target.value)
                }
                placeholder="phuhuynh@school.edu.vn"
                type="email"
                value={form.parent_email}
              />
              <Input
                error={errors.high_school_name}
                id="high-school-name"
                label="Trường THPT"
                onChange={(event) =>
                  updateForm("high_school_name", event.target.value)
                }
                placeholder="Trường THPT ..."
                value={form.high_school_name}
              />
              <Select
                error={errors.grade_level}
                id="student-grade"
                label="Khối"
                onChange={(event) =>
                  updateForm("grade_level", event.target.value)
                }
                value={form.grade_level}
              >
                <option value="">Chọn khối</option>
                <option value="10">Khối 10</option>
                <option value="11">Khối 11</option>
                <option value="12">Khối 12</option>
              </Select>
              <Select
                disabled={!form.grade_level}
                error={errors.classroom_id}
                id="student-class"
                label="Lớp"
                onChange={(event) =>
                  updateForm("classroom_id", event.target.value)
                }
                value={form.classroom_id}
              >
                <option value="">
                  {form.grade_level ? "Chọn lớp" : "Chọn khối trước"}
                </option>
                {studentClasses.map((classroom) => (
                  <option key={classroom.id} value={classroom.id}>
                    {classroom.name}
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
                label="Số điện thoại học sinh"
                onChange={(event) =>
                  updateForm("student_phone", event.target.value)
                }
                placeholder="0901000001"
                value={form.student_phone}
              />
              <Input
                id="student-cccd"
                label="CCCD"
                onChange={(event) => updateForm("cccd", event.target.value)}
                placeholder="Số căn cước công dân"
                value={form.cccd}
              />
              <Input
                id="student-address"
                label="Địa chỉ"
                onChange={(event) => updateForm("address", event.target.value)}
                placeholder="Địa chỉ liên hệ"
                value={form.address}
              />
              <Input
                id="parent-full-name"
                label="Họ tên phụ huynh"
                onChange={(event) =>
                  updateForm("parent_full_name", event.target.value)
                }
                placeholder="Họ tên phụ huynh"
                value={form.parent_full_name}
              />
              <Input
                id="parent-phone"
                label="Số điện thoại phụ huynh"
                onChange={(event) =>
                  updateForm("parent_phone", event.target.value)
                }
                placeholder="0901000002"
                value={form.parent_phone}
              />
              <Select
                id="parent-relation"
                label="Quan hệ với học sinh"
                onChange={(event) =>
                  updateForm("parent_relation", event.target.value)
                }
                value={form.parent_relation}
              >
                <option value="">Chọn quan hệ</option>
                <option value="father">Cha</option>
                <option value="mother">Mẹ</option>
                <option value="guardian">Người giám hộ</option>
                <option value="other">Khác</option>
              </Select>
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
                File hỗ trợ .xlsx hoặc .csv. Các cột nên có: full_name,
                student_email, parent_email, high_school_name, date_of_birth,
                student_phone, address.
              </p>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Select
                id="import-grade"
                label="Khối mặc định"
                onChange={(event) =>
                  updateImportForm("grade_level", event.target.value)
                }
                value={importForm.grade_level}
              >
                <option value="">Dùng cột class_name trong file</option>
                <option value="10">Khối 10</option>
                <option value="11">Khối 11</option>
                <option value="12">Khối 12</option>
              </Select>
              <Select
                disabled={!importForm.grade_level}
                id="import-class"
                label="Lớp mặc định"
                onChange={(event) =>
                  updateImportForm("classroom_id", event.target.value)
                }
                value={importForm.classroom_id}
              >
                <option value="">
                  {importForm.grade_level ? "Chọn lớp" : "Chọn khối trước"}
                </option>
                {importClasses.map((classroom) => (
                  <option key={classroom.id} value={classroom.id}>
                    {classroom.name}
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
