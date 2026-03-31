<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories as a flat, paginated list.
     * Supports ?parent_id=N, ?parent_id=null (root only), and ?search=term.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::with('parent')->withCount(['products', 'children']);

            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->has('parent_id')) {
                $parentId = $request->parent_id;
                $query->where('parent_id', $parentId === 'null' ? null : $parentId);
            }

            $categories = $query->orderBy('name')
                               ->paginate($request->get('per_page', 50));

            return response()->json([
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all categories as a nested tree.
     * Root categories contain their subcategories recursively.
     */
    public function tree(): JsonResponse
    {
        try {
            $tree = Category::with('childrenRecursive')
                ->withCount('products')
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Category tree retrieved successfully',
                'data' => $tree,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category tree',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single category by ID.
     * Includes parent info, direct subcategories, and product count.
     */
    public function show($id): JsonResponse
    {
        try {
            $category = Category::with(['parent', 'children' => function ($q) {
                    $q->withCount('products')->orderBy('name');
                }])
                ->withCount('products')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Category retrieved successfully',
                'data' => $category,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }
    }
}
