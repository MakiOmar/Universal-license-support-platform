<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $customer = Customer::create([
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'company' => $data['company'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => 'active',
        ]);

        $token = $this->generateToken($customer);

        return response()->json([
            'customer' => new \App\Http\Resources\Api\V1\CustomerResource($customer),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($customer->status !== 'active') {
            return response()->json([
                'message' => 'Account is not active.',
            ], 403);
        }

        $token = $this->generateToken($customer);

        return response()->json([
            'customer' => new \App\Http\Resources\Api\V1\CustomerResource($customer),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer) {
            // Don't reveal if email exists
            return response()->json([
                'message' => 'If the email exists, a password reset link has been sent.',
            ]);
        }

        // Generate reset token (in production, store in password_resets table)
        $resetToken = Str::random(64);
        // Store in cache for 1 hour
        \Illuminate\Support\Facades\Cache::put(
            "password_reset_{$customer->id}",
            $resetToken,
            now()->addHour()
        );

        // In production, send email with reset link
        // Mail::to($customer->email)->send(new ResetPasswordMail($resetToken));

        return response()->json([
            'message' => 'If the email exists, a password reset link has been sent.',
            'reset_token' => $resetToken, // Remove in production
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::where('email', $request->email)->firstOrFail();

        $storedToken = \Illuminate\Support\Facades\Cache::get("password_reset_{$customer->id}");

        if (! $storedToken || $storedToken !== $request->token) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        $customer->password_hash = Hash::make($request->password);
        $customer->save();

        \Illuminate\Support\Facades\Cache::forget("password_reset_{$customer->id}");

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }

    protected function generateToken(Customer $customer): string
    {
        // Simple token generation (in production, use Laravel Sanctum or JWT)
        $token = Str::random(80);
        \Illuminate\Support\Facades\Cache::put(
            "customer_token_{$token}",
            $customer->id,
            now()->addDays(30)
        );

        return $token;
    }
}

