<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'El usuario está inactivo y no puede iniciar sesión.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->tokens()->delete();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Autenticación correcta.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], Response::HTTP_OK);
    }

    public function logout(): JsonResponse
    {
        $user = request()->user();
        $user?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada.',
        ], Response::HTTP_OK);
    }
}
