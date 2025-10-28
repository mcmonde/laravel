<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\Login;
use App\Repositories\AuthRepository;
use App\Services\RSAService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthRepository $repository;

    public function __construct(AuthRepository $repository)
    {
        $this->repository = $repository;
    }

    public function login(Login $request): JsonResponse
    {
        $credentials = $request->validated();
        $credentials['user_agent'] = request()->header('User-Agent');
        $credentials['ip_address'] = request()->ip();

        $data = $this->repository->login($credentials);

        $status = 200;

        if(isset($data['errors']))
            $status = 401;

        return response()->json($data, $status);
    }

    public function logout(Request $request): string
    {
        $data = $this->repository->logout($request);
        return response()->json($data);
    }

    public function publicKey(): string
    {
        $keyPath = storage_path('app/keys/public.pem');

        if (!file_exists($keyPath)) {
            return response()->json(['error' => 'Public key not found.'], 404);
        }

        return trim(file_get_contents($keyPath));
    }
}
