<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->addCorsHeaders(response()->noContent(), $request);
        }

        return $this->addCorsHeaders($next($request), $request);
    }

    private function addCorsHeaders(Response $response, Request $request): Response
    {
        $origin = $request->headers->get('Origin');

        if (! $origin || ! $this->isAllowedOrigin($origin)) {
            return $response;
        }

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            $request->headers->get('Access-Control-Request-Headers')
                ?: 'Origin, Content-Type, Accept, Authorization, X-Requested-With, X-XSRF-TOKEN',
        );
        $response->headers->set('Access-Control-Max-Age', '86400');
        $response->headers->set('Vary', 'Origin', false);

        return $response;
    }

    private function isAllowedOrigin(string $origin): bool
    {
        $allowedOrigins = array_filter([
            ...config('cors.allowed_origins', []),
            env('FRONTEND_URL'),
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://192.168.33.12:5173',
        ]);

        if (in_array($origin, $allowedOrigins, true)) {
            return true;
        }

        foreach (config('cors.allowed_origins_patterns', []) as $pattern) {
            if (@preg_match($pattern, $origin) === 1) {
                return true;
            }
        }

        return false;
    }
}
