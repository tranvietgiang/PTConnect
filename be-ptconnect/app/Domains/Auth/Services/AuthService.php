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
        $identifier = trim((string) ($credentials['email'] ?? $credentials['username'] ?? ''));
        $user = $this->findUserByIdentifier($identifier);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new InvalidCredentialsException();
        }

        if (array_key_exists('is_active', $user->getAttributes()) && ! $user->is_active) {
            throw new InvalidCredentialsException();
        }

        $refreshTtl = ! empty($credentials['remember_me'])
            ? (int) env('JWT_REMEMBER_TTL', 43200)
            : null;

        $refreshToken = $this->refreshTokenService->createRefreshToken($user, $refreshTtl);

        if (array_key_exists('last_login_at', $user->getAttributes())) {
            $user->forceFill(['last_login_at' => now()])->save();
        }

        return [
            'user' => $this->serializeUser($user),
            'access_token' => $this->jwtService->generateAccessToken($user),
            'refresh_token' => $refreshToken['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->accessTtlSeconds(),
        ];
    }

    public function refresh(string $refreshToken, bool $includeUser = false, ?int $refreshTtl = null): array
    {
        $storedToken = $this->refreshTokenService->validateRefreshToken($refreshToken);
        $user = $storedToken->user;

        if (! $user) {
            throw new UnauthorizedException('User not found.');
        }

        $newRefreshToken = $this->refreshTokenService->rotateRefreshToken($refreshToken, $user, $refreshTtl);

        $data = [
            'access_token' => $this->jwtService->generateAccessToken($user),
            'refresh_token' => $newRefreshToken['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->accessTtlSeconds(),
        ];

        if ($includeUser) {
            $data['user'] = $this->serializeUser($user);
        }

        return $data;
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

    public function meFromAccessToken(string $accessToken): array
    {
        $payload = $this->jwtService->decodeToken($accessToken);
        $userId = (int) ($payload->sub ?? 0);

        return $this->me($userId);
    }

    private function findUserByIdentifier(string $identifier)
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $email = new Email($identifier);

            return $this->authRepository->findUserByEmail($email->value());
        }

        return $this->authRepository->findUserByUsername($identifier);
    }

    private function serializeUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'role' => $user->role,
        ];
    }
}
