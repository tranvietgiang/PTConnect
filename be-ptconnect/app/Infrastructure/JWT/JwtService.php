<?php

namespace App\Infrastructure\JWT;

use App\Models\User;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use stdClass;

class JwtService
{
    private const ALGORITHM = 'HS256';

    public function generateAccessToken(User $user): string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + ($this->accessTtlMinutes() * 60);

        return JWT::encode([
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'jti' => (string) Str::uuid(),
        ], $this->secret(), self::ALGORITHM);
    }

    public function decodeToken(string $token): stdClass
    {
        return JWT::decode($token, new Key($this->secret(), self::ALGORITHM));
    }

    public function accessTtlSeconds(): int
    {
        return $this->accessTtlMinutes() * 60;
    }

    private function accessTtlMinutes(): int
    {
        return (int) env('JWT_ACCESS_TTL', 15);
    }

    private function secret(): string
    {
        $secret = (string) env('JWT_SECRET', '');

        if ($secret === '') {
            $secret = (string) config('app.key');
        }

        return $secret;
    }
}
