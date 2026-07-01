<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\EmailNotificationRepository;
use App\Repositories\StudentProfileRepository;
use App\Repositories\UserRepository;
use App\Services\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailNotificationController extends Controller
{
    public function __construct(
        protected EmailNotificationService $emailService,
        protected EmailNotificationRepository $emailNotificationRepo,
        protected StudentProfileRepository $studentRepo,
        protected UserRepository $userRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->emailNotificationRepo->newQuery()
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        $notifications = $query->paginate($request->input('per_page', 20));

        $notifications->getCollection()->transform(function ($item) {
            $student = $this->studentRepo->find($item->student_id);
            $creator = $item->created_by ? $this->userRepo->find($item->created_by) : null;

            return [
                'id' => $item->id,
                'student_id' => $item->student_id,
                'student_name' => $student?->full_name,
                'recipient_email' => $item->recipient_email,
                'subject' => $item->subject,
                'type' => $item->type,
                'status' => $item->status,
                'sent_at' => $item->sent_at,
                'creator_name' => $creator?->email,
            ];
        });

        return response()->json($notifications);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:attendance,score,assignment,general',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:student_profiles,id',
            'subject' => 'required_if:type,general|nullable|string|max:255',
            'content' => 'required_if:type,general|nullable|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
        ]);

        $result = $this->emailService->sendEmails($validated, $request->user()->id);

        return response()->json([
            'message' => 'Đã gửi email.',
            'data' => $result,
        ]);
    }
}
