<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;

class VendorOrderController extends Controller
{
    /**
     * List all orders for the authenticated vendor
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

        $query = Order::where('vendor_id', $vendor->id)
            ->with(['user', 'items.variant.product']);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
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
    public function show(Request $request, Order $order)
    {
        // Verify vendor owns the order
        $vendor = $request->user()->vendor;
        if (!$vendor || $order->vendor_id !== $vendor->id) {
            return $this->forbidden('You do not have permission to view this order.');
        }

        $order->load([
            'user',
            'items.variant.product',
            'statusHistory.changedBy',
        ]);

        return $this->success($order, 'Order details retrieved successfully');
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        // Verify vendor owns the order
        $vendor = $request->user()->vendor;
        if (!$vendor || $order->vendor_id !== $vendor->id) {
            return $this->forbidden('You do not have permission to update this order.');
        }

        $request->validate([
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
        ], [
            'status.required' => 'Order status is required.',
            'status.in' => 'Invalid order status. Allowed values: pending, processing, shipped, delivered, cancelled.',
        ]);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Prevent status change if already delivered or cancelled
        if ($order->status === 'delivered') {
            return $this->error(
                'Cannot change status of a delivered order.',
                'INVALID_STATUS_CHANGE',
                400
            );
        }

        if ($order->status === 'cancelled') {
            return $this->error(
                'Cannot change status of a cancelled order.',
                'INVALID_STATUS_CHANGE',
                400
            );
        }

        // Update order status
        $order->update(['status' => $newStatus]);

        // Record status change in history
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $newStatus,
            'changed_by' => $request->user()->id,
        ]);

        // If order is cancelled, restore stock
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            foreach ($order->items as $item) {
                $item->variant->updateStock($item->quantity, true);
            }
        }

        $order->load([
            'user',
            'items.variant.product',
            'statusHistory.changedBy',
        ]);

        return $this->success($order, 'Order status updated successfully');
    }
}
