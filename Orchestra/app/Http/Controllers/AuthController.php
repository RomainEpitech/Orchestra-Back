<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::with('role', 'enterprise')
            ->where('email', $validated['email'])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json([
            'token' => $user->createToken('auth-token')->plainTextToken,
            'user' => [
                'uuid' => $user->uuid,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => [
                    'name' => $user->role->name,
                    'authority' => $user->role->authority
                ]
            ],
            'enterprise' => [
                'name' => $user->enterprise->name,
                'key' => $user->enterprise->key
            ]
        ]);
    }
}