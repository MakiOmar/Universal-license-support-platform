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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());
        $token = $customer->createToken('auth')->plainTextToken;

        return response()->json([
            'customer' => new CustomerResource($customer),
            'token' => $token,
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

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        // Always return a generic success payload; never leak whether the email exists.
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
}
