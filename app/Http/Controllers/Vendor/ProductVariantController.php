<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    /**
     * List all variants for a product
     */
    public function index($productId)
    {
        $vendor = auth()->user()->vendor;
        if (!$vendor) {
            return $this->error('User is not associated with a vendor.', 'NO_VENDOR', 403);
        }

        $product = Product::where('id', $productId)
            ->where('vendor_id', $vendor->id)
            ->first();

        if (!$product) {
            return $this->notFound('Product not found or you do not have access to it.');
        }

        $variants = $product->variants()->with('attributes')->get();
        return $this->success($variants, 'Product variants retrieved successfully');
    }

    /**
     * Store a new variant
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'price_override' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'required|string|max:255|unique:product_variants,sku',
            'attributes' => 'required|array',
            'attributes.*.attribute_name' => 'required|string',
            'attributes.*.attribute_value' => 'required|string',
        ], [
            'price_override.numeric' => 'Price override must be a number.',
            'price_override.min' => 'Price override must be at least 0.',
            'stock.required' => 'Stock quantity is required.',
            'stock.integer' => 'Stock quantity must be a whole number.',
            'stock.min' => 'Stock quantity cannot be negative.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU already exists. Please use a different SKU.',
            'attributes.required' => 'Attributes are required (e.g., color, frame_type).',
            'attributes.array' => 'Attributes must be an array.',
            'attributes.*.attribute_name.required' => 'Each attribute must have a name.',
            'attributes.*.attribute_value.required' => 'Each attribute must have a value.',
        ]);

        $vendor = auth()->user()->vendor;
        if (!$vendor) {
            return $this->error('User is not associated with a vendor.', 'NO_VENDOR', 403);
        }

        $product = Product::where('id', $productId)
            ->where('vendor_id', $vendor->id)
            ->first();

        if (!$product) {
            return $this->notFound('Product not found or you do not have access to it.');
        }

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $request->sku,
            'price_override' => $request->price_override,
            'stock' => $request->stock,
        ]);

        // Create attributes using EAV pattern
        if ($request->has('attributes') && is_array($request->attributes)) {
            foreach ($request->attributes as $attr) {
                $variant->setAttributeValue($attr['attribute_name'], $attr['attribute_value']);
            }
        }

        $variant->load('attributes');
        return $this->success($variant, 'Product variant created successfully', 201);
    }

    /**
     * Update a variant
     */
    public function update(Request $request, ProductVariant $variant)
    {
        // Load product relationship if not already loaded
        if (!$variant->relationLoaded('product')) {
            $variant->load('product');
        }

        // Verify vendor owns the product that this variant belongs to
        $vendor = auth()->user()->vendor;
        if (!$vendor || $variant->product->vendor_id !== $vendor->id) {
            return $this->forbidden('You do not have permission to update this variant.');
        }

        $request->validate([
            'price_override' => 'nullable|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'sku' => 'sometimes|string|max:255|unique:product_variants,sku,' . $variant->id,
            'attributes' => 'nullable|array',
            'attributes.*.attribute_name' => 'required_with:attributes|string',
            'attributes.*.attribute_value' => 'required_with:attributes|string',
        ], [
            'price_override.numeric' => 'Price override must be a number.',
            'price_override.min' => 'Price override must be at least 0.',
            'stock.integer' => 'Stock quantity must be a whole number.',
            'stock.min' => 'Stock quantity cannot be negative.',
            'sku.unique' => 'This SKU already exists. Please use a different SKU.',
            'attributes.array' => 'Attributes must be an array.',
            'attributes.*.attribute_name.required_with' => 'Each attribute must have a name.',
            'attributes.*.attribute_value.required_with' => 'Each attribute must have a value.',
        ]);

        // Update variant fields
        $variant->update($request->only([
            'price_override', 'stock', 'sku'
        ]));

        // Update attributes if provided
        if ($request->has('attributes') && is_array($request->attributes)) {
            foreach ($request->attributes as $attr) {
                $variant->setAttributeValue($attr['attribute_name'], $attr['attribute_value']);
            }
        }

        $variant->load('attributes');
        return $this->success($variant, 'Product variant updated successfully');
    }

    /**
     * Delete a variant
     */
    public function destroy(ProductVariant $variant)
    {
        // Load product relationship if not already loaded
        if (!$variant->relationLoaded('product')) {
            $variant->load('product');
        }

        // Verify vendor owns the product that this variant belongs to
        $vendor = auth()->user()->vendor;
        if (!$vendor || $variant->product->vendor_id !== $vendor->id) {
            return $this->forbidden('You do not have permission to delete this variant.');
        }

        $variant->delete();

        return $this->success(null, 'Product variant deleted successfully');
    }
}
