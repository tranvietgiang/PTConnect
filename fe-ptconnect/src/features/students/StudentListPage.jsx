import { useEffect, useMemo, useState } from "react";
import { Plus, Search } from "lucide-react";
import { Link } from "react-router-dom";
import { classApi } from "../../api/classApi";
import { studentApi } from "../../api/studentApi";
import Button from "../../components/common/Button";
import Input from "../../components/common/Input";
import Loading from "../../components/common/Loading";
import Select from "../../components/common/Select";
import Table from "../../components/common/Table";
import { useAuth } from "../../store/useAuth";
import { useToast } from "../../store/useToast";

function StudentListPage() {
  const { user } = useAuth();
  const toast = useToast();
  const [students, setStudents] = useState([]);
  const [classes, setClasses] = useState([]);
  const [keyword, setKeyword] = useState("");
  const [selectedClass, setSelectedClass] = useState("");
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;

    async function loadData() {
      try {
        const [studentResponse, classResponse] = await Promise.all([
          studentApi.getAll(),
          classApi.getAll(),
        ]);

        if (mounted) {
          setStudents(studentResponse.data || []);
          setClasses(classResponse.data || []);
        }
      } catch (error) {
        toast.error(
          "Không tải được danh sách học sinh",
          error.message || "Vui lòng thử lại sau.",
        );
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

  const filteredStudents = useMemo(() => {
    const normalizedKeyword = keyword.trim().toLowerCase();

    return students.filter((student) => {
      const matchesKeyword =
        !normalizedKeyword ||
        student.full_name?.toLowerCase().includes(normalizedKeyword) ||
        student.student_code?.toLowerCase().includes(normalizedKeyword);
      const matchesClass =
        !selectedClass || String(student.classroom_id) === selectedClass;

      return matchesKeyword && matchesClass;
    });
  }, [keyword, selectedClass, students]);

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand-text">Học sinh</h1>
          <p className="mt-1 text-sm text-brand-muted">
            Quản lý hồ sơ học sinh và phân lớp.
          </p>
        </div>
        {user?.role === "admin" ? (
          <Button as={Link} icon={Plus} to="/hoc-sinh/them">
            Thêm học sinh
          </Button>
        ) : null}
      </div>

      <div className="grid gap-3 ">
        <Input
          id="student-search"
          onChange={(event) => setKeyword(event.target.value)}
          placeholder="Tìm theo tên hoặc mã học sinh"
          value={keyword}
        />

        <div className="w-full md:w-40">
          <Select
            id="student-class-filter"
            label="Lọc theo lớp"
            onChange={(event) => setSelectedClass(event.target.value)}
            value={selectedClass}
          >
            <option value="">Tất cả lớp</option>
            {classes.map((classroom) => (
              <option key={classroom.id} value={classroom.id}>
                {classroom.name}
              </option>
            ))}
          </Select>
        </div>
      </div>

      {loading ? (
        <Loading label="Đang tải danh sách học sinh" />
      ) : (
        <Table
          columns={[
            { header: "Mã", key: "student_code" },
            {
              header: "Họ tên",
              key: "full_name",
              render: (row) => (
                <div className="flex items-center gap-3">
                  <div className="grid size-10 shrink-0 place-items-center overflow-hidden rounded-md bg-brand-bg text-sm font-semibold text-brand-teal-dark">
                    {row.avatar_url ? (
                      <img alt={row.full_name} className="size-full object-cover" src={row.avatar_url} />
                    ) : (
                      row.full_name?.charAt(0) || "H"
                    )}
                  </div>
                  <Link
                    className="font-semibold text-brand-teal-dark"
                    to={`/hoc-sinh/${row.id}`}
                  >
                    {row.full_name}
                  </Link>
                </div>
              ),
            },
            { header: "Lớp", key: "class_name" },
            { header: "SĐT", key: "phone", render: (row) => row.phone || "-" },
            {
              header: "Trạng thái",
              key: "status",
              render: (row) =>
                row.status === "studying" ? "Đang học" : row.status,
            },
            {
              header: "",
              key: "action",
              render: () => (
                <Search
                  aria-hidden="true"
                  className="size-4 text-brand-muted"
                />
              ),
            },
          ]}
          data={filteredStudents}
          emptyText="Chưa có học sinh"
        />
      )}
    </div>
  );
}

export default StudentListPage;
