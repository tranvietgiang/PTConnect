<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Support\AccessControl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $query = $this->scoreQuery();

        if (! $this->applyRoleScope($query, $user)) {
            return $this->error('Forbidden.', 403);
        }

        $this->applyFilters($query, $request);

        $scores = $query
            ->get()
            ->sort(function (AssignmentSubmission $first, AssignmentSubmission $second): int {
                $timeCompare = ($second->submitted_at?->timestamp ?? 0) <=> ($first->submitted_at?->timestamp ?? 0);

                if ($timeCompare !== 0) {
                    return $timeCompare;
                }

                return strcasecmp($first->student?->full_name ?? '', $second->student?->full_name ?? '');
            })
            ->values()
            ->map(fn (AssignmentSubmission $submission): array => $this->serialize($submission))
            ->all();

        return $this->success('Scores retrieved.', $scores);
    }

    public function report(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $query = $this->scoreQuery();

        if (! $this->applyRoleScope($query, $user)) {
            return $this->error('Forbidden.', 403);
        }

        $this->applyFilters($query, $request);

        $reports = $query
            ->get()
            ->groupBy(fn (AssignmentSubmission $submission): string => $submission->student?->classroom?->name ?? 'Chưa có lớp')
            ->map(function ($submissions, string $className): array {
                $scores = $submissions
                    ->pluck('score')
                    ->filter(fn ($score): bool => $score !== null && $score !== '')
                    ->map(fn ($score): float => (float) $score);

                return [
                    'id' => $className,
                    'className' => $className,
                    'total' => $scores->count(),
                    'excellent' => $scores->filter(fn (float $score): bool => $score >= 8)->count(),
                    'good' => $scores->filter(fn (float $score): bool => $score >= 6.5 && $score < 8)->count(),
                    'support' => $scores->filter(fn (float $score): bool => $score < 6.5)->count(),
                    'average' => $scores->count() ? round($scores->average(), 2) : null,
                ];
            })
            ->sortBy('className')
            ->values()
            ->all();

        return $this->success('Score report retrieved.', $reports);
    }

    private function scoreQuery(): Builder
    {
        return AssignmentSubmission::query()
            ->with([
                'assignment:id,title,classroom_id,grade_level,due_date',
                'assignment.classroom:id,name,course_id',
                'assignment.classroom.course:id,name,grade_level',
                'student:id,student_code,full_name,classroom_id',
                'student.classroom:id,name,course_id',
                'student.classroom.course:id,name,grade_level',
            ])
            ->where('status', 'submitted');
    }

    private function applyRoleScope(Builder $query, $user): bool
    {
        if ($user?->isAdmin()) {
            return true;
        }

        if ($user?->isTeacher() || $user?->isAssistant()) {
            $classroomIds = AccessControl::assignedClassroomIds($user);

            $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->whereIn('classroom_id', $classroomIds));

            return true;
        }

        if ($user?->isStudent()) {
            $studentIds = AccessControl::legacyStudentIdsForUser($user);

            $query->whereIn('student_id', $studentIds);

            return true;
        }

        return false;
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $gradeLevel = $request->query('grade_level');
        $classroomId = $request->query('classroom_id');
        $studentName = trim((string) ($request->query('student_name') ?? $request->query('name') ?? $request->query('q') ?? ''));

        if ($gradeLevel && $gradeLevel !== 'all') {
            $query->whereHas(
                'student.classroom.course',
                fn (Builder $courseQuery) => $courseQuery->where('grade_level', (int) $gradeLevel),
            );
        }

        if ($classroomId && $classroomId !== 'all') {
            $query->whereHas(
                'student',
                fn (Builder $studentQuery) => $studentQuery->where('classroom_id', (int) $classroomId),
            );
        }

        if ($studentName !== '') {
            $query->whereHas(
                'student',
                fn (Builder $studentQuery) => $studentQuery->where('full_name', 'like', '%'.$studentName.'%'),
            );
        }
    }

    private function serialize(AssignmentSubmission $submission): array
    {
        $student = $submission->student;
        $classroom = $student?->classroom ?? $submission->assignment?->classroom;

        return [
            'row_key' => 'assignment-submission-'.$submission->id,
            'id' => $submission->id,
            'source' => 'assignment_submissions',
            'assignment_id' => $submission->assignment_id,
            'submission_id' => $submission->id,
            'assignment_title' => $submission->assignment?->title,
            'student_id' => $submission->student_id,
            'student_code' => $student?->student_code,
            'student_name' => $student?->full_name,
            'classroom_id' => $student?->classroom_id ?? $submission->assignment?->classroom_id,
            'class_name' => $classroom?->name,
            'grade_level' => $classroom?->course?->grade_level ?? $submission->assignment?->grade_level,
            'subject' => 'Sinh học',
            'file_name' => $submission->submitted_file_name,
            'file_mime' => $submission->submitted_file_mime,
            'score' => $submission->score,
            'comment' => $submission->teacher_comment,
            'submitted_at' => $submission->submitted_at?->toDateTimeString(),
            'status' => $submission->status,
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

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => null,
        ], $status);
    }
}
