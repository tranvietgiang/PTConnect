<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Support\AccessControl;
use ZipArchive;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $query = StudentProfile::query()
            ->with([
                'studentEnrollments' => fn ($inner) => $inner->latest('enrolled_at'),
                'studentEnrollments.classroom.course',
                'studentEnrollments.course',
            ]);

        if ($user->isStudent()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isTeacher() || $user->isAssistant()) {
            $classroomIds = $this->assignedClassroomIds($user->id);
            $query->whereHas('studentEnrollments', fn ($inner) => $inner->whereIn('classroom_id', $classroomIds));
        } elseif (! $user->isAdmin()) {
            return $this->error('Forbidden.', 403);
        }

        $students = $query
            ->when($request->filled('classroom_id'), fn ($inner) => $inner->whereHas(
                'studentEnrollments',
                fn ($enrollment) => $enrollment->where('classroom_id', $request->integer('classroom_id')),
            ))
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = trim((string) $request->input('keyword'));

                $query->where(function ($inner) use ($keyword): void {
                    $inner->where('full_name', 'like', "%{$keyword}%")
                        ->orWhere('student_code', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('full_name')
            ->get()
            ->map(fn (StudentProfile $student): array => $this->serialize(
                $student,
                limited: $user->isTeacher() || $user->isAssistant(),
            ));

        return $this->success('Students retrieved.', $students->all());
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if (! $user || (! $user->isAdmin() && ! $user->isTeacher())) {
            return $this->error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'student_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('student_profiles', 'student_email'),
            ],
            'parent_email' => ['required', 'email', 'max:255'],
            'high_school_name' => ['required', 'string', 'max:255'],
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
            'cccd' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'student_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'parent_full_name' => ['nullable', 'string', 'max:255'],
            'parent_relation' => ['nullable', 'string', 'max:50'],
        ]);

        $student = DB::transaction(function () use ($validated, $user): StudentProfile {
            $classroom = Classroom::query()
                ->with('course')
                ->findOrFail($validated['classroom_id']);

            if ($user->isTeacher() && (int) $classroom->teacher_id !== (int) $user->id) {
                abort(403, 'Forbidden.');
            }

            if (! $classroom->course) {
                abort(422, 'Lop hoc chua gan khoa hoc.');
            }

            $studentCode = $this->generateStudentCodeForGrade((int) $classroom->course->grade_level);
            $studentUser = User::query()->create([
                'name' => trim($validated['full_name']),
                'email' => mb_strtolower(trim($validated['student_email'])),
                'username' => $studentCode,
                'password' => Hash::make($studentCode),
                'role' => User::ROLE_STUDENT,
                'is_active' => true,
            ]);

            $student = StudentProfile::query()->create([
                'user_id' => $studentUser->id,
                'student_code' => $studentCode,
                'full_name' => trim($validated['full_name']),
                'student_email' => mb_strtolower(trim($validated['student_email'])),
                'parent_email' => mb_strtolower(trim($validated['parent_email'])),
                'high_school_name' => trim($validated['high_school_name']),
                'cccd' => $this->blankToNull($validated['cccd'] ?? null),
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'student_phone' => $this->blankToNull($validated['student_phone'] ?? null),
                'address' => $this->blankToNull($validated['address'] ?? null),
                'parent_phone' => $this->blankToNull($validated['parent_phone'] ?? null),
                'parent_full_name' => $this->blankToNull($validated['parent_full_name'] ?? null),
                'parent_relation' => $this->blankToNull($validated['parent_relation'] ?? null),
            ]);

            StudentEnrollment::query()->create([
                'student_id' => $student->id,
                'course_id' => $classroom->course_id,
                'classroom_id' => $classroom->id,
                'status' => StudentEnrollment::STATUS_ACTIVE,
                'enrolled_at' => now(),
                'ended_at' => null,
            ]);

            return $student->load([
                'studentEnrollments' => fn ($inner) => $inner->latest('enrolled_at'),
                'studentEnrollments.classroom.course',
                'studentEnrollments.course',
            ]);
        });

        return $this->success('Student created.', [
            ...$this->serialize($student),
            'account' => [
                'email' => $student->student_email,
                'default_password' => $student->student_code,
                'login_path' => '/dang-nhap',
            ],
        ], 201);
    }

    public function show(StudentProfile $student): JsonResponse
    {
        $request = request();
        $user = $request->attributes->get('auth_user');

        if (! $this->canAccessStudent($request, $student)) {
            return $this->error('Forbidden.', 403);
        }

        return $this->success('Student retrieved.', $this->serialize(
            $student->load([
                'studentEnrollments' => fn ($inner) => $inner->latest('enrolled_at'),
                'studentEnrollments.classroom.course',
                'studentEnrollments.course',
            ]),
            limited: !$user?->isAdmin(),
        ));
    }

    public function update(Request $request, StudentProfile $student): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if (! $user || ! $this->canManageStudent($request, $student)) {
            return $this->error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'student_email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('student_profiles', 'student_email')->ignore($student->id),
                Rule::unique('users', 'email')->ignore($student->user_id),
            ],
            'parent_email' => ['sometimes', 'email', 'max:255'],
            'high_school_name' => ['sometimes', 'string', 'max:255'],
            'cccd' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'student_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'parent_full_name' => ['nullable', 'string', 'max:255'],
            'parent_relation' => ['nullable', 'string', 'max:50'],
        ]);

        $student->update([
            'full_name' => trim($validated['full_name']),
            'student_email' => isset($validated['student_email']) ? mb_strtolower(trim($validated['student_email'])) : $student->student_email,
            'parent_email' => isset($validated['parent_email']) ? mb_strtolower(trim($validated['parent_email'])) : $student->parent_email,
            'high_school_name' => isset($validated['high_school_name']) ? trim($validated['high_school_name']) : $student->high_school_name,
            'cccd' => $this->blankToNull($validated['cccd'] ?? $student->cccd),
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'student_phone' => $this->blankToNull($validated['student_phone'] ?? $student->student_phone),
            'address' => $this->blankToNull($validated['address'] ?? $student->address),
            'parent_phone' => $this->blankToNull($validated['parent_phone'] ?? $student->parent_phone),
            'parent_full_name' => $this->blankToNull($validated['parent_full_name'] ?? $student->parent_full_name),
            'parent_relation' => $this->blankToNull($validated['parent_relation'] ?? $student->parent_relation),
        ]);

        if (array_key_exists('student_email', $validated)) {
            $student->user?->update([
                'email' => mb_strtolower(trim($validated['student_email'])),
                'name' => trim($validated['full_name']),
            ]);
        } else {
            $student->user?->update(['name' => trim($validated['full_name'])]);
        }

        return $this->success('Student updated.', $this->serialize($student->refresh()->load([
            'studentEnrollments' => fn ($inner) => $inner->latest('enrolled_at'),
            'studentEnrollments.classroom.course',
            'studentEnrollments.course',
        ])));
    }

    public function import(Request $request): JsonResponse
    {
        if (! $request->attributes->get('auth_user')?->isAdmin()) {
            return $this->error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,pdf,doc,docx,xls,xlsx,ppt,jpg,jpeg,png,zip,txt'],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
        ]);

        $defaultClassroomId = $validated['classroom_id'] ?? null;
        $rows = $this->readImportRows($request->file('file')->getRealPath(), $request->file('file')->getClientOriginalExtension());
        $classroomList = Classroom::query()->get();
        $classrooms = $classroomList->keyBy(fn(Classroom $classroom): string => mb_strtolower(trim($classroom->name)));
        $classroomsById = $classroomList->keyBy('id');
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $fullName = trim((string) ($row['full_name'] ?? ''));
            $className = mb_strtolower(trim((string) ($row['class_name'] ?? '')));
            $classroomId = $defaultClassroomId;
            $classroom = $classroomId ? ($classroomsById[(int) $classroomId] ?? null) : null;

            if ($className !== '') {
                $classroom = $classrooms[$className] ?? null;
                $classroomId = $classroom?->id;
            }

            if ($fullName === '' || ! $classroom) {
                $skipped++;
                $errors[] = "Dòng {$line}: thiếu họ tên hoặc lớp không tồn tại.";
                continue;
            }

            DB::transaction(function () use ($classroom, $fullName, $row): void {
                Student::query()->create([
                    'classroom_id' => $classroom->id,
                    'student_code' => $this->generateStudentCode($classroom),
                    'full_name' => $fullName,
                    'date_of_birth' => $this->normalizeDate($row['date_of_birth'] ?? null),
                    'phone' => trim((string) ($row['student_phone'] ?? '')) ?: null,
                    'avatar' => trim((string) ($row['avatar'] ?? '')) ?: null,
                    'address' => trim((string) ($row['address'] ?? '')) ?: null,
                    'status' => trim((string) ($row['status'] ?? '')) ?: 'studying',
                ]);
            });

            $created++;
        }

        return $this->success('Student import completed.', [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 20),
        ], 201);
    }

    private function serialize(StudentProfile $student, bool $limited = false): array
    {
        $student->loadMissing([
            'studentEnrollments' => fn ($inner) => $inner->latest('enrolled_at'),
            'studentEnrollments.classroom.course',
            'studentEnrollments.course',
        ]);

        $enrollment = $student->studentEnrollments
            ->firstWhere('status', StudentEnrollment::STATUS_ACTIVE)
            ?? $student->studentEnrollments->first();
        $classroom = $enrollment?->classroom;
        $course = $enrollment?->course ?? $classroom?->course;

        $base = [
            'id' => $student->id,
            'student_code' => $student->student_code,
            'full_name' => $student->full_name,
            'high_school_name' => $student->high_school_name,
        ];

        if ($limited) {
            return $base;
        }

        return [
            ...$base,
            'user_id' => $student->user_id,
            'student_email' => $student->student_email,
            'parent_email' => $student->parent_email,
            'classroom_id' => $classroom?->id,
            'class_name' => $classroom?->name,
            'course_id' => $course?->id,
            'course_name' => $course?->name,
            'grade_level' => $course?->grade_level,
            'cccd' => $student->cccd,
            'date_of_birth' => $student->date_of_birth?->toDateString(),
            'phone' => $student->student_phone,
            'student_phone' => $student->student_phone,
            'avatar' => null,
            'avatar_url' => null,
            'address' => $student->address,
            'parent_phone' => $student->parent_phone,
            'parent_full_name' => $student->parent_full_name,
            'parent_relation' => $student->parent_relation,
            'status' => $enrollment?->status === StudentEnrollment::STATUS_ACTIVE ? 'studying' : ($enrollment?->status ?? 'inactive'),
        ];
    }

    private function success(string $message, array $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function canAccessStudent(Request $request, StudentProfile $student): bool
    {
        $user = $request->attributes->get('auth_user');

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return (int) $student->user_id === (int) $user->id;
        }

        if ($user->isTeacher() || $user->isAssistant()) {
            return $student->studentEnrollments()
                ->whereIn('classroom_id', $this->assignedClassroomIds($user->id))
                ->exists();
        }

        return false;
    }

    private function canManageStudent(Request $request, StudentProfile $student): bool
    {
        $user = $request->attributes->get('auth_user');

        if ($user?->isAdmin()) {
            return true;
        }

        if (! $user?->isTeacher()) {
            return false;
        }

        return $student->studentEnrollments()
            ->whereIn('classroom_id', $this->assignedClassroomIds($user->id))
            ->exists();
    }

    private function assignedClassroomIds(int $userId): array
    {
        $user = User::query()->find($userId);

        return $user ? AccessControl::assignedClassroomIds($user) : [];
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => null,
        ], $status);
    }

    private function resolveClassroomForManualCreate(array $validated): Classroom
    {
        if (! empty($validated['classroom_id'])) {
            $classroom = Classroom::query()->findOrFail($validated['classroom_id']);

            if ((int) $classroom->grade_level !== (int) $validated['grade_level']) {
                abort(422, 'Lớp không thuộc khối đã chọn.');
            }

            return $classroom;
        }

        $classroom = Classroom::query()
            ->where('grade_level', (int) $validated['grade_level'])
            ->where('is_active', true)
            ->orderBy('name')
            ->first();

        if (! $classroom) {
            abort(422, 'Không tìm thấy lớp đang hoạt động cho khối đã chọn.');
        }

        return $classroom;
    }

    private function ensureParentAccount(Student $student): ParentProfile
    {
        return DB::transaction(function () use ($student): ParentProfile {
            $studentCode = $student->student_code;
            $fallbackEmail = strtolower($studentCode) . '@parent.ptconnect.test';
            $parent = $student->parents()->with('user')->first();
            $parentUser = $parent?->user;

            if (! $parentUser) {
                $parentUser = User::query()->firstOrNew(['username' => $studentCode]);
            }

            $parentUser->fill([
                'username' => $studentCode,
                'email' => $parentUser->email ?: $parent?->email ?: $fallbackEmail,
                'password' => Hash::make($studentCode),
                'role' => User::ROLE_STUDENT,
                'is_active' => true,
            ]);
            $parentUser->save();

            if (! $parent) {
                $parent = new ParentProfile([
                    'student_id' => $student->id,
                    'full_name' => 'Phụ huynh ' . $student->full_name,
                    'email' => $parentUser->email ?: $fallbackEmail,
                    'phone' => $student->phone,
                    'relationship' => 'parent',
                    'address' => $student->address,
                ]);
            }

            $parent->fill([
                'user_id' => $parentUser->id,
                'student_id' => $student->id,
                'email' => $parent->email ?: $parentUser->email ?: $fallbackEmail,
                'phone' => $parent->phone ?: $student->phone,
                'address' => $parent->address ?: $student->address,
            ]);
            $parent->save();

            return $parent->refresh();
        });
    }

    private function generateStudentCode(Classroom $classroom): string
    {
        $classroom->loadMissing('course');

        return $this->generateStudentCodeForGrade((int) $classroom->course?->grade_level);
    }

    private function generateStudentCodeForGrade(int $gradeLevel): string
    {
        $prefix = 'HS' . $gradeLevel;
        $usedSequences = Student::query()
            ->where('student_code', 'like', "{$prefix}%")
            ->pluck('student_code')
            ->merge(
                StudentProfile::query()
                    ->where('student_code', 'like', "{$prefix}%")
                    ->pluck('student_code')
            )
            ->merge(
                User::query()
                    ->where('username', 'like', "{$prefix}%")
                    ->pluck('username')
            )
            ->reduce(function (array $used, string $code) use ($prefix): array {
                if (preg_match('/^' . preg_quote($prefix, '/') . '(\d{4})$/', $code, $matches)) {
                    $used[(int) $matches[1]] = true;
                }

                return $used;
            }, []);

        for ($sequence = 1; $sequence <= 9999; $sequence++) {
            if (! isset($usedSequences[$sequence])) {
                $studentCode = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

                if (! Student::query()->where('student_code', $studentCode)->exists()) {
                    if (! StudentProfile::query()->where('student_code', $studentCode)->exists()) {
                        if (! User::query()->where('username', $studentCode)->exists()) {
                            return $studentCode;
                        }
                    }
                }
            }
        }

        abort(422, 'Không thể tạo mã học sinh mới cho khối này.');
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function readImportRows(string $path, string $extension): array
    {
        if (strtolower($extension) === 'xlsx') {
            return $this->readXlsxRows($path);
        }

        return $this->readCsvRows($path);
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            return [];
        }

        $headers = null;
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = $this->normalizeHeaders($data);
                continue;
            }

            $rows[] = $this->combineRow($headers, $data);
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $sheetXml) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        $rawRows = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];

            foreach ($row->c as $cell) {
                $attributes = $cell->attributes();
                $reference = (string) $attributes['r'];
                $type = (string) $attributes['t'];
                $columnIndex = $this->columnIndexFromReference($reference);
                $value = (string) ($cell->v ?? '');

                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                }

                $cells[$columnIndex] = trim($value);
            }

            if ($cells !== []) {
                ksort($cells);
                $rawRows[] = $cells;
            }
        }

        if ($rawRows === []) {
            return [];
        }

        $headers = $this->normalizeHeaders(array_values($rawRows[0]));
        $rows = [];

        foreach (array_slice($rawRows, 1) as $rawRow) {
            $data = [];

            for ($index = 0; $index < count($headers); $index++) {
                $data[$index] = $rawRow[$index] ?? null;
            }

            $rows[] = $this->combineRow($headers, $data);
        }

        return $rows;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if (! $xml) {
            return [];
        }

        $sharedStrings = [];
        $strings = simplexml_load_string($xml);

        foreach ($strings->si as $item) {
            if (isset($item->t)) {
                $sharedStrings[] = (string) $item->t;
                continue;
            }

            $text = '';

            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }

            $sharedStrings[] = $text;
        }

        return $sharedStrings;
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(fn($header): string => $this->mapImportHeader((string) $header), $headers);
    }

    private function mapImportHeader(string $header): string
    {
        $normalized = mb_strtolower(trim($header));
        $normalized = str_replace([' ', '-', '.'], '_', $normalized);

        return match ($normalized) {
            'ma_hoc_sinh', 'mã_học_sinh', 'student_code', 'code' => 'student_code',
            'ho_ten', 'họ_và_tên', 'họ_tên', 'full_name', 'name' => 'full_name',
            'lop', 'lớp', 'class', 'class_name', 'ten_lop', 'tên_lớp' => 'class_name',
            'ngay_sinh', 'ngày_sinh', 'date_of_birth', 'dob' => 'date_of_birth',
            'sdt', 'sđt', 'so_dt', 'số_đt', 'sdt_hoc_sinh', 'sđt_học_sinh',
            'so_dien_thoai', 'số_điện_thoại', 'dien_thoai', 'điện_thoại',
            'student_phone', 'phone' => 'student_phone',
            'anh_dai_dien', 'ảnh_đại_diện', 'avatar' => 'avatar',
            'dia_chi', 'địa_chỉ', 'address' => 'address',
            'trang_thai', 'trạng_thái', 'status' => 'status',
            default => $normalized,
        };
    }

    private function combineRow(array $headers, array $data): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $row[$header] = $data[$index] ?? null;
        }

        return $row;
    }

    private function columnIndexFromReference(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference));
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return CarbonImmutable::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
