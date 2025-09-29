<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VendorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $user = Auth::user();

        // Check if user is a vendor
        if ($user->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Vendor role required.'
            ], 403);
        }

        // Check if user has an associated vendor record
        if (!$user->vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor profile not found. Please complete your vendor registration.'
            ], 403);
        }

        // Check if vendor account is active
        if (!$user->vendor->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor account is not active. Please contact support.',
                'vendor_status' => $user->vendor->is_active ? 'active' : 'inactive'
            ], 403);
        }

        return $next($request);
    }
}
