<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    /**
     * List all products
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'status' => 'nullable|in:active,inactive',
                'vendor_id' => 'nullable|integer|exists:vendors,id',
                'category_id' => 'nullable|integer|exists:categories,id',
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
            ], [
                'status.in' => 'Status must be either active or inactive.',
                'vendor_id.exists' => 'Selected vendor does not exist.',
                'category_id.exists' => 'Selected category does not exist.',
                'search.max' => 'Search term cannot exceed 255 characters.',
                'per_page.integer' => 'Per page must be a number.',
                'per_page.min' => 'Per page must be at least 1.',
                'per_page.max' => 'Per page cannot exceed 100.',
            ]);

            $query = Product::with(['vendor', 'category', 'variants.attributes', 'images']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by vendor
            if ($request->has('vendor_id')) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Search by name or description
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            $products = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->success($products, 'Products retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve products. Please try again.',
                'PRODUCTS_RETRIEVAL_ERROR',
                500
            );
        }
    }

    /**
     * View product details
     */
    public function show(Product $product)
    {
        try {
            $product->load([
                'vendor',
                'category',
                'variants.attributes',
                'images',
            ]);

            return $this->success($product, 'Product details retrieved successfully');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve product details. Please try again.',
                'PRODUCT_RETRIEVAL_ERROR',
                500
            );
        }
    }

    /**
     * Create a new product
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
                'vendor_id' => 'nullable|exists:vendors,id',
                'moq' => 'required|integer|min:1',
                'status' => 'nullable|string|in:active,inactive',
            ], [
                'name.required' => 'Product name is required.',
                'name.max' => 'Product name cannot exceed 255 characters.',
                'price.required' => 'Product price is required.',
                'price.numeric' => 'Product price must be a number.',
                'price.min' => 'Product price must be at least 0.',
                'category_id.required' => 'Category is required.',
                'category_id.exists' => 'Selected category does not exist.',
                'vendor_id.exists' => 'Selected vendor does not exist.',
                'moq.required' => 'Minimum order quantity (MOQ) is required.',
                'moq.integer' => 'MOQ must be a whole number.',
                'moq.min' => 'MOQ must be at least 1.',
                'status.in' => 'Status must be either active or inactive.',
            ]);

            // Validate that category is a leaf category
            $category = Category::find($data['category_id']);
            if (!$category) {
                return $this->error(
                    'Selected category does not exist.',
                    'CATEGORY_NOT_FOUND',
                    404
                );
            }

            if (!$category->isLeaf()) {
                return $this->error(
                    'Products can only be attached to leaf (lowest-level) categories. Please select a category that has no subcategories.',
                    'INVALID_CATEGORY',
                    400
                );
            }

            // Verify vendor exists and is approved (if vendor_id is provided)
            if (isset($data['vendor_id']) && $data['vendor_id']) {
                $vendor = \App\Models\Vendor::find($data['vendor_id']);
                if (!$vendor) {
                    return $this->error(
                        'Selected vendor does not exist.',
                        'VENDOR_NOT_FOUND',
                        404
                    );
                }

                if ($vendor->status !== 'approved') {
                    return $this->error(
                        'Products can only be created for approved vendors. Please approve the vendor first.',
                        'VENDOR_NOT_APPROVED',
                        400
                    );
                }
            }

            $product = Product::create([
                ...$data,
                'status' => $data['status'] ?? 'active',
            ]);

            return $this->success(
                $product->load(['vendor', 'category', 'variants.attributes', 'images']),
                'Product created successfully',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to create product. Please try again.',
                'PRODUCT_CREATION_ERROR',
                500
            );
        }
    }

    /**
     * Update a product
     */
    public function update(Request $request, Product $product)
    {
        try {
            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'category_id' => 'sometimes|required|exists:categories,id',
                'vendor_id' => 'nullable|exists:vendors,id',
                'moq' => 'sometimes|required|integer|min:1',
                'status' => 'nullable|string|in:active,inactive',
            ], [
                'name.required' => 'Product name is required.',
                'name.max' => 'Product name cannot exceed 255 characters.',
                'price.required' => 'Product price is required.',
                'price.numeric' => 'Product price must be a number.',
                'price.min' => 'Product price must be at least 0.',
                'category_id.required' => 'Category is required.',
                'category_id.exists' => 'Selected category does not exist.',
                'vendor_id.exists' => 'Selected vendor does not exist.',
                'moq.required' => 'Minimum order quantity (MOQ) is required.',
                'moq.integer' => 'MOQ must be a whole number.',
                'moq.min' => 'MOQ must be at least 1.',
                'status.in' => 'Status must be either active or inactive.',
            ]);

            // Validate leaf category if category_id is being updated
            if (isset($data['category_id'])) {
                $category = Category::find($data['category_id']);
                if (!$category) {
                    return $this->error(
                        'Selected category does not exist.',
                        'CATEGORY_NOT_FOUND',
                        404
                    );
                }

                if (!$category->isLeaf()) {
                    return $this->error(
                        'Products can only be attached to leaf (lowest-level) categories. Please select a category that has no subcategories.',
                        'INVALID_CATEGORY',
                        400
                    );
                }
            }

            // Verify vendor if vendor_id is being updated (and not null)
            if (isset($data['vendor_id']) && $data['vendor_id']) {
                $vendor = \App\Models\Vendor::find($data['vendor_id']);
                if (!$vendor) {
                    return $this->error(
                        'Selected vendor does not exist.',
                        'VENDOR_NOT_FOUND',
                        404
                    );
                }

                if ($vendor->status !== 'approved') {
                    return $this->error(
                        'Products can only be assigned to approved vendors. Please approve the vendor first.',
                        'VENDOR_NOT_APPROVED',
                        400
                    );
                }
            } elseif (isset($data['vendor_id']) && $data['vendor_id'] === null) {
                // Allow setting vendor_id to null for admin products
                $data['vendor_id'] = null;
            }

            $product->update($data);

            return $this->success(
                $product->load(['vendor', 'category', 'variants.attributes', 'images']),
                'Product updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to update product. Please try again.',
                'PRODUCT_UPDATE_ERROR',
                500
            );
        }
    }

    /**
     * Delete a product
     */
    public function destroy(Product $product)
    {
        try {
            // Check if product has orders
            $hasOrders = \App\Models\OrderItem::whereHas('variant', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })->exists();

            if ($hasOrders) {
                return $this->error(
                    'Cannot delete product that has been ordered. Please deactivate it instead by setting status to inactive.',
                    'PRODUCT_HAS_ORDERS',
                    400
                );
            }

            $product->delete();

            return $this->success(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to delete product. Please try again.',
                'PRODUCT_DELETION_ERROR',
                500
            );
        }
    }
}
