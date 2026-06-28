<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class V1DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@ptconnect.test'],
            [
                'name' => 'PTConnect Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        $teacher = User::query()->updateOrCreate(
            ['email' => 'teacher@ptconnect.test'],
            [
                'name' => 'Sample Teacher',
                'username' => 'teacher',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'is_active' => true,
            ],
        );

        $assistant = User::query()->updateOrCreate(
            ['email' => 'assistant@ptconnect.test'],
            [
                'name' => 'Sample Assistant',
                'username' => 'assistant',
                'password' => Hash::make('password'),
                'role' => 'assistant',
                'is_active' => true,
            ],
        );

        $academicYear = AcademicYear::query()->updateOrCreate(
            ['name' => '2025-2026'],
            [
                'start_date' => '2025-08-01',
                'end_date' => '2026-05-31',
                'is_active' => true,
            ],
        );

        $classrooms = collect([
            ['name' => '10A1', 'grade_level' => 10],
            ['name' => '11A1', 'grade_level' => 11],
            ['name' => '12A1', 'grade_level' => 12],
        ])->mapWithKeys(function (array $classroom) use ($academicYear, $teacher, $assistant) {
            $model = Classroom::query()->updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'name' => $classroom['name'],
                ],
                [
                    'grade_level' => $classroom['grade_level'],
                    'description' => 'Sample class for grade '.$classroom['grade_level'],
                    'is_active' => true,
                ],
            );

            $model->users()->syncWithoutDetaching([
                $teacher->id => ['role_in_class' => 'teacher'],
                $assistant->id => ['role_in_class' => 'assistant'],
            ]);

            return [$classroom['name'] => $model];
        });

        collect([
            ['name' => 'Math', 'code' => 'MATH'],
            ['name' => 'Physics', 'code' => 'PHYSICS'],
            ['name' => 'Chemistry', 'code' => 'CHEM'],
            ['name' => 'English', 'code' => 'ENG'],
        ])->each(fn (array $subject) => Subject::query()->updateOrCreate(
            ['code' => $subject['code']],
            [
                'name' => $subject['name'],
                'description' => null,
                'is_active' => true,
            ],
        ));

        collect([
            [
                'classroom' => '10A1',
                'student_code' => 'HS100001',
                'full_name' => 'Minh Tran',
                'gender' => 'male',
                'parent_name' => 'Tran Parent',
                'parent_email' => 'parent.hs100001@ptconnect.test',
                'relationship' => 'father',
            ],
            [
                'classroom' => '11A1',
                'student_code' => 'HS110001',
                'full_name' => 'Lan Nguyen',
                'gender' => 'female',
                'parent_name' => 'Nguyen Parent',
                'parent_email' => 'parent.hs110001@ptconnect.test',
                'relationship' => 'mother',
            ],
            [
                'classroom' => '12A1',
                'student_code' => 'HS120001',
                'full_name' => 'An Le',
                'gender' => 'other',
                'parent_name' => 'Le Parent',
                'parent_email' => 'parent.hs120001@ptconnect.test',
                'relationship' => 'guardian',
            ],
        ])->each(function (array $data) use ($classrooms) {
            $student = Student::query()->updateOrCreate(
                ['student_code' => $data['student_code']],
                [
                    'classroom_id' => $classrooms[$data['classroom']]->id,
                    'full_name' => $data['full_name'],
                    'gender' => $data['gender'],
                    'status' => 'studying',
                ],
            );

            $parentUser = User::query()->updateOrCreate(
                ['username' => $data['student_code']],
                [
                    'name' => $data['parent_name'],
                    'email' => $data['parent_email'],
                    'password' => Hash::make('12345678'),
                    'role' => 'parent',
                    'is_active' => true,
                ],
            );

            $parent = ParentProfile::query()->updateOrCreate(
                ['user_id' => $parentUser->id],
                [
                    'full_name' => $data['parent_name'],
                    'email' => $data['parent_email'],
                    'relationship' => $data['relationship'],
                ],
            );

            $student->parents()->syncWithoutDetaching([
                $parent->id => ['is_primary' => true],
            ]);
        });
    }
}
