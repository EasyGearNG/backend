<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminDiscountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Discount::query();

            if ($request->filled('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . strtoupper($request->code) . '%');
            }

            $discounts = $query->latest()->paginate($request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'message' => 'Discount codes retrieved successfully',
                'data'    => $discounts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve discount codes',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code'              => 'required|string|max:50|unique:discounts,code',
            'discount_type'     => 'required|in:percentage,fixed',
            'discount_value'    => 'required|numeric|min:0.01',
            'valid_from'        => 'required|date',
            'valid_to'          => 'required|date|after_or_equal:valid_from',
            'min_order_amount'  => 'nullable|numeric|min:0',
            'max_uses'          => 'nullable|integer|min:1',
            'is_active'         => 'nullable|boolean',
        ]);

        // Percentage can't exceed 100
        $validator->after(function ($v) use ($request) {
            if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
                $v->errors()->add('discount_value', 'Percentage discount cannot exceed 100.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $discount = Discount::create([
                'code'             => strtoupper($request->code),
                'discount_type'    => $request->discount_type,
                'discount_value'   => $request->discount_value,
                'valid_from'       => $request->valid_from,
                'valid_to'         => $request->valid_to,
                'min_order_amount' => $request->input('min_order_amount', 0),
                'max_uses'         => $request->max_uses,
                'is_active'        => $request->input('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Discount code created successfully',
                'data'    => $discount,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create discount code',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $discount = Discount::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Discount code retrieved successfully',
                'data'    => $discount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Discount code not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $discount = Discount::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Discount code not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code'             => ['sometimes', 'string', 'max:50', Rule::unique('discounts', 'code')->ignore($discount->id)],
            'discount_type'    => 'sometimes|in:percentage,fixed',
            'discount_value'   => 'sometimes|numeric|min:0.01',
            'valid_from'       => 'sometimes|date',
            'valid_to'         => 'sometimes|date|after_or_equal:valid_from',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'is_active'        => 'sometimes|boolean',
        ]);

        $validator->after(function ($v) use ($request, $discount) {
            $type  = $request->input('discount_type', $discount->discount_type);
            $value = $request->input('discount_value', $discount->discount_value);
            if ($type === 'percentage' && $value > 100) {
                $v->errors()->add('discount_value', 'Percentage discount cannot exceed 100.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only([
                'discount_type', 'discount_value', 'valid_from',
                'valid_to', 'min_order_amount', 'max_uses', 'is_active',
            ]);

            if ($request->filled('code')) {
                $data['code'] = strtoupper($request->code);
            }

            $discount->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Discount code updated successfully',
                'data'    => $discount->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update discount code',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $discount = Discount::findOrFail($id);
            $discount->delete();

            return response()->json([
                'success' => true,
                'message' => 'Discount code deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Discount code not found',
            ], 404);
        }
    }
}
