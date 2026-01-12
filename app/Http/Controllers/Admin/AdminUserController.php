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
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve users. Please try again.',
                'USERS_RETRIEVAL_ERROR',
                500
            );
        }
    }

    /**
     * View user details
     */
    public function show(User $user)
    {
        try {
            $user->load(['role', 'vendor']);

            return $this->success($user, 'User details retrieved successfully');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve user details. Please try again.',
                'USER_RETRIEVAL_ERROR',
                500
            );
        }
    }

    /**
     * Approve a user
     */
    public function approve(Request $request, User $user)
    {
        try {
            if ($user->approval_status === 'approved') {
                return $this->error(
                    'This user is already approved.',
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
        } catch (\Exception $e) {
            return $this->error(
                'Failed to approve user. Please try again.',
                'USER_APPROVAL_ERROR',
                500
            );
        }
    }

    /**
     * Reject a user
     */
    public function reject(Request $request, User $user)
    {
        try {
            $request->validate([
                'reason' => 'nullable|string|max:500',
            ], [
                'reason.max' => 'Rejection reason cannot exceed 500 characters.',
            ]);

            if ($user->approval_status === 'rejected') {
                return $this->error(
                    'This user is already rejected.',
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to reject user. Please try again.',
                'USER_REJECTION_ERROR',
                500
            );
        }
    }

    /**
     * Create a new customer (admin only)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
                'approval_status' => ['nullable', 'string', 'in:pending,approved'],
            ], [
                'name.required' => 'Customer name is required.',
                'name.max' => 'Customer name cannot exceed 255 characters.',
                'email.required' => 'Email address is required.',
                'email.email' => 'Please provide a valid email address.',
                'email.unique' => 'This email address is already registered. Please use a different email.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters long.',
                'approval_status.in' => 'Approval status must be either pending or approved.',
            ]);

            // Get the customer role
            $role = \App\Models\Role::where('name', 'customer')->first();
            
            if (!$role) {
                return $this->error(
                    'Customer role not found. Please contact system administrator.',
                    'ROLE_NOT_FOUND',
                    500
                );
            }

            // Create user with customer role
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
                'role_id' => $role->id,
                'approval_status' => $validated['approval_status'] ?? 'approved',
                'approved_at' => $validated['approval_status'] === 'approved' ? now() : null,
                'approved_by' => $validated['approval_status'] === 'approved' ? $request->user()->id : null,
            ]);

            $user->load(['role']);

            return $this->success($user, 'Customer created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed. Please check your input.');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to create customer. Please try again.',
                'CUSTOMER_CREATION_ERROR',
                500
            );
        }
    }
}
