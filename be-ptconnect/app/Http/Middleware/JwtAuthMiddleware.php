<?php

namespace App\Http\Middleware;

use App\Domains\Auth\Repositories\AuthRepositoryInterface;
use App\Infrastructure\JWT\JwtService;
use Closure;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class JwtAuthMiddleware
{
    public function __construct(
        private readonly JwtService $jwtService,
        private readonly AuthRepositoryInterface $authRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized('Missing access token.');
        }

        $token = trim(substr($header, 7));

        try {
            $payload = $this->jwtService->decodeToken($token);
            $user = $this->authRepository->findUserById((int) $payload->sub);

            if (! $user) {
                return $this->unauthorized('Invalid token user.');
            }

            if (array_key_exists('is_active', $user->getAttributes()) && ! $user->is_active) {
                return $this->unauthorized('Inactive user.');
            }

            $request->attributes->set('auth_user', $user);
        } catch (ExpiredException) {
            return $this->unauthorized('Token has expired.');
        } catch (Throwable) {
            return $this->unauthorized('Invalid token.');
        }

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => null,
        ], 401);
    }
}
