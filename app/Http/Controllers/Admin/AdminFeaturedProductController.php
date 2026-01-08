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
        $query = FeaturedProduct::with(['product.vendor', 'product.category']);

        // Filter by section
        if ($request->has('section')) {
            $query->where('section', $request->section);
        }

        $featuredProducts = $query->orderBy('priority')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($featuredProducts, 'Featured products retrieved successfully');
    }

    /**
     * Create a featured product
     */
    public function store(Request $request)
    {
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

        // Verify product is active
        $product = Product::findOrFail($request->product_id);
        if ($product->status !== 'active') {
            return $this->error(
                'Only active products can be featured.',
                'PRODUCT_NOT_ELIGIBLE',
                400
            );
        }

        // Get next priority if not provided
        $priority = $request->priority ?? FeaturedProduct::where('section', $request->section)->max('priority') + 1 ?? 0;

        $featuredProduct = FeaturedProduct::create([
            'product_id' => $request->product_id,
            'section' => $request->section,
            'priority' => $priority,
        ]);

        $featuredProduct->load(['product.vendor', 'product.category']);

        return $this->success($featuredProduct, 'Product featured successfully', 201);
    }

    /**
     * Delete a featured product
     */
    public function destroy(FeaturedProduct $featuredProduct)
    {
        $featuredProduct->delete();

        return $this->success(null, 'Featured product removed successfully');
    }
}
