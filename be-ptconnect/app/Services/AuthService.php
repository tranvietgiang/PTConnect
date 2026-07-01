<?php

namespace App\Services;

use App\Repositories\RefreshTokenRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        protected UserRepository $userRepo,
        protected RefreshTokenRepository $refreshTokenRepo,
    ) {}

    public function login(string $email, string $password): ?array
    {
        $user = $this->userRepo->findActiveByEmail($email);

        if (!$user) {
            return null;
        }

        $token = JWTAuth::claims(['role' => $user->role])->attempt([
            'email' => $email,
            'password' => $password,
        ]);

        if (!$token) {
            return null;
        }

        $refreshToken = $this->refreshTokenRepo->createForUser(
            $user->id,
            Str::random(64)
        );

        $this->userRepo->updateLastLogin($user->id);

        $studentProfile = $user->isStudent() ? $user->studentProfile : null;
        $teacherProfile = $user->isTeacher() ? $user->teacherProfile : null;
        $assistantProfile = $user->isAssistant() ? $user->assistantProfile : null;

        return [
            'access_token' => $token,
            'refresh_token' => $refreshToken->token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user,
        ];
    }

    public function refresh(?string $refreshToken): ?array
    {
        if (!$refreshToken) {
            return null;
        }

        $existing = $this->refreshTokenRepo->findValidToken($refreshToken);

        if (!$existing) {
            return null;
        }

        $user = $this->userRepo->find($existing->user_id);

        if (!$user || !$user->is_active) {
            $existing->delete();
            return null;
        }

        $existing->delete();

        try {
            $newToken = JWTAuth::claims(['role' => $user->role])->fromUser($user);
        } catch (JWTException $e) {
            return null;
        }

        $newRefresh = $this->refreshTokenRepo->createForUser(
            $user->id,
            Str::random(64)
        );

        return [
            'access_token' => $newToken,
            'refresh_token' => $newRefresh->token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user,
        ];
    }

    public function logout(?\Tymon\JWTAuth\Contracts\JWTSubject $user, ?string $refreshToken): void
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException) {
        }

        if ($refreshToken) {
            $this->refreshTokenRepo->deleteByToken($refreshToken);
        }
    }
}
