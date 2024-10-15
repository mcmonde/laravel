<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VerifyBearerToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request['authorization'];
        $token = $this->extractToken($token);
        $token = hash('sha256', $token);

        if (!$token || !$this->validateToken($token)) {
            return abort(401, 'Unauthenticated');
        }

        return $next($request);
    }

    private function extractToken($bearerToken)
    {
        $parts = explode('|', $bearerToken);
        return isset($parts[1]) ? $parts[1] : null;
    }

    private function validateToken($token)
    {
        $tokenRecord = DB::table('personal_access_tokens')->where('token', $token)->first();

        if (!$tokenRecord) {
            return false;
        }

        return true;
    }

    private function isTokenExpired($expiresAt)
    {
        return now()->greaterThan($expiresAt);
    }
}
