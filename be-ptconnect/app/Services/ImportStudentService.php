<?php

namespace App\Services;

use App\Repositories\ClassroomRepository;
use App\Repositories\StudentEnrollmentRepository;
use App\Repositories\StudentProfileRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportStudentService
{
    public function __construct(
        protected UserRepository $userRepo,
        protected StudentProfileRepository $studentRepo,
        protected ClassroomRepository $classroomRepo,
        protected StudentEnrollmentRepository $enrollmentRepo,
    ) {}

    public function createSingle(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $classroom = $this->classroomRepo->findOrFail($data['classroom_id']);

            $user = $this->userRepo->create([
                'email' => $data['student_email'],
                'password' => Hash::make('12345678'),
                'role' => 'student',
                'is_active' => true,
            ]);

            $studentCode = $this->generateStudentCode($classroom->grade_level);

            $student = $this->studentRepo->create([
                'user_id' => $user->id,
                'student_code' => $studentCode,
                'full_name' => $data['full_name'],
                'email' => $data['student_email'],
                'parent_email' => $data['parent_email'],
                'high_school' => $data['high_school_name'],
                'cccd' => $data['cccd'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'phone' => $data['student_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'parent_phone' => $data['parent_phone'] ?? null,
                'parent_name' => $data['parent_full_name'] ?? null,
                'parent_relationship' => $data['parent_relation'] ?? null,
            ]);

            $this->enrollmentRepo->create([
                'student_id' => $student->id,
                'course_id' => $classroom->course_id,
                'classroom_id' => $classroom->id,
                'status' => 'active',
                'enrolled_at' => now(),
            ]);

            return [
                'data' => [
                    'id' => $student->id,
                    'student_code' => $student->student_code,
                    'full_name' => $student->full_name,
                    'user_id' => $user->id,
                ],
            ];
        });
    }

    public function importFromFile($file, ?int $defaultClassroomId): array
    {
        $errors = [];
        $created = 0;
        $skipped = 0;

        $rows = $this->parseFile($file);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $validation = $this->validateRow($row, $rowNumber);
            if (!empty($validation)) {
                $errors = array_merge($errors, $validation);
                $skipped++;
                continue;
            }

            $classroomId = $defaultClassroomId;
            if (empty($classroomId) && !empty($row['class_name'])) {
                $classroom = $this->classroomRepo->findByName($row['class_name']);
                if (!$classroom) {
                    $errors[] = "Dòng {$rowNumber}: Lớp {$row['class_name']} không tồn tại.";
                    $skipped++;
                    continue;
                }
                $classroomId = $classroom->id;
            }

            if (empty($classroomId)) {
                $errors[] = "Dòng {$rowNumber}: Không xác định được lớp học.";
                $skipped++;
                continue;
            }

            $classroom = $this->classroomRepo->find($classroomId);
            if (!$classroom) {
                $errors[] = "Dòng {$rowNumber}: Lớp ID {$classroomId} không tồn tại.";
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use ($row, $classroom) {
                    $user = $this->userRepo->create([
                        'email' => $row['student_email'],
                        'password' => Hash::make('12345678'),
                        'role' => 'student',
                        'is_active' => true,
                    ]);

                    $studentCode = $this->generateStudentCode($classroom->grade_level);

                    $student = $this->studentRepo->create([
                        'user_id' => $user->id,
                        'student_code' => $studentCode,
                        'full_name' => $row['full_name'],
                        'email' => $row['student_email'],
                        'parent_email' => $row['parent_email'],
                        'high_school' => $row['high_school'],
                        'cccd' => $row['cccd'] ?? null,
                        'date_of_birth' => $row['date_of_birth'] ?? null,
                        'phone' => $row['student_phone'] ?? null,
                        'address' => $row['address'] ?? null,
                        'parent_phone' => $row['parent_phone'] ?? null,
                        'parent_name' => $row['parent_name'] ?? null,
                        'parent_relationship' => $row['parent_relationship'] ?? null,
                    ]);

                    $this->enrollmentRepo->create([
                        'student_id' => $student->id,
                        'course_id' => $classroom->course_id,
                        'classroom_id' => $classroom->id,
                        'status' => 'active',
                        'enrolled_at' => now(),
                    ]);
                });

                $created++;
            } catch (\Exception $e) {
                $errors[] = "Dòng {$rowNumber}: Lỗi hệ thống - {$e->getMessage()}";
                $skipped++;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    public function generateStudentCode(int $gradeLevel): string
    {
        $prefix = 'HS' . $gradeLevel;

        $lastStudent = $this->studentRepo->getLastStudentCode($prefix);

        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->student_code, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    private function parseFile($file): array
    {
        $rows = [];

        if ($file->getClientOriginalExtension() === 'csv') {
            $handle = fopen($file->getPathname(), 'r');
            $headers = fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = array_combine($headers, $data);
            }

            fclose($handle);
        } else {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            if (count($data) > 0) {
                $headers = array_shift($data);
                foreach ($data as $row) {
                    $rows[] = array_combine($headers, $row);
                }
            }
        }

        return $rows;
    }

    private function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        if (empty($row['full_name'] ?? $row['Họ tên HS'] ?? '')) {
            $errors[] = "Dòng {$rowNumber}: Thiếu họ tên học sinh.";
        }

        $studentEmail = $row['student_email'] ?? $row['Email HS'] ?? '';
        if (empty($studentEmail)) {
            $errors[] = "Dòng {$rowNumber}: Thiếu email học sinh.";
        } elseif (!filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Dòng {$rowNumber}: Email học sinh không đúng định dạng.";
        } elseif ($this->userRepo->emailExists($studentEmail)) {
            $errors[] = "Dòng {$rowNumber}: Email học sinh {$studentEmail} đã tồn tại.";
        }

        $parentEmail = $row['parent_email'] ?? $row['Email PH'] ?? '';
        if (empty($parentEmail)) {
            $errors[] = "Dòng {$rowNumber}: Thiếu email phụ huynh.";
        } elseif (!filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Dòng {$rowNumber}: Email phụ huynh không đúng định dạng.";
        }

        $highSchool = $row['high_school'] ?? $row['Trường C3'] ?? '';
        if (empty($highSchool)) {
            $errors[] = "Dòng {$rowNumber}: Thiếu trường cấp 3.";
        }

        return $errors;
    }
}
