<?php

namespace Tests\Feature\Api;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    private User $admin;
    private User $teacher;
    private User $parentUser;
    private Classroom $classroom;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);

        $academicYear = AcademicYear::create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-05-31', 'is_active' => true,
        ]);

        $this->classroom = Classroom::create([
            'academic_year_id' => $academicYear->id, 'name' => '10A1', 'grade_level' => 10, 'is_active' => true,
        ]);
        $this->classroom->users()->attach($this->teacher->id, ['role_in_class' => 'teacher']);

        $this->student = Student::create([
            'classroom_id' => $this->classroom->id, 'student_code' => 'HS001', 'full_name' => 'Nguyen Van A',
            'status' => 'studying',
        ]);

        $this->parentUser = User::factory()->create(['role' => 'parent']);
        ParentProfile::create([
            'user_id' => $this->parentUser->id, 'student_id' => $this->student->id,
            'full_name' => 'Phu huynh A', 'email' => 'parent@test.com', 'relationship' => 'father',
        ]);
    }

    private function authHeader(User $user): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'password',
        ]);

        return ['Authorization' => 'Bearer ' . $response->json('data.access_token')];
    }

    public function test_admin_can_list_assignments(): void
    {
        Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap 1', 'status' => 'published',
        ]);

        $response = $this->getJson('/api/assignments', $this->authHeader($this->admin));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    public function test_teacher_sees_only_assigned_class_assignments(): void
    {
        Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap 1', 'classroom_id' => $this->classroom->id, 'status' => 'published',
        ]);
        $otherClassroom = Classroom::create([
            'academic_year_id' => $this->classroom->academic_year_id, 'name' => '11A1', 'grade_level' => 11, 'is_active' => true,
        ]);
        Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap khac', 'classroom_id' => $otherClassroom->id, 'status' => 'published',
        ]);

        $response = $this->getJson('/api/assignments', $this->authHeader($this->teacher));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_parent_sees_published_assignments(): void
    {
        Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap 1', 'classroom_id' => $this->classroom->id, 'status' => 'published',
        ]);

        $response = $this->getJson('/api/assignments', $this->authHeader($this->parentUser));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_parent_does_not_see_draft_assignments(): void
    {
        Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap draft', 'classroom_id' => $this->classroom->id, 'status' => 'draft',
        ]);

        $response = $this->getJson('/api/assignments', $this->authHeader($this->parentUser));

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_admin_can_create_assignment(): void
    {
        $response = $this->postJson('/api/assignments', [
            'title' => 'Bai tap moi',
            'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(),
        ], $this->authHeader($this->admin));

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Assignment created.']);
    }

    public function test_teacher_can_create_assignment_for_assigned_class(): void
    {
        $response = $this->postJson('/api/assignments', [
            'title' => 'Bai tap cho lop 10A1',
            'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(),
        ], $this->authHeader($this->teacher));

        $response->assertStatus(201);
    }

    public function test_teacher_cannot_create_assignment_for_unassigned_class(): void
    {
        $otherClassroom = Classroom::create([
            'academic_year_id' => $this->classroom->academic_year_id, 'name' => '11A1', 'grade_level' => 11, 'is_active' => true,
        ]);

        $response = $this->postJson('/api/assignments', [
            'title' => 'Bai tap',
            'classroom_id' => $otherClassroom->id,
        ], $this->authHeader($this->teacher));

        $response->assertStatus(403);
    }

    public function test_create_assignment_requires_title(): void
    {
        $response = $this->postJson('/api/assignments', [], $this->authHeader($this->admin));
        $response->assertStatus(422);
    }

    public function test_create_assignment_requires_classroom_or_grade(): void
    {
        $response = $this->postJson('/api/assignments', [
            'title' => 'No target',
        ], $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_parent_can_submit_assignment_pdf(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap 1', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);

        $file = UploadedFile::fake()->create('bai_tap.pdf', 100);

        $response = $this->postJson("/api/assignments/{$assignment->id}/submissions", [
            'student_id' => $this->student->id,
            'submitted_file' => $file,
        ], $this->authHeader($this->parentUser));

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Submission uploaded.']);
    }

    public function test_parent_can_submit_assignment_doc(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap 2', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);

        $file = UploadedFile::fake()->create('bai_tap.doc', 100);

        $response = $this->postJson("/api/assignments/{$assignment->id}/submissions", [
            'student_id' => $this->student->id,
            'submitted_file' => $file,
        ], $this->authHeader($this->parentUser));

        $response->assertStatus(201);
    }

    public function test_parent_can_submit_assignment_docx(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap 3', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);

        $file = UploadedFile::fake()->create('bai_tap.docx', 100);

        $response = $this->postJson("/api/assignments/{$assignment->id}/submissions", [
            'student_id' => $this->student->id,
            'submitted_file' => $file,
        ], $this->authHeader($this->parentUser));

        $response->assertStatus(201);
    }

    public function test_non_parent_cannot_submit_assignment(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap', 'status' => 'published',
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this->postJson("/api/assignments/{$assignment->id}/submissions", [
            'student_id' => $this->student->id,
            'submitted_file' => $file,
        ], $this->authHeader($this->teacher));

        $response->assertStatus(403);
    }

    public function test_cannot_submit_to_overdue_assignment(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap qua han', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->subDays(1)->toDateString(), 'status' => 'published',
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this->postJson("/api/assignments/{$assignment->id}/submissions", [
            'student_id' => $this->student->id,
            'submitted_file' => $file,
        ], $this->authHeader($this->parentUser));

        $response->assertStatus(422);
    }

    public function test_submission_requires_file(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);

        $response = $this->postJson("/api/assignments/{$assignment->id}/submissions", [
            'student_id' => $this->student->id,
        ], $this->authHeader($this->parentUser));

        $response->assertStatus(422);
    }

    public function test_download_attachment_returns_404_for_missing(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'No file', 'status' => 'published',
        ]);

        $response = $this->getJson("/api/assignments/{$assignment->id}/attachment", $this->authHeader($this->admin));

        $response->assertStatus(404);
    }

    public function test_download_submission_works(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap 1', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id, 'student_id' => $this->student->id,
            'submitted_file_path' => 'assignments/submissions/test.txt',
            'submitted_file_name' => 'test.txt', 'submitted_file_mime' => 'text/plain',
            'submitted_at' => now(), 'status' => 'submitted',
        ]);
        Storage::disk('local')->put('assignments/submissions/test.txt', 'test content');

        $response = $this->getJson("/api/assignment-submissions/{$submission->id}/download", $this->authHeader($this->admin));

        $response->assertStatus(200);
    }

    public function test_admin_can_grade_submission(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id, 'student_id' => $this->student->id,
            'submitted_file_path' => 'test.txt', 'submitted_file_name' => 'test.txt',
            'submitted_file_mime' => 'text/plain', 'submitted_at' => now(), 'status' => 'submitted',
        ]);

        $response = $this->patchJson("/api/assignment-submissions/{$submission->id}/grade", [
            'score' => 8.5, 'teacher_comment' => 'Tot!',
        ], $this->authHeader($this->admin));

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submission->id, 'score' => 8.5, 'teacher_comment' => 'Tot!',
        ]);
    }

    public function test_teacher_can_grade_submission_for_assigned_class(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id, 'student_id' => $this->student->id,
            'submitted_file_path' => 'test.txt', 'submitted_file_name' => 'test.txt',
            'submitted_file_mime' => 'text/plain', 'submitted_at' => now(), 'status' => 'submitted',
        ]);

        $response = $this->patchJson("/api/assignment-submissions/{$submission->id}/grade", [
            'score' => 9.0,
        ], $this->authHeader($this->teacher));

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_teacher_cannot_grade_submission_for_unassigned_class(): void
    {
        $otherClassroom = Classroom::create([
            'academic_year_id' => $this->classroom->academic_year_id, 'name' => '11A1', 'grade_level' => 11, 'is_active' => true,
        ]);
        $otherStudent = Student::create([
            'classroom_id' => $otherClassroom->id, 'student_code' => 'HS002', 'full_name' => 'Nguyen Van B', 'status' => 'studying',
        ]);

        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap', 'classroom_id' => $otherClassroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id, 'student_id' => $otherStudent->id,
            'submitted_file_path' => 'test.txt', 'submitted_file_name' => 'test.txt',
            'submitted_file_mime' => 'text/plain', 'submitted_at' => now(), 'status' => 'submitted',
        ]);

        $response = $this->patchJson("/api/assignment-submissions/{$submission->id}/grade", [
            'score' => 7.0,
        ], $this->authHeader($this->teacher));

        $response->assertStatus(403);
    }

    public function test_parent_cannot_grade_submission(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id, 'student_id' => $this->student->id,
            'submitted_file_path' => 'test.txt', 'submitted_file_name' => 'test.txt',
            'submitted_file_mime' => 'text/plain', 'submitted_at' => now(), 'status' => 'submitted',
        ]);

        $response = $this->patchJson("/api/assignment-submissions/{$submission->id}/grade", [
            'score' => 8.0,
        ], $this->authHeader($this->parentUser));

        $response->assertStatus(403);
    }

    public function test_grade_submission_validates_score_range(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id, 'student_id' => $this->student->id,
            'submitted_file_path' => 'test.txt', 'submitted_file_name' => 'test.txt',
            'submitted_file_mime' => 'text/plain', 'submitted_at' => now(), 'status' => 'submitted',
        ]);

        $response = $this->patchJson("/api/assignment-submissions/{$submission->id}/grade", [
            'score' => 11,
        ], $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_grade_submission_can_clear_score(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id, 'title' => 'Bai tap', 'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'published',
        ]);
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id, 'student_id' => $this->student->id,
            'submitted_file_path' => 'test.txt', 'submitted_file_name' => 'test.txt',
            'submitted_file_mime' => 'text/plain', 'submitted_at' => now(), 'status' => 'submitted',
            'score' => 8.0,
        ]);

        $response = $this->patchJson("/api/assignment-submissions/{$submission->id}/grade", [
            'score' => null, 'teacher_comment' => null,
        ], $this->authHeader($this->admin));

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submission->id, 'score' => null, 'teacher_comment' => null,
        ]);
    }

    public function test_admin_can_create_assignment_with_doc_attachment(): void
    {
        $file = UploadedFile::fake()->create('de_thi.doc', 100);

        $response = $this->postJson('/api/assignments', [
            'title' => 'Bai tap co file dinh kem',
            'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'attachment_file' => $file,
        ], $this->authHeader($this->admin));

        $response->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_admin_can_create_assignment_with_docx_attachment(): void
    {
        $file = UploadedFile::fake()->create('de_thi.docx', 100);

        $response = $this->postJson('/api/assignments', [
            'title' => 'Bai tap co file docx',
            'classroom_id' => $this->classroom->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'attachment_file' => $file,
        ], $this->authHeader($this->admin));

        $response->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_unauthenticated_user_cannot_access_assignments(): void
    {
        $response = $this->getJson('/api/assignments');
        $response->assertStatus(401);
    }
}
