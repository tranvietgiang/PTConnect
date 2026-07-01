<?php

namespace App\Services;

use App\Repositories\AssignmentRepository;
use App\Repositories\AssignmentSubmissionRepository;
use App\Repositories\ClassroomRepository;
use App\Repositories\StudentProfileRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentService
{
    public function __construct(
        protected AssignmentRepository $assignmentRepo,
        protected AssignmentSubmissionRepository $submissionRepo,
        protected ClassroomRepository $classroomRepo,
        protected StudentProfileRepository $studentRepo,
    ) {}

    public function createAssignment(array $data, int $userId, ?UploadedFile $file): \App\Models\Assignment
    {
        $classroom = $this->classroomRepo->findOrFail($data['classroom_id']);

        $assignmentData = [
            'created_by' => $userId,
            'classroom_id' => $data['classroom_id'],
            'grade_level' => $classroom->grade_level,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => 'published',
        ];

        if ($file) {
            $path = $file->store('assignments', 'public');
            $assignmentData['attachment_path'] = $path;
            $assignmentData['attachment_name'] = $file->getClientOriginalName();
            $assignmentData['attachment_mime'] = $file->getMimeType();
        }

        return $this->assignmentRepo->create($assignmentData);
    }

    public function submitAssignment(int $assignmentId, int $userId, UploadedFile $file): \App\Models\AssignmentSubmission
    {
        $this->assignmentRepo->findOrFail($assignmentId);

        $student = $this->studentRepo->findByUserId($userId);

        if (!$student) {
            abort(404, 'Không tìm thấy học sinh.');
        }

        $path = $file->store('submissions', 'public');

        $assignment = $this->assignmentRepo->find($assignmentId);
        $isLate = $assignment && $assignment->due_date && now()->gt($assignment->due_date);

        return $this->submissionRepo->updateOrCreate(
            [
                'assignment_id' => $assignmentId,
                'student_id' => $student->id,
            ],
            [
                'submitted_file_path' => $path,
                'submitted_file_name' => $file->getClientOriginalName(),
                'submitted_file_mime' => $file->getMimeType(),
                'submitted_at' => now(),
                'status' => $isLate ? 'late' : 'submitted',
            ]
        );
    }

    public function downloadAttachment(int $assignmentId): StreamedResponse
    {
        $assignment = $this->assignmentRepo->findOrFail($assignmentId);

        if (!$assignment->attachment_path || !Storage::disk('public')->exists($assignment->attachment_path)) {
            abort(404, 'File không tồn tại.');
        }

        return Storage::disk('public')->download(
            $assignment->attachment_path,
            $assignment->attachment_name
        );
    }

    public function downloadSubmission(int $submissionId): StreamedResponse
    {
        $submission = $this->submissionRepo->findOrFail($submissionId);

        if (!$submission->submitted_file_path || !Storage::disk('public')->exists($submission->submitted_file_path)) {
            abort(404, 'File không tồn tại.');
        }

        return Storage::disk('public')->download(
            $submission->submitted_file_path,
            $submission->submitted_file_name
        );
    }
}
