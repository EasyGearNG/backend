<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DevToolsController extends Controller
{
    /**
     * GET /dev/summary
     */
    public function summary(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'users' => User::count(),
                'vendors' => Vendor::count(),
                'vendors_pending' => Vendor::where('is_active', false)->count(),
                'vendors_approved' => Vendor::where('is_active', true)->count(),
                'customers' => User::where('role', 'customer')->count(),
                'admins' => User::where('role', 'admin')->count(),
            ],
        ]);
    }

    /**
     * GET /dev/users?role=vendor&search=email
     */
    public function listUsers(Request $request): JsonResponse
    {
        $query = User::query()->with('vendor');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * GET /dev/vendors?search=email
     */
    public function listVendors(Request $request): JsonResponse
    {
        $query = Vendor::withTrashed()->with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contact_email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('approval_status')) {
            if ($request->approval_status === 'pending') {
                $query->where('is_active', false);
            } elseif ($request->approval_status === 'approved') {
                $query->where('is_active', true);
            }
        }

        $vendors = $query->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $vendors,
        ]);
    }

    /**
     * DELETE /dev/users  { "email": "user@example.com" }
     */
    public function deleteUserByEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            DB::transaction(function () use ($user) {
                $user->tokens()->delete();

                if ($user->vendor) {
                    $user->vendor->forceDelete();
                }

                $user->delete();
            });

            Log::warning('DevTools: user deleted by email', ['email' => $request->email]);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
                'data' => ['email' => $request->email],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /dev/vendors  { "email": "vendor@example.com" }
     * Matches vendor user email or vendor contact_email.
     */
    public function deleteVendorByEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $vendor = Vendor::withTrashed()
                ->with('user')
                ->where(function ($q) use ($request) {
                    $q->where('contact_email', $request->email)
                      ->orWhereHas('user', fn ($uq) => $uq->where('email', $request->email));
                })
                ->first();

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found for that email',
                ], 404);
            }

            DB::transaction(function () use ($vendor) {
                $vendor->is_active = false;
                $vendor->save();

                if ($vendor->user) {
                    $vendor->user->tokens()->delete();
                    $vendor->user->is_active = false;
                    $vendor->user->save();
                }

                if ($vendor->trashed()) {
                    $vendor->forceDelete();
                } else {
                    $vendor->delete();
                }
            });

            Log::warning('DevTools: vendor deleted by email', ['email' => $request->email]);

            return response()->json([
                'success' => true,
                'message' => 'Vendor deleted successfully (soft delete)',
                'data' => ['email' => $request->email],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vendor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /dev/vendors/hard  { "email": "vendor@example.com" }
     * Permanently removes vendor and linked user account.
     */
    public function hardDeleteVendorByEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->where('role', 'vendor')->first();

            if (!$user) {
                $vendor = Vendor::withTrashed()
                    ->where('contact_email', $request->email)
                    ->first();

                if (!$vendor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vendor user not found for that email',
                    ], 404);
                }

                $vendor->forceDelete();

                return response()->json([
                    'success' => true,
                    'message' => 'Orphan vendor record permanently deleted',
                    'data' => ['email' => $request->email],
                ]);
            }

            DB::transaction(function () use ($user) {
                $user->tokens()->delete();

                if ($user->vendor) {
                    $user->vendor->forceDelete();
                }

                $user->delete();
            });

            Log::warning('DevTools: vendor hard-deleted by email', ['email' => $request->email]);

            return response()->json([
                'success' => true,
                'message' => 'Vendor and user permanently deleted',
                'data' => ['email' => $request->email],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to hard delete vendor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
