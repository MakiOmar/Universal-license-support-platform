<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use App\Notifications\VerifyCustomerEmailNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());
        $token = $customer->createToken('auth')->plainTextToken;

        $this->sendVerificationCode($customer);

        return response()->json([
            'customer' => new CustomerResource($customer),
            'token' => $token,
            'message' => 'Registered. Please verify your email with the code we sent.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $customer = Customer::where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => [__('The provided credentials are incorrect.')],
            ]);
        }

        if ($customer->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => [__('Your account is not active.')],
            ]);
        }

        $token = $customer->createToken('auth')->plainTextToken;

        return response()->json([
            'customer' => new CustomerResource($customer),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function sendVerificationEmail(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        if ($customer->email_verified_at) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $this->sendVerificationCode($customer);

        return response()->json(['message' => 'Verification code sent.']);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        /** @var Customer $customer */
        $customer = $request->user();
        $cached = Cache::get($this->verificationCacheKey($customer));

        if (! $cached || ! hash_equals((string) $cached, (string) $data['code'])) {
            throw ValidationException::withMessages([
                'code' => [__('Invalid or expired verification code.')],
            ]);
        }

        $customer->forceFill(['email_verified_at' => now()])->save();
        Cache::forget($this->verificationCacheKey($customer));

        return response()->json([
            'message' => 'Email verified.',
            'customer' => new CustomerResource($customer->fresh()),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            Password::broker('customers')->sendResetLink($request->only('email'));
        } catch (\Throwable) {
            // Ignore broker/mail failures so the API never returns a 500 for this flow.
        }

        return response()->json([
            'message' => __('If that email exists, a reset link has been sent.'),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password): void {
                $customer->forceFill(['password' => $password])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => __('Password reset successfully.')]);
    }

    public function me(Request $request): CustomerResource
    {
        return new CustomerResource($request->user());
    }

    public function updateProfile(UpdateProfileRequest $request): CustomerResource
    {
        /** @var Customer $customer */
        $customer = $request->user();
        $data = $request->validated();

        $customer->update($data);

        return new CustomerResource($customer->fresh());
    }

    protected function sendVerificationCode(Customer $customer): void
    {
        $code = (string) random_int(100000, 999999);
        Cache::put($this->verificationCacheKey($customer), $code, now()->addHour());
        $customer->notify(new VerifyCustomerEmailNotification($code));
    }

    protected function verificationCacheKey(Customer $customer): string
    {
        return 'customer_email_verify:'.$customer->id;
    }
}
