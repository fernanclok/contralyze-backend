<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Invalid or expired token'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
