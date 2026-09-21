<?php

namespace App\Actions;

use App\Contracts\QueryInterface;
use App\Contracts\ReadActionInterface;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Auth;

class LoginAction implements ReadActionInterface
{
    public function handle(QueryInterface $query): array|false
    {
        if (! Auth::attempt($query->get('credentials'))) {
            return false;
        }

        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $payload = [
            'sub'   => $user->id,
            'email' => $user->email,
            'role'  => $user->role,
            'iat'   => time(),
            'exp'   => time() + 60 * 60 * 8, // 8 hours, mirrored by the Symfony token_ttl
        ];

        return [
            'authToken'  => JWT::encode($payload, config('app.jwt_shared_secret'), 'HS256'),
            'loggedUser' => $user,
        ];
    }
}
