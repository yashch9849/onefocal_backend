<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * Upload and store image for a product
     */
    public function store(Request $request, Product $product)
    {
        // Verify vendor owns the product
        $vendor = $request->user()->vendor;
        if (!$vendor || $product->vendor_id !== $vendor->id) {
            return $this->forbidden('You do not have permission to add images to this product.');
        }

        // Validate image file or URL
        $request->validate([
            'image' => 'required_without:image_url|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'image_url' => 'required_without:image|string|url',
            'position' => 'nullable|integer|min:0',
        ], [
            'image.required_without' => 'Either image file or image URL is required.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Image must be one of: jpeg, png, jpg, gif, webp.',
            'image.max' => 'Image size cannot exceed 5MB.',
            'image_url.required_without' => 'Either image file or image URL is required.',
            'image_url.url' => 'Please provide a valid image URL.',
            'position.integer' => 'Position must be a whole number.',
            'position.min' => 'Position cannot be negative.',
        ]);

        $imagePath = null;

        // Handle file upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('products/' . $product->id, $filename, 'public');
            $imagePath = Storage::url($path);
        } 
        // Handle URL
        elseif ($request->has('image_url')) {
            $imagePath = $request->image_url;
        }

        // Get next position if not provided
        $position = $request->position ?? ProductImage::where('product_id', $product->id)->max('position') + 1 ?? 0;

        $image = ProductImage::create([
            'product_id' => $product->id,
            'image_url' => $imagePath,
            'position' => $position,
        ]);

        return $this->success($image, 'Product image uploaded successfully', 201);
    }

    /**
     * List all images for a product
     */
    public function index(Product $product, Request $request)
    {
        // Verify vendor owns the product
        $vendor = $request->user()->vendor;
        if (!$vendor || $product->vendor_id !== $vendor->id) {
            return $this->forbidden('You do not have permission to view images for this product.');
        }

        $images = ProductImage::where('product_id', $product->id)
            ->orderBy('position')
            ->get();

        return $this->success($images, 'Product images retrieved successfully');
    }

    /**
     * Delete a product image
     */
    public function destroy(Request $request, Product $product, $imageId)
    {
        // Verify vendor owns the product
        $vendor = $request->user()->vendor;
        if (!$vendor || $product->vendor_id !== $vendor->id) {
            return $this->forbidden('You do not have permission to delete images from this product.');
        }

        // Find the image
        $image = ProductImage::where('id', $imageId)
            ->where('product_id', $product->id)
            ->first();

        if (!$image) {
            return $this->notFound('Image not found or does not belong to this product.');
        }

        // Delete file from storage if it's a local file
        if (strpos($image->image_url, '/storage/') === 0) {
            $filePath = str_replace('/storage/', '', $image->image_url);
            Storage::disk('public')->delete($filePath);
        }

        $image->delete();

        return $this->success(null, 'Product image deleted successfully');
    }
}
