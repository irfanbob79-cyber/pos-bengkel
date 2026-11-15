<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Mobile Android & API Login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::with('branch')->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse('Kredensial login tidak cocok.', 401);
        }

        if (! $user->is_active) {
            return $this->errorResponse('Akun Anda telah dinonaktifkan.', 403);
        }

        $deviceName = $validated['device_name'] ?? 'Android-Device';
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Login berhasil.');
    }

    /**
     * Ambil data profil user yang sedang login
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('branch');

        return $this->successResponse(new UserResource($user), 'Profil berhasil diambil.');
    }

    /**
     * Logout / Hapus token perangkat saat ini
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Berhasil logout dan token dinonaktifkan.');
    }
}
