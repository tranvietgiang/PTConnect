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
use Symfony\Component\HttpFoundation\Cookie;
use Throwable;

class AuthController extends Controller
{
    private const REMEMBER_COOKIE = 'ptconnect_remember_token';

    public function __construct(private readonly AuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $remember = $request->boolean('remember_me');
            $authData = $this->authService->login($request->validated());
            $responseData = $authData;
            $response = $this->success('Login successful.', $responseData);

            if ($remember) {
                unset($responseData['refresh_token']);
                $response = $this->success('Login successful.', $responseData);

                return $response->withCookie(
                    $this->rememberCookie($authData['refresh_token'], (int) env('JWT_REMEMBER_TTL', 43200)),
                );
            }

            return $response->withCookie($this->forgetRememberCookie());
        } catch (InvalidCredentialsException $exception) {
            return $this->error($exception->getMessage(), 401);
        }
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $bodyRefreshToken = $request->validated('refresh_token');
            $cookieRefreshToken = $request->cookie(self::REMEMBER_COOKIE);
            $refreshToken = $bodyRefreshToken ?: $cookieRefreshToken;

            if (! $refreshToken) {
                throw new UnauthorizedException('Invalid refresh token.');
            }

            $usingRememberCookie = ! $bodyRefreshToken && $cookieRefreshToken;
            $authData = $this->authService->refresh(
                $refreshToken,
                false,
                $usingRememberCookie ? (int) env('JWT_REMEMBER_TTL', 43200) : null,
            );
            $responseData = $authData;
            $response = $this->success('Token refreshed successfully.', $responseData);

            if ($usingRememberCookie) {
                unset($responseData['refresh_token']);
                $response = $this->success('Token refreshed successfully.', $responseData);

                return $response->withCookie(
                    $this->rememberCookie($authData['refresh_token'], (int) env('JWT_REMEMBER_TTL', 43200)),
                );
            }

            return $response;
        } catch (UnauthorizedException $exception) {
            return $this->error($exception->getMessage(), 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $refreshToken = $request->input('refresh_token') ?: $request->cookie(self::REMEMBER_COOKIE);

            if ($refreshToken) {
                $this->authService->logout($refreshToken);
            }

            return $this->success('Logout successful.')->withCookie($this->forgetRememberCookie());
        } catch (Throwable) {
            return $this->success('Logout successful.')->withCookie($this->forgetRememberCookie());
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $authorization = (string) $request->header('Authorization', '');

            if (str_starts_with($authorization, 'Bearer ')) {
                try {
                    return $this->success('Authenticated user retrieved.', [
                        'user' => $this->authService->meFromAccessToken(substr($authorization, 7)),
                    ]);
                } catch (Throwable) {
                    // Fall back to the remember cookie when the short-lived access token expired.
                }
            }

            $refreshToken = $request->cookie(self::REMEMBER_COOKIE);

            if (! $refreshToken) {
                throw new UnauthorizedException('Unauthorized.');
            }

            $authData = $this->authService->refresh(
                $refreshToken,
                true,
                (int) env('JWT_REMEMBER_TTL', 43200),
            );
            $responseData = $authData;
            unset($responseData['refresh_token']);

            return $this->success('Authenticated user restored.', $responseData)
                ->withCookie($this->rememberCookie($authData['refresh_token'], (int) env('JWT_REMEMBER_TTL', 43200)));
        } catch (Throwable) {
            return $this->error('Unauthorized.', 401)->withCookie($this->forgetRememberCookie());
        }
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

    private function rememberCookie(string $refreshToken, int $minutes): Cookie
    {
        return cookie(
            self::REMEMBER_COOKIE,
            $refreshToken,
            $minutes,
            '/',
            $this->cookieDomain(),
            $this->secureCookie(),
            true,
            false,
            config('session.same_site', 'lax') ?: 'lax',
        );
    }

    private function forgetRememberCookie(): Cookie
    {
        return cookie()->forget(self::REMEMBER_COOKIE, '/', $this->cookieDomain());
    }

    private function cookieDomain(): ?string
    {
        $domain = config('session.domain');

        return $domain === null || $domain === '' || $domain === 'null' ? null : $domain;
    }

    private function secureCookie(): bool
    {
        return filter_var(config('session.secure'), FILTER_VALIDATE_BOOL);
    }
}
