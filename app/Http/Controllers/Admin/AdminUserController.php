<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * List all users with filters
     */
    public function index(Request $request)
    {
        $request->validate([
            'approval_status' => 'nullable|in:pending,approved,rejected',
            'role' => 'nullable|in:admin,vendor,customer',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ], [
            'approval_status.in' => 'Approval status must be one of: pending, approved, rejected.',
            'role.in' => 'Role must be one of: admin, vendor, customer.',
            'search.max' => 'Search term cannot exceed 255 characters.',
            'per_page.integer' => 'Per page must be a number.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 100.',
        ]);

        $query = User::with(['role', 'vendor']);

        // Filter by approval status
        if ($request->has('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Search by name or email
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($users, 'Users retrieved successfully');
    }

    /**
     * View user details
     */
    public function show(User $user)
    {
        $user->load(['role', 'vendor']);

        return $this->success($user, 'User details retrieved successfully');
    }

    /**
     * Approve a user
     */
    public function approve(Request $request, User $user)
    {
        if ($user->approval_status === 'approved') {
            return $this->error(
                'User is already approved.',
                'ALREADY_APPROVED',
                400
            );
        }

        // Update user approval status
        $user->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'rejection_reason' => null, // Clear any previous rejection reason
        ]);

        // If user is a vendor, also approve the vendor
        if ($user->role && $user->role->name === 'vendor' && $user->vendor) {
            $user->vendor->update([
                'status' => 'approved',
            ]);
        }

        $user->load(['role', 'vendor']);

        return $this->success($user, 'User approved successfully');
    }

    /**
     * Reject a user
     */
    public function reject(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ], [
            'reason.max' => 'Rejection reason cannot exceed 500 characters.',
        ]);

        if ($user->approval_status === 'rejected') {
            return $this->error(
                'User is already rejected.',
                'ALREADY_REJECTED',
                400
            );
        }

        // Update user approval status
        $user->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->reason ?? 'Account rejected by administrator.',
        ]);

        // If user is a vendor, also suspend the vendor
        if ($user->role && $user->role->name === 'vendor' && $user->vendor) {
            $user->vendor->update([
                'status' => 'suspended',
            ]);
        }

        $user->load(['role', 'vendor']);

        return $this->success($user, 'User rejected successfully');
    }
}
