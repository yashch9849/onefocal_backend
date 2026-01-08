<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * List all orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['vendor', 'user', 'items.variant.product']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vendor
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($orders, 'Orders retrieved successfully');
    }

    /**
     * View order details
     */
    public function show(Order $order)
    {
        $order->load([
            'vendor',
            'user',
            'items.variant.product',
            'statusHistory.changedBy',
        ]);

        return $this->success($order, 'Order details retrieved successfully');
    }
}
