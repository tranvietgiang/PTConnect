<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Exceptions\InvalidCredentialsException;
use App\Domains\Auth\Exceptions\UnauthorizedException;
use App\Domains\Auth\Repositories\AuthRepositoryInterface;
use App\Domains\Auth\ValueObjects\Email;
use App\Infrastructure\JWT\JwtService;
use App\Infrastructure\JWT\RefreshTokenService;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly JwtService $jwtService,
        private readonly RefreshTokenService $refreshTokenService,
    ) {}

    public function login(array $credentials): array
    {
        $email = new Email($credentials['email']);
        $user = $this->authRepository->findUserByEmail($email->value());

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new InvalidCredentialsException();
        }

        $refreshToken = $this->refreshTokenService->createRefreshToken($user);

        return [
            'user' => $this->serializeUser($user),
            'access_token' => $this->jwtService->generateAccessToken($user),
            'refresh_token' => $refreshToken['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->accessTtlSeconds(),
        ];
    }

    public function refresh(string $refreshToken): array
    {
        $storedToken = $this->refreshTokenService->validateRefreshToken($refreshToken);
        $user = $storedToken->user;

        if (! $user) {
            throw new UnauthorizedException('User not found.');
        }

        $newRefreshToken = $this->refreshTokenService->rotateRefreshToken($refreshToken, $user);

        return [
            'access_token' => $this->jwtService->generateAccessToken($user),
            'refresh_token' => $newRefreshToken['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->accessTtlSeconds(),
        ];
    }

    public function logout(string $refreshToken): void
    {
        $this->refreshTokenService->revokeRefreshToken($refreshToken);
    }

    public function me(int $userId): array
    {
        $user = $this->authRepository->findUserById($userId);

        if (! $user) {
            throw new UnauthorizedException('User not found.');
        }

        return $this->serializeUser($user);
    }

    private function serializeUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }
}
