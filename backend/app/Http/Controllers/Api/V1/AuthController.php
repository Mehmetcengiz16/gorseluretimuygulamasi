<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CreditTransactionType;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Setting;
use App\Models\User;
use App\Services\Credits\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, CreditService $credits): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = DB::transaction(function () use ($data, $credits) {
            $user = User::create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
                'last_login_at' => now(),
            ]);

            $bonus = (int) Setting::get('credits.signup_bonus');
            if ($bonus > 0) {
                $credits->credit($user, $bonus, CreditTransactionType::SignupBonus, null, 'Hoş geldin bonusu');
            }

            return $user;
        });

        return $this->tokenResponse($user, $data['device_name'] ?? 'mobile', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', mb_strtolower($data['email']))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'E-posta veya şifre hatalı.']);
        }

        if (! $user->is_active) {
            throw ApiException::accountDisabled();
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->tokenResponse($user, $data['device_name'] ?? 'mobile');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Çıkış yapıldı.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Kullanıcının var olup olmadığını sızdırmamak için her durumda aynı yanıt.
        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Hesap mevcutsa şifre sıfırlama bağlantısı gönderildi.']);
    }

    private function tokenResponse(User $user, string $device, int $status = 200): JsonResponse
    {
        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user->refresh()),
        ], $status);
    }
}
