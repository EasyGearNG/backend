<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'role' => $request->role ?? 'customer',
            'is_active' => true,
        ]);

        // Create vendor profile if user is registering as a vendor
        if ($request->role === 'vendor') {
            Vendor::create([
                'user_id' => $user->id,
                'name' => $request->business_name,
                'contact_email' => $user->email,
                'contact_phone' => $request->business_phone,
                'address' => $request->business_address,
                'commission_rate' => 15.00, // Default commission rate
                'is_active' => true,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Store token in HTTP-only cookie
        $response = response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user->load('addresses')
            ]
        ], 201);

        // Set access token cookie (HTTP-only for security)
        $response->cookie(
            'access_token',
            $token,
            config('session.lifetime', 120), // Cookie lifetime in minutes
            '/', // Path
            null, // Domain
            true, // Secure (HTTPS only in production)
            true, // HTTP-only
            false, // Raw
            'Strict' // SameSite
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

        $token = $user->createToken('auth_token')->plainTextToken;

        // Store token in HTTP-only cookie
        $response = response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->load('addresses')
            ]
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
        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'data' => [
                'user' => $request->user()->load('addresses')
            ]
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'username', 'email', 'phone_number']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->fresh()
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
}
