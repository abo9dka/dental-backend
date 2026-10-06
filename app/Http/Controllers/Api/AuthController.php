<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'patient',
        ]);

        Patient::create([
            'user_id' => $user->id,
        ]);

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return $this->successResponse(
            [
                'user' => $user,
                'token' => $token,
            ],
            'Registration successful.',
            201
        );
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        $user = User::where(
            'email',
            $validated['email']
        )->first();

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return $this->successResponse(
            [
                'user' => $user,
                'token' => $token,
            ],
            'Login successful.'
        );
    }

    public function logout(Request $request)
    {
        $request
            ->user()
            ->currentAccessToken()
            ->delete();

        return $this->successResponse(
            null,
            'Logout successful.'
        );
    }

    public function me(Request $request)
    {
        return $this->successResponse(
            [
                'user' => $request->user(),
            ],
            'User retrieved successfully.'
        );
    }
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user = $request->user();

        if (!Hash::check(
            $validated['current_password'],
            $user->password
        )) {
            return $this->errorResponse(
                'Current password is incorrect.',
                422
            );
        }

        $user->update([
            'password' => Hash::make(
                $validated['new_password']
            ),
        ]);

        // إلغاء جميع التوكنات الحالية لأسباب أمنية
        $user->tokens()->delete();

        // إنشاء توكن جديد للجلسة الحالية
        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return $this->successResponse(
            [
                'token' => $token,
            ],
            'Password changed successfully.'
        );
    }
}
