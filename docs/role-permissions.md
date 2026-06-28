# PTConnect Role Permissions

## Parent

- Chỉ xem dữ liệu của con mình.
- Được xem hồ sơ học sinh, lớp, lịch sử điểm danh, điểm kiểm tra, bài tập, trạng thái nộp bài và thông báo liên quan.
- Được nộp bài tập cho con mình.
- Không được xem học sinh khác.
- Không được tạo, sửa, xoá dữ liệu quản trị.

## Teacher

- Chỉ xem và thao tác trên các lớp được phân công trong `class_user_assignments`.
- Được xem lớp và học sinh thuộc lớp được phân công.
- Được điểm danh, nhập điểm, quản lý bài tập, chấm bài nộp và gửi thông báo/điểm cho phụ huynh trong phạm vi lớp được phân công.
- Không được quản lý user, academic year, toàn bộ lớp hoặc toàn bộ học sinh ngoài phạm vi phân công.
- Khi tạo bài tập, chỉ được giao theo lớp được phân công, không được giao theo toàn khối.

## Teaching Assistant

- Chỉ xem các lớp và học sinh thuộc lớp được phân công.
- Chỉ hỗ trợ điểm danh.
- Không được quản lý học sinh, lớp, user, điểm số, bài tập hoặc thông báo.

## Admin

- Toàn quyền quản trị hệ thống.
- Được quản lý users, teachers, assistants, parents, classes, students, subjects, academic years, assignments, attendance, scores và system data.
- Được import danh sách học sinh.
- Được tạo lớp và giao bài theo lớp hoặc theo khối.

## Backend Enforcement

- Backend không tin frontend.
- API phải đọc user hiện tại từ JWT middleware.
- Nếu role không hợp lệ hoặc truy cập ngoài phạm vi dữ liệu được phép, API trả `403 Forbidden`.
- Parent API phải lọc theo student thuộc parent.
- Teacher/assistant API phải lọc theo lớp được phân công.
