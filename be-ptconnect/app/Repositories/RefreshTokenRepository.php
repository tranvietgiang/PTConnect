<?php

namespace App\Repositories;

use App\Models\RefreshToken;

class RefreshTokenRepository extends Repository
{
    protected function model(): string
    {
        return RefreshToken::class;
    }

    public function findValidToken(string $token): ?RefreshToken
    {
        return RefreshToken::where('token', $token)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function deleteByToken(string $token): void
    {
        RefreshToken::where('token', $token)->delete();
    }

    public function createForUser(int $userId, string $token, int $days = 30): RefreshToken
    {
        return RefreshToken::create([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => now()->addDays($days),
        ]);
    }
}
