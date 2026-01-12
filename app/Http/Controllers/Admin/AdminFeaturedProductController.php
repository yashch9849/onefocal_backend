<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedProduct;
use App\Models\Product;
use Illuminate\Http\Request;

class AdminFeaturedProductController extends Controller
{
    /**
     * List all featured products
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'section' => 'nullable|in:homepage,category',
                'per_page' => 'nullable|integer|min:1|max:100',
            ], [
                'section.in' => 'Section must be either homepage or category.',
                'per_page.integer' => 'Per page must be a number.',
                'per_page.min' => 'Per page must be at least 1.',
                'per_page.max' => 'Per page cannot exceed 100.',
            ]);

            $query = FeaturedProduct::with(['product.vendor', 'product.category']);

            // Filter by section
            if ($request->has('section')) {
                $query->where('section', $request->section);
            }

            $featuredProducts = $query->orderBy('priority')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->success($featuredProducts, 'Featured products retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve featured products. Please try again.',
                'FEATURED_PRODUCTS_RETRIEVAL_ERROR',
                500
            );
        }
    }

    /**
     * Create a featured product
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'section' => 'required|in:homepage,category',
                'priority' => 'nullable|integer|min:0',
            ], [
                'product_id.required' => 'Product is required.',
                'product_id.exists' => 'Selected product does not exist.',
                'section.required' => 'Section is required.',
                'section.in' => 'Section must be either homepage or category.',
                'priority.integer' => 'Priority must be a whole number.',
                'priority.min' => 'Priority cannot be negative.',
            ]);

            // Check if product is already featured in this section
            $existing = FeaturedProduct::where('product_id', $request->product_id)
                ->where('section', $request->section)
                ->first();

            if ($existing) {
                return $this->error(
                    'This product is already featured in the ' . $request->section . ' section.',
                    'ALREADY_FEATURED',
                    400
                );
            }

            // Verify product exists and is active
            $product = Product::find($request->product_id);
            if (!$product) {
                return $this->error(
                    'Selected product does not exist.',
                    'PRODUCT_NOT_FOUND',
                    404
                );
            }

            if ($product->status !== 'active') {
                return $this->error(
                    'Only active products can be featured. Please activate the product first.',
                    'PRODUCT_NOT_ELIGIBLE',
                    400
                );
            }

            // Get next priority if not provided
            $priority = $request->priority ?? ((FeaturedProduct::where('section', $request->section)->max('priority') ?? 0) + 1);

            $featuredProduct = FeaturedProduct::create([
                'product_id' => $request->product_id,
                'section' => $request->section,
                'priority' => $priority,
            ]);

            $featuredProduct->load(['product.vendor', 'product.category']);

            return $this->success($featuredProduct, 'Product featured successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to feature product. Please try again.',
                'FEATURED_PRODUCT_CREATION_ERROR',
                500
            );
        }
    }

    /**
     * Delete a featured product
     */
    public function destroy(FeaturedProduct $featuredProduct)
    {
        try {
            $featuredProduct->delete();

            return $this->success(null, 'Featured product removed successfully');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to remove featured product. Please try again.',
                'FEATURED_PRODUCT_DELETION_ERROR',
                500
            );
        }
    }
}
