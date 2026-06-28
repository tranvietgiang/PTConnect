# TEST RESULT REPORT

## Tổng quan

- **Dự án:** PTConnect Backend (Laravel 12)
- **Thư mục backend:** `D:\PTConnect\be-ptconnect`
- **Lệnh đã chạy:** `php vendor/bin/phpunit --configuration phpunit.xml`
- **Kết quả:** **172 tests, 293 assertions — OK (100% pass)**
- **Môi trường:** SQLite in-memory, PHP 8.2.12, PHPUnit 11.5.55

---

## Danh sách tính năng đã phát hiện

| STT | Tính năng | Mô tả | API Routes |
|-----|-----------|-------|------------|
| 1 | **Auth** | Đăng nhập (email/username), refresh token, logout, me | `POST /auth/login`, `POST /auth/refresh`, `POST /auth/logout`, `GET /auth/me`, `GET /me` |
| 2 | **Classroom** | Quản lý lớp học (CRUD), phân quyền theo role | `GET /classes`, `GET /classes/{id}`, `POST /classes` |
| 3 | **Student** | Quản lý học sinh, import CSV/XLSX, phân quyền | `GET /students`, `POST /students`, `GET /students/{id}`, `POST /students/import` |
| 4 | **Attendance** | Điểm danh, lịch sử, gửi thông báo | `GET /attendance/today`, `POST /attendance`, `GET /attendance/history` |
| 5 | **Assignment** | Quản lý bài tập, nộp bài, tải file | `GET /assignments`, `POST /assignments`, `GET /assignments/{id}/attachment`, `POST /assignments/{id}/submissions`, `GET /assignment-submissions/{id}/download` |
| 6 | **Models** | 16 Eloquent models với relationships | Users, Students, Classrooms, ParentProfile, AttendanceSession, AttendanceRecord, Assignment, AssignmentSubmission, Subject, Exam, Score, Notification, NotificationRecipient, EmailLog, AcademicYear, RefreshToken |
| 7 | **Middleware** | JWT auth, Role-based access, CORS | `jwt.auth`, `role:admin,teacher,assistant,parent` |

---

## Bảng kết quả từng tính năng

### 1. Auth

| Test file | Test name | Status | Chi tiết |
|-----------|-----------|--------|----------|
| `tests/Feature/Api/AuthTest.php` | test_login_with_email_success | **PASS** | Login bằng email, trả về access+refresh token |
| `tests/Feature/Api/AuthTest.php` | test_login_with_username_success | **PASS** | Login bằng username |
| `tests/Feature/Api/AuthTest.php` | test_login_with_remember_me_sets_cookie | **PASS** | Cookie ptconnect_remember_token được set |
| `tests/Feature/Api/AuthTest.php` | test_login_fails_with_wrong_password | **PASS** | 401 + message tiếng Việt |
| `tests/Feature/Api/AuthTest.php` | test_login_fails_with_nonexistent_email | **PASS** | 401 cho email không tồn tại |
| `tests/Feature/Api/AuthTest.php` | test_login_validation_fails_without_credentials | **PASS** | 422 khi thiếu email/username |
| `tests/Feature/Api/AuthTest.php` | test_login_fails_for_inactive_user | **PASS** | 401 cho user bị inactive |
| `tests/Feature/Api/AuthTest.php` | test_refresh_token_success | **PASS** | Refresh token trả về access token mới |
| `tests/Feature/Api/AuthTest.php` | test_refresh_fails_with_invalid_token | **PASS** | 401 cho token không hợp lệ |
| `tests/Feature/Api/AuthTest.php` | test_refresh_fails_without_token | **PASS** | 401 khi không gửi token |
| `tests/Feature/Api/AuthTest.php` | test_logout_success | **PASS** | Revoke refresh token |
| `tests/Feature/Api/AuthTest.php` | test_logout_without_token_still_succeeds | **PASS** | Logout vẫn thành công dù không có token |
| `tests/Feature/Api/AuthTest.php` | test_me_with_valid_token | **PASS** | Trả về thông tin user |
| `tests/Feature/Api/AuthTest.php` | test_me_public_endpoint | **PASS** | GET /me không có token → 401 |
| `tests/Feature/Api/AuthTest.php` | test_me_fails_without_token | **PASS** | GET /auth/me không có token → 401 |
| `tests/Feature/Api/AuthTest.php` | test_protected_route_fails_without_token | **PASS** | Route có jwt.auth → 401 |

### 2. Classroom

| Test file | Test name | Status | Chi tiết |
|-----------|-----------|--------|----------|
| `tests/Feature/Api/ClassroomTest.php` | test_admin_can_list_all_classes | **PASS** | Admin thấy tất cả lớp |
| `tests/Feature/Api/ClassroomTest.php` | test_teacher_sees_only_assigned_classes | **PASS** | Teacher chỉ thấy lớp được phân công |
| `tests/Feature/Api/ClassroomTest.php` | test_unauthenticated_user_cannot_list_classes | **PASS** | 401 không token |
| `tests/Feature/Api/ClassroomTest.php` | test_admin_can_create_class | **PASS** | Admin tạo lớp thành công (201) |
| `tests/Feature/Api/ClassroomTest.php` | test_teacher_cannot_create_class | **PASS** | Teacher bị 403 |
| `tests/Feature/Api/ClassroomTest.php` | test_create_class_validates_grade_level | **PASS** | 422 cho grade_level=13 |
| `tests/Feature/Api/ClassroomTest.php` | test_create_class_requires_name | **PASS** | 422 khi thiếu name |
| `tests/Feature/Api/ClassroomTest.php` | test_admin_can_show_class | **PASS** | Admin xem chi tiết lớp |
| `tests/Feature/Api/ClassroomTest.php` | test_teacher_can_show_assigned_class | **PASS** | Teacher xem lớp được phân công |
| `tests/Feature/Api/ClassroomTest.php` | test_teacher_cannot_show_unassigned_class | **PASS** | 403 cho lớp không được phân công |

### 3. Student

| Test file | Test name | Status | Chi tiết |
|-----------|-----------|--------|----------|
| `tests/Feature/Api/StudentTest.php` | test_admin_can_list_students | **PASS** | Admin xem tất cả học sinh |
| `tests/Feature/Api/StudentTest.php` | test_student_list_supports_search | **PASS** | Tìm kiếm theo keyword |
| `tests/Feature/Api/StudentTest.php` | test_student_list_filters_by_classroom | **PASS** | Lọc theo classroom_id |
| `tests/Feature/Api/StudentTest.php` | test_parent_sees_only_own_children | **PASS** | Parent chỉ thấy con của mình |
| `tests/Feature/Api/StudentTest.php` | test_admin_can_create_student | **PASS** | Admin tạo học sinh (201) |
| `tests/Feature/Api/StudentTest.php` | test_non_admin_cannot_create_student | **PASS** | Non-admin bị 403 |
| `tests/Feature/Api/StudentTest.php` | test_create_student_validates_required_fields | **PASS** | 422 thiếu required |
| `tests/Feature/Api/StudentTest.php` | test_create_student_validates_unique_code | **PASS** | 422 trùng student_code |
| `tests/Feature/Api/StudentTest.php` | test_admin_can_show_student | **PASS** | Xem chi tiết học sinh |
| `tests/Feature/Api/StudentTest.php` | test_parent_can_show_own_child | **PASS** | Parent xem con mình |
| `tests/Feature/Api/StudentTest.php` | test_parent_cannot_show_unrelated_student | **PASS** | 403 cho học sinh không liên quan |
| `tests/Feature/Api/StudentTest.php` | test_admin_can_import_students_from_csv | **PASS** | Import CSV thành công |
| `tests/Feature/Api/StudentTest.php` | test_non_admin_cannot_import_students | **PASS** | 403 cho non-admin |
| `tests/Feature/Api/StudentTest.php` | test_unauthenticated_user_cannot_access_students | **PASS** | 401 không token |

### 4. Attendance

| Test file | Test name | Status | Chi tiết |
|-----------|-----------|--------|----------|
| `tests/Feature/Api/AttendanceTest.php` | test_admin_can_get_today_attendance | **PASS** | Admin xem điểm danh hôm nay |
| `tests/Feature/Api/AttendanceTest.php` | test_assistant_can_get_today_attendance | **PASS** | Assistant xem điểm danh |
| `tests/Feature/Api/AttendanceTest.php` | test_teacher_cannot_get_today_attendance | **PASS** | Teacher bị 403 |
| `tests/Feature/Api/AttendanceTest.php` | test_today_attendance_validates_classroom | **PASS** | 422 thiếu classroom_id |
| `tests/Feature/Api/AttendanceTest.php` | test_admin_can_submit_attendance | **PASS** | Submit điểm danh (201) |
| `tests/Feature/Api/AttendanceTest.php` | test_assistant_can_submit_attendance | **PASS** | Assistant submit điểm danh |
| `tests/Feature/Api/AttendanceTest.php` | test_attendance_submit_validates_records | **PASS** | 422 records rỗng |
| `tests/Feature/Api/AttendanceTest.php` | test_attendance_submit_validates_status | **PASS** | 422 status không hợp lệ |
| `tests/Feature/Api/AttendanceTest.php` | test_attendance_submit_rejects_student_not_in_class | **PASS** | 422 học sinh không thuộc lớp |
| `tests/Feature/Api/AttendanceTest.php` | test_admin_can_view_attendance_history | **PASS** | Xem lịch sử điểm danh |
| `tests/Feature/Api/AttendanceTest.php` | test_assistant_can_view_history | **PASS** | Assistant xem lịch sử |
| `tests/Feature/Api/AttendanceTest.php` | test_teacher_cannot_view_attendance_history | **PASS** | Teacher bị 403 |
| `tests/Feature/Api/AttendanceTest.php` | test_unauthenticated_user_cannot_access_attendance | **PASS** | 401 không token |
| `tests/Feature/Api/AttendanceTest.php` | test_attendance_creates_notifications_for_late_students | **PASS** | Tạo notification + email_log cho học sinh đi muộn |

### 5. Assignment

| Test file | Test name | Status | Chi tiết |
|-----------|-----------|--------|----------|
| `tests/Feature/Api/AssignmentTest.php` | test_admin_can_list_assignments | **PASS** | Admin xem tất cả bài tập |
| `tests/Feature/Api/AssignmentTest.php` | test_teacher_sees_only_assigned_class_assignments | **PASS** | Teacher chỉ xem bài tập lớp được phân công |
| `tests/Feature/Api/AssignmentTest.php` | test_parent_sees_published_assignments | **PASS** | Parent xem bài tập published |
| `tests/Feature/Api/AssignmentTest.php` | test_parent_does_not_see_draft_assignments | **PASS** | Parent không thấy draft |
| `tests/Feature/Api/AssignmentTest.php` | test_admin_can_create_assignment | **PASS** | Admin tạo bài tập (201) |
| `tests/Feature/Api/AssignmentTest.php` | test_teacher_can_create_assignment_for_assigned_class | **PASS** | Teacher tạo bài tập cho lớp được phân công |
| `tests/Feature/Api/AssignmentTest.php` | test_teacher_cannot_create_assignment_for_unassigned_class | **PASS** | 403 cho lớp không được phân công |
| `tests/Feature/Api/AssignmentTest.php` | test_create_assignment_requires_title | **PASS** | 422 thiếu title |
| `tests/Feature/Api/AssignmentTest.php` | test_create_assignment_requires_classroom_or_grade | **PASS** | 422 thiếu classroom_id và grade_level |
| `tests/Feature/Api/AssignmentTest.php` | test_parent_can_submit_assignment_pdf | **PASS** | Parent nộp bài PDF (201) |
| `tests/Feature/Api/AssignmentTest.php` | test_parent_can_submit_assignment_doc | **PASS** | Parent nộp bài .doc (201) |
| `tests/Feature/Api/AssignmentTest.php` | test_parent_can_submit_assignment_docx | **PASS** | Parent nộp bài .docx (201) |
| `tests/Feature/Api/AssignmentTest.php` | test_non_parent_cannot_submit_assignment | **PASS** | Non-parent bị 403 |
| `tests/Feature/Api/AssignmentTest.php` | test_cannot_submit_to_overdue_assignment | **PASS** | 422 bài tập quá hạn |
| `tests/Feature/Api/AssignmentTest.php` | test_submission_requires_file | **PASS** | 422 thiếu file |
| `tests/Feature/Api/AssignmentTest.php` | test_download_attachment_returns_404_for_missing | **PASS** | 404 khi file không tồn tại |
| `tests/Feature/Api/AssignmentTest.php` | test_download_submission_works | **PASS** | Download file nộp bài |
| `tests/Feature/Api/AssignmentTest.php` | test_admin_can_grade_submission | **PASS** | Admin chấm điểm bài nộp (200) |
| `tests/Feature/Api/AssignmentTest.php` | test_teacher_can_grade_submission_for_assigned_class | **PASS** | Teacher chấm điểm lớp được phân công |
| `tests/Feature/Api/AssignmentTest.php` | test_teacher_cannot_grade_submission_for_unassigned_class | **PASS** | 403 cho lớp không được phân công |
| `tests/Feature/Api/AssignmentTest.php` | test_parent_cannot_grade_submission | **PASS** | Parent bị 403 |
| `tests/Feature/Api/AssignmentTest.php` | test_grade_submission_validates_score_range | **PASS** | 422 score > 10 |
| `tests/Feature/Api/AssignmentTest.php` | test_grade_submission_can_clear_score | **PASS** | Gửi null để xoá điểm |
| `tests/Feature/Api/AssignmentTest.php` | test_admin_can_create_assignment_with_doc_attachment | **PASS** | Tạo assignment với file .doc đính kèm |
| `tests/Feature/Api/AssignmentTest.php` | test_admin_can_create_assignment_with_docx_attachment | **PASS** | Tạo assignment với file .docx đính kèm |
| `tests/Feature/Api/AssignmentTest.php` | test_unauthenticated_user_cannot_access_assignments | **PASS** | 401 không token |

### 6. Existing Model Tests (fixed)

| Test file | Tests | Status |
|-----------|-------|--------|
| `tests/Feature/Models/UserTest.php` | 11 tests | **PASS** |
| `tests/Feature/Models/ClassroomTest.php` | 8 tests | **PASS** |
| `tests/Feature/Models/StudentTest.php` | 8 tests | **PASS** |
| `tests/Feature/Models/ParentProfileTest.php` | 6 tests | **PASS** |
| `tests/Feature/Models/NotificationTest.php` | 5 tests | **PASS** |
| `tests/Feature/Models/NotificationRecipientTest.php` | 4 tests | **PASS** |
| `tests/Feature/Models/AssignmentTest.php` | 5 tests | **PASS** |
| `tests/Feature/Models/AssignmentSubmissionTest.php` | 3 tests | **PASS** |
| `tests/Feature/Models/AttendanceSessionTest.php` | — | **PASS** |
| `tests/Feature/Models/AttendanceRecordTest.php` | — | **PASS** |
| `tests/Feature/Models/AcademicYearTest.php` | — | **PASS** |
| `tests/Feature/Models/ExamTest.php` | 6 tests | **PASS** |
| `tests/Feature/Models/ScoreTest.php` | 5 tests | **PASS** |
| `tests/Feature/Models/SubjectTest.php` | 3 tests | **PASS** |
| `tests/Feature/Models/EmailLogTest.php` | — | **PASS** |
| `tests/Unit/ExampleTest.php` | 1 test | **PASS** |

---

## File đã thêm/sửa

### File mới (API Feature Tests)

| File | Số test | Mô tả |
|------|---------|-------|
| `tests/Feature/Api/AuthTest.php` | 16 tests | Login/logout/refresh/me endpoints |
| `tests/Feature/Api/ClassroomTest.php` | 10 tests | Class CRUD + phân quyền |
| `tests/Feature/Api/StudentTest.php` | 14 tests | Student CRUD + import + phân quyền |
| `tests/Feature/Api/AttendanceTest.php` | 14 tests | Attendance + history + notifications |
| `tests/Feature/Api/AssignmentTest.php` | 27 tests | Assignment + submissions + grade + download |

### File đã sửa (bug fixes)

| File | Sửa | Lý do |
|------|-----|-------|
| `tests/TestCase.php` | Thêm `DatabaseMigrations` trait | Tất cả model tests bị lỗi "no such table" vì không có migration |
| `tests/Feature/Models/UserTest.php` | Sửa fillable expected array, sửa `test_user_can_be_created` (bỏ `name`), sửa `test_user_has_parent_profile_relation` (thêm `student_id`) | Fillable sai, `name` không có trong DB schema, ParentProfile cần `student_id` |
| `tests/Feature/Models/ParentProfileTest.php` | Thêm `student_id` khi tạo ParentProfile, sửa test relationship từ `belongsToMany` thành `belongsTo` | `parents.student_id` là NOT NULL, model dùng BelongsTo không phải BelongsToMany |
| `tests/Feature/Models/NotificationTest.php` | Thêm `student_id` khi tạo ParentProfile | `parents.student_id` NOT NULL |
| `tests/Feature/Models/NotificationRecipientTest.php` | Thêm `student_id` khi tạo ParentProfile | `parents.student_id` NOT NULL |
| `tests/Feature/Models/StudentTest.php` | Thêm `avatar` vào fillable expected, sửa `test_student_belongs_to_many_parents` thành `test_student_has_many_parents` | Model có `avatar`, Student.parents là HasMany không phải BelongsToMany |
| `app/Http/Controllers/Api/AttendanceController.php` | Sửa `?:` thành `??` cho `$validated['session_name']` | Bug: `Undefined array key "session_name"` khi field không được gửi lên → 500 Internal Server Error |
| `app/Http/Controllers/Api/StudentController.php` | Thêm `csv` vào mimes validation cho import | Code hỗ trợ CSV nhưng validation không cho phép → 422 |

---

## Đã xác nhận yêu cầu demo

### 1. Seeder: password 12345678 cho tất cả tài khoản demo ✅
- `database/seeders/V1DatabaseSeeder.php` dùng `Hash::make('12345678')` cho:
  - `admin@ptconnect.test` / `admin`
  - `teacher@ptconnect.test` / `teacher`
  - `assistant@ptconnect.test` / `assistant`
  - Tất cả phụ huynh (`<studentCode>@parent.ptconnect.test` / `<studentCode>`)

### 2. Mỗi lớp chỉ có 40-60 học sinh ✅
- Hằng số `MIN_STUDENTS_PER_CLASS = 40`, `MAX_STUDENTS_PER_CLASS = 60`
- 12 lớp (10A1-10A4, 11A1-11A4, 12A1-12A4) → 480–720 học sinh + tài khoản phụ huynh tương ứng

### 3. Upload file nhận .doc / .docx / .pdf ✅
- `AssignmentController::ALLOWED_FILE_TYPES = 'pdf,doc,docx,...'` (dùng rule `extensions:`)
- Hỗ trợ thêm: xls, xlsx, ppt, pptx, jpg, jpeg, png, zip, rar, 7z, txt, csv
- File tối đa 10MB

### 4. Điểm số (Scores/Grades) — API đã có một phần, còn thiếu ⚠️
| API | Trạng thái | Ghi chú |
|-----|-----------|---------|
| `PATCH /api/assignment-submissions/{id}/grade` | ✅ Đã có + đã test (6 tests) | Chấm điểm bài nộp tập (admin/teacher), score 0-10, teacher_comment max 2000 ký tự. Điểm lưu trong `assignment_submissions.score` (decimal 4,2). |
| `GET/POST/PUT/DELETE /api/exams` | ❌ **Không tồn tại** | Model `Exam` (id, classroom_id, subject_id, teacher_id, title, exam_type, exam_date, max_score, note, is_published, published_at) + migration có sẵn. **Không có controller, không có route.** |
| `GET/POST/PUT/DELETE /api/scores` | ❌ **Không tồn tại** | Model `Score` (id, exam_id, student_id, score decimal 5,2, comment, email_sent_at) + migration có sẵn. Unique constraint [exam_id, student_id]. **Không có controller, không có route.** |

**Chi tiết Exam model (đã có sẵn nhưng không có API):**
- `exam_type`: string free-text (không có enum, không có validation constraint — DB lưu string thuần)
- `max_score`: decimal 5,2, default 10.00
- `is_published`: boolean, default false
- Relationships: `classroom()`, `subject()`, `teacher()`, `scores()`

**Chi tiết Score model (đã có sẵn nhưng không có API):**
- `score`: decimal 5,2 — lưu điểm thi thực tế
- `email_sent_at`: timestamp nullable — thời điểm gửi email thông báo điểm cho phụ huynh
- `comment`: text nullable — nhận xét
- Unique: `[exam_id, student_id]` (mỗi học sinh chỉ có 1 điểm cho 1 bài thi)

### 5. Thông báo chung (Notifications) — API còn thiếu ⚠️
| API | Trạng thái | Ghi chú |
|-----|-----------|---------|
| `GET/PUT/DELETE /api/notifications` | ❌ **Không tồn tại** | **Không có `NotificationController`, không có route API.** Không thể xem danh sách, đánh dấu đã đọc, hay xoá thông báo qua API. |
| `GET /api/notifications/unread-count` | ❌ **Không tồn tại** | Không có endpoint đếm thông báo chưa đọc. |
| Tạo thông báo tự động (qua attendance) | ✅ Hoạt động nội bộ | `AttendanceController@createParentNotification()` tạo `Notification` + `NotificationRecipient` + `EmailLog` khi điểm danh học sinh đi muộn/vắng |
| Gửi email | ✅ Hoạt động nội bộ | Email được lưu vào `email_logs` table (dùng MAIL_MAILER=array khi test) |

**Chi tiết Notification model (đã có sẵn nhưng không có API):**
- `type`: string — hiện chỉ dùng giá trị `'attendance'`. Có thể mở rộng: `'score'`, `'assignment'`, `'general'`, `'system'`
- `target_type`: string — hiện chỉ dùng giá trị `'student'`. Có thể mở rộng: `'class'`, `'grade'`, `'all'`
- `sender_id`: FK → users (người gửi)
- `classroom_id`, `grade_level`, `student_id`, `parent_id`: nullable filters
- Relationship: `recipients()` → NotificationRecipient (sent_at, read_at, status)

**Chi tiết NotificationRecipient model (đã có sẵn, không có API):**
- `status`: string, default `'pending'` — có thể là `'sent'`, `'read'`
- `read_at`: timestamp nullable — đã hỗ trợ đánh dấu đã đọc nhưng không có API để update

### Tổng kết yêu cầu demo
| Yêu cầu | Kết quả | Chi tiết |
|---------|---------|----------|
| Password 12345678 cho tất cả tài khoản | ✅ | Admin, teacher, assistant, parent đều dùng 12345678 |
| 40-60 học sinh mỗi lớp | ✅ | 12 lớp × 40-60 = 480-720 học sinh |
| Upload .doc/.docx/.pdf | ✅ | Cả attachment (tạo assignment) và submission (nộp bài) đều chấp nhận. Test cụ thể với từng định dạng. |
| Điểm số API | ⚠️ **Thiếu Exam + Score CRUD** | Chỉ có chấm bài tập. **Cần implement ExamController + ScoreController** (model + migration có sẵn) |
| Thông báo chung API | ⚠️ **Thiếu hoàn toàn** | Chỉ có tạo notification nội bộ qua attendance. **Cần implement NotificationController** để xem, đánh dấu đã đọc, lọc theo type/status |

---

## Các giả định khi viết test

1. **Database:** Sử dụng SQLite in-memory với `DatabaseMigrations` (migrate mới cho mỗi test).
2. **JWT Auth:** Các request được auth bằng cách gọi login thật, lấy access token, gửi trong header `Authorization: Bearer <token>`.
3. **Seed data:** Không dùng DatabaseSeeder - mỗi test tự tạo dữ liệu cần thiết.
4. **Storage:** Sử dụng `Storage::fake('local')` cho các test liên quan đến file upload/download.
5. **Model tests:** Test relationship và fillable, không test API.
6. **Response format:** API luôn trả về JSON với `success`, `message`, `data`/`errors`.
7. **Roles:** `admin`, `teacher`, `assistant`, `parent` - middleware `jwt.auth` + `role` kiểm soát access.

---

## Những phần chưa test được và lý do

| Phần | Lý do | Giải pháp đề xuất |
|------|-------|-------------------|
| **Import XLSX** | Không thể fake file XLSX hợp lệ trong test dễ dàng | Đã test CSV, XLSX có cấu trúc ZIP phức tạp cần thư viện riêng |
| **Email gửi thật** | MAIL_MAILER=array trong testing environment | Đã assert `email_logs` trong database thay vì gửi mail thật |
| **Exam CRUD API** | Chưa có controller/route — model + migration có sẵn (fields: classroom_id, subject_id, teacher_id, title, exam_type, exam_date, max_score, note, is_published, published_at) | Cần tạo `ExamController` + routes (resource) + tests cho đầy đủ CRUD + phân quyền |
| **Score CRUD API** | Chưa có controller/route — model + migration có sẵn (fields: exam_id, student_id, score decimal 5,2, comment, email_sent_at; unique [exam_id, student_id]) | Cần tạo `ScoreController` + routes + tests; nhập điểm theo file Excel nếu cần |
| **Notification/GENERAL API** | Chưa có controller/route nào — model + migration có sẵn (fields: type, target_type, sender_id, classroom_id, grade_level, student_id, parent_id). Hiện chỉ có internal logic trong AttendanceController tạo notification type='attendance'. **Không thể xem danh sách thông báo, đánh dấu đã đọc, lọc theo type.** | Cần tạo `NotificationController` + routes; endpoints cần: GET index (filter type/status), PUT markAsRead, GET unread-count |
| **Subject management** | Chưa có controller/route cho Subject | Chỉ có model và migration |
| **Seeder demo** | Chỉ chạy được với MySQL (DB_HOST=mysql trong .env). Không test được với SQLite in-memory vì cần Docker. | Đã xác nhận code seeder đúng yêu cầu (password 12345678, 40-60 HS/lớp). Cần kiểm tra thủ công khi có MySQL. |
| **Attachment download** | File thật cần được tạo trong storage | Đã dùng `Storage::fake` để test luồng not found và download từ path |
| **Rate limiting** | Không có rate limiting trong code | N/A |
| **Pagination** | Các API không phân trang | N/A |
| **Unit tests cho services** | AuthService có dependency cần mock | Đã test qua Integration (API calls), chưa có Unit test riêng |
