<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $vendor = $request->user()->vendor;
        
        if (!$vendor) {
            return $this->error('User is not associated with a vendor.', 'NO_VENDOR', 403);
        }

        $products = Product::where('vendor_id', $vendor->id)
            ->with(['variants.attributes', 'images', 'category'])
            ->get();

        return $this->success($products, 'Products retrieved successfully');
    }

    public function store(Request $request)
    {
        $vendor = $request->user()->vendor;
        
        if (!$vendor) {
            return $this->error('User is not associated with a vendor.', 'NO_VENDOR', 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
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
            'moq.required' => 'Minimum order quantity (MOQ) is required.',
            'moq.integer' => 'MOQ must be a whole number.',
            'moq.min' => 'MOQ must be at least 1.',
            'status.in' => 'Status must be either active or inactive.',
        ]);

        // Validate that category is a leaf category
        $category = \App\Models\Category::findOrFail($data['category_id']);
        if (!$category->isLeaf()) {
            return $this->error(
                'Products can only be attached to leaf (lowest-level) categories.',
                'INVALID_CATEGORY',
                400
            );
        }

        $product = Product::create([
            ...$data,
            'vendor_id' => $vendor->id,
            'status' => $data['status'] ?? 'active',
        ]);

        return $this->success($product->load(['variants.attributes', 'images', 'category']), 'Product created successfully', 201);
    }

    public function show(Request $request, Product $product)
    {
        $this->authorizeVendor($request, $product);

        return $this->success($product->load(['variants.attributes', 'images', 'category']), 'Product retrieved successfully');
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeVendor($request, $product);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'moq' => 'sometimes|required|integer|min:1',
            'status' => 'nullable|string|in:active,inactive',
        ], [
            'name.max' => 'Product name cannot exceed 255 characters.',
            'price.numeric' => 'Product price must be a number.',
            'price.min' => 'Product price must be at least 0.',
            'category_id.exists' => 'Selected category does not exist.',
            'moq.integer' => 'MOQ must be a whole number.',
            'moq.min' => 'MOQ must be at least 1.',
            'status.in' => 'Status must be either active or inactive.',
        ]);

        // Validate leaf category if category_id is being updated
        if (isset($data['category_id'])) {
            $category = \App\Models\Category::findOrFail($data['category_id']);
            if (!$category->isLeaf()) {
                return $this->error(
                    'Products can only be attached to leaf (lowest-level) categories.',
                    'INVALID_CATEGORY',
                    400
                );
            }
        }

        $product->update($data);

        return $this->success($product->load(['variants.attributes', 'images', 'category']), 'Product updated successfully');
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorizeVendor($request, $product);

        $product->delete();

        return $this->success(null, 'Product deleted successfully');
    }

    private function authorizeVendor(Request $request, Product $product)
    {
        $vendor = $request->user()->vendor;
        if (!$vendor || $product->vendor_id !== $vendor->id) {
            abort(403, 'You do not have permission to access this product.');
        }
    }
}
