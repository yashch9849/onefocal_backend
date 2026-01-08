<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboardStats(Request $request)
    {
        // Total vendors
        $totalVendors = Vendor::count();
        $pendingVendors = Vendor::where('status', 'pending')->count();
        $approvedVendors = Vendor::where('status', 'approved')->count();

        // Total products
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $inactiveProducts = Product::where('status', 'inactive')->count();

        // Total orders
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $completedOrders = Order::where('status', 'delivered')->count();

        // Total revenue
        $totalRevenue = Order::where('status', '!=', 'cancelled')
            ->sum('total');

        // Total users
        $totalUsers = User::count();
        $pendingUsers = User::where('approval_status', 'pending')->count();
        $approvedUsers = User::where('approval_status', 'approved')->count();

        // Recent orders (last 7 days)
        $recentOrders = Order::where('created_at', '>=', now()->subDays(7))
            ->count();

        // Revenue this month
        $monthlyRevenue = Order::where('status', '!=', 'cancelled')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        return $this->success([
            'vendors' => [
                'total' => $totalVendors,
                'pending' => $pendingVendors,
                'approved' => $approvedVendors,
            ],
            'products' => [
                'total' => $totalProducts,
                'active' => $activeProducts,
                'inactive' => $inactiveProducts,
            ],
            'orders' => [
                'total' => $totalOrders,
                'pending' => $pendingOrders,
                'completed' => $completedOrders,
                'recent_7_days' => $recentOrders,
            ],
            'revenue' => [
                'total' => round($totalRevenue, 2),
                'this_month' => round($monthlyRevenue, 2),
            ],
            'users' => [
                'total' => $totalUsers,
                'pending' => $pendingUsers,
                'approved' => $approvedUsers,
            ],
        ], 'Dashboard statistics retrieved successfully');
    }
}
