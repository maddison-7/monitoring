<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\OutboundChannelService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly OutboundChannelService $outboundChannelService)
    {
    }

    public function registerApplicant(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $user = User::query()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email' => $validated['email'],
            'role' => 'applicant',
            'password' => Hash::make($validated['password']),
        ]);

        Applicant::query()->create([
            'user_id' => $user->id,
            'phone' => $validated['phone'] ?? null,
        ]);

        $token = $user->createToken('applicant-api')->plainTextToken;

        return response()->json([
            'message' => 'Applicant account created successfully.',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'If email exists, OTP has been sent.']);
        }

        $otp = (string) random_int(100000, 999999);
        Cache::put('otp:' . strtolower($user->email), Hash::make($otp), now()->addMinutes(10));

        $this->outboundChannelService->sendEmail(
            (string) $user->email,
            'Ajira Portal Verification OTP',
            'Your verification code is: ' . $otp . '. It expires in 10 minutes.'
        );

        $phone = (string) optional($user->applicant)->phone;
        if ($phone !== '') {
            $this->outboundChannelService->sendSms(
                $phone,
                'Ajira OTP: ' . $otp . '. Expires in 10 minutes.'
            );
        }

        return response()->json(['message' => 'OTP generated and sent.']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $email = strtolower($validated['email']);
        $hashedOtp = Cache::get('otp:' . $email);

        if (!is_string($hashedOtp) || !Hash::check($validated['otp'], $hashedOtp)) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid or expired OTP.',
            ]);
        }

        Cache::forget('otp:' . $email);

        return response()->json(['message' => 'OTP verified successfully.']);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        LoginHistory::query()->create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'logged_in_at' => now(),
        ]);

        if ($user->applicant) {
            $user->applicant->update(['last_login_at' => now()]);
        }

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('applicant');

        return response()->json([
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(['email' => $validated['email']]);

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }
}
