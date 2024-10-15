<?php

namespace App\Http\Middleware;

use App\Models\Log;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRouteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Perform the request
        $response = $next($request);

        // Log information about the request to the database
        $user = auth()->user();
        $route = $request->path();
        $name = $request->route()->getName() ?? '';
        $user_agent = $request->header('User-Agent');
        $ip_address = $request->ip();
        $method = $request->method();

        if ($name == 'api.login' || preg_match('/^.*\.store$/', $name) || preg_match('/^.*\.update$/', $name))
            $data = json_encode($request->except(['password', 'new_password', 'current_password']));
        else
            $data = json_encode($request->all());

        Log::create([
            'user_id' => $user?->id,
            'route' => $route,
            'name' => $name,
            'method' => $method,
            'user_agent' => $user_agent,
            'ip_address' => $ip_address,
            'data' => $data,
        ]);

        return $response;
    }
}
