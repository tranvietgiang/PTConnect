# TEST RESULT REPORT

**Project:** PTConnect — Backend (be-ptconnect) + Frontend (fe-ptconnect)
**Date:** 2026-06-29
**Environment:** PHP 8.2.12 / SQLite (testing) / Laravel 12

---

## I. PHPUnit Test Results (Automated)

### Full Test Suite

| Metric       | Value                |
| ------------ | -------------------- |
| Tests        | 159                  |
| Assertions   | 289                  |
| **Failures** | **1 FAILURE**        |
| Errors       | 0                    |

### Failed Test Detail

**Test:** `Tests\Feature\ExampleTest::test_the_application_returns_a_successful_response`

| Field       | Detail                                                         |
| ----------- | -------------------------------------------------------------- |
| Status Code | **500** (expected 200)                                         |
| Exception   | `InvalidArgumentException: View [welcome] not found.`           |
| File        | `be-ptconnect/routes/web.php:6` — line `view('welcome')`       |
| Root Cause  | The `welcome.blade.php` view file is missing from `resources/views/`. The default Laravel welcome page was removed or not included. |

---

### Feature Tests — "Class" Features (Classroom API + Model)

| Test File                                    | Tests | Assertions | Result |
| -------------------------------------------- | ----- | ---------- | ------ |
| `tests/Feature/Api/ClassroomTest.php`        | 9     | 19         | **PASS** |
| `tests/Feature/Models/ClassroomTest.php`     | 6     | 12         | **PASS** |
| `tests/Feature/Api/AttendanceTest.php`       | 13    | 22         | **PASS** |
| `tests/Feature/Models/AttendanceSessionTest.php` | 6 | 11         | **PASS** |
| `tests/Feature/Models/AttendanceRecordTest.php` | 7  | 12         | **PASS** |

### Unit Tests

| Test File                         | Tests | Assertions | Result |
| --------------------------------- | ----- | ---------- | ------ |
| `tests/Unit/ExampleTest.php`      | 1     | 1          | **PASS** |

---

## II. Integration Test Results (Manual API Testing)

API endpoints were tested live using a running Laravel dev server (SQLite).

| #  | Endpoint                            | Method | Expected | Actual | Result      |
| -- | ----------------------------------- | ------ | -------- | ------ | ----------- |
| 1  | `/api/auth/login` (correct creds)   | POST   | 200      | 200    | **PASS**    |
| 2  | `/api/auth/login` (wrong password)  | POST   | 401      | 401    | **PASS**    |
| 3  | `/api/attendance/today` (valid)     | GET    | 200      | 200    | **PASS**    |
| 4  | `/api/attendance/today` (no id)     | GET    | 422      | 422    | **PASS**    |
| 5  | `/api/attendance/today` (bad id)    | GET    | 422      | 422    | **PASS**    |
| 6  | `/api/scores`                       | GET    | 200      | 200    | **PASS**    |
| 7  | `/api/scores` (assistant role)      | GET    | 403      | 403    | **PASS**    |
| 8  | `/api/students`                     | GET    | 200      | 200    | **PASS**    |
| 9  | `/api/classes`                      | GET    | 200      | 200    | **PASS**    |
| 10 | `/api/scores/report`                | GET    | 200      | 200    | **PASS**    |
| 11 | `/api/attendance/parent`            | GET    | 200      | 200    | **PASS**    |

---

## III. Issues Found Outside Automated Tests

### ERROR 1 — ExampleTest Failure (Welcome View Missing)
- **Severity:** Medium
- **File:** `be-ptconnect/routes/web.php:6`
- **Description:** The root route `GET /` calls `view('welcome')` but the `resources/views/welcome.blade.php` file does not exist.
- **Impact:** The homepage returns HTTP 500. Automated test fails.

### ERROR 2 — Missing Migration Files from Directory
- **Severity:** High
- **Description:** The migration files `2026_06_28_000007_create_subjects_table.php` and `2026_06_29_000002_add_lesson_number_to_attendance_sessions_table.php` are **referenced in code** but **do not exist** in the `database/migrations/` directory as of the current test run.
  - `Subject` model and related code exist but the migration to create the `subjects` table is missing.
  - Code references `lesson_number` column on `attendance_sessions` but the migration that adds this column is missing.
- **Impact:** On a fresh MySQL database, running `php artisan migrate` will NOT create these tables/columns, causing SQL errors like:
  ```
  SQLSTATE[42S22]: Column not found: 1054 Unknown column 'lesson_number' in 'where clause'
  ```

### ERROR 3 — Raw SQL Exception Leaked to Frontend
- **Severity:** Medium
- **File:** `fe-ptconnect/src/features/attendance/AttendancePage.jsx:159` (and 12 other `.jsx` files)
- **Description:** Error handling passes `error.message` directly to the toast notification, which can expose raw SQL/backtrace to end users when `APP_DEBUG=true`.
- **Fix Applied:** Updated `AttendancePage.jsx:159` to show a fixed user-friendly message instead of raw error message.

### ERROR 4 — Sidebar NavLink Double Highlight (Parent Role)
- **Severity:** Medium
- **File:** `fe-ptconnect/src/components/layout/Sidebar.jsx`
- **Description:** React Router's `NavLink` uses prefix matching by default. Navigating to `/phu-huynh/diem-so` (Điểm số) also highlights the `/phu-huynh` (Thông tin) nav item because `/phu-huynh` is a prefix of `/phu-huynh/diem-so`.
- **Fix Applied:** Added `end` prop to all NavLink items for exact matching.

---

## IV. Summary

| Category               | Total | Pass | Fail |
| ---------------------- | ----- | ---- | ---- |
| PHPUnit Tests          | 159   | 158  | 1    |
| Integration Tests (API) | 11    | 11   | 0    |
| Code Issues Found      | 4     | —    | 4    |

**Key takeaway:** The core business logic (Classroom, Attendance, Scores, Students APIs) works correctly. The critical issue is the **missing migration files** which will cause SQL errors when deploying against a fresh MySQL database. Fix the missing migrations before deploying to production.
