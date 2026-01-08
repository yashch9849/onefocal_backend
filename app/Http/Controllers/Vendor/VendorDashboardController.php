<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorDashboardController extends Controller
{
    /**
     * Get dashboard statistics for the authenticated vendor
     */
    public function index(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->error(
                'User is not associated with a vendor.',
                'NO_VENDOR',
                403
            );
        }

        // Get total products
        $totalProducts = Product::where('vendor_id', $vendor->id)->count();

        // Get total variants across all products
        $totalVariants = ProductVariant::whereHas('product', function ($query) use ($vendor) {
            $query->where('vendor_id', $vendor->id);
        })->count();

        // Get total orders
        $totalOrders = Order::where('vendor_id', $vendor->id)->count();

        // Get total revenue (sum of all order totals)
        $totalRevenue = Order::where('vendor_id', $vendor->id)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        // Get recent orders count (last 30 days)
        $recentOrders = Order::where('vendor_id', $vendor->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Get low stock variants (stock < 10)
        $lowStockVariants = ProductVariant::whereHas('product', function ($query) use ($vendor) {
            $query->where('vendor_id', $vendor->id);
        })
        ->where('stock', '<', 10)
        ->count();

        // Get inactive products
        $inactiveProducts = Product::where('vendor_id', $vendor->id)
            ->where('status', 'inactive')
            ->count();

        return $this->success([
            'total_products' => $totalProducts,
            'total_variants' => $totalVariants,
            'total_orders' => $totalOrders,
            'total_revenue' => round($totalRevenue, 2),
            'recent_orders' => $recentOrders,
            'low_stock_variants' => $lowStockVariants,
            'inactive_products' => $inactiveProducts,
        ], 'Dashboard statistics retrieved successfully');
    }
}
