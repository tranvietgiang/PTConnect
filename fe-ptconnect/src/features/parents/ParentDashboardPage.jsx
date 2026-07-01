import { Save, User } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { attendanceApi } from "../../api/attendanceApi";
import { scoreApi } from "../../api/scoreApi";
import { studentApi } from "../../api/studentApi";
import Button from "../../components/common/Button";
import Input from "../../components/common/Input";
import Loading from "../../components/common/Loading";
import Select from "../../components/common/Select";
import { useToast } from "../../store/useToast";
import { formatDate } from "../../utils/formatDate";

function averageScore(rows) {
  const values = rows
    .map((row) => row.score)
    .filter((score) => score !== null && score !== undefined && score !== "")
    .map((score) => Number(score))
    .filter((value) => !Number.isNaN(value));

  if (!values.length) return "Chưa có";

  const average = values.reduce((sum, value) => sum + value, 0) / values.length;

  return average.toLocaleString("vi-VN", {
    maximumFractionDigits: 2,
    minimumFractionDigits: 0,
  });
}

function statusLabel(status) {
  if (status === "studying") return "Đang học";
  if (status === "paused") return "Tạm nghỉ";
  if (status === "left") return "Đã nghỉ";

  return status || "-";
}

function attendanceStatusBadge(status) {
  if (status === "present")
    return "bg-green-100 text-green-700";
  if (status === "late")
    return "bg-amber-100 text-amber-700";
  if (status === "absent")
    return "bg-red-100 text-red-700";
  return "bg-gray-100 text-gray-500";
}

function attendanceStatusText(status) {
  if (status === "present") return "Có mặt";
  if (status === "late") return "Đi muộn";
  if (status === "absent") return "Vắng";
  return "-";
}

const tabs = [
  { key: "info", label: "Thông tin" },
  { key: "attendance", label: "Điểm danh" },
];

function buildProfileForm(student) {
  return {
    full_name: student?.full_name || "",
    gender: student?.gender || "",
    phone: student?.phone || "",
    date_of_birth: student?.date_of_birth || "",
    address: student?.address || "",
  };
}

function ParentDashboardPage() {
  const toast = useToast();
  const [students, setStudents] = useState([]);
  const [scores, setScores] = useState([]);
  const [attendanceRecords, setAttendanceRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedAttendanceStudentId, setSelectedAttendanceStudentId] = useState(null);
  const [profileForms, setProfileForms] = useState({});
  const [profileSavingId, setProfileSavingId] = useState(null);

  useEffect(() => {
    let mounted = true;

    async function loadData() {
      try {
        const [studentResponse, scoreResponse, attendanceResponse] = await Promise.all([
          studentApi.getAll(),
          scoreApi.getAll(),
          attendanceApi.getParentHistory(),
        ]);

        if (!mounted) return;

        const loadedStudents = studentResponse.data || [];

        setStudents(loadedStudents);
        setProfileForms(
          loadedStudents.reduce((forms, student) => {
            forms[String(student.id)] = buildProfileForm(student);
            return forms;
          }, {}),
        );
        setScores(scoreResponse.data || []);
        setAttendanceRecords(attendanceResponse.data || []);
      } catch (error) {
        if (mounted) {
          toast.error(
            "Không tải được thông tin",
            error.message || "Vui lòng thử lại sau.",
          );
        }
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    }

    loadData();

    return () => {
      mounted = false;
    };
  }, []);

  const scoresByStudent = useMemo(() => {
    return scores.reduce((groups, score) => {
      const studentId = String(score.student_id);

      groups[studentId] = groups[studentId] || [];
      groups[studentId].push(score);

      return groups;
    }, {});
  }, [scores]);

  const attendanceByStudent = useMemo(() => {
    return attendanceRecords.reduce((groups, record) => {
      const studentId = String(record.student_id);

      groups[studentId] = groups[studentId] || [];
      groups[studentId].push(record);

      return groups;
    }, {});
  }, [attendanceRecords]);

  const attendanceSummary = useMemo(() => {
    return students.reduce((summary, student) => {
      const records = attendanceByStudent[String(student.id)] || [];
      const present = records.filter((r) => r.status === "present").length;
      const late = records.filter((r) => r.status === "late").length;
      const absent = records.filter((r) => r.status === "absent").length;

      summary[String(student.id)] = { total: records.length, present, late, absent };

      return summary;
    }, {});
  }, [students, attendanceByStudent]);

  const updateProfileForm = (studentId, field, value) => {
    setProfileForms((current) => ({
      ...current,
      [String(studentId)]: {
        ...(current[String(studentId)] || {}),
        [field]: value,
      },
    }));
  };

  const handleProfileSubmit = async (event, studentId) => {
    event.preventDefault();

    const profileForm = profileForms[String(studentId)] || {};

    if (!profileForm.full_name?.trim()) {
      toast.error("Thiếu họ tên", "Vui lòng nhập họ tên học sinh.");
      return;
    }

    setProfileSavingId(studentId);

    try {
      const response = await studentApi.update(studentId, {
        full_name: profileForm.full_name.trim(),
        gender: profileForm.gender || null,
        phone: profileForm.phone?.trim() || null,
        date_of_birth: profileForm.date_of_birth || null,
        address: profileForm.address?.trim() || null,
      });
      const updatedStudent = response.data;

      setStudents((current) =>
        current.map((student) =>
          student.id === studentId ? updatedStudent : student,
        ),
      );
      setProfileForms((current) => ({
        ...current,
        [String(studentId)]: buildProfileForm(updatedStudent),
      }));
      toast.success("Đã cập nhật hồ sơ", "Thông tin học sinh đã được lưu.");
    } catch (error) {
      toast.error(
        "Không cập nhật được hồ sơ",
        error.message || "Vui lòng kiểm tra lại thông tin.",
      );
    } finally {
      setProfileSavingId(null);
    }
  };

  if (loading) {
    return <Loading label="Đang tải thông tin phụ huynh" />;
  }

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-2xl font-bold text-brand-text">
          Thông tin học sinh
        </h1>
        <p className="mt-1 text-sm text-brand-muted">
          Xem hồ sơ học sinh, bài đã nộp, điểm và nhận xét từ giáo viên.
        </p>
      </div>

      {!students.length ? (
        <div className="rounded-lg border border-brand-border bg-brand-white px-4 py-8 text-center text-sm text-brand-muted">
          Chưa có học sinh được liên kết với tài khoản phụ huynh này.
        </div>
      ) : (
        students.map((student) => {
          const studentScores = scoresByStudent[String(student.id)] || [];
          const studentAttendance = attendanceByStudent[String(student.id)] || [];
          const summary = attendanceSummary[String(student.id)] || { total: 0, present: 0, late: 0, absent: 0 };
          const profileForm = profileForms[String(student.id)] || buildProfileForm(student);

          return (
            <section className="space-y-4" key={student.id}>
              <div className="rounded-lg border border-brand-border bg-brand-white p-5 shadow-sm">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                  <div className="grid size-20 shrink-0 place-items-center overflow-hidden rounded-md bg-brand-bg text-2xl font-bold text-brand-teal-dark">
                    {student.avatar_url ? (
                      <img
                        alt={student.full_name}
                        className="size-full object-cover"
                        src={student.avatar_url}
                      />
                    ) : (
                      <User className="size-8 text-brand-teal-dark" />
                    )}
                  </div>
                  <div className="min-w-0">
                    <p className="text-sm font-semibold uppercase tracking-wide text-brand-teal-dark">
                      {student.student_code}
                    </p>
                    <h2 className="mt-1 text-xl font-bold text-brand-text">
                      {student.full_name}
                    </h2>
                    <div className="mt-3 grid gap-1 text-sm text-brand-muted sm:grid-cols-2">
                      <p>Lớp: {student.class_name || "-"}</p>
                      <p>SĐT học sinh: {student.phone || "-"}</p>
                      <p>
                        Ngày sinh:{" "}
                        {student.date_of_birth
                          ? formatDate(student.date_of_birth)
                          : "-"}
                      </p>
                      <p>Trạng thái: {statusLabel(student.status)}</p>
                      <p>Số bài đã chấm: {studentScores.length}</p>
                      <p>Điểm trung bình: {averageScore(studentScores)}</p>
                      <p className="sm:col-span-2">
                        Địa chỉ: {student.address || "-"}
                      </p>
                    </div>
                  </div>
                </div>

                <form
                  className="mt-5 rounded-md border border-brand-border bg-brand-bg p-4"
                  onSubmit={(event) => handleProfileSubmit(event, student.id)}
                >
                  <h3 className="text-base font-semibold text-brand-text">
                    Bổ sung thông tin học sinh
                  </h3>
                  <div className="mt-3 grid gap-3 sm:grid-cols-2">
                    <Input
                      id={`student-name-${student.id}`}
                      label="Họ và tên"
                      onChange={(event) =>
                        updateProfileForm(student.id, "full_name", event.target.value)
                      }
                      value={profileForm.full_name}
                    />
                    <Select
                      id={`student-gender-${student.id}`}
                      label="Giới tính"
                      onChange={(event) =>
                        updateProfileForm(student.id, "gender", event.target.value)
                      }
                      value={profileForm.gender}
                    >
                      <option value="">Không chọn</option>
                      <option value="male">Nam</option>
                      <option value="female">Nữ</option>
                      <option value="other">Khác</option>
                    </Select>
                    <Input
                      id={`student-dob-${student.id}`}
                      label="Ngày sinh"
                      onChange={(event) =>
                        updateProfileForm(student.id, "date_of_birth", event.target.value)
                      }
                      type="date"
                      value={profileForm.date_of_birth}
                    />
                    <Input
                      id={`student-phone-${student.id}`}
                      label="Số điện thoại"
                      onChange={(event) =>
                        updateProfileForm(student.id, "phone", event.target.value)
                      }
                      placeholder="0901000001"
                      value={profileForm.phone}
                    />
                    <div className="sm:col-span-2">
                      <Input
                        id={`student-address-${student.id}`}
                        label="Địa chỉ"
                        onChange={(event) =>
                          updateProfileForm(student.id, "address", event.target.value)
                        }
                        placeholder="Địa chỉ liên hệ"
                        value={profileForm.address}
                      />
                    </div>
                  </div>
                  <div className="mt-4 flex justify-end">
                    <Button
                      disabled={profileSavingId === student.id}
                      icon={Save}
                      type="submit"
                    >
                      {profileSavingId === student.id ? "Đang lưu" : "Lưu thông tin"}
                    </Button>
                  </div>
                </form>

                <div className="mt-4 border-t border-brand-border pt-4">
                  <div className="flex gap-1 rounded-lg bg-brand-bg p-1">
                    {tabs.map((tab) => (
                      <button
                        className={`rounded-md px-4 py-1.5 text-sm font-medium transition-colors ${
                          (tab.key === "attendance" ? selectedAttendanceStudentId === student.id : selectedAttendanceStudentId !== student.id)
                            ? "bg-brand-white text-brand-teal-dark shadow-sm"
                            : "text-brand-muted hover:text-brand-text"
                        }`}
                        key={tab.key}
                        onClick={() => {
                          setSelectedAttendanceStudentId(
                            tab.key === "attendance" ? student.id : null,
                          );
                        }}
                        type="button"
                      >
                        {tab.label}
                      </button>
                    ))}
                  </div>

                  {selectedAttendanceStudentId === student.id && (
                    <div className="mt-3 space-y-2">
                      {summary.total > 0 && (
                        <div className="flex gap-3 text-sm">
                          <span className="font-medium text-brand-text">
                            Tổng: {summary.total} buổi
                          </span>
                          <span className="text-green-600">
                            Có mặt: {summary.present}
                          </span>
                          <span className="text-amber-600">
                            Đi muộn: {summary.late}
                          </span>
                          <span className="text-red-600">
                            Vắng: {summary.absent}
                          </span>
                        </div>
                      )}

                      {studentAttendance.length ? (
                        <div className="overflow-x-auto">
                          <table className="w-full text-left text-sm">
                            <thead>
                              <tr className="border-b border-brand-border text-xs font-bold uppercase tracking-wide text-brand-muted">
                                <th className="px-3 py-2">Ngày</th>
                                <th className="px-3 py-2">Buổi học</th>
                                <th className="px-3 py-2">Lớp</th>
                                <th className="px-3 py-2">Trạng thái</th>
                              </tr>
                            </thead>
                            <tbody>
                              {studentAttendance.map((record) => (
                                <tr
                                  className="border-b border-brand-border last:border-0"
                                  key={record.id}
                                >
                                  <td className="px-3 py-2 text-brand-text">
                                    {record.attendance_date}
                                  </td>
                                  <td className="px-3 py-2 text-brand-text">
                                    {record.session_name || "-"}
                                  </td>
                                  <td className="px-3 py-2 text-brand-muted">
                                    {record.class_name || "-"}
                                  </td>
                                  <td className="px-3 py-2">
                                    <span
                                      className={`inline-flex h-6 items-center rounded-md px-2 text-xs font-semibold ${attendanceStatusBadge(record.status)}`}
                                    >
                                      {attendanceStatusText(record.status)}
                                      {record.status === "late" && record.late_minutes
                                        ? (() => {
                                            const m = Number(record.late_minutes)
                                            const h = Math.floor(m / 60)
                                            const r = m % 60
                                            let t = `${m} phút`
                                            if (h > 0) t += ` (${h} giờ${r > 0 ? ` ${r} phút` : ''})`
                                            return ` (${t})`
                                          })()
                                        : ""}
                                    </span>
                                  </td>
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>
                      ) : (
                        <p className="text-sm text-brand-muted">
                          Chưa có dữ liệu điểm danh.
                        </p>
                      )}
                    </div>
                  )}
                </div>
              </div>
            </section>
          );
        })
      )}
    </div>
  );
}

export default ParentDashboardPage;
