<?php

namespace App\Infrastructure\JWT;

use App\Domains\Auth\Exceptions\UnauthorizedException;
use App\Models\RefreshToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class RefreshTokenService
{
    public function generateRefreshToken(): string
    {
        return Str::random(80);
    }

    public function hashRefreshToken(string $refreshToken): string
    {
        return hash('sha256', $refreshToken);
    }

    /**
     * @return array{refresh_token:string, model:RefreshToken}
     */
    public function createRefreshToken(User $user, ?int $ttlMinutes = null): array
    {
        $plainToken = $this->generateRefreshToken();
        $model = RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => $this->hashRefreshToken($plainToken),
            'jti' => (string) Str::uuid(),
            'expires_at' => CarbonImmutable::now()->addMinutes($ttlMinutes ?? (int) env('JWT_REFRESH_TTL', 10080)),
        ]);

        return [
            'refresh_token' => $plainToken,
            'model' => $model,
        ];
    }

    public function validateRefreshToken(string $refreshToken): RefreshToken
    {
        $token = RefreshToken::query()
            ->where('token_hash', $this->hashRefreshToken($refreshToken))
            ->first();

        if (! $token || $token->revoked_at || $token->expires_at->isPast()) {
            throw new UnauthorizedException('Invalid refresh token.');
        }

        return $token;
    }

    public function revokeRefreshToken(string $refreshToken): void
    {
        $token = $this->validateRefreshToken($refreshToken);
        $token->forceFill(['revoked_at' => now()])->save();
    }

    /**
     * @return array{refresh_token:string, model:RefreshToken}
     */
    public function rotateRefreshToken(string $oldRefreshToken, User $user, ?int $ttlMinutes = null): array
    {
        $this->revokeRefreshToken($oldRefreshToken);

        return $this->createRefreshToken($user, $ttlMinutes);
    }
}
