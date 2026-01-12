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
        try {
            $request->validate([
                'status' => 'nullable|string',
                'vendor_id' => 'nullable|integer|exists:vendors,id',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'per_page' => 'nullable|integer|min:1|max:100',
            ], [
                'vendor_id.exists' => 'Selected vendor does not exist.',
                'from_date.date' => 'From date must be a valid date.',
                'to_date.date' => 'To date must be a valid date.',
                'to_date.after_or_equal' => 'To date must be after or equal to from date.',
                'per_page.integer' => 'Per page must be a number.',
                'per_page.min' => 'Per page must be at least 1.',
                'per_page.max' => 'Per page cannot exceed 100.',
            ]);

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve orders. Please try again.',
                'ORDERS_RETRIEVAL_ERROR',
                500
            );
        }
    }

    /**
     * View order details
     */
    public function show(Order $order)
    {
        try {
            $order->load([
                'vendor',
                'user',
                'items.variant.product',
                'statusHistory.changedBy',
            ]);

            return $this->success($order, 'Order details retrieved successfully');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve order details. Please try again.',
                'ORDER_RETRIEVAL_ERROR',
                500
            );
        }
    }
}
