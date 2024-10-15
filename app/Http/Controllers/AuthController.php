<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\Login;
use App\Repositories\AuthRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthRepository $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function login(Login $request): JsonResponse
    {
        $data = $this->authRepository->login($request);

        if(isset($data['errors']))
            $status = 401;
        else
            $status = 200;

        return response()->json($data, $status);
    }

    public function logout(Request $request): string
    {
        $data = $this->authRepository->logout($request);
        return response()->json($data);
    }
}
