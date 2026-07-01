<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Repositories\AssistantProfileRepository;
use App\Repositories\StudentProfileRepository;
use App\Repositories\TeacherProfileRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected UserRepository $userRepo,
        protected StudentProfileRepository $studentRepo,
        protected TeacherProfileRepository $teacherRepo,
        protected AssistantProfileRepository $assistantRepo,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->userRepo->findByEmail($request->input('email'));

        if ($user && !$user->is_active) {
            return response()->json([
                'message' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.',
            ], 401);
        }

        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        if (!$result) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không đúng.',
            ], 401);
        }

        return response()->json([
            'data' => $result,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refresh(
            $request->input('refresh_token')
        );

        if (!$result) {
            return response()->json([
                'message' => 'Refresh token không hợp lệ hoặc đã hết hạn.',
            ], 401);
        }

        return response()->json([
            'data' => $result,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authService->logout(
            $user,
            $request->input('refresh_token')
        );

        return response()->json([
            'message' => 'Đã đăng xuất thành công.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = null;
        if ($user->isStudent()) {
            $profile = $this->studentRepo->findByUserId($user->id);
        } elseif ($user->isTeacher()) {
            $profile = $this->teacherRepo->findByUserId($user->id);
        } elseif ($user->isAssistant()) {
            $profile = $this->assistantRepo->findByUserId($user->id);
        }

        return response()->json([
            'data' => [
                'user' => $user,
                'profile' => $profile,
            ],
        ]);
    }
}
