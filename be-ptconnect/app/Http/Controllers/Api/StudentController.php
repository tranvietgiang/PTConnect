<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ImportStudentRequest;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Models\StudentEnrollment;
use App\Repositories\ClassroomRepository;
use App\Repositories\StudentEnrollmentRepository;
use App\Repositories\StudentProfileRepository;
use App\Services\ImportStudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        protected StudentProfileRepository $studentRepo,
        protected StudentEnrollmentRepository $enrollmentRepo,
        protected ClassroomRepository $classroomRepo,
        protected ImportStudentService $importService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->studentRepo->newQuery();

        if ($request->filled('classroom_id')) {
            $ids = $this->enrollmentRepo->newQuery()
                ->where('classroom_id', $request->input('classroom_id'))
                ->pluck('student_id');
            $query->whereIn('id', $ids);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('full_name', 'like', "%{$keyword}%")
                  ->orWhere('student_code', 'like', "%{$keyword}%");
            });
        }

        $students = $query->paginate($request->input('per_page', 50));

        $students->getCollection()->transform(function ($student) {
            $enrollments = $this->enrollmentRepo->newQuery()
                ->where('student_id', $student->id)
                ->get();

            $activeEnrollment = $enrollments->firstWhere('status', 'active');
            $className = '';
            if ($activeEnrollment) {
                $classroom = $this->classroomRepo->find($activeEnrollment->classroom_id);
                $className = $classroom?->name ?? '';
            }

            return [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'date_of_birth' => $student->date_of_birth,
                'address' => $student->address,
                'high_school' => $student->high_school,
                'classroom_id' => $activeEnrollment?->classroom_id,
                'class_name' => $className,
                'status' => $activeEnrollment ? 'studying' : 'inactive',
            ];
        });

        return response()->json($students);
    }

    public function show(int $id): JsonResponse
    {
        $student = $this->studentRepo->findOrFail($id);

        $enrollments = $this->enrollmentRepo->newQuery()
            ->where('student_id', $id)
            ->get();

        $activeEnrollment = $enrollments->firstWhere('status', 'active');
        $className = '';
        if ($activeEnrollment) {
            $classroom = $this->classroomRepo->find($activeEnrollment->classroom_id);
            $className = $classroom?->name ?? '';
        }

        return response()->json([
            'data' => [
                'id' => $student->id,
                'user_id' => $student->user_id,
                'student_code' => $student->student_code,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'date_of_birth' => $student->date_of_birth,
                'address' => $student->address,
                'high_school' => $student->high_school,
                'cccd' => $student->cccd,
                'parent_email' => $student->parent_email,
                'parent_name' => $student->parent_name,
                'parent_phone' => $student->parent_phone,
                'parent_relationship' => $student->parent_relationship,
                'classroom_id' => $activeEnrollment?->classroom_id,
                'class_name' => $className,
                'status' => $activeEnrollment ? 'studying' : 'inactive',
            ],
        ]);
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $result = $this->importService->createSingle($request->validated());

        if (!empty($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }

        return response()->json([
            'message' => 'Đã tạo học sinh thành công.',
            'data' => $result['data'],
        ], 201);
    }

    public function import(ImportStudentRequest $request): JsonResponse
    {
        $result = $this->importService->importFromFile(
            $request->file('file'),
            $request->input('classroom_id')
        );

        return response()->json($result);
    }
}
