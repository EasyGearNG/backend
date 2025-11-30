<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Sanctum\PersonalAccessToken;

class AuthStatusController extends Controller
{
    /**
     * Public endpoint that returns whether the request is authenticated.
     * If a valid token is provided (Authorization: Bearer ...), the user object
     * will be returned. Otherwise returns authenticated: false.
     */
    public function check(Request $request): JsonResponse
    {
        // Try the standard authenticated user first (works when session/cookie auth used)
        $user = $request->user();

        // If no user but a Bearer token exists, attempt to resolve it via Sanctum's PersonalAccessToken
        if (! $user) {
            $bearer = $request->bearerToken();
            if ($bearer) {
                $token = PersonalAccessToken::findToken($bearer);
                if ($token && $token->tokenable) {
                    $user = $token->tokenable;
                }
            }
        }

        if ($user) {
            return response()->json([
                'success' => true,
                'authenticated' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role ?? null,
                    ]
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'authenticated' => false,
            'data' => null
        ]);
    }
}
