<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    /**
     * List all categories with optional filtering
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'is_active' => 'nullable',
                'root_only' => 'nullable',
                'parent_id' => 'nullable|integer|exists:categories,id',
                'search' => 'nullable|string|max:255',
            ], [
                'parent_id.exists' => 'Selected parent category does not exist.',
                'search.max' => 'Search term cannot exceed 255 characters.',
            ]);

            $query = Category::with(['parent', 'children']);

            // Filter by active status - handle boolean values properly
            if ($request->has('is_active')) {
                $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($isActive !== null) {
                    $query->where('is_active', $isActive);
                }
            }

            // Filter by parent (root categories only) - handle boolean values properly
            if ($request->has('root_only')) {
                $rootOnly = filter_var($request->input('root_only'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($rootOnly === true) {
                    $query->whereNull('parent_id');
                }
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve categories. Please try again.',
                'CATEGORIES_RETRIEVAL_ERROR',
                500
            );
        }
    }

    /**
     * Show a specific category
     */
    public function show(Category $category)
    {
        try {
            $category->load(['parent', 'children', 'products']);

            return $this->success($category, 'Category retrieved successfully');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve category details. Please try again.',
                'CATEGORY_RETRIEVAL_ERROR',
                500
            );
        }
    }

    /**
     * Create a new category
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
                'parent_id' => ['nullable', 'exists:categories,id'],
                'is_active' => ['nullable'],
            ], [
                'name.required' => 'Category name is required.',
                'name.max' => 'Category name cannot exceed 255 characters.',
                'slug.unique' => 'This slug is already taken. Please use a different slug.',
                'parent_id.exists' => 'The selected parent category does not exist.',
            ]);

            // Convert is_active to boolean properly - handle false values correctly
            if (array_key_exists('is_active', $validated)) {
                $isActiveValue = $validated['is_active'];
                // Handle various formats: true, false, "true", "false", "1", "0", 1, 0
                if ($isActiveValue === false || $isActiveValue === 'false' || $isActiveValue === '0' || $isActiveValue === 0) {
                    $validated['is_active'] = false;
                } elseif ($isActiveValue === true || $isActiveValue === 'true' || $isActiveValue === '1' || $isActiveValue === 1) {
                    $validated['is_active'] = true;
                } else {
                    $validated['is_active'] = filter_var($isActiveValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
                }
            }

            // Validate parent category if provided
            if (isset($validated['parent_id'])) {
                $parent = Category::find($validated['parent_id']);
                if (!$parent) {
                    return $this->error(
                        'Selected parent category does not exist.',
                        'PARENT_CATEGORY_NOT_FOUND',
                        404
                    );
                }
            }

            // Generate slug from name if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
                
                // Ensure uniqueness
                $originalSlug = $validated['slug'];
                $counter = 1;
                while (Category::where('slug', $validated['slug'])->exists()) {
                    $validated['slug'] = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            // Set default is_active if not provided
            if (!isset($validated['is_active'])) {
                $validated['is_active'] = true;
            }

            $category = Category::create($validated);
            $category->load(['parent', 'children']);

            return $this->success($category, 'Category created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to create category. Please try again.',
                'CATEGORY_CREATION_ERROR',
                500
            );
        }
    }

    /**
     * Update a category
     */
    public function update(Request $request, Category $category)
    {
        try {
            $validated = $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
                'parent_id' => ['sometimes', 'nullable', 'exists:categories,id', function ($attribute, $value, $fail) use ($category) {
                    // Prevent category from being its own parent
                    if ($value == $category->id) {
                        $fail('A category cannot be its own parent.');
                    }
                    // Prevent circular references (category cannot be parent of its own ancestor)
                    $ancestors = $this->getAncestors($category);
                    if (in_array($value, $ancestors)) {
                        $fail('A category cannot be a parent of its own ancestor.');
                    }
                }],
                'is_active' => ['sometimes', 'nullable'],
            ], [
                'name.required' => 'Category name is required.',
                'name.max' => 'Category name cannot exceed 255 characters.',
                'slug.unique' => 'This slug is already taken. Please use a different slug.',
                'parent_id.exists' => 'The selected parent category does not exist.',
            ]);

            // Convert is_active to boolean properly if provided - handle false values correctly
            if (array_key_exists('is_active', $validated)) {
                $isActiveValue = $validated['is_active'];
                // Handle various formats: true, false, "true", "false", "1", "0", 1, 0
                if ($isActiveValue === false || $isActiveValue === 'false' || $isActiveValue === '0' || $isActiveValue === 0) {
                    $validated['is_active'] = false;
                } elseif ($isActiveValue === true || $isActiveValue === 'true' || $isActiveValue === '1' || $isActiveValue === 1) {
                    $validated['is_active'] = true;
                } else {
                    $validated['is_active'] = filter_var($isActiveValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
                }
            }

            // Generate slug from name if name changed and slug not provided
            if (isset($validated['name']) && !isset($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
                
                // Ensure uniqueness
                $originalSlug = $validated['slug'];
                $counter = 1;
                while (Category::where('slug', $validated['slug'])->where('id', '!=', $category->id)->exists()) {
                    $validated['slug'] = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            $category->update($validated);
            $category->load(['parent', 'children']);

            return $this->success($category, 'Category updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to update category. Please try again.',
                'CATEGORY_UPDATE_ERROR',
                500
            );
        }
    }

    /**
     * Delete a category
     */
    public function destroy(Category $category)
    {
        try {
            // Check if category has products
            $productCount = $category->products()->count();
            if ($productCount > 0) {
                return $this->error(
                    "Cannot delete category that has {$productCount} product(s). Please reassign or delete products first.",
                    'CATEGORY_HAS_PRODUCTS',
                    400
                );
            }

            // Check if category has children
            $childrenCount = $category->children()->count();
            if ($childrenCount > 0) {
                return $this->error(
                    "Cannot delete category that has {$childrenCount} subcategory(ies). Please delete or reassign subcategories first.",
                    'CATEGORY_HAS_CHILDREN',
                    400
                );
            }

            $category->delete();

            return $this->success(null, 'Category deleted successfully');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to delete category. Please try again.',
                'CATEGORY_DELETION_ERROR',
                500
            );
        }
    }

    /**
     * Get all ancestors of a category (for circular reference prevention)
     */
    private function getAncestors(Category $category, array $ancestors = []): array
    {
        if ($category->parent_id) {
            $parent = Category::find($category->parent_id);
            if ($parent) {
                $ancestors[] = $parent->id;
                return $this->getAncestors($parent, $ancestors);
            }
        }
        return $ancestors;
    }
}
