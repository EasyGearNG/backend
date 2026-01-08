<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user.
     */
    public function index(): JsonResponse
    {
        try {
            $addresses = Auth::user()->addresses()->orderBy('is_default', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Addresses retrieved successfully',
                'data' => $addresses,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve addresses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single address by ID.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $address = Auth::user()->addresses()->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Address retrieved successfully',
                'data' => $address,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
            ], 404);
        }
    }

    /**
     * Create a new address for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'address_type' => 'required|in:shipping,billing',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // If setting as default, unset other defaults
            if ($request->is_default) {
                $user->addresses()->update(['is_default' => false]);
            }

            // If this is the first address, make it default automatically
            $isFirstAddress = $user->addresses()->count() === 0;

            $address = $user->addresses()->create([
                'address_line1' => $request->address_line1,
                'address_line2' => $request->address_line2,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'address_type' => $request->address_type,
                'is_default' => $request->is_default || $isFirstAddress,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Address created successfully',
                'data' => $address,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing address.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_line1' => 'sometimes|required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'sometimes|required|string|max:100',
            'postal_code' => 'sometimes|required|string|max:20',
            'country' => 'sometimes|required|string|max:100',
            'address_type' => 'sometimes|required|in:shipping,billing',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $address = Auth::user()->addresses()->findOrFail($id);

            DB::beginTransaction();

            // If setting as default, unset other defaults
            if ($request->is_default) {
                Auth::user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
            }

            $address->update($request->only([
                'address_line1',
                'address_line2',
                'city',
                'state',
                'postal_code',
                'country',
                'address_type',
                'is_default',
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully',
                'data' => $address->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException 
                    ? 'Address not found' 
                    : 'Failed to update address',
                'error' => $e->getMessage(),
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Delete an address.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $address = $user->addresses()->findOrFail($id);

            DB::beginTransaction();

            $wasDefault = $address->is_default;
            $address->delete();

            // If deleted address was default, set another address as default
            if ($wasDefault) {
                $newDefault = $user->addresses()->first();
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException 
                    ? 'Address not found' 
                    : 'Failed to delete address',
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Set an address as default.
     */
    public function setDefault(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $address = $user->addresses()->findOrFail($id);

            DB::beginTransaction();

            // Unset all other defaults
            $user->addresses()->update(['is_default' => false]);

            // Set this address as default
            $address->update(['is_default' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Default address updated successfully',
                'data' => $address->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to set default address',
            ], 500);
        }
    }
}
