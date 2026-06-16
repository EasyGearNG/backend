<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VendorWelcome;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:15',
            'role' => 'in:customer,vendor',
            
            // Vendor-specific fields (required if role is vendor)
            'business_name' => 'required_if:role,vendor|string|max:255',
            'business_address' => 'required_if:role,vendor|string|max:500',
            'business_phone' => 'required_if:role,vendor|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $isVendor = $request->role === 'vendor';

        $user = DB::transaction(function () use ($request, $isVendor) {
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'role' => $request->role ?? 'customer',
                'is_active' => true,
                'email_verified_at' => $isVendor ? null : now(),
            ]);

            if ($isVendor) {
                Vendor::create([
                    'user_id' => $user->id,
                    'name' => $request->business_name,
                    'contact_email' => $user->email,
                    'contact_phone' => $request->business_phone,
                    'address' => $request->business_address,
                    'commission_rate' => 15.00,
                    'is_active' => false,
                ]);
            }

            return $user;
        });

        if ($isVendor) {
            Log::info('Vendor signup: initiating email verification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'mail_mailer' => config('mail.default'),
                'mail_from' => config('mail.from.address'),
                'app_url' => config('app.url'),
            ]);

            $verificationEmailSent = false;
            $verificationEmailError = null;

            try {
                $user->sendEmailVerificationNotification();
                $verificationEmailSent = true;
            } catch (\Throwable $e) {
                $verificationEmailError = $e->getMessage();
                Log::error('Vendor signup: verification email failed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'mail_mailer' => config('mail.default'),
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $user->load('vendor');

            return response()->json([
                'success' => true,
                'message' => $verificationEmailSent
                    ? 'Registration successful. Please check your email to verify your address and complete signup.'
                    : 'Registration successful, but we could not send the verification email. Please use resend verification or contact support.',
                'data' => [
                    'user' => $user,
                    'requires_email_verification' => true,
                    'verification_email_sent' => $verificationEmailSent,
                    'approval_status' => $user->vendor?->approval_status ?? 'pending',
                ],
            ], 201);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user->load('addresses'),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);

        $response->cookie(
            'access_token',
            $token,
            config('session.lifetime', 120),
            '/',
            null,
            true,
            true,
            false,
            'Strict'
        );

        return $response;
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string', // Can be email or username
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $login = $request->login;
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated'
            ], 403);
        }

        if ($user->role === 'vendor' && !$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address before logging in. Check your inbox for the verification link.',
                'error_code' => 'email_not_verified',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load($user->role === 'vendor' ? ['vendor', 'addresses'] : 'addresses');

        $loginData = [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];

        if ($user->role === 'vendor' && $user->vendor) {
            $loginData['approval_status'] = $user->vendor->approval_status;
        }

        // Store token in HTTP-only cookie
        $response = response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $loginData,
        ], 200);

        // Set access token cookie (HTTP-only for security)
        $response->cookie(
            'access_token',
            $token,
            config('session.lifetime', 120), // Cookie lifetime in minutes
            '/', // Path
            null, // Domain
            request()->secure(), // Secure (HTTPS only in production)
            true, // HTTP-only
            false, // Raw
            'Strict' // SameSite
        );

        return $response;
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load($user->role === 'vendor' ? ['vendor', 'addresses'] : 'addresses');

        $data = ['user' => $user];

        if ($user->role === 'vendor' && $user->vendor) {
            $data['approval_status'] = $user->vendor->approval_status;
        }

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'data' => $data,
        ], 200);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:15',
            
            // Address fields (all optional)
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'address_type' => 'nullable|in:shipping,billing',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update user basic info
        $user->update($request->only(['name', 'username', 'email', 'phone_number']));

        // Update or create address if address fields are provided
        if ($request->has('address_line1')) {
            $addressData = $request->only([
                'address_line1',
                'address_line2',
                'city',
                'state',
                'postal_code',
                'country',
                'address_type'
            ]);

            // Get user's default address or create new one
            $address = $user->addresses()->where('is_default', true)->first();
            
            if ($address) {
                // Update existing default address
                $address->update($addressData);
            } else {
                // Create new default address
                $addressData['user_id'] = $user->id;
                $addressData['is_default'] = true;
                $addressData['address_type'] = $addressData['address_type'] ?? 'shipping';
                $user->addresses()->create($addressData);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->fresh()->load('addresses')
            ]
        ], 200);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ], 200);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        $response = response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);

        // Clear the access token cookie
        $response->cookie(
            'access_token',
            '',
            -1, // Expire immediately
            '/',
            null,
            request()->secure(),
            true,
            false,
            'Strict'
        );

        return $response;
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        $response = response()->json([
            'success' => true,
            'message' => 'Logged out from all devices successfully'
        ], 200);

        // Clear the access token cookie
        $response->cookie(
            'access_token',
            '',
            -1, // Expire immediately
            '/',
            null,
            request()->secure(),
            true,
            false,
            'Strict'
        );

        return $response;
    }

    /**
     * Refresh access token
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Delete the current token
        $request->user()->currentAccessToken()->delete();

        // Create a new token
        $newToken = $user->createToken('auth_token')->plainTextToken;

        $response = response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'user' => $user
            ]
        ], 200);

        // Set new access token cookie
        $response->cookie(
            'access_token',
            $newToken,
            config('session.lifetime', 120),
            '/',
            null,
            request()->secure(),
            true,
            false,
            'Strict'
        );

        return $response;
    }

    /**
     * Verify vendor email (signed link from email).
     */
    public function verifyEmail(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This verification link is invalid or has expired.',
                ], 403);
            }

            return redirect(rtrim(config('frontend.url'), '/') . '/verify-email?status=invalid');
        }

        $user = User::with('vendor')->findOrFail($request->query('userId'));

        if (!hash_equals((string) $user->getEmailForVerification(), (string) $request->query('email'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification link.',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            $payload = [
                'success' => true,
                'message' => 'Email already verified.',
                'data' => ['already_verified' => true],
            ];

            if ($request->expectsJson()) {
                return response()->json($payload);
            }

            return redirect(rtrim(config('frontend.url'), '/') . '/verify-email?status=already_verified');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        if ($user->role === 'vendor') {
            try {
                Mail::to($user->email)->send(new VendorWelcome(
                    $user,
                    $user->vendor?->name
                ));
            } catch (\Exception $e) {
                Log::warning('Failed to send vendor welcome email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($request->expectsJson()) {
            $user = $user->fresh()->load('vendor');

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully. You can now log in.',
                'data' => [
                    'user' => $user,
                    'approval_status' => $user->vendor?->approval_status ?? 'pending',
                ],
            ]);
        }

        return redirect(rtrim(config('frontend.url'), '/') . '/verify-email?status=success');
    }

    /**
     * Resend vendor email verification link.
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        Log::info('Vendor verification resend: request received', [
            'email' => $request->email,
            'mail_mailer' => config('mail.default'),
            'mail_from' => config('mail.from.address'),
        ]);

        $user = User::where('email', $request->email)->where('role', 'vendor')->first();

        if (!$user) {
            Log::info('Vendor verification resend: no vendor account found for email', [
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'If that email is registered as a vendor, a verification link has been sent.',
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            Log::info('Vendor verification resend: email already verified', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'This email is already verified. You can log in.',
            ]);
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('Vendor verification resend: send failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'mail_mailer' => config('mail.default'),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send verification email. Please try again later.',
            ], 500);
        }

        Log::info('Vendor verification resend: email sent successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent. Please check your inbox.',
        ]);
    }

    /**
     * Send password reset link to user's email
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email address.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to send reset link. Please try again.'
        ], 500);
    }

    /**
     * Reset password using token from email link
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => __($status)
        ], 400);
    }
}
