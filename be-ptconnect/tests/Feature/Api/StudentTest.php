<?php

namespace Tests\Feature\Api;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentTest extends TestCase
{
    private User $admin;
    private User $teacher;
    private User $parent;
    private User $parentUser;
    private Classroom $classroom;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);

        $academicYear = AcademicYear::create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-05-31', 'is_active' => true,
        ]);

        $this->classroom = Classroom::create([
            'academic_year_id' => $academicYear->id, 'name' => '10A1', 'grade_level' => 10, 'is_active' => true,
        ]);

        $this->student = Student::create([
            'classroom_id' => $this->classroom->id, 'student_code' => 'HS100001', 'full_name' => 'Nguyen Van A',
            'status' => 'studying',
        ]);

        $this->parentUser = User::factory()->create(['role' => 'parent']);
        \App\Models\ParentProfile::create([
            'user_id' => $this->parentUser->id, 'student_id' => $this->student->id,
            'full_name' => 'Phu huynh A', 'email' => 'parent@test.com', 'relationship' => 'father',
        ]);
        $this->parent = $this->parentUser;
    }

    private function authHeader(User $user): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'password',
        ]);

        return ['Authorization' => 'Bearer ' . $response->json('data.access_token')];
    }

    public function test_admin_can_list_students(): void
    {
        $response = $this->getJson('/api/students', $this->authHeader($this->admin));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    public function test_student_list_supports_search(): void
    {
        Student::create([
            'classroom_id' => $this->classroom->id, 'student_code' => 'HS002', 'full_name' => 'Tran Van B',
            'status' => 'studying',
        ]);

        $response = $this->getJson('/api/students?keyword=Van+A', $this->authHeader($this->admin));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_student_list_filters_by_classroom(): void
    {
        Student::create([
            'classroom_id' => $this->classroom->id, 'student_code' => 'HS002', 'full_name' => 'Tran Van B',
            'status' => 'studying',
        ]);

        $response = $this->getJson('/api/students?classroom_id=' . $this->classroom->id, $this->authHeader($this->admin));

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_parent_sees_only_own_children(): void
    {
        $response = $this->getJson('/api/students', $this->authHeader($this->parent));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Nguyen Van A', $response->json('data.0.full_name'));
    }

    public function test_admin_can_create_student(): void
    {
        $response = $this->postJson('/api/students', [
            'username' => 'HS100002',
            'grade_level' => 10,
            'classroom_id' => $this->classroom->id,
        ], $this->authHeader($this->admin));

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Student created.'])
            ->assertJsonStructure(['data' => ['id', 'student_code', 'full_name', 'parent_account']])
            ->assertJsonPath('data.student_code', 'HS100002')
            ->assertJsonPath('data.full_name', 'HS100002')
            ->assertJsonPath('data.parent_account.username', 'HS100002')
            ->assertJsonPath('data.parent_account.password', 'HS100002');

        $this->assertDatabaseHas('students', ['student_code' => 'HS100002', 'full_name' => 'HS100002']);
        $this->assertDatabaseHas('users', ['username' => 'HS100002', 'role' => 'parent']);
        $this->assertTrue(Hash::check('HS100002', User::query()->where('username', 'HS100002')->first()->password));
    }

    public function test_non_admin_cannot_create_student(): void
    {
        $response = $this->postJson('/api/students', [
            'username' => 'HS100002',
            'grade_level' => 10,
            'classroom_id' => $this->classroom->id,
        ], $this->authHeader($this->teacher));

        $response->assertStatus(403);
    }

    public function test_create_student_validates_required_fields(): void
    {
        $response = $this->postJson('/api/students', [], $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_create_student_uses_username_as_student_code(): void
    {
        $response = $this->postJson('/api/students', [
            'username' => 'HS100002',
            'grade_level' => 10,
            'classroom_id' => $this->classroom->id,
            'student_code' => 'CLIENT_SHOULD_NOT_DECIDE',
        ], $this->authHeader($this->admin));

        $response->assertStatus(201)
            ->assertJsonPath('data.student_code', 'HS100002');

        $this->assertDatabaseMissing('students', ['student_code' => 'CLIENT_SHOULD_NOT_DECIDE']);
    }

    public function test_parent_can_update_own_child_profile(): void
    {
        $response = $this->putJson('/api/students/' . $this->student->id, [
            'full_name' => 'Nguyen Van A Updated',
            'gender' => 'male',
            'phone' => '0909000001',
            'date_of_birth' => '2010-01-15',
            'address' => 'Thu Duc',
        ], $this->authHeader($this->parent));

        $response->assertStatus(200)
            ->assertJsonPath('data.full_name', 'Nguyen Van A Updated')
            ->assertJsonPath('data.gender', 'male')
            ->assertJsonPath('data.phone', '0909000001');

        $this->assertDatabaseHas('students', [
            'id' => $this->student->id,
            'full_name' => 'Nguyen Van A Updated',
            'phone' => '0909000001',
        ]);
    }

    public function test_parent_cannot_update_unrelated_student(): void
    {
        $otherStudent = Student::create([
            'classroom_id' => $this->classroom->id, 'student_code' => 'HS999', 'full_name' => 'Xa Lac',
            'status' => 'studying',
        ]);

        $response = $this->putJson('/api/students/' . $otherStudent->id, [
            'full_name' => 'Bad Update',
        ], $this->authHeader($this->parent));

        $response->assertStatus(403);
    }

    public function test_admin_can_show_student(): void
    {
        $response = $this->getJson('/api/students/' . $this->student->id, $this->authHeader($this->admin));

        $response->assertStatus(200)
            ->assertJsonPath('data.full_name', 'Nguyen Van A');
    }

    public function test_parent_can_show_own_child(): void
    {
        $response = $this->getJson('/api/students/' . $this->student->id, $this->authHeader($this->parent));

        $response->assertStatus(200)
            ->assertJsonPath('data.full_name', 'Nguyen Van A');
    }

    public function test_parent_cannot_show_unrelated_student(): void
    {
        $otherStudent = Student::create([
            'classroom_id' => $this->classroom->id, 'student_code' => 'HS999', 'full_name' => 'Xa Lac',
            'status' => 'studying',
        ]);

        $response = $this->getJson('/api/students/' . $otherStudent->id, $this->authHeader($this->parent));

        $response->assertStatus(403);
    }

    public function test_admin_can_import_students_from_csv(): void
    {
        Storage::fake('local');
        $csvContent = "ho_ten,lop,Số điện thoại\nNguyen Van Import,{$this->classroom->name},0909123456";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $response = $this->postJson('/api/students/import', [
            'file' => $file,
        ], $this->authHeader($this->admin));

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('students', [
            'student_code' => 'HS100002',
            'full_name' => 'Nguyen Van Import',
            'phone' => '0909123456',
        ]);
    }

    public function test_non_admin_cannot_import_students(): void
    {
        $file = UploadedFile::fake()->create('import.csv');
        $response = $this->postJson('/api/students/import', [
            'file' => $file,
        ], $this->authHeader($this->teacher));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_students(): void
    {
        $response = $this->getJson('/api/students');
        $response->assertStatus(401);
    }
}
