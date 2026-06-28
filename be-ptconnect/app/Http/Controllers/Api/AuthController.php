<?php

namespace App\Http\Controllers\Api;

use App\Domains\Auth\Exceptions\InvalidCredentialsException;
use App\Domains\Auth\Exceptions\UnauthorizedException;
use App\Domains\Auth\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            return $this->success(
                'Login successful.',
                $this->authService->login($request->validated()),
            );
        } catch (InvalidCredentialsException $exception) {
            return $this->error($exception->getMessage(), 401);
        }
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            return $this->success(
                'Token refreshed successfully.',
                $this->authService->refresh($request->validated('refresh_token')),
            );
        } catch (UnauthorizedException $exception) {
            return $this->error($exception->getMessage(), 401);
        }
    }

    public function logout(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $this->authService->logout($request->validated('refresh_token'));

            return $this->success('Logout successful.');
        } catch (Throwable $exception) {
            return $this->error('Invalid refresh token.', 401);
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if (! $user) {
            return $this->error('Unauthorized.', 401);
        }

        return $this->success('Authenticated user retrieved.', [
            'user' => $this->authService->me($user->id),
        ]);
    }

    private function success(string $message, ?array $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
