<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    /**
     * List all products
     */
    public function index(Request $request)
    {
        $query = Product::with(['vendor', 'category', 'variants.attributes', 'images']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vendor
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($products, 'Products retrieved successfully');
    }

    /**
     * View product details
     */
    public function show(Product $product)
    {
        $product->load([
            'vendor',
            'category',
            'variants.attributes',
            'images',
        ]);

        return $this->success($product, 'Product details retrieved successfully');
    }
}
