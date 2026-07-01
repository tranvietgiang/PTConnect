# Role Test Cases — PTConnect

> File này liệt kê tất cả API endpoint và quyền truy cập của từng role.
> Dùng để test hệ thống sau mỗi lần thay đổi phân quyền.

## Accounts mẫu (seeder)

| Email | Password | Role |
|-------|----------|------|
| `system.admin@ptconnect.test` | `12345678` | `system_admin` |
| `school.admin@ptconnect.test` | `12345678` | `school_admin` |
| `teacher@ptconnect.test` | `12345678` | `teacher` |
| `assistant@ptconnect.test` | `12345678` | `assistant` |
| `student.an@ptconnect.test` | `PTC100001` | `student` |

---

## 1. Auth

### `POST /auth/login`
| Role | Kết quả |
|------|---------|
| Tất cả | ✅ 200 + token |

### `POST /auth/me`
| Role | Kết quả |
|------|---------|
| Tất cả authenticated | ✅ 200 + user info |

---

## 2. Classroom

### `GET /classes`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ Thấy tất cả lớp |
| `school_admin` | ✅ Thấy tất cả lớp |
| `teacher` | ✅ Chỉ thấy lớp có `teacher_id = mình` |
| `assistant` | ✅ Chỉ thấy lớp có assignment active |
| `student` | ✅ Chỉ thấy lớp có enrollment active |

### `GET /classes/{id}`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu là teacher của lớp |
| `assistant` | ✅ Nếu có assignment active |
| `student` | ✅ Nếu có enrollment active |

### `POST /classes`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ 201 |
| `school_admin` | ✅ 201 |
| `teacher` | ❌ 403 |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

### `PUT|PATCH /classes/{id}`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ❌ 403 |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

---

## 3. Student (StudentProfile)

### `GET /students`
| Role | Kết quả | Dữ liệu trả về |
|------|---------|----------------|
| `system_admin` | ✅ Tất cả | Full: id, student_code, full_name, high_school_name, emails, CCCD, phone, address, parent info |
| `school_admin` | ✅ Tất cả | Full |
| `teacher` | ✅ Chỉ HS thuộc lớp được phân công | **Limited:** id, student_code, full_name, high_school_name |
| `assistant` | ✅ Chỉ HS thuộc lớp được phân công | **Limited:** id, student_code, full_name, high_school_name |
| `student` | ✅ Chỉ profile của mình | Full |

### `POST /students`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ 201 |
| `school_admin` | ✅ 201 |
| `teacher` | ✅ Chỉ tạo được cho lớp mình dạy |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

### `POST /students/import`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ❌ 403 |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

### `GET /students/{id}`
| Role | Kết quả | Dữ liệu |
|------|---------|---------|
| `system_admin` | ✅ | Full |
| `school_admin` | ✅ | Full |
| `teacher` | ✅ Nếu HS thuộc lớp được phân công | **Limited:** id, student_code, full_name, high_school_name |
| `assistant` | ✅ Nếu HS thuộc lớp được phân công | **Limited:** id, student_code, full_name, high_school_name |
| `student` | ✅ Chỉ profile của mình | Full |

### `PUT /students/{id}`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu HS thuộc lớp mình dạy |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

---

## 4. Attendance

### `GET /attendance/sessions`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ Tất cả |
| `school_admin` | ✅ Tất cả |
| `teacher` | ✅ Chỉ lớp được phân công |
| `assistant` | ✅ Chỉ lớp được phân công |
| `student` | ❌ 403 |

### `POST /attendance/sessions`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Chỉ lớp mình dạy |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

### `POST /attendance/sessions/bulk`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Chỉ lớp mình dạy |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

### `GET /attendance/sessions/{id}`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu được phân công |
| `assistant` | ✅ Nếu được phân công |
| `student` | ❌ 403 |

### `PUT /attendance/sessions/{id}`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu là teacher của lớp |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

### `PATCH /attendance/sessions/{id}/close`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu là teacher của lớp |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

### `DELETE /attendance/sessions/{id}`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu là teacher của lớp |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

### `GET /attendance/today`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu được phân công |
| `assistant` | ✅ Nếu được phân công |
| `student` | ❌ 403 |

### `POST /attendance`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu được phân công |
| `assistant` | ✅ Nếu được phân công |
| `student` | ❌ 403 |

### `POST /attendance/sessions/{id}/send-email`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu được phân công |
| `assistant` | ✅ Nếu được phân công |
| `student` | ❌ 403 |

### `GET /attendance/history`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ Tất cả |
| `school_admin` | ✅ Tất cả |
| `teacher` | ✅ Chỉ lớp được phân công |
| `assistant` | ✅ Chỉ lớp được phân công |
| `student` | ❌ 403 |

### `GET /attendance/parent`
| Role | Kết quả |
|------|---------|
| `system_admin` | ❌ 403 |
| `school_admin` | ❌ 403 |
| `teacher` | ❌ 403 |
| `assistant` | ❌ 403 |
| `student` | ✅ Chỉ điểm danh của mình |

---

## 5. Assignment

### `GET /assignments`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ Tất cả |
| `school_admin` | ✅ Tất cả |
| `teacher` | ✅ Chỉ lớp được phân công |
| `assistant` | ✅ Chỉ lớp được phân công |
| `student` | ✅ Chỉ bài tập thuộc lớp/khối của mình |

### `POST /assignments`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Chỉ giao cho lớp mình dạy |
| `assistant` | ❌ 403 |
| `student` | ❌ 403 |

### `GET /assignments/{id}/attachment`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu thuộc lớp được phân công |
| `assistant` | ✅ Nếu thuộc lớp được phân công |
| `student` | ✅ Nếu thuộc lớp/khối của mình |

### `POST /assignments/{id}/submissions`
| Role | Kết quả |
|------|---------|
| `system_admin` | ❌ 403 |
| `school_admin` | ❌ 403 |
| `teacher` | ❌ 403 |
| `assistant` | ❌ 403 |
| `student` | ✅ Nộp bài cho con em mình |

### `PATCH /assignment-submissions/{id}/grade`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu thuộc lớp được phân công |
| `assistant` | ✅ Nếu thuộc lớp được phân công |
| `student` | ❌ 403 |

### `POST /assignment-submissions/{id}/send-email`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu thuộc lớp được phân công |
| `assistant` | ✅ Nếu thuộc lớp được phân công |
| `student` | ❌ 403 |

### `POST /assignment-submissions/send-email-bulk`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu các submission thuộc lớp được phân công |
| `assistant` | ✅ Nếu các submission thuộc lớp được phân công |
| `student` | ❌ 403 |

### `GET /assignment-submissions/{id}/download`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Nếu thuộc lớp được phân công |
| `assistant` | ✅ Nếu thuộc lớp được phân công |
| `student` | ✅ Nếu là bài của mình |

---

## 6. Score

### `GET /scores`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ Tất cả |
| `school_admin` | ✅ Tất cả |
| `teacher` | ✅ Chỉ HS thuộc lớp được phân công |
| `assistant` | ✅ Chỉ HS thuộc lớp được phân công |
| `student` | ✅ Chỉ điểm của mình |

### `GET /scores/report`
| Role | Kết quả |
|------|---------|
| `system_admin` | ✅ |
| `school_admin` | ✅ |
| `teacher` | ✅ Chỉ lớp được phân công |
| `assistant` | ✅ Chỉ lớp được phân công |
| `student` | ❌ 403 |

---

## 7. Auto-lock Account (Scheduled Task)

| Tính năng | Mô tả | Cách test |
|-----------|-------|-----------|
| Lock student | Student không có enrollment active nào, enrollment cuối ended >= 7 ngày → `is_active = false` | Chạy `php artisan account:auto-lock` |
| Lock assistant | Assistant không có assignment active nào, assignment cuối ended >= 7 ngày → `is_active = false` | Chạy `php artisan account:auto-lock` |
| Reactivate student | Student có enrollment active mới → `is_active = true` | Tạo enrollment active → chạy command |
| Reactivate assistant | Assistant có assignment active mới → `is_active = true` | Tạo assignment active → chạy command |

### Test manual auto-lock:
```bash
# Chạy thủ công (không cần chờ schedule)
php artisan account:auto-lock

# Output mẫu:
# Locked 2 student(s) with no active enrollment for 7+ days.
# Locked 0 assistant(s) with no active assignment for 7+ days.
# Reactivated 1 student(s) with new active enrollment(s).
```

### Test login bị chặn:
```bash
# Sau khi account bị lock, login sẽ trả về:
curl -X POST /auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"student.an@ptconnect.test","password":"PTC100001"}'
# → 401: "Account has been locked"
```

---

## 8. Email

### Attendance email
```bash
# Gửi email điểm danh cho 1 hoặc nhiều học sinh trong session
curl -X POST /api/attendance/sessions/1/send-email \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"student_ids": [1, 2, 3]}'

# Gửi cho tất cả học sinh trong session (bỏ trống student_ids)
curl -X POST /api/attendance/sessions/1/send-email \
  -H "Authorization: Bearer <token>"
```

### Score email
```bash
# Gửi email điểm cho 1 submission
curl -X POST /api/assignment-submissions/1/send-email \
  -H "Authorization: Bearer <token>"

# Gửi email điểm hàng loạt
curl -X POST /api/assignment-submissions/send-email-bulk \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"submission_ids": [1, 2, 3]}'
```

### Check trạng thái email trong response:
```json
{
  "email_status": "not_sent",   // sent | failed | not_sent
  "email_status_label": "Chưa gửi",
  "score_emailed_at": null
}
```

---

## 9. Edge cases cần test

| # | Tình huống | Expected |
|---|------------|----------|
| 1 | Student không có enrollment nào → vẫn active (chưa đủ 7 ngày) | ✅ |
| 2 | Student hết hạn enrollment 7 ngày → lock | ✅ |
| 3 | Student lock → tạo enrollment mới → reactivate | ✅ |
| 4 | Assistant hết hạn assignment 7 ngày → lock | ✅ |
| 5 | Assistant lock → tạo assignment mới → reactivate | ✅ |
| 6 | Teacher xem student → chỉ thấy limited fields | ✅ |
| 7 | Assistant xem student → chỉ thấy limited fields | ✅ |
| 8 | Assistant tạo session → 403 | ✅ |
| 9 | Assistant điểm danh → 200 (nếu có assignment) | ✅ |
| 10 | Student xem student khác → 403 | ✅ |
| 11 | Teacher tạo student cho lớp không phải của mình → 403 | ✅ |
| 12 | Gửi email điểm danh khi chưa có điểm danh → 422 | ✅ |
| 13 | Gửi email điểm khi chưa có điểm → 422 | ✅ |
| 14 | Resend email → email_status từ sent → sent (vẫn là sent, ghi đè) | ✅ |
