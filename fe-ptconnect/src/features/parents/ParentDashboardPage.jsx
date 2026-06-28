import { User } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { scoreApi } from "../../api/scoreApi";
import { studentApi } from "../../api/studentApi";
import Loading from "../../components/common/Loading";
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

function ParentDashboardPage() {
  const toast = useToast();
  const [students, setStudents] = useState([]);
  const [scores, setScores] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;

    async function loadData() {
      try {
        const [studentResponse, scoreResponse] = await Promise.all([
          studentApi.getAll(),
          scoreApi.getAll(),
        ]);

        if (!mounted) return;

        setStudents(studentResponse.data || []);
        setScores(scoreResponse.data || []);
      } catch (error) {
        if (mounted) {
          toast.error(
            "Không tải được thông tin phụ huynh",
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

      {students.length ? (
        students.map((student) => {
          const studentScores = scoresByStudent[String(student.id)] || [];

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
              </div>
            </section>
          );
        })
      ) : (
        <div className="rounded-lg border border-brand-border bg-brand-white px-4 py-8 text-center text-sm text-brand-muted">
          Chưa có học sinh được liên kết với tài khoản phụ huynh này.
        </div>
      )}
    </div>
  );
}

export default ParentDashboardPage;
