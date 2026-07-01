<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classroom\StoreClassroomRequest;
use App\Models\StudentEnrollment;
use App\Models\AttendanceSession;
use App\Models\ScoreColumn;
use App\Repositories\AcademicYearRepository;
use App\Repositories\ClassroomRepository;
use App\Repositories\CourseRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function __construct(
        protected ClassroomRepository $classroomRepo,
        protected CourseRepository $courseRepo,
        protected AcademicYearRepository $academicYearRepo,
        protected UserRepository $userRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->classroomRepo->newQuery();

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->input('grade_level'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $classrooms = $query->get()->map(function ($classroom) {
            $course = $this->courseRepo->find($classroom->course_id);
            $academicYear = $this->academicYearRepo->find($classroom->academic_year_id);
            $teacher = $this->userRepo->find($classroom->teacher_id);
            $assistant = $classroom->assistant_id ? $this->userRepo->find($classroom->assistant_id) : null;

            $studentsCount = \App\Models\StudentEnrollment::where('classroom_id', $classroom->id)
                ->where('status', 'active')
                ->count();

            return [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'status' => $classroom->status,
                'start_date' => $classroom->start_date,
                'end_date' => $classroom->end_date,
                'total_lessons' => $classroom->total_lessons,
                'students_count' => $studentsCount,
                'course' => $course ? ['id' => $course->id, 'name' => $course->name] : null,
                'academic_year' => $academicYear ? ['id' => $academicYear->id, 'name' => $academicYear->name] : null,
                'teacher' => $teacher ? ['id' => $teacher->id, 'email' => $teacher->email] : null,
                'assistant' => $assistant ? ['id' => $assistant->id, 'email' => $assistant->email] : null,
            ];
        });

        return response()->json([
            'data' => $classrooms,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $classroom = $this->classroomRepo->findOrFail($id);

        $course = $this->courseRepo->find($classroom->course_id);
        $academicYear = $this->academicYearRepo->find($classroom->academic_year_id);
        $teacher = $this->userRepo->find($classroom->teacher_id);
        $assistant = $classroom->assistant_id ? $this->userRepo->find($classroom->assistant_id) : null;

        $activeEnrollments = StudentEnrollment::where('classroom_id', $id)
            ->where('status', 'active')
            ->get();

        $scoreColumns = ScoreColumn::where('classroom_id', $id)->get();
        $attendanceSessions = AttendanceSession::where('classroom_id', $id)->get();

        return response()->json([
            'data' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'status' => $classroom->status,
                'course' => $course,
                'academic_year' => $academicYear,
                'teacher' => $teacher,
                'assistant' => $assistant,
                'active_enrollments' => $activeEnrollments,
                'score_columns' => $scoreColumns,
                'attendance_sessions' => $attendanceSessions,
            ],
        ]);
    }

    public function store(StoreClassroomRequest $request): JsonResponse
    {
        $classroom = $this->classroomRepo->create($request->validated());

        return response()->json([
            'message' => 'Đã tạo lớp học thành công.',
            'data' => $classroom,
        ], 201);
    }

    public function update(int $id, StoreClassroomRequest $request): JsonResponse
    {
        $classroom = $this->classroomRepo->findOrFail($id);
        $this->classroomRepo->update($classroom, $request->validated());

        return response()->json([
            'message' => 'Đã cập nhật lớp học thành công.',
            'data' => $classroom,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->classroomRepo->deleteById($id);

        return response()->json([
            'message' => 'Đã xóa lớp học thành công.',
        ]);
    }
}
