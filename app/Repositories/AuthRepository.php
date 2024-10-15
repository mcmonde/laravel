<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthRepository
{
    public function login($request): array
    {
        try {
            if (Auth::attempt($request->only('email', 'password'))) {

                $user = Auth::user();

                if ($user->status && $user->allow_login) {
                    $user_agent = $request->header('User-Agent');
                    $ip_address = $request->ip();

                    $token = $user->createToken('auth-token');

                    DB::table('personal_access_tokens')->where('id', $token->accessToken->id)
                        ->update([
                            'user_agent' => $user_agent,
                            'ip_address' => $ip_address
                        ]);

                    // Record login history
                    //                $this->recordLoginHistory($user, $request);

                    return ([
                        'message' => 'Logged in successfully.',
                        'user' => $user->load(['roles']),
                        'token' => $token->plainTextToken,
                    ]);
                } else {
                    return ([
                        'message' => 'Incorrect Email & Password',
                        'errors' => [
                            'email' => ['Incorrect Email & Password'],
                            'password'  => ['Incorrect Email & Password'],
                        ]
                    ]);
                }
            }

            return ([
                'message' => 'Incorrect Email & Password',
                'errors' => [
                    'email' => ['Incorrect Email & Password'],
                    'password'  => ['Incorrect Email & Password'],
                ]
            ]);
        } catch (\Throwable $throwable) {
            return ([
                'message' => $throwable->getMessage()
            ]);
        }
    }

    public function logout($request): array
    {
        $message = 'Successfully logged out';

        if ($request->logout == 'others') {
            $request->user()->tokens
                ->where('id', '<>', $request->user()->currentAccessToken()->id)
                ->each(function ($token) {
                    $token->update(['expires_at' => now()]);
                });
            $message = 'Successfully logged out other devices.';
        } elseif ($request->logout == 'all') {
            $request->user()->tokens->each(function ($token) {
                $token->update(['expires_at' => now()]);
            });
            $message = 'Successfully logged out all devices.';
        } else {
            $request->user()->currentAccessToken()->update([
                'expires_at' => now(),
            ]);
            $message = 'Successfully logged out.';
        }

        return (['message' => $message]);
    }
}
