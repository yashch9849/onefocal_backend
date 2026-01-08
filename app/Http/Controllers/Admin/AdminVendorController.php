<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class AdminVendorController extends Controller
{
    /**
     * List all vendors with filters
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,suspended',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ], [
            'status.in' => 'Status must be one of: pending, approved, suspended.',
            'search.max' => 'Search term cannot exceed 255 characters.',
            'per_page.integer' => 'Per page must be a number.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 100.',
        ]);

        $query = Vendor::with(['user.role', 'products']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $vendors = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($vendors, 'Vendors retrieved successfully');
    }

    /**
     * Approve a vendor
     */
    public function approve(Request $request, Vendor $vendor)
    {
        if ($vendor->status === 'approved') {
            return $this->error(
                'Vendor is already approved.',
                'ALREADY_APPROVED',
                400
            );
        }

        $vendor->update([
            'status' => 'approved',
        ]);

        // Approve the user associated with this vendor (1:1 relationship)
        if ($vendor->user) {
            $vendor->user->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
            ]);
        }

        $vendor->load(['user.role', 'products']);

        return $this->success($vendor, 'Vendor approved successfully');
    }

    /**
     * Reject a vendor
     */
    public function reject(Request $request, Vendor $vendor)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ], [
            'reason.max' => 'Rejection reason cannot exceed 500 characters.',
        ]);

        if ($vendor->status === 'rejected') {
            return $this->error(
                'Vendor is already rejected.',
                'ALREADY_REJECTED',
                400
            );
        }

        $vendor->update([
            'status' => 'suspended', // Using suspended as rejection status
        ]);

        // Reject the user associated with this vendor (1:1 relationship)
        if ($vendor->user) {
            $vendor->user->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $request->reason ?? 'Vendor account rejected by administrator.',
            ]);
        }

        $vendor->load(['user.role', 'products']);

        return $this->success($vendor, 'Vendor rejected successfully');
    }

    /**
     * View vendor details
     */
    public function show(Vendor $vendor)
    {
        $vendor->load(['user.role', 'products.category']);

        return $this->success($vendor, 'Vendor details retrieved successfully');
    }
}
