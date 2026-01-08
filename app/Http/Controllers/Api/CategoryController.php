<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List all active categories (public endpoint)
     * Can be used by customers, vendors, or anyone to browse categories
     */
    public function index(Request $request)
    {
        $query = Category::where('is_active', true)
            ->with(['parent', 'children' => function ($q) {
                $q->where('is_active', true);
            }]);

        // Filter by root categories only
        if ($request->has('root_only') && $request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        // Filter by parent_id
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderBy('name')->get();

        return $this->success($categories, 'Categories retrieved successfully');
    }

    /**
     * Show a specific category with its products count (public endpoint)
     */
    public function show(Category $category)
    {
        // Only show active categories
        if (!$category->is_active) {
            return $this->notFound('Category not found.');
        }

        $category->load([
            'parent',
            'children' => function ($q) {
                $q->where('is_active', true);
            }
        ]);

        // Add products count (only active products)
        $category->products_count = $category->products()
            ->where('status', 'active')
            ->count();

        return $this->success($category, 'Category retrieved successfully');
    }
}
