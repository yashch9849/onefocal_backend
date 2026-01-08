<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    /**
     * List all orders for the authenticated customer
     */
    public function index(Request $request)
    {
        $query = Order::where('user_id', $request->user()->id)
            ->with(['vendor', 'items.variant.product']);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vendor if provided
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($orders, 'Orders retrieved successfully');
    }

    /**
     * View order details
     */
    public function show(Request $request, Order $order)
    {
        // Verify customer owns the order
        if ($order->user_id !== $request->user()->id) {
            return $this->forbidden('You do not have permission to view this order.');
        }

        $order->load([
            'vendor',
            'items.variant.product',
            'statusHistory.changedBy',
        ]);

        return $this->success($order, 'Order details retrieved successfully');
    }
}
